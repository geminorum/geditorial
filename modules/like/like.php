<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;

class Like extends gEditorial\Module
{

	public $meta_key   = '_ge_like';
	protected $cookie  = 'geditorial-like';
	protected $post_id = FALSE;

	public static function module()
	{
		return [
			'name'  => 'like',
			'title' => _x( 'Like', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Like Button for Posts and Comments', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'heart',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'avatars',
					'title'       => _x( 'Avatars', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display avatars next to the like button', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'comments',
					'title'       => _x( 'Comments', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Also display button for comments of enabled post types', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'adminbar_summary',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	public function setup( $args = [] )
	{
		parent::setup();

		if ( ! is_admin() )
			$this->action( 'template_redirect' );
	}

	public function init()
	{
		parent::init();

		$this->cookie = 'geditorial-like-'.get_current_blog_id();
	}

	public function init_ajax()
	{
		$this->_hook_ajax( TRUE );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->post_types() ) )
			return;

		$this->post_id = get_queried_object_id();

		$this->enqueue_asset_js();
		$this->enqueue_styles();
	}

	public function get_button( $post_id = NULL )
	{
		if ( is_null( $post_id ) )
			$post_id = $this->post_id;

		$avatars = $this->get_setting( 'avatars', FALSE );

		$title = $this->filters( 'loading', _x( 'Loading &hellip;', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ), $post_id );

		$html  = '<div class="geditorial-wrap like" style="display:none;" data-avatars="'.( $avatars ? 'true' : 'false' ).'">';
		$html .= '<div><a class="like loading" title="'.esc_attr( $title ).'" href="#" data-id="'.$post_id.'">';

		$html .= $this->filters( 'icon', '<span class="genericon genericon-heart"></span>', $post_id );

		$html .= '</a></div><div><span class="like"></span></div>';

		if ( $avatars )
			$html .= '<div><ul class="like"></ul></div>';

		$html .= '</div>';

		return $html;
	}

	public function adminbar_init( $wp_admin_bar, $parent, $link )
	{
		if ( ! $this->post_id )
			return;

		if ( is_admin() || ! is_singular( $this->post_types() ) )
			return;

		if ( ! $this->cuc( 'adminbar' ) )
			return;

		$users  = $this->get_postmeta( $this->post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $this->post_id, FALSE, [], $this->meta_key.'_guests' );

		if ( count( $users ) ) {

			$cap = current_user_can( 'edit_users' );

			$wp_admin_bar->add_node( [
				'id'     => $this->classs( 'users' ),
				'title'  => sprintf( _x( 'Like Summary: Users %s', 'Modules: Like: Adminbar', GEDITORIAL_TEXTDOMAIN ),
					'(<span class="count">'.Number::format( count( $users ) ).'<span>)' ),
				'parent' => $parent,
				'href'   => Settings::subURL( $this->key, 'reports' ),
			] );

			foreach ( $users as $timestamp => $user_id )
				$wp_admin_bar->add_node( [
					'id'     => $this->classs( 'user', $user_id ),
					'title'  => Helper::humanTimeDiffRound( intval( $timestamp ) ).' &ndash; '.get_the_author_meta( 'display_name', $user_id ),
					'parent' => $this->classs( 'users' ),
					'href'   => $cap ? WordPress::getUserEditLink( $user_id ) : FALSE,
					'meta'   => [
						'title' => Helper::humanTimeAgo( intval( $timestamp ), current_time( 'timestamp', FALSE ) ),
					],
				] );
		}

		if ( count( $guests ) ) {

			$wp_admin_bar->add_node( [
				'id'     => $this->classs( 'guests' ),
				'title'  => sprintf( _x( 'Like Summary: Guests %s', 'Modules: Like: Adminbar', GEDITORIAL_TEXTDOMAIN ),
					'(<span class="count">'.Number::format( count( $guests ) ).'<span>)' ),
				'parent' => $parent,
				'href'   => Settings::subURL( $this->key, 'reports' ),
			] );

			foreach ( $guests as $timestamp => $ip )
				$wp_admin_bar->add_node( [
					'id'     => $this->classs( 'guest', $timestamp ),
					'title'  => Helper::humanTimeDiffRound( intval( $timestamp ) ).' &ndash; '.$ip,
					'parent' => $this->classs( 'guests' ),
					'href'   => sprintf( 'http://freegeoip.net/?q=%s', $ip ),
					'meta'   => [
						'title' => Helper::humanTimeAgo( intval( $timestamp ), current_time( 'timestamp', FALSE ) ),
					],
				] );
		}
	}

	public function ajax()
	{
		$post = wp_unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		switch ( $what ) {

			default:
			case 'check':

				list( $check, $count ) = $this->check( $post['id'] );

				wp_send_json_success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => $check ? 'unlike' : 'dolike',
					'remove'  => 'loading',
					'add'     => $check ? 'unlike' : 'dolike',
					'nonce'   => wp_create_nonce( 'geditorial_like_ajax-'.$post['id'] ),
					'count'   => Number::format( $count ),
					'avatars' => $this->get_setting( 'avatars', FALSE ) ? $this->avatars( $post['id'] ) : NULL,
				] );

			break;
			case 'dolike':

				Ajax::checkReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->like( $post['id'] );

				wp_send_json_success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'unlike',
					'remove'  => 'dolike',
					'add'     => 'unlike',
					'count'   => Number::format( $count ),
					'avatars' => $this->get_setting( 'avatars', FALSE ) ? $this->avatars( $post['id'] ) : NULL,
				] );

			break;
			case 'unlike':

				Ajax::checkReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->unlike( $post['id'] );

				wp_send_json_success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'dolike',
					'remove'  => 'unlike',
					'add'     => 'dolike',
					'count'   => Number::format( $count ),
					'avatars' => $this->get_setting( 'avatars', FALSE ) ? $this->avatars( $post['id'] ) : NULL,
				] );
		}

		Ajax::errorWhat();
	}

	public function title( $liked, $post_id = NULL )
	{
		return $this->filters( 'title', ( $liked ? _x( 'Unlike', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ) : _x( 'Like', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ) ), $liked, $post_id );
	}

	public function unlike( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		$count  = count( $users ) + count( $guests );

		if ( is_user_logged_in() ) {
			$key = array_search( get_current_user_id(), $users );
			if ( FALSE !== $key ) {
				unset( $users[$key] );
				$this->set_meta( $post_id, $users, '_users' );
				$count--;
			}
		}

		$cookie = $this->get_cookie();
		$key    = array_key_exists( $post_id, $cookie );
		if ( $key ) {

			$timestamp = array_search( $cookie[$post_id], $guests );
			if ( $timestamp ) {
				unset( $guests[$timestamp] );
				$this->set_meta( $post_id, $guests, '_guests' );
				$count--;
			}

			unset( $cookie[$post_id] );
			$this->set_cookie( $cookie, FALSE );

		}

		return [ FALSE, $count ];
	}

	public function like( $post_id )
	{
		$users     = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests    = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		$count     = count( $users ) + count( $guests );
		$timestamp = current_time( 'timestamp' );

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! array_search( $user_id, $users ) ) {
				$users[$timestamp] = $user_id;
				$this->set_meta( $post_id, $users, '_users' );
				$count++;
			}
			return [ TRUE, $count ];
		} else {
			$cookie = $this->get_cookie();
			if ( ! array_key_exists( $post_id, $cookie ) ) {
				$guests[$timestamp] = HTTP::IP();
				$this->set_meta( $post_id, $guests, '_guests' );
				$this->set_cookie( [ $post_id => $guests[$timestamp] ] );
				$count++;
			}
			return [ TRUE, $count ];
		}
	}

	public function check( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		$count  = count( $users ) + count( $guests );

		if ( is_user_logged_in() ) {
			return [ array_search( get_current_user_id(), $users ), $count ];
		} else {
			$cookie = $this->get_cookie();
			return [ array_key_exists( $post_id, $cookie ), $count ];
		}
	}

	public function avatars( $post_id )
	{
		$html = '';

		if ( ! $this->get_setting( 'avatars', FALSE ) )
			return $html;

		$users = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );

		if ( count( $users ) ) {

			$query = new \WP_User_Query( [
				'include' => array_values( $users ),
				'fields'  => [ 'user_email', 'ID', 'display_name' ],
			] );

			if ( ! empty( $query->results ) ) {
				foreach ( $query->results as $user ) {
					if ( function_exists( 'bp_core_get_userlink' ) ) {
						$html .= '<li><a href="'.bp_core_get_user_domain( $user->ID ).'" title="'.bp_core_get_user_displayname( $user->ID ).'">'.get_avatar( $user->user_email, 40, '', 'avatar' ).'</a></li>';
					} else {
						$html .= '<li><a title="'.esc_attr( $user->display_name ).'" >'.get_avatar( $user->user_email, 40, '', 'avatar' ).'</a></li>';
					}
				}
			}
		}

		return $html;
	}
}

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

	public $meta_key = '_ge_like';

	protected $disable_no_posttypes = TRUE;

	protected $cookie  = 'geditorial-like';
	protected $post_id = FALSE;

	public static function module()
	{
		return [
			'name'  => 'like',
			'title' => _x( 'Like', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Like Button for Guests and Users', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'heart',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'adminbar_summary',
				[
					'field'       => 'display_avatars',
					'title'       => _x( 'Display Avatars', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays avatars next to the like button.', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'max_avatars',
					'type'        => 'number',
					'title'       => _x( 'Max Avatars', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Maximum number of avatars to display.', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 12,
				],
			],
			'_strings' => [
				[
					'field'       => 'string_loading',
					'type'        => 'text',
					'title'       => _x( 'Loading Title', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Title attribute of the like button.', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Loading &hellip;', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'string_like',
					'type'        => 'text',
					'title'       => _x( 'Like Title', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Title attribute of the like button.', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Like', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'string_unlike',
					'type'        => 'text',
					'title'       => _x( 'Unlike Title', 'Modules: Like: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Title attribute of the like button.', 'Modules: Like: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Unlike', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->cookie = $this->classs( get_current_blog_id() );
	}

	public function init_ajax()
	{
		$this->_hook_ajax( TRUE );
	}

	public function template_redirect()
	{
		if ( is_embed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->post_id = get_queried_object_id();

		$this->enqueue_asset_js();
		$this->enqueue_styles();
	}

	public function get_button( $post_id = NULL )
	{
		if ( is_null( $post_id ) )
			$post_id = $this->post_id;

		if ( ! $post_id )
			return FALSE;

		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		if ( ! in_array( $post->post_type, $this->posttypes() ) )
			return FALSE;

		$avatars = $this->get_setting( 'display_avatars', FALSE );

		$title = $this->get_setting( 'string_loading', _x( 'Loading &hellip;', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ) );
		$title = $this->filters( 'loading', $title, $post );

		$html  = '<div class="geditorial-wrap -like" style="display:none;" data-avatars="'.( $avatars ? 'true' : 'false' ).'">';
		$html.= '<div><a class="like loading" title="'.HTML::escape( $title ).'" href="#" data-id="'.$post->ID.'">';

		// $html.= $this->filters( 'icon', '<span class="genericon genericon-heart"></span>', $post->ID );
		$html.= $this->icon( 'heart', 'old' );

		$html.= '</a></div><div><span class="like"></span></div>';

		if ( $avatars )
			$html.= '<div><ul class="like"></ul></div>';

		$html.= '</div>';

		return $html;
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $this->post_id )
			return;

		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		if ( ! current_user_can( 'edit_post', $this->post_id ) )
			return;

		$users  = $this->get_postmeta( $this->post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $this->post_id, FALSE, [], $this->meta_key.'_guests' );

		if ( count( $users ) ) {

			$cap = current_user_can( 'edit_users' );

			$nodes[] = [
				'id'     => $this->classs( 'users' ),
				'title'  => Helper::getCounted( count( $users ), _x( 'Like Summary: Users %s', 'Modules: Like: Adminbar', GEDITORIAL_TEXTDOMAIN ) ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			foreach ( $users as $timestamp => $user_id )
				$nodes[] = [
					'id'     => $this->classs( 'user', $user_id ),
					'title'  => Helper::humanTimeDiffRound( intval( $timestamp ) ).' &ndash; '.get_the_author_meta( 'display_name', $user_id ),
					'parent' => $this->classs( 'users' ),
					'href'   => $cap ? WordPress::getUserEditLink( $user_id ) : FALSE,
					'meta'   => [ 'title' => Helper::humanTimeAgo( intval( $timestamp ), current_time( 'timestamp', FALSE ) ) ],
				];
		}

		if ( count( $guests ) ) {

			$nodes[] = [
				'id'     => $this->classs( 'guests' ),
				'title'  => Helper::getCounted( count( $guests ), _x( 'Like Summary: Guests %s', 'Modules: Like: Adminbar', GEDITORIAL_TEXTDOMAIN ) ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			foreach ( $guests as $timestamp => $ip )
				$nodes[] = [
					'id'     => $this->classs( 'guest', $timestamp ),
					'title'  => Helper::humanTimeDiffRound( intval( $timestamp ) ).' &ndash; '.$ip,
					'parent' => $this->classs( 'guests' ),
					'href'   => sprintf( 'https://redirect.li/map/?ip=%s', $ip ),
					'meta'   => [ 'title' => Helper::humanTimeAgo( intval( $timestamp ), current_time( 'timestamp', FALSE ) ) ],
				];
		}
	}

	public function ajax()
	{
		$post = self::unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		switch ( $what ) {

			default:
			case 'check':

				list( $check, $count ) = $this->check( $post['id'] );

				Ajax::success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => $check ? 'unlike' : 'dolike',
					'remove'  => 'loading',
					'add'     => $check ? 'unlike' : 'dolike',
					'nonce'   => wp_create_nonce( 'geditorial_like_ajax-'.$post['id'] ),
					'count'   => Number::format( $count ),
					'avatars' => $this->avatars( $post['id'] ),
				] );

			break;
			case 'dolike':

				Ajax::checkReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->like( $post['id'] );

				Ajax::success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'unlike',
					'remove'  => 'dolike',
					'add'     => 'unlike',
					'count'   => Number::format( $count ),
					'avatars' => $this->avatars( $post['id'] ),
				] );

			break;
			case 'unlike':

				Ajax::checkReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->unlike( $post['id'] );

				Ajax::success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'dolike',
					'remove'  => 'unlike',
					'add'     => 'dolike',
					'count'   => Number::format( $count ),
					'avatars' => $this->avatars( $post['id'] ),
				] );
		}

		Ajax::errorWhat();
	}

	public function title( $liked, $post_id = NULL )
	{
		if ( $liked )
			$title = $this->get_setting( 'string_unlike', _x( 'Unlike', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ) );
		else
			$title = $this->get_setting( 'string_like', _x( 'Like', 'Modules: Like', GEDITORIAL_TEXTDOMAIN ) );

		return $this->filters( 'title', $title, $liked, $post_id );
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
			$ip     = HTTP::IP();

			if ( ! array_key_exists( $post_id, $cookie )
				&& ! array_search( $ip, $guests ) ) {

				$guests[$timestamp] = $ip;
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

		if ( ! $this->get_setting( 'display_avatars', FALSE ) )
			return $html;

		$users = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );

		if ( count( $users ) ) {

			$query = new \WP_User_Query( [
				'blog_id' => 0,
				'include' => array_values( $users ),
				'orderby' => 'post_count',
				'number'  => $this->get_setting( 'max_avatars', 12 ),
				'fields'  => [ 'user_email', 'ID', 'display_name' ],
			] );

			if ( ! empty( $query->results ) ) {
				foreach ( $query->results as $user ) {
					if ( function_exists( 'bp_core_get_userlink' ) ) {
						$html.= '<li><a href="'.bp_core_get_user_domain( $user->ID ).'" title="'.bp_core_get_user_displayname( $user->ID ).'">'.get_avatar( $user->user_email, 40, '', 'avatar' ).'</a></li>';
					} else {
						$html.= '<li><a title="'.HTML::escape( $user->display_name ).'" >'.get_avatar( $user->user_email, 40, '', 'avatar' ).'</a></li>';
					}
				}
			}
		}

		return $html;
	}
}

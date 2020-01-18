<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Datetime;
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
			'title' => _x( 'Like', 'Modules: Like', 'geditorial' ),
			'desc'  => _x( 'Like Button for Guests and Users', 'Modules: Like', 'geditorial' ),
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
					'title'       => _x( 'Display Avatars', 'Modules: Like: Setting Title', 'geditorial' ),
					'description' => _x( 'Displays avatars next to the like button.', 'Modules: Like: Setting Description', 'geditorial' ),
				],
				[
					'field'       => 'max_avatars',
					'type'        => 'number',
					'title'       => _x( 'Max Avatars', 'Modules: Like: Setting Title', 'geditorial' ),
					'description' => _x( 'Maximum number of avatars to display.', 'Modules: Like: Setting Description', 'geditorial' ),
					'default'     => 12,
				],
			],
			'_editlist' => [
				[
					'field'       => 'like_count',
					'title'       => _x( 'Like Count', 'Modules: Like: Setting Title', 'geditorial' ),
					'description' => _x( 'Displays likes summary of the post.', 'Modules: Like: Setting Description', 'geditorial' ),
				],
			],
			'_strings' => [
				[
					'field'       => 'string_loading',
					'type'        => 'text',
					'title'       => _x( 'Loading Title', 'Modules: Like: Setting Title', 'geditorial' ),
					'description' => _x( 'Title attribute of the like button.', 'Modules: Like: Setting Description', 'geditorial' ),
					'default'     => _x( 'Loading &hellip;', 'Modules: Like', 'geditorial' ),
				],
				[
					'field'       => 'string_like',
					'type'        => 'text',
					'title'       => _x( 'Like Title', 'Modules: Like: Setting Title', 'geditorial' ),
					'description' => _x( 'Title attribute of the like button.', 'Modules: Like: Setting Description', 'geditorial' ),
					'default'     => _x( 'Like', 'Modules: Like', 'geditorial' ),
				],
				[
					'field'       => 'string_unlike',
					'type'        => 'text',
					'title'       => _x( 'Unlike Title', 'Modules: Like: Setting Title', 'geditorial' ),
					'description' => _x( 'Title attribute of the like button.', 'Modules: Like: Setting Description', 'geditorial' ),
					'default'     => _x( 'Unlike', 'Modules: Like', 'geditorial' ),
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
		$this->_hook_ajax( NULL );
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& in_array( $screen->post_type, $this->posttypes() ) ) {

			if ( $this->get_setting( 'like_count' ) )
				$this->action_module( 'tweaks', 'column_attr', 1, 50 );
		}
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

		$title = $this->get_setting( 'string_loading', _x( 'Loading &hellip;', 'Modules: Like', 'geditorial' ) );
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
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$users  = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		// $total  = $this->get_postmeta( $post_id, FALSE, 0, $this->meta_key.'_total' ); // FIXME: display total

		if ( count( $users ) ) {

			$cap = current_user_can( 'edit_users' );

			$nodes[] = [
				'id'     => $this->classs( 'users' ),
				/* translators: %s: count placeholder */
				'title'  => Helper::getCounted( count( $users ), _x( 'Like Summary: Users %s', 'Modules: Like: Adminbar', 'geditorial' ) ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			foreach ( $users as $timestamp => $user_id )
				$nodes[] = [
					'id'     => $this->classs( 'user', $user_id ),
					'title'  => Datetime::humanTimeDiffRound( intval( $timestamp ) ).' &ndash; '.get_the_author_meta( 'display_name', $user_id ),
					'parent' => $this->classs( 'users' ),
					'href'   => $cap ? WordPress::getUserEditLink( $user_id ) : FALSE,
					'meta'   => [ 'title' => Datetime::humanTimeAgo( intval( $timestamp ), current_time( 'timestamp', FALSE ) ) ],
				];
		}

		if ( count( $guests ) ) {

			$nodes[] = [
				'id'     => $this->classs( 'guests' ),
				/* translators: %s: count placeholder */
				'title'  => Helper::getCounted( count( $guests ), _x( 'Like Summary: Guests %s', 'Modules: Like: Adminbar', 'geditorial' ) ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			foreach ( $guests as $timestamp => $ip )
				$nodes[] = [
					'id'     => $this->classs( 'guest', $timestamp ),
					'title'  => Datetime::humanTimeDiffRound( intval( $timestamp ) ).' &ndash; '.$ip,
					'parent' => $this->classs( 'guests' ),
					'href'   => sprintf( 'https://redirect.li/map/?ip=%s', $ip ),
					'meta'   => [ 'title' => Datetime::humanTimeAgo( intval( $timestamp ), current_time( 'timestamp', FALSE ) ) ],
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
					'avatars' => $this->get_setting( 'display_avatars' ) ? $this->avatars( $post['id'] ) : '',
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
					'avatars' => $this->get_setting( 'display_avatars' ) ? $this->avatars( $post['id'] ) : '',
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
					'avatars' => $this->get_setting( 'display_avatars' ) ? $this->avatars( $post['id'] ) : '',
				] );
		}

		Ajax::errorWhat();
	}

	public function title( $liked, $post_id = NULL )
	{
		if ( $liked )
			$title = $this->get_setting( 'string_unlike', _x( 'Unlike', 'Modules: Like', 'geditorial' ) );
		else
			$title = $this->get_setting( 'string_like', _x( 'Like', 'Modules: Like', 'geditorial' ) );

		return $this->filters( 'title', $title, $liked, $post_id );
	}

	public function unlike( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		$total  = count( $users ) + count( $guests );
		$cookie = $this->get_cookie();

		if ( is_user_logged_in() ) {

			$key = array_search( get_current_user_id(), $users );

			if ( FALSE !== $key ) {

				$total--;
				unset( $users[$key] );

				$this->set_meta( $post_id, $users, '_users' );
				$this->set_meta( $post_id, $total, '_total' );
			}
		}

		if ( array_key_exists( $post_id, $cookie ) ) {

			if ( $timestamp = array_search( $cookie[$post_id], $guests ) ) {

				$total--;
				unset( $guests[$timestamp] );

				$this->set_meta( $post_id, $guests, '_guests' );
				$this->set_meta( $post_id, $total, '_total' );
			}

			unset( $cookie[$post_id] );

			$this->set_cookie( $cookie, FALSE );
		}

		return [ FALSE, $total ];
	}

	public function like( $post_id )
	{
		$users     = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests    = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		$total     = count( $users ) + count( $guests );
		$timestamp = current_time( 'timestamp' );

		if ( is_user_logged_in() ) {

			$user_id = get_current_user_id();

			if ( ! array_search( $user_id, $users ) ) {

				$total++;
				$users[$timestamp] = $user_id;

				$this->set_meta( $post_id, $users, '_users' );
				$this->set_meta( $post_id, $total, '_total' );
			}

			return [ TRUE, $total ];

		} else {

			$cookie = $this->get_cookie();
			$ip     = HTTP::IP();

			if ( ! array_key_exists( $post_id, $cookie )
				&& ! array_search( $ip, $guests ) ) {

				$total++;
				$guests[$timestamp] = $ip;

				$this->set_meta( $post_id, $guests, '_guests' );
				$this->set_meta( $post_id, $total, '_total' );
				$this->set_cookie( [ $post_id => $guests[$timestamp] ] );
			}

			return [ TRUE, $total ];
		}
	}

	public function check( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_guests' );
		$total  = count( $users ) + count( $guests );

		return is_user_logged_in()
			? [ array_search( get_current_user_id(), $users ), $total ]
			: [ array_key_exists( $post_id, $this->get_cookie() ), $total ];
	}

	public function avatars( $post_id )
	{
		$html  = '';
		$users = $this->get_postmeta( $post_id, FALSE, [], $this->meta_key.'_users' );

		if ( count( $users ) ) {

			$query = new \WP_User_Query( [
				'blog_id' => 0,
				'include' => array_values( $users ),
				'orderby' => 'post_count',
				'number'  => $this->get_setting( 'max_avatars', 12 ),
				'fields'  => [ 'user_email', 'ID', 'display_name' ],

				'count_total' => FALSE,
			] );

			if ( ! empty( $query->results ) ) {
				foreach ( $query->results as $user ) {

					// FIXME: needs internal api
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

	public function tweaks_column_attr( $post )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$total = $this->get_postmeta( $post->ID, FALSE, 0, $this->meta_key.'_total' );

		if ( empty( $total ) )
			return;

		echo '<li class="-row tweaks-like-count">';

			echo $this->get_column_icon( FALSE, 'heart', _x( 'Likes', 'Modules: Like: Row Icon Title', 'geditorial' ) );

			/* translators: %s: likes count */
			printf( _nx( '%s Like', '%s Likes', $total, 'Modules: Like', 'geditorial' ), Number::format( $total ) );

			$list   = [];
			$users  = $this->get_postmeta( $post->ID, FALSE, [], $this->meta_key.'_users' );
			$guests = $this->get_postmeta( $post->ID, FALSE, [], $this->meta_key.'_guests' );

			if ( ! empty( $users ) )
				/* translators: %s: users count */
				$list[] = sprintf( _nx( '%s User', '%s Users', count( $users ), 'Modules: Like', 'geditorial' ), Number::format( count( $users ) ) );

			if ( ! empty( $guests ) )
				/* translators: %s: guests count */
				$list[] = sprintf( _nx( '%s Guest', '%s Guests', count( $guests ), 'Modules: Like', 'geditorial' ), Number::format( count( $guests ) ) );

			echo Helper::getJoined( $list, ' <span class="-like-counts">(', ')</span>' );

		echo '</li>';
	}
}

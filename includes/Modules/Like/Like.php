<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;

class Like extends gEditorial\Module
{

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
					'title'       => _x( 'Display Avatars', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Displays avatars next to the like button.', 'Setting Description', 'geditorial-like' ),
				],
				[
					'field'       => 'max_avatars',
					'type'        => 'number',
					'title'       => _x( 'Max Avatars', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Maximum number of avatars to display.', 'Setting Description', 'geditorial-like' ),
					'default'     => 12,
				],
			],
			'_editlist' => [
				[
					'field'       => 'like_count',
					'title'       => _x( 'Like Count', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Displays likes summary of the post.', 'Setting Description', 'geditorial-like' ),
				],
			],
			'_strings' => [
				[
					'field'       => 'string_loading',
					'type'        => 'text',
					'title'       => _x( 'Loading Title', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Title attribute of the like button.', 'Setting Description', 'geditorial-like' ),
					'default'     => _x( 'Loading &hellip;', 'Setting Default', 'geditorial-like' ),
				],
				[
					'field'       => 'string_like',
					'type'        => 'text',
					'title'       => _x( 'Like Title', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Title attribute of the like button.', 'Setting Description', 'geditorial-like' ),
					'default'     => _x( 'Like', 'Setting Default', 'geditorial-like' ),
				],
				[
					'field'       => 'string_unlike',
					'type'        => 'text',
					'title'       => _x( 'Unlike Title', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Title attribute of the like button.', 'Setting Description', 'geditorial-like' ),
					'default'     => _x( 'Unlike', 'Setting Default', 'geditorial-like' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_liked_users'  => '_ge_like_users',
			'metakey_liked_guests' => '_ge_like_guests',
			'metakey_liked_total'  => '_ge_like_total',
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
			&& $this->posttype_supported( $screen->post_type ) ) {

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

		$title = $this->get_setting( 'string_loading', _x( 'Loading &hellip;', 'Setting Default', 'geditorial-like' ) );
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

		$users  = $this->get_liked_users( $post_id );
		$guests = $this->get_liked_guests( $post_id );
		// $total  = $this->get_liked_total( $post_id ); // FIXME: display total

		if ( count( $users ) ) {

			$cap = current_user_can( 'edit_users' );

			$nodes[] = [
				'id'     => $this->classs( 'users' ),
				/* translators: %s: count placeholder */
				'title'  => Helper::getCounted( count( $users ), _x( 'Like Summary: Users %s', 'Adminbar', 'geditorial-like' ) ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			foreach ( $users as $timestamp => $user_id )
				$nodes[] = [
					'id'     => $this->classs( 'user', $user_id ),
					'title'  => Datetime::humanTimeDiffRound( (int) $timestamp ).' &ndash; '.get_the_author_meta( 'display_name', $user_id ),
					'parent' => $this->classs( 'users' ),
					'href'   => $cap ? WordPress::getUserEditLink( $user_id ) : FALSE,
					'meta'   => [ 'title' => Datetime::humanTimeAgo( (int) $timestamp, current_time( 'timestamp', FALSE ) ) ],
				];
		}

		if ( count( $guests ) ) {

			$nodes[] = [
				'id'     => $this->classs( 'guests' ),
				/* translators: %s: count placeholder */
				'title'  => Helper::getCounted( count( $guests ), _x( 'Like Summary: Guests %s', 'Adminbar', 'geditorial-like' ) ),
				'parent' => $parent,
				'href'   => $this->get_module_url(),
			];

			foreach ( $guests as $timestamp => $ip )
				$nodes[] = [
					'id'     => $this->classs( 'guest', $timestamp ),
					'title'  => Datetime::humanTimeDiffRound( (int) $timestamp ).' &ndash; '.$ip,
					'parent' => $this->classs( 'guests' ),
					'href'   => sprintf( 'https://redirect.li/map/?ip=%s', $ip ),
					'meta'   => [ 'title' => Datetime::humanTimeAgo( (int) $timestamp, current_time( 'timestamp', FALSE ) ) ],
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
			$title = $this->get_setting( 'string_unlike', _x( 'Unlike', 'Setting Default', 'geditorial-like' ) );
		else
			$title = $this->get_setting( 'string_like', _x( 'Like', 'Setting Default', 'geditorial-like' ) );

		return $this->filters( 'title', $title, $liked, $post_id );
	}

	public function unlike( $post_id )
	{
		$users  = $this->get_liked_users( $post_id );
		$guests = $this->get_liked_guests( $post_id );
		$total  = count( $users ) + count( $guests );
		$cookie = $this->get_cookie();

		if ( is_user_logged_in() ) {

			$key = array_search( get_current_user_id(), $users );

			if ( FALSE !== $key ) {

				$total--;
				unset( $users[$key] );

				$this->set_liked_users( $post_id, $users );
				$this->set_liked_total( $post_id, $total );
			}
		}

		if ( array_key_exists( $post_id, $cookie ) ) {

			if ( $timestamp = array_search( $cookie[$post_id], $guests ) ) {

				$total--;
				unset( $guests[$timestamp] );

				$this->set_liked_guests( $post_id, $guests );
				$this->set_liked_total( $post_id, $total );
			}

			unset( $cookie[$post_id] );

			$this->set_cookie( $cookie, FALSE );
		}

		return [ FALSE, $total ];
	}

	public function like( $post_id )
	{
		$users  = $this->get_liked_users( $post_id );
		$guests = $this->get_liked_guests( $post_id );
		$total  = count( $users ) + count( $guests );
		$time   = current_time( 'timestamp' );

		if ( is_user_logged_in() ) {

			$user_id = get_current_user_id();

			if ( ! array_search( $user_id, $users ) ) {

				$total++;
				$users[$time] = $user_id;

				$this->set_liked_users( $post_id, $users );
				$this->set_liked_total( $post_id, $total );
			}

			return [ TRUE, $total ];

		} else {

			$cookie = $this->get_cookie();
			$ip     = HTTP::IP();

			if ( ! array_key_exists( $post_id, $cookie )
				&& ! array_search( $ip, $guests ) ) {

				$total++;
				$guests[$time] = $ip;

				$this->set_liked_guests( $post_id, $guests );
				$this->set_liked_total( $post_id, $total );
				$this->set_cookie( [ $post_id => $guests[$time] ] );
			}

			return [ TRUE, $total ];
		}
	}

	public function check( $post_id )
	{
		$users  = $this->get_liked_users( $post_id );
		$guests = $this->get_liked_guests( $post_id );
		$total  = count( $users ) + count( $guests );

		return is_user_logged_in()
			? [ array_search( get_current_user_id(), $users ), $total ]
			: [ array_key_exists( $post_id, $this->get_cookie() ), $total ];
	}

	public function avatars( $post_id )
	{
		$html  = '';
		$users = $this->get_liked_users( $post_id );

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

	private function sync_counts( $post_id )
	{
		$users  = $this->get_liked_users( $post_id );
		$guests = $this->get_liked_guests( $post_id );
		$total  = count( $users ) + count( $guests );

		$this->set_liked_total( $post_id, $total );

		return $total;
	}

	private function get_liked_users( $post_id )
	{
		return $this->fetch_postmeta( $post_id, [], $this->constant( 'metakey_liked_users' ) );
	}

	private function get_liked_guests( $post_id )
	{
		return $this->fetch_postmeta( $post_id, [], $this->constant( 'metakey_liked_guests' ) );
	}

	private function get_liked_total( $post_id )
	{
		return $this->fetch_postmeta( $post_id, 0, $this->constant( 'metakey_liked_total' ) );
	}

	private function set_liked_users( $post_id, $data )
	{
		return $this->store_postmeta( $post_id, $data, $this->constant( 'metakey_liked_users' ) );
	}

	private function set_liked_guests( $post_id, $data )
	{
		return $this->store_postmeta( $post_id, $data, $this->constant( 'metakey_liked_guests' ) );
	}

	private function set_liked_total( $post_id, $data )
	{
		return $this->store_postmeta( $post_id, $data, $this->constant( 'metakey_liked_total' ) );
	}

	public function tweaks_column_attr( $post )
	{
		if ( ! current_user_can( 'read_post', $post->ID ) )
			return;

		$total = $this->get_liked_total( $post->ID );

		if ( empty( $total ) )
			return;

		echo '<li class="-row tweaks-like-count">';

			echo $this->get_column_icon( FALSE, 'heart', _x( 'Likes', 'Row Icon Title', 'geditorial-like' ) );

			/* translators: %s: likes count */
			printf( _nx( '%s Like', '%s Likes', $total, 'Noop', 'geditorial-like' ), Number::format( $total ) );

			$list   = [];
			$users  = $this->get_liked_users( $post->ID );
			$guests = $this->get_liked_guests( $post->ID );

			if ( ! empty( $users ) )
				/* translators: %s: users count */
				$list[] = sprintf( _nx( '%s User', '%s Users', count( $users ), 'Noop', 'geditorial-like' ), Number::format( count( $users ) ) );

			if ( ! empty( $guests ) )
				/* translators: %s: guests count */
				$list[] = sprintf( _nx( '%s Guest', '%s Guests', count( $guests ), 'Noop', 'geditorial-like' ), Number::format( count( $guests ) ) );

			echo Helper::getJoined( $list, ' <span class="-like-counts">(', ')</span>' );

		echo '</li>';
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( Tablelist::isAction( 'sync_counts_all' ) ) {

					$count = 0;
					$query = new \WP_Query;

					$posts = $query->query( [
						'fields'                 => 'ids',
						'post_type'              => $this->posttypes(),
						'post_status'            => 'any',
						'posts_per_page'         => -1,
						'suppress_filters'       => TRUE,
						'update_post_meta_cache' => FALSE,
						'update_post_term_cache' => FALSE,
						'lazy_load_term_meta'    => FALSE,
					] );

					foreach ( $posts as $post_id ) {
						$this->sync_counts( $post_id );
						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'sync_counts', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						$this->sync_counts( $post_id );
						$count++;
					}

					WordPress::redirectReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );
				}
			}

			$this->add_sub_screen_option( $sub );
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list  = $this->list_posttypes();
		$query = [
			'meta_key' => $this->meta_key.'_total',
			'orderby'  => 'meta_value_num',
			'order'    => 'DESC',
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], array_keys( $list ), $this->get_sub_limit_option( $sub ) );

		$pagination['actions']['sync_counts']     = _x( 'Sync Counts', 'Table Action', 'geditorial-like' );
		$pagination['actions']['sync_counts_all'] = _x( 'Sync All Counts', 'Table Action', 'geditorial-like' );

		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'total' => [
				'title'    => _x( 'Total', 'Table Column', 'geditorial-like' ),
				'callback' => function( $value, $row, $column, $index ){
					return Helper::htmlCount( $this->get_liked_total( $row->ID ) );
				},
			],
			'guests' => [
				'title'    => _x( 'Guests', 'Table Column', 'geditorial-like' ),
				'callback' => function( $value, $row, $column, $index ){
					return Helper::htmlCount( $this->get_liked_guests( $row->ID ) );
				},
			],
			'users' => [
				'title'    => _x( 'Users', 'Table Column', 'geditorial-like' ),
				'callback' => function( $value, $row, $column, $index ){
					return Helper::htmlCount( $this->get_liked_users( $row->ID ) );
				},
			],
			'avatars' => [
				'title'    => _x( 'Avatars', 'Table Column', 'geditorial-like' ),
				'callback' => function( $value, $row, $column, $index ){
					$html = $this->avatars( $row->ID );
					return $html ? HTML::tag( 'ul', $html ) : Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Post Likes', 'Header', 'geditorial-like' ) ),
			'empty'      => $this->get_posttype_label( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}
}

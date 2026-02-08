<?php namespace geminorum\gEditorial\Modules\Like;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Like extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreCookies;
	use Internals\PostMeta;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'like',
			'title'    => _x( 'Like', 'Modules: Like', 'geditorial-admin' ),
			'desc'     => _x( 'Like Button for Guests and Users', 'Modules: Like', 'geditorial-admin' ),
			'icon'     => 'heart',
			'access'   => 'stable',
			'keywords' => [
				'has-adminbar',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_avatars' => [
				'avatar_support' => [ _x( 'Displays avatars next to the like button.', 'Setting Description', 'geditorial-like' ), TRUE ],
				'buddybress_support',
				[
					'field'       => 'max_avatars',
					'type'        => 'number',
					'title'       => _x( 'Max Avatars', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Maximum number of avatars to display.', 'Setting Description', 'geditorial-like' ),
					'default'     => 12,
				],
			],
			'_roles'           => [
				// 'manage_roles'   => [ NULL, $roles ], // TODO!
				'reports_roles'  => [ NULL, $roles ],
				'reports_post_edit',
			],
			'_editlist' => [
				[
					'field'       => 'like_count',
					'title'       => _x( 'Like Count', 'Setting Title', 'geditorial-like' ),
					'description' => _x( 'Displays likes summary of the post.', 'Setting Description', 'geditorial-like' ),
				],
			],
			'_frontend'         => [
				'adminbar_summary',
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

	public function setup_ajax()
	{
		$this->_hook_ajax( NULL, NULL, 'do_ajax_public' );

		if ( ! $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'like_count' ) )
			$this->coreadmin__hook_tweaks_column_attr( $posttype, 50 );
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			if ( $this->get_setting( 'like_count' ) )
				$this->coreadmin__hook_tweaks_column_attr( $screen->post_type, 50 );
		}
	}

	public function template_redirect()
	{
		if ( is_robots() || is_favicon() || is_feed() )
			return;

		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->current_queried = get_queried_object_id();

		$this->enqueue_asset_js();
		$this->enqueue_styles();
	}

	public function get_button( $post = NULL )
	{
		if ( is_null( $post ) )
			$post = $this->current_queried;

		if ( ! $post )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! in_array( $post->post_type, $this->posttypes() ) )
			return FALSE;

		$avatars = $this->get_setting( 'avatar_support', TRUE );

		$title = $this->get_setting( 'string_loading', _x( 'Loading &hellip;', 'Setting Default', 'geditorial-like' ) );
		$title = $this->filters( 'loading', $title, $post );

		$html  = '<div class="geditorial-wrap -like" style="display:none;" data-avatars="'.( $avatars ? 'true' : 'false' ).'">';
		$html.= '<div><a class="like loading" title="'.Core\HTML::escape( $title ).'" href="#" data-id="'.$post->ID.'">';

		// $html.= $this->filters( 'icon', '<span class="genericon genericon-heart"></span>', $post->ID );
		$html.= $this->icon( 'heart', 'misc-32' );

		$html.= '</a></div><div><span class="like"></span></div>';

		if ( $avatars )
			$html.= '<div><ul class="like"></ul></div>';

		$html.= '</div>';

		return $html;
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$icon    = $this->adminbar__get_icon();
		$reports = $this->role_can_post( $post, 'reports' );
		$users   = $this->get_liked_users( $post->ID );
		$guests  = $this->get_liked_guests( $post->ID );
		// $total   = $this->get_liked_total( $post->ID );       // FIXME: display total

		if ( count( $users ) ) {

			$cap = current_user_can( 'edit_users' );

			$nodes[] = [
				'parent' => $parent,
				'id'     => $this->classs( 'users' ),
				'title'  => $icon.WordPress\Strings::getCounted(
					count( $users ),
					/* translators: `%s`: count placeholder */
					_x( 'Like Summary: Users %s', 'Node: Title', 'geditorial-like' )
				),
				'href' => $reports ? $this->get_module_url( 'reports', NULL, [ 'id' => $post->ID ] ) : FALSE,
				'meta' => [
					'class' => $this->adminbar__get_css_class(),
				],
			];

			foreach ( $users as $timestamp => $user_id )
				$nodes[] = [
					'parent' => $this->classs( 'users' ),
					'id'     => $this->classs( 'user', $user_id ),
					'title'  => gEditorial\Datetime::humanTimeDiffRound( (int) $timestamp ).' &ndash; '.get_the_author_meta( 'display_name', $user_id ),
					'href'   => $cap ? WordPress\User::edit( $user_id ) : FALSE,
					'meta'   => [
						'title' => gEditorial\Datetime::humanTimeAgo( (int) $timestamp, current_time( 'timestamp', FALSE ) ),
						'class' => $this->adminbar__get_css_class(),
					],
				];
		}

		if ( count( $guests ) ) {

			$nodes[] = [
				'parent' => $parent,
				'id'     => $this->classs( 'guests' ),
				'title'  => $icon.WordPress\Strings::getCounted(
					count( $guests ),
					/* translators: `%s`: count placeholder */
					_x( 'Like Summary: Guests %s', 'Node: Title', 'geditorial-like' )
				),
				'href' => $reports ? $this->get_module_url( 'reports', NULL, [ 'id' => $post->ID ] ) : FALSE,
				'meta' => [
					'class' => $this->adminbar__get_css_class(),
				],
			];

			foreach ( $guests as $timestamp => $ip )
				$nodes[] = [
					'parent' => $this->classs( 'guests' ),
					'id'     => $this->classs( 'guest', $timestamp ),
					'title'  => gEditorial\Datetime::humanTimeDiffRound( (int) $timestamp ).' &ndash; '.$ip,
					'href'   => sprintf( 'https://redirect.li/map/?ip=%s', $ip ), // TODO: use Services
					'meta'   => [
						'title' => gEditorial\Datetime::humanTimeAgo( (int) $timestamp, current_time( 'timestamp', FALSE ) ),
						'class' => $this->adminbar__get_css_class(),
					],
				];
		}
	}

	public function do_ajax_public()
	{
		$post = self::unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		switch ( $what ) {

			default:
			case 'check':

				list( $check, $count ) = $this->check( $post['id'] );

				gEditorial\Ajax::success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => $check ? 'unlike' : 'dolike',
					'remove'  => 'loading',
					'add'     => $check ? 'unlike' : 'dolike',
					'nonce'   => wp_create_nonce( 'geditorial_like_ajax-'.$post['id'] ),
					'count'   => Core\Number::format( $count ),
					'avatars' => $this->get_setting( 'avatar_support', TRUE ) ? $this->avatars( $post['id'] ) : '',
				] );

				break;

			case 'dolike':

				gEditorial\Ajax::checkReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->like( $post['id'] );

				gEditorial\Ajax::success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'unlike',
					'remove'  => 'dolike',
					'add'     => 'unlike',
					'count'   => Core\Number::format( $count ),
					'avatars' => $this->get_setting( 'avatar_support', TRUE ) ? $this->avatars( $post['id'] ) : '',
				] );

				break;

			case 'unlike':

				gEditorial\Ajax::checkReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->unlike( $post['id'] );

				gEditorial\Ajax::success( [
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'dolike',
					'remove'  => 'unlike',
					'add'     => 'dolike',
					'count'   => Core\Number::format( $count ),
					'avatars' => $this->get_setting( 'avatar_support', TRUE ) ? $this->avatars( $post['id'] ) : '',
				] );
		}

		gEditorial\Ajax::errorWhat();
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
		$cookie = $this->corecookies_get();

		if ( is_user_logged_in() ) {

			$key = array_search( get_current_user_id(), $users );

			if ( FALSE !== $key ) {

				--$total;
				unset( $users[$key] );

				$this->set_liked_users( $post_id, $users );
				$this->set_liked_total( $post_id, $total );
			}
		}

		if ( array_key_exists( $post_id, $cookie ) ) {

			if ( $timestamp = array_search( $cookie[$post_id], $guests ) ) {

				--$total;
				unset( $guests[$timestamp] );

				$this->set_liked_guests( $post_id, $guests );
				$this->set_liked_total( $post_id, $total );
			}

			unset( $cookie[$post_id] );

			$this->corecookies_set( $cookie, FALSE );
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

				++$total;
				$users[$time] = $user_id;

				$this->set_liked_users( $post_id, $users );
				$this->set_liked_total( $post_id, $total );
			}

			return [ TRUE, $total ];

		} else {

			$cookie = $this->corecookies_get();
			$ip     = Core\HTTP::IP();

			if ( ! array_key_exists( $post_id, $cookie )
				&& ! array_search( $ip, $guests ) ) {

				++$total;
				$guests[$time] = $ip;

				$this->set_liked_guests( $post_id, $guests );
				$this->set_liked_total( $post_id, $total );
				$this->corecookies_set( [ $post_id => $guests[$time] ] );
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
			: [ array_key_exists( $post_id, $this->corecookies_get() ), $total ];
	}

	public function avatars( $post_id )
	{
		$html  = '';
		$users = $this->get_liked_users( $post_id );
		$bp    = $this->get_setting( 'buddybress_support' ) && function_exists( 'bp_core_get_userlink' ) ;

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

					// TODO: handle via `WordPress\BuddyPress`
					if ( $bp ) {
						$html.= '<li><a href="'.bp_core_get_user_domain( $user->ID ).'" title="'.bp_core_get_user_displayname( $user->ID ).'">'.get_avatar( $user->user_email, 40, '', 'avatar' ).'</a></li>';
					} else {
						// $html.= '<li><a title="'.Core\HTML::escape( $user->display_name ).'">'.get_avatar( $user->user_email, 40, '', 'avatar' ).'</a></li>';

						$row = Core\HTML::tag( 'a', [
							'title' => $user->display_name,
							'href' => '#',
						], Services\Avatars::getByUser( $user ) );

						$html.= Core\HTML::tag( 'li', $row );
					}
				}
			}

		} else if ( WordPress\IsIt::dev() ) {

			// $count = wp_rand( 10, 150 );
			$count = wp_rand( 1, $this->get_setting( 'max_avatars', 12 ) );
			$attr  = Services\Markup::getImgCursorHover();

			for ( $i = 0; $i <= $count; $i++ )
				$html.= '<li><a title=""><img src="https://avatar.iran.liara.run/public" alt=""'.$attr.'/></a></li>';
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

	public function tweaks_column_attr( $post, $before, $after )
	{
		if ( ! current_user_can( 'read_post', $post->ID ) )
			return;

		$total = $this->get_liked_total( $post->ID );

		if ( empty( $total ) )
			return;

		printf( $before, '-like-count' );

			echo $this->get_column_icon( FALSE, 'heart', _x( 'Likes', 'Row Icon Title', 'geditorial-like' ) );

			printf(
				/* translators: `%s`: likes count */
				_nx( '%s Like', '%s Likes', $total, 'Noop', 'geditorial-like' ),
				Core\Number::format( $total )
			);

			$list   = [];
			$users  = $this->get_liked_users( $post->ID );
			$guests = $this->get_liked_guests( $post->ID );

			if ( ! empty( $users ) )
				$list[] = sprintf(
					/* translators: `%s`: users count */
					_nx( '%s User', '%s Users', count( $users ), 'Noop', 'geditorial-like' ),
					Core\Number::format( count( $users ) )
				);

			if ( ! empty( $guests ) )
				$list[] = sprintf(
					/* translators: `%s`: guests count */
					_nx( '%s Guest', '%s Guests', count( $guests ), 'Noop', 'geditorial-like' ),
					Core\Number::format( count( $guests ) )
				);

			echo WordPress\Strings::getJoined( $list, ' <span class="-like-counts">(', ')</span>' );

		echo $after;
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback );
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( gEditorial\Tablelist::isAction( 'sync_counts_all' ) ) {

					$count = 0;
					$query = new \WP_Query();

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
						++$count;
					}

					WordPress\Redirect::doReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );

				} else if ( gEditorial\Tablelist::isAction( 'sync_counts', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						$this->sync_counts( $post_id );
						++$count;
					}

					WordPress\Redirect::doReferer( [
						'message' => 'synced',
						'count'   => $count,
					] );
				}
			}
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list  = $this->list_posttypes();
		$query = [
			'meta_key' => $this->constant( 'metakey_liked_total' ),
			'orderby'  => 'meta_value_num',
			'order'    => 'DESC',
		];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], array_keys( $list ), $this->get_sub_limit_option( $sub, 'reports' ) );

		$pagination['actions']['sync_counts']     = _x( 'Sync Counts', 'Table Action', 'geditorial-like' );
		$pagination['actions']['sync_counts_all'] = _x( 'Sync All Counts', 'Table Action', 'geditorial-like' );

		$pagination['before'][] = gEditorial\Tablelist::filterPostTypes( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterAuthors( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => gEditorial\Tablelist::columnPostID(),
			'date'  => gEditorial\Tablelist::columnPostDate(),
			'type'  => gEditorial\Tablelist::columnPostType(),
			'title' => gEditorial\Tablelist::columnPostTitle(),
			'total' => [
				'title'    => _x( 'Total', 'Table Column', 'geditorial-like' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					return gEditorial\Helper::htmlCount( $this->get_liked_total( $row->ID ) );
				},
			],
			'guests' => [
				'title'    => _x( 'Guests', 'Table Column', 'geditorial-like' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					return gEditorial\Helper::htmlCount( $this->get_liked_guests( $row->ID ) );
				},
			],
			'users' => [
				'title'    => _x( 'Users', 'Table Column', 'geditorial-like' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					return gEditorial\Helper::htmlCount( $this->get_liked_users( $row->ID ) );
				},
			],
			'avatars' => [
				'title'    => _x( 'Avatars', 'Table Column', 'geditorial-like' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					$html = $this->avatars( $row->ID );
					return $html ? Core\HTML::tag( 'ul', $html ) : gEditorial\Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Post Likes', 'Header', 'geditorial-like' ) ),
			'empty'      => Services\CustomPostType::getLabel( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}
}

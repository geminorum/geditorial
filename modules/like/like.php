<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialLike extends gEditorialModuleCore
{

	public $meta_key   = '_ge_like';
	protected $cookie  = 'geditorial-like';
	protected $post_id = FALSE;

	public static function module()
	{
		return array(
			'name'  => 'like',
			'title' => _x( 'Like', 'Like Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Like Button for Posts and Comments', 'Like Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'heart',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'avatars',
					'title'       => _x( 'Avatars', 'Like Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display avatars next to the like button', 'Like Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'comments',
					'title'       => _x( 'Comments', 'Like Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Also display button for comments of enabled post types', 'Like Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	public function setup()
	{
		parent::setup();

		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );

			add_action( 'gnetwork_debugbar_panel_geditorial_like', array( $this, 'gnetwork_debugbar_panel' ) );
			add_filter( 'gnetwork_debugbar_panel_groups', function( $groups ){
				$groups['geditorial_like'] = _x( 'Editorial Like', 'Like Module', GEDITORIAL_TEXTDOMAIN );
				return $groups;
			} );
		}
	}

	public function setup_ajax( $request )
	{
		add_action( 'wp_ajax_geditorial_like', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_geditorial_like', array( $this, 'ajax' ) );
	}

	public function init()
	{
		$this->cookie = 'geditorial-like-'.get_current_blog_id();
		do_action( 'geditorial_like_init', $this->module );

		$this->do_globals();
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->post_types() ) )
			return;

		$this->post_id = get_queried_object_id();

		$this->enqueue_asset_js( TRUE );
		$this->enqueue_styles();
	}

	public function get_button( $post_id = NULL )
	{
		if ( is_null( $post_id ) )
			$post_id = $this->post_id;

		$avatars = $this->get_setting( 'avatars', FALSE );

		$title = apply_filters( 'geditorial_like_loading', _x( 'Loading &hellip;', 'Like Module', GEDITORIAL_TEXTDOMAIN ), $post_id );
		$html  = '<div class="geditorial-wrap like" style="display:none;" data-avatars="'.( $avatars ? 'true' : 'false' ).'">';
		$html .= '<div><a class="like loading" title="'.esc_attr( $title ).'" href="#" data-id="'.$post_id.'">';
		$html .= apply_filters( 'geditorial_like_icon', '<span class="genericon genericon-heart"></span>', $post_id );
		$html .= '</a></div><div><span class="like"></span></div>';

		if ( $avatars )
			$html .= '<div><ul class="like"></ul></div>';

		$html .= '</div>';

		return $html;
	}

	public function gnetwork_debugbar_panel()
	{
		if ( ! $this->post_id )
			return;

		$users = $this->get_postmeta( $this->post_id, FALSE, array(), $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $this->post_id, FALSE, array(), $this->meta_key.'_guests' );
		$cookie = $this->get_cookie();

		echo 'Users:'; self::dump( $users );
		echo 'Guests:'; self::dump( $guests );
		echo 'Cookie:'; self::dump( $cookie );
	}

	public function ajax()
	{
		$post = wp_unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		switch ( $what ) {

			default:
			case 'check':

				list( $check, $count ) = $this->check( $post['id'] );

				wp_send_json_success( array(
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => $check ? 'unlike' : 'dolike',
					'remove'  => 'loading',
					'add'     => $check ? 'unlike' : 'dolike',
					'nonce'   => wp_create_nonce( 'geditorial_like_ajax-'.$post['id'] ),
					'count'   => number_format_i18n( $count ),
					'avatars' => $this->get_setting( 'avatars', FALSE ) ? $this->avatars( $post['id'] ) : NULL,
				) );

			break;
			case 'dolike':

				gEditorialHelper::checkAjaxReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->like( $post['id'] );

				wp_send_json_success( array(
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'unlike',
					'remove'  => 'dolike',
					'add'     => 'unlike',
					'count'   => number_format_i18n( $count ),
					'avatars' => $this->get_setting( 'avatars', FALSE ) ? $this->avatars( $post['id'] ) : NULL,
				) );

			break;
			case 'unlike':

				gEditorialHelper::checkAjaxReferer( 'geditorial_like_ajax-'.$post['id'] );

				list( $check, $count ) = $this->unlike( $post['id'] );

				wp_send_json_success( array(
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'dolike',
					'remove'  => 'unlike',
					'add'     => 'dolike',
					'count'   => number_format_i18n( $count ),
					'avatars' => $this->get_setting( 'avatars', FALSE ) ? $this->avatars( $post['id'] ) : NULL,
				) );
		}

		wp_send_json_error( gEditorialHTML::error( _x( 'What?!', 'Like Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );
	}

	public function title( $liked, $post_id = NULL )
	{
		return apply_filters( 'geditorial_like_title', ( $liked ? _x( 'Unlike', 'Like Module', GEDITORIAL_TEXTDOMAIN ) : _x( 'Like', 'Like Module', GEDITORIAL_TEXTDOMAIN ) ), $liked, $post_id );
	}

	public function unlike( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_guests' );
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

		return array( FALSE, $count );
	}

	public function like( $post_id )
	{
		$users     = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_users' );
		$guests    = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_guests' );
		$count     = count( $users ) + count( $guests );
		$timestamp = current_time( 'timestamp' );

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! array_search( $user_id, $users ) ) {
				$users[$timestamp] = $user_id;
				$this->set_meta( $post_id, $users, '_users' );
				$count++;
			}
			return array( TRUE, $count );
		} else {
			$cookie = $this->get_cookie();
			if ( ! array_key_exists( $post_id, $cookie ) ) {
				$guests[$timestamp] = self::IP();
				$this->set_meta( $post_id, $guests, '_guests' );
				$this->set_cookie( array( $post_id => $guests[$timestamp] ) );
				$count++;
			}
			return array( TRUE, $count );
		}
	}

	public function check( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_guests' );
		$count  = count( $users ) + count( $guests );

		if ( is_user_logged_in() ) {
			return array( array_search( get_current_user_id(), $users ), $count );
		} else {
			$cookie = $this->get_cookie();
			return array( array_key_exists( $post_id, $cookie ), $count );
		}
	}

	public function avatars( $post_id )
	{
		$html = '';

		if ( ! $this->get_setting( 'avatars', FALSE ) )
			return $html;

		$users = $this->get_postmeta( $post_id, FALSE, array(), $this->meta_key.'_users' );

		if ( count( $users ) ) {
			$query = new WP_User_Query( array(
				'include' => array_values( $users ),
				'fields'  => array( 'user_email', 'ID', 'display_name' ),
			) );

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

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialLike extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'like';
	var $meta_key    = '_ge_like';

	var $post_id = FALSE; // current post id
	var $cookie = 'geditorial-like';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Like', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Like Button for Posts and Comments', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( '', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'heart',
			'slug'                 => 'like',
			'load_frontend'        => FALSE,
			'constants'            => array(),
			'default_options'      => array(
				'enabled'  => FALSE,
				'settings' => array(),

				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'post_fields' => array(),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'comments',
						'type'        => 'enabled',
						'title'       => _x( 'Comments', 'Enable Like for Comments', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Like button for enabled post types comments', GEDITORIAL_TEXTDOMAIN ),
						'default'     => '0',
					),
					array(
						'field'       => 'avatars',
						'type'        => 'enabled',
						'title'       => _x( 'Avatars', 'Enable Like for Comments', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Display avatars alongside like button', GEDITORIAL_TEXTDOMAIN ),
						'default'     => '0',
					),
				),
				'post_types_option' => 'post_types_option',
				// 'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles'       => array(),
				'descriptions' => array(),
				'misc'         => array(),
				'labels'       => array(),
			),
			'configure_page_cb' => 'print_configure_view',
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		} else {
			add_action( 'template_redirect', array( &$this, 'template_redirect' ) );

			add_action( 'gnetwork_debugbar_panel_geditorial_like', array( &$this, 'gnetwork_debugbar_panel' ) );
			add_filter( 'gnetwork_debugbar_panel_groups', function( $groups ){
				$groups['geditorial_like'] = __( 'gEditorial Like', GEDITORIAL_TEXTDOMAIN );
				return $groups;
			} );
		}

		add_action( 'wp_ajax_geditorial_like'       , array( &$this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_geditorial_like', array( &$this, 'ajax' ) );
	}

	public function init()
	{
		$this->cookie = 'geditorial-like-'.get_current_blog_id();
		do_action( 'geditorial_like_init', $this->module );
	}

	public function template_redirect()
	{
		if ( ! is_singular() )
			return;

		$post = get_queried_object();

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return;

		$this->post_id = $post->ID;
		$this->enqueue_asset_js();
		$this->enqueue_styles();
	}

	public function get_button( $post_id = NULL )
	{
		if ( is_null( $post_id ) )
			$post_id = $this->post_id;

		$title = apply_filters( 'geditorial_like_loading', _x( 'Loading&hellip;', 'gEditorial Like', GEDITORIAL_TEXTDOMAIN ), $post_id );
		$html  = '<div class="geditorial-wrap like" style="display:none;">';
		$html .= '<div><a class="like loading" title="'.esc_attr( $title ).'" href="#" data-id="'.$post_id.'">';
		$html .= apply_filters( 'geditorial_like_icon', '<span class="genericon genericon-heart"></span>', $post_id );
		$html .= '</a></div><div><span class="like"></span></div>';

		// maybe js error
		//if ( $this->get_setting( 'avatars', false ) )
			//$html .= '<div><ul class="geditorial-like"></ul></div>';
			$html .= '<div><ul class="like"></ul></div>';

		$html .= '</div>';

		return $html;
	}

	public function gnetwork_debugbar_panel()
	{
		if ( ! $this->post_id )
			return;

		$users = $this->get_postmeta( $this->post_id, false, array(), $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $this->post_id, false, array(), $this->meta_key.'_guests' );
		$cookie = $this->get_cookie();

		echo 'Users:'; gEditorialHelper::dump( $users );
		echo 'Guests:'; gEditorialHelper::dump( $guests );
		echo 'Cookie:'; gEditorialHelper::dump( $cookie );
	}

	public function ajax()
	{
		$post = wp_unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		switch( $what ) {
			default :
			case 'check':
				//wp_send_json_success( gEditorialHelper::notice( __( 'Success', GEDITORIAL_TEXTDOMAIN ), 'updated', false ) );
				list( $check, $count ) = $this->check( $post['id'] );
				wp_send_json_success( array(
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => ( $check ? 'unlike' : 'dolike' ),
					'remove'  => 'loading',
					'add'     => ( $check ? 'unlike' : 'dolike' ),
					'nonce'   => wp_create_nonce( 'geditorial_like_ajax-'.$post['id'] ),
					'count'   => number_format_i18n( $count ),
					'avatars' => $this->avatars( $post['id'] ),
				) );
			break;
			case 'dolike' :
				check_ajax_referer( 'geditorial_like_ajax-'.$post['id'] );
				list( $check, $count ) = $this->like( $post['id'] );
				wp_send_json_success( array(
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'unlike',
					'remove'  => 'dolike',
					'add'     => 'unlike',
					'count'   => number_format_i18n( $count ),
					'avatars' => $this->avatars( $post['id'] ),
				) );

			break;
			case 'unlike' :
				check_ajax_referer( 'geditorial_like_ajax-'.$post['id'] );
				list( $check, $count ) = $this->unlike( $post['id'] );
				wp_send_json_success( array(
					'title'   => $this->title( $check, $post['id'] ),
					'action'  => 'dolike',
					'remove'  => 'unlike',
					'add'     => 'dolike',
					'count'   => number_format_i18n( $count ),
					'avatars' => $this->avatars( $post['id'] ),
				) );

			break;
		}
		wp_send_json_error( gEditorialHelper::notice( _x( 'Waht?!', 'Ajax Notice', GEDITORIAL_TEXTDOMAIN ), 'error', false ) );
	}

	public function title( $liked, $post_id = NULL )
	{
		return apply_filters( 'geditorial_like_title', ( $liked ? _x( 'Unlike', 'gEditorial Like', GEDITORIAL_TEXTDOMAIN ) : _x( 'Like', 'gEditorial Like', GEDITORIAL_TEXTDOMAIN ) ), $liked, $post_id );
	}

	public function unlike( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_guests' );
		$count  = count( $users ) + count( $guests );

		if ( is_user_logged_in() ) {
			$key = array_search( get_current_user_id(), $users );
			if ( false !== $key ) {
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
			$this->set_cookie( $cookie, false );

		}

		return array( FALSE, $count );
	}

	public function like( $post_id )
	{
		$users     = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_users' );
		$guests    = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_guests' );
		$count     = count( $users ) + count( $guests );
		$timestamp = current_time( 'timestamp' );

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! array_search( $user_id, $users ) ) {
				$users[$timestamp] = $user_id;
				$this->set_meta( $post_id, $users, '_users' );
				$count++;
			}
			return array( true, $count );
		} else {
			$cookie = $this->get_cookie();
			if ( ! array_key_exists( $post_id, $cookie ) ) {
				$guests[$timestamp] = gEditorialHelper::IP();
				$this->set_meta( $post_id, $guests, '_guests' );
				$this->set_cookie( array( $post_id => $guests[$timestamp] ) );
				$count++;
			}
			return array( true, $count );
		}
	}

	public function check( $post_id )
	{
		$users  = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_users' );
		$guests = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_guests' );
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

		if ( ! $this->get_setting( 'avatars', false ) )
			return $html;

		$users = $this->get_postmeta( $post_id, false, array(), $this->meta_key.'_users' );

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

// https://wordpress.org/plugins/wp-ulike/
// https://github.com/tommcfarlin/ajax-notification/

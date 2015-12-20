<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialDrafts extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'     => 'drafts',
			'title'    => _x( 'Drafts', 'Drafts Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Adds a Drafts tab to the admin bar so that you can quickly access your draft blog posts. ', 'Drafts Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'filter',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'max_posts',
					'type'        => 'number',
					'title'       => _x( 'Max Posts', 'Drafts Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display maximum posts for each post type', 'Drafts Module', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 100,
				),
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	public function init()
	{
		do_action( 'geditorial_drafts_init', $this->module );
		$this->do_globals();

		if ( ! is_admin()
			&& is_admin_bar_showing()
			&& current_user_can( 'edit_posts' )
			&& count( $this->post_types() ) ) {

				add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 65 );
				$this->enqueue_asset_js( TRUE );
		}

		add_action( 'wp_ajax_geditorial_drafts', array( $this, 'ajax' ) );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		$wp_admin_bar->add_node( array(
			'id'    => 'editorial-drafts',
			'title' => _x( 'Drafts', 'Drafts Module: Admin Bar Title', GEDITORIAL_TEXTDOMAIN ),
			'href'  => admin_url( 'edit.php?post_status=draft' ),
		) );
	}

	public function ajax()
	{
		check_ajax_referer( 'geditorial', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) )
			wp_send_json_error( self::error( __( 'Cheatin&#8217; uh?', GEDITORIAL_TEXTDOMAIN ) ) );

		// $post = wp_unslash( $_POST );
		$what = isset( $post['what'] ) ? $post['what'] : 'nothing';

		switch( $what ) {
			default:
			case 'list':
				wp_send_json_success( array(
					'html' => $this->drafts_list(),
				) );
		}
	}

	private function drafts_list()
	{
		$output = '';

		foreach ( $this->post_types() as $post_type ) {

			$block = '';

			foreach ( $this->get_drafts( $post_type ) as $draft ) {
				$post_title = ! empty( $draft->post_title ) ? esc_html( $draft->post_title ) : _x( '(untitled)', 'Drafts Module', GEDITORIAL_TEXTDOMAIN );
				// TODO: add last modified time
				$block .= '<li><a href="'.esc_url(admin_url('post.php?action=edit&post='.$draft->ID)).'">'.$post_title.'</a></li>';
			}

			if ( $block ) {
				$object = get_post_type_object( $post_type );
				$output .= '<div class="-block"><h3><a href="'.esc_url( admin_url( 'edit.php?post_type='.$post_type) ).'" title="">'.$object->labels->name.'</a></h3><ul>';
				$output .= $block.'</ul></div>';
			}
		}

		if ( ! $output )
			$output = '<div class="-empty"><p>'._x( '(none)', 'Drafts Module', GEDITORIAL_TEXTDOMAIN).'</p></div>';

		return $output;
	}

	private function get_drafts( $post_type = 'post' )
	{
		$query = new WP_Query( array(
			'post_type'      => $post_type,
			'post_status'    => 'draft',
			'posts_per_page' => $this->get_setting( 'max_posts', 100 ),
			'order'          => 'DESC',
			'orderby'        => 'modified',
		) );

		return $query->posts;
	}
}

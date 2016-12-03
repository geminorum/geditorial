<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialDrafts extends gEditorialModuleCore
{

	protected $caps = array(
		'ajax' => 'edit_posts',
	);

	public static function module()
	{
		return array(
			'name'  => 'drafts',
			'title' => _x( 'Drafts', 'Drafts Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Adds a dropdown to the admin bar so that you can quickly access your draft blog posts.', 'Drafts Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'filter',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'summary_scope',
				array(
					'field'       => 'max_posts',
					'type'        => 'number',
					'title'       => _x( 'Max Posts', 'Drafts Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display maximum posts for each post type', 'Drafts Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 100,
				),
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	public function setup_ajax( $request )
	{
		add_action( 'wp_ajax_geditorial_drafts', array( $this, 'ajax' ) );
	}

	public function init()
	{
		do_action( 'geditorial_drafts_init', $this->module );
		$this->do_globals();

		if ( ! is_admin()
			&& is_admin_bar_showing()
			&& $this->cuc( 'ajax' )
			&& count( $this->post_types() ) ) {

				add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 265 );
				$this->enqueue_asset_js( TRUE );
				$this->enqueue_styles();
		}
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		$wp_admin_bar->add_node( array(
			'id'    => 'editorial-drafts',
			'href'  => admin_url( 'edit.php?post_status=draft' ), // FIXME: add default posttype
			'title' => _x( 'Drafts', 'Drafts Module: Admin Bar Title', GEDITORIAL_TEXTDOMAIN )
				.'<span class="geditorial-spinner-adminbar"></span>',
		) );
	}

	public function ajax()
	{
		gEditorialHelper::checkAjaxReferer();

		if ( ! $this->cuc( 'ajax' ) )
			self::cheatin();

		$post = wp_unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			default:
			case 'list':

				wp_send_json_success( array(
					'html' => $this->drafts_list(),
				) );
		}
	}

	private function drafts_list()
	{
		$html  = '';
		$all   = _x( 'View all %s drafts', 'Drafts Module', GEDITORIAL_TEXTDOMAIN );
		$empty = _x( '(untitled)', 'Drafts Module', GEDITORIAL_TEXTDOMAIN );
		$user  = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();

		foreach ( $this->post_types() as $post_type ) {

			$block = '';

			foreach ( $this->get_drafts( $post_type ) as $draft )
				$block .= '<li>'.gEditorialHTML::tag( 'a', array(
					'href'  => gEditorialWordPress::getPostEditLink( $draft->ID ),
					'title' => gEditorialHelper::postModified( $draft, TRUE ),
				), ( empty( $draft->post_title ) ? $empty : esc_html(
					apply_filters( 'the_title', $draft->post_title, $draft->ID ) ) ) ).'</li>';

			if ( ! $block )
				continue;

			$object = get_post_type_object( $post_type );

			$link = gEditorialHTML::tag( 'a', array(
				'href'  => gEditorialWordPress::getPostTypeEditLink( $post_type, $user ),
				'title' => sprintf( $all, $object->labels->singular_name ),
			), esc_html( $object->labels->name ) );

			$html .= '<div class="-block"><h3>'.$link.'</h3><ul>'.$block.'</ul></div>';
		}

		return $html ? $html :'<div class="-empty"><p>'._x( '(none)', 'Drafts Module', GEDITORIAL_TEXTDOMAIN).'</p></div>';
	}

	private function get_drafts( $post_type = 'post' )
	{
		$query = new \WP_Query( array(
			'post_type'      => $post_type,
			'post_status'    => 'draft',
			'posts_per_page' => $this->get_setting( 'max_posts', 100 ),
			'order'          => 'DESC',
			'orderby'        => 'modified',
		) );

		return $query->posts;
	}
}

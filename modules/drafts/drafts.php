<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;

class Drafts extends gEditorial\Module
{

	protected $caps = [
		'ajax' => 'edit_posts',
	];

	public static function module()
	{
		return [
			'name'  => 'drafts',
			'title' => _x( 'Drafts', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Tools to work with drafts', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-post',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'summary_scope',
				[
					'field'       => 'max_posts',
					'type'        => 'number',
					'title'       => _x( 'Max Posts', 'Modules: Drafts: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display maximum posts for each post type', 'Modules: Drafts: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 100,
				],
			],
		];
	}

	public function init()
	{
		parent::init();


		if ( is_admin() ) {


		} else {

			if ( is_admin_bar_showing()
				&& $this->cuc( 'ajax' )
				&& count( $this->post_types() ) ) {

				$this->action( 'admin_bar_menu', 1, 265 );
				$this->enqueue_asset_js();
				$this->enqueue_styles();
			}

		}
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		$wp_admin_bar->add_node( [
			'id'    => 'editorial-drafts',
			'href'  => admin_url( 'edit.php?post_status=draft' ), // FIXME: add default posttype
			'title' => _x( 'Drafts', 'Modules: Drafts: Admin Bar Title', GEDITORIAL_TEXTDOMAIN )
				.'<span class="geditorial-spinner-adminbar"></span>',
		] );
	}

	public function ajax()
	{
		Ajax::checkReferer( $this->hook() );

		if ( ! $this->cuc( 'ajax' ) )
			self::cheatin();

		$post = wp_unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'list': Ajax::successHTML( $this->drafts_list() );
		}

		Ajax::errorWhat();
	}

	private function drafts_list()
	{
		$html = '';
		$all  = _x( 'View all %s drafts', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN );
		$user = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();

		foreach ( $this->post_types() as $post_type ) {

			$object = get_post_type_object( $post_type );

			if ( ! current_user_can( $object->cap->edit_posts ) )
				continue;

			$block = '';

			foreach ( $this->get_drafts( $post_type, $user ) as $post ) {

				$block .= '<li>'.Helper::getPostTitleRow( $post, 'edit',
					Helper::postModified( $post, TRUE ) ).'</li>';

				// FIXME: add author suffix
			}

			if ( ! $block )
				continue; // FIXME: add new posttype link

			$link = HTML::tag( 'a', [
				'href'  => WordPress::getPostTypeEditLink( $post_type, $user ),
				'title' => sprintf( $all, $object->labels->singular_name ),
			], esc_html( $object->labels->name ) );

			$html .= '<div class="-block"><h3>'.$link.'</h3><ul>'.$block.'</ul></div>';
		}

		return $html ? $html :'<div class="-empty"><p>'._x( '(none)', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ).'</p></div>';
	}

	private function get_drafts( $post_type = 'post', $user = 0 )
	{
		$args = [
			'post_type'      => $post_type,
			'post_status'    => 'draft',
			'posts_per_page' => $this->get_setting( 'max_posts', 100 ),
			'order'          => 'DESC',
			'orderby'        => 'modified',
		];

		if ( $user )
			$args['author'] = $user;

		$query = new \WP_Query( $args );
		return $query->posts;
	}
}

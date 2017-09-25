<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;

class Drafts extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'drafts',
			'title' => _x( 'Drafts', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Tools to Work With Drafts', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-post',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'public_preview',
					'title'       => _x( 'Public Preview', 'Modules: Drafts: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Provides a secret link to non-logged in users to view post drafts.', 'Modules: Drafts: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'_frontend' => [
				[
					'field'       => 'adminbar_summary',
					'title'       => _x( 'Adminbar Summary', 'Modules: Drafts: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Summary for the current item as a node in adminbar', 'Modules: Drafts: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'summary_scope',
				'adminbar_roles',
				[
					'field'       => 'max_posts',
					'type'        => 'number',
					'title'       => _x( 'Max Posts', 'Modules: Drafts: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Maximum number of posts for each post-type.', 'Modules: Drafts: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 25,
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'meta_secret' => '_preview_secret',
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'public_preview', FALSE ) )
			$this->filter( 'the_posts', 2 );
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function current_screen( $screen )
	{
		if ( ! $this->get_setting( 'public_preview', FALSE ) )
			return;

		if ( in_array( $screen->post_type, $this->post_types() ) ) {
			if ( 'post' == $screen->base ) {
				$this->action( 'post_submitbox_minor_actions', 1, 12 );
				$this->enqueue_asset_js( [], $screen );

			} else if ( 'edit' == $screen->base ) {
				$this->filter( 'post_row_actions', 2 );
				add_action( 'geditorial_tweaks_column_attr', [ $this, 'column_attr' ], 90 );
			}
		}
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() )
			return;

		if ( ! $this->role_can( 'adminbar' ) )
			return;

		$nodes[] = [
			'id'    => $this->classs(),
			'href'  => '#',
			'title' => _x( 'Drafts', 'Modules: Drafts: Adminbar', GEDITORIAL_TEXTDOMAIN ).Ajax::spinner(),
			'meta'  => [ 'class' => 'geditorial-adminbar-node' ],
		];

		$this->enqueue_asset_js();
		$this->enqueue_styles();
	}

	public function ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'private':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
					Ajax::errorUserCant();

				if ( ! $this->nonce_verify( $post['post_id'], $post['nonce'] ) )
					self::cheatin();

				if ( ! $this->make_private( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to make preview private. Please try again.', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ) );

				Ajax::success();

			break;
			case 'public':

				if ( empty( $post['post_id'] ) )
					Ajax::errorMessage();

				if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
					Ajax::errorUserCant();

				if ( ! $this->nonce_verify( $post['post_id'], $post['nonce'] ) )
					self::cheatin();

				if ( ! $link = $this->make_public( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to make preview public. Please try again.', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ) );

				Ajax::success( $link );

			break;
			case 'list':

				Ajax::checkReferer( $this->hook() );

				if ( ! $this->role_can( 'adminbar' ) )
					self::cheatin();

				Ajax::success( $this->drafts_list() );
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

				$block .= '<li>'.Helper::getPostTitleRow( $post, 'edit', FALSE,
					Helper::postModified( $post, TRUE ) ).'</li>';

				// FIXME: add author suffix
			}

			if ( ! $block )
				continue; // FIXME: add new posttype link

			$link = HTML::tag( 'a', [
				'href'  => WordPress::getPostTypeEditLink( $post_type, $user, [ 'post_status' => 'draft' ] ),
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
			'posts_per_page' => $this->get_setting( 'max_posts', 25 ),
			'order'          => 'DESC',
			'orderby'        => 'modified',
		];

		if ( $user )
			$args['author'] = $user;

		$query = new \WP_Query( $args );
		return $query->posts;
	}

	public function post_submitbox_minor_actions( $post )
	{
		$allowed = $this->filters( 'preview_statuses', [ 'draft', 'pending', 'auto-draft', 'future' ] );

		if ( ! in_array( get_post_status( $post ), $allowed ) )
			return;

		$public = $this->is_public( $post->ID );
		$nonce  = $this->nonce_create( $post->ID );

		echo '<div class="geditorial-admin-wrap -drafts">';

		echo HTML::tag( 'input', [
			'type'     => 'text',
			'value'    => $this->get_preview_url( $post->ID ),
			'style'    => $public ? FALSE : 'display:none;',
			'class'    => [ 'widefat', '-link' ],
			'readonly' => TRUE,
			'onclick'  => 'this.focus();this.select()',
		] );

		echo '<span class="-loading spinner"></span>';

		echo HTML::tag( 'a', [
			'href'  => '#',
			'class' => [ 'button', 'button-small', '-button', '-action', '-after-private' ],
			'style' => $public ? 'display:none;' : FALSE,
			'data'  => [
				'id'     => $post->ID,
				'action' => 'public',
				'nonce'  => $nonce,
			],
		], _x( 'Make Preview Public', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ) );

		echo HTML::tag( 'a', [
			'href'  => '#',
			'class' => [ 'button', 'button-small', '-button', '-action', '-after-public' ],
			'style' => $public ? FALSE : 'display:none;',
			'data'  => [
				'id'     => $post->ID,
				'action' => 'private',
				'nonce'  => $nonce,
			],
		], _x( 'Make Preview Private', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ) );

		echo '</div>';
	}

	public function get_preview_url( $post_id, $key = NULL )
	{
		$url = get_permalink( $post_id );

		if ( is_null( $key ) )
			$key = get_post_meta( $post_id, $this->constant( 'meta_secret' ), TRUE );

		return $key ? add_query_arg( 'secret', $key, $url ) : $url;
	}

	public function make_private( $post_id )
	{
		return delete_post_meta( $post_id, $this->constant( 'meta_secret' ) );
	}

	public function make_public( $post_id )
	{
		$key   = wp_generate_password( 6, FALSE, FALSE );
		$added = add_post_meta( $post_id, $this->constant( 'meta_secret' ), $key, TRUE );

		return $added ? $this->get_preview_url( $post_id, $key ) : FALSE;
	}

	public function is_public( $post_id )
	{
		return strlen( get_post_meta( $post_id, $this->constant( 'meta_secret' ), TRUE ) ) > 0;
	}

	public function the_posts( $posts, $wp_query )
	{
		global $wpdb;

		if ( isset( $_GET['secret'] )
			&& $wp_query->is_main_query()
			&& get_post_meta( $wp_query->query_vars['p'], $this->constant( 'meta_secret' ), TRUE ) === $_GET['secret'] )
				$posts = $wpdb->get_results( $wp_query->request );

		return $posts;
	}

	public function post_row_actions( $actions, $post )
	{
		$allowed = $this->filters( 'preview_statuses', [ 'draft', 'pending', 'auto-draft', 'future' ] );

		if ( ! in_array( get_post_status( $post ), $allowed ) )
			return $actions;

		if ( $this->is_public( $post->ID ) )
			$actions['public_link'] = HTML::link(
				_x( 'Public Preview', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ),
				$this->get_preview_url( $post->ID )
			);

		return $actions;
	}

	public function column_attr( $post )
	{
		if ( ! $this->is_public( $post->ID ) )
			return;

		$link = $this->get_preview_url( $post->ID );

		echo '<li class="-row -drafts -preview-link">';
			echo $this->get_column_icon( FALSE, 'welcome-view-site', _x( 'Preview', 'Modules: Drafts: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

			echo HTML::tag( 'a', [
				'href'   => $link,
				'class'  => '-link',
				'target' => '_blank',
			], _x( 'Has public preview link', 'Modules: Drafts', GEDITORIAL_TEXTDOMAIN ) );

		echo '</li>';
	}
}

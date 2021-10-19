<?php namespace geminorum\gEditorial\Modules\Drafts;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;

class Drafts extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'drafts',
			'title' => _x( 'Drafts', 'Modules: Drafts', 'geditorial' ),
			'desc'  => _x( 'Tools to Work With Drafts', 'Modules: Drafts', 'geditorial' ),
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
					'title'       => _x( 'Public Preview', 'Setting Title', 'geditorial-drafts' ),
					'description' => _x( 'Provides a secret link to non-logged in users to view post drafts.', 'Setting Description', 'geditorial-drafts' ),
				],
				'admin_rowactions',
			],
			'_frontend' => [
				[
					'field'       => 'adminbar_summary',
					'title'       => _x( 'Adminbar Summary', 'Setting Title', 'geditorial-drafts' ),
					'description' => _x( 'Summary for the current item as a node in adminbar', 'Setting Description', 'geditorial-drafts' ),
				],
				'summary_scope',
				'adminbar_roles',
				[
					'field'       => 'max_posts',
					'type'        => 'number',
					'title'       => _x( 'Max Posts', 'Setting Title', 'geditorial-drafts' ),
					'description' => _x( 'Maximum number of posts for each post-type.', 'Setting Description', 'geditorial-drafts' ),
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

	// @REF: https://core.trac.wordpress.org/ticket/43739
	public function all_posttypes( $exclude = TRUE, $args = [ 'show_ui' => TRUE ] )
	{
		$posttypes = PostType::get( 0, $args );
		$excluded  = $this->posttypes_excluded();
		$viewables = [];

		if ( ! empty( $excluded ) )
			$posttypes = Arraay::stripByKeys( $posttypes, $excluded );

		foreach ( $posttypes as $posttype => $label )
			if ( is_post_type_viewable( $posttype ) )
				$viewables[$posttype] = $label;

		return $viewables;
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

		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$this->action( 'post_submitbox_minor_actions', 1, 12 );

				$this->enqueue_asset_js( [], $screen );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_rowactions' ) ) {

					$this->filter( 'page_row_actions', 2 );
					$this->filter( 'post_row_actions', 2 );
				}

				$this->action_module( 'tweaks', 'column_attr', 1, 90 );
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
			'title' => _x( 'Drafts', 'Adminbar', 'geditorial-drafts' ).Ajax::spinner(),
			'meta'  => [ 'class' => 'geditorial-adminbar-node '.$this->classs() ],
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
					Ajax::errorMessage( __( 'Unable to make preview private. Please try again.', 'geditorial-drafts' ) );

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
					Ajax::errorMessage( __( 'Unable to make preview public. Please try again.', 'geditorial-drafts' ) );

				Ajax::success( $link );

			break;
			case 'list':

				Ajax::checkReferer( $this->hook() );

				if ( ! $this->role_can( 'adminbar' ) )
					Ajax::errorUserCant();

				Ajax::success( $this->drafts_list() );
		}

		Ajax::errorWhat();
	}

	private function drafts_list()
	{
		$html = '';
		/* translators: %s: drafts count */
		$all  = _x( 'View all %s drafts', 'Header', 'geditorial-drafts' );
		$user = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();

		foreach ( $this->posttypes() as $posttype ) {

			$object = PostType::object( $posttype );

			if ( ! current_user_can( $object->cap->edit_posts ) )
				continue;

			$block = '';

			foreach ( $this->get_drafts( $posttype, $user ) as $post ) {

				$block.= '<li>'.Helper::getPostTitleRow( $post, 'edit', FALSE,
					Datetime::postModified( $post, TRUE ) ).'</li>';

				// FIXME: add author suffix
			}

			if ( ! $block )
				continue; // FIXME: add new posttype link

			$link = HTML::tag( 'a', [
				'href'  => WordPress::getPostTypeEditLink( $posttype, $user, [ 'post_status' => 'draft' ] ),
				'title' => sprintf( $all, $object->labels->singular_name ),
			], HTML::escape( $object->labels->name ) );

			$html.= '<div class="-block"><h3>'.$link.'</h3><ul>'.$block.'</ul></div>';
		}

		return $html ?:'<div class="-empty"><p>'._x( '(none)', 'Empty', 'geditorial-drafts' ).'</p></div>';
	}

	private function get_drafts( $posttype = 'post', $user = 0 )
	{
		$args = [
			'post_type'      => $posttype,
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

		echo Ajax::spinner();

		echo HTML::tag( 'a', [
			'href'  => '#',
			'class' => [ 'button', 'button-small', '-button', '-action', '-after-private' ],
			'style' => $public ? 'display:none;' : FALSE,
			'data'  => [
				'id'     => $post->ID,
				'action' => 'public',
				'nonce'  => $nonce,
			],
		], _x( 'Make Preview Public', 'Button', 'geditorial-drafts' ) );

		echo HTML::tag( 'a', [
			'href'  => '#',
			'class' => [ 'button', 'button-small', '-button', '-action', '-after-public' ],
			'style' => $public ? FALSE : 'display:none;',
			'data'  => [
				'id'     => $post->ID,
				'action' => 'private',
				'nonce'  => $nonce,
			],
		], _x( 'Make Preview Private', 'Button', 'geditorial-drafts' ) );

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
			&& ( $wp_query->is_single || $wp_query->is_page )
			&& ! empty( $wp_query->query_vars['p'] ) ) {

			if ( $_GET['secret'] === get_post_meta( $wp_query->query_vars['p'], $this->constant( 'meta_secret' ), TRUE ) )
				$posts = $wpdb->get_results( $wp_query->request );
		}

		return $posts;
	}

	public function page_row_actions( $actions, $post )
	{
		return $this->post_row_actions( $actions, $post );
	}

	public function post_row_actions( $actions, $post )
	{
		$allowed = $this->filters( 'preview_statuses', [ 'draft', 'pending', 'auto-draft', 'future' ] );

		if ( ! in_array( get_post_status( $post ), $allowed ) )
			return $actions;

		if ( $this->is_public( $post->ID ) )
			$actions['public_link'] = HTML::link(
				_x( 'Public Preview', 'Action', 'geditorial-drafts' ),
				$this->get_preview_url( $post->ID )
			);

		return $actions;
	}

	public function tweaks_column_attr( $post )
	{
		if ( ! $this->is_public( $post->ID ) )
			return;

		$link = $this->get_preview_url( $post->ID );

		echo '<li class="-row -drafts -preview-link">';
			echo $this->get_column_icon( FALSE, 'welcome-view-site', _x( 'Preview', 'Row Icon Title', 'geditorial-drafts' ) );

			echo HTML::tag( 'a', [
				'href'   => $link,
				'class'  => '-link',
				'target' => '_blank',
			], _x( 'Has public preview link', 'Row', 'geditorial-drafts' ) );

		echo '</li>';
	}
}

<?php namespace geminorum\gEditorial\Modules\Drafts;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Drafts extends gEditorial\Module
{
	use Internals\CoreAdmin;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'drafts',
			'title'  => _x( 'Drafts', 'Modules: Drafts', 'geditorial-admin' ),
			'desc'   => _x( 'Tools to Work With Drafts', 'Modules: Drafts', 'geditorial-admin' ),
			'icon'   => 'admin-post',
			'access' => 'stable',
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

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
				'adminbar_roles' => [ NULL, $roles ],
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
			'public_queryvar' => 'secret',
			'admin_queryvar'  => 'public-preview',
			'metakey_secret'  => '_public_preview_secret',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				/* translators: `%s`: count */
				'public_preview' => _n_noop(
					'Public Preview <span class="count -counted">(%s)</span>',
					'Public Previews <span class="count -counted">(%s)</span>',
					'geditorial-drafts'
				),
			],
		];

		return $strings;
	}

	// @REF: https://core.trac.wordpress.org/ticket/43739
	public function all_posttypes( $exclude = TRUE, $args = [ 'show_ui' => TRUE ] )
	{
		$posttypes = WordPress\PostType::get( 0, $args );
		$excluded  = Core\Arraay::prepString( $this->posttypes_excluded() );
		$viewables = [];

		if ( ! empty( $excluded ) )
			$posttypes = Core\Arraay::stripByKeys( $posttypes, $excluded );

		foreach ( $posttypes as $posttype => $label )
			if ( WordPress\PostType::viewable( $posttype ) )
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

	public function setup_ajax()
	{
		if ( ! $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			return;

		$this->coreadmin__hook_tweaks_column_attr( $posttype, 90 );
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

				$this->filter( 'display_post_states', 2 );

				if ( $this->get_setting( 'admin_rowactions' ) ) {

					$this->filter( 'page_row_actions', 2 );
					$this->filter( 'post_row_actions', 2 );
				}

				$this->_hook_posttype_views( $screen->post_type );
				$this->coreadmin__hook_tweaks_column_attr( $screen->post_type, 90 );
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
			'title' => _x( 'Drafts', 'Adminbar', 'geditorial-drafts' ).gEditorial\Ajax::spinner(),
			'meta'  => [ 'class' => $this->get_adminbar_node_class( $this->classs() ) ],
		];

		$this->enqueue_asset_js();
		$this->enqueue_styles();
	}

	public function do_ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'private':

				if ( empty( $post['post_id'] ) )
					gEditorial\Ajax::errorMessage();

				if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
					gEditorial\Ajax::errorUserCant();

				if ( ! $this->nonce_verify( $post['post_id'], $post['nonce'] ) )
					self::cheatin();

				if ( ! $this->make_private( $post['post_id'] ) )
					gEditorial\Ajax::errorMessage( __( 'Unable to make preview private. Please try again.', 'geditorial-drafts' ) );

				gEditorial\Ajax::success();

			break;
			case 'public':

				if ( empty( $post['post_id'] ) )
					gEditorial\Ajax::errorMessage();

				if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
					gEditorial\Ajax::errorUserCant();

				if ( ! $this->nonce_verify( $post['post_id'], $post['nonce'] ) )
					self::cheatin();

				if ( ! $link = $this->make_public( $post['post_id'] ) )
					gEditorial\Ajax::errorMessage( __( 'Unable to make preview public. Please try again.', 'geditorial-drafts' ) );

				gEditorial\Ajax::success( $link );

			break;
			case 'list':

				gEditorial\Ajax::checkReferer( $this->hook() );

				if ( ! $this->role_can( 'adminbar' ) )
					gEditorial\Ajax::errorUserCant();

				gEditorial\Ajax::success( $this->drafts_list() );
		}

		gEditorial\Ajax::errorWhat();
	}

	private function drafts_list()
	{
		$html = '';
		/* translators: `%s`: drafts count */
		$all  = _x( 'View all %s drafts', 'Header', 'geditorial-drafts' );
		$user = 'all' == $this->get_setting( 'summary_scope', 'all' ) ? 0 : get_current_user_id();

		foreach ( $this->posttypes() as $posttype ) {

			$object = WordPress\PostType::object( $posttype );

			if ( ! current_user_can( $object->cap->edit_posts ) )
				continue;

			$block = '';

			foreach ( $this->get_drafts( $posttype, $user ) as $post ) {

				$block.= '<li>'.gEditorial\Helper::getPostTitleRow( $post, 'edit', FALSE,
					gEditorial\Datetime::postModified( $post, TRUE ) ).'</li>';

				// FIXME: add author suffix
			}

			if ( ! $block )
				continue; // FIXME: add new post-type link

			$link = Core\HTML::tag( 'a', [
				'href'  => WordPress\PostType::edit( $posttype, [ 'post_status' => 'draft', 'author' => $user ] ),
				'title' => sprintf( $all, $object->labels->singular_name ),
			], Core\HTML::escape( $object->labels->name ) );

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

		$query = new \WP_Query();
		return $query->query( $args );
	}

	// @SEE: `is_post_status_viewable()`
	// @SEE: `is_post_publicly_viewable()`
	// TODO: supported statuses must be optional via settings
	private function _is_preview_status( $post )
	{
		$statuses = $this->filters( 'preview_statuses', [
			'draft',
			'pending',
			'auto-draft',
			'future',
		], $post );

		return (bool) in_array( get_post_status( $post ), $statuses, TRUE );
	}

	public function post_submitbox_minor_actions( $post )
	{
		if ( ! $this->_is_preview_status( $post ) )
			return;

		$public = $this->is_public( $post->ID );
		$nonce  = $this->nonce_create( $post->ID );

		echo '<div class="geditorial-admin-wrap -drafts">';

		echo Core\HTML::tag( 'input', [
			'type'     => 'text',
			'value'    => $this->get_preview_url( $post->ID ),
			'style'    => $public ? FALSE : 'display:none;',
			'class'    => [ 'widefat', '-link' ],
			'readonly' => TRUE,
			'onclick'  => 'this.focus();this.select()',
		] );

		echo gEditorial\Ajax::spinner();

		echo Core\HTML::tag( 'a', [
			'href'  => '#',
			'class' => Core\HTML::buttonClass( TRUE, [ '-action', '-after-private' ] ),
			'style' => $public ? 'display:none;' : FALSE,
			'data'  => [
				'id'     => $post->ID,
				'action' => 'public',
				'nonce'  => $nonce,
			],
		], _x( 'Make Preview Public', 'Button', 'geditorial-drafts' ) );

		echo Core\HTML::tag( 'a', [
			'href'  => '#',
			'class' => Core\HTML::buttonClass( TRUE, [ '-action', '-after-public' ] ),
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
			$key = get_post_meta( $post_id, $this->constant( 'metakey_secret' ), TRUE );

		return $key ? add_query_arg( $this->constant( 'public_queryvar' ), $key, $url ) : $url;
	}

	public function make_private( $post_id )
	{
		return delete_post_meta( $post_id, $this->constant( 'metakey_secret' ) );
	}

	public function make_public( $post_id )
	{
		$key   = wp_generate_password( 6, FALSE, FALSE );
		$added = add_post_meta( $post_id, $this->constant( 'metakey_secret' ), $key, TRUE );

		return $added ? $this->get_preview_url( $post_id, $key ) : FALSE;
	}

	public function is_public( $post_id )
	{
		return strlen( get_post_meta( $post_id, $this->constant( 'metakey_secret' ), TRUE ) ) > 0;
	}

	public function the_posts( $posts, $query )
	{
		global $wpdb;

		if ( ! empty( $posts ) || ! $query->is_main_query() )
			return $posts;

		if ( empty( $query->query_vars['p'] ) || ! ( $query->is_single || $query->is_page ) )
			return $posts;

		if ( ! $arg = self::req( $this->constant( 'public_queryvar' ) ) )
			return $posts;

		if ( ! $secret = get_post_meta( $query->query_vars['p'], $this->constant( 'metakey_secret' ), TRUE ) )
			return $posts;

		if ( $secret !== $arg )
			return $posts;

		add_filter( 'wp_robots', 'wp_robots_no_robots' );

		return $wpdb->get_results( $query->request );
	}

	public function display_post_states( $states, $post )
	{
		$query = $this->constant( 'admin_queryvar' );

		if ( self::req( $query ) )
			return $states; // avoid on the view

		if ( ! $this->_is_preview_status( $post ) )
			return $states;

		if ( ! $this->is_public( $post->ID ) )
			return $states;

		$states[$query] = sprintf( '<span title="%2$s">%1$s</span>',
			_x( 'Public Preview', 'State', 'geditorial-drafts' ),
			sprintf(
				/* translators: `%s`: post title */
				_x( 'Open public preview of &#8220;%s&#8221;', 'State Title', 'geditorial-drafts' ),
				_draft_or_post_title( $post )
			)
		);

		return $states;
	}

	public function page_row_actions( $actions, $post )
	{
		return $this->post_row_actions( $actions, $post );
	}

	public function post_row_actions( $actions, $post )
	{
		if ( ! $this->_is_preview_status( $post ) )
			return $actions;

		if ( $this->is_public( $post->ID ) )
			$actions['public_link'] = Core\HTML::link(
				_x( 'Public Preview', 'Action', 'geditorial-drafts' ),
				$this->get_preview_url( $post->ID )
			);

		return $actions;
	}

	public function tweaks_column_attr( $post, $before, $after )
	{
		if ( ! $this->_is_preview_status( $post ) )
			return;

		if ( ! $this->is_public( $post->ID ) )
			return;

		$link = $this->get_preview_url( $post->ID );

		printf( $before, '-public-preview' );
			echo $this->get_column_icon( FALSE, 'welcome-view-site', _x( 'Preview', 'Row Icon Title', 'geditorial-drafts' ) );

			echo Core\HTML::tag( 'a', [
				'href'   => $link,
				'class'  => '-link',
				'target' => '_blank',
			], _x( 'Has public preview link', 'Row', 'geditorial-drafts' ) );

		echo $after;
	}

	private function _hook_posttype_views( $posttype )
	{
		add_filter( sprintf( 'views_edit-%s', $posttype ),
			function ( $views ) use ( $posttype ) {

				$ids = WordPress\PostType::getIDListByMetakey(
					$this->constant( 'metakey_secret' ), $posttype, [
						'post_status' => 'draft',
					] );

				if ( ! $count = count( $ids ) )
					return $views;

				$query = $this->constant( 'admin_queryvar' );

				$views[$query] = sprintf(
					'<a href="%s"%s>%s</a>',
					Core\HTML::escapeURL( add_query_arg( [ 'post_type' => $posttype, $query => 1 ], 'edit.php' ) ),
					'1' === self::req( $query ) ? ' class="current"  aria-current="page"' : '',
					$this->nooped_count( 'public_preview', $count )
				);

				return $views;
			} );

		add_action( 'pre_get_posts',
			function ( &$query ) {

				if ( ! $query->is_admin || ! $query->is_main_query() )
					return;

				if ( '1' === self::req( $this->constant( 'admin_queryvar' ) ) ) {

					$meta_query = $query->query_vars['meta_query'] ?? [];
					$meta_query[] = [
						'key'     => $this->constant( 'metakey_secret' ),
						'compare' => 'EXISTS',
					];

					$query->set( 'meta_query', $meta_query );
				}
			} );
	}
}

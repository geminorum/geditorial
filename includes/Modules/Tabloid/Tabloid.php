<?php namespace geminorum\gEditorial\Modules\Tabloid;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Tabloid extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\FramePageViews;

	protected $deafults = [
		'admin_rowactions' => TRUE,
	];

	public static function module()
	{
		return [
			'name'     => 'tabloid',
			'title'    => _x( 'Tabloid', 'Modules: Tabloid', 'geditorial-admin' ),
			'desc'     => _x( 'Custom Overview of Contents', 'Modules: Tabloid', 'geditorial-admin' ),
			'icon'     => 'analytics',
			'access'   => 'beta',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_roles'            => [
				'overview_roles' => [ _x( 'Roles that can view overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
				'prints_roles'   => [ _x( 'Roles that can print overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
				'reports_roles'  => [ _x( 'Roles that can export overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->filter( 'post_overview_pre_link', 3, 12, FALSE, 'nucleus' );
		$this->filter( 'term_overview_pre_link', 3, 12, FALSE, 'nucleus' );
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'overview' );
	}

	public function load_overview_adminpage( $context = 'overview' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->_make_linked_viewable();
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'overview', 'update' );
	}

	public function setup_ajax()
	{
		if ( $this->role_can( 'overview' ) && ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) ) )
			$this->rowactions__hook_mainlink_for_post( $posttype, 8, FALSE, TRUE );
	}

	/**
	 * Fires after the current screen has been set.
	 *
	 * @param object $screen
	 * @return void
	 */
	public function current_screen( $screen )
	{
		if ( in_array( $screen->base, [ 'post', 'edit' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'post' === $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& ( $html = $this->rowaction_get_mainlink_for_post( WordPress\Post::get(), 'page-title-action' ) ) ) {

						Services\HeaderButtons::register( $this->key, [
							'html'     => $html,
							'priority' => -99,
						] );

						gEditorial\Scripts::enqueueColorBox();
					}

				} else if ( 'edit' === $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& $this->rowactions__hook_mainlink_for_post( $screen->post_type, 8, FALSE, TRUE ) )
							gEditorial\Scripts::enqueueColorBox();
				}
			}

		} else if ( in_array( $screen->base, [ 'edit-tags', 'term' ], TRUE ) ) {

			if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

				if ( 'term' === $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& ( $html = $this->rowaction_get_mainlink_for_term( WordPress\Term::get(), 'page-title-action' ) ) ) {

						Services\HeaderButtons::register( $this->key, [
							'html'     => $html,
							'priority' => -9,
						] );

						gEditorial\Scripts::enqueueColorBox();
					}

				} else if ( 'edit-tags' === $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& $this->rowactions__hook_mainlink_for_term( $screen->taxonomy, 8, FALSE, TRUE, NULL, TRUE ) )
							gEditorial\Scripts::enqueueColorBox();
				}
			}
		}
	}

	public function rowaction_get_mainlink_for_post( $post, $extra = NULL, $icon = FALSE )
	{
		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return FALSE;

		if ( ! $text = $this->filters( 'post_action', $this->is_post_viewable( $post ) ? _x( 'Overview', 'Action', 'geditorial-tabloid' ) : FALSE, $post ) )
			return FALSE;

		if ( FALSE !== $icon )
			$text = Core\Text::glued( [ '%1$s', $text ] );

		return $this->framepage_get_mainlink_for_post( $post, [
			'title' => sprintf(
				/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
				_x( 'Overview of this %2$s', 'Post Row Action Title Attr', 'geditorial-tabloid' ),
				WordPress\Post::title( $post ),
				Services\CustomPostType::getLabel( $post, 'singular_name' )
			),
			'text'         => $text,
			'icon'         => $icon,
			'context'      => 'rowaction',
			'link_context' => 'overview',
			'maxwidth'     => '920px',
			'extra'        => $extra ?? [
				'-tabloid-overview',
			]
		] );
	}

	public function rowaction_get_mainlink_for_term( $term, $extra = NULL )
	{
		if ( ! $term || ! WordPress\Term::can( $term, 'assign_term' ) )
			return FALSE;

		if ( ! $text = $this->filters( 'term_action', $this->is_term_viewable( $term ) ? _x( 'Overview', 'Action', 'geditorial-tabloid' ) : FALSE, $term ) )
			return FALSE;

		return $this->framepage_get_mainlink_for_term( $term, [
			'title' => sprintf(
				/* translators: `%1$s`: current term name, `%2$s`: taxonomy singular name */
				_x( 'Overview of this %2$s', 'Term Row Action Title Attr', 'geditorial-tabloid' ),
				WordPress\Term::title( $term ),
				Services\CustomTaxonomy::getLabel( $term, 'singular_name' )
			),
			'text'         => $text,
			'context'      => 'rowaction',
			'link_context' => 'overview',
			'maxwidth'     => '920px',
			'extra'        => $extra ?? [
				'-tabloid-overview',
			]
		] );
	}

	public function post_overview_pre_link( $link, $post, $context )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $link;

		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return $link;

		return $this->get_adminpage_url( TRUE, [
			'linked'   => $post->ID,
			'target'   => 'post',
			'noheader' => 1,
		], 'overview' );
	}

	public function term_overview_pre_link( $link, $term, $context )
	{
		if ( ! $this->taxonomy_supported( $term->taxonomy ) )
			return $link;

		if ( ! WordPress\Term::can( $term, 'assign_term' ) )
			return $link;

		return $this->get_adminpage_url( TRUE, [
			'linked'   => $term->term_id,
			'target'   => 'term',
			'noheader' => 1,
		], 'overview' );
	}

	private function _make_linked_viewable()
	{
		if ( ! $linked = self::req( 'linked' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $linked ) )
			return FALSE;

		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return FALSE;

		return add_filter( 'is_post_type_viewable',
			function ( $is_viewable, $posttype ) use ( $post ) {
				return $posttype->name === $post->post_type ? TRUE : $is_viewable;
			}, 2, 99 );
	}

	protected function render_overview_content()
	{
		$this->framepageviews__render_context_content( 'overview' );
	}

	protected function framepageviews__handle_flags_for_post( $flags, $post, $context, $data )
	{
		if ( in_array( 'needs-barcode', $flags, TRUE ) )
			gEditorial\Scripts::enqueueJSBarcode();

		if ( in_array( 'needs-qrcode', $flags, TRUE ) )
			gEditorial\Scripts::enqueueQRCodeSVG();
	}

	protected function framepageviews__handle_flags_for_term( $flags, $term, $context, $data )
	{
		if ( in_array( 'needs-barcode', $flags, TRUE ) )
			gEditorial\Scripts::enqueueJSBarcode();

		if ( in_array( 'needs-qrcode', $flags, TRUE ) )
			gEditorial\Scripts::enqueueQRCodeSVG();
	}

	protected function framepageviews__prep_data_for_post( $data, $post, $context )
	{
		if ( $comments = Services\RestAPI::getCommentsResponse( $post, 'view' ) )
			$data['comments_rendered'] = ModuleHelper::prepCommentsforPost( $comments, $this->default_calendar() );

		$data['___sides'] = array_fill_keys( [
			'post',
			'meta',
			'terms',
			'custom',
			'comments',
		], '' );

		return $data;
	}

	protected function framepageviews__prep_hooks_for_post( $data, $post, $context )
	{
		return array_fill_keys( [
			'after-actions',
			'after-post',
			'after-meta',
			'after-term',
			'after-custom',
			'after-content',
			'after-comments',
		], '' );
	}

	protected function framepageviews__cleanup_data_for_post( $data, $post, $context )
	{
		unset( $data['comments_rendered'] );

		return $data;
	}

	protected function framepageviews__prep_data_for_term( $data, $term, $context )
	{
		$data['___sides'] = array_fill_keys( [
			'term',
			'meta',
			'terms',
			'custom',
		], '' );

		return $data;
	}

	protected function framepageviews__prep_hooks_for_term( $data, $term, $context )
	{
		return array_fill_keys( [
			'after-actions',
			'after-post',
			'after-meta',
			'after-term',
			'after-custom',
			'after-content',
		], '' );
	}
}

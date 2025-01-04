<?php namespace geminorum\gEditorial\Modules\Tabloid;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Tabloid extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\ViewEngines;

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
				'exports_roles'  => [ _x( 'Roles that can export overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->filter( 'post_overview_pre_link', 3, 12, FALSE, $this->base );
		$this->filter( 'term_overview_pre_link', 3, 12, FALSE, $this->base );
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
			$this->rowactions__hook_mainlink_for_post( $posttype, 8, FALSE, TRUE, NULL, TRUE );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->base, [ 'post', 'edit' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'post' == $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& ( $html = $this->rowaction_get_mainlink_for_post( WordPress\Post::get(), 'page-title-action button -button -header-button' ) ) ) {

						Services\HeaderButtons::register( $this->key, [
							'html'     => $html,
							'priority' => -9,
						] );

						Scripts::enqueueColorBox();
					}

				} else if ( 'edit' == $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& $this->rowactions__hook_mainlink_for_post( $screen->post_type, 8, FALSE, TRUE, NULL, TRUE ) )
							Scripts::enqueueColorBox();
				}
			}

		} else if ( in_array( $screen->base, [ 'edit-tags', 'term' ], TRUE ) ) {

			if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

				if ( 'term' == $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& ( $html = $this->rowaction_get_mainlink_for_term( WordPress\Term::get(), 'page-title-action button -button -header-button' ) ) ) {

						Services\HeaderButtons::register( $this->key, [
							'html'     => $html,
							'priority' => -9,
						] );

						Scripts::enqueueColorBox();
					}

				} else if ( 'edit-tags' == $screen->base ) {

					if ( $this->role_can( 'overview' )
						&& $this->rowactions__hook_mainlink_for_term( $screen->taxonomy, 8, FALSE, TRUE, NULL, TRUE ) )
							Scripts::enqueueColorBox();
				}
			}
		}
	}

	public function rowaction_get_mainlink_for_post( $post, $extra = NULL )
	{
		if ( ! $post || ! current_user_can( 'read', $post->ID ) )
			return FALSE;

		if ( ! $text = $this->filters( 'post_action', $this->is_post_viewable( $post ) ? _x( 'Overview', 'Action', 'geditorial-tabloid' ) : FALSE, $post ) )
			return FALSE;

		return $this->framepage_get_mainlink_for_post( $post, [
			'title' => sprintf(
				/* translators: %1$s: current post title, %2$s: posttype singular name */
				_x( 'Overview of this %2$s', 'Post Row Action Title Attr', 'geditorial-tabloid' ),
				WordPress\Post::title( $post ),
				Helper::getPostTypeLabel( $post, 'singular_name' )
			),
			'text'         => $text,
			'target'       => 'post',
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
				/* translators: %1$s: current term name, %2$s: taxonomy singular name */
				_x( 'Overview of this %2$s', 'Term Row Action Title Attr', 'geditorial-tabloid' ),
				WordPress\Term::title( $term ),
				Helper::getTaxonomyLabel( $term, 'singular_name' )
			),
			'text'         => $text,
			'target'       => 'term',
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

		if ( ! current_user_can( 'read', $post->ID ) )
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

		if ( ! current_user_can( 'read', $post->ID ) )
			return FALSE;

		add_filter( 'is_post_type_viewable',
			function ( $is_viewable, $posttype ) use ( $post ) {
				return $posttype->name === $post->post_type ? TRUE : $is_viewable;
			}, 2, 99 );
	}

	protected function render_overview_content()
	{
		if ( 'post' === self::req( 'target' ) ) {

			if ( ! $post = WordPress\Post::get( self::req( 'linked', FALSE ) ) )
				return Info::renderNoPostsAvailable();

			$this->_render_view_for_post( $post, 'overview' );

		} else if ( 'term' === self::req( 'target' ) ) {

			if ( ! $term = WordPress\Term::get( self::req( 'linked', FALSE ) ) )
				return Info::renderNoTermsAvailable();

			$this->_render_view_for_term( $term, 'overview' );

		} else {

			Info::renderNoDataAvailable();
		}
	}

	private function _render_view_for_post( $post, $context )
	{
		$part = $this->get_view_part_by_post( $post, $context );
		$data = $this->_get_view_data_for_post( $post, $context );

		$this->_handle_flags_for_post( $post, $context, $data );

		echo $this->wrap_open( '-view -'.$part );
			$this->actions( 'render_view_post_before', $post, $context, $data, $part );
			$this->render_view( $part, $data );
			$this->actions( 'render_view_post_after', $post, $context, $data, $part );
		echo '</div>';

		$data = $this->_cleanup_view_data_for_post( $post, $context, $data );

		$this->_print_script_for_post( $post, $context, $data );

		echo $this->wrap_open( '-debug -debug-data', TRUE, $this->classs( 'raw' ), TRUE );
			Core\HTML::tableSide( $data );
		echo '</div>';
	}

	private function _render_view_for_term( $term, $context )
	{
		$part = $this->get_view_part_by_term( $term, $context );
		$data = $this->_get_view_data_for_term( $term, $context );

		$this->_handle_flags_for_term( $term, $context, $data );

		echo $this->wrap_open( '-view -'.$part );
			$this->actions( 'render_view_term_before', $term, $context, $data, $part );
			$this->render_view( $part, $data );
			$this->actions( 'render_view_term_after', $term, $context, $data, $part );
		echo '</div>';

		$data = $this->_cleanup_view_data_for_term( $term, $context, $data );

		$this->_print_script_for_term( $term, $context, $data );

		echo $this->wrap_open( '-debug -debug-data', TRUE, $this->classs( 'raw' ), TRUE );
			Core\HTML::tableSide( $data );
		echo '</div>';
	}

	private function _handle_flags_for_post( $post, $context, $data )
	{
		if ( empty( $data['___flags'] ) )
			return;

		$flags = (array) $data['___flags'];

		if ( in_array( 'needs-barcode', $flags, TRUE ) )
			Scripts::enqueueJSBarcode();

		if ( in_array( 'needs-qrcode', $flags, TRUE ) )
			Scripts::enqueueQRCodeSVG();
	}

	private function _handle_flags_for_term( $term, $context, $data )
	{
		if ( empty( $data['___flags'] ) )
			return;

		$flags = (array) $data['___flags'];

		if ( in_array( 'needs-barcode', $flags, TRUE ) )
			Scripts::enqueueJSBarcode();

		if ( in_array( 'needs-qrcode', $flags, TRUE ) )
			Scripts::enqueueQRCodeSVG();
	}

	private function _print_script_for_post( $post, $context, $data )
	{
		Core\HTML::wrapScript( sprintf( 'window.%s = %s;',
			$this->hook( 'data' ),
			wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) )
		);

		$this->enqueue_asset_js( [
			'config' => [
				'printtitle'  => WordPress\Post::title( $post ),
				'printstyles' => Scripts::getPrintStylesURL(),
			],
		], $this->dotted( $context ), [
			'jquery',
			Scripts::pkgPrintThis(),
		] );
	}

	private function _print_script_for_term( $term, $context, $data )
	{
		Core\HTML::wrapScript( sprintf( 'window.%s = %s;',
			$this->hook( 'data' ),
			wp_json_encode( $data, JSON_UNESCAPED_UNICODE )
		) );

		$this->enqueue_asset_js( [
			'config' => [
				'printtitle'  => WordPress\Term::title( $term ),
				'printstyles' => Scripts::getPrintStylesURL(),
			],
		], $this->dotted( $context ), [
			'jquery',
			Scripts::pkgPrintThis(),
		] );
	}

	private function _get_view_data_for_post( $post, $context )
	{
		$data = [];

		if ( $response = Services\RestAPI::getPostResponse( $post, 'view' ) )
			$data = $response;

		if ( $comments = Services\RestAPI::getCommentsResponse( $post, 'view' ) )
			$data['comments_rendered'] = ModuleHelper::prepCommentsforPost( $comments );

		// fallback if `title` is not supported by the posttype
		if ( empty( $data['title'] ) )
			$data['title'] = [ 'rendered' => WordPress\Post::title( $post ) ];

		// strip the generated excerpt
		if ( empty( $data['excerpt']['raw'] ) )
			$data['excerpt']['rendered'] = '';

		$data = ModuleHelper::stripByProp( $data, 'meta_rendered', Core\Arraay::prepString( $this->filters( 'post_meta_exclude_rendered', [], $post, $context ) ) );
		$data = ModuleHelper::stripByProp( $data, 'terms_rendered', Core\Arraay::prepString( $this->filters( 'post_terms_exclude_rendered', [], $post, $context ) ) );

		$data = ModuleHelper::stripEmptyValues( $data, 'meta_rendered' );
		$data = ModuleHelper::stripEmptyValues( $data, 'terms_rendered' );

		$data['__direction']  = Core\HTML::rtl() ? 'rtl' : 'ltr';
		$data['__can_edit']   = WordPress\Post::can( $post, 'edit_post' ) ? get_edit_post_link( $post ) : FALSE;
		$data['__can_debug']  = Core\WordPress::isDev() || WordPress\User::isSuperAdmin();
		$data['__can_print']  = $this->role_can( 'prints' );
		$data['__can_export'] = $this->role_can( 'exports' );
		$data['__today']      = Datetime::dateFormat( 'now', 'print' );
		$data['__summaries']  = $this->filters( 'post_summaries', [], $data, $post, $context );
		$data['___flags']     = $this->filters( 'post_flags', [], $data, $post, $context );
		$data['___sides']     = array_fill_keys( [ 'post', 'meta', 'terms', 'custom', 'comments' ], '' );
		$data['___hooks']     = array_fill_keys( [
			'after-actions',
			'after-post',
			'after-meta',
			'after-term',
			'after-custom',
			'after-content',
			'after-comments',
		], '' );

		return $this->filters( 'view_data_for_post', $data, $post, $context );
	}

	private function _cleanup_view_data_for_post( $post, $context, $data )
	{
		unset( $data['meta_rendered'] );
		unset( $data['units_rendered'] );
		unset( $data['terms_rendered'] );
		unset( $data['comments_rendered'] );

		unset( $data['_links'] );

		unset( $data['___flags'] );
		unset( $data['___sides'] );
		unset( $data['___hooks'] );
		unset( $data['__summaries'] );
		unset( $data['__direction'] );
		unset( $data['__can_edit'] );
		unset( $data['__can_debug'] );
		unset( $data['__can_print'] );
		unset( $data['__can_export'] );
		unset( $data['__today'] );

		return $this->filters( 'cleanup_view_data_for_post', $data, $post, $context );
	}

	private function _get_view_data_for_term( $term, $context )
	{
		$data = [];

		if ( $response = Services\RestAPI::getTermResponse( $term, 'view' ) )
			$data = $response;

		// TODO: count by post-type: see `coreadmin__hook_taxonomy_multiple_supported_column()`
		// TODO: list assigned posts: see `tabloid_post_summaries_paired_posttype()`

		$data = ModuleHelper::stripByProp( $data, 'meta_rendered', Core\Arraay::prepString( $this->filters( 'term_meta_exclude_rendered', [], $term, $context ) ) );
		$data = ModuleHelper::stripByProp( $data, 'terms_rendered', Core\Arraay::prepString( $this->filters( 'term_terms_exclude_rendered', [], $term, $context ) ) );

		$data = ModuleHelper::stripEmptyValues( $data, 'meta_rendered' );
		$data = ModuleHelper::stripEmptyValues( $data, 'terms_rendered' );

		$data['__direction']  = Core\HTML::rtl() ? 'rtl' : 'ltr';
		$data['__can_edit']   = WordPress\Term::can( $term, 'edit_term' ) ? get_edit_term_link( $term ) : FALSE;
		$data['__can_debug']  = Core\WordPress::isDev() || WordPress\User::isSuperAdmin();
		$data['__can_print']  = $this->role_can( 'prints' );
		$data['__can_export'] = $this->role_can( 'exports' );
		$data['__today']      = Datetime::dateFormat( 'now', 'print' );
		$data['__summaries']  = $this->filters( 'term_summaries', [], $data, $term, $context );
		$data['___flags']     = $this->filters( 'term_flags', [], $data, $term, $context );
		$data['___sides']     = array_fill_keys( [ 'term', 'meta', 'terms', 'custom' ], '' );
		$data['___hooks']     = array_fill_keys( [
			'after-actions',
			'after-post',
			'after-meta',
			'after-term',
			'after-custom',
			'after-content',
		], '' );

		return $this->filters( 'view_data_for_term', $data, $term, $context );
	}

	private function _cleanup_view_data_for_term( $term, $context, $data )
	{
		unset( $data['meta_rendered'] );
		unset( $data['units_rendered'] );
		unset( $data['terms_rendered'] );

		unset( $data['_links'] );

		unset( $data['___flags'] );
		unset( $data['___sides'] );
		unset( $data['___hooks'] );
		unset( $data['__summaries'] );
		unset( $data['__direction'] );
		unset( $data['__can_edit'] );
		unset( $data['__can_debug'] );
		unset( $data['__can_print'] );
		unset( $data['__can_export'] );
		unset( $data['__today'] );

		return $this->filters( 'cleanup_view_data_for_term', $data, $term, $context );
	}
}

<?php namespace geminorum\gEditorial\Modules\Tabloid;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
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

	// TODO: roles for each supported posttypes
	protected function get_global_settings()
	{
		$settings = [];
		$roles    = $this->get_settings_default_roles();

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $this->list_posttypes() as $posttype_name => $posttype_label ) {

			$settings['_posttypes'][] = [
				'field'       => sprintf( 'posttype_%s_action_title', $posttype_name ),
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Action Title for %s', 'Setting Title', 'geditorial-tabloid' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Used as title on the actions row.', 'Setting Description', 'geditorial-tabloid' ),
				'placeholder' => _x( 'Overview', 'Action', 'geditorial-tabloid' ) ,
			];

			$settings['_posttypes'][] = [
				'field'       => sprintf( 'posttype_%s_overview_title', $posttype_name ),
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Overview Title for %s', 'Setting Title', 'geditorial-tabloid' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Used as title on the overview pages.', 'Setting Description', 'geditorial-tabloid' ),
				'placeholder' => _x( 'Overview', 'Action', 'geditorial-tabloid' ) ,
			];
		}

		$settings['_roles'] = [
			'overview_roles' => [ _x( 'Roles that can view posttype overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
			'prints_roles'   => [ _x( 'Roles that can print posttype overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
			'exports_roles'  => [ _x( 'Roles that can export posttype overviews.', 'Setting Description', 'geditorial-tabloid' ), $roles ],
		];

		return $settings;
	}

	public function init()
	{
		parent::init();

		$this->filter( 'post_overview_pre_link', 3, 12, FALSE, $this->base );
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
			$this->rowactions__hook_mainlink_for_post( $posttype, 8, TRUE, NULL, TRUE );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( $this->role_can( 'overview' )
					&& ( $link = $this->rowaction_get_mainlink_for_post( WordPress\Post::get(), 'page-title-action' ) ) ) {

					$this->enqueue_asset_js( [ 'button' => $link ], $screen );
					Scripts::enqueueColorBox();
				}

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( 'overview' )
					&& $this->rowactions__hook_mainlink_for_post( $screen->post_type, 8, TRUE, NULL, TRUE ) )
						Scripts::enqueueColorBox();
			}
		}
	}

	public function rowaction_get_mainlink_for_post( $post, $extra = NULL )
	{
		if ( ! $post || ! current_user_can( 'read', $post->ID ) )
			return FALSE;

		$custom = $this->get_setting_fallback( sprintf( 'posttype_%s_action_title', $post->post_type ),
			_x( 'Overview', 'Action', 'geditorial-tabloid' ) );

		if ( ! $filtered = $this->filters( 'action', $this->is_post_viewable( $post ) ? $custom : FALSE, $post ) )
			return FALSE;

		return $this->framepage_get_mainlink_for_post( $post, [
			'title'        => $this->get_setting( sprintf( 'posttype_%s_overview_title', $post->post_type ), $filtered ),
			'text'         => $filtered,
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
		if ( ! $linked = self::req( 'linked' ) )
			return Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $linked ) )
			return Info::renderNoPostsAvailable();

		$this->_render_view_for_post( $post, 'overview' );
	}

	private function _render_view_for_post( $post, $context )
	{
		$part = $this->get_view_part_by_post( $post, $context );
		$data = $this->_get_view_data_for_post( $post, $context );

		$this->_handle_flags_for_post( $post, $context, $data );

		echo $this->wrap_open( '-view -'.$part );
			$this->actions( 'render_view_before', $post, $context, $data, $part );
			$this->render_view( $part, $data );
			$this->actions( 'render_view_after', $post, $context, $data, $part );
		echo '</div>';

		$data = $this->_cleanup_view_data( $post, $context, $data );

		$this->_print_script_for_post( $post, $context, $data );

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

	private function _print_script_for_post( $post, $context, $data )
	{
		Core\HTML::wrapScript( sprintf( 'window.%s = %s;', $this->hook( 'data' ), wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) ) );

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

		$data = ModuleHelper::stripEmptyValues( $data, 'meta_rendered' );
		$data = ModuleHelper::stripEmptyValues( $data, 'terms_rendered' );

		$data['__direction']  = Core\HTML::rtl() ? 'rtl' : 'ltr';
		$data['__can_debug']  = Core\WordPress::isDev() || Core\User::isSuperAdmin();
		$data['__can_print']  = $this->role_can( 'prints' );
		$data['__can_export'] = $this->role_can( 'exports' );
		$data['__today']      = Datetime::dateFormat( 'now', 'print' );
		$data['__summaries']  = $this->filters( 'post_summaries', [], $data, $post, $context );
		$data['___flags']     = $this->filters( 'post_flags', [], $data, $post, $context );
		$data['___sides']     = array_fill_keys( [ 'post', 'meta', 'term', 'custom', 'comments' ], '' );
		$data['___hooks']     = array_fill_keys( [
			'after-actions',
			'after-post',
			'after-meta',
			'after-term',
			'after-custom',
			'after-content',
			'after-comments',
		], '' );

		return $this->filters( 'view_data', $data, $post, $context );
	}

	private function _cleanup_view_data( $post, $context, $data )
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
		unset( $data['__can_debug'] );
		unset( $data['__can_print'] );
		unset( $data['__can_export'] );
		unset( $data['__today'] );

		return $this->filters( 'cleanup_view_data', $data, $post, $context );
	}
}

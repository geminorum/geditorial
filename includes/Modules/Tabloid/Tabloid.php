<?php namespace geminorum\gEditorial\Modules\Tabloid;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class Tabloid extends gEditorial\Module
{
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\ViewEngines;

	public static function module()
	{
		return [
			'name'     => 'tabloid',
			'title'    => _x( 'Tabloid', 'Modules: Tabloid', 'geditorial' ),
			'desc'     => _x( 'Custom Overview of Contents', 'Modules: Tabloid', 'geditorial' ),
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
			[
				'field'       => 'overview_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-tabloid' ),
				'description' => _x( 'Roles that can view posttype overviews.', 'Setting Description', 'geditorial-tabloid' ),
				'values'      => $roles,
			],
			[
				'field'       => 'print_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Print Roles', 'Setting Title', 'geditorial-tabloid' ),
				'description' => _x( 'Roles that can print posttype overviews.', 'Setting Description', 'geditorial-tabloid' ),
				'values'      => $roles,
			],
			[
				'field'       => 'export_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Export Roles', 'Setting Title', 'geditorial-tabloid' ),
				'description' => _x( 'Roles that can export posttype overviews.', 'Setting Description', 'geditorial-tabloid' ),
				'values'      => $roles,
			],
		];

		return $settings;
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'overview' );
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'overview', 'update' );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				// TODO

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( 'overview' )
					&& $this->rowactions__hook_mainlink( $screen, 9, NULL, TRUE ) )
						Scripts::enqueueColorBox();
			}
		}
	}

	public function rowaction_get_mainlink( $post )
	{
		if ( ! current_user_can( 'read', $post->ID ) )
			return FALSE;

		$custom = $this->get_setting_fallback( sprintf( 'posttype_%s_action_title', $post->post_type ),
			_x( 'Overview', 'Action', 'geditorial-tabloid' ) );

		if ( ! $filtred = $this->filters( 'action', $this->is_post_viewable( $post ) ? $custom : FALSE, $post ) )
			return FALSE;

		return $this->framepage_get_mainlink( $post, [
			'title'        => $this->get_setting( sprintf( 'posttype_%s_overview_title', $post->post_type ), $filtred ),
			'text'         => $filtred,
			'context'      => 'rowaction',
			'link_context' => 'overview',
			'maxwidth'     => '920px',
			'extra'        => [
				'-tabloid-overview',
			]
		] );
	}

	protected function render_overview_content()
	{
		if ( ! $post = self::req( 'linked' ) )
			return Core\HTML::desc( _x( 'There are no posts available!', 'Message', 'geditorial-tabloid' ) );

		if ( ! $post = WordPress\Post::get( $post ) )
			return Core\HTML::desc( _x( 'There are no posts available!', 'Message', 'geditorial-tabloid' ) );

		$this->_render_view( $post, 'overview' );
	}

	private function _render_view( $post, $context )
	{
		$data = $this->_get_view_data( $post, $context );
		$part = $this->_get_view_part( $post, $context );

		echo $this->wrap_open( '-view -'.$part );
			$this->actions( 'render_view_before', $post, $context, $data, $part );
			$this->render_view( $part, $data );
			$this->actions( 'render_view_after', $post, $context, $data, $part );
		echo '</div>';

		$this->_print_script( $post, $context, $data );

		echo $this->wrap_open( '-debug -debug-data', TRUE, $this->classs( 'raw' ), TRUE );
			Core\HTML::tableSide( $data );
		echo '</div>';
	}

	private function _print_script( $post, $context, $data )
	{
		unset( $data['__direction'] );
		unset( $data['__can_debug'] );
		unset( $data['__can_print'] );
		unset( $data['__can_export'] );
		unset( $data['_links'] );

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

	private function _get_view_part( $post, $context )
	{
		$part = sprintf( '%s-type-%s', $context, $post->post_type );

		if ( ! is_readable( $this->get_view_path( $part ) ) )
			$part = sprintf( '%s-type-default', $context );

		return $this->filters( 'view_part', $part, $post, $context );
	}

	private function _get_view_data( $post, $context )
	{
		$data = [];

		if ( $route = WordPress\Post::getRestRoute( $post ) )
			$data = WordPress\Rest::doInternalRequest( $route, [ 'context' => 'edit' ] );

		// fallback if `title` is not supported by the posttype
		if ( empty( $data['title'] ) )
			$data['title'] = [ 'rendered' => WordPress\Post::title( $post ) ];

		// strip the generated excerpt
		if ( empty( $data['excerpt']['raw'] ) )
			$data['excerpt']['rendered'] = '';

		// strip empty meta values
		if ( ! empty( $data['meta_rendered'] ) ) {

			foreach ( $data['meta_rendered'] as $offset => $meta )
				if ( empty( $meta['rendered'] ) )
					unset( $data['meta_rendered'][$offset] );

			if ( ! empty( $data['meta_rendered'] ) )
				$data['meta_rendered'] = array_values( $data['meta_rendered'] );
		}

		// strip empty term values
		if ( ! empty( $data['terms_rendered'] ) ) {

			foreach ( $data['terms_rendered'] as $offset => $meta )
				if ( empty( $meta['rendered'] ) )
					unset( $data['terms_rendered'][$offset] );

			if ( ! empty( $data['terms_rendered'] ) )
				$data['terms_rendered'] = array_values( $data['terms_rendered'] );
		}

		$data['__direction']  = Core\HTML::rtl() ? 'rtl' : 'ltr';
		$data['__can_debug']  = Core\WordPress::isDev() || Core\User::isSuperAdmin();
		$data['__can_print']  = $this->role_can( 'print' );
		$data['__can_export'] = $this->role_can( 'export' );

		return $this->filters( 'view_data', $data, $post, $context );
	}
}

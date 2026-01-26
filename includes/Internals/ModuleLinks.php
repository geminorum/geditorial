<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait ModuleLinks
{

	// TODO: get dashboard menu for the module
	protected function get_module_links()
	{
		$links  = [];
		$screen = get_current_screen();

		if ( method_exists( $this, 'reports_settings' ) && ! gEditorial\Settings::isScreenContext( 'reports', $screen ) )
			foreach ( $this->append_sub( [], 'reports' ) as $sub => $title )
				$links[] = [
					'context' => 'reports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'reports', $sub ),
					'title'   => sprintf(
						/* translators: `%s`: subtitle */
						_x( '%s Reports', 'Module: Extra Link: Reports', 'geditorial-admin' ),
						is_array( $title ) ? $title['title'] : $title,
					),
				];

		if ( method_exists( $this, 'tools_settings' ) && ! gEditorial\Settings::isScreenContext( 'tools', $screen ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$links[] = [
					'context' => 'tools',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'tools', $sub ),
					'title'   => sprintf(
						/* translators: `%s`: subtitle */
						_x( '%s Tools', 'Module: Extra Link: Tools', 'geditorial-admin' ),
						is_array( $title ) ? $title['title'] : $title,
					),
				];

		if ( method_exists( $this, 'roles_settings' ) && ! gEditorial\Settings::isScreenContext( 'roles', $screen ) )
			foreach ( $this->append_sub( [], 'roles' ) as $sub => $title )
				$links[] = [
					'context' => 'roles',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'roles', $sub ),
					'title'   => sprintf(
						/* translators: `%s`: subtitle */
						_x( '%s Roles', 'Module: Extra Link: Roles', 'geditorial-admin' ),
						is_array( $title ) ? $title['title'] : $title,
					),
				];

		if ( method_exists( $this, 'imports_settings' ) && ! gEditorial\Settings::isScreenContext( 'imports', $screen ) )
			foreach ( $this->append_sub( [], 'imports' ) as $sub => $title )
				$links[] = [
					'context' => 'imports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'imports', $sub ),
					'title'   => sprintf(
						/* translators: `%s`: subtitle */
						_x( '%s Imports', 'Module: Extra Link: Tools', 'geditorial-admin' ),
						is_array( $title ) ? $title['title'] : $title,
					),
				];

		if ( isset( $this->caps['settings'] ) && ! gEditorial\Settings::isScreenContext( 'settings', $screen ) && $this->cuc( 'settings' ) )
			$links[] = [
				'context' => 'settings',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $this->get_module_url( 'settings' ),
				'title'   => sprintf(
					/* translators: `%s`: module title */
					_x( '%s Settings', 'Module: Extra Link: Settings', 'geditorial-admin' ),
					$this->module->title
				),
			];

		if ( GEDITORIAL_DISABLE_HELP_TABS )
			return $links;

		if ( $docs = $this->get_module_url( 'docs' ) )
			$links[] = [
				'context' => 'docs',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $docs,
				'title'   => sprintf(
					/* translators: `%s`: module title */
					_x( '%s Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
					$this->module->title
				),
			];

		if ( 'config' != $this->module->name )
			$links[] = [
				'context' => 'docs',
				'sub'     => FALSE,
				'text'    => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
				'url'     => gEditorial\Settings::getModuleDocsURL( FALSE ),
				'title'   => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
			];

		return $links;
	}

	public function get_module_url( $context = NULL, $sub = NULL, $extra = [] )
	{
		$sub     = $sub     ?? $this->key;
		$context = $context ?? $this->default_link_context;

		switch ( $context ) {
			case 'tools'    :
			case 'rols'     :
			case 'imports'  :
			case 'reports'  : $url = gEditorial\Settings::getURLbyContext( $context ); break;
			case 'config'   : $url = gEditorial\Settings::getURLbyContext( 'settings' ); break;
			case 'settings' : $url = gEditorial\Settings::getURLbyContext( 'settings', TRUE, [ 'module' => $this->module->name ] ); $sub = FALSE; break;
			case 'docs'     : $url = gEditorial\Settings::getModuleDocsURL( $this->module ); $sub = FALSE; break;
			case 'listtable': $url = $this->get_adminpage_url( TRUE, [], 'adminmenu' ); $sub = FALSE; break;
			     default    : $url = Core\URL::current();
		}

		if ( FALSE === $url )
			return FALSE;

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	// @SEE: `Settings::getURLbyContext()`
	protected function get_adminpage_url( $full = TRUE, $extra = [], $context = 'mainpage', $admin_base = NULL )
	{
		$page = in_array( $context, [ 'mainpage', 'adminmenu' ], TRUE )
			? $this->classs()
			: $this->classs( $context );

		if ( ! $full )
			return $page;

		if ( is_null( $admin_base ) )
			$admin_base = in_array( $context, [ 'adminmenu', 'printpage', 'framepage', 'newpost', 'importitems' ], TRUE )
				? get_admin_url( NULL, 'index.php' )
				: get_admin_url( NULL, 'admin.php' );

		return add_query_arg( array_merge( [ 'page' => $page ], $extra ), $admin_base );
	}

	// DEFAULT METHOD
	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		if ( $this->_paired && method_exists( $this, 'paired_get_constants' ) ) {

			// if ( $constants = $this->paired_get_constants() )
			// 	return $this->paired_do_get_to_posts( $constants[0], $constants[1], $post, $single, $published );

			// NOTE: `published` status is the only available on non-admin
			if ( $linked = $this->paired_all_connected_from( $post, NULL ) )
				return $single ? reset( $linked ) : $linked;
		}

		return FALSE;
	}

	protected function modulelinks__hook_calendar_linked_post( $screen = NULL, $module = NULL )
	{
		$module = $module ?? 'schedule';

		if ( ! gEditorial\Settings::isScreenContext( $module, $screen ) )
			return FALSE;

		add_filter( $this->classs_base( $module, 'post_row_title' ),
			function ( $title, $post, $the_day, $calendar_args ) use ( $module ) {

				if ( ! $this->posttype_supported( $post->post_type ) )
					return $title;

				if ( ! $linked = $this->get_linked_to_posts( $post->ID, TRUE ) )
					return $title;

				return sprintf( '%s – %s', $title, WordPress\Post::title( $linked ) );

			}, 12, 4 );

		return TRUE;
	}

	protected function modulelinks__register_headerbuttons( $contexts = NULL )
	{
		if ( is_null( $contexts ) ) {

			$screen   = get_current_screen();
			$contexts = [];

			if ( $this->module->configure && ! gEditorial\Settings::isScreenContext( 'settings', $screen ) )
				$contexts['settings'] = _x( 'Settings', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'reports_settings' ) && ! gEditorial\Settings::isScreenContext( 'reports', $screen ) )
				$contexts['reports'] = _x( 'Reports', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'tools_settings' ) && ! gEditorial\Settings::isScreenContext( 'tools', $screen ) )
				$contexts['tools'] = _x( 'Tools', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'roles_settings' ) && ! gEditorial\Settings::isScreenContext( 'roles', $screen ) )
				$contexts['roles'] = _x( 'Roles', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'imports_settings' ) && ! gEditorial\Settings::isScreenContext( 'imports', $screen ) )
				$contexts['imports'] = _x( 'Imports', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );
		}

		foreach ( $contexts as $context => $text ) {

			if ( ! $this->cuc( $context, 'manage_options' ) )
				return FALSE;

			$args = [
				'text'     => $text,
				'link'     => $this->get_module_url( $context ),
				'title'    => sprintf( '%s :: %s', $this->module->title, $this->module->desc ),
				'icon'     => $this->modulelinks__get_context_icon( $context, FALSE ),
				'class'    => '-link-to-'.$context,
				'priority' => 20,
			];

			Services\HeaderButtons::register( sprintf( 'module_%s', $context ), $args );
		}

		return $contexts;
	}

	protected function modulelinks__get_context_icon( $context, $fallback = NULL )
	{
		switch ( $context ) {
			case 'settings': return $this->module->icon;
			case 'reports' : return 'chart-pie';
			case 'tools'   : return 'admin-tools';
			case 'roles'   : return 'groups';
			case 'imports' : return 'upload';
		}

		return $fallback ?? 'screenoptions';
	}

	protected function modulelinks__register_posttype_export_headerbuttons( $constant_or_posttype, $context = 'reports', $check = TRUE )
	{
		if ( ! method_exists( $this, 'exports_get_export_links' ) )
			return FALSE;

		if ( $check && ! $this->cuc( $context ) )
			return FALSE;

		$posttype = $this->constant( $constant_or_posttype, $constant_or_posttype );
		$links    = $this->exports_get_export_links( $posttype, $context, 'posttype' );
		$icon     = Services\Icons::get( 'download' );

		foreach ( $links as $name => $url )
			Services\HeaderButtons::register( $name, [
				'html'     => $url,
				'icon'     => $icon,
				'priority' => 800,
			] );

		return $posttype;
	}
}

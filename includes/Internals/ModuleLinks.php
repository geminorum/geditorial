<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait ModuleLinks
{

	// TODO: get dashboard menu for the module
	protected function get_module_links()
	{
		$links  = [];
		$screen = get_current_screen();

		if ( method_exists( $this, 'reports_settings' ) && ! Settings::isReports( $screen ) )
			foreach ( $this->append_sub( [], 'reports' ) as $sub => $title )
				$links[] = [
					'context' => 'reports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'reports', $sub ),
					/* translators: `%s`: sub title */
					'title'   => sprintf( _x( '%s Reports', 'Module: Extra Link: Reports', 'geditorial-admin' ), $title ),
				];

		if ( method_exists( $this, 'tools_settings' ) && ! Settings::isTools( $screen ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$links[] = [
					'context' => 'tools',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'tools', $sub ),
					/* translators: `%s`: sub title */
					'title'   => sprintf( _x( '%s Tools', 'Module: Extra Link: Tools', 'geditorial-admin' ), $title ),
				];

		if ( method_exists( $this, 'roles_settings' ) && ! Settings::isRoles( $screen ) )
			foreach ( $this->append_sub( [], 'roles' ) as $sub => $title )
				$links[] = [
					'context' => 'roles',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'roles', $sub ),
					/* translators: `%s`: sub title */
					'title'   => sprintf( _x( '%s Roles', 'Module: Extra Link: Roles', 'geditorial-admin' ), $title ),
				];

		if ( method_exists( $this, 'imports_settings' ) && ! Settings::isImports( $screen ) )
			foreach ( $this->append_sub( [], 'imports' ) as $sub => $title )
				$links[] = [
					'context' => 'imports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'imports', $sub ),
					/* translators: `%s`: sub title */
					'title'   => sprintf( _x( '%s Imports', 'Module: Extra Link: Tools', 'geditorial-admin' ), $title ),
				];

		if ( isset( $this->caps['settings'] ) && ! Settings::isSettings( $screen ) && $this->cuc( 'settings' ) )
			$links[] = [
				'context' => 'settings',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $this->get_module_url( 'settings' ),
				/* translators: `%s`: module title */
				'title'   => sprintf( _x( '%s Settings', 'Module: Extra Link: Settings', 'geditorial-admin' ), $this->module->title ),
			];

		if ( GEDITORIAL_DISABLE_HELP_TABS )
			return $links;

		if ( $docs = $this->get_module_url( 'docs' ) )
			$links[] = [
				'context' => 'docs',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $docs,
				/* translators: `%s`: module title */
				'title'   => sprintf( _x( '%s Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ), $this->module->title ),
			];

		if ( 'config' != $this->module->name )
			$links[] = [
				'context' => 'docs',
				'sub'     => FALSE,
				'text'    => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
				'url'     => Settings::getModuleDocsURL( FALSE ),
				'title'   => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
			];

		return $links;
	}

	public function get_module_url( $context = NULL, $sub = NULL, $extra = [] )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		if ( is_null( $context ) )
			$context = 'reports'; // TODO get from module class static: this is the default module link!

		switch ( $context ) {
			case 'config'    : $url = Settings::settingsURL(); break;
			case 'reports'   : $url = Settings::reportsURL(); break;
			case 'tools'     : $url = Settings::toolsURL(); break;
			case 'rols'      : $url = Settings::rolesURL(); break;
			case 'imports'   : $url = Settings::importsURL(); break;
			case 'docs'      : $url = Settings::getModuleDocsURL( $this->module ); $sub = FALSE; break;
			case 'settings'  : $url = add_query_arg( 'module', $this->module->name, Settings::settingsURL() ); $sub = FALSE; break;
			case 'listtable' : $url = $this->get_adminpage_url( TRUE, [], 'adminmenu' ); $sub = FALSE; break;
			default          : $url = Core\URL::current();
		}

		if ( FALSE === $url )
			return FALSE;

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

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

	protected function modulelinks__register_headerbuttons( $contexts = NULL )
	{
		if ( is_null( $contexts ) ) {

			$screen   = get_current_screen();
			$contexts = [];

			if ( $this->module->configure && ! Settings::isSettings( $screen ) )
				$contexts['settings'] = _x( 'Settings', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'reports_settings' ) && ! Settings::isReports( $screen ) )
				$contexts['reports'] = _x( 'Reports', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'tools_settings' ) && ! Settings::isTools( $screen ) )
				$contexts['tools'] = _x( 'Tools', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'roles_settings' ) && ! Settings::isRoles( $screen ) )
				$contexts['roles'] = _x( 'Roles', 'Internal: ModuleLinks: Header Button', 'geditorial-admin' );

			if ( method_exists( $this, 'imports_settings' ) && ! Settings::isImports( $screen ) )
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
		$icon     = Helper::getIcon( 'download' );

		foreach ( $links as $name => $url )
			Services\HeaderButtons::register( $name, [
				'html'     => $url,
				'icon'     => $icon,
				'priority' => 800,
			] );

		return $posttype;
	}
}

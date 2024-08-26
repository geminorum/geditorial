<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait ModuleLinks
{

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
}

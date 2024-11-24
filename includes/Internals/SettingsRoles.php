<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait SettingsRoles
{
	/**
	 * Gets default roles for use in settings.
	 *
	 * @param  array $extra_excludes
	 * @param  bool  $filtered
	 * @return array $rols
	 */
	protected function get_settings_default_roles( $extra_excludes = [], $force_include = [], $filtered = TRUE )
	{
		$supported = WordPress\User::getAllRoleList( $filtered );
		$excluded  = Settings::rolesExcluded( $extra_excludes );

		return array_merge( array_diff_key( $supported, array_flip( $excluded ), (array) $force_include ) );
	}
}

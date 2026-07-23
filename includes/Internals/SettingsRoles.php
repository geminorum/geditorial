<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait SettingsRoles
{
	/**
	 * Gets default roles for use in settings.
	 *
	 * @param array|string $extra_excludes
	 * @param array|string $force_include
	 * @param bool $filtered
	 * @return array
	 */
	protected function get_settings_default_roles(
		array|string $extra_excludes = [],
		array|string $force_include = [],
		bool $filtered = TRUE,
	): array {

		$supported = WordPress\Role::get( 0, [], FALSE, $filtered );
		$excluded  = Core\Arraay::prepString(
			gEditorial\Settings::rolesExcluded(
				$extra_excludes,
				$this->keep_roles
			)
		);

		return array_merge( array_diff_key( $supported, array_flip( $excluded ), (array) $force_include ) );
	}
}

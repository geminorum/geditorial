<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait CoreRoles
{
	// NOTE: accepts array and performs `OR` check
	protected function role_can( $whats = 'supported', $user_id = NULL, $fallback = FALSE, $admins = TRUE, $prefix = NULL )
	{
		if ( is_null( $whats ) )
			return TRUE;

		if ( FALSE === $whats )
			return FALSE;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( ! $user_id )
			return $fallback;

		if ( $admins && WordPress\User::isSuperAdmin( $user_id ) )
			return TRUE;

		foreach ( (array) $whats as $what ) {

			$setting = $this->get_setting( sprintf( '%s%s', $what, $prefix ?? '_roles' ), [] );

			if ( TRUE === $setting )
				return $setting;

			if ( FALSE === $setting || ( empty( $setting ) && ! $admins ) )
				continue; // check others

			if ( $admins )
				$setting = array_merge( $setting, [ 'administrator' ] );

			if ( WordPress\User::hasRole( $setting, $user_id ) )
				return TRUE;
		}

		return $fallback;
	}

	/**
	 * Overrides the current-user-check for customized contexts.
	 *
	 * @param string $context
	 * @param string $fallback
	 * @param array $customized
	 * @return mixed
	 */
	protected function _override_module_cuc( $context = 'settings', $fallback = '', $customized = NULL )
	{
		return in_array( $context, $customized ?? [ 'reports' ], TRUE )
			? $this->role_can( $context, NULL, $fallback )
			: parent::cuc( $context, $fallback );
	}

	/**
	 * Overrides the current-user-check for customized contexts by taxonomy.
	 *
	 * @param string $constant
	 * @param string $context
	 * @param string $fallback
	 * @param array $customized
	 * @return mixed
	 */
	protected function _override_module_cuc_by_taxonomy( $constant, $context = 'settings', $fallback = '', $customized = NULL )
	{
		return in_array( $context, $customized ?? [ 'reports', 'tools' ], TRUE )
			? $this->corecaps_taxonomy_role_can( $constant, $context, NULL, $fallback )
			: parent::cuc( $context, $fallback );
	}
}

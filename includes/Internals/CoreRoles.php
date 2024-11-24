<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait CoreRoles
{
	// NOTE: accepts array and performs `OR` check
	protected function role_can( $whats = 'supported', $user_id = NULL, $fallback = FALSE, $admins = TRUE, $prefix = '_roles' )
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

			$setting = $this->get_setting( $what.$prefix, [] );

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
}

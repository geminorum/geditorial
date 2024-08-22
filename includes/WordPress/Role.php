<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Role extends Core\Base
{

	// $role->add_cap( $cap );
	// $role->remove_cap( $cap );
	// remove_role( $role );
	// https://learn.wordpress.org/tutorial/custom-post-types-and-capabilities/

	public static function object( $role )
	{
		if ( ! $role )
			return FALSE;

		if ( $role instanceof \WP_Role )
			return $role;

		return get_role( $role ) ?: FALSE;
	}

	public static function capabilities( $role, $fallback = [] )
	{
		if ( ! $object = self::object( $role ) )
			return $fallback;

		if ( \property_exists( $object, 'capabilities' ) )
			return $object->capabilities ?: $fallback;

		return $fallback;
	}

	public static function default( $fallback = 'subscriber' )
	{
		return self::object( get_option( 'default_role', $fallback ) );
	}

	public static function get( $mod = 0, $args = [], $user = FALSE, $filtered = TRUE )
	{
		$roles = $filtered ? get_editable_roles() : wp_roles()->roles;

		switch ( $mod ) {
			 case 0:

				$list = [];

				foreach ( $roles as $role_name => $role )
					$list[$role_name] = translate_user_role( $role['name'] );

				break;

			case 1:

				$list = new \stdClass;

				foreach ( $roles as $role_name => $role )
					$list->{$role_name} = translate_user_role( $role['name'] );

				break;

			default:
				$list = $roles;
		}

		return $list;
	}

	public static function sanitize( $input )
	{
		return preg_replace( '/[^a-zA-Z0-9_]/gu', '', Core\Number::translate( Core\Text::trim( $input ) ) );
	}
}

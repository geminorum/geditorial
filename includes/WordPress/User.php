<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;

class User extends Core\Base
{

	public static function get( $all_fields = FALSE, $network = FALSE, $extra = array(), $rekey = 'ID' )
	{
		$users = get_users( array_merge( array(
			'blog_id' => ( $network ? '' : $GLOBALS['blog_id'] ),
			'orderby' => 'display_name',
			'fields'  => ( $all_fields ? 'all_with_meta' : 'all' ),
		), $extra ) );

		return Arraay::reKey( $users, $rekey );
	}

	public static function user( $field, $key = FALSE )
	{
		if ( is_int( $field ) )
			$user = get_user_by( 'id', $field );

		else if ( is_string( $field ) )
			$user = get_user_by( 'login', $field );

		else
			return FALSE;

		if ( ! is_object( $user ) )
			return FALSE;

		if ( ! $key )
			return $user;

		if ( isset( $user->{$key} ) )
			return $user->{$key};

		return FALSE;
	}

	// current user can
	public static function cuc( $cap, $none = TRUE )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		if ( 'read' != $cap && ! is_user_logged_in() )
			return FALSE;

		return current_user_can( $cap );
	}

	// alt to `is_super_admin()`
	public static function isSuperAdmin( $user_id = FALSE )
	{
		$cap = is_multisite() ? 'manage_network' : 'manage_options';
		return $user_id ? user_can( $user_id, $cap ) : current_user_can( $cap );
	}

	public static function superAdminOnly()
	{
		if ( ! self::isSuperAdmin() )
			self::cheatin();
	}

	// @REF: `get_blogs_of_user()`
	public static function getUserBlogs( $user_id, $prefix )
	{
		$blogs = array();
		$keys  = get_user_meta( $user_id );

		if ( empty( $keys ) )
			return $blogs;

		if ( isset( $keys[$prefix.'capabilities'] ) && defined( 'MULTISITE' ) ) {
			$blogs[] = 1;
			unset( $keys[$prefix.'capabilities'] );
		}

		foreach ( array_keys( $keys ) as $key ) {

			if ( 'capabilities' !== substr( $key, -12 ) )
				continue;

			if ( $prefix && 0 !== strpos( $key, $prefix ) )
				continue;

			$blog = str_replace( array( $prefix, '_capabilities' ), '', $key );

			if ( is_numeric( $blog ) )
				$blogs[] = (int) $blog;
		}

		return $blogs;
	}

	// @REF: `get_role_list()`
	public static function getRoleList( $user_id = FALSE )
	{
		if ( ! $user_id )
			return self::getAllRoleList();

		$list = array();
		$user = is_object( $user_id ) ? $user_id : get_user_by( 'id', $user_id );

		if ( ! is_object( $user ) )
			return $list;

		$roles = wp_roles();

		foreach ( $user->roles as $role )
			if ( isset( $roles->role_names[$role] ) )
				$list[$role] = translate_user_role( $roles->role_names[$role] );

		return $list;
	}

	public static function getRoles( $user_id = FALSE )
	{
		$user = get_user_by( 'id', ( $user_id ? $user_id : get_current_user_id() ) );
		return empty( $user ) ? array() : (array) $user->roles;
	}

	public static function hasRole( $role, $user_id = FALSE )
	{
		$roles = self::getRoles( $user_id );

		foreach ( (array) $role as $name )
			if ( in_array( $name, $roles ) )
				return TRUE;

		return FALSE;
	}

	// current user role
	public static function cur( $role = FALSE )
	{
		$roles = self::getRoles();
		return $role ? in_array( $role, $roles ) : $roles;
	}

	public static function getAllRoleList( $object = FALSE )
	{
		$roles = $object ? new stdClass : array();

		foreach ( get_editable_roles() as $role_name => $role )

			if ( $object )
				$roles->{$role_name} = translate_user_role( $role['name'] );

			else
				$roles[$role_name] = translate_user_role( $role['name'] );

		return $roles;
	}
}

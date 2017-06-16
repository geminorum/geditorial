<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;

class User extends Core\Base
{

	public static function get( $all_fields = FALSE, $network = FALSE, $extra = array() )
	{
		$users = get_users( array_merge( array(
			'blog_id' => ( $network ? '' : $GLOBALS['blog_id'] ),
			'orderby' => 'display_name',
			'fields'  => ( $all_fields ? 'all_with_meta' : 'all' ),
		), $extra ) );

		return Arraay::reKey( $users, 'ID' );
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

		return current_user_can( $cap );
	}

	// alt to `is_super_admin()`
	public static function isSuperAdmin( $user_id = FALSE )
	{
		return $user_id
			? user_can( $user_id, 'manage_network' )
			: current_user_can( 'manage_network' );
	}

	public static function superAdminOnly()
	{
		if ( ! self::isSuperAdmin() )
			self::cheatin();
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

	public static function getRoleList( $object = FALSE )
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

class Walker_User_Checklist extends \Walker
{
	public $tree_type = 'user';
	public $db_fields = array ('parent' => 'parent', 'id' => 'ID');

	public function start_el( &$output, $user, $depth = 0, $args = array(), $id = 0 )
	{
		$output .= "\n".'<li class="-user"><label>'.
			HTML::tag( 'input', array(
				'type'     => 'checkbox',
				'name'     => $args['name'].'[]',
				'value'    => $user->user_login,
				'checked'  => in_array( $user->user_login, (array) $args['selected'] ),
				'disabled' => empty( $args['disabled'] ) ? FALSE : $args['disabled'],
			) ).
			' <code class="-login">'.$user->user_login.'</code> '.esc_html( $user->display_name ).
			'<br /><span class="-email code">'.$user->user_email.'</span></label>';
	}

	public function end_el( &$output, $category, $depth = 0, $args = array() )
	{
		$output .= '</li>';
	}
}

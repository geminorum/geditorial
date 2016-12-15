<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPUser extends gEditorialBaseCore
{

	// current user can
	public static function cuc( $cap, $none = TRUE )
	{
		if ( 'none' == $cap || '0' == $cap )
			return $none;

		return current_user_can( $cap );
	}

	public static function isSuperAdmin( $user_id = FALSE )
	{
		if ( $user_id )
			return user_can( $user_id, 'manage_network' );

		return current_user_can( 'manage_network' );
	}

	public static function superAdminOnly()
	{
		if ( ! self::isSuperAdmin() )
			self::cheatin();
	}
}

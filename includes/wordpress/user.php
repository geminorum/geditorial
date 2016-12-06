<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPUser extends gEditorialBaseCore
{

	public static function superAdminOnly()
	{
		if ( ! is_super_admin() )
			self::cheatin();
	}
}

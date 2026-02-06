<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Avatars extends gEditorial\Service
{
	const DEFAULT_SIZE  = 96;           // It is hardcoded on WordPress core. // NOTE: Less HTTP requests with fixed sizes!
	const DEFAULT_VALUE = 'mysteryman';

	public static function isDisabled()
	{
		return ! get_option( 'show_avatars' );
	}

	public static function getByUser( $user, $fallback = '', $extra = [] )
	{
		// WTF?!
		return self::getByEmail(
			is_object( $user ) ? $user->ID : $user,
			$fallback,
			$extra
		);
	}

	public static function getByEmail( $email, $fallback = '', $extra = [] )
	{
		$args = array_merge( [
			'class' => self::classs( 'avatar' ),

			'force_display' => TRUE,
		], $extra );

		return get_avatar(
			$email,
			static::DEFAULT_SIZE,
			static::DEFAULT_VALUE,
			'', // alt
			$args
		);
	}
}

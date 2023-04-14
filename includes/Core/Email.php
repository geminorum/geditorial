<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Email extends Base
{

	// wrapper for `is_email()`
	public static function is( $text )
	{
		return is_email( $text );
	}

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME!
	}

	public static function toUsername( $email, $strict = TRUE )
	{
		return preg_replace( '/\s+/', '', sanitize_user( preg_replace( '/([^@]*).*/', '$1', $email ), $strict ) );
	}
}

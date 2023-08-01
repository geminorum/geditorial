<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Email extends Base
{

	// wrapper for `is_email()`
	public static function is( $text )
	{
		return is_email( $text );
	}

	/**
	 * Prepares a value as email address for the given context.
	 *
	 * @param  string $value
	 * @param  array  $field
	 * @param  string $context
	 * @return string $prepped
	 */
	public static function prep( $value, $field = [], $context = 'display' )
	{
		if ( empty( $value ) )
			return '';

		$raw   = $value;
		$title = empty( $field['title'] ) ? NULL : $field['title'];

		switch ( $context ) {
			case 'edit' : return $raw;
			case 'print': return Core\HTML::wrapLTR( trim( $raw ) );
			     default: return HTML::mailto( $raw, NULL, self::is( $value ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
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

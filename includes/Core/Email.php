<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Email extends Base
{

	// TODO: must convert to `DataType`

	/**
	 * Verifies that an email is valid.
	 * NOTE: wrapper for WordPress core `is_email()`
	 *
	 * @param  string $input
	 * @return bool   $is
	 */
	public static function is( $input )
	{
		if ( self::empty( $input ) )
			return FALSE;

		return (bool) is_email( Text::trim( $input ) );
	}

	/**
	 * Strips out all characters that are not allowable in an email.
	 * NOTE: wrapper for WordPress core `sanitize_email()`
	 *
	 * @param  string $input
	 * @return string $sanitized
	 */
	public static function sanitize( $input )
	{
		if ( self::empty( $input ) )
			return '';

		return sanitize_email( Text::trim( $input ) );
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
		if ( self::empty( $value ) )
			return '';

		$raw   = $value;
		$title = empty( $field['title'] ) ? NULL : $field['title'];

		switch ( $context ) {
			case 'edit' : return $raw;
			case 'print': return Core\HTML::wrapLTR( trim( $raw ) );
			case 'icon' : return HTML::mailto( $raw, HTML::getDashicon( 'email-alt' ), self::is( $value ) ? '-is-valid' : '-is-not-valid' );
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

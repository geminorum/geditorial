<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Mobile extends Base
{

	/**
	 * Validates a phone number using a regular expression.
	 *
	 * @source `WC_Validation::is_phone()`
	 *
	 * @param  string $text Phone number to validate.
	 * @return bool
	 */
	public static function is( $text )
	{
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)\.]/', '', $text ) ) ) )
			return FALSE;

		// all zeros!
		if ( ! intval( $text ) )
			return FALSE;

		return TRUE;
	}

	// TODO: strip prefix: `tel:+98912000000`
	public static function sanitize( $input )
	{
		$sanitized = Number::intval( trim( $input ), FALSE );

		if ( ! self::is( $sanitized ) )
			return '';

		$sanitized = trim( str_ireplace( [
			' ',
			'.',
			'-',
			'#',
			'|',
			'(',
			')',
		], '', $sanitized ) );

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( in_array( $sanitized, [ '+989000000000', '989000000000', '09000000000' ], TRUE ) )
				$sanitized = '';

			else if ( preg_match( '/^9\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			else if ( preg_match( '/^09\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );
		}

		return $sanitized;
	}

	/**
	 * Prepares a value as mobile number for the given context.
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

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( Text::starts( $value, '+98' ) )
				$value = '0'.Text::stripPrefix( $value, '+98' );
		}

		switch ( $context ) {
			case 'edit'  : return $raw;
			case 'export': return $value;
			case 'print' : return Number::localize( $value );
			     default : return HTML::tel( $raw, $title ?: FALSE, Number::localize( $value ), self::is( $raw ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
	}

	public static function getHTMLPattern()
	{
		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹+]{11,}';
			// @REF: https://codepen.io/Hi-mohammad/pen/KKGYWQR
			// return '09(1[0-9]|3[1-9]|2[1-9])-?[0-9]{3}-?[0-9]{4}';

		return '[0-9+]{11,}';
	}
}

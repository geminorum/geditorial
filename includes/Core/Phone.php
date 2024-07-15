<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Phone extends Base
{

	// @SEE: https://github.com/brick/phonenumber

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
		$sanitized = Number::translate( Text::trim( $input ) );

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

			$province_prefix = self::const( 'GCORE_DEFAULT_PROVINCE_PHONE', '21' );
			$province_length = strlen( $province_prefix );

			// 10 digits and starts with province prefix
			if ( preg_match( "/^$province_prefix\d{".( 10 - $province_length )."}$/", $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			// 10 digits and starts with `9`
			else if ( preg_match( '/^9\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', $sanitized );

			// 11 digits and starts with `09`
			else if ( preg_match( '/^09\d{9}$/', $sanitized ) )
				$sanitized = sprintf( '+989%s', substr( $sanitized, -9 ) );

			// 11 digits and starts with `0`
			else if ( preg_match( '/^0\d{10}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );

			// 8 digits and starts with non `0`
			else if ( preg_match( '/^[1-9]{1}\d{7}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s%s', $province_prefix, $sanitized );

			// NOTE: invalidate likes of `+982530000000`, `+982100000000`
			if ( 13 === strlen( $sanitized ) && '0000000' === substr( $sanitized, -7 ) )
				$sanitized = '';
		}

		return $sanitized;
	}

	/**
	 * Prepares a value as phone number for the given context.
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

		// tries to sanitize with fallback
		if ( ! $value = self::sanitize( $value ) )
			$value = $raw;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( Text::starts( $value, '+98' ) )
				$value = '0'.Text::stripPrefix( $value, '+98' );

			$value = Number::localize( $value );
		}

		switch ( $context ) {
			case 'edit'  : return $raw;
			case 'export': return Number::translate( $value );
			case 'print' : return Number::localize( $value );
			     default : return HTML::tel( $raw, $title ?: FALSE, $value, self::is( $raw ) ? '-is-valid' : '-is-not-valid' );
		}

		return $value;
	}

	// @REF: https://www.abstractapi.com/guides/validate-phone-number-javascript
	// @SEE: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/tel
	public static function getHTMLPattern()
	{
		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			return '[0-9۰-۹]{3}-[0-9۰-۹]{3}-[0-9۰-۹]{4}';

		return '[0-9]{3}-[0-9]{3}-[0-9]{4}';
	}

	/**
	 * Convert plaintext phone number to clickable phone number.
	 *
	 * Remove formatting and allow "+".
	 * Example and specs: https://developer.mozilla.org/en/docs/Web/HTML/Element/a#Creating_a_phone_link
	 *
	 * @source `wc_make_phone_clickable()`
	 *
	 * @param string $text Content to convert phone number.
	 * @return string Content with converted phone number.
	 */
	public static function clickable( $text )
	{
		$number = Text::trim( preg_replace( '/[^\d|\+]/', '', $text ) );

		return $number ? '<a href="tel:'.esc_attr( $number ).'">'.esc_html( $text ).'</a>' : '';
	}

	public static function prepMobileForUsername( $text )
	{
		if ( ! ( $text = Text::trim( $text ) ) )
			return '';

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			$text = preg_replace( '/^\+98(\d{10})$/', '$1', $text );
			$text = preg_replace( '/^98(\d{10})$/', '$1', $text );
		}

		$text = preg_replace( '/^0(\d{10})$/', '$1', $text );

		if ( preg_replace( '/\d{10}/', '', $text ) )
			return '';

		return trim( $text );
	}
}

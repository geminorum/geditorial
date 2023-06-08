<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Phone extends Base
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

	// @SEE: `HTML::sanitizePhoneNumber()`
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
		], '', $sanitized ) );

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			if ( preg_match( '/^0\d{10}$/', $sanitized ) )
				$sanitized = sprintf( '+98%s', ltrim( $sanitized, '0' ) );

			else if ( preg_match( '/^[1-9]{1}\d{7}$/', $sanitized ) )
				$sanitized = sprintf( '+9821%s', $sanitized ); // WTF: Tehran prefix!

			// NOTE: invalidate likes of `+982530000000`, `+982100000000`
			if ( 13 === strlen( $sanitized ) && '0000000' === substr( $sanitized, -7 ) )
				$sanitized = '';
		}

		return $sanitized;
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
		$number = trim( preg_replace( '/[^\d|\+]/', '', $text ) );

		return $number ? '<a href="tel:'.esc_attr( $number ).'">'.esc_html( $text ).'</a>' : '';
	}
}
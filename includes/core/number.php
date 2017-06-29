<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Number extends Base
{

	// FIXME: use our own
	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', $number );
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function intval( $text, $intval = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $intval ? intval( $number ) : $number;
	}

	// never let a numeric value be less than zero.
	// @SOURCE: `bbp_number_not_negative()`
	public static function notNegative( $number )
	{
		// protect against formatted strings
		if ( is_string( $number ) ) {
			$number = strip_tags( $number );                    // no HTML
			$number = preg_replace( '/[^0-9-]/', '', $number ); // no number-format

		// protect against objects, arrays, scalars, etc...
		} else if ( ! is_numeric( $number ) ) {
			$number = 0;
		}

		// make the number an integer
		$int = intval( $number );

		// pick the maximum value, never less than zero
		$not_less_than_zero = max( 0, $int );

		return $not_less_than_zero;
	}

	// @SOURCE: WP's `zeroise()`
	public static function zeroise( $number, $threshold, $locale = NULL )
	{
		return sprintf( '%0'.$threshold.'s', $number );
	}
}

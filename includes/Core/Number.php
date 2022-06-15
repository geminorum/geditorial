<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Number extends Base
{

	public static function toOrdinal( $number, $locale = NULL )
	{
		if ( ! $sanitized = self::intval( $number ) )
			return $number;

		if ( is_null( $locale ) )
			$locale = L10n::locale();

		if ( class_exists( 'NumberFormatter' ) ) {

			// $formatter = new \NumberFormatter( $locale, \NumberFormatter::ORDINAL );

			// @REF: https://stackoverflow.com/a/19411974
			$formatter = new \NumberFormatter( $locale, \NumberFormatter::SPELLOUT );
			$formatter->setTextAttribute( \NumberFormatter::DEFAULT_RULESET, "%spellout-ordinal" );

			$formatted = $formatter->format( $sanitized );

		} else if ( 'en_US' == $locale ) {

			$formatted = self::englishOrdinal( $sanitized );

		} else {

			$formatted = $sanitized;
		}

		return apply_filters( 'number_format_ordinal', $formatted, $sanitized, $locale );
	}

	// @REF: https://en.wikipedia.org/wiki/English_numerals#Ordinal_numbers
	// @REF: https://stackoverflow.com/a/3110033
	public static function englishOrdinal( $number )
	{
		$ends = [ 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' ];

		if ( ( ( $number % 100 ) >= 11 ) && ( ( $number % 100 ) <= 13 ) )
			return $number.'th';

		return $number.$ends[$number % 10];
	}

	public static function toWords( $number, $locale = NULL )
	{
		if ( ! $sanitized = self::intval( $number ) )
			return $number;

		if ( is_null( $locale ) )
			$locale = L10n::locale();

		if ( class_exists( 'NumberFormatter' ) ) {

			$formatter = new \NumberFormatter( $locale, \NumberFormatter::SPELLOUT );
			$formatter->setTextAttribute( \NumberFormatter::DEFAULT_RULESET, "%spellout-numbering-verbose" );

			$formatted = $formatter->format( $sanitized );

		} else if ( 'en_US' == $locale ) {

			$formatted = self::englishWords( $sanitized );

		} else {

			$formatted = $sanitized;
		}

		return apply_filters( 'number_format_words', $formatted, $sanitized, $locale );
	}

	// @REF: https://stackoverflow.com/a/30299572
	public static function englishWords( $number )
	{
		$number = (int) $number;
		$words = [];
		$list1 = [ '', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen' ];
		$list2 = [ '', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred' ];
		$list3 = [ '', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion', 'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion', 'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion' ];

		$num_length = strlen( $number );
		$levels     = (int) ( ( $num_length + 2 ) / 3 );
		$max_length = $levels * 3;
		$number     = substr( '00'.$number, -$max_length );
		$num_levels = str_split( $number, 3 );

		for ( $i = 0; $i < count( $num_levels ); $i++ ) {
			$levels--;

			$hundreds = (int) ( $num_levels[$i] / 100 );
			$hundreds = ( $hundreds ? ' '.$list1[$hundreds].' hundred'.' ' : '' );
			$tens     = (int) ( $num_levels[$i] % 100 );
			$singles  = '';

			if ( $tens < 20 ) {

				$tens = ( $tens ? ' '.$list1[$tens].' ' : '' );

			} else {

				$tens    = (int) ( $tens / 10 );
				$tens    = ' '.$list2[$tens].' ';
				$singles = (int) ( $num_levels[$i] % 10 );
				$singles = ' '.$list1[$singles].' ';
			}

			$words[] = $hundreds.$tens.$singles.( ( $levels && (int) ( $num_levels[$i] ) ) ? ' '.$list3[$levels].' ' : '' );
		}

		$commas = count( $words );

		if ($commas > 1)
			$commas = $commas - 1;

		return implode( ' ', $words );
	}

	public static function localize( $number )
	{
		return apply_filters( 'number_format_i18n', $number );
	}

	public static function format( $number, $decimals = 0, $locale = NULL )
	{
		return apply_filters( 'number_format_i18n', number_format( $number, absint( $decimals ) ), $number, $decimals );
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function intval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', trim( $text ) );

		return $force ? (int) $number : $number;
	}

	// FIXME: use our own
	// converts back number chars into english
	public static function floatval( $text, $force = TRUE )
	{
		$number = apply_filters( 'string_format_i18n_back', $text );

		return $force ? (float) $number : $number;
	}

	// never let a numeric value be less than zero
	// @SOURCE: `bbp_number_not_negative()`
	public static function notNegative( $number )
	{
		if ( is_string( $number ) ) {

			// protect against formatted strings
			$number = strip_tags( $number ); // no HTML
			$number = apply_filters( 'string_format_i18n_back', $number );
			$number = preg_replace( '/[^0-9-]/', '', $number ); // no number-format

		} else if ( ! is_numeric( $number ) ) {

			// protect against objects, arrays, scalars, etc...
			$number = 0;
		}

		// make the number an integer
		// pick the maximum value, never less than zero
		return max( 0, (int) $number );
	}

	// @SOURCE: WP's `zeroise()`
	public static function zeroise( $number, $threshold, $locale = NULL )
	{
		return sprintf( '%0'.$threshold.'s', $number );
	}

	/**
	 * Round a number using the built-in `round` function, but unless the value to round is numeric
	 * (a number or a string that can be parsed as a number), apply 'floatval' first to it
	 * (so it will convert it to 0 in most cases).
	 *
	 * This is needed because in PHP 7 applying `round` to a non-numeric value returns 0,
	 * but in PHP 8 it throws an error. Specifically, in WooCommerce we have a few places where
	 * round('') is often executed.
	 *
	 * @source `Automattic\WooCommerce\Utilities\NumberUtil::round()`
	 *
	 * @param mixed $val The value to round.
	 * @param int   $precision The optional number of decimal digits to round to.
	 * @param int   $mode A constant to specify the mode in which rounding occurs.
	 *
	 * @return float The value rounded to the given precision as a float, or the supplied default value.
	 */
	public static function round( $val, int $precision = 0, int $mode = PHP_ROUND_HALF_UP )
	{
		if ( ! is_numeric( $val ) )
			$val = floatval( $val );

		return round( $val, $precision, $mode );
	}
}

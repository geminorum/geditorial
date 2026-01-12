<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class ISBN extends Base
{

	// TODO: must convert to `DataType`

	// Matches an un-formatted `ISBN-10`.
	public const ISBN10 = '/^[0-9]{9}[0-9Xx]$/';

	// Matches an un-formatted `ISBN-13`.
	public const ISBN13 = '/^97[89][0-9]{10}$/';

	public static function getHTMLPattern()
	{
		// @source https://input-pattern.com/en/training.php#isbn
		return '(97[89])?\d{10}|(97[89]-)?(?=.{13}$)(\d+-){3}\d';
	}

	public static function prep( $input, $wrap = FALSE )
	{
		// NOTE: returns the original if not valid
		if ( ! self::validate( $input ) )
			return $wrap
				? HTML::tag( 'span', [ 'class' => [ 'isbn', '-is-not-valid' ] ], HTML::wrapLTR( $input ) )
				: $input;

		if ( class_exists( 'Nicebooks\\Isbn\\IsbnTools' ) ) {

			/**
			 * @package `nicebooks/isbn`
			 * @source https://github.com/nicebooks-com/isbn/tree/0.3.48
			 */
			$tools  = new \Nicebooks\Isbn\IsbnTools();
			$string = $tools->format( self::sanitize( $input ) );

		} else {

			$string = Text::dashify( self::sanitize( $input ), 3, '&ndash;' );
		}

		return $wrap
			? HTML::tag( 'span', [ 'class' => [ 'isbn', '-is-valid'	] ], HTML::wrapLTR( $string ) )
			: $string;
	}

	/**
	 * Tries to discover if given criteria is supported.
	 * NOTE: converts ISBN-10 to ISBN-13.
	 * NOTE: avoids validation to support fake ISBN numbers.
	 *
	 * @param string $criteria
	 * @return string|false
	 */
	public static function discovery( $criteria )
	{
		if ( ! $sanitized = self::sanitize( $criteria ) )
			return FALSE;

		// only numbers
		if ( ! Number::is( $sanitized ) )
			return FALSE;

		// only between 10-13 digits/`x`
		if ( ! preg_match( '/^[\dx]{10,13}$/i', $sanitized ) )
			return FALSE;

		return self::convertToISBN13( $sanitized );
	}

	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		$sanitized = Text::trim( $input );
		$sanitized = Text::stripAllSpaces( $sanitized );
		$sanitized = Number::translate( $sanitized );
		$sanitized = str_ireplace( [ 'isbn', '-', ':' ], '', $sanitized );
		$sanitized = Text::trim( $sanitized );

		return $sanitized;
	}

	/**
	 * Validates given ISBN.
	 * @source http://stackoverflow.com/a/14096142
	 *
	 * `ISBN:0-306-40615-2`     // return `1`
	 * `0-306-40615-2`          // return `1`
	 * `ISBN:0306406152`        // return `1`
	 * `0306406152`             // return `1`
	 * `ISBN:979-1-090-63607-1` // return `2`
	 * `979-1-090-63607-1`      // return `2`
	 * `ISBN:9791090636071`     // return `2`
	 * `9791090636071`          // return `2`
	 * `ISBN:97811`             // return `FALSE`
	 *
	 * @param string $input
	 * @return bool|int
	 */
	public static function validate( $input )
	{
		$data    = Text::trim( Number::translate( $input ) );
		$pattern = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';

		if ( preg_match( $pattern, str_replace( '-', '', $data ), $matches ) )
			return ( 10 === strlen( $matches[1] ) )
				? self::isValidISBN10( $matches[1] )  // ISBN-10
				: self::isValidISBN13( $matches[1] ); // ISBN-13

		return FALSE;
	}

	/**
	 * Validates given ISBN-10.
	 * @source http://stackoverflow.com/a/14096142
	 *
	 * @param string $input
	 * @return bool
	 */
	public static function isValidISBN10( $input )
	{
		if ( ! preg_match( static::ISBN10, $input, $matches ) )
			return FALSE;

		$input = (string) $input;
		$check = 0;

		for ( $i = 0; $i < 10; $i++ ) {

			if ( 'x' === strtolower( $input[$i] ) )
				$check += 10 * ( 10 - $i );

			else if ( is_numeric( $input[$i] ) )
				$check += (int) $input[$i] * ( 10 - $i );

			else
				return FALSE;
		}

		return ( 0 === ( $check % 11 ) ) ? 1 : FALSE;
	}

	/**
	 * Validates given ISBN-13.
	 * @source http://stackoverflow.com/a/14096142
	 *
	 * @param string $input
	 * @return bool
	 */
	public static function isValidISBN13( $input )
	{
		if ( ! preg_match( static::ISBN13, $input, $matches ) )
			return FALSE;

		$input = (string) $input;
		$check = 0;

		for ( $i = 0; $i < 13; $i += 2 )
			$check += (int) $input[$i];

		for ( $i = 1; $i < 12; $i += 2 )
			$check += 3 * (int) $input[$i];

		return ( 0 === ( $check % 10 ) ) ? 2 : FALSE;
	}

	/**
	 * Converts given ISBN-13 to ISBN-10.
	 * @source https://github.com/nicebooks-com/isbn
	 *
	 * @param string $input
	 * @return string
	 */
	public static function convertToISBN10( $input )
	{
		if ( ! preg_match( static::ISBN13, $input, $matches ) )
			return $input;

		if ( '978' !== substr( $input, 0, 3 ) )
			return $input;

		$code = substr( $input, 3, 9 );

		return sprintf( '%s%d', $code, self::checksumForISBN10( $code ) );
	}

	/**
	 * Calculates ISBN-10 checksum for given string.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function checksumForISBN10( $input )
	{
		for ( $sum = 0, $i = 0; $i < 9; $i++ ) {
			$digit = (int) $input[$i];
			$sum += $digit * (1 + $i);
		}

		$sum %= 11;

		return $sum === 10 ? 'X' : (string) $sum;
	}

	/**
	 * Converts given ISBN-10 to ISBN-13.
	 * @source https://github.com/nicebooks-com/isbn
	 *
	 * @param string $input
	 * @return string
	 */
	public static function convertToISBN13( $input )
	{
		if ( ! preg_match( static::ISBN10, $input, $matches ) )
			return $input;

		$code = sprintf( '978%s', substr( $input, 0, 9 ) );

		return sprintf( '%s%d', $code, self::checksumForISBN13( $code ) );
	}

	/**
	 * Calculates ISBN-13 checksum for given string.
	 *
	 * @param string $input
	 * @return int
	 */
	public static function checksumForISBN13( $input )
	{
		for ( $sum = 0, $i = 0; $i < 12; $i++ ) {
			$digit = (int) $input[$i];
			$sum += $digit * ( 1 + 2 * ( $i % 2 ) );
		}

		return ( ( 10 - ( $sum % 10 ) ) % 10 );
	}
}

<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class ISBN extends Base
{

	// TODO: must convert to `DataType`

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME!
	}

	public static function prep( $input, $wrap = FALSE )
	{
		// NOTE: returns the original if not valid
		if ( ! self::validate( $input ) )
			return $wrap
				? HTML::tag( 'span', [ 'class' => [ 'isbn', '-is-not-valid' ] ], HTML::wrapLTR( $input ) )
				: $input;

		return $wrap
			? HTML::tag( 'span', [ 'class' => [ 'isbn', '-is-valid' ] ], HTML::wrapLTR( self::sanitize( $input ) ) )
			: self::sanitize( $input );
	}

	public static function sanitize( $input )
	{
		$sanitized = Number::translate( Text::trim( $input ) );

		return Text::trim( str_ireplace( [ 'isbn', '-', ':', ' ' ], '', $sanitized ) );
	}

	// Finding ISBNs
	// @REF: http://stackoverflow.com/a/14096142
	/*
		ISBN::validate( 'ISBN:0-306-40615-2' ) );     // return 1
		ISBN::validate( '0-306-40615-2' ) );          // return 1
		ISBN::validate( 'ISBN:0306406152' ) );        // return 1
		ISBN::validate( '0306406152' ) );             // return 1
		ISBN::validate( 'ISBN:979-1-090-63607-1' ) ); // return 2
		ISBN::validate( '979-1-090-63607-1' ) );      // return 2
		ISBN::validate( 'ISBN:9791090636071' ) );     // return 2
		ISBN::validate( '9791090636071' ) );          // return 2
		ISBN::validate( 'ISBN:97811' ) );             // return FALSE
	*/
	public static function validate( $input )
	{
		$data    = Number::translate( $input );
		$pattern = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';

		if ( preg_match( $pattern, str_replace( '-', '', $data ), $matches ) )
			return ( 10 === strlen( $matches[1] ) )
				? self::isValidISBN10( $matches[1] )  // ISBN-10
				: self::isValidISBN13( $matches[1] ); // ISBN-13

		return FALSE;
	}

	// Validate ISBN-10
	// @REF: http://stackoverflow.com/a/14096142
	public static function isValidISBN10( $string )
	{
		$check = 0;

		for ( $i = 0; $i < 10; $i++ ) {
			if ( 'x' === strtolower( $string[$i] ) )
				$check += 10 * ( 10 - $i );
			else if ( is_numeric( $string[$i] ) )
				$check += (int) $string[$i] * ( 10 - $i );
			else
				return FALSE;
		}

		return ( 0 === ( $check % 11 ) ) ? 1 : FALSE;
	}

	// Validate ISBN-13
	// @REF: http://stackoverflow.com/a/14096142
	public static function isValidISBN13( $string )
	{
		$check = 0;

		for ( $i = 0; $i < 13; $i += 2 )
			$check += (int) $string[$i];

		for ( $i = 1; $i < 12; $i += 2 )
			$check += 3 * $string[$i];

		return ( 0 === ( $check % 10 ) ) ? 2 : FALSE;
	}
}

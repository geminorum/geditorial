<?php namespace geminorum\gEditorial\Helpers;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;

class Book extends gEditorial\Helper
{

	public static function ISBN( $string )
	{
		return HTML::link( self::getISBN( $string, TRUE ), self::lookupISBN( $string ), TRUE );
	}

	public static function lookupISBN( $string )
	{
		return sprintf( 'https://www.google.com/search?q=ISBN:%s', urlencode( self::sanitizeISBN( $string ) ) );
	}

	// TODO: make this more reliable!
	public static function barcodeISBN( $string )
	{
		return add_query_arg( [
			'bcid'        => 'ean13',
			// 'scaleX'      => '2',
			// 'scale'       => '2',
			'text'        => $string,
			'includetext' => '', // to display the code
		], 'http://bwipjs-api.metafloor.com' );
	}

	public static function sanitizeISBN( $string, $intval = FALSE )
	{
		if ( $intval )
			$string = Number::intval( $string, FALSE );

		return trim( str_ireplace( [ 'isbn', '-', ':', ' ' ], '', $string ) );
	}

	public static function getISBN( $input, $wrap = FALSE, $link = FALSE )
	{
		$string = Number::intval( $input, FALSE );

		if ( self::validateISBN( $string ) ) {
			$string = self::sanitizeISBN( $string );
			return $wrap ? '<span class="isbn -valid">&#8206;'.$string.'&#8207;<span>' : $string;
		}

		// CAUTION: returns the original
		return $wrap ? '<span class="isbn -not-valid">&#8206;'.$input.'&#8207;<span>' : $input;
	}

	// Finding ISBNs
	// @link: http://stackoverflow.com/a/14096142
	/*
		self::validateISBN( 'ISBN:0-306-40615-2' ) );     // return 1
		self::validateISBN( '0-306-40615-2' ) );          // return 1
		self::validateISBN( 'ISBN:0306406152' ) );        // return 1
		self::validateISBN( '0306406152' ) );             // return 1
		self::validateISBN( 'ISBN:979-1-090-63607-1' ) ); // return 2
		self::validateISBN( '979-1-090-63607-1' ) );      // return 2
		self::validateISBN( 'ISBN:9791090636071' ) );     // return 2
		self::validateISBN( '9791090636071' ) );          // return 2
		self::validateISBN( 'ISBN:97811' ) );             // return FALSE
	*/
	public static function validateISBN( $string )
	{
		$pattern = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';

		if ( preg_match( $pattern, str_replace( '-', '', $string ), $matches ) )
			return ( 10 === strlen( $matches[1] ) )
				? self::isValidISBN10( $matches[1] )  // ISBN-10
				: self::isValidISBN13( $matches[1] ); // ISBN-13

		return FALSE;
	}

	// Validate ISBN-10
	// @link: http://stackoverflow.com/a/14096142
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
	// @link: http://stackoverflow.com/a/14096142
	public static function isValidISBN13( $string )
	{
		$check = 0;

		for ( $i = 0; $i < 13; $i += 2 )
			$check += (int) $string[$i];

		for ( $i = 1; $i < 12; $i += 2 )
			$check += 3 * $string[$i];

		return ( 0 === ( $check % 10 ) ) ? 2 : FALSE;
	}

	// usort( $posts, 'bcpt_sort_books' );
	function bcpt_sort_books( $a, $b )
	{
		$title_a = mb_strtolower( preg_replace( '~\P{Xan}++~u', '', $a->post_title ) );
		$title_b = mb_strtolower( preg_replace( '~\P{Xan}++~u', '', $b->post_title ) );

		if ( $title_a == $title_b )
			return 0 ;

		return ( $title_a < $title_b ) ? -1 : 1;
	}

	// usort( $terms, 'bcpt_sort_artists' );
	function bcpt_sort_artists( $a, $b )
	{
		$aLast = end( explode( ' ', $a->name ) );
		$bLast = end( explode( ' ', $b->name ) );

		return strcasecmp( $aLast, $bLast );
	}
}

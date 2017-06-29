<?php namespace geminorum\gEditorial\Helpers;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;

class Book extends gEditorial\Helper
{

	public static function ISBN( $string )
	{
		return HTML::link( self::getISBN( $string, TRUE ),
			sprintf( 'https://www.google.com/search?q=ISBN:%s',
				urlencode( trim( str_ireplace( [ 'isbn', '-', ':', ' ' ], '', $string ) ) ) ), TRUE );
	}

	public static function getISBN( $input, $wrap = FALSE, $link = FALSE )
	{
		$string = Number::intval( $input, FALSE );

		if ( self::findISBN( $string ) ) {
			$string = trim( str_ireplace( [ 'isbn', '-', ':', ' ' ], '', $string ) );
			return $wrap ? '<span class="isbn -valid">'.$string.'<span>' : $string;
		}

		// CAUTION: returns the original
		return $wrap ? '<span class="isbn -not-valid">'.$input.'<span>' : $input;
	}

	// Finding ISBNs
	// @link: http://stackoverflow.com/a/14096142
	/*
		self::findISBN( 'ISBN:0-306-40615-2' ) );     // return 1
		self::findISBN( '0-306-40615-2' ) );          // return 1
		self::findISBN( 'ISBN:0306406152' ) );        // return 1
		self::findISBN( '0306406152' ) );             // return 1
		self::findISBN( 'ISBN:979-1-090-63607-1' ) ); // return 2
		self::findISBN( '979-1-090-63607-1' ) );      // return 2
		self::findISBN( 'ISBN:9791090636071' ) );     // return 2
		self::findISBN( '9791090636071' ) );          // return 2
		self::findISBN( 'ISBN:97811' ) );             // return FALSE
	*/
	public static function findISBN( $str )
	{
		$pattern = '/\b(?:ISBN(?:: ?| ))?((?:97[89])?\d{9}[\dx])\b/i';

		if ( preg_match( $pattern, str_replace( '-', '', $str ), $matches ) )
			return ( 10 === strlen( $matches[1] ) )
				? self::isValidISBN10( $matches[1] )  // ISBN-10
				: self::isValidISBN13( $matches[1] ); // ISBN-13

		return FALSE;
	}

	// Validate ISBN-10
	// @link: http://stackoverflow.com/a/14096142
	public static function isValidISBN10($isbn)
	{
		$check = 0;

		for ( $i = 0; $i < 10; $i++ ) {
			if ( 'x' === strtolower( $isbn[$i] ) )
				$check += 10 * ( 10 - $i );
			else if ( is_numeric( $isbn[$i] ) )
				$check += (int) $isbn[$i] * ( 10 - $i );
			else
				return FALSE;
		}

		return ( 0 === ( $check % 11 ) ) ? 1 : FALSE;
	}

	// Validate ISBN-13
	// @link: http://stackoverflow.com/a/14096142
	public static function isValidISBN13($isbn)
	{
		$check = 0;

		for ( $i = 0; $i < 13; $i += 2 )
			$check += (int) $isbn[$i];

		for ( $i = 1; $i < 12; $i += 2 )
			$check += 3 * $isbn[$i];

		return ( 0 === ( $check % 10 ) ) ? 2 : FALSE;
	}

	// usort( $posts, 'bcpt_sort_books' );
	function bcpt_sort_books( $a, $b )
	{
		$title_a = mb_strtolower( preg_replace( '~\P{Xan}++~u', '', $a->post_title ) );
		$title_b = mb_strtolower( preg_replace( '~\P{Xan}++~u', '', $b->post_title ) );

		if( $title_a == $title_b ) {
			return 0 ;
		}
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

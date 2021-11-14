<?php namespace geminorum\gEditorial\Modules\Book;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\ISBN;
use geminorum\gEditorial\Core\Number;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'book';

	public static function ISBN( $string )
	{
		return HTML::link( ISBN::prep( $string, TRUE ), self::lookupISBN( $string ), TRUE );
	}

	// http://books.google.com/books?vid=isbn9789646799950
	public static function lookupISBN( $isbn )
	{
		// $url = add_query_arg( [
		// 	// 'q' => 'ISBN:'.urlencode( ISBN::sanitize( $isbn ) ),
		// 	'q' => urlencode( ISBN::sanitize( $isbn ) ),
		// ], 'https://www.google.com/search' );

		$url = add_query_arg( [
			'vid' => urlencode( 'isbn'.ISBN::sanitize( $isbn ) ),
		], 'https://books.google.com/books' );

		apply_filters( static::BASE.'_'.static::MODULE.'_lookup_isbn', $url, $isbn );
	}

	public static function barcodeISBN( $isbn )
	{
		return Barcode::getBWIPPjs( 'ean13', $isbn );
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

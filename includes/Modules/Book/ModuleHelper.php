<?php namespace geminorum\gEditorial\Modules\Book;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Barcode;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'book';

	public static function ISBN( $string )
	{
		return Core\HTML::link( Core\ISBN::prep( $string, TRUE ), Info::lookupISBN( $string ), TRUE );
	}

	// FIXME: DEPRECATED: use `Info::lookupISBN()`
	public static function lookupISBN( $isbn )
	{
		self::_dep( 'Info::lookupISBN()' );

		return Info::lookupISBN( $isbn );
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

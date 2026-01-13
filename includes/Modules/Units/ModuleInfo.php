<?php namespace geminorum\gEditorial\Modules\Units;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class ModuleInfo extends gEditorial\Info
{
	const MODULE = 'units';

	public static function getHelpTabs( $context = NULL )
	{
		return [
			[
				'title'   => _x( 'Shortcodes', 'Help Tab Title', 'geditorial-units' ),
				'id'      => static::classs( 'shortcodes' ),
				'content' => self::buffer( [ __CLASS__, 'renderHelpTabList' ], [
					[
						Core\HTML::code( '[unit /]' ),
					]
				] ),
			],
		];
	}

	// @REF: https://papersizes.io/books/
	// @SEE: https://en.wikipedia.org/wiki/Book_size
	// @SEE: https://www.blurb.com/book-dimensions
	public static function getBookCovers( $context = NULL )
	{
		return [
			'octavo'      => _x( 'Octavo', 'Book Cover', 'geditorial-units' ),          // vaziri
			'folio'       => _x( 'Folio', 'Book Cover', 'geditorial-units' ),           // soltani
			'medium'      => _x( 'Medium Octavo', 'Book Cover', 'geditorial-units' ),   // roghee
			'quatro'      => _x( 'Quatro', 'Book Cover', 'geditorial-units' ),          // rahli
			'duodecimo'   => _x( 'Duodecimo', 'Book Cover', 'geditorial-units' ),       // paltoyee
			'sextodecimo' => _x( 'Sextodecimo', 'Book Cover', 'geditorial-units' ),     // jibi
		];
	}

	// @REF: https://papersizes.io/
	// @SEE: https://www.neenahpaper.com/resources/paper-101/international-sizes
	public static function getPaperSizes( $context = NULL )
	{
		return [
			'a1' => _x( 'A1', 'Paper Size', 'geditorial-units' ),
			'a2' => _x( 'A2', 'Paper Size', 'geditorial-units' ),
			'a3' => _x( 'A3', 'Paper Size', 'geditorial-units' ),
			'a4' => _x( 'A4', 'Paper Size', 'geditorial-units' ),
			'a5' => _x( 'A5', 'Paper Size', 'geditorial-units' ),
			'a6' => _x( 'A6', 'Paper Size', 'geditorial-units' ),
		];
	}

	public static function getEuropeanShoeSizes( $context = NULL )
	{
		return [
			'35'  => _x( '35', 'Shoe Size', 'geditorial-units' ),
			'36'  => _x( '36', 'Shoe Size', 'geditorial-units' ),
			'37'  => _x( '37', 'Shoe Size', 'geditorial-units' ),
			'38'  => _x( '38', 'Shoe Size', 'geditorial-units' ),
			'39'  => _x( '39', 'Shoe Size', 'geditorial-units' ),
			'40'  => _x( '40', 'Shoe Size', 'geditorial-units' ),
			'41'  => _x( '41', 'Shoe Size', 'geditorial-units' ),
			'42'  => _x( '42', 'Shoe Size', 'geditorial-units' ),
			'43'  => _x( '43', 'Shoe Size', 'geditorial-units' ),
			'44'  => _x( '44', 'Shoe Size', 'geditorial-units' ),
			'45'  => _x( '45', 'Shoe Size', 'geditorial-units' ),
			'46'  => _x( '46', 'Shoe Size', 'geditorial-units' ),
			'47'  => _x( '47', 'Shoe Size', 'geditorial-units' ),
			'48'  => _x( '48', 'Shoe Size', 'geditorial-units' ),
			'50'  => _x( '50', 'Shoe Size', 'geditorial-units' ),
		];
	}

	// @REF: https://cutterbuck.com/fit-size-chart/
	public static function getInternationalShirtSizes( $context = NULL )
	{
		return [
			'small'  => _x( 'Small', 'Shirt Size', 'geditorial-units' ),
			'medium' => _x( 'Medium', 'Shirt Size', 'geditorial-units' ),
			'large'  => _x( 'Large', 'Shirt Size', 'geditorial-units' ),
			'xl'     => _x( 'X-Large', 'Shirt Size', 'geditorial-units' ),
			'xxl'    => _x( '2X-Large', 'Shirt Size', 'geditorial-units' ),
			'xxxl'   => _x( '3X-Large', 'Shirt Size', 'geditorial-units' ),
		];
	}

	// @REF: https://cutterbuck.com/fit-size-chart/
	// TODO: considering genders!
	public static function getInternationalPantsSizes( $context = NULL )
	{
		return [
			'30'  => _x( '30', 'Pant Size', 'geditorial-units' ),
			'32'  => _x( '32', 'Pant Size', 'geditorial-units' ),
			'33'  => _x( '33', 'Pant Size', 'geditorial-units' ),
			'34'  => _x( '34', 'Pant Size', 'geditorial-units' ),
			'35'  => _x( '35', 'Pant Size', 'geditorial-units' ),
			'36'  => _x( '36', 'Pant Size', 'geditorial-units' ),
			'38'  => _x( '38', 'Pant Size', 'geditorial-units' ),
			'40'  => _x( '40', 'Pant Size', 'geditorial-units' ),
			'42'  => _x( '42', 'Pant Size', 'geditorial-units' ),
			'44'  => _x( '44', 'Pant Size', 'geditorial-units' ),
			'46'  => _x( '46', 'Pant Size', 'geditorial-units' ),
			'48'  => _x( '48', 'Pant Size', 'geditorial-units' ),
			'50'  => _x( '50', 'Pant Size', 'geditorial-units' ),
			'52'  => _x( '52', 'Pant Size', 'geditorial-units' ),
			'54'  => _x( '54', 'Pant Size', 'geditorial-units' ),
			'56'  => _x( '56', 'Pant Size', 'geditorial-units' ),
		];
	}
}

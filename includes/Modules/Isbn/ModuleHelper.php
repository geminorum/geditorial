<?php namespace geminorum\gEditorial\Modules\Isbn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE  = 'isbn';
	const BARCODE = 'ean13';

	public static function barcode( $data )
	{
		return Services\Barcodes::getByBWIPP( static::BARCODE, $data );
	}

	// @SEE: `getTypeOptions()` on `Bookmarked` Module
	// @SEE: `_generate_link()` on `Bookmarked` Module
	public static function getLookups( $context = NULL )
	{
		static $data = [];

		$context = $context ?? 'default';

		if ( ! empty( $data[$context] ) )
			return $data[$context];

		$list = [
			[
				'name'     => 'wikipedia',
				'title'    => _x( 'Wikipedia', 'Lookup: Name', 'geditorial-isbn' ),
				'template' => 'https://{{_iso639}}.wikipedia.org/wiki/Special:BookSources?isbn={{isbn}}',
				'cssclass' => '-wikipedia',
				'icon'     => [ 'misc-16', 'wikipedia' ],
				// 'logo'     => '',
			],
			[
				'name'     => 'openlibrary',
				'title'    => _x( 'OpenLibrary', 'Lookup: Name', 'geditorial-isbn' ),
				'template' => 'https://openlibrary.org/search?isbn={{isbn}}',
				'cssclass' => '-openlibrary',
				// 'icon'     => [ 'misc-16', '' ], // FIXME
				// 'logo'     => '',
			],
			[
				'name'     => 'google-books',
				'title'    => _x( 'Google Books', 'Lookup: Name', 'geditorial-isbn' ),
				'template' => 'https://books.google.com/books?vid=isbn{{isbn}}',
				'cssclass' => '-google-books',
				// 'icon'     => [ 'misc-24', '' ], // FIXME
				// 'logo'     => '',
			],
			[
				'name'     => 'google-search',
				'title'    => _x( 'Google Search', 'Lookup: Name', 'geditorial-isbn' ),
				// 'template' => 'https://www.google.com/search?tbm=bks&q={{isbn}}',
				'template' => 'https://www.google.com/search?q={{isbn}}',
				'cssclass' => '-google-search',
				// 'icon'     => [ 'misc-16', '' ], // FIXME
				// 'logo'     => '',
			],
			[
				'name'     => 'goodreads',
				'title'    => _x( 'Goodreads', 'Lookup: Name', 'geditorial-isbn' ),
				'desc'     => _x( 'Search Goodreads for Books via ISBN', 'Lookup: Description', 'geditorial-isbn' ),
				'template' => 'https://www.goodreads.com/search?utf8=1&q={{isbn}}&search_type=books',
				'cssclass' => '-goodreads',
				'icon'     => [ 'misc-24', 'goodreads' ],
				// 'logo'     => '',
				'color'    => '#59461b',
			],
		];

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			$list = array_merge( $list, [
				[
					'name'     => 'ketab.ir',
					'title'    => _x( 'Ketab.IR', 'Lookup: Name', 'geditorial-isbn' ),
					'template' => 'https://ketab.ir/search/{{isbn}}',
					'cssclass' => '-ketab-ir',
					// 'icon'     => [ 'misc-16', '' ], // FIXME
					// 'logo'     => '',
				],
			] );

		return $data[$context] = self::filters( 'lookups', $list, $context );
	}
}

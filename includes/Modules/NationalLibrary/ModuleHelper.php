<?php namespace geminorum\gEditorial\Modules\NationalLibrary;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'national_library';

	const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

	public static function getRemoteBody( $url )
	{
		return Core\HTTP::getHTML( $url, [
			'timeout'    => 30,
			'user-agent' => static::USER_AGENT,
		] );
	}

	public static function linkBib( $bib, $link = TRUE, $html = NULL, $class = FALSE )
	{
		$url = sprintf( 'https://opac.nlai.ir/opac-prod/bibliographic/%d', $bib );

		if ( ! $link )
			return $url;

		return Core\HTML::tag( 'a', [
			'href'     => $url,
			'class'    => $class,
			'data-bib' => $bib,
			'title'    => _x( 'Book Page on Nali.ir', 'Helper: Title Attr', 'geditorial-national-library' ),
			'rel'      => 'noreferrer',
			'target'   => '_blank',
		], $html ?? $bib );
	}

	public static function linkISBN( $isbn, $link = TRUE, $html = NULL, $class = FALSE )
	{
		$base = 'https://opac.nlai.ir';
		$url  = add_query_arg( [
			'simpleSearch.value'                           => $isbn,
			'bibliographicLimitQueryBuilder.biblioDocType' => 'BF',
			'simpleSearch.indexFieldId'                    => '221091',
			'nliHolding'                                   => '', // 'nli',
			'command'                                      => 'I',
			'simpleSearch.tokenized'                       => 'true',
			'classType'                                    => '0',
			'pageStatus'                                   => '0',
			// 'bibliographicLimitQueryBuilder.useDateRange'  => 'null',
			// 'bibliographicLimitQueryBuilder.year'          => '',
			'documentType'                                 => '',
			'attributes.locale'                            => 'fa',
		], $base.'/opac-prod/search/bibliographicSimpleSearchProcess.do' );

		if ( ! $link )
			return $url;

		return Core\HTML::tag( 'a', [
			'href'      => $url,
			'class'     => $class,
			'data-isbn' => $isbn,
			'title'     => _x( 'Search ISBN on Nali.ir', 'Helper: Title Attr', 'geditorial-national-library' ),
			'rel'       => 'noreferrer',
			'target'    => '_blank',
		], $html ?? Core\ISBN::prep( $isbn, TRUE ) );
	}

	public static function scrapeURLFromISBN( $isbn )
	{
		if ( ! $isbn )
			return FALSE;

		$base = 'https://opac.nlai.ir';
		$search = add_query_arg( [
			'simpleSearch.value'                           => $isbn,
			'bibliographicLimitQueryBuilder.biblioDocType' => 'BF',
			'simpleSearch.indexFieldId'                    => '221091',
			'nliHolding'                                   => '', // 'nli',
			'command'                                      => 'I',
			'simpleSearch.tokenized'                       => 'true',
			'classType'                                    => '0',
			'pageStatus'                                   => '0',
			'bibliographicLimitQueryBuilder.useDateRange'  => 'null',
			'bibliographicLimitQueryBuilder.year'          => '',
			'documentType'                                 => '',
			'attributes.locale'                            => 'fa',
		], $base.'/opac-prod/search/bibliographicSimpleSearchProcess.do' );

		if ( ! $body = self::getRemoteBody( $search ) )
			return FALSE;

		$dom = @new \Rct567\DomQuery\DomQuery( trim( $body ) );

		if ( ! $brief = $dom->find( '[href^="/opac-prod/search/briefListSearch.do"]' )->attr( 'href' ) )
			return FALSE;

		return $base.$brief;
	}

	public static function scrapeFipaFromURL( $url, $biblio = NULL, $isbn = NULL )
	{
		if ( ! $url )
			return FALSE;

		if ( ! $body = self::getRemoteBody( $url ) )
			return FALSE;

		$dom  = @new \Rct567\DomQuery\DomQuery( trim( $body ) );
		$data = [
			'rows'   => [],
			'title'  => '',
			'link'   => '',
			// 'search' => $url ?? '',
			'biblio' => $biblio ?? '',
			'isbn'   => $isbn ? Core\ISBN::sanitize( $isbn ) : '',
		];

		foreach ( $dom->find( '.formcontent table table table' )->children( 'tr' ) as $tr ) {

			$row = [];

			foreach ( $tr->children( 'td' ) as $cell ) {

				$text = Core\Text::trim( $cell->text() );

				if ( in_array( $text, [
					'اطلاعات رکورد کتابشناسی',
					'وضعیت فهرست نویسی',
				], TRUE ) )
					continue 2;

				if ( ':' == $text )
					continue;

				$text = Core\Text::trim( trim( $text, '.;:,' ) );

				if ( Core\Text::has( $text, "\n" ) )
					$row[] = nl2br( Core\Text::normalizeWhitespace( $text, TRUE ) );

				else
					$row[] = Core\Text::normalizeWhitespace( $text );
			}

			$data['rows'][] = $row;
		}

		if ( $title = $dom->find( '.formcontent table td [href^="http://opac.nlai.ir/opac-prod/bibliographic"]' )->text() )
			$data['title'] = Core\Text::normalizeWhitespace( Core\Text::correctMixedEncoding( $title ) );

		if ( $link = $dom->find( '.formcontent table td [href^="http://opac.nlai.ir/opac-prod/bibliographic"]' )->attr( 'href' ) )
			$data['link'] = $link;

		if ( $data['link'] && empty( $data['biblio'] ) )
			$data['biblio'] = Core\Text::stripPrefix( $data['link'], 'http://opac.nlai.ir/opac-prod/bibliographic/' );

		return $data;
	}

	public static function getFibaByBib( $bib, $isbn = NULL )
	{
		if ( WordPress\Strings::isEmpty( $bib ) )
			return FALSE;

		return self::scrapeFipaFromURL( self::linkBib( $bib, FALSE ), $bib, $isbn );
	}

	public static function getFibaByISBN( $isbn )
	{
		if ( WordPress\Strings::isEmpty( $isbn ) )
			return FALSE;

		if ( ! $type = Core\ISBN::validate( $isbn ) )
			return FALSE;

		if ( $url = self::scrapeURLFromISBN( $isbn ) )
			return self::scrapeFipaFromURL( $url, NULL, $isbn );

		$converted = $type === 2
			? Core\ISBN::convertToISBN10( $isbn )
			: Core\ISBN::convertToISBN13( $isbn );

		if ( $url = self::scrapeURLFromISBN( $converted ) )
			return self::scrapeFipaFromURL( $url, NULL, $isbn );

		return FALSE;
	}

	public static function getTitle( $raw, $fallback = FALSE )
	{
		if ( ! $parsed = self::parseFipa( $raw ) )
			return $fallback;

		return isset( $parsed['title'][0] )
			? $parsed['title'][0]
			: $fallback;
	}

	public static function parseFipa( $raw )
	{
		$data = [
			'title'   => [],
			'notes'   => [],
			'subject' => [],
			'people'  => [],
		];

		foreach ( $raw['rows'] as $row ) {

			if ( empty( $row[1] ) )
				continue;

			$text     = Core\Text::normalizeZWNJ( Core\Text::trim( $row[1], '.;:' ) );
			$featured = FALSE;

			switch ( $row[0] ) {

				case 'يادداشت':

					if ( Core\Text::starts( $text, 'عنوان اصلی:' ) )
						$data['title'][] = Core\Text::trim( Core\Text::stripPrefix( $text, 'عنوان اصلی:' ), '.;:' );

					else
						$data['notes'][] = $text;

					break;

				// case 'عنوان قراردادی':
				case 'عنوان و نام پديدآور':

					$data['title'][] = $text;
					break;

				case 'رده بندی کنگره':

					$data['llc'] = Core\Number::translate( $text );
					break;

				case 'رده بندی دیویی':

					$data['ddc'] = Core\Number::translate( $text );
					break;

				case 'شابک':

					if ( Core\Text::has( $text, ':' ) ) {

						$isbn = WordPress\Strings::getSeparated( $text, ':', 2 );

						if ( isset( $isbn[1] ) )
							$data['isbn'] = Core\ISBN::sanitize( $isbn[1] );

						if ( isset( $isbn[0] ) )
							$data['price'] = Core\Number::translate( $isbn[0] );

					} else if ( Core\Text::has( $text, [ 'ریال', '﷼' ] ) ) {

						$parts = WordPress\Strings::getSeparated( str_ireplace( [ 'ریال', '﷼' ], '|', $text ) );

						if ( $isbn = array_pop( $parts ) )
							$data['isbn'] = Core\ISBN::sanitize( $isbn );

						if ( count( $parts ) > 1 )
							$data['price'] = Core\Arraay::mergeConsecutive( $parts, 2, ' ' );

						else if ( count( $parts ) )
							$data['price'] = Core\Number::translate( $parts[0] );

					} else {

						$data['isbn'] = Core\Number::translate( str_ireplace( '-', '', $text ) );
					}

					break;

				case 'شماره کتابشناسی ملی':

					$data['biblio'] = Core\Number::translate( $row[1] );
					break;

				case 'موضوع':

					$text = str_ireplace( [ '--', '<br>', '<br/>', '<br />' ], '|', $text );
					$text = str_ireplace( [ '*' ], '', $text );
					$data['subject'] = Core\Arraay::prepString( $data['subject'], WordPress\Strings::getSeparated( $text, '|' ) );
					break;

				case 'فروست':

					$data['serie'] = Core\Text::normalizeWhitespace( $text );
					break;

				case 'سرشناسه':

					$featured = TRUE;

				case 'شناسه افزوده':

					$text = str_ireplace( [ '--', '<br>', '<br/>', '<br />' ], '|', $text );
					$text = str_ireplace( [ '*' ], '', $text );

					$data['people'][] = [
						'raw'      => $text,
						'parsed'   => self::parsePeople( WordPress\Strings::getSeparated( $text, '|' ), $featured ),
						'featured' => $featured,
						'label'    => $row[0],
					];

					break;
			}
		}

		return $data;
	}

	public static function parsePeople( $batch, $featured = FALSE )
	{
		$parsed = [];

		foreach ( $batch as $raw ) {

			if ( WordPress\Strings::isEmpty( $raw ) )
				continue;

			$raw   = Core\Text::normalizeZWNJ( $raw );
			$raw   = Core\Text::singleWhitespaceUTF8( $raw );
			$parts = WordPress\Strings::getSeparated( $raw, '،,' );
			$count = count( $parts );

			if ( 1 === $count ) {

				$flags = [ 'undefined' ];

				if ( $featured )
					$flags[] = 'featured';

				$parsed[] = [
					'raw'   => $raw,
					'flags' => $flags,
					'data'  => [],
				];

			} else if ( $count > 1 ) {

				$flags = [ 'has-name' ];
				$data  = [
					'fullname'   => Core\Text::spaced( $parts[1], $parts[0] ),
					'first_name' => $parts[1],
					'last_name'  => $parts[0],
				];

				if ( $featured )
					$flags[] = 'featured';

				if ( $count > 2 ) {

					if ( Core\Text::has( $parts[2], [ 'فروردین', 'شهریور' ] ) )
						$data['calendar'] = 'persian';

					else if ( Core\Text::has( $parts[2], 'م.' ) )
						$data['calendar'] = 'gregorian';

					else if ( Core\Text::has( $parts[2], 'ق.' ) )
						$data['calendar'] = 'islamic';

					$dates = WordPress\Strings::getSeparated( str_ireplace( [
						'م.',
						'ق.',
						'?',
						'؟',
						'فروردین',
						'شهریور',
					], '', $parts[2] ), '-', 2 );

					if ( count( $dates ) )
						$flags[] = 'has-dates';

					if ( isset( $dates[0] ) )
						$data['born'] = Core\Number::translate( $dates[0] );

					if ( isset( $dates[1] ) )
						$data['dead'] = Core\Number::translate( $dates[1] );
				}

				if ( $count > 3 )
					$data['relation'] = $parts[3];

				$parsed[] = [
					'raw'   => $raw,
					'flags' => $flags,
					'data'  => $data,
				];
			}
		}

		return $parsed;
	}

	public static function linkPeople( $criteria, $link = TRUE, $html = NULL )
	{
		$criteria = Core\Text::normalizeZWNJ( $criteria );

		if ( WordPress\Strings::isEmpty( $criteria ) )
			return FALSE;

		$base = 'https://opac.nlai.ir';
		$url  = add_query_arg( [
			'simpleSearch.value'                           => urlencode( $criteria ),
			'bibliographicLimitQueryBuilder.biblioDocType' => 'BF',
			'simpleSearch.indexFieldId'                    => '220901',
			'nliHolding'                                   => '', // 'nli',
			'command'                                      => 'I',
			'simpleSearch.tokenized'                       => 'true',
			'classType'                                    => '0',
			'pageStatus'                                   => '0',
			'bibliographicLimitQueryBuilder.useDateRange'  => 'null',
			'bibliographicLimitQueryBuilder.year'          => '',
			'documentType'                                 => '',
			'attributes.locale'                            => 'fa',
		], $base.'/opac-prod/search/bibliographicSimpleSearchProcess.do' );

		if ( ! $link )
			return $url;

		return Core\HTML::tag( 'a', [
			'href'      => $url,
			'data-isbn' => $criteria,
			'title'     => _x( 'Search People on Nali.ir', 'Helper: Title Attr', 'geditorial-national-library' ),
			'rel'       => 'noreferrer',
			'target'    => '_blank',
		], $html ?? $criteria );
	}

	public static function generateHints( $raw, $post, $context, $queried )
	{
		$hints = [];

		if ( ! $data = self::parseFipa( $raw ) )
			return $hints;

		if ( ! empty( $data['biblio'] ) )
			$hints[] = [
				'html'     => self::linkBib( $data['biblio'], TRUE, isset( $data['title'][0] ) ? $data['title'][0] : NULL ),
				'class'    => static::classs( 'biblio' ),
				'source'   => static::MODULE,
				'priority' => 60,
			];

		if ( empty( $data['people'] ) )
			return $hints;

		foreach ( $data['people'] as $row )
			$hints[] = [
				'html'     => sprintf( '%s: %s', $row['label'], self::linkPeople( $row['raw'] ) ),
				'data'     => isset( $row['parsed'][0]['data'] ) ? $row['parsed'][0]['data'] : [],
				'class'    => static::classs( 'people', isset( $row['parsed'][0]['flags'] ) ? $row['parsed'][0]['flags'] : [] ),
				'source'   => static::MODULE,
				'priority' => $row['featured'] ? 6 : 8,
			];

		return $hints;
	}

	/**
	 * Generates data for a CSV row.
	 *
	 * @param string $isbn
	 * @return array
	 */
	public static function getFipaRow( $isbn )
	{
		if ( ! $sanitized = Core\ISBN::sanitize( $isbn ) )
			return [];

		if ( ! $raw = self::getFibaByISBN( $sanitized ) )
			return [];

		$parsed = self::parseFipa( $raw );
		$people = [];

		foreach ( $parsed['people'] as $person )
			if ( ! empty( $person['parsed'][0]['data']['fullname'] ) )
				$people[] = $person['parsed'][0]['data']['fullname'];

		$data = [
			'raw'      => $isbn,
			'title'    => isset( $parsed['title'][0] ) ? $parsed['title'][0] : '',
			'alttitle' => isset( $parsed['title'][1] ) ? $parsed['title'][1] : '',
			'people'   => $people,
			'serie'    => isset( $parsed['serie'] ) ? $parsed['serie'] : '',
			'isbn'     => isset( $parsed['isbn'] ) ? sprintf( 'ISBN:%s', $parsed['isbn'] ) : '',
			'biblio'   => isset( $parsed['biblio'] ) ? $parsed['biblio'] : '',
			'subject'  => $parsed['subject'],
			// 'price'    => isset( $parsed['price'] ) ? $parsed['price'] : '',
			// 'llc'      => isset( $parsed['llc'] ) ? $parsed['llc'] : '',
			// 'ddc'      => isset( $parsed['ddc'] ) ? $parsed['ddc'] : '',
		];

		return $data;
	}
}

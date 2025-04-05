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

	public static function linkBib( $bib, $link = TRUE, $html = NULL )
	{
		$url = sprintf( 'https://opac.nlai.ir/opac-prod/bibliographic/%d', $bib ) ;

		if ( ! $link )
			return $url;

		return Core\HTML::tag( 'a', [
			'href'     => $url,
			'data-bib' => $bib,
			'title'    => _x( 'Book Page on Nali.ir', 'Helper: Title Attr', 'geditorial-national-library' ),
			'rel'      => 'noreferrer',
			'target'   => '_blank',
		], $html ?? $bib );
	}

	public static function linkISBN( $isbn, $link = TRUE, $html = NULL )
	{
		$base = 'https://opac.nlai.ir';
		$url  = add_query_arg( [
			'simpleSearch.value'                           => $isbn,
			'bibliographicLimitQueryBuilder.biblioDocType' => 'BF',
			'simpleSearch.indexFieldId'                    => '221091',
			'nliHolding'                                   => 'nli',
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
			'nliHolding'                                   => 'nli',
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

	public static function scrapeFipaFromURL( $url )
	{
		if ( ! $url )
			return FALSE;

		if ( ! $body = self::getRemoteBody( $url ) )
			return FALSE;

		$data = [];
		$dom  = @new \Rct567\DomQuery\DomQuery( trim( $body ) );

		foreach ( $dom->find( '.formcontent table table table' )->children('tr') as $tr ) {

			$row = [];

			foreach ( $tr->children('td') as $cell ) {

				$text = Core\Text::trim( $cell->text() );

				if ( in_array( $text, [
					'اطلاعات رکورد کتابشناسی',
					'وضعیت فهرست نویسی',
				], TRUE ) )
					continue 2;

				if ( ':' == $text )
					continue;

				$text = trim( $text, '.;:' );

				if ( Core\Text::has( $text, "\n" ) ) {
					// $row = array_merge( $row, explode( "\n", $text ) );
					$row[] = nl2br( Core\Text::normalizeWhitespace( $text, TRUE ) );
				} else {
					$row[] = Core\Text::normalizeWhitespace( $text );
				}
			}

			$data[] = $row;
		}

		return $data;
	}

	public static function getFibaByBib( $bib )
	{
		if ( WordPress\Strings::isEmpty( $bib ) )
			return FALSE;

		return self::scrapeFipaFromURL( self::linkBib( $bib, FALSE ) );
	}

	public static function getFibaByISBN( $isbn )
	{
		if ( WordPress\Strings::isEmpty( $isbn ) )
			return FALSE;

		return self::scrapeFipaFromURL( self::scrapeURLFromISBN( $isbn ) );
	}

	public static function parseFipa( $raw )
	{
		$data = [
			'title'   => [],
			'notes'   => [],
			'subject' => [],
			'people'  => [],
		];

		foreach ( $raw as $row ) {

			if ( empty( $row[1] ) )
				continue;

			$text     = Core\Text::normalizeZWNJ( trim( $row[1], '.;:' ) );
			$featured = FALSE;

			switch ( $row[0] ) {

				case 'يادداشت':

					$data['notes'][] = $text;
					break;

				case 'عنوان و نام پديدآور':
					$data['title'][] = $text;
					break;

				case 'عنوان قراردادی':

					$data['title'][] = $text;
					break;

				case 'رده بندی کنگره':

					$data['llc'] = Core\Number::translate( $text );
					break;

				case 'رده بندی دیویی':

					$data['ddc'] = Core\Number::translate( $text );
					break;

				case 'شابک':

					$data['isbn'] = Core\Number::translate( str_ireplace( '-', '', $text ) );
					// $data['isbn'] = Core\ISBN::sanitize( $text );
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

					$data['serie'] = $text;
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
			$raw   = Core\Text::normalizeWhitespaceUTF8( $raw );
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
					'fullname'   => sprintf( '%s %s', $parts[1], $parts[0] ),
					'first_name' => $parts[1],
					'last_name'  => $parts[0],
				];

				if ( $featured )
					$flags[] = 'featured';

				if ( $count > 2 ) {

					if ( Core\Text::has( $parts[2], [ 'فروردین' ] ) )
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
}

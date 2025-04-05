<?php namespace geminorum\gEditorial\Modules\NationalLibrary;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'national_library';

	public static function linkBib( $bib, $link = TRUE )
	const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

	public static function getRemoteBody( $url )
	{
		return Core\HTTP::getHTML( $url, [
			'timeout'    => 30,
			'user-agent' => static::USER_AGENT,
		] );
	}

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
		], $bib );
	}

	public static function linkISBN( $isbn, $link = TRUE )
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
		], Core\ISBN::prep( $isbn, TRUE ) );
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

		return self::scrapeFipaFromURL( sprintf( 'https://opac.nlai.ir/opac-prod/bibliographic/%d', $bib ) );
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
			'note'    => [],
			'subject' => [],
		];

		foreach ( $raw as $row ) {

			if ( empty( $row[1] ) )
				continue;

			$text = trim( $row[1], '.;:' );

			switch ( $row[0] ) {

				case 'يادداشت':

					$data['note'][] = $text;
					break;

				case 'عنوان قراردادی':

					$data['title'] = $text;
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

					$data['bibliographic'] = Core\Number::translate( $row[1] );
					break;

				case 'موضوع':

					$text = str_ireplace( [ '--', '<br>', '<br/>', '<br />' ], '|', $text );
					$text = str_ireplace( [ '*' ], '', $text );
					$data['subject'] = Core\Arraay::prepString( $data['subject'], WordPress\Strings::getSeparated( $text, '|' ) );
					break;

				case 'فروست':

					$data['serie'] = $text;
					break;
			}
		}

		return $data;
	}
}

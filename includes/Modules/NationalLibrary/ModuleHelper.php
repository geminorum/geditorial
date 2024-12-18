<?php namespace geminorum\gEditorial\Modules\NationalLibrary;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'national_library';

	public static function linkBib( $bib, $link = TRUE )
	{
		$url = sprintf( 'http://opac.nlai.ir/opac-prod/bibliographic/%d', $bib ) ;

		if ( ! $link )
			return $url;

		return Core\HTML::tag( 'a', [
			'href'     => $url,
			'data-bib' => $bib,
			'title'    => _x( 'Book Page on Nali.ir', 'Helper: Title Attr', 'geditorial-national-library' ),
			'rel'      => 'noopener',
			'target'   => '_blank',
		], $bib );
	}

	public static function linkISBN( $isbn, $link = TRUE )
	{
		$base = 'http://opac.nlai.ir';
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
			'rel'       => 'noopener',
			'target'    => '_blank',
		], Core\ISBN::prep( $isbn, TRUE ) );
	}

	public static function scrapeURLFromISBN( $isbn )
	{
		if ( ! $isbn )
			return FALSE;

		$base = 'http://opac.nlai.ir';
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

		if ( ! $body = Core\HTTP::getHTML( $search ) )
			return FALSE;

		$dom = new \Rct567\DomQuery\DomQuery( trim( $body ) );

		if ( ! $brief = $dom->find( '[href^="/opac-prod/search/briefListSearch.do"]' )->attr( 'href' ) )
			return FALSE;

		return $base.$brief;
	}

	public static function scrapeFipaFromURL( $url )
	{
		if ( ! $url )
			return FALSE;

		if ( ! $body = Core\HTTP::getHTML( $url ) )
			return FALSE;

		$data = [];
		$dom  = new \Rct567\DomQuery\DomQuery( trim( $body ) );

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

		return self::scrapeFipaFromURL( sprintf( 'http://opac.nlai.ir/opac-prod/bibliographic/%d', $bib ) );
	}

	public static function getFibaByISBN( $isbn )
	{
		if ( WordPress\Strings::isEmpty( $isbn ) )
			return FALSE;

		return self::scrapeFipaFromURL( self::scrapeURLFromISBN( $isbn ) );
	}
}

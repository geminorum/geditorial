<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\WordPress;

class Locations extends gEditorial\Service
{
	public static function setup()
	{
		if ( is_admin() )
			return;

		add_filter( static::BASE.'_prep_location', [ __CLASS__, 'filter_prep_location_front' ], 5, 3 );
	}

	public static function isParserAvailable()
	{
		return in_array(
			Core\L10n::locale( TRUE ),
			Misc\AddressInPersian::SUPPORTED_LOCALE,
			TRUE
		);
	}

	// OLD: `WordPress\Strings::prepAddress()`
	public static function prepAddress( $data, $context = 'display', $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( ! $data = Core\Text::normalizeWhitespace( self::cleanupChars( $data ) ) )
			return $fallback;

		$data = trim( $data, '.-|…' );
		$data = str_ireplace( [ '_', '|', '–', '—'  ], '-', $data );
		$data = sprintf( ' %s ', $data ); // padding with space

		if ( self::isParserAvailable() )
			$data = Misc\AddressInPersian::prepExtra( $data, $context, '' );

		$data = preg_replace( '/\s+([\,\،])/mu', '$1', $data );
		$data = preg_replace( '/\s+([\-])/mu', '$1', $data );
		$data = preg_replace( '/([\-])\s+/mu', '$1', $data );

		return Core\Text::normalizeWhitespace( $data );
	}

	public static function prepVenue( $value, $empty = '', $separator = NULL )
	{
		if ( self::empty( $value ) )
			return $empty;

		$list = [];

		foreach ( Markup::getSeparated( $value ) as $location )
			if ( $prepared = apply_filters( static::BASE.'_prep_location', $location, $location, $value ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function filter_prep_location_front( $location, $raw, $value )
	{
		if ( $link = WordPress\URL::search( $location ) )
			return Core\HTML::link( $location, $link );

		return $location;
	}

	public static function getTermLocation( $term = NULL, $context = NULL )
	{
		if ( ! gEditorial()->enabled( 'terms' ) )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		if ( $address = TaxonomyFields::getFieldRaw( 'address', $term->term_id ) ) {

			return [
				'address' => $address,
				'title'   => TaxonomyFields::getFieldRaw( 'venue', $term->term_id ) ?: '',
				'latlng'  => TaxonomyFields::getFieldRaw( 'latlng', $term->term_id ) ?: '',
			];
		}

		return FALSE;
	}

	public static function getPostLocation( $post = NULL, $context = NULL )
	{
		if ( ! gEditorial()->enabled( 'meta' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( gEditorial()->enabled( 'venue' ) ) {

			if ( $items = gEditorial()->module( 'venue' )->paired_all_connected_from( $post, $context ) ) {
				foreach ( $items as $item )
					return [
						'title'   => WordPress\Post::fullTitle( $item ),
						'address' => PostTypeFields::getFieldRaw( 'street_address', $item->ID ) ?: '',
						'latlng'  => PostTypeFields::getFieldRaw( 'latlng', $item->ID ) ?: '',
					];
			}

		} else if ( $address = PostTypeFields::getFieldRaw( 'street_address', $post->ID ) ) {

			return [
				'address' => $address,
				'title'   => PostTypeFields::getFieldRaw( 'venue_string', $post->ID ) ?: '',
				'latlng'  => PostTypeFields::getFieldRaw( 'latlng', $post->ID ) ?: '',
			];
		}

		return FALSE;
	}
}

<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

class Locations extends gEditorial\Service
{
	public static function setup()
	{
		if ( is_admin() )
			return;

		add_filter( static::BASE.'_prep_location', [ __CLASS__, 'filter_prep_location_front' ], 5, 3 );
	}

	public static function prepVenue( $value, $empty = '', $separator = NULL )
	{
		if ( self::empty( $value ) )
			return $empty;

		$list = [];

		foreach ( Helper::getSeparated( $value ) as $location )
			if ( $prepared = apply_filters( static::BASE.'_prep_location', $location, $location, $value ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function filter_prep_location_front( $location, $raw, $value )
	{
		if ( $link = Core\WordPress::getSearchLink( $location ) )
			return Core\HTML::link( $location, $link );

		return $location;
	}

	public static function getSingularLocation( $post = NULL, $context = NULL )
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

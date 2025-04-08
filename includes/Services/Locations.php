<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Locations extends gEditorial\Service
{
	// TODO: support: `Venue` Module

	public static function setup()
	{
		if ( is_admin() )
			return;

		add_filter( static::BASE.'_prep_location', [ __CLASS__, 'filter_prep_location_front' ], 5, 3 );
	}

	public static function filter_prep_location_front( $location, $raw, $value )
	{
		if ( $link = Core\WordPress::getSearchLink( $location ) )
			return Core\HTML::link( $location, $link );

		return $location;
	}
}

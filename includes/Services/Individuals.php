<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Individuals extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function setup()
	{
		if ( ! is_admin() )
			return;

		add_filter( static::BASE.'_prep_individual', [ __CLASS__, 'filter_prep_individual_front' ], 5, 3 );
	}

	public static function filter_prep_individual_front( $individual, $raw, $value )
	{
		if ( $link = Core\WordPress::getSearchLink( $individual ) )
			return Core\HTML::link( $individual, $link );

		return $individual;
	}
}

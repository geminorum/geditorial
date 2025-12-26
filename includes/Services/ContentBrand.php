<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ContentBrand extends gEditorial\Service
{
	public static function siteIcon( $size = NULL, $fallback = '' )
	{
		return get_site_icon_url( $size ?? 'full', $fallback );
	}
}

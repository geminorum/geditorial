<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ContentBrand extends gEditorial\Service
{
	public static function siteIcon(
		?int $size = NULL,
		string|false|null $fallback = '',
	): null|false|string {

		return get_site_icon_url(
			$size ?? 512,  // TODO: handle strings
			$fallback,
		);
	}
}

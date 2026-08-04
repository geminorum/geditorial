<?php namespace geminorum\gEditorial\Modules\Units;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'units';

	public static function getPostTypeFieldKeyMap()
	{
		return [
			'distance_in_metres' => [ 'distance_in_metres', 'distance_in_meter' ],
		];
	}
}

<?php namespace geminorum\gEditorial\Modules\Placard;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{
	const MODULE = 'placard';

	public static function availableTemplates()
	{
		return [
			'bs5-carousel-slide'   => _x( 'Carousel Slides', 'Template Title', 'geditorial-placard' ),
		];
	}

	public static function defaultTemplate()
	{
		return Core\Arraay::keyFirst( self::availableTemplates() );
	}
}

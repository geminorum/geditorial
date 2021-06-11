<?php namespace geminorum\gEditorial\Modules\Terms;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'terms';

	public static function termImage( $atts = [], $module = NULL )
	{
		return parent::termImage( $atts, static::MODULE );
	}

	public static function termContact( $atts = [], $module = NULL )
	{
		return parent::termContact( $atts, static::MODULE );
	}
}

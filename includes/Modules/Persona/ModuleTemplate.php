<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\ISBN;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'persona';

	public static function summary( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'human_posttype', 'human' );

		return self::metaSummary( $atts );
	}

	public static function theCover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	public static function cover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'human_posttype', 'human' );

		return parent::postImage( $atts, static::MODULE );
	}
}

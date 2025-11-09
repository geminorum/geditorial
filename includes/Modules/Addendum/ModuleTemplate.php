<?php namespace geminorum\gEditorial\Modules\Addendum;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{
	const MODULE = 'addendum';

	public static function downloadFileSize( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$html = gEditorial()
			->module( static::MODULE )
			->maindownload__get_filesize( $atts['id'], TRUE );

		if ( ! $atts['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function summary( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'primary_posttype', 'appendage' );

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
			$atts['type'] = self::constant( 'primary_posttype', 'appendage' );

		return parent::postImage( $atts, static::MODULE );
	}
}

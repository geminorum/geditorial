<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;

class Scripts extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	public static function enqueue( $asset, $dep = [ 'jquery' ], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/js' )
	{
		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$asset.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueVendor( $asset, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/js/vendor' )
	{
		return self::enqueue( $asset, $dep, $version, $base, $path );
	}

	public static function enqueuePackage( $asset, $package = NULL, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/packages' )
	{
		if ( is_null( $package ) )
			$package = $asset.'/'.$asset;

		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$package.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function registerPackage( $asset, $package = NULL, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/packages' )
	{
		if ( is_null( $package ) )
			$package = $asset.'/'.$asset;

		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( $handle, $base.$path.'/'.$package.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueTimeAgo()
	{
		$callback = [ 'gPersianDateTimeAgo', 'enqueue' ];

		if ( ! is_callable( $callback ) )
			return FALSE;

		return call_user_func( $callback );
	}

	public static function registerColorBox( $ver = '1.6.4' )
	{
		wp_register_style( 'jquery-colorbox', GEDITORIAL_URL.'assets/css/admin.colorbox.css', [], $ver, 'screen' );
		wp_register_script( 'jquery-colorbox', GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], $ver, TRUE );

		return 'jquery-colorbox';
	}

	public static function enqueueColorBox()
	{
		wp_enqueue_style( 'jquery-colorbox' );
		wp_enqueue_script( 'jquery-colorbox' );

		return 'jquery-colorbox';
	}

	public static function pkgAutosize( $ver = '4.0.2' )
	{
		$handle = static::BASE.'-autosize';

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], $ver, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	public static function pkgSortable( $enqueue = FALSE, $ver = '0.9.13' )
	{
		return $enqueue
			? self::enqueuePackage( 'jquery-sortable', NULL, [ 'jquery' ], $ver )
			: self::registerPackage( 'jquery-sortable', NULL, [ 'jquery' ], $ver );
	}

	public static function pkgListJS( $enqueue = FALSE, $ver = '1.5.0' )
	{
		return $enqueue
			? self::enqueuePackage( 'listjs', 'list.js/list', [], $ver )
			: self::registerPackage( 'listjs', 'list.js/list', [], $ver );
	}
}

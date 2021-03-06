<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Main;

class Scripts extends Main
{

	const BASE = 'geditorial';

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

	public static function enqueueColorPicker()
	{
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public static function enqueueCodeEditor()
	{
		wp_enqueue_script( 'code-editor' );
		wp_enqueue_style( 'code-editor' );
	}

	public static function enqueueThickBox()
	{
		if ( function_exists( 'add_thickbox' ) )
			add_thickbox();
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

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], NULL, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	public static function pkgVueJS( $enqueue = FALSE, $ver = '2.6.12' )
	{
		$handle = static::BASE.'-vuejs';

		$url = WordPress::isDev()
			? 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js'
			: 'https://cdn.jsdelivr.net/npm/vue@'.$ver.'/dist/vue.min.js';

		if ( $enqueue )
			wp_enqueue_script( $handle, $url, [], $ver, TRUE );
		else
			wp_register_script( $handle, $url, [], $ver, TRUE );

		return $handle;
	}

	public static function pkgSortable( $enqueue = FALSE, $ver = '0.9.13' )
	{
		return $enqueue
			? self::enqueuePackage( 'jquery-sortable', NULL, [ 'jquery' ], $ver )
			: self::registerPackage( 'jquery-sortable', NULL, [ 'jquery' ], $ver );
	}

	public static function pkgListJS( $enqueue = FALSE, $ver = '2.3.0' )
	{
		return $enqueue
			? self::enqueuePackage( 'listjs', 'list.js/list', [], $ver )
			: self::registerPackage( 'listjs', 'list.js/list', [], $ver );
	}

	// @REF: https://github.com/axenox/onscan.js
	// @REF: https://a.kabachnik.info/onscan-js.html
	public static function pkgOnScanJS( $enqueue = FALSE, $ver = '1.5.2' )
	{
		return $enqueue
			? self::enqueuePackage( 'onscanjs', 'onscan.js/onscan', [], $ver )
			: self::registerPackage( 'onscanjs', 'onscan.js/onscan', [], $ver );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( static::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.static::BASE.'", '.wp_json_encode( $strings ).');'."\n" : '';
	}
}

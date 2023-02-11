<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Main;

class Scripts extends Main
{

	const BASE = 'geditorial';

	public static function enqueueStyle( $asset, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/css', $media = 'all' )
	{
		$handle = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );

		wp_enqueue_style( $handle, $base.$path.'/'.$asset.'.css', $dep, $version, $media );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
	}

	public static function inlineScript( $asset, $script, $dep = [ 'jquery' ] )
	{
		if ( empty( $script ) )
			return FALSE;

		$handle = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );

		// @REF: https://core.trac.wordpress.org/ticket/44551
		// @REF: https://wordpress.stackexchange.com/a/311279
		wp_register_script( $handle, '', $dep, '', TRUE );
		wp_enqueue_script( $handle ); // must register then enqueue
		wp_add_inline_script( $handle, $script );

		return $handle;
	}

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

	// LEGACY: do not use thickbox anymore!
	// @SEE: https://core.trac.wordpress.org/ticket/17249
	// @SEE: http://web.archive.org/web/20130224045422/http://binarybonsai.com/blog/using-thickbox-in-the-wordpress-admin
	// @SEE: https://codex.wordpress.org/Javascript_Reference/ThickBox
	public static function enqueueThickBox()
	{
		if ( function_exists( 'add_thickbox' ) )
			add_thickbox();
	}

	// @REF: https://www.jacklmoore.com/colorbox/
	// @REF: https://github.com/jackmoore/colorbox
	public static function registerColorBox( $ver = '1.6.4' )
	{
		$handle = 'jquery-colorbox';

		wp_register_style( $handle, GEDITORIAL_URL.'assets/css/admin.colorbox.css', [], $ver, 'screen' );
		wp_register_script( $handle, GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], $ver, TRUE );

		return $handle;
	}

	public static function enqueueColorBox()
	{
		$handle = 'jquery-colorbox';

		wp_enqueue_style( $handle );
		wp_enqueue_script( $handle );

		return $handle;
	}

	public static function pkgAutosize( $ver = '4.0.2' )
	{
		$handle = static::BASE.'-autosize';

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], NULL, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	public static function pkgVueJS2( $enqueue = FALSE, $ver = '2.6.14' )
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

	// @REF: https://www.jsdelivr.com/package/npm/vue
	public static function pkgVueJS3( $enqueue = FALSE, $ver = '3.2.11' )
	{
		$handle = static::BASE.'-vuejs';

		$url = WordPress::isDev()
			? 'https://unpkg.com/vue@next'
			: 'https://cdn.jsdelivr.net/npm/vue@'.$ver.'/dist/vue.global.min.js';

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

	// @REF: https://github.com/fgnass/spin.js
	// @REF: https://spin.js.org/
	public static function pkgSpinJS( $enqueue = FALSE, $ver = '4.1.1' )
	{
		return $enqueue
			? self::enqueuePackage( 'spinjs', 'spin.js/spin.umd', [], $ver )
			: self::registerPackage( 'spinjs', 'spin.js/spin.umd', [], $ver );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( static::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.static::BASE.'", '.wp_json_encode( $strings ).');'."\n" : '';
	}
}

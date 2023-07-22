<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Scripts extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function noScriptMessage()
	{
		echo Core\HTML::tag( 'noscript', '<strong>'._x( 'We\'re sorry but this application doesn\'t work properly without JavaScript enabled. Please enable it to continue.', 'Scripts: No Script Message', 'geditorial' ).'</strong>' );
	}

	public static function renderAppMounter( $name, $module = FALSE, $html = NULL )
	{
		if ( is_null( $html ) )
			$html = Plugin::moment();

		echo Core\HTML::tag( 'div', [
			'id'    => sprintf( '%s-app-%s', static::BASE, $name ),
			'class' => [
				'-wrap',
				static::BASE.'-wrap',
				( $module ? ( '-'.$module ) : '' ),
				'editorial-app',
				'hide-if-no-js',
			],
		], $html ?: '' );
	}

	public static function enqueueApp( $name, $dependencies = [], $version = NULL, $path = 'assets/apps', $base_path = GEDITORIAL_DIR, $base_url = GEDITORIAL_URL )
	{
		$handle = strtolower( static::BASE.'-'.str_replace( '.', '-', $name ) );

		$script = sprintf( '%s%s/%s/build/main.js', $base_url, $path, $name );
		$style  = sprintf( '%s%s/%s/build/main.css', $base_url, $path, $name );
		$asset  = sprintf( '%s%s/%s/build/main.asset.php', $base_path, $path, $name );

		$config = is_readable( $asset )
			? require( $asset )
			: [
				'dependencies' => [],
				'version'      => $version ?? GEDITORIAL_VERSION,
			];

		wp_enqueue_style( $handle, $style, [], $config['version'], 'all' );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		wp_enqueue_script( $handle, $script, Core\Arraay::prepString( $config['dependencies'], $dependencies ), $config['version'], TRUE );

		return $handle;
	}

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

	public static function getPrintStylesURL( $name = 'general', $base = GEDITORIAL_URL, $path = 'assets/css' )
	{
		return sprintf( '%s%s/print.%s%s.css', $base, $path, $name, Core\HTML::rtl() ? '-rtl' : '' );
	}

	public static function enqueueWordCount()
	{
		return self::enqueue( 'all.wordcount', [
			'jquery',
			'word-count',
			'underscore',
		] );
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

	public static function enqueueColorBox()
	{
		return self::enqueue( 'all.colorbox', [ 'jquery', self::pkgColorBox( TRUE ) ] );
	}

	// @REF: https://www.jacklmoore.com/colorbox/
	// @REF: https://github.com/jackmoore/colorbox
	public static function pkgColorBox( $enqueue = FALSE, $ver = '1.6.4' )
	{
		$handle = 'jquery-colorbox';

		if ( $enqueue ) {

			wp_enqueue_style( $handle, GEDITORIAL_URL.'assets/css/admin.colorbox.css', [], $ver, 'screen' );
			wp_enqueue_script( $handle, GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], $ver, TRUE );

		} else {

			wp_register_style( $handle, GEDITORIAL_URL.'assets/css/admin.colorbox.css', [], $ver, 'screen' );
			wp_register_script( $handle, GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], $ver, TRUE );
		}

		return $handle;
	}

	public static function pkgAutosize( $ver = '6.0.1' )
	{
		$handle = static::BASE.'-autosize';

		wp_enqueue_script( $handle, '//cdn.jsdelivr.net/npm/autosize@'.$ver.'/dist/autosize.min.js', [], NULL, TRUE );
		wp_add_inline_script( $handle, "autosize(document.querySelectorAll('textarea'));" );

		return $handle;
	}

	public static function pkgVueJS2( $enqueue = FALSE, $ver = '2.6.14' )
	{
		$handle = static::BASE.'-vuejs';

		$url = Core\WordPress::isDev()
			? 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js'
			: 'https://cdn.jsdelivr.net/npm/vue@'.$ver.'/dist/vue.min.js';

		if ( $enqueue )
			wp_enqueue_script( $handle, $url, [], $ver, TRUE );
		else
			wp_register_script( $handle, $url, [], $ver, TRUE );

		return $handle;
	}

	// @REF: https://www.jsdelivr.com/package/npm/vue
	public static function pkgVueJS3( $enqueue = FALSE, $ver = '3.2.36' )
	{
		$handle = static::BASE.'-vuejs';

		$url = Core\WordPress::isDev()
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

	public static function pkgListJS( $enqueue = FALSE, $ver = '2.3.1' )
	{
		return $enqueue
			? self::enqueuePackage( 'listjs', 'list.js/list', [], $ver )
			: self::registerPackage( 'listjs', 'list.js/list', [], $ver );
	}

	public static function pkgPrintThis( $enqueue = FALSE, $ver = '2.0.0' )
	{
		return $enqueue
			? self::enqueuePackage( 'printthis', 'printThis/printThis', [ 'jquery' ], $ver )
			: self::registerPackage( 'printthis', 'printThis/printThis', [ 'jquery' ], $ver );
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

	// @REF: https://github.com/select2/select2/
	// @REF: https://select2.org/
	public static function pkgSelect2( $enqueue = FALSE, $ver = '4.1.0-rc.0' )
	{
		return $enqueue
			? self::enqueuePackage( 'select2', 'select2/select2', [ 'jquery' ], $ver )
			: self::registerPackage( 'select2', 'select2/select2', [ 'jquery' ], $ver );
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( static::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.static::BASE.'", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$props = array_merge( $args, [
			'_base' => static::BASE,
			'_url'  => sanitize_url( admin_url( 'admin-ajax.php' ) ),

			'_restBase'  => rest_url(),
			'_restNonce' => wp_create_nonce( 'wp_rest' ),
		] );

	?><script type="text/javascript">
/* <![CDATA[ */
	window.<?php echo $object; ?> = <?php echo $object; ?> = <?php echo wp_json_encode( $props ); ?>;
	<?php if ( Core\WordPress::isDev() ) {
		echo 'console.log("'.$object.'", '.$object.');'."\n";
		echo "\t".'jQuery(document).on("gEditorialReady", function(e, module, app){console.log("'.$object.': "+module, app);});'."\n";
	} ?>
/* ]]> */
</script><?php
	}
}

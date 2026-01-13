<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Scripts extends WordPress\Main
{

	const BASE    = 'geditorial';
	const PATH    = GEDITORIAL_DIR;
	const URL     = GEDITORIAL_URL;
	const VERSION = GEDITORIAL_VERSION;

	public static function factory()
	{
		return gEditorial();
	}

	// TODO: move to `Services\Markup`
	public static function noScriptMessage( $verbose = TRUE )
	{
		$html = Core\HTML::tag( 'noscript',
			'<strong>'.
				_x( 'We\'re sorry but this application doesn\'t work properly without JavaScript enabled. Please enable it to continue.', 'Scripts: No Script Message', 'geditorial' )
			.'</strong>'
		);

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	// TODO: move to `Services\Markup`
	public static function renderAppMounter( $name, $module = FALSE, $verbose = TRUE, $message = NULL )
	{
		$html = Core\HTML::tag( 'div', [
			'id'    => sprintf( '%s-app-%s', static::BASE, $name ),
			'class' => [
				'-wrap',
				static::BASE.'-wrap',
				( $module ? ( '-'.$module ) : '' ),
				'editorial-app',
				'hide-if-no-js',
			],
		], $message ?? Plugin::moment() );

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public static function enqueueApp( $name, $dependencies = [], $version = NULL, $path = 'assets/apps', $base_path = NULL, $base_url = NULL )
	{
		$handle = strtolower( static::BASE.'-'.str_replace( '.', '-', $name ) );

		$script = sprintf( '%s%s/%s/build/main.js',        $base_url  ?? static::URL,  $path, $name );
		$style  = sprintf( '%s%s/%s/build/main.css',       $base_url  ?? static::URL,  $path, $name );
		$asset  = sprintf( '%s%s/%s/build/main.asset.php', $base_path ?? static::PATH, $path, $name );

		$config = Core\File::requireData( $asset, [
			'dependencies' => [],
			'version'      => $version ?? static::VERSION,
		] );

		wp_enqueue_style( $handle, $style, [], $config['version'], 'all' );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		wp_enqueue_script( $handle, $script, Core\Arraay::prepString( $config['dependencies'], $dependencies ), $config['version'], TRUE );
		wp_script_add_data( $handle, 'strategy', 'defer' ); // @REF: https://make.wordpress.org/core/2023/07/14/registering-scripts-with-async-and-defer-attributes-in-wordpress-6-3/
		// wp_script_add_data( $handle, 'fetchpriority', 'low' );

		return $handle;
	}

	public static function enqueueStyle( $asset, $dep = [], $version = NULL, $base = NULL, $path = 'assets/css', $media = 'all' )
	{
		$handle = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );

		wp_enqueue_style( $handle, ( $base ?? static::URL ).$path.'/'.$asset.'.css', $dep, $version ?? static::VERSION, $media );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
	}

	// NOTE: for inline scripts without dependencies
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

	public static function enqueue( $asset, $dep = [ 'jquery' ], $version = NULL, $base = NULL, $path = 'assets/js' )
	{
		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = self::const( 'SCRIPT_DEBUG' ) ? '' : '.min';

		wp_enqueue_script( $handle, ( $base ?? static::URL ).$path.'/'.$asset.$variant.'.js', $dep, $version ?? static::VERSION, TRUE );

		return $handle;
	}

	public static function enqueueVendor( $asset, $dep = [], $version = NULL, $base = NULL, $path = 'assets/js/vendor' )
	{
		return self::enqueue( $asset, $dep, $version, $base, $path );
	}

	public static function enqueuePackage( $asset, $package = NULL, $dep = [], $version = NULL, $base = NULL, $path = 'assets/packages' )
	{
		$package = $package ?? sprintf( '%s/%s', $asset, $asset );
		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = self::const( 'SCRIPT_DEBUG' ) ? '' : '.min';

		wp_enqueue_script( $handle, ( $base ?? static::URL ).$path.'/'.$package.$variant.'.js', $dep, $version ?? static::VERSION, TRUE );
		wp_script_add_data( $handle, 'strategy', 'defer' );
		wp_script_add_data( $handle, 'fetchpriority', 'low' );

		return $handle;
	}

	public static function registerPackage( $asset, $package = NULL, $dep = [], $version = NULL, $base = NULL, $path = 'assets/packages' )
	{
		$package = $package ?? sprintf( '%s/%s', $asset, $asset );
		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = self::const( 'SCRIPT_DEBUG' ) ? '' : '.min';

		wp_register_script( $handle, ( $base ?? static::URL ).$path.'/'.$package.$variant.'.js', $dep, $version ?? static::VERSION, TRUE );

		return $handle;
	}

	public static function getPrintStylesURL( $name = 'general', $base = NULL, $path = 'assets/css' )
	{
		return sprintf( '%s%s/print.%s%s.css',
			$base ?? static::URL,
			$path,
			$name,
			Core\HTML::rtl() ? '-rtl' : ''
		);
	}

	// TODO: support all kinds of check-boxes!
	public static function enqueueAdminSelectAll()
	{
		return self::enqueue( 'admin.selectall', [ 'jquery' ] );
	}

	public static function enqueueTableOverflow()
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		return $enqueued = self::inlineScript( static::BASE.'-tableoverflow',
			'jQuery("table.-table-overflow").tableoverflow();',
			[
				self::enqueueVendor( 'jquery-tableoverflow', [ 'jquery' ] ),
				'jquery',
			]
		);
	}

	// @REF: https://clipboardjs.com/
	public static function enqueueClickToClip()
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$selector = '.do-clicktoclip';
		$script   = <<<JS
(function () {
	const clipboard = new ClipboardJS('{$selector}');

	clipboard.on('success', function (e) {
		console.info(e.action + ':', e.text);
		e.clearSelection();
	});

	clipboard.on('error', function (e) {
		console.error('Action:', e.action);
		console.error('Trigger:', e.trigger);
	});

	document
		.querySelectorAll('{$selector}')
		.forEach(e => e.style.cursor = 'grab');
})();
JS;

		return $enqueued = self::inlineScript( static::BASE.'-clicktoclip', $script, [ 'clipboard' ] );
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

	// LEGACY: do not use thick-box anymore!
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
		// NOTE: since we need `gEditorial` object on this script!
		gEditorial()->enqueue_asset_config();

		return self::enqueue( 'all.colorbox', [ 'jquery', self::pkgColorBox( TRUE ) ] );
	}

	// @REF: https://www.jacklmoore.com/colorbox/
	// @REF: https://github.com/jackmoore/colorbox
	public static function pkgColorBox( $enqueue = FALSE, $ver = '1.6.4' )
	{
		$handle = 'jquery-colorbox';

		if ( $enqueue ) {

			wp_enqueue_style( $handle, static::URL.'assets/css/admin.colorbox.css', [], $ver, 'screen' );
			wp_enqueue_script( $handle, static::URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], $ver, TRUE );

		} else {

			wp_register_style( $handle, static::URL.'assets/css/admin.colorbox.css', [], $ver, 'screen' );
			wp_register_script( $handle, static::URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], $ver, TRUE );
		}

		wp_script_add_data( $handle, 'strategy', 'defer' );
		wp_script_add_data( $handle, 'fetchpriority', 'low' );

		return $handle;
	}

	/**
	 * Enqueues and or registers `SheetJS` package.
	 *
	 * @link https://cdn.sheetjs.com/
	 * @link https://git.sheetjs.com/SheetJS/sheetjs
	 *
	 * @param bool $enqueue
	 * @param string $version
	 * @return string
	 */
	public static function pkgSheetJS( $enqueue = FALSE, $version = '0.20.3' )
	{
		$handle = 'xlsx'; // NOTE: no prefix to use as dependency for apps.

		if ( $enqueue )
			wp_enqueue_script( $handle, static::URL.'assets/packages/sheetjs/xlsx.full.min.js', [], $version, TRUE );

		else
			wp_register_script( $handle, static::URL.'assets/packages/sheetjs/xlsx.full.min.js', [], $version, TRUE );

		wp_script_add_data( $handle, 'strategy', 'defer' );
		wp_script_add_data( $handle, 'fetchpriority', 'low' );

		return $handle;
	}

	public static function pkgMustache( $enqueue = FALSE, $version = '4.2.0' )
	{
		$handle = 'mustache';  // NOTE: no prefix to use as dependency for apps.

		if ( $enqueue )
			wp_enqueue_script( $handle, static::URL.'assets/packages/mustache.js/mustache.min.js', [], $version, TRUE );

		else
			wp_register_script( $handle, static::URL.'assets/packages/mustache.js/mustache.min.js', [], $version, TRUE );

		// wp_script_add_data( $handle, 'strategy', 'defer' );
		// wp_script_add_data( $handle, 'fetchpriority', 'low' );

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

		$url = WordPress\IsIt::dev()
			? 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js'
			: 'https://cdn.jsdelivr.net/npm/vue@'.$ver.'/dist/vue.min.js';

		if ( $enqueue )
			wp_enqueue_script( $handle, $url, [], $ver, TRUE );
		else
			wp_register_script( $handle, $url, [], $ver, TRUE );

		return $handle;
	}

	// @REF: https://www.jsdelivr.com/package/npm/vue
	public static function pkgVueJS3( $enqueue = FALSE, $ver = '3.4.31' )
	{
		$handle = static::BASE.'-vuejs';

		$url = WordPress\IsIt::dev()
			? 'https://unpkg.com/vue@'.$ver.'/dist/vue.global.js'
			: 'https://cdn.jsdelivr.net/npm/vue@'.$ver.'/dist/vue.global.min.js';

		if ( $enqueue )
			wp_enqueue_script( $handle, $url, [], $ver, TRUE );
		else
			wp_register_script( $handle, $url, [], $ver, TRUE );

		return $handle;
	}

	// NOTE: wp/npm version is behind!
	// @REF: https://github.com/moxiecode/plupload
	// @REF: https://www.plupload.com/
	public static function pkgPlupload( $enqueue = FALSE, $ver = '3.1.5' )
	{
		$handle = sprintf( '%s-%s', static::BASE, 'plupload' );

		return $enqueue
			? self::enqueuePackage( $handle, 'plupload/plupload.full', [], $ver )
			: self::registerPackage( $handle, 'plupload/plupload.full', [], $ver );
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

	// TODO: move to `Services\Barcodes`
	public static function markupJSBarcode( $data, $atts = [] )
	{
		$args = self::atts( [
			'format'     => 'CODE128',
			'display'    => FALSE,
			'width'      => 2,
			'height'     => 20,
			'margin'     => 5,
			'background' => '#fff',
			'textmargin' => 0,
		], $atts );

		return Core\HTML::tag( 'svg', [
			'class'                  => 'do-jsbarcode',
			'value'                  => $data,
			'jsbarcode-format'       => $args['format'],
			'jsbarcode-width'        => $args['width'],
			'jsbarcode-height'       => $args['height'],
			'jsbarcode-margin'       => $args['margin'],
			'jsbarcode-background'   => $args['background'],
			'jsbarcode-textmargin'   => $args['textmargin'],
			'jsbarcode-displayValue' => $args['display'] ? 'true' : 'false',
		], NULL );
	}

	public static function enqueueJSBarcode()
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		return $enqueued = self::inlineScript( static::BASE.'-jsbarcode',
			'JsBarcode(".do-jsbarcode").init();',
			[
				self::pkgJSBarcode( FALSE, 'all' ),
			]
		);
	}

	/**
	 * Registers or Enqueues the `JsBarcode` package.
	 * @package https://github.com/lindell/JsBarcode
	 * TODO: move to `Services\Barcodes`
	 *
	 * @param bool $enqueue
	 * @param string $barcode
	 * @param string $version
	 * @return string
	 */
	public static function pkgJSBarcode( $enqueue = FALSE, $barcode = '', $version = '3.12.1' )
	{
		switch ( strtolower( $barcode ) ) {
			case    'all':        $filepath = 'all';        break;  // All the barcodes!
			case    'code128':    $filepath = 'code128';    break;  // `CODE128` (auto and force mode)
			case    'code39' :    $filepath = 'code39';     break;  // `CODE39`
			case    'ean/upc':    $filepath = 'ean-upc';    break;  // `EAN-13`, `EAN-8`, `EAN-5`, `EAN-2`, `UPC (A)`
			case    'itf':        $filepath = 'itf';        break;  // `ITF`, `ITF-14`
			case    'msi':        $filepath = 'msi';        break;  // `MSI`, `MSI10`, `MSI11`, `MSI1010`, `MSI1110`
			case    'pharmacode': $filepath = 'pharmacode'; break;  // `Pharmacode`
			case    'codabar':    $filepath = 'codabar';    break;  // `Codabar`
			default:              $filepath = 'code128';    break;  // DEFAULT is `CODE128`
		}

		return $enqueue
			? self::enqueuePackage( 'jsbarcode', 'jsbarcode/JsBarcode.'.$filepath, [], $version )
			: self::registerPackage( 'jsbarcode', 'jsbarcode/JsBarcode.'.$filepath, [], $version );
	}

	/**
	 * Generates mark-up to use with `QRcodeSVG` script.
	 * @package https://github.com/papnkukn/qrcode-svg
	 * TODO: move to `Services\Barcodes`
	 *
	 * @param string $data
	 * @param array $atts
	 * @return string
	 */
	public static function markupQRCodeSVG( $data, $atts = [] )
	{
		$args = self::atts( [
			'padding'    => 4,
			'width'      => 256,
			'height'     => 256,
			'ecl'        => 'M',         // error correction level: L, M, H, Q
			'color'      => '#000000',
			'background' => '#ffffff',
		], $atts );

		return Core\HTML::tag( 'div', [
			'class'           => 'do-qrcodesvg',
			'data-code'       => $data,
			'data-padding'    => $args['padding'],
			'data-width'      => $args['width'],
			'data-height'     => $args['height'],
			'data-ecl'        => $args['ecl'],
			'data-color'      => $args['color'],
			'data-background' => $args['background'],
		], NULL );
	}

	// TODO: move to `Services\Barcodes`
	public static function enqueueQRCodeSVG()
	{
		static $enqueued = FALSE;

		if ( $enqueued )
			return $enqueued;

		$script = <<<JS
(function($) {
	$(".do-qrcodesvg").each(function (i,obj) {

		const qrcode = new QRCode({
			padding: $(this).data("padding") || 4,
			width: $(this).data("width") || 256,
			height: $(this).data("height") || 256,
			color: $(this).data("color") || "#000000",
			background: $(this).data("background") || "#ffffff",
			ecl: $(this).data("ecl") || "M",
			content: $(this).data("code").padEnd(220), // @REF: https://stackoverflow.com/a/73544657
			container: "svg-viewbox",
			join: true
		});

		$(this).html(qrcode.svg());
	});
})(jQuery);
JS;
		return $enqueued = self::inlineScript( static::BASE.'-qrcodesvg', $script,[ 'jquery', self::pkgQRCodeSVG() ] );
	}

	/**
	 * Registers or Enqueues the `QRCodeSVG` package.
	 * @ref https://github.com/papnkukn/qrcode-svg
	 *
	 * @param bool $enqueue
	 * @param string $version
	 * @return string
	 */
	public static function pkgQRCodeSVG( $enqueue = FALSE, $version = '1.1.0' )
	{
		return $enqueue
			? self::enqueuePackage( 'qrcodesvg', 'qrcode-svg/qrcode', [], $version )
			: self::registerPackage( 'qrcodesvg', 'qrcode-svg/qrcode', [], $version );
	}

	public static function pkgPrintThis( $enqueue = FALSE, $ver = '2.0.0' )
	{
		return $enqueue
			? self::enqueuePackage( 'printthis', 'printThis/printThis', [ 'jquery' ], $ver )
			: self::registerPackage( 'printthis', 'printThis/printThis', [ 'jquery' ], $ver );
	}

	// @REF: https://printjs.crabbly.com/
	// @REF: https://github.com/crabbly/Print.js
	public static function pkgPrintJS( $enqueue = FALSE, $ver = '1.6.0' )
	{
		$handle = 'printjs';

		if ( $enqueue ) {

			wp_enqueue_style( $handle, static::URL.'assets/packages/printjs/print.min.css', [], $ver, 'screen' );
			wp_enqueue_script( $handle, static::URL.'assets/packages/printjs/print.min.js', [], $ver, TRUE );

		} else {

			wp_register_style( $handle, static::URL.'assets/packages/printjs/print.min.css', [], $ver, 'screen' );
			wp_register_script( $handle, static::URL.'assets/packages/printjs/print.min.js', [], $ver, TRUE );
		}

		wp_script_add_data( $handle, 'strategy', 'defer' );
		wp_script_add_data( $handle, 'fetchpriority', 'low' );

		return $handle;
	}

	/**
	 * Provides `Dropzone` package for register or enqueue.
	 *
	 * @homepage https://www.dropzone.dev/
	 * @github https://github.com/dropzone/dropzone
	 *
	 * @param bool $enqueue
	 * @param string $version
	 * @return string
	 */
	public static function pkgDropzone( $enqueue = FALSE, $version = '6.0.0-beta.2' )
	{
		$handle = 'dropzone';

		if ( $enqueue ) {

			// wp_enqueue_style( $handle, static::URL.'assets/packages/dropzone/basic.css', [], $version, 'screen' );
			wp_enqueue_style( $handle, static::URL.'assets/packages/dropzone/dropzone.css', [], $version, 'screen' );
			wp_enqueue_script( $handle, static::URL.'assets/packages/dropzone/dropzone-min.js', [], $version, TRUE );

		} else {

			// wp_register_style( $handle, static::URL.'assets/packages/dropzone/basic.css', [], $version, 'screen' );
			wp_register_style( $handle, static::URL.'assets/packages/dropzone/dropzone.css', [], $version, 'screen' );
			wp_register_script( $handle, static::URL.'assets/packages/dropzone/dropzone-min.js', [], $version, TRUE );
		}

		// wp_script_add_data( $handle, 'strategy', 'defer' );
		// wp_script_add_data( $handle, 'fetchpriority', 'low' );

		return $handle;
	}

	public static function linkDropzone( $ver = '6.0.0-beta.2' )
	{
		// Core\HTML::linkStyleSheet( static::URL.'assets/packages/dropzone/basic.css', $ver, 'screen' );
		Core\HTML::linkStyleSheet( static::URL.'assets/packages/dropzone/dropzone.css', $ver, 'screen' );
		printf( '<script src="%s"></script>', add_query_arg( 'ver', $ver, static::URL.'assets/packages/dropzone/dropzone-min.js' ) );
	}

	// @REF: https://github.com/axenox/onscan.js
	// @REF: https://a.kabachnik.info/onscan-js.html
	public static function pkgOnScanJS( $enqueue = FALSE, $ver = '1.5.2' )
	{
		return $enqueue
			? self::enqueuePackage( 'onscanjs', 'onscan.js/onscan', [], $ver )
			: self::registerPackage( 'onscanjs', 'onscan.js/onscan', [], $ver );
	}

	// @REF: https://github.com/mbraak/jqTree
	// @REF: http://mbraak.github.io/jqTree/
	public static function pkgJqTree( $enqueue = FALSE, $ver = '1.8.10' )
	{
		return $enqueue
			? self::enqueuePackage( 'jqtree', 'jqtree/tree.jquery', [ 'jquery' ], $ver )
			: self::registerPackage( 'jqtree', 'jqtree/tree.jquery', [ 'jquery' ], $ver );
	}

	// @REF: https://igorescobar.github.io/jQuery-Mask-Plugin/
	// @REF: https://github.com/igorescobar/jQuery-Mask-Plugin
	public static function pkgJqueryMask( $enqueue = FALSE, $ver = '1.14.16' )
	{
		return $enqueue
			? self::enqueuePackage( 'jquery-mask', 'jquery-mask/jquery.mask', [ 'jquery' ], $ver )
			: self::registerPackage( 'jquery-mask', 'jquery-mask/jquery.mask', [ 'jquery' ], $ver );
	}

	// @REF: https://github.com/fgnass/spin.js
	// @REF: https://spin.js.org/
	public static function pkgSpinJS( $enqueue = FALSE, $ver = '4.1.2' )
	{
		return $enqueue
			? self::enqueuePackage( 'spinjs', 'spin.js/spin.umd', [], $ver )
			: self::registerPackage( 'spinjs', 'spin.js/spin.umd', [], $ver );
	}

	// @REF: https://github.com/chartjs/Chart.js
	// @REF: https://www.chartjs.org/
	public static function pkgChartJS( $enqueue = FALSE, $ver = '4.5.1' )
	{
		return $enqueue
			? self::enqueuePackage( 'chartjs', 'chart.js/chart.umd', [], $ver )
			: self::registerPackage( 'chartjs', 'chart.js/chart.umd', [], $ver );
	}

	// TODO: move to `Services\Markup`
	public static function markupChartJS( $name, $module = FALSE )
	{
		return Core\HTML::wrap( Core\HTML::tag( 'canvas', [
			'id' => sprintf( '%s-chart-%s', static::BASE, $name ),
		] ), [
			static::BASE.'-wrap',
			( $module ? ( '-'.$module ) : '' ),
			'editorial-chart',
			'hide-if-no-js',
		] );
	}

	public static function enqueueChartJS_Bar( $name, $data, $atts = [] )
	{
		$args = self::atts( [
			'type'   => 'bar',
			'label'  => '',
			'labels' => array_keys( $data ),
			'values' => array_values( $data ),

			'rtl'    => Core\L10n::rtl(),
			'locale' => Core\L10n::getISO639(),   // 'fa-IR',

			// 'color'      => '#000000',
			// 'background' => '#ffffff',
		], $atts );

		$rtl      = $args['rtl'] ? 'true' : 'false';
		$labels   = Core\HTML::encode( $args['labels'] );
		$values   = Core\HTML::encode( $args['values'] );
		$selector = sprintf( '%s-chart-%s', static::BASE, $name );

		$script   = <<<JS
(function () {
	const ctx = document.getElementById('{$selector}');

	Chart.defaults.font.family = getComputedStyle(document.body).getPropertyValue('font-family');

	const myChart = new Chart(ctx, {
		type: '{$args['type']}',
		data: {
			labels: {$labels},
			datasets: [{
				label: '{$args['label']}',
				data: {$values},
				borderWidth: 1
			}]
		},
		options: {
			locale: '{$args['locale']}',
			layout: {
            	padding: 0
        	},
			plugins: {
				legend: {
					rtl: {$rtl},
					// textDirection: 'rtl',
					labels: {
						font: {
							// size: 9
						}
					}
				}
        	},
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
})();
JS;

		return self::inlineScript( sprintf( '%s-chartjs-%s', static::BASE, $name ), $script, [ self::pkgChartJS( TRUE ) ] );
	}

	// @REF: https://github.com/select2/select2/
	// @REF: https://select2.org/
	public static function pkgSelect2( $enqueue = FALSE, $ver = '4.1.0-rc.0' )
	{
		$handle    = 'select2';
		$dir       = Core\HTML::rtl() ? '-rtl' : '';
		$wooselect = WordPress\WooCommerce::isActive();

		if ( $enqueue ) {

			wp_enqueue_script( $handle, static::URL.'assets/packages/select2/select2.min.js', [ 'jquery' ], $ver, TRUE );

			if ( ! $wooselect )
				wp_enqueue_style( $handle, static::URL.'assets/css/admin.select2'.$dir.'.css', [], $ver, 'screen' );

		} else {

			wp_register_script( $handle, static::URL.'assets/packages/select2/select2.min.js', [ 'jquery' ], $ver, TRUE );

			if ( ! $wooselect )
				wp_register_style( $handle, static::URL.'assets/css/admin.select2'.$dir.'.css', [], $ver, 'screen' );
		}

		return $handle;
	}

	public static function linkBootstrap5( $ver = '5.3.8', $screen = 'all' )
	{
		$var = self::const( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$dir = Core\HTML::rtl() ? '.rtl' : '';

		return Core\HTML::linkStyleSheet(
			GEDITORIAL_URL.'assets/packages/bootstrap/bootstrap'.$dir.$var.'.css',
			$ver,
			$screen
		);
	}

	public static function linkVazirMatn( $ver = '33.0.3', $screen = 'all' )
	{
		return Core\HTML::linkStyleSheet(
			GEDITORIAL_URL.'assets/packages/vazirmatn/Vazirmatn-font-face.css',
			$ver,
			$screen
		);
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( static::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.'.static::BASE.'", '.Core\HTML::encode( $strings ).');'."\n" : '';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$props = array_merge( $args, [
			'_base' => static::BASE,
			'_url'  => sanitize_url( admin_url( 'admin-ajax.php' ) ),

			'_restBase'  => Core\URL::untrail( rest_url() ),
			'_restNonce' => wp_create_nonce( 'wp_rest' ),
		] );

	?><script>
	window.<?php echo $object; ?> = <?php echo $object; ?> = <?php echo Core\HTML::encode( $props ); ?>;
	<?php if ( WordPress\IsIt::dev() ) {
		echo 'console.log("'.$object.'", '.$object.');'."\n";
		echo "\t".'jQuery(document).on("gEditorialReady", function(e, module, app){console.log("'.$object.': "+module, app);});'."\n";
		echo "\t".'jQuery(document).on("gEditorial:Module:Loaded", function(e, module, app){console.log("'.$object.': "+module, app);});'."\n";
	} ?>
</script><?php
	}
}

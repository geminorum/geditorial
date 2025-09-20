<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

// FIXME: Already included: https://github.com/tecnickcom/tc-lib-barcode

class Barcodes extends gEditorial\Service
{

	/**
	 * You can invoke Binary Eye with a web URI intent from anything
	 * that can open URIs. There are two options:
	 *
	 * - `binaryeye://scan`
	 * - `http(s)://markusfisch.de/BinaryEye`
	 *
	 * If you want to get the scanned contents, you can add a `ret` query
	 * argument with a (URL encoded) URI template. For example:
	 *
	 * `http://markusfisch.de/BinaryEye?ret=http%3A%2F%2Fexample.com%2F%3Fresult%3D{RESULT}`
	 *
	 * Supported symbols are:
	 * `RESULT`: scanned content
	 * `RESULT_BYTES`: raw result as a hex string
	 * `FORMAT`: bar-code format
	 *
	 * @source https://github.com/markusfisch/BinaryEye
	 */
	public static function binaryEyeLink( $query_var = 's', $url = NULL )
	{
		$ret = add_query_arg( [
			$query_var => '{RESULT}',
			'barcode'  => '{FORMAT}',
		], $url ?? Core\URL::current() );

		return sprintf( 'binaryeye://scan?ret=%s', rawurlencode( $ret ) );
	}

	public static function binaryEyeHeaderButton()
	{
		HeaderButtons::register( 'barcodescanner', [
			'icon'  => [ 'misc-512', 'openlibrary-barcodescanner' ],
			'text'  => '',
			'title' => _x( 'Scan to Search using BinaryEye', 'Service: Barcodes: Title Attr', 'geditorial-admin' ),
			'link'  => self::binaryEyeLink(),
			'class' => [
				'-only-icon',
				'-mobile-only-inline-block',
			],
			'hide_in_search' => FALSE,
			'priority'       => 9999,
		] );
	}

	// @REF: https://github.com/hbgl/php-code-128-encoder
	// NOTE: depends on `tecnickcom/tc-lib-barcode`
	public static function encode( $data, $type )
	{
		switch ( $type ) {
			case 'code128': return \Hbgl\Barcode\Code128Encoder\Code128Encoder::encode( $data );
		}

		return $data;
	}

	// @REF: https://github.com/metafloor/bwip-js/wiki/Online-Barcode-API
	// @SEE: https://github.com/metafloor/bwip-js/wiki/BWIPP-Barcode-Types
	public static function getBWIPPjs( $type, $text, $extra = [], $tag = FALSE, $cache = TRUE, $sub = 'bwipjs', $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR )
			$cache = FALSE;

		$direct = add_query_arg( array_merge( [
			'bcid'        => $type, // must follow immediately after the question mark
			// 'scaleX'      => '2',
			// 'scale'       => '2',
			'text'        => $text,
			'includetext' => '', // to display the code
		], $extra ), 'https://bwipjs-api.metafloor.com' );

		if ( ! $cache )
			return $tag ? Core\HTML::img( $direct, [ '-barcode', sprintf( '-%s', $type ) ] ) : $direct;

		$file = sprintf( '%s.png', md5( maybe_serialize( $direct ) ) );
		$path = FileCache::getDIR( $sub, $base ).'/'.$file;
		$url  = FileCache::getURL( $sub, $base ).'/'.$file;

		if ( ! file_exists( $path.'/'.$file ) ) {

			$bypass = $tag ? Core\HTML::img( $direct, [ '-barcode', sprintf( '-%s', $type ), '-direct' ] ) : $direct;

			if ( ! $image = Core\HTTP::getContents( $direct ) )
				return $bypass;

			if ( FALSE === file_put_contents( $path.'/'.$file, $image ) )
				return $bypass;
		}

		return $tag ? Core\HTML::img( $url, [ '-barcode', sprintf( '-%s', $type ), '-cached' ] ) : $url;
	}
}

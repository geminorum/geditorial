<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

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

	// @REF: https://bwip-js.metafloor.com/demo/demo.html
	const TYPES_WITH_TEXT = [
		'isbn',  // NOTE: must be dashed
		'ean13',
		'code39',
		'code39ext',
		'code128',
	];

	// @REF: https://github.com/metafloor/bwip-js/wiki/Online-Barcode-API
	// @SEE: https://github.com/metafloor/bwip-js/wiki/BWIPP-Barcode-Types
	// https://github.com/metafloor/bwip-js/wiki/BWIPP-border-vs-bwipjs-padding
	public static function getByBWIPP( $type, $text, $extra = [], $tag = FALSE, $cache = TRUE, $sub = 'bwipjs', $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR )
			$cache = FALSE;

		$direct = add_query_arg( array_merge( [
			'bcid'        => $type, // Must follow immediately after the question mark.
			'scale'       => '2',
			'text'        => $text,
			'includetext' => in_array( $type, static::TYPES_WITH_TEXT, TRUE ) ? '' : FALSE,
		], $extra ), 'https://bwipjs-api.metafloor.com' );

		if ( ! $cache )
			return $tag ? Core\HTML::img( $direct, [ '-barcode', sprintf( '-%s', $type ) ] ) : $direct;

		$file = sprintf( '%s.png', md5( maybe_serialize( $direct ) ) );
		$url  = FileCache::getURL( $sub, $base ).'/'.$file;
		$path = FileCache::getDIR( $sub, $base );

		if ( ! Core\File::exists( $file, $path ) ) {

			$bypass = $tag ? Core\HTML::img( $direct, [ '-barcode', sprintf( '-%s', $type ), '-direct' ] ) : $direct;

			if ( ! $image = Core\HTTP::getContents( $direct ) )
				return $bypass;

			if ( FALSE === Core\File::putContents( $file, $image, $path ) )
				return $bypass;
		}

		return $tag ? Core\HTML::img( $url, [ '-barcode', sprintf( '-%s', $type ), '-cached' ] ) : $url;
	}

	public static function getQRCode( $data, $type = 'text', $size = 300, $tag = FALSE, $cache = TRUE, $sub = 'qrcodes', $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR )
			$cache = FALSE;

		switch ( $type ) {
			case 'url'    : $prepared = Core\DataCode::prepDataURL( $data ); break;
			case 'email'  : $prepared = Core\DataCode::prepDataEmail( is_array( $data ) ? $data : [ 'email' => $data ] ); break;
			case 'phone'  : $prepared = Core\DataCode::prepDataPhone( $data ); break;
			case 'sms'    : $prepared = Core\DataCode::prepDataSMS( is_array( $data ) ? $data : [ 'mobile' => $data ] ); break;
			case 'contact': $prepared = Core\DataCode::prepDataContact( is_array( $data ) ? $data : [ 'name' => $data ] ); break;
			default       : $prepared = Core\Text::trim( $data ); break;
		}

		if ( ! $cache )
			return Core\DataCode::getQRCode( $prepared, [ 'tag' => $tag, 'size' => $size ] );

		$file = sprintf( '%s-%s.svg', md5( maybe_serialize( $prepared ) ), $size );
		$url  = Core\URL::trail( FileCache::getURL( $sub, $base ) ).$file;
		$path = FileCache::getDIR( $sub, $base );

		if ( ! Core\File::exists( $file, $path ) ) {

			if ( ! Core\DataCode::cacheQRCode( Core\File::join( $path, $file ), $prepared, [ 'size' => $size ] ) )
				return $tag ? '' : FALSE;
		}

		return $tag ? Core\HTML::tag( 'img', [
			'src'      => $url,
			'width'    => $size,
			'height'   => $size,
			'alt'      => '',
			'decoding' => 'async',
			'loading'  => 'lazy',
		] ) : $url;
	}
}

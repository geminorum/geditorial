<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Barcode extends WordPress\Main
{

	const BASE = 'geditorial';

	// QRCode
	// php 7.1: https://github.com/Bacon/BaconQrCode


	// php 7.3: https://github.com/endroid/qr-code
	//
	// create hash of args
	// store in temp folder in updaloads
	// serce cached images

	// ---------------------------------------------------------
	// ---------------------------------------------------------
	// https://github.com/tecnickcom/tc-lib-barcode

	// // instantiate the barcode class
	// $barcode = new \Com\Tecnick\Barcode\Barcode();
	//
	// // generate a barcode
	// $bobj = $barcode->getBarcodeObj(
	//     'QRCODE,H',                     // barcode type and additional comma-separated parameters
	//     'https://tecnick.com',          // data string to encode
	//     -4,                             // bar width (use absolute or negative value as multiplication factor)
	//     -4,                             // bar height (use absolute or negative value as multiplication factor)
	//     'black',                        // foreground color
	//     array(-2, -2, -2, -2)           // padding (use absolute or negative values as multiplication factors)
	//     )->setBackgroundColor('white'); // background color
	//
	// // output the barcode as HTML div (see other output formats in the documentation and examples)
	// $bobj->getHtmlDiv();

	// ---------------------------------------------------------


	// QRCODE decode
	// https://github.com/khanamiryan/php-qrcode-detector-decoder
	// $qrcode = new \QrReader('path/to_image');
	// $text = $qrcode->text(); //return decoded text from QR Code


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
		$path = self::getCacheDIR( $sub, $base ).'/'.$file;
		$url  = self::getCacheURL( $sub, $base ).'/'.$file;

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

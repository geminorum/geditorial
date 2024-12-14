<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

class Barcodes extends WordPress\Main
{
	const BASE = 'geditorial';

	// FIXME: Already included: https://github.com/tecnickcom/tc-lib-barcode

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
		$path = Helper::getCacheDIR( $sub, $base ).'/'.$file;
		$url  = Helper::getCacheURL( $sub, $base ).'/'.$file;

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

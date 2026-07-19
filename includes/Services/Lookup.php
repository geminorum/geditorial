<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Lookup extends gEditorial\Service
{
	// `https://db-ip.com/xxx.xxx.xx.xxx`
	// TODO: customize for this plugin
	// OLD: `Info::lookupIP()`
	public static function htmlIP( string $ip ): string
	{
		if ( function_exists( 'gnetwork_ip_lookup' ) )
			return gnetwork_ip_lookup( $ip );

		return $ip;
	}

	// OLD: `Info::lookupLatLng()`
	public static function htmlLatLng( string $latlng, array $extra = [] ): string
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::linkLatLng( $latlng ),
			'class'  => Core\HTML::attrClass( '-latlng-lookup', $extra ),
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\LatLng::prep( $latlng, TRUE ) );
	}

	// @SEE: https://www.latlong.net/countries.html
	// @REF: https://stackoverflow.com/a/52943975
	// `maps.google.com/?q=35.6928,50.82565`
	// OLD: `Info::lookupURLforLatLng()`
	public static function linkLatLng( string $latlng ): string
	{
		if ( ! $latlng )
			return '#';

		if ( ! is_array( $latlng ) )
			$latlng = Core\LatLng::extract( $latlng );

		// $url = add_query_arg( [
		// 	'api'   => '1',
		// 	'query' => sprintf( '%s,%s', $latlng[0], $latlng[1] ),
		// ], 'https://www.google.com/maps/search/' );

		$url = sprintf( 'geo:%s,%s', $latlng[0], $latlng[1] );

		return apply_filters( self::und( static::BASE, 'lookup', 'latlng' ),
			$url,
			$latlng
		);
	}

	// TODO: customize for this plugin
	// OLD: `Info::lookupCountry()`
	public static function htmlCountry( string $code ): string
	{
		if ( function_exists( 'gnetwork_country_lookup' ) )
			return gnetwork_country_lookup( $code );

		return $code;
	}

	// OLD: `Info::lookupISBN()`
	public static function htmlISBN( string $isbn ): string
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::linkISBN( $isbn ),
			'class'  => '-isbn-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\ISBN::prep( $isbn, TRUE ) );
	}

	// OLD: `Info::lookupURLforISBN()`
	public static function linkISBN( string $isbn ): string
	{
		$url = add_query_arg( [
			'vid' => urlencode( 'isbn'.Core\ISBN::sanitize( $isbn ) ),
		], 'https://books.google.com/books' );

		return apply_filters( self::und( static::BASE, 'lookup', 'isbn' ),
			$url,
			$isbn
		);
	}

	// OLD: `Info::lookupVIN()`
	public static function htmlVIN( string $vin ): string
	{
		return Core\HTML::tag( 'a', [
			'href'   => self::linkVin( $vin ),
			'class'  => '-vin-lookup',
			'target' => '_blank',
			'rel'    => 'noreferrer',
		], Core\Validation::sanitizeVIN( $vin ) );
	}

	// `https://en.vindecoder.pl/en/decode/JH2RC3605MM101581`
	// @SEE: https://vpic.nhtsa.dot.gov/decoder/
	// OLD: `Info::lookupURLforVIN()`
	public static function linkVin( string $vin ): string
	{
		$url = sprintf(
			'https://en.vindecoder.pl/en/decode/%s',
			Core\Validation::sanitizeVIN( $vin )
		);

		return apply_filters( self::und( static::BASE, 'lookup', 'vin' ),
			$url,
			$vin
		);
	}
}

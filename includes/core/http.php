<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class HTTP extends Base
{

	// if this is a POST request
	public static function isPOST()
	{
		return (bool) ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}

	// if this is a GET request
	public static function isGET()
	{
		return (bool) ( 'GET' === strtoupper( $_SERVER['REQUEST_METHOD'] ) );
	}

	// http://code.tutsplus.com/tutorials/a-look-at-the-wordpress-http-api-a-brief-survey-of-wp_remote_get--wp-32065
	// http://wordpress.stackexchange.com/a/114922
	public static function getJSON( $url, $atts = array(), $assoc = FALSE )
	{
		$args = self::recursiveParseArgs( $atts, array(
			'timeout' => 15,
		) );

		$response = wp_remote_get( $url, $args );

		if ( ! self::isError( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return json_decode( wp_remote_retrieve_body( $response ), $assoc );
		}

		return FALSE;
	}

	public static function getHTML( $url, $atts = array() )
	{
		$args = self::recursiveParseArgs( $atts, array(
			'timeout' => 15,
		) );

		$response = wp_remote_get( $url, $args );

		if ( ! self::isError( $response )
			&& 200 == wp_remote_retrieve_response_code( $response ) ) {
				return wp_remote_retrieve_body( $response );
		}

		return FALSE;
	}

	public static function getContents( $url )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_URL, $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, TRUE );

		$contents = curl_exec( $handle );

		curl_close( $handle );

		if ( ! $contents )
			return FALSE;

		return $contents;
	}

	// @SOURCE: `wp_get_raw_referer()`
	public static function referer()
	{
		if ( ! empty( $_REQUEST['_wp_http_referer'] ) )
			return self::normalizeIP( $_REQUEST['_wp_http_referer'] );

		if ( ! empty( $_SERVER['HTTP_REFERER'] ) )
			return self::normalizeIP( $_SERVER['HTTP_REFERER'] );

		return FALSE;
	}

	// @REF: `WP_Community_Events::get_unsafe_client_ip()`
	public static function IP( $pad = FALSE )
	{
		$ip = '';

		if ( getenv( 'HTTP_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_CLIENT_IP' );

		else if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_X_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_X_FORWARDED' ) )
			$ip = getenv( 'HTTP_X_FORWARDED' );

		else if ( getenv( 'HTTP_X_CLUSTER_CLIENT_IP' ) )
			$ip = getenv( 'HTTP_X_CLUSTER_CLIENT_IP' );

		else if ( getenv( 'HTTP_FORWARDED_FOR' ) )
			$ip = getenv( 'HTTP_FORWARDED_FOR' );

		else if ( getenv( 'HTTP_FORWARDED' ) )
			$ip = getenv( 'HTTP_FORWARDED' );

		else
			$ip = getenv( 'REMOTE_ADDR' );

		// HTTP_X_FORWARDED_FOR can contain a chain of comma-separated addresses
		$ip = explode( ',', $ip );
		$ip = trim( $ip[0] );

		$ip = self::normalizeIP( $ip );

		if ( $pad )
			return str_pad( $ip, 15, ' ', STR_PAD_LEFT );

		return $ip;
	}

	public static function normalizeIP( $ip )
	{
		return trim( preg_replace( '/[^0-9., ]/', '', stripslashes( $ip ) ) );
	}

	public static function headers( $array )
	{
		foreach ( $array as $h => $k )
			@header( "{$h}: {$k}", TRUE );
	}

	public static function headerRetryInMinutes( $minutes = '30' )
	{
		@header( "Retry-After: ".( absint( $minutes ) * 60 ) );
	}

	public static function headerContentUTF8()
	{
		@header( "Content-Type: text/html; charset=utf-8" );
	}

	// @REF: https://gist.github.com/eric1234/37fd102798d99d94b0dcebde6bb29ef3
	//
	// Abstracts the idiocy of the CURL API for something simpler. Assumes we are
	// downloading data (so a GET request) and we need no special request headers.
	// Returns an IO stream which will be the data requested. The headers of the
	// response will be stored in the $headers param reference.
	//
	// If the request fails for some reason FALSE is returned with the $err_msg
	// param containing more info.
	public static function download( $url, &$headers = array(), &$err_msg )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		$in_out  = curl_init( $url );
		$stream = fopen( 'php://temp', 'w+' );

		curl_setopt_array( $in_out, array(
			CURLOPT_FAILONERROR    => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_HEADER         => TRUE,
			CURLOPT_FILE           => $stream,
		) );

		if ( FALSE === curl_exec( $in_out ) ) {
			$err_msg << curl_error( $in_out );
			return FALSE;
		}

		curl_close( $in_out );
		rewind( $stream );

		$line = trim( fgets( $stream ) );

		if ( preg_match( '/^HTTP\/([^ ]+) (.*)/i', $line, $matches ) ) {
			$headers['HTTP_VERSION'] = $matches[1];
			$headers['STATUS']       = $matches[2];
		}

		while ( $line = fgets( $stream ) ) {
			if ( preg_match( '/^\s+$/', $line ) )
				break;
			list( $key, $value ) = preg_split( '/\s*:\s*/', $line, 2 );
			$headers[strtoupper( $key )] = trim( $value );
		}

		return $stream;
	}

	// @REF: http://arguments.callee.info/2010/02/21/multiple-curl-requests-with-php/
	// @REF: http://stackoverflow.com/a/9950468/4864081
	public static function checkURLs( $urls = array() )
	{
		if ( ! extension_loaded( 'curl' ) )
			return FALSE;

		if ( ! count( $urls ) )
			return array();

		$ch = $results = array();

		$mh = curl_multi_init();

		for ( $i = 0; $i < count( $urls ); $i++ ) {

			$ch[$i] = curl_init();

			curl_setopt( $ch[$i], CURLOPT_URL, $urls[$i] );
			curl_setopt( $ch[$i], CURLOPT_RETURNTRANSFER, TRUE );
			// curl_setopt( $ch[$i], CURLOPT_CUSTOMREQUEST, 'HEAD' );
			curl_setopt( $ch[$i], CURLOPT_HEADER, FALSE );
			curl_setopt( $ch[$i], CURLOPT_NOBODY, TRUE );
			curl_setopt( $ch[$i], CURLOPT_SSL_VERIFYPEER, FALSE );
			curl_setopt( $ch[$i], CURLOPT_FOLLOWLOCATION, FALSE );
			curl_setopt( $ch[$i], CURLOPT_FAILONERROR, TRUE );

			curl_multi_add_handle( $mh, $ch[$i] );
		}

		do { // execute all queries simultaneously, and continue when all are complete

			curl_multi_exec( $mh, $running );

		} while ( $running );

		for ( $i = 0; $i < count( $urls ); $i++ ) {
			$results[$urls[$i]] = curl_getinfo( $ch[$i], CURLINFO_HTTP_CODE );
			curl_multi_remove_handle( $mh, $ch[$i] );
		}

		curl_multi_close( $mh );

		return $results;
	}
}

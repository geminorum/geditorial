<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class URL extends Base
{

	// @SOURCE: http://stackoverflow.com/a/8891890/4864081
	public static function current( $trailingslashit = FALSE, $forwarded_host = FALSE )
	{
		$ssl = ( ! empty( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] );

		$protocol = strtolower( $_SERVER['SERVER_PROTOCOL'] );
		$protocol = substr( $protocol, 0, strpos( $protocol, '/' ) ).( ( $ssl ) ? 's' : '' );

		$port = $_SERVER['SERVER_PORT'];
		$port = ( ( ! $ssl && '80' == $port ) || ( $ssl && '443' == $port ) ) ? '' : ':'.$port;

		$host = ( $forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : NULL );
		$host = isset( $host ) ? $host : $_SERVER['SERVER_NAME'].$port;

		$current = $protocol.'://'.$host.$_SERVER['REQUEST_URI'];
		return $trailingslashit ? self::trail( $current ) : $current;
	}

	// like twitter links
	public static function prepTitle( $url, $slash = FALSE )
	{
		$title = preg_replace( '|^http(s)?://(www\.)?|i', '', $url );
		$title = self::untrail( $title );
		return $slash ? str_ireplace( array( '/', '\/' ), '-', $title ) : $title;
	}

	public static function prepTitleQuery( $string )
	{
		return str_ireplace( array( '_', '-' ), ' ', urldecode( $string ) );
	}

	// @SOURCE: `add_query_arg()`
	public static function parse( $url )
	{
		if ( $frag = strstr( $url, '#' ) )
			$url = substr( $url, 0, -strlen( $frag ) );
		else
			$frag = '';

		if ( 0 === stripos( $url, 'http://' ) ) {
			$pro = 'http';
			$url = substr( $url, 7 );
		} else if ( 0 === stripos( $url, 'https://' ) ) {
			$pro = 'https';
			$url = substr( $url, 8 );
		} else {
			$pro = '';
		}

		if ( FALSE !== strpos( $url, '?' ) ) {
			list( $base, $query ) = explode( '?', $url, 2 );
		} else if ( $pro || FALSE === strpos( $url, '=' ) ) {
			$base  = $url;
			$query = '';
		} else {
			$base  = '';
			$query = $url;
		}

		parse_str( $query, $args );

		return array(
			'base'     => $base,
			'query'    => $args,
			'protocol' => $pro,
			'fragment' => $frag,
		);
	}

	// will remove trailing forward and backslashes if it exists already before adding
	// a trailing forward slash. This prevents double slashing a string or path.
	// @SOURCE: `trailingslashit()`
	public static function trail( $path )
	{
		return self::untrail( $path ).'/';
	}

	// removes trailing forward slashes and backslashes if they exist.
	// @SOURCE: `untrailingslashit()`
	public static function untrail( $path )
	{
		return rtrim( $path, '/\\' );
	}

	// FIXME: strip all the path
	public static function domain( $path )
	{
		if ( FALSE === strpos( $path, '.' ) )
			return $path;

		$parts = explode( '.', $path );
		return strtolower( $parts[0] );
	}

	// @SOURCE: `wp_make_link_relative()`
	public static function relative( $url )
	{
		return preg_replace( '|^(https?:)?//[^/]+(/?.*)|i', '$2', $url );
	}

	public static function fromPath( $path, $base = ABSPATH )
	{
		return str_ireplace(
			wp_normalize_path( $base ),
			self::trail( get_option( 'siteurl' ) ),
			wp_normalize_path( $path )
		);
	}

	// check whether the given URL belongs to this site
	public static function isLocal( $url, $domain = NULL )
	{
		return parse_url( $url, PHP_URL_HOST ) === parse_url( ( is_null( $domain ) ? home_url() : $domain ), PHP_URL_HOST );
	}

	// check whether the given URL is relative or not
	public static function isRelative( $url )
	{
		$parsed = parse_url( $url );
		return empty( $parsed['host'] ) && empty( $parsed['scheme'] );
	}

	public static function checkExternals( $urls = array(), $site = NULL )
	{
		if ( ! count( $urls ) )
			return array();

		if ( is_null( $site ) )
			$site = get_option( 'siteurl' );

		$urls    = array_values( array_unique( $urls ) );
		$length  = strlen( $site );
		$results = array();

		foreach ( $urls as $url )
			$results[$url] = $site !== substr( $url, 0, $length );

		return $results;
	}
}

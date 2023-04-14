<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Info extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function lookupIP( $ip )
	{
		if ( function_exists( 'gnetwork_ip_lookup' ) )
			return gnetwork_ip_lookup( $ip );

		return $ip;
	}

	// https://books.google.com/books?vid=isbn9789646799950
	// https://www.google.com/search?tbm=bks&q=9786005334395
	public static function lookupISBN( $isbn )
	{
		// $url = add_query_arg( [
		// 	// 'q' => 'ISBN:'.urlencode( ISBN::sanitize( $isbn ) ),
		// 	'q' => urlencode( ISBN::sanitize( $isbn ) ),
		// ], 'https://www.google.com/search' );

		$url = add_query_arg( [
			'vid' => urlencode( 'isbn'.Core\ISBN::sanitize( $isbn ) ),
		], 'https://books.google.com/books' );

		return apply_filters( static::BASE.'_lookup_isbn', $url, $isbn );
	}

	public static function renderNoticeP2P()
	{
		if ( defined( 'P2P_PLUGIN_VERSION' ) )
			return;

		/* translators: %1$s: plugin url, %2$s: plugin url */
		Core\HTML::desc( sprintf( _x( 'Please consider installing <a href="%1$s" target="_blank">Posts to Posts</a> or <a href="%2$s" target="_blank">Objects to Objects</a>.', 'Info: P2P', 'geditorial' ),
			'https://github.com/scribu/wp-posts-to-posts/', 'https://github.com/voceconnect/objects-to-objects' ) );
	}

	// OLD: `infoP2P()`
	public static function renderConnectedP2P()
	{
		return sprintf(
			/* translators: %s: code placeholder */
			_x( 'Connected via %s', 'Info: P2P', 'geditorial' ),
			'<code>P2P</code>'
		);
	}
}

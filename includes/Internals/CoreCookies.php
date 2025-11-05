<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreCookies
{
	/**
	 * Retrieves the cookie name for the module.
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function corecookies_name( $suffix = '' )
	{
		return $this->classs( $this->site, $suffix );
	}

	/**
	 * Sends a cookie with given data.
	 * @OLD: `set_cookie()`
	 *
	 * @param array $data
	 * @param bool $append
	 * @param string $expire
	 * @param string $suffix
	 * @return bool
	 */
	public function corecookies_set( $data, $append = TRUE, $expire = '+ 365 day', $suffix = '' )
	{
		$name = $this->corecookies_name( $suffix );

		if ( $append ) {

			$old = isset( $_COOKIE[$name] ) ? json_decode( self::unslash( $_COOKIE[$name] ) ) : [];
			$new = wp_json_encode( self::recursiveParseArgs( $data, $old ) );

		} else {

			$new = wp_json_encode( $data );
		}

		return setcookie(
			$name,
			$new,
			strtotime( $expire ),
			COOKIEPATH,
			COOKIE_DOMAIN,
			FALSE
		);
	}

	/**
	 * Retrieves the cookie.
	 * @OLD: `get_cookie()`
	 *
	 * @param string $suffix
	 * @return array
	 */
	public function corecookies_get( $suffix = '' )
	{
		$name = $this->corecookies_name( $suffix );

		return isset( $_COOKIE[$name] )
			? json_decode( self::unslash( $_COOKIE[$name] ), TRUE )
			: [];
	}

	/**
	 * Clears the cookie.
	 * @OLD: `delete_cookie()`
	 *
	 * @param string $suffix
	 * @return bool
	 */
	public function corecookies_clear( $suffix = '' )
	{
		return setcookie(
			$this->corecookies_name( $suffix ),
			'',
			time() - 3600,
			COOKIEPATH,
			COOKIE_DOMAIN,
			FALSE
		);
	}
}

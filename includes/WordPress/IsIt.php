<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class IsIt extends Core\Base
{

	/**
	 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.).
	 *
	 * NOTE: Whilst caching issues are mentioned in more information for
	 * this function it cannot be re-stated enough that any page caching,
	 * which does not split itself into mobile and non-mobile buckets,
	 * will break this function. If your page caching is global and a
	 * desktop device triggers a refresh, the return of this function
	 * will always be FALSE until the next refresh. Likewise if a mobile
	 * device triggers the refresh, the return will always be TRUE.
	 * IF you expect the result of this function to change on a per user
	 * basis, ensure that you have considered how caching will affect your code.
	 * @source https://developer.wordpress.org/reference/functions/wp_is_mobile/
	 *
	 * @return bool
	 */
	public static function mobile()
	{
		if ( \function_exists( 'wp_is_mobile' ) )
			return wp_is_mobile();

		if ( isset( $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ) )
			return '?1' === $_SERVER['HTTP_SEC_CH_UA_MOBILE'];

		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) )
			return FALSE;

		$agents = [
			'Mobile',
			'Android',
			'Silk/',
			'Kindle',
			'BlackBerry',
			'Opera Mini',
			'Opera Mobi',
		];

		if ( Core\Text::has( $_SERVER['HTTP_USER_AGENT'], $agents ) )
			return TRUE;

		return FALSE;
	}

	public static function sitemap()
	{
		return Core\Text::has( $_SERVER[REQUEST_URI], 'wp-sitemap' );
	}
}

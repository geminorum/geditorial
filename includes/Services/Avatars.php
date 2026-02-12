<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Avatars extends gEditorial\Service
{
	const DEFAULT_SIZE  = 96;           // It is hardcoded on WordPress core. // NOTE: Less HTTP requests with fixed sizes!
	const DEFAULT_VALUE = 'mysteryman';

	public static function isDisabled()
	{
		return ! get_option( 'show_avatars' );
	}

	public static function getByUser( $user, $fallback = '', $extra = [] )
	{
		// WTF?!
		return self::getByEmail(
			is_object( $user ) ? $user->ID : $user,
			$fallback,
			$extra
		);
	}

	public static function getByEmail( $email, $fallback = '', $extra = [] )
	{
		$args = array_merge( [
			'class'         => self::classs( 'avatar' ),
			'extra_attr'    => Markup::getImgCursorHover(),
			'force_display' => TRUE,
		], $extra );

		return get_avatar(
			$email,
			static::DEFAULT_SIZE,
			static::DEFAULT_VALUE,
			'', // alt
			$args
		);
	}

	// @REF: https://docs.gravatar.com/rest/hash/
	public function urlGravatar( $email, $size = NULL, $default = NULL )
	{
		if ( empty( $email ) )
			return FALSE;

		return vsprintf( '//www.gravatar.com/avatar/%s?s=%d&d=%s', [
			hash( 'sha256', strtolower( trim( $email ) ) ),
			$size ?? static::DEFAULT_SIZE,
			urlencode( $default ?? DEFAULT_VALUE )
		] );
	}

	/**
	 * Get either a Gravatar URL or complete image tag for a specified email address.
	 * @source https://gravatar.com/site/implement/images/php/
	 *
	 * @param string $email The email address
	 * @param int $size Size in pixels, defaults to `64px` [ 1 - 2048 ]
	 * @param string $default_image_type Default image-set to use [ 404 | mp | `identicon` | `monsterid` | `wavatar` ]
	 * @param bool $force_default Force default image always. By default false.
	 * @param string $rating Maximum rating (inclusive) [ g | pg | r | x ]
	 * @param bool $return_image True to return a complete `IMG` tag False for just the URL.
	 * @param array $html_tag_attributes Optional, additional key/value attributes to include in the `IMG` tag.
	 * @return string containing either just a URL or a complete image tag
	 */
	public static function getGravatar(
		$email,
		$size                = 64,
		$default_image_type  = 'mp',
		$force_default       = FALSE,
		$rating              = 'g',
		$return_image        = FALSE,
		$html_tag_attributes = []
	) {

		$params = [
			's' => htmlentities( $size ),
			'd' => htmlentities( $default_image_type ),
			'r' => htmlentities( $rating ),
		];

		if ( $force_default )
			$params['f'] = 'y';

		$url = sprintf( '%s/%s?%s',
			'//www.gravatar.com/avatar',
			hash( 'sha256', strtolower( trim( $email ) ) ),
			http_build_query( $params )
		);

		if ( ! $return_image )
			return $url;

		$attributes = '';

		foreach ( $html_tag_attributes as $key => $value )
			$attributes.= sprintf( '%s="%s" ', $key, htmlentities( $value, ENT_QUOTES, 'UTF-8' ) );

		return sprintf( '<img src="%s" %s/>', $url, $attributes );
	}
}

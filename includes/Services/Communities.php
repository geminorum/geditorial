<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Communities extends gEditorial\Service
{
	public static function setup(): void
	{
		add_filter( self::und( static::BASE, 'prep_social' ), [ __CLASS__, 'filter_prep_social' ], 5, 5 );
	}

	public static function prepSocial( mixed $value, ?string $service = NULL, ?string $context = NULL, null|false|string $empty = '', ?string $separator = NULL, null|string|array $delimiters = NULL ): null|false|string
	{
		if ( self::empty( $value ) )
			return $empty;

		// @hook `geditorial_prep_social`
		$hook = self::und( static::BASE, 'prep', 'social' );
		$list = [];

		if ( $context && is_null( $separator ) ) {

			if ( in_array( $context, [ 'export' ] ) )
				$separator = '|';
		}

		foreach ( Markup::getSeparated( $value, $delimiters ) as $item )
			if ( $prepared = apply_filters( $hook, $item, $item, $value, $context, $service ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function prepSocialIcons( mixed $value, ?string $service = NULL, ?string $context = NULL, null|false|array $empty = [], null|string|array $delimiters = NULL ): null|false|array
	{
		if ( self::empty( $value ) )
			return $empty;

		// @hook `geditorial_prep_social`
		$hook = self::und( static::BASE, 'prep', 'social', 'icon' );
		$list = [];

		foreach ( Markup::getSeparated( $value, $delimiters ) as $item )
			if ( $prepared = apply_filters( $hook, self::prepSocial_Legacy( $item, $service, '', TRUE ), $item, $value, $context, $service ) )
				$list[] = $prepared;

		return $list;
	}

	public static function filter_prep_social( string $item, string $raw, mixed $value, ?string $context, ?string $service ): string
	{
		// late check for REST-API
		if ( WordPress\IsIt::rest() )
			return $item;

		if ( $context && in_array( $context, [ 'print', 'export' ] ) ) {

			if ( Core\Text::starts( $item, '@' ) )
				return sprintf( '%s%s',
					'print' === $context ? '@' : '',
					Core\URL::unquery( Core\Text::stripPrefix( $item, '@' ) ),
				);

			if ( 'print' === $context )
				return Core\URL::prepTitle( $item );
		}

		return self::prepSocial_Legacy( $item, $service ) ?: $item;
	}

	/**
	 * Prepares data for display as a social media link.
	 *
	 * @param string $value
	 * @param string $service
	 * @param mixed $empty
	 * @param bool $icon
	 * @return string
	 */
	public static function prepSocial_Legacy(
		string $value,
		?string $service = NULL,
		mixed $empty = '',
		bool $icon = FALSE,
	): mixed {

		if ( self::empty( $value ) )
			return $empty;

		$prepared = Core\Socials::htmlHandle( $value, $service ) ?: $value;

		return apply_filters( self::und( static::BASE, 'prep', 'contact', 'legacy' ),
			$prepared,
			$value,
			$service,
		);
	}
}

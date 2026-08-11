<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Contacts extends gEditorial\Service
{
	public static function setup(): void
	{
		add_filter( self::und( static::BASE, 'prep_contact' ), [ __CLASS__, 'filter_prep_contact_legacy' ], 5, 5 );
	}

	public static function prepContact( mixed $value, ?string $context = NULL, null|false|string $empty = '', ?string $separator = NULL, null|string|array $delimiters = NULL ): null|false|string
	{
		if ( self::empty( $value ) )
			return $empty;

		// @hook `geditorial_prep_contact`
		$hook = self::und( static::BASE, 'prep', 'contact' );
		$list = [];

		if ( $context && is_null( $separator ) ) {

			if ( in_array( $context, [ 'export' ] ) )
				$separator = '|';
		}

		foreach ( Markup::getSeparated( $value, $delimiters ) as $item )
			if ( $prepared = apply_filters( $hook, $item, $item, $value, $context ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function prepContactIcons( mixed $value, ?string $context = NULL, null|false|array $empty = [], null|string|array $delimiters = NULL ): null|false|array
	{
		if ( self::empty( $value ) )
			return $empty;

		// @hook `geditorial_prep_contact`
		$hook = self::und( static::BASE, 'prep', 'contact', 'icon' );
		$list = [];

		foreach ( Markup::getSeparated( $value, $delimiters ) as $item )
			if ( $prepared = apply_filters( $hook, self::prepContact_Legacy( $item, NULL, '', TRUE ), $item, $value, $context ) )
				$list[] = $prepared;

		return $list;
	}

	public static function filter_prep_contact_legacy( string $item, string $raw, mixed $value, ?string $context ): string
	{
		// late check for REST-API
		if ( WordPress\IsIt::rest() )
			return $item;

		if ( $context && in_array( $context, [ 'print', 'export' ] ) )
			return $item;

		return self::prepContact_Legacy( $item ) ?: $item;
	}

	/**
	 * Prepares data for display as a contact.
	 *
	 * @param string $value
	 * @param string $title
	 * @param mixed $empty
	 * @param bool $icon
	 * @return string
	 */
	public static function prepContact_Legacy(
		string $value,
		?string $title = NULL,
		mixed $empty = '',
		bool $icon = FALSE,
	): mixed {

		if ( self::empty( $value ) )
			return $empty;

		if ( Core\Email::is( $value ) )
			$prepared = Core\Email::prep(
				$value,
				[ 'title' => $title ?? $value ],
				$icon ? 'icon' : 'display',
				$icon ? Icons::get( [ 'misc-16', 'envelope-fill' ] ) : NULL
			);

		else if ( Core\URL::isValid( $value ) )
			$prepared = Core\HTML::link(
				$icon ? Icons::get( [ 'misc-16', 'link-45deg' ] ) : Core\URL::prepTitle( $value ),
				$value,
				TRUE
			);

		else if ( Core\Phone::is( $value ) )
			$prepared = Core\Phone::prep(
				$value,
				[ 'title' => $title ],
				$icon ? 'icon' : 'display',
				$icon ? Icons::get( [ 'misc-16', 'telephone-fill' ] ) : NULL
			);

		else
			$prepared = $icon
				? Core\HTML::tag( 'span', [ 'title' => $value ], Icons::get( [ 'misc-16', 'patch-question-fill' ] ) )
				: Core\HTML::escape( $value );

		return apply_filters( self::und( static::BASE, 'prep', 'contact', 'legacy' ),
			$prepared,
			$value,
			$title
		);
	}
}

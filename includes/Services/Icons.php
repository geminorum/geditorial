<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Icons extends gEditorial\Service
{
	const MENUICON_PROP = 'editorial_icon';

	/**
	 * Retrieves mark-up for given icon.
	 * OLD: `Visual::getIcon()`
	 * TODO: support for `none`: `div.wp-menu-image`
	 *
	 * The URL to the icon to be used for `add_menu_page()`
	 * - Pass a `base64-encoded` SVG using a data URI, which will be colored to match the color scheme. This should begin with `data:image/svg+xml;base64,`.
	 * - Pass the name of a `Dashicons` helper class to use a font icon, e.g. `dashicons-chart-pie`.
	 * - Pass `none` to leave `div.wp-menu-image` empty so an icon can be added via CSS.
	 *
	 * @param mixed $icon
	 * @param string $fallback_icon
	 * @param string|array $extra_class
	 * @return string
	 */
	public static function get( mixed $icon, string $fallback_icon = 'admin-post', string|array $extra_class = [] ): string
	{
		if ( ! $icon || 'none' === $icon )
			return Core\HTML::getDashicon( $fallback_icon, FALSE, $extra_class );

		if ( is_array( $icon ) )
			return gEditorial()->icon( $icon[1], $icon[0], $extra_class );

		if ( Core\Text::starts( $icon, '#' ) )
			return Core\Icon::getLink( $icon, $extra_class );

		if ( Core\Text::starts( $icon, 'data:image/' ) )
			return Core\HTML::img( $icon, Core\HTML::attrClass( '-icon', '-encoded', $extra_class ) );

		if ( Core\Text::starts( $icon, 'dashicons-' ) )
			return Core\HTML::getDashicon( Core\Text::stripPrefix( $icon, 'dashicons-' ) );

		if ( Core\URL::isValid( $icon ) )
			return Core\Icon::wrapURL( Core\HTML::escapeURL( $icon ), $extra_class );

		// `icon-name:icon-set`
		if ( Core\Text::has( $icon, ':' ) )
			return Core\Icon::wrapBase64( Core\Icon::getBase64( ...explode( ':', $icon, 2 ) ), $extra_class ); // better to return hashed

		return Core\HTML::getDashicon( $icon, FALSE, $extra_class );
	}

	// OLD: `Visual::getMenuIcon()`
	public static function menu( string|array $icon, ?string $fallback_icon = NULL ): string
	{
		if ( ! $icon )
			$icon = $fallback_icon ?? 'screenoptions';

		return is_array( $icon )
			? Core\Icon::getBase64( $icon[1], $icon[0] )
			: self::dsh( 'dashicons', $icon );
	}

	// OLD: `Visual::getPostTypeIconMarkup()`
	public static function posttypeMarkup( string|object $posttype, ?string $fallback_icon = NULL, bool $raw = FALSE ): mixed
	{
		$fallback_icon = $fallback_icon ?? 'admin-post';

		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return $raw ? $fallback_icon : Core\HTML::getDashicon( $fallback_icon );

		if ( ! empty( $object->{static::MENUICON_PROP} ) )
			return $raw
				? $object->{static::MENUICON_PROP}
				: self::get( $object->{static::MENUICON_PROP} );

		if ( ! empty( $object->menu_icon )
			&& is_string( $object->menu_icon ) ) {

			if ( Core\Text::has( $object->menu_icon, 'data:image/svg+xml;base64,' ) )
				return $raw
					? $object->menu_icon
					: Core\Icon::wrapBase64( $object->menu_icon );

			if ( Core\Text::starts( $object->menu_icon, 'dashicons-' ) )
				return $raw
					? Core\Text::stripPrefix( $object->menu_icon, 'dashicons-' )
					: Core\HTML::getDashicon( Core\Text::stripPrefix( $object->menu_icon, 'dashicons-' ) );

			return $raw
				? $object->menu_icon
				: Core\Icon::wrapURL( esc_url( $object->menu_icon ) );
		}

		return $raw ? $fallback_icon : Core\HTML::getDashicon( $fallback_icon );
	}

	// OLD: `Visual::getTaxonomyIconMarkup()`
	public static function taxonomyMarkup( string|object $taxonomy, ?string $fallback_icon = NULL, bool $raw = FALSE ): mixed
	{
		$fallback_icon = $fallback_icon ?? 'admin-post';

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return $raw ? $fallback_icon	: Core\HTML::getDashicon( $fallback_icon );

		if ( ! empty( $object->{static::MENUICON_PROP} ) )
			return $raw
				? $object->{static::MENUICON_PROP}
				: self::get( $object->{static::MENUICON_PROP} );

		if ( ! empty( $object->menu_icon )
			&& is_string( $object->menu_icon ) ) {

			if ( Core\Text::has( $object->menu_icon, 'data:image/svg+xml;base64,' ) )
				return $raw
					? $object->menu_icon
					: Core\Icon::wrapBase64( $object->menu_icon );

			if ( Core\Text::starts( $object->menu_icon, 'dashicons-' ) )
				return $raw
					? Core\Text::stripPrefix( $object->menu_icon, 'dashicons-' )
					: Core\HTML::getDashicon( Core\Text::stripPrefix( $object->menu_icon, 'dashicons-' ) );

			return $raw
				? $object->menu_icon
				: Core\Icon::wrapURL( esc_url( $object->menu_icon ) );
		}

		return $raw ? $fallback_icon : Core\HTML::getDashicon( $fallback_icon );
	}

	// OLD: `Visual::getAdminBarIconMarkup()`
	// NOTE: for `dashicons` only, not supporting SVG!
	// NOTE: must use in parent with `.geditorial-adminbar-node-icononly` for icon only
	// NOTE: must use in parent with `.geditorial-adminbar-node.-has-icon` for icon + label
	public static function adminBarMarkup( string $icon = 'screenoptions', string $style = '' ): string
	{
		return Core\HTML::tag( 'span', [
			'class' => [
				'ab-icon',
				'dashicons',
				'dashicons-'.$icon,
			],
			'style' => $style ?: FALSE,
		], NULL );
	}

	public static function ltrMarkup( ?string $icon = NULL, string|array $extra_class = [] ): string
	{
		return Core\HTML::getDashicon(
			$icon ?? 'arrow-right-alt',
			_x( 'Left-to-Right', 'Service: Icons', 'geditorial' ),
			Core\HTML::attrClass( '-direction-icon', $extra_class )
		);
	}

	public static function rtlMarkup( ?string $icon = NULL, string|array $extra_class = [] ): string
	{
		return Core\HTML::getDashicon(
			$icon ?? 'arrow-left-alt',
			_x( 'Right-to-Left', 'Service: Icons', 'geditorial' ),
			Core\HTML::attrClass( '-direction-icon', $extra_class )
		);
	}
}

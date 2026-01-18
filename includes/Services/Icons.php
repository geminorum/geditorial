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
	 * - Pass 'none' to leave div.wp-menu-image empty so an icon can be added via CSS.
	 *
	 * @param string|array $icon
	 * @param string $fallback
	 * @return string $markup
	 */
	public static function get( $icon, $fallback = 'admin-post' )
	{
		if ( ! $icon || 'none' === $icon )
			return Core\HTML::getDashicon( $fallback );

		if ( is_array( $icon ) )
			return gEditorial()->icon( $icon[1], $icon[0] );

		if ( Core\Text::starts( $icon, 'data:image/' ) )
			return Core\HTML::img( $icon, [ '-icon', '-encoded' ] );

		if ( Core\Text::starts( $icon, 'dashicons-' ) )
			$icon = Core\Text::stripPrefix( $icon, 'dashicons-' );

		else if ( Core\URL::isValid( $icon ) )
			return Core\Icon::wrapURL( esc_url( $icon ) );

		return Core\HTML::getDashicon( $icon );
	}

	// OLD: `Visual::getMenuIcon()`
	public static function menu( $icon, $fallback = NULL )
	{
		if ( ! $icon )
			$icon = $fallback ?? 'screenoptions';

		return is_array( $icon )
			? Core\Icon::getBase64( $icon[1], $icon[0] )
			: sprintf( 'dashicons-%s', $icon );
	}

	// OLD: `Visual::getPostTypeIconMarkup()`
	public static function posttypeMarkup( $posttype, $fallback = NULL, $raw = FALSE )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return $raw
				? ( $fallback ?? 'admin-post' )
				: Core\HTML::getIcon( $fallback ?? 'admin-post' );

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
					? str_ireplace( 'dashicons-', '', $object->menu_icon )
					: Core\HTML::getDashicon( str_ireplace( 'dashicons-', '', $object->menu_icon ) );

			return $raw
				? $object->menu_icon
				: Core\Icon::wrapURL( esc_url( $object->menu_icon ) );
		}

		return $raw
			? ( $fallback ?? 'admin-post' )
			: Core\HTML::getIcon( $fallback ?? 'admin-post' );
	}

	// OLD: `Visual::getTaxonomyIconMarkup()`
	public static function taxonomyMarkup( $taxonomy, $fallback = NULL, $raw = FALSE )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return $raw
				? ( $fallback ?? 'admin-post' )
				: Core\HTML::getIcon( $fallback ?? 'admin-post' );

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
					? str_ireplace( 'dashicons-', '', $object->menu_icon )
					: Core\HTML::getDashicon( str_ireplace( 'dashicons-', '', $object->menu_icon ) );

			return $raw
				? $object->menu_icon
				: Core\Icon::wrapURL( esc_url( $object->menu_icon ) );
		}

		return $raw
			? ( $fallback ?? 'admin-post' )
			: Core\HTML::getIcon( $fallback ?? 'admin-post' );
	}

	// OLD: `Visual::getAdminBarIconMarkup()`
	// NOTE: must use in parent with `.geditorial-adminbar-node-icononly`
	public static function adminBarMarkup( $icon = 'screenoptions', $style = FALSE )
	{
		return Core\HTML::tag( 'span', [
			'class' => [
				'ab-icon',
				'dashicons',
				'dashicons-'.$icon,
			],
			'style' => $style,
		], NULL );
	}
}

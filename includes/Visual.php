<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Visual extends WordPress\Main
{
	const BASE = 'geditorial';

	const MENUICON_PROP = 'editorial_icon';

	public static function factory()
	{
		return gEditorial();
	}

	/**
	 * Retrieves markup for given icon.
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
	public static function getIcon( $icon, $fallback = 'admin-post' )
	{
		if ( ! $icon || 'none' === $icon )
			return Core\HTML::getDashicon( $fallback );

		if ( is_array( $icon ) )
			return gEditorial()->icon( $icon[1], $icon[0] );

		if ( Core\Text::starts( $icon, 'data:image/' ) )
			return Core\HTML::img( $icon, [ '-icon', '-encoded' ] );

		if ( Core\Text::starts( $icon, 'dashicons-' ) )
			$icon = Core\Text::stripPrefix( $icon, 'dashicons-' );

		if ( Core\URL::isValid( $icon ) )
			return Core\Icon::wrapURL( esc_url( $icon ) );

		return Core\HTML::getDashicon( $icon );
	}

	public static function getMenuIcon( $icon, $fallback = NULL )
	{
		if ( ! $icon )
			$icon = $fallback ?? 'screenoptions';

		return is_array( $icon )
			? Core\Icon::getBase64( $icon[1], $icon[0] )
			: sprintf( 'dashicons-%s', $icon );
	}

	public static function getPostTypeIconMarkup( $posttype, $fallback = NULL, $raw = FALSE )
	{
		$object = WordPress\PostType::object( $posttype );

		if ( ! empty( $object->{static::MENUICON_PROP} ) )
			return $raw
				? $object->{static::MENUICON_PROP}
				: Core\HTML::getDashicon( $object->{static::MENUICON_PROP} );

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
			: Core\HTML::getDashicon( $fallback ?? 'admin-post' );
	}

	public static function getTaxonomyIconMarkup( $taxonomy, $fallback = NULL, $raw = FALSE )
	{
		$object = WordPress\Taxonomy::object( $taxonomy );

		if ( ! empty( $object->{static::MENUICON_PROP} ) )
			return $raw
				? $object->{static::MENUICON_PROP}
				: Core\HTML::getDashicon( $object->{static::MENUICON_PROP} );

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
			: Core\HTML::getDashicon( $fallback ?? 'admin-post' );
	}

	public static function getAdminBarIconMarkup( $icon = 'screenoptions', $style = 'margin:2px 1px 0 1px;' )
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

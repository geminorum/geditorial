<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Visual extends WordPress\Main
{
	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function getPostTypeIconMarkup( $posttype, $fallback = NULL, $raw = FALSE )
	{
		$object = WordPress\PostType::object( $posttype );

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

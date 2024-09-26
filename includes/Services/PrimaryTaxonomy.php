<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class PrimaryTaxonomy extends WordPress\Main
{
	const BASE = 'geditorial';

	const POSTTYPE_PROP   = 'primary_taxonomy';
	const STATUS_TAX_PROP = 'status_taxonomy';

	/**
	 * Retrieves the primary taxonomy for given posttype.
	 * @OLD: `WordPress\PostType::getPrimaryTaxonomy()`
	 *
	 * @param  string $posttype
	 * @param  mixed  $fallback
	 * @return string $taxonomy
	 */
	public static function get( $posttype, $fallback = FALSE )
	{
		$taxonomy = $fallback;

		if ( 'post' === $posttype )
			$taxonomy = 'category';

		else if ( 'page' === $posttype )
			$taxonomy = $fallback;

		else if ( $posttype == WordPress\WooCommerce::getProductPosttype() && WordPress\WooCommerce::isActive() )
			$taxonomy = WordPress\WooCommerce::getProductCategoryTaxonomy();

		if ( ! $taxonomy && ( $object = WordPress\PostType::object( $posttype ) ) ) {

			if ( ! empty( $object->{self::POSTTYPE_PROP} )
				&& WordPress\Taxonomy::exists( $object->{self::POSTTYPE_PROP} ) )
					$taxonomy = $object->{self::POSTTYPE_PROP};
		}

		return apply_filters( static::BASE.'_posttype_primary_taxonomy', $taxonomy, $posttype, $fallback );
	}
}

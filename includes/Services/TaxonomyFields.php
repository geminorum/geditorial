<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class TaxonomyFields extends gEditorial\Service
{
	/**
	 * Retrieves the term meta-key for given field.
	 *
	 * @param string $field_key
	 * @param string $taxonomy
	 * @param string $module
	 * @param bool $check
	 * @return string $meta_key
	 */
	public static function getTermMetaKey( $field_key, $taxonomy = NULL, $module = 'terms', $check = TRUE )
	{
		if ( ! $field_key )
			return FALSE;

		if ( 'image' === $field_key && $taxonomy
			&& in_array( $taxonomy, WordPress\WooCommerce::PRODUCT_TAXONOMIES, TRUE ) )
			return WordPress\WooCommerce::TERM_IMAGE_METAKEY;

		if ( $check && ! gEditorial()->enabled( $module ) )
			return FALSE;

		return gEditorial()->module( $module )->get_supported_metakey( $field_key, $taxonomy );
	}
}

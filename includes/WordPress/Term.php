<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Term extends Core\Base
{
	/**
	 * Gets all term data.
	 *
	 * @param  int|object $term_or_id
	 * @param  string $taxonomy
	 * @return false|object $term
	 */
	public static function get( $term_or_id, $taxonomy = '' )
	{
		if ( $term_or_id instanceof \WP_Term )
			return $term_or_id;

		if ( ! $term_or_id ) {

			if ( is_admin() )
				return FALSE;

			if ( 'category' == $taxonomy && ! is_category() )
				return FALSE;

			if ( 'post_tag' == $taxonomy && ! is_tag() )
				return FALSE;

			if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ) )
				&& ! is_tax( $taxonomy ) )
					return FALSE;

			if ( ! $term_or_id = get_queried_object_id() )
				return FALSE;
		}

		if ( is_numeric( $term_or_id ) )
			// $term = get_term_by( 'id', $term_or_id, $taxonomy );
			$term = get_term( (int) $term_or_id, $taxonomy ); // allows for empty taxonomy

		else if ( $taxonomy )
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		else
			$term = get_term( $term_or_id, $taxonomy ); // allows for empty taxonomy

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return $term;
	}

	/**
	 * Checks if a term exists and return term id only.
	 *
	 * @source `term_exists()`
	 *
	 * @param  int|string $term
	 * @param  string $taxonomy
	 * @param  int $parent
	 * @return false|int $term_id
	 */
	public static function exists( $term, $taxonomy = '', $parent = NULL )
	{
		if ( ! $term )
			return FALSE;

		if ( $exists = term_exists( $term, $taxonomy, $parent ) )
			return $exists['term_id'];

		return FALSE;
	}

	/**
	 * Checks if a term is publicly viewable.
	 *
	 * @source: `is_term_publicly_viewable()`
	 * @since WP6.1.0
	 *
	 * @param  int|string|object $term
	 * @return bool $viewable
	 */
	public static function viewable( $term )
	{
		$term = get_term( $term );

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return Taxonomy::viewable( $term->taxonomy );
	}

	/**
	 * Updates the taxonomy for the term.
	 *
	 * also accepts term and taxonomy objects
	 * and checks if its a different taxonomy
	 *
	 * @param  int|object $term
	 * @param  string|object $taxonomy
	 * @param  bool $clean_taxonomy
	 * @return bool $success
	 */
	public static function setTaxonomy( $term, $taxonomy, $clean_taxonomy = TRUE )
	{
		global $wpdb;

		if ( ! $taxonomy = Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( ! $term = self::get( $term ) )
			return FALSE;

		if ( $taxonomy->name === $term->taxonomy )
			return TRUE;

		$success = $wpdb->query( $wpdb->prepare( "
			UPDATE {$wpdb->term_taxonomy} SET taxonomy = %s WHERE term_taxonomy_id = %d
		", $taxonomy->name, absint( $term->term_taxonomy_id ) ) );

		clean_term_cache( $term->term_taxonomy_id, $term->taxonomy, $clean_taxonomy );
		clean_term_cache( $term->term_taxonomy_id, $taxonomy->name, $clean_taxonomy );

		return $success;
	}
}
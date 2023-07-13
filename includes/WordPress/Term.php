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

			if ( is_admin() ) {

				if ( is_null( $term_or_id ) && ( $query = self::req( 'tag_ID' ) ) )
					return self::get( (int) $query, $taxonomy );

				return FALSE;
			}

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
	 * Retrieves term title given a post ID or post object.
	 *
	 * @old `Taxonomy::getTermTitle()`
	 *
	 * @param  null|int|object $term
	 * @param  null|string $fallback
	 * @param  bool   $filter
	 * @return string $title
	 */
	public static function title( $term, $fallback = NULL, $filter = TRUE )
	{
		if ( ! $term = self::get( $term ) )
			return '';

		$title = $filter
			? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' )
			: $term->name;

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return __( '(Untitled)' );

		return $fallback;
	}

	/**
	 * Retrieves term taxonomy given a term ID or term object.
	 *
	 * @param  null|int|string|object $term
	 * @return string $taxonomy
	 */
	public static function taxonomy( $term )
	{
		if ( $object = self::get( $term ) )
			return $object->taxonomy;

		return FALSE;
	}

	/**
	 * Retrieves term link given a term ID or term object.
	 *
	 * @param  null|int|string|object $term
	 * @param  null|string $fallback
	 * @return string $link
	 */
	public static function link( $term, $fallback = NULL )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		if ( ! $url = get_term_link( $term ) )
			return $fallback;

		return $url;
	}

	/**
	 * Generates HTML link for given term
	 *
	 * @param  null|int|string|object $term
	 * @param  null|false|string $title
	 * @param  bool|string $fallback
	 * @return string|false $html
	 */
	public static function htmlLink( $term, $title = NULL, $fallback = FALSE )
	{
		if ( ! $term = self::get( $term ) )
			return $fallback;

		if ( ! $url = get_term_link( $term ) )
			return $fallback;

		if ( is_wp_error( $url ) )
			return $fallback;

		if ( FALSE === $title )
			return $url;

		if ( is_null( $title ) )
			$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

		return Core\HTML::tag( 'a', [
			'href'  => $url,
			'class' => [ '-term', '-term-link' ],
			'data'  => [
				'term_id'  => $term->term_id,
				'taxonomy' => $term->taxonomy,
			],
		], $title );
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

	/**
	 * retrieves meta-data for a given term.
	 *
	 * @OLD: `Taxonomy::getTermMeta()`
	 *
	 * @param  object|int $term
	 * @param  bool|array $keys `false` for all meta
	 * @param  bool $single
	 * @return array
	 */
	public static function getMeta( $term, $keys = FALSE, $single = TRUE )
	{
		if ( ! $term = self::get( $term ) )
			return FALSE;

		$list = [];

		if ( FALSE === $keys ) {

			if ( $single ) {

				foreach ( (array) get_term_meta( $term->term_id ) as $key => $meta )
					$list[$key] = maybe_unserialize( $meta[0] );

			} else {

				foreach ( (array) get_term_meta( $term->term_id ) as $key => $meta )
					foreach ( $meta as $offset => $value )
						$list[$key][$offset] = maybe_unserialize( $value );
			}

		} else {

			foreach ( $keys as $key => $default )
				$list[$key] = get_term_meta( $term->term_id, $key, $single ) ?: $default;
		}

		return $list;
	}

	public static function add( $term, $taxonomy, $sanitize = TRUE )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		if ( self::get( $term, $taxonomy ) )
			return TRUE;

		if ( TRUE === $sanitize )
			$slug = sanitize_title( $term );
		else if ( ! $sanitize )
			$slug = $term;
		else
			$slug = $sanitize;

		return wp_insert_term( $term, $taxonomy, array( 'slug' => $slug ) );
	}
}

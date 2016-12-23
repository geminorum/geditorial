<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPTaxonomy extends gEditorialBaseCore
{

	// TODO: our version of `wp_dropdown_categories()`
	// - https://developer.wordpress.org/reference/functions/wp_dropdown_categories/#comment-1823
	// SEE: do_restrict_manage_posts_taxes()
	// ALSO: trim term titles
	// MUST USE: custom walker

	public static function hasTerms( $taxonomy = 'category', $empty = TRUE )
	{
		$terms = get_terms( array(
			'taxonomy'               => $taxonomy,
			'hide_empty'             => ! $empty,
			'fields'                 => 'ids',
			'update_term_meta_cache' => FALSE,
		) );

		return (bool) count( $terms );
	}

	public static function getTerm( $term_or_id, $taxonomy = 'category' )
	{
		if ( is_object( $term_or_id ) )
			$term = $term_or_id;

		else if ( is_numeric( $term_or_id ) )
			$term = get_term_by( 'id', $term_or_id, $taxonomy );

		else
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return $term;
	}

	public static function getTerms( $taxonomy = 'category', $post_id = FALSE, $object = FALSE, $key = 'term_id', $extra = array() )
	{
		$the_terms = array();

		if ( FALSE === $post_id ) {
			$terms = get_terms( array_merge( array(
				'taxonomy'               => $taxonomy,
				'hide_empty'             => FALSE,
				'orderby'                => 'name',
				'order'                  => 'ASC',
				'update_term_meta_cache' => FALSE,
			), $extra ) );
		} else {
			$terms = get_the_terms( $post_id, $taxonomy );
		}

		if ( is_wp_error( $terms ) || FALSE === $terms )
			return $the_terms;

		$list  = wp_list_pluck( $terms, $key );
		$terms = array_combine( $list, $terms );

		if ( $object )
			return $terms;

		foreach ( $terms as $term )
			$the_terms[] = $term->term_id;

		return $the_terms;
	}

	public static function prepTerms( $taxonomy = 'category', $extra = array(), $terms = NULL, $key = 'term_id', $object = TRUE )
	{
		$new_terms = array();

		if ( is_null( $terms ) ) {
			$terms = get_terms( array_merge( array(
				'taxonomy'               => $taxonomy,
				'hide_empty'             => FALSE,
				'orderby'                => 'name',
				'order'                  => 'ASC',
				'update_term_meta_cache' => FALSE,
			), $extra ) );
		}

		if ( is_wp_error( $terms ) || FALSE === $terms )
			return $new_terms;

		foreach ( $terms as $term ) {

			$new = array(
				'name'        => $term->name,
				'description' => $term->description,
				'link'        => get_term_link( $term, $taxonomy ),
				'count'       => $term->count,
				'parent'      => $term->parent,
				'slug'        => $term->slug,
				'id'          => $term->term_id,
			);

			$new_terms[$term->{$key}] = $object ? (object) $new : $new;
		}

		return $new_terms;
	}

	public static function theTerm( $taxonomy, $post_id, $object = FALSE )
	{
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) )
			foreach ( $terms as $term )
				return $object ? $term : $term->term_id;

		return '0';
	}

	public static function getDBTaxonomies( $same_key = FALSE )
	{
		global $wpdb;

		$taxonomies = $wpdb->get_col( "
			SELECT taxonomy
			FROM $wpdb->term_taxonomy
			GROUP BY taxonomy
			ORDER BY taxonomy ASC
		" );

		return $same_key ? self::sameKey( $taxonomies ) : $taxonomies;
	}
}

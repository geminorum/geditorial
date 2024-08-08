<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class TermHierarchy extends WordPress\Main
{

	const BASE = 'geditorial';

	const AUTO_SET_PARENT_TERMS = 'auto_set_parent_terms';
	const AUTO_SET_CHILD_TERMS  = 'auto_set_child_terms';
	const REVERSE_ORDERED_TERMS = 'reverse_ordered_terms';

	public static function setup()
	{
		add_action( 'set_object_terms', [ __CLASS__, 'set_object_terms_auto_set_parent_terms' ], 9999, 6 );
		add_action( 'set_object_terms', [ __CLASS__, 'set_object_terms_auto_set_child_terms' ], 9999, 6 );
		add_filter( 'get_terms_defaults', [ __CLASS__, 'get_terms_defaults' ], 9, 2 );
	}

	/**
	 * Automatically assigns parent taxonomy terms to posts.
	 *
	 * This function will automatically set parent taxonomy terms whenever terms are set on a post,
	 * with the option to configure specific post types, and/or taxonomies.
	 *
	 * @source https://gist.github.com/tripflex/65dbffc4342cf7077e49d641462b46ad
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function set_object_terms_auto_set_parent_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		if ( empty( $tt_ids ) )
			return;

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( empty( $object->{self::AUTO_SET_PARENT_TERMS} ) || ! $object->hierarchical )
			return;

		foreach ( $tt_ids as $tt_id )
			if ( $parent = wp_get_term_taxonomy_parent_id( $tt_id, $taxonomy ) )
				wp_set_post_terms( $object_id, [ $parent ], $taxonomy, TRUE );
	}

	/**
	 * Automatically assigns child taxonomy terms to posts.
	 *
	 * This function will automatically set child taxonomy terms whenever a parent term is set on a post,
	 * with the option to configure specific post types, and/or taxonomies.
	 *
	 * @source https://gist.github.com/tripflex/33025718246b4ffb0050058dd8a69fe3
	 *
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function set_object_terms_auto_set_child_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		if ( empty( $tt_ids ) )
			return;

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( empty( $object->{self::AUTO_SET_CHILD_TERMS} ) || ! $object->hierarchical )
			return;

		foreach ( $tt_ids as $tt_id ) {

			$children = get_term_children( $tt_id, $taxonomy );

			if ( ! empty( $children ) )
				wp_set_post_terms( $object_id, $children, $taxonomy, TRUE );
		}
	}

	/**
	 * Filters the terms query default arguments.
	 *
	 * @param  array $defaults
	 * @param  array $taxonomies
	 * @return array $defaults
	 */
	public static function get_terms_defaults( $defaults, $taxonomies )
	{
		if ( empty( $taxonomies ) || count( (array) $taxonomies ) > 1 )
			return $defaults;

		if ( ! $object = WordPress\Taxonomy::object( reset( $taxonomies ) ) )
			return $defaults;

		if ( empty( $object->{self::REVERSE_ORDERED_TERMS} ) )
			return $defaults;

		$defaults['orderby'] = $object->{self::REVERSE_ORDERED_TERMS};
		$defaults['order']   = 'DESC';

		return $defaults;
	}
}

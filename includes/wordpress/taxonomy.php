<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\HTML;

class Taxonomy extends Core\Base
{

	// TODO: our version of `wp_dropdown_categories()`
	// - https://developer.wordpress.org/reference/functions/wp_dropdown_categories/#comment-1823
	// SEE: do_restrict_manage_posts_taxes()
	// ALSO: trim term titles
	// MUST USE: custom walker

	public static function get( $mod = 0, $args = array(), $object = FALSE )
	{
		$list = array();

		if ( FALSE === $object )
			$objects = get_taxonomies( $args, 'objects' );
		else
			$objects = get_object_taxonomies( $object, 'objects' );

		foreach ( $objects as $taxonomy => $taxonomy_obj ) {

			// label
			if ( 0 === $mod )
				$list[$taxonomy] = $taxonomy_obj->label ? $taxonomy_obj->label : $taxonomy_obj->name;

			// plural
			else if ( 1 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$taxonomy] = array(
					0          => $taxonomy_obj->labels->singular_name,
					1          => $taxonomy_obj->labels->name,
					'singular' => $taxonomy_obj->labels->singular_name,
					'plural'   => $taxonomy_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				);

			// object
			else if ( 4 === $mod )
				$list[$taxonomy] = $taxonomy_obj;

			// with object_type
			else if ( 5 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->name.Core\HTML::joined( $taxonomy_obj->object_type, ' [', ']' );

			// with name
			else if ( 6 === $mod )
				$list[$taxonomy] = $taxonomy_obj->labels->menu_name.' ('.$taxonomy_obj->name.')';
		}

		return $list;
	}

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

	public static function getTerms( $taxonomy = 'category', $object_id = FALSE, $object = FALSE, $key = 'term_id', $extra = array() )
	{
		if ( is_null( $object_id ) )
			$terms = wp_get_object_terms( get_post()->ID, $taxonomy, $extra );

		else if ( FALSE !== $object_id )
			$terms = wp_get_object_terms( $object_id, $taxonomy, $extra );

		else
			$terms = get_terms( array_merge( array(
				'taxonomy'               => $taxonomy,
				'hide_empty'             => FALSE,
				'orderby'                => 'name',
				'order'                  => 'ASC',
				'update_term_meta_cache' => FALSE,
			), $extra ) );

		if ( ! $terms || is_wp_error( $terms ) )
			return array();

		$list = wp_list_pluck( $terms, $key );

		return $object ? array_combine( $list, $terms ) : $list;
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
				// 'name'        => sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				'description' => $term->description,
				'link'        => get_term_link( $term ),
				'count'       => $term->count,
				'parent'      => $term->parent,
				'slug'        => $term->slug,
				'id'          => $term->term_id,
			);

			$new_terms[$term->{$key}] = $object ? (object) $new : $new;
		}

		return $new_terms;
	}

	// EXPERIMENTAL: parsing: 'category:12,11|post_tag:3|people:58'
	public static function parseTerms( $string )
	{
		if ( empty( $string ) || ! $string )
			return FALSE;

		$taxonomies = array();

		foreach ( explode( '|', $string ) as $taxonomy ) {

			list( $tax, $terms ) = explode( ':', $taxonomy );

			$terms = explode( ',', $terms );
			$terms = array_map( 'intval', $terms );

			$taxonomies[$tax] = array_unique( $terms );
		}

		return $taxonomies;
	}

	public static function theTerm( $taxonomy, $post_id, $object = FALSE )
	{
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) )
			foreach ( $terms as $term )
				return $object ? $term : $term->term_id;

		return '0';
	}

	public static function addTerm( $term, $taxonomy = 'category', $sanitize = TRUE )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		if ( self::getTerm( $term, $taxonomy ) )
			return TRUE;

		if ( TRUE === $sanitize )
			$slug = sanitize_title( $term );
		else if ( ! $sanitize )
			$slug = $term;
		else
			$slug = $sanitize;

		return wp_insert_term( $term, $taxonomy, array( 'slug' => $slug ) );
	}

	// EDITED: 5/2/2016, 9:31:13 AM
	public static function insertDefaultTerms( $taxonomy, $terms )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		$count = 0;

		foreach ( $terms as $slug => $term ) {

			$name = $term;
			$args = array( 'slug' => $slug, 'name' => $term );
			$meta = array();

			if ( is_array( $term ) ) {

				if ( ! empty( $term['name'] ) )
					$name = $args['name'] = $term['name'];
				else
					$name = $slug;

				if ( ! empty( $term['description'] ) )
					$args['description'] = $term['description'];

				if ( ! empty( $term['slug'] ) )
					$args['slug'] = $term['slug'];

				if ( ! empty( $term['parent'] ) ) {
					if ( is_numeric( $term['parent'] ) )
						$args['parent'] = $term['parent'];
					else if ( $parent = term_exists( $term['parent'], $taxonomy ) )
						$args['parent'] = $parent['term_id'];
				}

				if ( ! empty( $term['meta'] ) && is_array( $term['meta'] ) )
					foreach ( $term['meta'] as $term_meta_key => $term_meta_value )
						$meta[$term_meta_key] = $term_meta_value;
			}

			if ( $existed = term_exists( $slug, $taxonomy ) )
				wp_update_term( $existed['term_id'], $taxonomy, $args );
			else
				$existed = wp_insert_term( $name, $taxonomy, $args );

			if ( ! is_wp_error( $existed ) ) {

				foreach ( $meta as $meta_key => $meta_value )
					add_term_meta( $existed['term_id'], $meta_key, $meta_value, TRUE ); // will bail if an entry with the same key is found

				$count++;
			}
		}

		return $count;
	}

	public static function addSupport( $taxonomy, $features )
	{
		global $gEditorialTaxonomyFeatures;

		foreach ( (array) $features as $feature )

			if ( 2 == func_num_args() )
				$gEditorialTaxonomyFeatures[$taxonomy][$feature] = TRUE;

			else
				$gEditorialTaxonomyFeatures[$taxonomy][$feature] = array_slice( func_get_args(), 2 );
	}

	public static function removeSupport( $taxonomy, $feature )
	{
		global $gEditorialTaxonomyFeatures;

		unset( $gEditorialTaxonomyFeatures[$taxonomy][$feature] );
	}

	public static function getAllSupports( $taxonomy )
	{
		global $gEditorialTaxonomyFeatures;

		if ( isset( $gEditorialTaxonomyFeatures[$taxonomy] ) )
			return $gEditorialTaxonomyFeatures[$taxonomy];

		return array();
	}

	public static function supports( $taxonomy, $feature )
	{
		$all = self::getAllSupports( $taxonomy );

		if ( isset( $all[$feature][0] ) && is_array( $all[$feature][0] ) )
			return $all[$feature][0];

		return array();
	}

	public static function getBySupport( $feature, $operator = 'and' )
	{
		global $gEditorialTaxonomyFeatures;

		$features = array_fill_keys( (array) $feature, TRUE );

		return array_keys( wp_filter_object_list( $gEditorialTaxonomyFeatures, $features, $operator ) );
	}

	// must add `add_thickbox()` for thickbox
	public static function getFeaturedImageHTML( $term_id, $size = 'thumbnail', $link = TRUE )
	{
		if ( ! $term_image_id = get_term_meta( $term_id, 'image', TRUE ) )
			return '';

		if ( ! $term_thumbnail_img = wp_get_attachment_image_src( $term_image_id, $size ) )
			return '';

		$image = HTML::tag( 'img', array(
			'src'   => $term_thumbnail_img[0],
			'class' => '-featured',
			'alt'   => '',
			'data'  => array(
				'term'       => $term_id,
				'attachment' => $term_image_id,
			),
		) );

		if ( ! $link )
			return $image;

		return HTML::tag( 'a', array(
			'href'   => wp_get_attachment_url( $term_image_id ),
			'title'  => get_the_title( $term_image_id ),
			'class'  => 'thickbox',
			'target' => '_blank',
			'data'   => array(
				'term'       => $term_id,
				'attachment' => $term_image_id,
			),
		), $image );
	}
}

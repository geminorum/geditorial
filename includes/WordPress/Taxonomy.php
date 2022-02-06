<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Taxonomy extends Core\Base
{

	public static function object( $taxonomy )
	{
		return is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );
	}

	public static function can( $taxonomy, $capability = 'manage_terms', $user_id = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		$cap = self::object( $taxonomy )->cap->{$capability};

		return is_null( $user_id )
			? current_user_can( $cap )
			: user_can( $user_id, $cap );
	}

	public static function get( $mod = 0, $args = [], $object = FALSE, $capability = NULL, $user_id = NULL )
	{
		$list = [];

		if ( FALSE === $object || 'any' == $object )
			$objects = get_taxonomies( $args, 'objects' );
		else
			$objects = get_object_taxonomies( $object, 'objects' );

		foreach ( $objects as $taxonomy => $taxonomy_obj ) {

			if ( ! self::can( $taxonomy_obj, $capability, $user_id ) )
				continue;

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
				$list[$taxonomy] = [
					0          => $taxonomy_obj->labels->singular_name,
					1          => $taxonomy_obj->labels->name,
					'singular' => $taxonomy_obj->labels->singular_name,
					'plural'   => $taxonomy_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				];

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

	// @REF: `is_post_type_viewable()`
	public static function isViewable( $taxonomy )
	{
		if ( is_scalar( $taxonomy ) ) {

			if ( ! $taxonomy = get_taxonomy( $taxonomy ) )
				return FALSE;
		}

		return $taxonomy->publicly_queryable
			|| ( $taxonomy->_builtin && $taxonomy->public );
	}

	public static function getDefaultTermID( $taxonomy, $fallback = FALSE )
	{
		return get_option( self::getDefaultTermOptionKey( $taxonomy ), $fallback );
	}

	public static function getDefaultTermOptionKey( $taxonomy )
	{
		if ( 'category' == $taxonomy )
			return 'default_category'; // WordPress

		if ( 'product_cat' == $taxonomy )
			return 'default_product_cat'; // WooCommerce

		return 'default_term_'.$taxonomy;
	}

	public static function hasTerms( $taxonomy, $empty = TRUE )
	{
		$terms = get_terms( array(
			'taxonomy'               => $taxonomy,
			'hide_empty'             => ! $empty,
			'fields'                 => 'ids',
			'orderby'                => 'none',
			'suppress_filter'        => TRUE,
			'update_term_meta_cache' => FALSE,
		) );

		return (bool) count( $terms );
	}

	public static function getTerm( $term_or_id, $taxonomy = '' )
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
			$term = get_term_by( 'id', $term_or_id, $taxonomy );

		else
			$term = get_term_by( 'slug', $term_or_id, $taxonomy );

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		return $term;
	}

	public static function getTerms( $taxonomy, $object_id = FALSE, $object = FALSE, $key = 'term_id', $extra = array(), $post_object = TRUE )
	{
		if ( FALSE === $object_id ) {

			$id = FALSE;

		} else if ( $post_object && ( $post = get_post( $object_id ) ) ) {

			$id = $post->ID;

		} else {

			$id = (int) $object_id;
		}

		if ( $id ) {

			// using cached terms, only for posts, when no extra args provided
			$terms = $post_object && empty( $extra )
				? get_the_terms( $id, $taxonomy )
				: wp_get_object_terms( $id, $taxonomy, $extra );

		} else {

			// FIXME: use WP_Term_Query directly

			$terms = get_terms( array_merge( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => FALSE,
				// 'order'      => 'ASC',
				'order'      => 'DESC',
				// 'orderby'    => 'meta_value_num',
				'orderby'    => 'order_clause',
				'meta_query' => [
					// @REF: https://core.trac.wordpress.org/ticket/34996
					// @SEE: https://wordpress.stackexchange.com/a/246206
					// @SEE: https://wordpress.stackexchange.com/a/277755
					'relation' => 'OR',
					'order_clause' => [
						'key'     => 'order',
						// 'value'   => 0,
						// 'compare' => '>=',
						'type'    => 'NUMERIC'
					],
					[
						'key'     => 'order',
						'compare' => 'NOT EXISTS'
					],
				],
				'update_term_meta_cache' => FALSE,
			), $extra ) );
		}

		if ( ! $terms || is_wp_error( $terms ) )
			return array();

		$list = wp_list_pluck( $terms, $key );

		return $object ? array_combine( $list, $terms ) : $list;
	}

	// @REF: https://developer.wordpress.org/?p=22286
	public static function listTerms( $taxonomy, $fields = NULL, $extra = array() )
	{
		$query = new \WP_Term_Query( array_merge( array(
			'taxonomy'   => (array) $taxonomy,
			'order'      => 'ASC',
			'orderby'    => 'meta_value_num', // 'name',
			'meta_query' => [
				// @REF: https://core.trac.wordpress.org/ticket/34996
				'relation' => 'OR',
				[
					'key'     => 'order',
					'compare' => 'NOT EXISTS'
				],
				[
					'key'     => 'order',
					'compare' => '>=',
					'value'   => 0,
				],
			],
			'fields'     => is_null( $fields ) ? 'id=>name' : $fields,
			'hide_empty' => FALSE,
		), $extra ) );

		if ( empty( $query->terms ) )
			return array();

		return $query->terms;
	}

	public static function listTermsJS( $taxonomy, $fields = NULL, $extra = [] )
	{
		if ( is_null( $fields ) )
			$fields = [ 'term_id', 'name' ];

		return array_map( function ( $term ) use ( $fields ) {
			return Core\Arraay::keepByKeys( get_object_vars( $term ), $fields );
		}, self::listTerms( $taxonomy, 'all', $extra ) );
	}

	public static function prepTerms( $taxonomy, $extra = array(), $terms = NULL, $key = 'term_id', $object = TRUE )
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

	public static function reorderTermsByMeta( $terms, $meta_key = 'order', $fields = 'all' )
	{
		if ( empty( $terms ) || count( $terms ) === 1 || 'count' === $fields )
			return $terms;

		$type = 'object';
		$prop = '_order';
		$list = [];

		if ( in_array( $fields, [ 'ids', 'tt_ids' ], TRUE ) )
			$type = 'array';

		else if ( Core\Text::start( $fields, 'id=>' ) )
			$type = 'assoc';

		foreach ( $terms as $index => $data ) {

			if ( 'array' == $type )
				$term_id = $data;

			else if ( 'assoc' == $type )
				$term_id = $index;

			else if ( isset( $data->term_id ) )
				$term_id = $data->term_id;

			else
				continue;

			if ( $meta = get_term_meta( $term_id, $meta_key, TRUE ) )
				$order = (int) $meta;

			else
				$order = 0;

			if ( 'array' == $type ) {

				$list[] = [
					'term_id' => $data,
					$prop     => $order,
				];

			} else if ( 'assoc' == $type ) {

				$list[] = [
					'term_id' => $index,
					'data'    => $data,
					$prop     => $order,
				];

			} else if ( 'object' == $type ) {

				$data->{$prop} = $order;
				$list[] = $data;
			}
		}

		// bail if cannot determine the term ids
		if ( empty( $list ) )
			return $terms;

		if ( 'array' == $type )
			return array_column( Core\Arraay::sortByPriority( $list, $prop ), 'term_id' );

		if ( 'assoc' == $type )
			return array_column( Core\Arraay::sortByPriority( $list, $prop ), 'data', 'term_id' );

		return Core\Arraay::sortObjectByPriority( $list, $prop );
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

	public static function addTerm( $term, $taxonomy, $sanitize = TRUE )
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

	public static function getIDbyMeta( $key, $value )
	{
		static $results = [];

		if ( isset( $results[$key][$value] ) )
			return $results[$key][$value];

		global $wpdb;

		$term_id = $wpdb->get_var(
			$wpdb->prepare( "
				SELECT term_id
				FROM {$wpdb->termmeta}
				WHERE meta_key = %s
				AND meta_value = %s
			", $key, $value )
		);

		return $results[$key][$value] = $term_id;
	}

	public static function appendParentTermIDs( $term_ids, $taxonomy )
	{
		if ( ! self::object( $taxonomy )->hierarchical )
			return $term_ids;

		$terms = [];

		foreach ( $term_ids as $term_id )
			$terms = array_merge( $terms, self::getTermParents( $term_id, $taxonomy ) );

		return Core\Arraay::prepNumeral( $term_ids, $terms );
	}

	public static function getTermParents( $term_id, $taxonomy )
	{
		static $data = [];

		if ( isset( $data[$taxonomy][$term_id] ) )
			return $data[$taxonomy][$term_id];

		$current = $term_id;
		$parents = [];
		$up      = TRUE;

		while ( $up ) {

			$term = get_term( (int) $current, $taxonomy );

			if ( $term->parent )
				$parents[] = (int) $term->parent;

			else
				$up = FALSE;

			$current = $term->parent;
		}

		return $data[$taxonomy][$term_id] = $parents;
	}

	// TODO: must suport different parents
	public static function getTargetTerm( $target, $taxonomy, $args = [], $meta = [] )
	{
		$target = trim( $target );

		if ( is_numeric( $target ) ) {

			if ( $term = term_exists( (int) $target, $taxonomy ) )
				return get_term( $term['term_id'], $taxonomy );

			else
				return FALSE; // avoid inserting numbers as new terms!

		} else if ( $term = term_exists( $target, $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( apply_filters( 'string_format_i18n', $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::nameFamilyFirst( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );

		} else if ( $term = term_exists( Core\Text::nameFamilyLast( $target ), $taxonomy ) ) {

			return get_term( $term['term_id'], $taxonomy );
		}

		// avoid filtering the new term
		$term = wp_insert_term( $target, $taxonomy, $args );

		if ( self::isError( $term ) )
			return FALSE;

		foreach ( $meta as $meta_key => $meta_value )
			add_term_meta( $term['term_id'], $meta_key, $meta_value, TRUE );

		return get_term( $term['term_id'], $taxonomy );
	}

	public static function insertDefaultTerms( $taxonomy, $terms, $update_terms = TRUE )
	{
		if ( ! taxonomy_exists( $taxonomy ) )
			return FALSE;

		$count = [];

		foreach ( $terms as $slug => $term ) {

			$name   = $term;
			$meta   = array();
			$args   = array( 'slug' => $slug, 'name' => $term );
			$update = $update_terms;

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

				if ( array_key_exists( 'update', $term ) )
					$update = $term['update'];
			}

			if ( $existed = term_exists( $slug, $taxonomy ) ) {

				if ( $update )
					wp_update_term( $existed['term_id'], $taxonomy, $args );

			} else {

				$existed = wp_insert_term( $name, $taxonomy, $args );
			}

			if ( ! is_wp_error( $existed ) ) {

				foreach ( $meta as $meta_key => $meta_value ) {

					if ( $update )
						update_term_meta( $existed['term_id'], $meta_key, $meta_value );
					else
						// will bail if an entry with the same key is found
						add_term_meta( $existed['term_id'], $meta_key, $meta_value, TRUE );
				}

				$count[] = $existed;
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
	// @SEE: `Scripts::enqueueThickBox()`
	public static function htmlFeaturedImage( $term_id, $size = 'thumbnail', $link = TRUE, $metakey = 'image' )
	{
		if ( ! $term_image_id = get_term_meta( $term_id, $metakey, TRUE ) )
			return '';

		if ( ! $term_thumbnail_img = wp_get_attachment_image_src( $term_image_id, $size ) )
			return '';

		$image = Core\HTML::tag( 'img', array(
			'src'     => $term_thumbnail_img[0],
			'alt'     => '',
			'class'   => '-featured',
			'loading' => 'lazy',
			'data'    => array(
				'term'       => $term_id,
				'attachment' => $term_image_id,
			),
		) );

		if ( ! $link )
			return $image;

		return Core\HTML::tag( 'a', array(
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

	public static function disableTermCounting()
	{
		wp_defer_term_counting( TRUE );

		// also avoids query for post terms
		remove_action( 'transition_post_status', '_update_term_count_on_transition_post_status', 10 );

		// WooCommerce
		add_filter( 'woocommerce_product_recount_terms', '__return_false' );
	}
}

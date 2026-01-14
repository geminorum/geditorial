<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class TermRelations extends gEditorial\Service
{
	const POSTTYPE_PROP   = 'terms_related';
	const TAXONOMY_PROP   = 'terms_related';
	const POSTTYPE_ATTR   = 'terms_relations';
	const TAXONOMY_ATTR   = 'terms_relations';
	const FIELD_ORDER     = '_order';
	const FIELD_USERID    = '_userid';          // last edit user_id
	const FIELD_TIMESTAMP = '_timestamp';       // last edit timestamp
	const CUSTOM_ORDER    = 'termrelation';
	const GLOBAL_CONTEXT  = 'termrelation';

	public static function setup()
	{
		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );
		add_filter( 'get_object_terms', [ __CLASS__, 'get_object_terms' ], 8, 4 );
	}

	// TODO: register endpoint for rendered: `{prefix}/{post_id}/{taxonomy}`
	// @SEE `rest_{$this->taxonomy}_query` filter
	public static function rest_api_init()
	{
		if ( ! $taxonomies = self::getTaxonomies() )
			return;

		register_rest_field( self::getPostTypes( $taxonomies ), self::POSTTYPE_ATTR, [
			'schema' => NULL,

			'get_callback' => static function ( $params, $attr, $request, $object_type ) {
				return self::getPostData( (int) $params['id'], $request['context'] );
			},

			'update_callback' => static function ( $data, $object ) {
				return self::updatePostData( $object->ID, $data );
			},
		] );

		register_rest_field( array_keys( $taxonomies ), self::TAXONOMY_ATTR, [
			'schema' => NULL,

			'get_callback' => static function ( $params, $attr, $request, $object_type ) {
				return empty( $request['post'] )
					? FALSE
					: self::getTermData(
						(int) $request['post'],
						(int) $params['id'],
						$request['context']
					);
			},
		] );

		// whitelist our custom order
		// @SEE: https://iamshishir.com/sorting-orderby-for-custom-meta-fields-in-wordpress/
		foreach ( $taxonomies as $taxonomy => $objects )
			add_filter( "rest_{$taxonomy}_collection_params",
				static function ( $query_params ) {
					$query_params['orderby']['enum'][]  = static::CUSTOM_ORDER;
					$query_params['orderby']['default'] = static::CUSTOM_ORDER;
					return $query_params;
				} );
	}

	// NOTE: this will also effect the results on `get_the_terms` filter
	public static function get_object_terms( $terms, $object_ids, $taxonomies, $args )
	{
		// bail if no terms found
		if ( empty( $terms ) )
			return $terms;

		// bail if more than one object
		if ( count( $object_ids ) !== 1 || count( $taxonomies ) !== 1 )
			return $terms;

		// bail if no taxonomy or not supported
		$supported = array_keys( self::getTaxonomies() );

		if ( empty( $supported ) || ! in_array( $taxonomies[0], $supported, TRUE ) )
			return $terms;

		// bail if `orderby` exists and is not `relatedmeta`
		if ( array_key_exists( 'orderby', $args ) && static::CUSTOM_ORDER != $args['orderby'] )
			return $terms;

		// bail if count only
		if ( array_key_exists( 'count', $args ) && $args['count'] )
			return $terms;

		$fields = empty( $args['fields'] ) ? 'all' : $args['fields'];

		// bail if count only
		if ( 'count' === $fields )
			return $terms;

		$metakey = self::getMetakey( static::FIELD_ORDER, $object_ids[0] );

		return WordPress\Taxonomy::reorderTermsByMeta( $terms, $metakey, $fields );
	}

	public static function updatePostData( $post, $data )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$supported = [];

		foreach ( $data as $raw ) {

			if ( empty( $raw['id'] ) || ! $term = WordPress\Term::get( (int) $raw['id'] ) )
				continue;

			if ( ! current_user_can( 'assign_term', $term->term_id ) )
				continue;

			if ( ! empty( $raw['__delete'] ) ) {
				wp_remove_object_terms( $post->ID, $term->term_id, $term->taxonomy );
				// MAYBE: delete all meta related!
				// TODO: clean-up process for residual data
				continue;
			}

			$result = wp_set_object_terms(
				$post->ID,
				$term->term_id,
				$term->taxonomy,
				TRUE
			);

			if ( is_wp_error( $result ) )
				continue;

			if ( empty( $supported[$term->taxonomy] ) )
				$supported[$term->taxonomy] = self::get_supported( $term->taxonomy, 'edit', $post->post_type );

			foreach ( $supported[$term->taxonomy] as $field => $args ) {

				if ( array_key_exists( $field, $raw ) ) {

					switch ( empty( $args['type'] ) ? 'string' : $args['type'] ) {

						case 'boolean':
							$meta = $raw[$field] ? '1' : '';
							break;

						default:
						case 'string':
							$meta = Core\Text::trim( $raw[$field] );
					}

					$filtered = apply_filters(
						sprintf( '%s_sanitize_field_data', static::BASE ),
						$meta, $field, $term, $post, $raw
					);

					// skipped by filter!
					if ( FALSE === $filtered )
						continue;

				} else if ( ! in_array( $field, [ static::FIELD_USERID, static::FIELD_TIMESTAMP ], TRUE ) ) {

					continue;
				}

				$metakey = self::getMetakey( $field, $post->ID );

				if ( static::FIELD_USERID === $field )
					$result = update_term_meta( $term->term_id, $metakey, get_current_user_id() );

				else if ( static::FIELD_TIMESTAMP === $field )
					$result = update_term_meta( $term->term_id, $metakey, current_time( 'mysql' ) );

				else if ( is_null( $filtered ) || '' === $filtered || ( static::FIELD_ORDER == $field && empty( $filtered ) ) )
					$result = delete_term_meta( $term->term_id, $metakey );

				else
					$result = update_term_meta( $term->term_id, $metakey, $filtered );

				// if ( is_wp_error( $result ) )
			}
		}

		return TRUE;
	}

	public static function getPostData( $post, $context = 'view' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return [];

		$data       = [];
		$supported  = get_object_taxonomies( $post );
		$taxonomies = array_keys( self::getTaxonomies() );

		foreach ( $taxonomies as $taxonomy ) {

			if ( ! in_array( $taxonomy, $supported, TRUE ) )
				continue;

			$data = array_merge(
				$data,
				self::_getData( $post, $taxonomy, NULL, $context )
			);
		}

		return $data;
	}

	private static function _getData( $post, $taxonomy, $terms = NULL, $context = 'view' )
	{
		if ( is_null( $terms ) )
			$terms = get_the_terms( $post, $taxonomy ); // hits the cache

		if ( ! $terms || is_wp_error( $terms ) )
			return [];

		$list   = [];
		$fields = self::get_supported( $taxonomy, $context, $post->post_type );

		foreach ( $terms as $term ) {

			$meta = get_term_meta( $term->term_id );
			$data = [
				'id'       => $term->term_id,
				'taxonomy' => $term->taxonomy,
			];

			if ( 'edit' === $context ) {
				// NOTE: must comp with `SearchSelect`
				$data['text']  = WordPress\Term::title( $term );
				$data['extra'] = SearchSelect::getExtraForTerm( $term, [ 'context' => static::GLOBAL_CONTEXT ] );
				$data['image'] = SearchSelect::getImageForTerm( $term, [ 'context' => static::GLOBAL_CONTEXT ] );
			}

			foreach ( $fields as $field => $args ) {

				$metakey = self::getMetakey( $field, $post->ID );

				if ( array_key_exists( $metakey, $meta ) )
					$data[$field] = $meta[$metakey][0];

				else if ( is_array( $args ) && array_key_exists( 'default', $args ) )
					$data[$field] = $args['default'];

				else if ( static::FIELD_ORDER == $field )
					$data[$field] = 0;

				if ( ! empty( $args['type'] ) )  {

					if ( 'boolean' === $args['type'] )
						$data[$field] = (bool) $data[$field];

					else if ( in_array( $args['type'], [ 'integer', 'number' ], TRUE ) )
						$data[$field] = (int) $data[$field];

				} else if ( static::FIELD_ORDER === $field ) {

					$data[$field] = (int) $data[$field];
				}
			}

			$list[] = $data;
		}

		return $list;
	}

	public static function getTermData( $post, $term, $context = 'view' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		if ( ! in_array( $term->taxonomy, get_object_taxonomies( $post ), TRUE ) )
			return FALSE;

		$data = self::_getData( $post, $term->taxonomy, [ $term ], $context );

		return empty( $data ) ? FALSE : reset( $data );
	}

	public static function getPostTypes( $taxonomies = NULL )
	{
		$taxonomies = $taxonomies ?? self::getTaxonomies();

		if ( empty( $taxonomies ) )
			return [];

		return Core\Arraay::prepString( ...array_values( $taxonomies ) );
	}

	public static function getTaxonomies()
	{
		return WordPress\Taxonomy::get( 7, [
			// 'show_ui'      => TRUE,
			'show_in_rest' => TRUE,
			'_builtin'     => FALSE,

			self::TAXONOMY_PROP => TRUE,
		] );
	}

	public static function get_supported( $taxonomy, $context = NULL, $posttype = FALSE )
	{
		return apply_filters( sprintf( '%s_termrelations_supported', static::BASE ), [
			static::FIELD_ORDER     => NULL,
			// static::FIELD_USERID    => NULL,
			// static::FIELD_TIMESTAMP => NULL,
		], $taxonomy, $context, $posttype );
	}

	public static function getMetakey( $key, $object_id, $for = 'post' )
	{
		return sprintf( '_%3$s_%2$d_%1$s', $key, $object_id, $for );
	}
}

<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress\Main;

class Rest extends Main
{

	const BASE = 'geditorial';

	public static function setup()
	{
		$class = __NAMESPACE__.'\\Rest';

		add_action( 'rest_api_init', [ $class, 'rest_api_init' ], 20 );
	}

	public static function rest_api_init()
	{
		$excluded  = Settings::posttypesExcluded();
		$posttypes = get_post_types( [ 'show_in_rest' => TRUE ] );
		$posttypes = array_diff_key( $posttypes, array_flip( $excluded ) );
		$posttypes = apply_filters( static::BASE.'_rest_terms_rendered_posttypes', $posttypes );

		register_rest_field( $posttypes, 'terms_rendered', [
			'get_callback' => [ __NAMESPACE__.'\\Rest', 'terms_rendered_get_callback' ],
		] );
	}

	public static function terms_rendered_get_callback( $post, $attr, $request, $object_type )
	{
		$rendered = [];
		$ignored  = apply_filters( static::BASE.'_rest_terms_rendered_ignored', [ 'post_format' ], $post, $object_type );

		foreach ( get_object_taxonomies( $object_type, 'objects' ) as $taxonomy ) {

			// @REF: `is_taxonomy_viewable()`
			if ( ! $taxonomy->publicly_queryable )
				continue;

			if ( in_array( $taxonomy->name, $ignored ) )
				continue;

			$base = empty( $taxonomy->rest_base ) ? $taxonomy->name : $taxonomy->rest_base;
			$list = Template::getTheTermList( $taxonomy->name, $post['id'] );

			$rendered[$base] = apply_filters( static::BASE.'_rest_terms_rendered_html', $list, $post, $taxonomy, $object_type );
		}

		return $rendered;
	}
}

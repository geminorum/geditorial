<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Rest extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE )
	{
		return gEditorial()->{static::MODULE}->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = [], $metakey = NULL )
	{
		return FALSE === $field
			? gEditorial()->{static::MODULE}->get_postmeta_legacy( $post_id, $default )
			: gEditorial()->{static::MODULE}->get_postmeta_field( $post_id, $field, $default, $metakey );
	}

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

		foreach ( $posttypes as $posttype )
			register_rest_field( $posttype, 'terms_rendered', [
				'get_callback' => [ __NAMESPACE__.'\\Rest', 'terms_rendered_get_callback' ],
			] );
	}

	public static function terms_rendered_get_callback( $post, $attr, $request, $object_type )
	{
		$rendered = [];
		$ignored  = apply_filters( static::BASE.'_rest_terms_rendered_ignored', [ 'post_format' ], $post );

		foreach ( get_object_taxonomies( $post['type'], 'objects' ) as $taxonomy ) {

			// @REF: `is_taxonomy_viewable()`
			if ( ! $taxonomy->publicly_queryable )
				continue;

			if ( in_array( $taxonomy->name, $ignored ) )
				continue;

			$base = empty( $taxonomy->rest_base ) ? $taxonomy->name : $taxonomy->rest_base;
			$list = Template::getTheTermList( $taxonomy->name, $post['id'] );

			$rendered[$base] = apply_filters( static::BASE.'_rest_terms_rendered_html', $list, $post, $taxonomy );
		}

		return $rendered;
	}
}

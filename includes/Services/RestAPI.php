<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class RestAPI extends gEditorial\Service
{
	const REST_FIELD_TERMS = 'terms_rendered';

	public static function setup()
	{
		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ], 20 );
	}

	public static function rest_api_init()
	{
		if ( $posttypes = self::getSupportedPosttypes( NULL, 'terms_rendered' ) )
			register_rest_field( $posttypes, static::REST_FIELD_TERMS, [
				'get_callback' => [ __CLASS__, 'terms_rendered_get_callback' ],
			] );
	}

	public static function getSupportedPosttypes( $posttypes = NULL, $context = NULL )
	{
		$excluded = [
			'attachment',
			'inbound_message',
			'amp_validated_url',
			'guest-author',      // Co-Authors Plus
			'bp-email',
			'wp_block',
			'shop_order',        // WooCommerce
			'shop_coupon',       // WooCommerce
		];

		if ( is_null( $posttypes ) )
			$posttypes = get_post_types( [ 'show_in_rest' => TRUE ] );

		return apply_filters( static::BASE.'_restapi_supported_posttypes',
			array_diff_key( $posttypes, array_flip( $excluded ) ),
			$context,
			$excluded
		);
	}

	public static function terms_rendered_get_callback( $params, $attr, $request, $object_type )
	{
		if ( empty( $params['id'] ) )
			return [];

		if ( ! $post = get_post( (int) $params['id'] ) )
			return [];

		$rendered = [];
		$user_id  = get_current_user_id();
		$ignored  = apply_filters( static::BASE.'_restapi_terms_rendered_ignored',
			[
				'post_format',
			],
			$params,
			$object_type,
			$post
		);

		foreach ( get_object_taxonomies( $object_type, 'objects' ) as $taxonomy ) {

			if ( in_array( $taxonomy->name, $ignored, TRUE ) )
				continue;

			if ( ! is_taxonomy_viewable( $taxonomy )
				&& ! WordPress\Taxonomy::can( $taxonomy, 'assign_terms', $user_id ) )
					continue;

			$rows = WordPress\Taxonomy::getTheTermRows( $taxonomy->name, $post );
			$html = apply_filters( static::BASE.'_restapi_terms_rendered_html',
				$rows,
				$taxonomy,
				$params,
				$object_type,
				$post
			);

			if ( FALSE === $html )
				continue;

			$rendered[$taxonomy->rest_base] = apply_filters( static::BASE.'_restapi_terms_rendered',
				[
					'name'     => $taxonomy->name,
					'title'    => $taxonomy->label,
					'link'     => WordPress\Taxonomy::link( $taxonomy ),
					'rendered' => $html,
				],
				$taxonomy,
				$params,
				$object_type,
				$post
			);
		}

		return $rendered;
	}

	public static function getPostResponse( $post, $context = 'view' )
	{
		$response = FALSE;

		if ( $route = WordPress\Post::getRestRoute( $post ) )
			$response = WordPress\Rest::doInternalRequest( $route, [ 'context' => $context ] );

		return apply_filters( static::BASE.'_restapi_post_response', $response, $post, $context, $route );
	}

	public static function getCommentsResponse( $post, $context = 'view' )
	{
		$response = FALSE;
		$route    = '/wp/v2/comments';

		if ( $post = WordPress\Post::get( $post ) )
			$response = WordPress\Rest::doInternalRequest( $route, [
				'context' => $context,
				'post'    => $post->ID,
			] );

		return apply_filters( static::BASE.'_restapi_comments_response', $response, $post, $context, $route );
	}

	public static function getTermResponse( $term, $context = 'view' )
	{
		$response = FALSE;

		if ( $route = WordPress\Term::getRestRoute( $term ) )
			$response = WordPress\Rest::doInternalRequest( $route, [ 'context' => $context ] );

		return apply_filters( static::BASE.'_restapi_term_response', $response, $term, $context, $route );
	}

	public static function getErrorForbidden( $code = NULL, $status = 401 )
	{
		return new \WP_Error(
			$code ?? 'rest_forbidden',
			esc_html_x( 'OMG you can not view private data.', 'Service: RestAPI: Error Forbidden', 'geditorial' ),
			[
				'status' => $status,
			]
		);
	}

	public static function getErrorArgNotEmpty( $key = NULL, $data = [], $code = NULL, $status = NULL )
	{
		$message = is_null( $key )
			? _x( 'The argument must not be empty.', 'Service: RestAPI: Error Arg Not Empty', 'geditorial' )
			/* translators: `%s`: argument key */
			: sprintf( _x( 'The `%s` argument must not be empty.', 'Service: RestAPI: Error Arg Not Empty', 'geditorial' ), $key );

		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code ?? 'rest_invalid_param', $message, $data );
	}

	public static function getErrorNoPermission( $code = NULL, $message = NULL, $data = [], $status = 401 )
	{
		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code ?? 'not_authorized', $message ?? gEditorial\Plugin::denied( FALSE ), $data );
	}

	public static function getErrorInvalidData( $code = NULL, $message = NULL, $data = [], $status = NULL )
	{
		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code ?? 'invalid_data_provided', $message ?? gEditorial\Plugin::invalid( FALSE ), $data );
	}

	public static function getErrorNotFound( $code = NULL, $message = NULL, $data = [], $status = 404 )
	{
		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code ?? 'not_found', $message ?? __( 'Not Available', 'geditorial' ), $data );
	}

	public static function getErrorSomethingIsWrong( $code = NULL, $message = NULL, $data = [], $status = NULL )
	{
		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code ?? 'no_correct_settings', $message ?? gEditorial\Plugin::wrong( FALSE ), $data );
	}

	public static function defineArgument_postid( $description = NULL, $required = TRUE, $validate = NULL )
	{
		return [
			'required'          => (bool) $required,
			'type'              => 'integer',
			'description'       => $description ?? esc_html_x( 'The id of the post.', 'Service: RestAPI: Arg Description', 'geditorial' ),
			'validate_callback' => $validate ?? [ __CLASS__, 'validateArgument_postid' ],
		];
	}

	public static function validateArgument_postid( $param, $request, $key )
	{
		if ( empty( $param ) || (int) $param <= 0 )
			return self::getErrorArgNotEmpty( $key );

		if ( ! WordPress\Post::get( (int) $param ) )
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: `%s`: argument key */
				sprintf( _x( 'The `%s` argument must be a post.', 'Error', 'geditorial' ), $key )
			);

		return TRUE;
	}

	public static function defineArgument_commentid( $description = NULL, $required = TRUE, $validate = NULL )
	{
		return [
			'required'          => (bool) $required,
			'type'              => 'integer',
			'description'       => $description ?? esc_html_x( 'The id of the comment.', 'Service: RestAPI: Arg Description', 'geditorial' ),
			'validate_callback' => $validate ?? [ __CLASS__, 'validateArgument_commentid' ],
		];
	}

	public static function validateArgument_commentid( $param, $request, $key )
	{
		if ( empty( $param ) || (int) $param <= 0 )
			return self::getErrorArgNotEmpty( $key );

		if ( ! WordPress\Comment::get( (int) $param ) )
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: `%s`: argument key */
				sprintf( _x( 'The `%s` argument must be a comment.', 'Error', 'geditorial' ), $key )
			);

		return TRUE;
	}
}

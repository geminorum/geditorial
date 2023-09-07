<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress;

class RestAPI extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function getPostResponse( $post, $context = 'view' )
	{
		$response = FALSE;

		if ( $route = WordPress\Post::getRestRoute( $post ) )
			$response = WordPress\Rest::doInternalRequest( $route, [ 'context' => $context ] );

		return apply_filters( static::BASE.'_restapi_post_response', $response, $post, $context, $route );
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
			/* translators: %s: argument key */
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

		if ( ! get_post( (int) $param ) )
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: %s: argument key */
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

		if ( ! get_comment( (int) $param ) )
			return new \WP_Error(
				'rest_invalid_param',
				/* translators: %s: argument key */
				sprintf( _x( 'The `%s` argument must be a comment.', 'Error', 'geditorial' ), $key )
			);

		return TRUE;
	}
}

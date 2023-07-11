<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress\Main;

class RestAPI extends Main
{

	const BASE = 'geditorial';

	public static function getErrorForbidden( $code = 'rest_forbidden', $status = 401 )
	{
		return new \WP_Error(
			$code,
			esc_html_x( 'OMG you can not view private data.', 'Service: RestAPI: Error Forbidden', 'geditorial' ),
			[
				'status' => $status,
			]
		);
	}

	public static function getErrorArgNotEmpty( $key = NULL, $data = [], $code = 'rest_invalid_param', $status = NULL )
	{
		$message = is_null( $key )
			? _x( 'The argument must not be empty.', 'Service: RestAPI: Error Arg Not Empty', 'geditorial' )
			/* translators: %s: argument key */
			: sprintf( _x( 'The `%s` argument must not be empty.', 'Service: RestAPI: Error Arg Not Empty', 'geditorial' ), $key );

		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code, $message, $data );
	}

	public static function getErrorNoPermission( $code = 'not_authorized', $message = NULL, $data = [], $status = 401 )
	{
		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code, $message ?? gEdiorial\Plugin::denied( FALSE ), $data );
	}

	public static function getErrorSomethingIsWrong( $code = 'no_correct_settings', $message = NULL, $data = [], $status = NULL )
	{
		if ( ! is_null( $status ) )
			$data['status'] = $status;

		return new \WP_Error( $code, $message ?? gEdiorial\Plugin::wrong( FALSE ), $data );
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

<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ObjectHints extends gEditorial\Service
{
	const REST_ENDPOINT_SUFFIX     = 'object-hints';
	const REST_ENDPOINT_VERSION    = 'v1';
	const REST_ENDPOINT_MAIN_ROUTE = 'tips';

	public static function setup()
	{
		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );
	}

	public static function namespace()
	{
		return sprintf( '%s-%s/%s',
			static::BASE,
			static::REST_ENDPOINT_SUFFIX,
			static::REST_ENDPOINT_VERSION
		);
	}

	public static function rest_api_init()
	{
		register_rest_route( self::namespace(), '/'.static::REST_ENDPOINT_MAIN_ROUTE, [
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => [ __CLASS__, 'main_route_callback' ],
			'permission_callback' => '__return_true', // NOTE: later we check for access
		] );
	}

	// TODO: support terms
	// TODO: support users
	public static function main_route_callback( $request )
	{
		$queried = self::atts( [
			'id'       => '',
			'target'   => '',          // `post`/`term`/`user`
			'extend'   => 'default',
			'context'  => 'view',
			'posttype' => '',
			'taxonomy' => '',
			'role'     => '',
			'page'     => '1',
			'per'      => '10',
		], $request->get_query_params() );

		switch ( $queried['target'] ) {

			case 'post':

				if ( empty( $queried['id'] ) )
					return RestAPI::getErrorSomethingIsWrong();

				if ( ! $post = WordPress\Post::get( (int) $queried['id'] ) )
					return RestAPI::getErrorSomethingIsWrong();

				$access = in_array( $queried['context'], [ 'edit' ], TRUE )
					? WordPress\Post::can( $post, 'edit_post' )
					: WordPress\Post::viewable( $post );

				if ( ! $access )
					return RestAPI::getErrorNoPermission();

				$response = self::_get_tips_posts( $post, $queried );
				break;

			default:
				return RestAPI::getErrorSomethingIsWrong();
		}

		return new \WP_REST_Response( $response, 200 );
	}

	private static function _get_tips_posts( $post, $queried )
	{
		//@hook: `geditorial_objecthints_tips_for_post`
		$hints = apply_filters( self::hook( 'objecthints', 'tips_for_post' ),
			[],
			$post,
			$queried['extend'],
			$queried['context'],
			$queried
		);

		$data = array_map(
			static function ( $hint ) {
				return self::atts( [
					'html'     => '',
					'text'     => '',
					'title'    => '',
					'link'     => '#',
					'data'     => [],
					'class'    => self::classs( static::REST_ENDPOINT_MAIN_ROUTE ),
					'source'   => static::REST_ENDPOINT_MAIN_ROUTE,
					'priority' => 10,
				], $hint );
			}, $hints );

		if ( count( $data ) > 1 )
			$data = Core\Arraay::sortByPriority( $data, 'priority' );

		return $data;
	}
}

<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class LineDiscovery extends gEditorial\Service
{
	const REST_ENDPOINT_SUFFIX     = 'line-discovery';
	const REST_ENDPOINT_VERSION    = 'v1';
	const REST_ENDPOINT_MAIN_ROUTE = 'bulk';

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
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => [ __CLASS__, 'main_route_callback' ],
			'permission_callback' => '__return_true', // NOTE: later we check for access
		] );
	}

	// TODO: support terms
	// TODO: support users
	public static function main_route_callback( $request )
	{
		$queried = self::atts( [
			'raw'      => [],
			'insert'   => FALSE,
			'target'   => '',
			'refkey'   => '_refkey',
			'posttype' => '',
			'taxonomy' => '',
			'role'     => '',
			'page'     => '1',
			'per'      => '10',
		], $request->get_json_params() );

		if ( empty( $queried['raw'] ) )
			return new \WP_Error( 'empty_raw_data', gEditorial\Plugin::invalid( FALSE ) );

		switch ( $queried['target'] ) {

			case 'post':

				if ( empty( $queried['posttype'] ) )
					return RestAPI::getErrorSomethingIsWrong();

				if ( ! is_array( $queried['posttype'] ) )
					$queried['posttype'] = explode( ',', $queried['posttype'] );

				foreach ( $queried['posttype'] as $index => $posttype )
					if ( ! WordPress\PostType::can( $posttype, 'read' ) )
						unset( $queried['posttype'][$index] );

				// again check if any left!
				if ( empty( $queried['posttype'] ) )
					return RestAPI::getErrorNoPermission();

				$response = self::_get_bulk_posts( $queried );
				break;

			default:
				return RestAPI::getErrorSomethingIsWrong();
		}

		return new \WP_REST_Response( $response, 200 );
	}

	private static function _get_bulk_posts( $queried )
	{
		$data   = [];
		$refkey = $queried['refkey'];

		foreach ( $queried['raw'] as $row ) {

			$discovered = apply_filters( sprintf( '%s_linediscovery_data_for_post', static::BASE ),
				NULL,
				$row,
				$queried['posttype'],
				(bool) $queried['insert'],
				$queried['raw']
			);

			if ( is_null( $discovered ) )
				$data[] = [
					$refkey   => $row[$refkey],
					'status'  => 'unavailable',
					'postid'  => 0,
					'code'    => NULL,
					'message' => gEditorial\Plugin::na( FALSE ),
				];

			else if ( TRUE === $discovered )
				$data[] = [
					$refkey   => $row[$refkey],
					'status'  => 'creatable',
					'postid'  => 0,
					'code'    => $queried['posttype'],
					'message' => _x( 'Creatable!', 'Service: Line Discovery', 'geditorial' ),
				];

			else if ( self::isError( $discovered ) )
				$data[] = [
					$refkey   => $row[$refkey],
					'status'  => 'error',
					'postid'  => 0,
					'code'    => $discovered->get_error_code(),
					'message' => $discovered->get_error_message(),
				];

			else if ( $discovered && ( $post = WordPress\Post::get( $discovered ) ) )
				$data[] = [
					$refkey   => $row[$refkey],
					'status'  => 'available',
					'postid'  => $post->ID,
					'code'    => $post->post_type,
					'message' => WordPress\Post::fullTitle( $post ),
				];

			else
				$data[] = [
					$refkey   => $row[$refkey],
					'status'  => 'wrong',
					'postid'  => 0,
					'code'    => sprintf( '%s', $discovered ),
					'message' => gEditorial\Plugin::wrong( FALSE ),
				];
		}

		return $data;
	}
}

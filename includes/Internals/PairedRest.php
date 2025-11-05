<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedRest
{
	// TODO: endpoints for adding paired by identifier


	protected function pairedrest_register_rest_route( $object )
	{
		if ( empty( $object ) || empty( $object->show_in_rest ) )
			return FALSE;

		add_action( 'rest_api_init', function () use ( $object ) {
			register_rest_route( $object->rest_namespace,
				'/'.$object->rest_base.'/(?P<parent>[\d]+)/'.Services\Paired::PAIRED_REST_FROM,
				[
					'args' => [
						'parent' => Services\RestAPI::defineArgument_postid( _x( 'The ID for the parent of the paired.', 'Internal: PairedRest', 'geditorial' ) ),
					],
					[
						'methods'             => \WP_REST_Server::READABLE,
						'callback'            => [ $this, 'pairedrest_get_posts' ],
						'permission_callback' => [ $this, 'pairedrest_get_posts_permissions_check' ],
						// 'args'                => $this->pairedrest_get_collection_params(), // TODO
					],
					[
						'methods'             => \WP_REST_Server::CREATABLE,
						'callback'            => [ $this, 'pairedrest_connect_post' ],
						'permission_callback' => [ $this, 'pairedrest_connect_post_permissions_check' ],
						// 'args'                => $this->pairedrest_get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					],
					[
						'methods'             => \WP_REST_Server::DELETABLE,
						'callback'            => [ $this, 'pairedrest_disconnect_post' ],
						'permission_callback' => [ $this, 'pairedrest_disconnect_post_permissions_check' ],
						// 'args'                => $this->pairedrest_get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
					],
					// 'schema' => [ $this, 'pairedrest_get_public_item_schema' ], // TODO
				]
			);
		}, 999 );
	}

	public function pairedrest_get_posts_permissions_check( $request )
	{
		if ( ! current_user_can( 'read_post', (int) $request['parent'] ) )
			return Services\RestAPI::getErrorForbidden();

		// if ( ! $this->role_can( 'paired' ) ) // FIXME
		// 	return Services\RestAPI::getErrorForbidden();

		return TRUE;
	}

	public function pairedrest_connect_post_permissions_check( $request )
	{
		if ( ! current_user_can( 'edit_post', (int) $request['parent'] ) )
			return Services\RestAPI::getErrorForbidden();

		// if ( ! $this->role_can( 'assign' ) ) // FIXME
		// 	return Services\RestAPI::getErrorForbidden();

		return TRUE;
	}

	public function pairedrest_disconnect_post_permissions_check( $request )
	{
		if ( ! current_user_can( 'edit_post', (int) $request['parent'] ) )
			return Services\RestAPI::getErrorForbidden();

		// if ( ! $this->role_can( 'assign' ) ) // FIXME
		// 	return Services\RestAPI::getErrorForbidden();

		return TRUE;
	}

	public function pairedrest_get_posts( $request )
	{
		if ( ! $parent = WordPress\Post::get( (int) $request['parent'] ) )
			return Services\RestAPI::getErrorSomethingIsWrong();

		if ( ! $posts = $this->paired_all_connected_to( $parent, 'restapi' ) )
			return rest_ensure_response( [] );

		$data = [];

		foreach ( $posts as $post ) {

			$prepped = [
				'id'    => $post->ID,
				'title' => WordPress\Post::title( $post ),
				'link'  => WordPress\Post::link( $post ),
			];

			if ( $filtered = apply_filters( $this->hook_base( 'pairedrest', 'prepped_post' ), $prepped, $post, $parent ) )
				$data[] = (object) $filtered;
		}

		return rest_ensure_response( $data );
	}

	public function pairedrest_connect_post( $request )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return Services\RestAPI::getErrorSomethingIsWrong();

		$raw    = $request->get_json_params();
		$parent = WordPress\Post::get( (int) $request['parent'] );
		$posts  = Core\Arraay::pluck( $raw, 'id' );

		// TODO: use `TermRelations` API
		// $metas = Core\Arraay::pluck( $raw, 'meta', 'post_id' );

		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();

		$result = $this->paired_do_connection( 'store',
			$posts,
			$parent->ID,
			$constants[0],
			$constants[1],
			$this->get_setting( 'multiple_instances' )
		);

		if ( FALSE === $result )
			return Services\RestAPI::getErrorSomethingIsWrong();

		// NOTE: must return the endpoint as `get`
		return $this->pairedrest_get_posts( $request );
	}

	public function pairedrest_disconnect_post( $request )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return Services\RestAPI::getErrorSomethingIsWrong();

		$raw    = $request->get_json_params();
		$parent = WordPress\Post::get( (int) $request['parent'] );
		$posts  = Core\Arraay::pluck( $raw, 'id' );

		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();

		$result = $this->paired_do_connection( 'remove',
			$posts,
			$parent->ID,
			$constants[0],
			$constants[1],
			$this->get_setting( 'multiple_instances' )
		);

		if ( FALSE === $result )
			return Services\RestAPI::getErrorSomethingIsWrong();

		// NOTE: must return the endpoint as `get`
		return $this->pairedrest_get_posts( $request );
	}
}

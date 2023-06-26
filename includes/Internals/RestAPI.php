<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress;

trait RestAPI
{
	protected function restapi_get_namespace()
	{
		return $this->constant( 'restapi_namespace', $this->classs() ).'/'.$this->rest_api_version;
	}

	// FIXME: add extra args
	// @REF: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	protected function restapi_register_route( $route, $methods = 'GET', $suffix = '', $extra = [] )
	{
		$args = [];
		$hook = self::sanitize_hook( $route );

		foreach ( (array) $methods as $method ) {

			$method = strtolower( $method );

			if ( method_exists( $this, 'restapi_'.$hook.'_'.$method.'_callback' ) )
				$callback = [ $this, 'restapi_'.$hook.'_'.$method.'_callback' ];
			else
				continue;

			if ( method_exists( $this, 'restapi_'.$hook.'_'.$method.'_arguments' ) )
				$arguments = call_user_func( [ $this, 'restapi_'.$hook.'_'.$method.'_arguments' ] );

			else if ( array_key_exists( '_'.$method, $extra ) )
				$arguments = $extra['_'.$method];

			else
				$arguments = [];

			if ( method_exists( $this, 'restapi_'.$hook.'_'.$method.'_permission' ) )
				$permission = [ $this, 'restapi_'.$hook.'_'.$method.'_permission' ];
			else
				$permission = [ $this, 'restapi_default_permission_callback' ];

			switch ( $method ) {

				// READABLE   = 'GET'; // Alias for GET transport method
				// CREATABLE  = 'POST'; // Alias for POST transport method
				// EDITABLE   = 'POST, PUT, PATCH'; // Alias for POST, PUT, PATCH transport methods together
				// DELETABLE  = 'DELETE'; // Alias for DELETE transport method
				// ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE'; // Alias for GET, POST, PUT, PATCH & DELETE transport methods together

				case 'post':

					$args[] = [
						'methods'             => \WP_REST_Server::CREATABLE,
						'callback'            => $callback,
						'args'                => $arguments,
						'permission_callback' => $permission,
					];

					break;

				case 'delete':

					$args[] = [
						'methods'             => \WP_REST_Server::DELETABLE,
						'callback'            => $callback,
						'args'                => $arguments,
						'permission_callback' => $permission,
					];

					break;

				case 'get':

					$args[] = [
						'methods'             => \WP_REST_Server::READABLE,
						'callback'            => $callback,
						'args'                => $arguments,
						'permission_callback' => $permission,
					];

					break;
			}
		}

		return register_rest_route(
			$this->restapi_get_namespace(),
			'/'.$route.( $suffix ? '/'.$suffix : '' ),
			$args
		);
	}

	protected function restapi_get_error_rest_forbidden()
	{
		return new \WP_Error(
			'rest_forbidden',
			esc_html_x( 'OMG you can not view private data.', 'Error: Rest Forbidden', 'geditorial' ),
			[
				'status' => 401,
			]
		);
	}

	// 'Authorization: Basic '. base64_encode("user:password")
	// @REF: https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/#permissions-callback
	public function restapi_default_permission_callback( $request )
	{
		if ( defined( 'GEDITORIAL_DISABLE_AUTH' ) && GEDITORIAL_DISABLE_AUTH )
			return TRUE;

		if ( ! current_user_can( 'read' ) && ! WordPress\User::isSuperAdmin() )
			return $this->restapi_get_error_rest_forbidden();

		return TRUE;
	}
}

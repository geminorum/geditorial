<?php namespace geminorum\gEditorial\Modules\Connected;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Connected extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;
	protected $textdomain_frontend  = FALSE;

	public static function module()
	{
		return [
			'name'     => 'connected',
			'title'    => _x( 'Connected', 'Modules: Connected', 'geditorial' ),
			'desc'     => _x( 'Posts-to-Posts Extended', 'Modules: Connected', 'geditorial' ),
			'icon'     => 'controls-repeat',
			'disabled' => defined( 'P2P_PLUGIN_VERSION' ) ? FALSE : _x( 'Needs Posts-to-Posts', 'Modules: Connected', 'geditorial' ),
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'admin_column',
					'title'       => _x( 'Admin Column', 'Settings', 'geditorial-connected' ),
					'description' => _x( 'Displays connected column on admin list screen.', 'Settings', 'geditorial-connected' ),
				],
				[
					'field'       => 'admin_box_context',
					'title'       => _x( 'Admin Box Context', 'Setting Title', 'geditorial-connected' ),
					'description' => _x( 'Where to display the connected box on admin post edit screen.', 'Settings', 'geditorial-connected' ),
					'values'      => [
						_x( 'Normal', 'Settings', 'geditorial-connected' ),
						_x( 'Advanced', 'Settings', 'geditorial-connected' ),
					],
				],
				[
					'field'       => 'duplicate_connections',
					'title'       => _x( 'Duplicate Connections', 'Settings', 'geditorial-connected' ),
					'description' => _x( 'Displays multiple connections between connecties.', 'Settings', 'geditorial-connected' ),
				],
				[
					'field'       => 'field_desc',
					'title'       => _x( 'Description Field', 'Settings', 'geditorial-connected' ),
					'description' => _x( 'Displays description field on connected metabox.', 'Settings', 'geditorial-connected' ),
				],
				[
					'field'       => 'string_desc',
					'type'        => 'text',
					'title'       => _x( 'Description Title', 'Settings', 'geditorial-connected' ),
					'description' => _x( 'Appears as description column title on connected metabox.', 'Settings', 'geditorial-connected' ),
					'default'     => _x( 'Description', 'Settings', 'geditorial-connected' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'p2p_name' => 'connected_posts',
		];
	}

	public function p2p_init()
	{
		$posttypes = $this->posttypes();

		if ( empty( $posttypes ) )
			return;

		$args = [
			'name'       => $this->constant( 'p2p_name' ),
			'from'       => $posttypes,
			'to'         => $posttypes,
			'reciprocal' => TRUE,

			'duplicate_connections' => $this->get_setting( 'duplicate_connections', FALSE ),
			'admin_column'          => $this->get_setting( 'admin_column', FALSE ) ? 'any' : FALSE,
			'admin_box'             => [
				'context' => $this->get_setting( 'admin_box_context', FALSE ) ? 'advanced' : 'normal',
			],

			'title'     => _x( 'Connected', 'Metabox Title', 'geditorial-connected' ),
			'to_labels' => [
				'singular_name' => _x( 'Connectie', 'Label', 'geditorial-connected' ),
				'search_items'  => _x( 'Search Connecties', 'Label', 'geditorial-connected' ),
				'not_found'     => _x( 'No Connecties found.', 'Label', 'geditorial-connected' ),
				'create'        => _x( 'Connect to a connectie', 'Label', 'geditorial-connected' ),
			],
		];

		if ( $this->get_setting( 'field_desc', FALSE ) )
			$args['fields']['desc'] = [
				'title' => $this->get_setting( 'string_desc', _x( 'Description', 'Field Title', 'geditorial-connected' ) ),
				'type'  => 'text',
			];

		p2p_register_connection_type( $args );
	}
}

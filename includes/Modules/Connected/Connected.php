<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Connected extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

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
					'title'       => _x( 'Admin Column', 'Modules: Connected: Settings', 'geditorial' ),
					'description' => _x( 'Displays connected column on admin list screen.', 'Modules: Connected: Settings', 'geditorial' ),
				],
				[
					'field'       => 'admin_box_context',
					'title'       => _x( 'Admin Box Context', 'Modules: Connected: Setting Title', 'geditorial' ),
					'description' => _x( 'Where to display the connected box on admin post edit screen.', 'Modules: Connected: Settings', 'geditorial' ),
					'values'      => [
						_x( 'Normal', 'Modules: Connected: Settings', 'geditorial' ),
						_x( 'Advanced', 'Modules: Connected: Settings', 'geditorial' ),
					],
				],
				[
					'field'       => 'duplicate_connections',
					'title'       => _x( 'Duplicate Connections', 'Modules: Connected: Settings', 'geditorial' ),
					'description' => _x( 'Displays multiple connections between connecties.', 'Modules: Connected: Settings', 'geditorial' ),
				],
				[
					'field'       => 'field_desc',
					'title'       => _x( 'Description Field', 'Modules: Connected: Settings', 'geditorial' ),
					'description' => _x( 'Displays description field on connected metabox.', 'Modules: Connected: Settings', 'geditorial' ),
				],
				[
					'field'       => 'string_desc',
					'type'        => 'text',
					'title'       => _x( 'Description Title', 'Modules: Connected: Settings', 'geditorial' ),
					'description' => _x( 'Appears as description column title on connected metabox.', 'Modules: Connected: Settings', 'geditorial' ),
					'default'     => _x( 'Description', 'Modules: Connected: Settings', 'geditorial' ),
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

			'title'     => _x( 'Connected', 'Modules: Connected', 'geditorial' ),
			'to_labels' => [
				'singular_name' => _x( 'Connectie', 'Modules: Connected', 'geditorial' ),
				'search_items'  => _x( 'Search Connecties', 'Modules: Connected', 'geditorial' ),
				'not_found'     => _x( 'No Connecties found.', 'Modules: Connected', 'geditorial' ),
				'create'        => _x( 'Connect to a connectie', 'Modules: Connected', 'geditorial' ),
			],
		];

		if ( $this->get_setting( 'field_desc', FALSE ) )
			$args['fields']['desc'] = [
				'title' => $this->get_setting( 'string_desc', _x( 'Description', 'Modules: Connected', 'geditorial' ) ),
				'type'  => 'text',
			];

		p2p_register_connection_type( $args );
	}
}

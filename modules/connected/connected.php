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
			'title'    => _x( 'Connected', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Posts-to-Posts Extended', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'controls-repeat',
			'disabled' => defined( 'P2P_PLUGIN_VERSION' ) ? FALSE : _x( 'Needs Posts-to-Posts', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'admin_column',
					'title'       => _x( 'Admin Column', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays connected column on admin list screen.', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'admin_box_context',
					'title'       => _x( 'Admin Box Context', 'Modules: Connected: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Where to display the connected box on admin post edit screen.', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					'values'      => [
						_x( 'Normal', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
						_x( 'Advanced', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					],
				],
				[
					'field'       => 'duplicate_connections',
					'title'       => _x( 'Duplicate Connections', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays multiple connections between connecties.', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'field_desc',
					'title'       => _x( 'Description Field', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays description field on connected metabox.', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'string_desc',
					'type'        => 'text',
					'title'       => _x( 'Description Title', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Appears as description column title on connected metabox.', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Description', 'Modules: Connected: Settings', GEDITORIAL_TEXTDOMAIN ),
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

			'title'     => _x( 'Connected', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
			'to_labels' => [
				'singular_name' => _x( 'Connectie', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
				'search_items'  => _x( 'Search Connecties', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
				'not_found'     => _x( 'No Connecties found.', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
				'create'        => _x( 'Connect to a connectie', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( $this->get_setting( 'field_desc', FALSE ) )
			$args['fields']['desc'] = [
				'title' => $this->get_setting( 'string_desc', _x( 'Description', 'Modules: Connected', GEDITORIAL_TEXTDOMAIN ) ),
				'type'  => 'text',
			];

		p2p_register_connection_type( $args );
	}
}

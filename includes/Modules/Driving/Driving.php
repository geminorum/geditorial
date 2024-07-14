<?php namespace geminorum\gEditorial\Modules\Driving;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\WordPress;

class Driving extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\PostMeta;
	use Internals\RestAPI;
	use Internals\SubContents;

	public static function module()
	{
		return [
			'name'     => 'driving',
			'title'    => _x( 'Driving', 'Modules: Driving', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Drivers Management', 'Modules: Driving', 'geditorial-admin' ),
			'icon'     => 'car',
			'access'   => 'beta',
			'keywords' => [
				'car',
				'vehicle',
				'subcontent',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, Core\Arraay::stripByKeys(
					$this->subcontent_define_fields(),
					$this->subcontent_get_required_fields( 'settings' )
				) ],
			],
			'_roles' => [
				'reports_roles' => [ NULL, $roles ],
				'assign_roles'  => [ NULL, $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_supports' => [
				'shortcode_support',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'registered-vehicles',
			'subcontent_type'   => 'registered_vehicles',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'registered-vehicles',

			'term_empty_subcontent_data' => 'vehicle-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'platenumber' => _x( 'Plate Number', 'Field Label: `platenumber`', 'geditorial-driving' ),
					'carmodel'    => _x( 'Car Model', 'Field Label: `carmodel`', 'geditorial-driving' ),
					'color'       => _x( 'Color', 'Field Label: `color`', 'geditorial-driving' ),
					'fullname'    => _x( 'Owner', 'Field Label: `fullname`', 'geditorial-driving' ),
					'relation'    => _x( 'Relation', 'Field Label: `relation`', 'geditorial-driving' ),
					'identity'    => _x( 'Identity', 'Field Label: `identity`', 'geditorial-driving' ),
					'contact'     => _x( 'Contact', 'Field Label: `contact`', 'geditorial-driving' ),
					'year'        => _x( 'Year', 'Field Label: `year`', 'geditorial-driving' ),
					'vin'         => _x( 'VIN', 'Field Label: `vin`', 'geditorial-driving' ),
					'desc'        => _x( 'Description', 'Field Label: `desc`', 'geditorial-driving' ),
				],
			],
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no vehicle information available!', 'Notice', 'geditorial-driving' ),
			'noaccess' => _x( 'You have not necessary permission to manage the vehicles data.', 'Notice', 'geditorial-driving' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Vehicles', 'MetaBox Title', 'geditorial-driving' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-driving' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbutton_title' => _x( 'Vehicles of %1$s', 'Button Title', 'geditorial-driving' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Vehicles of %2$s', 'Button Text', 'geditorial-driving' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'rowaction_title' => _x( 'Vehicles of %1$s', 'Action Title', 'geditorial-driving' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'rowaction_text'  => _x( 'Vehicles', 'Action Text', 'geditorial-driving' ),

			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'columnrow_title' => _x( 'Vehicles of %1$s', 'Row Title', 'geditorial-driving' ),
			/* translators: %1$s: icon markup, %2$s: posttype singular name */
			'columnrow_text'  => _x( 'Vehicles', 'Row Text', 'geditorial-driving' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'vehicle_model' => [
						'title'       => _x( 'Vehicle Model', 'Field Title', 'geditorial-driving' ),
						'description' => _x( 'Registered Vehicle Model', 'Field Description', 'geditorial-driving' ),
						'order'       => 300,
					],
					'vehicle_color' => [
						'title'       => _x( 'Vehicle Color', 'Field Title', 'geditorial-driving' ),
						'description' => _x( 'Registered Vehicle Color', 'Field Description', 'geditorial-driving' ),
						'order'       => 300,
					],
					'vehicle_year' => [
						'title'       => _x( 'Vehicle Year', 'Field Title', 'geditorial-driving' ),
						'description' => _x( 'Registered Vehicle Year', 'Field Description', 'geditorial-driving' ),
						'type'        => 'year',
						'order'       => 300,
					],
					'vehicle_plate' => [
						'title'       => _x( 'Vehicle Plate', 'Field Title', 'geditorial-driving' ),
						'description' => _x( 'Registered Vehicle Plate', 'Field Description', 'geditorial-driving' ),
						'type'        => 'code',
						'order'       => 300,
					],
					'vin' => [
						'title'       => _x( 'VIN', 'Field Title', 'geditorial-driving' ),
						'description' => _x( 'Vehicle Identification Number', 'Field Description', 'geditorial-driving' ),
						'type'        => 'vin',
						'order'       => 300,
					],
				],
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content'      => 'desc',         // `text`
			'comment_author'       => 'fullname',     // `tinytext`
			'comment_author_url'   => 'platenumber',  // `varchar(200)`
			'comment_author_email' => 'contact',      // `varchar(100)`
			'comment_author_IP'    => 'year',         // `varchar(100)`
			'comment_agent'        => 'vin',          // `varchar(255)`
			'comment_karma'        => 'ref',          // `int(11)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'identity' => 'identity',
			'relation' => 'relation',
			'carmodel' => 'carmodel',
			'color'    => 'color',
		];
	}

	protected function subcontent_define_searchable_fields()
	{
		if ( $human = gEditorial()->constant( 'personage', 'primary_posttype' ) )
			return [ 'fullname' => [ $human ] ];

		return [];
	}

	protected function subcontent_define_hidden_fields()
	{
		return [
			'ref',
		];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'vin',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'carmodel',
			'color',
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter_module( 'audit', 'auto_audit_save_post', 5 );
		$this->register_shortcode( 'main_shortcode' );

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();
		$this->filter_module( 'personage', 'editform_meta_summary', 2, 20 );
	}

	public function current_screen( $screen )
	{
		if ( $this->in_setting( $screen->post_type, 'subcontent_posttypes' ) ) {

			if ( 'post' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) )
					$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

				$this->subcontent_do_enqueue_asset_js( $screen );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

					if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type ) )
						$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18 );

					Scripts::enqueueColorBox();
				}
			}
		}
	}

	public function tweaks_column_row( $post, $before, $after )
	{
		printf( $before, '-vehicle-grid' );

			echo $this->get_column_icon( FALSE, NULL, NULL, $post->post_type );

			echo $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'columnrow',
			] );

			if ( $count = $this->subcontent_get_data_count( $post ) )
				printf( ' <span class="-counted">(%s)</span>', $this->nooped_count( 'vehicle', $count ) );

		echo $after;
	}

	protected function rowaction_get_mainlink_for_post( $post )
	{
		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'rowaction',
			] ),
		];
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->subcontent_render_metabox_data_grid( $object, $context );

		if ( $this->role_can( 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $object, [
				'context' => 'mainbutton',
				'target'  => 'grid',
			] ), 'field-wrap -buttons' );

		else
			echo $this->subcontent_get_noaccess_notice();
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'framepage', 'read' );
	}

	public function load_submenu_adminpage( $context = 'framepage' )
	{
		$this->_load_submenu_adminpage( $context );
		$this->subcontent_do_enqueue_app( 'vehicle-grid' );
	}

	public function render_framepage_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'vehicle-grid',
			'framepage',
			/* translators: %s: post title */
			_x( 'Vehicle Grid for %s', 'Page Title', 'geditorial-driving' ),
			/* translators: %s: post title */
			_x( 'Vehicle Overview for %s', 'Page Title', 'geditorial-driving' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Helper::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Vehicle Data', 'Default Term: Audit', 'geditorial-driving' ),
		] ) : $terms;
	}

	public function audit_auto_audit_save_post( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->in_setting( $post->post_type, 'subcontent_posttypes' ) )
			return $terms;

		if ( $exists = term_exists( $this->constant( 'term_empty_subcontent_data' ), $taxonomy ) ) {

			if ( $this->subcontent_get_data_count( $post ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}

	public function personage_editform_meta_summary( $fields, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $fields;

		$fields['vehicle_model'] = NULL;
		$fields['vehicle_color'] = NULL;
		// $fields['vehicle_year']  = NULL;
		$fields['vehicle_plate'] = NULL;
		// $fields['vin']           = NULL;

		return $fields;
	}
}

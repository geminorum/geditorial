<?php namespace geminorum\gEditorial\Modules\Physical;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

// @SEE: `Evaluation` Module

class Physical extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\CoreAdmin;
	use Internals\CoreRowActions;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\PostTypeFields;
	use Internals\RestAPI;
	use Internals\SubContents;

	public static function module()
	{
		return [
			'name'     => 'physical',
			'title'    => _x( 'Physical', 'Modules: Physical', 'geditorial-admin' ),
			'desc'     => _x( 'Physical Examination Data', 'Modules: Physical', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'lungs-fill' ],
			'access'   => 'beta',
			'keywords' => [
				'grade',
				'sport',
				'has-shortcodes',
				'subcontent',
				'tabmodule',
				'crm-feature',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, $this->subcontent_get_fields_for_settings() ],
				'reports_roles'        => [ NULL, $roles ],
				'assign_roles'         => [ NULL, $roles ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'tabs_support',
				'tab_title'    => [ NULL, $this->strings['frontend']['tab_title'] ],
				'tab_priority' => [ NULL, 80 ],
			],
			'_supports' => [
				'shortcode_support',
			],
			'_units' => [
				'units_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_namespace' => 'physicals-data',
			'subcontent_type'   => 'physicals_data',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'physicals-data',

			'term_empty_subcontent_data' => 'physicals-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'fields' => [
				'subcontent' => [
					'label'    => _x( 'Event', 'Field Label: `label`', 'geditorial-physical' ),
					'grade'    => _x( 'Grade', 'Field Label: `grade`', 'geditorial-physical' ),
					'age'      => _x( 'Age', 'Field Label: `age`', 'geditorial-physical' ),
					'stature'  => _x( 'Stature', 'Field Label: `stature`', 'geditorial-physical' ),
					'mass'     => _x( 'Mass', 'Field Label: `mass`', 'geditorial-physical' ),
					'date'     => _x( 'Date', 'Field Label: `date`', 'geditorial-physical' ),
					'location' => _x( 'Venue', 'Field Label: `location`', 'geditorial-physical' ),
					'people'   => _x( 'Instructors', 'Field Label: `location`', 'geditorial-physical' ),
					'desc'     => _x( 'Description', 'Field Label: `desc`', 'geditorial-physical' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Physical Examinations', 'Tab Title', 'geditorial-physical' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no physical examination available!', 'Notice', 'geditorial-physical' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage this examination data.', 'Notice', 'geditorial-physical' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Physicals', 'MetaBox Title', 'geditorial-physical' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-physical' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Physical Examinations of %1$s', 'Button Title', 'geditorial-physical' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Physical Examinations of %2$s', 'Button Text', 'geditorial-physical' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Physical Examinations of %1$s', 'Action Title', 'geditorial-physical' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Physicals', 'Action Text', 'geditorial-physical' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Physical Examinations of %1$s', 'Row Title', 'geditorial-physical' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Physicals', 'Row Text', 'geditorial-physical' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'units' => [
				'_supported' => [
					'mass_in_kg' => [
						'title'       => _x( 'Mass', 'Field Title', 'geditorial-physical' ),
						'description' => _x( 'Body Mass in Kilograms', 'Field Description', 'geditorial-physical' ),
						'type'        => 'kilogram',
						'data_unit'   => 'kilogram',
						'icon'        => 'image-filter',
						'order'       => 100,
					],
					'stature_in_cm' => [
						'title'       => _x( 'Stature', 'Field Title', 'geditorial-physical' ),
						'description' => _x( 'Body Stature in Centimetres', 'Field Description', 'geditorial-physical' ),
						'type'        => 'centimetre',
						'data_unit'   => 'centimetre',
						'icon'        => 'sort',
						'order'       => 100,
					],
				],
			]
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'location',   // `tinytext`
			'comment_author_url'   => 'mass',       // `varchar(200)`
			'comment_author_email' => 'grade',      // `varchar(100)`
			'comment_author_IP'    => 'date',       // `varchar(100)`
		] );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			'age'     => 'age',
			'stature' => 'stature',
			'people'  => 'people',
			'postid'  => '_post_ref',
		];
	}

	protected function subcontent_define_searchable_fields()
	{
		$posttypes = Core\Arraay::prepString( [
			gEditorial()->constant( 'trained', 'primary_posttype' ),
			gEditorial()->constant( 'ranged', 'primary_posttype' ),
			gEditorial()->constant( 'listed', 'primary_posttype' ),
			gEditorial()->constant( 'programmed', 'primary_posttype' ),
		] );

		if ( count( $posttypes ) )
			return [ 'label' => $posttypes ];

		return [];
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			'date',
		];
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'grade',
			'label',
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->filter_module( 'audit', 'auto_audit_save_post', 5, 12, 'subcontent' );
		$this->register_shortcode( 'main_shortcode' );
		$this->subcontent_hook__post_tabs();

		if ( ! is_admin() )
			return;

		$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );
	}

	public function units_init()
	{
		$this->add_posttype_fields_supported( $this->get_setting_posttypes( 'units' ), NULL, TRUE, 'units' );

		$this->action_module( 'pointers', 'post', 6, 500 );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->base, [ 'edit', 'post' ], TRUE ) ) {

			if ( $this->in_setting_posttypes( $screen->post_type, 'subcontent' ) ) {

				if ( 'post' == $screen->base ) {

					if ( $this->role_can( [ 'reports', 'assign' ] ) )
						$this->_hook_general_supportedbox( $screen, NULL, 'advanced', 'low', '-subcontent-grid-metabox' );

					$this->subcontent_do_enqueue_asset_js( $screen );

				} else if ( 'edit' == $screen->base ) {

					if ( $this->role_can( [ 'reports', 'assign' ] ) ) {

						if ( ! $this->rowactions__hook_mainlink_for_post( $screen->post_type, 18, 'subcontent' ) )
							$this->coreadmin__hook_tweaks_column_row( $screen->post_type, 18, 'subcontent' );

						gEditorial\Scripts::enqueueColorBox();
					}
				}
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->role_can( [ 'assign', 'reports' ] ) )
			$this->_hook_submenu_adminpage( 'overview', 'exist' );
	}

	public function load_submenu_adminpage()
	{
		$this->_load_submenu_adminpage( 'overview' );
		$this->subcontent_do_enqueue_app();
	}

	public function render_submenu_adminpage()
	{
		$this->subcontent_do_render_iframe_content(
			'overview',
			/* translators: `%s`: post title */
			_x( 'Athletics Grid for %s', 'Page Title', 'geditorial-physical' ),
			/* translators: `%s`: post title */
			_x( 'Athletics Overview for %s', 'Page Title', 'geditorial-physical' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->subcontent_do_render_supportedbox_content( $object, $context ?? 'supportedbox' );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return $this->subcontent_do_main_shortcode( $atts, $content, $tag );
	}

	public function audit_get_default_terms( $terms, $taxonomy )
	{
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Athletics Data', 'Default Term: Audit', 'geditorial-physical' ),
		] ) : $terms;
	}

	// TODO: append data to `Papered`
	public function pointers_post( $post, $before, $after, $new_post, $context, $screen )
	{
		if ( $new_post )
			return;

		if ( ! $this->in_setting_posttypes( $post->post_type, 'units' ) )
			return;

		$fields = Services\PostTypeFields::getEnabled( $post->post_type, 'units' );

		if ( ! array_key_exists( 'mass_in_kg', $fields ) || ! array_key_exists( 'stature_in_cm', $fields ) )
			return;

		if ( ! $mass = gEditorial\Template::getMetaFieldRaw( 'mass_in_kg', $post->ID, 'units' ) )
			return;

		if ( ! $stature = gEditorial\Template::getMetaFieldRaw( 'stature_in_cm', $post->ID, 'units' ) )
			return;

		// FIXME: passing age/gender
		if ( ! $bmi = ModuleHelper::calculateBMI( $mass, $stature ) )
			return;

		printf( $before, '-bmi-suammry' );
			// TODO: hint the data!
			printf( '%s <span class="-bmi -bmi-%s -text-%s" title="%s">%s</span>',
				$this->get_column_icon(),
				$bmi['result'],
				$bmi['state'],
				$bmi['message'],
				$bmi['report']
			);
		echo $after;

		$this->actions( 'pointers_post_after', $post, $bmi, $mass, $stature, $before, $after );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', TRUE );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->subcontent_reports_render_table( $uri, $sub, 'reports', _x( 'Overview of the Physical Examinations', 'Header', 'geditorial-physical' ) ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}

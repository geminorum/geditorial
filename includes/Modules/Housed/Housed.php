<?php namespace geminorum\gEditorial\Modules\Housed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Housed extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreRowActions;
	use Internals\DashboardSummary;
	use Internals\FramePage;
	use Internals\MetaBoxSupported;
	use Internals\RestAPI;
	use Internals\SubContents;
	use Internals\TaxonomyOverview;
	use Internals\TemplateTaxonomy;

	public static function module()
	{
		return [
			'name'     => 'housed',
			'title'    => _x( 'Housed', 'Modules: Housed', 'geditorial-admin' ),
			'desc'     => _x( 'Content Housings', 'Modules: Housed', 'geditorial-admin' ),
			'icon'     => 'admin-home',
			'access'   => 'beta',
			'keywords' => [
				'housing',
				'taxmodule',
				'subcontent',
				'tabmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$roles = $this->get_settings_default_roles();
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'_subcontent' => [
				'subcontent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
				'subcontent_fields'    => [ NULL, $this->subcontent_get_fields_for_settings() ],
				'reports_roles'        => [ NULL, $roles ],
				'assign_roles'         => [ NULL, $roles ],
			],
			'posttypes_option' => 'posttypes_option',
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard'       => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_editpost' => [
				'admin_rowactions',
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
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
			'main_taxonomy'     => 'housing',
			'restapi_namespace' => 'visiting-data',
			'subcontent_type'   => 'visiting_data',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'visiting-data',

			'term_empty_subcontent_data' => 'visiting-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Housing Condition', 'Housing Conditions', 'geditorial-housed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Housing Conditions', 'Label: `menu_name`', 'geditorial-housed' ),
					'extended_label'       => _x( 'Housing Conditions', 'Label: `extended_label`', 'geditorial-housed' ),
					'show_option_all'      => _x( 'Housings', 'Label: `show_option_all`', 'geditorial-housed' ),
					'show_option_no_items' => _x( '(Undefined)', 'Label: `show_option_no_items`', 'geditorial-housed' ),
				],
			],
			'fields' => [
				'subcontent' => [
					'label'    => _x( 'Event', 'Field Label: `label`', 'geditorial-housed' ),
					'date'     => _x( 'Date', 'Field Label: `date`', 'geditorial-housed' ),
					'people'   => _x( 'Attendees', 'Field Label: `location`', 'geditorial-housed' ),
					'address'  => _x( 'Address', 'Field Label: `address`', 'geditorial-housed' ),
					'location' => _x( 'Venue', 'Field Label: `location`', 'geditorial-housed' ),
					'desc'     => _x( 'Description', 'Field Label: `desc`', 'geditorial-housed' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Visiting', 'Tab Title', 'geditorial-housed' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no visiting information available!', 'Notice', 'geditorial-housed' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage this visiting data.', 'Notice', 'geditorial-housed' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports &ldquo;Housing Conditions&rdquo; for the selected post-types.', 'Setting Description', 'geditorial-housed' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Visiting', 'MetaBox Title', 'geditorial-housed' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-housed' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Visiting of %1$s', 'Button Title', 'geditorial-housed' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Visiting of %2$s', 'Button Text', 'geditorial-housed' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Visiting of %1$s', 'Action Title', 'geditorial-housed' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Visiting', 'Action Text', 'geditorial-housed' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Visiting of %1$s', 'Row Title', 'geditorial-housed' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Visiting', 'Row Text', 'geditorial-housed' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'units' => [
				'_supported' => [
					'area_in_meter_squared' => [
						'title'       => _x( 'Housing Area', 'Field Title', 'geditorial-housed' ),
						'description' => _x( 'Housing area in meter squared', 'Field Description', 'geditorial-housed' ),
						'type'        => 'area',
						'data_unit'   => 'meter_squared',
						'order'       => 100,
					],
				],
			]
		];
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'ownership' => _x( 'Ownership', 'Main Taxonomy: Default Term', 'geditorial-housed' ),
				'rental'    => _x( 'Rental', 'Main Taxonomy: Default Term', 'geditorial-housed' ),
				'parental'  => _x( 'Parental', 'Main Taxonomy: Default Term', 'geditorial-housed' ),
				'dormitory' => _x( 'Dormitory', 'Main Taxonomy: Default Term', 'geditorial-housed' ),
				'homeless'  => _x( 'Homeless', 'Main Taxonomy: Default Term', 'geditorial-housed' ),
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'address',    // `tinytext`
			'comment_author_url'   => 'people',     // `varchar(200)`
			'comment_author_email' => 'location',   // `varchar(100)`
			'comment_author_IP'    => 'date',       // `varchar(100)`
		] );
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

	protected function subcontent_define_required_fields()
	{
		return [
			'label',
			'date',
		];
	}

	public function after_setup_theme()
	{
		$this->filter_module( 'audit', 'get_default_terms', 2 );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'single_selected' => TRUE,
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );
		$this->hook_dashboardsummary_paired_post_summaries( 'main_taxonomy' );
		$this->bulkexports__hook_tabloid_term_assigned( 'main_taxonomy' );

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
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( in_array( $screen->base, [ 'edit', 'post' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'edit' === $screen->base ) {

					if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
						$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

				} else if ( 'post' === $screen->base ) {

					$this->hook_taxonomy_metabox_mainbox(
						'main_taxonomy',
						$screen->post_type,
						'__singleselect_restricted_terms_callback'
					);
				}
			}

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

		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
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
			_x( 'Visiting Grid for %s', 'Page Title', 'geditorial-housed' ),
			/* translators: `%s`: post title */
			_x( 'Visiting Overview for %s', 'Page Title', 'geditorial-housed' )
		);
	}

	public function setup_restapi()
	{
		$this->subcontent_restapi_register_routes();
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
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
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Visiting Data', 'Default Term: Audit', 'geditorial-housed' ),
		] ) : $terms;
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->taxonomy_overview_render_table( 'main_taxonomy', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}

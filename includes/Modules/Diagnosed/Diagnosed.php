<?php namespace geminorum\gEditorial\Modules\Diagnosed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Diagnosed extends gEditorial\Module
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

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'diagnosed',
			'title'    => _x( 'Diagnosed', 'Modules: Diagnosed', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Diagnosis', 'Modules: Diagnosed', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'clipboard2-pulse-fill' ],
			'access'   => 'beta',
			'keywords' => [
				'diagnosis',
				'medical',
				'taxmodule',
				'subcontent',
				'tabmodule',
				'crm-feature',
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
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
				'dashboard_widgets',
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editpost' => [
				'metabox_advanced',
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
				'tabs_support',
				'tab_title'    => [ NULL, $this->strings['frontend']['tab_title'] ],
				'tab_priority' => [ NULL, 80 ],
			],
			'_constants' => [
				'main_taxonomy_constant'  => [ NULL, 'diagnosis' ],
				'main_shortcode_constant' => [ NULL, 'medical-records' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'diagnosis',

			'restapi_namespace' => 'medical-records',
			'subcontent_type'   => 'family_record',
			'subcontent_status' => 'private',
			'main_shortcode'    => 'medical-records',

			'term_empty_subcontent_data' => 'medical-data-empty',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Diagnosis', 'Diagnoses', 'geditorial-diagnosed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Diagnosis Definitions', 'Label: `menu_name`', 'geditorial-diagnosed' ),
					'extended_label'       => _x( 'Diagnosis Definitions', 'Label: `extended_label`', 'geditorial-diagnosed' ),
					'show_option_all'      => _x( 'Diagnoses', 'Label: `show_option_all`', 'geditorial-diagnosed' ),
					'show_option_no_items' => _x( '(Undiagnosed)', 'Label: `show_option_no_items`', 'geditorial-diagnosed' ),
				],
			],
			'fields' => [
				'subcontent' => [
					'label'      => _x( 'Subject', 'Field Label: `label`', 'geditorial-diagnosed' ),
					'age'        => _x( 'Age', 'Field Label: `age`', 'geditorial-diagnosed' ),
					'datestring' => _x( 'Date', 'Field Label: `datestring`', 'geditorial-diagnosed' ),
					'location'   => _x( 'Location', 'Field Label: `location`', 'geditorial-diagnosed' ),
					'people'     => _x( 'Doctors', 'Field Label: `location`', 'geditorial-diagnosed' ),
					'desc'       => _x( 'Description', 'Field Label: `desc`', 'geditorial-diagnosed' ),
				],
			],
		];

		$strings['frontend'] = [
			'tab_title' => _x( 'Medical', 'Tab Title', 'geditorial-diagnosed' ),
		];

		$strings['notices'] = [
			'empty'    => _x( 'There is no medical information available!', 'Notice', 'geditorial-diagnosed' ),
			'noaccess' => _x( 'You do not have the necessary permission to manage this medical data.', 'Notice', 'geditorial-diagnosed' ),
		];

		if ( ! is_admin() )
			return $strings;

		$strings['settings'] = [
			'post_types_after' => _x( 'Supports medical fields for the selected post-types.', 'Setting Description', 'geditorial-diagnosed' ),
		];

		$strings['metabox'] = [
			'supportedbox_title'  => _x( 'Medical', 'MetaBox Title', 'geditorial-diagnosed' ),
			// 'metabox_action' => _x( 'Directory', 'MetaBox Action', 'geditorial-diagnosed' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Medical Records of %1$s', 'Button Title', 'geditorial-diagnosed' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage the Medical Records of %2$s', 'Button Text', 'geditorial-diagnosed' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'rowaction_title' => _x( 'Medical Records of %1$s', 'Action Title', 'geditorial-diagnosed' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'rowaction_text'  => _x( 'Medical', 'Action Text', 'geditorial-diagnosed' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'columnrow_title' => _x( 'Medical Records of %1$s', 'Row Title', 'geditorial-diagnosed' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'columnrow_text'  => _x( 'Medical', 'Row Text', 'geditorial-diagnosed' ),
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'sciatica' => _x( 'Sciatica', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'diabetes' => _x( 'Diabetes', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'glasses'  => _x( 'Glasses', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'migraine' => _x( 'Migraine', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'epilepsy' => _x( 'Epilepsy', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'gastro'   => _x( 'Gastro', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'kidney'   => _x( 'Kidney', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'liver'    => _x( 'Liver', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'heart'    => _x( 'Heart', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'knee'     => _x( 'Knee', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'ankle'    => _x( 'Ankle', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
			],
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return array_merge( $this->subcontent_base_data_mapping(), [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)`

			'comment_author'       => 'location',     // `tinytext`
			'comment_author_url'   => 'age',          // `varchar(200)`
			'comment_author_email' => 'people',       // `varchar(100)`
			'comment_author_IP'    => 'datestring',   // `varchar(100)`
		] );
	}

	protected function subcontent_define_required_fields()
	{
		return [
			'age',
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

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
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

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( in_array( $screen->base, [ 'edit', 'post' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'edit' == $screen->base ) {

					if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
						$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

				} else if ( 'post' === $screen->base ) {

					if ( ! $this->get_setting( 'metabox_advanced' ) )
						$this->hook_taxonomy_metabox_mainbox(
							'main_taxonomy',
							$screen->post_type,
							$this->get_setting( 'selectmultiple_term', TRUE )
								? '__checklist_restricted_terms_callback'
								: '__singleselect_restricted_terms_callback'
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

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->subcontent_do_render_supportedbox_content( $object, $context ?? 'supportedbox' );
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
			_x( 'Medical Grid for %s', 'Page Title', 'geditorial-diagnosed' ),
			/* translators: `%s`: post title */
			_x( 'Medical Overview for %s', 'Page Title', 'geditorial-diagnosed' )
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
		return Services\Modulation::isTaxonomyAudit( $taxonomy ) ? array_merge( $terms, [
			$this->constant( 'term_empty_subcontent_data' ) => _x( 'Empty Medical Data', 'Default Term: Audit', 'geditorial-diagnosed' ),
		] ) : $terms;
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

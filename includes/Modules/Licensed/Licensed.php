<?php namespace geminorum\gEditorial\Modules\Licensed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Licensed extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\MetaBoxSupported;
	use Internals\TaxonomyOverview;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'licensed',
			'title'    => _x( 'Licensed', 'Modules: Licensed', 'geditorial-admin' ),
			'desc'     => _x( 'Driver Licence Management', 'Modules: Licensed', 'geditorial-admin' ),
			'icon'     => 'id',
			'access'   => 'beta',
			'keywords' => [
				'car',
				'vehicle',
				'taxmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
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
			'_editpost' => [
				'metabox_advanced',
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_editlist' => [
				'admin_restrict',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'driving_licence',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Driving Licence', 'Driving Licences', 'geditorial-licensed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Licences', 'Label: Show Option All', 'geditorial-licensed' ),
					'show_option_no_items' => _x( '(Unlicensed)', 'Label: Show Option No Terms', 'geditorial-licensed' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'motorcycle'     => _x( 'Motorcycle', 'Default Term', 'geditorial-licensed' ),
				'category-three' => _x( 'Category Three', 'Default Term', 'geditorial-licensed' ),
				'category-two'   => _x( 'Category Two', 'Default Term', 'geditorial-licensed' ),
				'category-one'   => _x( 'Category One', 'Default Term', 'geditorial-licensed' ),
				'loader'         => _x( 'Loader', 'Default Term', 'geditorial-licensed' ),
				'helicopter'     => _x( 'Helicopter', 'Default Term', 'geditorial-licensed' ),
				'airplane'       => _x( 'Airplane', 'Default Term', 'geditorial-licensed' ),
			],
		];
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
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );
		$this->hook_dashboardsummary_paired_post_summaries( 'main_taxonomy' );
		$this->bulkexports__hook_tabloid_term_assigned( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

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
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
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
			return Info::renderNoReportsAvailable();
	}
}

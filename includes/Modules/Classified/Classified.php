<?php namespace geminorum\gEditorial\Modules\Classified;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Classified extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'classified',
			'title'    => _x( 'Classified', 'Modules: Classified', 'geditorial-admin' ),
			'desc'     => _x( 'Content Classifications', 'Modules: Classified', 'geditorial-admin' ),
			'icon'     => 'privacy',
			'access'   => 'beta',
			'keywords' => [
				'classification',
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
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit',
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'classification',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'main_taxonomy' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Classification', 'Classifications', 'geditorial-classified' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Classifications', 'Label: Menu Name', 'geditorial-classified' ),
					'show_option_all'      => _x( 'Classifications', 'Label: Show Option All', 'geditorial-classified' ),
					'show_option_no_items' => _x( '(Unclassified)', 'Label: Show Option No Terms', 'geditorial-classified' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => ModuleInfo::getClassifications( 'strings' ),
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
			'data_length'        => _x( '20', 'Main Taxonomy Argument: `data_length`', 'geditorial-classified' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'custom_captype'  => TRUE,
			'single_selected' => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->hook_dashboardsummary_paired_post_summaries( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();
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
}

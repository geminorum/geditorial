<?php namespace geminorum\gEditorial\Modules\Achieved;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Achieved extends gEditorial\Module
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
			'name'     => 'achieved',
			'title'    => _x( 'Achieved', 'Modules: Achieved', 'geditorial-admin' ),
			'desc'     => _x( 'Badges of Honor', 'Modules: Achieved', 'geditorial-admin' ),
			'icon'     => 'awards',
			'access'   => 'beta',
			'keywords' => [
				'has-shortcodes',
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
				'auto_term_parents',
				'show_in_quickedit',
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
			],
			'_supports' => [
				'shortcode_support',
			],
			'_constants' => [
				'main_taxonomy_constant'  => [ NULL, 'achievement' ],
				'main_shortcode_constant' => [ NULL, 'achievements' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'  => 'achievement',
			'main_shortcode' => 'achievements',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Achievement', 'Achievements', 'geditorial-achieved' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'extended_label'       => _x( 'Content Achievements', 'Label: `extended_label`', 'geditorial-achieved' ),
					'menu_name'            => _x( 'Content Achievements', 'Label: `menu_name`', 'geditorial-achieved' ),
					'show_option_all'      => _x( 'Achievements', 'Label: `show_option_all`', 'geditorial-achieved' ),
					'show_option_no_items' => _x( '(Unachieved)', 'Label: `show_option_no_items`', 'geditorial-achieved' ),
				],
			],
		];

		return $strings;
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
			'data_length'        => _x( '120', 'Main Taxonomy Argument: `data_length`', 'geditorial-achieved' ),
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

		$this->register_shortcode( 'main_shortcode' );
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

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			'post',
			$this->constant( 'main_taxonomy' ),
			array_merge( [
				'post_id'   => NULL,
				'posttypes' => $this->posttypes(),
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode' )
		);
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

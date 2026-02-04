<?php namespace geminorum\gEditorial\Modules\Athlete;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Athlete extends gEditorial\Module
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
			'name'     => 'athlete',
			'title'    => _x( 'Athlete', 'Modules: Athlete', 'geditorial-admin' ),
			'desc'     => _x( 'Fields of Sport', 'Modules: Athlete', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'person-raised-hand' ],
			'access'   => 'beta',
			'keywords' => [
				'sport',
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
				'custom_archives',
			],
			'_supports' => [
				'shortcode_support',
			],
			'_constants' => [
				'main_taxonomy_constant'  => [ NULL, 'sports_field' ],
				'main_shortcode_constant' => [ NULL, 'sports-field' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'  => 'sports_field',
			'main_shortcode' => 'sports-field',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Sports Field', 'Sports Fields', 'geditorial-athlete' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Sports', 'Label: `menu_name`', 'geditorial-athlete' ),
					'show_option_all'      => _x( 'Sports Field', 'Label: `show_option_all`', 'geditorial-athlete' ),
					'show_option_no_items' => _x( '(Unspecified)', 'Label: `show_option_no_items`', 'geditorial-athlete' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			// @SEE: https://en.wikipedia.org/wiki/List_of_sports
			// @SEE: https://samva.net/majors/
			'main_taxonomy' => [
				'acrobatic'    => _x( 'Acrobatic', 'Default Term: Sports Field', 'geditorial-athlete' ),      // حرکتی
				'air'          => _x( 'Air', 'Default Term: Sports Field', 'geditorial-athlete' ),            // هوایی
				'performance ' => _x( 'Performance', 'Default Term: Sports Field', 'geditorial-athlete' ),    // نمایشی
				'martial-arts' => _x( 'Martial Arts', 'Default Term: Sports Field', 'geditorial-athlete' ),   // رزمی
				'strength'     => _x( 'Strength', 'Default Term: Sports Field', 'geditorial-athlete' ),       // قدرتی
				'adventure'    => _x( 'Adventure', 'Default Term: Sports Field', 'geditorial-athlete' ),      // ماجراجویانه
				'riding'       => _x( 'Riding', 'Default Term: Sports Field', 'geditorial-athlete' ),         // سواری
				'shooting'     => _x( 'Shooting', 'Default Term: Sports Field', 'geditorial-athlete' ),       // نشانه‌روی
				'group'        => _x( 'Group', 'Default Term: Sports Field', 'geditorial-athlete' ),          // گروهی
				'net-and-wall' => _x( 'Net and Wall', 'Default Term: Sports Field', 'geditorial-athlete' ),   // راکتی
				'mind'         => _x( 'Mind', 'Default Term: Sports Field', 'geditorial-athlete' ),           // فکری
				'beach'        => _x( 'Beach', 'Default Term: Sports Field', 'geditorial-athlete' ),          // ساحلی
				'water'        => _x( 'Water', 'Default Term: Sports Field', 'geditorial-athlete' ),          // آبی
				'snow'         => _x( 'Snow', 'Default Term: Sports Field', 'geditorial-athlete' ),           // برفی
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
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );
		$this->templatetaxonomy__hook_custom_archives( 'main_taxonomy' );
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

			if ( 'edit' === $screen->base ) {

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

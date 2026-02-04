<?php namespace geminorum\gEditorial\Modules\Almanac;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Almanac extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'almanac',
			'title'    => _x( 'Almanac', 'Modules: Almanac', 'geditorial-admin' ),
			'desc'     => _x( 'Calendar Classifications for Contents', 'Modules: Almanac', 'geditorial-admin' ),
			'icon'     => 'calendar',
			'access'   => 'beta',
			'keywords' => [
				'date',
				'calendar',
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
				'selectmultiple_term',
			],
			'_editlist' => [
				'admin_restrict',
				'show_in_quickedit',
			],
			'_constants' => [
				'main_taxonomy_constant' => [ NULL, 'calendar_type' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'calendar_type',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Calendar Type', 'Calendar Types', 'geditorial-almanac' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Calendars', 'Label: Menu Name', 'geditorial-almanac' ),
					'show_option_all'      => _x( 'Calendars', 'Label: Show Option All', 'geditorial-almanac' ),
					'show_option_no_items' => _x( '(Undefined)', 'Label: Show Option No Terms', 'geditorial-almanac' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				// @REF: https://unicode-org.github.io/icu/userguide/datetime/calendar/
				// @SEE: `Services\Calendars::getDefualts()`
				'gregorian'     => _x( 'Gregorian', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'japanese'      => _x( 'Japanese', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'buddhist'      => _x( 'Buddhist', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'chinese'       => _x( 'Chinese', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'persian'       => _x( 'Persian', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'indian'        => _x( 'Indian', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'islamic'       => _x( 'Islamic', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'islamic-civil' => _x( 'Islamic-Civil', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'coptic'        => _x( 'Coptic', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
				'ethiopic'      => _x( 'Ethiopic', 'Main Taxonomy: Default Term', 'geditorial-almanac' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
		], NULL, [
			'custom_captype'  => TRUE,
			'single_selected' => ! $this->get_setting( 'selectmultiple_term' ),
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

			} else if ( 'post' === $screen->base ) {

				$this->hook_taxonomy_metabox_mainbox(
					'main_taxonomy',
					$screen->post_type,
					$this->get_setting( 'selectmultiple_term' )
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
}

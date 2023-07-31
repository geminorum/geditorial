<?php namespace geminorum\gEditorial\Modules\Educated;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Educated extends gEditorial\Module
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
			'name'   => 'educated',
			'title'  => _x( 'Educated', 'Modules: Educated', 'geditorial' ),
			'desc'   => _x( 'Editorial Educations', 'Modules: Educated', 'geditorial' ),
			'icon'   => 'welcome-learn-more',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'education',
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
				'main_taxonomy' => _n_noop( 'Education Definition', 'Education Definitions', 'geditorial-educated' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Educations', 'Label: Show Option All', 'geditorial-educated' ),
					'show_option_no_items' => _x( '(Uneducated)', 'Label: Show Option No Terms', 'geditorial-educated' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Team Education Summary', 'Dashboard Widget Title', 'geditorial-educated' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Education Summary', 'Dashboard Widget Title', 'geditorial-educated' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy' => [
				'illiterate' => _x( 'Illiterate', 'Default Term', 'geditorial-educated' ),
				'primary'    => _x( 'Primary', 'Default Term', 'geditorial-educated' ),
				'secondary'  => _x( 'Secondary', 'Default Term', 'geditorial-educated' ),
				'bachelor'   => _x( 'Bachelor', 'Default Term', 'geditorial-educated' ),
				'master'     => _x( 'Master', 'Default Term', 'geditorial-educated' ),
				'doctorate'  => _x( 'Doctorate', 'Default Term', 'geditorial-educated' ),
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => '__checklist_restricted_terms_callback',
		], NULL, TRUE );

		$this->corecaps__init_taxonomy_meta_caps( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	protected function dashboard_widgets()
	{
		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
			return;

		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
	}
}

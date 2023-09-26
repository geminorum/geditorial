<?php namespace geminorum\gEditorial\Modules\Territory;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Territory extends gEditorial\Module
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
			'name'     => 'territory',
			'title'    => _x( 'Territory', 'Modules: Territory', 'geditorial' ),
			'desc'     => _x( 'Territorial Arrangements', 'Modules: Territory', 'geditorial' ),
			'icon'     => 'location-alt',
			'access'   => 'beta',
			'keywords' => [
				'geo',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy' ),
			'_dashboard'       => [
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
			'main_taxonomy' => 'territorial_arrangement',
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
				'main_taxonomy' => _n_noop( 'Territorial Arrangement', 'Territorial Arrangements', 'geditorial-territory' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Territory', 'Label: Menu Name', 'geditorial-territory' ),
					'show_option_all'      => _x( 'Territory', 'Label: Show Option All', 'geditorial-territory' ),
					'show_option_no_items' => _x( '(No-Territory)', 'Label: Show Option No Terms', 'geditorial-territory' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Territory Arrangements Summary', 'Dashboard Widget Title', 'geditorial-territory' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Territory Arrangements Summary', 'Dashboard Widget Title', 'geditorial-territory' ), ],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => NULL,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
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

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context
			? $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports', NULL, $fallback )
			: parent::cuc( $context, $fallback );
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
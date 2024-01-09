<?php namespace geminorum\gEditorial\Modules\Licensed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Licensed extends gEditorial\Module
{
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
			'name'     => 'licensed',
			'title'    => _x( 'Licensed', 'Modules: Licensed', 'geditorial-admin' ),
			'desc'     => _x( 'Driver Licence Management', 'Modules: Licensed', 'geditorial-admin' ),
			'icon'     => 'id',
			'access'   => 'beta',
			'keywords' => [
				'taxmodule',
				'vehicle',
				'car',
			],
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
			'_editpost' => [
				'selectmultiple_term',
			],
			'_editlist' => [
				'show_in_quickedit',
			],
			'_frontend' => [
				'show_in_navmenus',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'driving_licence',
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
				'main_taxonomy' => _n_noop( 'Driving Licence', 'Driving Licences', 'geditorial-licensed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Licences', 'Label: Show Option All', 'geditorial-licensed' ),
					'show_option_no_items' => _x( '(Unlicensed)', 'Label: Show Option No Terms', 'geditorial-licensed' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Driving Licence Summary', 'Dashboard Widget Title', 'geditorial-licensed' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Driving Licence Summary', 'Dashboard Widget Title', 'geditorial-licensed' ), ],
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
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
		], NULL, [], TRUE );

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

			} else if ( 'post' === $screen->base ) {

				$this->hook_taxonomy_metabox_mainbox(
					'main_taxonomy',
					$screen->post_type,
					$this->get_setting( 'selectmultiple_term' )
						? '__singleselect_restricted_terms_callback'
						: '__checklist_restricted_terms_callback'
				);
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

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return 'reports' == $context
			? $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports', NULL, $fallback )
			: parent::cuc( $context, $fallback );
	}

	public function template_include( $template )
	{
		return $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) );
	}
}

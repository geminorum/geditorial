<?php namespace geminorum\gEditorial\Modules\Skilled;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Skilled extends gEditorial\Module
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
			'name'     => 'skilled',
			'title'    => _x( 'Skilled', 'Modules: Skilled', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Expertise', 'Modules: Skilled', 'geditorial-admin' ),
			'icon'     => 'admin-tools',
			'access'   => 'planned',
			'keywords' => [
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
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
				'dashboard_widgets',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'show_in_quick_edit',
			],
			'_frontend' => [
				'show_in_nav_menus',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'skill',
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
				'main_taxonomy' => _n_noop( 'Skill', 'Skills', 'geditorial-skilled' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Skills', 'Label: Show Option All', 'geditorial-skilled' ),
					'show_option_no_items' => _x( '(No Skill)', 'Label: Show Option No Terms', 'geditorial-skilled' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Team Skill Summary', 'Dashboard Widget Title', 'geditorial-skilled' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Skill Summary', 'Dashboard Widget Title', 'geditorial-skilled' ), ],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default hierarchical ui
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

				// $this->hook_taxonomy_metabox_mainbox(
				// 	'main_taxonomy',
				// 	$screen->post_type,
				// 	// '__singleselect_restricted_terms_callback',
				// 	'__checklist_restricted_terms_callback',
				// );
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
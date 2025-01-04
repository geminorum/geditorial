<?php namespace geminorum\gEditorial\Modules\Ranked;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Ranked extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\MetaBoxSupported;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'ranked',
			'title'    => _x( 'Ranked', 'Modules: Ranked', 'geditorial-admin' ),
			'desc'     => _x( 'Ranking for Editorial Content', 'Modules: Ranked', 'geditorial-admin' ),
			'icon'     => 'shield-alt',
			'access'   => 'beta',
			'keywords' => [
				'taxmodule',
				'ranking',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE ),
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
				'selectmultiple_term',
			],
			'_editlist' => [
				'admin_restrict',
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
			'main_taxonomy' => 'ranking',
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
				'main_taxonomy' => _n_noop( 'Ranking Definition', 'Ranking Definitions', 'geditorial-ranked' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Rankings', 'Label: Menu Name', 'geditorial-ranked' ),
					'show_option_all'      => _x( 'Rankings', 'Label: Show Option All', 'geditorial-ranked' ),
					'show_option_no_items' => _x( '(Unranked)', 'Label: Show Option No Terms', 'geditorial-ranked' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'publisher'          => _x( 'Publisher', 'Default Term', 'geditorial-ranked' ),
				'editor-in-chief'    => _x( 'Editor in Chief', 'Default Term', 'geditorial-ranked' ),
				'editorial-director' => _x( 'Editorial Director', 'Default Term', 'geditorial-ranked' ),
				'managing-editor'    => _x( 'Managing Editor', 'Default Term', 'geditorial-ranked' ),
				'senior-editor'      => _x( 'Senior Editor', 'Default Term', 'geditorial-ranked' ),
				'digital-producer'   => _x( 'Digital Producer', 'Default Term', 'geditorial-ranked' ),
				'copy-editor'        => _x( 'Copy Editor', 'Default Term', 'geditorial-ranked' ),
				'proofreader'        => _x( 'Proofreader', 'Default Term', 'geditorial-ranked' ),
				'executive'          => _x( 'Executive', 'Default Term', 'geditorial-ranked' ),
				// Contributing Writer
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
			'single_selected' => ! $this->get_setting( 'selectmultiple_term' ),
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->bulkexports__hook_tabloid_term_assigned( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

			} else if ( 'post' === $screen->base ) {

				if ( ! $this->get_setting( 'metabox_advanced' ) )
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

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}
}

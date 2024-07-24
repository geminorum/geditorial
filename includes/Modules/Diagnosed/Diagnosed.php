<?php namespace geminorum\gEditorial\Modules\Diagnosed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Diagnosed extends gEditorial\Module
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
			'name'     => 'diagnosed',
			'title'    => _x( 'Diagnosed', 'Modules: Diagnosed', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Diagnosis', 'Modules: Diagnosed', 'geditorial-admin' ),
			'icon'     => 'color-picker',
			'access'   => 'beta',
			'keywords' => [
				'diagnosis',
				'medical',
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
			'main_taxonomy' => 'diagnosis',
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
				'main_taxonomy' => _n_noop( 'Diagnosis', 'Diagnoses', 'geditorial-diagnosed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Diagnosis Definitions', 'Label: `menu_name`', 'geditorial-diagnosed' ),
					'extended_label'       => _x( 'Diagnosis Definitions', 'Label: `extended_label`', 'geditorial-diagnosed' ),
					'show_option_all'      => _x( 'Diagnoses', 'Label: `show_option_all`', 'geditorial-diagnosed' ),
					'show_option_no_items' => _x( '(Undiagnosed)', 'Label: `show_option_no_items`', 'geditorial-diagnosed' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'sciatica' => _x( 'Sciatica', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'diabetes' => _x( 'Diabetes', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'glasses'  => _x( 'Glasses', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'migraine' => _x( 'Migraine', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'epilepsy' => _x( 'Epilepsy', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'gastro'   => _x( 'Gastro', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'kidney'   => _x( 'Kidney', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'liver'    => _x( 'Liver', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'heart'    => _x( 'Heart', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'knee'     => _x( 'Knee', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
				'ankle'    => _x( 'Ankle', 'Main Taxonomy: Default Term', 'geditorial-diagnosed' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
			'show_in_menu'       => FALSE,
		], NULL, [
			'is_viewable'    => $this->get_setting( 'contents_viewable', TRUE ),
			'custom_captype' => TRUE,
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
		if ( ! $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
			return;

		$this->add_dashboard_widget(
			'term-summary',
			$this->get_taxonomy_label( 'main_taxonomy', 'extended_label' ),
			'refresh'
		);
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
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}
}

<?php namespace geminorum\gEditorial\Modules\Abo;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Abo extends gEditorial\Module
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
			'name'     => 'abo',
			'title'    => _x( 'Abo', 'Modules: Abo', 'geditorial-admin' ),
			'desc'     => _x( 'Blood Group System', 'Modules: Abo', 'geditorial-admin' ),
			'icon'     => 'heart',
			'access'   => 'beta',
			'keywords' => [
				'blood',
				'medical',
				'taxmodule',
				'crm-feature',
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
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'admin_restrict',
				'show_in_quickedit',
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
			],
			'_constants' => [
				'main_taxonomy_constant' => [ NULL, 'blood_type' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'blood_type',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Blood Type', 'Blood Types', 'geditorial-abo' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'extended_label'       => _x( 'Blood-Type', 'Label: Extended Label', 'geditorial-abo' ),
					'show_option_all'      => _x( 'Blood-Type', 'Label: Show Option All', 'geditorial-abo' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-abo' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				// @REF: https://www.redcrossblood.org/donate-blood/blood-types.html
				'a-positive'  => _x( 'A&plus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'a-negative'  => _x( 'A&minus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'b-positive'  => _x( 'B&plus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'b-negative'  => _x( 'B&minus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'o-positive'  => _x( 'O&plus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'o-negative'  => _x( 'O&minus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'ab-positive' => _x( 'AB&plus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'ab-negative' => _x( 'AB&minus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'rh-positive' => _x( 'RH&plus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
				'rh-negative' => _x( 'RH&minus;', 'Main Taxonomy: Default Term', 'geditorial-abo' ),
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
			'show_in_menu'       => FALSE,
			'data_length'        => _x( '3', 'Main Taxonomy Argument: `data_length`', 'geditorial-abo' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'custom_captype'  => TRUE,
			'single_selected' => TRUE,
			'suitable_metas'  => [
				'code',
			],
		] );

		add_filter( sprintf( '%s_%s', $this->constant( 'main_taxonomy' ), 'name' ), [ $this, 'main_taxonomy_name_field' ], 20, 3 );

		$this->hook_taxonomy_tabloid_exclude_rendered( 'main_taxonomy' );
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
					'__singleselect_restricted_terms_callback'
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

	// filter for `{$taxonomy}_{$field}` on `sanitize_term_field()`
	public function main_taxonomy_name_field( $value, $term_id, $context )
	{
		return 'display' === $context ? Core\HTML::wrapLTR( $value ) : $value;
	}
}

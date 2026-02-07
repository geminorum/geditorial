<?php namespace geminorum\gEditorial\Modules\Conscripted;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Conscripted extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreRowActions;
	use Internals\DashboardSummary;
	use Internals\MetaBoxSupported;
	use Internals\TaxonomyOverview;
	use Internals\TemplateTaxonomy;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'conscripted',
			'title'    => _x( 'Conscripted', 'Modules: Conscripted', 'geditorial-admin' ),
			'desc'     => _x( 'State-mandated National Service', 'Modules: Conscripted', 'geditorial-admin' ),
			'icon'     => [ 'misc-512', 'person-military-rifle' ],
			'access'   => 'beta',
			'keywords' => [
				'conscription',
				'compulsory',
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
				'summary_parents',
				'summary_excludes' => [ NULL, $terms, $empty ],
				'summary_scope',
				'summary_drafts',
				'count_not',
			],
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit' => [ $this->get_taxonomy_show_in_quickedit_desc( 'main_taxonomy' ) ],
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
			],
			'_constants' => [
				'main_taxonomy_constant' => [ NULL, 'conscription' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'conscription',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Conscription', 'Conscriptions', 'geditorial-conscripted' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'extended_label'       => _x( 'Conscription', 'Label: `extended_label`', 'geditorial-conscripted' ),
					'show_option_all'      => _x( 'Conscription', 'Label: Show Option All', 'geditorial-conscripted' ),
					'show_option_no_items' => _x( '(Unknown)', 'Label: Show Option No Terms', 'geditorial-conscripted' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'underage-for-service' => _x( 'Underage for Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),   // قبل از سن مشمولیت
				'subject-to-service'   => _x( 'Subject to Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),     // مشمول خدمت
				'currently-in-service' => _x( 'Currently in Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),   // در حال خدمت
				'end-of-service'       => _x( 'End of Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),         // پایان خدمت
				'medical-exemption'    => _x( 'Medical Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),      // معافیت پزشکی
				'education-exemption'  => _x( 'Education Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),    // معافیت تحصیلی
				'veteran-exemption'    => _x( 'Veteran Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),      // معافیت ایثارگری
				'permanent-exemption'  => _x( 'Permanent Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),    // معافیت دائم
				'sponsor-exemption'    => _x( 'Sponsor Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),      // معافیت کفالت
				'paid-exemption'       => _x( 'Paid Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),         // خرید خدمت
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
			'data_length'        => _x( '20', 'Main Taxonomy Argument: `data_length`', 'geditorial-conscripted' ),
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'single_selected' => TRUE,
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );
		$this->hook_dashboardsummary_paired_post_summaries( 'main_taxonomy' );
		$this->bulkexports__hook_tabloid_term_assigned( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->_hook_parentfile_for_usersphp();
			$this->modulelinks__register_headerbuttons();
			$this->bulkexports__hook_supportedbox_for_term( 'main_taxonomy', $screen );
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) ) {
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );
					$this->rowactions__hook_force_default_term( $screen, 'main_taxonomy', TRUE );
				}

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
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'users.php' );
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

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->taxonomy_overview_render_table( 'main_taxonomy', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}

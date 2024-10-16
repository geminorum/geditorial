<?php namespace geminorum\gEditorial\Modules\Conscripted;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Conscripted extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\CoreRowActions;
	use Internals\DashboardSummary;
	use Internals\TemplateTaxonomy;

	// TODO: calculate underage/subject to service based on given dob metakey

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'conscripted',
			'title'    => _x( 'Conscripted', 'Modules: Conscripted', 'geditorial-admin' ),
			'desc'     => _x( 'State-mandated National Service', 'Modules: Conscripted', 'geditorial-admin' ),
			'icon'     => 'superhero-alt',
			'access'   => 'beta',
			'keywords' => [
				'conscription',
				'compulsorily',
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
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
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
			'main_taxonomy' => 'conscription',
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

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Team Conscription Summary', 'Dashboard Widget Title', 'geditorial-conscripted' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Conscription Summary', 'Dashboard Widget Title', 'geditorial-conscripted' ), ],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'underage-for-service' => _x( 'Underage for Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
				'subject-to-service'   => _x( 'Subject to Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
				'currently-in-service' => _x( 'Currently in Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
				'end-of-service'       => _x( 'End of Service', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
				'medical-exemption'    => _x( 'Medical Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
				'education-exemption'  => _x( 'Education Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
				'permanent-exemption'  => _x( 'Permanent Exemption', 'Main Taxonomy: Default Term', 'geditorial-conscripted' ),
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
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'custom_captype'  => TRUE,
			'single_selected' => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->hook_dashboardsummary_paired_post_summaries( 'main_taxonomy' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();
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
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}
}

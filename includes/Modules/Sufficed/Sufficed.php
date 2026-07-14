<?php namespace geminorum\gEditorial\Modules\Sufficed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Sufficed extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;
	use Internals\TemplateTaxonomy;

	// NOTE: contents are `Sufficient` by default, posts with no main-terms are acceptable

	protected $disable_no_posttypes = TRUE;

	public static function module(): array
	{
		return [
			'name'     => 'sufficed',
			'title'    => _x( 'Sufficed', 'Modules: Sufficed', 'geditorial-admin' ),
			'desc'     => _x( 'Content Deficiencies', 'Modules: Sufficed', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'bookmark-check-fill' ],
			'access'   => 'beta',
			'keywords' => [
				'deficiency',
				'taxmodule',
			],
		];
	}

	protected function get_global_settings(): array
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
				'metabox_advanced',
				'selectmultiple_term' => [ NULL, TRUE ],
			],
			'_editlist' => [
				'admin_restrict',
				'auto_term_parents',
				'show_in_quickedit',
			],
			'_frontend' => [
				'contents_viewable',
				'show_in_navmenus',
				'archive_override',
			],
		];
	}

	protected function get_global_constants(): array
	{
		return [
			'main_taxonomy' => 'deficiency',
		];
	}

	protected function get_global_strings(): array
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Deficiency', 'Deficiencies', 'geditorial-sufficed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Deficiencies', 'Label: Menu Name', 'geditorial-sufficed' ),
					'show_option_all'      => _x( 'Deficiencies', 'Label: Show Option All', 'geditorial-sufficed' ),
					'show_option_no_items' => _x( '(Sufficient)', 'Label: Show Option No Terms', 'geditorial-sufficed' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms(): array
	{
		return [
			'main_taxonomy' => [
				// '' => _x( '', 'Main Taxonomy: Default Term', 'geditorial-sufficed' ),
			],
		];
	}

	public function init(): void
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
			'show_in_nav_menus'  => (bool) $this->get_setting( 'show_in_navmenus' ),
			'show_in_menu'       => FALSE,
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'auto_parents'    => $this->get_setting( 'auto_term_parents', TRUE ),
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
			'custom_icon'     => [ 'misc-16', 'bookmark-x-fill' ],
			'custom_captype'  => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
	}

	/**
	 * Fires after the current screen has been set.
	 *
	 * @param object $screen
	 * @return void
	 */
	public function current_screen( $screen ): void
	{
		if ( $this->is_screen_taxonomy( 'main_taxonomy', $screen ) ) {

			$this->_hook_parentfile_for_optionsgeneralphp();
			$this->modulelinks__register_headerbuttons();

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' === $screen->base ) {

				if ( $this->corecaps_taxonomy_role_can( 'main_taxonomy', 'reports' ) )
					$this->corerestrictposts__hook_screen_taxonomies( 'main_taxonomy' );

			} else if ( 'post' === $screen->base ) {

				$this->coretax__hook_posttype_mainbox( 'main_taxonomy', $screen, TRUE );
			}
		}
	}

	public function admin_menu(): void
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function dashboard_widgets(): void
	{
		$this->add_dashboard_term_summary( 'main_taxonomy' );
	}

	public function cuc( ?string $context = NULL, string $fallback_capability = '' ): bool
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback_capability );
	}

	public function template_include( string $template ): string
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}
}

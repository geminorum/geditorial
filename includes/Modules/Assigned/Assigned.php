<?php namespace geminorum\gEditorial\Modules\Assigned;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Assigned extends gEditorial\Module
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
			'name'     => 'assigned',
			'title'    => _x( 'Assigned', 'Modules: Assigned', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Assignments', 'Modules: Assigned', 'geditorial-admin' ),
			'icon'     => 'hammer',
			'access'   => 'beta',
			'keywords' => [
				'taxmodule',
				'assignment',
			],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				'parent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
			],
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
				'selectmultiple_term',
			],
			'_editlist' => [
				'admin_restrict',
				'show_in_quickedit',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy' => 'assignment',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy' => _n_noop( 'Assignment', 'Assignments', 'geditorial-assigned' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'menu_name'            => _x( 'Content Assignments', 'Label: Menu Name', 'geditorial-assigned' ),
					'show_option_all'      => _x( 'Assignment', 'Label: Show Option All', 'geditorial-assigned' ),
					'show_option_no_items' => _x( '(Unassigned)', 'Label: Show Option No Terms', 'geditorial-assigned' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'main_taxonomy' => [
				'research'  => _x( 'Research', 'Default Term', 'geditorial-assigned' ),
				'planning'  => _x( 'Planning', 'Default Term', 'geditorial-assigned' ),
				'execution' => _x( 'Execution', 'Default Term', 'geditorial-assigned' ),
				'reporting' => _x( 'Reporting', 'Default Term', 'geditorial-assigned' ),
			],
		];
	}

	public function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'assigned_to_userid' => [
						'title'       => _x( 'Assigned To', 'Field Title', 'geditorial-assigned' ),
						'description' => _x( 'Determines the user responsible for this assignment.', 'Field Description', 'geditorial-assigned' ),
						'type'        => 'user',
					],
					'assigned_to_postid' => [
						'title'       => _x( 'Assigned To', 'Field Title', 'geditorial-assigned' ),
						'description' => _x( 'Determines the individual responsible for this assignment.', 'Field Description', 'geditorial-assigned' ),
						'type'        => 'post',
						'posttype'    => $this->get_setting_posttypes( 'parent' ),
					],
					'assigned_due_date' => [
						'title'       => _x( 'Due Date', 'Field Title', 'geditorial-assigned' ),
						'description' => _x( 'Determines the planned date that this assignment is required to be completed.', 'Field Description', 'geditorial-assigned' ),
						'type'        => 'datetime',
					],
				],
			],
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'public'             => FALSE,
			'rewrite'            => FALSE,
			'hierarchical'       => TRUE,
			'show_in_menu'       => FALSE,
			'show_in_quick_edit' => (bool) $this->get_setting( 'show_in_quickedit' ),
		], NULL, [
			'custom_captype'  => TRUE,
			'single_selected' => ! $this->get_setting( 'selectmultiple_term' ),
		] );

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
}

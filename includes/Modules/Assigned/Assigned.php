<?php namespace geminorum\gEditorial\Modules\Assigned;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Assigned extends gEditorial\Module
{
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'default' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'     => 'assigned',
			'title'    => _x( 'Assigned', 'Modules: Assigned', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Assignments', 'Modules: Assigned', 'geditorial-admin' ),
			'icon'     => 'hammer',
			'access'   => 'beta',
			'keywords' => [ 'assignment' ],
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'parent_posttypes' => [ NULL, $this->get_settings_posttypes_parents() ],
			],
			'_roles'     => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy', TRUE, TRUE, $terms, $empty ),
			'_dashboard' => [
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
			'main_taxonomy' => 'assignment',
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

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Assignments Summary', 'Dashboard Widget Title', 'geditorial-assigned' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Assignments Summary', 'Dashboard Widget Title', 'geditorial-assigned' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy' => [
				'research'  => _x( 'Research', 'Default Term', 'geditorial-assigned' ),
				'planning'  => _x( 'Planning', 'Default Term', 'geditorial-assigned' ),
				'execution' => _x( 'Execution', 'Default Term', 'geditorial-assigned' ),
				'reporting' => _x( 'Reporting', 'Default Term', 'geditorial-assigned' ),
			],
		];

		return $strings;
	}

	public function get_global_fields()
	{
		return [ 'meta' => [
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
					'description' => _x( '', 'Field Description', 'geditorial-assigned' ),
					'type'        => 'datetime',
				],
			],
		] ];
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
			'show_in_quick_edit' => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => '__checklist_restricted_terms_callback',
		], NULL, TRUE );

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

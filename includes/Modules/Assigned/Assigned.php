<?php namespace geminorum\gEditorial\Modules\Assigned;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Assigned extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'default' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'   => 'assigned',
			'title'  => _x( 'Assigned', 'Modules: Assigned', 'geditorial' ),
			'desc'   => _x( 'Editorial Assignments', 'Modules: Assigned', 'geditorial' ),
			'icon'   => 'hammer',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$roles = $this->get_settings_default_roles();
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_items_available', NULL, 'no_terms' );

		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'posttypes_parents' => [ NULL, $this->get_settings_posttypes_parents() ],
			],
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-assigned' ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Assignments.', 'Setting Description', 'geditorial-assigned' ),
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-assigned' ),
					'description' => _x( 'Roles that can assign Assignments.', 'Setting Description', 'geditorial-assigned' ),
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-assigned' ),
					'description' => _x( 'Roles that can see Assignments Reports.', 'Setting Description', 'geditorial-assigned' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Restricted Roles', 'Setting Title', 'geditorial-assigned' ),
					'description' => _x( 'Roles that check for Assignments visibility.', 'Setting Description', 'geditorial-assigned' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Terms', 'Setting Title', 'geditorial-assigned' ),
					'description' => _x( 'Handles visibility of each term based on meta values.', 'Setting Description', 'geditorial-assigned' ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Setting Option', 'geditorial-assigned' ),
						'hidden'   => _x( 'Hidden', 'Setting Option', 'geditorial-assigned' ),
					],
				],
				[
					'field'        => 'locking_terms',
					'type'         => 'checkbox-panel',
					'title'        => _x( 'Locking Terms', 'Setting Title', 'geditorial-assigned' ),
					'description'  => _x( 'Selected terms will lock editing the post to assignment managers.', 'Setting Description', 'geditorial-assigned' ),
					'string_empty' => $empty,
					'values'       => $terms,
				],
			],
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
		return [
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
					'posttype'    => $this->get_setting( 'posttypes_parents', [] ),
				],
				'assigned_due_date' => [
					'title'       => _x( 'Due Date', 'Field Title', 'geditorial-assigned' ),
					'description' => _x( '', 'Field Description', 'geditorial-assigned' ),
					'type'        => 'datetime',
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
			'show_in_quick_edit' => TRUE,
			'show_in_menu'       => FALSE,
			'meta_box_cb'        => '__checklist_restricted_terms_callback',
		], NULL, TRUE );

		$this->filter( 'map_meta_cap', 4 );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) == $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->role_can( 'reports' ) )
					$this->_hook_screen_restrict_taxonomies();
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'main_taxonomy' ];
	}

	protected function dashboard_widgets()
	{
		if ( ! $this->role_can( 'reports' ) )
			return;

		$this->add_dashboard_widget( 'term-summary', NULL, 'refresh' );
	}

	public function render_widget_term_summary( $object, $box )
	{
		$this->do_dashboard_term_summary( 'main_taxonomy', $box );
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		$taxonomy = $this->constant( 'main_taxonomy' );

		switch ( $cap ) {

			case 'edit_post':
			case 'edit_page':
			case 'delete_post':
			case 'delete_page':
			case 'publish_post':

				$locking = $this->get_setting( 'locking_terms', [] );

				if ( empty( $locking ) )
					return $caps;

				if ( ! $post = WordPress\Post::get( $args[0] ) )
					return $caps;

				if ( ! $this->posttype_supported( $post->post_type ) )
					return $caps;

				foreach ( $locking as $term_id )
					if ( is_object_in_term( $post->ID, $taxonomy, (int) $term_id ) )
						return $this->role_can( 'manage', $user_id ) ? $caps : [ 'do_not_allow' ];

			break;
			case 'manage_'.$taxonomy:
			case 'edit_'.$taxonomy:
			case 'delete_'.$taxonomy:

				return $this->role_can( 'manage', $user_id )
					? [ 'read' ]
					: [ 'do_not_allow' ];

			break;
			case 'assign_'.$taxonomy:

				return $this->role_can( 'assign', $user_id )
					? [ 'read' ]
					: [ 'do_not_allow' ];
			break;
			case 'assign_term':

				$term = get_term( (int) $args[0] );

				if ( ! $term || is_wp_error( $term ) )
					return $caps;

				if ( $taxonomy != $term->taxonomy )
					return $caps;

				if ( ! $roles = get_term_meta( $term->term_id, 'roles', TRUE ) )
					return $caps;

				if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
					return [ 'do_not_allow' ];
		}

		return $caps;
	}
}

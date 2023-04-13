<?php namespace geminorum\gEditorial\Modules\Educated;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress;

class Educated extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'educated',
			'title'  => _x( 'Educated', 'Modules: Educated', 'geditorial' ),
			'desc'   => _x( 'Editorial Educations', 'Modules: Educated', 'geditorial' ),
			'icon'   => 'welcome-learn-more',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$roles = $this->get_settings_default_roles();
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_terms',
			_x( 'There are no education definitions available!', 'Setting', 'geditorial-educated' ) );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-educated' ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Education Definitions.', 'Setting Description', 'geditorial-educated' ),
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-educated' ),
					'description' => _x( 'Roles that can Assign Education Definitions.', 'Setting Description', 'geditorial-educated' ),
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-educated' ),
					'description' => _x( 'Roles that can see Education Definitions Reports.', 'Setting Description', 'geditorial-educated' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Restricted Roles', 'Setting Title', 'geditorial-educated' ),
					'description' => _x( 'Roles that check for Education Definitions visibility.', 'Setting Description', 'geditorial-educated' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Definitions', 'Setting Title', 'geditorial-educated' ),
					'description' => _x( 'Handles visibility of each definition based on meta values.', 'Setting Description', 'geditorial-educated' ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Setting Option', 'geditorial-educated' ),
						'hidden'   => _x( 'Hidden', 'Setting Option', 'geditorial-educated' ),
					],
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
			'main_taxonomy' => 'education',
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
				'main_taxonomy' => _n_noop( 'Education Definition', 'Education Definitions', 'geditorial-educated' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Educations', 'Label: Show Option All', 'geditorial-educated' ),
					'show_option_no_items' => _x( '(Uneducated)', 'Label: Show Option No Terms', 'geditorial-educated' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Team Education Summary', 'Dashboard Widget Title', 'geditorial-educated' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Education Summary', 'Dashboard Widget Title', 'geditorial-educated' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy' => [
				'illiterate' => _x( 'Illiterate', 'Default Term', 'geditorial-educated' ),
				'primary'    => _x( 'Primary', 'Default Term', 'geditorial-educated' ),
				'secondary'  => _x( 'Secondary', 'Default Term', 'geditorial-educated' ),
				'bachelor'   => _x( 'Bachelor', 'Default Term', 'geditorial-educated' ),
				'master'     => _x( 'Master', 'Default Term', 'geditorial-educated' ),
				'doctorate'  => _x( 'Doctorate', 'Default Term', 'geditorial-educated' ),
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => '__checklist_restricted_terms_callback',
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

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'main_taxonomy' ];
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'options-general.php' );
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
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

				if ( ! WordPress\User::hasRole( array_merge( [ 'administrator' ], (array) $roles ), $user_id ) )
					return [ 'do_not_allow' ];
		}

		return $caps;
	}
}

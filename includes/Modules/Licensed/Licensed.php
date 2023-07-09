<?php namespace geminorum\gEditorial\Modules\Licensed;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Licensed extends gEditorial\Module
{
	use Internals\CoreMenuPage;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'licensed',
			'title'  => _x( 'Licensed', 'Modules: Licensed', 'geditorial' ),
			'desc'   => _x( 'Driver Licence Management', 'Modules: Licensed', 'geditorial' ),
			'icon'   => 'id',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$roles = $this->get_settings_default_roles();
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_terms',
			_x( 'There are no driving licences available!', 'Setting', 'geditorial-licensed' ) );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-licensed' ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Driving Licences.', 'Setting Description', 'geditorial-licensed' ),
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-licensed' ),
					'description' => _x( 'Roles that can Assign Driving Licences.', 'Setting Description', 'geditorial-licensed' ),
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-licensed' ),
					'description' => _x( 'Roles that can see Driving Licences Reports.', 'Setting Description', 'geditorial-licensed' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Restricted Roles', 'Setting Title', 'geditorial-licensed' ),
					'description' => _x( 'Roles that check for Driving Licences visibility.', 'Setting Description', 'geditorial-licensed' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Licences', 'Setting Title', 'geditorial-licensed' ),
					'description' => _x( 'Handles visibility of each licence based on meta values.', 'Setting Description', 'geditorial-licensed' ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Setting Option', 'geditorial-licensed' ),
						'hidden'   => _x( 'Hidden', 'Setting Option', 'geditorial-licensed' ),
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
			'main_taxonomy' => 'driving_licence',
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
				'main_taxonomy' => _n_noop( 'Driving Licence', 'Driving Licences', 'geditorial-licensed' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Licences', 'Label: Show Option All', 'geditorial-licensed' ),
					'show_option_no_items' => _x( '(Unlicensed)', 'Label: Show Option No Terms', 'geditorial-licensed' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['dashboard'] = [
			'current' => [ 'widget_title' => _x( 'Your Driving Licence Summary', 'Dashboard Widget Title', 'geditorial-licensed' ), ],
			'all'     => [ 'widget_title' => _x( 'Editorial Driving Licence Summary', 'Dashboard Widget Title', 'geditorial-licensed' ), ],
		];

		$strings['default_terms'] = [
			'main_taxonomy' => [
				'motorcycle'     => _x( 'Motorcycle', 'Default Term', 'geditorial-licensed' ),
				'category-three' => _x( 'Category Three', 'Default Term', 'geditorial-licensed' ),
				'category-two'   => _x( 'Category Two', 'Default Term', 'geditorial-licensed' ),
				'category-one'   => _x( 'Category One', 'Default Term', 'geditorial-licensed' ),
				'loader'         => _x( 'Loader', 'Default Term', 'geditorial-licensed' ),
				'helicopter'     => _x( 'Helicopter', 'Default Term', 'geditorial-licensed' ),
				'airplane'       => _x( 'Airplane', 'Default Term', 'geditorial-licensed' ),
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

				if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
					return [ 'do_not_allow' ];
		}

		return $caps;
	}
}

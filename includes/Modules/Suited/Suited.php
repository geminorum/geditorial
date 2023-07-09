<?php namespace geminorum\gEditorial\Modules\Suited;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Shortcode;
use geminorum\gEditorial\WordPress;

class Suited extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\DashboardSummary;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'suited',
			'title'  => _x( 'Suited', 'Modules: Suited', 'geditorial' ),
			'desc'   => _x( 'Suitable Targets for Contents', 'Modules: Suited', 'geditorial' ),
			'icon'   => 'superhero-alt',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$terms = WordPress\Taxonomy::listTerms( $this->constant( 'main_taxonomy' ) );
		$roles = $this->get_settings_default_roles();
		$empty = $this->get_taxonomy_label( 'main_taxonomy', 'no_terms',
			_x( 'There are no suitable targets available!', 'Setting', 'geditorial-suited' ) );

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Manage Roles', 'Setting Title', 'geditorial-suited' ),
					'description' => _x( 'Roles that can Manage, Edit and Delete Suitable Targets.', 'Setting Description', 'geditorial-suited' ),
					'values'      => $roles,
				],
				[
					'field'       => 'assign_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Assign Roles', 'Setting Title', 'geditorial-suited' ),
					'description' => _x( 'Roles that can Assign Suitable Targets.', 'Setting Description', 'geditorial-suited' ),
					'values'      => $roles,
				],
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-suited' ),
					'description' => _x( 'Roles that can see Suitable Targets Reports.', 'Setting Description', 'geditorial-suited' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Restricted Roles', 'Setting Title', 'geditorial-suited' ),
					'description' => _x( 'Roles that check for Suitable Targets visibility.', 'Setting Description', 'geditorial-suited' ),
					'values'      => $roles,
				],
				[
					'field'       => 'restricted',
					'type'        => 'select',
					'title'       => _x( 'Restricted Targets', 'Setting Title', 'geditorial-suited' ),
					'description' => _x( 'Handles visibility of each target based on meta values.', 'Setting Description', 'geditorial-suited' ),
					'default'     => 'disabled',
					'values'      => [
						'disabled' => _x( 'Disabled', 'Setting Option', 'geditorial-suited' ),
						'hidden'   => _x( 'Hidden', 'Setting Option', 'geditorial-suited' ),
					],
				],
			],
			'_supports' => [
				'shortcode_support',
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
			'main_taxonomy'  => 'suitable_target',
			'main_shortcode' => 'suitable',
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
				'main_taxonomy' => _n_noop( 'Suitable Target', 'Suitable Targets', 'geditorial-suited' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'show_option_all'      => _x( 'Suitable', 'Label: Show Option All', 'geditorial-suited' ),
					'show_option_no_items' => _x( '(Unsuitable)', 'Label: Show Option No Terms', 'geditorial-suited' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		// $strings['misc'] = [];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'main_taxonomy', [
			'hierarchical' => TRUE,
			'show_in_menu' => FALSE,
			'meta_box_cb'  => '__checklist_restricted_terms_callback',
		] );

		$this->filter( 'map_meta_cap', 4 );

		$this->register_shortcode( 'main_shortcode' );
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

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return Shortcode::listPosts( 'assigned',
			'post',
			$this->constant( 'main_taxonomy' ),
			array_merge( [
				'posttypes' => $this->posttypes(),
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode' )
		);
	}
}

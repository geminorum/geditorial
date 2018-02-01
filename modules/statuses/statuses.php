<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\L10n;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\User;
use geminorum\gEditorial\WordPress\Taxonomy;

class Statuses extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	private $map_caps = FALSE;

	public static function module()
	{
		return [
			'name'     => 'statuses',
			'title'    => _x( 'Statuses', 'Modules: Statuses', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Custom Post Statuses', 'Modules: Statuses', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'post-status',
			'disabled' => class_exists( 'WP_Statuses' ) ? FALSE : _x( 'Needs WP Statuses', 'Modules: Statuses', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	protected function get_global_settings()
	{
		$roles    = User::getAllRoleList();
		$exclude  = [ 'administrator', 'subscriber' ];
		$statuses = Taxonomy::getTerms( $this->constant( 'status_tax' ), FALSE, TRUE );

		$settings = [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'status_menus',
					'title'       => _x( 'Status Menu', 'Modules: Statuses: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Adds status links to the admin submenus for each supported posttype.', 'Modules: Statuses: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				// [
				// 	'field'       => 'status_restrict',
				// 	'title'       => _x( 'Restrict by Status', 'Modules: Statuses: Setting Title', GEDITORIAL_TEXTDOMAIN ),
				// 	'description' => _x( 'Filters default status of posts based on status role meta.', 'Modules: Statuses: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				// ],
				[
					'field' => 'map_status_roles',
					'title' => _x( 'Map Status Roles', 'Modules: Statuses: Setting Title', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'   => 'default_status',
					'title'   => _x( 'Default Status', 'Modules: Statuses: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'type'    => 'select',
					'default' => 'draft',
					'values'  => [ 'draft' => __( 'Draft' ) ] + wp_list_pluck( $statuses, 'name', 'slug' ),
				],
			],
		];

		foreach ( $statuses as $status )
			$settings['_roles'][] = [
				'field'       => 'status_roles_'.$status->term_id,
				'type'        => 'checkbox',
				'title'       => sprintf( _x( 'Roles for %s', 'Modules: Statuses: Setting Title', GEDITORIAL_TEXTDOMAIN ), $status->name ),
				'description' => sprintf( _x( 'The <b>%s</b> status will be visibile to the selected roles.', 'Modules: Statuses: Setting Description', GEDITORIAL_TEXTDOMAIN ), $status->name ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'status_tax' => 'custom_status',
		];
	}

	public function init()
	{
		parent::init();

		register_taxonomy(
			$this->constant( 'status_tax' ),
			[],
			[
				'label'        => _x( 'Statuses', 'Modules: Statuses', GEDITORIAL_TEXTDOMAIN ),
				'show_ui'      => $this->cuc( 'settings' ),
				'public'       => FALSE,
				'meta_box_cb'  => FALSE,
				'capabilities' => [
					'manage_terms' => $this->caps['settings'],
					'edit_terms'   => $this->caps['settings'],
					'delete_terms' => $this->caps['settings'],
					'assign_terms' => $this->caps['settings'],
				],
			]
		);

		$statuses = Taxonomy::getTerms( $this->constant( 'status_tax' ), FALSE, TRUE );

		foreach ( $statuses as $status ) {

			$can = $this->role_can( 'status', NULL, FALSE, TRUE, '_roles_'.$status->term_id );

			$args = [

				'public'      => TRUE,
				'label'       => $status->name,
				'label_count' => L10n::getNooped( $status->name.' <span class="count">(%s)</span>', $status->name.' <span class="count">(%s)</span>' ),

				'show_in_admin_all_list'    => TRUE,
				'show_in_admin_status_list' => $can,

				// WP Statuses specific args
				'show_in_metabox_dropdown'    => $can,
				'show_in_inline_dropdown'     => $can,
				'show_in_press_this_dropdown' => $can,

				// FIXME: check for posttype meta
				'post_type' => $this->post_types(),

				// FIXME: check for posttype icon
				// 'dashicon' => 'dashicons-archive',

				'labels' => [
					'metabox_dropdown' => $status->name,
					'inline_dropdown'  => $status->name,

					'metabox_submit'     => sprintf( _x( 'Submit: %s', 'Modules: Statuses: Metabox Submit', GEDITORIAL_TEXTDOMAIN ), $status->name ),
					'metabox_save_on'    => sprintf( _x( 'Save as %s on:', 'Modules: Statuses: Metabox Save On', GEDITORIAL_TEXTDOMAIN ), $status->name ),
					'metabox_save_date'  => sprintf( _x( 'Save as %s on: %s', 'Modules: Statuses: Metabox Save Date', GEDITORIAL_TEXTDOMAIN ), $status->name, '<b>%1$s</b>' ),
					'metabox_saved_on'   => sprintf( _x( 'Saved as %s on:', 'Modules: Statuses: Metabox Saved On', GEDITORIAL_TEXTDOMAIN ), $status->name ),
					'metabox_saved_date' => sprintf( _x( 'Saved as %s on: %s', 'Modules: Statuses: Metabox Saved Date', GEDITORIAL_TEXTDOMAIN ), $status->name, '<b>%1$s</b>' ),
					'metabox_save_now'   => sprintf( _x( 'Save as %s <b>now</b>', 'Modules: Statuses: Metabox Saved On', GEDITORIAL_TEXTDOMAIN ), $status->name ),
					// 'metabox_save_later' => sprintf( _x( 'Schedule for: %s', 'Modules: Statuses: Metabox Saved On', GEDITORIAL_TEXTDOMAIN ), '<b>%1$s</b>' ),
				],
			];

			register_post_status( $status->slug, $args );
		}

		$this->filter( 'wp_statuses_get_registered_post_types', 2 );
		$this->filter( 'wp_insert_post_data', 2 );

		if ( $this->get_setting( 'map_status_roles' ) )
			$this->filter( 'map_meta_cap', 4 );

		if ( ! is_admin() )
			$this->action( 'pre_get_posts', 1, 12, 'front' );
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		$map = $this->get_map_caps();
		return isset( $map[$cap] ) ? [ $map[$cap] ] : $caps;
	}

	// HACK: WP-Statuses only allows `publish_posts` to change the current status
	private function get_map_caps()
	{
		if ( $this->map_caps )
			return $this->map_caps;

		$this->map_caps = [];

		foreach ( $this->post_types() as $posttype ) {
			$object = get_post_type_object( $posttype );
			$this->map_caps[$object->cap->publish_posts] = $object->cap->edit_posts;
		}

		return $this->map_caps;
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'status_menus' ) ) {

			$statuses = get_post_stati( [ 'show_in_admin_status_list' => TRUE ], 'objects' );

			foreach ( $this->post_types() as $posttype ) {

				$object = get_post_type_object( $posttype );

				if ( ! current_user_can( $object->cap->edit_others_posts ) )
				// if ( ! current_user_can( $object->cap->edit_posts ) )
					continue;

				$counts = wp_count_posts( $posttype );

				$menu = 'post' === $posttype
					? 'edit.php'
					: 'edit.php?post_type='.$posttype;

				foreach ( $statuses as $status )
					if ( in_array( $posttype, $status->post_type ) ) // added by WP-Statuses
						if ( $counts->{$status->name} > 0 )
							$GLOBALS['submenu'][$menu][] = [
								sprintf( '%1$s <span class="awaiting-mod" data-count="%3$s"><span class="pending-count">%2$s</span></span>',
									HTML::escape( $status->label ),
									Number::format( $counts->{$status->name} ),
									$counts->{$status->name} ),
								'read',
								sprintf( 'edit.php?post_status=%1$s&post_type=%2$s', $status->name, $posttype ),
							];
			}
		}

		if ( $tax = get_taxonomy( $this->constant( 'status_tax' ) ) )
			add_options_page(
				HTML::escape( $tax->labels->menu_name ),
				HTML::escape( $tax->labels->menu_name ),
				$tax->cap->manage_terms,
				'edit-tags.php?taxonomy='.$tax->name
			);
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'status_tax' ) == $screen->taxonomy ) {

			add_filter( 'parent_file', function(){
				return 'options-general.php';
			} );

			if ( 'edit-tags' == $screen->base )
				add_filter( 'manage_edit-'.$this->constant( 'status_tax' ).'_columns', [ $this, 'manage_columns' ] );

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

			} else if ( 'edit' == $screen->base ) {

				// if ( $this->get_setting( 'status_restrict' ) )
				// 	$this->action( 'pre_get_posts', 1, 20, 'admin' );

				$this->filter( 'display_post_states', 2 ); // FIXME: add setting
			}
		}
	}

	public function manage_columns( $columns )
	{
		unset( $columns['posts'] );
		return $columns;
	}

	public function pre_get_posts_admin( &$wp_query )
	{
 		if ( ! $wp_query->is_admin )
			return;

		if ( $status = $wp_query->get( 'post_status' ) )
			return;

		if ( ! $posttype = $wp_query->get( 'post_type' ) )
			return;

		if ( ! in_array( $posttype, $this->post_types() ) )
			return;

		$args = [
			'hide_empty'      => FALSE,
			'suppress_filter' => TRUE,
			'meta_query'      => [ [
				'key'     => 'role',
				'value'   => User::getRoles(),
				'compare' => 'IN'
			] ],
		];

		// FIXME: use `WP_Term_Query` directly
		$terms = get_terms( $this->constant( 'status_tax' ), $args );

		if ( empty( $terms ) )
			return;

		$wp_query->set( 'post_status', $terms[0]->slug );
		$_REQUEST['post_status'] = $terms[0]->slug;
	}

	// CAUTION: should at least keep `draft` & `pending`
	// @REF: https://gist.github.com/imath/2b6d2ce1ead6aba11c8ad12c6beb4770
	public function wp_statuses_get_registered_post_types( $posttypes, $status )
	{
		if ( in_array( $status, [ 'draft', 'pending' ] ) ) // FIXME: add setting with core statuses
			return $posttypes;

		// all other statuses won't be applied to our posttypes
		return array_diff( $posttypes, $this->post_types() );
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		if ( in_array( $data['post_status'], [ 'trash', 'private', 'auto-draft' ] ) )
			return $data;

		if ( ! in_array( $data['post_type'], $this->post_types() ) )
			return $data;

		if ( empty( $postarr['original_post_status'] ) ) {
			$data['post_status'] = $this->get_setting( 'default_status', 'draft' );
			return $data;
		}

		$original = $postarr['original_post_status'];
		$allowed  = get_post_stati( [ 'show_in_admin_status_list' => TRUE ] );

		// saved from auto-draft
		if ( in_array( $original, [ 'trash', 'private', 'auto-draft' ] ) ) {

			if ( ! in_array( $data['post_status'], $allowed ) )
				$data['post_status'] = $this->get_setting( 'default_status', 'draft' );

			return $data;
		}

		// saved from old status
		if ( ! in_array( $original, $allowed ) )
			$data['post_status'] = $original; // revert

		else if ( empty( $postarr['_wp_statuses_status'] ) )
			$data['post_status'] = $this->get_setting( 'default_status', 'draft' );

		else if ( in_array( $postarr['_wp_statuses_status'], $allowed ) )
			$data['post_status'] = sanitize_key( $postarr['_wp_statuses_status'] );

		else
			$data['post_status'] = $this->get_setting( 'default_status', 'draft' );

		return $data;
	}

	public function display_post_states( $states, $post )
	{
		// bail if the view is set
		if ( self::req( 'post_status' ) )
			return $states;

		$statuses = get_post_stati( [], 'objects' );

		if ( array_key_exists( $post->post_status, $statuses ) )
			return [ $post->post_status => $statuses[$post->post_status]->label ] + $states;

		return $states;
	}

	public function pre_get_posts_front( &$wp_query )
	{
		if ( ! $wp_query->is_main_query() )
			return;

		if ( in_array( $wp_query->get( 'post_type', 'post' ), $this->post_types() ) )
			$wp_query->set( 'post_status', 'publish' ); // FIXME: add settings for this
	}
}

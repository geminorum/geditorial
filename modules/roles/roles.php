<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\User;

class Roles extends gEditorial\Module
{
	protected $caps = [
		'default' => 'manage_options',
	];

	protected $priority_admin_menu = 99;

	public static function module()
	{
		return [
			'name'  => 'roles',
			'title' => _x( 'Roles', 'Modules: Roles', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Member & Role Managment', 'Modules: Roles', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'businessman',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'author_posttags',
					'title'       => _x( 'Tags for Authors', 'Modules: Roles: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Allows Authors to manage post tags.', 'Modules: Roles: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'duplicate_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Duplicate Roles', 'Modules: Roles: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles to duplicate as editorial roles. Though Administrators have it all!', 'Modules: Roles: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => [ 'administrator', 'subscriber' ],
					'values'      => $this->get_roles_support_duplicate(),
				],
				[
					'field'       => 'editorial_posttypes',
					'type'        => 'posttypes',
					'title'       => _x( 'Editorial Posttypes', 'Modules: Roles: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Posttypes to handle via Editorial roles.', 'Modules: Roles: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_posttypes_support_editorial(),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'base_type'   => [ 'editorial', 'editorials' ],
			'base_prefix' => 'editorial_',
		];
	}

	private function get_roles_support_duplicate()
	{
		$caps   = [];
		$prefix = $this->constant( 'base_prefix' );

		foreach ( User::getAllRoleList() as $role => $title )
			if ( ! Text::has( $role, $prefix ) )
				$caps[$role] = $title;

		return $caps;
	}

	private function get_posttypes_support_editorial()
	{
		$posttypes = [];
		$supported = get_post_types_by_support( 'editorial-roles' );
		$excludes  = [
			'profile', // gPeople
		];

		foreach ( PostType::get( 0, [ 'public' => TRUE, '_builtin' => FALSE ] ) as $post_type => $label )
			if ( in_array( $post_type, $supported )
				&& ! in_array( $post_type, $excludes ) )
					$posttypes[$post_type] = $label;

		return $posttypes;
	}

	public function before_settings( $module = FALSE )
	{
		//FIXME: WTF: nonce!

		if ( isset( $_POST['add_default_roles'] ) )
			$this->add_default_roles();

		if ( isset( $_POST['add_defaults_to_editor'] ) )
			$this->add_default_caps( 'editor' );

		else if ( isset( $_POST['remove_default_roles'] ) )
			$this->remove_default_roles();
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'add_default_roles', _x( 'Add Default Roles', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
		$this->register_button( 'add_defaults_to_editor', _x( 'Add Default Caps to Editor Role', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
		$this->register_button( 'remove_default_roles', _x( 'Remove Default Roles', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'delete' );
	}

	public function init()
	{
		parent::init();

		$this->filter( 'map_meta_cap', 4 );
	}

	// hack to hide shared tag submenu on other cpt
	public function admin_menu()
	{
		global $menu;

		foreach ( $menu as $offset => $item )
			if ( Text::has( $item[2], 'edit-tags.php' ) && ! current_user_can( $item[1] ) )
				unset( $menu[$offset] );
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
	}

	// OVERWRITE
	public function post_types( $post_types = NULL )
	{
		$supported = $this->get_setting( 'editorial_posttypes', [] );

		if ( is_null( $post_types ) )
			return $supported;

		foreach ( (array) $post_types as $post_type )
			if ( in_array( $post_type, $supported ) )
				return TRUE;

		return FALSE;
	}

	// @SEE: https://developer.wordpress.org/?p=1109
	private function map( $base = NULL )
	{
		if ( is_null( $base ) )
			$base = $this->constant( 'base_type' );

		return [

			// mapped, no need to add them to ours
			// 'edit_post'   => 'edit_'.$base[0],
			// 'read_post'   => 'read_'.$base[0],
			// 'delete_post' => 'delete_'.$base[0],

			// must add to roles
			'edit_posts'             => 'edit_'.$base[1],
			'edit_others_posts'      => 'edit_others_'.$base[1],
			'publish_posts'          => 'publish_'.$base[1],
			'read_private_posts'     => 'read_private_'.$base[1],
			'read'                   => 'read',
			'delete_posts'           => 'delete_'.$base[1],
			'delete_private_posts'   => 'delete_private_'.$base[1],
			'delete_published_posts' => 'delete_published_'.$base[1],
			'delete_others_posts'    => 'delete_others_'.$base[1],
			'edit_private_posts'     => 'edit_private_'.$base[1],
			'edit_published_posts'   => 'edit_published_'.$base[1],
		];
	}

	// @REF: http://justintadlock.com/?p=2462
	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		$base = $this->constant( 'base_type' );

		if ( in_array( $cap, [ 'manage_post_tags', 'edit_post_tags', 'delete_post_tags' ] ) ) {

			if ( $this->get_setting( 'author_posttags', FALSE ) )
				return user_can( $user_id, 'publish_posts' ) ? [ 'publish_posts' ] : [ 'publish_'.$base[1] ];

			else
				return user_can( $user_id, 'manage_categories' ) ? $caps : [ 'edit_others_'.$base[1] ];
		}

		if ( 'assign_post_tags' == $cap )
			return user_can( $user_id, 'edit_posts' ) ? $caps : [ 'edit_'.$base[1] ];

		if ( 'edit_'.$base[0] == $cap || 'delete_'.$base[0] == $cap || 'read_'.$base[0] == $cap ) {

			$post = get_post( $args[0] );
			$type = get_post_type_object( $post->post_type );
			$caps = [];

		} else {

			return $caps; // bailing!
		}

		if ( 'edit_'.$base[0] == $cap ) {

			if ( $user_id == $post->post_author )
				$caps[] = $type->cap->edit_posts;

			else
				$caps[] = $type->cap->edit_others_posts;

		} else if ( 'delete_'.$base[0] == $cap ) {

			if ( $user_id == $post->post_author )
				$caps[] = $type->cap->delete_posts;

			else
				$caps[] = $type->cap->delete_others_posts;

		} else if ( 'read_'.$base[0] == $cap ) {

			if ( 'private' != $post->post_status )
				$caps[] = 'read';

			else if ( $user_id == $post->post_author )
				$caps[] = 'read';

			else
				$caps[] = $type->cap->read_private_posts;
		}

		return $caps;
	}

	private function add_default_roles()
	{
		$count  = 0;
		$roles  = User::getRoleList();
		$prefix = $this->constant( 'base_prefix' );
		$map    = $this->map();

		foreach ( $this->get_setting( 'duplicate_roles', [] ) as $core ) {

			$object = get_role( $core );

			if ( is_null( $object ) )
				continue;

			$title = sprintf( _x( 'Editorial: %s', 'Modules: Roles', GEDITORIAL_TEXTDOMAIN ), translate_user_role( $roles[$core] ) );
			$role  = add_role( $prefix.$core, $title );

			if ( is_null( $role ) )
				continue;

			foreach ( $map as $cap => $editorial )
				if ( $object->has_cap( $cap ) )
					$role->add_cap( $editorial );

			$count++;
		}

		// add default caps into administrator
		$this->add_default_caps( 'administrator' );

		return $count;
	}

	private function add_default_caps( $role )
	{
		$count  = 0;
		$object = get_role( $role );

		foreach ( $this->map() as $cap => $editorial )
			if ( $object->has_cap( $cap ) )
				if ( $object->add_cap( $editorial ) )
					$count++;

		return $count;
	}

	private function remove_default_caps( $role )
	{
		$count  = 0;
		$object = get_role( $role );

		foreach ( $this->map() as $cap => $editorial )
			if ( $object->remove_cap( $editorial ) )
					$count++;

		return $count;
	}

	private function remove_default_roles()
	{
		$count  = 0;
		$prefix = $this->constant( 'base_prefix' );

		foreach ( $this->get_setting( 'duplicate_roles', [] ) as $core )
			if ( remove_role( $prefix.$core ) )
				$count++;

		// removes default caps from other roles
		$this->remove_default_caps( 'administrator' );
		$this->remove_default_caps( 'editor' );

		return $count;
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( isset( $_POST['add_default_roles'] ) ) {
					$this->add_default_roles();

				} else if ( isset( $_POST['add_defaults_to_editor'] ) ) {
					$this->add_default_caps( 'editor' );

				} else if ( isset( $_POST['remove_default_roles'] ) ) {
					$this->remove_default_roles();
				}
			}
		}
	}

	public function tools_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Editorial Roles', 'Modules: Roles', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'Current Roles', 'Modules: Roles', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			Settings::submitButton( 'check_current_roles', _x( 'Check Roles', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ), TRUE );
			Settings::submitButton( 'check_current_caps', _x( 'Check Capabilities', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
			Settings::submitButton( 'add_default_roles', _x( 'Add Defaults', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
			Settings::submitButton( 'add_defaults_to_editor', _x( 'Add Default Caps to Editors', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
			Settings::submitButton( 'remove_default_roles', _x( 'Remove Defaults', 'Modules: Roles: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'delete' );

			if ( isset( $_POST['check_current_roles'] ) )
				echo HTML::tableCode( User::getRoleList(), TRUE );

			if ( isset( $_POST['check_current_caps'] ) ) {
				$prefix = $this->constant( 'base_prefix' );
				foreach ( $this->get_setting( 'duplicate_roles', [] ) as $core ) {
					$role = get_role( $prefix.$core );
					echo HTML::tableCode( $role->capabilities, TRUE, $role->name );
				}
			}

			echo '</td></tr>';
			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}
}

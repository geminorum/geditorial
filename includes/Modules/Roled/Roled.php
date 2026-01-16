<?php namespace geminorum\gEditorial\Modules\Roled;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Roled extends gEditorial\Module
{

	protected $caps = [
		'default' => 'manage_options',
	];

	protected $priority_admin_menu = 99;

	public static function module()
	{
		return [
			'name'   => 'roled',
			'title'  => _x( 'Roled', 'Modules: Roled', 'geditorial-admin' ),
			'desc'   => _x( 'Member & Role Management', 'Modules: Roled', 'geditorial-admin' ),
			'icon'   => 'privacy',
			'i18n'   => 'adminonly',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$roles   = $this->get_roles_support_duplicate();
		$exclude = [ 'administrator', 'subscriber' ];

		$settings = [
			'_general' => [
				[
					'field'       => 'editorial_posttypes',
					'type'        => 'posttypes',
					'title'       => _x( 'Editorial Posttypes', 'Setting Title', 'geditorial-roled' ),
					'description' => _x( 'Posttypes to handle via Editorial roles.', 'Setting Description', 'geditorial-roled' ),
					'values'      => $this->get_posttypes_support_editorial(),
				],
				[
					'field'       => 'duplicate_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Duplicate Roles', 'Setting Title', 'geditorial-roled' ),
					'description' => _x( 'Roles to duplicate as Editorial Roles.', 'Setting Description', 'geditorial-roled' ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
			],
			'_misc' => [
				[
					'field'       => 'author_posttags',
					'title'       => _x( 'Tags for Authors', 'Setting Title', 'geditorial-roled' ),
					'description' => _x( 'Allows Authors to manage post tags.', 'Setting Description', 'geditorial-roled' ),
				],
				[
					'field'       => 'disable_builtin_post',
					'type'        => 'disabled',
					'title'       => _x( 'Built-in Post & Category', 'Setting Title', 'geditorial-roled' ),
					'description' => _x( 'Removes access to the builtin post post-type and category taxonomy on the admin.', 'Setting Description', 'geditorial-roled' ),
				],
			],
		];

		foreach ( $this->get_setting( 'duplicate_roles', [] ) as $role )
			$settings['_general'][] = [
				'field' => 'role_name_'.$role,
				'type'  => 'text',
				'title' => sprintf(
					/* translators: `%s`: role name */
					_x( 'Role Name for %s', 'Setting Title', 'geditorial-roled' ),
					$roles[$role]
				),
				'description' => _x( 'Custom name for the duplicated role.', 'Setting Description', 'geditorial-roled' ),
				'default'     => sprintf(
					/* translators: `%s`: role name */
					_x( 'Editorial: %s', 'Setting Default', 'geditorial-roled' ),
					$roles[$role]
				),
			];

		return $settings;
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

		foreach ( $this->get_settings_default_roles() as $role => $title )
			if ( ! Core\Text::has( $role, $prefix ) )
				$caps[$role] = $title;

		return $caps;
	}

	private function get_posttypes_support_editorial()
	{
		$posttypes = [];
		$supported = get_post_types_by_support( 'editorial-roles' );
		$excludes  = [];

		foreach ( WordPress\PostType::get( 0, [ 'show_ui' => TRUE, '_builtin' => FALSE ] ) as $posttype => $label )
			if ( in_array( $posttype, $supported )
				&& ! in_array( $posttype, $excludes ) )
					$posttypes[$posttype] = $label;

		return $posttypes;
	}

	public function after_setup_theme()
	{
		if ( $this->get_setting( 'disable_builtin_post' ) ) {
			$this->filter( 'register_post_post_type_args', 2, 12, 'disable' );     // @since WP 6.0.0
			$this->filter( 'register_category_taxonomy_args', 2, 12, 'disable' );  // @since WP 6.0.0

			$this->filter_append( $this->hook_base( 'posttypes_excluded' ), 'post' );
			$this->filter_append( $this->hook_base( 'taxonomies_excluded' ), 'category' );
		}
	}

	public function init()
	{
		parent::init();
		$this->filter( 'map_meta_cap', 4 );
	}

	// NOTE: hacked to hide shared tag sub-menu on other post-types
	public function admin_menu()
	{
		global $menu;

		foreach ( $menu as $offset => $item )
			if ( Core\Text::has( $item[2], 'edit-tags.php' ) && ! current_user_can( $item[1] ) )
				unset( $menu[$offset] );
	}

	// OVERWRITE
	public function post_types( $posttypes = NULL )
	{
		$supported = $this->get_setting( 'editorial_posttypes', [] );

		if ( is_null( $posttypes ) )
			return $supported;

		foreach ( (array) $posttypes as $posttype )
			if ( in_array( $posttype, $supported ) )
				return TRUE;

		return FALSE;
	}

	// @REF: https://wordpress.stackexchange.com/a/293207
	public function register_post_post_type_args_disable( $args, $posttype_name )
	{
		$args['capabilities'] = [
            'create_posts'           => 'do_not_allow',
            'delete_others_posts'    => 'do_not_allow',
            'delete_posts'           => 'do_not_allow',
            'delete_private_posts'   => 'do_not_allow',
            'delete_published_posts' => 'do_not_allow',
            'edit_others_posts'      => 'do_not_allow',
            'edit_posts'             => 'do_not_allow',
            'edit_private_posts'     => 'do_not_allow',
            'edit_published_posts'   => 'do_not_allow',
            'publish_posts'          => 'do_not_allow',
            'read'                   => 'do_not_allow',
		];

		return $args;
	}

	// TODO: maybe `rewrite`/`publicly_queryable` FALSE
	public function register_category_taxonomy_args_disable( $args, $taxonomy )
	{
		$args['capabilities'] = [
            'manage_terms' => 'do_not_allow',
            'edit_terms'   => 'do_not_allow',
            'delete_terms' => 'do_not_allow',
            'assign_terms' => 'do_not_allow',
		];

		return $args;
	}

	// @SEE: https://developer.wordpress.org/?p=1109
	private function map( $base = NULL )
	{
		if ( is_null( $base ) )
			$base = $this->constant( 'base_type' );

		return [

			// Already mapped, no need to add them to this.
			// 'edit_post'   => 'edit_'.$base[0],
			// 'read_post'   => 'read_'.$base[0],
			// 'delete_post' => 'delete_'.$base[0],

			// must add to roles
			'create_posts'           => 'create_'.$base[1],
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
		list( $singular, $plural ) = $this->constant( 'base_type' );

		if ( in_array( $cap, [ 'manage_post_tags', 'edit_post_tags', 'delete_post_tags' ] ) ) {

			if ( $this->get_setting( 'author_posttags', FALSE ) )
				return user_can( $user_id, 'publish_posts' ) ? [ 'publish_posts' ] : [ 'publish_'.$plural ];

			else
				return user_can( $user_id, 'manage_categories' ) ? $caps : [ 'edit_others_'.$plural ];
		}

		if ( 'assign_post_tags' == $cap )
			return user_can( $user_id, 'edit_posts' ) ? $caps : [ 'edit_'.$plural ];

		if ( 'edit_'.$singular == $cap
			|| 'delete_'.$singular == $cap
			|| 'read_'.$singular == $cap ) {

			$post = WordPress\Post::get( $args[0] );
			$type = WordPress\PostType::object( $post->post_type );
			$caps = [];

		} else {

			return $caps; // bailing!
		}

		if ( 'edit_'.$singular == $cap ) {

			if ( $user_id == $post->post_author )
				$caps[] = $type->cap->edit_posts;

			else
				$caps[] = $type->cap->edit_others_posts;

		} else if ( 'delete_'.$singular == $cap ) {

			if ( $user_id == $post->post_author )
				$caps[] = $type->cap->delete_posts;

			else
				$caps[] = $type->cap->delete_others_posts;

		} else if ( 'read_'.$singular == $cap ) {

			if ( 'private' != $post->post_status )
				$caps[] = 'read';

			else if ( $user_id == $post->post_author )
				$caps[] = 'read';

			else
				$caps[] = $type->cap->read_private_posts;
		}

		return $caps;
	}

	protected function handle_settings_extra_buttons( $module )
	{
		if ( isset( $_POST['duplicate_default_roles'] ) )
			$this->duplicate_default_roles();

		else if ( isset( $_POST['add_defaults_to_editor'] ) )
			$this->add_default_caps( 'editor' );

		else if ( isset( $_POST['remove_duplicate_roles'] ) )
			$this->remove_duplicate_roles();

		else if ( isset( $_POST['add_theme_to_editor'] ) )
			$this->add_theme_caps( 'editor' );
	}

	protected function register_settings_extra_buttons( $module )
	{
		$this->register_button( 'duplicate_default_roles', _x( 'Duplicate Default Roles', 'Button', 'geditorial-roled' ) );
		$this->register_button( 'remove_duplicate_roles', _x( 'Remove Duplicated Roles', 'Button', 'geditorial-roled' ), 'danger' );
		$this->register_button( 'add_defaults_to_editor', _x( 'Add Default Caps to Editor Role', 'Button', 'geditorial-roled' ) );

		$this->register_button( 'add_theme_to_editor', _x( 'Add Theme Caps to Editor Role', 'Button', 'geditorial-roled' ) );
	}

	private function duplicate_default_roles()
	{
		$count  = 0;
		$roles  = $this->get_settings_default_roles();
		$prefix = $this->constant( 'base_prefix' );
		$map    = $this->map();

		foreach ( $this->get_setting( 'duplicate_roles', [] ) as $core ) {

			// already added
			if ( get_role( $prefix.$core ) )
				continue;

			$object = get_role( $core );

			if ( is_null( $object ) )
				continue;

			if ( ! $name = $this->get_setting( 'role_name_'.$core, FALSE ) )
				$name = sprintf(
					/* translators: `%s`: role name */
					_x( 'Editorial: %s', 'Setting Default', 'geditorial-roled' ),
					translate_user_role( $roles[$core] )
				);

			$role = add_role( $prefix.$core, $name );

			if ( is_null( $role ) )
				continue;

			foreach ( $object->capabilities as $cap => $grant )
				$role->add_cap( ( array_key_exists( $cap, $map ) ? $map[$cap] : $cap ), $grant );

			$role->add_cap( 'read' ); // duh?!

			++$count;
		}

		// add default caps into administrator
		$this->add_default_caps( 'administrator' );

		return $count;
	}

	private function add_default_caps( $role )
	{
		if ( ! $object = get_role( $role ) )
			return FALSE;

		$count  = 0;

		foreach ( $this->map() as $cap => $editorial )
			if ( $object->has_cap( $cap ) )
				if ( $object->add_cap( $editorial ) )
					++$count;

		return $count;
	}

	private function remove_default_caps( $role )
	{
		if ( ! $object = get_role( $role ) )
			return FALSE;

		$count  = 0;

		foreach ( $this->map() as $cap => $editorial )
			if ( $object->remove_cap( $editorial ) )
				++$count;

		return $count;
	}

	private function remove_duplicate_roles()
	{
		$count  = 0;
		$prefix = $this->constant( 'base_prefix' );

		foreach ( $this->get_setting( 'duplicate_roles', [] ) as $core )
			if ( remove_role( $prefix.$core ) )
				++$count;

		// Removes default caps from other roles.
		$this->remove_default_caps( 'administrator' );
		$this->remove_default_caps( 'editor' );

		return $count;
	}

	// FIXME: move this to Network
	private function add_theme_caps( $role )
	{
		if ( ! $object = get_role( $role ) )
			return FALSE;

		$object->add_cap( 'edit_theme_options' );

		return TRUE;
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( gEditorial\Tablelist::isAction( 'duplicate_default_roles' ) ) {

					$this->duplicate_default_roles();

				} else if ( gEditorial\Tablelist::isAction( 'add_defaults_to_editor' ) ) {

					$this->add_default_caps( 'editor' );

				} else if ( gEditorial\Tablelist::isAction( 'remove_duplicate_roles' ) ) {

					$this->remove_duplicate_roles();
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		Core\HTML::h3( _x( 'Editorial Roles', 'Header', 'geditorial-roled' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Current Roles', 'Header', 'geditorial-roled' ).'</th><td>';

		gEditorial\Settings::submitButton( 'check_current_roles', _x( 'Check Roles', 'Button', 'geditorial-roled' ), TRUE );
		gEditorial\Settings::submitButton( 'check_current_caps', _x( 'Check Capabilities', 'Button', 'geditorial-roled' ) );
		gEditorial\Settings::submitButton( 'duplicate_default_roles', _x( 'Duplicate Defaults', 'Button', 'geditorial-roled' ) );
		gEditorial\Settings::submitButton( 'add_defaults_to_editor', _x( 'Add Default Caps to Editors', 'Button', 'geditorial-roled' ) );
		gEditorial\Settings::submitButton( 'remove_duplicate_roles', _x( 'Remove Duplicates', 'Button', 'geditorial-roled' ), 'danger' );

		if ( isset( $_POST['check_current_roles'] ) )
			echo Core\HTML::tableCode( $this->get_settings_default_roles(), TRUE );

		if ( isset( $_POST['check_current_caps'] ) ) {
			$prefix = $this->constant( 'base_prefix' );
			foreach ( $this->get_setting( 'duplicate_roles', [] ) as $core ) {
				$role = get_role( $prefix.$core );
				echo Core\HTML::tableCode( $role->capabilities, TRUE, $role->name );
			}
		}

		echo '</td></tr>';
		echo '</table>';
	}
}

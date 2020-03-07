<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Cartable extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;
	protected $priority_admin_menu  = 90;

	protected $support_users  = FALSE;
	protected $support_groups = FALSE;
	protected $support_types  = FALSE;
	protected $before_terms   = [];

	public static function module()
	{
		return [
			'name'     => 'cartable',
			'title'    => _x( 'Cartable', 'Modules: Cartable', 'geditorial' ),
			'desc'     => _x( 'Customized Content Folders', 'Modules: Cartable', 'geditorial' ),
			'icon'     => 'portfolio',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$roles   = User::getAllRoleList();
		$exclude = [ 'administrator', 'subscriber' ];

		$settings = [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'support_users',
					'title'       => _x( 'User Cartables', 'Modules: Cartable: Setting Title', 'geditorial' ),
					'description' => _x( 'Enables cartables based on registered users.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				],
				[
					'field'       => 'support_groups',
					'title'       => _x( 'Group Cartables', 'Modules: Cartable: Setting Title', 'geditorial' ),
					'description' => _x( 'Enables cartables based on custom groups. Needs <i>Users</i> module.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				],
				[
					'field'       => 'support_types',
					'title'       => _x( 'Type Cartables', 'Modules: Cartable: Setting Title', 'geditorial' ),
					'description' => _x( 'Enables cartables based on custom types.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				],
			],
		];

		if ( $this->support_users )
			$settings['_roles'][] = [
				'field'       => 'map_cap_user',
				'title'       => _x( 'Map User Capabilities', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Gives access to edit posts based on user cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'map_cap_group',
				'title'       => _x( 'Map Group Capabilities', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Gives access to edit posts based on group cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
			];

		if ( $this->support_users )
			$settings['_roles']['excluded_roles'] = _x( 'Roles that excluded from cartables.', 'Modules: Cartable: Setting Description', 'geditorial' );

		if ( $this->support_users )
			$settings['_roles'][] = [
				'field'       => 'view_user_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'View User Cartable', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that can view user cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'view_group_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'View Group Cartable', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that can view group cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_types )
			$settings['_roles'][] = [
				'field'       => 'view_type_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'View Type Cartable', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that can view type cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_users )
			$settings['_roles'][] = [
				'field'       => 'assign_user_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign User Cartables', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that can assign user cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'assign_group_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign Group Cartables', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that can assign gorup cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_types )
			$settings['_roles'][] = [
				'field'       => 'assign_type_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign Type Cartables', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that can assign type cartables.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'restricted_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Restricted Groups', 'Modules: Cartable: Setting Title', 'geditorial' ),
				'description' => _x( 'Roles that restricted to their group users.', 'Modules: Cartable: Setting Description', 'geditorial' ),
				'exclude'     => $exclude,
				'values'      => $roles,
			];

		if ( $this->support_users )
			$settings['_editpost']['display_threshold'] = _x( 'Maximum number of users to display the search box.', 'Modules: Cartable: Setting Description', 'geditorial' );

		$settings['_dashboard'] = [
			'dashboard_widgets',
			'dashboard_statuses',
			'dashboard_authors',
			'dashboard_count',
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'user_tax'  => 'cartable_user',
			'group_tax' => 'cartable_group',
			'type_tax'  => 'cartable_type',
			'group_ref' => 'user_group', // ref to the constant in Users module
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'meta_box_title'  => _x( 'Cartable', 'Modules: Cartable: MetaBox Title', 'geditorial' ),
				'meta_box_action' => _x( 'View All', 'Modules: Cartable: MetaBox Action', 'geditorial' ),
			],
			'noops' => [
				'type_tax' => _nx_noop( 'Cartable Type', 'Cartable Types', 'Modules: Cartable: Noop', 'geditorial' ),
			],
			'settings' => [
				'sync_terms' => _x( 'Sync Users & Groups', 'Modules: Cartable: Setting Button', 'geditorial' ),
			],
		];
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['sync_terms'] ) )
			$this->do_sync_terms();
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );
		$this->register_button( 'sync_terms' );
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'support_users' ) ) {

			$this->register_taxonomy( 'user_tax', [
				'hierarchical' => TRUE,
				'public'       => FALSE,
				'show_ui'      => FALSE,
				'meta_box_cb'  => FALSE,
			], NULL, [
				'manage_terms' => $this->caps['settings'],
				'edit_terms'   => $this->caps['settings'],
				'delete_terms' => $this->caps['settings'],
				'assign_terms' => 'assign_'.$this->constant( 'user_tax' ),
			] );

			// new term for new users
			$this->action( 'add_user_to_blog', 3 );

			$this->support_users = TRUE;
		}

		if ( $this->get_setting( 'support_groups' ) )
			$this->action_module( 'users' );

		if ( $this->get_setting( 'support_types' ) ) {

			$this->register_taxonomy( 'type_tax', [
				'hierarchical' => TRUE,
				'public'       => FALSE,
				'show_ui'      => TRUE,
				'show_in_menu' => FALSE,
			], NULL, [
				'manage_terms' => $this->caps['settings'],
				'edit_terms'   => $this->caps['settings'],
				'delete_terms' => $this->caps['settings'],
				'assign_terms' => 'assign_'.$this->constant( 'type_tax' ),
			]  );

			$this->support_types = TRUE;
		}

		$this->filter( 'map_meta_cap', 4 );
	}

	public function users_init( $options )
	{
		if ( empty( $options->settings['user_groups'] ) )
			return;

		$this->register_taxonomy( 'group_tax', [
			'hierarchical' => TRUE,
			'public'       => FALSE,
			'show_ui'      => FALSE,
		], NULL, [
			'manage_terms' => $this->caps['settings'],
			'edit_terms'   => $this->caps['settings'],
			'delete_terms' => $this->caps['settings'],
			'assign_terms' => 'assign_'.$this->constant( 'group_tax' ),
		] );

		$this->filter( 'wp_update_term_data', 4 );
		add_action( 'edited_'.$this->constant( 'group_ref' ), [ $this, 'edited_term' ], 10, 2 );

		$this->support_groups = TRUE;
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		// hack to bypass the dumb `_wp_translate_postdata()`
		if ( isset( $_POST['post_type'] )
			&& in_array( $_POST['post_type'], $this->posttypes() ) ) {

			$posttype = PostType::object( $_POST['post_type'] );

			// override the cap
			if ( $cap === $posttype->cap->edit_others_posts )
				$cap = 'edit_post';

			if ( isset( $_POST['post_ID'] ) && empty( $args[0] ) )
				$args[0] = (int) $_POST['post_ID'];
		}

		switch ( $cap ) {

			case 'assign_'.$this->constant( 'user_tax' ):

				if ( $this->support_users )
					return $this->role_can( 'assign_user', $user_id )
						? [ 'read' ]
						: [ 'do_not_allow' ];

			break;
			case 'assign_'.$this->constant( 'group_tax' ):

				if ( $this->support_groups )
					return $this->role_can( 'assign_group', $user_id )
						? [ 'read' ]
						: [ 'do_not_allow' ];

			break;
			case 'assign_'.$this->constant( 'type_tax' ):

				if ( $this->support_types )
					return $this->role_can( 'assign_type', $user_id )
						? [ 'read' ]
						: [ 'do_not_allow' ];

			break;
			case 'read_post':
			case 'read_page':
			case 'edit_post':
			case 'edit_page':
			case 'delete_post':
			case 'delete_page':
			// case 'publish_post':

				if ( ! $post = get_post( $args[0] ) )
					return $caps;

				if ( ! in_array( $post->post_type, $this->posttypes() ) )
					return $caps;

				if ( $this->support_users && $this->get_setting( 'map_cap_user' ) ) {

					$user = get_user_by( 'id', $user_id )->user_login;

					if ( in_array( $user, $this->get_users( $post->ID ) ) )
						return [ 'read' ];
				}

				if ( $this->support_groups && $this->get_setting( 'map_cap_group' ) ) {

					foreach ( $this->get_groups( $post->ID ) as $group )
						if ( is_object_in_term( $user_id, $this->constant( 'group_ref' ), $group ) )
							return [ 'read' ];
				}
		}

		return $caps;
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'type_tax' ) == $screen->taxonomy ) {

			add_filter( 'parent_file', function(){
				return 'options-general.php';
			} );

		} else if ( in_array( $screen->post_type, $this->posttypes() ) ) {

			if ( 'edit' == $screen->base ) {

				if ($this->support_users && $this->role_can( 'view_user' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'users' );

				if ( $this->support_groups && $this->role_can( 'view_group' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'groups' );

				if ( $this->support_types && $this->role_can( 'view_type' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'types' );

			} else if ( 'post' == $screen->base ) {

				$this->class_metabox( $screen, 'main' );

				add_meta_box( $this->classs( 'main' ),
					$this->get_meta_box_title( 'users', $this->get_adminmenu( FALSE ), TRUE ),
					[ $this, 'render_metabox_main' ],
					$screen,
					'side',
					'high'
				);

				if ( $this->support_types )
					add_action( $this->hook( 'render_metabox' ), [ $this, 'render_metabox_types' ], 10, 2 );

				if ( $this->support_groups )
					add_action( $this->hook( 'render_metabox' ), [ $this, 'render_metabox_groups' ], 10, 2 );

				if ( $this->support_users )
					add_action( $this->hook( 'render_metabox' ), [ $this, 'render_metabox_users' ], 10, 2 );
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->support_types ) {

			$tax = get_taxonomy( $this->constant( 'type_tax' ) );

			add_options_page(
				HTML::escape( $tax->labels->menu_name ),
				HTML::escape( $tax->labels->menu_name ),
				$tax->cap->manage_terms,
				'edit-tags.php?taxonomy='.$tax->name
			);
		}

		if ( ! $this->support_users
			&& ! $this->support_groups
			&& ! $this->support_types )
				return;

		$user_id = get_current_user_id();

		if ( $this->role_can( 'view_user', $user_id )
			|| $this->role_can( 'view_group', $user_id )
			|| $this->role_can( 'type_group', $user_id ) ) {

			add_submenu_page(
				'index.php',
				_x( 'Editorial Cartables', 'Modules: Cartable: Page Title', 'geditorial' ),
				_x( 'My Cartables', 'Modules: Cartable: Menu Title', 'geditorial' ),
				'read',
				$this->get_adminmenu(),
				[ $this, 'admin_cartable_page' ]
			);
		}
	}

	public function admin_cartable_page()
	{
		$user = wp_get_current_user();
		$uri  = $this->get_adminmenu( FALSE );

		$sub  = $slug = $context = FALSE;
		$subs = [];

		if ( $this->support_users && $this->role_can( 'view_user', $user->ID ) ) {

			$sub     = 'personal';
			$slug    = $user->user_login;
			$context = 'user';

			$subs['personal'] = _x( 'Personal', 'Modules: Cartable', 'geditorial' );
		}

		if ( $this->support_groups && $this->role_can( 'view_group', $user->ID ) ) {

			foreach ( $this->get_user_groups( $user->ID ) as $term ) {

				if ( ! count( $subs ) ) {
					$sub     = 'group-'.$term->slug;
					$slug    = $term->slug;
					$context = 'group';
				}

				$subs['group-'.$term->slug] = [
					'title' => HTML::escape( $term->name ),
					'args'  => [
						'sub'     => 'group-'.$term->slug,
						'context' => 'group',
						'slug'    => $term->slug,
					],
				];
			}
		}

		if ( $this->support_types && $this->role_can( 'type_group', $user->ID ) ) {

			foreach ( $this->get_types( FALSE, TRUE ) as $term ) {

				if ( ! count( $subs ) ) {
					$sub     = 'type-'.$term->slug;
					$slug    = $term->slug;
					$context = 'type';
				}

				$subs['type-'.$term->slug] = [
					'title' => HTML::escape( $term->name ),
					'args'  => [
						'sub'     => 'type-'.$term->slug,
						'context' => 'type',
						'slug'    => $term->slug,
					],
				];
			}
		}

		Settings::wrapOpen( $this->key, 'listtable' );

			Settings::headerTitle( _x( 'Editorial Cartables', 'Modules: Cartable: Page Title', 'geditorial' ), FALSE );

			$context = self::req( 'context', $context );
			$slug    = 'user' == $context ? $slug : self::req( 'slug', $slug ); // prevents access to other users

			$current = Taxonomy::getTerm( $slug, $this->constant( $context.'_tax' ) );

			if ( $current && 'group' == $context && $this->role_can( 'restricted', NULL, FALSE, FALSE ) ) {

				// prevents access to other groups
				if ( ! in_array( $current->slug, wp_list_pluck( $this->get_user_groups( $user->ID ), 'slug' ) ) )
					$current = FALSE;
			}

			if ( $current && count( $subs ) ) {

				HTML::headerNav( $uri, self::req( 'sub', $sub ), $subs );

				$this->tableCartable( $current, $context );

			} else {

				HTML::desc( _x( 'Something\'s wrong!', 'Modules: Cartable', 'geditorial' ), FALSE, '-empty' );
			}

			$this->settings_signature( 'listtable' );
		Settings::wrapClose();
	}

	public function tweaks_column_attr_users( $post )
	{
		if ( ! $users = $this->get_users( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'portfolio', _x( 'User Cartables', 'Modules: Cartable: Row Icon Title', 'geditorial' ) );

			$list = [];

			foreach ( $users as $slug )
				if ( $user = get_user_by( 'login', $slug ) )
					$list[] = HTML::escape( $user->display_name ); // FIXME: make clickable

			echo Helper::getJoined( $list );
		echo '</li>';
	}

	public function tweaks_column_attr_groups( $post )
	{
		if ( ! $groups = $this->get_groups( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'groups', _x( 'Group Cartables', 'Modules: Cartable: Row Icon Title', 'geditorial' ) );

			$list = [];

			foreach ( $groups as $slug )
				if ( $term = get_term_by( 'slug', $slug, $this->constant( 'group_tax' ) ) )
					$list[] = HTML::escape( $term->name ); // FIXME: make clickable

			echo Helper::getJoined( $list );
		echo '</li>';
	}

	public function tweaks_column_attr_types( $post )
	{
		if ( ! $types = $this->get_types( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-type">';

			echo $this->get_column_icon( FALSE, 'portfolio', _x( 'Type Cartables', 'Modules: Cartable: Row Icon Title', 'geditorial' ) );

			$list = [];

			foreach ( $types as $slug )
				if ( $term = get_term_by( 'slug', $slug, $this->constant( 'type_tax' ) ) )
					$list[] = HTML::escape( $term->name ); // FIXME: make clickable

			echo Helper::getJoined( $list );
		echo '</li>';
	}

	protected function dashboard_widgets()
	{
		$user_id = get_current_user_id();

		if ( $this->support_users && $this->role_can( 'view_user', $user_id ) ) {

			$title = _x( 'Your Personal Cartable', 'Modules: Cartable: Dashboard Widget Title', 'geditorial' );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $this->get_adminmenu( FALSE ) ).'"';
			$title.= ' title="'._x( 'Click to view all items in this cartable', 'Modules: Cartable: Dashboard Widget Title Action', 'geditorial' ).'">';
			$title.= _x( 'All Items', 'Modules: Cartable: Dashboard Widget Title Action', 'geditorial' ).'</a></span>';

			wp_add_dashboard_widget(
				$this->classs( 'user-cartable' ),
				$title,
				[ $this, 'dashboard_widget_summary' ],
				NULL,
				[ 'context' => 'user', 'slug' => get_user_by( 'id', $user_id )->user_login ]
			);
		}

		if ( $this->support_groups && $this->role_can( 'view_group', $user_id ) ) {

			foreach ( $this->get_user_groups( $user_id ) as $term ) {

				$url = $this->get_adminmenu( FALSE, [ 'context' => 'group', 'slug' => $term->slug, 'sub' => 'group-'.$term->slug ] );

				/* translators: %s: term name placeholder */
				$title = sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Dashboard Widget Title', 'geditorial' ), $term->name );
				$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'"';
				$title.= ' title="'._x( 'Click to view all items in this cartable', 'Modules: Cartable: Dashboard Widget Title Action', 'geditorial' ).'">';
				$title.= _x( 'All Items', 'Modules: Cartable: Dashboard Widget Title Action', 'geditorial' ).'</a></span>';

				wp_add_dashboard_widget(
					$this->classs( 'group-cartable', $term->slug ),
					$title,
					[ $this, 'dashboard_widget_summary' ],
					NULL,
					[ 'context' => 'group', 'slug' => $term->slug ]
				);
			}
		}

		if ( $this->support_types && $this->role_can( 'view_type', $user_id ) ) {

			foreach ( $this->get_types( FALSE, TRUE ) as $term ) {

				$url = $this->get_adminmenu( FALSE, [ 'context' => 'type', 'slug' => $term->slug, 'sub' => 'type-'.$term->slug ] );

				/* translators: %s: term name placeholder */
				$title = sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Dashboard Widget Title', 'geditorial' ), $term->name );
				$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'"';
				$title.= ' title="'._x( 'Click to view all items in this cartable', 'Modules: Cartable: Dashboard Widget Title Action', 'geditorial' ).'">';
				$title.= _x( 'All Items', 'Modules: Cartable: Dashboard Widget Title Action', 'geditorial' ).'</a></span>';

				wp_add_dashboard_widget(
					$this->classs( 'type-cartable', $term->slug ),
					$title,
					[ $this, 'dashboard_widget_summary' ],
					NULL,
					[ 'context' => 'type', 'slug' => $term->slug ]
				);
			}
		}
	}

	protected function do_sync_terms()
	{
		if ( ! $this->nonce_verify( 'settings' ) )
			return;

		$count  = 0;

		if ( $this->support_users ) {

			$site   = gEditorial()->user();
			$admins = get_super_admins();

			foreach ( $this->get_blog_users() as $user ) {

				if ( $site == $user->ID )
					continue;

				if ( in_array( $user->user_login, $admins ) )
					continue;

				if ( Taxonomy::addTerm( $user->user_login, $this->constant( 'user_tax' ), FALSE ) )
					$count++;
			}
		}

		if ( $this->support_groups )
			foreach ( Taxonomy::getTerms( $this->constant( 'group_ref' ), FALSE, TRUE ) as $group )
				if ( Taxonomy::addTerm( $group->name, $this->constant( 'group_tax' ), $group->slug ) )
					$count++;

		WordPress::redirectReferer( [
			'message' => 'synced',
			'count'   => $count,
		] );
	}

	public function wp_update_term_data( $data, $term_id, $taxonomy, $args )
	{
		if ( $taxonomy == $this->constant( 'group_ref' ) )
			$this->before_terms[$term_id] = get_term_by( 'id', $term_id, $this->constant( 'group_ref' ) );

		return $data;
	}

	public function edited_term( $term_id, $tt_id )
	{
		if ( ! isset( $this->before_terms[$term_id] ) )
			return;

		$before = $this->before_terms[$term_id];
		$edited = get_term_by( 'id', $term_id, $this->constant( 'group_ref' ) );

		if ( $mirrored = get_term_by( 'slug', $before->slug, $this->constant( 'group_tax' ) ) )
			wp_update_term( $mirrored->term_id, $this->constant( 'group_tax' ), [
				'name' => $edited->name,
				'slug' => $edited->slug,
			] );
	}

	public function created_term( $term_id, $tt_id )
	{
		if ( $term = get_term_by( 'id', $term_id, $this->constant( 'group_ref' ) ) )
			wp_insert_term( $term->name, $this->constant( 'group_tax' ), [
				'slug' => $term->slug,
			] );
	}

	public function add_user_to_blog( $user_id, $role, $blog_id )
	{
		if ( in_array( $role, $this->get_setting( 'excluded_roles', [] ) ) )
			return;

		if ( ! $user = get_user_by( 'id', $user_id ) )
			return;

		Taxonomy::addTerm( $user->user_login, $this->constant( 'user_tax' ), FALSE );
	}

	public function dashboard_widget_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		if ( ! $term = Taxonomy::getTerm( $box['args']['slug'], $this->constant( $box['args']['context'].'_tax' ) ) )
			return HTML::desc( _x( 'Something\'s wrong!', 'Modules: Cartable', 'geditorial' ), FALSE, '-empty' );

		$this->tableCartableSummary( $term, $box['args']['context'] );
	}

	public function render_metabox_main( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

			if ( 'auto-draft' == $post->post_status )
				HTML::desc( _x( 'You can see cartable details, once you\'ve saved it for the first time.', 'Modules: Cartable', 'geditorial' ) );

			else if ( has_action( $this->hook( 'render_metabox' ) ) )
				$this->actions( 'render_metabox', $post, $box, NULL, 'main' );

			else
				echo $this->metabox_summary( $post );

		echo '</div>';
	}

	public function metabox_summary( $post, $check_groups = TRUE, $wrap = TRUE )
	{
		$html  = '';
		$user  = wp_get_current_user();
		$users = $this->get_users( $post->ID );

		if ( in_array( $user->user_login, $users ) )
			$html.= '<li class="-row">'
				.$this->get_column_icon( FALSE, 'portfolio', _x( 'User Cartables', 'Modules: Cartable: Row Icon Title', 'geditorial' ) )
				._x( 'This currently is on your cartable.', 'Modules: Cartable', 'geditorial' )
				.'</li>';

		if ( $check_groups && $this->support_groups ) {
			$groups = $this->get_groups( $post->ID );

			foreach ( $groups as $group ) {

				if ( is_object_in_term( $user->ID, $this->constant( 'group_ref' ), $group ) ) {

					$html.= '<li class="-row">'
						.$this->get_column_icon( FALSE, 'groups', _x( 'Group Cartables', 'Modules: Cartable: Row Icon Title', 'geditorial' ) )
						._x( 'This currently is on your group cartable.', 'Modules: Cartable', 'geditorial' )
						.'</li>';

					break;
				}
			}
		}

		if ( ! $wrap )
			return $html;

		if ( ! $html )
			$html.= '<li class="-row">'._x( 'This currently is <b>not</b> on any of your cartables.', 'Modules: Cartable', 'geditorial' ).'</li>';

		return HTML::wrap( '<ul class="-rows">'.$html.'</ul>', 'field-wrap -summary' );
	}

	public function render_metabox_users( $post, $box )
	{
		$users   = [];
		$disable = ! $this->role_can( 'assign_user' );

		if ( $this->support_groups && ! $disable && $this->role_can( 'restricted', NULL, FALSE, FALSE ) ) {

			foreach ( $this->get_user_groups() as $group ) {

				$members = get_objects_in_term( $group->term_id, $this->constant( 'group_ref' ) );

				if ( count( $members ) )
					$users = array_merge( $users, $this->get_blog_users( NULL, $members ) );
			}

			if ( ! count( $users ) ) {
				echo $this->metabox_summary( $post, FALSE );
				return;
			}

		} else {
			$users = $this->get_blog_users();
		}

		$list = MetaBox::checklistUserTerms( $post->ID, [
			'taxonomy'          => $this->constant( 'user_tax' ),
			'posttype'          => $post->post_type,
			'list_only'         => $disable,
			'selected_only'     => $disable,
			'selected_preserve' => TRUE,
		], $users, $this->get_setting( 'display_threshold', 5 ) );

		if ( FALSE === $list )
			echo $this->metabox_summary( $post, FALSE );
	}

	public function render_metabox_groups( $post, $box )
	{
		$disable = ! $this->role_can( 'assign_group' );

		MetaBox::checklistTerms( $post->ID, [
			'taxonomy'          => $this->constant( 'group_tax' ),
			'edit'              => FALSE,
			'list_only'         => $disable,
			'selected_only'     => $disable,
			// 'selected_preserve' => TRUE, // NO NEED: only on custom group lists
		] );
	}

	public function render_metabox_types( $post, $box )
	{
		$disable = ! $this->role_can( 'assign_type' );

		MetaBox::checklistTerms( $post->ID, [
			'taxonomy'          => $this->constant( 'type_tax' ),
			'edit'              => FALSE,
			'list_only'         => $disable,
			'selected_only'     => $disable,
			// 'selected_preserve' => TRUE, // NO NEED: only on custom group lists
		] );
	}

	private function get_users( $post_id, $object = FALSE, $key = 'slug' )
	{
		return Taxonomy::getTerms( $this->constant( 'user_tax' ), $post_id, $object, $key );
	}

	private function get_groups( $post_id, $object = FALSE, $key = 'slug' )
	{
		return Taxonomy::getTerms( $this->constant( 'group_tax' ), $post_id, $object, $key );
	}

	private function get_types( $post_id, $object = FALSE, $key = 'slug' )
	{
		return Taxonomy::getTerms( $this->constant( 'type_tax' ), $post_id, $object, $key );
	}

	private function get_user_groups( $user_id = NULL )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		return wp_get_object_terms( $user_id, $this->constant( 'group_ref' ) );
	}

	private function tableCartable( $term, $context = 'user' )
	{
		if ( 'user' == $context )
			$title = _x( 'Your Cartable', 'Modules: Cartable: Page Title', 'geditorial' );
		else
		/* translators: %s: term name placeholder */
			$title = sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Menu Title', 'geditorial' ), $term->name );

		HTML::h3( $title );

		$list  = $this->list_posttypes();
		$query = [
			'tax_query'      => [ [
				'taxonomy' => $this->constant( $context.'_tax' ),
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],
		];

		list( $posts, $pagination ) = $this->getTablePosts( $query );

		$pagination['actions']['empty_cartable'] = _x( 'Empty Cartable', 'Modules: Cartable: Table Action', 'geditorial' );
		$pagination['before'][] = Helper::tableFilterPostTypes( $list );
		$pagination['before'][] = Helper::tableFilterSearch( $list );

		return HTML::tableList( [
			'_cb'   => 'ID',
			// 'ID'    => Helper::tableColumnPostID(),
			'date'  => Helper::tableColumnPostDate(),
			// 'type'  => Helper::tableColumnPostType(), // FIXME: add setting for this
			'title' => Helper::tableColumnPostTitle(),
			'terms' => Helper::tableColumnPostTerms( Taxonomy::get( 4, [ 'public' => TRUE ] ) ),
			'cartable' => [
				'title'    => _x( 'Cartable', 'Modules: Cartable: Table Column Title', 'geditorial' ),
				'callback' => function( $value, $row, $column, $index ){
					return $this->metabox_summary( $row );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'empty'      => _x( 'The cartable is empty!', 'Modules: Cartable', 'geditorial' ),
			'pagination' => $pagination,
		] );
	}

	private function tableCartableSummary( $term, $context = 'user' )
	{
		$args = [

			'tax_query'      => [ [
				'taxonomy' => $this->constant( $context.'_tax' ),
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],

			'orderby'     => 'modified',
			'post_type'   => $this->posttypes(),
			'post_status' => 'any',

			'posts_per_page'      => $this->get_setting( 'dashboard_count', 10 ),
			'ignore_sticky_posts' => TRUE,
			'suppress_filters'    => TRUE,
			'no_found_rows'       => TRUE,

			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$query = new \WP_Query;

		$columns = [ 'title' => Helper::tableColumnPostTitleSummary() ];

		if ( $this->get_setting( 'dashboard_statuses', FALSE ) )
			$columns['status'] = Helper::tableColumnPostStatusSummary();

		if ( $this->get_setting( 'dashboard_authors', FALSE ) )
			$columns['author'] = Helper::tableColumnPostAuthorSummary();

		$columns['modified'] = Helper::tableColumnPostDateModified();

		HTML::tableList( $columns, $query->query( $args ), [
			'empty' => _x( 'The cartable is empty!', 'Modules: Cartable', 'geditorial' ),
		] );
	}
}

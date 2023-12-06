<?php namespace geminorum\gEditorial\Modules\Cartable;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Cartable extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRoles;

	// TODO: dynamic cartables based on external taxonomies

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
			'title'    => _x( 'Cartable', 'Modules: Cartable', 'geditorial-admin' ),
			'desc'     => _x( 'Customized Content Folders', 'Modules: Cartable', 'geditorial-admin' ),
			'icon'     => 'portfolio',
			'access'   => 'beta',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		$settings = [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'support_users',
					'title'       => _x( 'User Cartables', 'Setting Title', 'geditorial-cartable' ),
					'description' => _x( 'Enables cartables based on registered users.', 'Setting Description', 'geditorial-cartable' ),
				],
				[
					'field'       => 'support_groups',
					'title'       => _x( 'Group Cartables', 'Setting Title', 'geditorial-cartable' ),
					'description' => _x( 'Enables cartables based on custom groups. Needs <i>Users</i> module.', 'Setting Description', 'geditorial-cartable' ),
				],
				[
					'field'       => 'support_types',
					'title'       => _x( 'Type Cartables', 'Setting Title', 'geditorial-cartable' ),
					'description' => _x( 'Enables cartables based on custom types.', 'Setting Description', 'geditorial-cartable' ),
				],
			],
		];

		if ( $this->support_users )
			$settings['_roles'][] = [
				'field'       => 'map_cap_user',
				'title'       => _x( 'Map User Capabilities', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Gives access to edit posts based on user cartables.', 'Setting Description', 'geditorial-cartable' ),
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'map_cap_group',
				'title'       => _x( 'Map Group Capabilities', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Gives access to edit posts based on group cartables.', 'Setting Description', 'geditorial-cartable' ),
			];

		if ( $this->support_users )
			$settings['_roles']['excluded_roles'] = [
				_x( 'Roles that excluded from cartables.', 'Setting Description', 'geditorial-cartable' ),
				array_merge( $roles, [ 'subscriber' ] ),
 			];

		if ( $this->support_users )
			$settings['_roles'][] = [
				'field'       => 'view_user_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'View User Cartable', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that can view user cartables.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'view_group_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'View Group Cartable', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that can view group cartables.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_types )
			$settings['_roles'][] = [
				'field'       => 'view_type_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'View Type Cartable', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that can view type cartables.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_users )
			$settings['_roles'][] = [
				'field'       => 'assign_user_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign User Cartables', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that can assign user cartables.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'assign_group_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign Group Cartables', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that can assign gorup cartables.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_types )
			$settings['_roles'][] = [
				'field'       => 'assign_type_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign Type Cartables', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that can assign type cartables.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_groups )
			$settings['_roles'][] = [
				'field'       => 'restricted_roles',
				'type'        => 'checkboxes',
				'title'       => _x( 'Restricted Groups', 'Setting Title', 'geditorial-cartable' ),
				'description' => _x( 'Roles that restricted to their group users.', 'Setting Description', 'geditorial-cartable' ),
				'values'      => $roles,
			];

		if ( $this->support_users )
			$settings['_editpost']['display_threshold'] = _x( 'Maximum number of users to display the search box.', 'Setting Description', 'geditorial-cartable' );

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
			'metabox' => [
				'metabox_title'  => _x( 'Cartable', 'MetaBox Title', 'geditorial-cartable' ),
				'metabox_action' => _x( 'View All', 'MetaBox Action', 'geditorial-cartable' ),
			],
			'noops' => [
				'type_tax' => _n_noop( 'Cartable Type', 'Cartable Types', 'geditorial-cartable' ),
			],
			'settings' => [
				'sync_terms' => _x( 'Sync Users & Groups', 'Button', 'geditorial-cartable' ),
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
				'rewrite'      => FALSE,
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
			$this->action_module( 'users', 'init' );

		if ( $this->get_setting( 'support_types' ) ) {

			$this->register_taxonomy( 'type_tax', [
				'hierarchical' => TRUE,
				'public'       => FALSE,
				'rewrite'      => FALSE,
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
			'rewrite'      => FALSE,
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

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		// hack to bypass the dumb `_wp_translate_postdata()`
		if ( isset( $_POST['post_type'] )
			&& $this->posttype_supported( $_POST['post_type'] ) ) {

			$posttype = WordPress\PostType::object( $_POST['post_type'] );

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

				if ( empty( $args[0] ) )
					return $caps;

				if ( ! $post = WordPress\Post::get( $args[0] ) )
					return $caps;

				if ( ! $this->posttype_supported( $post->post_type ) )
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

			$this->filter_string( 'parent_file', 'options-general.php' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit' == $screen->base ) {

				if ( $this->support_users && $this->role_can( 'view_user' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'users' );

				if ( $this->support_groups && $this->role_can( 'view_group' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'groups' );

				if ( $this->support_types && $this->role_can( 'view_type' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'types' );

			} else if ( 'post' == $screen->base ) {

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( 'users', $this->get_adminpage_url( TRUE, [], 'adminmenu' ), TRUE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				if ( $this->support_types )
					$this->action_self( 'render_metabox', 2, 10, 'types' );

				if ( $this->support_groups )
					$this->action_self( 'render_metabox', 2, 10, 'groups' );

				if ( $this->support_users )
					$this->action_self( 'render_metabox', 2, 10, 'users' );
			}
		}
	}

	public function admin_menu()
	{
		if ( $this->support_types )
			$this->_hook_menu_taxonomy( 'type_tax', 'options-general.php' );

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
				_x( 'Editorial Cartables', 'Page Title', 'geditorial-cartable' ),
				_x( 'My Cartables', 'Menu Title', 'geditorial-cartable' ),
				'read',
				$this->get_adminpage_url( FALSE ),
				[ $this, 'admin_cartable_page' ]
			);
		}
	}

	public function admin_cartable_page()
	{
		$user = wp_get_current_user();
		$uri  = $this->get_adminpage_url( TRUE, [], 'adminmenu' );

		$sub  = $slug = $context = FALSE;
		$subs = [];

		if ( $this->support_users && $this->role_can( 'view_user', $user->ID ) ) {

			$sub     = 'personal';
			$slug    = $user->user_login;
			$context = 'user';

			$subs['personal'] = _x( 'Personal', 'Sub', 'geditorial-cartable' );
		}

		if ( $this->support_groups && $this->role_can( 'view_group', $user->ID ) ) {

			foreach ( $this->get_user_groups( $user->ID ) as $term ) {

				if ( ! count( $subs ) ) {
					$sub     = 'group-'.$term->slug;
					$slug    = $term->slug;
					$context = 'group';
				}

				$subs['group-'.$term->slug] = [
					'title' => Core\HTML::escape( $term->name ),
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
					'title' => Core\HTML::escape( $term->name ),
					'args'  => [
						'sub'     => 'type-'.$term->slug,
						'context' => 'type',
						'slug'    => $term->slug,
					],
				];
			}
		}

		Settings::wrapOpen( $this->key, 'listtable' );

			Settings::headerTitle( _x( 'Editorial Cartables', 'Page Title', 'geditorial-cartable' ), FALSE );

			$context = self::req( 'context', $context );
			$slug    = 'user' == $context ? $slug : self::req( 'slug', $slug ); // prevents access to other users

			$current = WordPress\Term::get( $slug, $this->constant( $context.'_tax' ) );

			if ( $current && 'group' == $context && $this->role_can( 'restricted', NULL, FALSE, FALSE ) ) {

				// prevents access to other groups
				if ( ! in_array( $current->slug, Core\Arraay::pluck( $this->get_user_groups( $user->ID ), 'slug' ) ) )
					$current = FALSE;
			}

			if ( $current && count( $subs ) ) {

				Core\HTML::headerNav( $uri, self::req( 'sub', $sub ), $subs );

				$this->tableCartable( $current, $context );

			} else {

				echo gEditorial\Plugin::wrong();
			}

			$this->settings_signature( 'listtable' );
		Settings::wrapClose();
	}

	public function tweaks_column_attr_users( $post )
	{
		if ( ! $users = $this->get_users( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'portfolio', _x( 'User Cartables', 'Row Icon Title', 'geditorial-cartable' ) );

			$list = [];

			foreach ( $users as $slug )
				if ( $user = get_user_by( 'login', $slug ) )
					$list[] = Core\HTML::escape( $user->display_name ); // FIXME: make clickable

			echo WordPress\Strings::getJoined( $list );
		echo '</li>';
	}

	public function tweaks_column_attr_groups( $post )
	{
		if ( ! $groups = $this->get_groups( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'groups', _x( 'Group Cartables', 'Row Icon Title', 'geditorial-cartable' ) );

			$list = [];

			foreach ( $groups as $slug )
				if ( $term = get_term_by( 'slug', $slug, $this->constant( 'group_tax' ) ) )
					$list[] = Core\HTML::escape( $term->name ); // FIXME: make clickable

			echo WordPress\Strings::getJoined( $list );
		echo '</li>';
	}

	public function tweaks_column_attr_types( $post )
	{
		if ( ! $types = $this->get_types( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-type">';

			echo $this->get_column_icon( FALSE, 'portfolio', _x( 'Type Cartables', 'Row Icon Title', 'geditorial-cartable' ) );

			$list = [];

			foreach ( $types as $slug )
				if ( $term = get_term_by( 'slug', $slug, $this->constant( 'type_tax' ) ) )
					$list[] = Core\HTML::escape( $term->name ); // FIXME: make clickable

			echo WordPressStrings::getJoined( $list );
		echo '</li>';
	}

	protected function dashboard_widgets()
	{
		$user_id = get_current_user_id();

		if ( $this->support_users && $this->role_can( 'view_user', $user_id ) ) {

			$title = _x( 'Your Personal Cartable', 'Dashboard Widget Title', 'geditorial-cartable' );
			$title.= MetaBox::getTitleAction( [
				'url'   => $this->get_adminpage_url( TRUE, [], 'adminemnu' ),
				'title' => _x( 'Click to view all items in this cartable', 'Dashboard Widget Title Action', 'geditorial-cartable' ),
				'link'  => _x( 'All Items', 'Dashboard Widget Title Action', 'geditorial-cartable' ),
			] );

			$this->add_dashboard_widget(
				'user-cartable',
				$title,
				'refresh',
				[ 'context' => 'user', 'slug' => get_user_by( 'id', $user_id )->user_login ],
				[ $this, 'render_widget_summary' ]
			);
		}

		if ( $this->support_groups && $this->role_can( 'view_group', $user_id ) ) {

			foreach ( $this->get_user_groups( $user_id ) as $term ) {

				/* translators: %s: term name placeholder */
				$title = sprintf( _x( 'Cartable: %s', 'Dashboard Widget Title', 'geditorial-cartable' ), $term->name );
				$title.= MetaBox::getTitleAction( [
					'url'   => $this->get_adminpage_url( TRUE, [ 'context' => 'group', 'slug' => $term->slug, 'sub' => 'group-'.$term->slug ], 'adminmenu' ),
					'title' => _x( 'Click to view all items in this cartable', 'Dashboard Widget Title Action', 'geditorial-cartable' ),
					'link'  => _x( 'All Items', 'Dashboard Widget Title Action', 'geditorial-cartable' ),
				] );

				$this->add_dashboard_widget(
					sprintf( 'group-cartable-%s', $term->term_id ),
					$title,
					'refresh',
					[ 'context' => 'group', 'slug' => $term->slug ],
					[ $this, 'render_widget_summary' ]
				);
			}
		}

		if ( $this->support_types && $this->role_can( 'view_type', $user_id ) ) {

			foreach ( $this->get_types( FALSE, TRUE ) as $term ) {

				/* translators: %s: term name placeholder */
				$title = sprintf( _x( 'Cartable: %s', 'Dashboard Widget Title', 'geditorial-cartable' ), $term->name );
				$title.= MetaBox::getTitleAction( [
					'url'   => $this->get_adminpage_url( TRUE, [ 'context' => 'type', 'slug' => $term->slug, 'sub' => 'type-'.$term->slug ], 'adminmenu' ),
					'title' => _x( 'Click to view all items in this cartable', 'Dashboard Widget Title Action', 'geditorial-cartable' ),
					'link'  => _x( 'All Items', 'Dashboard Widget Title Action', 'geditorial-cartable' ),
				] );

				$this->add_dashboard_widget(
					sprintf( 'type-cartable-%s', $term->term_id ),
					$title,
					'refresh',
					[ 'context' => 'type', 'slug' => $term->slug ],
					[ $this, 'render_widget_summary' ]
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

				if ( WordPress\Term::add( $user->user_login, $this->constant( 'user_tax' ), FALSE ) )
					$count++;
			}
		}

		if ( $this->support_groups )
			foreach ( WordPress\Taxonomy::getTerms( $this->constant( 'group_ref' ), FALSE, TRUE ) as $group )
				if ( WordPress\Term::add( $group->name, $this->constant( 'group_tax' ), $group->slug ) )
					$count++;

		Core\WordPress::redirectReferer( [
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
		if ( $this->in_setting( $role, 'excluded_roles' ) )
			return;

		if ( ! $user = get_user_by( 'id', $user_id ) )
			return;

			WordPress\Term::add( $user->user_login, $this->constant( 'user_tax' ), FALSE );
	}

	public function render_widget_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		if ( ! $term = WordPress\Term::get( $box['args']['slug'], $this->constant( $box['args']['context'].'_tax' ) ) )
			return Info::renderSomethingIsWrong();

		$this->tableCartableSummary( $term, $box['args']['context'] );
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

			if ( 'auto-draft' == $post->post_status )
				Core\HTML::desc( _x( 'You can see cartable details, once you\'ve saved it for the first time.', 'Message', 'geditorial-cartable' ) );

			else if ( has_action( $this->hook( 'render_metabox' ) ) )
				$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

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
				.$this->get_column_icon( FALSE, 'portfolio', _x( 'User Cartables', 'Row Icon Title', 'geditorial-cartable' ) )
				._x( 'This currently is on your cartable.', 'Message', 'geditorial-cartable' )
				.'</li>';

		if ( $check_groups && $this->support_groups ) {
			$groups = $this->get_groups( $post->ID );

			foreach ( $groups as $group ) {

				if ( is_object_in_term( $user->ID, $this->constant( 'group_ref' ), $group ) ) {

					$html.= '<li class="-row">'
						.$this->get_column_icon( FALSE, 'groups', _x( 'Group Cartables', 'Row Icon Title', 'geditorial-cartable' ) )
						._x( 'This currently is on your group cartable.', 'Message', 'geditorial-cartable' )
						.'</li>';

					break;
				}
			}
		}

		if ( ! $wrap )
			return $html;

		if ( ! $html )
			$html.= '<li class="-row">'._x( 'This currently is <b>not</b> on any of your cartables.', 'Message', 'geditorial-cartable' ).'</li>';

		return Core\HTML::wrap( '<ul class="-rows">'.$html.'</ul>', 'field-wrap -summary' );
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
			'posttype'          => $post->post_type,
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
			'posttype'          => $post->post_type,
			'edit'              => FALSE,
			'list_only'         => $disable,
			'selected_only'     => $disable,
			// 'selected_preserve' => TRUE, // NO NEED: only on custom group lists
		] );
	}

	private function get_users( $post_id, $object = FALSE, $key = 'slug' )
	{
		return WordPress\Taxonomy::getPostTerms( $this->constant( 'user_tax' ), $post_id, $object, $key );
	}

	private function get_groups( $post_id, $object = FALSE, $key = 'slug' )
	{
		return WordPress\Taxonomy::getPostTerms( $this->constant( 'group_tax' ), $post_id, $object, $key );
	}

	private function get_types( $post_id, $object = FALSE, $key = 'slug' )
	{
		return WordPress\Taxonomy::getPostTerms( $this->constant( 'type_tax' ), $post_id, $object, $key );
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
			$title = _x( 'Your Cartable', 'Page Title', 'geditorial-cartable' );
		else
		/* translators: %s: term name placeholder */
			$title = sprintf( _x( 'Cartable: %s', 'Menu Title', 'geditorial-cartable' ), $term->name );

		Core\HTML::h3( $title );

		$list  = $this->list_posttypes();
		$query = [
			'tax_query' => [ [
				'taxonomy' => $this->constant( $context.'_tax' ),
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], array_keys( $list ), $this->get_sub_limit_option() );

		$pagination['actions']['empty_cartable'] = _x( 'Empty Cartable', 'Table Action', 'geditorial-cartable' );
		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			// 'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			// 'type'  => Tablelist::columnPostType(), // FIXME: add setting for this
			'title' => Tablelist::columnPostTitle(),
			'terms' => Tablelist::columnPostTerms( WordPress\Taxonomy::get( 4, [ 'public' => TRUE ] ) ),
			'cartable' => [
				'title'    => _x( 'Cartable', 'Table Column Title', 'geditorial-cartable' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					return $this->metabox_summary( $row );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'empty'      => _x( 'The cartable is empty!', 'Message', 'geditorial-cartable' ),
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

		$query = new \WP_Query();

		$columns = [ 'title' => Tablelist::columnPostTitleSummary() ];

		if ( $this->get_setting( 'dashboard_statuses', FALSE ) )
			$columns['status'] = Tablelist::columnPostStatusSummary();

		if ( $this->get_setting( 'dashboard_authors', FALSE ) )
			$columns['author'] = Tablelist::columnPostAuthorSummary();

		$columns['modified'] = Tablelist::columnPostDateModified();

		Core\HTML::tableList( $columns, $query->query( $args ), [
			'empty' => _x( 'The cartable is empty!', 'Message', 'geditorial-cartable' ),
		] );
	}
}

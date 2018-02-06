<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Cartable extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;
	protected $priority_admin_menu  = 90;

	protected $support_groups = FALSE;
	protected $before_terms   = [];

	public static function module()
	{
		return [
			'name'     => 'cartable',
			'title'    => _x( 'Cartable', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Personalized Folders for Users', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'portfolio',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$roles   = User::getAllRoleList();
		$exclude = [ 'administrator', 'subscriber' ];

		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'map_cap_user',
					'title'       => _x( 'Map User Capabilities', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Gives access to edit posts based on user cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'map_cap_group',
					'title'       => _x( 'Map Group Capabilities', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Gives access to edit posts based on group cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'disabled'    => ! $this->support_groups,
				],
			],
			'_roles' => [
				'excluded_roles' => _x( 'Roles that excluded from cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				[
					'field'       => 'view_user_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'View User Cartable', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can view user cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'view_group_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'View Group Cartable', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can view group cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
					'disabled'    => ! $this->support_groups,
				],
				[
					'field'       => 'assign_user_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Assign User Cartables', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can assign user cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'assign_group_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Assign Group Cartables', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can assign gorup cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
					'disabled'    => ! $this->support_groups,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Restricted Groups', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that restricted to their group users.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
					'disabled'    => ! $this->support_groups,
				],
			],
			'_editpost' => [
				'display_threshold' => _x( 'Maximum number of users to display the search box.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			],
			// '_editlist' => [
			// 	'admin_rowactions',
			// ],
			'_dashboard' => [
				'dashboard_widgets',
				'dashboard_statuses',
				'dashboard_authors',
				'dashboard_count',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'user_tax'  => 'cartable_user',
			'group_tax' => 'cartable_group',
			// 'type_tax'  => 'cartable_type',
			'group_ref' => 'user_group', // ref to the constant in Users module
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'meta_box_title'  => _x( 'Cartable', 'Modules: Cartable: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_action' => _x( 'View All', 'Modules: Cartable: MetaBox Action', GEDITORIAL_TEXTDOMAIN ),
			],
			'settings' => [
				'sync_terms' => _x( 'Sync Users & Groups', 'Modules: Cartable: Setting Button', GEDITORIAL_TEXTDOMAIN ),
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

		$this->register_taxonomy( 'user_tax', [
			'hierarchical' => TRUE,
			'public'      => FALSE,
			'show_ui'     => FALSE,
			'meta_box_cb' => FALSE,
		], NULL, [
			'manage_terms' => $this->caps['settings'],
			'edit_terms'   => $this->caps['settings'],
			'delete_terms' => $this->caps['settings'],
			'assign_terms' => 'assign_'.$this->constant( 'user_tax' ),
		] );

		// see gpeople affiliations
		// $this->register_taxonomy( 'type_tax', [
		// 	'hierarchical' => TRUE,
		// 	'public'       => FALSE,
		// 	'show_ui'      => FALSE,
		// ], 'user_tax', [
		// 	'manage_terms' => $this->caps['settings'],
		// 	'edit_terms'   => $this->caps['settings'],
		// 	'delete_terms' => $this->caps['settings'],
		// 	'delete_terms' => $this->caps['settings'],
		// ]  );

		$this->action_module( 'users' );
		$this->action( 'add_user_to_blog', 3 ); // new term for new users
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
		// add_action( 'created_'.$this->constant( 'group_ref' ), [ $this, 'created_term' ], 10, 2 ); // only on edit/resync after new groups

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
			&& in_array( $_POST['post_type'], $this->post_types() ) ) {

			$posttype = get_post_type_object( $_POST['post_type'] );

			// override the cap
			if ( $cap === $posttype->cap->edit_others_posts )
				$cap = 'edit_post';

			if ( isset( $_POST['post_ID'] ) && empty( $args[0] ) )
				$args[0] = (int) $_POST['post_ID'];
		}

		switch ( $cap ) {

			case 'assign_'.$this->constant( 'user_tax' ):

				return $this->role_can( 'assign_user', $user_id )
					? [ 'read' ]
					: [ 'do_not_allow' ];

			break;
			case 'assign_'.$this->constant( 'group_tax' ):

				return $this->role_can( 'assign_group', $user_id )
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

				if ( ! in_array( $post->post_type, $this->post_types() ) )
					return $caps;

				if ( $this->get_setting( 'map_cap_user' ) ) {

					$user = get_user_by( 'id', $user_id )->user_login;

					if ( in_array( $user, $this->get_users( $post->ID ) ) )
						return [ 'read' ];
				}

				if ( $this->support_groups && $this->get_setting( 'map_cap_group' ) ) {

					foreach( $this->get_groups( $post->ID ) as $group )
						if ( is_object_in_term( $user_id, $this->constant( 'group_ref' ), $group ) )
							return [ 'read' ];
				}
		}

		return $caps;
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'edit' == $screen->base ) {

				// if ( $this->get_setting( 'admin_rowactions' ) ) {
				//
				// 	$this->filter( 'post_row_actions', 2 );
				// 	$this->enqueue_asset_js( [], $screen );
				// }

				if ( $this->role_can( 'view_user' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'users' );

				if ( $this->support_groups && $this->role_can( 'view_group' ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 20, 'groups' );

			} else if ( 'post' == $screen->base ) {

				// if ( $this->role_can( 'cartable' ) ) {

					$this->class_meta_box( $screen, 'main' );

					add_meta_box( $this->classs( 'main' ),
						$this->get_meta_box_title( 'users', $this->get_adminmenu( FALSE ), TRUE ),
						[ $this, 'do_meta_box_main' ],
						$screen->post_type,
						'side',
						'high'
					);

					if ( $this->support_groups )
						add_action( $this->hook( 'main_meta_box' ), [ $this, 'main_meta_box_groups' ], 10, 2 );

					add_action( $this->hook( 'main_meta_box' ), [ $this, 'main_meta_box_users' ], 10, 2 );
				// }
			}
		}
	}

	public function admin_menu()
	{
		$page    = 'index.php';
		$menu    = $this->get_adminmenu();
		$user_id = get_current_user_id();

		// just for super admins
		if ( ! is_user_member_of_blog( $user_id ) )
			return;

		$hook = add_submenu_page(
			$page,
			_x( 'Editorial Cartable', 'Modules: Cartable: Page Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'My Cartable', 'Modules: Cartable: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			'read', // $this->role_can( 'view_user', $user_id ) ? 'read' : 'do_not_allow', // FIXME: this will lock the group's
			$menu,
			[ $this, 'admin_cartable_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'admin_cartable_load' ] );

		if ( ! $this->support_groups )
			return;

		if ( ! $this->role_can( 'view_group', $user_id ) )
			return;

		foreach ( $this->get_user_groups( $user_id ) as $group ) {

			$hook = add_submenu_page(
				$page,
				_x( 'Editorial Cartable', 'Modules: Cartable: Page Title', GEDITORIAL_TEXTDOMAIN ),
				sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Menu Title', GEDITORIAL_TEXTDOMAIN ), $group->name ),
				'read',
				$menu.'&group='.$group->slug, // must be slug
				[ $this, 'admin_cartable_page' ]
			);

			add_action( 'load-'.$hook, [ $this, 'admin_cartable_load' ] );
		}
	}

	public function admin_cartable_load()
	{
		if ( ( $group = isset( $_REQUEST['group'] ) ? $_REQUEST['group'] : NULL ) )
			$GLOBALS['submenu_file'] = $this->get_adminmenu().'&group='.$group;
	}

	public function admin_cartable_page()
	{
		Settings::wrapOpen( $this->key, $this->base, 'listtable' );

			$this->tableCartable( self::req( 'group', FALSE ) );

			$this->settings_signature( 'listtable' );
		Settings::wrapClose();
	}

	public function tweaks_column_attr_users( $post )
	{
		if ( ! $users = $this->get_users( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'portfolio', _x( 'User Cartables', 'Modules: Cartable: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

			$list = [];

			foreach ( $users as $slug )
				if ( $user = get_user_by( 'login', $slug ) )
					$list[] = $user->display_name; // FIXME: make clickable

			echo Helper::getJoined( $list );
		echo '</li>';
	}

	public function tweaks_column_attr_groups( $post )
	{
		if ( ! $groups = $this->get_groups( $post->ID ) )
			return FALSE;

		echo '<li class="-row -cartable-user">';

			echo $this->get_column_icon( FALSE, 'groups', _x( 'Group Cartables', 'Modules: Cartable: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

			$list = [];

			foreach ( $groups as $slug )
				if ( $group = get_term_by( 'slug', $slug, $this->constant( 'group_tax' ) ) )
					$list[] = $group->name; // FIXME: make clickable

			echo Helper::getJoined( $list );
		echo '</li>';
	}

	protected function dashboard_widgets()
	{
		$user_id = get_current_user_id();

		if ( $this->role_can( 'view_user', $user_id ) ) {

			$title = _x( 'Your Cartable', 'Modules: Cartable: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $this->get_adminmenu( FALSE ) ).'"';
			$title.= ' title="'._x( 'Click to view all items in this cartable', 'Modules: Cartable: Dashboard Widget Title Action', GEDITORIAL_TEXTDOMAIN ).'">';
			$title.= _x( 'All Items', 'Modules: Cartable: Dashboard Widget Title Action', GEDITORIAL_TEXTDOMAIN ).'</a></span>';

			wp_add_dashboard_widget( $this->classs( 'user-cartable' ), $title, [ $this, 'dashboard_widget_user_cartable' ] );
		}

		if ( ! $this->support_groups )
			return;

		if ( ! $this->role_can( 'view_group', $user_id ) )
			return;

		foreach ( $this->get_user_groups( $user_id ) as $group ) {

			$title = sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN ), $group->name );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $this->get_adminmenu( FALSE ).'&group='.$group->slug ).'"';
			$title.= ' title="'._x( 'Click to view all items in this cartable', 'Modules: Cartable: Dashboard Widget Title Action', GEDITORIAL_TEXTDOMAIN ).'">';
			$title.= _x( 'All Items', 'Modules: Cartable: Dashboard Widget Title Action', GEDITORIAL_TEXTDOMAIN ).'</a></span>';

			wp_add_dashboard_widget( $this->classs( 'group-cartable', $group->slug ), $title, [ $this, 'dashboard_widget_group_cartable' ], NULL, [ 'group' => $group ] );
		}
	}

	protected function do_sync_terms()
	{
		if ( ! $this->nonce_verify( 'settings' ) )
			return;

		$count  = 0;
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

	public function dashboard_widget_user_cartable( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		$user = wp_get_current_user();

		if ( ! $term = Taxonomy::getTerm( $user->user_login, $this->constant( 'user_tax' ) ) )
			return HTML::desc( _x( 'Something\'s wrong!', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ), FALSE, '-empty' );

		$this->tableCartableSummary( $term );
	}

	public function dashboard_widget_group_cartable( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		if ( ! $term = Taxonomy::getTerm( $box['args']['group']->slug, $this->constant( 'group_tax' ) ) )
			return HTML::desc( _x( 'Something\'s wrong!', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ), FALSE, '-empty' );

		$this->tableCartableSummary( $term, TRUE );
	}

	public function do_meta_box_main( $post, $box )
	{
		if ( $this->check_hidden_metabox( 'main' ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

			if ( 'auto-draft' == $post->post_status )
				HTML::desc( _x( 'You can see cartable details, once you\'ve saved it for the first time.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ) );

			else if ( has_action( $this->hook( 'main_meta_box' ) ) )
				$this->actions( 'main_meta_box', $post, $box );

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
				.$this->get_column_icon( FALSE, 'portfolio', _x( 'User Cartables', 'Modules: Cartable: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) )
				._x( 'This currently is on your cartable.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN )
				.'</li>';

		if ( $check_groups && $this->support_groups ) {
			$groups = $this->get_groups( $post->ID );

			foreach( $groups as $group ) {

				if ( is_object_in_term( $user->ID, $this->constant( 'group_ref' ), $group ) ) {

					$html.= '<li class="-row">'
						.$this->get_column_icon( FALSE, 'groups', _x( 'Group Cartables', 'Modules: Cartable: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) )
						._x( 'This currently is on your group cartable.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN )
						.'</li>';

					break;
				}
			}
		}

		if ( ! $wrap )
			return $html;

		if ( ! $html )
			$html.= '<li class="-row">'._x( 'This currently is <b>not</b> on any of your cartables.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ).'</li>';

		return HTML::wrap( '<ul class="-rows">'.$html.'</ul>', 'field-wrap field-wrap-summary' );
	}

	public function main_meta_box_users( $post, $box )
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
			'list_only'         => $disable,
			'selected_only'     => $disable,
			'selected_preserve' => TRUE,
		], $users, $this->get_setting( 'display_threshold', 5 ) );

		if ( FALSE === $list )
			echo $this->metabox_summary( $post, FALSE );
	}

	public function main_meta_box_groups( $post, $box )
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

	private function get_users( $post_id, $object = FALSE, $key = 'slug' )
	{
		return Taxonomy::getTerms( $this->constant( 'user_tax' ), $post_id, $object, $key );
	}

	private function get_groups( $post_id, $object = FALSE, $key = 'slug' )
	{
		return Taxonomy::getTerms( $this->constant( 'group_tax' ), $post_id, $object, $key );
	}

	private function get_user_groups( $user_id = NULL )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		return wp_get_object_terms( $user_id, $this->constant( 'group_ref' ) );
	}

	private function tableCartable( $group = FALSE )
	{
		$user = wp_get_current_user();
		$term = FALSE;

		if ( $this->support_groups && $group && $this->role_can( 'view_group', $user->ID ) )
			$term = Taxonomy::getTerm( $group, $this->constant( 'group_tax' ) );

		else if ( $this->role_can( 'view_user', $user->ID ) )
			$term = Taxonomy::getTerm( $user->user_login, $this->constant( 'user_tax' ) );

		if ( $group && $term )
			$title = sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Menu Title', GEDITORIAL_TEXTDOMAIN ), $term->name );
		else
			$title = _x( 'Your Cartable', 'Modules: Cartable: Page Title', GEDITORIAL_TEXTDOMAIN );

		Settings::headerTitle( $title, FALSE );

		// checking for group access
		if ( $group && $term && $this->role_can( 'restricted', NULL, FALSE, FALSE ) ) {

			if ( ! in_array( $term->slug, wp_list_pluck( $this->get_user_groups( $user->ID ), 'slug' ) ) )
				$term = FALSE;
		}

		if ( ! $term ) {
			echo HTML::error( _x( 'Something\'s wrong!', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ), FALSE );
			return FALSE;
		}

		$query = [
			'tax_query'      => [ [
				'taxonomy' => $this->constant( $group ? 'group_tax' : 'user_tax' ),
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],
		];

		list( $posts, $pagination ) = $this->getTablePosts( $query );

		$pagination['actions']['empty_cartable'] = _x( 'Empty Cartable', 'Modules: Cartable: Table Action', GEDITORIAL_TEXTDOMAIN );
		$pagination['before'][] = Helper::tableFilterPostTypes( $this->list_post_types() );

		return HTML::tableList( [
			'_cb'   => 'ID',
			// 'ID'    => Helper::tableColumnPostID(),
			'date'  => Helper::tableColumnPostDate(),
			// 'type'  => Helper::tableColumnPostType(), // FIXME: add setting for this
			'title' => Helper::tableColumnPostTitle(),
			'terms' => Helper::tableColumnPostTerms( Taxonomy::get( 4, [ 'public' => TRUE ] ) ),
			'cartable' => [
				'title'    => _x( 'Cartable', 'Modules: Cartable: Table Column Title', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){
					return $this->metabox_summary( $row );
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'empty'      => _x( 'The cartable is empty!', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ),
			'pagination' => $pagination,
		] );
	}

	private function tableCartableSummary( $term, $group = FALSE )
	{
		$args = [

			'tax_query'      => [ [
				'taxonomy' => $this->constant( $group ? 'group_tax' : 'user_tax' ),
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],

			'orderby'     => 'modified',
			'post_type'   => $this->post_types(),
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
			'empty' => _x( 'The cartable is empty!', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ),
		] );
	}
}

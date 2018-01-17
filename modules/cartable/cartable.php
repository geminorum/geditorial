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
			'_roles' => [
				'excluded_roles' => _x( 'Roles that excluded from cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				[
					'field'       => 'cartable_user_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'User Cartable Roles', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can view user cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'cartable_group_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Group Cartable Roles', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can view group cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
					'disabled'    => ! $this->support_groups,
				],
				[
					'field'       => 'user_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'User Cartable Roles', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can assign user cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
				],
				[
					'field'       => 'group_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Group Cartable Roles', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that can assign gorup cartables.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
					'disabled'    => ! $this->support_groups,
				],
				[
					'field'       => 'restricted_roles',
					'type'        => 'checkbox',
					'title'       => _x( 'Restricted Roles', 'Modules: Cartable: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Roles that restricted to their group users.', 'Modules: Cartable: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'exclude'     => $exclude,
					'values'      => $roles,
					'disabled'    => ! $this->support_groups,
				],
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
				// 'admin_rowactions',
			],
			'_dashboard' => [
				'dashboard_widgets',
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
			// FIXME: CAPS!
		] );

		// see gpeople affiliations
		// $this->register_taxonomy( 'type_tax', [
		// 	'hierarchical' => TRUE,
		// 	'public'       => FALSE,
		// 	'show_ui'      => FALSE,
		// ], 'user_tax', [
		// 	// FIXME: CAPS!
		// ] );

		$this->action_module( 'users' );
		$this->action( 'add_user_to_blog', 3 ); // new term for new users

		if ( $this->get_setting( 'map_cap_user' )
			|| $this->get_setting( 'map_cap_group' ) )
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
			// FIXME: CAPS!
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

				if ( $this->get_setting( 'map_cap_user' )
					&& in_array( get_user_by( 'id', $user_id )->user_login, $this->get_users( $post->ID ) ) )
						return [ 'read' ];

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

			} else if ( 'post' == $screen->base ) {

				// if ( $this->role_can( 'cartable' ) ) {

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
			'read', // $this->role_can( 'cartable_user', $user_id ) ? 'read' : 'do_not_allow', // FIXME this will lock the group's
			$menu,
			[ $this, 'admin_cartable_page' ]
		);

		add_action( 'load-'.$hook, [ $this, 'admin_cartable_load' ] );

		if ( ! $this->support_groups )
			return;

		if ( ! $this->role_can( 'cartable_group', $user_id ) )
			return;

		$groups = wp_get_object_terms( $user_id, $this->constant( 'group_ref' ) );

		foreach ( $groups as $group ) {

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

	// protected function dashboard_widgets()
	// {
	// 	wp_add_dashboard_widget( $this->classs( 'summary' ),
	// 		_x( 'Your Cartable', 'Modules: Cartable: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN ),
	// 		[ $this, 'dashboard_widget_summary' ]
	// 	);
    //
	// 	// $this->enqueue_asset_js( [], 'cartable.dashboard' );
	// }

	protected function do_sync_terms()
	{
		if ( ! $this->nonce_verify( 'settings' ) )
			return;

		$count = 0;

		foreach ( $this->get_blog_users() as $user )
			if ( Taxonomy::addTerm( $user->user_login, $this->constant( 'user_tax' ), FALSE ) )
				$count++;

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

	// public function dashboard_widget_summary()
	// {
	// 	if ( $this->check_hidden_metabox( 'summary' ) )
	// 		return;
	//
	// }

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

	public function metabox_summary( $post, $check_groups = TRUE )
	{
		$html  = '';
		$user  = wp_get_current_user();
		$users = $this->get_users( $post->ID );

		if ( in_array( $user->user_login, $users ) )
			$html.= '<li>'._x( 'This currently is on your cartable.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ).'</li>';

		if ( $check_groups && $this->support_groups ) {
			$groups = $this->get_groups( $post->ID );

			foreach( $groups as $group )
				if ( is_object_in_term( $user->ID, $this->constant( 'group_ref' ), $group ) )
					$html.= '<li>'._x( 'This currently is on your group cartable.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ).'</li>';
		}

		if ( ! $html )
			$html.= '<li>'._x( 'This currently is <b>not</b> on any of your cartables.', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ).'</li>';

		return HTML::wrap( '<ul>'.$html.'</ul>', 'field-wrap field-wrap-summary' );
	}

	public function main_meta_box_users( $post, $box )
	{
		$users   = [];
		$disable = ! $this->role_can( 'user' );

		if ( $this->support_groups && ! $disable && $this->role_can( 'restricted', NULL, FALSE, FALSE ) ) {

			$groups = wp_get_object_terms( get_current_user_id(), $this->constant( 'group_ref' ) );

			foreach ( $groups as $group ) {

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
			'taxonomy'      => $this->constant( 'user_tax' ),
			'list_only'     => $disable,
			'selected_only' => $disable,
		], $users, 5 ); // FIXME: add setting

		if ( FALSE === $list )
			echo $this->metabox_summary( $post, FALSE );
	}

	public function main_meta_box_groups( $post, $box )
	{
		$disable = ! $this->role_can( 'group' );

		MetaBox::checklistTerms( $post->ID, [
			'taxonomy'      => $this->constant( 'group_tax' ),
			'edit'          => FALSE,
			'list_only'     => $disable,
			'selected_only' => $disable,
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

	private function tableCartable( $group = FALSE )
	{
		$user = wp_get_current_user();
		$term = FALSE;

		if ( $this->support_groups && $group && $this->role_can( 'cartable_group', $user->ID ) )
			$term = Taxonomy::getTerm( $group, $this->constant( 'group_tax' ) );

		else if ( $this->role_can( 'cartable_user', $user->ID ) )
			$term = Taxonomy::getTerm( $user->user_login, $this->constant( 'user_tax' ) );

		if ( ! $term )
			return HTML::desc( _x( 'Something\'s wrong!', 'Modules: Cartable', GEDITORIAL_TEXTDOMAIN ), TRUE, '-empty' );

		if ( $group )
			$title = sprintf( _x( 'Cartable: %s', 'Modules: Cartable: Menu Title', GEDITORIAL_TEXTDOMAIN ), $term->name );
		else
			$title = _x( 'Your Cartable', 'Modules: Cartable: Page Title', GEDITORIAL_TEXTDOMAIN );

		Settings::headerTitle( $title, FALSE );

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
			'empty'      => Helper::tableArgEmptyPosts(),
			'pagination' => $pagination,
		] );
	}
}

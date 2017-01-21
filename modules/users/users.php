<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialUsers extends gEditorialModuleCore
{

	protected $caps = array(
		'reports' => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'  => 'users',
			'title' => _x( 'Users', 'Users Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Editorial Users', 'Users Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-users',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field' => 'posttype_counts',
					'title' => _x( 'Posttype Counts', 'Users Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field' => 'user_groups',
					'title' => _x( 'User Groups', 'Users Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
				),
				// array(
				// 	'field' => 'user_types',
				// 	'title' => _x( 'User Types', 'Users Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
				// ),
				'calendar_type',
				'admin_restrict',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'group_tax'      => 'user_group',
			'group_tax_slug' => 'users/group',
			'type_tax'       => 'user_type',
			'type_tax_slug'  => 'users/type',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'group_tax' => array(
					'show_option_all'    => _x( 'All user groups', 'Users Module: Show Option All', GEDITORIAL_TEXTDOMAIN ),
					'users_column_title' => _x( 'Users', 'Users Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'          => _x( 'Groups', 'Users Module: User Group Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				),
				'type_tax' => array(
					'show_option_all'    => _x( 'All user types', 'Users Module: Show Option All', GEDITORIAL_TEXTDOMAIN ),
					'users_column_title' => _x( 'Users', 'Users Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'          => _x( 'Types', 'Users Module: User Type Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				),
				'show_option_all'     => _x( 'All authors', 'Users Module: Show Option All', GEDITORIAL_TEXTDOMAIN ),
				'counts_column_title' => _x( 'Summary', 'Users Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings' => array(
				'posttype_stats' => _x( 'Query Stats', 'Users Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'group_tax' => _nx_noop( 'User Group', 'User Groups', 'Users Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'type_tax'  => _nx_noop( 'User Type', 'User Types', 'Users Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_users_init', $this->module );
		$this->do_globals();

		if ( ! $this->get_setting( 'user_groups', FALSE ) )
			return;

		$this->register_taxonomy( 'group_tax', array(
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'capabilities'       => array(
				'manage_terms' => 'list_users',
				'edit_terms'   => 'list_users',
				'delete_terms' => 'list_users',
				'assign_terms' => 'list_users',
			),
		), array( 'user' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_filter( 'parent_file', array( $this, 'parent_file' ) );
		}

		add_filter( 'sanitize_user', array( $this, 'sanitize_user' ) );
	}

	public function admin_menu()
	{
		if ( ! $tax = get_taxonomy( $this->constant( 'group_tax' ) ) )
			return;

		add_users_page(
			esc_attr( $tax->labels->menu_name ),
			esc_attr( $tax->labels->menu_name ),
			$tax->cap->manage_terms,
			'edit-tags.php?taxonomy='.$tax->name
		);
	}

	public function parent_file( $parent_file = '' )
	{
		global $pagenow;

		if ( ! empty( $_GET['taxonomy'] )
			&& $_GET['taxonomy'] == $this->constant( 'group_tax' )
			&& ( $pagenow == 'edit-tags.php' || $pagenow == 'term.php' ) )
				$parent_file = 'users.php';

		return $parent_file;
	}

	public function current_screen( $screen )
	{
		$groups = $this->get_setting( 'user_groups', FALSE );

		if ( 'edit' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {

			if ( $this->get_setting( 'admin_restrict', FALSE ) )
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ), 12, 2 );

		} else if ( 'users' == $screen->base ) {

			if ( $this->get_setting( 'posttype_counts', FALSE ) ) {
				add_filter( 'manage_users_columns', array( $this, 'manage_users_columns' ) );
				add_filter( 'manage_users_custom_column', array( $this, 'manage_users_custom_column' ), 10, 3 );
			}

		} else if ( $groups && ( 'profile' == $screen->base
			|| 'user-edit' == $screen->base ) ) {

			add_action( 'show_user_profile', array( $this, 'edit_user_profile' ), 5 );
			add_action( 'edit_user_profile', array( $this, 'edit_user_profile' ), 5 );
			add_action( 'personal_options_update', array( $this, 'edit_user_profile_update' ) );
			add_action( 'edit_user_profile_update', array( $this, 'edit_user_profile_update' ) );

		} else if ( $groups && 'edit-tags' == $screen->base
			&& $this->constant( 'group_tax' ) == $screen->taxonomy ) {

			add_filter( 'manage_edit-'.$this->constant( 'group_tax' ).'_columns', array( $this, 'manage_columns' ) );
			add_action( 'manage_'.$this->constant( 'group_tax' ).'_custom_column', array( $this, 'custom_column' ), 10, 3 );

		// } else if ( $groups && 'term' == $screen->base
		// 	&& $this->constant( 'group_tax' ) == $screen->taxonomy ) {

		}
	}

	public function sanitize_user( $username )
	{
		if ( $username == $this->constant( 'group_tax_slug' ) )
			$username = '';

		return $username;
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		global $wp_query;

		wp_dropdown_users( array(
			'name'                    => 'author',
			'show'                    => 'display_name_with_login',
			'selected'                => isset( $wp_query->query['author'] ) ? $wp_query->query['author'] : 0,
			'show_option_all'         => $this->get_string( 'show_option_all', get_query_var( 'post_type', 'post' ), 'misc' ),
			'option_none_value'       => 0,
			'hide_if_only_one_author' => TRUE,
		) );
	}

	public function manage_users_columns( $columns )
	{
		$new = array();

		foreach ( $columns as $column => $title )
			if ( 'posts' == $column )
				$new['counts'] = $this->get_column_title( 'counts', 'users' );
			else
				$new[$column] = $title;

		return $new;
	}

	public function manage_users_custom_column( $output, $column_name, $user_id )
	{
		if ( 'counts' != $column_name )
			return $output;

		if ( empty( $this->all_posttypes ) )
			$this->all_posttypes = gEditorialWPPostType::get( 1 );

		$counts = gEditorialWPDatabase::countPostsByUser( $user_id );
		$list   = array();

		foreach ( $this->all_posttypes as $posttype => $label )
			if ( ! empty( $counts[$posttype] ) )
				$list[$label] = gEditorialHTML::tag( 'a', array(
					'href'   => gEditorialWordPress::getPostTypeEditLink( $posttype, $user_id ),
					'target' => '_blank',
				), gEditorialNumber::format( $counts[$posttype] ) );

		ob_start();

		if ( count( $list ) )
			gEditorialHTML::tableCode( $list );
		else
			$this->column_count( 0 );

		return ob_get_clean();
	}

	public function manage_columns( $columns )
	{
		unset( $columns['posts'] );
		return array_merge( $columns, array( 'users' => $this->get_column_title( 'users', 'group_tax' ) ) );
	}

	public function custom_column( $display, $column, $term_id )
	{
		if ( 'users' == $column )
			$this->column_count( get_term( $term_id, $this->constant( 'group_tax' ) )->count );
	}

	public function edit_user_profile( $user )
	{
		$tax = get_taxonomy( $this->constant( 'group_tax' ) );

		if ( ! current_user_can( $tax->cap->assign_terms ) )
			return;

		$terms = get_terms( $this->constant( 'group_tax' ), array( 'hide_empty' => FALSE ) );

		echo gEditorialHTML::tag( 'h2', _x( 'Group', 'Users Module', GEDITORIAL_TEXTDOMAIN ) );

		echo '<table class="form-table">';
			echo '<tr><th scope="row">'._x( 'Select Group', 'Users Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $terms ) ) {

				foreach ( $terms as $term ) {

					$html = gEditorialHTML::tag( 'input', array(
						'type'    => 'radio',
						'name'    => 'groups',
						'id'      => 'groups-'.$term->slug,
						'value'   => $term->slug,
						'checked' => is_object_in_term( $user->ID, $this->constant( 'group_tax' ), $term ),
					) );

					echo '<p>'.gEditorialHTML::tag( 'label', array(
						'for' => 'groups-'.$term->slug,
					), $html.'&nbsp;'.esc_html( $term->name ) ).'</p>';
				 }

			} else {
				_ex( 'There are no groups available.', 'Users Module', GEDITORIAL_TEXTDOMAIN );
			}

		echo '</td></tr>';
		echo '</table>';
	}

	public function edit_user_profile_update( $user_id )
	{
		$tax = get_taxonomy( $this->constant( 'group_tax' ) );

		if ( ! current_user_can( 'edit_user', $user_id )
			&& current_user_can( $tax->cap->assign_terms ) )
				return FALSE;

		if ( ! isset( $_POST['groups'] ) )
			return;

		$term = esc_attr( $_POST['groups'] );
		wp_set_object_terms( $user_id, array( $term ), $this->constant( 'group_tax' ), FALSE );
		clean_object_term_cache( $user_id, $this->constant( 'group_tax' ) );
	}

	public function reports_settings( $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			return;

		if ( $this->module->name == $sub ) {

			// if ( ! empty( $_POST ) ) {
			// 	$this->settings_check_referer( $sub, 'reports' );
			// 	if ( isset( $_POST['posttype_stats'] ) ) {}
			// }

			// add_filter( 'geditorial_reports_messages', array( $this, 'reports_messages' ), 10, 2 );
			add_action( 'geditorial_reports_sub_'.$this->module->name, array( $this, 'reports_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_reports_subs', array( $this, 'append_sub' ), 10, 2 );
	}

	public function reports_sub( $uri, $sub )
	{
		$post       = empty( $_POST[$this->module->group]['reports'] ) ? array() : $_POST[$this->module->group]['reports'];
		$user_id    = empty( $post['user_id'] ) ? gEditorialHelper::getEditorialUserID() : $post['user_id'];
		$post_type  = empty( $post['post_type'] ) ? 'post' : $post['post_type'];
		$year_month = empty( $post['year_month'] ) ? '' : $post['year_month'];

		$calendar_type = $this->get_setting( 'calendar_type', 'gregorian' );

		echo '<form class="settings-form" method="post" action="">';

			$this->settings_field_referer( $sub, 'reports' );

			echo '<h3>'._x( 'User Reports', 'Users Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'By PostType', 'Users Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( array(
				'type'         => 'select',
				'field'        => 'post_type',
				'values'       => gEditorialWPPostType::get(),
				'default'      => $post_type,
				'option_group' => 'reports',
			) );

			$this->do_settings_field( array(
				'type'         => 'user',
				'field'        => 'user_id',
				'none_title'   => _x( 'All Users', 'Users Module', GEDITORIAL_TEXTDOMAIN ),
				'none_value'   => 0,
				'default'      => $user_id,
				'option_group' => 'reports',
			) );

			$this->do_settings_field( array(
				'type'         => 'select',
				'field'        => 'year_month',
				'values'       => gEditorialHelper::getPosttypeMonths( $calendar_type, $post_type, array(), $user_id ),
				'default'      => $year_month,
				'option_group' => 'reports',
			) );

			$this->submit_button( 'posttype_stats' );

			if ( ! empty( $_POST ) && isset( $_POST['posttype_stats'] ) ) {

				$period = $year_month ? gEditorialHelper::monthFirstAndLast( $calendar_type, substr( $year_month, 0, 4 ), substr( $year_month, 4, 2 ) ) : array();
				$posts  = gEditorialWPDatabase::countPostsByPosttype( $post_type, $user_id, $period );

				gEditorialHTML::tableCode( $posts );
			}

			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}

	// FIXME: DRAFT : need styling / register the shortcode!!
	public function user_groups_shortcode()
	{
		$term_id = get_queried_object_id();
		$term    = get_queried_object();
		$users   = get_objects_in_term( $term_id, $term->taxonomy );

		if ( ! empty( $users ) ) {

			foreach ( $users as $user_id ) {
				echo '<div class="user-entry">';

					// FIXME: use custom Avatar
					echo get_avatar( get_the_author_meta( 'email', $user_id ), '96' );

					echo '<h2 class="user-title">'.gEditorialHTML::tag( 'a', array(
						'href'  => get_author_posts_url( $user_id ),
						'title' => '',
					), get_the_author_meta( 'display_name', $user_id ) ).'</h2>';

					echo '<div class="description">'.wpautop( get_the_author_meta( 'description', $user_id ) ).'</div>';

				echo '</div>';
			}
		}
	}
}

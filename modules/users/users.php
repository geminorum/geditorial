<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Third;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Users extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'users',
			'title' => _x( 'Users', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Editorial Users', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-users',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'posttype_counts',
					'title'       => _x( 'Posttype Counts', 'Modules: Users: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays posttype count for each user', 'Modules: Users: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'user_groups',
					'title'       => _x( 'User Groups', 'Modules: Users: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Taxonomy for organizing users in groups', 'Modules: Users: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'user_types',
					'title'       => _x( 'User Types', 'Modules: Users: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Taxonomy for organizing users in types', 'Modules: Users: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				'dashboard_widgets',
				'admin_restrict',
				[
					'field'       => 'author_restrict',
					'title'       => _x( 'Author Restrictions', 'Modules: Users: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Enhance admin edit page for authors', 'Modules: Users: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'_reports' => [
				'calendar_type',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'group_tax'      => 'user_group',
			'group_tax_slug' => 'users/group',
			'type_tax'       => 'user_type',
			'type_tax_slug'  => 'users/type',
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'group_tax' => [
					'show_option_all'    => _x( 'All user groups', 'Modules: Users: Show Option All', GEDITORIAL_TEXTDOMAIN ),
					'users_column_title' => _x( 'Users', 'Modules: Users: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'          => _x( 'Groups', 'Modules: Users: User Group Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				],
				'type_tax' => [
					'show_option_all'    => _x( 'All user types', 'Modules: Users: Show Option All', GEDITORIAL_TEXTDOMAIN ),
					'users_column_title' => _x( 'Users', 'Modules: Users: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'          => _x( 'Types', 'Modules: Users: User Type Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
				],
				'show_option_all'     => _x( 'All authors', 'Modules: Users: Show Option All', GEDITORIAL_TEXTDOMAIN ),
				'counts_column_title' => _x( 'Summary', 'Modules: Users: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'group_tax' => _nx_noop( 'User Group', 'User Groups', 'Modules: Users: Noop', GEDITORIAL_TEXTDOMAIN ),
				'type_tax'  => _nx_noop( 'User Type', 'User Types', 'Modules: Users: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'user_groups', FALSE ) ) {

			$this->register_taxonomy( 'group_tax', [
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
				'capabilities'       => [
					'manage_terms' => 'list_users',
					'edit_terms'   => 'list_users',
					'delete_terms' => 'list_users',
					'assign_terms' => 'list_users',
				],
			], [ 'user' ] );

			// no need, we use slash in slug
			// add_filter( 'sanitize_user', [ $this, 'sanitize_user' ] );
		}
	}

	public function admin_menu()
	{
		if ( ! $this->get_setting( 'user_groups' ) )
			return;

		if ( ! $tax = get_taxonomy( $this->constant( 'group_tax' ) ) )
			return;

		add_users_page(
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
		$groups = $this->get_setting( 'user_groups', FALSE );

		if ( 'edit' == $screen->base
			&& in_array( $screen->post_type, $this->post_types() ) ) {

			if ( $this->get_setting( 'admin_restrict', FALSE ) )
				$this->action( 'restrict_manage_posts', 2, 12 );

			if ( $this->get_setting( 'author_restrict', FALSE ) )
				$this->action( 'pre_get_posts' );

		} else if ( 'users' == $screen->base ) {

			if ( $this->get_setting( 'posttype_counts', FALSE ) ) {
				$this->filter( 'manage_users_columns' );
				$this->filter( 'manage_users_custom_column', 3 );
			}

			add_action( 'geditorial_tweaks_column_user', [ $this, 'column_user' ], 12 );

		} else if ( $groups && ( 'profile' == $screen->base
			|| 'user-edit' == $screen->base ) ) {

			add_action( 'show_user_profile', [ $this, 'edit_user_profile' ], 5 );
			add_action( 'edit_user_profile', [ $this, 'edit_user_profile' ], 5 );
			add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
			add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );

		} else if ( $groups && 'edit-tags' == $screen->base
			&& $this->constant( 'group_tax' ) == $screen->taxonomy ) {

			$this->filter( 'parent_file' );

			add_filter( 'manage_edit-'.$this->constant( 'group_tax' ).'_columns', [ $this, 'manage_columns' ] );
			add_action( 'manage_'.$this->constant( 'group_tax' ).'_custom_column', [ $this, 'custom_column' ], 10, 3 );

		// } else if ( $groups && 'term' == $screen->base
		// 	&& $this->constant( 'group_tax' ) == $screen->taxonomy ) {

		}
	}

	public function parent_file( $parent_file )
	{
		global $pagenow;

		if ( ! empty( $_GET['taxonomy'] )
			&& $_GET['taxonomy'] == $this->constant( 'group_tax' )
			&& ( $pagenow == 'edit-tags.php' || $pagenow == 'term.php' ) )
				$parent_file = 'users.php';

		return $parent_file;
	}

	public function sanitize_user( $username )
	{
		if ( $username == $this->constant( 'group_tax_slug' ) )
			$username = '';

		else if ( $username == $this->constant( 'type_tax_slug' ) )
			$username = '';

		return $username;
	}

	protected function dashboard_widgets()
	{
		$title = _x( 'Your Profile', 'Modules: Users: Dashboard Widget Title', GEDITORIAL_TEXTDOMAIN );
		$title.= ' <span class="postbox-title-action"><a href="'.esc_url( admin_url( 'profile.php' )  ).'"';
		$title.= ' title="'._x( 'Edit your profile', 'Modules: Users: Dashboard Widget Action', GEDITORIAL_TEXTDOMAIN ).'">';
		$title.= _x( 'Edit', 'Modules: Users: Dashboard Widget Action', GEDITORIAL_TEXTDOMAIN ).'</a></span>';

		wp_add_dashboard_widget( $this->classs( 'profile-summary' ), $title, [ $this, 'dashboard_summary' ] );
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		global $wp_query;

		wp_dropdown_users( [
			'name'                    => 'author',
			'who'                     => 'authors',
			'show'                    => 'display_name_with_login',
			'selected'                => $wp_query->get( 'author' ) ? $wp_query->get( 'author' ) : 0,
			'show_option_all'         => $this->get_string( 'show_option_all', get_query_var( 'post_type', 'post' ), 'misc' ),
			'option_none_value'       => 0,
			'hide_if_only_one_author' => TRUE,
		] );
	}

	public function pre_get_posts( &$wp_query )
	{
 		if ( ! $wp_query->is_admin )
			return;

		if ( current_user_can( 'edit_others_posts' ) )
			return;

		if ( '' === $wp_query->get( 'author' ) )
			$wp_query->set( 'author', $GLOBALS['user_ID'] );
	}

	public function manage_users_columns( $columns )
	{
		$new = [];

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
			$this->all_posttypes = PostType::get( 1 );

		$counts = Database::countPostsByUser( $user_id );
		$list   = [];

		foreach ( $this->all_posttypes as $posttype => $label )
			if ( ! empty( $counts[$posttype] ) )
				$list[$label] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, $user_id ),
					'target' => '_blank',
				], Number::format( $counts[$posttype] ) );

		ob_start();

		if ( count( $list ) )
			echo HTML::tableCode( $list );
		else
			$this->column_count( 0 );

		return ob_get_clean();
	}

	public function column_user( $user )
	{
		if ( $this->get_setting( 'user_groups', FALSE ) ) {

			if ( $terms = Taxonomy::getTerms( $this->constant( 'group_tax' ), $user->ID, TRUE, 'term_id', [], FALSE ) ) {

				foreach ( $terms as $term ) {

					echo '<li class="-row -groups">';
						echo $this->get_column_icon( FALSE, 'networking', _x( 'Group', 'Modules: Users: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
						echo sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
					echo '</li>';
				}
			}
		}

		if ( $this->get_setting( 'user_types', FALSE ) ) {

			if ( $terms = Taxonomy::getTerms( $this->constant( 'type_tax' ), $user->ID, TRUE, 'term_id', [], FALSE ) ) {

				foreach ( $terms as $term ) {

					echo '<li class="-row -types">';
						echo $this->get_column_icon( FALSE, 'networking', _x( 'Type', 'Modules: Users: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
						echo sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
					echo '</li>';
				}
			}
		}
	}

	public function manage_columns( $columns )
	{
		unset( $columns['posts'] );
		return array_merge( $columns, [ 'users' => $this->get_column_title( 'users', 'group_tax' ) ] );
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

		$terms = get_terms( $this->constant( 'group_tax' ), [ 'hide_empty' => FALSE ] );

		HTML::h2( _x( 'Group', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ) );

		echo '<table class="form-table">';
			echo '<tr><th scope="row">'._x( 'Select Group', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $terms ) ) {

				echo '<div class="wp-tab-panel"><ul>';

				foreach ( $terms as $term ) {

					$html = HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'groups',
						'id'      => 'groups-'.$term->slug,
						'value'   => $term->slug,
						'checked' => is_object_in_term( $user->ID, $this->constant( 'group_tax' ), $term ),
					] );

					echo '<li>'.HTML::tag( 'label', [
						'for' => 'groups-'.$term->slug,
					], $html.'&nbsp;'.HTML::escape( $term->name ) ).'</li>';
				 }

				echo '</ul></div>';

			} else {
				_ex( 'There are no groups available.', 'Modules: Users', GEDITORIAL_TEXTDOMAIN );
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

		wp_set_object_terms( $user_id, [ HTML::escape( $_POST['groups'] ) ], $this->constant( 'group_tax' ), FALSE );

		clean_object_term_cache( $user_id, $this->constant( 'group_tax' ) );
	}

	public function dashboard_summary()
	{
		if ( $this->check_hidden_metabox( 'profile-summary' ) )
			return;

		$user = wp_get_current_user();

		echo '<div class="geditorial-admin-wrap-widget -users -contacts">';

		echo HTML::wrap( get_avatar( $user->user_email, 125 ), '-avatar' );

		echo '<ul class="-rows">';

		if ( $user->first_name || $user->last_name ) {
			echo '<li class="-row -name">';
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Modules: Users: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo "$user->first_name $user->last_name";
			echo '</li>';
		}

		if ( $user->user_email ) {
			echo '<li class="-row -email">';
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Modules: Users: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo HTML::mailto( $user->user_email );
			echo '</li>';
		}

		if ( $user->user_url ) {
			echo '<li class="-row -url">';
				echo $this->get_column_icon( FALSE, 'admin-links', _x( 'URL', 'Modules: Users: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo HTML::link( URL::prepTitle( $user->user_url ), $user->user_url );
			echo '</li>';
		}

		foreach ( wp_get_user_contact_methods( $user ) as $method => $title ) {

			if ( ! $meta = get_user_meta( $user->ID, $method, TRUE ) )
				continue;

			if ( in_array( $method, [ 'twitter', 'facebook', 'googleplus' ] ) )
				$icon = $method;
			else if ( in_array( $method, [ 'mobile', 'phone' ] ) )
				$icon = 'phone';
			else
				$icon = 'email-alt';

			echo '<li class="-row -contact -contact-'.$method.'">';
				echo $this->get_column_icon( FALSE, $icon, $title );
				echo $this->display_meta( $meta, $method );
			echo '</li>';
		}

		if ( $user->user_registered ) {
			echo '<li class="-row -registered">';
				echo $this->get_column_icon( FALSE, 'calendar', _x( 'Registered', 'Modules: Users: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo sprintf( _x( 'Registered on %s', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ),
					Helper::getDateEditRow( $user->user_registered, '-registered' ) );
			echo '</li>';
		}

		echo '</ul><div class="clear"></div></div>';
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'mobile'    : return HTML::tel( $value );
			case 'twitter'   : return Third::htmlTwitterIntent( $value, TRUE );
			case 'googleplus': return HTML::link( URL::prepTitle( $value ), $value );
			case 'facebook'  : return HTML::link( URL::prepTitle( $value ), $value );
		}

		return HTML::escape( $value );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	public function reports_sub( $uri, $sub )
	{
		$args = $this->settings_form_req( [
			'post_type'  => 'post',
			'user_id'    => '0',
			'year_month' => '',
		], 'reports' );

		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE, FALSE );

			HTML::h3( _x( 'User Reports', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'By PostType', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'post_type',
				'values'       => PostType::get(),
				'default'      => $args['post_type'],
				'option_group' => 'reports',
			] );

			echo '&nbsp;';

			$this->do_settings_field( [
				'type'         => 'user',
				'field'        => 'user_id',
				'none_title'   => _x( 'All Users', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ),
				'default'      => $args['user_id'],
				'option_group' => 'reports',
			] );

			echo '&nbsp;';

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'year_month',
				'none_title'   => _x( 'All Months', 'Modules: Users', GEDITORIAL_TEXTDOMAIN ),
				'values'       => Helper::getPostTypeMonths( $this->default_calendar(), $args['post_type'], [], $args['user_id'] ),
				'default'      => $args['year_month'],
				'option_group' => 'reports',
			] );

			echo '&nbsp;';

			Settings::submitButton( 'posttype_stats',
				_x( 'Query Stats', 'Modules: Users: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

			if ( ! empty( $_POST ) && isset( $_POST['posttype_stats'] ) ) {

				$period = $args['year_month'] ? Helper::monthFirstAndLast( $this->default_calendar(), substr( $args['year_month'], 0, 4 ), substr( $args['year_month'], 4, 2 ) ) : [];

				echo HTML::tableCode( Database::countPostsByPosttype( $args['post_type'], $args['user_id'], $period ) );
			}

			echo '</td></tr>';
			echo '</table>';
		$this->settings_form_after( $uri, $sub );
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

					echo '<h2 class="user-title">'.HTML::tag( 'a', [
						'href'  => get_author_posts_url( $user_id ),
						'title' => '',
					], get_the_author_meta( 'display_name', $user_id ) ).'</h2>';

					echo '<div class="description">'.wpautop( get_the_author_meta( 'description', $user_id ) ).'</div>';

				echo '</div>';
			}
		}
	}
}

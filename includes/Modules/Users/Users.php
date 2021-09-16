<?php namespace geminorum\gEditorial\Modules\Users;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Third;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\User;

class Users extends gEditorial\Module
{

	protected $caps = [
		'tools'   => 'edit_users',
		'reports' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'  => 'users',
			'title' => _x( 'Users', 'Modules: Users', 'geditorial' ),
			'desc'  => _x( 'Editorial Users', 'Modules: Users', 'geditorial' ),
			'icon'  => 'admin-users',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'posttype_counts',
					'title'       => _x( 'Posttype Counts', 'Setting Title', 'geditorial-users' ),
					'description' => _x( 'Displays posttype count for each user', 'Setting Description', 'geditorial-users' ),
				],
				[
					'field'       => 'user_groups',
					'title'       => _x( 'User Groups', 'Setting Title', 'geditorial-users' ),
					'description' => _x( 'Taxonomy for organizing users in groups', 'Setting Description', 'geditorial-users' ),
				],
				[
					'field'       => 'user_types',
					'title'       => _x( 'User Types', 'Setting Title', 'geditorial-users' ),
					'description' => _x( 'Taxonomy for organizing users in types', 'Setting Description', 'geditorial-users' ),
				],
				'dashboard_widgets',
				'admin_restrict',
				[
					'field'       => 'author_restrict',
					'title'       => _x( 'Author Restrictions', 'Setting Title', 'geditorial-users' ),
					'description' => _x( 'Enhance admin edit page for authors', 'Setting Description', 'geditorial-users' ),
				],
				[
					'field'       => 'author_categories',
					'title'       => _x( 'Author Categories', 'Setting Title', 'geditorial-users' ),
					'description' => _x( 'Limits each author to post just on selected categories.', 'Setting Description', 'geditorial-users' ),
				],
			],
			'_reports' => [
				'calendar_type',
			],
			'_supports' => [
				'restapi_support',
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

			'metakey_categories' => 'author_categories_%s',
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'group_tax' => [
					'menu_name'          => _x( 'Groups', 'Taxonomy Menu', 'geditorial-users' ),
					'users_column_title' => _x( 'Users', 'Column Title', 'geditorial-users' ),
					'show_option_all'    => _x( 'All user groups', 'Show Option All', 'geditorial-users' ),
				],
				'type_tax' => [
					'menu_name'          => _x( 'Types', 'Taxonomy Menu', 'geditorial-users' ),
					'users_column_title' => _x( 'Users', 'Column Title', 'geditorial-users' ),
					'show_option_all'    => _x( 'All user types', 'Show Option All', 'geditorial-users' ),
				],
				'counts_column_title' => _x( 'Summary', 'Column Title', 'geditorial-users' ),
			],
			'noops' => [
				'group_tax' => _n_noop( 'User Group', 'User Groups', 'geditorial-users' ),
				'type_tax'  => _n_noop( 'User Type', 'User Types', 'geditorial-users' ),
			],
			'labels' => [
				'group_tax' => [
					'not_found' => _x( 'There are no groups available.', 'Label: Not Found', 'geditorial-users' ),
				],
				'type_tax'  => [
					'not_found' => _x( 'There are no types available.', 'Label: Not Found', 'geditorial-users' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'user_groups' ) ) {

			$this->register_taxonomy( 'group_tax', [
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
			], 'user' );

			// no need, we use slash in slug
			// add_filter( 'sanitize_user', [ $this, 'sanitize_user' ] );
		}

		if ( $this->get_setting( 'user_types' ) )
			$this->register_taxonomy( 'type_tax', [
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
			], 'user' );

		if ( $this->get_setting( 'author_categories' ) )
			$this->filter( 'pre_option_default_category', 3 );
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'user_groups' ) )
			$this->_hook_menu_user_taxonomy( 'group_tax' );

		if ( $this->get_setting( 'user_types' ) )
			$this->_hook_menu_user_taxonomy( 'type_tax' );
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
	}

	public function current_screen( $screen )
	{
		$groups     = $this->get_setting( 'user_groups' );
		$types      = $this->get_setting( 'user_types' );
		$categories = $this->get_setting( 'author_categories' );

		if ( 'users' == $screen->base ) {

			if ( $this->get_setting( 'posttype_counts', FALSE ) ) {
				$this->filter( 'manage_users_columns' );
				$this->filter( 'manage_users_custom_column', 3 );
			}

			$this->action_module( 'tweaks', 'column_user', 1, 12 );

		} else if ( $categories && 'post' == $screen->base
			&& is_object_in_taxonomy( $screen->post_type, 'category' ) ) {

			if ( current_user_can( 'edit_posts' )
				&& ! current_user_can( 'edit_others_posts' ) ) {

				remove_meta_box( 'categorydiv', $screen, 'side' );
				add_meta_box( $this->classs( 'categories' ),
					__( 'Categories' ),
					[ $this, 'render_metabox_categories' ],
					$screen,
					'side',
					'core'
				);
			}

		} else if ( 'edit' == $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			if ( $this->get_setting( 'admin_restrict', FALSE ) )
				$this->action( 'restrict_manage_posts', 2, 12 );

			if ( $this->get_setting( 'author_restrict', FALSE ) )
				$this->action( 'pre_get_posts' );

			if ( $categories && is_object_in_taxonomy( $screen->post_type, 'category' ) ) {

				if ( current_user_can( 'edit_posts' )
					&& ! current_user_can( 'edit_others_posts' ) )
						$this->_admin_enabled();
			}

		} else if ( $groups || $types || $categories ) {

			if ( 'profile' == $screen->base || 'user-edit' == $screen->base ) {

				add_action( 'show_user_profile', [ $this, 'edit_user_profile' ], 5 );
				add_action( 'edit_user_profile', [ $this, 'edit_user_profile' ], 5 );
				add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
				add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );

			} else if ( $screen->taxonomy == $this->constant( 'group_tax' ) ) {

				$this->filter_string( 'parent_file', 'users.php' );

				if ( 'edit-tags' == $screen->base ) {
					add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', [ $this, 'manage_columns_groups' ] );
					add_action( 'manage_'.$screen->taxonomy.'_custom_column', [ $this, 'custom_column_groups' ], 10, 3 );
				}

			} else if ( $screen->taxonomy == $this->constant( 'type_tax' ) ) {

				$this->filter_string( 'parent_file', 'users.php' );

				if ( 'edit-tags' == $screen->base ) {
					add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', [ $this, 'manage_columns_types' ] );
					add_action( 'manage_'.$screen->taxonomy.'_custom_column', [ $this, 'custom_column_types' ], 10, 3 );
				}
			}
		}
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
		$title = _x( 'Your Profile', 'Dashboard Widget Title', 'geditorial-users' );
		$this->add_dashboard_widget( 'profile-summary', $title, [
			'url'   => admin_url( 'profile.php' ),
			'title' => _x( 'Edit your profile', 'Dashboard Widget Action', 'geditorial-users' ),
			'link'  => _x( 'Edit', 'Dashboard Widget Action', 'geditorial-users' ),
		] );
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_authors( $posttype );
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

		foreach ( $columns as $column => $title ) {

			if ( 'posts' == $column ) {

				$new[$this->classs( 'counts' )] = $this->get_column_title( 'counts', 'users' );

			} else if ( 'geditorial-tweaks-id' == $column ) {

				$new[$this->classs( 'counts' )] = $this->get_column_title( 'counts', 'users' );

				$new[$column] = $title;

			} else {

				$new[$column] = $title;
			}
		}

		return $new;
	}

	public function manage_users_custom_column( $output, $column_name, $user_id )
	{
		if ( $this->classs( 'counts' ) != $column_name )
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
			echo Listtable::columnCount( 0 );

		return ob_get_clean();
	}

	// FIXME: use `Helper::renderUserTermsEditRow()`
	public function tweaks_column_user( $user )
	{
		if ( $this->get_setting( 'user_groups', FALSE ) ) {

			if ( $terms = Taxonomy::getTerms( $this->constant( 'group_tax' ), $user->ID, TRUE, 'term_id', [], FALSE ) ) {

				foreach ( $terms as $term ) {

					echo '<li class="-row -groups">';
						echo $this->get_column_icon( FALSE, 'networking', _x( 'Group', 'Row Icon Title', 'geditorial-users' ) );
						echo sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
					echo '</li>';
				}
			}
		}

		if ( $this->get_setting( 'user_types', FALSE ) ) {

			if ( $terms = Taxonomy::getTerms( $this->constant( 'type_tax' ), $user->ID, TRUE, 'term_id', [], FALSE ) ) {

				foreach ( $terms as $term ) {

					echo '<li class="-row -types">';
						echo $this->get_column_icon( FALSE, 'networking', _x( 'Type', 'Row Icon Title', 'geditorial-users' ) );
						echo sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
					echo '</li>';
				}
			}
		}
	}

	public function manage_columns_groups( $columns )
	{
		unset( $columns['posts'] );
		return array_merge( $columns, [ 'users' => $this->get_column_title( 'users', 'group_tax' ) ] );
	}

	public function custom_column_groups( $display, $column, $term_id )
	{
		if ( 'users' == $column )
			echo Listtable::columnCount( get_term( $term_id, $this->constant( 'group_tax' ) )->count );
	}

	public function manage_columns_types( $columns )
	{
		unset( $columns['posts'] );
		return array_merge( $columns, [ 'users' => $this->get_column_title( 'users', 'type_tax' ) ] );
	}

	public function custom_column_types( $display, $column, $term_id )
	{
		if ( 'users' == $column )
			echo Listtable::columnCount( get_term( $term_id, $this->constant( 'type_tax' ) )->count );
	}

	public function edit_user_profile( $user )
	{
		if ( $this->get_setting( 'user_groups' ) )
			MetaBox::tableRowObjectTaxonomy( $this->constant( 'group_tax' ), $user->ID, $this->classs( 'custom_tax' ), NULL, '<table class="form-table">', '</table>' );

		if ( $this->get_setting( 'user_types' ) )
			MetaBox::tableRowObjectTaxonomy( $this->constant( 'type_tax' ), $user->ID, $this->classs( 'custom_tax' ), NULL, '<table class="form-table">', '</table>' );

		if ( $this->get_setting( 'author_categories' ) ) {

			if ( user_can( $user, 'edit_posts' ) && ! user_can( $user, 'edit_others_posts' ) )
				$this->render_author_categories( $user );
		}
	}

	private function render_author_categories( $user )
	{
		$terms    = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => FALSE ] );
		$default  = get_option( 'default_category' );
		$selected = $this->get_user_categories( $user->ID );

		HTML::h2( _x( 'Site Categories', 'Header', 'geditorial-users' ) );
		HTML::desc( _x( 'Restrict non editor users to post in selected categories only.', 'Message', 'geditorial-users' ) );

		echo '<table class="form-table">';
			echo '<tr><th scope="row">'._x( 'User Categories', 'Header', 'geditorial-users' ).'</th><td>';

			if ( ! empty( $terms ) ) {

				echo '<div class="wp-tab-panel"><ul>';

				foreach ( $terms as $term ) {

					if ( $default == $term->term_id )
						continue;

					$html = HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'categories[]',
						'id'      => 'categories-'.$term->slug,
						'value'   => $term->term_id,
						'checked' => in_array( $term->term_id, $selected ),
					] );

					HTML::label( $html.'&nbsp;'.HTML::escape( $term->name ), 'categories-'.$term->slug, 'li' );
				 }

				echo '</ul></div>';

				// passing empty value for clearing up
				echo '<input type="hidden" name="categories[]" value="0" />';

			} else {
				_ex( 'There are no categories available.', 'Message', 'geditorial-users' );
			}

		echo '</td></tr>';
		echo '</table>';
	}

	private function get_user_categories( $user_id = NULL, $blog_id = NULL, $fallback = TRUE )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( is_null( $blog_id ) )
			$blog_id = get_current_blog_id();

		$key = sprintf( $this->constant( 'metakey_categories' ), $blog_id );

		if ( $cats = get_user_meta( $user_id, $key, TRUE ) )
			return (array) $cats;

		return $fallback ? [ get_option( 'default_category' ) ] : [];
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( ! current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		$selected = self::req( $this->classs( 'custom_tax' ), [] );

		if ( $this->get_setting( 'user_groups' ) )
			MetaBox::storeObjectTaxonomy( $this->constant( 'group_tax' ), $user_id, $selected );

		if ( $this->get_setting( 'user_types' ) )
			MetaBox::storeObjectTaxonomy( $this->constant( 'type_tax' ), $user_id, $selected );

		if ( $this->get_setting( 'author_categories' ) && isset( $_POST['categories'] ) ) {

			$key = sprintf( $this->constant( 'metakey_categories' ), get_current_blog_id() );
			update_user_meta( $user_id, $key, array_filter( $_POST['categories'] ) );
		}
	}

	public function pre_option_default_category( $false, $option, $default )
	{
		if ( current_user_can( 'edit_posts' )
			&& ! current_user_can( 'edit_others_posts' ) ) {

			$selected = $this->get_user_categories( NULL, NULL, FALSE );

			// only if user has one cat, otherwise fallback to default
			if ( 1 === count( $selected ) )
				return $selected[0];
		}

		return $false;
	}

	public function render_metabox_categories( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$terms = [];

		foreach ( $this->get_user_categories() as $selected )
			$terms[] = Taxonomy::getTerm( $selected, 'category' );

		echo $this->wrap_open( '-admin-metabox' );

			MetaBox::checklistTerms( $post->ID, [
				'taxonomy'          => 'category',
				'posttype'          => $post->post_type,
				'edit'              => FALSE,
				'selected_cats'     => 1 === count( $terms ) ? [ $terms[0]->term_id ] : FALSE,
				'selected_preserve' => TRUE,
			], $terms );

		echo '</div>';
	}

	public function render_widget_profile_summary( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		$user = wp_get_current_user();

		echo '<div class="geditorial-wrap -admin-widget -users -contacts">';

		echo HTML::wrap( get_avatar( $user->user_email, 125 ), '-avatar' );

		echo '<ul class="-rows">';

		if ( $user->first_name || $user->last_name ) {
			echo '<li class="-row -name">';
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Row Icon Title', 'geditorial-users' ) );
				echo "$user->first_name $user->last_name";
			echo '</li>';
		}

		if ( $user->user_email ) {
			echo '<li class="-row -email">';
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Row Icon Title', 'geditorial-users' ) );
				echo HTML::mailto( $user->user_email );
			echo '</li>';
		}

		if ( $user->user_url ) {
			echo '<li class="-row -url">';
				echo $this->get_column_icon( FALSE, 'admin-links', _x( 'URL', 'Row Icon Title', 'geditorial-users' ) );
				echo HTML::link( URL::prepTitle( $user->user_url ), $user->user_url );
			echo '</li>';
		}

		foreach ( wp_get_user_contact_methods( $user ) as $method => $title ) {

			if ( ! $meta = get_user_meta( $user->ID, $method, TRUE ) )
				continue;

			echo '<li class="-row -contact -contact-'.$method.'">';
				echo $this->get_column_icon( FALSE, Icon::guess( $method, 'email-alt' ), $title );
				echo $this->display_meta_row( $meta, $method );
			echo '</li>';
		}

		if ( $user->user_registered ) {
			echo '<li class="-row -registered">';
				echo $this->get_column_icon( FALSE, 'calendar', _x( 'Registered', 'Row Icon Title', 'geditorial-users' ) );
				/* translators: %s: date */
				printf( _x( 'Registered on %s', 'Row', 'geditorial-users' ),
					Helper::getDateEditRow( $user->user_registered, '-registered' ) );
			echo '</li>';
		}

		$role = $this->get_column_icon( FALSE, 'businessman', _x( 'Roles', 'Row Icon Title', 'geditorial-users' ) );
		echo Helper::getJoined( User::getRoleList( $user ), '<li class="-row -roles">'.$role, '</li>' );

		$this->tweaks_column_user( $user );

		echo '</ul><div class="clear"></div></div>';
	}

	public function display_meta_row( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'mobile'    : return HTML::tel( $value );
			case 'twitter'   : return Third::htmlTwitterIntent( $value, TRUE );
			case 'facebook'  : return HTML::link( URL::prepTitle( $value ), $value );
			case 'instagram' : return Third::htmlHandle( $value, 'https://instagram.com/' );
			case 'telegram'  : return Third::htmlHandle( $value, 'https://t.me/' );
		}

		return parent::display_meta_row( $value, $key, $field );
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports' );
	}

	// MAYBE: move to Statistics module
	protected function render_reports_html( $uri, $sub )
	{
		$args = $this->get_current_form( [
			'post_type'  => 'post',
			'user_id'    => '0',
			'year_month' => '',
		], 'reports' );

		HTML::h3( _x( 'User Reports', 'Header', 'geditorial-users' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'By PostType', 'Header', 'geditorial-users' ).'</th><td>';

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'post_type',
			'values'       => PostType::get( 0, [ 'show_ui' => TRUE ] ),
			'default'      => $args['post_type'],
			'option_group' => 'reports',
		] );

		echo '&nbsp;';

		$this->do_settings_field( [
			'type'         => 'user',
			'field'        => 'user_id',
			'none_title'   => _x( 'All Users', 'None Title', 'geditorial-users' ),
			'default'      => $args['user_id'],
			'option_group' => 'reports',
		] );

		echo '&nbsp;';

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'year_month',
			'none_title'   => _x( 'All Months', 'None Title', 'geditorial-users' ),
			'values'       => Datetime::getPostTypeMonths( $this->default_calendar(), $args['post_type'], [], $args['user_id'] ),
			'default'      => $args['year_month'],
			'option_group' => 'reports',
		] );

		echo '&nbsp;';

		Settings::submitButton( 'posttype_stats', _x( 'Query Stats', 'Button', 'geditorial-users' ) );

		if ( ! empty( $_POST ) && isset( $_POST['posttype_stats'] ) ) {

			$period = $args['year_month'] ? Datetime::monthFirstAndLast( $this->default_calendar(), substr( $args['year_month'], 0, 4 ), substr( $args['year_month'], 4, 2 ) ) : [];

			echo HTML::tableCode( Database::countPostsByPosttype( $args['post_type'], $args['user_id'], $period ) );
		}

		echo '</td></tr>';
		echo '</table>';
	}

	public function tools_settings( $sub )
	{
		global $wpdb;

		if ( $this->check_settings( $sub, 'tools' ) ) {
			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'remap_post_authors' ) ) {

					// FIXME: use `Media::handleImportUpload()`
					$file = wp_import_handle_upload();

					if ( isset( $file['error'] ) || empty( $file['file'] ) )
						WordPress::redirectReferer( 'wrong' );

					$count    = 0;
					$blog_id  = get_current_blog_id();
					$role     = get_option( 'default_role' );

					$users    = User::get( TRUE, TRUE, [], 'user_email' );
					$currents = $wpdb->get_results( "SELECT post_author as user, GROUP_CONCAT( ID ) as posts FROM {$wpdb->posts} GROUP BY post_author", ARRAY_A );

					// FIXME: use `Helper::parseCSV()`
					$iterator = new \SplFileObject( File::normalize( $file['file'] ) );
					$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8', 'limit' => 1 ] );
					$header   = $parser->parse();
					$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8', 'offset' => 1, 'header' => $header[0] ] );
					$old_map  = Arraay::reKey( $parser->parse(), 'ID' );

					foreach ( $currents as $current ) {

						if ( isset( $old_map[$current['user']] )
							&& isset( $users[$old_map[$current['user']]['user_email']] ) ) {

							$user  = $users[$old_map[$current['user']]['user_email']];
							$query = $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author = %d WHERE ID IN ( ".trim( $current['posts'], ',' )." )", $user->ID );
							$count+= $wpdb->query( $query );

							if ( ! is_user_member_of_blog( $user->ID, $blog_id ) )
								add_user_to_blog( $blog_id, $user->ID, $role );
						}
					}

					WordPress::redirectReferer( [
						'message'    => 'synced',
						'count'      => $count,
						'attachment' => $file['id'],
					] );

				} else {

					WordPress::redirectReferer( 'huh' );
				}
			}
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo '<table class="form-table">';
		echo '<tr><th scope="row">'._x( 'Re-Map Authors', 'Header', 'geditorial-users' ).'</th><td>';

		$wpupload = Media::upload();

		if ( ! empty( $wpupload['error'] ) ) {

			/* translators: %s: error */
			echo HTML::error( sprintf( _x( 'Before you can upload a file, you will need to fix the following error: %s', 'Message', 'geditorial-users' ), '<b>'.$wpupload['error'].'</b>' ), FALSE );

		} else {

			$this->do_settings_field( [
				'type'      => 'file',
				'field'     => 'import_users_file',
				'name_attr' => 'import',
				'values'    => [ '.csv' ],
			] );

			$size = File::formatSize( apply_filters( 'import_upload_size_limit', wp_max_upload_size() ) );

			/* translators: %s: size */
			HTML::desc( sprintf( _x( 'Checks for post authors and re-map them with current registered users. Maximum upload size: <b>%s</b>', 'Message', 'geditorial-users' ), HTML::wrapLTR( $size ) ) );

			echo '<br />';
			echo $this->wrap_open_buttons();
				Settings::submitButton( 'remap_post_authors', _x( 'Upload and Re-Map', 'Button', 'geditorial-users' ), 'danger' );
			echo '</p>';
		}

		echo '</td></tr>';
		echo '</table>';
	}

	// FIXME: DRAFT : need styling / register the shortcode!!
	// @SEE: https://core.trac.wordpress.org/ticket/31383
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

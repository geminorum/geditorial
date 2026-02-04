<?php namespace geminorum\gEditorial\Modules\Users;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Users extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\PostMeta;

	protected $caps = [
		'tools' => 'edit_users',
	];

	public static function module()
	{
		return [
			'name'     => 'users',
			'title'    => _x( 'Users', 'Modules: Users', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Users', 'Modules: Users', 'geditorial-admin' ),
			'icon'     => 'admin-users',
			'access'   => 'beta',
			'keywords' => [
				'role',
				'capability',
				'profile',
				'sysmodule',
			],
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
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'group_taxonomy'      => 'user_group',
			'group_taxonomy_slug' => 'users/group',
			'type_taxonomy'       => 'user_type',
			'type_taxonomy_slug'  => 'users/type',

			'metakey_categories' => 'author_categories_%s',
		];
	}

	protected function get_global_strings()
	{
		return [
			'dashboard' => [
				'widget_title' => _x( 'Your Profile', 'Dashboard Widget Title', 'geditorial-users' ),
				'action_attr'  => _x( 'Edit your profile', 'Dashboard Widget Action', 'geditorial-users' ),
				'action_link'  => _x( 'Edit', 'Dashboard Widget Action', 'geditorial-users' ),
			],
			'misc' => [
				'group_taxonomy' => [
					'menu_name'          => _x( 'Groups', 'Taxonomy Menu', 'geditorial-users' ),
					'users_column_title' => _x( 'Users', 'Column Title', 'geditorial-users' ),
					'show_option_all'    => _x( 'All user groups', 'Show Option All', 'geditorial-users' ),
				],
				'type_taxonomy' => [
					'menu_name'          => _x( 'Types', 'Taxonomy Menu', 'geditorial-users' ),
					'users_column_title' => _x( 'Users', 'Column Title', 'geditorial-users' ),
					'show_option_all'    => _x( 'All user types', 'Show Option All', 'geditorial-users' ),
				],
				'counts_column_title' => _x( 'Summary', 'Column Title', 'geditorial-users' ),
			],
			'noops' => [
				'group_taxonomy' => _n_noop( 'User Group', 'User Groups', 'geditorial-users' ),
				'type_taxonomy'  => _n_noop( 'User Type', 'User Types', 'geditorial-users' ),
			],
			'labels' => [
				'group_taxonomy' => [
					'not_found' => _x( 'There are no groups available.', 'Label: Not Found', 'geditorial-users' ),
				],
				'type_taxonomy'  => [
					'not_found' => _x( 'There are no types available.', 'Label: Not Found', 'geditorial-users' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'user_groups' ) ) {

			$this->register_taxonomy( 'group_taxonomy', [
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
			], FALSE, [
				'target_object' => 'user',
				'custom_icon'   => 'groups',
			] );

			// no need, we use slash in slug
			// add_filter( 'sanitize_user', [ $this, 'sanitize_user' ] );
		}

		if ( $this->get_setting( 'user_types' ) )
			$this->register_taxonomy( 'type_taxonomy', [
				'show_admin_column'  => TRUE,
				'show_in_quick_edit' => TRUE,
			], FALSE, [
				'target_object' => 'user',
				'custom_icon'   => 'screenoptions',
			] );

		if ( $this->get_setting( 'author_categories' ) )
			$this->filter( 'pre_option_default_category', 3 );
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'user_groups' ) )
			$this->_hook_menu_taxonomy( 'group_taxonomy', 'users.php' );

		if ( $this->get_setting( 'user_types' ) )
			$this->_hook_menu_taxonomy( 'type_taxonomy', 'users.php' );
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

			$this->action_module( 'tweaks', 'column_user', 3, 12 );

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

			$this->corerestrictposts__hook_screen_authors();

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

			} else if ( $screen->taxonomy == $this->constant( 'group_taxonomy' ) ) {

				$this->_hook_parentfile_for_usersphp();
				$this->modulelinks__register_headerbuttons();

				if ( 'edit-tags' == $screen->base ) {
					add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', [ $this, 'manage_columns_groups' ] );
					add_action( 'manage_'.$screen->taxonomy.'_custom_column', [ $this, 'custom_column_groups' ], 10, 3 );
				}

			} else if ( $screen->taxonomy == $this->constant( 'type_taxonomy' ) ) {

				$this->_hook_parentfile_for_usersphp();
				$this->modulelinks__register_headerbuttons();

				if ( 'edit-tags' == $screen->base ) {
					add_filter( 'manage_edit-'.$screen->taxonomy.'_columns', [ $this, 'manage_columns_types' ] );
					add_action( 'manage_'.$screen->taxonomy.'_custom_column', [ $this, 'custom_column_types' ], 10, 3 );
				}
			}
		}
	}

	public function sanitize_user( $username )
	{
		if ( $username == $this->constant( 'group_taxonomy_slug' ) )
			$username = '';

		else if ( $username == $this->constant( 'type_taxonomy_slug' ) )
			$username = '';

		return $username;
	}

	public function dashboard_widgets()
	{
		$this->add_dashboard_widget( 'profile-summary', NULL, [
			'url'   => admin_url( 'profile.php' ),
			'title' => $this->get_string( 'action_attr', NULL, 'dashboard', '' ),
			'link'  => $this->get_string( 'action_link', NULL, 'dashboard', '' ),
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

	public function manage_users_custom_column( $output, $column, $user_id )
	{
		if ( $this->classs( 'counts' ) != $column )
			return $output;

		if ( $this->check_hidden_column( $column ) )
			return $output;

		if ( empty( $this->cache['posttypes'] ) )
		$this->cache['posttypes'] = WordPress\PostType::get( 1, [ 'show_ui' => TRUE ] );

		$counts = WordPress\Database::countPostsByUser( $user_id );
		$list   = [];

		foreach ( $this->cache['posttypes'] as $posttype => $label )
			if ( ! empty( $counts[$posttype] ) )
				$list[$label] = Core\HTML::tag( 'a', [
					'href'   => WordPress\PostType::edit( $posttype, [ 'author' => $user_id ] ),
					'target' => '_blank',
				], Core\Number::format( $counts[$posttype] ) );

		ob_start();

		if ( count( $list ) )
			echo Core\HTML::tableCode( $list );
		else
			echo gEditorial\Listtable::columnCount( 0 );

		return ob_get_clean();
	}

	// FIXME: use `gEditorial\Helper::renderUserTermsEditRow()`
	public function tweaks_column_user( $user, $before, $after )
	{
		if ( $this->get_setting( 'user_groups', FALSE ) ) {

			if ( $terms = WordPress\Taxonomy::getTerms( $this->constant( 'group_taxonomy' ), $user->ID, TRUE, 'term_id', [], FALSE ) ) {

				foreach ( $terms as $term ) {

					printf( $before, '-user-group' );
						echo $this->get_column_icon( FALSE, 'networking', _x( 'Group', 'Row Icon Title', 'geditorial-users' ) );
						echo WordPress\Term::title( $term );
					echo $after;
				}
			}
		}

		if ( $this->get_setting( 'user_types', FALSE ) ) {

			if ( $terms = WordPress\Taxonomy::getTerms( $this->constant( 'type_taxonomy' ), $user->ID, TRUE, 'term_id', [], FALSE ) ) {

				foreach ( $terms as $term ) {

					printf( $before, '-user-type' );
						echo $this->get_column_icon( FALSE, 'networking', _x( 'Type', 'Row Icon Title', 'geditorial-users' ) );
						echo WordPress\Term::title( $term );
					echo $after;
				}
			}
		}
	}

	public function manage_columns_groups( $columns )
	{
		unset( $columns['posts'] );
		return array_merge( $columns, [ 'users' => $this->get_column_title( 'users', 'group_taxonomy' ) ] );
	}

	public function custom_column_groups( $display, $column, $term_id )
	{
		if ( 'users' !== $column )
			return;

		if ( $this->check_hidden_column( $column ) )
			return;

		echo gEditorial\Listtable::columnCount( get_term( $term_id, $this->constant( 'group_taxonomy' ) )->count );
	}

	public function manage_columns_types( $columns )
	{
		unset( $columns['posts'] );
		return array_merge( $columns, [ 'users' => $this->get_column_title( 'users', 'type_taxonomy' ) ] );
	}

	public function custom_column_types( $display, $column, $term_id )
	{
		if ( 'users' !== $column )
			return;

		if ( $this->check_hidden_column( $column ) )
			return;

		echo gEditorial\Listtable::columnCount( get_term( $term_id, $this->constant( 'type_taxonomy' ) )->count );
	}

	public function edit_user_profile( $user )
	{
		if ( $this->get_setting( 'user_groups' ) )
			gEditorial\MetaBox::tableRowObjectTaxonomy(
				$this->constant( 'group_taxonomy' ),
				$user->ID,
				$this->classs( 'group-taxonomy' ),
				NULL,
				'<table class="form-table">',
				'</table>'
			);

		if ( $this->get_setting( 'user_types' ) )
			gEditorial\MetaBox::tableRowObjectTaxonomy(
				$this->constant( 'type_taxonomy' ),
				$user->ID,
				$this->classs( 'type-taxonomy' ),
				NULL,
				'<table class="form-table">',
				'</table>'
			);

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

		Core\HTML::h3( _x( 'Site Categories', 'Header', 'geditorial-users' ) );
		Core\HTML::desc( _x( 'Restrict non editor users to post in selected categories only.', 'Message', 'geditorial-users' ) );

		echo '<table class="form-table">';
			echo '<tr><th scope="row">'._x( 'User Categories', 'Header', 'geditorial-users' ).'</th><td>';

			if ( ! empty( $terms ) ) {

				echo gEditorial\Settings::tabPanelOpen();

				foreach ( $terms as $term ) {

					if ( $default == $term->term_id )
						continue;

					$html = Core\HTML::tag( 'input', [
						'type'    => 'checkbox',
						'name'    => 'categories[]',
						'id'      => 'categories-'.$term->slug,
						'value'   => $term->term_id,
						'checked' => in_array( $term->term_id, $selected ),
					] );

					Core\HTML::label( $html.'&nbsp;'.Core\HTML::escape( $term->name ), 'categories-'.$term->slug, 'li' );
				 }

				echo '</ul></div>';

				// Passing empty value for clearing up
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
			$blog_id = $this->site;

		$key = sprintf( $this->constant( 'metakey_categories' ), $blog_id );

		if ( $cats = get_user_meta( $user_id, $key, TRUE ) )
			return (array) $cats;

		return $fallback ? [ get_option( 'default_category' ) ] : [];
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( ! current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		if ( $this->get_setting( 'user_groups' ) )
			gEditorial\MetaBox::storeObjectTaxonomy(
				$this->constant( 'group_taxonomy' ),
				$user_id,
				self::req( $this->classs( 'group-taxonomy' ), [] )
			);

		if ( $this->get_setting( 'user_types' ) )
			gEditorial\MetaBox::storeObjectTaxonomy(
				$this->constant( 'type_taxonomy' ),
				$user_id,
				self::req( $this->classs( 'type-taxonomy' ), [] )
			);

		if ( $this->get_setting( 'author_categories' )
			&& isset( $_POST['categories'] ) ) {

			$key = sprintf( $this->constant( 'metakey_categories' ), $this->site );
			update_user_meta( $user_id, $key, array_filter( $_POST['categories'] ) );
		}
	}

	public function pre_option_default_category( $false, $option, $default )
	{
		if ( current_user_can( 'edit_posts' )
			&& ! current_user_can( 'edit_others_posts' ) ) {

			$selected = $this->get_user_categories( NULL, NULL, FALSE );

			// Only if user has one cat, otherwise fallback to default.
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
			$terms[] = WordPress\Term::get( $selected, 'category' );

		echo $this->wrap_open( '-admin-metabox' );

			gEditorial\MetaBox::checklistTerms( $post->ID, [
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

		$user   = wp_get_current_user();
		$after  = '</li>';
		$before = $this->wrap_open_row( 'row', [
			'-profile-row',
			'%s', // to use by caller
		] );

		echo '<div class="geditorial-wrap -admin-widget -users -contacts">';

		echo Core\HTML::wrap( get_avatar( $user->user_email, 125 ), '-avatar' );

		echo '<ul class="-rows">';

		if ( $user->first_name || $user->last_name ) {
			printf( $before, '-fullname' );
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Row Icon Title', 'geditorial-users' ) );
				echo "$user->first_name $user->last_name";
			echo $after;
		}

		if ( $user->user_email ) {
			printf( $before, '-email' );
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Row Icon Title', 'geditorial-users' ) );
				echo Core\HTML::mailto( $user->user_email );
			echo $after;
		}

		if ( $user->user_url ) {
			printf( $before, '-url' );
				echo $this->get_column_icon( FALSE, 'admin-links', _x( 'URL', 'Row Icon Title', 'geditorial-users' ) );
				echo Core\HTML::link( Core\URL::prepTitle( $user->user_url ), $user->user_url );
			echo $after;
		}

		foreach ( wp_get_user_contact_methods( $user ) as $method => $title ) {

			if ( ! $value = get_user_meta( $user->ID, $method, TRUE ) )
				continue;

			printf( $before, '-contact -contact-'.$method );
				echo $this->get_column_icon( FALSE, Core\Icon::guess( $method, 'email-alt' ), $title );
				echo $this->prep_meta_row( $value, $method, [ 'type' => 'contact_method', 'title' => $title ], $value );
			echo $after;
		}

		if ( $user->user_registered ) {
			printf( $before, '-registered' );
				echo $this->get_column_icon( FALSE, 'calendar', _x( 'Registered', 'Row Icon Title', 'geditorial-users' ) );
				printf(
					/* translators: `%s`: date */
					_x( 'Registered on %s', 'Row', 'geditorial-users' ),
					gEditorial\Helper::getDateEditRow( $user->user_registered, '-registered' )
				);
			echo $after;
		}

		$role = $this->get_column_icon( FALSE, 'businessman', _x( 'Roles', 'Row Icon Title', 'geditorial-users' ) );
		echo WordPress\Strings::getJoined( WordPress\Role::get( 0, [], $user ), sprintf( $before, '-roles' ).$role, $after );

		$this->tweaks_column_user( $user, $before, $after );

		echo '</ul><div class="clear"></div></div>';
	}

	public function tools_settings( $sub )
	{
		global $wpdb;

		if ( $this->check_settings( $sub, 'tools' ) ) {
			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( gEditorial\Tablelist::isAction( 'duplicate_role' ) ) {

					if ( ! $from = self::req( 'role_from' ) )
						WordPress\Redirect::doReferer( 'huh' );

					if ( ! $caps = WordPress\Role::capabilities( $from, FALSE ) )
						WordPress\Redirect::doReferer( 'wrong' );

					$name  = self::req( 'role_name' ) ?: sprintf( '%s_duplicated', $from );
					$title = self::req( 'role_title' ) ?: Core\Text::readableKey( $name );

					if ( ! add_role( WordPress\Role::sanitize( $name ), $title, $caps ) )
						WordPress\Redirect::doReferer( 'wrong' );

					WordPress\Redirect::doReferer( 'added' );

				} else if ( gEditorial\Tablelist::isAction( 'delete_role' ) ) {

					if ( ! $delete = self::req( 'role_delete' ) )
						WordPress\Redirect::doReferer( 'huh' );

					if ( in_array( $delete, [ 'administrator', 'subscriber' ], TRUE ) )
						WordPress\Redirect::doReferer( 'noaccess' );

					if ( ! WordPress\Role::object( $delete ) )
						WordPress\Redirect::doReferer( 'wrong' );

					remove_role( $delete );
					WordPress\Redirect::doReferer( 'removed' );

				} else if ( gEditorial\Tablelist::isAction( 'remap_post_authors' ) ) {

					if ( ! $file = WordPress\Media::handleImportUpload() )
						WordPress\Redirect::doReferer( 'wrong' );

					$count = 0;
					$role  = get_option( 'default_role' );

					$users    = WordPress\User::get( TRUE, TRUE, [], 'user_email' );
					$currents = $wpdb->get_results( "SELECT post_author as user, GROUP_CONCAT( ID ) as posts FROM {$wpdb->posts} GROUP BY post_author", ARRAY_A );

					// FIXME: use `Parser::fromCSV_Legacy()`
					$iterator = new \SplFileObject( Core\File::normalize( $file['file'] ) );
					$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8', 'limit' => 1 ] );
					$header   = $parser->parse();
					$parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8', 'offset' => 1, 'header' => $header[0] ] );
					$old_map  = Core\Arraay::reKey( $parser->parse(), 'ID' );

					foreach ( $currents as $current ) {

						if ( isset( $old_map[$current['user']] )
							&& isset( $users[$old_map[$current['user']]['user_email']] ) ) {

							$user  = $users[$old_map[$current['user']]['user_email']];
							$query = $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author = %d WHERE ID IN ( ".trim( $current['posts'], ',' )." )", $user->ID );
							$count+= $wpdb->query( $query );

							if ( ! is_user_member_of_blog( $user->ID, $this->site ) )
								add_user_to_blog( $this->site, $user->ID, $role );
						}
					}

					WordPress\Redirect::doReferer( [
						'message'    => 'synced',
						'count'      => $count,
						'attachment' => $file['id'],
					] );

				} else {

					WordPress\Redirect::doReferer( 'huh' );
				}
			}
		}
	}

	// FIXME: move to `Config`: `render_roles_html()`
	// TODO: export/import/override roles via JSON list of caps // MAYBE: new Module
	protected function render_tools_html( $uri, $sub )
	{
		$roles = WordPress\Role::get();
		$none  = gEditorial\Settings::showOptionNone();

		echo '<table class="form-table">';
		echo '<tr><th scope="row">'._x( 'Duplicate Current Roles', 'Header', 'geditorial-users' ).'</th><td>';

		$this->do_settings_field( [
			'type'      => 'select',
			'field'     => 'role_from',
			'name_attr' => 'role_from',
			'values'    => $roles,
			'none_value' => '0',
			'none_title' => $none,
		] );

		gEditorial\Settings::fieldSeparate( 'to' );

		$this->do_settings_field( [
			'type'        => 'text',
			'field'       => 'role_name',
			'name_attr'   => 'role_name',
			'field_class' => 'medium-text',
			'title_attr'  => _x( 'The new role name in lowercase alphanumeric with underlines.', 'TitleAttr', 'geditorial-users' ),
			'placeholder' => 'role_name',
			'dir'         => 'ltr',
		] );

		gEditorial\Settings::fieldSeparate( 'as' );

		$this->do_settings_field( [
			'type'        => 'text',
			'field'       => 'role_title',
			'name_attr'   => 'role_title',
			'field_class' => 'regular-text',
			'placeholder' => _x( 'Role Title', 'PlaceHolder', 'geditorial-users' ),
			'title_attr'  => _x( 'The new role title in the localized term.', 'TitleAttr', 'geditorial-users' ),
		] );

		echo $this->wrap_open_buttons();
			gEditorial\Settings::submitButton( 'duplicate_role', _x( 'Duplicate Role', 'Button', 'geditorial-users' ), FALSE );
			Core\HTML::desc( _x( 'Tries to make a duplicate from existing roles with given name and title.', 'Message', 'geditorial-users' ), FALSE );

		echo '</p></td></tr>';
		echo '<tr><th scope="row">'._x( 'Delete Current Roles', 'Header', 'geditorial-users' ).'</th><td>';

		$this->do_settings_field( [
			'type'       => 'select',
			'field'      => 'role_delete',
			'name_attr'  => 'role_delete',
			'values'     => Core\Arraay::stripByKeys( $roles, [ 'administrator', 'subscriber' ] ),
			'none_value' => '0',
			'none_title' => $none,
		] );

		echo $this->wrap_open_buttons();
		gEditorial\Settings::submitButton( 'delete_role', _x( 'Delete Role', 'Button', 'geditorial-users' ), 'danger', TRUE );
		Core\HTML::desc( _x( 'Tries to wipe the selected existing role.', 'Message', 'geditorial-users' ), FALSE );

		echo '</p></td></tr>';
		echo '<tr><th scope="row">'._x( 'Re-Map Authors', 'Header', 'geditorial-users' ).'</th><td>';

		if ( $filesize = $this->settings_render_upload_field( '.csv' ) ) {
			echo $this->wrap_open_buttons();
				gEditorial\Settings::submitButton( 'remap_post_authors', _x( 'Upload and Re-Map', 'Button', 'geditorial-users' ), 'danger' );

				Core\HTML::desc( sprintf(
					/* translators: `%1$s`: file ext-type, `%2$s`: file size */
					_x( 'Checks for post authors via a %1$s file and re-map them with current registered users. Maximum upload size: %2$s', 'Message', 'geditorial-users' ),
					Core\HTML::code( 'csv' ),
					Core\HTML::code( Core\HTML::wrapLTR( $filesize ) )
				), FALSE );
			echo '</p>';
		}

		echo '</td></tr>';
		echo '</table>';
	}

	// FIXME: move to `Config`: `roles_overview()` and display list of caps for each
	public function tools_sidebox( $sub, $uri, $context )
	{
		echo Core\HTML::tableCode(
			WordPress\Role::get(),
			TRUE,
			_x( 'Available Roles', 'Caption', 'geditorial-users' )
		);
	}

	// FIXME: DRAFT : need styling / register the short-code!
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

					echo '<h2 class="user-title">'.Core\HTML::tag( 'a', [
						'href'  => get_author_posts_url( $user_id ),
						'title' => '',
					], get_the_author_meta( 'display_name', $user_id ) ).'</h2>';

					echo '<div class="description">'.wpautop( get_the_author_meta( 'description', $user_id ) ).'</div>';

				echo '</div>';
			}
		}
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialContest extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'  => 'contest',
			'title' => _x( 'Contest', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Contest Management', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'megaphone',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'multiple_instances',
				'admin_ordering',
				'admin_restrict',
				'redirect_archives',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'contest_cpt'         => 'contest',
			'contest_cpt_archive' => 'contests',
			'apply_cpt'           => 'apply',
			'apply_cpt_archive'   => 'applies',
			'contest_cat'         => 'contest_cat',
			'contest_tax'         => 'contests',
			'apply_cat'           => 'apply_cat',
			'apply_status_tax'    => 'apply_status',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'contest_cpt' => array(
					'meta_box_title'        => _x( 'Metadata', 'Contest Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_box_title'       => _x( 'Poster', 'Contest Module: CoverBox Title', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title'    => _x( 'Poster', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title'    => _x( 'O', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'children_column_title' => _x( 'Applies', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'contest_cat' => array(
					'tweaks_column_title' => _x( 'Contest Categories', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'apply_cat' => array(
					'tweaks_column_title' => _x( 'Apply Categories', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'apply_status_tax' => array(
					'meta_box_title'      => _x( 'Apply Statuses', 'Contest Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
					'tweaks_column_title' => _x( 'Apply Statuses', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'meta_box_title'      => _x( 'Contests', 'Contest Module: MetaBox Title', GEDITORIAL_TEXTDOMAIN ),
				'tweaks_column_title' => _x( 'Contests', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings' => array(
				'install_def_apply_status_tax' => _x( 'Install Default Apply Statuses', 'Contest Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
				'contest_tax_check'            => _x( 'Check Terms', 'Contest Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
				'contest_post_create'          => _x( 'Create Contest Posts', 'Contest Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
				'contest_post_connect'         => _x( 'Re-Connect Posts', 'Contest Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'contest_cpt'      => _nx_noop( 'Contest', 'Contests', 'Contest Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'contest_tax'      => _nx_noop( 'Contest', 'Contests', 'Contest Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'contest_cat'      => _nx_noop( 'Contest Category', 'Contest Categories', 'Contest Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'apply_cpt'        => _nx_noop( 'Apply', 'Applies', 'Contest Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'apply_cat'        => _nx_noop( 'Apply Category', 'Apply Categories', 'Contest Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'apply_status_tax' => _nx_noop( 'Apply Status', 'Apply Statuses', 'Contest Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
			'terms' => array(
				'apply_status_tax' => array(
					'approved' => _x( 'Approved', 'Contest Module: Apply Statuses Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'pending'  => _x( 'Pending', 'Contest Module: Apply Statuses Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'contest_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'comments',
				'revisions',
				'page-attributes',
				'date-picker', // gPersianDate
			),
			'apply_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'comments',
				'revisions',
				'page-attributes',
				'date-picker', // gPersianDate
			),
		);
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'contest_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_contest_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( 'attachment', $this->constant( 'contest_cpt' ) );

		$this->register_post_type( 'contest_cpt', array(
			'hierarchical'  => TRUE,
		), array( 'post_tag' ) );

		$this->register_post_type( 'apply_cpt', array(
			'menu_icon' => 'dashicons-portfolio',
		), array( 'post_tag' ) );

		$this->register_taxonomy( 'contest_cat', array(
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'contest_cpt' );

		$this->register_taxonomy( 'contest_tax', array(
			'show_ui'            => FALSE,
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		) );

		$this->register_taxonomy( 'apply_cat', array(
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'apply_cpt' );

		$this->register_taxonomy( 'apply_status_tax', array(
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'apply_cpt' );

		if ( ! is_admin() ) {
			add_filter( 'term_link', array( $this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'contest_cpt' ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'contest_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 9, 2 );
				add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

				add_filter( 'geditorial_meta_box_callback', '__return_false', 12 );

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				add_meta_box( 'geditorial-contest-main',
					$this->get_meta_box_title( 'contest_cpt', FALSE ),
					array( $this, 'do_meta_box_main' ),
					$screen->post_type,
					'side',
					'high'
				);

				add_meta_box( 'geditorial-contest-list',
					$this->get_meta_box_title( 'contest_tax', $this->get_url_post_edit( 'post_cpt' ), 'edit_others_posts' ),
					array( $this, 'do_meta_box_list' ),
					$screen->post_type,
					'advanced',
					'low'
				);

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_ordering', TRUE ) )
					add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

				$this->_edit_screen( $screen->post_type );
				add_filter( 'manage_edit-'.$screen->post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );

				add_action( 'geditorial_tweaks_column_attr', array( $this, 'main_column_attr' ) );
			}

			add_action( 'save_post', array( $this, 'save_post_main_cpt' ), 20, 3 );
			add_action( 'post_updated', array( $this, 'post_updated' ), 20, 3 );

			add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
			add_action( 'untrash_post', array( $this, 'untrash_post' ) );
			add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );

		} else if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				$this->remove_meta_box( $screen->post_type, $screen->post_type, 'parent' );
				add_meta_box( 'geditorial-contest-supported',
					$this->get_meta_box_title( $screen->post_type, $this->get_url_post_edit( 'contest_cpt' ), 'edit_others_posts' ),
					array( $this, 'do_meta_box_supported' ),
					$screen->post_type,
					'side'
				);

				// internal actions:
				add_action( 'geditorial_contest_supported_meta_box', array( $this, 'supported_meta_box' ), 5, 2 );

				// TODO: add a thick-box to list the posts with this issue taxonomy

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts_supported_cpt' ), 12, 2 );
			}

			add_action( 'save_post', array( $this, 'save_post_supported_cpt' ), 20, 3 );
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_filter( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_apply_status_tax'] ) )
			$this->insert_default_terms( 'apply_status_tax' );

		parent::register_settings( $page );
		$this->register_button( 'install_def_apply_status_tax' );
	}

	public function meta_post_types( $post_types )
	{
		return array_merge( $post_types, array(
			$this->constant( 'contest_cpt' ),
			$this->constant( 'apply_cpt' ),
		) );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array(
			$this->constant( 'contest_cpt' ),
			$this->constant( 'apply_cpt' ),
		) );
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'contest_cat' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'contest_cat' ),
					'icon'   => 'category',
					'title'  => $this->get_column_title( 'tweaks', 'contest_cat' ),
				),
				$this->constant( 'contest_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'contest_tax' ),
					'icon'   => 'megaphone',
					'title'  => $this->get_column_title( 'tweaks', 'contest_tax' ),
				),
				$this->constant( 'apply_cat' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'apply_cat' ),
					'icon'   => 'category',
					'title'  => $this->get_column_title( 'tweaks', 'apply_cat' ),
				),
				$this->constant( 'apply_status_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'apply_status_tax' ),
					'icon'   => 'portfolio',
					'title'  => $this->get_column_title( 'tweaks', 'apply_status_tax' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $contests = $this->dashboard_glance_post( 'contest_cpt' ) )
			$items[] = $contests;

		if ( $applies = $this->dashboard_glance_post( 'apply_cpt' ) )
			$items[] = $applies;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'contest_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->get_linked_post_id( $term, 'contest_cpt', 'contest_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'contest_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->get_linked_post_id( $term, 'contest_cpt', 'contest_tax' ) )
				gEditorialWordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'contest_cpt' ) )
			|| is_post_type_archive( $this->constant( 'apply_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				gEditorialWordPress::redirect( $redirect, 301 );
		}
	}

	public function do_meta_box_main( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -contest">';

		// OLD ACTION: 'geditorial_the_contest_meta_box'
		do_action( 'geditorial_contest_main_meta_box', $post );

		do_action( 'geditorial_meta_do_meta_box', $post, $box, NULL );

		$this->field_post_order( 'contest_cpt', $post );

		if ( get_post_type_object( $this->constant( 'contest_cpt' ) )->hierarchical )
			$this->field_post_parent( 'contest_cpt', $post );

		echo '</div>';
	}

	public function do_meta_box_list( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -contest">';

		do_action( 'geditorial_contest_list_meta_box', $post, $box );

		// TODO: add collapsible button
		if ( $term = $this->get_linked_term( $post->ID, 'contest_cpt', 'contest_tax' ) )
			echo gEditorialHelper::getTermPosts( $this->constant( 'contest_tax' ), $term );

		echo '</div>';
	}

	public function do_meta_box_supported( $post, $box )
	{
		echo '<div class="geditorial-admin-wrap-metabox -contest">';

		$terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'contest_tax' ), $post->ID, TRUE );

		// OLD ACTION: 'geditorial_contest_meta_box'
		do_action( 'geditorial_contest_supported_meta_box', $post, $terms );

		echo '</div>';
	}

	public function supported_meta_box( $post, $terms )
	{
		$this->field_post_order( 'apply_cpt', $post );

		$dropdowns = $excludes = array();

		foreach ( $terms as $term ) {

			$dropdowns[$term->slug] = wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'contest_cpt' ),
				'selected'         => $term->slug,
				'name'             => 'geditorial-contest-contest[]',
				'id'               => 'geditorial-contest-contest-'.$term->slug,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => gEditorialSettingsCore::showOptionNone(),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

			$excludes[] = $term->slug;
		}

		if ( ! count( $terms ) || $this->get_setting( 'multiple_instances', FALSE ) ) {
			$dropdowns[0] = wp_dropdown_pages( array(
				'post_type'        => $this->constant( 'contest_cpt' ),
				'selected'         => '',
				'name'             => 'geditorial-contest-contest[]',
				'id'               => 'geditorial-contest-contest-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => gEditorialSettingsCore::showOptionNone(),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'exclude'          => $excludes,
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));
		}

		foreach ( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo '<div class="field-wrap">';
					echo $dropdown;
				echo '</div>';
			}
		}
	}

	public function meta_box_cb_apply_status_tax( $post, $box )
	{
		gEditorialMetaBox::checklistTerms( $post, $box );
	}

	public function post_updated_messages( $messages )
	{
		if ( $this->is_current_posttype( 'contest_cpt' ) )
			$messages[$this->constant( 'contest_cpt' )] = $this->get_post_updated_messages( 'contest_cpt' );

		else if ( $this->is_current_posttype( 'apply_cpt' ) )
			$messages[$this->constant( 'apply_cpt' )] = $this->get_post_updated_messages( 'apply_cpt' );

		return $messages;
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->constant( 'contest_cpt' ) == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = gEditorialWordPress::getLastPostOrder( $this->constant( 'contest_cpt' ),
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'contest_tax' ) )
			return $post_ID;

		if ( 'trash' == $post_after->post_status )
			return $post_ID;

		if ( empty( $post_before->post_name ) )
			$post_before->post_name = sanitize_title( $post_before->post_title );

		if ( empty( $post_after->post_name ) )
			$post_after->post_name = sanitize_title( $post_after->post_title );

		$args = array(
			'name'        => $post_after->post_title,
			'slug'        => $post_after->post_name,
			'description' => $post_after->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'contest_tax' ) );

		if ( FALSE === $the_term ){
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'contest_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'contest_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'contest_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'contest_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'contest_cpt', 'contest_tax' );

		return $post_ID;
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( $update )
			return $post_ID;

		if ( ! $this->is_save_post( $post ) )
			return $post_ID;

		if ( empty( $post->post_name ) )
			$post->post_name = sanitize_title( $post->post_title );

		$args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$term = wp_insert_term( $post->post_title, $this->constant( 'contest_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_ID, $term['term_id'], 'contest_cpt', 'contest_tax' );

		return $post_ID;
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		if ( isset( $_POST['geditorial-contest-contest'] ) ) {
			$terms = array();

			foreach ( $_POST['geditorial-contest-contest'] as $contest ) {
				if ( trim( $contest ) ) {
					$term = get_term_by( 'slug', $contest, $this->constant( 'contest_tax' ) );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$terms[] = intval( $term->term_id );
				}
			}

			wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $this->constant( 'contest_tax' ), FALSE );
		}

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'contest_cpt', 'contest_tax' ) ) {
			wp_update_term( $term->term_id, $this->constant( 'contest_tax' ), array(
				'name' => $term->name.' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ),
				'slug' => $term->slug.'-trashed',
			) );
		}
	}

	public function untrash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'contest_cpt', 'contest_tax' ) ) {
			wp_update_term( $term->term_id, $this->constant( 'contest_tax' ), array(
				'name' => str_ireplace( ' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ), '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			) );
		}
	}

	public function before_delete_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'contest_cpt', 'contest_tax' ) ) {
			wp_delete_term( $term->term_id, $this->constant( 'contest_tax' ) );
			delete_metadata( 'term', $term->term_id, $this->constant( 'contest_cpt' ).'_linked' );
		}
	}

	public function restrict_manage_posts_supported_cpt( $post_type, $which )
	{
		$contest_tax = $this->constant( 'contest_tax' );
		$tax_obj   = get_taxonomy( $contest_tax );

		wp_dropdown_pages( array(
			'post_type'        => $this->constant( 'contest_cpt' ),
			'selected'         => isset( $_GET[$contest_tax] ) ? $_GET[$contest_tax] : '',
			'name'             => $contest_tax,
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => $tax_obj->labels->all_items,
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => 'publish,private,draft',
			'value_field'      => 'post_name',
			'walker'           => new gEditorial_Walker_PageDropdown(),
		));
	}

	public function pre_get_posts( $wp_query )
	{
		if ( $wp_query->is_admin
			&& isset( $wp_query->query['post_type'] ) ) {

			if ( $this->constant( 'contest_cpt' ) == $wp_query->query['post_type'] ) {

				if ( ! isset( $_GET['orderby'] ) )
					$wp_query->set( 'orderby', 'menu_order' );

				if ( ! isset( $_GET['order'] ) )
					$wp_query->set( 'order', 'DESC' );
			}
		}
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'title' ) {
				$new_columns['order'] = $this->get_column_title( 'order', 'contest_cpt' );
				$new_columns['cover'] = $this->get_column_title( 'cover', 'contest_cpt' );
				$new_columns[$key] = $value;

			} else if ( 'date' == $key ){
				$new_columns['children'] = $this->get_column_title( 'children', 'contest_cpt' );

			} else if ( in_array( $key, array( 'author', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'children' == $column_name )
			$this->column_count( $this->get_linked_posts( $post_id, 'contest_cpt', 'contest_tax', TRUE ) );

		else if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id, $this->get_image_size_key( 'contest_cpt' ) );
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, array( 'order' => 'menu_order' ) );
	}

	public function main_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'contest_cpt', 'contest_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-attr -magazine -children">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'children', 'contest_cpt' ) );

			$post_types = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = array(
				$this->constant( 'contest_tax' ) => $post->post_name,
			);

			if ( empty( $this->all_post_types ) )
				$this->all_post_types = gEditorialWPPostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = array();

			foreach ( $post_types as $post_type )
				$list[] = gEditorialHTML::tag( 'a', array(
					'href'   => gEditorialWordPress::getPostTypeEditLink( $post_type, 0, $args ),
					'title'  => _x( 'View the connected list', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				), $this->all_post_types[$post_type] );

			echo gEditorialHelper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	public function tools_sub( $uri, $sub )
	{
		echo '<form class="settings-form" method="post" action="">';

			echo '<h3>'._x( 'Contest Tools', 'Contest Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'From Terms', 'Contest Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['contest_tax_check'] ) ) {

				gEditorialHTML::tableList( array(
					'_cb'     => 'term_id',
					'term_id' => _x( 'ID', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
					'name'    => _x( 'Name', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
					'issue'   => array(
						'title' => _x( 'Contest', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){
							if ( $post_id = gEditorialWPPostType::getIDbySlug( $row->slug, $this->constant( 'contest_cpt' ) ) )
								return $post_id.' &mdash; '.get_post($post_id)->post_title;
							return _x( '&mdash;&mdash;&mdash;&mdash; No Contest', 'Contest Module', GEDITORIAL_TEXTDOMAIN );
						},
					),
					'count' => array(
						'title'    => _x( 'Count', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column, $index ){
							if ( $post_id = gEditorialWPPostType::getIDbySlug( $row->slug, $this->constant( 'contest_cpt' ) ) )
								return number_format_i18n( $this->get_linked_posts( $post_id, 'contest_cpt', 'contest_tax', TRUE ) );
							return number_format_i18n( $row->count );
						},
					),
					'description' => array(
						'title'    => _x( 'Description', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
						'callback' => 'wpautop',
						'class'    => 'description',
					),
				), gEditorialWPTaxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE ) );

				echo '<br />';
			}

			$this->submit_button( 'contest_tax_check', TRUE );
			$this->submit_button( 'contest_post_create' );
			$this->submit_button( 'contest_post_connect' );

			echo gEditorialHTML::tag( 'p', array(
				'class' => 'description',
			), _x( 'Check for contest terms and create corresponding contest posts.', 'Contest Module', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';

			$this->settings_field_referer( $sub, 'tools' );

		echo '</form>';
	}

	public function tools_settings( $sub )
	{
		if ( ! $this->cuc( 'tools' ) )
			return;

		if ( $this->module->name == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'tools' );

				if ( isset( $_POST['_cb'] )
					&& isset( $_POST['contest_post_create'] ) ) {

					$terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE );
					$posts = array();

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = gEditorialWPPostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'contest_cpt' ) ) ;

						if ( FALSE !== $post_id )
							continue;

						$posts[] = gEditorialWordPress::newPostFromTerm(
							$terms[$term_id],
							$this->constant( 'contest_tax' ),
							$this->constant( 'contest_cpt' ),
							gEditorialHelper::getEditorialUserID()
						);
					}

					gEditorialWordPress::redirectReferer( array(
						'message' => 'created',
						'count'   => count( $posts ),
					) );

				} else if ( isset( $_POST['_cb'] )
					&& isset( $_POST['contest_post_connect'] ) ) {

					$terms = gEditorialWPTaxonomy::getTerms( $this->constant( 'contest_tax' ), FALSE, TRUE );
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {

						if ( ! isset( $terms[$term_id] ) )
							continue;

						$post_id = gEditorialWPPostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( 'contest_cpt' ) ) ;

						if ( FALSE === $post_id )
							continue;

						if ( $this->set_linked_term( $post_id, $terms[$term_id], 'contest_cpt', 'contest_tax' ) )
							$count++;
					}

					gEditorialWordPress::redirectReferer( array(
						'message' => 'updated',
						'count'   => $count,
					) );
				}
			}

			add_action( 'geditorial_tools_sub_'.$this->module->name, array( $this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_tools_subs', array( $this, 'append_sub' ), 10, 2 );
	}
}

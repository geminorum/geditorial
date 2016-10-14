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
					'meta_box_title'  => _x( 'Metadata', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
					'cover_box_title' => _x( 'Poster', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),

					'cover_column_title'    => _x( 'Poster', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title'    => _x( 'O', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
					'children_column_title' => _x( 'Applies', 'Contest Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
				'apply_status_tax' => array(
					'meta_box_title' => _x( 'Apply Statuses', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
				),
				'meta_box_title' => _x( 'Contests', 'Contest Module', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings' => array(
				'install_def_apply_status_tax' => _x( 'Install Default Apply Statuses', 'Contest Module: Setting Button', GEDITORIAL_TEXTDOMAIN ),
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
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
			'apply_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				// 'trackbacks',
				// 'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
		);
	}

	public function setup()
	{
		parent::setup();

		if ( is_admin() ) {

			if ( $this->get_setting( 'admin_ordering', TRUE ) )
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

		} else {
			add_filter( 'term_link', array( $this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		}

		add_action( 'split_shared_term', array( $this, 'split_shared_term' ), 10, 4 );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'contest_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_contest_init', $this->module );

		$this->do_globals();

		$this->post_types_excluded = array( $this->constant( 'contest_cpt' ) );

		$this->register_post_type( 'contest_cpt', array(
			'hierarchical'  => TRUE,
		), array( 'post_tag' ) );

		$this->register_post_type( 'apply_cpt', array(
			'menu_icon' => 'dashicons-portfolio',
		), array( 'post_tag' ) );

		$this->register_taxonomy( 'contest_cat', array(
			'show_admin_column' => TRUE,
			'hierarchical'      => TRUE,
		), 'contest_cpt' );

		$this->register_taxonomy( 'contest_tax', array(
			'show_ui'           => self::isDev(),
			'hierarchical'      => TRUE,
			'show_admin_column' => TRUE,
		) );

		$this->register_taxonomy( 'apply_cat', array(
			'show_admin_column' => TRUE,
			'hierarchical'      => TRUE,
		), 'apply_cpt' );

		$this->register_taxonomy( 'apply_status_tax', array(
			'show_admin_column' => TRUE,
			'hierarchical'      => TRUE,
		), 'apply_cpt' );
	}

	public function admin_init()
	{
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 9, 2 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );
		add_action( 'save_post_'.$this->constant( 'contest_cpt' ), array( $this, 'save_post_main_cpt' ), 20, 3 );
		add_action( 'post_updated', array( $this, 'post_updated' ), 20, 3 );
		add_action( 'save_post', array( $this, 'save_post_supported_cpt' ), 20, 3 );

		add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );
		add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );

		add_filter( 'manage_'.$this->constant( 'contest_cpt' ).'_posts_columns', array( $this, 'manage_posts_columns' ) );
		add_filter( 'manage_'.$this->constant( 'contest_cpt' ).'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
		add_filter( 'manage_edit-'.$this->constant( 'contest_cpt' ).'_sortable_columns', array( $this, 'sortable_columns' ) );

		// internal actions:
		add_action( 'geditorial_contest_supported_meta_box', array( $this, 'supported_meta_box' ), 5, 2 );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_apply_status_tax'] ) )
			$this->insert_default_terms( 'apply_status_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_apply_status_tax' );
	}

	// DISABLED
	public function meta_init_DIS( $meta_module )
	{
		// NO NEED: unless we have our own meta fields
		// add_filter( 'geditorial_meta_sanitize_post_meta', array( $this, 'meta_sanitize_post_meta' ), 10, 4 );

		// NO NEED: unless we want to integrate meta fields on our own box
		// add_action( 'geditorial_contest_main_meta_box', array( $this, 'meta_main_meta_box' ), 10, 1 );
		// add_action( 'geditorial_contest_supported_meta_box', array( $this, 'meta_supported_meta_box' ), 10, 2 );
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
					'title'  => $this->get_string( 'name', 'contest_cat', 'labels' ),
				),
				$this->constant( 'contest_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'contest_tax' ),
					'icon'   => 'megaphone',
					'title'  => $this->get_string( 'name', 'contest_tax', 'labels' ),
				),
				$this->constant( 'apply_cat' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'apply_cat' ),
					'icon'   => 'category',
					'title'  => $this->get_string( 'name', 'apply_cat', 'labels' ),
				),
				$this->constant( 'apply_status_tax' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'apply_status_tax' ),
					'icon'   => 'portfolio',
					'title'  => $this->get_string( 'name', 'apply_status_tax', 'labels' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'contest_tax' ) == $taxonomy ) {
			$post_id = '';

			// FIXME: working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$post_id = get_term_meta( $term->term_id, $this->constant( 'contest_cpt' ).'_linked', TRUE );

			if ( FALSE == $post_id || empty( $post_id ) )
				$post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'contest_cpt' ) );

			if ( ! empty( $post_id ) )
				return get_permalink( $post_id );
		}

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'contest_tax' ) ) ) {

			$term = get_queried_object();
			if ( $post_id = self::getPostIDbySlug( $term->slug, $this->constant( 'contest_cpt' ) ) )
				gEditorialWordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'contest_cpt' ) )
			|| is_post_type_archive( $this->constant( 'apply_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				gEditorialWordPress::redirect( $redirect, 301 );
		}
	}

	public function do_meta_box_main( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_contest_main_meta_box', $post ); // OLD ACTION: 'geditorial_the_contest_meta_box'

		$this->field_post_order( 'contest_cpt', $post );

		if ( get_post_type_object( $this->constant( 'contest_cpt' ) )->hierarchical )
			$this->field_post_parent( 'contest_cpt', $post );

		$term_id = get_post_meta( $post->ID, '_'.$this->constant( 'contest_cpt' ).'_term_id', TRUE );
		echo gEditorialHelper::getTermPosts( $this->constant( 'contest_tax' ), intval( $term_id ) );

		echo '</div>';
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->constant( 'contest_cpt' ) ) {

			$this->remove_meta_box( $post_type, $post_type, 'parent' );
			add_meta_box( 'geditorial-contest-main',
				$this->get_meta_box_title( 'contest_cpt', FALSE ),
				array( $this, 'do_meta_box_main' ),
				$post_type,
				'side',
				'high'
			);

		} else if ( in_array( $post_type, $this->post_types() ) ) {

			$this->remove_meta_box( $post_type, $post_type, 'parent' );
			add_meta_box( 'geditorial-contest-supported',
				$this->get_meta_box_title( 'post', $this->get_url_post_edit( 'contest_cpt' ), 'edit_others_posts' ),
				array( $this, 'do_meta_box_supported' ),
				$post_type,
				'side'
			);

			$this->add_meta_box_choose_tax( 'apply_status_tax', $post_type );
		}
	}

	public function do_meta_box_supported( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox contest">';

		$terms = gEditorialHelper::getTerms( $this->constant( 'contest_tax' ), $post->ID, TRUE );

		do_action( 'geditorial_contest_supported_meta_box', $post, $terms ); // OLD ACTION: 'geditorial_contest_meta_box'

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

	public function split_shared_term( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy )
	{
		if ( $this->constant( 'contest_tax' ) == $taxonomy ) {

			$post_ids = get_posts( array(
				'post_type'  => $this->constant( 'contest_cpt' ),
				'meta_key'   => '_'.$this->constant( 'contest_cpt' ).'_term_id',
				'meta_value' => $term_id,
				'fields'     => 'ids',
			) );

			if ( $post_ids ) {
				foreach ( $post_ids as $post_id ) {
					update_post_meta( $post_id, '_'.$this->constant( 'contest_cpt' ).'_term_id', $new_term_id, $term_id );
				}
			}
		}
	}

	public function post_updated_messages( $messages )
	{
		if ( $this->is_current_posttype( 'contest_cpt' ) ) {
			$messages[$this->constant( 'contest_cpt' )] = $this->get_post_updated_messages( 'contest_cpt' );
		} else if ( $this->is_current_posttype( 'apply_cpt' ) ) {
			$messages[$this->constant( 'apply_cpt' )] = $this->get_post_updated_messages( 'apply_cpt' );
		}

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

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->constant( 'contest_tax' ).'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->constant( 'contest_tax' ).'_linked', $post_ID );
		}

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

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->constant( 'contest_cpt' ).'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->constant( 'contest_cpt' ).'_linked', $post_ID );
		}

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

			} else if ( 'comments' == $key ){
				$new_columns['children'] = $this->get_column_title( 'children', 'contest_cpt' );

			} else if ( in_array( $key, array( 'author', 'date' ) ) ) {
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
		$columns['order'] = 'menu_order';
		return $columns;
	}
}

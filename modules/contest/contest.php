<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialContest extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'contest';
	var $pre_term    = 'gXcXoE-';

	var $import      = false;

	public function __construct()
	{
		global $gEditorial;

		// adding support for another internal module : gEditorialMeta
		add_filter( 'geditorial_module_defaults_meta', array( &$this, 'module_defaults_meta' ), 10, 2 );
		add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );

		$args = array(
			'title'                => __( 'Contest', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Contest Management', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Set of tools to create and manage text contests and/or gather assignments', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'smiley',
			'slug'                 => 'contest',
			'load_frontend'        => true,
			'constants'            => array(
				'contest_cpt'      => 'contest',
				'contest_archives' => 'contests',
				'apply_cpt'        => 'apply',
				'apply_archives'   => 'applies',
				'contest_tax'      => 'contests',
				'apply_status_tax' => 'apply_status',

			),
			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
					'post'  => 'off',
					'page'  => 'off',
					'apply' => 'on',
				),
				'post_fields' => array(
					'post_title'   => 'on',
					'post_content' => 'on',
					'post_author'  => 'on',
				),
				'settings' => array(
				),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'multiple',
						'title'       => __( 'Multiple Contests', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple contests for appplies.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
				),
				'post_types_option' => 'post_types_option',
			),

			'strings' => array(
				'titles' => array(
					'post' => array(
					),
				),
				'descriptions' => array(
					'post' => array(
					),
				),
				'misc' => array(
					'post' => array(
						'apply_box_title'   => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
						'contest_box_title' => __( 'The Contest', GEDITORIAL_TEXTDOMAIN ),
						'poster_box_title'  => __( 'Contest Poster', GEDITORIAL_TEXTDOMAIN ),
						'column_title'      => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
						'select_contest'    => __( '&mdash; Choose a Contest &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'contest_cpt' => array(
						'name'               => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'      => __( 'Contest', GEDITORIAL_TEXTDOMAIN ),
						'add_new'            => __( 'Add New', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'       => __( 'Add New Contest', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'          => __( 'Edit Contest', GEDITORIAL_TEXTDOMAIN ),
						'new_item'           => __( 'New Contest', GEDITORIAL_TEXTDOMAIN ),
						'view_item'          => __( 'View Contest', GEDITORIAL_TEXTDOMAIN ),
						'search_items'       => __( 'Search Contests', GEDITORIAL_TEXTDOMAIN ),
						'not_found'          => __( 'No contests found', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash' => __( 'No contests found in Trash', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'  => __( 'Parent Contest:', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
					),
					'apply_cpt' => array(
						'name'               => __( 'Applies', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'      => __( 'Apply', GEDITORIAL_TEXTDOMAIN ),
						'add_new'            => __( 'Add New', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'       => __( 'Add New Apply', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'          => __( 'Edit Apply', GEDITORIAL_TEXTDOMAIN ),
						'new_item'           => __( 'New Apply', GEDITORIAL_TEXTDOMAIN ),
						'view_item'          => __( 'View Apply', GEDITORIAL_TEXTDOMAIN ),
						'search_items'       => __( 'Search Applies', GEDITORIAL_TEXTDOMAIN ),
						'not_found'          => __( 'No applies found', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash' => __( 'No applies found in Trash', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'  => __( 'Parent Apply:', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => __( 'Applies', GEDITORIAL_TEXTDOMAIN ),
					),
					'contest_tax' => array(
						'name'                       => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Contest', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Contests', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null, // __( 'Popular Contests', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Contests', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Contest', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Contest:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Contest', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Contest', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Contest', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Contest', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate contests with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove Contests', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from most used Contests', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
					),
					'apply_status_tax' => array(
						'name'                       => __( 'Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Apply Status', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null, // __( 'Popular Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Apply Status', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Apply Status:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Apply Status', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Apply Status', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Apply Status', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Apply Status', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate apply statuses with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from most used Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-contest-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/contest',
				__( 'Editorial Contest Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/gEditorial' ),
			);

		$gEditorial->register_module( $this->module_name, $args );
	}

	// default options for meta module
	public function module_defaults_meta( $default_options, $mod_data )
	{
		if ( ! self::enabled( $this->module_name ) )
			return $default_options;

		$default_options['post_types'][$this->module->constants['contest_cpt']] = 'on';
		$default_options['post_types'][$this->module->constants['apply_cpt']] = 'on';
		$default_options[$this->module->constants['contest_cpt'].'_fields'] = $default_options['post_fields'];
		$default_options[$this->module->constants['apply_cpt'].'_fields'] = $default_options['post_fields'];
		return $default_options;
	}

	// setup actions and filters for meta module
	public function meta_init( $meta_module )
	{
		// TODO: must first check if this module (contest) is active
		if ( ! self::enabled( $this->module_name ) )
			return;

		add_filter( 'geditorial_meta_strings', array( &$this, 'meta_strings' ), 6 , 1 );
		add_filter( 'geditorial_meta_box_callback', array( &$this, 'meta_box_callback' ), 10 , 2 );
		add_filter( 'geditorial_meta_dbx_callback', array( &$this, 'meta_dbx_callback' ), 10 , 2 );
	}

	public function meta_box_callback( $func, $post_type )
	{
		global $gEditorial;
		if ( $post_type == $this->module->constants['contest_cpt']
			|| $post_type == $this->module->constants['apply_cpt'] )
				return array( $gEditorial->meta, 'post_meta_box' );
		return $func;
	}

	public function meta_dbx_callback( $func, $post_type )
	{
		global $gEditorial;
		if ( $post_type == $this->module->constants['contest_cpt']
			|| $post_type == $this->module->constants['apply_cpt'] )
				return array( $gEditorial->meta, 'post_meta_raw' );
		return $func;
	}

	public function meta_strings( $strings )
	{
		// $strings['misc']['post']['box_title'] = 'FFFFFFFFFFFFFFF';
		return $strings;
		// return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			// add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
			// add_filter( 'parent_file', array( &$this, 'parent_file' ) );
		}

		$this->_post_types_excluded = array( $this->module->constants['contest_cpt'] );
	}

	public function init()
	{
		do_action( 'geditorial_contest_init', $this->module );

		$this->do_filters();
		$this->register_post_types();
		$this->register_taxonomies();

		add_filter( 'term_link', array( &$this, 'term_link' ), 10, 3 );
		add_action( 'template_redirect', array( &$this, 'template_redirect' ) );

		add_action( 'save_post', array( &$this, 'save_post_contest_cpt' ), 20, 2 );
		add_filter( 'pre_insert_term', array( &$this, 'pre_insert_term' ), 10, 2 );
		add_action( 'import_start', array( &$this, 'import_start' ) );
		add_action( 'save_post', array( &$this, 'save_post_apply_cpt' ), 20, 2 );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 12, 2 );
		add_action( 'add_meta_boxes', array( &$this, 'remove_meta_boxes' ), 20, 2 );

		// internal actions:
		add_action( 'geditorial_contest_meta_box', array( &$this, 'geditorial_contest_meta_box' ), 5, 2 );
	}

	public function admin_menu()
	{
		$taxonomy = get_taxonomy( $this->module->constants['apply_status_tax'] );
		add_submenu_page( 'edit.php?post_type='.$this->module->constants['contest_cpt'],
			esc_attr( $taxonomy->labels->menu_name ),
			esc_attr( $taxonomy->labels->menu_name ),
			$taxonomy->cap->manage_terms,
			'edit-tags.php?taxonomy='.$taxonomy->name
		);
	}

	public function parent_file( $parent_file = '' )
	{
		global $pagenow;
		if ( ! empty( $_GET['taxonomy'] )
			&& ( $_GET['taxonomy'] == $this->module->constants['apply_status_tax'] )
			&& $pagenow == 'edit-tags.php' )
				$parent_file = 'edit.php?post_type='.$this->module->constants['contest_cpt'];
		return $parent_file;
	}

	public function register_post_types()
	{
		register_post_type( $this->module->constants['contest_cpt'], array(
			'labels'              => $this->module->strings['labels']['contest_cpt'],
			'hierarchical'        => true,
			'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes' ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-megaphone',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => $this->module->constants['contest_archives'],
			'query_var'           => $this->module->constants['contest_cpt'],
			'can_export'          => true,
			'rewrite'             => array(
				'slug'       => $this->module->constants['contest_cpt'],
				'with_front' => false
			),
			'map_meta_cap' => true,
		) );

		register_post_type( $this->module->constants['apply_cpt'], array(
			'labels'              => $this->module->strings['labels']['apply_cpt'],
			'hierarchical'        => false,
			// 'description'         => 'Apply Description',
			'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions' ), //, 'page-attributes' ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true, //'edit.php?post_type='.$this->module->constants['contest_cpt'],
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-format-aside',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => $this->module->constants['apply_archives'],
			'query_var'           => $this->module->constants['apply_cpt'],
			'can_export'          => true,
			'rewrite'             => array(
				'slug'       => $this->module->constants['apply_cpt'],
				'with_front' => false
			),
			'map_meta_cap' => true,
		) );
	}

	public function register_taxonomies()
	{
		register_taxonomy( $this->module->constants['contest_tax'],
			$this->post_types(), array(
				'labels'                => $this->module->strings['labels']['contest_tax'],
				'public'                => true,
				'show_in_nav_menus'     => false,
				'show_ui'               => false, //current_user_can( 'update_plugins' ),
				'show_admin_column'     => true,
				'show_tagcloud'         => false,
				'hierarchical'          => false,
				'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
				'query_var'             => true,
				'rewrite'               => array(
					'slug'         => $this->module->constants['contest_tax'],
					'hierarchical' => false,
					'with_front'   => false
				),
				'capabilities' => array(
					'manage_terms' => 'edit_others_posts',
					'edit_terms'   => 'edit_others_posts',
					'delete_terms' => 'edit_others_posts',
					'assign_terms' => 'edit_published_posts'
				)
			)
		);

		register_taxonomy( $this->module->constants['apply_status_tax'],
			$this->post_types(), array(
				'labels' => $this->module->strings['labels']['apply_status_tax'],
				apply_filters( 'geditorial_contest_apply_status_tax_labels', array(

				) ),
				'public'                => true,
				'show_in_nav_menus'     => false,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'show_tagcloud'         => false,
				'hierarchical'          => true,
				'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
				'query_var'             => true,
				'rewrite'               => array(
					'slug'         => $this->module->constants['apply_status_tax'],
					'hierarchical' => true,
					'with_front'   => false
				),
				'capabilities' => array(
					'manage_terms' => 'edit_others_posts',
					'edit_terms'   => 'edit_others_posts',
					'delete_terms' => 'edit_others_posts',
					'assign_terms' => 'edit_published_posts'
				)
			)
		);
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->module->constants['contest_tax'] == $taxonomy ) {
			$post_id = '';

			if ( function_exists( 'get_term_meta' ) )
				$post_id = get_term_meta( $term->term_id, $this->module->constants['contest_cpt'].'_linked', true );

			if ( false == $post_id || empty( $post_id ) )
				$post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['contest_cpt'] );

			if ( ! empty( $post_id ) )
				return get_permalink( $post_id );
		}

		return $link;
	}

	public function template_redirect()
	{
		if ( ! is_tax() )
			return;

		$term = get_queried_object();

		if ( $this->module->constants['contest_tax'] == $term->taxonomy ) {
			$post_id = '';

			if ( function_exists( 'get_term_meta' ) )
				$post_id = get_term_meta( $term->term_id, $this->module->constants['contest_cpt'].'_linked', true );

			if ( false == $post_id || empty( $post_id ) )
				$post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['contest_cpt'] );

			if ( ! empty( $post_id ) )
				wp_redirect( get_permalink( $post_id ), 301 );
		}
	}

	public function save_post_contest_cpt( $post_id, $post )
	{
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

		if ( $post->post_type != $this->module->constants['contest_cpt'] )
			return $post_id;

		if ( empty( $post->post_name ) )
			return $post_id;

		$term = get_term_by( 'slug', $post->post_name, $this->module->constants['contest_tax'] );
		$pre_meta_issue = get_post_meta( $post_id, '_'.$this->module->constants['contest_cpt'].'_term_id', true );

		$args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		if ( false === $term ) {
			if ( $pre_meta_issue ) {
				$new_term = wp_update_term( intval( $pre_meta_issue ), $this->module->constants['contest_tax'], $args );
			} else {
				$new_term = wp_insert_term( $this->pre_term.$post->post_title, $this->module->constants['contest_tax'], $args );
			}
		} else {
			$new_term = wp_update_term( $term->term_id, $this->module->constants['contest_tax'], $args );
		}

		if ( ! is_wp_error( $new_term ) ) {
			update_post_meta( $post_id, '_'.$this->module->constants['contest_cpt'].'_term_id', $new_term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $new_term['term_id'], $this->module->constants['contest_cpt'].'_linked', $post_id );
		}

		return $post_id;
	}

	// Note that, only admins can insert tax manually, others must create corresponding post type first.
	public function pre_insert_term( $term, $taxonomy )
	{
		if ( $this->module->constants['contest_tax'] != $taxonomy )
			return $term;

		if ( $this->import )
			return $term;

		if ( false === strpos( $term, $this->pre_term ) )
			return new WP_Error( 'not_authenticated', __( 'you\'re doing it wrong!', GEDITORIAL_TEXTDOMAIN ) );

		return str_ireplace( $this->pre_term, '', $term );
	}

	public function import_start()
	{
		$this->import = true;
	}

	public function save_post_apply_cpt( $post_id, $post )
	{
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $post_id;

		if ( isset( $_POST['geditorial_contest_terms'] ) ) {
			$pre_terms = array();

			foreach( $_POST['geditorial_contest_terms'] as $term_id )
				if ( $term_id && '-1' != $term_id )
					$pre_terms[] = intval( $term_id );

			wp_set_object_terms( $post_id, ( count( $pre_terms ) ? $pre_terms : null ), $this->module->constants['contest_tax'], false );
		}

		return $post_id;
	}

	public function remove_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->module->constants['contest_cpt'] ) {

			remove_meta_box( 'pageparentdiv', $post_type, 'side' ); // remove post parent meta box
			add_meta_box( 'geditorial-contest',
				$this->get_string( 'contest_box_title', 'post', 'misc' ),
				array( &$this, 'do_meta_box_contest_cpt' ), $post_type, 'side', 'high' );

			remove_meta_box( 'postimagediv', $post_type, 'side' );
			add_meta_box( 'postimagediv',
				$this->get_string( 'poster_box_title', 'post', 'misc' ),
				'post_thumbnail_meta_box', $post_type, 'side', 'high' );
		}

		// } else if ( ! in_array( $post_type, $this->post_types() ) ) return;
		//
		// remove issue tax box for contributors
		// if ( ! current_user_can( 'edit_published_posts' ) )
		// the tax UI disabled so no need to remove
		// remove_meta_box( 'tagsdiv-'.$this->_constants['issue_tax'], $post_type, 'side' );
	}

	public function do_meta_box_contest_cpt( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_the_contest_meta_box', $post );

		$html = gEditorialHelper::html( 'input', array(
			'type'        => 'number',
			'step'        => '1',
			'size'        => '4',
			'name'        => 'menu_order',
			'id'          => 'menu_order',
			'value'       => $post->menu_order,
			'title'       => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'small-text',
		) );

		echo gEditorialHelper::html( 'div', array(
			'class' => 'field-wrap',
		), $html );

		// $post_type_object = get_post_type_object( $post->post_type );
		// if ( $post_type_object->hierarchical ) {
			$pages = wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['contest_cpt'],
				'selected'         => $post->post_parent,
				'name'             => 'parent_id',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => __( '(no parent)', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order, post_title',
				'echo'             => 0
			));
			if ( ! empty( $pages ) ) {
				echo gEditorialHelper::html( 'div', array(
					'class' => 'field-wrap',
				), $pages );
			}
		// }

		echo '</div>';
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! in_array( $post_type, $this->post_types() ) )
			return;

		$title = $this->get_string( 'apply_box_title', 'post', 'misc' );
		if ( current_user_can( 'manage_options' ) ) {
			$url = add_query_arg( 'page', 'geditorial-contest-settings', get_admin_url( null, 'admin.php' ) );
			$title .= ' <span class="geditorial-admin-action-metabox"><a href="'.esc_url( $url ).'" class="edit-box open-box" >'.__( 'Configure', GEDITORIAL_TEXTDOMAIN ).'</a></span>';
		}
		add_meta_box( 'geditorial-contests', $title, array( &$this, 'do_meta_box_applies' ), $post_type, 'side' );
	}

	public function do_meta_box_applies( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';
		do_action( 'geditorial_contest_meta_box', $post, $this->get_contests( $post->ID, true ) );
		echo '</div>';
	}

	public function geditorial_contest_meta_box( $post, $the_terms )
	{
		$html = gEditorialHelper::html( 'input', array(
			'type'        => 'number',
			'step'        => '1',
			'size'        => '4',
			'name'        => 'menu_order',
			'id'          => 'menu_order',
			'value'       => $post->menu_order,
			'title'       => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'small-text',
		) );

		echo gEditorialHelper::html( 'div', array(
			'class' => 'field-wrap',
		), $html );

		$contests_dropdowns = $excludes = array();
		foreach ( $the_terms as $the_term ) {
			$contests_dropdowns[$the_term->term_id] = wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['contest_tax'],
				'selected'         => $the_term->term_id,
				'show_option_none' => $this->get_string( 'select_contest', 'post', 'misc' ),
				'name'             => 'geditorial_contest_terms[]',
				'id'               => 'geditorial_contest_terms-'.$the_term->term_id,
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
			) );
			$excludes[] = $the_term->term_id;
		}

		if ( $this->get_setting( 'multiple', false ) || ! count( $the_terms ) )
			$contests_dropdowns[0] = wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['contest_tax'],
				'selected'         => 0,
				'show_option_none' => $this->get_string( 'select_contest', 'post', 'misc' ),
				'name'             => 'geditorial_contest_terms[]',
				'id'               => 'geditorial_contest_terms-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
				'exclude'          => $excludes,
			) );

		foreach( $contests_dropdowns as $issues_term_id => $contests_dropdown ) {
			if ( $contests_dropdown ) {
				echo '<div class="field-wrap">';
				echo $contests_dropdown;
				// do_action( 'geditorial_contests_meta_box_select', $issues_term_id, $post, $the_issue_terms );
				echo '</div>';
			}
		}
	}

	public function get_contests( $post_ID, $object = false )
	{
		$the_terms = array();
		$terms = get_the_terms( $post_ID, $this->module->constants['contest_tax'] );
		if ( is_wp_error( $terms ) || false === $terms )
			return $the_terms;

		if ( $object )
			return $terms;

		foreach ( $terms as $term )
			$the_terms[] = $term->term_id;

		return $the_terms;
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazine extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'magazine';
	var $meta_key    = '_ge_magazine';

	var $post_id = false; // current post id
	var $cookie  = 'geditorial-magazine';

	var $_post_types_excluded = array();

	var $_import      = false;
	var $_term_suffix = 'gXmXaXg_';

    public function __construct()
    {
		global $gEditorial;

		// adding support for another internal module : gEditorialMeta
		add_filter( 'geditorial_module_defaults_meta', array( &$this, 'module_defaults_meta' ), 10, 2 );
		add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );

		add_action( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		// support for gPeople
		add_filter( 'gpeople_remote_support_post_types', array( &$this, 'gpeople_remote_support_post_types' ) );

		$this->module_url = $this->get_module_url( __FILE__ );
		$args = array(
			'title'                => __( 'Magazine', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Issue Management for Magazines', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Magazine suite for wordpress', GEDITORIAL_TEXTDOMAIN ),
			'module_url'           => $this->module_url,
			'dashicon'             => 'book',
			'slug'                 => 'magazine',
			'load_frontend'        => true,

			'constants' => array(
				'issue_cpt'       => 'issue',
				'issue_archives'  => 'issues',
				'issue_tax'       => 'issues',
				'span_tax'        => 'span',
				'issue_shortcode' => 'issue',
				'span_shortcode'  => 'span',
				'connection_type' => 'related_issues',
			),

			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
				),
				'post_fields' => array(
				),
				'settings' => array(
				),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'comments',
						'type'        => 'enabled',
						'title'       => _x( 'Comments', 'Enable Magazine for Comments', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Magazine button for enabled post types comments', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'avatars',
						'type'        => 'enabled',
						'title'       => _x( 'Avatars', 'Enable Magazine for Comments', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Display avatars alongside magazine button', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
				),
				'post_types_option' => 'post_types_option',
				//'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles' => array(
				),
				'descriptions' => array(
				),
				'misc' => array(
					'meta_box_title'     => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
					'issue_box_title'    => __( 'The Issue', GEDITORIAL_TEXTDOMAIN ),
					'cover_box_title'    => __( 'Cover', GEDITORIAL_TEXTDOMAIN ),
					'order_column_title' => __( 'O', GEDITORIAL_TEXTDOMAIN ),
					'cover_column_title' => __( 'Cover', GEDITORIAL_TEXTDOMAIN ),
					'posts_column_title' => __( 'Posts', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'issue_cpt' => array(
						'name'               => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'      => __( 'Issue', GEDITORIAL_TEXTDOMAIN ),
						'add_new'            => __( 'Add New', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'       => __( 'Add New Issue', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'          => __( 'Edit Issue', GEDITORIAL_TEXTDOMAIN ),
						'new_item'           => __( 'New Issue', GEDITORIAL_TEXTDOMAIN ),
						'view_item'          => __( 'View Issue', GEDITORIAL_TEXTDOMAIN ),
						'search_items'       => __( 'Search Issues', GEDITORIAL_TEXTDOMAIN ),
						'not_found'          => __( 'No issues found', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash' => __( 'No issues found in Trash', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'  => __( 'Parent Issue:', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
					),
					'issue_tax' => array(
						'name'                       => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Issue', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Issues', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null, // __( 'Popular Issues', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Issues', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Issue', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Issue:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Issue', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Issue', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Issue', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Issue', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate issues with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove Issues', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from most used Issues', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
					),
					'span_tax' => array(
						'name'                       => __( 'Spans', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Span', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Spans', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null, // __( 'Popular Spans', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Spans', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Span', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Span:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Span', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Span', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Span', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Span', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate spans with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove Spans', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from most used Spans', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Spans', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),

			'configure_page_cb' => 'print_configure_view',
			'settings_help_tabs' => array( array(
				'id'      => 'geditorial-magazine-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
			) ),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/magazine',
				__( 'Editorial Magazine Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/gEditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	// default options for meta module
	public function module_defaults_meta( $default_options, $mod_data )
	{
		if ( ! self::enabled( $this->module_name ) )
			return $default_options;

		$fields = $this->get_meta_fields();

		$default_options['post_types'][$this->module->constants['issue_cpt']] = true;
		$default_options[$this->module->constants['issue_cpt'].'_fields'] = $fields[$this->module->constants['issue_cpt']];
		$default_options['post_fields'] = array_merge( $default_options['post_fields'], $fields['post'] );

		return $default_options;
	}

	// setup actions and filters for meta module
	public function meta_init( $meta_module )
	{
		if ( ! self::enabled( $this->module_name ) )
			return;

		add_filter( 'geditorial_meta_strings', array( &$this, 'meta_strings' ), 6, 1 );

		//add_filter( 'geditorial_meta_box_callback', array( &$this, 'meta_box_callback' ), 10, 2 );
		add_filter( 'geditorial_meta_dbx_callback', array( &$this, 'meta_dbx_callback' ), 10, 2 );
		add_filter( 'geditorial_meta_sanitize_post_meta', array( &$this, 'meta_sanitize_post_meta' ), 10 , 4 );

		add_action( 'geditorial_magazine_issue_meta_box', array( &$this, 'meta_issue_meta_box' ), 10, 1 );
		add_action( 'geditorial_magazine_issues_meta_box', array( &$this, 'meta_issues_meta_box' ), 10, 2 );
	}

	public function setup()
	{
		require_once( GEDITORIAL_DIR.'modules/magazine/templates.php' );

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

            add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
            add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
            add_filter( 'parse_query', array( &$this, 'parse_query_issues' ) );
		} else {
			add_filter( 'term_link', array( &$this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( &$this, 'template_redirect' ) );

			add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), 36 );
		}

		add_action( 'split_shared_term', array( &$this, 'split_shared_term' ), 10, 4 );

		// WHAT ABOUT : constant filters
		$this->_post_types_excluded = array( $this->module->constants['issue_cpt'] );
	}

	public function init()
	{
		do_action( 'geditorial_magazine_init', $this->module );

		$this->do_filters();
		$this->register_post_types();
		$this->register_taxonomies();

		add_shortcode( $this->module->constants['issue_shortcode'], array( 'gEditorialMagazineTemplates', 'issue_shortcode' ) );
		add_shortcode( $this->module->constants['span_shortcode'], array( 'gEditorialMagazineTemplates', 'span_shortcode' ) );
	}

	public function admin_init()
	{
		// tools actions for settings module
		if ( current_user_can( 'edit_others_posts' ) ) {
			add_filter( 'geditorial_tools_subs', array( &$this, 'tools_subs' ) );
			add_filter( 'geditorial_tools_messages', array( &$this, 'tools_messages' ), 10, 2 );
			add_action( 'geditorial_tools_load', array( &$this, 'tools_load' ) );
			add_action( 'geditorial_tools_sub_magazine', array( &$this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );

		add_action( 'save_post_'.$this->module->constants['issue_cpt'], array( &$this, 'save_post_main_cpt' ), 20, 3 );
		add_action( 'save_post', array( &$this, 'save_post_supported_cpt' ), 20, 3 );

		add_filter( 'pre_insert_term', array( &$this, 'pre_insert_term' ), 10, 2 );
		add_action( 'import_start', array( &$this, 'import_start' ) );

        add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 12, 2 );
        add_action( 'add_meta_boxes', array( &$this, 'remove_meta_boxes' ), 20, 2 );

        add_filter( "manage_{$this->module->constants['issue_cpt']}_posts_columns", array( $this, 'manage_posts_columns' ) );
        add_filter( "manage_{$this->module->constants['issue_cpt']}_posts_custom_column", array( $this, 'custom_column'), 10, 2 );
        add_filter( "manage_edit-{$this->module->constants['issue_cpt']}_sortable_columns", array( $this, 'sortable_columns' ) );

        // internal actions:
        add_action( 'geditorial_magazine_issues_meta_box', array( &$this, 'issues_meta_box' ), 5, 2 );
	}

	public function widgets_init()
	{
		require_once( GEDITORIAL_DIR.'modules/magazine/widgets.php' );

		register_widget( 'gEditorialMagazineWidget_IssueCover' );
	}

	public function register_post_types()
    {
        register_post_type( $this->module->constants['issue_cpt'], array(
			'labels'       => $this->module->strings['labels']['issue_cpt'],
			'hierarchical' => true,
			'supports'     => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'trackbacks',
				'custom-fields',
				'comments',
				'revisions',
				'page-attributes'
			),
			'taxonomies'          => array( $this->module->constants['issue_tax'] ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-book',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => $this->module->constants['issue_archives'],
			'query_var'           => $this->module->constants['issue_cpt'],
			'can_export'          => true,
			'rewrite'             => array(
				'slug'       => $this->module->constants['issue_cpt'],
				'with_front' => false
			),
			'map_meta_cap' => true,
        ) );
	}

	public function register_taxonomies()
	{
        register_taxonomy( $this->module->constants['issue_tax'], $this->post_types(), array(
			'labels'                => $this->module->strings['labels']['issue_tax'],
			'public'                => true,
			'show_in_nav_menus'     => false,
			'show_ui'               => false,
			'show_admin_column'     => false,
			'show_tagcloud'         => false,
			'hierarchical'          => true,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['issue_tax'],
				'hierarchical' => true,
				'with_front'   => true
			),
			'query_var'    => true,
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
            )
        ) );

        register_taxonomy( $this->module->constants['span_tax'], array( $this->module->constants['issue_cpt'] ), array(
			'labels'                => $this->module->strings['labels']['span_tax'],
			'public'                => true,
			'show_in_nav_menus'     => true,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_tagcloud'         => false,
			'hierarchical'          => false,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['span_tax'],
				'hierarchical' => false,
				'with_front'   => true
			),
			'query_var'    => true,
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
            )
        ) );
	}

    // http://justintadlock.com/archives/2010/08/20/linking-terms-to-a-specific-post
    public function term_link( $link, $term, $taxonomy )
    {
        if ( $this->module->constants['issue_tax'] == $taxonomy ) {
            $post_id = '';

			// working but disabled
            //if ( function_exists( 'get_term_meta' ) )
                //$post_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', true );

            if ( false == $post_id || empty( $post_id ) )
                $post_id = gEditorialHelper::get_post_id_by_slug( $term->slug, $this->module->constants['issue_cpt'] );

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

		if ( $this->module->constants['issue_tax'] == $term->taxonomy ) {
			$post_id = '';

			// working but disabled
			//if ( function_exists( 'get_term_meta' ) )
				//$post_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', true );

			if ( false == $post_id || empty( $post_id ) )
				$post_id = gEditorialHelper::get_post_id_by_slug( $term->slug, $this->module->constants['issue_cpt'] );

			if ( ! empty( $post_id ) )
				wp_redirect( get_permalink( $post_id ), 301 );
		}
    }

    public function save_post_main_cpt( $post_id, $post, $update )
	{
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

        if ( empty( $post->post_name ) )
            return $post_id;

        // TODO: issue parent
        // if ( $post->post_parent ) {
        //     $parent_post = get_post( $post->post_parent, ARRAY_A );
        //     $parent_term = term_exists( $parent_post['post_name'], $this->_issue_tax );
        //     if ( false == $parent_term ) {
        //         // TODO: update term parent
        //     }
        // }

		$term           = get_term_by( 'slug', $post->post_name, $this->module->constants['issue_tax'] );
		$pre_meta_issue = get_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', true );

        $args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
        );

        if ( false === $term ) {
            if ( $pre_meta_issue ) {
                $new_term = wp_update_term( intval( $pre_meta_issue ), $this->module->constants['issue_tax'], $args );
            } else {
                $new_term = wp_insert_term( $this->_term_suffix.$post->post_title, $this->module->constants['issue_tax'], $args );
            }
        } else {
            $new_term = wp_update_term( $term->term_id, $this->module->constants['issue_tax'], $args );
        }

        if ( ! is_wp_error( $new_term ) ) {
            update_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', $new_term['term_id'] );

            if ( function_exists( 'update_term_meta' ) )
                update_term_meta( $new_term['term_id'], $this->module->constants['issue_cpt'].'_linked', $post_id );
        }

        return $post_id;
    }

	// https://gist.github.com/boonebgorges/e873fc9589998f5b07e1
	public function split_shared_term( $term_id, $new_term_id, $term_taxonomy_id, $taxonomy )
	{
	    if ( $this->module->constants['issue_tax'] == $taxonomy ) {

	        $post_ids = get_posts( array(
				'post_type'  => $this->module->constants['issue_cpt'],
				'meta_key'   => '_'.$this->module->constants['issue_cpt'].'_term_id',
				'meta_value' => $term_id,
				'fields'     => 'ids',
	        ) );

	        if ( $post_ids ) {
	            foreach ( $post_ids as $post_id ) {
	                update_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', $new_term_id, $term_id );
	            }
	        }
	    }
	}

    public function import_start()
    {
        $this->_import = true;
    }

	// note that, only admins can insert tax manually, others must create corresponding post type first.
    public function pre_insert_term( $term, $taxonomy )
    {
        //if ( $this->module->constants['issue_tax'] == $taxonomy && ( ! current_user_can( 'edit_theme_options' ) ) )
        if ( $this->module->constants['issue_tax'] != $taxonomy )
            return $term;

        if ( $this->_import )
            return $term;

        if ( false === strpos( $term, $this->_term_suffix ) )
            return new WP_Error( 'not_authenticated', __( 'you\'re doing it wrong!', GEDITORIAL_TEXTDOMAIN ) );

        return str_ireplace( $this->_term_suffix, '', $term );
    }

    public function save_post_supported_cpt( $post_id, $post, $update )
    {
		if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $post_id;

        if ( isset( $_POST['geditorial_magazine_issue_terms'] ) ) {
			$pre_issue_terms = array();

            foreach( $_POST['geditorial_magazine_issue_terms'] as $the_issue_term_id )
                if ( $the_issue_term_id )
                    $pre_issue_terms[] = intval( $the_issue_term_id );

            wp_set_object_terms( $post_id, ( count( $pre_issue_terms ) ? $pre_issue_terms : null ), $this->module->constants['issue_tax'], false );
        }

		return $post_id;
	}

    public function after_setup_theme()
    {
        //add_theme_support( 'post-thumbnails', array( $this->module->constants['issue_cpt'] ) );
		self::themeThumbnails( array( $this->module->constants['issue_cpt'] ) );

        foreach( $this->get_image_sizes() as $name => $size )
            add_image_size( $name, $size['w'], $size['h'], $size['c'] );
    }

    public function p2p_init()
    {
        // https://github.com/scribu/wp-posts-to-posts/wiki/Connection-information
        $args = apply_filters( 'geditorial_magazine_p2p_args', array(
            'name' => $this->module->constants['connection_type'],
            'from' => $this->post_types(),
            'to' => $this->module->constants['issue_cpt'],
            'title' => array(
				'from' => __( 'Connected Issues', GEDITORIAL_TEXTDOMAIN ),
				'to'   => __( 'Connected Posts', GEDITORIAL_TEXTDOMAIN )
            ),
            'from_labels' => array(
				'singular_name' => __( 'Post', GEDITORIAL_TEXTDOMAIN ),
				'search_items'  => __( 'Search posts', GEDITORIAL_TEXTDOMAIN ),
				'not_found'     => __( 'No posts found.', GEDITORIAL_TEXTDOMAIN ),
				'create'        => __( 'Connect to a post', GEDITORIAL_TEXTDOMAIN ),
            ),
            'to_labels' => array(
				'singular_name' => __( 'Issue', GEDITORIAL_TEXTDOMAIN ),
				'search_items'  => __( 'Search issues', GEDITORIAL_TEXTDOMAIN ),
				'not_found'     => __( 'No issues found.', GEDITORIAL_TEXTDOMAIN ),
				'create'        => __( 'Connect to an issue', GEDITORIAL_TEXTDOMAIN ),
        ) ) );

        if ( $args )
            p2p_register_connection_type( $args );
    }

    public function get_image_sizes()
    {
        return apply_filters( 'geditorial_magazine_issue_image_sizes', array(
            'issue-thumbnail' => array(
                'n' => __( 'Thumbnail', GEDITORIAL_TEXTDOMAIN ),
                'w' => get_option( 'thumbnail_size_w' ),
                'h' => get_option( 'thumbnail_size_h' ),
                'c' => get_option( 'thumbnail_crop' ),
            ),
            'issue-medium' => array(
                'n' => __( 'Medium', GEDITORIAL_TEXTDOMAIN ),
                'w' => get_option( 'medium_size_w' ),
                'h' => get_option( 'medium_size_h' ),
                'c' => 0,
            ),
            'issue-large' => array(
                'n' => __( 'Large', GEDITORIAL_TEXTDOMAIN ),
                'w' => get_option( 'large_size_w' ),
                'h' => get_option( 'large_size_h' ),
                'c' => 0,
            ),
        ) );
    }

    public function admin_bar_menu( $wp_admin_bar )
    {
        if ( ! is_admin_bar_showing() || is_admin() )
            return;

		if ( current_user_can( 'edit_posts' ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'site-name',
				'id'     => 'all-issues',
				'title'  => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
				'href'   => admin_url( 'edit.php?post_type='.$this->module->constants['issue_cpt'] ),
			) );
        }
    }

	public function gpeople_remote_support_post_types( $post_types )
	{
		return array_merge( $post_types, array( $this->module->constants['issue_cpt'] ) );
	}

    public function pre_get_posts( $wp_query )
    {
        if ( is_admin() && isset( $wp_query->query['post_type'] ) ) {
            if ( $this->module->constants['issue_cpt'] == $wp_query->query['post_type'] ) {
                if ( ! isset( $_GET['orderby'] ) )
                    $wp_query->set( 'orderby', 'menu_order' );
                if ( ! isset( $_GET['order'] ) )
                    $wp_query->set( 'order', 'DESC' );
            }
        }
    }

    public function restrict_manage_posts()
    {
        global $typenow;

        if ( in_array( $typenow, $this->post_types() ) ) {

			//$filters = get_object_taxonomies( $typenow );
            $tax_obj = get_taxonomy( $this->module->constants['issue_tax'] );

			// TODO : check if there's no issue
            $selected = isset( $_GET[$this->module->constants['issue_tax']] ) ? $_GET[$this->module->constants['issue_tax']] : 0;

			wp_dropdown_categories( array(
				'show_option_all' => $tax_obj->labels->all_items,
				'taxonomy'        => $this->module->constants['issue_tax'],
				'name'            => $tax_obj->name,
				'class'            => 'geditorial-admin-dropbown',
				// 'orderby'         => 'slug',
				'order'           => 'DESC',
				'selected'        => $selected,
				'hierarchical'    => $tax_obj->hierarchical,
				'show_count'      => false,
				'hide_empty'      => true,
            ) );
        }
    }

    public function parse_query_issues( $query )
    {
        global $pagenow, $typenow;

        if ( 'edit.php' == $pagenow ) {

            // TODO: just add our tax!

			$filters = get_object_taxonomies( $typenow );
            foreach ( $filters as $tax_slug ) {
				$var = &$query->query_vars[$tax_slug];
				if ( isset( $var ) ) {
					$term = get_term_by( 'id', $var, $tax_slug );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$var = $term->slug;
                }
            }
        }
    }

    public function add_meta_boxes( $post_type, $post )
    {
        if ( in_array( $post_type, $this->post_types() ) ) {

			$title = $this->get_string( 'meta_box_title', $post_type, 'misc' );
            if ( current_user_can( 'edit_others_posts' ) ) {
                $url = add_query_arg( 'post_type', $this->module->constants['issue_cpt'], get_admin_url( null, 'edit.php' ) );
                $title .= ' <span class="geditorial-admin-action-metabox"><a href="'.esc_url( $url ).'" class="edit-box open-box">'.__( 'Issues', GEDITORIAL_TEXTDOMAIN ).'</a></span>';
            }

            add_meta_box( 'geditorial-magazine-issues',
				$title,
				array( &$this, 'do_meta_box_issues' ),
				$post_type,
				'side'
			);
        }

        // TODO : add a box to list the posts with this issue taxonomy
    }

    public function do_meta_box_issues( $post )
    {
		echo '<div class="geditorial-admin-wrap-metabox magazine">';
		$issues = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post->ID, true );
        do_action( 'geditorial_magazine_issues_meta_box', $post, $issues );
		echo '</div>';
    }

    public function issues_meta_box( $post, $the_issue_terms )
    {
        $issues_dropdowns = $excludes = array();
        foreach ( $the_issue_terms as $the_issue_term ) {
            $issues_dropdowns[$the_issue_term->term_id] = wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['issue_tax'],
				'selected'         => $the_issue_term->term_id,
				'show_option_none' => __( '&mdash; Select an Issue &mdash;', GEDITORIAL_TEXTDOMAIN ),
				'name'             => 'geditorial_magazine_issue_terms[]',
				'id'               => 'geditorial_magazine_issue_terms-'.$the_issue_term->term_id,
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
            ) );
			$excludes[] = $the_issue_term->term_id;
		}

        // TODO : use option to disable/enable this
		if ( ! count( $the_issue_terms ) )
            $issues_dropdowns[0] = wp_dropdown_categories( array(
				'taxonomy'         => $this->module->constants['issue_tax'],
				'selected'         => 0,
				'show_option_none' => __( '&mdash; Select an Issue &mdash;', GEDITORIAL_TEXTDOMAIN ),
				'name'             => 'geditorial_magazine_issue_terms[]',
				'id'               => 'geditorial_magazine_issue_terms-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_count'       => 1,
				'hide_empty'       => 0,
				'echo'             => 0,
				'exclude'          => $excludes,
            ) );

        foreach( $issues_dropdowns as $issues_term_id => $issues_dropdown ) {
            if ( $issues_dropdown ) {
                echo '<div class="field-wrap">';
                echo $issues_dropdown;
                // do_action( 'gmag_issues_meta_box_select', $issues_term_id, $post, $the_issue_terms );
                echo '</div>';
            }
        }

        return;

        if ( $the_issue_term )
            $the_issue_post = get_page_by_title( $the_issue_term->name, OBJECT, $this->module->constants['issue_cpt'] );

        if ( isset( $the_issue_post ) && $the_issue_post )
            $the_issue_post_id = $the_issue_post->ID;
        else
            $the_issue_post_id = '0'; // TODO : get default!

        // TODO : must write our own, core function does not support post statuses
        $issues = wp_dropdown_pages( array(
			'post_type'        => $this->module->constants['issue_cpt'],
			'selected'         => $the_issue_post_id,
			'name'             => 'geditorial_magazine_issue_terms[]',
			'id'               => 'geditorial_magazine_issue_terms',
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => __( '&mdash; Select an Issue &mdash;', GEDITORIAL_TEXTDOMAIN ),
			// 'hierarchical'     => 0,
			'sort_column'      => 'menu_order, post_title',
			'echo'             => 0
        ));

        if ( isset( $issues ) && ! empty( $issues ) ) {
            echo $issues;
        } else {
            echo __( 'There are no issues!', GEDITORIAL_TEXTDOMAIN );
            return; // to skip page associations
        }

        // TODO : add : page start, page end
    }

    public function remove_meta_boxes( $post_type, $post )
    {
        if ( $post_type == $this->module->constants['issue_cpt'] ) {

			// remove post parent meta box
            remove_meta_box( 'pageparentdiv', $post_type, 'side' );
            add_meta_box( 'geditorial-magazine-issue',
				$this->get_string( 'issue_box_title', $post_type, 'misc' ),
				array( &$this, 'do_meta_box_issue' ),
				$post_type,
				'side',
				'high'
			);

            remove_meta_box( 'postimagediv', $this->module->constants['issue_cpt'], 'side' );
            add_meta_box( 'postimagediv',
				$this->get_string( 'cover_box_title', $post_type, 'misc' ),
                'post_thumbnail_meta_box',
                $this->module->constants['issue_cpt'],
                'side',
                'high'
            );
        }

        // remove issue tax box for contributors
        //if ( ! current_user_can( 'edit_published_posts' ) )
        // the tax UI disabled so no need to remove
        //remove_meta_box( 'tagsdiv-'.$this->module->constants['issue_tax'], $post_type, 'side' );
    }

    public function do_meta_box_issue( $post )
    {
		echo '<div class="geditorial-admin-wrap-metabox">';

        do_action( 'geditorial_magazine_issue_meta_box', $post );

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

        $post_type_object = get_post_type_object( $this->module->constants['issue_cpt'] );
        if ( $post_type_object->hierarchical ) {
            $pages = wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['issue_cpt'],
				'selected'         => $post->post_parent,
				'name'             => 'parent_id',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => __( '(no parent)', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order, post_title',
				'exclude_tree'     => $post->ID,
				'echo'             => 0,
            ));
			if ( $pages )
				echo gEditorialHelper::html( 'div', array(
					'class' => 'field-wrap',
				), $pages );
		}

		$term_id = get_post_meta( $post->ID, '_'.$this->module->constants['issue_cpt'].'_term_id', true );
		echo gEditorialHelper::getTermPosts( $this->module->constants['issue_tax'], intval( $term_id ) );

		echo '</div>';
    }

    public function get_issue_post( $post_ID = null )
    {
        $post_ID = ( null === $post_ID ) ? get_the_ID() : $post_ID;

		$terms = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post_ID, true );
        if ( ! count( $terms ) )
            return false;

		$the_id = false;
        $ids = array();
        foreach ( $terms as $term ) {
            // working but disabled
			//if ( function_exists( 'get_term_meta' ) )
                //$the_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', true );

            if ( false == $the_id || empty( $the_id ) )
                $the_id = gEditorialHelper::get_post_id_by_slug( $term->slug, $this->module->constants['issue_cpt'] );

            if ( false != $the_id && ! empty( $the_id ) ) {
				$status = get_post_status( $the_id );
				if ( 'publish' == $status )
					$ids[$the_id] = get_permalink( $the_id );
				else
					$ids[$the_id] = false;
			}
        }

        if ( ! count( $ids ) )
            return false;
        return $ids;
    }

    public function manage_posts_columns( $posts_columns )
    {
        $new_columns = array();
		foreach( $posts_columns as $key => $value ) {
			if ( $key == 'title' ) {
                $new_columns['issue_order'] = $this->get_string( 'order_column_title', null, 'misc' );
                $new_columns['cover'] = $this->get_string( 'cover_column_title', null, 'misc' );
                $new_columns[$key] = $value;
            } else if ( 'author' == $key ){
                // $new_columns[$key] = $value;
            } else if ( 'comments' == $key ){
                $new_columns['issue_posts'] = $this->get_string( 'posts_column_title', null, 'misc' );
                $new_columns[$key] = $value;
            } else {
                $new_columns[$key] = $value;
            }
		}
		return $new_columns;
    }

	public function issue_post_count( $post_id )
	{
		$term_id = get_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', true );

		$items = get_posts( array(
			'tax_query' => array( array(
				'taxonomy' => $this->module->constants['issue_tax'],
				'field' => 'id',
				'terms' => array( $term_id )
			) ),
			'post_type' => $this->post_types(),
			'numberposts' => -1,
		) );

		return count( $items );
	}

    public function custom_column( $column_name, $post_id )
    {
        if ( 'issue_posts' == $column_name ) {

			$count = $this->issue_post_count( $post_id );
            if ( $count )
                echo number_format_i18n( $count );
            else
                _e( '<span title="No Posts">&mdash;</span>', GEDITORIAL_TEXTDOMAIN );


        } else if ( 'XSVWSVWVWV' == $column_name ) { // disabled!
			$issues = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post_id, true );
            if ( $issues ) {
                $issue_terms = array();
                foreach ( $issues as $term )
                    $issue_terms[] = "<a href='edit.php?post_type={$this->module->constants['entry_cpt']}&{$this->module->constants['section_tax']}={$term->slug}'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, $this->module->constants['section_tax'], 'edit')) . '</a>';
                echo join( _x( ', ', 'issues column terms between', GEDITORIAL_TEXTDOMAIN ), $issue_terms );
            } else {
                _e( '<span title="No Posts">&mdash;</span>', GEDITORIAL_TEXTDOMAIN );
            }

        } else if ( 'issue_order' == $column_name ) {
            $post = get_post( $post_id );
            if ( ! empty( $post->menu_order ) )
                echo number_format_i18n( $post->menu_order );
            else
                _e( '<span title="No Order">&mdash;</span>', GEDITORIAL_TEXTDOMAIN );

        } elseif ( 'cover' == $column_name ) {
			$cover = gEditorialHelper::get_featured_image_src( $post_id, 'issue-thumbnail', false );
			if ( $cover )
				echo gEditorialHelper::html( 'img', array(
					'src' => $cover,
					'style' => 'max-width:50px;max-height:60px;',
				) );
		}
    }

    public function sortable_columns( $columns )
    {
        $columns['issue_order'] = 'menu_order';
    	return $columns;
    }

	public function get_meta_fields()
	{
		return array(
			$this->module->constants['issue_cpt'] => array (
				'ot'                => 'off',
				'st'                => 'on',
				'issue_number_line' => 'on',
				'issue_total_pages' => 'on',
			 ),
			'post' => array(
				'in_issue_order'      => 'on',
				'in_issue_page_start' => 'on',
				'in_issue_pages'      => 'off',
		) );
	}

	public function meta_strings( $strings )
	{
		$new = array(
			'titles' => array(
				$this->module->constants['issue_cpt'] => array(
					'issue_number_line' => __( 'Number Line', GEDITORIAL_TEXTDOMAIN ),
					'issue_total_pages' => __( 'Total Pages', GEDITORIAL_TEXTDOMAIN ),
				),
				'post' => array(
					'in_issue_order'      => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_page_start' => __( 'Page Start', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_pages'      => __( 'Total Pages', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				$this->module->constants['issue_cpt'] => array(
					'issue_number_line' => __( 'The issue number line', GEDITORIAL_TEXTDOMAIN ),
					'issue_total_pages' => __( 'The issue total pages', GEDITORIAL_TEXTDOMAIN ),
				),
				'post' => array(
					'in_issue_order'      => __( 'Post order in issue list', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_page_start' => __( 'Post start page on issue (printed)', GEDITORIAL_TEXTDOMAIN ),
					'in_issue_pages'      => __( 'Post total pages on issue (printed)', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->module->constants['issue_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['issue_tax'],
					'dashicon'   => 'book',
					'title_attr' => $this->get_string( 'name', 'issue_tax', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}


	public function meta_dbx_callback( $func, $post_type )
	{
		if ( $this->module->constants['issue_cpt'] == $post_type )
			return array( &$this, 'raw_callback' );
		return $func;
	}

    // meta on edit issue page
	public function raw_callback()
	{
		global $gEditorial, $post;

		$fields = $gEditorial->meta->post_type_fields( $post->post_type );

		gEditorialHelper::meta_admin_title_field( 'ot', $fields, $post );
		gEditorialHelper::meta_admin_title_field( 'st', $fields, $post );

		wp_nonce_field( 'geditorial_meta_post_raw', '_geditorial_meta_post_raw' );
	}

	public function meta_sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		$fields = $this->get_meta_fields();

		if ( $this->module->constants['issue_cpt'] == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_magazine_issue_box'], 'geditorial_magazine_issue_box' ) ) {

			foreach ( $fields[$post_type] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'issue_total_pages' :
					case 'issue_number_line' :
						if ( isset( $_POST['geditorial-meta-'.$field] )
							&& strlen( $_POST['geditorial-meta-'.$field] ) > 0 )
							// && $gEditorial->meta->module->strings['titles'][$field] !== $_POST['geditorial-meta-'.$field] )
								$postmeta[$field] = strip_tags( $_POST['geditorial-meta-'.$field] );
						elseif ( isset( $postmeta[$field] ) && isset( $_POST['geditorial-meta-'.$field] ) )
							unset( $postmeta[$field] );
				}
			}

		} else if ( 'post' == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_magazine_meta_post_raw'], 'geditorial_magazine_meta_post_raw' )  ) {

			foreach ( $fields[$post_type] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'in_issue_order' :
					case 'in_issue_page_start' :
					case 'in_issue_pages' :
						if ( isset( $_POST['geditorial-meta-'.$field] )
							&& strlen( $_POST['geditorial-meta-'.$field] ) > 0 )
							//&& $gEditorial->meta->module->strings['titles'][$field] !== $_POST['geditorial-meta-'.$field] )
								$postmeta[$field] = strip_tags( $_POST['geditorial-meta-'.$field] );
						elseif ( isset( $postmeta[$field] ) && isset( $_POST['geditorial-meta-'.$field] ) )
							unset( $postmeta[$field] );
				}
			}

		}
		return $postmeta;
	}

    // on issue
	//function box_callback()
	public static function meta_issue_meta_box( $post )
	{
		global $gEditorial;

		$fields = $gEditorial->meta->post_type_fields( $post->post_type );

		do_action( 'geditorial_meta_box_before', $gEditorial->meta->module, $post, $fields );

		gEditorialHelper::meta_admin_field( 'issue_number_line', $fields, $post );
		gEditorialHelper::meta_admin_field( 'issue_total_pages', $fields, $post );

		do_action( 'geditorial_meta_box_after', $gEditorial->meta->module, $post, $fields );

        wp_nonce_field( 'geditorial_magazine_issue_box', '_geditorial_magazine_issue_box' );
	}

    // on posts
	public static function meta_issues_meta_box( $post, $the_issue_terms )
	{
		// do not display if it's not assigned to any issue
		if ( ! count( $the_issue_terms ) )
			return;

		global $gEditorial;
		$fields = $gEditorial->meta->post_type_fields( $post->post_type );

		gEditorialHelper::meta_admin_field( 'in_issue_page_start', $fields, $post );
		gEditorialHelper::meta_admin_field( 'in_issue_order', $fields, $post );
		gEditorialHelper::meta_admin_field( 'in_issue_pages', $fields, $post );

		wp_nonce_field( 'geditorial_magazine_meta_post_raw', '_geditorial_magazine_meta_post_raw' );
	}

	public function post_updated_messages( $messages )
	{
		global $post, $post_ID;

		if ( $this->module->constants['issue_cpt'] == $post->post_type ) {
			$link = get_permalink( $post_ID );

			$messages[$this->module->constants['issue_cpt']] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Issue updated. <a href="%s">View issue</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => __( 'Issue updated.', GEDITORIAL_TEXTDOMAIN ),
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Issue restored to revision from %s', GEDITORIAL_TEXTDOMAIN ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => sprintf( __( 'Issue published. <a href="%s">View issue</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				7  => __( 'Issue saved.', GEDITORIAL_TEXTDOMAIN ),
				8  => sprintf( __( 'Issue submitted. <a target="_blank" href="%s">Preview issue</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
				9  => sprintf( __( 'Issue scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview issue</a>', GEDITORIAL_TEXTDOMAIN ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $link ) ),
				10 => sprintf( __( 'Issue draft updated. <a target="_blank" href="%s">Preview issue</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
			);
		}

 		return $messages;
	}

	public function tools_subs( $subs )
	{
		$subs['magazine'] = __( 'Magazine', GEDITORIAL_TEXTDOMAIN );
		return $subs;
	}

	public function tools_messages( $messages, $sub )
	{
		if ( 'magazine' == $sub ) {
			if ( isset( $_GET['count'] ) && $_GET['count'] )
				$messages['created'] = gEditorialHelper::notice(
					sprintf( __( '%s Issue Post(s) Created', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $_GET['count'] ) ), 'updated fade', false );
			else
				$messages['created'] = gEditorialHelper::notice(
					__( 'No Issue Post Created', GEDITORIAL_TEXTDOMAIN ), 'updated fade', false );
		}
		return $messages;
	}

	public function tools_sub( $settings_uri, $sub )
	{
		echo '<form method="post" action="">';
			echo '<h3>'.__( 'Magazine Tools', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'.__( 'Current Issues', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['issue_tax_check'] ) ) {

				$terms = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], false, true );

				foreach ( $terms as $term_id => $term ) {
					echo $term->name;
					$issue_post_id = gEditorialHelper::get_post_id_by_slug( $term->slug, $this->module->constants['issue_cpt'] ) ;
					if ( $issue_post_id ){
						echo ' :: ISSUE POST ID: '.$issue_post_id;
						echo ' :: POST COUNT: '.number_format_i18n( $this->issue_post_count( $issue_post_id ) );

					} else {
						echo ' :: NO ISSUE POST';
						echo ' :: POST COUNT: '.number_format_i18n( $term->count );
					}
					if ( $term->description )
						echo gEditorialHelper::html( 'p', array(
							'class' => 'description',
						), $term->description );
					echo '<br />';
				}

			} else {
				echo gEditorialHelper::html( 'p', array(
					'class' => 'description',
				), __( 'Click Check button below', GEDITORIAL_TEXTDOMAIN ) );
			}

			echo '</td></tr>';

			echo '<tr><th scope="row">'.__( 'From Terms', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				submit_button( __( 'Check', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_tax_check', false, array( 'default' => 'default' ) ); echo '&nbsp;&nbsp;';
				submit_button( __( 'Create', GEDITORIAL_TEXTDOMAIN ), 'primary', 'issue_post_create', false  ); //echo '&nbsp;&nbsp;';

				echo gEditorialHelper::html( 'p', array(
					'class' => 'description',
				), __( 'Check for new Issue taxs and create the corresponding Issue Posts.', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';

			wp_referer_field();
		echo '</form>';

	}

	public function tools_load( $sub )
	{
		if ( 'magazine' == $sub ) {
			if ( ! empty( $_POST ) ) {

				// check_admin_referer( 'geditorial_tools_'.$sub.'-options' );

				if ( isset( $_POST['issue_post_create'] ) ) {

					$terms = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], false, true );
					$posts = array();

					foreach ( $terms as $term_id => $term ) {
						$issue_post_id = gEditorialHelper::get_post_id_by_slug( $term->slug, $this->module->constants['issue_cpt'] ) ;
						if ( false === $issue_post_id ) {
							$posts[] = gEditorialHelper::newPostFromTerm( $term, $this->module->constants['issue_tax'], $this->module->constants['issue_cpt'] );

							break;
						}
					}

					wp_redirect( add_query_arg( array(
						'message' => 'created',
						'count' => count( $posts ),
					), wp_get_referer() ) );
					exit();
				}
			}
		}
	}
}

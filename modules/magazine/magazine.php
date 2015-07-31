<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazine extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'magazine';
	var $meta_key    = '_ge_magazine';

	var $post_id = FALSE; // current post id
	var $cookie  = 'geditorial-magazine';

	var $_post_types_excluded = array();

	var $_import      = FALSE;
	var $_term_suffix = 'gXmXaXg_';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Magazine', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Issue Management for Magazines', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Magazine suite for WordPress', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'book',
			'slug'                 => 'magazine',
			'load_frontend'        => TRUE,

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
				'enabled'     => FALSE,
				'post_types'  => array(),
				'post_fields' => array(),
				'settings'    => array(),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'multiple_issues',
						'title'       => __( 'Multiple Issues', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple issues for posts.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'redirect_archives',
						'type'        => 'text',
						'title'       => __( 'Redirect Archives', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Redirect Issue Archives to a Page', GEDITORIAL_TEXTDOMAIN ),
						'default'     => '',
						'dir'         => 'ltr',
					),
					array(
						'field'       => 'redirect_spans',
						'type'        => 'text',
						'title'       => __( 'Redirect Spans', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Redirect all Span Archives to a Page', GEDITORIAL_TEXTDOMAIN ),
						'default'     => '',
						'dir'         => 'ltr',
					),
				),
				'post_types_option' => 'post_types_option',
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
						'popular_items'              => NULL,
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
						'popular_items'              => NULL,
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
				'https://github.com/geminorum/geditorial/wiki/Modules-Magazine',
				__( 'Editorial Magazine Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );

		add_filter( 'geditorial_module_defaults_meta', array( &$this, 'module_defaults_meta' ), 10, 2 );
		add_filter( 'gpeople_remote_support_post_types', array( &$this, 'gpeople_remote_support_post_types' ) );
	}

	public function setup()
	{
		add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		require_once( GEDITORIAL_DIR.'modules/magazine/templates.php' );

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			add_filter( 'disable_months_dropdown', array( &$this, 'disable_months_dropdown' ), 8, 2 );
			add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
			add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
			add_filter( 'parse_query', array( &$this, 'parse_query_issues' ) );

		} else {
			add_filter( 'term_link', array( &$this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( &$this, 'template_redirect' ) );

			add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), 36 );

			add_action( 'gnetwork_debugbar_panel_geditorial_magazine', array( &$this, 'gnetwork_debugbar_panel' ) );
			add_filter( 'gnetwork_debugbar_panel_groups', function( $groups ){
				$groups['geditorial_magazine'] = __( 'gEditorial Magazine', GEDITORIAL_TEXTDOMAIN );
				return $groups;
			} );
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

		add_filter( 'wp_insert_post_data', array( &$this, 'wp_insert_post_data' ), 9, 2 );
		add_action( 'save_post_'.$this->module->constants['issue_cpt'], array( &$this, 'save_post_main_cpt' ), 20, 3 );
		add_action( 'post_updated', array( &$this, 'post_updated' ), 20, 3 );
		add_action( 'save_post', array( &$this, 'save_post_supported_cpt' ), 20, 3 );
		add_action( 'wp_trash_post', array( &$this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( &$this, 'untrash_post' ) );
		add_action( 'before_delete_post', array( &$this, 'before_delete_post' ) );

		// DISABLED
		// add_filter( 'pre_insert_term', array( &$this, 'pre_insert_term' ), 10, 2 );
		// add_action( 'import_start', array( &$this, 'import_start' ) );

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

	public function module_defaults_meta( $default_options, $mod_data )
	{
		$fields = $this->get_meta_fields();

		$default_options['post_types'][$this->module->constants['issue_cpt']] = TRUE;
		$default_options[$this->module->constants['issue_cpt'].'_fields'] = $fields[$this->module->constants['issue_cpt']];
		$default_options['post_fields'] = array_merge( $default_options['post_fields'], $fields['post'] );

		return $default_options;
	}

	// setup actions and filters for meta module
	public function meta_init( $meta_module )
	{
		add_filter( 'geditorial_meta_strings', array( &$this, 'meta_strings' ), 6, 1 );

		// add_filter( 'geditorial_meta_box_callback', array( &$this, 'meta_box_callback' ), 10, 2 );
		add_filter( 'geditorial_meta_dbx_callback', array( &$this, 'meta_dbx_callback' ), 10, 2 );
		add_filter( 'geditorial_meta_sanitize_post_meta', array( &$this, 'meta_sanitize_post_meta' ), 10 , 4 );

		add_action( 'geditorial_magazine_issue_meta_box', array( &$this, 'meta_issue_meta_box' ), 10, 1 );
		add_action( 'geditorial_magazine_issues_meta_box', array( &$this, 'meta_issues_meta_box' ), 10, 2 );
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

	public function register_post_types()
	{
		register_post_type( $this->module->constants['issue_cpt'], array(
			'labels'       => $this->module->strings['labels']['issue_cpt'],
			'hierarchical' => TRUE,
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
			'public'              => TRUE,
			'show_ui'             => TRUE,
			'show_in_menu'        => TRUE,
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-book',
			'show_in_nav_menus'   => TRUE,
			'publicly_queryable'  => TRUE,
			'exclude_from_search' => FALSE,
			'has_archive'         => $this->module->constants['issue_archives'],
			'query_var'           => $this->module->constants['issue_cpt'],
			'can_export'          => TRUE,
			'rewrite'             => array(
				'slug'       => $this->module->constants['issue_cpt'],
				'with_front' => FALSE
			),
			'map_meta_cap' => TRUE,
		) );
	}

	public function register_taxonomies()
	{
		register_taxonomy( $this->module->constants['issue_tax'], $this->post_types(), array(
			'labels'                => $this->module->strings['labels']['issue_tax'],
			'public'                => TRUE,
			'show_in_nav_menus'     => FALSE,
			'show_ui'               => gEditorialHelper::isDev(),
			'show_admin_column'     => FALSE,
			'show_tagcloud'         => FALSE,
			'hierarchical'          => TRUE,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['issue_tax'],
				'hierarchical' => TRUE,
				'with_front'   => TRUE
			),
			'query_var'    => TRUE,
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );

		register_taxonomy( $this->module->constants['span_tax'], array( $this->module->constants['issue_cpt'] ), array(
			'labels'                => $this->module->strings['labels']['span_tax'],
			'public'                => TRUE,
			'show_in_nav_menus'     => TRUE,
			'show_ui'               => TRUE,
			'show_admin_column'     => TRUE,
			'show_tagcloud'         => FALSE,
			'hierarchical'          => FALSE,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['span_tax'],
				'hierarchical' => FALSE,
				'with_front'   => TRUE
			),
			'query_var'    => TRUE,
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );
	}

	public function disable_months_dropdown( $false, $post_type )
	{
		if ( $this->module->constants['issue_cpt'] == $post_type )
			return TRUE;

		return $false;
	}

	// http://justintadlock.com/archives/2010/08/20/linking-terms-to-a-specific-post
	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->module->constants['issue_tax'] == $taxonomy ) {
			$post_id = '';

			// working but disabled
			//if ( function_exists( 'get_term_meta' ) )
				//$post_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', TRUE );

			if ( FALSE == $post_id || empty( $post_id ) )
				$post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['issue_cpt'] );

			if ( ! empty( $post_id ) )
				return get_permalink( $post_id );
		}

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->module->constants['issue_tax'] ) ) {

			$term = get_queried_object();
			if ( $post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['issue_cpt'] ) )
				self::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->module->constants['span_tax'] ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				self::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->module->constants['issue_cpt'] ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				self::redirect( $redirect, 301 );
		}
	}

	//	FIXME: OLD / REMOVE
	public function template_redirect_OLD()
	{
		if ( ! is_tax() )
			return;

		$term = get_queried_object();

		if ( $this->module->constants['issue_tax'] == $term->taxonomy ) {
			$post_id = '';

			// working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$post_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', TRUE );

			if ( FALSE == $post_id || empty( $post_id ) )
				$post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['issue_cpt'] );

			if ( ! empty( $post_id ) )
				wp_redirect( get_permalink( $post_id ), 301 );

		} else if ( $this->module->constants['span_tax'] == $term->taxonomy ) {

			$redirect = $this->get_setting( 'redirect_spans', FALSE );
			if ( $redirect )
				wp_redirect( $redirect, 301 );
		}
	}

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| 'revision' == $post_after->post_type )
				return $post_ID;

		if ( $this->module->constants['issue_cpt'] != $post_after->post_type
			|| 'trash' == $post_after->post_status )
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

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->module->constants['issue_tax'] );

		if ( FALSE === $the_term ){
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->module->constants['issue_tax'] );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->module->constants['issue_tax'], $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->module->constants['issue_tax'], $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->module->constants['issue_tax'], $args );
		}

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->module->constants['issue_cpt'].'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->module->constants['issue_cpt'].'_linked', $post_ID );
		}

		return $post_ID;
	}

	public function save_post_main_cpt( $post_ID, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( $update )
			return $post_ID;

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| $post->post_type == 'revision' )
				return $post_ID;

		if ( empty( $post->post_name ) )
			$post->post_name = sanitize_title( $post->post_title );

		$args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		$term = wp_insert_term( $post->post_title, $this->module->constants['issue_tax'], $args );

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->module->constants['issue_cpt'].'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->module->constants['issue_cpt'].'_linked', $post_ID );
		}

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		if ( $term = $this->get_issue_term( $post_id ) ) {
			wp_update_term( $term->term_id, $this->module->constants['issue_tax'], array(
				'name' => $term->name.' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ),
				'slug' => $term->slug.'-trashed',
			) );
		}
	}

	public function untrash_post( $post_id )
	{
		if ( $term = $this->get_issue_term( $post_id ) ) {
			wp_update_term( $term->term_id, $this->module->constants['issue_tax'], array(
				'name' => str_ireplace( ' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ), '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			) );
		}
	}

	public function before_delete_post( $post_id )
	{
		if ( $term = $this->get_issue_term( $post_id ) ) {
			wp_delete_term( $term->term_id, $this->module->constants['issue_tax'] );
			delete_metadata( 'term', $term->term_id, $this->module->constants['issue_cpt'].'_linked' );
		}
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->module->constants['issue_cpt'] == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = gEditorialHelper::getLastPostOrder( $this->module->constants['issue_cpt'],
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	// helper
	public function get_issue_term( $post_id )
	{
		$term_id = get_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', TRUE );
		return get_term_by( 'id', intval( $term_id ), $this->module->constants['issue_tax'] );
	}

	// OLD
	public function save_post_main_cpt_OLD( $post_id, $post, $update )
	{
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| empty( $_POST )
			|| $post->post_type == 'revision' )
				return $post_id;

		if ( empty( $post->post_name ) )
			return $post_id;

		// TODO: issue parent
		// if ( $post->post_parent ) {
		//     $parent_post = get_post( $post->post_parent, ARRAY_A );
		//     $parent_term = term_exists( $parent_post['post_name'], $this->_issue_tax );
		//     if ( FALSE == $parent_term ) {
		//         // TODO: update term parent
		//     }
		// }

		// FIXME: check if it's working : probably the importer will add the tax too!
		// FIXME: how about term_id of not-yet-created issue tax!?
		if ( $this->_import )
			return $post_id;

		$term           = get_term_by( 'slug', $post->post_name, $this->module->constants['issue_tax'] );
		$pre_meta_issue = get_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', TRUE );

		$args = array(
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		);

		if ( FALSE === $term ) {
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

	// DISABLED
	public function import_start()
	{
		$this->_import = TRUE;
	}

	// DISABLED
	// note that, only admins can insert tax manually, others must create corresponding post type first.
	public function pre_insert_term( $term, $taxonomy )
	{
		//if ( $this->module->constants['issue_tax'] == $taxonomy && ( ! current_user_can( 'edit_theme_options' ) ) )
		if ( $this->module->constants['issue_tax'] != $taxonomy )
			return $term;

		if ( $this->_import )
			return $term;

		if ( FALSE === strpos( $term, $this->_term_suffix ) )
			return new WP_Error( 'not_authenticated', __( 'you\'re doing it wrong!', GEDITORIAL_TEXTDOMAIN ) );

		return str_ireplace( $this->_term_suffix, '', $term );
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| $post->post_type == 'revision' )
				return $post_ID;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $post_ID;

		// if ( isset( $_POST['geditorial_magazine_issue_terms'] ) ) {
		if ( isset( $_POST['geditorial-magazine-issue'] ) ) {
			$terms = array();

			// foreach( $_POST['geditorial_magazine_issue_terms'] as $term_id )
			// 	if ( $term_id )
			// 		$terms[] = intval( $term_id );

			foreach( $_POST['geditorial-magazine-issue'] as $issue ) {
				if ( trim( $issue ) ) {
					$term = get_term_by( 'slug', $issue, $this->module->constants['issue_tax'] );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$terms[] = intval( $term->term_id );
				}
			}

			wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $this->module->constants['issue_tax'], FALSE );
		}

		return $post_ID;
	}

	public function after_setup_theme()
	{
		//add_theme_support( 'post-thumbnails', array( $this->module->constants['issue_cpt'] ) );
		self::themeThumbnails( array( $this->module->constants['issue_cpt'] ) );

		foreach( $this->get_image_sizes() as $name => $size )
			self::addImageSize( $name, $size['w'], $size['h'], $size['c'], array( $this->module->constants['issue_cpt'] ) );
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
		if ( $wp_query->is_admin
			&& isset( $wp_query->query['post_type'] ) ) {

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
		$post_type = gEditorialHelper::get_current_post_type();

		if ( in_array( $post_type, $this->post_types() ) ) {

			$issue_tax = $this->module->constants['issue_tax'];
			$tax_obj   = get_taxonomy( $issue_tax );

			wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['issue_cpt'],
				'selected'         => isset( $_GET[$issue_tax] ) ? $_GET[$issue_tax] : '',
				'name'             => $issue_tax,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => $tax_obj->labels->all_items,
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

		} else if ( $this->module->constants['issue_cpt'] == $post_type ) {

			$span_tax = $this->module->constants['span_tax'];
			$tax_obj   = get_taxonomy( $span_tax );

			wp_dropdown_categories( array(
				'show_option_all' => $tax_obj->labels->all_items,
				'taxonomy'        => $span_tax,
				'name'            => $tax_obj->name,
				// 'orderby'         => 'slug',
				'order'           => 'DESC',
				'selected'        => isset( $_GET[$span_tax] ) ? $_GET[$span_tax] : 0,
				'hierarchical'    => $tax_obj->hierarchical,
				'show_count'      => FALSE,
				'hide_empty'      => TRUE,
			) );
		}
	}

	public function parse_query_issues( $query )
	{
		// FIXME: ?!!
		if ( ! is_object( $query ) )
			error_log( print_r( $query, TRUE ) );

		if ( $query->is_admin && 'edit' == get_current_screen()->base ) {
			$span_tax = $this->module->constants['span_tax'];
			if ( isset( $query->query_vars[$span_tax] ) ) {
				$var = &$query->query_vars[$span_tax];
				$span = get_term_by( 'id', $var, $span_tax );
				if ( ! empty( $span ) && ! is_wp_error( $span ) )
					$var = $span->slug;
			}
		}
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( in_array( $post_type, $this->post_types() ) ) {

			$url = add_query_arg( 'post_type', $this->module->constants['issue_cpt'], get_admin_url( NULL, 'edit.php' ) );
			add_meta_box( 'geditorial-magazine-issues',
				$this->get_meta_box_title( $post_type, $url ),
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
		$issues = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post->ID, TRUE );
		do_action( 'geditorial_magazine_issues_meta_box', $post, $issues );
		echo '</div>';
	}

	public function issues_meta_box( $post, $terms )
	{
		$dropdowns = $excludes = array();

		foreach ( $terms as $term ) {

			$dropdowns[$term->slug] = wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['issue_cpt'],
				'selected'         => $term->slug,
				'name'             => 'geditorial-magazine-issue[]',
				'id'               => 'geditorial-magazine-issue-'.$term->slug,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => __( '&mdash; Select an Issue &mdash;', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

			$excludes[] = $term->slug;
		}

		if ( ! count( $terms ) || $this->get_setting( 'multiple_issues', FALSE ) ) {
			$dropdowns[0] = wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['issue_cpt'],
				'selected'         => '',
				'name'             => 'geditorial-magazine-issue[]',
				'id'               => 'geditorial-magazine-issue-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => __( '&mdash; Select an Issue &mdash;', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'exclude'          => $excludes,
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));
		}

		foreach( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo '<div class="field-wrap">';
					echo $dropdown;
				echo '</div>';
			}
		}
	}

	// OLD
	public function issues_meta_box_OLD( $post, $the_issue_terms )
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
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'exclude_tree'     => $post->ID,
				'echo'             => 0,
			));
			if ( $pages )
				echo gEditorialHelper::html( 'div', array(
					'class' => 'field-wrap',
				), $pages );
		}

		$term_id = get_post_meta( $post->ID, '_'.$this->module->constants['issue_cpt'].'_term_id', TRUE );
		echo gEditorialHelper::getTermPosts( $this->module->constants['issue_tax'], intval( $term_id ) );

		echo '</div>';
	}

	public function get_issue_post( $post_ID = NULL )
	{
		$post_ID = ( NULL === $post_ID ) ? get_the_ID() : $post_ID;

		$terms = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post_ID, TRUE );
		if ( ! count( $terms ) )
			return FALSE;

		$the_id = FALSE;
		$ids = array();
		foreach ( $terms as $term ) {
			// working but disabled
			//if ( function_exists( 'get_term_meta' ) )
				//$the_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', TRUE );

			if ( FALSE == $the_id || empty( $the_id ) )
				$the_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['issue_cpt'] );

			if ( FALSE != $the_id && ! empty( $the_id ) ) {
				$status = get_post_status( $the_id );
				if ( 'publish' == $status )
					$ids[$the_id] = get_permalink( $the_id );
				else
					$ids[$the_id] = FALSE;
			}
		}

		if ( ! count( $ids ) )
			return FALSE;
		return $ids;
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		foreach( $posts_columns as $key => $value ) {
			if ( $key == 'title' ) {
				$new_columns['issue_order'] = $this->get_string( 'order_column_title', NULL, 'misc' );
				$new_columns['cover'] = $this->get_string( 'cover_column_title', NULL, 'misc' );
				$new_columns[$key] = $value;
			} else if ( 'author' == $key ){
				// $new_columns[$key] = $value;
			} else if ( 'comments' == $key ){
				$new_columns['issue_posts'] = $this->get_string( 'posts_column_title', NULL, 'misc' );
				$new_columns[$key] = $value;
			} else {
				$new_columns[$key] = $value;
			}
		}
		return $new_columns;
	}

	public function issue_posts( $post_id, $count = FALSE, $term_id = NULL )
	{
		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', TRUE );

		$items = get_posts( array(
			'tax_query' => array( array(
				'taxonomy' => $this->module->constants['issue_tax'],
				'field' => 'id',
				'terms' => array( $term_id )
			) ),
			'post_type' => $this->post_types(),
			'numberposts' => -1,
		) );

		if ( $count )
			return count( $items );

		return $items;
	}

	public function custom_column( $column_name, $post_id )
	{
		if ( 'issue_posts' == $column_name ) {

			$count = $this->issue_posts( $post_id, TRUE );
			if ( $count )
				echo number_format_i18n( $count );
			else
				_e( '<span title="No Posts">&mdash;</span>', GEDITORIAL_TEXTDOMAIN );


		} else if ( 'XSVWSVWVWV' == $column_name ) { // disabled!
			$issues = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post_id, TRUE );
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
			$cover = gEditorialHelper::getFeaturedImage( $post_id, 'issue-thumbnail', FALSE );
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
				'ot'                => FALSE,
				'st'                => TRUE,
				'issue_number_line' => TRUE,
				'issue_total_pages' => TRUE,
			 ),
			'post' => array(
				'in_issue_order'      => TRUE,
				'in_issue_page_start' => TRUE,
				'in_issue_pages'      => FALSE,
			),
		);
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
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Issue restored to revision from %s', GEDITORIAL_TEXTDOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
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
					sprintf( __( '%s Issue Post(s) Created', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $_GET['count'] ) ), 'updated fade', FALSE );
			else
				$messages['created'] = gEditorialHelper::notice(
					__( 'No Issue Post Created', GEDITORIAL_TEXTDOMAIN ), 'updated fade', FALSE );
		}
		return $messages;
	}

	public function tools_sub( $settings_uri, $sub )
	{
		echo '<form method="post" action="">';
			echo '<h3>'.__( 'Magazine Tools', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'.__( 'From Terms', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			if ( ! empty( $_POST ) && isset( $_POST['issue_tax_check'] ) ) {

				gEditorialHelper::table( array(
					'_cb' => 'term_id',
					'term_id' => __( 'ID', GEDITORIAL_TEXTDOMAIN ),
					'name' => __( 'Name', GEDITORIAL_TEXTDOMAIN ),
					'issue' => array(
						'title' => __( 'Issue', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column ){
							if ( $post_id = gEditorialHelper::getPostIDbySlug( $row->slug, $this->module->constants['issue_cpt'] ) )
								return $post_id.' &mdash; '.get_post($post_id)->post_title;
							return __( '&mdash;&mdash;&mdash;&mdash; No Issue', GEDITORIAL_TEXTDOMAIN );
						},
					),
					'count' => array(
						'title' => __( 'Count', GEDITORIAL_TEXTDOMAIN ),
						'callback' => function( $value, $row, $column ){
							if ( $post_id = gEditorialHelper::getPostIDbySlug( $row->slug, $this->module->constants['issue_cpt'] ) )
								return number_format_i18n( $this->issue_posts( $post_id, TRUE ) );
							return number_format_i18n( $row->count );
						},
					),
					'description' => array(
						'title'    => __( 'Description', GEDITORIAL_TEXTDOMAIN ),
						'callback' => 'wpautop',
						'class'    => 'description',
					),
				), gEditorialHelper::getTerms( $this->module->constants['issue_tax'], FALSE, TRUE ) );

				echo '<br />';
			}
				submit_button( __( 'Check Terms', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_tax_check', FALSE, array( 'default' => 'default' ) ); echo '&nbsp;&nbsp;';
				submit_button( __( 'Create Issue', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_post_create', FALSE  ); echo '&nbsp;&nbsp;';
				submit_button( __( 'Store Orders', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'issue_store_order', FALSE  ); //echo '&nbsp;&nbsp;';

				echo gEditorialHelper::html( 'p', array(
					'class' => 'description',
				), __( 'Check for Issue terms and Create corresponding Issue posts.', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';

			wp_referer_field();
		echo '</form>';
	}

	public function post_row_table( $value, $row, $column )
	{
		$issue_post_id = gEditorialHelper::getPostIDbySlug( $row->slug, $this->module->constants['issue_cpt'] ) ;
		gnetwork_dump( $value ); die();
		return $value;
	}

	public function tools_load( $sub )
	{
		global $gEditorial;

		if ( 'magazine' == $sub ) {
			if ( ! empty( $_POST ) ) {

				// check_admin_referer( 'geditorial_tools_'.$sub.'-options' );

				if ( isset( $_POST['issue_post_create'] ) ) {

					// FIXME: get term_id list from table checkbox

					$terms = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], FALSE, TRUE );
					$posts = array();

					foreach ( $terms as $term_id => $term ) {
						$issue_post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['issue_cpt'] ) ;
						if ( FALSE === $issue_post_id )
							$posts[] = gEditorialHelper::newPostFromTerm( $term, $this->module->constants['issue_tax'], $this->module->constants['issue_cpt'] );
					}

					wp_redirect( add_query_arg( array(
						'message' => 'created',
						'count'   => count( $posts ),
					), wp_get_referer() ) );
					exit();

				} else if ( isset( $_POST['_cb'] )
					&& ( isset( $_POST['issue_store_order'] )
						|| isset( $_POST['issue_store_start'] ) ) ) {

					$meta_key = isset( $_POST['issue_store_order'] ) ? 'in_issue_order' : 'in_issue_page_start';
					$count = 0;

					foreach ( $_POST['_cb'] as $term_id ) {
						foreach( $this->issue_posts( NULL, FALSE, $term_id ) as $post ) {

							// echo $post->ID;
							// echo '--';
							// echo $post->menu_order;
							// echo '--';
							// echo $gEditorial->meta->get_postmeta( $post->ID, 'in_issue_order', 'XX' );
							// echo '--';
							// echo $gEditorial->meta->get_postmeta( $post->ID, 'in_issue_page_start', 'YY' );
							// echo '<br />';
							// continue;

							if ( $post->menu_order )
								continue;

							if ( $order = $gEditorial->meta->get_postmeta( $post->ID, $meta_key, FALSE ) ) {
								wp_update_post( array(
									'ID'         => $post->ID,
									'menu_order' => $order,
								) );
								$count++;
							}
						}
					}

					wp_redirect( add_query_arg( array(
						'message' => 'ordered',
						'count'   => $count,
					), wp_get_referer() ) );
					exit();
				}
			}
		}
	}

	// NO NEED: we can use gNetwork Debug: Meta Panel
	public function gnetwork_debugbar_panel()
	{
		if ( ! is_singular() )
			return;

		global $gEditorial;

		$post_id = get_the_ID();

		echo 'ISSUE: term_id: '.get_post_meta( $post_id, '_'.$this->module->constants['issue_cpt'].'_term_id', TRUE );
		echo '<br />';
		echo 'META: in_issue_order: '.$gEditorial->meta->get_postmeta( $post_id, 'in_issue_order', 'NOT DEFINDED' );
		echo '<br />';
		echo 'META: in_issue_page_start: '.$gEditorial->meta->get_postmeta( $post_id, 'in_issue_page_start', 'NOT DEFINDED' );
	}
}

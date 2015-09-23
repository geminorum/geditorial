<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazine extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'magazine';
	var $meta_key    = '_ge_magazine';
	var $_root_key   = 'GEDITORIAL_MAGAZINE_ROOT_BLOG';

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
				'issue_cpt'         => 'issue',
				'issue_cpt_archive' => 'issues',
				'issue_cpt_p2p'     => 'related_issues',

				'issue_tax'         => 'issues',
				'span_tax'          => 'span',
				'issue_shortcode'   => 'issue',
				'span_shortcode'    => 'span',
			),

			'supports' => array(
				'issue_cpt' => array(
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
				'titles'       => array(),
				'descriptions' => array(),

				'misc' => array(
					'issue_cpt' => array(
						'cover_column_title'    => _x( 'Cover', '[Magazine Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
						'order_column_title'    => _x( 'O', '[Magazine Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
						'children_column_title' => _x( 'Posts', '[Magazine Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
					),
					'meta_box_title'        => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
					'issue_box_title'       => __( 'The Issue', GEDITORIAL_TEXTDOMAIN ),
					'cover_box_title'       => __( 'Cover', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'issue_cpt' => array(
						'name'               => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => __( 'Issues', GEDITORIAL_TEXTDOMAIN ),
						// 'description' => '',
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
				'p2p' => array(
					'issue_cpt' => array(
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
						),
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

		$this->require_code();

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
		add_action( 'p2p_init', array( &$this, 'p2p_init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			add_filter( 'disable_months_dropdown', array( &$this, 'disable_months_dropdown' ), 8, 2 );
			add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
			add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
			add_filter( 'parse_query', array( &$this, 'parse_query' ) );

		} else {
			add_filter( 'term_link', array( &$this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( &$this, 'template_redirect' ) );

			// add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), 36 );
		}

		add_action( 'split_shared_term', array( &$this, 'split_shared_term' ), 10, 4 );

		// WHAT ABOUT : constant filters
		$this->_post_types_excluded = array( $this->module->constants['issue_cpt'] );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'issue_cpt' );
	}

	public function p2p_init()
	{
		$this->register_p2p( 'issue_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_magazine_init', $this->module );

		$this->do_filters();

		$this->register_post_type( 'issue_cpt', array(
			'hierarchical'  => TRUE,
			'menu_position' => 4,
		), array( $this->module->constants['issue_tax'] ) );

		$this->register_taxonomy( 'issue_tax', array(
			'show_ui'           => gEditorialHelper::isDev(),
			'hierarchical'      => TRUE,
			'show_admin_column' => TRUE,
		) );

		$this->register_taxonomy( 'span_tax', array(
			'show_admin_column' => TRUE,
		), 'issue_cpt' );

		$this->register_shortcode( 'issue_shortcode', array( 'gEditorialMagazineTemplates', 'issue_shortcode' ) );
		$this->register_shortcode( 'span_shortcode', array( 'gEditorialMagazineTemplates', 'span_shortcode' ) );
	}

	public function admin_init()
	{
		if ( current_user_can( 'edit_others_posts' ) )
			add_action( 'geditorial_tools_settings', array( &$this, 'tools_settings' ) );

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

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 20, 2 );
		add_filter( "manage_{$this->module->constants['issue_cpt']}_posts_columns", array( &$this, 'manage_posts_columns' ) );
		add_filter( "manage_{$this->module->constants['issue_cpt']}_posts_custom_column", array( &$this, 'posts_custom_column'), 10, 2 );
		add_filter( "manage_edit-{$this->module->constants['issue_cpt']}_sortable_columns", array( &$this, 'sortable_columns' ) );

		// internal actions:
		add_action( 'geditorial_magazine_issues_meta_box', array( &$this, 'issues_meta_box' ), 5, 2 );
	}

	public function widgets_init()
	{
		$this->require_code( 'widgets' );

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
				$this->module->constants['span_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['span_tax'],
					'dashicon'   => 'backup',
					'title_attr' => $this->get_string( 'name', 'span_tax', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function disable_months_dropdown( $false, $post_type )
	{
		if ( $this->module->constants['issue_cpt'] == $post_type )
			return TRUE;

		return $false;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->module->constants['issue_tax'] == $taxonomy ) {
			$post_id = '';

			// working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$post_id = get_term_meta( $term->term_id, $this->module->constants['issue_cpt'].'_linked', TRUE );

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

	public function post_updated( $post_ID, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'issue_cpt' ) )
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
		if ( ! $this->is_save_post( $post ) )
			return $post_ID;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $post_ID;

		if ( isset( $_POST['geditorial-magazine-issue'] ) ) {
			$terms = array();

			foreach ( $_POST['geditorial-magazine-issue'] as $issue ) {
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

	// DISABLED
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
		$post_type = gEditorialHelper::getCurrentPostType();

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

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'span_tax',
		), 'issue_cpt' );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->module->constants['issue_cpt'] ) {

			// remove post parent meta box
			remove_meta_box( 'pageparentdiv', $post_type, 'side' );
			add_meta_box( 'geditorial-magazine-issue',
				$this->get_string( 'issue_box_title', $post_type, 'misc' ), // FIXME: use get_meta_box_title()
				array( &$this, 'do_meta_box_issue' ),
				$post_type,
				'side',
				'high'
			);

			remove_meta_box( 'postimagediv', $this->module->constants['issue_cpt'], 'side' );
			add_meta_box( 'postimagediv',
				$this->get_string( 'cover_box_title', $post_type, 'misc' ), // FIXME: use get_meta_box_title()
				'post_thumbnail_meta_box',
				$this->module->constants['issue_cpt'],
				'side',
				'high'
			);

		} else if ( in_array( $post_type, $this->post_types() ) ) {

			add_meta_box( 'geditorial-magazine-issues',
				$this->get_meta_box_title( 'post', $this->get_url_post_edit( 'issue_cpt' ) ),
				array( &$this, 'do_meta_box_issues' ),
				$post_type,
				'side'
			);

			// TODO : add a box to list the posts with this issue taxonomy
		}
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

		foreach ( $dropdowns as $term_slug => $dropdown ) {
			if ( $dropdown ) {
				echo '<div class="field-wrap">';
					echo $dropdown;
				echo '</div>';
			}
		}
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

		if ( get_post_type_object( $this->module->constants['issue_cpt'] )->hierarchical )
			$this->field_post_parent( 'issue_cpt', $post );

		$term_id = get_post_meta( $post->ID, '_'.$this->module->constants['issue_cpt'].'_term_id', TRUE );
		echo gEditorialHelper::getTermPosts( $this->module->constants['issue_tax'], intval( $term_id ) );

		echo '</div>';
	}

	public function get_issue_post( $post_id = NULL, $single = FALSE )
	{
		if ( is_null( $post_id ) )
			$post_id = get_the_ID();

		$terms = gEditorialHelper::getTerms( $this->module->constants['issue_tax'], $post_id, TRUE );
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

				if ( $single )
					return $the_id;

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
		foreach ( $posts_columns as $key => $value ) {

			if ( $key == 'title' ) {
				$new_columns['order'] = $this->get_column_title( 'order', 'issue_cpt' );
				$new_columns['cover'] = $this->get_column_title( 'cover', 'issue_cpt' );
				$new_columns[$key] = $value;

			} else if ( 'comments' == $key ){
				$new_columns['children'] = $this->get_column_title( 'children', 'issue_cpt' );

			} else if ( in_array( $key, array( 'author', 'date' ) ) ) {
				continue; // he he!

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
				'field'    => 'id',
				'terms'    => array( $term_id )
			) ),
			'post_type'   => $this->post_types(),
			'numberposts' => -1,
		) );

		if ( $count )
			return count( $items );

		return $items;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'children' == $column_name )
			$this->column_count( $this->issue_posts( $post_id, TRUE ) );

		else if ( 'order' == $column_name )
			$this->column_count( get_post( $post_id )->menu_order );

		else if ( 'cover' == $column_name )
			$this->column_thumb( $post_id );
	}

	public function sortable_columns( $columns )
	{
		$columns['order'] = 'menu_order';
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
						$this->set_postmeta_field_string( $postmeta, $field );
				}
			}

		} else if ( in_array( $post_type, $this->post_types() )
			&& wp_verify_nonce( @$_REQUEST['_geditorial_magazine_meta_post_raw'], 'geditorial_magazine_meta_post_raw' )  ) {

			foreach ( $fields[$post_type] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'in_issue_order' :
					case 'in_issue_page_start' :
						$this->set_postmeta_field_number( $postmeta, $field );
					break;
					case 'in_issue_pages' :
						$this->set_postmeta_field_string( $postmeta, $field );
				}
			}
		}

		return $postmeta;
	}

	// on issue
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

			$this->tools_field_referer( $sub );

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
		echo '</form>';
	}

	public function post_row_table( $value, $row, $column )
	{
		$issue_post_id = gEditorialHelper::getPostIDbySlug( $row->slug, $this->module->constants['issue_cpt'] ) ;
		gnetwork_dump( $value ); die();
		return $value;
	}

	public function tools_settings( $sub )
	{
		global $gEditorial;

		if ( 'magazine' == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->tools_check_referer( $sub );

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
						foreach ( $this->issue_posts( NULL, FALSE, $term_id ) as $post ) {

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

			add_filter( 'geditorial_tools_messages', array( &$this, 'tools_messages' ), 10, 2 );
			add_action( 'geditorial_tools_sub_magazine', array( &$this, 'tools_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_tools_subs', array( &$this, 'tools_subs' ) );
	}
}

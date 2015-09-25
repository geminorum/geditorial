<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialContest extends gEditorialModuleCore
{

	var $module_name = 'contest';

	public function __construct()
	{
		global $gEditorial;

		$args = array(

			'title'                => __( 'Contest', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Contest Management', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Set of tools to create and manage text contests and/or gather assignments', GEDITORIAL_TEXTDOMAIN ),

			'dashicon' => 'megaphone',
			'slug'     => 'contest',
			'frontend' => TRUE,

			'constants' => array(
				'contest_cpt'         => 'contest',
				'contest_cpt_archive' => 'contests',
				'apply_cpt'           => 'apply',
				'apply_cpt_archive'   => 'applies',

				'contest_cat'      => 'contest_cat',
				'contest_tax'      => 'contests',
				'apply_cat'        => 'apply_cat',
				'apply_status_tax' => 'apply_status',
			),

			'supports' => array(
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
			),

			'default_options' => array(
				'enabled' => FALSE,
				'post_types' => array(
					'post'  => FALSE,
					'page'  => FALSE,
					'apply' => TRUE,
				),
				'post_fields' => array(
					'post_title'   => TRUE,
					'post_content' => TRUE,
					'post_author'  => TRUE,
				),
				'settings' => array(),
			),

			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'multiple_contests',
						'title'       => __( 'Multiple Contests', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple contests for appplies.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'redirect_archives',
						'type'        => 'text',
						'title'       => __( 'Redirect Archives', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Redirect Contest & Apply Archives to a URL', GEDITORIAL_TEXTDOMAIN ),
						'default'     => '',
						'dir'         => 'ltr',
					),
				),
				'post_types_option' => 'post_types_option',
			),

			'strings' => array(
				'titles'       => array(),
				'descriptions' => array(),
				'misc'         => array(
					'contest_cpt' => array(
						'meta_box_title'  => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
						'cover_box_title' => __( 'Poster', GEDITORIAL_TEXTDOMAIN ),

						'cover_column_title'    => _x( 'Poster', '[Contest Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
						'order_column_title'    => _x( 'O', '[Contest Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
						'children_column_title' => _x( 'Applies', '[Contest Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
					),
					'apply_status_tax' => array(
						'meta_box_title' => __( 'Apply Statuses', GEDITORIAL_TEXTDOMAIN ),
					),
					'meta_box_title'    => __( 'Contests', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'contest_cpt' => array(
						'name'                  => _x( 'Contests', 'Contest CPT Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'             => _x( 'Contests', 'Contest CPT Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'description'           => _x( 'Contest Post Type', 'Contest CPT Description', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'         => _x( 'Contest', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new'               => _x( 'Add New', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'          => _x( 'Add New Contest', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'             => _x( 'Edit Contest', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item'              => _x( 'New Contest', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'view_item'             => _x( 'View Contest', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'          => _x( 'Search Contests', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'not_found'             => _x( 'No contests found', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash'    => _x( 'No contests found in Trash', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'     => _x( 'Parent Contest:', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'featured_image'        => _x( 'Poster Image', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'set_featured_image'    => _x( 'Set poster image', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'remove_featured_image' => _x( 'Remove poster image', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'use_featured_image'    => _x( 'Use as poster image', 'Contest CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					),
					'apply_cpt' => array(
						'name'                  => _x( 'Applies', 'Apply CPT Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'             => _x( 'Applies', 'Apply CPT Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'description'           => _x( 'Apply Post Type', 'Apply CPT Description', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'         => _x( 'Apply', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new'               => _x( 'Add New', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'          => _x( 'Add New Apply', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'             => _x( 'Edit Apply', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item'              => _x( 'New Apply', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'view_item'             => _x( 'View Apply', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'          => _x( 'Search Applies', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'not_found'             => _x( 'No applies found', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash'    => _x( 'No applies found in Trash', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'     => _x( 'Parent Apply:', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'featured_image'        => _x( 'Cover Image', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'set_featured_image'    => _x( 'Set cover image', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'remove_featured_image' => _x( 'Remove cover image', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'use_featured_image'    => _x( 'Use as cover image', 'Apply CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					),
					'contest_cat' => array(
						'name'                       => _x( 'Contest Category', 'Contest Category Taxonomy Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Contest Categories', 'Contest Category Taxonomy Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Contest Category', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Contest Categories', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Contest Categories', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Contest Category', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Contest Category:', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Contest Category', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Contest Category', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Contest Category', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Contest Category', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate contest categories with commas', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove contest categories', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used contest categories', 'Contest Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'contest_tax' => array(
						'name'                       => _x( 'Contests', 'Contest Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Contests', 'Contest Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Contest', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Contests', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Contests', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Contest', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Contest:', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Contest', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Contest', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Contest', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Contest', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate contests with commas', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove Contests', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used Contests', 'Contest Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'apply_cat' => array(
						'name'                       => _x( 'Apply Category', 'Apply Category Taxonomy Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Apply Categories', 'Apply Category Taxonomy Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Apply Category', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Apply Categories', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Apply Categories', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Apply Category', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Apply Category:', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Apply Category', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Apply Category', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Apply Category', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Apply Category', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate apply categories with commas', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove apply categories', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used apply categories', 'Apply Category Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'apply_status_tax' => array(
						'name'                       => _x( 'Apply Statuses', 'Apply Status Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Apply Statuses', 'Apply Status Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Apply Status', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Apply Statuses', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Apply Statuses', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Apply Status', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Apply Status:', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Apply Status', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Apply Status', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Apply Status', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Apply Status', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate apply statuses with commas', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove Apply Statuses', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used Apply Statuses', 'Apply Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
				),
				'terms' => array(
					'apply_status_tax' => array(
						'approved' => _x( 'Approved', 'Apply Statuses Tax Defaults', GEDITORIAL_TEXTDOMAIN ), // vaziri
						'pending'  => _x( 'Pending', 'Apply Statuses Tax Defaults', GEDITORIAL_TEXTDOMAIN ), // soltani
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
		);

		$gEditorial->register_module( $this->module_name, $args );

		add_filter( 'geditorial_module_defaults_meta', array( &$this, 'module_defaults_meta' ), 10, 2 );
		add_filter( 'gpeople_remote_support_post_types', array( &$this, 'gpeople_remote_support_post_types' ) );
	}

	public function setup()
	{
		// add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {

			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			// add_filter( 'disable_months_dropdown', array( &$this, 'disable_months_dropdown' ), 8, 2 );
			// add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
			// add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
			// add_filter( 'parse_query', array( &$this, 'parse_query' ) );

		} else {

			add_filter( 'term_link', array( &$this, 'term_link' ), 10, 3 );
			add_action( 'template_redirect', array( &$this, 'template_redirect' ) );
		}

		add_action( 'split_shared_term', array( &$this, 'split_shared_term' ), 10, 4 );

		$this->_post_types_excluded = array( $this->module->constants['contest_cpt'] );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'contest_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_contest_init', $this->module );

		$this->do_filters();

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
			'show_ui'           => gEditorialHelper::isDev(),
			'hierarchical'      => TRUE,
			'show_admin_column' => TRUE,
		) );

		$this->register_taxonomy( 'apply_cat', array(
			'show_admin_column' => TRUE,
			'hierarchical'      => TRUE,
		), 'apply_cpt' );

		$this->register_taxonomy( 'apply_status_tax', array(
			'show_admin_column' => TRUE,
		), 'apply_cpt' );
	}

	public function admin_init()
	{
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		add_filter( 'wp_insert_post_data', array( &$this, 'wp_insert_post_data' ), 9, 2 );

		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 20, 2 );
		add_action( 'save_post_'.$this->module->constants['contest_cpt'], array( &$this, 'save_post_main_cpt' ), 20, 3 );
		add_action( 'post_updated', array( &$this, 'post_updated' ), 20, 3 );
		add_action( 'save_post', array( &$this, 'save_post_supported_cpt' ), 20, 3 );

		add_action( 'wp_trash_post', array( &$this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( &$this, 'untrash_post' ) );
		add_action( 'before_delete_post', array( &$this, 'before_delete_post' ) );

		add_filter( "manage_{$this->module->constants['contest_cpt']}_posts_columns", array( &$this, 'manage_posts_columns' ) );
		add_filter( "manage_{$this->module->constants['contest_cpt']}_posts_custom_column", array( &$this, 'posts_custom_column'), 10, 2 );
		add_filter( "manage_edit-{$this->module->constants['contest_cpt']}_sortable_columns", array( &$this, 'sortable_columns' ) );

		// internal actions:
		add_action( 'geditorial_contest_supported_meta_box', array( &$this, 'supported_meta_box' ), 5, 2 );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_apply_status_tax'] ) )
			$this->insert_default_terms( 'apply_status_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_apply_status_tax', __( 'Install Default Apply Statuses', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function module_defaults_meta( $default_options, $mod_data )
	{
		$default_options[$this->module->constants['contest_cpt'].'_fields'] = $default_options['post_fields'];
		$default_options[$this->module->constants['apply_cpt'].'_fields'] = $default_options['post_fields'];

		unset( $default_options[$this->module->constants['contest_cpt'].'_fields']['as'] ); // no author for contests

		return $default_options;
	}

	// DISABLED
	public function meta_init( $meta_module )
	{
		// NO NEED: unless we have our own meta fields
		// add_filter( 'geditorial_meta_sanitize_post_meta', array( &$this, 'meta_sanitize_post_meta' ), 10 , 4 );

		// NO NEED: unless we want to integrate meta fields on our own box
		// add_action( 'geditorial_contest_main_meta_box', array( &$this, 'meta_main_meta_box' ), 10, 1 );
		// add_action( 'geditorial_contest_supported_meta_box', array( &$this, 'meta_supported_meta_box' ), 10, 2 );
	}

	public function gpeople_remote_support_post_types( $post_types )
	{
		return array_merge( $post_types, array(
			$this->module->constants['contest_cpt'],
			$this->module->constants['apply_cpt'],
		) );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->module->constants['contest_cat'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['contest_cat'],
					'dashicon'   => 'category',
					'title_attr' => $this->get_string( 'name', 'contest_cat', 'labels' ),
				),
				$this->module->constants['contest_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['contest_tax'],
					'dashicon'   => 'megaphone',
					'title_attr' => $this->get_string( 'name', 'contest_tax', 'labels' ),
				),
				$this->module->constants['apply_cat'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['apply_cat'],
					'dashicon'   => 'category',
					'title_attr' => $this->get_string( 'name', 'apply_cat', 'labels' ),
				),
				$this->module->constants['apply_status_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['apply_status_tax'],
					'dashicon'   => 'portfolio',
					'title_attr' => $this->get_string( 'name', 'apply_status_tax', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->module->constants['contest_tax'] == $taxonomy ) {
			$post_id = '';

			// working but disabled
			// if ( function_exists( 'get_term_meta' ) )
			// 	$post_id = get_term_meta( $term->term_id, $this->module->constants['contest_cpt'].'_linked', TRUE );

			if ( FALSE == $post_id || empty( $post_id ) )
				$post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['contest_cpt'] );

			if ( ! empty( $post_id ) )
				return get_permalink( $post_id );
		}

		return $link;
	}

	public function template_redirect()
	{
		if ( is_tax( $this->module->constants['contest_tax'] ) ) {

			$term = get_queried_object();
			if ( $post_id = gEditorialHelper::getPostIDbySlug( $term->slug, $this->module->constants['contest_cpt'] ) )
				self::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->module->constants['contest_cpt'] )
			|| is_post_type_archive( $this->module->constants['apply_cpt'] ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				self::redirect( $redirect, 301 );
		}
	}

	public function do_meta_box_main( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_contest_main_meta_box', $post ); // OLD ACTION: 'geditorial_the_contest_meta_box'

		$this->field_post_order( 'contest_cpt', $post );

		if ( get_post_type_object( $this->module->constants['contest_cpt'] )->hierarchical )
			$this->field_post_parent( 'contest_cpt', $post );

		$term_id = get_post_meta( $post->ID, '_'.$this->module->constants['contest_cpt'].'_term_id', TRUE );
		echo gEditorialHelper::getTermPosts( $this->module->constants['contest_tax'], intval( $term_id ) );

		echo '</div>';
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->module->constants['contest_cpt'] ) {

			$this->remove_meta_box( $post_type, $post_type, 'parent' );
			add_meta_box( 'geditorial-contest-main',
				$this->get_meta_box_title( 'contest_cpt', FALSE ),
				array( &$this, 'do_meta_box_main' ),
				$post_type,
				'side',
				'high'
			);

		} else if ( in_array( $post_type, $this->post_types() ) ) {

			$this->remove_meta_box( $post_type, $post_type, 'parent' );
			add_meta_box( 'geditorial-contest-supported',
				$this->get_meta_box_title( 'post', $this->get_url_post_edit( 'contest_cpt' ) ),
				array( &$this, 'do_meta_box_supported' ),
				$post_type,
				'side'
			);

			$this->remove_meta_box( 'apply_status_tax', $post_type, 'tag' );
			add_meta_box( 'geditorial-contest-applystatus',
				$this->get_meta_box_title( 'apply_status_tax', $this->get_url_tax_edit( 'apply_status_tax' ), 'edit_others_posts' ),
				array( $this, 'meta_box_choose_tax' ),
				NULL,
				'side',
				'default',
				array(
					'taxonomy' => $this->module->constants['apply_status_tax'],
				)
			);
		}
	}

	public function do_meta_box_supported( $post )
	{
		echo '<div class="geditorial-admin-wrap-metabox contest">';

		$terms = gEditorialHelper::getTerms( $this->module->constants['contest_tax'], $post->ID, TRUE );

		do_action( 'geditorial_contest_supported_meta_box', $post, $terms ); // OLD ACTION: 'geditorial_contest_meta_box'

		echo '</div>';
	}

	public function supported_meta_box( $post, $terms )
	{
		$this->field_post_order( 'apply_cpt', $post );

		$dropdowns = $excludes = array();

		foreach ( $terms as $term ) {

			$dropdowns[$term->slug] = wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['contest_cpt'],
				'selected'         => $term->slug,
				'name'             => 'geditorial-contest-contest[]',
				'id'               => 'geditorial-contest-contest-'.$term->slug,
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => __( '&mdash; Select a contest &mdash;', GEDITORIAL_TEXTDOMAIN ),
				'sort_column'      => 'menu_order',
				'sort_order'       => 'desc',
				'post_status'      => 'publish,private,draft',
				'value_field'      => 'post_name',
				'echo'             => 0,
				'walker'           => new gEditorial_Walker_PageDropdown(),
			));

			$excludes[] = $term->slug;
		}

		if ( ! count( $terms ) || $this->get_setting( 'multiple_contests', FALSE ) ) {
			$dropdowns[0] = wp_dropdown_pages( array(
				'post_type'        => $this->module->constants['contest_cpt'],
				'selected'         => '',
				'name'             => 'geditorial-contest-contest[]',
				'id'               => 'geditorial-contest-contest-0',
				'class'            => 'geditorial-admin-dropbown',
				'show_option_none' => __( '&mdash; Select a contest &mdash;', GEDITORIAL_TEXTDOMAIN ),
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
		if ( $this->module->constants['contest_tax'] == $taxonomy ) {

			$post_ids = get_posts( array(
				'post_type'  => $this->module->constants['contest_cpt'],
				'meta_key'   => '_'.$this->module->constants['contest_cpt'].'_term_id',
				'meta_value' => $term_id,
				'fields'     => 'ids',
			) );

			if ( $post_ids ) {
				foreach ( $post_ids as $post_id ) {
					update_post_meta( $post_id, '_'.$this->module->constants['contest_cpt'].'_term_id', $new_term_id, $term_id );
				}
			}
		}
	}

	public function post_updated_messages( $messages )
	{
		global $post, $post_ID;

		if ( $this->module->constants['contest_cpt'] == $post->post_type ) {
			$link = get_permalink( $post_ID );

			$messages[$this->module->constants['contest_cpt']] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Contest updated. <a href="%s">View contest</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => __( 'Contest updated.', GEDITORIAL_TEXTDOMAIN ),
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Contest restored to revision from %s', GEDITORIAL_TEXTDOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
				6  => sprintf( __( 'Contest published. <a href="%s">View contest</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				7  => __( 'Contest saved.', GEDITORIAL_TEXTDOMAIN ),
				8  => sprintf( __( 'Contest submitted. <a target="_blank" href="%s">Preview contest</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
				9  => sprintf( __( 'Contest scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview contest</a>', GEDITORIAL_TEXTDOMAIN ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $link ) ),
				10 => sprintf( __( 'Contest draft updated. <a target="_blank" href="%s">Preview contest</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
			);

		} else if ( $this->module->constants['apply_cpt'] == $post->post_type ) {
			$link = get_permalink( $post_ID );

			$messages[$this->module->constants['apply_cpt']] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Apply updated. <a href="%s">View apply</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => __( 'Apply updated.', GEDITORIAL_TEXTDOMAIN ),
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Apply restored to revision from %s', GEDITORIAL_TEXTDOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
				6  => sprintf( __( 'Apply published. <a href="%s">View apply</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				7  => __( 'Apply saved.', GEDITORIAL_TEXTDOMAIN ),
				8  => sprintf( __( 'Apply submitted. <a target="_blank" href="%s">Preview apply</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
				9  => sprintf( __( 'Apply scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview apply</a>', GEDITORIAL_TEXTDOMAIN ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $link ) ),
				10 => sprintf( __( 'Apply draft updated. <a target="_blank" href="%s">Preview apply</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
			);
		}

		 return $messages;
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		if ( $this->module->constants['contest_cpt'] == $postarr['post_type'] && ! $data['menu_order'] )
			$data['menu_order'] = gEditorialHelper::getLastPostOrder( $this->module->constants['contest_cpt'],
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

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->module->constants['contest_tax'] );

		if ( FALSE === $the_term ){
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->module->constants['contest_tax'] );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->module->constants['contest_tax'], $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->module->constants['contest_tax'], $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->module->constants['contest_tax'], $args );
		}

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->module->constants['contest_tax'].'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->module->constants['contest_tax'].'_linked', $post_ID );
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

		$term = wp_insert_term( $post->post_title, $this->module->constants['contest_tax'], $args );

		if ( ! is_wp_error( $term ) ) {
			update_post_meta( $post_ID, '_'.$this->module->constants['contest_cpt'].'_term_id', $term['term_id'] );

			if ( function_exists( 'update_term_meta' ) )
				update_term_meta( $term['term_id'], $this->module->constants['contest_cpt'].'_linked', $post_ID );
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
					$term = get_term_by( 'slug', $contest, $this->module->constants['contest_tax'] );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$terms[] = intval( $term->term_id );
				}
			}

			wp_set_object_terms( $post_ID, ( count( $terms ) ? $terms : NULL ), $this->module->constants['contest_tax'], FALSE );
		}

		return $post_ID;
	}

	public function wp_trash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'contest_cpt', 'contest_tax' ) ) {
			wp_update_term( $term->term_id, $this->module->constants['contest_tax'], array(
				'name' => $term->name.' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ),
				'slug' => $term->slug.'-trashed',
			) );
		}
	}

	public function untrash_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'contest_cpt', 'contest_tax' ) ) {
			wp_update_term( $term->term_id, $this->module->constants['contest_tax'], array(
				'name' => str_ireplace( ' - '._x( '(Trashed)', 'Suffix for term name linked to trashed post', GEDITORIAL_TEXTDOMAIN ), '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			) );
		}
	}

	public function before_delete_post( $post_id )
	{
		if ( $term = $this->get_linked_term( $post_id, 'contest_cpt', 'contest_tax' ) ) {
			wp_delete_term( $term->term_id, $this->module->constants['contest_tax'] );
			delete_metadata( 'term', $term->term_id, $this->module->constants['contest_cpt'].'_linked' );
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
			$this->column_thumb( $post_id );
	}

	public function sortable_columns( $columns )
	{
		$columns['order'] = 'menu_order';
		return $columns;
	}
}

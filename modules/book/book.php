<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialBook extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'book';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Book', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Online House of Publications', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'book-alt',
			'slug'                 => 'book',
			'load_frontend'        => TRUE,

			'constants' => array(
				'publication_cpt'         => 'publication',
				'publication_cpt_archive' => 'publications',
				'subject_tax'             => 'publication_subject',
				'library_tax'             => 'publication_library',
				'publisher_tax'           => 'publication_publisher',
				'type_tax'                => 'publication_type',
				'status_tax'              => 'publication_status',
				'size_tax'                => 'publication_size',
			),
			'supports' => array(
				'publication_cpt' => array(
					'title',
					'editor',
					'excerpt',
					'author',
					'thumbnail',
					'trackbacks',
					'custom-fields',
					'comments',
					'revisions',
					'page-attributes',
				),
			),

			'default_options' => array(
				'enabled'  => FALSE,
				'settings' => array(),

				'post_types' => array(
					'post' => FALSE,
					'page' => FALSE,
				),
			),

			'settings' => array(),

			'strings' => array(
				'misc' => array(
					'publication_cpt' => array(
						'cover_column_title' => _x( 'Cover', '[Book Module] Column Title', GEDITORIAL_TEXTDOMAIN ),
					),

					'meta_box_title' => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'publication_cpt' => array(
						'name'               => _x( 'Publications', 'Publication CPT Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => _x( 'Publications', 'Publication CPT Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'description'        => _x( 'Publication List', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'      => _x( 'Publication', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new'            => _x( 'Add New', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'       => _x( 'Add New Publication', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'          => _x( 'Edit Publication', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item'           => _x( 'New Publication', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'view_item'          => _x( 'View Publication', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'       => _x( 'Search Publications', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'not_found'          => _x( 'No publications found', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash' => _x( 'No publications found in Trash', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'  => _x( 'Parent Publication:', 'Publication CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					),
					'subject_tax' => array(
						'name'                       => _x( 'Subjects', 'Publication Subject Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Subjects', 'Publication Subject Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Subject', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Subjects', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Subjects', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Subject', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Subject:', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Subject', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Subject', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Subject', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Subject', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate subjects with commas', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove subjects', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used subjects', 'Publication Subject Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'library_tax' => array(
						'name'                       => _x( 'Libraries', 'Publication Library Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Libraries', 'Publication Library Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Library', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Libraries', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Libraries', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Library', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Library:', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Library', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Library', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Library', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Library', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate libraries with commas', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove libraries', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used libraries', 'Publication Library Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'publisher_tax' => array(
						'name'                       => _x( 'Publishers', 'Publication Publisher Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Publishers', 'Publication Publisher Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Publisher', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Publishers', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Publishers', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Publisher', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Publisher:', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Publisher', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Publisher', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Publisher', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Publisher', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate publishers with commas', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove publishers', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used publishers', 'Publication Publisher Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'type_tax' => array(
						'name'                       => _x( 'Types', 'Publication Type Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Types', 'Publication Type Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Type', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Types', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Types', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Type', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Type:', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Type', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Type', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Type', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Type', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate types with commas', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove types', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used types', 'Publication Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'status_tax' => array(
						'name'                       => _x( 'Statuses', 'Publication Status Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Statuses', 'Publication Status Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Status', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Statuses', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Statuses', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Status', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Status:', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Status', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Status', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Status', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Status', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate statuses with commas', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove statuses', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used statuses', 'Publication Status Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
					'size_tax' => array(
						'name'                       => _x( 'Sizes', 'Publication Size Tax Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Sizes', 'Publication Size Tax Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => _x( 'Size', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => _x( 'Search Sizes', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => _x( 'All Sizes', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => _x( 'Parent Size', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => _x( 'Parent Size:', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => _x( 'Edit Size', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => _x( 'Update Size', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => _x( 'Add New Size', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => _x( 'New Size', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => _x( 'Separate sizes with commas', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => _x( 'Add or remove sizes', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => _x( 'Choose from most used sizes', 'Publication Size Tax Labels', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
				),
				'terms' => array(
					'size_tax' => array(
						'octavo'        => _x( 'Octavo', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), // vaziri
						'folio'         => _x( 'Folio', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), // soltani
						'medium-octavo' => _x( 'Medium Octavo', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), //roghee
						'quatro'        => _x( 'Quatro', 'Publication Sizes Tax Defaults', GEDITORIAL_TEXTDOMAIN ), //rahli
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
		add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		// $this->require_code();
		$this->require_code( 'query' );

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'publication_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_book_init', $this->module );

		$this->do_filters();

		$this->register_post_type( 'publication_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'subject_tax', array( 'hierarchical' => TRUE, ), $this->module->constants['publication_cpt'] );
		$this->register_taxonomy( 'library_tax', array( 'hierarchical' => TRUE, ), $this->module->constants['publication_cpt'] );

		$this->register_taxonomy( 'publisher_tax', array(), $this->module->constants['publication_cpt'] );
		$this->register_taxonomy( 'type_tax', array(), $this->module->constants['publication_cpt'] );
		$this->register_taxonomy( 'status_tax', array(), $this->module->constants['publication_cpt'] );

		// FIXME: check not working!
		// if ( $this->_geditorial_meta )
			$this->register_taxonomy( 'size_tax', array(), $this->module->constants['publication_cpt'] );
	}

	public function admin_init()
	{
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 20, 2 );
		add_filter( 'manage_'.$this->module->constants['publication_cpt'].'_posts_columns', array( &$this, 'manage_posts_columns' ) );
		add_action( 'manage_'.$this->module->constants['publication_cpt'].'_posts_custom_column', array( &$this, 'posts_custom_column' ), 10, 2 );
		add_filter( 'disable_months_dropdown', array( &$this, 'disable_months_dropdown' ), 8, 2 );
		add_action( 'restrict_manage_posts', array( &$this, 'restrict_manage_posts' ) );
		add_action( 'parse_query', array( &$this, 'parse_query' ) );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( $post_type == $this->module->constants['publication_cpt'] ) {

			$post_type_object = get_post_type_object( $this->module->constants['publication_cpt'] );

			if ( $this->_geditorial_meta ) {
				add_meta_box( 'geditorial-book',
					$this->get_meta_box_title( $post_type, FALSE ),
					array( &$this, 'do_meta_box' ),
					$post_type,
					'side',
					'high'
				);
			}

			remove_meta_box( 'tagsdiv-'.$this->module->constants['status_tax'], $post_type, 'side' );
			add_meta_box( 'geditorial-book-status',
				$this->get_meta_box_title( $post_type, $this->get_url_tax_edit( 'status_tax' ), 'edit_others_posts', __( 'Status', GEDITORIAL_TEXTDOMAIN ) ),
				array( $this, 'meta_box_choose_tax' ),
				NULL,
				'side',
				'default',
				array(
					'taxonomy' => $this->module->constants['status_tax'],
				)
			);

			// TODO : must write your own _wp_post_thumbnail_html()
			// http://www.wpmayor.com/code/how-to-move-and-rename-the-featured-image-metabox/
			remove_meta_box( 'postimagediv', $this->module->constants['publication_cpt'], 'side' );
			add_meta_box( 'postimagediv',
				__( 'Cover', GEDITORIAL_TEXTDOMAIN ),
				'post_thumbnail_meta_box',
				$this->module->constants['publication_cpt'],
				'side',
				'high'
			);

			if ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) {
				remove_meta_box( 'authordiv', $this->module->constants['publication_cpt'], 'normal' );
				add_meta_box( 'authordiv',
					__( 'Curator', GEDITORIAL_TEXTDOMAIN ),
					'post_author_meta_box',
					$this->module->constants['publication_cpt'],
					'side'
				);
			}

			remove_meta_box( 'postexcerpt', $this->module->constants['publication_cpt'], 'normal' );
			add_meta_box( 'postexcerpt',
				__( 'Summary', GEDITORIAL_TEXTDOMAIN ),
				'post_excerpt_meta_box',
				$this->module->constants['publication_cpt'],
				'normal',
				'high'
			);
		}
	}

	public function gpeople_remote_support_post_types( $post_types )
	{
		return array_merge( $post_types, array( $this->module->constants['publication_cpt'] ) );
	}

	public function module_defaults_meta( $default_options, $mod_data )
	{
		$fields = $this->get_meta_fields();
		$default_options['publication_fields'] = $fields['publication'];

		return $default_options;
	}

	// setup actions and filters for meta module
	public function meta_init( $meta_module )
	{
		$this->_geditorial_meta = TRUE;

		add_filter( 'geditorial_meta_strings', array( &$this, 'meta_strings' ), 6, 1 );

		add_filter( 'geditorial_meta_dbx_callback', array( &$this, 'meta_dbx_callback' ), 10, 2 );
		add_filter( 'geditorial_meta_sanitize_post_meta', array( &$this, 'meta_sanitize_post_meta' ), 10 , 4 );
	}

	public function get_meta_fields()
	{
		return array(
			'publication' => array (
				'ot'        => FALSE,
				'st'        => TRUE,
				'alt_title' => FALSE,
				'isbn'      => TRUE,
				'size'      => TRUE,
				'year'      => TRUE,
				'edition'   => TRUE,
				'print'     => FALSE,
				'pages'     => TRUE,
			),
		);
	}

	public function meta_strings( $strings )
	{
		$new = array(
			'titles' => array(
				$this->module->constants['publication_cpt'] => array(
					'ot'        => __( 'Collection Title', GEDITORIAL_TEXTDOMAIN ),
					'st'        => __( 'Second Title', GEDITORIAL_TEXTDOMAIN ),
					'alt_title' => __( 'Alternative Title', GEDITORIAL_TEXTDOMAIN ),
					'isbn'      => __( 'ISBN', GEDITORIAL_TEXTDOMAIN ),
					'size'      => __( 'Size', GEDITORIAL_TEXTDOMAIN ),
					'year'      => __( 'Year', GEDITORIAL_TEXTDOMAIN ),
					'edition'   => __( 'Edition', GEDITORIAL_TEXTDOMAIN ),
					'print'     => __( 'Print', GEDITORIAL_TEXTDOMAIN ),
					'pages'     => __( 'Pages', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				$this->module->constants['publication_cpt'] => array(
					'ot'        => __( 'Collection Title', GEDITORIAL_TEXTDOMAIN ),
					'st'        => __( 'Second Title', GEDITORIAL_TEXTDOMAIN ),
					'alt_title' => __( 'The Original Title or Title on Another Language', GEDITORIAL_TEXTDOMAIN ),
					'isbn'      => __( 'International Standard Book Number', GEDITORIAL_TEXTDOMAIN ),
					'size'      => __( 'The Size of the Publication (mainly books)', GEDITORIAL_TEXTDOMAIN ),
					'year'      => __( 'Year of Publish', GEDITORIAL_TEXTDOMAIN ),
					'edition'   => __( 'Edition of the Publication', GEDITORIAL_TEXTDOMAIN ),
					'print'     => __( 'Specefic Print of the Publication', GEDITORIAL_TEXTDOMAIN ),
					'pages'     => __( 'Total Pages of the Publication', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->module->constants['subject_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['subject_tax'],
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_string( 'name', 'subject_tax', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function meta_dbx_callback( $func, $post_type )
	{
		if ( $this->module->constants['publication_cpt'] == $post_type )
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

	public function do_meta_box( $post )
	{
		global $gEditorial;

		$fields = $gEditorial->meta->post_type_fields( $post->post_type );

		echo '<div class="geditorial-admin-wrap-metabox">';

		do_action( 'geditorial_meta_box_before', $gEditorial->meta->module, $post, $fields );

		gEditorialHelper::meta_admin_field( 'alt_title', $fields, $post );
		gEditorialHelper::meta_admin_field( 'edition', $fields, $post );
		gEditorialHelper::meta_admin_field( 'year', $fields, $post );
		gEditorialHelper::meta_admin_field( 'print', $fields, $post );
		gEditorialHelper::meta_admin_field( 'pages', $fields, $post );
		gEditorialHelper::meta_admin_field( 'isbn', $fields, $post, TRUE );
		gEditorialHelper::meta_admin_tax_field( 'size', $fields, $post, $this->module->constants['size_tax'] );

		do_action( 'geditorial_meta_box_after', $gEditorial->meta->module, $post, $fields );

		wp_nonce_field( 'geditorial_book_meta_box', '_geditorial_book_meta_box' );

		echo '</div>';
	}

	public function meta_sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		$fields = $this->get_meta_fields();

		if ( $this->module->constants['publication_cpt'] == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_book_meta_box'], 'geditorial_book_meta_box' ) ) {

			foreach ( $fields[$post_type] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'size' :
						$this->set_postmeta_field_term( $post_id, $field, 'size_tax' );
					break;

					case 'alt_title' :
					case 'edition' :
					case 'year' :
					case 'print' :
					case 'pages' :
					case 'isbn' :
						$this->set_postmeta_field_string( $postmeta, $field );
				}
			}
		}

		return $postmeta;
	}

	public function disable_months_dropdown( $false, $post_type )
	{
		if ( $this->module->constants['publication_cpt'] == $post_type )
			return TRUE;

		return $false;
	}

	public function restrict_manage_posts()
	{
		$this->do_restrict_manage_posts_taxes( array(
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'publisher_tax',
		), 'publication_cpt' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query->query_vars, array(
			'type_tax',
			'subject_tax',
			'library_tax',
			'status_tax',
			'publisher_tax',
		), 'publication_cpt' );
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();

		foreach ( $posts_columns as $key => $value ) {

			if ( 'title' == $key ) {
				$new_columns['cover'] = $this->get_column_title( 'cover', 'publication_cpt' );
				$new_columns[$key]    = $value;

			} else if ( in_array( $key, array( 'author', 'date', 'comments' ) ) ) {
				continue; // he he!

			} else {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( 'cover' == $column_name )
			$this->column_thumb( $post_id );
	}

	public function post_updated_messages( $messages )
	{
		global $post, $post_ID;

		if ( $this->module->constants['publication_cpt'] == $post->post_type ) {

			$link = get_permalink( $post_ID );

			$messages[$this->module->constants['publication_cpt']] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Publication updated. <a href="%s">View publication</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				2  => __( 'Custom field updated.' ),
				3  => __( 'Custom field deleted.' ),
				4  => __( 'Publication updated.', GEDITORIAL_TEXTDOMAIN ),
				5  => isset( $_GET['revision'] ) ? sprintf( __( 'Publication restored to revision from %s', GEDITORIAL_TEXTDOMAIN ), wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
				6  => sprintf( __( 'Publication published. <a href="%s">View publication</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( $link ) ),
				7  => __( 'Publication saved.', GEDITORIAL_TEXTDOMAIN ),
				8  => sprintf( __( 'Publication submitted. <a target="_blank" href="%s">Preview publication</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
				9  => sprintf( __( 'Publication scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview publication</a>', GEDITORIAL_TEXTDOMAIN ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( $link ) ),
				10 => sprintf( __( 'Publication draft updated. <a target="_blank" href="%s">Preview publication</a>', GEDITORIAL_TEXTDOMAIN ), esc_url( add_query_arg( 'preview', 'true', $link ) ) ),
			);
		}

		return $messages;
	}
}

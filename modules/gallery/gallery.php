<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialGallery extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'gallery';
	var $meta_key    = '_ge_gallery';
	var $cookie      = 'geditorial-gallery';

	function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Gallery', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Photo Directory and Gallery for WordPress Editorial', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding gallery functionality to WordPress with custom posttypes and taxonomies.', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'format-gallery',
			'slug'                 => 'gallery',
			'load_frontend'        => TRUE,

			'constants' => array(
				'album_cpt'         => 'photo_album',
				'album_cpt_slug'    => 'album',
				'album_cpt_archive' => 'albums',
				'album_cat_tax'     => 'photo_gallery',
				'album_tag_tax'     => 'album_tag',
				'photo_tag_tax'     => 'photo_tag',
			),
			'supports' => array(
				'album_cpt' => array(
					'title',
					'editor',
					'excerpt',
					'author',
					'thumbnail',
					// 'trackbacks',
					// 'custom-fields',
					'comments',
					'revisions',
					// 'page-attributes',
				),
			),

			'default_options' => array(
				'enabled'  => FALSE,

				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
			),
			'settings' => array(
				'post_types_option' => 'post_types_option', // add p2p to connect with selected post types
			),
			'strings' => array(
				'labels' => array(
					'album_cpt' => array(
						'name'      => _x( 'Photo Albums', 'Gallery CPT Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name' => _x( 'Gallery', 'Gallery CPT Menu Name', GEDITORIAL_TEXTDOMAIN ),
					),
					'album_cat_tax' => array(),
					'photo_tag_tax' => array(),
					'album_tag_tax' => array(),
				),
				'terms' => array(
					'album_cat_tax' => array(),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tabs' => array(
				array(
				'id'       => 'geditorial-gallery-overview',
				'title'    => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content'  => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				'callback' => FALSE,
			),
		),
		'settings_help_sidebar' => sprintf(
			__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
			'http://geminorum.ir/wordpress/geditorial/modules/gallery',
			__( 'Editorial Gallery Documentations', GEDITORIAL_TEXTDOMAIN ),
			'https://github.com/geminorum/gEditorial' ),
		);

		$gEditorial->register_module( $this->module_name, $args );
	}


	public function setup()
	{
		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}

		$this->_post_types_excluded = array( $this->module->constants['album_cpt'] );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'album_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_gallery_init', $this->module );

		$this->do_filters();

		$this->register_post_type( 'album_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'album_cat_tax', array(), $this->module->constants['album_cpt'] );

		$this->register_taxonomy( 'album_tag_tax', array(
			'hierarchical' => FALSE,
		), $this->module->constants['album_cpt'] );

		$this->register_taxonomy( 'photo_tag_tax', array(
			'hierarchical' => FALSE,
		), 'attachments' );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_album_cats'] ) )
			$this->insert_default_terms( 'album_cat_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_album_cats', __( 'Install Default Album Cats', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function register_post_types()
	{
		register_post_type( $this->module->constants['album_cpt'], array(
			'show_in_menu'        => TRUE,
			'menu_position'       => 4,
			'show_in_nav_menus'   => TRUE,
			'map_meta_cap'        => TRUE,
		) );
	}

	public function register_taxonomies()
	{
		$editor = current_user_can( 'edit_others_posts' );

		register_taxonomy( $this->module->constants['album_cat_tax'], $this->module->constants['album_cpt'], array(
			'labels'                => $this->module->strings['labels']['album_cat_tax'],
			'public'                => FALSE,
			'show_in_nav_menus'     => FALSE,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => FALSE,
			'hierarchical'          => TRUE,
			'query_var'             => TRUE,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['album_cat_tax'],
				'hierarchical' => TRUE,
				'with_front'   => TRUE
			),
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );

		register_taxonomy( $this->module->constants['album_tag_tax'], $this->module->constants['album_cpt'], array(
			'labels'                => $this->module->strings['labels']['album_tag_tax'],
			'public'                => FALSE,
			'show_in_nav_menus'     => FALSE,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => FALSE,
			'hierarchical'          => FALSE,
			'query_var'             => TRUE,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['album_tag_tax'],
				'hierarchical' => FALSE,
				'with_front'   => TRUE
			),
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );

		register_taxonomy( $this->module->constants['photo_tag_tax'], 'attachments', array(
			'labels'                => $this->module->strings['labels']['photo_tag_tax'],
			'public'                => FALSE,
			'show_in_nav_menus'     => FALSE,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => FALSE,
			'hierarchical'          => FALSE,
			'query_var'             => TRUE,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug'         => $this->module->constants['photo_tag_tax'],
				'hierarchical' => FALSE,
				'with_front'   => TRUE
			),
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );
	}
}

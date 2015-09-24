<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialGallery extends gEditorialModuleCore
{

	var $module_name = 'gallery';
	var $meta_key    = '_ge_gallery';

	public function __construct()
	{
		global $gEditorial;

		$args = array(

			'title'                => __( 'Gallery', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Photo Directory and Gallery for WordPress Editorial', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding gallery functionality to WordPress with custom posttypes and taxonomies.', GEDITORIAL_TEXTDOMAIN ),

			'dashicon' => 'format-gallery',
			'slug'     => 'gallery',
			'frontend' => TRUE,

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
		$this->register_taxonomy( 'album_cat_tax', array(), 'album_cpt' );

		$this->register_taxonomy( 'album_tag_tax', array(
			'hierarchical' => FALSE,
		), 'album_cpt' );

		$this->register_taxonomy( 'photo_tag_tax', array(
			'hierarchical' => FALSE,
		), array( 'attachments' ) );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_album_cats'] ) )
			$this->insert_default_terms( 'album_cat_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_album_cats', __( 'Install Default Album Cats', GEDITORIAL_TEXTDOMAIN ) );
	}
}

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
			'load_frontend'        => true,

			'constants' => array(
				'album_cpt'       => 'photo_album',
				'album_archives'  => 'albums',
				'album_cat_tax'   => 'photo_gallery',
				'album_tag_tax'   => 'album_tag',
				'photo_tag_tax'   => 'photo_tag',
			),

			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
					'post' => 'on',
					'page' => 'off',
				),
				'post_fields' => array(
				),
				'settings' => array(
				),
			),
			'settings' => array(
				'post_types_option' => 'post_types_option', // add p2p to connect with selected post types
			),
			'strings' => array(
				'labels' => array(
					'album_cpt' => array(
						'name'               => __( 'Photo Albums', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'      => __( 'Photo Album', GEDITORIAL_TEXTDOMAIN ),
						'add_new'            => __( 'Add New', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'       => __( 'Add New Photo Album', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'          => __( 'Edit Photo Album', GEDITORIAL_TEXTDOMAIN ),
						'new_item'           => __( 'New Photo Album', GEDITORIAL_TEXTDOMAIN ),
						'view_item'          => __( 'View Photo Album', GEDITORIAL_TEXTDOMAIN ),
						'search_items'       => __( 'Search Photo Albums', GEDITORIAL_TEXTDOMAIN ),
						'not_found'          => __( 'No photo albums found', GEDITORIAL_TEXTDOMAIN ),
						'not_found_in_trash' => __( 'No photo albums found in Trash', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'  => __( 'Parent Photo Album:', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'          => __( 'Gallery', GEDITORIAL_TEXTDOMAIN ),
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
				'callback' => false,
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
			// add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		} else {
		}

		$this->_post_types_excluded = array( $this->module->constants['album_cpt'] );
	}

	public function after_setup_theme()
	{
		self::themeThumbnails( array( $this->module->constants['album_cpt'] ) );
	}

	public function init()
	{
		do_action( 'geditorial_gallery_init', $this->module );

		$this->do_filters();
		$this->register_post_types();
		$this->register_taxonomies();
	}

	public function admin_init()
	{

	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_album_cats'] ) )
			$this->insert_default_terms();

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_album_cats', __( 'Install Default Album Cats', GEDITORIAL_TEXTDOMAIN ) );
	}

	private function insert_default_terms()
	{
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->module->options_group_name.'-options' ) )
			return;

		$added = gEditorialHelper::insertDefaultTerms(
			$this->module->constants['album_cat_tax'],
			$this->module->strings['terms']['album_cat_tax']
		);

		wp_redirect( add_query_arg( 'message', $added ? 'insert_default_terms' : 'error_default_terms' ) );
		exit;
	}


	public function register_post_types()
	{
		register_post_type( $this->module->constants['album_cpt'], array(
			'labels'              => $this->module->strings['labels']['album_cpt'],
			'taxonomies'          => array(
				$this->module->constants['album_cat_tax'],
				$this->module->constants['photo_tag_tax'],
			),
			'supports' => array(
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
			'hierarchical'        => true,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 4,
			'menu_icon'           => 'dashicons-format-gallery',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => $this->module->constants['album_archives'],
			'query_var'           => $this->module->constants['album_cpt'],
			'can_export'          => true,
			'map_meta_cap'        => true,
			'rewrite'             => array(
				'slug'       => $this->module->constants['album_cpt'],
				'with_front' => false
			),
		) );
	}

	public function register_taxonomies()
	{
		$editor = current_user_can( 'edit_others_posts' );

		register_taxonomy( $this->module->constants['album_cat_tax'], $this->module->constants['album_cpt'], array(
			'labels'                => $this->module->strings['labels']['album_cat_tax'],
			'public'                => false,
			'show_in_nav_menus'     => false,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => false,
			'hierarchical'          => true,
			'query_var'             => true,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug' => $this->module->constants['album_cat_tax'],
				'hierarchical' => true,
				'with_front' => true
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
			'public'                => false,
			'show_in_nav_menus'     => false,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => false,
			'hierarchical'          => false,
			'query_var'             => true,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug' => $this->module->constants['album_tag_tax'],
				'hierarchical' => false,
				'with_front' => true
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
			'public'                => false,
			'show_in_nav_menus'     => false,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => false,
			'hierarchical'          => false,
			'query_var'             => true,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'rewrite'               => array(
				'slug' => $this->module->constants['photo_tag_tax'],
				'hierarchical' => false,
				'with_front' => true
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

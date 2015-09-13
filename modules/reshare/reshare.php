<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshare extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'reshare';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Reshare', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Content from other sources', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'external',
			'slug'                 => 'reshare',
			'load_frontend'        => TRUE,

			'constants' => array(
				'reshare_cpt'         => 'reshare',
				'reshare_cpt_archive' => 'reshares',
				'reshare_cat'         => 'reshare_cat',
			),
			'supports' => array(
				'reshare_cpt' => array(
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
				'settings' => array(),

				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
			),

			'settings' => array(),

			'strings' => array(
				'misc' => array(
					'meta_box_title' => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'reshare_cpt' => array(
						'name'        => _x( 'Reshares', 'Reshare CPT Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'   => _x( 'Reshares', 'Reshare CPT Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Content from other sources', GEDITORIAL_TEXTDOMAIN ),
					),
					'reshare_cat' => array(
						'name'      => _x( 'Reshare Category', 'Reshare Taxonomy Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name' => _x( 'Reshare Categories', 'Reshare Taxonomy Menu Name', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
		);

		$gEditorial->register_module( $this->module_name, $args );

		add_filter( 'geditorial_module_defaults_meta', array( &$this, 'module_defaults_meta' ), 10, 2 );
	}

	public function setup()
	{
		add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		$this->require_code();

		add_action( 'after_setup_theme', array( &$this, 'after_setup_theme' ), 20 );
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'reshare_cpt' );
	}

	public function init()
	{
		do_action( 'geditorial_reshare_init', $this->module );

		$this->do_filters();

		$this->register_post_type( 'reshare_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'reshare_cat', array(
			'hierarchical' => TRUE,
		), $this->module->constants['reshare_cpt'] );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 10, 2 );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! $this->_geditorial_meta )
			return;

		if ( $post_type == $this->module->constants['reshare_cpt'] ) {
			add_meta_box( 'geditorial-reshare',
				$this->get_meta_box_title( $post_type, FALSE ),
				array( &$this, 'do_meta_box' ),
				$post_type,
				'side',
				'high'
			);
		}
	}

	public function module_defaults_meta( $default_options, $mod_data )
	{
		$fields = $this->get_meta_fields();
		$default_options['reshare_fields'] = $fields['reshare'];

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
			'reshare' => array (
				'ot'                   => TRUE,
				'st'                   => TRUE,
				'reshare_source_title' => TRUE,
				'reshare_source_url'   => TRUE,
			),
		);
	}

	public function meta_strings( $strings )
	{
		$new = array(
			'titles' => array(
				$this->module->constants['reshare_cpt'] => array(
					'reshare_source_title' => __( 'Source TITLE', GEDITORIAL_TEXTDOMAIN ),
					'reshare_source_url'   => __( 'Source URL', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				$this->module->constants['reshare_cpt'] => array(
					'reshare_source_title' => __( 'Source TITLE', GEDITORIAL_TEXTDOMAIN ),
					'reshare_source_url'   => __( 'Source URL', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->module->constants['reshare_cat'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['reshare_cat'],
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_string( 'name', 'reshare_cat', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function meta_dbx_callback( $func, $post_type )
	{
		if ( $this->module->constants['reshare_cpt'] == $post_type )
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

		gEditorialHelper::meta_admin_field( 'reshare_source_title', $fields, $post );
		gEditorialHelper::meta_admin_field( 'reshare_source_url', $fields, $post, TRUE );

		do_action( 'geditorial_meta_box_after', $gEditorial->meta->module, $post, $fields );

		wp_nonce_field( 'geditorial_reshare_meta_box', '_geditorial_reshare_meta_box' );

		echo '</div>';
	}

	public function meta_sanitize_post_meta( $postmeta, $fields, $post_id, $post_type )
	{
		$fields = $this->get_meta_fields();

		if ( $this->module->constants['reshare_cpt'] == $post_type
			&& wp_verify_nonce( @$_REQUEST['_geditorial_reshare_meta_box'], 'geditorial_reshare_meta_box' ) ) {

			foreach ( $fields[$post_type] as $field => $field_enabled ) {
				switch ( $field ) {
					case 'reshare_source_title' :
					case 'reshare_source_url' :
						$this->set_postmeta_field_string( $postmeta, $field );
				}
			}
		}

		return $postmeta;
	}
}

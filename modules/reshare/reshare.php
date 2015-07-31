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
			'dashicon'             => 'heart',
			'slug'                 => 'reshare',
			'load_frontend'        => TRUE,

			'constants' => array(
				'reshare_cpt' => 'reshare',
				'reshare_cat' => 'reshare_cat',
			),
			'supports' => array(
				'reshare_cpt' => array(
					'title',
					'editor',
					'excerpt',
					// 'author',
					// 'thumbnail',
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

			'settings' => array(
				// '_general' => array(
				// 	array(
				// 		'field'       => 'group_taxonomies',
				// 		'title'       => __( 'Group Default Taxonomies', GEDITORIAL_TEXTDOMAIN ),
				// 		'default'     => 0,
				// 	),
				// ),
				// 'post_types_option' => 'post_types_option',
				// 'taxonomies_option' => 'taxonomies_option',
			),
			'strings' => array(
				'misc' => array(
					'meta_box_title'           => __( 'Metadata', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'reshare_cpt' => array(
						'name' => __( 'Reshare', GEDITORIAL_TEXTDOMAIN ),
						'menu_name' => __( 'Reshares', GEDITORIAL_TEXTDOMAIN ),
					),
					'reshare_cat' => array(
						'name'      => __( 'Reshare', GEDITORIAL_TEXTDOMAIN ),
						'menu_name' => __( 'Reshares', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-reshare-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/reshare',
				__( 'Editorial Reshare Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );

		add_filter( 'geditorial_module_defaults_meta', array( &$this, 'module_defaults_meta' ), 10, 2 );
	}

	public function setup()
	{
		add_action( 'geditorial_meta_init', array( &$this, 'meta_init' ) );

		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_reshare_init', $this->module );

		$this->do_filters();

		$this->register_post_type( 'reshare_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'reshare_cat', array(), $this->module->constants['reshare_cpt'] );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 10, 2 );

		// if ( $this->get_setting( 'group_taxonomies', false ) ) {
		//
			// foreach( $this->post_types() as $post_type ) {
			// 	add_filter( "manage_{$post_type}_posts_columns", array( &$this, 'manage_posts_columns' ) );
			// 	add_filter( "manage_{$post_type}_posts_custom_column", array( &$this, 'custom_column'), 10, 2 );
			// }
		// }
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( ! $this->_geditorial_meta )
			return; // no meta

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
						if ( isset( $_POST['geditorial-meta-'.$field] )
							&& strlen( $_POST['geditorial-meta-'.$field] ) > 0 )
							// && $gEditorial->meta->module->strings['titles'][$field] !== $_POST['geditorial-meta-'.$field] )
								$postmeta[$field] = strip_tags( $_POST['geditorial-meta-'.$field] );
						elseif ( isset( $postmeta[$field] ) && isset( $_POST['geditorial-meta-'.$field] ) )
							unset( $postmeta[$field] );
				}
			}
		}

		return $postmeta;
	}
}

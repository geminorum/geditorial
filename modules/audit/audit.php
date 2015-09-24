<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAudit extends gEditorialModuleCore
{

	var $module_name = 'audit';
	var $meta_key    = '_ge_audit';

	public function __construct()
	{
		global $gEditorial;

		$args = array(

			'title'                => __( 'Audit', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Content Inventory Tools', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding auditing functionality to WordPress with custom taxonomies.', GEDITORIAL_TEXTDOMAIN ),

			'dashicon' => 'visibility',
			'slug'     => 'audit',
			'frontend' => TRUE,

			'constants' => array(
				'audit_tax' => 'audit',
			),

			'default_options' => array(
				'enabled' => FALSE,
				'settings' => array(),
				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
			),
			'settings' => array(
				'post_types_option' => 'post_types_option',
			),
			'strings' => array(
				'titles' => array(
				),
				'descriptions' => array(
				),
				'misc' => array(
					'meta_box_title'     => __( 'Audit', GEDITORIAL_TEXTDOMAIN ),
					'meta_box_action'    => __( 'Management', GEDITORIAL_TEXTDOMAIN ),
					'table_column_title' => __( 'Audit', GEDITORIAL_TEXTDOMAIN ),
				),
				'labels' => array(
					'audit_tax' => array(
						'name'                       => _x( 'Audit Attributes', 'Audit Attributes Taxonomy Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => _x( 'Audit Attributes', 'Audit Attributes Taxonomy Menu Name', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Audit Attribute',                        GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Audit Attributes',                GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Audit Attributes',                   GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Audit Attribute',                 GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Audit Attribute:',                GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Audit Attribute',                   GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Audit Attribute',                 GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Audit Attribute',                GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Audit Attribute',                    GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate audit attributes with commas',  GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove audit attributes',         GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from most used audit attributes', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
					),
				),
				'terms' => array(
					'audit_tax' => array(
						'audited'      => __( 'Audited', GEDITORIAL_TEXTDOMAIN ),
						'outdated'     => __( 'Outdated', GEDITORIAL_TEXTDOMAIN ),
						'redundant'    => __( 'Redundant', GEDITORIAL_TEXTDOMAIN ),
						'review-seo'   => __( 'Review SEO', GEDITORIAL_TEXTDOMAIN ),
						'review-style' => __( 'Review Style', GEDITORIAL_TEXTDOMAIN ),
						'trivial'      => __( 'Trivial', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_audit_init', $this->module );

		$this->do_filters();

		if ( current_user_can( 'edit_others_posts' ) ) // TODO: add setting option to choose editing role
			$this->register_taxonomy( 'audit_tax', array(
				'hierarchical' => TRUE,
			) );
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ), 20, 2 );
	}

	public function register_settings( $page = NULL )
	{
		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_audit_tax', __( 'Install Default Attributes', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->module->constants['audit_tax'] => array(
					'column'     => 'taxonomy-'.$this->module->constants['audit_tax'],
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_string( 'name', 'audit_tax', 'labels' ),
				),
			),
		);

		return gEditorialHelper::parse_args_r( $new, $strings );
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( in_array( $post_type, $this->post_types() ) ) {
			remove_meta_box( $this->module->constants['audit_tax'].'div', $post_type, 'side' );
			add_meta_box( 'geditorial-audit',
				$this->get_meta_box_title( 'audit_tax', $this->get_url_tax_edit( 'audit_tax' ), 'edit_others_posts' ),
				array( $this, 'meta_box_choose_tax' ),
				NULL,
				'side',
				'default',
				array(
					'taxonomy' => $this->module->constants['audit_tax'],
				)
			);
		}
	}
}

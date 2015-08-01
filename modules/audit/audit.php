<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAudit extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'audit';
	var $meta_key    = '_ge_audit';
	var $cookie      = 'geditorial-audit';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Audit', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Content Inventory Tools', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding auditing functionality to WordPress with custom taxonomies.', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'visibility',
			'slug'                 => 'audit',
			'load_frontend'        => TRUE,

			'constants' => array(
				'audit_tax' => 'audit',
			),

			'default_options' => array(
				'enabled' => FALSE,
				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'post_fields' => array(
				),
				'settings' => array(
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
				),
				'labels' => array(
					'audit_tax' => array(
						'name'      => _x( 'Audit Attributes', 'Audit Attributes Taxonomy Name', GEDITORIAL_TEXTDOMAIN ),
						'menu_name' => _x( 'Audit Attributes', 'Audit Attributes Taxonomy Menu Name', GEDITORIAL_TEXTDOMAIN ),

						'singular_name'              => __( 'Audit Attribute',                        GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Audit Attributes',                GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => NULL,
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
					),
				),
				'terms' => array(
					'audit_tax' => array(
						'audited'      => __( 'Audited',      GEDITORIAL_TEXTDOMAIN ),
						'outdated'     => __( 'Outdated',     GEDITORIAL_TEXTDOMAIN ),
						'redundant'    => __( 'Redundant',    GEDITORIAL_TEXTDOMAIN ),
						'review-seo'   => __( 'Review SEO',   GEDITORIAL_TEXTDOMAIN ),
						'review-style' => __( 'Review Style', GEDITORIAL_TEXTDOMAIN ),
						'trivial'      => __( 'Trivial',      GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-audit-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/audit',
				__( 'Editorial Audit Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_filter( 'geditorial_tweaks_strings', array( &$this, 'tweaks_strings' ) );

		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_audit_init', $this->module );

		$this->do_filters();

		$this->register_taxonomy( 'audit_tax', array() );
	}

	public function register_settings( $page = null )
	{
		if ( isset( $_POST['install_def_atts'] ) )
			$this->install_def_atts();

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_atts', __( 'Install Default Attributes', GEDITORIAL_TEXTDOMAIN ) );
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

	public function register_taxonomies()
	{
		$editor = current_user_can( 'edit_others_posts' );

		register_taxonomy( $this->module->constants['audit_tax'], $this->post_types(), array(
			'labels'                => $this->module->strings['labels']['audit_tax'],
			'public'                => false,
			'show_in_nav_menus'     => false,
			'show_ui'               => $editor,
			'show_admin_column'     => $editor,
			'show_tagcloud'         => false,
			'hierarchical'          => false,
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			'query_var'             => true,
			'rewrite'               => array(
				'slug'         => $this->module->constants['audit_tax'],
				'hierarchical' => true,
				'with_front'   => true
			),
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_published_posts'
			)
		) );
	}

	private function install_def_atts()
	{
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->module->options_group_name.'-options' ) )
			return;

		$added = gEditorialHelper::insertDefaultTerms(
			$this->module->constants['audit_tax'],
			$this->module->strings['terms']['audit_tax']
		);

		wp_redirect( add_query_arg( 'message', $added ? 'added_install_def_atts' : 'error_install_def_atts' ) );
		exit;
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAudit extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'     => 'audit',
			'title'    => _x( 'Audit', 'Audit Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Content Inventory Tools', 'Audit Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'visibility',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'audit_tax' => 'audit',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'meta_box_title'     => __( 'Audit', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_action'    => __( 'Management', GEDITORIAL_TEXTDOMAIN ),
				'table_column_title' => __( 'Audit', GEDITORIAL_TEXTDOMAIN ),
			),
			'labels' => array(
				'audit_tax' => array(
                    'name'                  => _x( 'Audit Attributes', 'Audit Module: Audit Attribute Tax: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Audit Attributes', 'Audit Module: Audit Attribute Tax: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Audit Attribute', 'Audit Module: Audit Attribute Tax: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Audit Attributes', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Audit Attributes', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Audit Attribute', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Audit Attribute:', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Audit Attribute', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Audit Attribute', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Audit Attribute', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Audit Attribute', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Audit Attribute Name', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No audit attributes found.', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No audit attributes', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Audit Attributes list navigation', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Audit Attributes list', 'Audit Module: Audit Attribute Tax', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'terms' => array(
				'audit_tax' => array(
					'audited'      => _x( 'Audited', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'outdated'     => _x( 'Outdated', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'redundant'    => _x( 'Redundant', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-seo'   => _x( 'Review SEO', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'review-style' => _x( 'Review Style', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
					'trivial'      => _x( 'Trivial', 'Audit Module: Audit Attributes Tax Defaults', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_audit_init', $this->module );

		$this->do_globals();

		// TODO: add setting option to choose editing role
		if ( current_user_can( 'edit_others_posts' ) )
			$this->register_taxonomy( 'audit_tax', array(
				'hierarchical' => TRUE,
			) );
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		if ( isset( $_POST['install_def_audit_tax'] ) )
			$this->insert_default_terms( 'audit_tax' );

		parent::register_settings( $page );
		$this->register_settings_button( 'install_def_audit_tax', _x( 'Install Default Attributes', 'Audit Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'audit_tax' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'audit_tax' ),
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_string( 'name', 'audit_tax', 'labels' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function meta_box_cb_audit_tax( $post, $box )
	{
		$this->meta_box_choose_tax( $post, $box );
	}
}

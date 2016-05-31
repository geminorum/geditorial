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
			'audit_tax' => 'audit_attribute',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'tweaks_column_title' => _x( 'Audit Attributes', 'Audit Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'audit_tax' => _nx_noop( 'Audit Attribute', 'Audit Attributes', 'Audit Module: Noop', GEDITORIAL_TEXTDOMAIN ),
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

		// FIXME: add setting option to choose editing role
		if ( current_user_can( 'edit_others_posts' ) )
			$this->register_taxonomy( 'audit_tax', array(
				'hierarchical' => TRUE,
			) );

		if ( is_admin() )
			add_filter( 'geditorial_tweaks_strings', array( $this, 'tweaks_strings' ) );
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
					'title_attr' => $this->get_column_title( 'tweaks', 'audit_tax' ),
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

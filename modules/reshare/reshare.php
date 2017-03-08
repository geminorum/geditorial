<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshare extends gEditorialModuleCore
{

	protected $partials = array( 'templates' );

	public static function module()
	{
		return array(
			'name'      => 'reshare',
			'title'     => _x( 'Reshare', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Contents from Other Sources', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'external',
			'configure' => FALSE,
		);
	}

	protected function get_global_constants()
	{
		return array(
			'reshare_cpt'         => 'reshare',
			'reshare_cpt_archive' => 'reshares',
			'reshare_cat'         => 'reshare_cat',
			'reshare_cat_slug'    => 'reshare-category',
		);
	}

	protected function get_module_icons()
	{
		return array(
			'taxonomies' => array(
				'reshare_cat' => NULL,
			),
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'tweaks_column_title' => _x( 'Reshare Categories', 'Reshare Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'reshare_cpt' => _nx_noop( 'Reshare', 'Reshares', 'Reshare Module: Noop', GEDITORIAL_TEXTDOMAIN ),
				'reshare_cat' => _nx_noop( 'Reshare Category', 'Reshare Categories', 'Reshare Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'reshare_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'comments',
				'revisions',
				'date-picker', // gPersianDate
			),
		);
	}

	public function meta_post_types( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'reshare_cpt' ) ) );
	}

	public function gpeople_support( $post_types )
	{
		return array_merge( $post_types, array( $this->constant( 'reshare_cpt' ) ) );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'reshare_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_post_type( 'reshare_cpt', array(), array( 'post_tag' ) );

		$this->register_taxonomy( 'reshare_cat', array(
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'reshare_cpt' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'reshare_cpt' ) ) {

			if ( 'edit' == $screen->base ) {

				$this->_tweaks_taxonomy();
			}
		}
	}
}

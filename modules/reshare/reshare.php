<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshare extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'      => 'reshare',
			'title'     => _x( 'Reshare', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Contents from Other Sources', 'Reshare Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'  => 'external',
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
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup( array(
			'templates',
		) );
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
		do_action( 'geditorial_reshare_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'reshare_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'reshare_cat', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL,
		), 'reshare_cpt' );

		if ( is_admin() )
			add_filter( 'geditorial_tweaks_strings', array( $this, 'tweaks_strings' ) );
	}

	public function meta_init()
	{
		add_filter( 'geditorial_meta_box_callback', array( $this, 'meta_box_callback' ), 10, 2 );
	}

	// FIXME: default will be true / DROP THIS
	public function meta_box_callback( $callback, $post_type )
	{
		if ( $post_type == $this->constant( 'reshare_cpt' ) )
			return TRUE;

		return $callback;
	}

	public function tweaks_strings( $strings )
	{
		$new = array(
			'taxonomies' => array(
				$this->constant( 'reshare_cat' ) => array(
					'column'     => 'taxonomy-'.$this->constant( 'reshare_cat' ),
					'dashicon'   => $this->module->dashicon,
					'title_attr' => $this->get_column_title( 'tweaks', 'reshare_cat' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}
}

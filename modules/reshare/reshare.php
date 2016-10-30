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

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'insert_content',
				array(
					'field'       => 'before_source',
					'type'        => 'text',
					'title'       => _x( 'Before Source', 'Reshare Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Default text before the source link', 'Reshare Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Source:', 'Reshare Module: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				),
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
		do_action( 'geditorial_reshare_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'reshare_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'reshare_cat', array(
			'hierarchical' => TRUE,
			'meta_box_cb'  => NULL,
		), 'reshare_cpt' );

		if ( ! is_admin() ) {

			$setting = $this->get_setting( 'insert_content', 'none' );

			if ( 'before' == $setting )
				add_action( 'gnetwork_themes_content_before', array( $this, 'insert_content' ), 50 );

			else if ( 'after' == $setting )
				add_action( 'gnetwork_themes_content_after', array( $this, 'insert_content' ), 50 );
		}
	}

	public function tweaks_strings( $strings )
	{
		$this->tweaks = TRUE;

		$new = array(
			'taxonomies' => array(
				$this->constant( 'reshare_cat' ) => array(
					'column' => 'taxonomy-'.$this->constant( 'reshare_cat' ),
					'icon'   => $this->module->icon,
					'title'  => $this->get_column_title( 'tweaks', 'reshare_cat' ),
				),
			),
		);

		return self::recursiveParseArgs( $new, $strings );
	}

	public function insert_content( $content, $posttypes = NULL )
	{
		if ( is_singular( $this->constant( 'reshare_cpt' ) )
			&& in_the_loop() && is_main_query() )
				gEditorialReshareTemplates::source( array(
					'before' => '<div class="geditorial-wrap -reshare -'
						.$this->get_setting( 'insert_content', 'none' ).' entry-source">'
						.$this->get_setting( 'before_source', '' ).' ',
					'after' => '</div>',
				) );
	}
}

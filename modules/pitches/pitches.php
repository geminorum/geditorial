<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialPitches extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'      => 'pitches',
			'title'     => _x( 'Pitches', 'Modules: Pitches', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Keep Track of Ideas', 'Modules: Pitches', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'cloud',
			'configure' => FALSE,
			'frontend'  => FALSE,
		);
	}

	protected function get_global_constants()
	{
		return array(
			'idea_cpt'         => 'idea',
			'idea_cpt_archive' => 'ideas',
			'idea_cat'         => 'idea_cat',
			'idea_cat_slug'    => 'idea-category',
		);
	}

	protected function get_module_icons()
	{
		return array(
			'taxonomies' => array(
				'idea_cat' => NULL,
			),
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'tweaks_column_title' => _x( 'Idea Categories', 'Modules: Pitches: Column Title', GEDITORIAL_TEXTDOMAIN ),
			),
			'noops' => array(
				'idea_cpt' => _nx_noop( 'Idea', 'Ideas', 'Modules: Pitches: Noop', GEDITORIAL_TEXTDOMAIN ),
				'idea_cat' => _nx_noop( 'Idea Category', 'Idea Categories', 'Modules: Pitches: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'idea_cpt' => array(
				'title',
				'excerpt',
				'author',
				'comments',
				'date-picker', // gPersianDate
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_pitches_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'idea_cpt', array(), array( 'post_tag' ) );

		$this->register_taxonomy( 'idea_cat', array(
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		), 'idea_cpt' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'idea_cpt' ) ) {

			if ( 'edit' == $screen->base ) {

				$this->_tweaks_taxonomy();
			}
		}
	}

	public function meta_box_cb_idea_cat( $post, $box )
	{
		gEditorialMetaBox::checklistTerms( $post, $box );
	}
}

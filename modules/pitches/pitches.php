<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;

class Pitches extends gEditorial\Module
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
		parent::init();

		$this->register_post_type( 'idea_cpt' );

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

				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				$this->_tweaks_taxonomy();
			}
		}
	}

	public function meta_box_cb_idea_cat( $post, $box )
	{
		MetaBox::checklistTerms( $post, $box );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'idea_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'idea_cat' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'idea_cat' );
	}
}

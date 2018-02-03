<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;

class Pitches extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'pitches',
			'title'    => _x( 'Pitches', 'Modules: Pitches', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Keep Track of Ideas', 'Modules: Pitches', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'lightbulb',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		return [
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'idea_cpt', [
					'title',
					'excerpt',
					'author',
					'comments',
					'date-picker',
					'editorial-roles'
				] ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'idea_cpt'         => 'idea',
			'idea_cpt_archive' => 'ideas',
			'idea_cat'         => 'idea_cat',
			'idea_cat_slug'    => 'idea-category',
			'pool_tax'         => 'idea_pool',
			'pool_tax_slug'    => 'idea-pool',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'idea_cat' => NULL,
				'pool_tax' => 'clipboard',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'idea_cpt' => _nx_noop( 'Idea', 'Ideas', 'Modules: Pitches: Noop', GEDITORIAL_TEXTDOMAIN ),
				'idea_cat' => _nx_noop( 'Idea Category', 'Idea Categories', 'Modules: Pitches: Noop', GEDITORIAL_TEXTDOMAIN ),
				'pool_tax' => _nx_noop( 'Idea Pool', 'Idea Pools', 'Modules: Pitches: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'idea_cat' => [
				'tweaks_column_title' => _x( 'Idea Categories', 'Modules: Pitches: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'pool_tax' => [
				'tweaks_column_title' => _x( 'Idea Pools', 'Modules: Pitches: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'idea_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'idea_cat', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'idea_cpt' );

		$this->register_taxonomy( 'pool_tax', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'idea_cpt' );

		$this->register_post_type( 'idea_cpt' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'idea_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	public function meta_box_cb_idea_cat( $post, $box )
	{
		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function meta_box_cb_pool_tax( $post, $box )
	{
		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'idea_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'idea_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'idea_cpt', $counts ) );
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'idea_cat' );
		$this->do_restrict_manage_posts_taxes( 'pool_tax' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'idea_cat' );
		$this->do_parse_query_taxes( $query, 'pool_tax' );
	}
}

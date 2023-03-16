<?php namespace geminorum\gEditorial\Modules\Pitches;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Pitches extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'pitches',
			'title'    => _x( 'Pitches', 'Modules: Pitches', 'geditorial' ),
			'desc'     => _x( 'Keep Track of Ideas', 'Modules: Pitches', 'geditorial' ),
			'icon'     => 'lightbulb',
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		return [
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'idea_posttype', [
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
			'idea_posttype'     => 'idea',
			'category_taxonomy' => 'idea_category',
			'pool_taxonomy'     => 'idea_pool',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'category_taxonomy' => NULL,
				'pool_taxonomy'     => 'clipboard',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'idea_posttype'     => _nx_noop( 'Idea', 'Ideas', 'Noop', 'geditorial-pitches' ),
				'category_taxonomy' => _nx_noop( 'Idea Category', 'Idea Categories', 'Noop', 'geditorial-pitches' ),
				'pool_taxonomy'     => _nx_noop( 'Idea Pool', 'Idea Pools', 'Noop', 'geditorial-pitches' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'category_taxonomy' => [
				'tweaks_column_title' => _x( 'Idea Categories', 'Column Title', 'geditorial-pitches' ),
			],
			'pool_taxonomy' => [
				'tweaks_column_title' => _x( 'Idea Pools', 'Column Title', 'geditorial-pitches' ),
			],
		];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'idea_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'idea_posttype' );

		$this->register_taxonomy( 'pool_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'idea_posttype' );

		$this->register_posttype( 'idea_posttype' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'idea_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'category_taxonomy', 'pool_taxonomy' ];
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'idea_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'idea_posttype' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'idea_posttype', $counts ) );
	}
}

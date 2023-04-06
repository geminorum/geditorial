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
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
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
			'primary_posttype' => 'idea',
			'primary_taxonomy' => 'idea_category',
			'primary_subterm'  => 'idea_pool',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'primary_taxonomy' => NULL,
				'primary_subterm'  => 'clipboard',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Idea', 'Ideas', 'geditorial-pitches' ),
				'primary_taxonomy' => _n_noop( 'Idea Category', 'Idea Categories', 'geditorial-pitches' ),
				'primary_subterm'  => _n_noop( 'Idea Pool', 'Idea Pools', 'geditorial-pitches' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		// $strings['misc'] = [];

		return $strings;
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'primary_posttype' );

		$this->register_taxonomy( 'primary_subterm', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'primary_posttype' );

		$this->register_posttype( 'primary_posttype', [
			'primary_taxonomy' => $this->constant( 'primary_taxonomy' ),
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

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
		return [ 'primary_taxonomy', 'primary_subterm' ];
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'primary_posttype' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'primary_posttype', $counts ) );
	}
}

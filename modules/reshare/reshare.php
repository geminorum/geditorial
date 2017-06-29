<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class Reshare extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'      => 'reshare',
			'title'     => _x( 'Reshare', 'Modules: Reshare', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Contents from Other Sources', 'Modules: Reshare', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'external',
			'configure' => FALSE,
		];
	}

	protected function get_global_constants()
	{
		return [
			'reshare_cpt'         => 'reshare',
			'reshare_cpt_archive' => 'reshares',
			'reshare_cat'         => 'reshare_cat',
			'reshare_cat_slug'    => 'reshare-category',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'reshare_cat' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'tweaks_column_title' => _x( 'Reshare Categories', 'Modules: Reshare: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'noops' => [
				'reshare_cpt' => _nx_noop( 'Reshare', 'Reshares', 'Modules: Reshare: Noop', GEDITORIAL_TEXTDOMAIN ),
				'reshare_cat' => _nx_noop( 'Reshare Category', 'Reshare Categories', 'Modules: Reshare: Noop', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	protected function get_global_supports()
	{
		return [
			'reshare_cpt' => [
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'comments',
				'revisions',
				'date-picker', // gPersianDate
			],
		];
	}

	public function meta_post_types( $post_types )
	{
		return array_merge( $post_types, [ $this->constant( 'reshare_cpt' ) ] );
	}

	public function after_setup_theme()
	{
		$this->register_post_type_thumbnail( 'reshare_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_post_type( 'reshare_cpt' );

		$this->register_taxonomy( 'reshare_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'reshare_cpt' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'reshare_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				$this->_tweaks_taxonomy();
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'reshare_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'reshare_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'reshare_cpt', $counts ) );
	}

	public function restrict_manage_posts( $post_type, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'reshare_cat' );
	}

	public function parse_query( $query )
	{
		$this->do_parse_query_taxes( $query, 'reshare_cat' );
	}
}

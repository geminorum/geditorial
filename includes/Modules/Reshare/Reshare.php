<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\O2O;

class Reshare extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'reshare',
			'title' => _x( 'Reshare', 'Modules: Reshare', 'geditorial' ),
			'desc'  => _x( 'Contents from Other Sources', 'Modules: Reshare', 'geditorial' ),
			'icon'  => 'share-alt',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'comment_status',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'reshare_cpt', [
					'title',
					'editor',
					'excerpt',
					'author',
					'thumbnail',
					'comments',
					'revisions',
					'date-picker',
					'editorial-meta',
					'editorial-roles',
				] ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'reshare_cpt'         => 'reshare',
			'reshare_cpt_archive' => 'reshares',
			'reshare_cat'         => 'reshare_category',
			'reshare_cat_slug'    => 'reshare-categories',

			'o2o_name' => 'reshares_to_posts',
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
				'tweaks_column_title' => _x( 'Reshare Categories', 'Column Title', 'geditorial-reshare' ),
			],
			'noops' => [
				'reshare_cpt' => _nx_noop( 'Reshare', 'Reshares', 'Noop', 'geditorial-reshare' ),
				'reshare_cat' => _nx_noop( 'Reshare Category', 'Reshare Categories', 'Noop', 'geditorial-reshare' ),
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'reshare_cpt' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'reshare_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'reshare_cat', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'reshare_cpt' );

		$this->register_posttype( 'reshare_cpt' );
	}

	public function wp_loaded()
	{
		return; // FIXME

		$name = $this->constant( 'o2o_name' );
		$args = [
			'name'       => $name,
			'from'       => $this->constant( 'reshare_cpt' ),
			'to'         => $this->posttypes( 'reshare_cpt' ),
			'reciprocal' => TRUE,
		];

		if ( O2O\API::registerConnectionType( $args ) )
			$this->o2o = $name;
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'reshare_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->action( 'restrict_manage_posts', 2, 12 );
				$this->action( 'parse_query' );

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
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

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'reshare_cat' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'reshare_cat' );
	}
}

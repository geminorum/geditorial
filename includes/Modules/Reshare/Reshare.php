<?php namespace geminorum\gEditorial\Modules\Reshare;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;

class Reshare extends gEditorial\Module
{
	use Internals\CoreDashboard;

	public static function module()
	{
		return [
			'name'   => 'reshare',
			'title'  => _x( 'Reshare', 'Modules: Reshare', 'geditorial' ),
			'desc'   => _x( 'Contents from Other Sources', 'Modules: Reshare', 'geditorial' ),
			'icon'   => 'share-alt',
			'access' => 'beta',
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
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
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
			'primary_posttype' => 'reshare',
			'primary_taxonomy' => 'reshare_category',

			'o2o_name' => 'reshares_to_posts',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'primary_taxonomy' => NULL,
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'noops' => [
				'primary_posttype' => _n_noop( 'Reshare', 'Reshares', 'geditorial-reshare' ),
				'primary_taxonomy' => _n_noop( 'Reshare Category', 'Reshare Categories', 'geditorial-reshare' ),
			],
		];
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( $extra + [ $this->constant( 'primary_posttype' ) ] ) );
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
			'meta_box_cb'        => NULL, // default meta box
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
		], 'primary_posttype' );

		$this->register_posttype( 'primary_posttype', [
			'primary_taxonomy' => $this->constant( 'primary_taxonomy' ),
		] );
	}

	public function o2o_init()
	{
		$this->_o2o = Services\O2O\API::registerConnectionType( [
			'name' => $this->constant( 'o2o_name' ),
			'from' => $this->constant( 'primary_posttype' ),
			'to'   => $this->posttypes( 'primary_posttype' ),

			'reciprocal' => TRUE,
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );
				$this->_hook_post_updated_messages( 'primary_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_screen_restrict_taxonomies();
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );

				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}
		}
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'primary_taxonomy' ];
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}
}

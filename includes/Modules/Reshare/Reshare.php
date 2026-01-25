<?php namespace geminorum\gEditorial\Modules\Reshare;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Reshare extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;

	public static function module()
	{
		return [
			'name'     => 'reshare',
			'title'    => _x( 'Reshare', 'Modules: Reshare', 'geditorial-admin' ),
			'desc'     => _x( 'Contents from Other Sources', 'Modules: Reshare', 'geditorial-admin' ),
			'icon'     => 'share-alt',
			'access'   => 'beta',
			'keywords' => [
				'manual-connect',
				'cptmodule',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				'comment_status',
			],
			'_supports' => [
				'assign_default_term',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype' ),
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
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'primary_posttype' ),
			], $this->keep_posttypes )
		);
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
		], 'primary_posttype', [
			'custom_icon' => $this->module->icon,
		] );

		$this->register_posttype( 'primary_posttype', [], [
			'primary_taxonomy' => TRUE,
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

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( 'primary_taxonomy' );
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}
}

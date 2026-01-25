<?php namespace geminorum\gEditorial\Modules\Pitches;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Pitches extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;

	// @EXAMPLE: http://useridea.idea.informer.com/
	// https://woocommerce.com/feature-requests/woocommerce/

	public static function module()
	{
		return [
			'name'     => 'pitches',
			'title'    => _x( 'Pitches', 'Modules: Pitches', 'geditorial-admin' ),
			'desc'     => _x( 'Keep Track of Ideas', 'Modules: Pitches', 'geditorial-admin' ),
			'icon'     => 'lightbulb',
			'access'   => 'beta',
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
		], 'primary_posttype', [
			'custom_icon' => $this->module->icon,
		] );

		$this->register_taxonomy( 'primary_subterm', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'primary_posttype', [
			'custom_icon' => 'clipboard',
		] );

		$this->register_posttype( 'primary_posttype', [], [
			'primary_taxonomy' => TRUE,
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->posttypes__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'primary_taxonomy',
					'primary_subterm',
				] );
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

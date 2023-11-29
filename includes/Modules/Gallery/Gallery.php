<?php namespace geminorum\gEditorial\Modules\Gallery;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Settings;

class Gallery extends gEditorial\Module
{
	use Internals\CoreDashboard;
	use Internals\CoreRestrictPosts;

	public static function module()
	{
		return [
			'name'   => 'gallery',
			'title'  => _x( 'Gallery', 'Modules: Gallery', 'geditorial-admin' ),
			'desc'   => _x( 'Photo Directory', 'Modules: Gallery', 'geditorial-admin' ),
			'icon'   => 'format-gallery',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'comment_status',
			],
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'album_cpt', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'album_cpt'         => 'photo_album',
			'album_cpt_slug'    => 'album',
			'album_cpt_archive' => 'albums',
			'album_cat'         => 'photo_gallery',
			'album_cat_slug'    => 'gallery',
			'photo_tag'         => 'photo_tag',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'album_cat' => 'format-image',
				'photo_tag' => 'images-alt2',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'album_cpt' => _n_noop( 'Photo Album', 'Photo Albums', 'geditorial-gallery' ),
				'album_cat' => _n_noop( 'Album Gallery', 'Album Galleries', 'geditorial-gallery' ),
				'photo_tag' => _n_noop( 'Photo Tag', 'Photo Tags', 'geditorial-gallery' ),
			],
			'labels' => [
				'album_cpt' => [
					'menu_name'      => _x( 'Gallery', 'Label: Menu Name', 'geditorial-gallery' ),
					'featured_image' => _x( 'Featured Photo', 'Label: Featured Image', 'geditorial-gallery' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		// $strings['misc'] = [];

		return $strings;
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( $extra + [ $this->constant( 'album_cpt' ) ] ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'album_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'album_cat', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'album_cpt' );

		$this->register_taxonomy( 'photo_tag', [
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], [ 'attachments' ] );

		$this->register_posttype( 'album_cpt' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'album_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );
				$this->_hook_post_updated_messages( 'album_cpt' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'album_cpt' );
				$this->corerestrictposts__hook_screen_taxonomies( 'album_cat' );
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'album_cpt' ) )
			$items[] = $glance;

		return $items;
	}
}

<?php namespace geminorum\gEditorial\Modules\Gallery;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

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
				$this->settings_supports_option( 'album_posttype', TRUE ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'album_posttype'         => 'photo_album',
			'album_posttype_slug'    => 'album',
			'album_posttype_archive' => 'albums',
			'category_taxonomy'      => 'photo_gallery',
			'category_taxonomy_slug' => 'gallery',
			'tag_taxonomy'           => 'photo_tag',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'album_posttype'    => _n_noop( 'Photo Album', 'Photo Albums', 'geditorial-gallery' ),
				'category_taxonomy' => _n_noop( 'Album Gallery', 'Album Galleries', 'geditorial-gallery' ),
				'tag_taxonomy'      => _n_noop( 'Photo Tag', 'Photo Tags', 'geditorial-gallery' ),
			],
			'labels' => [
				'album_posttype' => [
					'menu_name'      => _x( 'Gallery', 'Label: Menu Name', 'geditorial-gallery' ),
					'featured_image' => _x( 'Featured Photo', 'Label: Featured Image', 'geditorial-gallery' ),
				],
			],
		];

		return $strings;
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				$this->constant( 'album_posttype' ),
			], $this->keep_posttypes )
		);
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'album_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'show_in_nav_menus'  => TRUE,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'album_posttype', [
			'custom_icon' => 'format-image',
		] );

		$this->register_taxonomy( 'tag_taxonomy', [
			'meta_box_cb'        => NULL,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], [ 'attachments' ], [
			'custom_icon' => 'images-alt2',
		] );

		$this->register_posttype( 'album_posttype', [], [
			'custom_icon' => $this->module->icon,
		] );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == $this->constant( 'album_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->comments__handle_default_status( $screen->post_type );
				$this->_hook_post_updated_messages( 'album_posttype' );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_bulk_post_updated_messages( 'album_posttype' );
				$this->corerestrictposts__hook_screen_taxonomies( 'category_taxonomy' );
			}
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'album_posttype' ) )
			$items[] = $glance;

		return $items;
	}
}

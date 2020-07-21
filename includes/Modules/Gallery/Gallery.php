<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;

class Gallery extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'gallery',
			'title' => _x( 'Gallery', 'Modules: Gallery', 'geditorial' ),
			'desc'  => _x( 'Photo Directory', 'Modules: Gallery', 'geditorial' ),
			'icon'  => 'format-gallery',
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
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'album_cpt' => [
				'menu_name' => _x( 'Gallery', 'Posttype Menu', 'geditorial-gallery' ),
				'featured'  => _x( 'Featured Photo', 'Posttype Featured', 'geditorial-gallery' ),
			],
			'album_cat' => [
				'tweaks_column_title' => _x( 'Album Galleries', 'Column Title', 'geditorial-gallery' ),
			],
			'photo_tag' => [
				'tweaks_column_title' => _x( 'Photo Tags', 'Column Title', 'geditorial-gallery' ),
			],
		];

		$strings['terms'] = [
			'album_cat' => [],
		];

		return $strings;
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'album_cpt' ) );
	}

	public function before_settings( $module = FALSE )
	{
		if ( isset( $_POST['install_def_album_cats'] ) )
			$this->insert_default_terms( 'album_cat' );

		$this->help_tab_default_terms( 'album_cat' );
	}

	public function default_buttons( $module = FALSE )
	{
		parent::default_buttons( $module );

		$this->register_button( 'install_def_album_cats', _x( 'Install Default Album Cats', 'Button', 'geditorial-gallery' ) );
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

	public function meta_box_cb_album_cat( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'album_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'album_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'album_cpt', $counts ) );
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'album_cat' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'album_cat' );
	}
}

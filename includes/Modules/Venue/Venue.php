<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Venue extends gEditorial\Module
{

	// protected $partials = [ 'Helper' ];

	public static function module()
	{
		return [
			'name'  => 'venue',
			'title' => _x( 'Venue', 'Modules: Venue', 'geditorial' ),
			'desc'  => _x( 'Place Listings', 'Modules: Venue', 'geditorial' ),
			'icon'  => 'location-alt',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Place Facilities', 'Settings', 'geditorial-venue' ),
					'description' => _x( 'Facility taxonomy for the places and supported post-types.', 'Settings', 'geditorial-venue' ),
				],
				'comment_status',
			],
			'_editlist' => [
				'admin_ordering',
				'admin_restrict',
			],
			'_editpost' => [
				'extra_metadata' => _x( 'Specifies location based on the actual latitude and longitude.', 'Settings', 'geditorial-venue' ),
			],
			'_frontend' => [
				'insert_cover',
				'insert_priority',
				'posttype_feeds',
				'posttype_pages',
				[
					'field'       => 'redirect_archives',
					'type'        => 'url',
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-venue' ),
					'description' => _x( 'Redirects place archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-venue' ),
					'placeholder' => URL::home( 'campus' ),
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'thumbnail_support',
				$this->settings_supports_option( 'place_cpt', [
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'editorial-roles',
				] ),
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'place_cpt'         => 'place',
			'place_cpt_archive' => 'places',
			'place_tax'         => 'places',
			'place_cat'         => 'place_category',
			'facility_tax'      => 'place_facility',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'place_tax'    => NULL,
				'place_cat'    => 'category',
				'facility_tax' => 'building',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'place_tax'    => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'place_cpt'    => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'place_cat'    => _n_noop( 'Place Category', 'Place Categories', 'geditorial-venue' ),
				'facility_tax' => _n_noop( 'Facility', 'Facilities', 'geditorial-venue' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'place_tax' => [
				'tweaks_column_title' => _x( 'Venue', 'Column Title', 'geditorial-venue' ),
				'meta_box_title'      => _x( 'Connected to this Place', 'Column Title', 'geditorial-venue' ),
				'show_option_none'    => _x( '&ndash; Select Place &ndash;', 'Select Option None', 'geditorial-venue' ),
			],
			'place_cat' => [
				'tweaks_column_title' => _x( 'Place Categories', 'Column Title', 'geditorial-venue' ),
			],
			'facility_tax' => [
				'tweaks_column_title' => _x( 'Place Facilities', 'Column Title', 'geditorial-venue' ),
				'show_option_none'    => _x( '&ndash; Select Facility &ndash;', 'Select Option None', 'geditorial-venue' ),
			],
			'meta_box_title'         => _x( 'Place Details', 'MetaBox Title', 'geditorial-venue' ),
			'tweaks_column_title'    => _x( 'Places', 'Column Title', 'geditorial-venue' ),
			'connected_column_title' => _x( 'Connected Places', 'Column Title', 'geditorial-venue' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->constant( 'place_cpt' ) => [
				'parent_complex' => [
					'title'       => _x( 'Parent Complex', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Parent complex title of the location', 'Field Description', 'geditorial-venue' ),
					'type'        => 'title_before',
				],
				'full_title' => [
					'title'       => _x( 'Full Title', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Full title of the location', 'Field Description', 'geditorial-venue' ),
					'type'        => 'title_after',
				],
				'street_address' => [
					'title'       => _x( 'Street Address', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Full street address, including city, state etc.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'note',
				],
				// FIXME: move to `extra_metadata`
				'geo_latitude' => [
					'title'       => _x( 'Latitude', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'The latitude (in decimal notation) for this location.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'code',
				],
				'geo_longitude' => [
					'title'       => _x( 'Longitude', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'The longitude (in decimal notation) for this location.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'code',
				],
			],
		];
	}

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded( $this->constant( 'place_cpt' ) );
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'place_cpt' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'place_cat', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'place_cpt' );

		// FIXME: maybe show in quick box
		$this->register_taxonomy( 'place_tax', [
			'show_ui'      => TRUE,
			'show_in_menu' => FALSE,
			'hierarchical' => TRUE,
		] );

		if ( $this->get_setting( 'subterms_support' ) )
			$this->register_taxonomy( 'facility_tax', [
				'hierarchical'       => TRUE,
				'meta_box_cb'        => NULL,
				'show_admin_column'  => FALSE,
				'show_in_quick_edit' => FALSE,
				'show_in_nav_menus'  => TRUE,
			], $this->posttypes( 'place_cpt' ) );

		$this->register_posttype( 'place_cpt', [
			'hierarchical' => TRUE,
			'rewrite'      => [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		] );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'place_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->get_linked_post_id( $term, 'place_cpt', 'place_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'place_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, 'place_cpt' ) )
			$this->_sync_linked( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'facility_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'place_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'post_updated_messages' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->filter_false_module( 'meta', 'mainbox_callback', 12 );
				$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
				$this->filter_false_module( 'tweaks', 'metabox_parent' );
				remove_meta_box( 'pageparentdiv', $screen, 'side' );

				$this->class_metabox( $screen, 'mainbox' );
				add_meta_box( $this->classs( 'mainbox' ),
					$this->get_meta_box_title( 'place_cpt', FALSE ),
					[ $this, 'render_mainbox_metabox' ],
					$screen,
					'side',
					'high'
				);

				$this->class_metabox( $screen, 'listbox' );
				add_meta_box( $this->classs( 'listbox' ),
					$this->get_meta_box_title( 'place_tax' ),
					[ $this, 'render_listbox_metabox' ],
					$screen,
					'advanced',
					'low'
				);

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				if ( $this->get_setting( 'admin_restrict', FALSE ) ) {
					$this->action( 'restrict_manage_posts', 2, 12 );
					$this->filter( 'parse_query' );
				}

				$this->action_module( 'meta', 'column_row', 3 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_sync_linked( $screen->post_type );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->class_metabox( $screen, 'linkedbox' );
				add_meta_box( $this->classs( 'linkedbox' ),
					$this->get_meta_box_title_posttype( 'place_cpt' ),
					[ $this, 'render_linkedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_linkedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					$this->action( 'restrict_manage_posts', 2, 12, 'supported' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );
			}

			$this->_hook_store_metabox( $screen->post_type );
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );
	}

	private function _sync_linked( $posttype )
	{
		$this->action( 'save_post', 3, 20 );
		$this->action( 'post_updated', 3, 20 );

		$this->action( 'wp_trash_post' );
		$this->action( 'untrash_post' );
		$this->action( 'before_delete_post' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'place_cpt' ) );
		// $this->add_posttype_fields_supported(); FIXME: add fields first
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'place_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'place_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->get_linked_post_id( $term, 'place_cpt', 'place_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		if ( ! $this->is_save_post( $post_after, 'place_cpt' ) )
			return;

		if ( 'trash' == $post_after->post_status )
			return;

		if ( empty( $post_before->post_name ) )
			$post_before->post_name = sanitize_title( $post_before->post_title );

		if ( empty( $post_after->post_name ) )
			$post_after->post_name = sanitize_title( $post_after->post_title );

		$args = [
			'name'        => $post_after->post_title,
			'slug'        => $post_after->post_name,
			'description' => $post_after->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		];

		$the_term = get_term_by( 'slug', $post_before->post_name, $this->constant( 'place_tax' ) );

		if ( FALSE === $the_term ) {
			$the_term = get_term_by( 'slug', $post_after->post_name, $this->constant( 'place_tax' ) );
			if ( FALSE === $the_term )
				$term = wp_insert_term( $post_after->post_title, $this->constant( 'place_tax' ), $args );
			else
				$term = wp_update_term( $the_term->term_id, $this->constant( 'place_tax' ), $args );
		} else {
			$term = wp_update_term( $the_term->term_id, $this->constant( 'place_tax' ), $args );
		}

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_id, $term['term_id'], 'place_cpt', 'place_tax' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( $update )
			return;

		if ( ! $this->is_save_post( $post ) )
			return;

		if ( empty( $post->post_name ) )
			$post->post_name = sanitize_title( $post->post_title );

		$args = [
			'name'        => $post->post_title,
			'slug'        => $post->post_name,
			'description' => $post->post_excerpt,
			// 'parent'      => ( isset( $parent_term_id ) ? $parent_term_id : 0 ),
		];

		$term = wp_insert_term( $post->post_title, $this->constant( 'place_tax' ), $args );

		if ( ! is_wp_error( $term ) )
			$this->set_linked_term( $post_id, $term['term_id'], 'place_cpt', 'place_tax' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->do_trash_post( $post_id, 'place_cpt', 'place_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->do_untrash_post( $post_id, 'place_cpt', 'place_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->do_before_delete_post( $post_id, 'place_cpt', 'place_tax' );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'place_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'place_cpt', $counts ) );
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_taxes( 'place_cat' );
	}

	public function restrict_manage_posts_supported( $posttype, $which )
	{
		$this->do_restrict_manage_posts_posts( 'place_tax', 'place_cpt' );
	}

	public function parse_query( &$query )
	{
		$this->do_parse_query_taxes( $query, 'place_cat' );
	}

	public function meta_box_cb_place_cat( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function render_linkedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'place_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'place_cpt' ) );

		} else {

			$this->actions( 'render_linkedbox_metabox', $post, $box, NULL, 'linkedbox_place' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'linkedbox_place' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		$this->do_render_metabox_assoc( $post, 'place_cpt', 'place_tax', 'facility_tax' );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->do_store_metabox_assoc( $post, 'place_cpt', 'place_tax', 'facility_tax' );
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_metabox', $post, $box, NULL, 'mainbox' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'mainbox' );

			MetaBox::fieldPostMenuOrder( $post );
			MetaBox::fieldPostParent( $post );

		echo '</div>';
	}

	public function render_listbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'render_listbox_metabox', $post, $box, NULL, 'listbox_place' );

			$term = $this->get_linked_term( $post->ID, 'place_cpt', 'place_tax' );

			if ( $list = MetaBox::getTermPosts( $this->constant( 'place_tax' ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo HTML::wrap( _x( 'No items connected!', 'Message', 'geditorial-venue' ), 'field-wrap -empty' );

		echo '</div>';
	}

	public function tweaks_column_attr( $post )
	{
		$posts = $this->get_linked_posts( $post->ID, 'place_cpt', 'place_tax' );
		$count = count( $posts );

		if ( ! $count )
			return;

		echo '<li class="-row -venue -connected">';

			echo $this->get_column_icon( FALSE, NULL, $this->get_column_title( 'connected', 'place_cpt' ) );

			$posttypes = array_unique( array_map( function( $r ){
				return $r->post_type;
			}, $posts ) );

			$args = [ $this->constant( 'place_tax' ) => $post->post_name ];

			if ( empty( $this->cache_posttypes ) )
				$this->cache_posttypes = PostType::get( 2 );

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Title Attr', 'geditorial-venue' ),
					'target' => '_blank',
				], $this->cache_posttypes[$posttype] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}
}

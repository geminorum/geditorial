<?php namespace geminorum\gEditorial\Modules\Venue;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;

class Venue extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'venue',
			'title' => _x( 'Venue', 'Modules: Venue', 'geditorial' ),
			'desc'  => _x( 'Place Listings', 'Modules: Venue', 'geditorial' ),
			'icon'  => 'location-alt',
		];
	}

	// TODO: custom list title for each supported posttypes
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
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'place_cat' ),
					$this->get_taxonomy_label( 'place_cat', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
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
			'_content' => [
				'display_searchform',
				'empty_content',
				'archive_title',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'shortcode_support',
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
			'place_cat_slug'    => 'place-categories',
			'facility_tax'      => 'place_facility',
			'facility_tax_slug' => 'place-facilities',

			'place_shortcode' => 'place',
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
				'phone_number' => [
					'title'       => _x( 'Phone Number', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Full contact number for the location.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'phone',
				],
				'website_url' => [
					'title'       => _x( 'Website URL', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Official website URL of the location.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'link',
				],
				'map_embed_url' => [
					'title'       => _x( 'Map Embed URL', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Embeddable map URL of the location.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'link',
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

		$this->paired_register_objects( 'place_cpt', 'place_tax', 'facility_tax' );

		$this->register_shortcode( 'place_shortcode' );

		if ( is_admin() )
			return;

		$this->filter( 'term_link', 3 );
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'place_tax' ) ) ) {

			$term = get_queried_object();

			if ( $post_id = $this->paired_get_to_post_id( $term, 'place_cpt', 'place_tax' ) )
				WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'place_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress::redirect( $redirect, 301 );
		}
	}

	public function init_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'place_cpt' ) )
			$this->_hook_paired_to( $posttype );
	}

	public function setup_restapi()
	{
		$this->_hook_paired_to( $this->constant( 'place_cpt' ) );
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

				$this->_hook_paired_to( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );
				$this->filter( 'bulk_post_updated_messages', 2 );

				$this->_hook_screen_restrict_taxonomies();
				$this->action( 'restrict_manage_posts', 2, 20, 'restrict_taxonomy' );
				$this->action( 'parse_query', 1, 12, 'restrict_taxonomy' );

				$this->action_module( 'meta', 'column_row', 3 );
				$this->action_module( 'tweaks', 'column_attr' );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_paired_to( $screen->post_type );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				if ( $subterms )
					remove_meta_box( $subterms.'div', $screen->post_type, 'side' );

				$this->class_metabox( $screen, 'pairedbox' );
				add_meta_box( $this->classs( 'pairedbox' ),
					$this->get_meta_box_title_posttype( 'place_cpt' ),
					[ $this, 'render_pairedbox_metabox' ],
					$screen,
					'side'
				);

				add_action( $this->hook( 'render_pairedbox_metabox' ), [ $this, 'render_metabox' ], 10, 4 );
				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->action( 'restrict_manage_posts', 2, 12, 'restrict_paired' );
				$this->_hook_screen_restrict_paired();

				$this->action_module( 'meta', 'column_row', 3 );
				$this->filter_module( 'tweaks', 'taxonomy_info', 3 );

				$this->_hook_store_metabox( $screen->post_type );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [ 'place_cpt', 'place_tax', 'facility_tax', 'place_cat' ];
	}

	protected function get_taxonomies_for_restrict_manage_posts()
	{
		return [ 'place_cat' ];
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'place_cpt' ) );
		// $this->add_posttype_fields_supported(); // FIXME: add fields first

		$this->filter( 'meta_field', 5, 9, FALSE, 'geditorial' );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'place_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->do_template_include( $template, 'place_cpt' );
	}

	public function template_get_archive_content()
	{
		$html = $this->get_search_form( 'place_cpt' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->alphabet->shortcode_posts( [ 'post_type' => $this->constant( 'place_cpt' ) ] );

		else
			$html.= $this->place_shortcode( [
				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
			] );

		return $html;
	}

	public function place_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'place_cpt' ),
			$this->constant( 'place_tax' ),
			array_merge( [
				'posttypes' => $this->posttypes(),
				'orderby'   => 'menu_order',
			], (array) $atts ),
			$content,
			$this->constant( 'place_shortcode', $tag ),
			$this->key
		);
	}

	public function term_link( $link, $term, $taxonomy )
	{
		if ( $this->constant( 'place_tax' ) != $taxonomy )
			return $link;

		if ( $post_id = $this->paired_get_to_post_id( $term, 'place_cpt', 'place_tax' ) )
			return get_permalink( $post_id );

		return $link;
	}

	public function post_updated( $post_id, $post_after, $post_before )
	{
		$this->paired_do_save_to_post_update( $post_after, $post_before, 'place_cpt', 'place_tax' );
	}

	public function save_post( $post_id, $post, $update )
	{
		// we handle updates on another action, see : post_updated()
		if ( ! $update )
			$this->paired_do_save_to_post_new( $post, 'place_cpt', 'place_tax' );
	}

	public function wp_trash_post( $post_id )
	{
		$this->paired_do_trash_to_post( $post_id, 'place_cpt', 'place_tax' );
	}

	public function untrash_post( $post_id )
	{
		$this->paired_do_untrash_to_post( $post_id, 'place_cpt', 'place_tax' );
	}

	public function before_delete_post( $post_id )
	{
		$this->paired_do_before_delete_to_post( $post_id, 'place_cpt', 'place_tax' );
	}

	public function post_updated_messages( $messages )
	{
		return array_merge( $messages, $this->get_post_updated_messages( 'place_cpt' ) );
	}

	public function bulk_post_updated_messages( $messages, $counts )
	{
		return array_merge( $messages, $this->get_bulk_post_updated_messages( 'place_cpt', $counts ) );
	}

	public function meta_box_cb_place_cat( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	public function render_pairedbox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );

		if ( ! Taxonomy::hasTerms( $this->constant( 'place_tax' ) ) ) {

			MetaBox::fieldEmptyPostType( $this->constant( 'place_cpt' ) );

		} else {

			$this->actions( 'render_pairedbox_metabox', $post, $box, NULL, 'pairedbox_place' );

			do_action( 'geditorial_meta_render_metabox', $post, $box, NULL, 'pairedbox_place' );
		}

		echo '</div>';
	}

	public function render_metabox( $post, $box, $fields = NULL, $context = NULL )
	{
		$this->paired_do_render_metabox( $post, 'place_cpt', 'place_tax', 'facility_tax' );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$this->paired_do_store_metabox( $post, 'place_cpt', 'place_tax', 'facility_tax' );
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

		$this->paired_render_listbox_metabox( $post, $box, 'place_cpt', 'place_tax' );
	}

	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		return $this->paired_do_get_to_posts( 'place_cpt', 'place_tax', $post, $single, $published );
	}

	public function tweaks_column_attr( $post )
	{
		$this->paired_tweaks_column_attr( $post, 'place_cpt', 'place_tax' );
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw )
	{
		switch ( $field ) {

			case 'map_embed_url':
				return Template::doEmbedShortCode( trim( $raw ) );
		}

		return $meta;
	}
}

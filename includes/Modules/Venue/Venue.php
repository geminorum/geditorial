<?php namespace geminorum\gEditorial\Modules\Venue;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;

class Venue extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\TemplatePostType;

	public static function module()
	{
		return [
			'name'   => 'venue',
			'title'  => _x( 'Venue', 'Modules: Venue', 'geditorial' ),
			'desc'   => _x( 'Place Listings', 'Modules: Venue', 'geditorial' ),
			'icon'   => 'location-alt',
			'access' => 'beta',
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
					'placeholder' => Core\URL::home( 'campus' ),
				],
			],
			'_content' => [
				'archive_override',
				'display_searchform',
				'empty_content',
				'archive_title' => [ NULL, $this->get_posttype_label( 'place_cpt', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'assign_default_term',
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
			'place_cpt'    => 'place',
			'place_tax'    => 'places',
			'place_cat'    => 'place_category',
			'facility_tax' => 'place_facility',

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
				'place_cpt'    => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'place_tax'    => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'place_cat'    => _n_noop( 'Place Category', 'Place Categories', 'geditorial-venue' ),
				'facility_tax' => _n_noop( 'Facility', 'Facilities', 'geditorial-venue' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Place', 'Misc: `column_icon_title`', 'geditorial-venue' ),
		];

		$strings['metabox'] = [
			'place_cpt' => [
				'metabox_title' => _x( 'Place Details', 'Label: MetaBox Title', 'geditorial-venue' ),
				'listbox_title' => _x( 'Connected to this Place', 'Label: MetaBox Title', 'geditorial-venue' ),
			],
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
				'postal_code' => [
					'type' => 'postcode',
				],
				'street_address' => [
					'title'       => _x( 'Street Address', 'Field Title', 'geditorial-venue' ),
					'description' => _x( 'Full street address, including city, state etc.', 'Field Description', 'geditorial-venue' ),
					'type'        => 'address',
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
					'type'        => 'embed',
				],
				// FIXME: move to `extra_metadata`
				// FIXME: see `Geo` Module
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
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'place_cpt' );

		$this->paired_register_objects( 'place_cpt', 'place_tax', 'facility_tax', 'place_cat' );

		$this->register_shortcode( 'place_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'place_tax' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'place_cpt', 'place_tax' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'place_cpt' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'facility_tax' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'place_cpt' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_post_updated_messages( 'place_cpt' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->action_module( 'meta', 'column_row', 3 );

				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'menu_order', 'ASC' );
				$this->_hook_bulk_post_updated_messages( 'place_cpt' );
				$this->pairedcore__hook_sync_paired();
				$this->pairedadmin__hook_tweaks_column_connected();
				$this->corerestrictposts__hook_screen_taxonomies( 'place_cat' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'place_cpt' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				$this->_metabox_remove_subterm( $screen, $subterms );
				$this->_hook_paired_pairedbox( $screen );
				$this->_hook_paired_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );
				$this->paired__hook_screen_restrictposts();

				// $this->action_module( 'meta', 'column_row', 3 );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	protected function paired_get_paired_constants()
	{
		return [
			'place_cpt',
			'place_tax',
			'facility_tax',
			'place_cat',
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'place_cpt' ) );
		// $this->add_posttype_fields_supported(); // FIXME: add fields first
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'place_cpt' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'place_cpt' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'place_cpt' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'post_type' => $posttype, // $this->constant( 'place_cpt' ),
			] );

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

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( 'place_cpt', 'place_tax' );
			}
		}

		Scripts::enqueueThickBox();
	}

	protected function render_tools_html( $uri, $sub )
	{
		return $this->paired_tools_render_tablelist( 'place_cpt', 'place_tax', NULL,
			_x( 'Venue Tools', 'Header', 'geditorial-venue' ) );
	}

	protected function render_tools_html_after( $uri, $sub )
	{
		$this->paired_tools_render_card( 'place_cpt', 'place_tax' );
	}
}

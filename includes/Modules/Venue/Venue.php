<?php namespace geminorum\gEditorial\Modules\Venue;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;

class Venue extends gEditorial\Module
{
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedMetaBox;
	use Internals\PairedRowActions;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;
	use Internals\TemplatePostType;

	public static function module()
	{
		return [
			'name'     => 'venue',
			'title'    => _x( 'Venue', 'Modules: Venue', 'geditorial-admin' ),
			'desc'     => _x( 'Place Listings', 'Modules: Venue', 'geditorial-admin' ),
			'icon'     => 'location-alt',
			'access'   => 'beta',
			'keywords' => [
				'geo',
				'place',
				'location',
				'pairedmodule',
			],
		];
	}

	// TODO: custom list title for each supported posttypes
	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'paired_force_parents',
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Place Facilities', 'Settings', 'geditorial-venue' ),
					'description' => _x( 'Facility taxonomy for the places and supported post-types.', 'Settings', 'geditorial-venue' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'category_taxonomy' ),
					$this->get_taxonomy_label( 'category_taxonomy', 'no_terms' ),
				],
			],
			'_editlist' => [
				'admin_ordering',
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
				'archive_title' => [ NULL, $this->get_posttype_label( 'place_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'assign_default_term',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'place_posttype', [
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'editorial-roles',
				] ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'place_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'place_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'place_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'place' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'place_posttype'    => 'place',
			'place_paired'      => 'places',
			'category_taxonomy' => 'place_category',
			'facility_taxonomy' => 'place_facility',
			'main_shortcode'    => 'place',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'place_paired'      => NULL,
				'category_taxonomy' => 'category',
				'facility_taxonomy' => 'building',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'place_posttype'    => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'place_paired'      => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'category_taxonomy' => _n_noop( 'Place Category', 'Place Categories', 'geditorial-venue' ),
				'facility_taxonomy' => _n_noop( 'Facility', 'Facilities', 'geditorial-venue' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Place', 'Misc: `column_icon_title`', 'geditorial-venue' ),
		];

		$strings['metabox'] = [
			'place_posttype' => [
				'metabox_title' => _x( 'Place Details', 'Label: MetaBox Title', 'geditorial-venue' ),
				'listbox_title' => _x( 'Connected to this Place', 'Label: MetaBox Title', 'geditorial-venue' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'place_posttype' ) => [
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
						'title'       => _x( 'Postal Code', 'Field Title', 'geditorial-venue' ),
						'description' => _x( 'Postal code of the location.', 'Field Description', 'geditorial-venue' ),
						'type'        => 'postcode',
					],
					'street_address' => [
						'title'       => _x( 'Street Address', 'Field Title', 'geditorial-venue' ),
						'description' => _x( 'Full street address, including city, state etc.', 'Field Description', 'geditorial-venue' ),
						'type'        => 'address',
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
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
					// FIXME: see `Geo` Module
					'geo_latitude' => [
						'title'       => _x( 'Latitude', 'Field Title', 'geditorial-venue' ),
						'description' => _x( 'The latitude (in decimal notation) for this location.', 'Field Description', 'geditorial-venue' ),
						'type'        => 'code',
						'icon'        => 'location',
					],
					'geo_longitude' => [
						'title'       => _x( 'Longitude', 'Field Title', 'geditorial-venue' ),
						'description' => _x( 'The longitude (in decimal notation) for this location.', 'Field Description', 'geditorial-venue' ),
						'type'        => 'code',
						'icon'        => 'location',
					],
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'place_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'place_posttype' );

		$this->paired_register();

		$this->register_shortcode( 'main_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'place_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'place_posttype', 'place_paired' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'place_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );
		}
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'place_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'facility_taxonomy' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'place_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_editform_meta_summary( [
					'street_address' => NULL,
					'postal_code'    => NULL,
					'phone_number'   => NULL,
					'website_url'    => NULL,
				] );

				$this->posttype__media_register_headerbutton( 'place_posttype' );
				$this->_hook_post_updated_messages( 'place_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->modulelinks__register_headerbuttons();
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'menu_order', 'ASC' );
				$this->_hook_bulk_post_updated_messages( 'place_posttype' );
				$this->pairedcore__hook_sync_paired();
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->corerestrictposts__hook_screen_taxonomies( 'category_taxonomy' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'place_posttype' ) ) );

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
			'place_posttype',
			'place_paired',
			'facility_taxonomy',
			'category_taxonomy',
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'place_posttype' ) );
		// $this->add_posttype_fields_supported(); // FIXME: add fields first
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'place_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'place_posttype' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'place_posttype' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'post_type' => $posttype, // $this->constant( 'place_posttype' ),
			] );

		else
			$html.= $this->main_shortcode( [
				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
			] );

		return $html;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'place_posttype' ),
			$this->constant( 'place_paired' ),
			array_merge( [
				'post_id'   => NULL,
				'posttypes' => $this->posttypes(),
				'orderby'   => 'menu_order',
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );
				$this->paired_tools_handle_tablelist( $sub );
			}

			Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen(
			_x( 'Venue Tools', 'Header', 'geditorial-venue' ) );

			$this->paired_tools_render_card( $uri, $sub );

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		return $this->paired_tools_render_before( $uri, $sub );
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );
				$this->paired_imports_handle_tablelist( $sub );
			}

			Scripts::enqueueThickBox();
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		if ( ! $this->paired_imports_render_tablelist( $uri, $sub ) )
			return Info::renderNoImportsAvailable();
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'place_posttype', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}

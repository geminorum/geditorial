<?php namespace geminorum\gEditorial\Modules\Venue;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Venue extends gEditorial\Module
{
	use Internals\AdminEditForm;
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\FramePage;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedAssignment;
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

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'multiple_instances',
				'paired_force_parents',
				'assignment_dock',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Place Facilities', 'Settings', 'geditorial-venue' ),
					'description' => _x( 'Facility taxonomy for the places and supported post-types.', 'Settings', 'geditorial-venue' ),
				],
				'comment_status',
				'paired_exclude_terms' => [
					NULL,
					$this->constant( 'primary_taxonomy' ),
					$this->get_taxonomy_label( 'primary_taxonomy', 'no_terms' ),
				],
			],
			'_roles' => [
				'contents_viewable',
				'paired_manage_restricted',
				'custom_captype',
				'paired_roles',
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
				'archive_title' => [ NULL, $this->get_posttype_label( 'primary_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'assign_default_term',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', [
					'title',
					'editor',
					'excerpt',
					'thumbnail',
					'editorial-roles',
				] ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'place' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'place',
			'primary_paired'   => 'place',
			'primary_taxonomy' => 'place_category',
			'primary_subterm'  => 'place_facility',
			'main_shortcode'   => 'place',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'primary_paired'   => _n_noop( 'Place', 'Places', 'geditorial-venue' ),
				'primary_taxonomy' => _n_noop( 'Place Category', 'Place Categories', 'geditorial-venue' ),
				'primary_subterm'  => _n_noop( 'Facility', 'Facilities', 'geditorial-venue' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Place', 'Misc: `column_icon_title`', 'geditorial-venue' ),
		];

		$strings['metabox'] = [
			'primary_posttype' => [
				'metabox_title' => _x( 'Place Details', 'Label: MetaBox Title', 'geditorial-venue' ),
				'listbox_title' => _x( 'Connected to this Place', 'Label: MetaBox Title', 'geditorial-venue' ),
			],

			'supportedbox_title'  => _x( 'Places', 'MetaBox Title', 'geditorial-venue' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbutton_title' => _x( 'Locations of %2$s', 'Button Title', 'geditorial-venue' ),
			/* translators: `%1$s`: icon markup, `%2$s`: post-type singular name */
			'mainbutton_text'  => _x( '%1$s Manage %2$s Places', 'Button Text', 'geditorial-venue' ),

			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'heading_title' => _x( 'Location Report for %1$s', 'Button Title', 'geditorial-venue' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'primary_posttype' ) => [
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

					'latlng' => [ 'type' => 'latlng' ],
				],
			],
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function init()
	{
		parent::init();

		$viewable = $this->get_setting( 'contents_viewable', TRUE );
		$captype  = $this->get_setting( 'custom_captype', FALSE )
			? $this->constant_plural( 'primary_posttype' )
			: FALSE;

		$this->register_taxonomy( 'primary_taxonomy', [
			'hierarchical'       => TRUE,
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
			'default_term'       => NULL,
			'meta_box_cb'        => '__checklist_terms_callback',
		], 'primary_posttype', [
			'custom_icon' => 'category',
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		$this->paired_register( [], [
			'primary_taxonomy' => TRUE,
			'is_viewable'      => $viewable,
			'custom_captype'   => $captype,
		], [
			'is_viewable'    => $viewable,
			'custom_captype' => $captype,
		] );

		if ( $this->get_setting( 'assignment_dock' ) )
			$this->paired_assignment__init();

		$this->register_shortcode( 'main_shortcode' );

		if ( is_admin() )
			return;

		if ( ! $viewable )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function template_redirect()
	{
		if ( is_tax( $this->constant( 'primary_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'primary_posttype', 'primary_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_post_type_archive( $this->constant( 'primary_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );
		}
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( 'primary_posttype' ) ) {
			$this->pairedadmin__hook_tweaks_column_connected( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'primary_subterm' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'primary_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->_hook_editform_meta_summary( [
					'street_address' => NULL,
					'postal_code'    => NULL,
					'phone_number'   => NULL,
					'website_url'    => NULL,
				] );

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->modulelinks__register_headerbuttons();
				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->coreadmin__hook_admin_ordering( $screen->post_type, 'menu_order', 'ASC' );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedcore__hook_sync_paired();
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->corerestrictposts__hook_screen_taxonomies( [
					'primary_subterm',
					'primary_taxonomy',
				] );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'primary_posttype' ) ) );

			if ( 'edit-tags' == $screen->base ) {

				$this->_hook_paired_taxonomy_bulk_actions( $screen->post_type, $screen->taxonomy );

			} else if ( 'post' == $screen->base ) {

				$this->_metabox_remove_subterm( $screen, $subterms );

				if ( $this->role_can( 'paired' ) ) {

					if ( $this->get_setting( 'assignment_dock' ) ) {

						$this->_hook_general_supportedbox( $screen );
						gEditorial\Scripts::enqueueColorBox();

					} else {

						$this->_hook_paired_pairedbox( $screen );
						$this->_hook_paired_store_metabox( $screen->post_type );
					}

				} else {

					$this->_hook_paired_overviewbox( $screen );
				}

			} else if ( 'edit' == $screen->base ) {

				$this->_hook_paired_store_metabox( $screen->post_type );
				$this->paired__hook_tweaks_column( $screen->post_type, 12 );
				$this->paired__hook_screen_restrictposts();
			}
		}

		// only for supported post-types
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
			'primary_subterm',
			'primary_taxonomy',
			TRUE,   // hierarchical
			FALSE,  // private
			(bool) $this->get_setting( 'assignment_dock' ),   // `terms_related`
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );
		// $this->add_posttype_fields_supported(); // FIXME: add fields first
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'assignment_dock' ) && $this->role_can( 'paired' ) )
			$this->_hook_submenu_adminpage( 'overview', 'exist' );
	}

	public function load_submenu_adminpage()
	{
		$this->_load_submenu_adminpage( 'overview' );
		$this->paired_assignment__load_submenu_adminpage( 'overview' );
	}

	public function render_submenu_adminpage()
	{
		$this->paired_assignment__do_render_iframe_content(
			'overview',
			/* translators: `%s`: post title */
			_x( 'Location Dock for %s', 'Page Title', 'geditorial-venue' ),
			/* translators: `%s`: post title */
			_x( 'Location Overview for %s', 'Page Title', 'geditorial-venue' )
		);
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'primary_posttype' ) );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		$html = $this->get_search_form( 'primary_posttype' );

		if ( gEditorial()->enabled( 'alphabet' ) )
			$html.= gEditorial()->module( 'alphabet' )->shortcode_posts( [
				'posttype'  => $posttype,
				'list_mode' => 'ul',
			] );

		else
			$html.= $this->main_shortcode( [
				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
			] );

		return $html;
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$this->pairedmetabox__render_supportedbox_content( $object, $box, $context, $screen );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'paired',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'primary_paired' ),
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

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen(
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

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		if ( ! $this->paired_imports_render_tablelist( $uri, $sub ) )
			return gEditorial\Info::renderNoImportsAvailable();
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'primary_posttype', $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}

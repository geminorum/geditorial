<?php namespace geminorum\gEditorial\Modules\Dossier;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Dossier extends gEditorial\Module
{
	use Internals\AdminPage;
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
	use Internals\PairedImports;
	use Internals\PairedRest;
	use Internals\PairedTools;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;
	use Internals\QuickPosts;
	use Internals\TemplatePostType;

	public static function module()
	{
		return [
			'name'     => 'dossier',
			'title'    => _x( 'Dossier', 'Modules: Dossier', 'geditorial-admin' ),
			'desc'     => _x( 'Collection of Contents', 'Modules: Dossier', 'geditorial-admin' ),
			'icon'     => 'portfolio',
			'access'   => 'stable',
			'keywords' => [
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
				'paired_manage_restricted',
				[
					'field'       => 'subterms_support',
					'title'       => _x( 'Dossier Sections', 'Settings', 'geditorial-dossier' ),
					'description' => _x( 'Section taxonomy for the dossiers and supported post-types.', 'Settings', 'geditorial-dossier' ),
				],
				'quick_newpost',
				'comment_status',
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
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-dossier' ),
					'description' => _x( 'Redirects dossier archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-dossier' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Settings', 'geditorial-dossier' ),
					'description' => _x( 'Redirects all span archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-dossier' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
			],
			'_content' => [
				'archive_override',
				'archive_title' => [ NULL, $this->get_posttype_label( 'primary_posttype', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant'  => [ NULL, 'dossier' ],
				'span_shortcode_constant'  => [ NULL, 'dossier-span' ],
				'cover_shortcode_constant' => [ NULL, 'dossier-cover' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'dossier',
			'primary_paired'   => 'dossiers',
			'span_taxonomy'    => 'dossier_span',
			'primary_subterm'  => 'dossier_section',

			'main_shortcode'  => 'dossier',
			'span_shortcode'  => 'dossier-span',
			'cover_shortcode' => 'dossier-cover',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Dossier', 'Dossiers', 'geditorial-dossier' ),
				'primary_paired'   => _n_noop( 'Dossier', 'Dossiers', 'geditorial-dossier' ),
				'span_taxonomy'    => _n_noop( 'Span', 'Spans', 'geditorial-dossier' ),
				'primary_subterm'  => _n_noop( 'Section', 'Sections', 'geditorial-dossier' ),
			],
			'labels' => [
				'primary_posttype' => [
					'featured_image' => _x( 'Cover Image', 'Label: Featured Image', 'geditorial-dossier' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Dossier', 'Misc: `column_icon_title`', 'geditorial-dossier' ),
		];

		$strings['metabox'] = [
			'primary_posttype' => [
				'metabox_title' => _x( 'The Dossier', 'MetaBox Title', 'geditorial-dossier' ),
				'listbox_title' => _x( 'In This Dossier', 'Label: MetaBox Title', 'geditorial-dossier' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'span_taxonomy' => gEditorial\Datetime::getYears( '-5 years' ),
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				$this->constant( 'primary_posttype' ) => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'lead'       => [ 'type' => 'postbox_html' ],

					'number_line' => [
						'title'       => _x( 'Number Line', 'Field Title', 'geditorial-dossier' ),
						'description' => _x( 'The dossier number line', 'Field Description', 'geditorial-dossier' ),
						'icon'        => 'menu',
					],

					'highlight'    => [ 'type' => 'note' ],
					'source_title' => [ 'type' => 'text' ],
					'source_url'   => [ 'type' => 'link' ],
					'action_title' => [ 'type' => 'text' ],
					'action_url'   => [ 'type' => 'link' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
				'_supported' => [
					'in_dossier_order' => [
						'title'       => _x( 'Order', 'Field Title', 'geditorial-dossier' ),
						'description' => _x( 'Post order in dossier list', 'Field Description', 'geditorial-dossier' ),
						'type'        => 'number',
						'context'     => 'pairedbox_dossier',
						'icon'        => 'sort',
						'order'       => 400,
					],
				],
			],
		];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'primary_posttype',
			'primary_paired',
			'primary_subterm',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'primary_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'span_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'primary_posttype', [] );

		$this->paired_register( [], [
			'custom_icon' => $this->module->icon,
		], [
			'custom_icon' => 'category',
		] );

		$this->register_shortcode( 'main_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
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

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttype__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->postmeta__hook_meta_column_row( $screen->post_type, TRUE );
				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'primary_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( 'span_taxonomy' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'primary_posttype' ) ) );

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
				$this->postmeta__hook_meta_column_row( $screen->post_type, [
					'in_dossier_order',
				] );
			}
		}

		// only for supported post-types
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );
		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'importitems', 'exist' );

		if ( $this->get_setting( 'quick_newpost' ) ) {
			$this->_hook_submenu_adminpage( 'newpost' );
			$this->action_self( 'newpost_aftercontent', 4, 10, 'menu_order' );
		}
	}

	public function template_redirect()
	{
		if ( $this->_paired && is_tax( $this->constant( 'primary_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'primary_posttype', 'primary_paired' ) )
				WordPress\Redirect::doWP( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_taxonomy' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'primary_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				WordPress\Redirect::doWP( $redirect, 301 );

		} else if ( is_singular( $this->constant( 'primary_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->hook_base( 'content', 'before' ),
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'primary_posttype' ), FALSE );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		return ModuleTemplate::spanTiles();
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'primary_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'primary_posttype' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {
			/* translators: `%s`: order */
			case 'in_dossier_order' : return WordPress\Strings::getCounted( $raw ?: $value, _x( 'Order in Dossier: %s', 'Display', 'geditorial-dossier' ) );
		}

		return $value;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'paired',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'primary_paired' ),
			array_merge( [
				'post_id'     => NULL,
				'posttypes'   => $this->posttypes(),
				'order_cb'    => NULL,                      // NULL for default ordering by meta
				'orderby'     => 'order',                   // order by meta
				// 'order_start' => 'in_dossier_page_start',   // meta field for ordering
				'order_order' => 'in_dossier_order',        // meta field for ordering
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'assigned',
			$this->constant( 'primary_posttype' ),
			$this->constant( 'span_taxonomy' ),
			array_merge( [
				'post_id' => NULL,
			], (array) $atts ),
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'primary_posttype' );
		$args = [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $type, NULL, 'medium' ),
			'type' => $type,
			'echo' => FALSE,
		];

		if ( is_singular( $args['type'] ) )
			$args['id'] = NULL;

		else if ( is_singular() )
			$args['id'] = 'paired';

		if ( ! $html = ModuleTemplate::postImage( array_merge( $args, (array) $atts ) ) )
			return $content;

		return gEditorial\ShortCode::wrap( $html,
			$this->constant( 'cover_shortcode' ),
			array_merge( [ 'wrap' => TRUE ], (array) $atts )
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
			_x( 'Dossiers Tools', 'Header', 'geditorial-dossier' ) );

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

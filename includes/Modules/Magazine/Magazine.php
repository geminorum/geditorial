<?php namespace geminorum\gEditorial\Modules\Magazine;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Magazine extends gEditorial\Module
{
	use Internals\AdminPage;
	use Internals\BulkExports;
	use Internals\CoreAdmin;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\CoreRestrictPosts;
	use Internals\LateChores;
	use Internals\MetaBoxMain;
	use Internals\PairedAdmin;
	use Internals\PairedCore;
	use Internals\PairedFront;
	use Internals\PairedImports;
	use Internals\PairedMetaBox;
	use Internals\PairedRest;
	use Internals\PairedRowActions;
	use Internals\PairedThumbnail;
	use Internals\PairedTools;
	use Internals\PostDate;
	use Internals\PostMeta;
	use Internals\PostTypeOverview;
	use Internals\QuickPosts;
	use Internals\TemplatePostType;

	public static function module()
	{
		return [
			'name'     => 'magazine',
			'title'    => _x( 'Magazine', 'Modules: Magazine', 'geditorial-admin' ),
			'desc'     => _x( 'Magazine Issue Management', 'Modules: Magazine', 'geditorial-admin' ),
			'icon'     => 'book',
			'access'   => 'stable',
			'keywords' => [
				'issue',
				'has-widgets',
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
					'title'       => _x( 'Issue Sections', 'Settings', 'geditorial-magazine' ),
					'description' => _x( 'Section taxonomy for the issues and supported post-types.', 'Settings', 'geditorial-magazine' ),
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
					'title'       => _x( 'Redirect Archives', 'Settings', 'geditorial-magazine' ),
					'description' => _x( 'Redirects issue archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-magazine' ),
					'placeholder' => Core\URL::home( 'archives' ),
				],
				[
					'field'       => 'redirect_spans',
					'type'        => 'url',
					'title'       => _x( 'Redirect Spans', 'Settings', 'geditorial-magazine' ),
					'description' => _x( 'Redirects all span archives to this URL. Leave empty to disable.', 'Settings', 'geditorial-magazine' ),
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
				'override_dates',
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				'thumbnail_fallback',
				$this->settings_supports_option( 'primary_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'primary_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'primary_posttype', 'units' ) ],
			],
			'_constants' => [
				'main_shortcode_constant'  => [ NULL, 'issue' ],
				'span_shortcode_constant'  => [ NULL, 'issue-span' ],
				'cover_shortcode_constant' => [ NULL, 'issue-cover' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'primary_posttype' => 'issue',
			'primary_paired'   => 'issues',
			'span_taxonomy'    => 'issue_span',
			'primary_subterm'  => 'issue_section',

			'main_shortcode'  => 'issue',
			'span_shortcode'  => 'issue-span',
			'cover_shortcode' => 'issue-cover',

			'field_paired_order' => 'in_issue_order',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'primary_posttype' => _n_noop( 'Issue', 'Issues', 'geditorial-magazine' ),
				'primary_paired'   => _n_noop( 'Issue', 'Issues', 'geditorial-magazine' ),
				'span_taxonomy'    => _n_noop( 'Span', 'Spans', 'geditorial-magazine' ),
				'primary_subterm'  => _n_noop( 'Section', 'Sections', 'geditorial-magazine' ),
			],
			'labels' => [
				'primary_posttype' => [
					'featured_image' => _x( 'Cover Image', 'Label: Featured Image', 'geditorial-magazine' ),
				],
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['misc'] = [
			'column_icon_title' => _x( 'Issue', 'Misc: `column_icon_title`', 'geditorial-magazine' ),
		];

		$strings['metabox'] = [
			'primary_posttype' => [
				'metabox_title' => _x( 'The Issue', 'Label: MetaBox Title', 'geditorial-magazine' ),
				'listbox_title' => _x( 'In This Issue', 'Label: MetaBox Title', 'geditorial-magazine' ),
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
						'title'       => _x( 'Number Line', 'Field Title', 'geditorial-magazine' ),
						'description' => _x( 'The issue number line', 'Field Description', 'geditorial-magazine' ),
						'icon'        => 'menu',
					],
					'total_pages' => [
						'title'       => _x( 'Total Pages', 'Field Title', 'geditorial-magazine' ),
						'description' => _x( 'The issue total pages', 'Field Description', 'geditorial-magazine' ),
						'icon'        => 'admin-page',
					],

					'date'         => [ 'type' => 'date',     'quickedit' => TRUE ],
					'datetime'     => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'datestart'    => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'dateend'      => [ 'type' => 'datetime', 'quickedit' => TRUE ],
					'highlight'    => [ 'type' => 'note' ],
					'source_title' => [ 'type' => 'text' ],
					'source_url'   => [ 'type' => 'link' ],
					'action_title' => [ 'type' => 'text' ],
					'action_url'   => [ 'type' => 'link' ],
					'cover_blurb'  => [ 'type' => 'note' ],
					'cover_price'  => [ 'type' => 'price' ],
					'content_fee'  => [ 'type' => 'price' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
				],
				'_supported' => [
					'in_issue_order' => [
						'title'       => _x( 'Order', 'Field Title', 'geditorial-magazine' ),
						'description' => _x( 'Post order in issue list', 'Field Description', 'geditorial-magazine' ),
						'type'        => 'number',
						'context'     => 'pairedbox_issue',
						'icon'        => 'sort',
						'order'       => 400,
					],
					'in_issue_page_start' => [
						'title'       => _x( 'Page Start', 'Field Title', 'geditorial-magazine' ),
						'description' => _x( 'Post start page on issue (printed)', 'Field Description', 'geditorial-magazine' ),
						'type'        => 'number',
						'context'     => 'pairedbox_issue',
						'icon'        => 'media-default',
						'order'       => 410,
					],
					'in_issue_pages' => [
						'title'       => _x( 'Total Pages', 'Field Title', 'geditorial-magazine' ),
						'description' => _x( 'Post total pages on issue (printed)', 'Field Description', 'geditorial-magazine' ),
						'context'     => 'pairedbox_issue',
						'icon'        => 'admin-page',
						'order'       => 420,
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

		$this->_hook_paired_thumbnail_fallback();

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

				$this->comments__handle_default_status( $screen->post_type );
				$this->posttypes__increase_menu_order( $screen->post_type );
				$this->posttypes__media_register_headerbutton( 'primary_posttype' );
				$this->_hook_post_updated_messages( 'primary_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->modulelinks__register_headerbuttons();
				$this->latechores__hook_admin_bulkactions( $screen );
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
					// 'in_issue_order',
					'in_issue_page_start',
					'in_issue_pages',
				] );
			}
		}

		// only for supported post-types
		$this->remove_taxonomy_submenu( $subterms );

		$this->modulelinks__hook_calendar_linked_post( $screen );
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\IssueCover' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields_for( 'meta', 'primary_posttype' );
		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );

		if ( $this->get_setting( 'override_dates', TRUE ) )
			$this->latechores__init_post_aftercare( $this->constant( 'primary_posttype' ) );
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

			case 'in_issue_order':
				return WordPress\Strings::getCounted(
					Core\Number::translate( $raw ?: $value ),
					/* translators: `%s`: order */
					_x( 'Order in Issue: %s', 'Display', 'geditorial-magazine' )
				);

			case 'in_issue_page_start':
				return WordPress\Strings::getCounted(
					Core\Number::translate( $raw ?: $value ),
					/* translators: `%s`: page */
					_x( 'Page in Issue: %s', 'Display', 'geditorial-magazine' )
				);

			case 'in_issue_pages':
				return sprintf(
					/* translators: `%s`: total count */
					_x( 'Total Pages: %s', 'Display', 'geditorial-magazine' ),
					Core\Number::localize( $raw ?: $value ) // NOTE: it may not be integer
				);
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
				'order_cb'    => NULL,                    // NULL for default ordering by meta
				'orderby'     => 'order',                 // order by meta
				'order_start' => 'in_issue_page_start',   // meta field for ordering
				'order_order' => 'in_issue_order',        // meta field for ordering
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

	protected function latechores_post_aftercare( $post )
	{
		return $this->postdate__get_post_data_for_latechores(
			$post,
			Services\PostTypeFields::getPostDateMetaKeys()
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
			_x( 'Magazine Tools', 'Header', 'geditorial-magazine' ) );

			$this->paired_tools_render_card( $uri, $sub );

			if ( $this->get_setting( 'override_dates', TRUE ) )
				$this->postdate__render_card_override_dates(
					$uri,
					$sub,
					$this->constant( 'primary_posttype' ),
					_x( 'Issue Date from Meta-data', 'Card', 'geditorial-magazine' )
				);


		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( FALSE === $this->postdate__render_before_override_dates(
			$this->constant( 'primary_posttype' ),
			Services\PostTypeFields::getPostDateMetaKeys(),
			$uri,
			$sub,
			'tools'
		) )
			return FALSE;

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

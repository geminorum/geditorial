<?php namespace geminorum\gEditorial\Modules\Magazine;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Magazine extends gEditorial\Module
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
	use Internals\PairedTools;
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
				'paired',
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
				'archive_title',
				'archive_content',
				'archive_template',
			],
			'posttypes_option' => 'posttypes_option',
			'_supports' => [
				'widget_support',
				'shortcode_support',
				'thumbnail_support',
				$this->settings_supports_option( 'issue_posttype', TRUE ),
			],
			'_reports' => [
				'overview_taxonomies' => [ NULL, $this->get_posttype_taxonomies_list( 'issue_posttype' ) ],
				'overview_fields'     => [ NULL, $this->get_posttype_fields_list( 'issue_posttype', 'meta' ) ],
				'overview_units'      => [ NULL, $this->get_posttype_fields_list( 'issue_posttype', 'units' ) ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'issue_posttype'   => 'issue',
			'issue_paired'     => 'issues',
			'span_taxonomy'    => 'issue_span',
			'section_taxonomy' => 'issue_section',

			'issue_shortcode' => 'issue',
			'span_shortcode'  => 'issue-span',
			'cover_shortcode' => 'issue-cover',

			'field_paired_order' => 'in_issue_order',
		];
	}

	protected function get_module_icons()
	{
		return [
			'taxonomies' => [
				'issue_paired'     => NULL,
				'span_taxonomy'    => 'backup',
				'section_taxonomy' => 'category',
			],
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'issue_posttype'   => _n_noop( 'Issue', 'Issues', 'geditorial-magazine' ),
				'issue_paired'     => _n_noop( 'Issue', 'Issues', 'geditorial-magazine' ),
				'span_taxonomy'    => _n_noop( 'Span', 'Spans', 'geditorial-magazine' ),
				'section_taxonomy' => _n_noop( 'Section', 'Sections', 'geditorial-magazine' ),
			],
			'labels' => [
				'issue_posttype' => [
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
			'issue_posttype' => [
				'metabox_title' => _x( 'The Issue', 'Label: MetaBox Title', 'geditorial-magazine' ),
				'listbox_title' => _x( 'In This Issue', 'Label: MetaBox Title', 'geditorial-magazine' ),
			],
		];

		return $strings;
	}

	protected function define_default_terms()
	{
		return [
			'span_taxonomy' => Datetime::getYears( '-5 years' ),
		];
	}

	protected function get_global_fields()
	{
		return [ 'meta' => [
			$this->constant( 'issue_posttype' ) => [
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
		] ];
	}

	protected function paired_get_paired_constants()
	{
		return [
			'issue_posttype',
			'issue_paired',
			'section_taxonomy',
		];
	}

	public function after_setup_theme()
	{
		$this->register_posttype_thumbnail( 'issue_posttype' );
	}

	public function init()
	{
		parent::init();

		$this->register_taxonomy( 'span_taxonomy', [
			'hierarchical'       => TRUE,
			'meta_box_cb'        => '__checklist_reverse_terms_callback',
			'show_admin_column'  => TRUE,
			'show_in_quick_edit' => TRUE,
		], 'issue_posttype' );

		$this->paired_register();

		$this->register_shortcode( 'issue_shortcode' );
		$this->register_shortcode( 'span_shortcode' );
		$this->register_shortcode( 'cover_shortcode' );

		if ( is_admin() )
			return;

		$this->_hook_paired_exclude_from_subterm();
		$this->_hook_paired_override_term_link();
	}

	public function template_redirect()
	{
		if ( $this->_paired && is_tax( $this->constant( 'issue_paired' ) ) ) {

			if ( $post_id = $this->paired_get_to_post_id( get_queried_object(), 'issue_posttype', 'issue_paired' ) )
				Core\WordPress::redirect( get_permalink( $post_id ), 301 );

		} else if ( is_tax( $this->constant( 'span_taxonomy' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_spans', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );

		} else if ( is_post_type_archive( $this->constant( 'issue_posttype' ) ) ) {

			if ( $redirect = $this->get_setting( 'redirect_archives', FALSE ) )
				Core\WordPress::redirect( $redirect, 301 );

		} else if ( is_singular( $this->constant( 'issue_posttype' ) ) ) {

			if ( $this->get_setting( 'insert_cover' ) )
				add_action( $this->hook_base( 'content', 'before' ),
					[ $this, 'insert_cover' ],
					$this->get_setting( 'insert_priority', -50 )
				);
		}
	}

	public function template_include( $template )
	{
		return $this->templateposttype__include( $template, $this->constant( 'issue_posttype' ), FALSE );
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		return ModuleTemplate::spanTiles();
	}

	public function current_screen( $screen )
	{
		$subterms = $this->get_setting( 'subterms_support' )
			? $this->constant( 'section_taxonomy' )
			: FALSE;

		if ( $screen->post_type == $this->constant( 'issue_posttype' ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );
				$this->filter( 'get_default_comment_status', 3 );

				$this->_hook_post_updated_messages( 'issue_posttype' );
				$this->_hook_paired_mainbox( $screen );
				$this->_hook_paired_listbox( $screen );
				$this->pairedcore__hook_sync_paired();

			} else if ( 'edit' == $screen->base ) {

				$this->filter_true( 'disable_months_dropdown', 12 );

				$this->postmeta__hook_meta_column_row( $screen->post_type );
				$this->coreadmin__hook_admin_ordering( $screen->post_type );
				$this->_hook_bulk_post_updated_messages( 'issue_posttype' );
				$this->pairedadmin__hook_tweaks_column_connected( $screen->post_type );
				$this->pairedcore__hook_sync_paired();
				$this->corerestrictposts__hook_screen_taxonomies( 'span_taxonomy' );
			}

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( $subterms && $subterms === $screen->taxonomy )
				$this->filter_string( 'parent_file', sprintf( 'edit.php?post_type=%s', $this->constant( 'issue_posttype' ) ) );

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
				$this->postmeta__hook_meta_column_row( $screen->post_type );
			}
		}

		// only for supported posttypes
		$this->remove_taxonomy_submenu( $subterms );

		if ( Settings::isDashboard( $screen ) )
			$this->filter_module( 'calendar', 'post_row_title', 4, 12 );
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\IssueCover' );
	}

	public function meta_init()
	{
		$this->add_posttype_fields( $this->constant( 'issue_posttype' ) );
		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
	}

	public function admin_menu()
	{
		if ( $this->get_setting( 'quick_newpost' ) ) {
			$this->_hook_submenu_adminpage( 'newpost' );
			$this->action_self( 'newpost_content', 4, 10, 'menu_order' );
		}
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_post( 'issue_posttype' ) )
			$items[] = $glance;

		return $items;
	}

	public function insert_cover( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::postImage( [
			'size' => WordPress\Media::getAttachmentImageDefaultSize( $this->constant( 'issue_posttype' ), NULL, 'medium' ),
			'link' => 'attachment',
		] );
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {
			/* translators: %s: order */
			case 'in_issue_order'      : return WordPress\Strings::getCounted( $raw ?: $value, _x( 'Order in Issue: %s', 'Display', 'geditorial-magazine' ) );
			/* translators: %s: page */
			case 'in_issue_page_start' : return WordPress\Strings::getCounted( $raw ?: $value, _x( 'Page in Issue: %s', 'Display', 'geditorial-magazine' ) );
			/* translators: %s: total count */
			case 'in_issue_pages'      : return WordPress\Strings::getCounted( $raw ?: $value, _x( 'Total Pages: %s', 'Display', 'geditorial-magazine' ) );
		}

		return $value;
	}

	public function issue_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'paired',
			$this->constant( 'issue_posttype' ),
			$this->constant( 'issue_paired' ),
			array_merge( [
				'posttypes'   => $this->posttypes(),
				'order_cb'    => NULL, // NULL for default ordering by meta
				'orderby'     => 'order', // order by meta
				'order_start' => 'in_issue_page_start', // meta field for ordering
				'order_order' => 'in_issue_order', // meta field for ordering
			], (array) $atts ),
			$content,
			$this->constant( 'issue_shortcode', $tag ),
			$this->key
		);
	}

	public function span_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return Shortcode::listPosts( 'assigned',
			$this->constant( 'issue_posttype' ),
			$this->constant( 'span_taxonomy' ),
			$atts,
			$content,
			$this->constant( 'span_shortcode' )
		);
	}

	public function cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$type = $this->constant( 'issue_posttype' );
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

		return ShortCode::wrap( $html,
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

			Scripts::enqueueThickBox();
		}
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo Settings::toolboxColumnOpen(
			_x( 'Magazine Tools', 'Header', 'geditorial-magazine' ) );

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
		$this->check_settings( $sub, 'reports' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttype_overview_render_table( 'issue_posttype', $uri, $sub ) )
			return Info::renderNoReportsAvailable();
	}
}

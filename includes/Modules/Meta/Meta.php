<?php namespace geminorum\gEditorial\Modules\Meta;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Meta extends gEditorial\Module
{
	use Internals\PostMeta;
	use Internals\PostTypeFields;
	use Internals\PostTypeFieldsReports;

	public $meta_key = '_gmeta';

	protected $priority_init           = 12;
	protected $priority_current_screen = 12;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'     => 'meta',
			'title'    => _x( 'Meta', 'Modules: Meta', 'geditorial-admin' ),
			'desc'     => _x( 'Curated Meta-data', 'Modules: Meta', 'geditorial-admin' ),
			'icon'     => 'tag',
			'access'   => 'stable',
			'keywords' => [
				'metafields',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
			'_general' => [
				'insert_content_enabled',
				[
					'field'       => 'overwrite_author',
					'title'       => _x( 'Overwrite Author', 'Setting Title', 'geditorial-meta' ),
					'description' => _x( 'Replaces user display name with author meta field data.', 'Setting Description', 'geditorial-meta' ),
				],
				[
					'field'       => 'before_source',
					'type'        => 'text',
					'title'       => _x( 'Before Source', 'Setting Title', 'geditorial-meta' ),
					'description' => _x( 'Used as default text before the source links.', 'Setting Description', 'geditorial-meta' ),
					'default'     => _x( 'Source:', 'Setting Default', 'geditorial-meta' ),
				],
				[
					'field'       => 'before_action',
					'type'        => 'text',
					'title'       => _x( 'Before Action', 'Setting Title', 'geditorial-meta' ),
					'description' => _x( 'Used as default text before the action buttons.', 'Setting Description', 'geditorial-meta' ),
				],
				[
					'field'       => 'price_format',
					'type'        => 'text',
					'title'       => _x( 'Price Format', 'Setting Title', 'geditorial-meta' ),
					'description' => _x( 'Used as default format on rendering prices.', 'Setting Description', 'geditorial-meta' ),
					/* translators: `%s`: price number */
					'default'     => _x( '%s Toman', 'Setting Default', 'geditorial-meta' ),
				],
				'calendar_type',
				// 'calendar_list',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_attribute' => 'meta_rendered',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'titles' => [
				'over_title' => _x( 'OverTitle', 'Titles', 'geditorial-meta' ),
				'sub_title'  => _x( 'SubTitle', 'Titles', 'geditorial-meta' ),
				'byline'     => _x( 'Byline', 'Titles', 'geditorial-meta' ),
				'lead'       => _x( 'Lead', 'Titles', 'geditorial-meta' ),

				'alt_title'   => _x( 'Alternative Title', 'Titles', 'geditorial-meta' ),
				'print_title' => _x( 'Print Title', 'Titles', 'geditorial-meta' ),
				'print_date'  => _x( 'Print Date', 'Titles', 'geditorial-meta' ),

				'published'     => _x( 'Published', 'Titles', 'geditorial-meta' ),
				'status_string' => _x( 'Status', 'Titles', 'geditorial-meta' ),
				'source_title'  => _x( 'Source Title', 'Titles', 'geditorial-meta' ),
				'source_url'    => _x( 'Source URL', 'Titles', 'geditorial-meta' ),
				'action_title'  => _x( 'Action Title', 'Titles', 'geditorial-meta' ),
				'action_url'    => _x( 'Action URL', 'Titles', 'geditorial-meta' ),
				'highlight'     => _x( 'Highlight', 'Titles', 'geditorial-meta' ),
				'dashboard'     => _x( 'Dashboard', 'Titles', 'geditorial-meta' ),
				'abstract'      => _x( 'Abstract', 'Titles', 'geditorial-meta' ),
				'foreword'      => _x( 'Foreword', 'Titles', 'geditorial-meta' ),
				'cover_blurb'   => _x( 'Cover Blurb', 'Titles', 'geditorial-meta' ),
				'cover_price'   => _x( 'Cover Price', 'Titles', 'geditorial-meta' ),
				'content_fee'   => _x( 'Content Fee', 'Titles', 'geditorial-meta' ),

				'venue_string'   => _x( 'Venue', 'Descriptions', 'geditorial-meta' ),
				'contact_string' => _x( 'Contact', 'Descriptions', 'geditorial-meta' ),
				'website_url'    => _x( 'Website URL', 'Titles', 'geditorial-meta' ),
				'wiki_url'       => _x( 'Wiki URL', 'Titles', 'geditorial-meta' ),
				'email_address'  => _x( 'Email Address', 'Titles', 'geditorial-meta' ),

				'content_embed_url' => _x( 'Content Embed URL', 'Titles', 'geditorial-meta' ),
				'text_source_url'   => _x( 'Text Source URL', 'Titles', 'geditorial-meta' ),
				'audio_source_url'  => _x( 'Audio Source URL', 'Titles', 'geditorial-meta' ),
				'video_source_url'  => _x( 'Video Source URL', 'Titles', 'geditorial-meta' ),
				'image_source_url'  => _x( 'Image Source URL', 'Titles', 'geditorial-meta' ),
				'main_download_url' => _x( 'Main Download URL', 'Titles', 'geditorial-meta' ),
				'main_download_id'  => _x( 'Main Download Attachment', 'Titles', 'geditorial-meta' ),

				'event_summary'  => _x( 'Event Summary', 'Titles', 'geditorial-meta' ),

				'date'      => _x( 'Date', 'Titles', 'geditorial-meta' ),
				'datetime'  => _x( 'Date-Time', 'Titles', 'geditorial-meta' ),
				'datestart' => _x( 'Date-Start', 'Titles', 'geditorial-meta' ),
				'dateend'   => _x( 'Date-End', 'Titles', 'geditorial-meta' ),
				'time'      => _x( 'Time', 'Titles', 'geditorial-meta' ),
				'timestart' => _x( 'Time-Start', 'Titles', 'geditorial-meta' ),
				'timeend'   => _x( 'Time-End', 'Titles', 'geditorial-meta' ),
				'distance'  => _x( 'Distance', 'Titles', 'geditorial-meta' ),
				'duration'  => _x( 'Duration', 'Titles', 'geditorial-meta' ),
				'area'      => _x( 'Area', 'Titles', 'geditorial-meta' ),
				'period'    => _x( 'Period', 'Titles', 'geditorial-meta' ),
				'amount'    => _x( 'Amount', 'Titles', 'geditorial-meta' ),
				'sku'       => _x( 'SKU', 'Titles', 'geditorial-meta' ),

				'notes'       => _x( 'Notes', 'Titles', 'geditorial-meta' ),
				'reference'   => _x( 'Reference', 'Titles', 'geditorial-meta' ),
				'itineraries' => _x( 'Itineraries', 'Titles', 'geditorial-meta' ),

				'latlng' => _x( 'Coordinates', 'Titles', 'geditorial-meta' ),

				// combined fields
				'source' => _x( 'Source', 'Titles', 'geditorial-meta' ),
				'action' => _x( 'Action', 'Titles', 'geditorial-meta' ),
			],

			// NOTE: descriptions appears as tooltip so must be Title-Cased
			'descriptions' => [
				'over_title' => _x( 'Text to place over the content title', 'Descriptions', 'geditorial-meta' ),
				'sub_title'  => _x( 'Text to place under the content title', 'Descriptions', 'geditorial-meta' ),
				'byline'     => _x( 'Text to override the content author', 'Descriptions', 'geditorial-meta' ),
				'lead'       => _x( 'Notes to place before the content text', 'Descriptions', 'geditorial-meta' ),

				'alt_title'   => _x( 'The Original Title or Title in Another Language', 'Descriptions', 'geditorial-meta' ),
				'print_title' => _x( 'Text to Overwrite the Original Title on Printing', 'Descriptions', 'geditorial-meta' ),
				'print_date'  => _x( 'Date to Overwrite the Original Date on Printing', 'Descriptions', 'geditorial-meta' ),

				'published'     => _x( 'Text to indicate the original date of the content', 'Descriptions', 'geditorial-meta' ),
				'status_string' => _x( 'Text to indicate the current status of the content', 'Descriptions', 'geditorial-meta' ),
				'source_title'  => _x( 'Custom title for the source of the content', 'Descriptions', 'geditorial-meta' ),
				'source_url'    => _x( 'Custom URL to the source of the content', 'Descriptions', 'geditorial-meta' ),
				'action_title'  => _x( 'Custom title for the action of the content', 'Descriptions', 'geditorial-meta' ),
				'action_url'    => _x( 'Custom URL to the action of the content', 'Descriptions', 'geditorial-meta' ),
				'highlight'     => _x( 'Notes highlighted about the content', 'Descriptions', 'geditorial-meta' ),
				'dashboard'     => _x( 'Custom HTML content on the dashboard', 'Descriptions', 'geditorial-meta' ),
				'abstract'      => _x( 'Brief summary of the content', 'Descriptions', 'geditorial-meta' ),
				'foreword'      => _x( 'Introduction to the Content', 'Descriptions', 'geditorial-meta' ),
				'cover_blurb'   => _x( 'Description included on the inside cover or on the back', 'Descriptions', 'geditorial-meta' ),
				'cover_price'   => _x( 'Cover Price of the content', 'Descriptions', 'geditorial-meta' ),
				'content_fee'   => _x( 'Fee of the content', 'Descriptions', 'geditorial-meta' ),

				'venue_string'   => _x( 'Placing Information about the Content', 'Descriptions', 'geditorial-meta' ),
				'contact_string' => _x( 'A Way to Contact Someone about the Content', 'Descriptions', 'geditorial-meta' ),
				'website_url'    => _x( 'Public Website URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'wiki_url'       => _x( 'Public Wiki URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'email_address'  => _x( 'Email Address about the Content', 'Descriptions', 'geditorial-meta' ),

				'content_embed_url' => _x( 'Embeddable URL of the External Content', 'Descriptions', 'geditorial-meta' ),
				'text_source_url'   => _x( 'Text Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'audio_source_url'  => _x( 'Audio Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'video_source_url'  => _x( 'Video Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'image_source_url'  => _x( 'Image Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'main_download_url' => _x( 'Downloadable URL of the External Content', 'Descriptions', 'geditorial-meta' ),
				'main_download_id'  => _x( 'Downloadable Attachment of the External Content', 'Descriptions', 'geditorial-meta' ),

				'event_summary'  => _x( 'Summary of the Event appears on the Calendar', 'Descriptions', 'geditorial-meta' ),

				'date'      => _x( 'Posts can have date to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'datetime'  => _x( 'Posts can have date-time to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'datestart' => _x( 'Posts can have date-start to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'dateend'   => _x( 'Posts can have date-end to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'time'      => _x( 'Posts can have time to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'timestart' => _x( 'Posts can have time-start to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'timeend'   => _x( 'Posts can have time-end to help organize them.', 'Descriptions', 'geditorial-meta' ),
				'distance'  => _x( 'The formatted length of space about the post.', 'Descriptions', 'geditorial-meta' ),
				'duration'  => _x( 'The formatted length of time about the post.', 'Descriptions', 'geditorial-meta' ),
				'area'      => _x( 'The formatted area, measured in square metres.', 'Descriptions', 'geditorial-meta' ),
				'period'    => _x( 'The length of time about the post.', 'Descriptions', 'geditorial-meta' ),
				'amount'    => _x( 'The quantity number about the post.', 'Descriptions', 'geditorial-meta' ),
				'sku'       => _x( 'Stock Keeping Unit', 'Descriptions', 'geditorial-meta' ),

				'notes'       => _x( 'General Notes about the Content', 'Descriptions: `notes`', 'geditorial-meta' ),
				'reference'   => _x( 'Full Reference to the Content', 'Descriptions: `reference`', 'geditorial-meta' ),
				'itineraries' => _x( 'Brief Itineraries of the Content', 'Descriptions: `itineraries`', 'geditorial-meta' ),

				'latlng' => _x( 'Latitude and Longitude', 'Descriptions: `latlng`', 'geditorial-meta' ),

				'source' => _x( 'Source of the content', 'Descriptions', 'geditorial-meta' ),
				'action' => _x( 'Action of the content', 'Descriptions', 'geditorial-meta' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbox_title'  => _x( 'Metadata', 'MetaBox: `mainbox_title`', 'geditorial-meta' ),
			'mainbox_action' => _x( 'Configure', 'MetaBox: `mainbox_action`', 'geditorial-meta' ),
		];

		$strings['notices'] = [
			'no_fields' => _x( 'There are no meta fields available!', 'Notice: `no_fields`', 'geditorial-meta' ),
		];

		$strings['misc'] = [
			'meta_column_title'   => _x( 'Metadata', 'Column Title', 'geditorial-meta' ),
			'author_column_title' => _x( 'Author', 'Column Title', 'geditorial-meta' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],
					'byline'     => [ 'type' => 'text', 'quickedit' => TRUE ],
					'lead'       => [ 'type' => 'postbox_html' ],                // OLD: 'postbox_legacy'

					'alt_title'     => [ 'type' => 'text' ],
					'print_title'   => [ 'type' => 'text' ],
					'print_date'    => [ 'type' => 'date' ],
					'published'     => [ 'type' => 'datestring', 'quickedit' => TRUE ],
					'status_string' => [ 'type' => 'text', 'quickedit' => TRUE ],
					'source_title'  => [ 'type' => 'text' ],
					'source_url'    => [ 'type' => 'link' ],
					'action_title'  => [ 'type' => 'text' ],
					'action_url'    => [ 'type' => 'link' ],
					'highlight'     => [ 'type' => 'note' ],
					'dashboard'     => [ 'type' => 'postbox_html' ],                      // or 'postbox_tiny'
					'abstract'      => [ 'type' => 'postbox_html' ],                      // or 'postbox_tiny'
					'foreword'      => [ 'type' => 'postbox_html' ],                      // or 'postbox_tiny'
					'cover_blurb'   => [ 'type' => 'note' ],
					'cover_price'   => [ 'type' => 'price' ],
					'content_fee'   => [ 'type' => 'price' ],

					'venue_string'   => [ 'type' => 'venue' ],
					'contact_string' => [ 'type' => 'contact' ],   // url/email/phone
					'website_url'    => [ 'type' => 'link' ],
					'wiki_url'       => [ 'type' => 'link' ],
					'email_address'  => [ 'type' => 'email' ],

					'content_embed_url' => [ 'type' => 'embed' ],
					'text_source_url'   => [ 'type' => 'text_source' ],
					'audio_source_url'  => [ 'type' => 'audio_source' ],
					'video_source_url'  => [ 'type' => 'video_source' ],
					'image_source_url'  => [ 'type' => 'image_source' ],
					'main_download_url' => [ 'type' => 'downloadable' ],
					'main_download_id'  => [ 'type' => 'attachment' ],

					'event_summary' => [ 'type' => 'text' ],
					'date'          => [ 'type' => 'date' ],
					'datetime'      => [ 'type' => 'datetime' ],
					'datestart'     => [ 'type' => 'datetime' ],
					'dateend'       => [ 'type' => 'datetime' ],
					'time'          => [ 'type' => 'time' ],
					'timestart'     => [ 'type' => 'time' ],
					'timeend'       => [ 'type' => 'time' ],
					'distance'      => [ 'type' => 'distance' ],
					'duration'      => [ 'type' => 'duration' ],
					'area'          => [ 'type' => 'area' ],
					'period'        => [ 'type' => 'text' ],
					'amount'        => [ 'type' => 'number' ],
					'sku'           => [ 'type' => 'code', 'quickedit' => TRUE ],

					'notes'       => [ 'type' => 'note' ],
					'reference'   => [ 'type' => 'note' ],
					'itineraries' => [ 'type' => 'note' ],

					'latlng' => [ 'type' => 'latlng' ],
				],
				'page' => [
					'over_title' => [ 'type' => 'title_before' ],
					'sub_title'  => [ 'type' => 'title_after' ],

					'content_embed_url' => [ 'type' => 'embed' ],
				],
			],
		];
	}

	private function get_posttypes_support_meta()
	{
		$posttypes = [ 'post' ];
		$supported = get_post_types_by_support( 'editorial-meta' );
		$excludes  = [
			'attachment',
			'page',
		];

		$list = array_diff( array_merge( $posttypes, $supported ), $excludes );

		return $this->filters( 'support_posttypes', $list );
	}

	public function init()
	{
		parent::init();

		$this->posttypefields_init_meta_fields();
		$this->posttypefields_register_meta_fields();
		$this->posttypefields__hook_template_newpost();

		$this->filter( 'meta_field', 7, 5, FALSE, $this->base );
		$this->filter( 'meta_field', 7, 15, 'tokens', $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, 'action', $this->base );
		$this->filter( 'searchselect_result_extra_for_post', 3, 12, 'filter', $this->base );
		$this->filter( 'objecthints_tips_for_post', 5, 8, FALSE, $this->base );
		$this->filter( 'calendars_post_summary', 4, 8, FALSE, $this->base );
	}

	public function importer_init()
	{
		$this->posttypefields__hook_importer_init();
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'insert_content' ) ) {
			add_action( $this->hook_base( 'content', 'before' ), [ $this, 'content_before' ], 50 );
			add_action( $this->hook_base( 'content', 'after' ), [ $this, 'content_after' ], 50 );
		}

		if ( $this->get_setting( 'overwrite_author', FALSE ) )
			$this->filter( 'the_author', 1, 9 );
	}

	public function setup_ajax()
	{
		if ( ! $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			return;

		$this->posttypefields__hook_setup_ajax( $posttype );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( ! in_array( $screen->base, [ 'post', 'edit' ] ) )
				return;

			$fields = $this->get_posttype_fields( $screen->post_type );

			// Bail if no fields enabled for this post-type!
			if ( ! count( $fields ) )
				return;

			if ( 'post' == $screen->base ) {

				$contexts = Core\Arraay::column( $fields, 'context' );
				$nobox    = $this->filters( 'nobox_callback', in_array( 'nobox', $contexts, TRUE ), $screen->post_type );

				if ( TRUE === $nobox )
					add_action( 'dbx_post_sidebar', [ $this, 'render_nobox_fields' ], 10, 1 );

				else if ( $nobox && is_callable( $nobox ) )
					add_action( 'dbx_post_sidebar', $nobox, 10, 1 );

				$lonebox = $this->filters( 'lonebox_callback', in_array( 'lonebox', $contexts, TRUE ), $screen->post_type );

				if ( TRUE === $lonebox )
					call_user_func_array( [ $this, 'register_lonebox_fields' ], [ $screen ] );

				else if ( $lonebox && is_callable( $lonebox ) )
					call_user_func_array( $lonebox, [ $screen ] );

				$asset = [
					// 'fields' => $fields, // not used yet!
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->posttypefields__hook_metabox( $screen, $fields );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();
				$this->posttypefields__hook_edit_screen( $screen->post_type );
				$this->posttypefields__enqueue_edit_screen( $screen->post_type, $fields );
				$this->_hook_store_metabox( $screen->post_type, 'posttypefields' );

				gEditorial\Scripts::enqueueClickToClip();
			}
		}
	}

	protected function posttypefields_custom_column_position( $posttype )
	{
		switch ( $posttype ) {

			case WordPress\WooCommerce::PRODUCT_POSTTYPE:
				return [ 'name', 'after' ];

			// NOTE: not supported yet!
			case WordPress\WooCommerce::ORDER_POSTTYPE:
				return [ 'order_number', 'after' ];
		}

		return [ 'title', 'after' ];
	}

	protected function posttypefields__hook_default_rows( $posttype )
	{
		add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_quickedit_posttypefields' ], 5, 6 );
		add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_extra' ], 15, 6 );
		add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_excerpt' ], 20, 6 );
	}

	public function render_nobox_fields( $post )
	{
		$fields = $this->get_posttype_fields( $post->post_type );

		if ( count( $fields ) ) {

			echo '&nbsp;'; // NOTE: workaround for weird CSS bug on `no-js`!

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'title_before':
					case 'title_after':
						// FIXME
						ModuleMetaBox::legacy_fieldTitle( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

					case 'postbox_legacy':
						// FIXME
						ModuleMetaBox::legacy_fieldBox( $field, [ $field ], $post, $args['ltr'], $args['title'] );
					break;
				}
			}
		}

		$this->actions( 'box_raw', $this->module, $post, $fields );
		$this->nonce_field( 'nobox' );
	}

	public function register_lonebox_fields( $screen )
	{
		$fields = $this->get_posttype_fields( $screen->post_type );

		if ( count( $fields ) ) {

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'postbox_html':
					case 'postbox_tiny':

						$metabox = $this->classs( $screen->post_type, $field );
						$title   = empty( $args['title'] ) ? $field : $args['title'];

						if ( ! empty( $args['description'] ) )
							$title.= WordPress\MetaBox::markupTitleHelp( $args['description'] );

						gEditorial\MetaBox::classEditorBox( $screen, $metabox );

						add_meta_box( $metabox,
							$title,
							[ $this, 'render_lonebox_metabox' ],
							$screen,
							'after_title', // TODO: must defined on field args
							'high',
							[
								'posttype'   => $screen->post_type,
								'metabox'    => $metabox,
								'field_name' => $field,
								'field_args' => $args,
							]
						);

					break;
				}
			}
		}
	}

	public function render_lonebox_metabox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		// FIXME
		ModuleMetaBox::legacy_fieldEditorBox(
			$box['args']['field_name'],
			$post,
			$box['args']['field_args']['ltr'],
			$box['args']['field_args']['title'],
			FALSE,
			$box['args']['field_args']['type']
		);
	}

	public function sanitize_postmeta_field_key_map()
	{
		return ModuleHelper::getPostTypeFieldKeyMap();
	}

	public function column_row_extra( $post, $before, $after, $module, $fields, $excludes )
	{
		if ( array_key_exists( 'source_title', $fields ) || array_key_exists( 'source_url', $fields ) )
			ModuleTemplate::metaSource( [
				'before' => sprintf( $before, '-'.$module.'-source' )
					.$this->get_column_icon( FALSE, 'external', $this->get_string( 'source', $post->post_type, 'titles', 'source' ) ),
				'after'  => $after,
			] );

		if ( array_key_exists( 'action_title', $fields ) || array_key_exists( 'action_url', $fields ) )
			ModuleTemplate::metaAction( [
				'before' => sprintf( $before, '-'.$module.'-action' )
					.$this->get_column_icon( FALSE, 'cart', $this->get_string( 'action', $post->post_type, 'titles', 'action' ) ),
				'after'  => $after,
			] );
	}

	// NOTE: only on excerpt mode
	public function column_row_excerpt( $post, $before, $after, $module, $fields, $excludes )
	{
		if ( 'excerpt' !== $GLOBALS['mode'] )
			return;

		foreach ( $fields as $field => $args ) {

			if ( ! in_array( $args['type'], [ 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				continue;

			$icon = $this->get_column_icon( FALSE, $args['icon'], $args['title'] );

			ModuleTemplate::metaFieldHTML( $field, [
				'before' => sprintf( $before, '-'.$module.'-'.$field ).$icon,
				'after'  => $after,
				'filter' => FALSE,
				'trim'   => 450,
			] );
		}
	}

	public function tableColumnPostMeta( $posttypes )
	{
		foreach ( (array) $posttypes as $posttype )
			$this->posttypefields__hook_default_rows( $posttype );

		if ( empty( $GLOBALS['mode'] ) )
			$GLOBALS['mode'] = 'excerpt';

		return [
			'title'    => $this->get_column_title( 'meta' ),
			'callback' => [ $this, 'tableColumnPostMeta_callback' ],
		];
	}

	public function tableColumnPostMeta_callback( $value, $row, $column, $index )
	{
		$this->posts_custom_column_posttypefields( $this->hook(), $row );
	}

	// NOTE: excludes are for other modules
	protected function posttypefields_custom_column_excludes( $fields )
	{
		$excludes = [];

		foreach ( $fields as $field => $args ) {

			if ( $args['quickedit'] )
				$excludes[] = $field;

			else if ( in_array( $args['name'], [ 'source_title', 'source_url', 'action_title', 'action_url' ] ) )
				$excludes[] = $field;

			else if ( in_array( $args['type'], [ 'term', 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				$excludes[] = $field;
		}

		return $excludes;
	}

	public function objecthints_tips_for_post( $tips, $post, $extend, $context, $queried )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $tips;

		return array_merge( $tips,
			ModuleHelper::generateHints( $post, $extend, $context, $queried ) );
	}

	public function calendars_post_summary( $summary, $post, $context, $final )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $summary;

		if ( ! $fields = $this->get_posttype_fields( $post->post_type ) )
			return $summary;

		if ( ! array_key_exists( 'event_summary', $fields ) )
			return $summary;

		if ( $meta = Services\PostTypeFields::getFieldRaw( 'event_summary', $post->ID, $this->key, FALSE ) )
			return $meta;

		return $summary;
	}

	// @REF: `Template::getMetaField()`
	// TODO: move to `Services\PostTypeFields`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {

			case 'cover_price':
			case 'content_fee':
				// TODO: format numbers
				return Core\Number::localize( sprintf( $this->get_setting( 'price_format', '%s' ), $raw ) );

			case 'date_of_birth':

				if ( 'print' === $context )
					return gEditorial\Datetime::prepForDisplay(
						trim( $raw ),
						'Y/n/j',
						$this->default_calendar()
					);

				if ( 'export' === $context )
					return gEditorial\Datetime::prepForInput(
						trim( $raw ),
						'Y/m/d',
						$this->default_calendar()
					);

				return gEditorial\Datetime::prepDateOfBirth(
					trim( $raw ),
					NULL,
					FALSE,
					$this->default_calendar()
				);
		}

		switch ( $field_args['type'] ) {

			case 'venue':

				if ( 'export' === $context )
					return WordPress\Strings::getPiped( Services\Markup::getSeparated( $raw ?: $meta ) );

				if ( 'print' === $context )
					return WordPress\Strings::getJoined( Services\Markup::getSeparated( $raw ?: $meta ) );

				return Services\Locations::prepVenue( $raw ?: $meta );

			case 'people':

				if ( 'export' === $context )
					return WordPress\Strings::getPiped( Services\Markup::getSeparated( $raw ?: $meta ) );

				if ( 'print' === $context )
					return WordPress\Strings::getJoined( Services\Markup::getSeparated( $raw ?: $meta ) );

				return Services\Individuals::prepPeople( $raw ?: $meta );

			case 'identity':

				if ( 'print' === $context )
					return Core\Number::localize( Core\Number::zeroise( $raw ?: $meta, 10 ) );

				if ( 'export' === $context )
					return Core\Number::zeroise( Core\Number::translate( $raw ?: $meta ), 10 );

				return sprintf( '<span class="-identity %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
					Core\Validation::isIdentityNumber( $raw ?: $meta ) ? '-is-valid' : '-not-valid',
					$meta, $meta );

			case 'postcode':

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				if ( FALSE === ( $postcode = gEditorial\Info::fromPostCode( $raw ?: $meta ) ) )
					return sprintf( '<span class="-postcode %s">%s</span>', '-not-valid', $raw ?: $meta );

				else
					return sprintf( '<span class="-postcode %s" title="%s">%s</span>',
						'-is-valid -ltr',
						empty( $postcode['country'] ) ? gEditorial()->na( FALSE ) : $postcode['country'],
						Core\HTML::wrapLTR( empty( $postcode['formatted'] ) ? ( $raw ?: $meta ) : $postcode['formatted'] )
					);

			case 'iban':

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				if ( FALSE === ( $iban = gEditorial\Info::fromIBAN( $raw ?: $meta ) ) )
					return sprintf( '<span class="-iban %s">%s</span>', '-not-valid', $raw ?: $meta );

				else
					return sprintf( '<span class="-iban %s" title="%s">%s</span>',
						'-is-valid',
						empty( $iban['bankname'] ) ? gEditorial()->na( FALSE ) : $iban['bankname'],
						empty( $iban['formatted'] ) ? ( $raw ?: $meta ) : $iban['formatted']
					);

			case 'bankcard':

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				if ( FALSE === ( $card = gEditorial\Info::fromCardNumber( $raw ?: $meta ) ) )
					return sprintf( '<span class="-bankcard %s">%s</span>', '-not-valid', $raw ?: $meta );

				else
					return sprintf( '<span class="-bankcard %s" title="%s">%s</span>',
						'-is-valid',
						empty( $card['bankname'] ) ? gEditorial()->na( FALSE ) : $card['bankname'],
						empty( $card['formatted'] ) ? ( $raw ?: $meta ) : $card['formatted']
					);

			case 'contact':
				return gEditorial\Helper::prepContact( trim( $raw ) );

			case 'email':
				return Core\Email::prep( $raw, $field_args, $context );

			case 'phone':

				// NOTE: `prep()` will handle the context
				return Core\Phone::prep( trim( $raw ), $field_args, $context );

			case 'mobile':

				// NOTE: `prep()` will handle the context
				return Core\Mobile::prep( trim( $raw ), $field_args, $context );

			case 'isbn':

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				return gEditorial\Info::lookupISBN( trim( $raw ) );

			case 'vin':

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				return gEditorial\Info::lookupPlate( trim( $raw ) );

			case 'plate':

				if ( 'print' === $context )
					return Core\Number::localize( $raw ?: $meta );

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				return sprintf( '<span class="-plate %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
					Core\Validation::isPlateNumber( $raw ?: $meta ) ? '-is-valid' : '-not-valid',
					$meta, $meta );

			case 'address':

				if ( 'export' === $context )
					return Core\Text::normalizeWhitespace( WordPress\Strings::cleanupChars( $raw ?: $meta ) );

				return WordPress\Strings::prepAddress( $raw ?: $meta, $context, $raw ?: $meta );

			case 'year':

				if ( 'export' === $context )
					return Core\Number::translate( $raw ?: $meta );

				return Core\Number::localize( trim( $raw ) );

			case 'date':

				if ( 'export' === $context )
					return gEditorial\Datetime::prepForInput(
						trim( $raw ),
						'Y/m/d',
						$this->default_calendar()
					);

				return gEditorial\Datetime::prepForDisplay(
					trim( $raw ),
					$context == 'print' ? 'Y/n/j' : 'Y/m/d',
					$this->default_calendar()
				);

			case 'datetime':

				if ( 'export' === $context )
					return gEditorial\Datetime::prepForInput(
						trim( $raw ),
						gEditorial\Datetime::isDateOnly( trim( $raw ) ) ? 'Y/n/j' : 'Y/n/j H:i',
						$this->default_calendar()
					);

				if ( 'print' === $context )
					return gEditorial\Datetime::prepForDisplay(
						trim( $raw ),
						gEditorial\Datetime::isDateOnly( trim( $raw ) ) ? 'Y/n/j' : 'Y/n/j H:i',
						$this->default_calendar()
					);

				return gEditorial\Datetime::prepForDisplay(
					trim( $raw ),
					gEditorial\Datetime::isDateOnly( trim( $raw ) ) ? 'Y/m/d' : 'Y/m/d H:i',
					$this->default_calendar()
				);

			case 'datestring':
				return Core\Number::localize( gEditorial\Datetime::stringFormat( $raw ) );

			case 'area':
				return Core\Area::prep( $raw, $field_args, $context );

			case 'embed':

				if ( 'export' === $context )
					return $raw ?: $meta;

				return gEditorial\Template::doEmbedShortCode( trim( $raw ), $post, $context );

			case 'text_source':

				if ( 'export' === $context )
					return $raw ?: $meta;

				return gEditorial\Template::doMediaShortCode( trim( $raw ), 'text', $post, $context );

			case 'audio_source':

				if ( 'export' === $context )
					return $raw ?: $meta;

				return gEditorial\Template::doMediaShortCode( trim( $raw ), 'audio', $post, $context );

			case 'video_source':

				if ( 'export' === $context )
					return $raw ?: $meta;

				return gEditorial\Template::doMediaShortCode( trim( $raw ), 'video', $post, $context );

			case 'image_source':

				if ( 'export' === $context )
					return $raw ?: $meta;

				return gEditorial\Template::doMediaShortCode( trim( $raw ), 'image', $post, $context );

			case 'embed':

				if ( 'export' === $context )
					return $raw ?: $meta;

				if ( 'print' === $context )
					return Core\URL::prepTitle( trim( $raw ) );

				return Core\HTML::link( Core\URL::getDomain( trim( $raw ) ), trim( $raw ), TRUE );

			case 'link':

				if ( 'export' === $context )
					return $raw ?: $meta;

				if ( 'print' === $context )
					return Core\URL::prepTitle( trim( $raw ) );

				return Core\HTML::link( Core\URL::prepTitle( trim( $raw ) ), trim( $raw ), TRUE );

			case 'latlng':

				if ( 'export' === $context )
					return $raw ?: $meta;

				// return Core\LatLng::prep( trim( $raw ) );
				return gEditorial\Info::lookupLatLng( trim( $raw ) );
		}

		return $meta;
	}

	public function meta_field_tokens( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		return Services\PostTypeFields::replaceTokens( $meta, $field_args, $post, $context );
	}

	public function content_before( $content )
	{
		if ( ! $this->is_content_insert( FALSE ) )
			return;

		ModuleTemplate::metaLead( [
			'before' => $this->wrap_open( '-before entry-lead' ),
			'after'  => '</div>',
		] );
	}

	public function content_after( $content )
	{
		if ( ! $this->is_content_insert( FALSE, FALSE ) )
			return;

		global $page, $pages;

		// only on the last page
		if ( $page == count( $pages ) ) {
			ModuleTemplate::metaSource( [
				'after'  => '</div>',
				'before' => $this->wrap_open( '-after entry-source' )
					.$this->get_setting( 'before_source', '' ).' ',
			] );

			ModuleTemplate::metaAction( [
				'after'  => '</div>',
				'before' => $this->wrap_open( '-after entry-action' )
					.$this->get_setting( 'before_action', '' ).' ',
			] );
		}
	}

	public function the_author( $display_name )
	{
		if ( ! $post = WordPress\Post::get() )
			return $display_name;

		// NO NEED
		// if ( ! in_array( 'byline', $this->posttype_fields( $post->post_type ) ) )
		// 	return $display_name;

		if ( $value = $this->get_postmeta_field( $post->ID, 'byline' ) )
			$display_name = $value;

		return $display_name;
	}

	protected function render_imports_html( $uri, $sub )
	{
		$na   = TRUE;
		$args = $this->get_current_form( [
			'custom_field'       => '',
			'custom_field_limit' => '',
			'custom_field_type'  => 'post',
			'custom_field_into'  => '',
		], 'imports' );

		Core\HTML::h3( _x( 'Meta Imports', 'Header', 'geditorial-meta' ) );

		echo '<table class="form-table">';

		if ( $metakeys = WordPress\Database::getPostMetaKeys( TRUE ) ) {
			echo '<tr><th scope="row">';
				echo _x( 'Import Custom Fields', 'Header', 'geditorial-meta' );
			echo '</th><td>';
				$this->_render_imports_db_metakeys( $metakeys, $args );
			echo '</td></tr>';
			$na = FALSE;
		}

		echo '</table>';

		if ( $na )
			gEditorial\Info::renderNoImportsAvailable();
	}

	private function _render_imports_db_metakeys( $metakeys, $args )
	{
		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field',
			'values'       => WordPress\Database::getPostMetaKeys( TRUE ),
			'default'      => $args['custom_field'],
			'option_group' => 'imports',
		] );

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'custom_field_limit',
			'default'      => $args['custom_field_limit'],
			'option_group' => 'imports',
			'field_class'  => 'small-text',
			'placeholder'  => 'limit',
		] );

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field_type',
			'values'       => $this->list_posttypes(),
			'default'      => $args['custom_field_type'],
			'option_group' => 'imports',
		] );

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field_into',
			'values'       => $this->posttype_fields_list( $args['custom_field_type'] ),
			'default'      => $args['custom_field_into'],
			'option_group' => 'imports',
		] );

		gEditorial\Settings::submitButton( 'custom_fields_check',
			_x( 'Check', 'Button', 'geditorial-meta' ), TRUE );

		gEditorial\Settings::submitButton( 'custom_fields_convert',
			_x( 'Covert', 'Button', 'geditorial-meta' ) );

		gEditorial\Settings::submitButton( 'custom_fields_delete',
			_x( 'Delete', 'Button', 'geditorial-meta' ), 'danger', TRUE );

		Core\HTML::desc( _x( 'Check for Custom Fields and import them into Meta', 'Message', 'geditorial-meta' ) );

		if ( isset( $_POST['custom_fields_check'] )
			&& $args['custom_field'] ) {

			echo '<br />';
			Core\HTML::tableList( [
				'post_id' => gEditorial\Tablelist::columnPostID(),
				'type'    => gEditorial\Tablelist::columnPostType( 'post_id' ),
				'title'   => [
					'title'    => _x( 'Title', 'Table Column', 'geditorial-meta' ),
					'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
						return WordPress\Post::title( $row->post_id );
					},
				],
				'meta' => sprintf(
					/* translators: `%s`: title */
					_x( 'Meta: %s', 'Table Column', 'geditorial-meta' ),
					Core\HTML::code( $args['custom_field']
				) ),
			], WordPress\Database::getPostMetaRows(
				stripslashes( $args['custom_field'] ),
				stripslashes( $args['custom_field_limit'] )
			), [
				'empty' => Core\HTML::warning( _x( 'There are no meta-data available!', 'Table Empty', 'geditorial-meta' ), FALSE ),
			] );
		}
	}

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );

				if ( gEditorial\Tablelist::isAction( 'custom_fields_convert' ) ) {

					$post = $this->get_current_form( [
						'custom_field'       => FALSE,
						'custom_field_into'  => FALSE,
						'custom_field_limit' => '25',
					], 'imports' );

					$result = 0;
					$this->raise_resources();

					if ( $post['custom_field'] && $post['custom_field_into'] )
						$result = $this->posttypefields_do_migrate_field(
							$post['custom_field'],
							$post['custom_field_into'],
							$post['custom_field_limit'] );

					if ( $result )
						WordPress\Redirect::doReferer( [
							'message' => 'converted',
							'field'   => $post['custom_field'],
							'limit'   => $post['custom_field_limit'],
							'count'   => $result,
						] );

				} else if ( gEditorial\Tablelist::isAction( 'custom_fields_delete' ) ) {

					$post = $this->get_current_form( [
						'custom_field'       => FALSE,
						'custom_field_limit' => '',
					], 'imports' );

					$result = [];
					$this->raise_resources();

					if ( $post['custom_field'] )
						$result = WordPress\Database::deletePostMeta( $post['custom_field'], $post['custom_field_limit'] );

					if ( $result )
						WordPress\Redirect::doReferer( [
							'message' => 'deleted',
							'field'   => $post['custom_field'],
							'limit'   => $post['custom_field_limit'],
							'count'   => $result,
						] );
				}
			}
		}
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );
				$this->posttypefields_reports_handle_tablelist( $sub );

				WordPress\Redirect::doReferer( 'huh' );
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->posttypefields_reports_render_tablelist( $uri, $sub ) )
			return gEditorial\Info::renderNoReportsAvailable();
	}
}

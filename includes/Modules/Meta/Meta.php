<?php namespace geminorum\gEditorial\Modules\Meta;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\ISBN;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\Post;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Meta extends gEditorial\Module
{
	public $meta_key = '_gmeta';

	protected $priority_init           = 12;
	protected $priority_current_screen = 12;

	protected $disable_no_posttypes = TRUE;

	protected $caps = [
		'tools' => 'import',
	];

	public static function module()
	{
		return [
			'name'   => 'meta',
			'title'  => _x( 'Meta', 'Modules: Meta', 'geditorial' ),
			'desc'   => _x( 'Curated Metadata', 'Modules: Meta', 'geditorial' ),
			'icon'   => 'tag',
			'access' => 'stable',
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
					/* translators: %s: price number */
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
			'label_tax'         => 'label',
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
				'label'      => _x( 'Label', 'Titles', 'geditorial-meta' ),
				'label_tax'  => _x( 'Label Taxonomy', 'Titles', 'geditorial-meta' ),

				'published'    => _x( 'Published', 'Titles', 'geditorial-meta' ),
				'source_title' => _x( 'Source Title', 'Titles', 'geditorial-meta' ),
				'source_url'   => _x( 'Source URL', 'Titles', 'geditorial-meta' ),
				'action_title' => _x( 'Action Title', 'Titles', 'geditorial-meta' ),
				'action_url'   => _x( 'Action URL', 'Titles', 'geditorial-meta' ),
				'highlight'    => _x( 'Highlight', 'Titles', 'geditorial-meta' ),
				'dashboard'    => _x( 'Dashboard', 'Titles', 'geditorial-meta' ),
				'abstract'     => _x( 'Abstract', 'Titles', 'geditorial-meta' ),
				'foreword'     => _x( 'Foreword', 'Titles', 'geditorial-meta' ),
				'cover_blurb'  => _x( 'Cover Blurb', 'Titles', 'geditorial-meta' ),
				'cover_price'  => _x( 'Cover Price', 'Titles', 'geditorial-meta' ),

				'contact_string' => _x( 'Contact', 'Descriptions', 'geditorial-meta' ),
				'phone_number'   => _x( 'Phone Number', 'Titles', 'geditorial-meta' ),
				'mobile_number'  => _x( 'Mobile Number', 'Titles', 'geditorial-meta' ),

				'website_url'    => _x( 'Website URL', 'Titles', 'geditorial-meta' ),
				'email_address'  => _x( 'Email Address', 'Titles', 'geditorial-meta' ),
				'postal_address' => _x( 'Postal Address', 'Titles', 'geditorial-meta' ),

				'content_embed_url' => _x( 'Content Embed URL', 'Titles', 'geditorial-meta' ),
				'text_source_url'   => _x( 'Text Source URL', 'Titles', 'geditorial-meta' ),
				'audio_source_url'  => _x( 'Audio Source URL', 'Titles', 'geditorial-meta' ),
				'video_source_url'  => _x( 'Video Source URL', 'Titles', 'geditorial-meta' ),
				'image_source_url'  => _x( 'Image Source URL', 'Titles', 'geditorial-meta' ),

				'date'      => _x( 'Date', 'Titles', 'geditorial-meta' ),
				'datetime'  => _x( 'Date-Time', 'Titles', 'geditorial-meta' ),
				'datestart' => _x( 'Date-Start', 'Titles', 'geditorial-meta' ),
				'dateend'   => _x( 'Date-End', 'Titles', 'geditorial-meta' ),
				'days'      => _x( 'Days', 'Titles', 'geditorial-meta' ),

				// combined fields
				'source' => _x( 'Source', 'Titles', 'geditorial-meta' ),
				'action' => _x( 'Action', 'Titles', 'geditorial-meta' ),
			],
			'descriptions' => [
				'over_title' => _x( 'Text to place over the content title', 'Descriptions', 'geditorial-meta' ),
				'sub_title'  => _x( 'Text to place under the content title', 'Descriptions', 'geditorial-meta' ),
				'byline'     => _x( 'Text to override the content author', 'Descriptions', 'geditorial-meta' ),
				'lead'       => _x( 'Notes to place before the content text', 'Descriptions', 'geditorial-meta' ),
				'label'      => _x( 'Text to indicate that the content is part of a column', 'Descriptions', 'geditorial-meta' ),
				'label_tax'  => _x( 'Taxonomy for better categorizing columns', 'Descriptions', 'geditorial-meta' ),

				'published'    => _x( 'Text to indicate the original date of the content', 'Descriptions', 'geditorial-meta' ),
				'source_title' => _x( 'Custom title for the source of the content', 'Descriptions', 'geditorial-meta' ),
				'source_url'   => _x( 'Custom URL to the source of the content', 'Descriptions', 'geditorial-meta' ),
				'action_title' => _x( 'Custom title for the action of the content', 'Descriptions', 'geditorial-meta' ),
				'action_url'   => _x( 'Custom URL to the action of the content', 'Descriptions', 'geditorial-meta' ),
				'highlight'    => _x( 'Notes highlighted about the content', 'Descriptions', 'geditorial-meta' ),
				'dashboard'    => _x( 'Custom HTML content on the dashboard', 'Descriptions', 'geditorial-meta' ),
				'abstract'     => _x( 'Brief summary of the content', 'Descriptions', 'geditorial-meta' ),
				'foreword'     => _x( 'Introduction to the Content', 'Descriptions', 'geditorial-meta' ),
				'cover_blurb'  => _x( 'Description included on the inside cover or on the back', 'Descriptions', 'geditorial-meta' ),
				'cover_price'  => _x( 'Cover Price of the content', 'Descriptions', 'geditorial-meta' ),

				'contact_string' => _x( 'A Way to Contact Someone about the Content', 'Descriptions', 'geditorial-meta' ),
				'phone_number'   => _x( 'Phone Contact Number about the Content', 'Descriptions', 'geditorial-meta' ),
				'mobile_number'  => _x( 'Mobile Contact Number about the Content', 'Descriptions', 'geditorial-meta' ),

				'website_url'    => _x( 'Public Website URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'email_address'  => _x( 'Email Address about the Content', 'Descriptions', 'geditorial-meta' ),
				'postal_address' => _x( 'Postal Address about the Content', 'Descriptions', 'geditorial-meta' ),

				'content_embed_url' => _x( 'Embeddable URL of the External Content', 'Descriptions', 'geditorial-meta' ),
				'text_source_url'   => _x( 'Text Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'audio_source_url'  => _x( 'Audio Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'video_source_url'  => _x( 'Video Source URL of the Content', 'Descriptions', 'geditorial-meta' ),
				'image_source_url'  => _x( 'Image Source URL of the Content', 'Descriptions', 'geditorial-meta' ),

				'date'      => _x( 'Posts can have date to help orginize them.', 'Descriptions', 'geditorial-meta' ),
				'datetime'  => _x( 'Posts can have date-time to help orginize them.', 'Descriptions', 'geditorial-meta' ),
				'datestart' => _x( 'Posts can have date-start to help orginize them.', 'Descriptions', 'geditorial-meta' ),
				'dateend'   => _x( 'Posts can have date-end to help orginize them.', 'Descriptions', 'geditorial-meta' ),
				'days'      => _x( 'Posts can have days number to help orginize them.', 'Descriptions', 'geditorial-meta' ),

				'source' => _x( 'Source of the content', 'Descriptions', 'geditorial-meta' ),
				'action' => _x( 'Action of the content', 'Descriptions', 'geditorial-meta' ),
			],
			'noops' => [
				'label_tax' => _n_noop( 'Column Header', 'Column Headers', 'geditorial-meta' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'metabox_title'  => _x( 'Metadata', 'MetaBox Title', 'geditorial-meta' ),
			'metabox_action' => _x( 'Configure', 'MetaBox Action', 'geditorial-meta' ),
		];

		$strings['misc'] = [
			'meta_column_title'   => _x( 'Metadata', 'Column Title', 'geditorial-meta' ),
			'author_column_title' => _x( 'Author', 'Column Title', 'geditorial-meta' ),
		];

		$strings['default_terms'] = [
			'label_tax' => [
				'introduction' => _x( 'Introduction', 'Default Term', 'geditorial-meta' ),
				'interview'    => _x( 'Interview', 'Default Term', 'geditorial-meta' ),
				'review'       => _x( 'Review', 'Default Term', 'geditorial-meta' ),
				'report'       => _x( 'Report', 'Default Term', 'geditorial-meta' ),
			],
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			'post' => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],
				'byline'     => [ 'type' => 'text', 'quickedit' => TRUE ],
				'lead'       => [ 'type' => 'postbox_html' ], // OLD: 'postbox_legacy'
				'label'      => [ 'type' => 'text' ],
				'label_tax'  => [ 'type' => 'term', 'taxonomy' => $this->constant( 'label_tax' ) ],

				'published'    => [ 'type' => 'datestring', 'quickedit' => TRUE ],
				'source_title' => [ 'type' => 'text' ],
				'source_url'   => [ 'type' => 'link' ],
				'action_title' => [ 'type' => 'text' ],
				'action_url'   => [ 'type' => 'link' ],
				'highlight'    => [ 'type' => 'note' ],
				'dashboard'    => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
				'abstract'     => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
				'foreword'     => [ 'type' => 'postbox_html' ], // or 'postbox_tiny'
				'cover_blurb'  => [ 'type' => 'note' ],
				'cover_price'  => [ 'type' => 'price' ],

				'contact_string' => [ 'type' => 'contact' ], // url/email/phone
				'phone_number'   => [ 'type' => 'phone' ],
				'mobile_number'  => [ 'type' => 'mobile' ],

				'website_url'    => [ 'type' => 'link' ],
				'email_address'  => [ 'type' => 'email' ],
				'postal_address' => [ 'type' => 'note' ],

				'content_embed_url' => [ 'type' => 'embed' ],
				'text_source_url'   => [ 'type' => 'text_source' ],
				'audio_source_url'  => [ 'type' => 'audio_source' ],
				'video_source_url'  => [ 'type' => 'video_source' ],
				'image_source_url'  => [ 'type' => 'image_source' ],

				'date'      => [ 'type' => 'date' ],
				'datetime'  => [ 'type' => 'datetime' ],
				'datestart' => [ 'type' => 'datetime' ],
				'dateend'   => [ 'type' => 'datetime' ],
				'days'      => [ 'type' => 'number' ],
			],
			'page' => [
				'over_title' => [ 'type' => 'title_before' ],
				'sub_title'  => [ 'type' => 'title_after' ],

				'content_embed_url' => [ 'type' => 'embed' ],
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

		$this->init_meta_fields();
		$this->register_meta_fields();

		if ( ! is_admin() )
			return;

		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 8 );
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'insert_content' ) ) {
			add_action( $this->base.'_content_before', [ $this, 'content_before' ], 50 );
			add_action( $this->base.'_content_after', [ $this, 'content_after' ], 50 );
		}

		if ( $this->get_setting( 'overwrite_author', FALSE ) )
			$this->filter( 'the_author', 1, 9 );
	}

	public function init_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) ) {
			$this->_edit_screen( $posttype );
			$this->_hook_default_rows();
			$this->_hook_store_metabox( $posttype );
		}
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( ! in_array( $screen->base, [ 'post', 'edit' ] ) )
				return;

			$fields = $this->get_posttype_fields( $screen->post_type );

			// bail if no fields enabled for this posttype
			if ( ! count( $fields ) )
				return;

			if ( 'post' == $screen->base ) {

				$contexts   = Arraay::column( $fields, 'context' );
				$metabox_id = $this->classs( $screen->post_type );

				$mainbox = $this->filters( 'mainbox_callback', in_array( 'mainbox', $contexts, TRUE ), $screen->post_type );

				if ( TRUE === $mainbox )
					$mainbox = [ $this, 'render_mainbox_metabox' ];

				if ( $mainbox && is_callable( $mainbox ) )
					add_meta_box( $metabox_id,
						$this->get_meta_box_title(),
						$mainbox,
						$screen,
						'side',
						'high',
						[
							'posttype'   => $screen->post_type,
							'metabox_id' => $metabox_id,
						]
					);

				$nobox = $this->filters( 'nobox_callback', in_array( 'nobox', $contexts, TRUE ), $screen->post_type );

				if ( TRUE === $nobox )
					add_action( 'dbx_post_sidebar', [ $this, 'render_nobox_fields' ], 10, 1 );

				else if ( $nobox && is_callable( $nobox ) )
					add_action( 'dbx_post_sidebar', $nobox, 10, 1 );

				$lonebox = $this->filters( 'lonebox_callback', in_array( 'lonebox', $contexts, TRUE ), $screen->post_type );

				if ( TRUE === $lonebox )
					call_user_func_array( [ $this, 'register_lonebox_fields' ], [ $screen ] );

				else if ( $lonebox && is_callable( $lonebox ) )
					call_user_func_array( $lonebox, [ $screen ] );

				add_action( 'geditorial_meta_render_metabox', [ $this, 'render_posttype_fields' ], 10, 4 );

				$asset = [
					// 'fields' => $fields, // not used yet!
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
				$this->_hook_default_rows();

				$asset = [
					'fields' => array_filter( Arraay::column( wp_list_filter( $fields, [ 'quickedit' => TRUE ] ), 'type', 'name' ) ),
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->_hook_store_metabox( $screen->post_type );
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		$this->filter( 'manage_posts_columns', 2, 5 );
		$this->filter( 'manage_pages_columns', 1, 5 );

		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );

		$this->action( 'quick_edit_custom_box', 2 );
	}

	// early and late actions to make room for other modules
	private function _hook_default_rows()
	{
		$this->action_self( 'column_row', 3, 5, 'default' );
		$this->action_self( 'column_row', 3, 15, 'extra' );
		$this->action_self( 'column_row', 3, 20, 'excerpt' );
	}

	protected function init_meta_fields()
	{
		$label_tax_tax_posttypes = [];

		foreach ( $this->posttypes() as $posttype )
			if ( in_array( 'label_tax', $this->posttype_fields( $posttype ), TRUE ) )
				$label_tax_tax_posttypes[] = $posttype;

		if ( count( $label_tax_tax_posttypes ) ) {

			$this->register_taxonomy( 'label_tax', [
				'show_in_rest' => FALSE, // temporarily disable in block editor
			], $label_tax_tax_posttypes );

			// $this->register_default_terms( 'label_tax' );
		}

		// default fields for custom posttypes
		foreach ( $this->get_posttypes_support_meta() as $posttype )
			$this->add_posttype_fields( $posttype, $this->fields['post'] );

		$this->add_posttype_fields( 'page' );

		$this->filter( 'meta_field', 6, 5, FALSE, $this->base );
	}

	protected function register_meta_fields()
	{
		foreach ( $this->posttypes() as $posttype ) {

			/**
			 * registering general field for all meta data
			 * mainly for display purposes
			 */
			register_rest_field( $posttype, $this->constant( 'restapi_attribute' ), [
				'get_callback' => [ $this, 'attribute_get_callback' ],
			] );

			/**
			 * the posttype must have `custom-fields` support
			 * otherwise the meta fields will not appear in the REST API
			 */
			if ( ! post_type_supports( $posttype, 'custom-fields' ) )
				continue;

			$fields = $this->get_posttype_fields( $posttype );

			foreach ( $fields as $field => $args ) {

				if ( empty( $args['rest'] ) )
					continue;

				if ( $args['repeat'] ) {

					$defaults = [
						// NOTE: require an item schema when registering `array` meta
						'type'    => 'array',
						'single'  => FALSE,
						'default' => (array) $args['default'],
					];

				} else if ( in_array( $args['type'], [ 'integer', 'number', 'float', 'price' ] ) ) {

					$defaults = [
						'type'    => 'integer',
						'single'  => TRUE,
						'default' => $args['default'] ?: 0,
					];

				} else {

					$defaults = [
						// NOTE: valid values: `string`, `boolean`, `integer`, `number`, `array`, `object`
						'type'    => 'string',
						'single'  => TRUE,
						'default' => $args['default'] ?: '',
					];
				}

				$register_args = array_merge( $defaults, [

					/**
					 * accepts `post`, `comment`, `term`, `user`
					 * or any other object type with an associated meta table
					 */
					'object_subtype' => $posttype,

					'description'       => sprintf( '%s: %s', $args['title'], $args['description'] ),
					'auth_callback'     => [ $this, 'register_auth_callback' ],
					'sanitize_callback' => [ $this, 'register_sanitize_callback' ],
					'show_in_rest'      => TRUE,
					// TODO: must prepare object scheme on repeatable fields
					// @SEE: https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#read-and-write-a-post-meta-field-in-post-responses
					// @SEE: `rest_validate_value_from_schema()`, `wp_register_persisted_preferences_meta()`
					// 'show_in_rest'      => [ 'prepare_callback' => [ $this, 'register_prepare_callback' ] ],
				] );

				if ( FALSE === $args['access_view'] )
					$register_args['show_in_rest'] = FALSE; // only for explicitly private fields

				$meta_key = $this->get_postmeta_key( $field );
				$filtred  = $this->filters( 'register_field_args', $register_args, $meta_key, $posttype );

				if ( FALSE !== $filtred )
					register_meta( 'post', $meta_key, $filtred );
			}
		}
	}

	public function attribute_get_callback( $post, $attr, $request, $object_type )
	{
		$list   = [];
		$fields = $this->get_posttype_fields( $post['type'] );

		foreach ( $fields as $field => $args ) {

			if ( empty( $args['rest'] ) )
				continue;

			$meta = ModuleTemplate::getMetaField( $field, [
				'id'       => $post['id'],
				'default'  => $args['default'],
				'noaccess' => FALSE,
			] );

			// if no access or default is FALSE
			if ( FALSE !== $meta || $meta === $args['default'] )
				$list[] = [
					'name'     => $args['rest'],
					'title'    => $args['title'],
					'rendered' => $meta,
					// 'value'    => ModuleTemplate::getMetaFieldRaw( $field, $post['id'], $this->key ),
				];
		}

		return $list;
	}

	/**
	 * NOTE: DEPRECATED FILTER: `geditorial_meta_disable_field_edit`
	 *
	 * - upon no `auth_callback`, wordpress checks for `is_protected_meta()` aka underline prefix
	 * - this filter is to call when performing `edit_post_meta`, `add_post_meta`, and `delete_post_meta` capability checks
	 * - return `true` to have the mapped meta caps from `edit_{$object_type}` apply
	*/
	public function register_auth_callback( $allowed, $meta_key, $object_id, $user_id, $cap, $caps )
	{
		if ( ! $field = $this->get_posttype_field_args( $this->stripprefix( $meta_key ), get_object_subtype( 'post', $object_id ) ) )
			return $allowed;

		return (bool) $this->access_posttype_field( $field, $object_id, 'edit', $user_id );
	}

	// WORKING BUT DISABLED
	// NO NEED: we use original key, so the core will retrieve the value
	public function register_prepare_callback( $value, $request, $args )
	{
		if ( ! $post = Post::get() )
			return $value;

		$fields = $this->get_posttype_fields( $post->post_type );
		$fields = wp_list_filter( $fields, [ 'rest' => $args['name'] ] );

		foreach ( $fields as $field => $field_args )
			return $this->get_postmeta_field( $post->ID, $field, $field_args['default'] );

		return $value;
	}

	public function register_sanitize_callback( $meta_value, $meta_key, $object_type )
	{
		$field = $this->get_posttype_field_args( $this->stripprefix( $meta_key ), $object_type );
		return $field ? $this->sanitize_posttype_field( $meta_value, $field, Post::get() ) : $meta_value;
	}

	public function render_posttype_fields( $post, $box, $fields = NULL, $context = 'mainbox' )
	{
		$user_id = wp_get_current_user();

		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			if ( ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			switch ( $args['type'] ) {

				case 'select':

					ModuleMetaBox::renderFieldSelect( $args, $post );
					break;

				case 'text':
				case 'datestring':

					ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

				case 'date':
				case 'identity':
				case 'isbn':
				case 'iban':
				case 'code':
				case 'contact':
				case 'mobile':
				case 'phone':
				case 'email':

					ModuleMetaBox::renderFieldInput( $args, $post, NULL );
					break;

				case 'float':
				case 'embed':
				case 'text_source':
				case 'audio_source':
				case 'video_source':
				case 'image_source':
				case 'link':

					ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );

				break;
				case 'price':
				case 'number':

					ModuleMetaBox::legacy_fieldNumber( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );

				break;
				case 'note':
				case 'textarea':
				case 'widget':

					ModuleMetaBox::legacy_fieldTextarea( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );

				break;
				case 'parent_post':

					ModuleMetaBox::renderFieldPostParent( $args, $post );
					break;

				case 'user':

					ModuleMetaBox::renderFieldUser( $args, $post );
					break;

				case 'post':

					ModuleMetaBox::renderFieldPost( $args, $post );

				break;
				case 'term':

					// TODO: migrate to: `ModuleMetaBox::renderFieldTerm( $args, $post )`

					if ( $args['taxonomy'] )
						ModuleMetaBox::legacy_fieldTerm( $field, [ $field ], $post, $args['taxonomy'], $args['ltr'], $args['title'] );
					else
						ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
			}
		}

		$this->nonce_field( 'mainbox' );
	}

	public function render_mainbox_metabox( $post, $box )
	{
		if ( ! empty( $box['args']['metabox_id'] ) && MetaBox::checkHidden( $box['args']['metabox_id'], $post->post_type ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );

			if ( count( $fields ) )
				$this->actions( 'render_metabox', $post, $box, $fields, 'mainbox' );

			else
				echo HTML::wrap( _x( 'No Meta Fields', 'Message', 'geditorial-meta' ), 'field-wrap -empty' );

			$this->actions( 'render_metabox_after', $post, $box, $fields, 'mainbox' );
		echo '</div>';
	}

	public function render_nobox_fields( $post )
	{
		$fields = $this->get_posttype_fields( $post->post_type );

		if ( count( $fields ) ) {

			echo '&nbsp;'; // workaround for weird css bug on no-js!

			foreach ( $fields as $field => $args ) {

				switch ( $args['type'] ) {

					case 'title_before':
					case 'title_after':
						ModuleMetaBox::legacy_fieldTitle( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

					case 'postbox_legacy':
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
							$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'
								.HTML::escape( $args['description'] ).'">'
								.HTML::getDashicon( 'editor-help' ).'</span>';

						MetaBox::classEditorBox( $screen, $metabox );

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

		ModuleMetaBox::legacy_fieldEditorBox(
			$box['args']['field_name'],
			$post,
			$box['args']['field_args']['ltr'],
			$box['args']['field_args']['title'],
			FALSE,
			$box['args']['field_args']['type']
		);
	}

	// FIXME: DROP THIS!
	public function sanitize_post_meta( $postmeta, $fields, $post )
	{
		if ( ! count( $fields ) )
			return $postmeta;

		if ( ! $post = Post::get( $post ) )
			return $postmeta;

		if ( ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return $postmeta;

		// MAYBE: check for `edit_post_meta`
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return $postmeta;

		foreach ( $fields as $field => $args ) {

			switch ( $args['type'] ) {

				case 'term':

					ModuleMetaBox::setPostMetaField_Term( $post->ID, $field, $args['taxonomy'] );

				break;
				case 'embed':
				case 'text_source':
				case 'audio_source':
				case 'video_source':
				case 'image_source':
				case 'link':

					ModuleMetaBox::setPostMetaField_URL( $postmeta, $field );

				break;
				case 'date':
				case 'contact':
				case 'phone':
				case 'mobile':
				case 'identity':
				case 'isbn':
				case 'iban':
				case 'email':
				case 'code':

					ModuleMetaBox::setPostMetaField_Code( $postmeta, $field );

				break;
				case 'price':
				case 'number':

					ModuleMetaBox::setPostMetaField_Number( $postmeta, $field );

				break;
				case 'select':
				case 'datestring':
				case 'text':
				case 'title_before':
				case 'title_after':

					ModuleMetaBox::setPostMetaField_String( $postmeta, $field );

				break;
				case 'note':
				case 'textarea':
				case 'widget':
				case 'postbox_legacy':
				case 'postbox_html':

					ModuleMetaBox::setPostMetaField_Text( $postmeta, $field );
			}
		}

		return $postmeta;
	}

	public function sanitize_postmeta_field_key( $field_key )
	{
		if ( is_array( $field_key ) )
			return $field_key;

		$fields = [
			// meta currents
			'over_title'   => [ 'over_title', 'ot' ],
			'sub_title'    => [ 'sub_title', 'st' ],
			'byline'       => [ 'byline', 'author', 'as' ],
			'lead'         => [ 'lead', 'le' ],
			'label'        => [ 'label', 'ch', 'column_header' ],
			'label_tax'    => [ 'label_tax', 'ct' ],  // term type
			'start'        => [ 'start', 'in_issue_page_start' ], // general
			'order'        => [ 'order', 'in_issue_order', 'in_collection_order', 'in_series_order' ], // general
			'number_line'  => [ 'number_line', 'issue_number_line', 'number' ],
			'total_pages'  => [ 'total_pages', 'issue_total_pages', 'pages' ],
			'source_title' => [ 'source_title', 'reshare_source_title' ],
			'source_url'   => [ 'source_url', 'reshare_source_url', 'es', 'ol' ],

			// meta oldies
			'ot' => [ 'over_title', 'ot' ],
			'st' => [ 'sub_title', 'st' ],
			'le' => [ 'lead', 'le' ],
			'as' => [ 'byline', 'author', 'as' ],
			'ch' => [ 'label', 'ch', 'column_header' ],
			'ct' => [ 'label_tax', 'ct' ],
			'es' => [ 'source_url', 'es' ],
			'ol' => [ 'source_url', 'ol' ],

			// book currents
			'publication_edition'   => [ 'publication_edition', 'edition' ],
			'publication_print'     => [ 'publication_print', 'print' ],
			'publication_isbn'      => [ 'publication_isbn', 'isbn' ],
			'publication_reference' => [ 'publication_reference', 'reference' ],
			'total_volumes'         => [ 'total_volumes', 'volumes' ],
			'publication_size'      => [ 'publication_size', 'size' ], // term type

			// book oldies
			'edition'   => [ 'publication_edition', 'edition' ],
			'print'     => [ 'publication_print', 'print' ],
			'isbn'      => [ 'publication_isbn', 'isbn' ],
			'reference' => [ 'publication_reference', 'reference' ],
			'volumes'   => [ 'total_volumes', 'volumes' ],
			'size'      => [ 'publication_size', 'size' ], // term type

			// other oldies
			'issue_number_line'    => [ 'number_line', 'issue_number_line' ],
			'issue_total_pages'    => [ 'total_pages', 'issue_total_pages' ],
			'reshare_source_title' => [ 'source_title', 'reshare_source_title' ],
			'reshare_source_url'   => [ 'source_url', 'reshare_source_url' ],

			// fallbacks
			'over-title' => [ 'over_title', 'ot' ],
			'sub-title'  => [ 'sub_title', 'st' ],
			'pages'      => [ 'total_pages', 'pages' ],
			'number'     => [ 'number_line', 'issue_number_line', 'number' ],
		];

		if ( isset( $fields[$field_key] ) )
			return $fields[$field_key];

		return [ $field_key ];
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		if ( ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return;

		// MAYBE: check for `edit_post_meta`
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! count( $fields ) )
			return;

		$user_id = wp_get_current_user();
		$legacy  = $this->get_postmeta_legacy( $post->ID );

		foreach ( $fields as $field => $args ) {

			if ( ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			$request = sprintf( '%s-%s-%s', $this->base, $this->module->name, $field );

			if ( FALSE !== ( $data = self::req( $request, FALSE ) ) )
				$this->import_posttype_field( $data, $args, $post );

			// passing not enabled legacy data
			else if ( array_key_exists( $field, $legacy ) )
				$this->set_postmeta_field( $post->ID, $field, $this->sanitize_posttype_field( $legacy[$field], $args, $post ) );
		}

		$this->clean_postmeta_legacy( $post->ID, $fields, $legacy );
	}

	public function import_posttype_field( $data, $field, $post )
	{
		switch ( $field['type'] ) {

			case 'parent_post':

				// do nothing! the input name works magic

				break;

			case 'term':

				$terms = $this->sanitize_posttype_field( $data, $field, $post );

				return wp_set_object_terms( $post->ID, Arraay::prepNumeral( $terms ), $field['taxonomy'], FALSE );

			default:

				return $this->set_postmeta_field( $post->ID, $field['name'], $this->sanitize_posttype_field( $data, $field, $post ) );
		}
	}

	// FIXME: DROP THIS!
	public function store_metabox_OLD( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		// NOUNCES MUST CHECKED BY FILTERS
		// CAPABILITIES MUST CHECKED BY FILTERS : if (current_user_can($post->cap->edit_post, $post_id))

		$this->store_postmeta( $post_id,
			$this->sanitize_post_meta(
				$this->get_postmeta_legacy( $post->ID ),
				$this->get_posttype_fields( $post->post_type ),
				$post
			)
		);
	}

	public function manage_pages_columns( $columns )
	{
		return $this->manage_posts_columns( $columns, 'page' );
	}

	public function manage_posts_columns( $columns, $posttype )
	{
		if ( in_array( 'byline', $this->posttype_fields( $posttype ) ) )
			unset( $columns['author'] );

		return Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title( 'meta', $posttype ),
		], 'title', 'after' );
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( $this->classs() != $column_name )
			return;

		if ( ! $post = Post::get( $post_id ) )
			return;

		$prefix   = $this->classs().'-';
		$fields   = $this->get_posttype_fields( $post->post_type );
		$excludes = []; // excludes are for other modules

		foreach ( $fields as $field => $args ) {

			if ( $args['quickedit'] )
				$excludes[] = $field;

			else if ( in_array( $args['name'], [ 'label', 'label_tax', 'source_title', 'source_url', 'action_title', 'action_url' ] ) )
				$excludes[] = $field;

			else if ( in_array( $args['type'], [ 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				$excludes[] = $field;
		}

		echo '<div class="geditorial-admin-wrap-column -meta"><ul class="-rows">';
			$this->actions( 'column_row', $post, $fields, $excludes );
		echo '</ul></div>';

		// for quick-edit
		foreach ( wp_list_filter( $fields, [ 'quickedit' => TRUE ] ) as $field => $args )
			echo '<div class="hidden '.$prefix.$field.'-value">'.$this->get_postmeta_field( $post->ID, $field ).'</div>';
	}

	// NOTE: only renders quick-edits
	public function column_row_default( $post, $fields, $excludes )
	{
		foreach ( $fields as $field_key => $field ) {

			if ( ! $field['quickedit'] )
				continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key ) )
				continue;

			echo '<li class="-row meta-'.$field_key.'">';
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo '</li>';
		}
	}

	public function column_row_extra( $post, $fields, $exclude )
	{
		if ( array_key_exists( 'label', $fields ) || array_key_exists( 'label_tax', $fields ) )
			ModuleTemplate::metaLabel( [
				'before' => '<li class="-row meta-label">'
					.$this->get_column_icon( FALSE, $fields['label']['icon'], $fields['label']['title'] ),
				'after'  => '</li>',
			] );

		if ( array_key_exists( 'source_title', $fields ) || array_key_exists( 'source_url', $fields ) )
			ModuleTemplate::metaSource( [
				'before' => '<li class="-row meta-source">'
					.$this->get_column_icon( FALSE, 'external', $this->get_string( 'source', $post->post_type, 'titles', 'source' ) ),
				'after'  => '</li>',
			] );

		if ( array_key_exists( 'action_title', $fields ) || array_key_exists( 'action_url', $fields ) )
			ModuleTemplate::metaAction( [
				'before' => '<li class="-row meta-action">'
					.$this->get_column_icon( FALSE, 'cart', $this->get_string( 'action', $post->post_type, 'titles', 'action' ) ),
				'after'  => '</li>',
			] );
	}

	// only on excerpt mode
	public function column_row_excerpt( $post, $fields, $exclude )
	{
		if ( 'excerpt' !== $GLOBALS['mode'] )
			return;

		foreach ( $fields as $field => $args ) {

			if ( ! in_array( $args['type'], [ 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				continue;

			// skip if empty
			if ( ! $value = $this->get_postmeta_field( $post->ID, $field ) )
				continue;

			$icon = $this->get_column_icon( FALSE, $args['icon'], $args['title'] );

			ModuleTemplate::metaFieldHTML( $field, [
				'before' => '<li class="-row meta-'.$field.'">'.$icon,
				'after'  => '</li>',
				'filter' => FALSE,
				'trim'   => 450,
			] );
		}
	}

	public function tableColumnPostMeta()
	{
		$this->_hook_default_rows();

		if ( empty( $GLOBALS['mode'] ) )
			$GLOBALS['mode'] = 'excerpt';

		return [
			'title'    => $this->get_column_title( 'meta' ),
			'callback' => [ $this, 'tableColumnPostMeta_callback'],
		];
	}

	public function tableColumnPostMeta_callback( $value, $row, $column, $index )
	{
		$this->posts_custom_column( $this->hook(), $row );
	}

	public function quick_edit_custom_box( $column_name, $posttype )
	{
		if ( $this->classs() != $column_name )
			return FALSE;

		$fields = $this->get_posttype_fields( $posttype );

		foreach ( $fields as $field => $args ) {

			if ( ! $args['quickedit'] )
				continue;

			$name  = $this->classs().'-'.$field; // to protect key underlines
			$class = HTML::prepClass( $name );

			echo '<label class="hidden '.$class.'">';
				echo '<span class="title">'.$args['title'].'</span>';
				echo '<span class="input-text-wrap"><input type="text" name="'.$name.'" class="'.$class.'" value=""></span>';
			echo '</label>';
		}

		$this->nonce_field( 'nobox' );
	}

	// @REF: `Template::getMetaField()`
	// TODO: for `iban`: display bank as title attr
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args )
	{
		switch ( $field ) {

			case 'cover_price':
				// TODO: format numbers
				return Number::localize( sprintf( $this->get_setting( 'price_format', '%s' ), $raw ) );

			case 'website_url':
				return HTML::link( URL::prepTitle( trim( $raw ) ), trim( $raw ), TRUE );

			case 'date_of_birth':
				return Datetime::prepDateOfBirth( trim( $raw ), 'Y/m/d' );
		}

		switch ( $field_args['type'] ) {

			case 'identity':
				return sprintf( '<span class="-identity %s">%s</span>',
					Core\Validation::isIdentityNumber( $raw ?: $meta ) ? '-is-valid' : '-not-valid',
					$meta );

			case 'iban':
				return sprintf( '<span class="-iban %s">%s</span>',
					Core\Validation::isIBAN( $raw ?: $meta ) ? '-is-valid' : '-not-valid',
					$meta );

			case 'contact':
				return Helper::prepContact( trim( $raw ) );

			case 'email':
				return apply_shortcodes( sprintf( '[email]%s[/email]', trim( $raw ) ) );

			case 'phone':
			case 'mobile':
				return apply_shortcodes( sprintf( '[tel]%s[/tel]', trim( $raw ) ) );

			case 'isbn':
				return HTML::link( ISBN::prep( $raw, TRUE ), Info::lookupISBN( $raw ), TRUE );

			case 'date':
				return Datetime::prepForDisplay( trim( $raw ), 'Y/m/d' );

			case 'datestring':
				return Number::localize( Datetime::stringFormat( $raw ) );

			case 'embed':
				return Template::doEmbedShortCode( trim( $raw ), $post );

			case 'text_source':
				return Template::doMediaShortCode( trim( $raw ), 'text', $post );

			case 'audio_source':
				return Template::doMediaShortCode( trim( $raw ), 'audio', $post );

			case 'video_source':
				return Template::doMediaShortCode( trim( $raw ), 'video', $post );

			case 'image_source':
				return Template::doMediaShortCode( trim( $raw ), 'image', $post );
		}

		return $meta;
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
		if ( ! $post = Post::get() )
			return $display_name;

		// NO NEED
		// if ( ! in_array( 'byline', $this->posttype_fields( $post->post_type ) ) )
		// 	return $display_name;

		if ( $value = $this->get_postmeta_field( $post->ID, 'byline' ) )
			$display_name = $value;

		return $display_name;
	}

	// TODO: bulk migrate data to another field with filters for processing
	// - or just rename metakey directly on database!
	protected function render_tools_html( $uri, $sub )
	{
		$args = $this->get_current_form( [
			'custom_field'       => '',
			'custom_field_limit' => '',
			'custom_field_type'  => 'post',
			'custom_field_into'  => '',
		], 'tools' );

		HTML::h3( _x( 'Meta Tools', 'Header', 'geditorial-meta' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Import Custom Fields', 'Header', 'geditorial-meta' ).'</th><td>';

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field',
			'values'       => Database::getPostMetaKeys( TRUE ),
			'default'      => $args['custom_field'],
			'option_group' => 'tools',
		] );

		$this->do_settings_field( [
			'type'         => 'text',
			'field'        => 'custom_field_limit',
			'default'      => $args['custom_field_limit'],
			'option_group' => 'tools',
			'field_class'  => 'small-text',
			'placeholder'  => 'limit',
		] );

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field_type',
			'values'       => $this->list_posttypes(),
			'default'      => $args['custom_field_type'],
			'option_group' => 'tools',
		] );

		$this->do_settings_field( [
			'type'         => 'select',
			'field'        => 'custom_field_into',
			'values'       => $this->posttype_fields_list( $args['custom_field_type'] ),
			'default'      => $args['custom_field_into'],
			'option_group' => 'tools',
		] );

		echo '&nbsp;&nbsp;';

		Settings::submitButton( 'custom_fields_check',
			_x( 'Check', 'Button', 'geditorial-meta' ), TRUE );

		Settings::submitButton( 'custom_fields_convert',
			_x( 'Covert', 'Button', 'geditorial-meta' ) );

		Settings::submitButton( 'custom_fields_delete',
			_x( 'Delete', 'Button', 'geditorial-meta' ), 'danger', TRUE );

		HTML::desc( _x( 'Check for Custom Fields and import them into Meta', 'Message', 'geditorial-meta' ) );

		if ( isset( $_POST['custom_fields_check'] )
			&& $args['custom_field'] ) {

			echo '<br />';
			// FIXME: use table list helpers
			HTML::tableList( [
				'post_id' => Tablelist::columnPostID(),
				'type'   => [
					'title'    => _x( 'Type', 'Table Column', 'geditorial-meta' ),
					'args'     => [ 'types' => PostType::get( 2 ) ],
					'callback' => static function( $value, $row, $column, $index, $key, $args ) {

						$post = Post::get( $row->post_id );

						return isset( $column['args']['types'][$post->post_type] )
							? $column['args']['types'][$post->post_type]
							: $post->post_type;
					},
				],
				'title'   => [
					'title'    => _x( 'Title', 'Table Column', 'geditorial-meta' ),
					'callback' => static function( $value, $row, $column, $index, $key, $args ) {
						return Post::title( $row->post_id );
					},
				],
				/* translators: %s: title */
				'meta' => sprintf( _x( 'Meta: %s', 'Table Column', 'geditorial-meta' ), HTML::code( $args['custom_field'] ) ),
			], Database::getPostMetaRows(
				stripslashes( $args['custom_field'] ),
				stripslashes( $args['custom_field_limit'] )
			), [
				'empty' => HTML::warning( _x( 'There are no meta-data available!', 'Table Empty', 'geditorial-meta' ), FALSE ),
			] );
		}

		echo '</td></tr>';
		echo '</table>';
	}

	public function tools_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'tools' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				if ( Tablelist::isAction( 'custom_fields_convert' ) ) {

					$post = $this->get_current_form( [
						'custom_field'       => FALSE,
						'custom_field_into'  => FALSE,
						'custom_field_limit' => '25',
					], 'tools' );

					$result = 0;
					$this->_raise_resources();

					if ( $post['custom_field'] && $post['custom_field_into'] )
						$result = $this->import_field_meta(
							$post['custom_field'],
							$post['custom_field_into'],
							$post['custom_field_limit'] );

					if ( $result )
						WordPress::redirectReferer( [
							'message' => 'converted',
							'field'   => $post['custom_field'],
							'limit'   => $post['custom_field_limit'],
							'count'   => $result,
						] );

				} else if ( Tablelist::isAction( 'custom_fields_delete' ) ) {

					$post = $this->get_current_form( [
						'custom_field'       => FALSE,
						'custom_field_limit' => '',
					], 'tools' );

					$result = [];
					$this->_raise_resources();

					if ( $post['custom_field'] )
						$result = Database::deletePostMeta( $post['custom_field'], $post['custom_field_limit'] );

					if ( $result )
						WordPress::redirectReferer( [
							'message' => 'deleted',
							'field'   => $post['custom_field'],
							'limit'   => $post['custom_field_limit'],
							'count'   => count( $result ),
						] );
				}
			}
		}
	}

	// OLD: `import_from_meta()`
	public function import_field_meta( $post_meta_key, $field, $limit = FALSE )
	{
		$rows = Database::getPostMetaRows( $post_meta_key, $limit );

		foreach ( $rows as $row )
			$this->import_field_raw( explode( ',', $row->meta ), $field, $row->post_id );

		return count( $rows );
	}

	// OLD: `import_to_meta()`
	public function import_field_raw( $data, $field_key, $post )
	{
		if ( ! $post = Post::get( $post ) )
			return FALSE;

		$field = $this->sanitize_postmeta_field_key( $field_key )[0];
		$data  = $this->filters( 'import_field_raw_pre', $data, $field, $post );

		if ( FALSE === $data )
			return FALSE;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! array_key_exists( $field, $fields ) )
			return FALSE;

		switch ( $fields[$field]['type'] ) {

			case 'term':

				$this->import_field_raw_terms( $data, $fields[$field], $post );

			break;
			default:

				$this->import_field_raw_strings( $data, $fields[$field], $post );
		}

		return $post->ID;
	}

	public function import_field_raw_strings( $data, $field, $post )
	{
		$strings = [];

		foreach ( (array) $data as $name ) {

			$sanitized = $this->sanitize_posttype_field( $data, $field, $post );

			if ( empty( $sanitized ) )
				continue;

			$strings[] = apply_filters( 'string_format_i18n', $sanitized );
		}

		return $this->set_postmeta_field( $post->ID, $field['name'], Strings::getJoined( $strings ) );
	}

	public function import_field_raw_terms( $data, $field, $post )
	{
		$terms = [];

		foreach ( (array) $data as $name ) {

			$sanitized = trim( Helper::kses( $name, 'none' ) );

			if ( empty( $sanitized ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $sanitized );

			if ( ! $term = get_term_by( 'name', $formatted, $field['taxonomy'] ) ) {

				$term = wp_insert_term( $formatted, $field['taxonomy'] );

				if ( ! is_wp_error( $term ) )
					$terms[] = $term->term_id;

			} else {

				$terms[] = $term->term_id;
			}
		}

		$terms = $this->sanitize_posttype_field( $terms, $field, $post );

		return wp_set_object_terms( $post->ID, Arraay::prepNumeral( $terms ), $field['taxonomy'], FALSE );
	}

	private function get_importer_fields( $posttype = NULL, $object = FALSE )
	{
		/* translators: %s: field title */
		$template = _x( 'Meta: %s', 'Import Field', 'geditorial-meta' );
		$fields   = [];

		foreach ( $this->get_posttype_fields( $posttype ) as $field => $args )
			if ( ! in_array( $args['type'], [ 'term' ] ) )
				$fields['meta__'.$field] = $object ? $args : sprintf( $template, $args['title'] );

		return $fields;
	}

	public function importer_fields( $fields, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $fields;

		return array_merge( $fields, $this->get_importer_fields( $posttype ) );
	}

	public function importer_prepare( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $value;

		$fields = $this->get_importer_fields( $posttype, TRUE );

		if ( ! array_key_exists( $field, $fields ) )
			return $value;

		return $this->sanitize_posttype_field( $value, $fields[$field] );
	}

	public function importer_saved( $post, $data, $prepared, $field_map, $source_id, $attach_id, $terms_all, $raw )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->get_importer_fields( $post->post_type, TRUE );

		foreach ( $field_map as $offset => $field )
			if ( array_key_exists( $field, $fields ) )
				$this->import_posttype_field( $raw[$offset], $fields[$field], $post );
	}

	private function _raise_resources( $count = 0 )
	{
		// Media::disableThumbnailGeneration();
		Taxonomy::disableTermCounting();
		wp_defer_comment_counting( TRUE );

		do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		$this->raise_resources( $count, 60, 'import' );
	}
}

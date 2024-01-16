<?php namespace geminorum\gEditorial\Modules\Units;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Units extends gEditorial\Module
{
	use Internals\PostMeta;
	use Internals\PostTypeFields;

	protected $priority_init           = 12;
	protected $priority_current_screen = 12;

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'   => 'units',
			'title'  => _x( 'Units', 'Modules: Units', 'geditorial-admin' ),
			'desc'   => _x( 'Measurement Units for Contents', 'Modules: Units', 'geditorial-admin' ),
			'icon'   => 'image-crop',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'restapi_attribute' => 'units_rendered',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'titles' => [
				'weight_in_g'  => _x( 'Weight', 'Titles', 'geditorial-units' ),
				'width_in_mm'  => _x( 'Width', 'Titles', 'geditorial-units' ),
				'height_in_mm' => _x( 'Height', 'Titles', 'geditorial-units' ),
				'length_in_mm' => _x( 'Length', 'Titles', 'geditorial-units' ),

				'mass_in_kg'    => _x( 'Mass', 'Titles', 'geditorial-units' ),
				'stature_in_cm' => _x( 'Stature', 'Titles', 'geditorial-units' ),

				'hair_color' => _x( 'Hair Color', 'Titles', 'geditorial-units' ),
				'skin_color' => _x( 'Skin Color', 'Titles', 'geditorial-units' ),
				'eye_color'  => _x( 'Eye Color', 'Titles', 'geditorial-units' ),

				'shoe_size_eu'   => _x( 'Shoe', 'Titles', 'geditorial-units' ),
				'shirt_size_int' => _x( 'Shirt', 'Titles', 'geditorial-units' ),
				'pants_size_int' => _x( 'Pants', 'Titles', 'geditorial-units' ),

				'total_days'  => _x( 'Total Days', 'Titles', 'geditorial-units' ),
				'total_hours' => _x( 'Total Hours', 'Titles', 'geditorial-units' ),

				'book_cover' => _x( 'Book Cover', 'Titles', 'geditorial-units' ),
				'paper_size' => _x( 'Paper Size', 'Titles', 'geditorial-units' ),
			],
			'descriptions' => [
				'weight_in_g'  => _x( 'Weight in Gram', 'Descriptions', 'geditorial-units' ),
				'width_in_mm'  => _x( 'Width in Milimeter', 'Descriptions', 'geditorial-units' ),
				'height_in_mm' => _x( 'Height in Milimeter', 'Descriptions', 'geditorial-units' ),
				'length_in_mm' => _x( 'Length in Milimeter', 'Descriptions', 'geditorial-units' ),

				'mass_in_kg'    => _x( 'Mass in Kilogram', 'Descriptions', 'geditorial-units' ),
				'stature_in_cm' => _x( 'Stature in Centimeter', 'Descriptions', 'geditorial-units' ),

				'hair_color' => _x( 'Color of the Hair', 'Descriptions', 'geditorial-units' ),
				'skin_color' => _x( 'Color of the Skin', 'Descriptions', 'geditorial-units' ),
				'eye_color'  => _x( 'Color of the Eye', 'Descriptions', 'geditorial-units' ),

				'shoe_size_eu'   => _x( 'Size of the Shoe by European standards', 'Descriptions', 'geditorial-units' ),
				'shirt_size_int' => _x( 'Size of the Shirt by International standards', 'Descriptions', 'geditorial-units' ),
				'pants_size_int' => _x( 'Size of the Pants by International standards', 'Descriptions', 'geditorial-units' ),

				'total_days'  => _x( 'The Total of the Days', 'Descriptions', 'geditorial-units' ),
				'total_hours' => _x( 'The Total of the Hours', 'Descriptions', 'geditorial-units' ),

				'book_cover' => _x( 'The Book Cover Size', 'Descriptions', 'geditorial-units' ),
				'paper_size' => _x( 'The Standard Paper Size', 'Descriptions', 'geditorial-units' ),
			],
			'values' => [
				'european_shoe'       => ModuleInfo::getEuropeanShoeSizes(),
				'international_shirt' => ModuleInfo::getInternationalShirtSizes(),
				'international_pants' => ModuleInfo::getInternationalPantsSizes(),
				'bookcover'           => ModuleInfo::getBookCovers(),
				'papersize'           => ModuleInfo::getPaperSizes(),
			],
			'none' => [
				'european_shoe'       => _x( '&ndash; Select Size &ndash;', 'None', 'geditorial-units' ),
				'international_shirt' => _x( '&ndash; Select Size &ndash;', 'None', 'geditorial-units' ),
				'international_pants' => _x( '&ndash; Select Size &ndash;', 'None', 'geditorial-units' ),
				'bookcover'           => _x( '&ndash; Select Cover &ndash;', 'None', 'geditorial-units' ),
				'papersize'           => _x( '&ndash; Select Size &ndash;', 'None', 'geditorial-units' ),
			],
		];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'metabox_title'  => _x( 'Measurements', 'MetaBox Title', 'geditorial-units' ),
			'metabox_action' => _x( 'Configure', 'MetaBox Action', 'geditorial-units' ),
		];

		$strings['misc'] = [
			'units_column_title' => _x( 'Measurements', 'Column Title', 'geditorial-units' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [ 'units' => [
			'_supported' => [
				'weight_in_g'  => [ 'type' => 'gram',      'icon' => 'image-filter' ],
				'width_in_mm'  => [ 'type' => 'milimeter', 'icon' => 'leftright'    ],
				'height_in_mm' => [ 'type' => 'milimeter', 'icon' => 'sort'         ],
				'length_in_mm' => [ 'type' => 'milimeter', 'icon' => 'editor-break' ],

				'mass_in_kg'    => [ 'type' => 'kilogram',   'icon' => 'image-filter' ],
				'stature_in_cm' => [ 'type' => 'centimeter', 'icon' => 'sort'         ],

				'shoe_size_eu'   => [ 'type' => 'european_shoe', 'icon' => 'universal-access-alt'   ],
				'shirt_size_int' => [ 'type' => 'international_shirt', 'icon' => 'universal-access' ],
				'pants_size_int' => [ 'type' => 'international_pants', 'icon' => 'universal-access' ],

				'total_days'  => [ 'type' => 'day'  ],
				'total_hours' => [ 'type' => 'hour' ],

				'book_cover' => [ 'type' => 'bookcover' ],
				'paper_size' => [ 'type' => 'papersize' ],
			],
		] ];
	}

	private function get_posttypes_support_units()
	{
		$posttypes = [ 'post' ];
		$supported = get_post_types_by_support( 'editorial-units' );
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

	}

	public function importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2 );
		$this->filter_module( 'importer', 'prepare', 7 );
		$this->action_module( 'importer', 'saved', 2 );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) ) {
			$this->_edit_screen( $posttype );
			$this->_hook_default_rows( $posttype );
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

				$contexts   = Core\Arraay::column( $fields, 'context' );
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
						'low',
						[
							'posttype'   => $screen->post_type,
							'metabox_id' => $metabox_id,
						]
					);

				add_action( 'geditorial_units_render_metabox', [ $this, 'render_posttype_fields' ], 10, 4 );

				$asset = [
					// 'fields' => $fields, // not used yet!
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->_hook_store_metabox( $screen->post_type );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
				$this->_hook_default_rows( $screen->post_type );

				$asset = [
					'fields' => array_filter( Core\Arraay::column( Core\Arraay::filter( $fields, [ 'quickedit' => TRUE ] ), 'type', 'name' ) ),
				];

				$this->enqueue_asset_js( $asset, $screen );
				$this->_hook_store_metabox( $screen->post_type );
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		$this->filter( 'manage_posts_columns', 2, 15 );
		$this->filter( 'manage_pages_columns', 1, 15 );

		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );

		$this->action( 'quick_edit_custom_box', 2 );
	}

	// early and late actions to make room for other modules
	private function _hook_default_rows( $posttype )
	{
		add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_default' ], 5, 5 );
		// add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_extra' ], 15, 5 );
		// add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_excerpt' ], 20, 5 );
	}

	protected function init_meta_fields()
	{
		foreach ( $this->get_posttypes_support_units() as $posttype )
			$this->add_posttype_fields( $posttype, $this->fields['_supported'], TRUE, 'units' );

		$this->action( 'wp_loaded' );
		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 5, FALSE, $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, FALSE, $this->base );
	}

	public function wp_loaded()
	{
		// initiate the posttype fields for each posttype
		foreach ( $this->posttypes() as $posttype )
			$this->get_posttype_fields( $posttype );
	}

	protected function register_meta_fields()
	{
		$this->filter( 'pairedrest_prepped_post', 3, 9, FALSE, $this->base );
		$this->filter( 'pairedimports_import_types', 4, 5, FALSE, $this->base );

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
				$filtered = $this->filters( 'register_field_args', $register_args, $meta_key, $posttype );

				if ( FALSE !== $filtered )
					register_meta( 'post', $meta_key, $filtered );
			}
		}
	}

	public function attribute_get_callback( $post, $attr, $request, $object_type )
	{
		return $this->get_posttype_fields_data( (int) $post['id'] );
	}

	public function pairedrest_prepped_post( $prepped, $post, $parent )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $prepped;

		return array_merge( $prepped, [
			$this->constant( 'restapi_attribute' ) => $this->get_posttype_fields_data( $post, TRUE ),
		] );
	}

	public function get_posttype_fields_data( $post, $raw = FALSE )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$list   = [];
		$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( empty( $args['rest'] ) )
				continue;

			$meta = ModuleTemplate::getMetaField( $field, [
				'id'       => $post->ID,
				'default'  => $args['default'],
				'noaccess' => FALSE,
			] );

			// if no access or default is FALSE
			if ( FALSE === $meta && $meta !== $args['default'] )
				continue;

			$row = [
				'name'     => $args['rest'],
				'title'    => $args['title'],
				'rendered' => $meta,
			];

			if ( $raw )
				$row['value'] = ModuleTemplate::getMetaFieldRaw( $field, $post->ID, $this->key, FALSE, NULL );

			$list[] = $row;
		}

		return $list;
	}

	// WORKING BUT DISABLED
	// NO NEED: we use original key, so the core will retrieve the value
	public function register_prepare_callback( $value, $request, $args )
	{
		if ( ! $post = WordPress\Post::get() )
			return $value;

		$fields = $this->get_posttype_fields( $post->post_type );
		$fields = Core\Arraay::filter( $fields, [ 'rest' => $args['name'] ] );

		foreach ( $fields as $field => $field_args )
			return $this->get_postmeta_field( $post->ID, $field, $field_args['default'] );

		return $value;
	}

	public function register_sanitize_callback( $meta_value, $meta_key, $object_type )
	{
		$field = $this->get_posttype_field_args( $this->stripprefix( $meta_key ), $object_type );
		return $field ? $this->sanitize_posttype_field( $meta_value, $field, WordPress\Post::get() ) : $meta_value;
	}

	public function render_posttype_fields( $post, $box, $fields = NULL, $context = 'mainbox' )
	{
		$user_id = get_current_user_id();

		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			if ( ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			switch ( $args['type'] ) {

				case 'european_shoe':
				case 'international_shirt':
				case 'international_pants':
				case 'bookcover':
				case 'papersize':
				case 'select':

					ModuleMetaBox::renderFieldSelect( $args, $post );
					break;

				case 'text':
				case 'datestring':

					// ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
					break;

				case 'date':
				case 'datetime':
				case 'identity':
				case 'isbn':
				case 'iban':
				case 'code':
				case 'postcode':
				case 'venue':
				case 'contact':
				case 'mobile':
				case 'phone':
				case 'email':

					ModuleMetaBox::renderFieldInput( $args, $post );
					break;

				case 'float':
				case 'embed':
				case 'text_source':
				case 'audio_source':
				case 'video_source':
				case 'image_source':
				case 'downloadable':
				case 'link':

					// ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, TRUE, $args['title'], FALSE, $args['type'] );
					break;

				case 'day':
				case 'hour':
				case 'gram':
				case 'milimeter':
				case 'kilogram':
				case 'centimeter':
				case 'price':  // TODO must use custom text input + code + ortho-number + separeator
				case 'number':

					ModuleMetaBox::renderFieldNumber( $args, $post );
					break;

				case 'address':
				case 'note':
				case 'textarea':

					ModuleMetaBox::renderFieldTextarea( $args, $post );
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

					// if ( $args['taxonomy'] && WordPress\Taxonomy::can( $args['taxonomy'], 'assign_terms' ) )
					// 	ModuleMetaBox::legacy_fieldTerm( $field, [ $field ], $post, $args['taxonomy'], $args['ltr'], $args['title'] );

					// else if ( ! $args['taxonomy'] )
					// 	ModuleMetaBox::legacy_fieldString( $field, [ $field ], $post, $args['ltr'], $args['title'], FALSE, $args['type'] );
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
				echo Core\HTML::wrap( _x( 'No Measurement Units', 'Message', 'geditorial-units' ), 'field-wrap -empty' );

			$this->actions( 'render_metabox_after', $post, $box, $fields, 'mainbox' );
		echo '</div>';
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		if ( ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return;

		// here only check for cap to edit this post
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! count( $fields ) )
			return;

		$user_id = get_current_user_id();

		foreach ( $fields as $field => $args ) {

			// skip for fields that are auto-saved on admin edit-post page
			if ( in_array( $field, [ 'parent_post' ], TRUE ) )
				continue;

			if ( ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			$request = sprintf( '%s-%s-%s', $this->base, $this->module->name, $field );

			if ( FALSE !== ( $data = self::req( $request, FALSE ) ) )
				$this->import_posttype_field( $data, $args, $post );
		}
	}

	public function import_posttype_field( $data, $field, $post, $override = TRUE )
	{
		switch ( $field['type'] ) {

			case 'parent_post':

				if ( ! $parent = WordPress\Post::get( (int) $data ) )
					return FALSE;

				if ( ! WordPress\Post::setParent( $post->ID, $parent->ID, FALSE ) )
					return FALSE;

				break;

			case 'term':

				if ( empty( $field['taxonomy'] ) )
					return FALSE;

				if ( ! WordPress\Taxonomy::can( $field['taxonomy'], 'assign_terms' ) )
					return FALSE;

				if ( ! $override && FALSE !== get_the_terms( $post, $field['taxonomy'] ) )
					return FALSE;

				$terms = $this->sanitize_posttype_field( $data, $field, $post );

				return wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $terms ), $field['taxonomy'], FALSE );

			default:

				if ( ! $override && FALSE !== $this->get_postmeta_field( $post->ID, $field['name'] ) )
					return FALSE;

				return $this->set_postmeta_field( $post->ID, $field['name'], $this->sanitize_posttype_field( $data, $field, $post ) );
		}
	}

	public function manage_pages_columns( $columns )
	{
		return $this->manage_posts_columns( $columns, 'page' );
	}

	public function manage_posts_columns( $columns, $posttype )
	{
		return Core\Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title( 'units', $posttype ),
		], 'comments', 'before' );
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		if ( $this->classs() != $column_name )
			return;

		if ( ! $post = WordPress\Post::get( $post_id ) )
			return;

		$prefix   = $this->classs().'-';
		$fields   = $this->get_posttype_fields( $post->post_type );
		$excludes = []; // excludes are for other modules

		foreach ( $fields as $field => $args ) {

			if ( $args['quickedit'] )
				$excludes[] = $field;

			else if ( in_array( $args['name'], [ 'source_title', 'source_url', 'action_title', 'action_url' ] ) )
				$excludes[] = $field;

			else if ( in_array( $args['type'], [ 'term', 'postbox_html', 'postbox_tiny', 'postbox_legacy' ] ) )
				$excludes[] = $field;
		}

		echo '<div class="geditorial-admin-wrap-column -units"><ul class="-rows">';

			// FIXME: DEPRECATED
			$this->actions( 'column_row', $post, $fields, $excludes );

			do_action( $this->hook( 'column_row', $post->post_type ),
				$post,
				$this->wrap_open_row( 'attr', [
					'-column-attr',
					'-type-'.$post->post_type,
					'%s', // to use by caller
				] ),
				'</li>',
				$fields,
				$excludes
			);

		echo '</ul></div>';

		// NOTE: for `quickedit` enabled fields
		foreach ( Core\Arraay::filter( $fields, [ 'quickedit' => TRUE ] ) as $field => $args )
			echo '<div class="hidden '.$prefix.$field.'-value">'.
				$this->_prep_posttype_field_for_input(
					$this->get_postmeta_field( $post->ID, $field ),
					$field,
					$args
				).'</div>';
	}

	// NOTE: only renders `quickedit` enabled fields
	public function column_row_default( $post, $before, $after, $fields, $excludes )
	{
		foreach ( $fields as $field_key => $field ) {

			// if ( ! $field['quickedit'] )
			// 	continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key ) )
				continue;

			printf( $before, '-units-'.$field_key );
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo $after;
		}
	}

	// NOTE: for more `MetaBox::renderFieldInput()`
	private function _prep_posttype_field_for_input( $value, $field_key, $field )
	{
		if ( empty( $field['type'] ) )
			return $value;

		switch ( $field['type'] ) {
			// case 'date'    : return $value ? Datetime::prepForInput( $value, 'Y/m/d', 'gregorian' )    : $value;
			// case 'datetime': return $value ? Datetime::prepForInput( $value, 'Y/m/d H:i', 'gregorian' ): $value;
		}

		return $value;
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
			$class = Core\HTML::prepClass( $name );

			echo '<label class="hidden '.$class.'">';
				echo '<span class="title">'.$args['title'].'</span>';
				echo '<span class="input-text-wrap">';
				echo '<input name="'.$name.'" class="'.$class.'" value=""';
				echo $args['pattern'] ? ( ' pattern="'.$args['pattern'].'"' ) : '';
				echo $args['ltr'] ? ' dir="ltr"' : '';
				echo $args['type'] === 'number' ? ' type="number" ' : ' type="text" ';
				echo '></span>';
			echo '</label>';
		}

		$this->nonce_field( 'nobox' );
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field ) {

			case 'total_days' :
				return sprintf( Helper::noopedCount( trim( $raw ),
					/* translators: %s: day count */
					_nx_noop( '%s Day', '%s Days', 'Noop', 'geditorial-units' ) ),
					Core\Number::format( trim( $raw ) )
				);

			case 'total_hours' :
				return sprintf( Helper::noopedCount( trim( $raw ),
					/* translators: %s: hour count */
					_nx_noop( '%s Hour', '%s Hours', 'Noop', 'geditorial-units' ) ),
					Core\Number::format( trim( $raw ) )
				);
		}

		switch ( $field_args['type'] ) {

			case 'gram':
				return sprintf( Helper::noopedCount( trim( $raw ),
					/* translators: %s: hour count */
					_nx_noop( '%s Gram', '%s Grams', 'Noop', 'geditorial-units' ) ),
					Core\Number::format( trim( $raw ) )
				);

			case 'kilogram':
				return sprintf( Helper::noopedCount( trim( $raw ),
					/* translators: %s: hour count */
					_nx_noop( '%s Kilogram', '%s Kilograms', 'Noop', 'geditorial-units' ) ),
					Core\Number::format( trim( $raw ) )
				);

			case 'milimeter':
					return sprintf( Helper::noopedCount( trim( $raw ),
						/* translators: %s: hour count */
						_nx_noop( '%s Milimeter', '%s Milimeters', 'Noop', 'geditorial-units' ) ),
						Core\Number::format( trim( $raw ) )
					);

			case 'centimeter':
				return sprintf( Helper::noopedCount( trim( $raw ),
					/* translators: %s: hour count */
					_nx_noop( '%s Centimeter', '%s Centimeters', 'Noop', 'geditorial-units' ) ),
					Core\Number::format( trim( $raw ) )
				);
		}

		return $meta;
	}

	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		// switch ( $field_key ) {}

		if ( ! empty( $field['type'] ) ) {

			switch ( $field['type'] ) {

				case 'european_shoe':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][($raw ?: $value)]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: shoe size placeholder */
					return sprintf( _x( 'Size %s Shoe', 'Display', 'geditorial-units' ), $meta );

				case 'international_shirt':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][($raw ?: $value)]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: shoe size placeholder */
					return sprintf( _x( 'Size %s Shirt', 'Display', 'geditorial-units' ), $meta );

				case 'international_pants':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][($raw ?: $value)]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: shoe size placeholder */
					return sprintf( _x( 'Size %s Pants', 'Display', 'geditorial-units' ), $meta );
			}
		}

		return $value;
	}

	public function posttypefields_import_raw_data( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || $module !== $this->key )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! count( $fields ) )
			return;

		$user_id = get_current_user_id();

		foreach ( $fields as $field => $args ) {

			if ( ! array_key_exists( $field, $data ) )
				continue;

			if ( $check_access && ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			$this->import_posttype_field( $data[$field], $args, $post, $override );
		}
	}

	// OLD: `import_from_meta()`
	public function import_field_meta( $post_meta_key, $field, $limit = FALSE )
	{
		$rows = WordPress\Database::getPostMetaRows( $post_meta_key, $limit );

		foreach ( $rows as $row )
			$this->import_field_raw( explode( ',', $row->meta ), $field, $row->post_id );

		return count( $rows );
	}

	// OLD: `import_to_meta()`
	public function import_field_raw( $data, $field_key, $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
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

		return $this->set_postmeta_field( $post->ID, $field['name'], WordPress\Strings::getJoined( $strings ) );
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

		return wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $terms ), $field['taxonomy'], FALSE );
	}

	private function get_importer_fields( $posttype = NULL, $object = FALSE )
	{
		/* translators: %s: field title */
		$template = _x( 'Units: %s', 'Import Field', 'geditorial-units' );
		$fields   = [];

		foreach ( $this->get_posttype_fields( $posttype ) as $field => $args )
			if ( ! in_array( $args['type'], [ 'term', 'parent_post' ], TRUE ) )
				$fields['units__'.$field] = $object ? $args : sprintf( $template, $args['title'] );

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

	public function importer_saved( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->get_importer_fields( $post->post_type, TRUE );

		foreach ( $atts['map'] as $offset => $field )
			if ( array_key_exists( $field, $fields ) )
				$this->import_posttype_field( $atts['raw'][$offset], $fields[$field], $post, $atts['override'] );
	}

	public function pairedimports_import_types( $types, $linked, $posttypes, $module_key )
	{
		foreach ( $this->posttypes() as $posttype ) {

			if ( ! in_array( $posttype, $posttypes, TRUE ) )
				continue;

			$fields = $this->get_posttype_fields( $posttype, [ 'import' => TRUE ] );

			if ( empty( $fields ) )
				continue;

			$types = array_merge( $types, Core\Arraay::pluck( $fields, 'title', 'name' ) );
		}

		return $types;
	}
}

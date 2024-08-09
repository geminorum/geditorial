<?php namespace geminorum\gEditorial\Modules\Units;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
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
			'_general' => [
				// 'insert_content_enabled', // TODO
			],
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

				'payload_in_kg'   => _x( 'Payload', 'Titles', 'geditorial-units' ),
				'maxspeed_in_kmh' => _x( 'Max Speed', 'Titles', 'geditorial-units' ),

				'hair_color' => _x( 'Hair Color', 'Titles', 'geditorial-units' ),
				'skin_color' => _x( 'Skin Color', 'Titles', 'geditorial-units' ),
				'eye_color'  => _x( 'Eye Color', 'Titles', 'geditorial-units' ),

				'total_days'  => _x( 'Total Days', 'Titles', 'geditorial-units' ),
				'total_hours' => _x( 'Total Hours', 'Titles', 'geditorial-units' ),

				'total_members' => _x( 'Total Members', 'Titles', 'geditorial-units' ),
				'total_people'  => _x( 'Total People', 'Titles', 'geditorial-units' ),

				'book_cover' => _x( 'Book Cover', 'Titles', 'geditorial-units' ),
				'paper_size' => _x( 'Paper Size', 'Titles', 'geditorial-units' ),
			],
			'descriptions' => [
				'weight_in_g'  => _x( 'Weight in Gram', 'Descriptions', 'geditorial-units' ),
				'width_in_mm'  => _x( 'Width in Milimeter', 'Descriptions', 'geditorial-units' ),
				'height_in_mm' => _x( 'Height in Milimeter', 'Descriptions', 'geditorial-units' ),
				'length_in_mm' => _x( 'Length in Milimeter', 'Descriptions', 'geditorial-units' ),

				'payload_in_kg'   => _x( 'Payload in Kilogram', 'Descriptions', 'geditorial-units' ),
				'maxspeed_in_kmh' => _x( 'Max Speed in Kilometre per Hour', 'Descriptions', 'geditorial-units' ),

				'hair_color' => _x( 'Color of the Hair', 'Descriptions', 'geditorial-units' ),
				'skin_color' => _x( 'Color of the Skin', 'Descriptions', 'geditorial-units' ),
				'eye_color'  => _x( 'Color of the Eye', 'Descriptions', 'geditorial-units' ),

				'total_days'  => _x( 'The Total Number of the Days', 'Descriptions', 'geditorial-units' ),
				'total_hours' => _x( 'The Total Number of the Hours', 'Descriptions', 'geditorial-units' ),

				'total_members' => _x( 'The Total Number of the Members', 'Descriptions', 'geditorial-units' ),
				'total_people'  => _x( 'The Total Number of the People', 'Descriptions', 'geditorial-units' ),

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

		$strings['importer'] = [
			/* translators: %s: field title */
			'field_title' => _x( 'Units: %s', 'Import Field Title', 'geditorial-units' ),
			/* translators: %s: field title */
			'ignored_title' => _x( 'Units: %s [Ignored]', 'Import Field Title', 'geditorial-units' ),
		];

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
				'weight_in_g'  => [ 'type' => 'gram',      'icon' => 'image-filter', 'data_unit' => 'gram'      ],
				'width_in_mm'  => [ 'type' => 'milimeter', 'icon' => 'leftright'   , 'data_unit' => 'milimeter' ],
				'height_in_mm' => [ 'type' => 'milimeter', 'icon' => 'sort'        , 'data_unit' => 'milimeter' ],
				'length_in_mm' => [ 'type' => 'milimeter', 'icon' => 'editor-break', 'data_unit' => 'milimeter' ],

				'payload_in_kg'   => [ 'type' => 'kilogram',     'icon' => 'image-filter', 'data_unit' => 'kilogram'    ],
				'maxspeed_in_kmh' => [ 'type' => 'km_per_hour',  'icon' => 'car'         , 'data_unit' => 'km_per_hour' ],

				'total_days'  => [ 'type' => 'day' , 'data_unit' => 'day'  ],
				'total_hours' => [ 'type' => 'hour', 'data_unit' => 'hour' ],

				'total_members' => [ 'type' => 'member', 'data_unit' => 'person' ],
				'total_people'  => [ 'type' => 'person', 'data_unit' => 'person' ],   // `participant`/`contributor`/`competitor`/`player`

				'book_cover' => [ 'type' => 'bookcover' ],
				'paper_size' => [ 'type' => 'papersize' ],
			],
			'page' => [],
		] ];
	}

	public function init()
	{
		parent::init();

		$this->posttypefields_init_meta_fields();
		$this->posttypefields_register_meta_fields();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 5, FALSE, $this->base );
		$this->action( 'posttypefields_import_raw_data', 5, 9, 'action', $this->base );
		$this->filter( 'searchselect_result_extra_for_post', 3, 12, 'filter', $this->base );
	}

	public function importer_init()
	{
		$this->posttypefields__hook_importer_init();
	}

	public function setup_ajax()
	{
		if ( ! $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			return;

		if ( ! $this->get_posttype_fields( $posttype ) )
			return;

		$this->_edit_screen( $posttype );
		$this->_hook_default_rows( $posttype );
		$this->_hook_store_metabox( $posttype );
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

				// $this->enqueue_asset_js( $asset, $screen );
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

	// TODO: optional display of column for each supported: `column_posttypes`
	// -- with fallback to tweaks column
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
				case 'people':
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

				case 'member':
				case 'person':
				case 'day':
				case 'hour':
				case 'gram':
				case 'km_per_hour':
				case 'milimeter':
				case 'kilogram':
				case 'centimeter':
				case 'meter':
				case 'kilometre':
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
				$this->posttypefields_do_import_field( $data, $args, $post );
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
		switch ( $field_args['type'] ) {

			case 'day':
			case 'hour':
			case 'member':
			case 'person':
			case 'gram':
			case 'kilogram':
			case 'km_per_hour':
			case 'milimeter':
			case 'centimeter':
			case 'meter':
			case 'kilometre':

				if ( 'export' === $context )
					return trim( $raw );

				return sprintf( Helper::noopedCount( trim( $raw ),
					Info::getNoop( $field_args['type'] ) ),
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
						? $this->strings['values'][$field['type']][( $raw ?: $value )]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: shoe size placeholder */
					return sprintf( _x( 'Size %s Shoe', 'Display', 'geditorial-units' ), $meta );

				case 'international_shirt':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][( $raw ?: $value )]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: shoe size placeholder */
					return sprintf( _x( 'Size %s Shirt', 'Display', 'geditorial-units' ), $meta );

				case 'international_pants':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][( $raw ?: $value )]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: shoe size placeholder */
					return sprintf( _x( 'Size %s Pants', 'Display', 'geditorial-units' ), $meta );
			}
		}

		return $value;
	}
}

<?php namespace geminorum\gEditorial\Modules\Units;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
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
			/* translators: %1$s: current post title, %2$s: posttype singular name */
			'mainbox_title'  => _x( 'Measurements', 'MetaBox: `mainbox_title`', 'geditorial-units' ),
			'mainbox_action' => _x( 'Configure', 'MetaBox: `mainbox_action`', 'geditorial-units' ),
		];

		$strings['notices'] = [
			'no_fields' => _x( 'There are no measurement units available!', 'Notice: `no_fields`', 'geditorial-units' ),
		];

		$strings['misc'] = [
			'units_column_title' => _x( 'Measurements', 'Column Title', 'geditorial-units' ),
		];

		return $strings;
	}

	protected function get_global_fields()
	{
		return [
			$this->key => [
				'_supported' => [
					'weight_in_g'  => [ 'type' => 'gram',      'icon' => 'image-filter', 'data_unit' => 'gram'      ],
					'width_in_mm'  => [ 'type' => 'milimeter', 'icon' => 'leftright'   , 'data_unit' => 'milimeter' ],
					'height_in_mm' => [ 'type' => 'milimeter', 'icon' => 'sort'        , 'data_unit' => 'milimeter' ],
					'length_in_mm' => [ 'type' => 'milimeter', 'icon' => 'editor-break', 'data_unit' => 'milimeter' ],

					'payload_in_kg'   => [ 'type' => 'kilogram',     'icon' => 'image-filter', 'data_unit' => 'kilogram'    ],
					'maxspeed_in_kmh' => [ 'type' => 'km_per_hour',  'icon' => 'car'         , 'data_unit' => 'km_per_hour' ],

					'total_days'  => [ 'type' => 'day',  'data_unit' => 'day'  ],
					'total_hours' => [ 'type' => 'hour', 'data_unit' => 'hour' ],

					'total_members' => [ 'type' => 'member', 'data_unit' => 'person' ],
					'total_people'  => [ 'type' => 'person', 'data_unit' => 'person' ],   // `participant`/`contributor`/`competitor`/`player`

					'book_cover' => [ 'type' => 'bookcover' ],
					'paper_size' => [ 'type' => 'papersize' ],
				],
				'page' => [],
			],
		];
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

		$this->posttypefields__hook_setup_ajax( $posttype );
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

				$this->posttypefields__hook_metabox( $screen, $fields );

			} else if ( 'edit' == $screen->base ) {

				$this->_admin_enabled();
				$this->posttypefields__hook_edit_screen( $screen->post_type );
				$this->posttypefields__enqueue_edit_screen( $screen->post_type, $fields );
				$this->_hook_store_metabox( $screen->post_type, 'posttypefields' );
			}
		}
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		switch ( $field_args['type'] ) {

			case 'distance':
				return Core\Distance::prep( $raw, $field_args, $context );

			case 'duration':
				return Core\Duration::prep( $raw, $field_args, $context );

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

					/* translators: %s: shirt size placeholder */
					return sprintf( _x( 'Size %s Shirt', 'Display', 'geditorial-units' ), $meta );

				case 'international_pants':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][( $raw ?: $value )]
						: Core\Number::localize( $raw ?: $value );

					/* translators: %s: pants size placeholder */
					return sprintf( _x( 'Size %s Pants', 'Display', 'geditorial-units' ), $meta );

				case 'bookcover':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][( $raw ?: $value )]
						: ( $raw ?: $value );

					/* translators: %s: book cover placeholder */
					return sprintf( _x( '%s Book-Cover', 'Display', 'geditorial-units' ), $meta );

				case 'papersize':

					$meta = \array_key_exists( $raw ?: $value, $this->strings['values'][$field['type']] )
						? $this->strings['values'][$field['type']][( $raw ?: $value )]
						: ( $raw ?: $value );

					/* translators: %s: paper size cover placeholder */
					return sprintf( _x( 'Size %s Paper', 'Display', 'geditorial-units' ), $meta );
			}
		}

		return $value;
	}
}

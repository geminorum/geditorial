<?php namespace geminorum\gEditorial\Modules\Isbn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

class Isbn extends gEditorial\Module
{

	protected $barcode_type = 'ean13';

	public static function module()
	{
		return [
			'name'     => 'isbn',
			'title'    => _x( 'ISBN', 'Modules: ISBN', 'geditorial-admin' ),
			'desc'     => _x( 'Standard Book Numbers', 'Modules: ISBN', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'upc-scan' ],
			'access'   => 'beta',
			'keywords' => [
				'barcode',
				'woocommerce',
				'bibliographic',
				'identifier',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_supports'        => [
				'shortcode_support',
				'woocommerce_support' => [
					_x( 'Select to display data on product attributes.', 'Setting Description', 'geditorial-isbn' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_shortcode' => 'isbn',
		];
	}

	protected function get_global_fields()
	{
		return [
			'meta' => [
				'_supported' => [
					'bibliographic' => [
						// @REF: `https://opac.nlai.ir/opac-prod/bibliographic/{$publication_bib}`
						'title'       => _x( 'Bibliographic', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'National Bibliographic Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'code',
						'icon'        => 'shortcode',
						'quickedit'   => TRUE,
						'order'       => 1810,
					],
					'isbn' => [
						'title'       => _x( 'ISBN', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
						'icon'        => [ 'misc-16', 'upc' ],
						'quickedit'   => TRUE,
						'order'       => 1810,
					],
					'isbn2' => [
						'title'       => _x( 'ISBN #2', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
						'quickedit'   => TRUE,
						'order'       => 1820,
					],
					'isbn2_label' => [
						'title'       => _x( 'ISBN #2 Label', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'Label to use on ISBN #2', 'Field Description', 'geditorial-isbn' ),
						'order'       => 1825,
					],
					'isbn3' => [
						'title'       => _x( 'ISBN #3', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
						'quickedit'   => TRUE,
						'order'       => 1830,
					],
					'isbn3_label' => [
						'title'       => _x( 'ISBN #3 Label', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'Label to use on ISBN #3', 'Field Description', 'geditorial-isbn' ),
						'order'       => 1835,
					],
					'isbn4' => [
						'title'       => _x( 'ISBN #4', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
						'quickedit'   => TRUE,
						'order'       => 1840,
					],
					'isbn4_label' => [
						'title'       => _x( 'ISBN #4 Label', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'Label to use on ISBN #4', 'Field Description', 'geditorial-isbn' ),
						'order'       => 1845,
					],
					'isbn5' => [
						'title'       => _x( 'ISBN #5', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
						'quickedit'   => TRUE,
						'order'       => 1850,
					],
					'isbn5_label' => [
						'title'       => _x( 'ISBN #5 Label', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'Label to use on ISBN #5', 'Field Description', 'geditorial-isbn' ),
						'order'       => 1855,
					],
				],
			],
		];
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();
		$this->filter_module( 'book', 'editform_meta_summary', 2, 20 );

		$this->filter_module( 'national_library', 'default_posttype_bib_metakey', 2 );
		$this->filter_module( 'national_library', 'default_posttype_isbn_metakey', 2 );
		$this->filter_module( 'datacodes', 'default_posttype_barcode_metakey', 2 );
		$this->filter_module( 'datacodes', 'default_posttype_barcode_type', 3 );

		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_type', 2 );
		$this->filter_module( 'identified', 'possible_keys_for_identifier', 2 );
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );

		$this->filter( 'searchselect_result_extra_for_post', 3, 22, FALSE, $this->base );

		$this->register_shortcode( 'main_shortcode' );

		if ( ! $this->get_setting( 'woocommerce_support' ) )
			return;

		$this->filter( 'display_product_attributes', 2, 99, FALSE, 'woocommerce' );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'raw'     => NULL,
			'field'   => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'class'   => '',
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = '';

		if ( $args['raw'] && $data = Core\ISBN::sanitize( $args['raw'] ) )
			$html = Info::lookupISBN( $data );

		else if ( $post = WordPress\Post::get( $args['id'] ) )
			$html = Services\PostTypeFields::getField( $args['field'] ?? 'isbn', [ 'id' => $post ] );

		if ( ! $html )
			return $content;

		return ShortCode::wrap( $html, $this->constant( 'main_shortcode' ), $args );
	}

	public function book_editform_meta_summary( $fields, $post )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $fields;

		$fields['isbn']  = NULL;
		$fields['isbn2'] = Services\PostTypeFields::getFieldRaw( 'isbn2_label', $post->ID, 'meta', FALSE, NULL );
		$fields['isbn3'] = Services\PostTypeFields::getFieldRaw( 'isbn3_label', $post->ID, 'meta', FALSE, NULL );
		$fields['isbn4'] = Services\PostTypeFields::getFieldRaw( 'isbn4_label', $post->ID, 'meta', FALSE, NULL );
		$fields['isbn5'] = Services\PostTypeFields::getFieldRaw( 'isbn5_label', $post->ID, 'meta', FALSE, NULL );

		return $fields;
	}

	public function national_library_default_posttype_bib_metakey( $default, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $default;

		if ( $metakey = Services\PostTypeFields::getPostMetaKey( 'bibliographic', 'meta' ) )
			return $metakey;

		return $default;
	}

	public function national_library_default_posttype_isbn_metakey( $default, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $default;

		if ( $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return $metakey;

		return $default;
	}

	public function datacodes_default_posttype_barcode_metakey( $default, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $default;

		if ( $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return $metakey;

		return $default;
	}

	public function datacodes_default_posttype_barcode_type( $default, $posttype, $types )
	{
		if ( $this->posttype_supported( $posttype ) )
			return ModuleHelper::BARCODE;

		return $default;
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $default;

		if ( $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return $metakey;

		return $default;
	}

	public function identified_default_posttype_identifier_type( $default, $posttype )
	{
		if ( $this->posttype_supported( $posttype ) )
			return 'isbn';

		return $default;
	}

	public function identified_possible_keys_for_identifier( $keys, $posttype )
	{
		if ( $this->posttype_supported( $posttype ) )
			return array_merge( $keys, [
				'publication_isbn' => 'isbn',
				'isbn'             => 'isbn',

				_x( 'ISBN', 'Possible Identifier Key', 'geditorial-isbn' ) => 'isbn',

				'شابک'       => 'isbn',
				'شماره شابک' => 'isbn',
				'شابک کتاب'  => 'isbn',
			] );

		return $keys;
	}

	public function static_covers_default_posttype_reference_metakey( $default, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $default;

		if ( $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return $metakey;

		return $default;
	}

	// NOTE: late overrides of the fields values and keys
	public function searchselect_result_extra_for_post( $data, $post, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $post = WordPress\Post::get( $post ) )
			return $data;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $data;

		if ( $isbn = Services\PostTypeFields::getFieldRaw( 'isbn', $post->ID ) )
			$data['isbn'] = $isbn;

		return $data;
	}

	public function display_product_attributes( $attributes, $product )
	{
		$post_id = $product->get_id();

		if ( $isbn = Services\PostTypeFields::getField( 'isbn', [ 'id' => $post_id ] ) )
			$attributes[$this->classs( 'primary' )] = [
				'label' => _x( 'ISBN', 'Field Title', 'geditorial-isbn' ),
				'value' => $isbn,
			];

		if ( $isbn2 = Services\PostTypeFields::getField( 'isbn2', [ 'id' => $post_id ] ) )
			$attributes[$this->classs( 'second' )] = [
				'label' => Services\PostTypeFields::getFieldRaw( 'isbn2_label', $post_id, 'meta', FALSE, _x( 'ISBN #2', 'Field Title', 'geditorial-isbn' ) ),
				'value' => $isbn2,
			];

		if ( $isbn3 = Services\PostTypeFields::getField( 'isbn3', [ 'id' => $post_id ] ) )
			$attributes[$this->classs( 'third' )] = [
				'label' => Services\PostTypeFields::getFieldRaw( 'isbn3_label', $post_id, 'meta', FALSE, _x( 'ISBN #3', 'Field Title', 'geditorial-isbn' ) ),
				'value' => $isbn3,
			];

		if ( $isbn4 = Services\PostTypeFields::getField( 'isbn4', [ 'id' => $post_id ] ) )
			$attributes[$this->classs( 'fourth' )] = [
				'label' => Services\PostTypeFields::getFieldRaw( 'isbn4_label', $post_id, 'meta', FALSE, _x( 'ISBN #4', 'Field Title', 'geditorial-isbn' ) ),
				'value' => $isbn4,
			];

		if ( $isbn5 = Services\PostTypeFields::getField( 'isbn5', [ 'id' => $post_id ] ) )
			$attributes[$this->classs( 'fifth' )] = [
				'label' => Services\PostTypeFields::getFieldRaw( 'isbn5_label', $post_id, 'meta', FALSE, _x( 'ISBN #5', 'Field Title', 'geditorial-isbn' ) ),
				'value' => $isbn5,
			];

		return $attributes;
	}
}

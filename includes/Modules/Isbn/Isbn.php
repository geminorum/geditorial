<?php namespace geminorum\gEditorial\Modules\Isbn;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Isbn extends gEditorial\Module
{
	use Internals\RawImports;

	protected $imports_datafiles = [
		'range-message' => 'RangeMessage.xml'  // Tue, 4 Nov 2025 23:31:28 GMT @source https://www.isbn-international.org/range_file_generation
	];

	public static function module()
	{
		return [
			'name'     => 'isbn',
			'title'    => _x( 'ISBN', 'Modules: ISBN', 'geditorial-admin' ),
			'desc'     => _x( 'Standard Book Numbers', 'Modules: ISBN', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'upc-scan' ],
			'access'   => 'beta',
			'keywords' => [
				'book',
				'barcode',
				'bibliographic',
				'identifier',
				'meta-field',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_supports'        => [
				'shortcode_support',
				'woocommerce_support',
			],
			'_content' => [
				[
					'field'        => 'default_posttype',
					'type'         => 'select',
					'title'        => _x( 'Default Post-Type', 'Setting Title', 'geditorial-isbn' ),
					'description'  => _x( 'Defines the default post-type to create new posts with queried data on front-end.', 'Setting Description', 'geditorial-isbn' ),
					'string_empty' => _x( 'Define supported post-types first!', 'Setting Description', 'geditorial-isbn' ),
					'none_title'   => gEditorial\Settings::showOptionNone(),
					'values'       => $this->list_posttypes() ?: FALSE,
				],
			],
			'_frontend' => [
				'frontend_search' => [ _x( 'Adds results by ISBN information on front-end search.', 'Setting Description', 'geditorial-isbn' ), TRUE ],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'isbn' ],
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
						'bulkedit'    => FALSE,
						'order'       => 1810,
					],
					'isbn' => [
						'title'       => _x( 'ISBN', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
						'icon'        => [ 'misc-16', 'upc' ],
						'quickedit'   => TRUE,
						'bulkedit'    => FALSE,
						'order'       => 1810,
					],
					'isbn2' => [
						'title'       => _x( 'ISBN #2', 'Field Title', 'geditorial-isbn' ),
						'description' => _x( 'International Standard Book Number', 'Field Description', 'geditorial-isbn' ),
						'type'        => 'isbn',
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

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				WordPress\WooCommerce::PRODUCT_POSTTYPE,
			], $this->keep_posttypes )
		);
	}

	public function meta_init()
	{
		$this->add_posttype_fields_supported();

		$this->filter( 'prep_meta_row', 2, 12, 'module', $this->base );
		$this->filter( 'meta_field', 7, 9, FALSE, $this->base );
		$this->filter( 'meta_initial_isbn', 4, 2, FALSE, $this->base );

		$this->filter_module( 'book', 'editform_meta_summary', 2, 20 );

		$this->filter_module( 'national_library', 'default_posttype_bib_metakey', 2 );
		$this->filter_module( 'national_library', 'default_posttype_isbn_metakey', 2 );
		$this->filter_module( 'datacodes', 'default_posttype_barcode_metakey', 2 );
		$this->filter_module( 'datacodes', 'default_posttype_barcode_type', 3 );

		$this->action_module( 'identified', 'identifier_notfound', 3 );
		$this->filter_module( 'identified', 'default_posttype_identifier_metakey', 2 );
		$this->filter_module( 'identified', 'default_posttype_identifier_type', 2 );
		$this->filter_module( 'identified', 'possible_keys_for_identifier', 2 );
		$this->filter_module( 'static_covers', 'default_posttype_reference_metakey', 2 );

		$this->filter( 'template_posttype_addnew_extra', 4, 10, FALSE, $this->base );
		$this->filter( 'searchselect_result_extra_for_post', 3, 22, FALSE, $this->base );

		if ( $this->get_setting( 'frontend_search', TRUE ) )
			$this->filter( 'posts_search_append_meta_frontend', 3, 8, FALSE, $this->base );

		if ( $this->get_setting( 'woocommerce_support' ) )
			$this->_init_woocommerce();

		$this->register_shortcode( 'main_shortcode' );
	}

	private function _init_woocommerce()
	{
		$fields = $this->fields['meta']['_supported'];
		unset( $fields['isbn'] ); // default on wc is `_global_unique_id`

		$this->add_posttype_fields( WordPress\WooCommerce::PRODUCT_POSTTYPE, $fields );

		if ( is_admin() ) {

			$this->action( 'current_screen', 1, 10, 'woocommerce' );

		} else {

			$this->filter( 'display_product_attributes', 2, 99, FALSE, 'woocommerce' );
		}
	}

	public function current_screen_woocommerce( $screen )
	{
		if ( 'edit' === $screen->base
			&& $screen->post_type === WordPress\WooCommerce::PRODUCT_POSTTYPE ) {

			add_filter( sprintf( 'manage_edit-%s_sortable_columns', $screen->post_type ),
				[ $this, 'manage_sortable_columns' ], 20, 1 );

			add_filter( sprintf( 'manage_%s_posts_columns', $screen->post_type ),
				[ $this, 'manage_products_columns' ], 20, 1 ); // after `Tweaks` Module

			add_action( sprintf( 'manage_%s_posts_custom_column', $screen->post_type ),
				[ $this, 'manage_custom_column' ], 10, 2 );

			add_action( $this->hook( 'column_row', $screen->post_type ),
				[ $this, 'column_row_global_unique_id' ], 5, 4 );

			add_action( $this->hook( 'column_row', $screen->post_type ),
				[ $this, 'column_row_posttype_fields' ], 9, 4 );
		}
	}

	public function manage_sortable_columns( $columns )
	{
		return array_merge( $columns, [
			$this->classs() => 'global_unique_id',
		] );
	}

	public function manage_products_columns( $columns )
	{
		unset( $columns['global_unique_id'] );

		return Core\Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title(
				$this->module->name,
				WordPress\WooCommerce::PRODUCT_POSTTYPE,
				_x( 'ISBN', 'Column Title', 'geditorial-isbn' )
			),
		], 'price', 'before' ); // `featured`
	}

	// NOTE: maybe double used (in future!)
	public function manage_custom_column( $column, $post_id )
	{
		global $post;

		if ( $this->classs() != $column )
			return;

		if ( $this->check_hidden_column( $column ) )
			return;

		echo '<div class="geditorial-admin-wrap-column -isbn -rows"><ul class="-rows">'; //  -flex-rows

			do_action( $this->hook( 'column_row', $post->post_type ),
				$post,
				$this->wrap_open_row( 'row', [
					'-column-row',
					'-type-'.$post->post_type,
					'%s',
				] ),
				'</li>',
				$this->module->name
			);

		echo '</ul></div>';
	}

	public function column_row_global_unique_id( $post, $before, $after, $module )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		if ( ! $gtin = $product->get_global_unique_id() )
			return;

		printf( $before, '-product-gtin' );
			echo $this->get_column_icon( FALSE, $this->module->icon, __( 'GTIN, UPC, EAN, or ISBN', 'woocommerce' ), $post->post_type );
			echo Core\HTML::code( $gtin, '-gtin', Core\ISBN::sanitize( $gtin ) );
		echo $after;
	}

	public function column_row_posttype_fields( $post, $before, $after, $module )
	{
		$title  = _x( 'ISBN', 'Row Icon Title', 'geditorial-isbn' );
		$fields = [
			'isbn',
			'isbn2',
			'isbn3',
			'isbn4',
			'isbn5',
		];

		foreach ( $fields as $field ) {

			if ( ! $data = Services\PostTypeFields::getField( $field, [ 'id' => $post ] ) )
				continue;

			$label = Services\PostTypeFields::getFieldRaw( sprintf( '%s_label', $field ), $post->ID, 'meta', FALSE, $title );

			printf( $before, '-product-isbn -'.$field );
				echo $this->get_column_icon( FALSE, $this->module->icon, $label ?: $title, $post->post_type );
				echo Core\HTML::code( $data, '-gtin', TRUE );
			echo $after;
		}
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
			$html = gEditorial\Info::lookupISBN( $data );

		// TODO: support Woo Commerce

		else if ( $post = WordPress\Post::get( $args['id'] ) )
			$html = Services\PostTypeFields::getField( $args['field'] ?? 'isbn', [ 'id' => $post ] );

		if ( ! $html )
			return $content;

		return gEditorial\ShortCode::wrap( $html, $this->constant( 'main_shortcode' ), $args );
	}

	private function _get_main_isbn_metakey( $posttype, $fallback = FALSE )
	{
		if ( $this->posttype_woocommerce( $posttype ) )
			return WordPress\WooCommerce::GTIN_METAKEY;

		if ( ! $this->posttype_supported( $posttype ) )
			return $fallback;

		if ( $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return $metakey;

		return $fallback;
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
		if ( $this->posttype_supported( $posttype ) || $this->posttype_woocommerce( $posttype ) )
			return Services\PostTypeFields::getPostMetaKey( 'bibliographic', 'meta' ) ?: $default;

		return $default;
	}

	public function national_library_default_posttype_isbn_metakey( $default, $posttype )
	{
		return $this->_get_main_isbn_metakey( $posttype ) ?: $default;
	}

	public function datacodes_default_posttype_barcode_metakey( $default, $posttype )
	{
		return $this->_get_main_isbn_metakey( $posttype ) ?: $default;
	}

	public function datacodes_default_posttype_barcode_type( $default, $posttype, $types )
	{
		if ( $this->posttype_supported( $posttype ) || $this->posttype_woocommerce( $posttype ) )
			return ModuleHelper::BARCODE;

		return $default;
	}

	public function identified_identifier_notfound( $type, $sanitized, $supported )
	{
		if ( 'isbn' !== $type )
			return;

		if ( ! $posttype = $this->get_setting( 'default_posttype' ) )
			return;

		// if ( ! Core\ISBN::validate( $sanitized ) )
		// 	return;

		if ( ! WordPress\PostType::can( $posttype, 'create_posts' ) )
			return;

		if ( ! $archive = WordPress\PostType::link( $posttype ) )
			return;

		$metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) ?: 'isbn';

		WordPress\Redirect::doWP( add_query_arg( [
			'newpost' => '',
			$metakey  => $sanitized,
		], $archive ), 307 );
	}

	public function template_posttype_addnew_extra( $extra, $posttype, $title, $module )
	{
		if ( ! $isbn = self::req( 'isbn' ) )
			return $extra;

		if ( ! $metakey = Services\PostTypeFields::getPostMetaKey( 'isbn', 'meta' ) )
			return $extra;

		$extra[$metakey] = $isbn;

		return $extra;
	}

	public function identified_default_posttype_identifier_metakey( $default, $posttype )
	{
		return $this->_get_main_isbn_metakey( $posttype ) ?: $default;
	}

	public function identified_default_posttype_identifier_type( $default, $posttype )
	{
		if ( $this->posttype_supported( $posttype ) )
			return 'isbn';

		if ( $this->posttype_woocommerce( $posttype ) )
			return 'gtin';

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

	public function posts_search_append_meta_frontend( $meta, $search, $posttypes )
	{
		if ( empty( $posttypes ) )
			return $meta;

		if ( ! $discovery = Core\ISBN::discovery( $search ) )
			return $meta; // criteria is not an ISBN

		if ( 'any' === $posttypes )
			$posttypes = $this->posttypes();

		$fields = [
			'isbn',
			'isbn2',
			'isbn3',
			'isbn4',
			'isbn5',
		];

		foreach ( (array) $posttypes as $posttype ) {

			if ( ! $this->posttype_woocommerce( $posttype )
				&& ! $this->posttype_supported( $posttype ) )
				continue;

			if ( ! WordPress\PostType::viewable( $posttype ) )
				continue;

			foreach ( $fields as $field )
				if ( $metakey = Services\PostTypeFields::getPostMetaKey( $field, 'meta', FALSE ) )
					$meta[] = [ $metakey, $discovery ];
		}

		return $meta;
	}

	public function static_covers_default_posttype_reference_metakey( $default, $posttype )
	{
		return $this->_get_main_isbn_metakey( $posttype ) ?: $default;
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

	public function imports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'imports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'imports', $sub );

				if ( ! ModuleSettings::handleImport_from_book_module() )
					WordPress\Redirect::doReferer( 'huh' );
			}
		}
	}

	protected function render_imports_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'ISBN Imports', 'Header', 'geditorial-isbn' ) );
		$available = FALSE;

		if ( ModuleSettings::renderCard_import_from_book_module() )
			$available = TRUE;

		if ( ! $available )
			gEditorial\Info::renderNoImportsAvailable();

		echo '</div>';
	}

	// NOTE: `isbn` field type will be handled by the service.
	public function prep_meta_row_module( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		switch ( $field_key ) {

			case 'bibliographic':

				if ( ! Core\Validation::isBibliographic( $raw ?: $value ) )
					return sprintf( '<span class="-biblio %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
						'-not-valid',
						$raw ?: $value,
						$raw ?: $value
					);

				return Core\HTML::tag( 'a', [
					'href'   => sprintf( 'https://opac.nlai.ir/opac-prod/bibliographic/%s', $raw ?: $value ),
					'title'  => _x( 'See the page about this on National Library website.', 'Field Title Attr', 'geditorial-isbn' ),
					'class'  => '-is-valid',
					'target' => '_blank',
				], Core\Number::localize( $raw ?: $value ) );
		}

		return $value;
	}

	// @REF: `Template::getMetaField()`
	public function meta_field( $meta, $field, $post, $args, $raw, $field_args, $context )
	{
		return $this->prep_meta_row_module( $meta, $field, $field_args, $raw );
	}

	// Makes sure all ISBN initiated on ISBN-13 format.
	public function meta_initial_isbn( $meta, $field, $post, $module )
	{
		return $meta ? Core\ISBN::convertToISBN13( $meta ) : $meta;
	}
}

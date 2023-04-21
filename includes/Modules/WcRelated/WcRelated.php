<?php namespace geminorum\gEditorial\Modules\WcRelated;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\WooCommerce;

class WcRelated extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_related',
			'title'    => _x( 'WC Related', 'Modules: WC Related', 'geditorial' ),
			'desc'     => _x( 'Related Product Enhancements for WooCommerce', 'Modules: WC Related', 'geditorial' ),
			'icon'     => 'tagcloud',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckWooCommerce(),
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'hide_outofstock_related',
					'title'       => _x( 'Hide Out-of-Stock', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Modifies the visibility of out-of-stock items on related products. Applicable only if out-of-stock items are not generally hidden.', 'Setting Description', 'geditorial-wc-related' ),
					'default'     => '1',
				],
				[
					'field'       => 'not_related_by_category',
					'type'        => 'disabled',
					'title'       => _x( 'Releated by Categories', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Sets products are related by product category.', 'Setting Description', 'geditorial-wc-related' ),
				],
				[
					'field'       => 'exclude_product_cats',
					'type'        => 'text',
					'title'       => _x( 'Exclude Categories', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Strips category terms form list of releated by. Leave empty to disable.', 'Setting Description', 'geditorial-wc-related' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'not_related_by_tag',
					'type'        => 'disabled',
					'title'       => _x( 'Releated by Tags', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Sets products are related by product tag.', 'Setting Description', 'geditorial-wc-related' ),
				],
				[
					'field'       => 'exclude_product_tags',
					'type'        => 'text',
					'title'       => _x( 'Exclude Tags', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Strips tag terms form list of releated by. Leave empty to disable.', 'Setting Description', 'geditorial-wc-related' ),
					'field_class' => [ 'regular-text', 'code-text' ],
				],
				[
					'field'       => 'exclude_default_terms',
					'title'       => _x( 'Exclude Default Terms', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Strips default term form list of releated by.', 'Setting Description', 'geditorial-wc-related' ),
				],
				[
					'field'       => 'related_force_display',
					'title'       => _x( 'Force Display Releated', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Sets all products are related.', 'Setting Description', 'geditorial-wc-related' ),
				],
			],
			'_misc' => [
				[
					'field'       => 'related_on_tabs',
					'title'       => _x( 'Related on Tabs', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Displays Upsells and Related products on front-end product tabs.', 'Setting Description', 'geditorial-wc-related' ),
				],
			],
			'_custom' => [
				[
					'field'       => 'hide_outofstock_attribute',
					'title'       => _x( 'Hide Out-of-Stock', 'Setting Title', 'geditorial-wc-related' ),
					'description' => _x( 'Enforces the visibility of out-of-stock items on related by attribute products.', 'Setting Description', 'geditorial-wc-related' ),
				],
				[
					'field'  => 'related_by_taxonomy',
					'type'   => 'object',
					'title'  => _x( 'Related by Taxonomy', 'Setting Title', 'geditorial-wc-related' ),
					'values' => [
						[
							'field'       => 'taxonomy',
							'type'        => 'text',
							'title'       => _x( 'Taxonomy', 'Setting Title', 'geditorial-wc-related' ),
							'description' => _x( 'Target taxonomy for related products.', 'Setting Description', 'geditorial-wc-related' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'heading',
							'type'        => 'text',
							'title'       => _x( 'Heading', 'Setting Title', 'geditorial-wc-related' ),
							'description' => _x( 'Template for related products heading.', 'Setting Description', 'geditorial-wc-related' ),
						],
					],
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'not_related_by_category' ) )
			$this->filter_false( 'woocommerce_product_related_posts_relate_by_category', 12 );

		if ( $this->get_setting( 'not_related_by_tag' ) )
			$this->filter_false( 'woocommerce_product_related_posts_relate_by_tag', 12 );

		if ( $this->get_setting( 'related_force_display' ) )
			$this->filter_true( 'woocommerce_product_related_posts_force_display', 12 );

		if ( $this->get_setting( 'exclude_default_terms' ) || $this->get_setting( 'exclude_product_cats' ) )
			$this->filter( 'get_related_product_cat_terms', 2, 12, FALSE, 'woocommerce' );

		// TODO: support for storefront: `storefront_single_product_pagination_excluded_terms`

		if ( $this->get_setting( 'exclude_default_terms' ) || $this->get_setting( 'exclude_product_tags' ) )
			$this->filter( 'get_related_product_tag_terms', 2, 12, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'related_on_tabs' ) )
			$this->filter( 'product_tabs', 1, 10, FALSE, 'woocommerce' );

		// if not on tabs
		else if ( ! empty( $this->get_setting( 'related_by_taxonomy' ) ) ) {

			// TODO: support custom priority

			if ( WooCommerce::isActiveWoodMart() )
				$this->action( 'woocommerce_after_sidebar', 0, 18, FALSE, 'woodmart' );

			else
				$this->action( 'after_single_product_summary', 0, 18, FALSE, 'woocommerce' );
		}

		if ( $this->get_setting( 'hide_outofstock_related', TRUE ) )
			$this->_apply_hide_out_of_stock();
	}

	// the related output is on `20`
	private function _apply_hide_out_of_stock( $priority = 20 )
	{
		// bail if already hidden
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) )
			return;

		$before = function() { add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', [ $this, '_return_string_yes' ] ); };
		$after  = function() { remove_filter( 'pre_option_woocommerce_hide_out_of_stock_items', [ $this, '_return_string_yes' ] ); };

		if ( WooCommerce::isActiveWoodMart() ) {

			add_action( 'woodmart_woocommerce_after_sidebar', $before, ( $priority - 1 ) );
			add_action( 'woodmart_woocommerce_after_sidebar', $after, ( $priority + 1 ) );

		} else {

			add_action( 'woocommerce_after_single_product_summary', $before, ( $priority - 1 ) );
			add_action( 'woocommerce_after_single_product_summary', $after, ( $priority + 1 ) );
		}
	}

	public function get_related_product_cat_terms( $term_ids, $product_id )
	{
		if ( empty( $term_ids ) )
			return $term_ids;

		$excludes = [];

		if ( $this->get_setting( 'exclude_default_terms' ) )
			$excludes[] = Taxonomy::getDefaultTermID( 'product_cat' );

		if ( $list = $this->get_setting( 'exclude_product_cats' ) )
			$excludes = array_merge( $excludes, Strings::getSeparated( $list ) );

		if ( ! $excludes = Arraay::prepNumeral( $excludes ) )
			return $term_ids;

		return array_diff( $term_ids, $excludes );
	}

	public function get_related_product_tag_terms( $term_ids, $product_id )
	{
		if ( empty( $term_ids ) )
			return $term_ids;

		$excludes = [];

		if ( $this->get_setting( 'exclude_default_terms' ) )
			$excludes[] = Taxonomy::getDefaultTermID( 'product_tag' );

		if ( $list = $this->get_setting( 'exclude_product_tags' ) )
			$excludes = array_merge( $excludes, Strings::getSeparated( $list ) );

		if ( ! $excludes = Arraay::prepNumeral( $excludes ) )
			return $term_ids;

		return array_diff( $term_ids, $excludes );
	}

	// @REF: https://gist.github.com/bekarice/0220adfc3e6ba8d0388714eabbb00adc
	public function product_tabs( $tabs )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return $tabs;

		if ( $product->get_upsell_ids()
			|| $product->get_cross_sell_ids()
			|| apply_filters( 'woocommerce_product_related_posts_force_display', FALSE, $product->get_id() ) ) {

			$tabs['related'] = [
				'title'    => $this->filters( 'tab_related_title', __( 'Related products', 'woocommerce' ), $product ), // TODO: use custom title
				'callback' => [ $this, 'product_tabs_related_callback' ],
				'priority' => 25,
			];

			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

			remove_action( 'woodmart_woocommerce_after_sidebar', 'woocommerce_upsell_display', 10 );
			remove_action( 'woodmart_woocommerce_after_sidebar', 'woocommerce_output_related_products', 20 );
		}

		return $tabs;
	}

	public function product_tabs_related_callback()
	{
		if ( function_exists( 'woocommerce_upsell_display' ) )
			woocommerce_upsell_display();

		if ( ! empty( $this->get_setting( 'related_by_taxonomy' ) ) )
			$this->after_single_product_summary();

		if ( function_exists( 'woocommerce_output_related_products' ) )
			woocommerce_output_related_products();
	}

	// @REF: `woodmart_woocommerce_after_sidebar`
	public function woocommerce_after_sidebar()
	{
		$this->after_single_product_summary();
	}

	// @REF: `woocommerce_after_single_product_summary`
	public function after_single_product_summary()
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		$args = self::atts( [
			'posts_per_page' => 4,
			'columns'        => 4,
			'orderby'        => 'rand',
			'order'          => 'desc',
		], apply_filters( 'woocommerce_output_related_products_args', [] ) );

		$product_id = $product->get_id();
		$excludes   = $product->get_upsell_ids();
		$columns    = apply_filters( 'woocommerce_related_products_columns', $args['columns'] );
		$outofstock = $this->get_setting( 'hide_outofstock_attribute' ) ? '_return_string_yes' : '_return_string_no';

		add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', [ $this, $outofstock ] );

		foreach ( $this->get_setting( 'related_by_taxonomy', [] ) as $index => $row ) {

			if ( empty( $row['taxonomy'] ) || ! taxonomy_exists( $row['taxonomy'] ) )
				continue;

			$related  = $this->get_related_products( $product_id, $row['taxonomy'], $args['posts_per_page'], $excludes );
			$products = array_filter( array_map( 'wc_get_product', $related ), 'wc_products_array_filter_visible' );
			$excludes = array_merge( $excludes, $related );

			$args['related_products'] = wc_products_array_orderby( $products, $args['orderby'], $args['order'] );

			wc_set_loop_prop( 'name', 'related_by_'.$row['taxonomy'] );
			wc_set_loop_prop( 'columns', $columns );

			$name = Text::start( $row['taxonomy'], 'pa_' )
				? wc_attribute_label( $row['taxonomy'], $product )
				: Taxonomy::object( $row['taxonomy'] )->labels->name;

			$heading  = trim( sprintf( $row['heading'], $name ) );
			$callback = static function() use ( $heading ) { return $heading; };

			add_filter( 'woocommerce_product_related_products_heading', $callback );
			wc_get_template( 'single-product/related.php', $args );
			remove_filter( 'woocommerce_product_related_products_heading', $callback );
		}

		remove_filter( 'pre_option_woocommerce_hide_out_of_stock_items', [ $this, $outofstock ] );
	}

	// @REF: `wc_get_related_products()`
	public function get_related_products( $product_id, $taxonomy, $limit = 5, $exclude_ids = [] )
	{
		$product_id     = absint( $product_id );
		$limit          = $limit >= -1 ? $limit : 5;
		$exclude_ids    = array_merge( [ 0, $product_id ], $exclude_ids );
		$transient_name = 'wc_related_'.$product_id;
		$query_args     = http_build_query( [
			'taxonomy'    => $taxonomy,
			'limit'       => $limit,
			'exclude_ids' => $exclude_ids,
		] );

		$transient        = get_transient( $transient_name );
		$related_products = $transient && isset( $transient[$query_args] ) ? $transient[$query_args] : FALSE;

		// we want to query related posts if they are not cached, or we don't have enough
		if ( FALSE === $related_products || count( $related_products ) < $limit ) {

			$related_products   = [];
			$attribute_terms = $this->filters( 'product_attribute_terms', wc_get_product_term_ids( $product_id, $taxonomy ), $product_id, $taxonomy );

			if ( ! empty( $attribute_terms ) ) {
				$data_store    = \WC_Data_Store::load( 'product' );
				$related_products = $data_store->get_related_products( $attribute_terms, [], $exclude_ids, $limit + 10, $product_id );
			}

			if ( $transient )
				$transient[$query_args] = $related_products;
			else
				$transient = [ $query_args => $related_products ];

			set_transient( $transient_name, $transient, DAY_IN_SECONDS );
		}

		$related_products = apply_filters(
			'woocommerce_related_products',
			$related_products,
			$product_id,
			[
				'taxonomy'     => $taxonomy,
				'limit'        => $limit,
				'excluded_ids' => $exclude_ids,
			]
		);

		if ( apply_filters( 'woocommerce_product_related_posts_shuffle', TRUE ) )
			shuffle( $related_products );

		return array_slice( $related_products, 0, $limit );
	}
}

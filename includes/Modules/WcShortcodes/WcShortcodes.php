<?php namespace geminorum\gEditorial\Modules\WcShortcodes;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcShortcodes extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_shortcodes',
			'title'    => _x( 'WC Shortcodes', 'Modules: WC Shortcodes', 'geditorial-admin' ),
			'desc'     => _x( 'Shortcode Enhancements for WooCommerce', 'Modules: WC Shortcodes', 'geditorial-admin' ),
			'icon'     => 'media-code',
			'access'   => 'beta',
			'disabled' => gEditorial\Helper::moduleCheckWooCommerce(),
			'keywords' => [
				'shortcode',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'          => 'shortcodes',
					'title'          => _x( 'Shortcodes', 'Setting Title', 'geditorial-wc-shortcodes' ),
					'description'    => _x( 'Enables the use of the selected short-codes.', 'Setting Description', 'geditorial-wc-shortcodes' ),
					'type'           => 'checkboxes-values',
					'values'         => $this->_list_shortcodes(),
					'template_value' => '[%s]',
				],
			],
		];
	}

	private function _list_shortcodes()
	{
		return [
			'wc-stock-status'       => _x( 'Stock Status', 'Shortcode Name', 'geditorial-wc-shortcodes' ),
			'wc-scheduled-on-sales' => _x( 'Scheduled On-sales', 'Shortcode Name', 'geditorial-wc-shortcodes' ),
		];
	}

	protected function get_global_constants()
	{
		return [
			'wc_stock_status_shortcode'       => 'wc-stock-status',
			'wc_scheduled_on_sales_shortcode' => 'wc-scheduled-on-sales',
		];
	}

	public function init()
	{
		parent::init();

		foreach ( $this->get_setting( 'shortcodes', [] ) as $shortcode )
			$this->register_shortcode( sprintf( '%s_shortcode', $this->sanitize_hook( $shortcode ) ), NULL, TRUE );

		if ( is_admin() )
			return;

		$this->filter( 'shortcode_products_query', 3, 12, FALSE, 'woocommerce' );
		$this->filter( 'shortcode_atts_products', 4, 12, 'woocommerce' );
	}

	public function shortcode_products_query( $query_args, $shortcode_atts, $shortcode_type )
	{
		foreach ( WordPress\WooCommerce::getProductTaxonomies( FALSE ) as $supported ) {

			if ( ! $query = WordPress\Taxonomy::queryVar( $supported ) )
				continue;

			if ( empty( $shortcode_atts[$query] ) )
				continue;

			$query_args['tax_query'][] = [
				'taxonomy' => $supported,
				'terms'    => Core\Arraay::prepNumeral( $shortcode_atts[$query] ),
			];
		}

		return $query_args;
	}

	public function shortcode_atts_products_woocommerce( $out, $pairs, $atts, $shortcode )
	{
		foreach ( WordPress\WooCommerce::getProductTaxonomies( FALSE ) as $supported ) {

			if ( ! $taxonomy = WordPress\Taxonomy::object( $supported ) )
				continue;

			if ( ! $query = WordPress\Taxonomy::queryVar( $taxonomy ) )
				continue;

			if ( ! empty( $atts[$query] ) )
				$out[$supported] = $atts[$query];
		}

		return $out;
	}

	// @REF: https://gist.github.com/hamidrezayazdani/c0e2f0be5142ee12691dca0b09337114
	public function wc_stock_status_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'context' => NULL,
			'wrap'    => TRUE,
			'class'   => '',
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $this->constant( 'wc_stock_status_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $product = wc_get_product( $args['id'] ) )
			return $content;

		$html = wc_get_stock_html( $product );

		return gEditorial\ShortCode::wrap( $html, $this->constant( 'wc_stock_status_shortcode' ), $args );
	}

	/**
	 * WooCommerce scheduled on-sale products list short-code.
	 *
	 * @author Hamid Reza Yazdani (yazdaniwp)
	 * @source https://github.com/hamidrezayazdani/wc-scheduled-onsales-shortcode
	 *
	 * @param array $atts
	 * @param string $content
	 * @param string $tag
	 * @return string
	 */
	public function wc_scheduled_on_sales_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'limit'   => 12,      // TODO: get default from WC
			'columns' => 3,       // TODO: get default from WC
			'empty'   => FALSE,   // `NULL` for default text
			'paged'   => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'class'   => '',
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $this->constant( 'wc_scheduled_on_sales_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( is_null( $args['paged'] ) )
			$args['paged'] = get_query_var( 'paged' ) ?: 1;

		$time  = time();
		$query = [
			'post_type'      => [ 'product', 'product_variation' ],
			'post__in'       => array_merge( [ 0 ], wc_get_product_ids_on_sale() ),
			'paged'          => $args['paged'],
			'posts_per_page' => $args['limit'],
			'meta_query'     => [

				// // If you set on sale start date uncomment this section
				// 'relation' => 'AND',
				// [
				// 	'key'     => '_sale_price_dates_from',
				// 	'value'   => $time,
				// 	'compare' => '<',
				// ],

				[
					'key'     => '_sale_price_dates_to',
					'value'   => $time,
					'compare' => '>',
				],
			],
		];

		$loop = new \WP_Query( $query );
		$html = '';

		if ( $loop->have_posts() ) {

			$html.= '<ul class ="products columns-'.$args['columns'].'">';

			while ( $loop->have_posts() ) {

				$loop->the_post();

				$html.= self::buffer( 'wc_get_template_part', [ 'content', 'product' ] );
			}

			$html.= '</ul>';

			if ( function_exists( 'pagination' ) )
				$html.= self::buffer( 'pagination', [ $loop->max_num_pages ] );

			wp_reset_postdata();

		} else {

			$html = $args['empty'] ?? _x( 'There are no products available!', 'Message', 'geditorial-wc-shortcodes' );
		}

		return gEditorial\ShortCode::wrap( $html ?: NULL, $this->constant( 'wc_scheduled_on_sales_shortcode' ), $args );
	}
}

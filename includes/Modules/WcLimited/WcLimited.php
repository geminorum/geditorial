<?php namespace geminorum\gEditorial\Modules\WcLimited;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;

class WcLimited extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_limited',
			'title'    => _x( 'WC Limited', 'Modules: WC Limited', 'geditorial' ),
			'desc'     => _x( 'Product Purchasing Limits for WooCommerce', 'Modules: WC Limited', 'geditorial' ),
			'icon'     => 'store',
			'disabled' => Helper::moduleCheckWooCommerce(),
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'once_purchased',
					'title'       => _x( 'Purchased Once', 'Setting Title', 'geditorial-wc-limited' ),
					'description' => _x( 'Denies further sales if user has already purchased a product.', 'Setting Description', 'geditorial-wc-limited' ),
				],
				[
					'field'        => 'limited_terms',
					'type'         => 'checkbox-panel',
					'title'        => _x( 'Limited Terms', 'Setting Title', 'geditorial-wc-limited' ),
					'description'  => _x( 'Products on the selected categories will be limited to only one per purchase.', 'Setting Description', 'geditorial-wc-limited' ),
					'values'       => Taxonomy::listTerms( 'product_cat' ),
				],
				[
					'field'       => 'limited_notice',
					'type'        => 'text',
					'title'       => _x( 'Limited Notice', 'Setting Title', 'geditorial-wc-limited' ),
					'description' => _x( 'Customized string to notice customer that product is limited to one per purchase.', 'Setting Description', 'geditorial-wc-limited' ),
					'default'     => _x( 'This product cannot be purchased with other products. Please, empty your cart first and then add it again.', 'Setting Default', 'geditorial-wc-limited' ),
					'field_class'  => [ 'large-text' ],
				],
				[
					'field'       => 'limited_already',
					'type'        => 'text',
					'title'       => _x( 'Limited Already', 'Setting Title', 'geditorial-wc-limited' ),
					'description' => _x( 'Customized string to notice customer that cart has already a product that is limited to one per purchase.', 'Setting Description', 'geditorial-wc-limited' ),
					'default'     => _x( 'You can only purchase one product at a time from limited categories.', 'Setting Default', 'geditorial-wc-limited' ),
					'field_class'  => [ 'large-text' ],
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		if ( ! empty( $this->get_setting( 'limited_terms' ) ) )
			$this->filter( 'woocommerce_add_to_cart_validation', 3, 8 );

		if ( $this->get_setting( 'once_purchased' ) )
			$this->filter( 'woocommerce_is_purchasable', 2, 999 );
	}

	public function woocommerce_add_to_cart_validation( $passed, $product_id, $quantity )
	{
		// already empty
		if ( WC()->cart->is_empty() )
			return $passed;

		$terms = $this->get_setting( 'limited_terms', [] );

		// new item is limited
		foreach ( $terms as $term )
			if ( has_term( (int) $term, 'product_cat', $product_id ) )
				return wc_add_notice( $this->get_setting( 'limited_notice',
					_x( 'This product cannot be purchased with other products. Please, empty your cart first and then add it again.', 'Setting Default', 'geditorial-wc-limited' ) ), 'error' );

		// already limited
		foreach ( WC()->cart->get_cart() as $item ) {

			if ( empty( $item['product_id'] ) )
				continue;

			foreach ( $terms as $term )
				if ( has_term( (int) $term, 'product_cat', (int) $item['product_id'] ) )
					return wc_add_notice( $this->get_setting( 'limited_already',
						_x( 'You can only purchase one product at a time from limited categories.', 'Setting Default', 'geditorial-wc-limited' ) ), 'error' );
		}

		return $passed;
	}

	// @REF: https://www.businessbloomer.com/woocommerce-hide-add-to-cart-if-already-purchased/
	public function woocommerce_is_purchasable( $is_purchasable, $product )
	{
		// not logged in
		if ( ! $user_id = get_current_user_id() )
			return $is_purchasable;

		if ( wc_customer_bought_product( '', $user_id, $product->get_id() ) )
			return FALSE;

		return $is_purchasable;
	}
}

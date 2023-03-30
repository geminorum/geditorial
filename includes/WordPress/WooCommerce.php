<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class WooCommerce extends Core\Base
{

	const PLUGIN = 'woocommerce/woocommerce.php';

	public static function isActive()
	{
		return Core\WordPress::isPluginActive( static::PLUGIN );
	}

	public static function isActiveWoodMart()
	{
		if ( defined( 'WOODMART_CORE_VERSION' ) )
			return TRUE;

		// fallback/unnesseary db call
		// if ( get_option( 'woodmart_is_activated' ) )
		// 	return TRUE;

		return FALSE;
	}

	public static function getProductPosttype()
	{
		return 'product';
	}

	public static function getProductCategoryTaxonomy()
	{
		return 'product_cat';
	}

	public static function getOrderStatuses()
	{
		$statuses = [];

		foreach ( wc_get_order_statuses() as $status => $name )
			$statuses[( 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status )] = $name;

		return $statuses;
	}

	// @REF: https://wordpress.stackexchange.com/a/334608/93391
	public static function getBaseAddress()
	{
		$country = WC()->countries->get_base_country();

		return [
			'address'      => WC()->countries->get_base_address(),
			'address-2'    => WC()->countries->get_base_address_2(),
			'postcode'     => WC()->countries->get_base_postcode(),
			'city'         => WC()->countries->get_base_city(),
			'state'        => WC()->countries->get_base_state(),
			'country'      => WC()->countries->countries[$country],
			'country-code' => $country,
			'mail'         => get_option( 'address-public-mail' ),
		];
	}

	/**
	 * Change product type
	 * If used an invalid type a WC_Product_Simple instance will be returned.
	 * NOTE: same as `wc_get_product_object()` with save trigger
	 * @source https://stackoverflow.com/a/62761862
	 *
	 * @param int     $product_id - The product id.
	 * @param string  $type       - The new product type
	 */
	public static function changeProductType( $product_id, $type )
	{
 		// Get the correct product classname from the new product type
		$classname = \WC_Product_Factory::get_product_classname( $product_id, $type );

		// Get the new product object from the correct classname
		$product = new $classname( $product_id );

		// Save product to database and sync caches
		$product->save();

		return $product;
	}
}

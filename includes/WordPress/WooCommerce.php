<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class WooCommerce extends Core\Base
{

	public static function isActive()
	{
		return Core\WordPress::isPluginActive( 'woocommerce/woocommerce.php' );
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
}

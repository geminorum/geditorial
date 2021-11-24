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
}

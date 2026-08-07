<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait WooCommerceAttributes
{
	protected $wc_has_attributes = FALSE;

	protected function wc_attributes__check_for_additional_tab()
	{
		return add_filter( 'woocommerce_product_tabs',
			function ( $tabs ) {

				if ( ! $this->wc_has_attributes )
					return $tabs;

				// already has attributes
				if ( array_key_exists( 'additional_information', $tabs ) )
					return $tabs;

				$tabs['additional_information'] = [
					'title'    => __( 'Additional information', 'woocommerce' ),
					'priority' => 20,
					'callback' => 'woocommerce_product_additional_information_tab',
				];

				return $tabs;
			}, 22, 1 ); // must below `99`
	}
}

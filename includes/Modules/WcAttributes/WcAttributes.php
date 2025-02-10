<?php namespace geminorum\gEditorial\Modules\WcAttributes;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

class WcAttributes extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_attributes',
			'title'    => _x( 'WC Attributes', 'Modules: WC Attributes', 'geditorial-admin' ),
			'desc'     => _x( 'Product Attribute Enhancements', 'Modules: WC Attributes', 'geditorial-admin' ),
			'icon'     => 'store',
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckWooCommerce(),
			'keywords' => [
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'localize_join_values',
					'title'       => _x( 'Localize Join', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Tries to join attribute values with a localized separator.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		// $this->filter( 'display_product_attributes', 2, 8, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'localize_join_values' ) )
			$this->filter( 'attribute', 3, 20, FALSE, 'woocommerce' );
	}

	public function display_product_attributes( $attributes, $product )
	{
		$before = $after = [];
		return $before + $attributes + $after;
	}

	public function attribute( $filtered, $attribute, $values )
	{
		return wpautop( wptexturize( WordPress\Strings::getJoined( $values ) ) );
	}
}

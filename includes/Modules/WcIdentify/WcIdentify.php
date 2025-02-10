<?php namespace geminorum\gEditorial\Modules\WcIdentify;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\WordPress;

class WcIdentify extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_identify',
			'title'    => _x( 'WC Identify', 'Modules: WC Identify', 'geditorial-admin' ),
			'desc'     => _x( 'Product Identification Enhancements', 'Modules: WC Identify', 'geditorial-admin' ),
			'icon'     => 'store',
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckWooCommerce(),
			'keywords' => [
				'gtin',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'gtin_display',
					'title'       => _x( 'Display GTIN', 'Setting Title', 'geditorial-wc-identify' ),
					'description' => _x( 'Prepends the global unique id on product attributes table.', 'Setting Description', 'geditorial-wc-identify' ),
				],
				[
					'field'       => 'gtin_lookup',
					'title'       => _x( 'GTIN lookup', 'Setting Title', 'geditorial-wc-identify' ),
					'description' => _x( 'Makes the value for the global unique id clickable on product attributes table.', 'Setting Description', 'geditorial-wc-identify' ),
				],
				[
					'field'       => 'gtin_label',
					'type'        => 'text',
					'title'       => _x( 'GTIN Label', 'Setting Title', 'geditorial-wc-identify' ),
					'description' => _x( 'Overrides the default label for the global unique id on product attributes table.', 'Setting Description', 'geditorial-wc-identify' ),
					'placeholder' => _x( 'GTIN', 'Attribute Label', 'geditorial-wc-identify' ),
					'field_class' => [ 'medium-text' ],
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->filter( 'display_product_attributes', 2, 8, FALSE, 'woocommerce' );
	}

	public function display_product_attributes( $attributes, $product )
	{
		$before = $after = [];

		if ( $this->get_setting( 'gtin_display' ) ) {

			if ( $gtin = $product->get_global_unique_id() )
				$before[$this->classs( 'gtin' )] = [
					'label' => $this->get_setting_fallback( 'gtin_label', _x( 'GTIN', 'Attribute Label', 'geditorial-wc-identify' ) ),
					'value' => $this->get_setting( 'gtin_lookup' ) ? Info::lookupISBN( $gtin ) : Core\ISBN::prep( $gtin, TRUE ),
				];
		}

		return $before + $attributes + $after;
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools', 'per_page' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Product Identification Tools', 'Header', 'geditorial-wc-identify' ) );
		$available = FALSE;

		if ( ModuleSettings::renderCard_tool_migrate_gtin() )
			$available = TRUE;

		if ( ! $available )
			Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( $this->_do_tool_migrate_gtin( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_tool_migrate_gtin( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_MIGRATE_GTIN ) )
			return FALSE;

		$this->raise_resources();

		return ModuleSettings::handleTool_migrate_gtin(
			WordPress\WooCommerce::getProductPosttype(),
			$this->get_sub_limit_option( $sub )
		);
	}
}

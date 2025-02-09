<?php namespace geminorum\gEditorial\Modules\WcAttributes;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
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
				'gtin',
				'attribute',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'localize_join_attributes',
					'title'       => _x( 'Localize Join', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Tries to join attributes with a localized separator.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
			],
			'_gtin' => [
				[
					'field'       => 'gtin_display',
					'title'       => _x( 'Display GTIN', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Prepends the global unique id on product attributes table.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
				[
					'field'       => 'gtin_lookup',
					'title'       => _x( 'GTIN lookup', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Links the default label for the global unique id on product attributes table.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
				[
					'field'       => 'gtin_label',
					'type'        => 'text',
					'title'       => _x( 'GTIN Label', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Overrides the default label for the global unique id on product attributes table.', 'Setting Description', 'geditorial-wc-attributes' ),
					'placeholder' => _x( 'GTIN', 'Field Title', 'geditorial-wc-attributes' ),
				],
			],
		];
	}

	protected function settings_section_titles( $suffix )
	{
		switch ( $suffix ) {

			case '_gtin': return [ _x( 'Global Unique ID', 'Setting Section Title', 'geditorial-wc-attributes' ),
				_x( 'Preferences about the Global Trade Item Number.', 'Setting Section Description', 'geditorial-wc-attributes' ) ];
		}

		return FALSE;
	}

	public function init()
	{
		parent::init();

		$this->filter( 'display_product_attributes', 2, 8, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'localize_join_attributes' ) )
			$this->filter( 'attribute', 3, 20, FALSE, 'woocommerce' );
	}

	public function display_product_attributes( $attributes, $product )
	{
		$before = $after = [];

		if ( $this->get_setting( 'gtin_display' ) ) {

			if ( $gtin = $product->get_global_unique_id() )
				$before[$this->classs( 'gtin' )] = [
					'label' => $this->get_setting_fallback( 'gtin_label', _x( 'GTIN', 'Field Title', 'geditorial-wc-attributes' ) ),
					'value' => $this->get_setting( 'gtin_lookup' ) ? Info::lookupISBN( $gtin ) : Core\ISBN::prep( $gtin, TRUE ),
				];
		}

		return $before + $attributes + $after;
	}

	public function attribute( $filtered, $attribute, $values )
	{
		return wpautop( wptexturize( WordPress\Strings::getJoined( $values ) ) );
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools', 'per_page' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Product Attribute Tools', 'Header', 'geditorial-wc-attributes' ) );
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

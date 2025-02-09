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
				'attribute',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_frontend' => [
				[
					'field'       => 'localize_join_attributes',
					'title'       => _x( 'Localize Join', 'Setting Title', 'geditorial-wc-attributes' ),
					'description' => _x( 'Tries to join attributes with a localized separator.', 'Setting Description', 'geditorial-wc-attributes' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'localize_join_attributes' ) )
			$this->filter( 'attribute', 3, 20, FALSE, 'woocommerce' );
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

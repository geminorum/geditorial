<?php namespace geminorum\gEditorial\Modules\WcIdentify;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class WcIdentify extends gEditorial\Module
{

	/**
	 * The most common product identifiers are GTINs and MPN:
	 *
	 * - GTIN8 / EAN-8 – a code used for items that are too small to fit the usual 12-14 digits.
	 * - GTIN12 / UPC  and GTIN13 / EAN – the most common codes used in North America (UPC) and outside of North America  (EAN).
	 * - GTIN14 / ITF-14: a code used for packaged products that contain multiple individual items, such as a pack of canned sodas.
	 * - ISBN: stands for “International Standard Book Number” and it is used, of course, for books.
	 * - MPN: stands for “Manufacturer part number”. These numbers are typically found on machines and hardware that contain different parts.
	 *
	 * @source https://yoast.com/help/how-to-add-product-identifiers-with-woocommerce-seo/
	 * @see https://support.google.com/merchants/answer/160161?hl=en
	 */

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
				[
					'field'       => 'gtin_exemptions',
					'title'       => _x( 'GTIN Exemptions', 'Setting Title', 'geditorial-wc-identify' ),
					'description' => _x( 'Instructs output structured data that a valid identifier for the product doesn\'t exist.', 'Setting Description', 'geditorial-wc-identify' ),
					'after'       => Settings::fieldAfterIcon( 'https://nicolamustone.blog/2023/11/20/how-to-disable-gtin-requirements-for-non-eligible-woocommerce-products/' ),
				],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->filter( 'display_product_attributes', 2, 8, FALSE, 'woocommerce' );
		$this->action_self( 'render_product_gtin', 4 );

		if ( $this->get_setting( 'gtin_exemptions' ) )
			$this->filter( 'structured_data_product', 2, 20, 'exemptions', 'woocommerce' );
	}

	public function render_product_gtin( $product, $before = '', $after = '', $template = NULL )
	{
		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		if ( ! $raw = $product->get_global_unique_id() )
			return;

		$gtin = Core\ISBN::sanitize( $raw );

		$tokens = [
			'raw'    => $raw,
			'gtin'   => $gtin,
			'prep'   => Core\ISBN::prep( $raw, TRUE ),
			'link'   => Info::lookupISBN( $gtin ),
			'label'  => $this->get_setting_fallback( 'gtin_label', _x( 'GTIN', 'Attribute Label', 'geditorial-wc-identify' ) ),
			'notice' => _x( 'Click to Copy', 'Notice', 'geditorial-wc-identify' ),
		];

		echo $before.Core\Text::replaceTokens(
			$template ?? '<span class="gtin_wrapper do-clicktoclip span-copy-link" data-clipboard-text="{{gtin}}" data-value="{{gtin}}" title="{{notice}}">{{label}}&nbsp;<span class="gtin" data-gtin="{{gtin}}">{{{link}}}</span></span>',
			$tokens ).$after;
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

	// @REF: https://nicolamustone.blog/2023/11/20/how-to-disable-gtin-requirements-for-non-eligible-woocommerce-products/
	public function structured_data_product_exemptions( $markup, $product )
	{
		if ( ! $gtin = $product->get_global_unique_id() )
			return $markup;

		if ( ! Core\ISBN::validate( $gtin ) )
			/**
			 * Instructs Woo Commerce to output structured data that indicates
			 * to Google that an identifier for the product doesn’t exist and isn’t necessary.
			 */
			$markup['identifier_exists'] = 'no';

		return $markup;
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

	// https://woocommerce.com/document/google-for-woocommerce/attribute-mapping/gtin/
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

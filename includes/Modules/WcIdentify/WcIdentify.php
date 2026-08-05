<?php namespace geminorum\gEditorial\Modules\WcIdentify;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
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

	public static function module(): array
	{
		return [
			'name'     => 'wc_identify',
			'title'    => _x( 'WC Identify', 'Modules: WC Identify', 'geditorial-admin' ),
			'desc'     => _x( 'Product Identification Enhancements', 'Modules: WC Identify', 'geditorial-admin' ),
			'icon'     => 'store',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
			'keywords' => [
				'gtin',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings(): array
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
			'_misc' => [
				[
					'field'       => 'gtin_display_order_details',
					'title'       => _x( 'Display on Order Details', 'Setting Title', 'geditorial-wc-identify' ),
					'description' => _x( 'Appends the global unique id on order details and preview.', 'Setting Description', 'geditorial-wc-identify' ),
				],
				[
					'field'       => 'gtin_exemptions',
					'title'       => _x( 'GTIN Exemptions', 'Setting Title', 'geditorial-wc-identify' ),
					'description' => _x( 'Instructs output structured data that a valid identifier for the product doesn\'t exist.', 'Setting Description', 'geditorial-wc-identify' ),
					'after'       => gEditorial\Settings::fieldAfterIcon( 'https://nicolamustone.blog/2023/11/20/how-to-disable-gtin-requirements-for-non-eligible-woocommerce-products/' ),
				],
			],
		];
	}

	public function init(): void
	{
		parent::init();

		$this->filter( 'display_product_attributes', 2, 8, FALSE, 'woocommerce' );
		$this->filter( 'search_results_products_ids', 3, 12, FALSE, 'aws' );
		$this->action_self( 'render_product_gtin', 4 );

		if ( $this->get_setting( 'gtin_exemptions' ) )
			$this->filter( 'structured_data_product', 2, 20, 'exemptions', 'woocommerce' );

		if ( ! is_admin() )
			return;

		if ( $this->get_setting( 'gtin_display_order_details' ) ) {
			$this->action( 'after_order_itemmeta', 3, 8, FALSE, 'woocommerce' );
			$this->filter( 'admin_order_preview_get_order_details', 2, 8, FALSE, 'woocommerce' );
		}
	}

	public function importer_init(): void
	{
		$this->filter_module( 'importer', 'source_id', 3 );
		$this->filter_module( 'importer', 'matched', 4 );
		$this->filter_module( 'importer', 'insert', 8, 18 );
	}

	private function _get_gtin_label( ?string $context = NULL )
	{
		return $this->get_setting_fallback( 'gtin_label', _x( 'GTIN', 'Attribute Label', 'geditorial-wc-identify' ) );
	}

	public function render_product_gtin(
		mixed $product,
		string $before = '',
		string $after = '',
		?string $template = NULL,
	): void {

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		if ( ! method_exists( $product, 'get_global_unique_id' ) )
			return;

		if ( ! $raw = $product->get_global_unique_id() )
			return;

		$gtin = Core\ISBN::sanitize( $raw );

		$tokens = [
			'raw'    => $raw,
			'gtin'   => $gtin,
			'prep'   => Core\ISBN::prep( $raw, TRUE ),
			'link'   => Services\Lookup::htmlISBN( $gtin ),
			'label'  => $this->_get_gtin_label( 'action' ),
			'notice' => _x( 'Click to Copy', 'Notice', 'geditorial-wc-identify' ),
		];

		echo $before.Core\Text::replaceTokens(
			$template ?? '<span class="gtin_wrapper do-clicktoclip span-copy-link" data-clipboard-text="{{gtin}}" data-value="{{gtin}}" title="{{notice}}">{{label}}&nbsp;<span class="gtin" data-gtin="{{gtin}}">{{{link}}}</span></span>',
			$tokens ).$after;
	}

	public function display_product_attributes( array $attributes, object $product ): array
	{
		$before = $after = [];

		if ( $this->get_setting( 'gtin_display' ) ) {

			if ( method_exists( $product, 'get_global_unique_id' ) ) {

				if ( $gtin = $product->get_global_unique_id() )
					$before[$this->classs( 'gtin' )] = [
						'label' => $this->_get_gtin_label( 'attributes' ),
						'value' => $this->get_setting( 'gtin_lookup' )
							? Services\Lookup::htmlISBN( $gtin )
							: Core\ISBN::prep( $gtin, TRUE ),
					];
			}
		}

		return $before + $attributes + $after;
	}

	// @FILTER: `aws_search_results_products_ids`
	public function search_results_products_ids( $posts_ids, $s, $data )
	{
		global $wpdb;

		if ( ! $discovery = Core\ISBN::discovery( $s ) )
			return $posts_ids; // criteria is not GTIN

		$posts = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '%s' AND meta_value = '%s'",
			WordPress\WooCommerce::GTIN_METAKEY,
			$discovery
		) );

		return Core\Arraay::prepNumeral( $posts_ids, $posts );
	}

	// @hook `Woocommerce_after_order_itemmeta`
	public function after_order_itemmeta( ?int $item_id, object $item, ?object $product ): void
	{
		if ( ! $item->is_type( 'line_item' ) )
			return;

		// product/variation
		if ( ! $product = $item->get_product() )
			return;

		if ( ! method_exists( $product, 'get_global_unique_id' ) )
			return;

		if ( ! $uniqueid = $product->get_global_unique_id() )
			return;

		echo Core\HTML::wrap( Core\Text::glued( [
			Core\HTML::strong( $this->_get_gtin_label( 'itemmeta' ) ),
			Core\HTML::code( $uniqueid, '-uniqueid', TRUE ),
		], '&nbsp;' ), '-additional-info' );
	}

	// @source https://gist.github.com/shameemreza/d5843f10c29fa46711d2c3cb903046d3
	// @hook `woocommerce_admin_order_preview_get_order_details`
	public function admin_order_preview_get_order_details( array $order_details, object $order ): array
	{
		$list = [];

		foreach ( $order->get_items() as $item_id => $item ) {

			if ( ! $product = $item->get_product() )
				continue;

			if ( ! method_exists( $product, 'get_global_unique_id' ) )
				continue;

			if ( $uniqueid = $product->get_global_unique_id() )
				$list[$item_id] = $uniqueid;
		}

		if ( ! count( $list ) )
			return $order_details;

		$items = $order_details['item_html'];
		$label = $this->_get_gtin_label( 'itemmeta' );

		foreach ( $list as $item_id => $uniqueid ) {

			// Each item row has a unique class `wc-order-preview-table__item--{item_id}`
			// NOTE: the `SKU` div always exists within this specific row.
			$pattern = sprintf(
				'/(<tr[^>]*wc-order-preview-table__item--%s[^>]*>.*?<div class="wc-order-item-sku">.*?<\/div>)/s',
				preg_quote( $item_id, '/' )
			);

			$html = Core\HTML::wrap( Core\Text::glued( [
				Core\HTML::strong( $label ),
				Core\HTML::code( $uniqueid, '-uniqueid', TRUE ),
			], '&nbsp;' ), '-additional-info' );

			// Replace by adding data div after the `SKU` div for this specific item.
			$items = preg_replace( $pattern, '$1'.$html, $items, 1 );
		}

		$order_details['item_html'] = $items;

		return $order_details;
	}

	// @REF: https://nicolamustone.blog/2023/11/20/how-to-disable-gtin-requirements-for-non-eligible-woocommerce-products/
	public function structured_data_product_exemptions( array $markup, object $product ): array
	{
		if ( ! method_exists( $product, 'get_global_unique_id' ) )
			return $markup;

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

	public function importer_source_id( mixed $source_id, string $posttype, mixed $raw ): mixed
	{
		if ( empty( $source_id ) )
			return NULL;

		if ( $posttype !== WordPress\WooCommerce::PRODUCT_POSTTYPE )
			return $source_id;

		return Core\ISBN::discovery( $source_id );
	}

	public function importer_matched( false|int $matched, mixed $source_id, string $posttype, mixed $raw ): false|int
	{
		if ( ! empty( $matched ) )
			return $matched;

		if ( $posttype !== WordPress\WooCommerce::PRODUCT_POSTTYPE )
			return $matched;

		if ( $post_id = WordPress\PostType::getIDbyMeta( WordPress\WooCommerce::GTIN_METAKEY, $source_id, TRUE ) )
			return (int) $post_id;

		return $matched;
	}

	public function importer_insert( array|false $data, array $prepared, array $taxonomies, string $posttype, mixed $source_id, int $attach_id, mixed $raw, bool $override ): array|false
	{
		if ( FALSE === $data )
			return $data; // already aborted!

		if ( ! empty( $data['ID'] ) )
			return $data; // already found!

		if ( $posttype !== WordPress\WooCommerce::PRODUCT_POSTTYPE )
			return $data;

		// store source_id as GTIN
		if ( empty( $data['meta_input'][WordPress\WooCommerce::GTIN_METAKEY] ) )
			$data['meta_input'][WordPress\WooCommerce::GTIN_METAKEY] = $source_id;

		return $data;
	}

	public function tools_settings( string $sub ): void
	{
		$this->check_settings( $sub, 'tools', 'per_page' );
	}

	protected function render_tools_html( string $uri, string $sub, string $action, string $context ): bool
	{
		echo ModuleSettings::toolboxColumnOpen( _x( 'Product Identification Tools', 'Header', 'geditorial-wc-identify' ) );

			$available = FALSE;

			if ( ModuleSettings::renderCard_tool_migrate_gtin() )
				$available = TRUE;

			if ( ! $available )
				gEditorial\Info::renderNoToolsAvailable();

			ModuleSettings::toolboxAfterLinks( $this->get_module_links( TRUE ) );

		echo '</div>';
		return TRUE;
	}

	protected function render_tools_html_before( string $uri, string $sub, string $action, string $context ): bool
	{
		if ( $this->_do_tool_migrate_gtin( $sub ) )
			return FALSE; // avoid further UI

		return TRUE;
	}

	// https://woocommerce.com/document/google-for-woocommerce/attribute-mapping/gtin/
	private function _do_tool_migrate_gtin( string $sub ): bool
	{
		if ( ! self::do( ModuleSettings::ACTION_MIGRATE_GTIN ) )
			return FALSE;

		$this->raise_resources();

		return ModuleSettings::handleTool_migrate_gtin(
			WordPress\WooCommerce::PRODUCT_POSTTYPE,
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}
}

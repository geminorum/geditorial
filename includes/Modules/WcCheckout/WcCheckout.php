<?php namespace geminorum\gEditorial\Modules\WcCheckout;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

class WcCheckout extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_checkout',
			'title'    => _x( 'WC Checkout', 'Modules: WC Checkout', 'geditorial-admin' ),
			'desc'     => _x( 'Checkout Enhancements for WooCommerce', 'Modules: WC Checkout', 'geditorial-admin' ),
			'icon'     => 'store',
			'i18n'     => 'adminonly',
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
			'_checkoutform' => [
				[
					'field'       => 'simplify_free',
					'title'       => _x( 'Simplify Freebies', 'Setting Title', 'geditorial-wc-checkout' ),
					'description' => _x( 'Removes unnecessary checkout fields for free checkouts.', 'Setting Description', 'geditorial-wc-checkout' ),
				],
				[
					'field'       => 'inside_labels',
					'title'       => _x( 'Inside Labels', 'Setting Title', 'geditorial-wc-checkout' ),
					'description' => _x( 'Moves labels inside the checkout fields.', 'Setting Description', 'geditorial-wc-checkout' ),
				],
			],
			'_ordernotes' => [
				[
					'field'       => 'remove_order_notes',
					'title'       => _x( 'Remove Order Notes', 'Setting Title', 'geditorial-wc-checkout' ),
					'description' => _x( 'Removes default `Order notes` from checkout form.', 'Setting Description', 'geditorial-wc-checkout' ),
				],
				[
					'field'       => 'order_notes_label',
					'type'        => 'text',
					'title'       => _x( 'Order Notes Label', 'Setting Title', 'geditorial-wc-checkout' ),
					'description' => _x( 'Changes default `Order notes` label for customers. Leave empty to use defaults.', 'Setting Description', 'geditorial-wc-checkout' ),
					'placeholder' => __( 'Order notes', 'woocommerce' ),
				],
				[
					'field'       => 'order_notes_placeholder',
					'type'        => 'textarea',
					'title'       => _x( 'Order Notes Placeholder', 'Setting Title', 'geditorial-wc-checkout' ),
					'description' => _x( 'Changes default `Order notes` placeholder for customers. Leave empty to use defaults.', 'Setting Description', 'geditorial-wc-checkout' ),
					'placeholder' => __( 'Notes about your order, e.g. special notes for delivery.', 'woocommerce' ),
				],
			],
		];
	}

	protected function settings_section_titles( $suffix )
	{
		switch ( $suffix ) {

			case '_checkoutform': return [ _x( 'Checkout Form', 'Setting Section Title', 'geditorial-wc-checkout' ), NULL ];
			case '_ordernotes'  : return [ _x( 'Order Notes', 'Setting Section Title', 'geditorial-wc-checkout' ), NULL ];
		}

		return FALSE;
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'simplify_free' ) )
			$this->action( 'wp', 0, 10, 'simplify' );

		$this->filter( 'checkout_fields', 1, 9999, 'late', 'woocommerce' );

		if ( $this->get_setting( 'remove_order_notes' ) )
			$this->filter_false( 'woocommerce_enable_order_notes_field' );
	}

	// @REF: https://gist.github.com/bekarice/474ab82ab37b8de8617d
	public function wp_simplify()
	{
		// bail if the cart needs payment, we don't want to do anything
		if ( WC()->cart && WC()->cart->needs_payment() )
			return;

		// now continue only if we're at checkout
		// is_checkout() was broken as of WC 3.2 in Ajax context, double-check for `is_ajax`
		// I would check `WOOCOMMERCE_CHECKOUT` but testing shows it's not set reliably
		if ( ! is_checkout() && ! WordPress\IsIt::ajax() )
			return;

		// remove coupon forms since why would you want a coupon for a free cart??
		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

		// remove the "Additional Info" order notes
		$this->filter_false( 'woocommerce_enable_order_notes_field', 9999 );

		// unset the fields we don't want in a free checkout
		$this->filter( 'checkout_fields', 1, 99, 'simplify', 'woocommerce' );
	}

	/**
	 * Removes unnecessary fields from check-out.
	 * @source https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/#section-2
	 * @filter `woocommerce_checkout_fields`
	 *
	 * @param array $fields
	 * @return array $fields
	 */
	public function checkout_fields_simplify( $fields )
	{
		$keys = $this->filters( 'simplify_checkout_fields', [
			'billing_company',
			'billing_phone',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_postcode',
			'billing_country',
			'billing_state',
			'customer_mobile', // `gNetwork` Mobile
		], $fields );

		return array_merge( $fields, [
			'billing' => Core\Arraay::stripByKeys( $fields['billing'], $keys ),
		] );
	}

	public function checkout_fields_late( $fields )
	{
		if ( $this->get_setting( 'inside_labels' ) )
			$fields = $this->_checkout_fields_inside_labels( $fields );

		if ( ! $this->get_setting( 'remove_order_notes' ) )
			$fields = $this->_checkout_fields_overwrite_order_notes( $fields );

		return $fields;
	}

	// @REF: https://www.businessbloomer.com/woocommerce-move-labels-inside-checkout-fields/
	private function _checkout_fields_inside_labels( $fields )
	{
		foreach ( $fields as $section => $section_fields ) {

			foreach ( $section_fields as $section_field => $section_field_settings ) {

				$fields[$section][$section_field]['placeholder'] = $fields[$section][$section_field]['label'];
				$fields[$section][$section_field]['label'] = '';
			}
		}

		return $fields;
	}

	private function _checkout_fields_overwrite_order_notes( $fields )
	{
		// only overwrite if previously set
		if ( ! empty( $fields['order']['order_comments']['label'] ) )
			$fields['order']['order_comments']['label'] = $this->get_setting_fallback( 'order_notes_label', $fields['order']['order_comments']['label'] );

		// only overwrite if previously set
		if ( ! empty( $fields['order']['order_comments']['placeholder'] ) )
			$fields['order']['order_comments']['placeholder'] = $this->get_setting_fallback( 'order_notes_placeholder', $fields['order']['order_comments']['placeholder'] );

		return $fields;
	}
}

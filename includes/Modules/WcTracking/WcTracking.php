<?php namespace geminorum\gEditorial\Modules\WcTracking;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcTracking extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_tracking',
			'title'    => _x( 'WC Tracking', 'Modules: WC Tracking', 'geditorial-admin' ),
			'desc'     => _x( 'Package Tracking Enhancements for WooCommerce', 'Modules: WC Tracking', 'geditorial-admin' ),
			'icon'     => [ 'misc-48', 'ir-post' ], // 'buddicons-tracking',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
		];
	}

	protected function get_global_settings()
	{
		$icon = $this->_service_icon( TRUE );

		return [
			'_tracking' => [
				[
					'field'       => 'tracking_metakey',
					'type'        => 'text',
					'title'       => _x( 'Tracking Metakey', 'Setting Title', 'geditorial-wc-tracking' ),
					'description' => _x( 'Defines the meta-key that stored the tracking identifier data. Leave empty to use default.', 'Setting Description', 'geditorial-wc-tracking' ),
					'placeholder' => 'post_barcode',
					'field_class' => [ 'regular-text', 'code-text' ],
					'after'       => gEditorial\Settings::fieldAfterIcon( 'https://github.com/MahdiY/Persian-woocommerce-shipping' ),
				],
				[
					'field'       => 'service_template',
					'type'        => 'text',
					'title'       => _x( 'Service Template', 'Setting Title', 'geditorial-wc-tracking' ),
					'description' => _x( 'Defines the template that used to build the url to the tracking service. Leave empty to use default.', 'Setting Description', 'geditorial-wc-tracking' ),
					'placeholder' => 'https://tracking.post.ir/?id=%s',
					'field_class' => [ 'regular-text', 'code-text' ],
					'after'       => gEditorial\Settings::fieldAfterIcon( 'https://tracking.post.ir' ),
				],
				[
					'field'       => 'service_icon',
					'type'        => 'url',
					'title'       => _x( 'Service Icon', 'Setting Title', 'geditorial-wc-tracking' ),
					'description' => _x( 'Defines the image that used as visual identifier of the tracking service. Leave empty to use default.', 'Setting Description', 'geditorial-wc-tracking' ),
					'placeholder' => $icon,
					'field_class' => [ 'regular-text', 'url-text' ],
					'after'       => gEditorial\Settings::fieldAfterIcon( $icon, _x( 'View Default Icon', 'Setting Icon', 'geditorial-wc-tracking' ), 'external' ),
				],
			],
			'_defaults' => [
				[
					'field'       => 'email_before_text',
					'type'        => 'textarea',
					'title'       => _x( 'Email Before Text', 'Setting Title', 'geditorial-wc-tracking' ),
					'description' => _x( 'String to use before tracking info button on email template of the order details. Leave empty to use default.', 'Setting Description', 'geditorial-wc-tracking' ),
					'placeholder' => _x( 'You can view the status of package at any time by visiting this page:', 'Setting Default', 'geditorial-wc-tracking' ),
				],
				[
					'field'       => 'email_button_text',
					'type'        => 'text',
					'title'       => _x( 'Email Button Text', 'Setting Title', 'geditorial-wc-tracking' ),
					'description' => _x( 'String to use as prefix to tracking info button on email template of the order details. Leave empty to use default.', 'Setting Description', 'geditorial-wc-tracking' ),
					'placeholder' => _x( 'Tracking Postal Package', 'Setting Default', 'geditorial-wc-tracking' ),
				],
				[
					'field'       => 'admin_button_title',
					'type'        => 'text',
					'title'       => _x( 'Admin Button Title', 'Setting Title', 'geditorial-wc-tracking' ),
					'description' => _x( 'String to use as title of order action button on admin area. Leave empty to use default.', 'Setting Description', 'geditorial-wc-tracking' ),
					'placeholder' => _x( 'Tracking Package', 'Setting Default', 'geditorial-wc-tracking' ),
				],
			],
		];
	}

	protected function settings_section_titles( $suffix )
	{
		switch ( $suffix ) {

			case '_tracking': return [ _x( 'Tracking', 'Setting Section Title', 'geditorial-wc-tracking' ), NULL ];
		}

		return FALSE;
	}

	public function init()
	{
		parent::init();

		$this->action( 'email_after_order_table', 4, 8, FALSE, 'woocommerce' );
		$this->filter( 'email_styles', 1, 10, FALSE, 'woocommerce' );

		$this->action( 'admin_order_data_after_shipping_address', 1, 1, FALSE, 'woocommerce' );
		$this->action( 'admin_order_actions_end', 1, 99, FALSE, 'woocommerce' );
		$this->filter_append( 'woocommerce_shop_order_search_fields', $this->_tracking_metakey() );

		$this->filter( 'wc_customer_order_csv_export_order_row', 3 );
		$this->filter_set( 'wc_customer_order_csv_export_order_headers', [
			'tracking_id' => _x( 'Tracking', 'Export Column', 'geditorial-wc-tracking' ),
		] );

		$this->action( 'order_details_after_order_table', 1, 20, FALSE, 'woocommerce' );

		if ( is_admin() )
			return;

		$this->filter( 'my_account_my_orders_actions', 2, 99, FALSE, 'woocommerce' );
	}

	private function _tracking_metakey()
	{
		return $this->get_setting_fallback( 'tracking_metakey', 'post_barcode' );
	}

	private function _service_url( $tracking )
	{
		return sprintf( $this->get_setting_fallback( 'service_template', 'https://tracking.post.ir/?id=%s' ), $tracking );
	}

	private function _service_icon( $default = FALSE )
	{
		$icon = $this->filters( 'service_icon', GEDITORIAL_URL.'assets/images/irpost.min.svg' );
		return $default ? $icon : $this->get_setting_fallback( 'service_icon', $icon );
	}

	// TODO: read-only input with copy button of tracking id
	public function order_details_after_order_table( $order )
	{
		$this->email_after_order_table( $order, '', '', '' );
	}

	public function email_after_order_table( $order, $sent_to_admin, $plain_text, $email )
	{
		if ( ! ( $order instanceof \WC_Order ) )
			return;

		if ( ! $tracking = $order->get_meta( $this->_tracking_metakey(), TRUE, 'edit' ) )
			return;

		echo '<div style="margin-bottom:1rem;"><p>';

			echo $this->get_setting_fallback( 'email_before_text',
				_x( 'You can view the status of package at any time by visiting this page:', 'Setting Default', 'geditorial-wc-tracking' ) );

			vprintf( ' <a class="button alt -tracking" href="%s">%s (<small>%s</small>)</a>', [
				$this->_service_url( $tracking ),
				$this->get_setting_fallback( 'email_button_text', _x( 'Tracking Postal Package', 'Setting Default', 'geditorial-wc-tracking' ) ),
				$tracking,
			] );
		echo '</p></div>';
	}

	public function email_styles( $styles )
	{
		$base = get_option( 'woocommerce_email_base_color' );
		$text = wc_light_or_dark( $base, '#202020', '#ffffff' );

		$styles.= 'a.button.alt.-tracking {
			background-color: '.esc_attr( $base ).';
			border: 0;
			color: '.esc_attr( $text ).';
			display: block;
			font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
			font-weight: bold;
			margin-top: 10px;
			padding: 10px 15px;
			text-decoration: none;
			text-align: center;
			width: fit-content;
		}';

		return $styles;
	}

	public function admin_order_data_after_shipping_address( $order )
	{
		if ( ! $tracking = $order->get_meta( $this->_tracking_metakey(), TRUE, 'edit' ) )
			return;

		echo $this->wrap_open( 'form-field form-field-wide -tracking' );
			echo Core\HTML::img( $this->_service_icon(), '-before-icon' );
			echo ' '._x( 'Tracking Package ID:', 'Action Title', 'geditorial-wc-tracking' );
			echo ' '.Core\HTML::link( $tracking, $this->_service_url( $tracking ), TRUE );
		echo '</div>';
	}

	public function my_account_my_orders_actions( $actions, $order )
	{
		if ( $tracking = $order->get_meta( $this->_tracking_metakey(), TRUE, 'edit' ) )
			$actions[$this->classs( 'tracking' )] = [
				'url'  => $this->_service_url( $tracking ),
				'name' => _x( 'Tracking', 'Action', 'geditorial-wc-tracking' ), // NOTE: can not use image tag, the name will be escaped upon rendering
			];

		return $actions;
	}

	public function admin_order_actions_end( $order )
	{
		if ( $tracking = $order->get_meta( $this->_tracking_metakey(), TRUE, 'edit' ) )
			echo Core\HTML::tag( 'a', [
				'href'   => $this->_service_url( $tracking ),
				'title'  => $this->get_setting_fallback( 'admin_button_title', _x( 'Tracking Package', 'Setting Default', 'geditorial-wc-tracking' ) ),
				'class'  => Core\HTML::buttonClass( FALSE, [ 'alt', $this->classs( 'tracking' ) ] ),
				'target' => '_blank',
			], Core\HTML::img( $this->_service_icon() ) );
	}

	// @REF: https://gist.github.com/bekarice/1be29b71731c31131103ff54a096e541
	public function wc_customer_order_csv_export_order_row( $order_data, $order, $csv_generator )
	{
		$tracking = [ 'tracking_id' => $order->get_meta( $this->_tracking_metakey(), TRUE, 'edit' ) ];

		$new_data         = [];
		$one_row_per_item = FALSE;

		// pre 4.0 compatibility
		if ( version_compare( wc_customer_order_csv_export()->get_version(), '4.0.0', '<' ) )
			$one_row_per_item = ( 'default_one_row_per_item' === $csv_generator->order_format
				|| 'legacy_one_row_per_item' === $csv_generator->order_format );

		// post 4.0 (requires 4.0.3+)
		else if ( isset( $csv_generator->format_definition ) )
			$one_row_per_item = 'item' === $csv_generator->format_definition['row_type'];

		if ( $one_row_per_item )
			foreach ( $order_data as $data )
				$new_data[] = array_merge( (array) $data, $tracking );

		else
			$new_data = array_merge( $order_data, $tracking );

		return $new_data;
	}
}

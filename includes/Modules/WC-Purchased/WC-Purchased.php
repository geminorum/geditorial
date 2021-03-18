<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;

class WcPurchased extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_purchased',
			'title'    => _x( 'WC Purchased', 'Modules: WC Purchased', 'geditorial' ),
			'desc'     => _x( 'Product Purchase Reports for WooCommerce', 'Modules: WC Purchased', 'geditorial' ),
			'icon'     => 'store',
			'disabled' => Helper::moduleCheckWooCommerce(),
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles( [ 'administrator', 'subscriber' ] );

		return [
			'_general' => [
				[
					'field'       => 'order_statuses',
					'type'        => 'checkboxes',
					'title'       => _x( 'Order Statuses', 'Setting Title', 'geditorial-wc-purchased' ),
					'description' => _x( 'Accepted statuses on order list reports.', 'Setting Description', 'geditorial-wc-purchased' ),
					'default'     => [ 'completed' ],
					'values'      => $this->get_order_statuses(),

				],
			],
			'_roles' => [
				[
					'field'       => 'reports_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Reports Roles', 'Setting Title', 'geditorial-wc-purchased' ),
					'description' => _x( 'Roles that can view product purchase reports.', 'Setting Description', 'geditorial-wc-purchased' ),
					'values'      => $roles,
				],
				[
					'field'       => 'export_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Export Roles', 'Setting Title', 'geditorial-wc-purchased' ),
					'description' => _x( 'Roles that can export product purchase reports.', 'Setting Description', 'geditorial-wc-purchased' ),
					'values'      => $roles,
				],
			],
		];
	}

	public function admin_menu()
	{
		$this->_hook_submenu_adminpage( 'reports' );
	}

	public function render_submenu_adminpage()
	{
		$this->render_default_mainpage( 'reports', 'update' );
	}

	public function current_screen( $screen )
	{
		if ( $screen->post_type == 'product' ) {

			if ( 'post' == $screen->base ) {

			} else if ( 'edit' == $screen->base ) {

				if ( $this->role_can( 'reports' ) ) {
					$this->filter( 'post_row_actions', 2 );
					Scripts::enqueueThickBox();
				}
			}
		}
	}

	public function post_row_actions( $actions, $post )
	{
		if ( in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			return $actions;

		if ( ! current_user_can( 'edit_others_products', $post->ID ) )
			return $actions;

		if ( $link = $this->get_adminpage_url( TRUE, [ 'post' => $post->ID, 'noheader' => 1 ], 'reports' ) )
			return Arraay::insert( $actions, [
				$this->classs() => HTML::tag( 'a', [
					'href'   => $link,
					'title'  => _x( 'Product Purchase Reports', 'Title Attr', 'geditorial-wc-purchased' ),
					'class'  => [ '-purchase-reports', 'thickbox' ],
					'target' => '_blank',
				], _x( 'Purchases', 'Action', 'geditorial-wc-purchased' ) ),
			], 'view', 'after' );

		return $actions;
	}

	protected function render_mainpage_content()
	{
		if ( ! $product_id = self::req( 'post' ) )
			return HTML::desc( _x( 'No Product!', 'Message', 'geditorial-wc-purchased' ) );

		if ( ! $orders = $this->get_product_purchased_orders( $product_id ) )
			return HTML::desc( _x( 'No Orders!', 'Message', 'geditorial-wc-purchased' ) );

		$export = $this->role_can( 'export' );

		if ( isset( $_GET['export'] ) && $export )
			Text::download( $this->get_product_purchased( $orders ), File::prepName( sprintf( 'product-%d-purchased.csv', $product_id ) ) );

		echo $this->wrap_open( '-header' );

			if ( $export )
				echo HTML::tag( 'a', [
					'href'    => $this->get_adminpage_url( TRUE, [ 'post' => $product_id, 'noheader' => 1, 'export' => '' ], 'reports' ),
					'class'   => [ 'button', 'button-small' ],
				], _x( 'Export CSV', 'Button', 'geditorial-wc-purchased' ) );

			HTML::h3( get_the_title( $product_id ), '-product-name' );

		echo '</div>';

		HTML::tableList( [
			'order_number' => [
				'title'    => _x( '#', 'Column Title', 'geditorial-wc-purchased' ),
				'callback' => function( $value, $row, $column, $index ){
					return $row->get_id(); // FIXME: link to order edit page
				},
			],
			'date_paid' => [
				'title'    => _x( 'Paid On', 'Column Title', 'geditorial-wc-purchased' ),
				'args'     => [ 'formats' => Datetime::dateFormats( FALSE ) ],
				'callback' => function( $value, $row, $column, $index ){
					return wp_date( $column['args']['formats']['datetime'], $row->get_date_paid() );
				},
			],
			'shipping_address' => [
				'title'    => _x( 'Address', 'Column Title', 'geditorial-wc-purchased' ),
				'callback' => function( $value, $row, $column, $index ){
					return $row->get_formatted_shipping_address();
				},
			],
			'total_price' => [
				'title'    => _x( 'Total', 'Column Title', 'geditorial-wc-purchased' ),
				'callback' => function( $value, $row, $column, $index ){
					return wc_price( $row->get_total() );
				},
			],

		], array_filter( array_map( [ $this, 'prep_product_data' ], $orders ) ), [
			'empty' => $this->get_posttype_label( 'shop_order', 'not_found' ),
		] );
	}

	// @REF: https://rfmeier.net/get-all-orders-for-a-product-in-woocommerce/
	private function get_product_purchased_orders( $product_id )
	{
		global $wpdb;

		$query = $wpdb->prepare( "SELECT `items`.`order_id`,
			MAX(CASE WHEN `itemmeta`.`meta_key` = '_product_id' THEN `itemmeta`.`meta_value` END) AS `product_id`
			FROM `{$wpdb->prefix}woocommerce_order_items` AS `items`
			INNER JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` AS `itemmeta`
			ON `items`.`order_item_id` = `itemmeta`.`order_item_id`
			WHERE `items`.`order_item_type` IN('line_item')
			AND `itemmeta`.`meta_key` IN('_product_id')
			GROUP BY `items`.`order_item_id`
			HAVING `product_id` = %d",
		$product_id );

		return $wpdb->get_results( $query );
	}

	private function prep_product_data( $object )
	{
		if ( ! $order = wc_get_order( $object->order_id ) )
			return NULL;

		if ( ! $order->has_status( $this->get_setting( 'order_statuses', [ 'completed' ] ) ) )
			return NULL;

		return $order;
	}

	private function get_product_purchased( $orders )
	{
		$formats = Datetime::dateFormats( FALSE );

		$data = [ [
			'order_number',
			// 'user_id',
			'date_paid',
			'total_price',
			'shipping_fullname',
			'billing_phone',
			'shipping_postcode',
			'shipping_address',
		] ];

		foreach ( $orders as $object ) {

			if ( ! $order = wc_get_order( $object->order_id ) )
			if ( ! $order = $this->prep_product_data( $object ) )
				continue;

			$data[] = [
				$order->get_id(),
				// $order->get_user_id(),
				wp_date( $formats['datetime'], $order->get_date_paid() ), // TODO: use `Datetime::dateFormat()`
				$order->get_total(),
				$order->get_formatted_shipping_full_name(),
				$order->get_billing_phone(),
				$order->get_shipping_postcode(),
				str_replace( '<br/>', ' - ', $order->get_formatted_shipping_address() ),
			];
		}

		return Text::toCSV( $data );
	}
	private function get_order_statuses()
	{
		$statuses = [];

		foreach ( wc_get_order_statuses() as $status => $name )
			$statuses[( 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status )] = $name;

		return $statuses;
	}
}

<?php namespace geminorum\gEditorial\Modules\WcDashboard;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;

class WcDashboard extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'     => 'wc_dashboard',
			'title'    => _x( 'WC Dashboard', 'Modules: WC Dashboard', 'geditorial' ),
			'desc'     => _x( 'Customer Dashboard Enhancements for WooCommerce', 'Modules: WC Dashboard', 'geditorial' ),
			'icon'     => 'dashboard',
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckWooCommerce(),
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'main_message',
					'type'        => 'textarea-quicktags-tokens',
					'title'       => _x( 'Main Message', 'Setting Title', 'geditorial-wc-dashboard' ),
					'description' => _x( 'Displays as main dashboard message on front-end account page.', 'Setting Description', 'geditorial-wc-dashboard' ),
					'placeholder' => $this->_default_main_message(),
					'field_class' => [ 'regular-text', 'textarea-autosize' ],
					'values'      => [ 'logout_url', 'display_name' ],
				],
				[
					'field'       => 'downloads_disabled',
					'type'        => 'disabled',
					'title'       => _x( 'Downloads on Dashboard', 'Setting Title', 'geditorial-wc-dashboard' ),
					'description' => _x( 'Displays purchased download products on front-end account page.', 'Setting Description', 'geditorial-wc-dashboard' ),
				],
				[
					'field'       => 'purchased_dashboard',
					'title'       => _x( 'Purchased on Dashboard', 'Setting Title', 'geditorial-wc-dashboard' ),
					'description' => _x( 'Displays recently purchased products on front-end account page.', 'Setting Description', 'geditorial-wc-dashboard' ),
				],
				[
					'field'       => 'purchased_menutitle',
					'type'        => 'text',
					'title'       => _x( 'Dashboard Purchased Title', 'Setting Title', 'geditorial-wc-dashboard' ),
					'description' => _x( 'Appears as title of the purchased products menu on front-end account page.', 'Setting Description', 'geditorial-wc-dashboard' ),
					'placeholder' => _x( 'Purchased', 'Default', 'geditorial-wc-dashboard' ),
				],
				[
					'field'       => 'purchased_empty_message',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Dashboard Purchased Empty', 'Setting Title', 'geditorial-wc-dashboard' ),
					'description' => _x( 'Appears as message for empty purchased products on front-end account page.', 'Setting Description', 'geditorial-wc-dashboard' ),
					'placeholder' => _x( 'Nothing purchased yet.', 'Default', 'geditorial-wc-dashboard' ),
				],
				[
					'field'       => 'override_menutitle',
					'type'        => 'text',
					'title'       => _x( 'Overwrite Menu Titles', 'Setting Title', 'geditorial-wc-dashboard' ),
					'description' => _x( 'Changes default dashboard menu titles on front-end account page. Leave empty for default.', 'Setting Description', 'geditorial-wc-dashboard' ),
					'values'      => [
						// @REF: `wc_get_account_menu_items()`
						'dashboard'       => __( 'Dashboard', 'woocommerce' ),
						'orders'          => __( 'Orders', 'woocommerce' ),
						'downloads'       => __( 'Downloads', 'woocommerce' ),
						'edit-address'    => _n( 'Addresses', 'Address', (int) wc_shipping_enabled(), 'woocommerce' ),
						'payment-methods' => __( 'Payment methods', 'woocommerce' ),
						'edit-account'    => __( 'Account details', 'woocommerce' ),
						'customer-logout' => __( 'Logout', 'woocommerce' ),
					],
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'endpoint_purchased' => 'purchased-products',
		];
	}

	private function _default_main_message()
	{
		/* translators: %1$s: user display name, %2$s: logout url */
		return sprintf( _x( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'Setting Default', 'geditorial-wc-dashboard' ), '{{display_name}}', '{{logout_url}}' );
	}

	public function init()
	{
		parent::init();

		$this->filter( 'wc_get_template', 5, 12 );
		$this->filter( 'account_menu_items', 2, 40, FALSE, 'woocommerce' );
		$this->action( 'account_dashboard', 0, 8, FALSE, 'woocommerce' );

		if ( $this->get_setting( 'purchased_dashboard' ) ) {

			// FIXME: WTF: must only append to my-account page, @SEE: WooCommerce approach
			add_rewrite_endpoint( $this->constant( 'endpoint_purchased' ), EP_PAGES );
			$this->action( 'account_purchased-products_endpoint', 0, 10, FALSE, 'woocommerce' );
		}
	}

	public function account_menu_items( $items, $endpoints )
	{
		if ( $this->get_setting( 'downloads_disabled' ) )
			unset( $items['downloads'] );

		if ( $this->get_setting( 'purchased_dashboard' ) )
			$items = Core\Arraay::insert( $items, [
				$this->constant( 'endpoint_purchased' ) => $this->get_setting_fallback( 'purchased_menutitle', _x( 'Purchased', 'Default', 'geditorial-wc-dashboard' ) ),
			], 'orders', 'after' );

		foreach ( $this->get_setting( 'override_menutitle', [] ) as $slug => $title )
			if ( ! empty( $title ) && array_key_exists( $slug, $items ) )
				$items[$slug] = $title;

		return $items;
	}

	public function wc_get_template( $template, $template_name, $args, $template_path, $default_path )
	{
		if ( 'myaccount/dashboard.php' == $template_name )
			return $this->path.'Templates/dashboard.php';

		return $template;
	}

	// @REF: https://rudrastyh.com/woocommerce/display-purchased-products.html
	public function account_purchased_products_endpoint()
	{
		global $wpdb;

		$user_id = get_current_user_id();

		// this SQL query allows to get all the products purchased by the
		// current user in this example we sort products by date but you
		// can reorder them another way
		$ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT      itemmeta.meta_value
			FROM        {$wpdb->prefix}woocommerce_order_itemmeta itemmeta
			INNER JOIN  {$wpdb->prefix}woocommerce_order_items items
			            ON itemmeta.order_item_id = items.order_item_id
			INNER JOIN  {$wpdb->posts} orders
			            ON orders.ID = items.order_id
			INNER JOIN  {$wpdb->postmeta} ordermeta
			            ON orders.ID = ordermeta.post_id
			WHERE       itemmeta.meta_key = '_product_id'
			            AND ordermeta.meta_key = '_customer_user'
			            AND ordermeta.meta_value = %s
			ORDER BY    orders.post_date DESC
		", $user_id ) );

		// some orders may contain the same product,
		// but we do not need it twice
		$ids = array_unique( $ids );

		if ( ! empty( $ids ) ) {

			$products = new \WP_Query( [
				'post_type'   => 'product',
				'post_status' => 'publish',
				'orderby'     => 'post__in',
				'post__in'    => $ids,
			] );

			echo $this->wrap_open( [ 'woocommerce', 'columns-3' ] );
			woocommerce_product_loop_start();

			while ( $products->have_posts() ) {
				$products->the_post();
				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();
			woocommerce_reset_loop();
			wp_reset_postdata();
			echo '</div>';

		} else {

			echo Helper::prepDescription( $this->get_setting_fallback( 'purchased_empty_message',
				_x( 'Nothing purchased yet.', 'Default', 'geditorial-wc-dashboard' ) ) );

			$this->actions( 'account_purchased_empty', $user_id ); // LIKE: first purchase coupon
		}
	}

	public function account_dashboard()
	{
		$user_id      = get_current_user_id();
		$current_user = get_user_by( 'id', $user_id );

		$tokens = [
			'logout_url'   => wc_logout_url(),
			'display_name' => $current_user->display_name,
		];

		$message = Core\Text::replaceTokens( $this->get_setting_fallback( 'main_message', $this->_default_main_message() ), $tokens );

		$this->actions( 'account_dashboard_main_before', $user_id );
			echo Core\Text::autoP( $message );
		$this->actions( 'account_dashboard_main_after', $user_id );
	}
}

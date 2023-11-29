<?php namespace geminorum\gEditorial\Modules\WcWidgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

class WcWidgets extends gEditorial\Module
{
	protected $deafults = [ 'widget_support' => TRUE ];

	public static function module()
	{
		return [
			'name'     => 'wc_widgets',
			'title'    => _x( 'WC Widgets', 'Modules: WC Widgets', 'geditorial-admin' ),
			'desc'     => _x( 'Widget Enhancements for WooCommerce', 'Modules: WC Widgets', 'geditorial-admin' ),
			'icon'     => 'welcome-widgets-menus',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Helper::moduleCheckWooCommerce(),
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'  => 'widgets',
					'title'  => _x( 'Widgets', 'Setting Title', 'geditorial-wc-widgets' ),
					'type'   => 'checkboxes',
					'values' => $this->_list_widgets(),
				],
				[
					'field'       => 'custom_areas',
					'type'        => 'checkboxes-values',
					'title'       => _x( 'Custom Areas', 'Setting Title', 'geditorial-wc-widgets' ),
					'description' => _x( 'Registers widget area on the selected action hooks.', 'Setting Description', 'geditorial-wc-widgets' ),
					'values'      => Core\Arraay::column( $this->_get_widget_action_hooks(), 'title', 'action' ),
				],
			],
		];
	}

	private function get_widgets()
	{
		return [
			'WC-Message' => 'WCMessage',
			// TODO: 'WC-ProductMeta' => 'WCProductMeta', // https://gist.github.com/bekarice/4022e3051d7684ddee2a
			// TODO: https://gist.github.com/bekarice/377d10d2efd929be1ab8
		];
	}

	private function _list_widgets()
	{
		$list = [];

		foreach ( $this->get_widgets() as $key => $class ) {

			$widget = call_user_func( [ __NAMESPACE__.'\\Widgets\\'.$class, 'setup' ] );

			$list[$key] = $widget['title'].': <em>'.$widget['desc'].'</em>';
		}

		return $list;
	}

	// @SEE: https://quadlayers.com/how-to-use-woocommerce-hooks/
	// @SEE: https://www.tychesoftwares.com/woocommerce-checkout-page-hooks-visual-guide-with-code-snippets/
	// @REF: https://www.businessbloomer.com/woocommerce-visual-hook-guide-checkout-page/
	private function _get_widget_action_hooks()
	{
		$list = [
			[
				'action'   => 'woocommerce_thankyou',
				'title'    => _x( 'Thank-you (Before Order Details)', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 10,
			],
			[
				'action'   => 'woocommerce_before_checkout_form',
				'title'    => _x( 'Before Checkout Form', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 8,
			],
			[
				'action'   => 'woocommerce_after_checkout_form',
				'title'    => _x( 'After Checkout Form', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 12,
			],
			[
				'action'   => 'woocommerce_before_order_notes',
				'title'    => _x( 'Before Order Notes', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 8,
			],
			[
				'action'   => 'woocommerce_review_order_before_payment',
				'title'    => _x( 'Review Order: Before Payment', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 8,
			],
			[
				'action'   => 'woocommerce_before_edit_account_address_form',
				'title'    => _x( 'Before Edit Account Address Form', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 8,
			],
			[
				'action'   => 'woocommerce_account_dashboard',
				'title'    => _x( 'Account Dashboard', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 10,
			],
		];

		if ( gEditorial()->enabled( 'wc_dashboard' ) )
			$list[] = [
				'action'   => 'geditorial_wc_dashboard_account_purchased_empty',
				'title'    => _x( 'Editorial: Dashboard Purchased Empty', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority' => 10,
			];

		if ( WordPress\WooCommerce::isActiveWoodMart() ) {
			$list[] = [
				'action'        => 'woodmart_shop_filters_area',
				'title'         => _x( 'Woodmart: Shop Filters Area', 'Action Hook', 'geditorial-wc-widgets' ),
				'priority'      => 99,
				'before_widget' => '<div id="%1$s" class="woodmart-widget widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h5 class="widget-title">',
				'after_title'   => '</h5>',
			];
		}

		return (array) $this->filters( 'widget_action_hooks', $list );
	}

	public function widgets_init()
	{
		$widgets = $this->get_setting( 'widgets', [] );

		foreach ( $this->get_widgets() as $key => $class )
			if ( in_array( $key, $widgets, TRUE ) )
				register_widget( __NAMESPACE__.'\\Widgets\\'.$class );

		$areas = Core\Arraay::reKey( $this->_get_widget_action_hooks(), 'action' );

		foreach ( $this->get_setting( 'custom_areas', [] ) as $index => $hook ) {

			$sidebar  = $this->classs( 'area', $index );
			$name     = isset( $areas[$hook]['title'] ) ? $areas[$hook]['title'] : Core\Text::readableKey( $hook );
			$priority = empty( $areas[$hook]['priority'] ) ? 10 : $areas[$hook]['priority'];

			add_action( $hook, static function () use ( $sidebar ) {
				dynamic_sidebar( $sidebar );
			}, $priority, 0 );

			register_sidebar( [
				'id'            => $sidebar,
				/* translators: %s: widget area name */
				'name'          => sprintf( _x( 'WooCommerce: %s', 'Widget Area Prefix', 'geditorial-wc-widgets' ), $name ),
				/* translators: %s: widget action hook */
				'description'   => sprintf( _x( 'Widgets in this area will appear on %s action hook.', 'Widget Area Description', 'geditorial-wc-widgets' ), Core\HTML::code( $hook ) ),
				'before_widget' => array_key_exists( 'before_widget', $areas[$hook] ) ? $areas[$hook]['before_widget'] : '', // empty to overrride defaults
				'after_widget'  => array_key_exists( 'after_widget', $areas[$hook] )  ? $areas[$hook]['after_widget']  : '', // empty to overrride defaults
				'before_title'  => array_key_exists( 'before_title', $areas[$hook] )  ? $areas[$hook]['before_title']  : '<h2 class="widgettitle">',
				'after_title'   => array_key_exists( 'after_title', $areas[$hook] )   ? $areas[$hook]['after_title']   : '</h2>',
			] );
		}
	}
}

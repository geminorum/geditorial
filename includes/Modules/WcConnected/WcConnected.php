<?php namespace geminorum\gEditorial\Modules\WcConnected;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class WcConnected extends gEditorial\Module
{
	use Internals\MetaBoxSupported;

	public static function module()
	{
		return [
			'name'     => 'wc_connected',
			'title'    => _x( 'WC Connected', 'Modules: WC Connected', 'geditorial-admin' ),
			'desc'     => _x( 'Content Connected Products', 'Modules: WC Connected', 'geditorial-admin' ),
			'icon'     => 'controls-repeat',
			'i18n'     => 'adminonly',
			'access'   => 'beta',
			'disabled' => Services\Modulation::moduleCheckWooCommerce(),
			'keywords' => [
				'manual-connect',
				'has-shortcodes',
				'woocommerce',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_frontend' => [
				'tabs_support' => [ _x( 'Displays connected posts on front-end product tabs.', 'Setting Description', 'geditorial-wc-connected' ) ],
				'tab_title'    => [ NULL , _x( 'Related Posts', 'Setting Default', 'geditorial-wc-connected' ) ],
				'tab_priority' => [ NULL, 60 ],
			],
			'_supports' => [
				'shortcode_support',
				'widget_support',
			],
			'_roles' => [
				'assign_roles' => [ NULL, $roles ],
			],
			'_constants' => [
				'main_shortcode_constant'      => [ NULL, 'connected-posts' ],
				'connected_shortcode_constant' => [ NULL, 'connected-products' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_shortcode'      => 'connected-posts',
			'connected_shortcode' => 'connected-products',
			'metakey_connected'   => '_connected_products',
		];
	}

	protected function get_global_strings()
	{
		$strings = [];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'mainbox_title'      => _x( 'Connected Contents', 'MetaBox: `mainbox_title`', 'geditorial-wc-connected' ),
			/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
			'supportedbox_title' => _x( 'Connected Products', 'MetaBox: `supportedbox_title`', 'geditorial-wc-connected' ),
		];

		return $strings;
	}

	protected function posttypes_excluded( $extra = [] )
	{
		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded( $extra + [
				WordPress\WooCommerce::PRODUCT_POSTTYPE,
			], $this->keep_posttypes )
		);
	}

	public function init()
	{
		parent::init();

		$this->register_shortcode( 'main_shortcode' );
		$this->register_shortcode( 'connected_shortcode' );

		if ( $this->get_setting( 'tabs_support', TRUE ) )
			$this->filter( 'product_tabs', 1, $this->get_setting( 'tab_priority', 65 ), FALSE, 'woocommerce' );
	}

	public function widgets_init()
	{
		register_widget( __NAMESPACE__.'\\Widgets\\WcConnectedProducts' );
	}

	public function current_screen( $screen )
	{
		if ( 'post' == $screen->base ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( $this->role_can( 'assign' ) ) {
					$this->_hook_general_supportedbox( $screen );
					$this->_save_meta_supported( $screen->post_type );
				}
			}

		} else if ( 'edit' == $screen->base ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

			}
		}
	}

	private function _save_meta_supported( $posttype )
	{
		$this->_hook_store_metabox( $posttype );
	}

	public function store_metabox( $post_id, $post, $update, $context = NULL )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		if ( ! $this->nonce_verify( 'supportedbox' ) )
			return;

		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$metakey = $this->constant( 'metakey_connected' );
		$request = $this->classs( $metakey );

		if ( FALSE === ( $data = self::req( $request, FALSE ) ) )
			return;

		$connected = get_metadata( 'post', $post->ID, $metakey, FALSE );
		$sanitized = Core\Arraay::prepNumeral( $data );

		// NOTE: loop through saved items for disconnecting
		foreach ( $connected as $already ) {

			if ( in_array( $already, $sanitized, TRUE ) )
				continue;

			delete_metadata( 'post', $post->ID, $metakey, $already );
		}

		// NOTE: loop through new items for connecting
		foreach ( $sanitized as $to ) {

			if ( in_array( $to, $connected, TRUE ) )
				continue;

			add_metadata( 'post', $post->ID, $metakey, $to );
		}
	}

	protected function _render_supportedbox_content( $post, $box, $context = NULL, $screen = NULL )
	{
		$context   = $context ?? 'supportedbox';
		$metakey   = $this->constant( 'metakey_connected' );
		$connected = get_metadata( 'post', $post->ID, $metakey, FALSE );
		$options   = [];

		foreach ( $connected as $product_id )
			if ( $product = wc_get_product( $product_id ) )
				$options[] = Core\HTML::tag( 'option', [
					'value'    => $product_id,
					'selected' => TRUE,
				], wp_strip_all_tags( $product->get_formatted_name() ) );

		$atts = [
			'name'  => sprintf( '%s[]', $this->classs( $metakey ) ),
			'class' => [
				'wc-product-search',
			],

			'multiple' => TRUE,
			'style'    => 'width:100%',

			'data-action'      => 'woocommerce_json_search_products_and_variations',
			'data-exclude'     => $post->ID,
			'data-placeholder' => _x( 'Search for a product …', 'Place Holder', 'geditorial-wc-connected' ),
		];

		echo Core\HTML::wrap(
			Core\HTML::tag( 'select', $atts, implode( "\n", $options ) ),
			'field-wrap -select -multiple hide-if-no-js'
		);

		Core\HTML::inputHidden( $atts['name'], '0' );
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'metadata',
			WordPress\WooCommerce::PRODUCT_POSTTYPE,
			'',
			array_merge( [
				'post_id'   => NULL,
				'posttypes' => $this->posttypes(),
				'metakey'   => $this->constant( 'metakey_connected' ),
				'future'    => 'off',
				'title'     => FALSE,
				'order'     => 'DESC',
				'orderby'   => 'date',
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	public function connected_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'columns' => wc_get_default_products_per_row(),
			'limit'   => '-1',
			'context' => NULL,
			'wrap'    => TRUE,
			'class'   => '',
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $this->constant( 'main_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		$metakey   = $this->constant( 'metakey_connected' );
		$connected = get_metadata( 'post', $post->ID, $metakey, FALSE );

		if ( empty( $connected ) )
			return $content;

		$shortcode = new \WC_Shortcode_Products( array_merge( $args, [
			'ids' => implode( ',', $connected ),
		] ), 'products' );

		return $shortcode->get_content();
	}

	public function product_tabs( $tabs )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return $tabs;

		if ( ! $this->has_content_for_product( $product, 'woocommerce' ) )
			return $tabs;

		$tabs['connected_posts'] = [
			'title'    => $this->get_setting_fallback( 'tab_title', _x( 'Related Posts', 'Setting Default', 'geditorial-wc-connected' ) ),
			'priority' => $this->get_setting( 'tab_priority', 65 ), // NOTE: `priority` does not applied on this filter!
			'callback' => [ $this, 'product_tabs_connected_callback' ],
		];

		return $tabs;
	}

	public function has_content_for_product( $product = NULL, $context = NULL )
	{
		if ( ! $product = wc_get_product( $product ) )
			return FALSE;

		return WordPress\PostType::hasPosts( $this->posttypes(), NULL, [
			'meta_query' => [ [
				'key'     => $this->constant( 'metakey_connected' ),
				'value'   => $product->get_id(),
				'compare' => '=',
			] ],
		] );
	}

	public function product_tabs_connected_callback()
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		echo gEditorial\ShortCode::listPosts( 'metadata',
			WordPress\WooCommerce::PRODUCT_POSTTYPE,
			'',
			$this->filters( 'product_listconnected_args', [
				'context' => 'woocommerce',
				'module'  => $this->module->name,
				'post_id' => $product->get_id(),

				'posttypes' => $this->posttypes(),
				'metakey'   => $this->constant( 'metakey_connected' ),

				'future' => 'off',
				'title'  => FALSE,
				'wrap'   => FALSE,
				'after'  => '</div>',
				'before' => $this->wrap_open( [
					'-product-listconnected',
					sprintf( 'columns-%d', wc_get_default_products_per_row() ),
				] ),

				'order'   => 'DESC',
				'orderby' => 'date',

				'exclude_posttypes' => WordPress\WooCommerce::PRODUCT_POSTTYPE,
			], $product )
		);
	}
}

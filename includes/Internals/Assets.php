<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait Assets
{
	public function enqueue_asset_style( $name = NULL, $deps = [], $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->key;

		// screen passed
		else if ( is_object( $name ) )
			$name = $name->base;

		else
			$name = $this->key.'.'.$name;

		$name = str_replace( '_', '-', $name );

		if ( is_null( $handle ) )
			$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

		$prefix = is_admin() ? 'admin.' : 'front.';

		wp_enqueue_style( $handle, GEDITORIAL_URL.'assets/css/'.$prefix.$name.'.css', $deps, GEDITORIAL_VERSION, 'all' );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
	}

	// NOTE: each script must have a `.min` version
	public function enqueue_asset_js( $args = [], $name = NULL, $deps = [ 'jquery' ], $key = NULL, $handle = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = $key;

		else if ( $name instanceof \WP_Screen )
			$name = $key.'.'.$name->base;

		if ( TRUE === $args ) {
			$args = [];

		} else if ( $args && $name && is_string( $args ) ) {
			$name.= '.'.$args;
			$args = [];
		}

		if ( $name ) {

			$name = str_replace( '_', '-', $name );

			if ( is_null( $handle ) )
				$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

			$prefix = is_admin() ? 'admin.' : 'front.';
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script(
				$handle,
				GEDITORIAL_URL.'assets/js/'.$prefix.$name.$suffix.'.js',
				$deps,
				GEDITORIAL_VERSION,
				TRUE
			);
		}

		if ( ! array_key_exists( '_rest', $args ) && method_exists( $this, 'restapi_get_namespace' ) )
			$args['_rest'] = sprintf( '/%s', $this->restapi_get_namespace() );

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( $this->hook() );

		gEditorial()->enqueue_asset_config( $args, $key );

		return $handle;
	}

	// combined global styles
	// CAUTION: front only
	// TODO: also we need api for module specified css
	public function enqueue_styles()
	{
		gEditorial()->enqueue_styles();
	}

	public function register_editor_button( $plugin, $settings_key = 'editor_button' )
	{
		if ( ! $this->get_setting( $settings_key, TRUE ) )
			return;

		gEditorial()->register_editor_button( $this->hook( $plugin ),
			'assets/js/tinymce/'.$this->module->name.'.'.$plugin.'.js' );
	}
}

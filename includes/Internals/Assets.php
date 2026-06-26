<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait Assets
{
	public function enqueue_asset_style( $name = NULL, $deps = NULL, $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->key;

		// screen passed
		else if ( is_object( $name ) )
			$name = $name->base;

		else
			$name = self::dot( $this->key, $name );

		$name   = str_replace( '_', '-', $name );
		$handle = $handle ?? strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );
		$prefix = is_admin() ? 'admin.' : 'front.';

		wp_enqueue_style(
			$handle,
			sprintf( '%sassets/css/%s%s.css',
				GEDITORIAL_URL,
				$prefix,
				$name
			),
			$deps ?? [],
			GEDITORIAL_HASH,
			'all'
		);

		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
	}

	// NOTE: each script must have a `.min` version
	public function enqueue_asset_js( $args = [], $name = NULL, $deps = NULL, $key = NULL, $handle = NULL )
	{
		$key = $key ?? $this->key;

		if ( is_null( $name ) )
			$name = $key;

		else if ( $name instanceof \WP_Screen )
			$name = self::dot( $key, $name->base );

		if ( TRUE === $args ) {

			$args = [];

		} else if ( $args && $name && is_string( $args ) ) {

			$name = self::dot( $name, $args );
			$args = [];
		}

		if ( $name ) {

			$name   = str_replace( '_', '-', $name );
			$handle = $handle ?? strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

			$prefix = is_admin() ? 'admin.' : 'front.';
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_enqueue_script(
				$handle,
				sprintf( '%sassets/js/%s%s%s.js',
					GEDITORIAL_URL,
					$prefix,
					$name,
					$suffix
				),
				$deps ?? [ 'jquery' ],
				GEDITORIAL_HASH,
				TRUE
			);
		}

		if ( ! array_key_exists( '_rest', $args ) && method_exists( $this, 'restapi_get_route' ) )
			$args['_rest'] = $this->restapi_get_route();

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( $this->hook() );

		gEditorial()->enqueue_asset_config( $args, $key );

		return $handle;
	}

	// CAUTION: front only
	// NOTE: combined global styles
	// TODO: also we need API for module specified CSS
	public function enqueue_styles()
	{
		gEditorial()->enqueue_styles();
	}

	public function register_editor_button( string $plugin, $level = NULL, $settings_key = NULL )
	{
		if ( ! $this->get_setting( $settings_key ?? 'editor_button', TRUE ) )
			return FALSE;

		return Services\ClassicEditor::registerButton(
			$this->hook( $plugin ),
			sprintf( 'assets/js/tinymce/%s.%s',
				$this->module->name,
				$plugin
			),
			$level
		);
	}
}

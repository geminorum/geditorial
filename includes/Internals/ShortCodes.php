<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait ShortCodes
{
	protected function register_shortcode( $constant, $callback = NULL, $force = FALSE )
	{
		if ( ! $force && ! $this->get_setting( 'shortcode_support', FALSE ) )
			return;

		if ( is_null( $callback ) && method_exists( $this, $constant ) )
			$callback = [ $this, $constant ];

		$shortcode = $this->constant( $constant );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( $this->hook_base( 'shortcode', $shortcode ), $callback, 10, 3 );
	}
}

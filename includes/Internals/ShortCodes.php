<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait ShortCodes
{
	protected function register_shortcode( $constant, $force = FALSE, $aliases = NULL, $callback = NULL )
	{
		if ( ! $force && ! $this->get_setting( 'shortcode_support', FALSE ) )
			return FALSE;

		if ( is_null( $callback ) && method_exists( $this, $constant ) )
			$callback = [ $this, $constant ];

		$shortcode = $this->constant( $constant );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( $this->hook_base( 'shortcode', $shortcode ), $callback, 10, 3 );

		if ( empty( $aliases ) )
			return $shortcode;

		foreach ( $aliases as $alias ) {
			remove_shortcode( $alias );
			add_shortcode( $alias, $callback );
		}

		return $shortcode;
	}
}

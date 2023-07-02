<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

trait CoreMenuPage
{

	protected function _hook_wp_submenu_page( $context, $parent_slug, $page_title, $menu_title = NULL, $capability = NULL, $menu_slug = '', $callback = '', $position = NULL )
	{
		if ( ! $context )
			return FALSE;

		$default_callback = [ $this, sprintf( 'admin_%s_page', $context ) ];

		$hook = add_submenu_page(
			$parent_slug,
			$page_title,
			( is_null( $menu_title ) ? $page_title : $menu_title ),
			( is_null( $capability ) ? ( isset( $this->caps[$context] ) ? $this->caps[$context] : 'manage_options' ) : $capability ),
			( empty( $menu_slug ) ? sprintf( '%s-%s', $this->base, $context ) : $menu_slug ),
			( empty( $callback ) ? ( is_callable( $default_callback ) ? $default_callback : '' ) : $callback ),
			( is_null( $position ) ? ( isset( $this->positions[$context] ) ? $this->positions[$context] : NULL ) : $position )
		);

		if ( $hook )
			add_action( 'load-'.$hook, [ $this, sprintf( 'admin_%s_load', $context ) ] );

		return $hook;
	}
}

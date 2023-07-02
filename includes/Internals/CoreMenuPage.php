<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

trait CoreMenuPage
{

	protected function _hook_menu_posttype( $constant, $parent_slug = 'index.php', $context = 'adminpage' )
	{
		if ( ! $posttype = get_post_type_object( $this->constant( $constant ) ) )
			return FALSE;

		return add_submenu_page(
			$parent_slug,
			Core\HTML::escape( $this->get_string( 'page_title', $constant, $context, $posttype->labels->all_items ) ),
			Core\HTML::escape( $this->get_string( 'menu_title', $constant, $context, $posttype->labels->menu_name ) ),
			$posttype->cap->edit_posts,
			'edit.php?post_type='.$posttype->name
		);
	}

	// $parent_slug options: `options-general.php`, `users.php`
	// also set `$this->filter_string( 'parent_file', $parent_slug );`
	protected function _hook_menu_taxonomy( $constant, $parent_slug = 'index.php', $context = 'submenu' )
	{
		if ( ! $taxonomy = get_taxonomy( $this->constant( $constant ) ) )
			return FALSE;

		return add_submenu_page(
			$parent_slug,
			Core\HTML::escape( $this->get_string( 'page_title', $constant, $context, $taxonomy->labels->name ) ),
			Core\HTML::escape( $this->get_string( 'menu_title', $constant, $context, $taxonomy->labels->menu_name ) ),
			$taxonomy->cap->manage_terms,
			'edit-tags.php?taxonomy='.$taxonomy->name
		);
	}

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

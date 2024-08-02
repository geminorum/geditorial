<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait CoreRowActions
{

	protected function rowactions__hook_admin_bulkactions( $screen, $cap_check = NULL )
	{
		if ( ! $this->get_setting( 'admin_bulkactions' ) )
			return;

		if ( FALSE === $cap_check )
			return;

		if ( TRUE !== $cap_check && ! WordPress\PostType::can( $screen->post_type, is_null( $cap_check ) ? 'edit_posts' : $cap_check ) )
			return;

		add_filter( 'bulk_actions-'.$screen->id, [ $this, 'rowactions_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'rowactions_handle_bulk_actions' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'rowactions_admin_notices' ] );
	}

	// EXAMPLE CALLBACK
	// public function rowactions_bulk_actions( $actions ) {}
	// public function rowactions_handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {}
	// public function rowactions_admin_notices() {}

	protected function rowactions__hook_mainlink_for_post( $posttype = NULL, $priority = 10, $callback_suffix = FALSE, $prepend = FALSE, $action_key = NULL, $setting_key = 'admin_rowactions' )
	{
		if ( FALSE === $setting_key )
			return FALSE;

		if ( TRUE !== $setting_key && ! $this->get_setting( $setting_key ) )
			return FALSE;

		$method = $callback_suffix ? sprintf( 'rowaction_get_mainlink_for_post_%s', $callback_suffix ) : 'rowaction_get_mainlink_for_post';

		if ( ! method_exists( $this, $method ) )
			return $this->log( 'NOTICE', sprintf( 'MISSING CALLBACK: %s', $method.'()' ) );

		$callback = function ( $actions, $post ) use ( $posttype, $prepend, $action_key, $method ) {

			if ( ! is_null( $posttype ) && $post->post_type !== $posttype )
				return $actions;

			if ( in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
				return $actions;

			if ( ! $links = call_user_func_array( [$this, $method ], [ $post ] ) )
				return $actions;

			if ( is_array( $links ) )
				return $prepend ? array_merge( $links, $actions ) : array_merge( $actions, $links );

			return $prepend
				? array_merge( [ $action_key ?? $this->classs() => $links ], $actions )
				: array_merge( $actions, [ $action_key ?? $this->classs() => $links ] );
		};

		add_filter( 'page_row_actions', $callback, $priority, 2 );
		add_filter( 'post_row_actions', $callback, $priority, 2 );

		return TRUE;
	}

	// EXAMPLE CALLBACK
	// protected function rowaction_get_mainlink_for_post( $post ) { return ''; }

	protected function rowactions__hook_mainlink_for_term( $taxonomy = NULL, $priority = 10, $action_key = NULL, $setting_key = 'admin_rowactions' )
	{
		if ( FALSE === $setting_key )
			return FALSE;

		if ( TRUE !== $setting_key && ! $this->get_setting( $setting_key ) )
			return FALSE;

		if ( ! method_exists( $this, 'rowaction_get_mainlink_for_term' ) )
			return $this->log( 'NOTICE', sprintf( 'MISSING CALLBACK: %s', 'rowaction_get_mainlink_for_term()' ) );

		$callback = function ( $actions, $term ) use ( $taxonomy, $action_key ) {

			if ( ! is_null( $taxonomy ) && $term->taxonomy !== $taxonomy )
				return $actions;

			if ( ! $links = $this->rowaction_get_mainlink_for_term( $term ) )
				return $actions;

			if ( is_array( $links ) )
				return array_merge( $actions, $links );

			return array_merge( $actions, [
				$action_key ?? $this->classs() => $links,
			] );
		};

		add_filter( 'tag_row_actions', $callback, $priority, 2 );

		return TRUE;
	}

	// EXAMPLE CALLBACK
	// protected function rowaction_get_mainlink_for_term( $term ) { return ''; }
}

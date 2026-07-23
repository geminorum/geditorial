<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreHooks
{

	// NOTE: adds the `{$module_key}-enabled` class to body in admin
	public function _admin_enabled( array $extra = [] ): bool
	{
		return add_filter( 'admin_body_class',
			function ( $classes ) use ( $extra ) {
				return trim( $classes ).' '.Core\HTML::prepClass( $this->classs( 'enabled' ), $extra );
			} );
	}

	protected function _hook_store_metabox( string $posttype, string $prefix = '' ): bool
	{
		if ( ! $posttype )
			return FALSE;

		return add_action( self::und( 'save_post', $posttype ),
			[ $this, self::und( 'store_metabox', $prefix ) ],
			20,
			3
		);
	}

	protected function class_metabox( object $screen, ?string $context = NULL ): bool
	{
		$context = $context ?? 'mainbox';

		return add_filter( self::und( 'postbox_classes', $screen->id, $this->classs( $context ) ),
			function ( $classes )
				use ( $context ) {
				return Core\Arraay::prepString( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-'.$this->key,
					'-'.$this->key.'-'.$context,
				] );
			} );
	}

	public function is_save_post(
		object $post,
		false|string|array $constant = FALSE,
	): bool {

		if ( $constant ) {

			if ( is_array( $constant ) && ! in_array( $post->post_type, $constant, TRUE ) )
				return FALSE;

			if ( ! is_array( $constant ) && $post->post_type != $this->constant( $constant ) )
				return FALSE;
		}

		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) )
			return FALSE;

		return TRUE;
	}

	// NOTE: for Ajax calls on quick-edit
	public function is_inline_save_posttype(
		false|string|array $target = FALSE,
		?array $request = NULL,
		string $key = 'post_type',
	): bool|string {

		if ( ! WordPress\IsIt::ajaxAdmin() )
			return FALSE;

		$request = $request ?? $_REQUEST;

		if ( empty( $request['bulk_edit'] )
			&& ( empty( $request['action'] ) || 'inline-save' != $request['action'] ) )
				return FALSE;

		if ( empty( $request[$key] ) )
			return FALSE;

		if ( is_array( $target )
			&& ! in_array( $request[$key], $target, TRUE ) )
				return FALSE;

		if ( $target
			&& ! is_array( $target )
			&& $request[$key] != $this->constant( $target ) )
				return FALSE;

		return $request[$key];
	}

	// NOTE: for Ajax calls on quick-edit
	public function is_inline_save_taxonomy(
		false|string|array $target = FALSE,
		?array $request = NULL,
		string $key = 'taxonomy',
	): bool|string {

		if ( ! WordPress\IsIt::ajaxAdmin() )
			return FALSE;

		if ( is_null( $request ) )
			$request = $_REQUEST;

		if ( empty( $request['action'] )
			|| ! in_array( $request['action'], [ 'add-tag', 'inline-save-tax' ], TRUE ) )
				return FALSE;

		if ( empty( $request[$key] ) )
			return FALSE;

		if ( is_array( $target )
			&& ! in_array( $request[$key], $target, TRUE ) )
				return FALSE;

		if ( $target
			&& ! is_array( $target )
			&& $request[$key] != $this->constant( $target ) )
				return FALSE;

		return $request[$key];
	}

	/**
	 * Filters a module hook if second parameter is equal to value of given constant.
	 *
	 * @param string|null $module
	 * @param string $hook
	 * @param mixed $override
	 * @param string $target
	 * @param int|null $priority
	 * @return true
	 */
	protected function filter_module_i2c(
		?string $module,
		string $hook,
		mixed $override,
		string $target,
		?int $priority = NULL,
	): true {

		return $this->filter_if_2_set_1(
			$this->hook_base( $module ?? $this->key, $hook ),
			$override,
			$this->constant( $target, $target ),
			$priority ?? 10,
		);
	}
}

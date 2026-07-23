<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PostMeta
{
	public function get_postmeta_key( string $field, ?string $prefix = NULL ): string
	{
		return sprintf( '_%s_%s', $prefix ?? $this->key, $field );
	}

	public function get_postmeta_field( false|int $post_id, string $field, mixed $default = FALSE, ?string $prefix = NULL, ?string $meta_key = NULL ): mixed
	{
		if ( ! $post_id )
			return $default;

		$legacy = $this->get_postmeta_legacy( $post_id, [], $meta_key );

		foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key ) {

			if ( $data = $this->fetch_postmeta( $post_id, $default, $this->get_postmeta_key( $field_key, $prefix ) ) )
				return $data;

			if ( is_array( $legacy ) && array_key_exists( $field_key, $legacy ) )
				return $legacy[$field_key];
		}

		return $default;
	}

	public function set_postmeta_field( int $post_id, string $field, mixed $data, ?string $prefix = NULL ): bool
	{
		if ( ! $this->store_postmeta( $post_id, $data, $this->get_postmeta_key( $field, $prefix ) ) )
			return FALSE;

		// Tries to clean-up old field keys, upon changing in the future
		foreach ( $this->sanitize_postmeta_field_key( $field ) as $offset => $field_key )
			if ( $offset ) // skips the current key!
				delete_post_meta( $post_id, $this->get_postmeta_key( $field_key, $prefix ) );

		return TRUE;
	}

	// Fetch module meta array
	// back-comp only
	public function get_postmeta_legacy( int $post_id, array $default = [], ?string $meta_key = NULL ): mixed
	{
		global $gEditorialPostMetaLegacy;

		$meta_key = $meta_key ?? $this->meta_key;

		if ( ! isset( $gEditorialPostMetaLegacy[$post_id][$meta_key] ) )
			$gEditorialPostMetaLegacy[$post_id][$meta_key] = $this->fetch_postmeta( $post_id, $default, $meta_key );

		return $gEditorialPostMetaLegacy[$post_id][$meta_key];
	}

	public function clean_postmeta_legacy( int $post_id, array $fields, ?array $legacy = NULL, ?string $meta_key = NULL ): bool
	{
		global $gEditorialPostMetaLegacy;

		$meta_key = $meta_key ?? $this->meta_key;
		$legacy   = $legacy   ?? $this->get_postmeta_legacy( $post_id, [], $meta_key );

		foreach ( $fields as $field => $args )
			foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key )
				if ( array_key_exists( $field_key, $legacy ) )
					unset( $legacy[$field_key] );

		unset( $gEditorialPostMetaLegacy[$post_id][$meta_key] );

		return $this->store_postmeta( $post_id, array_filter( $legacy ), $meta_key );
	}

	public function sanitize_postmeta_field_key_map(): array
	{
		return [];
	}

	public function sanitize_postmeta_field_key( string|array $field_key ): array
	{
		if ( is_array( $field_key ) )
			return $field_key;

		$key_map = $this->sanitize_postmeta_field_key_map();

		if ( isset( $key_map[$field_key] ) )
			return $key_map[$field_key];

		return [ $field_key ];
	}

	public function store_postmeta( int $post_id, mixed $data, ?string $meta_key = NULL ): bool
	{
		$meta_key = $meta_key ?? $this->meta_key; // back-comp

		if ( empty( $data ) )
			return delete_post_meta( $post_id, $meta_key );

		return (bool) update_post_meta( $post_id, $meta_key, $data );
	}

	public function fetch_postmeta( int $post_id, mixed $default = '', ?string $meta_key = NULL ): mixed
	{
		if ( ! $post_id )
			return $default;

		$meta_key = $meta_key ?? $this->meta_key; // back-comp
		$data     = get_metadata( 'post', $post_id, $meta_key, TRUE );

		return $data ?: $default;
	}

	protected function postmeta__hook_meta_column_row(
		string $posttype,
		true|array $fields,
		?int $priority = NULL,
		string $callback_suffix = '',
		?string $module = NULL,
	): bool {

		if ( ! $posttype || empty( $fields ) )
			return FALSE;

		$module = $module ?? 'meta';

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		$method = $callback_suffix
			? self::und( $module, 'column_row', $callback_suffix )
			: 'general_column_row'; // self::und( $module, 'column_row' );

		if ( ! method_exists( $this, $method ) )
			return FALSE;

		$target = TRUE !== $fields
			? Core\Arraay::keepByKeys( Services\PostTypeFields::getEnabled( $posttype, $module ), $fields )
			: TRUE;

		if ( empty( $target ) )
			return TRUE;

		add_action( $this->hook_base( $module, 'column_row', $posttype ),
			function ( $post, $before, $after, $module, $fields, $excludes ) use ( $method, $target ) {
				call_user_func_array(
					[ $this, $method ],
					[ $post, $before, $after, $module, ( $target === TRUE ? $fields : $target ), $excludes ]
				);
			}, $priority ?? 20, 6 );

		return TRUE;
	}

	// DEFAULT FILTER
	// NOTE: used when module defines `_supported` meta fields
	public function general_column_row( object $post, string $before, string $after, string $module, array $fields, array $excludes ): void
	{
		foreach ( $fields as $field_key => $field ) {

			if ( in_array( $field_key, $excludes ) )
				continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key, FALSE, $module ) )
				continue;

			printf( $before, sprintf( '-%s -%s-%s', $this->module->name, $module, $field_key ) );
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo $after;
		}
	}
}

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PostMeta
{
	public function get_postmeta_key( $field, $prefix = NULL )
	{
		return sprintf( '_%s_%s', $prefix ?? $this->key, $field );
	}

	public function get_postmeta_field( $post_id, $field, $default = FALSE, $prefix = NULL, $metakey = NULL )
	{
		if ( ! $post_id )
			return $default;

		if ( is_null( $prefix ) )
			$prefix = $this->key;

		$legacy = $this->get_postmeta_legacy( $post_id, [], $metakey );

		foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key ) {

			if ( $data = $this->fetch_postmeta( $post_id, $default, $this->get_postmeta_key( $field_key, $prefix ) ) )
				return $data;

			if ( is_array( $legacy ) && array_key_exists( $field_key, $legacy ) )
				return $legacy[$field_key];
		}

		return $default;
	}

	public function set_postmeta_field( $post_id, $field, $data, $prefix = NULL )
	{
		if ( is_null( $prefix ) )
			$prefix = $this->key;

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
	public function get_postmeta_legacy( $post_id, $default = [], $metakey = NULL )
	{
		global $gEditorialPostMetaLegacy;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key;

		if ( ! isset( $gEditorialPostMetaLegacy[$post_id][$metakey] ) )
			$gEditorialPostMetaLegacy[$post_id][$metakey] = $this->fetch_postmeta( $post_id, $default, $metakey );

		return $gEditorialPostMetaLegacy[$post_id][$metakey];
	}

	public function clean_postmeta_legacy( $post_id, $fields, $legacy = NULL, $metakey = NULL )
	{
		global $gEditorialPostMetaLegacy;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key;

		if ( is_null( $legacy ) )
			$legacy = $this->get_postmeta_legacy( $post_id, [], $metakey );

		foreach ( $fields as $field => $args )
			foreach ( $this->sanitize_postmeta_field_key( $field ) as $field_key )
				if ( array_key_exists( $field_key, $legacy ) )
					unset( $legacy[$field_key] );

		unset( $gEditorialPostMetaLegacy[$post_id][$metakey] );

		return $this->store_postmeta( $post_id, array_filter( $legacy ), $metakey );
	}

	public function sanitize_postmeta_field_key_map()
	{
		return [];
	}

	public function sanitize_postmeta_field_key( $field_key )
	{
		if ( is_array( $field_key ) )
			return $field_key;

		$key_map = $this->sanitize_postmeta_field_key_map();

		if ( isset( $key_map[$field_key] ) )
			return $key_map[$field_key];

		return [ $field_key ];
	}

	public function store_postmeta( $post_id, $data, $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$metakey = $this->meta_key; // back-comp

		if ( empty( $data ) )
			return delete_post_meta( $post_id, $metakey );

		return (bool) update_post_meta( $post_id, $metakey, $data );
	}

	public function fetch_postmeta( $post_id, $default = '', $metakey = NULL )
	{
		if ( ! $post_id )
			return $default;

		if ( is_null( $metakey ) )
			$metakey = $this->meta_key; // back-comp

		$data = get_metadata( 'post', $post_id, $metakey, TRUE );

		return $data ?: $default;
	}

	protected function postmeta__hook_meta_column_row( $posttype, $fields, $priority = 20, $callback_suffix = FALSE, $module = NULL )
	{
		if ( ! $posttype || empty( $fields ) )
			return FALSE;

		if ( is_null( $module ) )
			$module = 'meta';

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		$method = $callback_suffix
			? sprintf( '%s_column_row_%s', $module, $callback_suffix )
			: 'general_column_row'; // sprintf( '%s_column_row', $module );

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
			}, $priority, 6 );

		return TRUE;
	}

	// DEFAULT FILTER
	// NOTE: used when module defines `_supported` meta fields
	public function general_column_row( $post, $before, $after, $module, $fields, $excludes )
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

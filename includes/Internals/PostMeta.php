<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait PostMeta
{

	public function get_postmeta_key( $field, $prefix = NULL )
	{
		return sprintf( '_%s_%s', ( is_null( $prefix ) ? $this->key : $prefix ), $field );
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

		// tries to cleanup old field keys, upon changing in the future
		foreach ( $this->sanitize_postmeta_field_key( $field ) as $offset => $field_key )
			if ( $offset ) // skips the current key!
				delete_post_meta( $post_id, $this->get_postmeta_key( $field_key, $prefix ) );

		return TRUE;
	}

	// fetch module meta array
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

	public function sanitize_postmeta_field_key( $field_key )
	{
		return (array) $field_key;
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
		if ( is_null( $metakey ) )
			$metakey = $this->meta_key; // back-comp

		$data = get_metadata( 'post', $post_id, $metakey, TRUE );

		return $data ?: $default;
	}
}
<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class PostTypeFields extends WordPress\Main
{

	const BASE = 'geditorial';

	/**
	 * Retrieves the post meta-key for given field.
	 *
	 * @param  string $field_key
	 * @param  string $module
	 * @return string $meta_key
	 */
	public static function getPostMetaKey( $field_key, $module = 'meta' )
	{
		if ( ! $field_key )
			return FALSE;

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		return gEditorial()->module( $module )->get_postmeta_key( $field_key );
	}

	/**
	 * Checks the availability of posttype field for given posttype via certain module.
	 * OLD: `Helper::isPostTypeFieldAvailable()`
	 *
	 * @param  string $field_key
	 * @param  string $posttype
	 * @param  string $module
	 * @return mixed  $available
	 */
	public static function isAvailable( $field_key, $posttype, $module = 'meta' )
	{
		if ( ! $posttype || ! $field_key )
			return FALSE;

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		return gEditorial()->module( $module )->get_posttype_field_args( $field_key, $posttype );
	}

	/**
	 * Retrieves the export title of field for given posttype via certain module.
	 *
	 * @param  string $field_key
	 * @param  string $posttype
	 * @param  string $module
	 * @return string $export_title
	 */
	public static function getExportTitle( $field_key, $posttype, $module = 'meta' )
	{
		if ( ! $posttype )
			return $field_key;

		if ( ! gEditorial()->enabled( $module ) )
			return $field_key;

		return gEditorial()->module( $module )->get_posttype_field_export_title( $field_key, $posttype );
	}

	/**
	 * Retrieves the supported post-types given a field key via certain module.
	 * OLD: `Helper::getPostTypeFieldSupported()`
	 *
	 * @param  string $field_key
	 * @param  string $module
	 * @return array  $supported
	 */
	public static function getSupported( $field_key, $module = 'meta' )
	{
		if ( ! $field_key )
			return [];

		if ( ! gEditorial()->enabled( $module ) )
			return [];

		return gEditorial()->module( $module )->get_posttype_field_supported( $field_key );
	}

	/**
	 * Retrieves the enabled post-type fields given a post-type via certain module.
	 *
	 * @param  string $posttype
	 * @param  string $module
	 * @param  array  $filter
	 * @param  string $operator
	 * @return array  $enabled
	 */
	public static function getEnabled( $posttype, $module = 'meta', $filter = [], $operator = 'AND' )
	{
		if ( ! $posttype )
			return [];

		if ( ! gEditorial()->enabled( $module ) )
			return [];

		return gEditorial()->module( $module )->get_posttype_fields( $posttype, $filter, $operator );
	}

	/**
	 * Retrieves the post ID by field-key given a value via certain module.
	 *
	 * OLD: `posttypefields_get_post_by()`
	 *
	 * @param  string   $field_key
	 * @param  string   $value
	 * @param  string   $posttype
	 * @param  bool     $sanitize
	 * @param  string   $module
	 * @return bool|int $post_id
	 */
	public static function getPostByField( $field_key, $value, $posttype, $sanitize = FALSE, $module = 'meta' )
	{
		if ( ! $field_key || ! $value || ! $posttype )
			return FALSE;

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		$metakey = gEditorial()->module( $module )->get_postmeta_key( $field_key );

		if ( $sanitize ) {

			if ( ! $field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $posttype ) )
				$value = Core\Number::translate( trim( $value ) );

			else
				$value = gEditorial()->module( $module )->sanitize_posttype_field( $value, $field );

			if ( ! $value )
				return FALSE;
		}

		if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $value, FALSE ) )
			foreach ( $matches as $match )
				if ( $posttype === get_post_type( intval( $match ) ) )
					return intval( $match );

		return FALSE;
	}
}

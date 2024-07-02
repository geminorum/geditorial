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
}

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

	/**
	 * Retrieves the default icon given a field arguments and post-type.
	 * @old: `get_posttype_field_icon()`
	 *
	 * @param  string       $field_key
	 * @param  array        $args
	 * @param  string|null  $posttype
	 * @return string|array $icon
	 */
	public static function getFieldIcon( $field_key, $args = [], $posttype = NULL )
	{
		if ( ! empty( $args['icon'] ) )
			return $args['icon'];

		switch ( $field_key ) {
			case 'over_title' : return 'arrow-up-alt2';
			case 'sub_title'  : return 'arrow-down-alt2';
			case 'highlight'  : return 'pressthis';
			case 'byline'     : return 'admin-users';
			case 'published'  : return 'calendar-alt';
			case 'lead'       : return 'editor-paragraph';
			case 'label'      : return 'megaphone';
			case 'notes'      : return 'text-page';
			case 'itineraries': return 'editor-ul';
		}

		if ( ! empty( $args['type'] ) ) {
			switch ( $args['type'] ) {
				case 'email'   : return 'email';
				case 'phone'   : return 'phone';
				case 'mobile'  : return 'smartphone';
				case 'identity': return 'id-alt';
				case 'iban'    : return 'bank';
				case 'isbn'    : return 'book';
				case 'date'    : return 'calendar';
				case 'datetime': return 'calendar-alt';
				case 'duration': return 'clock';
				case 'day'     : return 'backup';
				case 'hour'    : return 'clock';
				case 'people'  : return 'groups';
				case 'address' : return 'location';
				case 'venue'   : return 'location-alt';
				case 'embed'   : return 'embed-generic';
				case 'link'    : return 'admin-links';
			}
		}

		return 'admin-post';
	}
}

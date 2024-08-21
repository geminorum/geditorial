<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Template;
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

	// OLD: `Template::getMetaField()`
	public static function getField( $field_key, $atts = [], $check = TRUE, $module = 'meta' )
	{
		$field = FALSE;
		$args  = self::atts( [
			'id'       => NULL,
			'fallback' => FALSE,
			'default'  => FALSE,
			'noaccess' => NULL, // returns upon no access, `NULL` for `default` arg
			'context'  => 'view', // for access checks // `FALSE` to disable checks
			'filter'   => FALSE, // or `__do_embed_shortcode`
			'trim'     => FALSE, // or number of chars
			'before'   => '',
			'after'    => '',
		], $atts );

		// NOTE: may come from posttype field args
		if ( is_null( $args['default'] ) )
			$args['default'] = '';

		if ( empty( $field_key ) )
			return $args['default'];

		if ( $check && ! gEditorial()->enabled( $module ) )
			return $args['default'];

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		if ( is_array( $field_key ) ) {

			if ( empty( $field_key['name'] ) )
				return $args['default'];

			$field     = $field_key;
			$field_key = $field['name'];
		}

		$meta = $raw = self::getFieldRaw( $field_key, $post->ID, $module );

		if ( FALSE === $meta && $args['fallback'] )
			return self::getField( $args['fallback'], array_merge( $atts, [ 'fallback' => FALSE ] ), FALSE );

		if ( empty( $field ) )
			$field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $post->post_type );

		// NOTE: field maybe disabled or overrided
		if ( FALSE === $field )
			$field = [ 'name' => $field_key, 'type' => 'text' ];

		if ( FALSE === $meta )
			$meta = apply_filters( static::BASE.'_meta_field_empty', $meta, $field_key, $post, $args, $raw, $field, $args['context'] );

		if ( FALSE === $meta )
			return $args['default'];

		if ( FALSE !== $args['context'] ) {

			$access = gEditorial()->module( $module )->access_posttype_field( $field, $post, $args['context'] );

			if ( ! $access )
				return is_null( $args['noaccess'] ) ? $args['default'] : $args['noaccess'];
		}

		$meta = apply_filters( static::BASE.'_meta_field', $meta, $field_key, $post, $args, $raw, $field, $args['context'] );
		$meta = apply_filters( static::BASE.'_meta_field_'.$field_key, $meta, $field_key, $post, $args, $raw, $field, $args['context'] );

		if ( '__do_embed_shortcode' === $args['filter'] )
			$args['filter'] = [ Template::class, 'doEmbedShortCode' ];

		if ( $args['filter'] && is_callable( $args['filter'] ) )
			$meta = call_user_func( $args['filter'], $meta );

		if ( $meta )
			return $args['before'].( $args['trim'] ? WordPress\Strings::trimChars( $meta, $args['trim'] ) : $meta ).$args['after'];

		return $args['default'];
	}

	// OLD: `Template::getMetaFieldRaw()`
	// NOTE: does not check for `access_view` arg
	public static function getFieldRaw( $field_key, $post_id, $module = 'meta', $check = FALSE, $default = FALSE )
	{
		if ( $check ) {

			if ( ! gEditorial()->enabled( $module ) )
				return FALSE;

			if ( ! $post = WordPress\Post::get( $post_id ) )
				return FALSE;

			$post_id = $post->ID;
		}

		$meta = gEditorial()->{$module}->get_postmeta_field( $post_id, $field_key, $default );

		return apply_filters( static::BASE.'_get_meta_field', $meta, $field_key, $post_id, $module, $default );
	}
}

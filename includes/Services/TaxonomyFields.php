<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class TaxonomyFields extends gEditorial\Service
{

	public static function getDefaultCalendar( $module = 'terms', $check = TRUE )
	{
		if ( $check && ! gEditorial()->enabled( $module ) )
			return Core\L10n::calendar();

		return gEditorial()->module( $module )->default_calendar();
	}

	/**
	 * Retrieves the term meta-key for given field.
	 * TODO: rename to `getMetaKey`
	 *
	 * @param string $field_key
	 * @param string $taxonomy
	 * @param string $module
	 * @param bool $check
	 * @return string $meta_key
	 */
	public static function getTermMetaKey( $field_key, $taxonomy = NULL, $module = 'terms', $check = TRUE )
	{
		if ( ! $field_key )
			return FALSE;

		if ( 'image' === $field_key && $taxonomy
			&& in_array( $taxonomy, WordPress\WooCommerce::PRODUCT_TAXONOMIES, TRUE ) )
			return WordPress\WooCommerce::TERM_IMAGE_METAKEY;

		if ( $check && ! gEditorial()->enabled( $module ) )
			return FALSE;

		return gEditorial()->module( $module )->get_supported_metakey( $field_key, $taxonomy );
	}

	public static function getField( $field_key, $atts = [], $check = TRUE, $module = 'terms' )
	{
		$field = FALSE;
		$args  = self::atts( [
			'id'       => NULL,
			'fallback' => FALSE,
			'default'  => FALSE,
			'noaccess' => NULL,     // returns upon no access, `NULL` for `default` argument
			'context'  => 'view',   // access checks, `FALSE` to disable checks
			'filter'   => FALSE,    // or `__do_embed_shortcode`
			'prefix'   => FALSE,    // prefix the value with field prop
			'trim'     => FALSE,    // or number of chars
			'before'   => '',
			'after'    => '',
		], $atts );

		// NOTE: may come from taxonomy field argument
		if ( is_null( $args['default'] ) )
			$args['default'] = '';

		if ( empty( $field_key ) )
			return $args['default'];

		if ( $check && ! gEditorial()->enabled( $module ) )
			return $args['default'];

		if ( ! $term = WordPress\Term::get( $args['id'] ) )
			return $args['default'];

		if ( is_array( $field_key ) ) {

			if ( empty( $field_key['name'] ) )
				return $args['default'];

			$field     = $field_key;
			$field_key = $field['name'];
		}

		$meta = $raw = self::getFieldRaw( $field_key, $term->term_id, $module );

		if ( FALSE === $meta && $args['fallback'] )
			return self::getField( $args['fallback'], array_merge( $atts, [ 'fallback' => FALSE ] ), FALSE );

		// if ( empty( $field ) )
		// 	$field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $post->post_type );
		$field = FALSE; // TODO

		// NOTE: field may be disabled or overridden
		if ( FALSE === $field )
			$field = [ 'name' => $field_key, 'type' => 'text' ];

		if ( FALSE === $meta )
			$meta = apply_filters( static::BASE.'_terms_field_empty', $meta, $field_key, $term, $args, $raw, $field, $args['context'], $module );

		if ( FALSE === $meta )
			return $args['default'];

		if ( FALSE !== $args['context'] ) {

			// $access = gEditorial()->module( $module )->access_posttype_field( $field, $term, $args['context'] );
			$access = TRUE; // TODO

			if ( ! $access )
				return is_null( $args['noaccess'] ) ? $args['default'] : $args['noaccess'];
		}

		$meta = apply_filters( static::BASE.'_terms_field', $meta, $field_key, $term, $args, $raw, $field, $args['context'], $module );
		$meta = apply_filters( static::BASE.'_terms_field_'.$field_key, $meta, $field_key, $term, $args, $raw, $field, $args['context'], $module );

		if ( '__do_embed_shortcode' === $args['filter'] )
			$args['filter'] = [ gEditorial\Template::class, 'doEmbedShortCode' ];

		if ( $args['filter'] && is_callable( $args['filter'] ) )
			$meta = call_user_func( $args['filter'], $meta );

		if ( $args['prefix'] )
			$meta = sprintf( '%s: %s', isset( $field[$args['prefix']] ) ? $field[$args['prefix']] : $args['prefix'], $meta );

		if ( $meta )
			return $args['before'].( $args['trim'] ? WordPress\Strings::trimChars( $meta, $args['trim'] ) : $meta ).$args['after'];

		return $args['default'];
	}

	public static function getFieldRaw( $field_key, $term_id, $module = 'terms', $check = FALSE, $default = FALSE )
	{
		if ( $check ) {

			if ( ! gEditorial()->enabled( $module ) )
				return $default;

			if ( ! $term = WordPress\Post::get( $term_id ) )
				return $default;

			$term_id = $term->term_id;
		}

		$metakey = self::getTermMetaKey( $field_key, ( empty( $term ) ? NULL : $term->taxonomy ), $module, FALSE );

		if ( FALSE === ( $data = get_term_meta( $term_id, $metakey, TRUE ) ) )
			$data = $default;

		return apply_filters( static::BASE.'_get_terms_field', $data, $field_key, $term_id, $module, $default );
	}

	public static function getFieldDate( $field_key, $term_id, $module = 'terms', $check = TRUE, $default = FALSE, $default_calendar = NULL )
	{
		if ( ! $date = self::getFieldRaw( $field_key, $term_id, $module, $check, $default ) )
			return $default;

		if ( ! $datetime = gEditorial\Datetime::prepForMySQL( $date, NULL, $default_calendar ?? self::getDefaultCalendar( $module, FALSE ) ) )
			return $default;

		return Core\Date::getObject( $datetime );
	}
}

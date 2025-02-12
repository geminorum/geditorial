<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Main extends Core\Base
{

	// TODO: make this a trait

	const BASE   = '';
	const MODULE = FALSE;

	public static function setup() {}

	protected static function factory()
	{
		return gEditorial();
	}

	protected static function classs()
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			if ( $arg )
				$string.= '-'.strtolower( Core\Text::sanitizeBase( $arg ) );

		if ( static::MODULE )
			$string = static::MODULE.$string;

		if ( static::BASE )
			$string = static::BASE.$string;

		return trim( $string, '-' );
	}

	protected static function hash()
	{
		$string = '';

		foreach ( func_get_args() as $arg )
			$string.= maybe_serialize( $arg );

		if ( static::MODULE )
			$string = static::MODULE.$string;

		if ( static::BASE )
			$string = static::BASE.$string;

		return md5( $string );
	}

	protected static function constant( $key, $default = FALSE, $module = NULL )
	{
		if ( is_null( $module ) )
			$module = static::MODULE;

		return self::factory()->constant( $module, $key, $default );
	}

	protected static function filters( $hook, ...$args )
	{
		return apply_filters( sprintf( '%s_%s_%s',
			static::BASE,
			static::MODULE,
			$hook
		), ...$args );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE, $module = NULL )
	{
		if ( is_null( $module ) )
			$module = static::MODULE;

		return self::factory()->module( $module )->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = [], $metakey = NULL, $module = NULL )
	{
		if ( is_null( $module ) )
			$module = static::MODULE;

		return FALSE === $field
			? self::factory()->module( $module )->get_postmeta_legacy( $post_id, $default )
			: self::factory()->module( $module )->get_postmeta_field( $post_id, $field, $default, $metakey );
	}

	// FIXME: WTF?!
	protected static function post_types( $posttypes = NULL, $module = NULL )
	{
		if ( is_null( $module ) )
			$module = static::MODULE;

		// if ( static::MODULE && self::factory()->enabled( static::MODULE ) )
			return self::factory()->module( $module )->posttypes( $posttypes );

		return [];
	}
}

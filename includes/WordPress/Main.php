<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Main extends Core\Base
{

	// TODO: make this a trait

	const BASE   = '';
	const MODULE = FALSE;

	protected static function factory()
	{
		return gEditorial();
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

	protected static function constant( $key, $default = FALSE )
	{
		return self::factory()->constant( static::MODULE, $key, $default );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE )
	{
		return self::factory()->{static::MODULE}->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = [], $metakey = NULL )
	{
		return FALSE === $field
			? self::factory()->{static::MODULE}->get_postmeta_legacy( $post_id, $default )
			: self::factory()->{static::MODULE}->get_postmeta_field( $post_id, $field, $default, $metakey );
	}

	// FIXME: WTF?!
	protected static function post_types( $posttypes = NULL )
	{
		// if ( static::MODULE && self::factory()->enabled( static::MODULE ) )
			return self::factory()->{static::MODULE}->posttypes( $posttypes );

		return [];
	}
}

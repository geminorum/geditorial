<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Main extends Core\Base
{

	const BASE   = '';
	const MODULE = FALSE;

	protected static function factory()
	{
		return gEditorial();
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
}

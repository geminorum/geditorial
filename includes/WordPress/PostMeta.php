<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class PostMeta extends ObjectMeta
{
	const META_TYPE       = 'post';
	const META_DB_TABLE   = 'postmeta';
	const OBJECT_ID_PROP  = 'ID';
	const TABLE_ID_COLUMN = 'post_id';

	protected static function _get( mixed $object ): mixed
	{
		return Post::get( $object );
	}
}

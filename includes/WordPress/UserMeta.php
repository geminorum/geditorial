<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;


class UserMeta extends ObjectMeta
{
	const META_TYPE       = 'user';
	const META_DB_TABLE   = 'usermeta';
	const OBJECT_ID_PROP  = 'ID';
	const TABLE_ID_COLUMN = 'user_id';

	// NOTE: `NULL` means current user
	protected static function _getObject( mixed $object ): mixed
	{
		return User::get( $object );
	}
}

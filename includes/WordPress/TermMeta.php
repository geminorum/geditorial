<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class TermMeta extends ObjectMeta
{
	const META_TYPE       = 'term';
	const META_DB_TABLE   = 'termmeta';
	const OBJECT_ID_PROP  = 'term_id';
	const TABLE_ID_COLUMN = 'term_id';

	protected static function _get( mixed $object ): mixed
	{
		return Term::get( $object );
	}
}

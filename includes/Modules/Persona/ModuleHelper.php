<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'persona';

	// TODO: support more complex naming
	public static function makeFullname( $names, $fallback = FALSE )
	{
		if ( ! $names )
			return $fallback;

		if ( empty( $names['last_name'] ) && empty( $names['first_name'] ) )
			return $fallback;

		return sprintf( '%s %s', $names['first_name'], $names['last_name'] );
	}
}

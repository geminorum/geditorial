<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'persona';

	// TODO: support more complex naming
	public static function makeFullname( $data, $fallback = FALSE )
	{
		if ( ! $data )
			return $fallback;

		$parts = self::atts( [
			'fullname'    => '',
			'first_name'  => '',
			'last_name'   => '',
			'middle_name' => '',
			'father_name' => '',
			'mother_name' => '',
		], $data );

		if ( empty( $parts['last_name'] ) && empty( $parts['first_name'] ) )
			return empty( $parts['fullname'] ) ? $fallback : $parts['fullname'];

		return Core\Text::normalizeWhitespace( vsprintf(
			/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
			_x( '%2$s, %1$s %3$s', 'Helper: Make Full-name', 'geditorial-persona' ),
			[
				$parts['first_name'],
				$parts['last_name'],
				$parts['middle_name'],
				$parts['father_name'],
				$parts['mother_name'],
			]
			), FALSE );
	}
}

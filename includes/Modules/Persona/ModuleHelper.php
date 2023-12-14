<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

use geminorum\gNetwork\Core\Orthography;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'persona';

	public static function cleanupChars( $string, $html = FALSE )
	{
		if ( empty( $string ) )
			return $string;

		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return apply_filters( 'string_format_i18n', $string );

		// return $html
		// 	? Orthography::cleanupPersianHTML( $string )
		// 	: Orthography::cleanupPersian( $string );

		return Orthography::cleanupPersianChars( $string );
	}

	// TODO: support more complex naming
	public static function makeFullname( $data, $context = 'display', $fallback = FALSE )
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
			return empty( $parts['fullname'] )
				? $fallback
				: Core\Text::normalizeWhitespace( $parts['fullname'], FALSE );

		switch ( $context ) {

			case 'import':
			case 'edit':

				$fullname = vsprintf(
					/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
					_x( '%1$s %3$s %2$s', 'Helper: Make Full-name: Edit', 'geditorial-persona' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);

				break;

			case 'export':
			case 'print':

				$fullname = vsprintf(
					/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
					_x( '%1$s %3$s %2$s', 'Helper: Make Full-name: Print', 'geditorial-persona' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);

				break;

			case 'display':
			default:

				$fullname = vsprintf(
					/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
					_x( '%2$s, %1$s %3$s', 'Helper: Make Full-name: Display', 'geditorial-persona' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);
		}

		return Core\Text::normalizeWhitespace( $fullname, FALSE );
	}
}

<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\WordPress;

class Individuals extends gEditorial\Service
{
	// TODO: support: `Byline` Module
	// TODO: support: `byline` field from meta-data

	public static function setup()
	{
		if ( is_admin() )
			return;

		add_filter( static::BASE.'_prep_individual', [ __CLASS__, 'filter_prep_individual_front' ], 5, 3 );
	}

	public static function filter_prep_individual_front( $individual, $raw, $value )
	{
		if ( $link = Core\WordPress::getSearchLink( $individual ) )
			return Core\HTML::link( $individual, $link );

		return $individual;
	}

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

		foreach ( $parts as $key => $value )
			$parts[$key] = Misc\NamesInPersian::replaceSplits( WordPress\Strings::cleanupChars( $value ) );

		if ( empty( $parts['last_name'] ) && empty( $parts['first_name'] ) )
			return empty( $parts['fullname'] )
				? $fallback
				: Core\Text::normalizeWhitespace( $parts['fullname'], FALSE );

		switch ( $context ) {

			case 'import':
			case 'edit':

				$fullname = vsprintf(
					/* translators: `%1$s`: first name, `%2$s`: last name, `%3$s`: middle name, `%4$s`: father name, `%5$s`: mother name */
					_x( '%1$s %3$s %2$s', 'Service: Individuals: Make Full-name: Edit', 'geditorial' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);

				break;

			case 'rest':
			case 'export':
			case 'print':

				$fullname = vsprintf(
					/* translators: `%1$s`: first name, `%2$s`: last name, `%3$s`: middle name, `%4$s`: father name, `%5$s`: mother name */
					_x( '%1$s %3$s %2$s', 'Service: Individuals: Make Full-name: Print', 'geditorial' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);

				break;

			case 'familyfirst':
			case 'display':
			default:

				$fullname = vsprintf(
					/* translators: `%1$s`: first name, `%2$s`: last name, `%3$s`: middle name, `%4$s`: father name, `%5$s`: mother name */
					_x( '%2$s, %1$s %3$s', 'Service: Individuals: Make Full-name: Display', 'geditorial' ),
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

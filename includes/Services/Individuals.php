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

	const FORMAT_TEMPLATE    = '%2$s, %1$s';
	const SEPARATOR_TEMPLATE = ', ';

	public static function setup()
	{
		if ( self::isParserAvailable() )
			add_filter( static::BASE.'_people_format_name', [ __CLASS__, 'filter_people_format_name' ], 9, 3 );

		if ( is_admin() )
			return;

		add_filter( static::BASE.'_prep_individual', [ __CLASS__, 'filter_prep_individual_front' ], 5, 3 );
	}

	public static function isParserAvailable()
	{
		return in_array( Core\L10n::locale( TRUE ), Misc\NamesInPersian::SUPPORTED_LOCALE, TRUE );
	}

	public static function prepPeople( $value, $empty = '', $separator = NULL )
	{
		if ( self::empty( $value ) )
			return $empty;

		$list = [];

		foreach ( Markup::getSeparated( $value ) as $individual )
			if ( $prepared = apply_filters( static::BASE.'_prep_individual', $individual, $individual, $value ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function filter_people_format_name( $formatted, $raw, $term = NULL )
	{
		// already formatted
		if ( Core\Text::has( $raw, trim( static::SEPARATOR_TEMPLATE ) ) )
			return $formatted;

		if ( ! $parsed = Misc\NamesInPersian::parseFullname( $raw ) )
			return $formatted;

		if ( WordPress\Strings::isEmpty( $parsed['first_name'] )
			|| WordPress\Strings::isEmpty( $parsed['last_name'] ) )
				return $formatted;

		return sprintf( static::FORMAT_TEMPLATE, $parsed['first_name'], $parsed['last_name'] );
	}

	public static function filter_prep_individual_front( $individual, $raw, $value )
	{
		if ( $link = WordPress\URL::search( $individual ) )
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

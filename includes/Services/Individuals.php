<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Misc;
use geminorum\gEditorial\WordPress;

class Individuals extends gEditorial\Service
{
	const FORMAT_TEMPLATE     = '%2$s, %1$s';
	const SEPARATOR_TEMPLATE  = ', ';
	const FULLNAME_DELIMITERS = [
		'/',
		'،',
		'؛',
		';',
		',',
		// '-',
		// '_',
		' - ', // with padding spaces
		'—',
		'–',
		'|',
	];

	public static function setup(): void
	{
		if ( self::isParserAvailable() ) {
			add_filter( 'nucleus_taxonomy_target_term', [ __CLASS__, 'taxonomy_target_term' ], 18, 4 );
			add_filter( self::und( static::BASE, 'people_format_name' ), [ __CLASS__, 'filter_people_format_name' ], 9, 3 );
		}

		if ( is_admin() )
			return;

		add_filter( self::und( static::BASE, 'prep_individual' ), [ __CLASS__, 'filter_prep_individual_front' ], 5, 4 );
	}

	public static function isParserAvailable(): bool
	{
		return in_array(
			Core\L10n::locale( TRUE ),
			Misc\NamesInPersian::SUPPORTED_LOCALE,
			TRUE
		);
	}

	public static function prepDateOfBirth( mixed $input, ?string $calendar_type = NULL, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'print' === $context )
			return gEditorial\Datetime::prepForDisplay(
				$data,
				gEditorial\Datetime::dateFormats( 'printdate' ),
				$calendar_type ?? Core\L10n::calendar(),
			);

		if ( 'export' === $context )
			return gEditorial\Datetime::prepForInput(
				$data,
				gEditorial\Datetime::dateFormats( 'default' ),
				$calendar_type ?? Core\L10n::calendar(),
			);

		return gEditorial\Datetime::prepDateOfBirth(
			$data,
			NULL,
			FALSE,
			$calendar_type ?? Core\L10n::calendar(),
		);
	}

	public static function prepIdentity( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'print' === $context )
			return Core\Number::localize( Core\Number::zeroise( $data, 10 ) );

		if ( 'export' === $context )
			return Core\Number::zeroise( Core\Number::translate( $data ), 10 );

		return sprintf(
			'<span class="-identity %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
			Core\Validation::isIdentityNumber( $data ) ? '-is-valid' : '-not-valid',
			$data,
			$data,
		);
	}

	public static function prepPeople( mixed $value, ?string $context = NULL, null|false|string $empty = '', ?string $separator = NULL, null|string|array $delimiters = NULL ): null|false|string
	{
		if ( self::empty( $value ) )
			return $empty;

		// @hook `geditorial_prep_individual`
		$hook = self::und( static::BASE, 'prep', 'individual' );
		$list = [];

		if ( $context && is_null( $separator ) ) {

			if ( in_array( $context, [ 'export' ] ) )
				$separator = '|';
		}

		foreach ( Markup::getSeparated( $value, $delimiters ) as $item )
			if ( $prepared = apply_filters( $hook, $item, $item, $value, $context ) )
				$list[] = $prepared;

		return WordPress\Strings::getJoined( $list, '', '', $empty, $separator );
	}

	public static function taxonomy_target_term( mixed $filtred, string $target, string $taxonomy, array $args ): mixed
	{
		// already found
		if ( ! is_null( $filtred ) )
			return $filtred;

		// already formatted
		if ( Core\Text::has( $target, trim( static::SEPARATOR_TEMPLATE ) ) )
			return $filtred;

		if ( ! $parsed = Misc\NamesInPersian::parseFullname( $target ) )
			return $filtred;

		if ( WordPress\Strings::isEmpty( $parsed['first_name'] )
			|| WordPress\Strings::isEmpty( $parsed['last_name'] ) )
				return $filtred;

		$formatted = sprintf( static::FORMAT_TEMPLATE,
			$parsed['first_name'],
			$parsed['last_name']
		);

		if ( $term = term_exists( $formatted, $taxonomy ) )
			return $term['term_id'];

		return $filtred;
	}

	public static function filter_people_format_name( string $formatted, string $raw, ?object $term = NULL ): string
	{
		// already formatted
		if ( Core\Text::has( $raw, trim( static::SEPARATOR_TEMPLATE ) ) )
			return $formatted;

		if ( ! $parsed = Misc\NamesInPersian::parseFullname( $raw ) )
			return $formatted;

		if ( WordPress\Strings::isEmpty( $parsed['first_name'] )
			|| WordPress\Strings::isEmpty( $parsed['last_name'] ) )
				return $formatted;

		return sprintf( static::FORMAT_TEMPLATE,
			$parsed['first_name'],
			$parsed['last_name']
		);
	}

	public static function filter_prep_individual_front( string $item, string $raw, mixed $value, ?string $context ): string
	{
		// late check for REST-API
		if ( WordPress\IsIt::rest() )
			return $item;

		if ( $context && in_array( $context, [ 'print', 'export' ] ) )
			return $item;

		if ( $link = WordPress\URL::search( $item ) )
			return Core\HTML::link( $item, $link );

		return $item;
	}

	public static function makeFullname( array $data, ?string $context = 'display', null|false|string $fallback = FALSE ): null|false|string
	{
		if ( ! $data )
			return $fallback;

		$parts = self::parsed( [
			'fullname'    => '',
			'first_name'  => '',
			'last_name'   => '',
			'middle_name' => '',
			'father_name' => '',
			'mother_name' => '',
		], $data );

		$fullname = '';
		$parser   = self::isParserAvailable();

		foreach ( $parts as $key => $value )
			$parts[$key] = $parser
				? Misc\NamesInPersian::replaceSplits( WordPress\Strings::cleanupChars( $value ) )
				: WordPress\Strings::cleanupChars( $value );

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

			default:
			case 'display':
			case 'address':
			case 'familyfirst':

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

		return $fullname ? Core\Text::normalizeWhitespace( $fullname, FALSE ) : $fallback;
	}
}

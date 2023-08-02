<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress\Main;

class Calendars extends Main
{
	const BASE = 'geditorial';

	public static function setup()
	{
		// add_action( '', [ __CLASS__, '' ], 9, 4 );
	}

	/**
	 * Retrieves the list of supported calendars.
	 * // @OLD: `Datetime::getDefualtCalendars()`
	 * @source https://unicode-org.github.io/icu/userguide/datetime/calendar/
	 *
	 * @param  bool  $filtered
	 * @return array $defaults
	 */
	public static function getDefualts( $filtered = FALSE )
	{
		$calendars = [
			'gregorian'     => _x( 'Gregorian', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'japanese'      => _x( 'Japanese', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'buddhist'      => _x( 'Buddhist', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'chinese'       => _x( 'Chinese', 'Datetime: Default Calendar Type', 'geditorial' ),
			'persian'       => _x( 'Persian', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'indian'        => _x( 'Indian', 'Datetime: Default Calendar Type', 'geditorial' ),
			'islamic'       => _x( 'Islamic', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'islamic-civil' => _x( 'Islamic-Civil', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'coptic'        => _x( 'Coptic', 'Datetime: Default Calendar Type', 'geditorial' ),
			// 'ethiopic'      => _x( 'Ethiopic', 'Datetime: Default Calendar Type', 'geditorial' ),
		];

		return $filtered ? apply_filters( static::BASE.'_default_calendars', $calendars ) : $calendars;
	}

	/**
	 * Sanitizes given calendar.
	 * // OLD: `Datetime::sanitizeCalendar()`
	 *
	 * @param  string $calendar
	 * @param  string $default
	 * @return string $sanitized
	 */
	public static function sanitize( $calendar, $default = 'gregorian' )
	{
		$calendars = self::getDefualts( FALSE );
		$sanitized = $calendar;

		if ( ! $calendar )
			$sanitized = $default;

		else if ( in_array( $calendar, [ 'Jalali', 'jalali', 'Persian', 'persian' ] ) )
			$sanitized = 'persian';

		else if ( in_array( $calendar, [ 'Hijri', 'hijri', 'Islamic', 'islamic' ] ) )
			$sanitized = 'islamic';

		else if ( in_array( $calendar, [ 'Gregorian', 'gregorian' ] ) )
			$sanitized = 'gregorian';

		else if ( in_array( $calendar, array_keys( $calendars ) ) )
			$sanitized = $calendar;

		else if ( $key = array_search( $calendar, $calendars ) )
			$sanitized = $key;

		else
			$sanitized = $default;

		return apply_filters( static::BASE.'_sanitize_calendar', $sanitized, $default, $calendar );
	}
}

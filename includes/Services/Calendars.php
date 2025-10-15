<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Calendars extends gEditorial\Service
{
	const REWRITE_ENDPOINT_NAME  = 'ics';
	const REWRITE_ENDPOINT_QUERY = 'ical';

	public static function setup()
	{
		add_action( 'init', [ __CLASS__, 'init' ] );

		if ( is_admin() )
			return;

		add_action( 'template_redirect', [ __CLASS__, 'template_redirect' ] );
	}

	public static function init()
	{
		add_rewrite_endpoint(
			static::REWRITE_ENDPOINT_NAME,
			EP_PERMALINK | EP_PAGES,
			static::REWRITE_ENDPOINT_QUERY
		);
	}

	// https://make.wordpress.org/plugins/2012/06/07/rewrite-endpoints-api/
	// https://gist.github.com/joncave/2891111
	public static function template_redirect()
	{
		global $wp_query;

		if ( ! array_key_exists( static::REWRITE_ENDPOINT_QUERY, $wp_query->query_vars ) || ! is_singular() )
			return;

		// output some JSON (normally you might include a template file here)
		// makeplugins_endpoints_do_json(); // FIXME
		exit;
	}

	/**
	 * Retrieves the list of supported calendars.
	 * @see `Almanac` Module
	 * @old: `Datetime::getDefualtCalendars()`
	 * @source https://unicode-org.github.io/icu/userguide/datetime/calendar/
	 *
	 * @param bool $filtered
	 * @return array
	 */
	public static function getDefualts( $filtered = FALSE )
	{
		$calendars = [
			'gregorian'     => _x( 'Gregorian', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'japanese'      => _x( 'Japanese', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'buddhist'      => _x( 'Buddhist', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'chinese'       => _x( 'Chinese', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			'persian'       => _x( 'Persian', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'indian'        => _x( 'Indian', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			'islamic'       => _x( 'Islamic', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'islamic-civil' => _x( 'Islamic-Civil', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'coptic'        => _x( 'Coptic', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'ethiopic'      => _x( 'Ethiopic', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
		];

		return $filtered ? apply_filters( static::BASE.'_default_calendars', $calendars ) : $calendars;
	}

	/**
	 * Sanitizes given calendar type string.
	 * @old: `Datetime::sanitizeCalendar()`
	 *
	 * @param string $calendar
	 * @param string $default
	 * @return string
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

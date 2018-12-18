<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Date;
use geminorum\gEditorial\Core\HTML;

class Datetime extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	public static function htmlCurrent( $format = NULL, $class = FALSE, $title = FALSE )
	{
		return Date::htmlCurrent( ( is_null( $format ) ? self::dateFormats( 'datetime' ) : $format ), $class, $title );
	}

	public static function dateFormat( $timestamp, $context = 'default' )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		return date_i18n( self::dateFormats( $context ), $timestamp );
	}

	// @SEE: http://www.phpformatdate.com/
	public static function dateFormats( $context = 'default' )
	{
		static $formats;

		if ( empty( $formats ) )
			$formats = apply_filters( 'custom_date_formats', [
				'fulltime'  => _x( 'l, M j, Y @ H:i', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'datetime'  => _x( 'M j, Y @ G:i', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'dateonly'  => _x( 'l, F j, Y', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'timedate'  => _x( 'H:i - F j, Y', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'timeampm'  => _x( 'g:i a', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'timeonly'  => _x( 'H:i', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'monthday'  => _x( 'n/j', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'default'   => _x( 'm/d/Y', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'wordpress' => get_option( 'date_format' ),
			] );

		if ( FALSE === $context )
			return $formats;

		if ( isset( $formats[$context] ) )
			return $formats[$context];

		return $formats['default'];
	}

	public static function postModified( $post = NULL, $attr = FALSE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		$format = self::dateFormats( 'dateonly' );
		$title  = _x( 'Last Modified on %s', 'Datetime: Post Modified', GEDITORIAL_TEXTDOMAIN );

		return $attr
			? sprintf( $title, date_i18n( $format, $local ) )
			: Date::htmlDateTime( $local, $gmt, $format, self::humanTimeDiffRound( $local, FALSE ) );
	}

	public static function htmlHumanTime( $timestamp, $flip = FALSE )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$now = current_time( 'timestamp', FALSE );

		if ( $flip )
			return '<span class="-date-diff" title="'
					.HTML::escape( self::dateFormat( $timestamp, 'fulltime' ) ).'">'
					.self::humanTimeDiff( $timestamp, $now )
				.'</span>';

		return '<span class="-time" title="'
			.HTML::escape( self::humanTimeAgo( $timestamp, $now ) ).'">'
			.self::humanTimeDiffRound( $timestamp, NULL, self::dateFormats( 'default' ), $now )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf( _x( '%s ago', 'Datetime: Human Time Ago', GEDITORIAL_TEXTDOMAIN ), human_time_diff( $from, $to ) );
	}

	public static function humanTimeDiffRound( $local, $round = NULL, $format = NULL, $now = NULL )
	{
		if ( is_null( $now ) )
			$now = current_time( 'timestamp', FALSE );

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $round ) )
			$round = Date::DAY_IN_SECONDS;

		$diff = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $format ) )
			$format = self::dateFormats( 'default' );

		return date_i18n( $format, $local, FALSE );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'    => _x( 'Now', 'Datetime: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),
				'_s_ago' => _x( '%s ago', 'Datetime: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),
				'in__s'  => _x( 'in %s', 'Datetime: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),

				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Datetime: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Datetime: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Datetime: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Datetime: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Datetime: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Datetime: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::humanTimeDiff( $timestamp, $now, $strings );
	}

	public static function htmlFromSeconds( $seconds, $round = FALSE )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'sep' => _x( ', ', 'Datetime: From Seconds: Seperator', GEDITORIAL_TEXTDOMAIN ),

				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Datetime: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Datetime: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Datetime: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Datetime: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
			];

		return Date::htmlFromSeconds( $seconds, $round, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'            => _x( 'Now', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'just_now'       => _x( 'Just now', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'one_minute_ago' => _x( 'One minute ago', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_minutes_ago' => _x( '%s minutes ago', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'one_hour_ago'   => _x( 'One hour ago', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_hours_ago'   => _x( '%s hours ago', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'yesterday'      => _x( 'Yesterday', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_days_ago'    => _x( '%s days ago', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'last_month'     => _x( 'Last month', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'last_year'      => _x( 'Last year', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in_a_minute'    => _x( 'in a minute', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_minutes'  => _x( 'in %s minutes', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in_an_hour'     => _x( 'in an hour', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_hours'    => _x( 'in %s hours', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'tomorrow'       => _x( 'Tomorrow', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'next_week'      => _x( 'next week', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_weeks'    => _x( 'in %s weeks', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'next_month'     => _x( 'next month', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'format_l'       => _x( 'l', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'format_f_y'     => _x( 'F Y', 'Datetime: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::moment( $timestamp, $now, $strings );
	}

	// @REF: [Calendar Classes - ICU User Guide](http://userguide.icu-project.org/datetime/calendar)
	public static function getDefualtCalendars( $filtered = FALSE )
	{
		$calendars = [
			'gregorian'     => _x( 'Gregorian', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'japanese'      => _x( 'Japanese', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'buddhist'      => _x( 'Buddhist', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'chinese'       => _x( 'Chinese', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'persian'       => _x( 'Persian', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'indian'        => _x( 'Indian', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'islamic'       => _x( 'Islamic', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'islamic-civil' => _x( 'Islamic-Civil', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'coptic'        => _x( 'Coptic', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'ethiopic'      => _x( 'Ethiopic', 'Datetime: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
		];

		return $filtered ? apply_filters( static::BASE.'_default_calendars', $calendars ) : $calendars;
	}

	public static function sanitizeCalendar( $calendar, $default_type = 'gregorian' )
	{
		$calendars = self::getDefualtCalendars( FALSE );
		$sanitized = $calendar;

		if ( ! $calendar )
			$sanitized = $default_type;

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
			$sanitized = $default_type;

		return apply_filters( static::BASE.'_sanitize_calendar', $sanitized, $default_type, $calendar );
	}

	public static function getPostTypeMonths( $calendar_type, $posttype = 'post', $args = [], $user_id = 0 )
	{
		$callback = [ __NAMESPACE__.'\\WordPress\\Database', 'getPostTypeMonths' ];

		if ( 'persian' == $calendar_type
			&& is_callable( [ 'gPersianDateWordPress', 'getPostTypeMonths' ] ) )
				$callback = [ 'gPersianDateWordPress', 'getPostTypeMonths' ];

		return call_user_func_array( $callback, [ $posttype, $args, $user_id ] );
	}

	public static function monthFirstAndLast( $calendar_type, $year, $month, $format = 'Y-m-d H:i:s' )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'monthFirstAndLast' ];

		if ( is_callable( [ 'gPersianDateDate', 'monthFirstAndLast' ] ) )
			$callback = [ 'gPersianDateDate', 'monthFirstAndLast' ];

		return call_user_func_array( $callback, [ $year, $month, $format, $calendar_type ] );
	}

	public static function makeFromInput( $input, $calendar = 'gregorian', $timezone = NULL )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'makeFromInput' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeFromInput' ] ) )
			$callback = [ 'gPersianDateDate', 'makeFromInput' ];

		// must be here, we can not pass NULL to gPersianDate
		if ( is_null( $timezone ) )
			$timezone = Date::currentTimeZone();

		return call_user_func_array( $callback, [ $input, $calendar, $timezone ] );
	}

	public static function makeMySQLFromInput( $input, $format = NULL, $calendar_type = 'gregorian', $timezone = NULL )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'makeMySQLFromInput' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeMySQLFromInput' ] ) )
			$callback = [ 'gPersianDateDate', 'makeMySQLFromInput' ];

		// must be here, we can not pass NULL to gPersianDate
		if ( is_null( $timezone ) )
			$timezone = Date::currentTimeZone();

		return call_user_func_array( $callback, [ $input, $format, $calendar_type, $timezone ] );
	}

	// FIXME: find a better way!
	public static function getMonths( $calendar_type = 'gregorian' )
	{
		if ( is_callable( [ 'gPersianDateStrings', 'month' ] ) ) {

			$map = [
				'gregorian' => 'Gregorian',
				'persian'   => 'Jalali',
				'islamic'   => 'Hijri',
			];

			if ( ! array_key_exists( $calendar_type, $map ) )
				return [];

			return \gPersianDateStrings::month( NULL, TRUE, $map[$calendar_type] );
		}

		global $wp_locale;

		if ( 'gregorian' )
			return $wp_locale->month;

		return [];
	}

	public static function getCalendar( $calendar_type = 'gregorian', $args = [] )
	{
		if ( is_callable( [ 'gPersianDateCalendar', 'build' ] ) ) {

			$map = [
				'gregorian' => 'Gregorian',
				'persian'   => 'Jalali',
				'islamic'   => 'Hijri',
			];

			if ( ! array_key_exists( $calendar_type, $map ) )
				return FALSE;

			$args['calendar'] = $map[$calendar_type];

			return \gPersianDateCalendar::build( $args );
		}

		return FALSE;
	}

	// - for post time, if the post is unpublished, the change sets the
	// publication timestamp
	// - if the post was published or scheduled for the future, the change will
	// change the timestamp. 'publish' posts will become scheduled if moved past
	// today and 'future' posts will be published if moved before today
	// @REF: `handle_ajax_drag_and_drop()`
	// FIXME: needs fallback
	public static function reSchedulePost( $post, $input, $calendar = FALSE, $set_timestamp = TRUE )
	{
		global $wpdb;

		if ( ! is_callable( 'gPersianDateDate', 'make' ) )
			return FALSE;

		$the_day = self::atts( [
			'cal'   => $calendar,
			'year'  => NULL,
			'month' => 1,
			'day'   => 1,
		], $input );

		// fallback to current year
		if ( is_null( $the_day['year'] ) && is_callable( 'gPersianDateDate', 'to' ) )
			$the_day['year'] = \gPersianDateDate::to( 'Y', NULL, 'UTC', FALSE, FALSE, $the_day['cal'] );

		if ( ! $the_day['cal'] || ! $the_day['year'] || ! $the_day['month'] || ! $the_day['day'] )
			return FALSE;

		if ( ! $post = get_post( $post ) )
			return FALSE;

		// persist the old hourstamp because we can't manipulate the exact time
		// on the calendar bump the last modified timestamps too
		$old  = date( 'H:i:s', strtotime( $post->post_date ) );
		$time = explode( ':', $old );

		$timestamp = \gPersianDateDate::make(
			$time[0],
			$time[1],
			$time[2],
			$the_day['month'],
			$the_day['day'],
			$the_day['year'],
			$the_day['cal'],
			'UTC'
		);

		if ( ! $timestamp )
			return _x( 'Something is wrong with the new date.', 'Datetime: Re-Schedule Post', GEDITORIAL_TEXTDOMAIN );

		$data = [
			'post_date'         => date( 'Y-m-d', $timestamp ).' '.$old,
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
		];

		// by default, changing a post on the calendar won't set the timestamp.
		// if the user desires that to be the behaviour, they can set the result
		// of this filter to 'true' with how WordPress works internally,
		// setting 'post_date_gmt' will set the timestamp
		if ( apply_filters( static::BASE.'_reschedule_set_timestamp', $set_timestamp ) )
			$data['post_date_gmt'] = date( 'Y-m-d', $timestamp ).' '.date( 'H:i:s', strtotime( $post->post_date_gmt ) );

		// self::_log( [ $month, $day, $year, $cal, $time ] );
		// self::_log( [ $post->post_date, $post->post_date_gmt, $post->post_modified, $post->post_modified_gmt ] );
		// self::_log( [ $data['post_date'], $data['post_date_gmt'], $data['post_modified'], $data['post_modified_gmt'] ] );

		// @SEE http://core.trac.wordpress.org/ticket/18362
		if ( ! $update = $wpdb->update( $wpdb->posts, $data, [ 'ID' => $post->ID ] ) )
			return FALSE;

		clean_post_cache( $post->ID );

		return TRUE;
	}

	// returns array of post date in given cal
	public static function getTheDayByPost( $post, $default_type = 'gregorian' )
	{
		$the_day = [ 'cal' => 'gregorian' ];

		// 'post_status' => 'auto-draft',

		switch ( strtolower( $default_type ) ) {

			case 'hijri':
			case 'islamic':

				$convertor = [ 'gPersianDateDateTime', 'toHijri' ];
				$the_day['cal'] = 'hijri';

			case 'jalali':
			case 'persian':

				$convertor = [ 'gPersianDateDateTime', 'toJalali' ];
				$the_day['cal'] = 'jalali';

			default:

				if ( class_exists( 'gPersianDateDateTime' )
					&& 'gregorian' != $the_day['cal'] ) {

					list(
						$the_day['year'],
						$the_day['month'],
						$the_day['day']
					) = call_user_func_array( $convertor,
						explode( '-', mysql2date( 'Y-n-j', $post->post_date, FALSE ) ) );

				} else {

					$the_day['cal'] = 'gregorian';
					$the_day['day']   = mysql2date( 'j', $post->post_date, FALSE );
					$the_day['month'] = mysql2date( 'n', $post->post_date, FALSE );
					$the_day['year']  = mysql2date( 'Y', $post->post_date, FALSE );
				}

				// FIXME: add time
		}

		return $the_day;
	}
}

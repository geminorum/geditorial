<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Datetime extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function htmlCurrent( $format = NULL, $class = FALSE, $title = FALSE )
	{
		return Core\Date::htmlCurrent( ( is_null( $format ) ? self::dateFormats( 'datetime' ) : $format ), $class, $title );
	}

	// @REF: https://unicode-table.com/en/060D/
	// @SEE: https://www.compart.com/en/unicode/U+002F
	// @SEE: [Arabic Date Separator U-060D](https://github.com/rastikerdar/vazir-font/issues/81)
	public static function dateSeparator()
	{
		return _x( '/', 'Datetime: Date Separator', 'geditorial' );
	}

	// FIXME: use regex!
	public static function stringFormat( $string )
	{
		// FIXME: WTF: messes with the dates!
		// $string = str_replace( '/', self::dateSeparator(), $string );

		return trim( $string );
	}

	public static function dateFormat( $timestamp, $context = 'default' )
	{
		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		return date_i18n( self::dateFormats( $context ), $timestamp );
	}

	// @SEE: http://www.phpformatdate.com/
	public static function dateFormats( $context = 'default' )
	{
		static $formats;

		if ( empty( $formats ) )
			$formats = apply_filters( 'custom_date_formats', [
				'fulltime'  => _x( 'l, M j, Y @ H:i', 'Date Format', 'geditorial' ),
				'datetime'  => _x( 'M j, Y @ G:i', 'Date Format', 'geditorial' ),
				'dateonly'  => _x( 'l, F j, Y', 'Date Format', 'geditorial' ),
				'timedate'  => _x( 'H:i - F j, Y', 'Date Format', 'geditorial' ),
				'timeampm'  => _x( 'g:i a', 'Date Format', 'geditorial' ),
				'timeonly'  => _x( 'H:i', 'Date Format', 'geditorial' ),
				'monthday'  => _x( 'n/j', 'Date Format', 'geditorial' ),
				'default'   => _x( 'm/d/Y', 'Date Format', 'geditorial' ),
				'age'       => _x( 'Y/m/d', 'Date Format: `age`', 'geditorial' ),
				'wordpress' => get_option( 'date_format' ),
			] );

		if ( FALSE === $context )
			return $formats;

		return empty( $formats[$context] )
			? $formats['default']
			: $formats[$context];
	}

	public static function postModified( $post = NULL, $attr = FALSE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		$format = self::dateFormats( 'dateonly' );
		/* translators: %s: date string */
		$title  = _x( 'Last Modified on %s', 'Datetime: Post Modified', 'geditorial' );

		return $attr
			? sprintf( $title, date_i18n( $format, $local ) )
			: Core\Date::htmlDateTime( $local, $gmt, $format, self::humanTimeDiffRound( $local, FALSE ) );
	}

	public static function htmlHumanTime( $timestamp, $flip = FALSE )
	{
		if ( ! $timestamp )
			return $timestamp;

		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$now = current_time( 'timestamp', FALSE );

		if ( $flip )
			return '<span class="-date-diff" title="'
					.Core\HTML::escape( self::dateFormat( $timestamp, 'fulltime' ) ).'">'
					.self::humanTimeDiff( $timestamp, $now )
				.'</span>';

		return '<span class="-time" title="'
			.Core\HTML::escape( self::humanTimeAgo( $timestamp, $now ) ).'">'
			.self::humanTimeDiffRound( $timestamp, NULL, self::dateFormats( 'default' ), $now )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf(
			/* translators: %s: time string */
			_x( '%s ago', 'Datetime: Human Time Ago', 'geditorial' ),
			human_time_diff( $from, $to )
		);
	}

	public static function humanTimeDiffRound( $local, $round = NULL, $format = NULL, $now = NULL )
	{
		if ( is_null( $now ) )
			$now = current_time( 'timestamp', FALSE );

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $round ) )
			$round = Core\Date::DAY_IN_SECONDS;

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
				'now'    => _x( 'Now', 'Datetime: Human Time Diff', 'geditorial' ),
				/* translators: %s: time string */
				'_s_ago' => _x( '%s ago', 'Datetime: Human Time Diff', 'geditorial' ),
				/* translators: %s: time string */
				'in__s'  => _x( 'in %s', 'Datetime: Human Time Diff', 'geditorial' ),

				/* translators: %s: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: %s: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: %s: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: %s: number of weeks */
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: %s: number of months */
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: %s: number of years */
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Core\Date::humanTimeDiff( $timestamp, $now, $strings );
	}

	public static function htmlFromSeconds( $seconds, $round = FALSE )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'sep' => Strings::separator(),

				/* translators: %s: number of seconds */
				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Datetime: From Seconds: Noop', 'geditorial' ),
				/* translators: %s: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Datetime: From Seconds: Noop', 'geditorial' ),
				/* translators: %s: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Datetime: From Seconds: Noop', 'geditorial' ),
				/* translators: %s: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Datetime: From Seconds: Noop', 'geditorial' ),
			];

		return Core\Date::htmlFromSeconds( $seconds, $round, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'            => _x( 'Now', 'Datetime: Date: Moment', 'geditorial' ),
				'just_now'       => _x( 'Just now', 'Datetime: Date: Moment', 'geditorial' ),
				'one_minute_ago' => _x( 'One minute ago', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of minutes */
				'_s_minutes_ago' => _x( '%s minutes ago', 'Datetime: Date: Moment', 'geditorial' ),
				'one_hour_ago'   => _x( 'One hour ago', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of hours */
				'_s_hours_ago'   => _x( '%s hours ago', 'Datetime: Date: Moment', 'geditorial' ),
				'yesterday'      => _x( 'Yesterday', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of days */
				'_s_days_ago'    => _x( '%s days ago', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of weeks */
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Datetime: Date: Moment', 'geditorial' ),
				'last_month'     => _x( 'Last month', 'Datetime: Date: Moment', 'geditorial' ),
				'last_year'      => _x( 'Last year', 'Datetime: Date: Moment', 'geditorial' ),
				'in_a_minute'    => _x( 'in a minute', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of minutes */
				'in__s_minutes'  => _x( 'in %s minutes', 'Datetime: Date: Moment', 'geditorial' ),
				'in_an_hour'     => _x( 'in an hour', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of hours */
				'in__s_hours'    => _x( 'in %s hours', 'Datetime: Date: Moment', 'geditorial' ),
				'tomorrow'       => _x( 'Tomorrow', 'Datetime: Date: Moment', 'geditorial' ),
				'next_week'      => _x( 'next week', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: %s: number of weeks */
				'in__s_weeks'    => _x( 'in %s weeks', 'Datetime: Date: Moment', 'geditorial' ),
				'next_month'     => _x( 'next month', 'Datetime: Date: Moment', 'geditorial' ),
				'format_l'       => _x( 'l', 'Datetime: Date: Moment', 'geditorial' ),
				'format_f_y'     => _x( 'F Y', 'Datetime: Date: Moment', 'geditorial' ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Core\Date::moment( $timestamp, $now, $strings );
	}

	public static function getPostTypeMonths( $calendar_type, $posttype = 'post', $args = [], $user_id = 0 )
	{
		$callback = [ __NAMESPACE__.'\\WordPress\\Database', 'getPostTypeMonths' ];

		if ( 'persian' == $calendar_type
			&& is_callable( [ 'gPersianDateWordPress', 'getPostTypeMonths' ] ) )
				$callback = [ 'gPersianDateWordPress', 'getPostTypeMonths' ];

		return call_user_func_array( $callback, [ $posttype, $args, $user_id ] );
	}

	public static function monthFirstAndLast( $calendar_type, $year, $month, $format = NULL )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'monthFirstAndLast' ];

		if ( is_callable( [ 'gPersianDateDate', 'monthFirstAndLast' ] ) )
			$callback = [ 'gPersianDateDate', 'monthFirstAndLast' ];

		return call_user_func_array( $callback, [ $year, $month, $format ?? Core\Date::MYSQL_FORMAT, $calendar_type ] );
	}

	public static function makeFromInput( $input, $calendar = 'gregorian', $timezone = NULL, $fallback = '' )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'makeFromInput' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeFromInput' ] ) )
			$callback = [ 'gPersianDateDate', 'makeFromInput' ];

		// must be here, we can not pass NULL to gPersianDate
		// if ( is_null( $timezone ) )
		// 	$timezone = Core\Date::currentTimeZone();

		return call_user_func_array( $callback, [ $input, $calendar, $timezone, $fallback ] );
	}

	public static function makeMySQLFromInput( $input, $format = NULL, $calendar_type = 'gregorian', $timezone = NULL, $fallback = NULL )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'makeMySQLFromInput' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeMySQLFromInput' ] ) )
			$callback = [ 'gPersianDateDate', 'makeMySQLFromInput' ];

		// must be here, we can not pass NULL to gPersianDate
		// if ( is_null( $timezone ) )
		// 	$timezone = Core\Date::currentTimeZone();

		return call_user_func_array( $callback, [ $input, $format, $calendar_type, $timezone, $fallback ] );
	}

	public static function prepForInput( $date, $format = NULL, $calendar_type = NULL, $timezone = NULL )
	{
		return apply_filters( 'date_format_i18n', $date, $format, $calendar_type, $timezone, FALSE );
	}

	public static function prepForDisplay( $date, $format = NULL, $calendar_type = 'gregorian', $timezone = NULL )
	{
		if ( is_null( $format ) )
			$format = self::dateFormats( 'default' );

		$timestamp = strtotime( $date );
		$timeage   = self::humanTimeDiffRound( $timestamp, FALSE );

		return Core\Date::htmlDateTime( $timestamp, NULL, $format, $timeage );
	}

	// TODO: utilize `htmlDateTime()`
	public static function prepDateOfBirth( $date, $format = NULL, $reversed = FALSE, $calendar_type = 'gregorian', $timezone = NULL )
	{
		$age = Core\Date::calculateAge( $date, $calendar_type, $timezone );

		/* translators: %s: year number */
		$title = sprintf( _nx( '%s year old', '%s years old', $age['year'], 'Datetime: Age Title Attr', 'geditorial' ), Core\Number::format( $age['year'] ) );

		$html     = apply_filters( 'date_format_i18n', $date, $format ?? self::dateFormats( 'age' ), $calendar_type, $timezone );
		$template = '<span title="%s" class="%s">%s</span>';

		return $reversed
			? sprintf( $template, $html, 'date-of-birth', $title )
			: sprintf( $template, $title, 'date-of-birth', $html );
	}

	/**
	 * Provides the distribution of the population according to age.
	 * @source: https://en.wikipedia.org/wiki/Demographic_profile
	 *
	 * @param  bool  $extended
	 * @return array $data
	 */
	public static function getAgeStructure( $extended = FALSE )
	{
		$data = [
			'00to14' => [
				'slug' => '00to14',
				'name' => _x( '0–14 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'max' => 14,
				],
			],
			'15to24' => [
				'slug' => '15to24',
				'name' => _x( '15–24 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 15,
					'max' => 24,
				],
			],
			'25to54' => [
				'slug' => '25to54',
				'name' => _x( '25–54 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 25,
					'max' => 54,
				],
			],
			'55to64' => [
				'slug' => '55to64',
				'name' => _x( '55–64 years', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 55,
					'max' => 64,
				],
			],
			'65over' => [
				'slug' => '65over',
				'name' => _x( '65 years and over', 'Datetime: Age Structure', 'geditorial' ),
				'meta' => [
					'min' => 65,
				],
			],
		];

		return $extended ? $data : Core\Arraay::pluck( $data, 'name', 'slug' );
	}

	/**
	 * The American Medical Association's age designations.
	 * @source https://www.nih.gov/nih-style-guide/age
	 * NOTE: `min`/`max` meta values are based on months
	 *
	 * - Neonates or newborns (birth to 1 month)
	 * - Infants (1 month to 1 year)
	 * - Children (1 year through 12 years)
	 * - Adolescents (13 years through 17 years. They may also be referred to as teenagers depending on the context.)
	 * - Adults (18 years or older)
	 * - Older adults (65 and older)
	 *
	 * @param  bool $extended
	 * @return array $data
	 */
	public static function getMedicalAge( $extended = FALSE )
	{
		$data = [
			'newborns'     => [ 'slug' => 'newborns',     'name' => _x( 'Newborns', 'Datetime: Medical Age', 'geditorial' ),     'meta' => [               'max' => 1   ] ],
			'infants'      => [ 'slug' => 'infants',      'name' => _x( 'Infants', 'Datetime: Medical Age', 'geditorial' ),      'meta' => [ 'min' => 1,   'max' => 12  ] ],
			'children'     => [ 'slug' => 'children',     'name' => _x( 'Children', 'Datetime: Medical Age', 'geditorial' ),     'meta' => [ 'min' => 13,  'max' => 144 ] ],
			'adolescents'  => [ 'slug' => 'adolescents',  'name' => _x( 'Adolescents', 'Datetime: Medical Age', 'geditorial' ),  'meta' => [ 'min' => 145, 'max' => 204 ] ],
			'adults'       => [ 'slug' => 'adults',       'name' => _x( 'Adults', 'Datetime: Medical Age', 'geditorial' ),       'meta' => [ 'min' => 205, 'max' => 781 ] ],
			'older-adults' => [ 'slug' => 'older-adults', 'name' => _x( 'Older Adults', 'Datetime: Medical Age', 'geditorial' ), 'meta' => [ 'min' => 781,              ] ],
		];

		return $extended ? $data : Core\Arraay::pluck( $data, 'name', 'slug' );
	}

	public static function getDecades( $from = '-100 years', $count = 10, $prefixed = FALSE, $metakey = NULL )
	{
		/* translators: %s: decade number */
		$name  = $prefixed ? _x( 'Decade %s', 'Datetime: Decade Prefix', 'geditorial' ) : '%s';
		$slug  = $prefixed ? 'decade-%s' : '%s';
		$meta  = $metakey ?? 'decade';
		$epoch = Core\Date::calculateDecade( $from );
		$list  = [];

		for ( $i = 1; $i <= $count; $i++ ) {

			$decade = $epoch + ( $i * 10 );

			$list[$decade] =  [
				'slug' => sprintf( $slug, $decade ),
				'name' => sprintf( $name, Core\Number::localize( $decade ) ),
				'meta' => [ $meta  => $decade ],
			];
		}

		return $list;
	}

	public static function getYearsByDecades( $from = '-100 years', $count = 10, $prefixed = TRUE, $metakey = NULL )
	{
		/* translators: %s: year number */
		$name    = $prefixed ? _x( 'Year %s', 'Datetime: Year Prefix', 'geditorial' ) : '%s';
		$slug    = $prefixed ? 'year-%s' : '%s';
		$meta    = $metakey ?? 'decade';
		$key     = $metakey ? 'children' : 'years';
		$decades = self::getDecades( $from, $count, $prefixed, $metakey );
		$list    = [];

		foreach ( $decades as $decade => $args ) {

			$years = [];

			for ( $i = 1; $i <= 10; $i++ ) {

				$year = $decade + $i;

				$years[$year] = [
					'slug' => sprintf( $slug, $year ),
					'name' => sprintf( $name, Core\Number::localize( $decade + $i ) ),
					'meta' => [ $meta  => $year ],
				];
			}

			$args[$key] = $years;
			$list[$decade] = $args;
		}

		return $list;
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

		if ( ! $post = get_post( $post ) )
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
			return _x( 'Something is wrong with the new date.', 'Datetime: Re-Schedule Post', 'geditorial' );

		$data = [
			'post_date'         => date( 'Y-m-d', $timestamp ).' '.$old,
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 ),
		];

		// - by default, changing a post on the calendar won't set the timestamp
		// - if the user desires that to be the behaviour, they can set the result
		// of this filter to 'true'
		// - with how WordPress works internally, setting 'post_date_gmt'
		// will set the timestamp
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

	// NOT USED
	// FIXME: DROP THIS
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

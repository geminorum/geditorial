<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Datetime extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function htmlCurrent( $format = NULL, $class = FALSE, $title = FALSE, $calendar_type = NULL, $timezone_string = NULL, $locale = NULL )
	{
		$html = self::htmlDateTime(
			'now',
			$format ?? self::dateFormats( 'current' ),
			$title,
			$calendar_type,
			$timezone_string,
			$locale
		);

		return $class
			? '<span class="'.$class.'">'.$html.'</span>'
			: $html;
	}

	public static function htmlDateTime( $datetime_string = NULL, $format = NULL, $title = FALSE, $calendar_type = NULL, $timezone_string = NULL, $locale = NULL )
	{
		return Core\HTML::tag( 'time', [
			'datetime' => Core\Date::getISO8601( $datetime_string, $timezone_string, FALSE ),
			'title'    => $title,
			'class'    => 'do-timeago', // @SEE: http://timeago.yarp.com/
		], self::formatByCalendar(
			$format ?? self::dateFormats( 'dateonly' ),
			$datetime_string,
			$calendar_type,
			$timezone_string,
			$locale
		) );
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

		return Core\Text::trim( $string );
	}

	public static function isDateOnly( $date_string )
	{
		return Core\Text::ends( $date_string, '00:00:00' );
	}

	public static function dateFormat( $timestamp, $context = 'default' )
	{
		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		return Core\Date::get( self::dateFormats( $context ), $timestamp );
	}

	// @SEE: http://www.phpformatdate.com/
	public static function dateFormats( $context = 'default' )
	{
		static $formats;

		if ( empty( $formats ) )
			$formats = apply_filters( static::BASE.'_custom_date_formats', [
				'age'       => _x( 'm/d/Y', 'Date Format: `age`', 'geditorial' ),
				'birthday'  => _x( 'm/d/Y', 'Date Format: `birthdate`', 'geditorial' ),
				'current'   => _x( 'M j, Y @ G:i', 'Date Format: `current`', 'geditorial' ),
				'dateonly'  => _x( 'l, F j, Y', 'Date Format: `dateonly`', 'geditorial' ),
				'datetime'  => _x( 'm/d/Y G:i', 'Date Format: `datetime`', 'geditorial' ),
				'default'   => _x( 'm/d/Y', 'Date Format: `default`', 'geditorial' ),
				'fulltime'  => _x( 'l, M j, Y @ H:i', 'Date Format: `fulltime`', 'geditorial' ),
				'monthday'  => _x( 'n/j', 'Date Format: `monthday`', 'geditorial' ),
				'printdate' => _x( 'j/n/Y', 'Date Format: `printdate`', 'geditorial' ),
				'printtime' => _x( 'j/n/Y G:i', 'Date Format: `printtime`', 'geditorial' ),
				'timeampm'  => _x( 'g:i a', 'Date Format: `timeampm`', 'geditorial' ),
				'timedate'  => _x( 'H:i - F j, Y', 'Date Format: `timedate`', 'geditorial' ),
				'timeonly'  => _x( 'H:i', 'Date Format: `timeonly`', 'geditorial' ),
				'yearonly'  => _x( 'Y', 'Date Format: `yearonly`', 'geditorial' ),

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
		/* translators: `%s`: date string */
		$title  = _x( 'Last Modified on %s', 'Datetime: Post Modified', 'geditorial' );

		return $attr
			? sprintf( $title, Core\Date::get( $format, $local ) )
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

	public static function humanTimeAgo( $from, $to = 0 )
	{
		return sprintf(
			/* translators: `%s`: time string */
			_x( '%s ago', 'Datetime: Human Time Ago', 'geditorial' ),
			human_time_diff( $from, $to )
		);
	}

	public static function humanTimeDiffRound( $local, $round = NULL, $format = NULL, $now = NULL )
	{
		$now = $now ?? current_time( 'timestamp', FALSE );

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		$round = $round ?? Core\Date::DAY_IN_SECONDS;
		$diff  = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		return Core\Date::get( $format ?? self::dateFormats( 'default' ), $local );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'    => _x( 'Now', 'Datetime: Human Time Diff', 'geditorial' ),
				/* translators: `%s`: time string */
				'_s_ago' => _x( '%s ago', 'Datetime: Human Time Diff', 'geditorial' ),
				/* translators: `%s`: time string */
				'in__s'  => _x( 'in %s', 'Datetime: Human Time Diff', 'geditorial' ),

				/* translators: `%s`: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: `%s`: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: `%s`: number of days */
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: `%s`: number of weeks */
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: `%s`: number of months */
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Datetime: Human Time Diff: Noop', 'geditorial' ),
				/* translators: `%s`: number of years */
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

				/* translators: `%s`: number of seconds */
				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Datetime: From Seconds: Noop', 'geditorial' ),
				/* translators: `%s`: number of minutes */
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Datetime: From Seconds: Noop', 'geditorial' ),
				/* translators: `%s`: number of hours */
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Datetime: From Seconds: Noop', 'geditorial' ),
				/* translators: `%s`: number of days */
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
				/* translators: `%s`: number of minutes */
				'_s_minutes_ago' => _x( '%s minutes ago', 'Datetime: Date: Moment', 'geditorial' ),
				'one_hour_ago'   => _x( 'One hour ago', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: `%s`: number of hours */
				'_s_hours_ago'   => _x( '%s hours ago', 'Datetime: Date: Moment', 'geditorial' ),
				'yesterday'      => _x( 'Yesterday', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: `%s`: number of days */
				'_s_days_ago'    => _x( '%s days ago', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: `%s`: number of weeks */
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Datetime: Date: Moment', 'geditorial' ),
				'last_month'     => _x( 'Last month', 'Datetime: Date: Moment', 'geditorial' ),
				'last_year'      => _x( 'Last year', 'Datetime: Date: Moment', 'geditorial' ),
				'in_a_minute'    => _x( 'in a minute', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: `%s`: number of minutes */
				'in__s_minutes'  => _x( 'in %s minutes', 'Datetime: Date: Moment', 'geditorial' ),
				'in_an_hour'     => _x( 'in an hour', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: `%s`: number of hours */
				'in__s_hours'    => _x( 'in %s hours', 'Datetime: Date: Moment', 'geditorial' ),
				'tomorrow'       => _x( 'Tomorrow', 'Datetime: Date: Moment', 'geditorial' ),
				'next_week'      => _x( 'next week', 'Datetime: Date: Moment', 'geditorial' ),
				/* translators: `%s`: number of weeks */
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
		$callback      = [ WordPress\Database::class, 'getPostTypeMonths' ];
		$calendar_type = $calendar_type ?? Core\L10n::calendar();

		if ( 'persian' === $calendar_type
			&& is_callable( [ 'gPersianDateWordPress', 'getPostTypeMonths' ] ) )
				$callback = [ 'gPersianDateWordPress', 'getPostTypeMonths' ];

		return call_user_func_array( $callback, [ $posttype, $args, $user_id ] );
	}

	public static function monthFirstAndLast( $calendar_type, $year, $month, $format = NULL )
	{
		$callback = [ Core\Date::class, 'monthFirstAndLast' ];

		if ( is_callable( [ 'gPersianDateDate', 'monthFirstAndLast' ] ) )
			$callback = [ 'gPersianDateDate', 'monthFirstAndLast' ];

		return call_user_func_array( $callback, [
			$year,
			$month,
			$format ?? Core\Date::MYSQL_FORMAT,
			$calendar_type ?? Core\L10n::calendar(),
		] );
	}

	// @REF: `cal_days_in_month()`
	// @SEE: `Datetime::getMonths()`
	public static function daysInMonth( $month, $year, $calendar_type = 'gregorian' )
	{
		$callback = [ Core\Date::class, 'daysInMonth' ];

		if ( is_callable( [ 'gPersianDateDate', 'daysInMonth' ] ) )
			$callback = [ 'gPersianDateDate', 'daysInMonth' ];

		return call_user_func_array( $callback, [
			$month,
			$year,
			$calendar_type ?? Core\L10n::calendar(),
		] );
	}

	public static function makeFromInput( $input, $calendar_type = 'gregorian', $timezone_string = NULL, $fallback = '' )
	{
		$callback = [ Core\Date::class, 'makeFromInput' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeFromInput' ] ) )
			$callback = [ 'gPersianDateDate', 'makeFromInput' ];

		return call_user_func_array( $callback, [
			$input,
			$calendar_type ?? Core\L10n::calendar(),
			$timezone_string,
			$fallback,
		] );
	}

	public static function makeMySQLFromArray( $array = [], $format = NULL, $fallback = '' )
	{
		$callback = [ Core\Date::class, 'makeMySQLFromArray' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeMySQLFromArray' ] ) )
			$callback = [ 'gPersianDateDate', 'makeMySQLFromArray' ];

		return call_user_func_array( $callback, [ $array, $format, $fallback ] );
	}

	public static function makeMySQLFromInput( $input, $format = NULL, $calendar_type = NULL, $timezone_string = NULL, $fallback = NULL )
	{
		$callback = [ Core\Date::class, 'makeMySQLFromInput' ];

		if ( is_callable( [ 'gPersianDateDate', 'makeMySQLFromInput' ] ) )
			$callback = [ 'gPersianDateDate', 'makeMySQLFromInput' ];

		return call_user_func_array( $callback, [
			$input,
			$format,
			$calendar_type ?? Core\L10n::calendar(),
			$timezone_string,
			$fallback,
		] );
	}

	/**
	 * Retrieves the date, by given calendar in localized format.
	 *
	 * @param string $format
	 * @param string $datetime_string
	 * @param string $calendar_type
	 * @param string $timezone_string
	 * @param string $locale
	 * @return false|string
	 */
	public static function formatByCalendar( $format, $datetime_string = NULL, $calendar_type = NULL, $timezone_string = NULL, $locale = NULL )
	{
		$callback = [ Core\Date::class, 'formatByCalendar' ];

		if ( is_callable( [ 'gPersianDateDate', 'formatByCalendar' ] ) )
			$callback = [ 'gPersianDateDate', 'formatByCalendar' ];

		return call_user_func_array( $callback, [
			$format ?? self::dateFormats( 'default' ),
			$datetime_string,
			$calendar_type ?? Core\L10n::calendar( $locale ),
			$timezone_string,
			$locale,
		] );
	}

	public static function prepForInput( $date, $format = NULL, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( $year = self::prepYearOnly( $date, FALSE ) )
			return $year;

		return self::formatByCalendar(
			$format,
			$date,
			$calendar_type,
			$timezone_string
		);
	}

	// NOTE: alternative to `Datetime::makeMySQLFromInput()` since the input only in year/mysql-format
	public static function prepForMySQL( $input, $format = NULL, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( ! $input )
			return FALSE;

		if ( ! $sanitized = Core\Number::translate( Core\Text::trim( $input ) ) )
			return FALSE;

		// NOTE: year-onlies stored in targeted calendar
		if ( 4 === strlen( $sanitized ) )
			$datetime = self::makeMySQLFromInput(
				sprintf( '%s-01-01', $sanitized ),
				$format,
				$calendar_type,
				$timezone_string,
				FALSE
			);

		// NOTE: full-dates stored in Gregorian
		else if ( Core\Date::isInFormat( $sanitized, 'Y-m-d' ) )
			// $datetime = sprintf( '%s 23:59:59', $sanitized );
			$datetime = sprintf( '%s 00:00:00', $sanitized );

		else if ( Core\Date::isInFormat( $sanitized, $format ?? Core\Date::MYSQL_FORMAT ) )
			$datetime = $sanitized;

		else
			return FALSE;

		return $datetime;
	}

	public static function prepYearOnly( $data, $localize = TRUE, $fallback = '' )
	{
		if ( ! $sanitized = Core\Text::trim( Core\Number::translate( $data ) ) )
			return $fallback;

		if ( strlen( $sanitized ) > 4 )
			return $fallback;

		return $localize ? Core\Number::localize( $sanitized ) : $sanitized;
	}

	public static function prepForDisplay( $datetime_string, $format = NULL, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( $year = self::prepYearOnly( $datetime_string ) )
			return $year;

		if ( $datetime_string )
			return self::htmlDateTime(
				$datetime_string,
				$format ?? self::dateFormats( 'default' ),
				self::humanTimeDiffRound( strtotime( $datetime_string ), FALSE ),
				$calendar_type,
				$timezone_string
			);

		return $datetime_string ?: '';
	}

	public static function prepDateOfBirth( $datetime_string, $format = NULL, $reversed = FALSE, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( ! $datetime_string )
			return '';

		$age = Core\Date::calculateAge( $datetime_string, $calendar_type, $timezone_string );

		$title = sprintf(
			/* translators: `%s`: year number */
			_nx( '%s year old', '%s years old', $age['year'], 'Datetime: Age Title Attr', 'geditorial' ),
			Core\Number::format( $age['year'] )
		);

		$template  = '<span title="%s" class="%s" datetime="%s">%s</span>';
		$datetime  = Core\Date::getISO8601( $datetime_string, $timezone_string, '' );
		$formatted = self::formatByCalendar(
			$format ?? self::dateFormats( 'birthday' ),
			$datetime_string,
			$calendar_type,
			$timezone_string
		);

		return $reversed
			? sprintf( $template, $formatted, 'date-of-birth', $datetime, $title )
			: sprintf( $template, $title, 'date-of-birth', $datetime, $formatted );
	}

	// NOTE: falls back on raw data: like `1362`
	// TODO: support other spans like: establish/abolish
	public static function prepBornDeadForDisplay( $born = '', $dead = '', $context = NULL, $calendar_type = NULL, $timezone_string = NULL )
	{
		if ( ! $born && ! $dead )
			return '';

		$template = Core\L10n::rtl()
			? '(<span class="-dead" title="%4$s">%2$s</span><span class="-sep">%5$s</span><span class="-born" title="%3$s">%1$s</span>)'
			: '(<span class="-born" title="%3$s">%1$s</span><span class="sep">%5$s</span><span class="-dead" title="%4$s">%2$s</span>)';

		return Core\Text::trim( vsprintf( $template, [
			self::prepForDisplay( $born, self::dateFormats( $context ?? 'yearonly' ), $calendar_type, $timezone_string ),
			self::prepForDisplay( $dead, self::dateFormats( $context ?? 'yearonly' ), $calendar_type, $timezone_string ),
			_x( 'Birthed', 'Datetime: Title Attribute', 'geditorial' ),
			_x( 'Deceased', 'Datetime: Title Attribute', 'geditorial' ),
			'&ndash;', // WordPress\Strings::separator(),
		] ) );
	}

	public static function getDecades( $from = '-100 years', $count = 10, $prefixed = FALSE, $metakey = NULL )
	{
		/* translators: `%s`: decade number */
		$name  = $prefixed ? _x( 'Decade %s', 'Datetime: Decade Prefix', 'geditorial' ) : '%s';
		$slug  = $prefixed ? 'decade-%s' : '%s';
		$meta  = $metakey ?? 'decade';
		$epoch = Core\Date::calculateDecade( $from );
		$list  = [];

		for ( $i = 1; $i <= $count; $i++ ) {

			$decade      = $epoch + ( $i * 10 );
			$decade_slug = sprintf( $slug, $decade );

			$list[$decade_slug] =  [
				'slug' => $decade_slug,
				'name' => sprintf( $name, Core\Number::localize( $decade ) ),
				'meta' => [ $meta  => $decade ],
			];
		}

		return $list;
	}

	public static function getYearsByDecades( $from = '-100 years', $count = 10, $prefixed = TRUE, $metakey = NULL )
	{
		/* translators: `%s`: year number */
		$name    = $prefixed ? _x( 'Year %s', 'Datetime: Year Prefix', 'geditorial' ) : '%s';
		$slug    = $prefixed ? 'year-%s' : '%s';
		$meta    = $metakey ?? 'decade';
		$key     = $metakey ? 'children' : 'years';
		$decades = self::getDecades( $from, $count, $prefixed, $metakey );
		$list    = [];

		foreach ( $decades as $decade_slug => $args ) {

			$years  = [];
			$decade = $args['meta'][$meta];

			for ( $i = 1; $i <= 10; $i++ ) {

				$year      = $decade + $i;
				$year_slug = sprintf( $slug, $year );

				$years[$year_slug] = [
					'slug' => $year_slug,
					'name' => sprintf( $name, Core\Number::localize( $year ) ),
					'meta' => [ $meta  => $year ],
				];
			}

			$args[$key] = $years;
			$list[$decade_slug] = $args;
		}

		return $list;
	}

	public static function getYears( $from = '-10 years' )
	{
		$list  = [];
		$start = Core\Number::translate( wp_date( 'Y', strtotime( $from ) ) );
		$end   = Core\Number::translate( wp_date( 'Y', strtotime( 'now' ) ) );

		if ( ! Core\Text::starts( $from, '-' ) ) {

			for ( $i = $start; $i > $end; $i-- )
				$list[$i-1] = Core\Number::localize( $i-1 );

			return array_reverse( $list );
		}

		for ( $i = $start; $i < $end; $i++ )
			$list[$i+1] = Core\Number::localize( $i+1 );

		return $list;
	}

	// FIXME: find a better way!
	// @SEE: `Datetime::daysInMonth()`
	public static function getMonths( $calendar_type = NULL )
	{
		$calendar_type = $calendar_type ?? Core\L10n::calendar();

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

	public static function getCalendar( $calendar_type = NULL, $args = [] )
	{
		$calendar_type = $calendar_type ?? Core\L10n::calendar();

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
	public static function reSchedulePost( $post, $array, $default_calendar = FALSE, $set_timestamp = TRUE )
	{
		global $wpdb;

		if ( ! is_callable( 'gPersianDateDate', 'make' ) )
			return FALSE;

		if ( ! $post = get_post( $post ) )
			return FALSE;

		$the_day = self::atts( [
			'cal'   => $default_calendar,
			'year'  => NULL,
			'month' => 1,
			'day'   => 1,
		], $array );

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

		/**
		 * - By default, changing a post on the calendar won't set the timestamp.
		 * - If the user desires that to be the behavior, they can set the result of this filter to `TRUE`.
		 * - With how WordPress works internally, setting `post_date_gmt` will set the timestamp.
		 */
		if ( apply_filters( static::BASE.'_reschedule_set_timestamp', $set_timestamp ) )
			$data['post_date_gmt'] = date( 'Y-m-d', $timestamp ).' '.date( 'H:i:s', strtotime( $post->post_date_gmt ) );

		// @SEE http://core.trac.wordpress.org/ticket/18362
		if ( ! $wpdb->update( $wpdb->posts, $data, [ 'ID' => $post->ID ] ) )
			return FALSE;

		clean_post_cache( $post->ID );

		return TRUE;
	}

	/**
	 * Retrieves the day array in given calendar
	 * @old: `::getTheDayFromToday()`
	 *
	 * @param string $datetime_string
	 * @param string $calendar_type
	 * @return array
	 */
	public static function getTheDay( $datetime_string = NULL, $calendar_type = NULL )
	{
		$calendar_type = $calendar_type ?? Core\L10n::calendar();
		$the_day       = [ 'cal' => $calendar_type ];

		if ( ! $the_date = self::formatByCalendar( 'Y-n-j', $datetime_string, $calendar_type, NULL, '' ) )
			return $the_day;

		list(
			$the_day['year'],
			$the_day['month'],
			$the_day['day']
		) = explode( '-', Core\Number::translate( $the_date ) );

		return $the_day;
	}
}

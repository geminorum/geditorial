<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialDate extends gEditorialBaseCore
{

	// [Carbon - A simple PHP API extension for DateTime.](http://carbon.nesbot.com/)
	// [Easier Date/Time in Laravel and PHP with Carbon | Scotch](https://scotch.io/tutorials/easier-datetime-in-laravel-and-php-with-carbon)

	const MINUTE_IN_SECONDS =  60;                 //       60
	const   HOUR_IN_SECONDS =  60 * 60;            //     3600
	const    DAY_IN_SECONDS =  24 * 60 * 60;       //    86400
	const   WEEK_IN_SECONDS =   7 * 24 * 60 * 60;  //   604800
	const  MONTH_IN_SECONDS =  30 * 24 * 60 * 60;  //  2592000
	const   YEAR_IN_SECONDS = 365 * 24 * 60 * 60;  // 31536000

	public static function monthFirstAndLast( $year, $month, $format = 'Y-m-d H:i:s' )
	{
		$start = new \DateTime( $year.'-'.$month.'-01 00:00:00' );
		$end   = $start->modify( '+1 month -1 day -1 minute' );

		return array(
			$start->format( $format ),
			$end->format( $format ),
		);
	}

	// @SOURCE: `bp_core_get_iso8601_date()`
	// EXAMPLE: `2005-08-15T15:52:01+0000`
	// timezone should be UTC before using this
	public static function getISO8601( $timestamp = '' )
	{
		if ( ! $timestamp )
			return '';

		try {
			$date = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );

		// not a valid date, so return blank string.
		} catch ( \Exception $e ) {
			return '';
		}

		return $date->format( \DateTime::ISO8601 );
	}

	public static function htmlDateTime( $time, $gmt = NULL, $format = 'l, F j, Y', $title = FALSE )
	{
		return gEditorialHTML::tag( 'time', array(
			'datetime' => date( 'c', ( $gmt ? $gmt : $time ) ),
			'title'    => $title,
			'class'    => 'do-timeago',
		), date_i18n( $format, $time ) );
	}

	// @SOURCE: WP `human_time_diff()`
	public static function humanTimeDiff( $timestamp, $now = '', $atts = array() )
	{
		$args = self::atts( array(
			'now'    => 'Now',
			'_s_ago' => '%s ago',
			'in__s'  => 'in %s',

			'noop_minutes' => gEditorialL10n::getNooped( '%s min', '%s mins' ),
			'noop_hours'   => gEditorialL10n::getNooped( '%s hour', '%s hours' ),
			'noop_days'    => gEditorialL10n::getNooped( '%s day', '%s days' ),
			'noop_weeks'   => gEditorialL10n::getNooped( '%s week', '%s weeks' ),
			'noop_months'  => gEditorialL10n::getNooped( '%s month', '%s months' ),
			'noop_years'   => gEditorialL10n::getNooped( '%s year', '%s years' ),
		), $atts );

		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		if ( empty( $now ) )
			$now = time();

		$diff = $now - $timestamp;

		if ( 0 == $diff ) {
			return $args['now'];

		} else if ( $diff > 0 ) {

			if ( $diff < self::HOUR_IN_SECONDS ) {

				$mins = round( $diff / self::MINUTE_IN_SECONDS );

				if ( $mins <= 1 )
					$mins = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_minutes'], $mins );

			} else if ( $diff < self::DAY_IN_SECONDS && $diff >= self::HOUR_IN_SECONDS ) {

				$hours = round( $diff / self::HOUR_IN_SECONDS );

				if ( $hours <= 1 )
					$hours = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_hours'], $hours );

			} else if ( $diff < self::WEEK_IN_SECONDS && $diff >= self::DAY_IN_SECONDS ) {

				$days = round( $diff / self::DAY_IN_SECONDS );

				if ( $days <= 1 )
					$days = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_days'], $days );

			} else if ( $diff < self::MONTH_IN_SECONDS && $diff >= self::WEEK_IN_SECONDS ) {

				$weeks = round( $diff / self::WEEK_IN_SECONDS );

				if ( $weeks <= 1 )
					$weeks = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_weeks'], $weeks );

			} else if ( $diff < self::YEAR_IN_SECONDS && $diff >= self::MONTH_IN_SECONDS ) {

				$months = round( $diff / self::MONTH_IN_SECONDS );

				if ( $months <= 1 )
					$months = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_months'], $months );

			} else if ( $diff >= self::YEAR_IN_SECONDS ) {

				$years = round( $diff / self::YEAR_IN_SECONDS );
				if ( $years <= 1 )
					$years = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_years'], $years );
			}

			return sprintf( $args['_s_ago'], $since );

		} else {

			$diff = abs( $diff );

			if ( $diff < self::HOUR_IN_SECONDS ) {

				$mins = round( $diff / self::MINUTE_IN_SECONDS );

				if ( $mins <= 1 )
					$mins = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_minutes'], $mins );

			} else if ( $diff < self::DAY_IN_SECONDS && $diff >= self::HOUR_IN_SECONDS ) {

				$hours = round( $diff / self::HOUR_IN_SECONDS );

				if ( $hours <= 1 )
					$hours = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_hours'], $hours );

			} else if ( $diff < self::WEEK_IN_SECONDS && $diff >= self::DAY_IN_SECONDS ) {

				$days = round( $diff / self::DAY_IN_SECONDS );

				if ( $days <= 1 )
					$days = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_days'], $days );

			} else if ( $diff < self::MONTH_IN_SECONDS && $diff >= self::WEEK_IN_SECONDS ) {

				$weeks = round( $diff / self::WEEK_IN_SECONDS );

				if ( $weeks <= 1 )
					$weeks = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_weeks'], $weeks );

			} else if ( $diff < self::YEAR_IN_SECONDS && $diff >= self::MONTH_IN_SECONDS ) {

				$months = round( $diff / self::MONTH_IN_SECONDS );

				if ( $months <= 1 )
					$months = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_months'], $months );

			} else if ( $diff >= self::YEAR_IN_SECONDS ) {

				$years = round( $diff / self::YEAR_IN_SECONDS );
				if ( $years <= 1 )
					$years = 1;

				$since = gEditorialL10n::sprintfNooped( $args['noop_years'], $years );
			}

			return sprintf( $args['in__s'], $since );
		}
	}

	public static function momentTest()
	{
		$format = 'Y-m-d H:i:s';
		$result = array();
		$spans  = array(
			'minute',
			'hour',
			'day',
			'week',
			'month',
			'year',
		);

		$date = new \DateTime();

		$result[$date->format( $format )] = self::moment( $date->getTimestamp() );

		foreach ( $spans as $span ) {
			$date->modify( '-1 '.$span );
			$result[$date->format( $format )] = self::moment( $date->getTimestamp() ).' :: '.( time() - $date->getTimestamp() );
		}

		$date = new \DateTime();

		foreach ( $spans as $span ) {
			$date->modify( '+1 '.$span );
			$result[$date->format( $format )] = self::moment( $date->getTimestamp() ).' :: '.( time() - $date->getTimestamp() );
		}

		gEditorialHTML::tableCode( $result, TRUE, 'Moment' );
	}

	// FIXME: correct last week : http://stackoverflow.com/a/7175802/4864081
	public static function moment( $timestamp, $now = '', $atts = array() )
	{
		$args = self::atts( array(
			'now'            => 'Now',
			'just_now'       => 'Just now',
			'one_minute_ago' => 'One minute ago',
			'_s_minutes_ago' => '%s minutes ago',
			'one_hour_ago'   => 'One hour ago',
			'_s_hours_ago'   => '%s hours ago',
			'yesterday'      => 'Yesterday',
			'_s_days_ago'    => '%s days ago',
			'_s_weeks_ago'   => '%s weeks ago',
			'last_month'     => 'Last month',
			'last_year'      => 'Last year',
			'in_a_minute'    => 'in a minute',
			'in__s_minutes'  => 'in %s minutes',
			'in_an_hour'     => 'in an hour',
			'in__s_hours'    => 'in %s hours',
			'tomorrow'       => 'Tomorrow',
			'next_week'      => 'next week',
			'in__s_weeks'    => 'in %s weeks',
			'next_month'     => 'next month',
			'format_l'       => 'l',
			'format_f_y'     => 'F Y',
		), $atts );

		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		if ( empty( $now ) )
			$now = time();

		$diff = $now - $timestamp;

		if ( 0 == $diff ) {
			return $args['now'];

		} else if ( $diff > 0 ) {

			$day_diff = floor( $diff / self::DAY_IN_SECONDS );

			if ( 0 == $day_diff ) {

				if ( $diff < 60 )
					return $args['just_now'];

				if ( $diff < 60 + 60 )
					return $args['one_minute_ago'];

				if ( $diff < self::HOUR_IN_SECONDS )
					return sprintf( $args['_s_minutes_ago'], gEditorialNumber::format( floor( $diff / 60 ) ) );

				if ( $diff < 7200 )
					return $args['one_hour_ago'];

				if ( $diff < self::DAY_IN_SECONDS )
					return sprintf( $args['_s_hours_ago'], gEditorialNumber::format( floor( $diff / self::HOUR_IN_SECONDS ) ) );
			}

			if ( 1 == $day_diff )
				return $args['yesterday'];

			if ( $day_diff < 7 )
				return sprintf( $args['_s_days_ago'], gEditorialNumber::format( $day_diff ) );

			if ( $day_diff < 31 )
				return sprintf( $args['_s_weeks_ago'], gEditorialNumber::format( ceil( $day_diff / 7 ) ) );

			if ( $day_diff < 60 )
				return $args['last_month'];

			if ( $day_diff < 365 )
				return $args['last_year'];

		} else {

			$diff = abs( $diff );

			$day_diff = floor( $diff / self::DAY_IN_SECONDS );

			if ( 0 == $day_diff ) {

				if ( $diff < 60 + 60 )
					return $args['in_a_minute'];

				if ( $diff < self::HOUR_IN_SECONDS )
					return sprintf( $args['in__s_minutes'], gEditorialNumber::format( floor( $diff / 60 ) ) );

				if ( $diff < 7200 )
					return $args['in_an_hour'];

				if ( $diff < self::DAY_IN_SECONDS )
					return sprintf( $args['in__s_hours'], gEditorialNumber::format( floor( $diff / 3600 ) ) );
			}

			if ( 1 == $day_diff )
				return $args['tomorrow'];

			if ( $day_diff < 4 )
				return date_i18n( $args['format_l'], $timestamp );

			if ( $day_diff < 7 + ( 7 - date( 'w' ) ) )
				return $args['next_week'];

			if ( ceil( $day_diff / 7 ) < 4 )
				return sprintf( $args['in__s_weeks'], gEditorialNumber::format( ceil( $day_diff / 7 ) ) );

			if ( date( 'n', $timestamp ) == date( 'n' ) + 1 )
				return $args['next_month'];
		}

		return date_i18n( $args['format_f_y'], $timestamp );
	}

	public static function parts( $i18n = FALSE, $gmt = FALSE )
	{
		$now   = array();
		$parts = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );

		if ( $i18n )
			$time = apply_filters( 'string_format_i18n_back', date_i18n( 'Y-m-d H:i:s', FALSE, $gmt ) );
		else
			$time = current_time( 'mysql', $gmt );

		foreach ( preg_split( '([^0-9])', $time ) as $offset => $part )
			$now[$parts[$offset]] = $part;

		return $now;
	}

	public static function examine()
	{
		echo "current_time( 'mysql' ) returns local site time: ".current_time( 'mysql' ).'<br />';
		echo "current_time( 'mysql', TRUE ) returns GMT: ".current_time( 'mysql', TRUE ).'<br />';
		echo "current_time( 'timestamp' ) returns local site time: ".date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ).'<br />';
		echo "current_time( 'timestamp', TRUE ) returns GMT: ".date( 'Y-m-d H:i:s', current_time( 'timestamp', TRUE ) ).'<br />';
	}
}

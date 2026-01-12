<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Duration extends Base
{

	// TODO: must convert to `DataType`
	// @SEE: `Timespan` DataType


	// @SEE: `human_readable_duration()`

	public static function is( $text )
	{
		if ( self::empty( $text ) )
			return FALSE;

		return TRUE; // FIXME!
	}

	// NOTE: returns in seconds
	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::is( $sanitized ) )
			return $default;

		$sanitized = trim( str_ireplace( [
			' ',
			'.',
			'-',
			'|',
			';',
			'/',
		], ':', $sanitized ) );

		if ( in_array( $sanitized, [ '00', '00:00', '00:00:00' ], TRUE ) )
			return $default;

		$parts = explode( ':', $sanitized );
		$count = count( $parts );

		if ( $count === 1 )
			return $sanitized; // most likely is in seconds

		else if ( $count === 2 )
			$sanitized = '00:'.$sanitized;  // in `mm:ss`
			// $sanitized.= ':00';             // in `hh:mm`

		else if ( $count > 3 )
			return $default;

		return self::timeToSeconds( $sanitized );
	}

	public static function prep( $value, $field = [], $context = 'display', $icon = NULL )
	{
		if ( self::empty( $value ) )
			return '';

		$raw   = $value;
		$title = empty( $field['title'] ) ? NULL : $field['title'];

		// tries to sanitize with fallback
		if ( ! $value = self::sanitize( $value ) )
			$value = $raw;

		$value = self::secondsToTime( (int) $value );

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			$value = Number::localize( $value );

		switch ( $context ) {
			case 'raw'   : return $raw;
			case 'edit'  : return $raw;
			case 'print' : return $value;
			case 'input' : return Number::translate( $value );
			case 'export': return Number::translate( $value );
				 default : return HTML::tag( 'span', [ 'title' => $title ?: FALSE, 'class' => self::is( $raw ) ? '-is-valid' : '-is-not-valid' ], $value );
		}

		return $value;
	}

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME!
	}

	// Converts a time string (`hh:mm:ss`) to an integer for the total seconds.
	public static function timeToSeconds( $time )
	{
		list( $hours, $minutes, $seconds ) = explode( ':', $time );
		return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
	}

	// Converts an integer of seconds to time format.
	// @SEE: `Misc\MP3File::formatTime()`
	// FIXME: WTF: test this
	public static function secondsToTime( $secondsInt )
	{
		$hours   = floor( $secondsInt / 3600 );
		$minutes = floor( ( $secondsInt / 60 ) % 60 );
		$seconds = floor( $secondsInt % 60 );

		return sprintf( '%02d:%02d:%02d', $hours, $minutes, $seconds );
	}

	// @REF: https://forums.phpfreaks.com/topic/303756-show-number-of-hours-when-greater-than-24-hours/?do=findComment&comment=1545749
	// NOTE: SQL: `SELECT SEC_TO_TIME( SUM( TIME_TO_SEC(`overtime_hours`) + TIME_TO_SEC(`overtime_hours1`) ) ) FROM tablename WHERE 1=1`
	public static function testCountTime()
	{
		$total =
			self::timeToSeconds( '12:15:35' ) +
			self::timeToSeconds( '08:20:15' ) +
			self::timeToSeconds( '06:15:05' );

		// Converts the total seconds back to a time format.
		$formatted = self::secondsToTime( $total );

		echo "Total seconds: {$total}";    // `96655`
		echo "Time format: {$formatted}";  // `26:50:55`
	}
}

<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class DateParser extends Core\Base
{

	const WEEK_DAYS = [
		'يكشنبه', 'يك شنبه', // arabic
		'یکشنبه', 'یک شنبه', 'یک‌شنبه',
		'دوشنبه', 'دو شنبه',
		'سه شنبه', 'سه‌شنبه', 'سهشنبه',
		'چهارشنبه', 'چهار شنبه',
		'پنج شنبه', 'پنج‌شنبه', 'پنجشنبه',
		'جمعه',
		'شنبه',
	];

	const MONTH_STRING = [
		'ماه',
		'‌ماه',
	];

	const MONTH_NAMES = [
		'فروردین'   => 1,
		'اردیبهشت'  => 2,
		'اردی‌بهشت' => 2,
		'اردی بهشت' => 2,
		'خرداد'     => 3,
		'تیر'       => 4,
		'امرداد'    => 5,
		'مرداد'     => 5,
		'شهریور'    => 6,
		'مهر'       => 7,
		'آبان'      => 8,
		'ابان'      => 8,
		'آذر'       => 9,
		'اذر'       => 9,
		'دی'        => 10,
		'بهمن'      => 11,
		'اسفند'     => 12,
		'اسپند'     => 12,
		'سپند'      => 12,
	];

	public static function parse( $input, $calendar = 'persian', $timezone = NULL )
	{
		if ( ! $input )
			return FALSE;

		if ( ! $sanitized = Core\Number::translate( Core\Text::trim( Core\Text::singleWhitespace( $input ) ) ) )
			return FALSE;

		if ( 'gregorian' === $calendar ) {

			preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})/', $sanitized, $matches );

			if ( empty( $matches ) || ! is_array( $matches ) || count( $matches ) < 4 )
				return FALSE;

			if ( ! checkdate( $matches[2], $matches[3], $matches[1] ) )
				return FALSE;

			$parts = [
				$matches[1],
				$matches[2],
				$matches[3],
			];

			return date_create( implode( '-', $parts ), $timezone ?? new \DateTimeZone( wp_timezone_string() ) );

		} else if ( 'persian' === $calendar ) {

			if ( ! $parts = self::extractParts( $sanitized ) )
				return FALSE;

			if ( 3 !== count( $parts ) )
				return FALSE;

			if ( $parts[2] > 31 )
				$parts = array_reverse( $parts );

			if ( 2 === strlen( $parts[0] ) )
				$parts[0] = (int) sprintf( '13%d', $parts[0] );

			else if ( 3 === strlen( $parts[0] ) && in_array( substr( $parts[0], 1 ), [ '1', '2', '3', '4' ] ) )
				$parts[0] = (int) sprintf( '1%d', $parts[0] );
		}

		if ( ! $date = gEditorial\Datetime::makeMySQLFromInput( implode( '-', $parts ), NULL, $calendar ) )
			return FALSE;

		return date_create( $date, $timezone ?? new \DateTimeZone( wp_timezone_string() ) );
	}

	public static function extractParts( $input )
	{
		$sanitized = $input;

		$sanitized = Core\Text::trim( str_ireplace( static::WEEK_DAYS, '', $sanitized ) );
		$sanitized = Core\Text::trim( str_ireplace( static::MONTH_STRING , '', $sanitized ) );
		$sanitized = Core\Text::trim( str_ireplace( [ '\\', '/', '-', '،', ',', ';', '؛' ] , '|', $sanitized ) );
		$sanitized = Core\Text::trim( str_ireplace( [ ' |', '| ', ' | ', '  |  ' ] , '|', $sanitized ) );
		// $sanitized = Core\Text::trim( preg_replace( '/\s+[|]\s+/u', '|', $sanitized ) );

		$sanitized = NumbersInPersian::textOrdinalToNumbers( $sanitized, 31 );

		$months  = array_map( 'preg_quote', array_keys( static::MONTH_NAMES ) );
		$pattern = '/(?=(^|.*))([|\s]?)('.implode( '|', $months ).')([|\s]?)(?=($|.*))/iu';

		$sanitized = preg_replace_callback( $pattern, [ __CLASS__, 'extractParts_months_callback' ], ' '.$sanitized.' ' );

		return Core\Arraay::prepNumeral( array_reverse( explode( '|', Core\Text::trim( $sanitized ) ) ) );
	}

	public static function extractParts_months_callback( $matches )
	{
		$month = trim( $matches[3], ' |' );

		if ( ! array_key_exists( $month, static::MONTH_NAMES ) )
			return $matches[0];

		return sprintf( '%s%s%s',
			trim( $matches[2] ) ?: '|',
			static::MONTH_NAMES[$month],
			trim( $matches[4] ) ?: '|'
		);
	}
}

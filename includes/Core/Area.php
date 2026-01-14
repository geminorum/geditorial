<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Area extends Base
{

	// TODO: convert to `DataType`


	// like `latlng`
	// keeping data: `{$width},{$height}`
	// display data: `{$width}&times;{$height} ({$square})`

	/**
	 * `MeasurementUnitArea`
	 *
	 * ## Fields
	 *
	 * | Name | Description |
	 * |  --- | --- |
	 * | `IMPERIAL_ACRE`            | The area is measured in acres.              |
	 * | `IMPERIAL_SQUARE_INCH`     | The area is measured in square inches.      |
	 * | `IMPERIAL_SQUARE_FOOT`     | The area is measured in square feet.        |
	 * | `IMPERIAL_SQUARE_YARD`     | The area is measured in square yards.       |
	 * | `IMPERIAL_SQUARE_MILE`     | The area is measured in square miles.       |
	 * | `METRIC_SQUARE_CENTIMETER` | The area is measured in square centimeters. |
	 * | `METRIC_SQUARE_METER`      | The area is measured in square meters.      |
	 * | `METRIC_SQUARE_KILOMETER`  | The area is measured in square kilometers.  |
	 */

	public static function is( $data )
	{
		if ( self::empty( $data ) )
			return FALSE;

		return TRUE; // FIXME!
	}

	// FIXME: check for suffix and compare to data_unit
	// -- convert to the target unit: @SEE https://github.com/lvivier/meters/blob/master/index.js
	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		$sanitized = Number::translate( Text::trim( $input ) );

		if ( ! self::is( $sanitized ) )
			return $default;

		$sanitized = trim( str_ireplace( [
			'-',
			'|',
			';',
		], '', $sanitized ) );

		// if ( ! empty( $field['data_unit'] ) ) {}

		// if ( in_array( $sanitized, [ '00', '00:00', '00:00:00' ], TRUE ) )
		// 	return $default;

		return $sanitized;
	}

	public static function prep( $value, $field = [], $context = 'display', $icon = NULL )
	{
		if ( self::empty( $value ) )
			return '';

		$raw   = $value;
		$title = empty( $field['title'] ) ? FALSE : $field['title'];

		// tries to sanitize with fallback
		if ( ! $value = self::sanitize( $value ) )
			$value = $raw;

		$copy = $value;

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) )
			$value = Number::localize( $value );

		switch ( $context ) {
			case 'raw'   : return $raw;
			case 'edit'  : return $raw;
			case 'print' : return $value;
			case 'input' : return Number::translate( $value );
			case 'export': return Number::translate( $value );
				 default : return HTML::tag( 'span', [
					'title' => $title,
					'class' => [
						self::is( $raw ) ? '-is-valid' : '-is-not-valid',
						'do-clicktoclip',
					],
					'data' => [
						'clipboard-text' => $copy,
					],
				], $value );
		}

		return $value;
	}

	public static function getHTMLPattern()
	{
		return FALSE; // FIXME!
	}
}

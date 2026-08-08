<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Distance extends Base
{

	// TODO: must convert to `DataType`

	public static function is( mixed $input ): bool
	{
		if ( self::empty( $input ) )
			return FALSE;

		return TRUE; // FIXME!
	}

	// FIXME: check for suffix and compare to data_unit
	// -- convert to the target unit: @SEE https://github.com/lvivier/meters/blob/master/index.js
	public static function sanitize( mixed $input, mixed $default = '', ?array $field = [], ?string $context = 'save' ): mixed
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

		if ( ! empty( $field['data_unit'] ) ) {

		}

		// if ( in_array( $sanitized, [ '00', '00:00', '00:00:00' ], TRUE ) )
		// 	return $default;

		return $sanitized;
	}

	public static function prep( mixed $value, ?array $field = [], ?string $context = 'display', mixed $icon = NULL ): string
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

	public static function getHTMLPattern(): string|false
	{
		return FALSE; // FIXME!
	}

	/**
	 * Calculate a new coordinate based on start, distance and bearing.
	 * @source https://www.splitbrain.org/blog/2010-06/29-calculate-a-destination-coordinate-based-on-distance-and-bearing-in-php
	 * @see https://www.movable-type.co.uk/scripts/latlong.html#destPoint
	 * @see https://stackoverflow.com/questions/877524/calculating-coordinates-given-a-bearing-and-a-distance/879531#879531
	 *
	 * @param $start array - start coordinate as decimal lat/lon pair
	 * @param $dist  float - distance in kilometers
	 * @param $brng  float - bearing in degrees (compass direction)
	 */
	public static function geoDestination( array $start, float $dist, float $brng )
	{
		$dist = $dist / 6371.01;           // Earth's radius in km
		$brng = self::geoDestinationToRad( $brng );
		$lat1 = self::geoDestinationToRad( $start[0] );
		$lon1 = self::geoDestinationToRad( $start[1] );

		$lat2 = asin( sin( $lat1 ) * cos( $dist ) + cos( $lat1 ) * sin( $dist ) * cos( $brng ) );
		$lon2 = $lon1 + atan2( sin( $brng ) * sin( $dist ) * cos( $lat1 ), cos( $dist ) - sin( $lat1 ) * sin( $lat2 ) );
		$lon2 = fmod( ( $lon2 + 3 * pi() ), ( 2 * pi() ) ) - pi();

		return [
			self::geoDestinationToDeg( $lat2 ),
			self::geoDestinationToDeg( $lon2 )
		];
	}

	public static function geoDestinationToRad( $deg )
	{
		return $deg * pi() / 180;
	}

	public static function geoDestinationToDeg( $rad )
	{
		return $rad * 180 / pi();
	}
}

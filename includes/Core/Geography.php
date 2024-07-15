<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Geography extends Base
{
	// @SEE: https://github.com/brick/geo
	// @SEE: https://github.com/jeroendesloovere/distance

	// @REF: https://snippets.ir/1269/calculate-distance-between-two-points-in-php.html
	public static function getDistance( $latitude1, $longitude1, $latitude2, $longitude2 )
	{
		$theta      = $longitude1 - $longitude2;
		$miles      = ( sin( deg2rad( $latitude1 ) ) * sin( deg2rad( $latitude2 ) ) ) + ( cos( deg2rad( $latitude1 ) ) * cos( deg2rad( $latitude2 ) ) * cos( deg2rad( $theta ) ) );
		$miles      = acos( $miles );
		$miles      = rad2deg( $miles );
		$miles      = $miles * 60 * 1.1515;
		$feet       = $miles * 5280;
		$yards      = $feet / 3;
		$kilometers = $miles * 1.609344;
		$meters     = $kilometers * 1000;

		return compact( 'miles', 'feet', 'yards', 'kilometers', 'meters' );
	}

	// `(42.32783298989135, -70.99989162915041)`
	// @REF: https://stackoverflow.com/a/68931818
	// @REF: https://3v4l.org/daAqb
	public static function extractLatLng( $string )
	{
		return sscanf( sprintf( '(%s)', $string ), '(%[^,], %[^)]' );
	}

	public static function prepLatLng( $input, $wrap = FALSE )
	{
		$string = Number::translate( $input );

		if ( self::validateLatLng( $string ) ) {
			$string = self::sanitizeLatLng( $string );
			return $wrap ? '<span class="latlng -valid">&#8206;'.$string.'&#8207;<span>' : $string;
		}

		// NOTE: returns the original if not valid
		return $wrap ? '<span class="latlng -not-valid">&#8206;'.$input.'&#8207;<span>' : $input;
	}

	public static function sanitizeLatLng( $string, $translate = FALSE )
	{
		if ( $translate )
			$string = Number::translate( $string );

		return trim( str_ireplace( [ '-', ':', ' ' ], '', $string ) );
	}

	public static function validateLatLng( $string )
	{
		return TRUE; // FIXME: WTF?!
	}
}

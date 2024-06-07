<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Geography extends Base
{
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
}

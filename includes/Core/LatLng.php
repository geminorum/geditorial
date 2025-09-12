<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class LatLng extends Base
{
	// @SEE: `DataType::LatLng`

	// $point1 = array('lat' => 41.008610, 'long' => 28.971111); // Istanbul
	// $point2 = array('lat' => 39.925018, 'long' => 32.836956); // Anitkabir

	/**
	 * Verifies that a coordinate is valid.
	 *
	 * @param string|array $data
	 * @return bool
	 */
	public static function is( $data )
	{
		if ( self::empty( $data ) )
			return FALSE;

		if ( ! is_array( $data ) )
			$data = self::extract( $data );

		return self::validate( $data[0], $data[1] );
	}

	/**
	 * Validates a given coordinate
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1?permalink_comment_id=3204977#gistcomment-3204977
	 *
	 * @param float|int|string $lat
	 * @param float|int|string $long
	 * @return bool
	 */
	public static function validate( $lat, $long )
	{
		return preg_match( '/\A[+-]?(?:90(?:\.0{1,18})?|\d(?(?<=9)|\d?)\.\d{1,18})\z/x', $lat )
			&& preg_match( '/\A[+-]?(?:180(?:\.0{1,18})?|(?:1[0-7]\d|\d{1,2})\.\d{1,18})\z/x', $long );
	}

	// `(42.32783298989135, -70.99989162915041)`
	// @REF: https://stackoverflow.com/a/68931818
	// @REF: https://3v4l.org/daAqb
	public static function extract( $string )
	{
		return sscanf( sprintf( '(%s)', $string ), '(%[^,], %[^)]' );
	}

	public static function prep( $input, $wrap = FALSE )
	{
		// NOTE: returns the original if not valid
		if ( ! self::is( $input ) )
			return $wrap
				? HTML::tag( 'span', [ 'class' => [ 'latlng', '-is-not-valid' ] ], HTML::wrapLTR( $input ) )
				: $input;

		return $wrap
			? HTML::tag( 'span', [ 'class' => [ 'latlng', '-is-valid' ] ], HTML::wrapLTR( self::sanitize( $input ) ) )
			: self::sanitize( $input );
	}

	// @SEE: https://github.com/jakubvalenta/geoshare
	public static function sanitize( $input )
	{
		$sanitized = Number::translate( Text::trim( htmlspecialchars_decode( $input ) ) );

		if ( Text::starts( $sanitized, 'geo:' ) )
			return Text::stripPrefix( $sanitized, 'geo:' );

		// Extracts `lat/lng` from URLs
		if ( URL::isValid( $sanitized ) ) {

			$url = URL::parseDeep( $sanitized );

			switch ( URL::untrail( $url['base'] ) ) {

				case 'maps.google.com':
				case 'maps.google.com/maps':
				case 'maps.apple.com':

					if ( isset( $url['query']['ll'] ) )
						return $url['query']['ll'];

					if ( isset( $url['query']['q'] ) )
						return $url['query']['q'];

					break;

				case 'openstreetmap.org':
				case 'www.openstreetmap.org':

					if ( isset( $url['query']['mlat'] ) && isset( $url['query']['mlon'] ) )
						return sprintf( '%s,%s', $url['query']['mlat'], $url['query']['mlon'] );

					break;


				case 'bing.com':
				case 'www.bing.com':
				case 'www.bing.com/maps':

					if ( isset( $url['query']['cp'] ) && Text::has( $url['query']['cp'], '~' ) )
						return vsprintf( '%s,%s', explode( '~', $url['query']['cp'], 2 ) );

					break;

				case 'balad.ir':
				case 'balad.ir/location':

					if ( isset( $url['query']['latitude'] ) && isset( $url['query']['longitude'] ) )
						return sprintf( '%s,%s', $url['query']['latitude'], $url['query']['longitude'] );

					break;

				case 'map.parsijoo.ir':

					if ( isset( $url['query']['lat'] ) && isset( $url['query']['lon'] ) )
						return sprintf( '%s,%s', $url['query']['lat'], $url['query']['lon'] );
			}

			return '';
		}

		// Extracts `lat/lng` from https://plus.codes
		if ( class_exists( '\YOCLIB\\OpenLocationCode\\OpenLocationCode' ) ) {

			/**
			 * @package `yocto/yoclib-openlocationcode`
			 * @link https://github.com/yocto/yoclib-openlocationcode-php
			 */
			if ( \YOCLIB\OpenLocationCode\OpenLocationCode::isValidCode( $sanitized ) ) {

				$code = new \YOCLIB\OpenLocationCode\OpenLocationCode( $sanitized );
				$data = $code->decode();

				return sprintf( '%s,%s',
					$data->getCenterLatitude(),
					$data->getCenterLongitude()
				);
			}
		}

		return Text::trim( str_ireplace( [ '-', ':', ' ' ], '', $sanitized ) );
	}

    /**
     * Get distance between two coordinates
	 *
	 * Calculate distance between two or multiple locations
	 * using Mathematic functions.
	 *
	 * @source `JeroenDesloovere\Distance::between()`
	 * @link https://github.com/jeroendesloovere/distance
	 * @author Jeroen Desloovere <info@jeroendesloovere.be>
	 * @source https://www.geodatasource.com/developers/php
     *
     * @param float $latitude1
     * @param float $longitude1
     * @param float $latitude2
     * @param float $longitude2
     * @param int $decimals: The amount of decimals
     * @param string $unit: `km`, `n`, `m`
	 * @return float
     */
    public static function distanceBetween( $latitude1, $longitude1, $latitude2, $longitude2, $decimals = 1, $unit = 'km' )
	{
        // define calculation variables
        $theta    = $longitude1 - $longitude2;
        $distance = ( sin( deg2rad ($latitude1 ) ) * sin( deg2rad( $latitude2 ) ) )
            + ( cos( deg2rad( $latitude1 ) ) * cos( deg2rad( $latitude2 ) ) * cos( deg2rad( $theta ) ) );
        $distance = acos( $distance );
        $distance = rad2deg( $distance );
        $distance = $distance * 60 * 1.1515;

		// Kilometers
        if ( 'km' === $unit )
            $distance = $distance * 1.609344; // redefine distance

		// Nautical Miles
		else if ( 'n' === $unit )
			return $distance * 0.8684;

		// Miles
        return round( $distance, $decimals ); // return with one decimal
    }

    /**
     * Get closest location from all locations
	 *
	 * Calculate distance between two or multiple locations
	 * using Mathematic functions.
	 *
	 * @source `JeroenDesloovere\Distance::getClosest()`
	 * @link https://github.com/jeroendesloovere/distance
	 * @author Jeroen Desloovere <info@jeroendesloovere.be>
     *
	 * @param float $latitude
	 * @param float $longitude
	 * @param array $items = `[ [ 'latitude' => 'x', 'longitude' => 'x' ], [...] ]`
	 * @param int $decimals The amount of decimals
	 * @param string $unit
     * @return array The item which is the closest + 'distance' to it.
     */
    public static function distanceGetClosest( $latitude, $longitude, $items, $decimals = 1, $unit = 'km' )
	{
        $distances = [];

        foreach ( $items as $key => $item ) {

            $distance = self::distanceBetween(
                $latitude,
                $longitude,
                $item['latitude'],
                $item['longitude'],
                10,
                $unit
            );

            $distances[$distance] = $key;

            // adds rounded distance to array
            $items[$key]['distance'] = round( $distance, $decimals );
        }

        // Returns the item with the closest distance
        return $items[$distances[min( array_keys( $distances ) )]];
    }

	// @REF: https://www.geeksforgeeks.org/haversine-formula-to-find-distance-between-two-points-on-a-sphere/
	// @REF: https://stackoverflow.com/a/46218890
	// @REF: http://www.hackingwithphp.com/4/6/6/mathematical-constants
	public static function haversine( $lat1, $lon1, $lat2, $lon2 )
	{
		// distance between latitudes and longitudes
		$dLat = ($lat2 - $lat1) * M_PI / 180.0;
		$dLon = ($lon2 - $lon1) * M_PI / 180.0;

		// convert to radians
		$lat1 = ($lat1) * M_PI / 180.0;
		$lat2 = ($lat2) * M_PI / 180.0;

		// apply formulae
		$a   = pow( sin( $dLat / 2 ), 2 ) + pow( sin( $dLon / 2 ), 2 ) * cos( $lat1 ) * cos( $lat2 );
		$rad = 6371;
		$c   = 2 * asin( sqrt( $a ) );

		return $rad * $c;
	}

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

	/**
	 * Validates a given latitude $lat
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1
	 *
	 * @param float|int|string $lat Latitude
	 * @return bool `true` if $lat is valid, `false` if not
	 */
	public static function validateLatitude( $lat )
	{
		return preg_match( '/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/', $lat );
	}

	/**
	 * Validates a given longitude $long
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1
	 *
	 * @param float|int|string $long Longitude
	 * @return bool `true` if $long is valid, `false` if not
	 */
	public static function validateLongitude( $long )
	{
		return preg_match( '/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/', $long );
	}

	/**
	 * Validates a given coordinate
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1
	 *
	 * @param float|int|string $lat Latitude
	 * @param float|int|string $long Longitude
	 * @return bool `true` if the coordinate is valid, `false` if not
	 */
	public static function validateLatLong( $lat, $long )
	{
		return preg_match( '/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $lat.','.$long );
	}
}

<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class LatLng extends Base
{
	// @SEE: `DataType::LatLng`

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
	 * Validates given coordinates.
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1?permalink_comment_id=3204977#gistcomment-3204977
	 * @source https://web.archive.org/web/20241109173648/https://www.beliefmedia.com.au/code/php-snippets/validate-latitude-longitude
	 *
	 * Latitude coordinate is between `-90` and `90`.
	 * Longitude coordinate is between `-180` and `180`.
	 *
	 * @param float|int|string $lat
	 * @param float|int|string $long
	 * @return bool
	 */
	public static function validate( $lat, $long )
	{
		return preg_match( '/\A[+-]?(?:90(?:\.0{1,18})?|\d(?(?<=9)|\d?)\.\d{1,18})\z/x', $lat )
		// return preg_match( '/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d{1,6}$/', $lat )
		// return preg_match( '/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/', $lat )
			&& preg_match( '/\A[+-]?(?:180(?:\.0{1,18})?|(?:1[0-7]\d|\d{1,2})\.\d{1,18})\z/x', $long );
			// && preg_match( '/^-?([1]?[1-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d{0,6}$/', $long );
			// && preg_match( '/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/', $long );
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
	public static function sanitize( $input, $default = '', $field = [], $context = 'save' )
	{
		if ( self::empty( $input ) )
			return $default;

		if ( is_array( $input ) && $array = self::extractFromArray( $input ) )
			return $array;

		if ( is_object( $input ) && $object = self::extractFromObject( $input ) )
			return $object;

		$original  = $input;
		$sanitized = Number::translate( Text::trim( htmlspecialchars_decode( $input ) ) );

		if ( Text::starts( $sanitized, 'geo:' ) ) {

			// EXAMPLE: `geo:41.40338,2.17403?q=41.40338%2C2.17403`
			if ( Text::has( $sanitized, '?' ) )
				list( $sanitized ) = explode( '?', $sanitized );

			return Text::stripPrefix( $sanitized, 'geo:' );
		}

		// Extracts `lat/lng` from URLs
		if ( URL::isValid( $sanitized ) )
			return self::extractFromURL( $sanitized, '' );

		if ( Text::has( $sanitized, [ '°', 'º', '\'', '"', '′', '″' ] ) ) {

			if ( $dms = self::extractFromDMS( $sanitized ) )
				return $dms;
		}

		if ( $utm = self::extractFromUTM( $sanitized ) )
			return $utm;

		// Extracts `lat/lng` from https://plus.codes
		if ( $pluscode = self::extractFromPlusCode( $sanitized ) )
			return $pluscode;

		return Text::trim( str_ireplace( [ '-', ':', ' ' ], '', $sanitized ) );
	}

	public static function extractFromArray( $data, $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( Arraay::isList( $data ) && 2 === count( $data ) )
			return vsprintf( '%s,%s', $data );

		if ( isset( $data['latitude'] ) && isset( $data['longitude'] ) )
			return sprintf( '%s,%s', $data['latitude'], $data['longitude'] );

		if ( isset( $data['lat'] ) && isset( $data['lng'] ) )
			return sprintf( '%s,%s', $data['lat'], $data['lng'] );

		if ( isset( $data['lat'] ) && isset( $data['lon'] ) )
			return sprintf( '%s,%s', $data['lat'], $data['lon'] );

		if ( isset( $data['lat'] ) && isset( $data['long'] ) )
			return sprintf( '%s,%s', $data['lat'], $data['long'] );

		return $fallback;
	}

	// Standard Formats
	// https://www.npmjs.com/package/haversine-distance
	// { latitude: 37.8136, longitude: 144.9631 } // (object)
	// { lat: 37.8136, lng: 144.9631 } // lat, lng (object)
	// { lat: 33.8650, lon: 151.2094 } // lat, lon (object)
	// [ 144.9631, 37.8136 ]; // GeoJSON (array)
	public static function extractFromObject( $data, $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( isset( $data->latitude ) && isset( $data->longitude ) )
			return sprintf( '%s,%s', $data->latitude, $data->longitude );

		if ( isset( $data->lat ) && isset( $data->lng ) )
			return sprintf( '%s,%s', $data->lat, $data->lng );

		if ( isset( $data->lat ) && isset( $data->lon ) )
			return sprintf( '%s,%s', $data->lat, $data->lon );

		if ( isset( $data->lat ) && isset( $data->long ) )
			return sprintf( '%s,%s', $data->lat, $data->long );

		return $fallback;
	}

	public static function extractFromUTM( $data, $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( ! class_exists( 'geminorum\\gEditorial\\Misc\\LangLongUTM' ) )
			return $fallback;

		$sanitized = Text::normalizeWhitespace( $data );

		// https://regex101.com/r/McZlIe/1
		$pattern = '/^(?<zone>\d{1,2}\w)\s(?<easting>[-+]?\d{5,6})\s(?<northing>[-+]?\d{7})$/'; // `39S 535262 3949513`

		if ( ! preg_match( $pattern, $sanitized, $parsed ) )
			return $fallback;

		$geopint = new \geminorum\gEditorial\Misc\LangLongUTM();
		$latlng  = $geopint->convertUtmToLatLng( $parsed['zone'], $parsed['easting'], $parsed['northing'] );

		return vsprintf( '%s,%s', $latlng );
	}

	public static function extractFromDMS( $data, $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		$sanitized = Text::normalizeWhitespace( $data );

		if ( Text::has( $sanitized, ',' ) ) {

			$dms = explode( ',', $sanitized, 2 );

		} else if ( 1 === substr_count( $sanitized, ' ' ) ) {

			$dms = explode( ' ', $sanitized, 2 );

		} else if ( preg_match( '/\s([nsewNSEW])\s/', $sanitized, $matches ) ) {

			// EXAMPLE: `34.500741° N 50.314809° E`
			$parts = preg_split( '/\s([nsewNSEW])\s/', $sanitized.' ', 3, PREG_SPLIT_DELIM_CAPTURE );

			if ( 4 > count( $parts ) )
				return $fallback;

			$dms = [
				$parts[0].$parts[1],
				$parts[2].$parts[3],
			];

		} else {

			return $fallback; // no way to split!
		}

		if ( ! $lat = self::convertDMSToDecimal( $dms[0] ) )
			return $fallback;

		if ( ! $long = self::convertDMSToDecimal( $dms[1] ) )
			return $fallback;

		return sprintf( '%s,%s', $lat, $long );
	}

	public static function extractFromURL( $data, $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		$url = URL::parseDeep( $data );

		switch ( URL::untrail( $url['base'] ) ) {

			case 'geohack.toolforge.org/geohack.php':
			case 'tools.wmflabs.org/geohack/geohack.php': // old links

				if ( isset( $url['query']['params'] ) ) {

					// EXAMPLE: `35_41_20_N_51_23_23_E_`
					$dms = trim( str_ireplace( '_', ' ', $url['query']['params'] ) );
					$dms = preg_split( '/([nsewNSEW])\s/', $dms.' ', 4, PREG_SPLIT_DELIM_CAPTURE );

					if ( count( $dms ) > 3 )
						return self::extractFromDMS( vsprintf( '%s%s,%s%s', $dms ) );
				}

				break;

			case 'www.openrailwaymap.org':

				if ( isset( $url['query']['lat'] ) && isset( $url['query']['lon'] ) )
					return sprintf( '%s,%s', $url['query']['lat'], $url['query']['lon'] );

				break;

			case 'www.latlong.net/c':

				if ( isset( $url['query']['lat'] ) && isset( $url['query']['long'] ) )
					return sprintf( '%s,%s', $url['query']['lat'], $url['query']['long'] );

				break;

			case 'wikinearby.toolforge.org':

				if ( isset( $url['query']['q'] ) )
					return $url['query']['q'];

				break;

			case 'www.google.com/maps':

				if ( isset( $url['query']['q'] ) && Text::starts( $url['query']['q'], 'loc:' ) )
					return Text::stripPrefix( $url['query']['q'], 'loc:' );

				if ( isset( $url['query']['q'] ) )
					return $url['query']['q'];

				break;

			case 'maps.google.com':
			case 'maps.google.com/maps':
			case 'ditu.google.com/maps':
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

		if ( Text::starts( $data, 'https://www.google.com/maps/place/' ) ) {

			$pattern = '/@([+-]?\d{1,3}\.\d{1,18}),([+-]?\d{1,3}\.\d{1,18})/';

			if ( \preg_match( $pattern, $data, $matches ) )
				return sprintf( '%s,%s', $matches[1], $matches[2] );

		} else if ( Text::starts( $data, 'https://balad.ir/p/' ) ) {

			if ( ! empty( $url['fragment'] ) )
				return vsprintf( '%s,%s', array_slice( explode( '/', $url['fragment'] ), 1 ) );

		} else if ( Text::starts( $data, 'https://plus.codes/' ) ) {

			// EXAMPLE: `https://plus.codes/8H7HHR42+M6`
			if ( $pluscode = self::extractFromPlusCode( Text::stripPrefix( $data, 'https://plus.codes/' ) ) )
				return $pluscode;
		}

		return $fallback;
	}

	public static function extractFromPlusCode( $data, $fallback = FALSE, $reference = NULL )
	{
		if ( self::empty( $data ) )
			return $fallback;

		/**
		 * @package `yocto/yoclib-openlocationcode`
		 * @link https://github.com/yocto/yoclib-openlocationcode-php
		 */
		if ( ! @class_exists( '\YOCLIB\\OpenLocationCode\\OpenLocationCode' ) )
			return $fallback;

		if ( ! \YOCLIB\OpenLocationCode\OpenLocationCode::isValidCode( $data ) )
			return $fallback;

		try {

			$code = new \YOCLIB\OpenLocationCode\OpenLocationCode( $data );

			if ( $code->isFull() ) {

				$area = $code->decode();

				return sprintf( '%s,%s',
					$area->getCenterLatitude(),
					$area->getCenterLongitude()
				);

			} else if ( $reference ) {

				$latlng = self::extract( $reference );
				$code   = $code->recover( $latlng[0], $latlng[1] );
				$area   = $code->decode();

				return sprintf( '%s,%s',
					$area->getCenterLatitude(),
					$area->getCenterLongitude()
				);
			}

		} catch ( \Exception $e ) {

			self::_log( 'OpenLocationCode Exception: '.$e->getMessage() );
		}

		return $fallback;
	}

	/**
	 * Converts `DMS` ( Degrees / minutes / seconds ) to decimal format.
	 * @source https://stackoverflow.com/a/22317686
	 *
	 * @param float|int|string $degrees
	 * @param float|int|string $minutes
	 * @param float|int|string $seconds
	 * @return float
	 */
	public static function convertDMStoDD( $degrees, $minutes, $seconds )
	{
		return $degrees + ( ( ( $minutes * 60 ) + $seconds ) / 3600 );
	}

	/**
	 * Converts decimal format to `DMS` ( Degrees / minutes / seconds ).
	 * @source https://stackoverflow.com/a/22317686
	 *
	 * @param string $decimal
	 * @return array
	 */
	public static function convertDDtoDMS( $decimal )
	{
		$vars    = explode( '.', $decimal );
		$degrees = $vars[0];
		$tempma  = "0.".$vars[1];

		$tempma  = $tempma * 3600;
		$minutes = floor( $tempma / 60 );
		$seconds = $tempma - ( $minutes * 60 );

		return compact( 'degrees', 'minutes', 'seconds' );
	}

	/**
	 * Converts `DMS` (degrees / minutes / seconds) to decimal degrees.
	 * @author Todd Trann - May 22, 2015
	 * @source https://github.com/prairiewest/phpconvertdmstodecimal
	 *
	 * @param string $latlng
	 * @return string|false
	 */
	public static function convertDMSToDecimal( $latlng )
	{
		$valid           = FALSE;
		$decimal_degrees = 0;
		$degrees         = 0;
		$minutes         = 0;
		$seconds         = 0;
		$direction       = 1;

		// Determine if there are extra periods in the input string
		$num_periods = substr_count( $latlng, '.' );

		if ( $num_periods > 1 ) {

			$temp = preg_replace( '/\./', ' ', $latlng, $num_periods - 1 );  // Replace all but last period with delimiter
			$temp = trim( preg_replace( '/[a-zA-Z]/', '', $temp ) );         // When counting chunks we only want numbers

			$chunk_count = count( explode( " ", $temp ) );

			if ( $chunk_count > 2 ) {
				$latlng = preg_replace( '/\./', ' ', $latlng, $num_periods - 1); // Remove last period
			} else {
				$latlng = str_replace( ".", " ", $latlng ); // Remove all periods, not enough chunks left by keeping last one
			}
		}

		// Remove unneeded characters
		$latlng = str_replace( [
			'º',
			'°',
			'′',
			'″',
			"'",
			'"',
			"\t",
			'  ',
		], ' ', trim( $latlng ) );

		// remove all but first dash
		$latlng = substr( $latlng, 0, 1 ).str_replace( '-', ' ', substr( $latlng, 1 ) );

		if ( ! $latlng )
			return FALSE;

		if ( preg_match( '/^([nsewoNSEWO]?)\s*(\d{1,3})\s+(\d{1,3})\s*(\d*\.?\d*)$/', $latlng, $matches ) ) {

			// `DMS` with the direction at the start of the string
			$valid   = TRUE;
			$degrees = intval( $matches[2] );
			$minutes = intval( $matches[3] );
			$seconds = floatval( $matches[4] );

			if ( strtoupper( $matches[1] ) === 'S'  || strtoupper( $matches[1] ) === 'W' )
				$direction = -1;

		} else if ( preg_match( '/^(-?\d{1,3})\s+(\d{1,3})\s*(\d*(?:\.\d*)?)\s*([nsewoNSEWO]?)$/', $latlng, $matches ) ) {

			// `DMS` with the direction at the end of the string
			$valid   = TRUE;
			$degrees = intval( $matches[1] );
			$minutes = intval( $matches[2] );
			$seconds = floatval( $matches[3] );

			if ( strtoupper( $matches[4] ) === 'S' || strtoupper( $matches[4] ) === 'W' || $degrees < 0 ) {
				$direction = -1;
				$degrees   = abs( $degrees );
			}
		}

		if ( $valid ) {

			// A match was found, do the calculation
			$decimal_degrees = ( $degrees + ( $minutes / 60 ) + ( $seconds / 3600 ) ) * $direction;

		} else {

			if ( preg_match( '/^([nsewNSEW]?)\s*(\d+(?:\.\d+)?)$/', $latlng, $matches ) ) {

				// Decimal degrees with a direction at the start of the string
				$valid = TRUE;

				if ( strtoupper( $matches[1] ) === 'S' || strtoupper( $matches[1] ) === 'W' )
					$direction = -1;

				$decimal_degrees = $matches[2] * $direction;

			} else if ( preg_match( '/^(-?\d+(?:\.\d+)?)\s*([nsewNSEW]?)$/', $latlng, $matches ) ) {

				// Decimal degrees with a direction at the end of the string
				$valid = TRUE;

				if ( strtoupper( $matches[2] ) === 'S' || strtoupper( $matches[2] ) === 'W' || $degrees < 0 ) {
					$direction = -1;
					$degrees   = abs( $degrees );
				}

				$decimal_degrees = $matches[1] * $direction;
			}
		}

		return $valid
			? preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $decimal_degrees )
			: FALSE;
	}

	/**
	 * Get distance between two coordinates
	 *
	 * Calculate distance between two or multiple locations
	 * using Mathematics functions.
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
	 * using Mathematics functions.
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

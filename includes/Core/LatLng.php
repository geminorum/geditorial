<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress;

class LatLng extends Base
{
	// @SEE: `DataType::LatLng`

	/**
	 * Verifies that a coordinate is valid.
	 *
	 * @param mixed $input
	 * @return bool
	 */
	public static function is( mixed $input ): bool
	{
		if ( self::empty( $input ) )
			return FALSE;

		if ( ! is_array( $input ) )
			$input = self::extract( $input );

		return self::validate( $input[0], $input[1] );
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
	public static function sanitize( mixed $input, mixed $default = '', ?array $field = [], ?string $context = 'save' ): mixed
	{
		if ( self::empty( $input ) )
			return $default;

		if ( is_array( $input ) && $array = self::extractFromArray( $input ) )
			return $array;

		if ( is_object( $input ) && $object = self::extractFromObject( $input ) )
			return $object;

		$original  = $input;
		$sanitized = Number::translate( Text::trim( htmlspecialchars_decode( $input ) ) );

		if ( $geo_link = self::extractFromGeoLink( $sanitized, '' ) )
			return $geo_link;

		// if ( $custom_scheme = self::extractFromCustomScheme( $sanitized, '' ) )
		// 	return $custom_scheme;

		// Extracts `lat/lng` from URLs
		if ( URL::isValid( $sanitized ) )
			return self::extractFromURL( $sanitized, '' );

		if ( Text::has( $sanitized, [ '°', 'º', '\'', '"', '′', '″' ] ) ) {

			if ( $dms = self::extractFromDMS( $sanitized ) )
				return $dms;
		}

		if ( $utm = self::extractFromUTM( $sanitized ) )
			return $utm;

		// Extracts `lat/lng` from plus codes format
		if ( $pluscode = self::extractFromPlusCode( $sanitized ) )
			return $pluscode;

		$sanitized = Text::trim( str_ireplace( [ '-', ':', ' ' ], '', $sanitized ) );

		return apply_filters( 'nucleus_datatype_latlng_sanitize',
			$sanitized,
			$original,
			$default,
			$field,
			$context,
		);
	}

	public static function extractFromArray( array $data, mixed $fallback = FALSE ): mixed
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
	// `{ latitude: 37.8136, longitude: 144.9631 }` // (object)
	// `{ lat: 37.8136, lng: 144.9631 }` // lat, lng (object)
	// `{ lat: 33.8650, lon: 151.2094 }` // lat, lon (object)
	// `[ 144.9631, 37.8136 ];` // GeoJSON (array)
	public static function extractFromObject( object $data, mixed $fallback = FALSE ): mixed
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

	// EXAMPLE: `geo:52.05539,-2.71519?Z=6`
	// EXAMPLE: `geo:41.40338,2.17403?q=41.40338%2C2.17403`
	public static function extractFromGeoLink( mixed $data, mixed $fallback = FALSE ): mixed
	{
		if ( ! $data = Text::force( $data ) )
			return $fallback;

		if ( Text::starts( $data, 'geo:' ) ) {


			if ( Text::has( $data, '?' ) )
				list( $data ) = explode( '?', $data );

			return Text::stripPrefix( $data, 'geo:' );
		}

		return $fallback;
	}

	public static function extractFromUTM( mixed $data, mixed $fallback = FALSE ): mixed
	{
		if ( ! $data = Text::force( $data ) )
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

	public static function extractFromDMS( mixed $data, mixed $fallback = FALSE ): mixed
	{
		if ( ! $data = Text::force( $data ) )
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

	public static function extractFromURL( string $data, mixed $fallback = FALSE ): mixed
	{
		if ( ! $data = Text::force( $data ) )
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

			// @SEE: https://wiki.openstreetmap.org/wiki/Shortlink
			case 'openstreetmap.org':
			case 'www.openstreetmap.org':
			case 'osm.org/query':
			case 'www.osm.org/query':
			case 'openstreetmap.org/query':
			case 'www.openstreetmap.org/query':

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

		} else if ( Text::starts( $data, static::COMAPS_PREFIXES ) ) {

			if ( $loc = self::extractFromCoMaps( Text::stripPrefix( $data, static::COMAPS_PREFIXES ) ) )
				return $loc;

		} else if ( Text::starts( $data, static::NESHAN_PREFIXES ) ) {

			// EXAMPLE: `https://nshn.ir/6a_bs-tSIxQN1b`
			if ( $loc = self::extractFromNeshan( Text::stripPrefix( $data, static::NESHAN_PREFIXES ) ) )
				return $loc;

		} else if ( Text::starts( $data, 'https://balad.ir/p/' ) ) {

			if ( ! empty( $url['fragment'] ) )
				return vsprintf( '%s,%s', array_slice( explode( '/', $url['fragment'] ), 1 ) );

		} else if ( Text::starts( $data, static::PLUSCODE_PREFIXES ) ) {

			// EXAMPLE: `https://plus.codes/8H7HHR42+M6`
			if ( $pluscode = self::extractFromPlusCode( Text::stripPrefix( $data, static::PLUSCODE_PREFIXES ) ) )
				return $pluscode;
		}

		return $fallback;
	}

	const PLUSCODE_PREFIXES = [
		'https://plus.codes/',
	];

	public static function extractFromPlusCode( mixed $data, $fallback = FALSE, $reference = NULL )
	{
		if ( ! $data = Text::force( $data ) )
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

	const NESHAN_PREFIXES = [
		'https://nshn.ir/',
		'https://neshan.org/maps/share/',
		'https://neshan.org/maps/places/',
	];

	/**
	 * Extracts coordinates from given Neshan.org identifier.
	 * @example `6a_bs-tSIxQN1b` from `https://nshn.ir/6a_bs-tSIxQN1b`
	 *
	 * @param string $data
	 * @param bool $fallback
	 * @param mixed $reference
	 * @return string|false|null
	 */
	public static function extractFromNeshan(
		string $data,
		string|false|null $fallback = FALSE,
		mixed $reference = NULL
	): string|false|null {

		if ( ! $data = Text::force( $data ) )
			return $fallback;

		// $canonical = sprintf( 'https://neshan.org/maps/places/%s', $data );
		$canonical = sprintf( 'https://neshan.org/maps/share/%s', $data );

		if ( ! $html = WordPress\Remote::getHTML( $canonical ) )
			return $fallback;

		// `<div id="map" data-ssr-loc="lat,long"></div>`
		$extracted = WordPress\HTML::extractAttributes( $html, [
			'map' => 'data-ssr-loc',
		], [
			'tag_name'    => 'DIV',
			'breadcrumbs' => [ 'HTML', 'BODY' ],
		], TRUE );

		return $extracted ?: $fallback;
	}

	// `MAPS.ME`/`OrganicMaps`/`COMAPS`
	const COMAPS_PREFIXES = [
		'https://comaps.at/',    // `https://comaps.at/ItdwBgeWW1/Hereford`
		'cm://'              ,   // `cm://ItdwBgeWW1/Hereford`
		'ge0://'            ,    // `ge0://B4srhdHVVt/Some_Name`
		'https://ge0.me/',
		'http://ge0.me/',
		'om://',
		'https://omaps.app/',  // `https://omaps.app/ZCoordba64/Name`
		'http://omaps.app/',
	];

	// https://codeberg.org/comaps/url-processor/
	// https://comaps.app/
	// https://organicmaps.app/
	// Regex("""((?:(?:https?://)?(?:comaps\.at|ge0\.me|omaps\.app)|ge0:/)/$URI_REST)""")
	public static function extractFromCoMaps(
		string $data,
		string|false|null $fallback = FALSE,
		mixed $reference = NULL
	): string|false|null {

		if ( ! $data = Text::force( $data ) )
			return $fallback;

		if ( ! ( $code = explode( '/', $data, 2 )[0] ?? '' ) )
			return $fallback;

		$canonical = sprintf( 'https://comaps.at/%s', $code );

		if ( ! $html = WordPress\Remote::getHTML( $canonical ) )
			return $fallback;

		// `<a class="button" id="geolink" href="geo:52.05539,-2.71519?Z=6">`
		$extracted = WordPress\HTML::extractAttributes( $html, [
			'geolink' => 'href',
		], [
			'tag_name'    => 'A',
			'breadcrumbs' => [ 'HTML', 'BODY', 'DIV', 'DIV', 'DIV' ],
		], TRUE );

		return $extracted
			? self::extractFromGeoLink( $sanitized, $fallback )
			: $fallback;
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
		// Defines calculation variables.
		$theta    = $longitude1 - $longitude2;
		$distance = ( sin( deg2rad( $latitude1 ) ) * sin( deg2rad( $latitude2 ) ) )
			+ ( cos( deg2rad( $latitude1 ) ) * cos( deg2rad( $latitude2 ) ) * cos( deg2rad( $theta ) ) );
		$distance = acos( $distance );
		$distance = rad2deg( $distance );
		$distance = $distance * 60 * 1.1515;

		// Kilometers
		if ( 'km' === $unit )
			$distance = $distance * 1.609344; // Redefines distance.

		// Nautical Miles
		else if ( 'n' === $unit )
			return $distance * 0.8684;

		// Miles
		return round( $distance, $decimals ); // Returns with one decimal.
	}

	/**
	 * Get closest location from all locations
	 *
	 * Calculate distance between two or multiple locations
	 * using Mathematics functions.
	 *
	 * @source `JeroenDesloovere\Distance::getClosest()`
	 * @link https://github.com/jeroendesloovere/distance
	 * @author `Jeroen Desloovere` <info@jeroendesloovere.be>
	 *
	 * @param float $latitude
	 * @param float $longitude
	 * @param array $items = `[ [ 'latitude' => 'x', 'longitude' => 'x' ], [...] ]`
	 * @param int $decimals
	 * @param string $unit
	 * @return array The item which is the closest + 'distance' to it.
	 */
	public static function distanceGetClosest( float $latitude, float $longitude, array $items, int $decimals = 1, string $unit = 'km' ): array
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

			$distances[(string) $distance] = $key;

			// adds rounded distance to array
			$items[$key]['distance'] = round( $distance, $decimals );
		}

		// Returns the item with the closest distance
		return $items[$distances[min( array_keys( $distances ) )]];
	}

	// @REF: https://www.geeksforgeeks.org/haversine-formula-to-find-distance-between-two-points-on-a-sphere/
	// @REF: https://stackoverflow.com/a/46218890
	// @REF: http://www.hackingwithphp.com/4/6/6/mathematical-constants
	public static function haversine( float $lat1, float $lon1, float $lat2, float $lon2 ): float
	{
		// distance between latitudes and longitudes
		$dLat = ( $lat2 - $lat1 ) * M_PI / 180.0;
		$dLon = ( $lon2 - $lon1 ) * M_PI / 180.0;

		// convert to radians
		$lat1 = ( $lat1 ) * M_PI / 180.0;
		$lat2 = ( $lat2 ) * M_PI / 180.0;

		// apply formulae
		$a   = pow( sin( $dLat / 2 ), 2 ) + pow( sin( $dLon / 2 ), 2 ) * cos( $lat1 ) * cos( $lat2 );
		$rad = 6371;
		$c   = 2 * asin( sqrt( $a ) );

		return $rad * $c;
	}

	// @REF: https://snippets.ir/1269/calculate-distance-between-two-points-in-php.html
	public static function getDistance( float $latitude1, float $longitude1, float $latitude2, float $longitude2 ): array
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
	 * Validates a given latitude
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1
	 *
	 * @param float|int|string $latitude
	 * @return bool
	 */
	public static function validateLatitude( float|int|string $latitude ): bool
	{
		return (bool) preg_match(
			'/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/',
			(string) $latitude
		);
	}

	/**
	 * Validates a given longitude
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1
	 *
	 * @param float|int|string $longitude
	 * @return bool
	 */
	public static function validateLongitude( $longitude )
	{
		return (bool) preg_match(
			'/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/',
			(string) $longitude
		);
	}

	/**
	 * Validates a given coordinates
	 * @source https://gist.github.com/arubacao/b5683b1dab4e4a47ee18fd55d9efbdd1
	 *
	 * @param float|int|string $latitude
	 * @param float|int|string $longitude
	 * @return bool
	 */
	public static function validateLatLong( $latitude, $longitude )
	{
		return (bool) preg_match(
			'/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?),[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',
			sprintf( '%s,%s', $latitude, $longitude )
		);
	}

	/**
	 * Creating `KMZ` File on the Fly
	 *
	 * My `KML` file was getting large and consuming time/bandwidth to download.
	 * So needed a quick solution and found `KMZ`. `KMZ` is a compressed `KML`
	 * file which is treated like `KML` by Google Earth. Here is a PHP code to
	 * create `KMZ` file from `KML` on the fly. My `KML` of `6.0Mb` was reduced to `600Kb`.
	 * @source https://shprabin.wordpress.com/2013/06/24/creating-kmz-file-on-the-fly-php/
	 *
	 * @param string $data
	 * @return void
	 */
	public static function kmz( $data )
	{
		header( 'Content-Type: application/vnd.google-earth.kmz' );
		header( 'Content-Disposition: attachment; filename="test.kmz"' );

		$kmlString = "This is your KML string";

		$file = "test.kmz";
		$zip  = new \ZipArchive();

		if ( $zip->open( $file, \ZIPARCHIVE::CREATE ) !== TRUE ) {
			exit("cannot open <$file>\n");
		}

		$zip->addFromString( "doc.kml", $kmlString );
		$zip->close();

		echo file_get_contents( $file );
	}


	// Create KMZ files on-the-fly
	// Google Earth uses a XML based file format for data exports, named with KML extension, which can be created easily with PHP. However, when icons and other data must be packed together to a KMZ file, the following snippet can create the ZIP-File, with KMZ extension using the PHP zip extension.
	// Copyright Robert Eisele 2017
	// https://raw.org/snippet/create-kmz-files-with-php/
	public static function kmz2()
	{
$kml = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>XARG.org Snippet</name>
    <Style id="msn">
        <IconStyle>
            <Icon>
                <href>files/bla.png</href>
            </Icon>
        </IconStyle>
    </Style>
    <Placemark>
        <name>This is a name</name>
            <description>This is a description.</description>
        <styleUrl>#msn</styleUrl>
        <Point>
            <coordinates>37.545734,14.159431,0</coordinates>
        </Point>
    </Placemark>
  </Document>
</kml>
KML;

$zip = new \ZipArchive();
if ($zip->open('GoogleEarth.kmz', \ZIPARCHIVE::CREATE)) {
    $zip->addEmptyDir('files');

    foreach (glob('icons/*') as $file) {
        $zip->addFile($file, 'files/'.basename( $file ) );
	}
    $zip->addFromString('doc.kml', $kml);
    $zip->close();
}
	}
}

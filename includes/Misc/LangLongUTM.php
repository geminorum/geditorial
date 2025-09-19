<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

/**
 * PHP class to convert Latitude+Longitude coordinates into UTM and wise versa.
 *
 * Code for datum and UTM conversion was converted from C++ code written by Chuck Gantz (chuck.gantz@globalstar.com) from http://www.gpsy.com/gpsinfo/geotoutm/
 * The C++ code was refactored and rewritten into PHP code by Hans Duedal (hd@onlinecity.dk).
 * The PHP conversion was inspired by work done by Brenor Brophy (brenor@sbcglobal.net), but derived from the "original" C++ source.
 *
 * @author chuck.gantz@globalstar.com, hd@onlinecity.dk
 *
 * `GeoPointConverter` (conversion between geographic points) Copyright (C) 2011 Hans Duedal (hd@onlinecity.dk)
 *
 * This library is free software. You can redistribute it and/or modify it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation; either version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
 *
 * This license can be read at: http://www.opensource.org/licenses/lgpl-2.1.php
 *
 * @link http://www.gpsy.com/gpsinfo/geotoutm/
 * @link https://gist.github.com/840476
 * @source https://gist.github.com/degerstrom/5180824
 * @SEE: https://www.latlong.net/lat-long-utm.html
 */
class LangLongUTM extends Core\Base
{
	const K0 = 0.9996;

	/**
	 * Equatorial Radius
	 * @var integer
	 */
	private $a;

	/**
	 * Datum Name
	 *
	 * @var string
	 */
	private $datum;

	/**
	 * Square of eccentricity
	 * @var float
	 */
	private $eccSquared;

	public function __construct( $datumName = 'ETRS89' )
	{
		$this->setEllipsoid( $datumName );
		$this->datum = $datumName;
	}

	/**
	 * Convert latitude/longitude into UTM coordinates. Equations from USGS Bulletin 1532
	 * Automatically calculates the zone, with special zone rules added for Denmark and Svalbard.
	 * Denmark stretches the zone 32 in `ETRS89` to include all of Zealand so users don't have to deal with zone crossings.
	 *
	 * @param float $latitude
	 * @param float $longitude
	 */
	public function convertLatLngToUTM( $latitude, $longitude )
	{
		// Make sure the longitude is between -180.00 ~ 179.9
		$LongTemp = ( $longitude + 180 ) - (int) ( ( $longitude + 180 ) / 360 ) * 360 - 180;  // -180.00 ~ 179.9;
		$LatRad   = deg2rad( $latitude );
		$LongRad  = deg2rad( $LongTemp );

		// Special zones for Denmark: http://www.kms.dk/Referencenet/Referencesystemer/UTM_ETRS89/
		if ( $LongTemp >= 8 && $LongTemp <= 13 && $latitude > 54.5 && $latitude < 58 ) {

			$ZoneNumber = 32;

		// From C++ code
		} else if ( $latitude >= 56.0 && $latitude < 64.0 && $LongTemp >= 3.0 && $LongTemp < 12.0 ) {

			$ZoneNumber = 32;

		} else {

			$ZoneNumber = (int) ( ( $LongTemp + 180 ) / 6 ) + 1;

			// Special zones for Svalbard
			if ( $latitude >= 72.0 && $latitude < 84.0 ) {

				if ( $LongTemp >= 0.0 && $LongTemp < 9.0 ) {
					$ZoneNumber = 31;
				} else if ( $LongTemp >= 9.0 && $LongTemp < 21.0 ) {
					$ZoneNumber = 33;
				} else if ( $LongTemp >= 21.0 && $LongTemp < 33.0 ) {
					$ZoneNumber = 35;
				} else if ( $LongTemp >= 33.0 && $LongTemp < 42.0 ) {
					$ZoneNumber = 37;
				}
			}
		}

		$LongOrigin    = ( $ZoneNumber - 1 ) * 6 - 180 + 3;  // +3 puts origin in middle of zone
		$LongOriginRad = deg2rad($LongOrigin);

		$UTMZone = $ZoneNumber.self::getUtmLetterDesignator( $latitude );

		$eccPrimeSquared = ( $this->eccSquared ) / ( 1 - $this->eccSquared );

		$N = $this->a/sqrt(1-$this->eccSquared*sin($LatRad)*sin($LatRad));
		$T = tan($LatRad)*tan($LatRad);
		$C = $eccPrimeSquared*cos($LatRad)*cos($LatRad);
		$A = cos($LatRad)*($LongRad-$LongOriginRad);

		$M = $this->a*((1 - $this->eccSquared/4	- 3*$this->eccSquared*$this->eccSquared/64	- 5*$this->eccSquared*$this->eccSquared*$this->eccSquared/256)*$LatRad
					- (3*$this->eccSquared/8 + 3*$this->eccSquared*$this->eccSquared/32	+ 45*$this->eccSquared*$this->eccSquared*$this->eccSquared/1024)*sin(2*$LatRad)
										+ (15*$this->eccSquared*$this->eccSquared/256 + 45*$this->eccSquared*$this->eccSquared*$this->eccSquared/1024)*sin(4*$LatRad)
										- (35*$this->eccSquared*$this->eccSquared*$this->eccSquared/3072)*sin(6*$LatRad));

		$UTMEasting = (float)(self::K0*$N*($A+(1-$T+$C)*$A*$A*$A/6
						+ (5-18*$T+$T*$T+72*$C-58*$eccPrimeSquared)*$A*$A*$A*$A*$A/120)
						+ 500000.0);

		$UTMNorthing = (float)(self::K0*($M+$N*tan($LatRad)*($A*$A/2+(5-$T+9*$C+4*$C*$C)*$A*$A*$A*$A/24
					 + (61-58*$T+$T*$T+600*$C-330*$eccPrimeSquared)*$A*$A*$A*$A*$A*$A/720)));

		if ( $latitude < 0 )
			$UTMNorthing += 10000000.0; //10000000 meter offset for southern hemisphere

		// Round them off, it's normally specified as integers
		// and conversion is not terribly exact anyway
		$UTMNorthing = (int) round( $UTMNorthing );
		$UTMEasting  = (int) round( $UTMEasting );

		return [ $UTMZone, $UTMEasting, $UTMNorthing ];
	}

	/**
	 * Convert UTM to Longitude/Latitude
	 *
	 * Equations from USGS Bulletin 1532.
	 * East Longitudes are positive, West longitudes are negative.
	 * North latitudes are positive, South latitudes are negative
	 * Lat and Long are in decimal degrees.
	 *
	 * @param string $UTMZone
	 * @param integer $UTMEasting
	 * @param integer $UTMNorthing
	 */
	public function convertUtmToLatLng( $UTMZone, $UTMEasting, $UTMNorthing )
	{
		$e1 = ( 1 - sqrt( 1 - $this->eccSquared ) ) / ( 1 + sqrt( 1 - $this->eccSquared ) );
		$x  = $UTMEasting - 500000.0; // remove 500,000 meter offset for longitude
		$y  = $UTMNorthing;

		sscanf( $UTMZone, '%d%s', $ZoneNumber, $ZoneLetter );

		if ( strcmp ('N', $ZoneLetter ) <= 0 ) {
			$NorthernHemisphere = 1; // point is in northern hemisphere
		} else {
			$NorthernHemisphere = 0; // point is in southern hemisphere
			$y -= 10000000.0; // remove 10,000,000 meter offset used for southern hemisphere
		}

		$LongOrigin = ( $ZoneNumber - 1 ) * 6 - 180 + 3;  // +3 puts origin in middle of zone

		$eccPrimeSquared = ( $this->eccSquared ) / ( 1 - $this->eccSquared );

		$M  = $y / self::K0;
		$mu = $M / ( $this->a * ( 1 - $this->eccSquared / 4 - 3 * $this->eccSquared * $this->eccSquared / 64 - 5 * $this->eccSquared * $this->eccSquared * $this->eccSquared / 256 ) );

		$phi1Rad = $mu	+ (3*$e1/2-27*$e1*$e1*$e1/32)*sin(2*$mu)
					+ (21*$e1*$e1/16-55*$e1*$e1*$e1*$e1/32)*sin(4*$mu)
					+(151*$e1*$e1*$e1/96)*sin(6*$mu);
		$phi1 = rad2deg($phi1Rad);

		$N1 = $this->a/sqrt(1-$this->eccSquared*sin($phi1Rad)*sin($phi1Rad));
		$T1 = tan($phi1Rad)*tan($phi1Rad);
		$C1 = $eccPrimeSquared*cos($phi1Rad)*cos($phi1Rad);
		$R1 = $this->a*(1-$this->eccSquared)/pow(1-$this->eccSquared*sin($phi1Rad)*sin($phi1Rad), 1.5);
		$D = $x/($N1*self::K0);

		$Lat = $phi1Rad - ($N1*tan($phi1Rad)/$R1)*($D*$D/2-(5+3*$T1+10*$C1-4*$C1*$C1-9*$eccPrimeSquared)*$D*$D*$D*$D/24
						+(61+90*$T1+298*$C1+45*$T1*$T1-252*$eccPrimeSquared-3*$C1*$C1)*$D*$D*$D*$D*$D*$D/720);
		$Lat = rad2deg($Lat);

		$Long = ($D-(1+2*$T1+$C1)*$D*$D*$D/6+(5-2*$C1+28*$T1-3*$C1*$C1+8*$eccPrimeSquared+24*$T1*$T1)
						*$D*$D*$D*$D*$D/120)/cos($phi1Rad);
		$Long = $LongOrigin + rad2deg($Long);

		return [ $Lat, $Long ];
	}

	/**
	 * Reference ellipsoids derived from Peter H. Dana's website:
	 * 	http://www.utexas.edu/depts/grg/gcraft/notes/datum/elist.html
	 * 	Department of Geography, University of Texas at Austin
	 * 	Internet: pdana@mail.utexas.edu 3/22/95
	 * Source:
	 * 	Defense Mapping Agency. 1987b. DMA Technical Report: Supplement to Department of Defense World Geodetic System 1984 Technical Report. Part I and II.
	 * 	Washington, DC: Defense Mapping Agency
	 * Alternative names added in for easy compatibility by hd@onlinecity.dk
	 *
	 * @param string $name
	 * @return void
	 */
	public function setEllipsoid( $name )
	{
		switch ( $name ) {

			case 'Airy'                 : $this->a = 6377563; $this->eccSquared = 0.00667054;  break;
			case 'Australian National'  : $this->a = 6378160; $this->eccSquared = 0.006694542; break;
			case 'Bessel 1841'          : $this->a = 6377397; $this->eccSquared = 0.006674372; break;
			case 'Bessel 1841 Nambia'   : $this->a = 6377484; $this->eccSquared = 0.006674372; break;
			case 'Clarke 1866'          : $this->a = 6378206; $this->eccSquared = 0.006768658; break;
			case 'Clarke 1880'          : $this->a = 6378249; $this->eccSquared = 0.006803511; break;
			case 'Everest'              : $this->a = 6377276; $this->eccSquared = 0.006637847; break;
			case 'Fischer 1960 Mercury' : $this->a = 6378166; $this->eccSquared = 0.006693422; break;
			case 'Fischer 1968'         : $this->a = 6378150; $this->eccSquared = 0.006693422; break;
			case 'GRS 1967'             : $this->a = 6378160; $this->eccSquared = 0.006694605; break;
			case 'GRS 1980'             : $this->a = 6378137; $this->eccSquared = 0.00669438;  break;
			case 'Helmert 1906'         : $this->a = 6378200; $this->eccSquared = 0.006693422; break;
			case 'Hough'                : $this->a = 6378270; $this->eccSquared = 0.00672267;  break;
			case 'International'        : $this->a = 6378388; $this->eccSquared = 0.00672267;  break;
			case 'Krassovsky'           : $this->a = 6378245; $this->eccSquared = 0.006693422; break;
			case 'Modified Airy'        : $this->a = 6377340; $this->eccSquared = 0.00667054;  break;
			case 'Modified Everest'     : $this->a = 6377304; $this->eccSquared = 0.006637847; break;
			case 'Modified Fischer 1960': $this->a = 6378155; $this->eccSquared = 0.006693422; break;
			case 'South American 1969'  : $this->a = 6378160; $this->eccSquared = 0.006694542; break;
			case 'WGS 60'               : $this->a = 6378165; $this->eccSquared = 0.006693422; break;
			case 'WGS 66'               : $this->a = 6378145; $this->eccSquared = 0.006694542; break;
			case 'WGS 72'               : $this->a = 6378135; $this->eccSquared = 0.006694318; break;
			case 'ED50'                 : $this->a = 6378388; $this->eccSquared = 0.00672267;  break;   // International Ellipsoid

			case 'WGS 84' :
			case 'EUREF89':   // Max deviation from WGS 84 is 40 cm/km see http://ocq.dk/euref89 (in danish)
			case 'ETRS89' :   // Same as EUREF89
				$this->a = 6378137;
				$this->eccSquared = 0.00669438;
				break;

			default:
				throw new \InvalidArgumentException( 'No ecclipsoid data associated with unknown datum: '.$name );
		}
	}

	/**
	 * Get the UTM letter designator for a given latitude.
	 * Returns 'Z' if latitude is outside the UTM limits of 84N to 80S
	 *
	 * @param float $latitude
	 * @return string
	 */
	public static function getUtmLetterDesignator( $latitude )
	{
		switch ( $latitude ) {
			case ( ( 84 >= $latitude ) && ( $latitude >= 72  ) ): return 'X';
			case ( (  72 > $latitude ) && ( $latitude >= 64  ) ): return 'W';
			case ( (  64 > $latitude ) && ( $latitude >= 56  ) ): return 'V';
			case ( (  56 > $latitude ) && ( $latitude >= 48  ) ): return 'U';
			case ( (  48 > $latitude ) && ( $latitude >= 40  ) ): return 'T';
			case ( (  40 > $latitude ) && ( $latitude >= 32  ) ): return 'S';
			case ( (  32 > $latitude ) && ( $latitude >= 24  ) ): return 'R';
			case ( (  24 > $latitude ) && ( $latitude >= 16  ) ): return 'Q';
			case ( (  16 > $latitude ) && ( $latitude >= 8   ) ): return 'P';
			case ( (   8 > $latitude ) && ( $latitude >= 0   ) ): return 'N';
			case ( (   0 > $latitude ) && ( $latitude >= -8  ) ): return 'M';
			case ( (  -8 > $latitude ) && ( $latitude >= -16 ) ): return 'L';
			case ( ( -16 > $latitude ) && ( $latitude >= -24 ) ): return 'K';
			case ( ( -24 > $latitude ) && ( $latitude >= -32 ) ): return 'J';
			case ( ( -32 > $latitude ) && ( $latitude >= -40 ) ): return 'H';
			case ( ( -40 > $latitude ) && ( $latitude >= -48 ) ): return 'G';
			case ( ( -48 > $latitude ) && ( $latitude >= -56 ) ): return 'F';
			case ( ( -56 > $latitude ) && ( $latitude >= -64 ) ): return 'E';
			case ( ( -64 > $latitude ) && ( $latitude >= -72 ) ): return 'D';
			case ( ( -72 > $latitude ) && ( $latitude >= -80 ) ): return 'C';
			default:                                              return 'Z';
		}
	}
}

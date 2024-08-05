<?php namespace geminorum\gEditorial\Modules\Athlete;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'athlete';

	// @REF: https://medium.com/@hermuwyim/a-simple-bmi-calculator-using-javascript-b2365f3206e8
	// @REF: https://www.w3schools.in/php/examples/php-program-to-calculate-bmi
	// @SEE: https://www.bahesab.ir/calc/bmi/
	// TODO: suggest the better range for the height
	public static function calculateBMI( $weight_in_kg, $height_in_cm, $context = 'display', $fallback = FALSE )
	{
		$bmi = round( ( $weight_in_kg * 2.2 ) / ( ( $height_in_cm * 0.393701 ) * ( $height_in_cm * 0.393701 ) ) * 703, 2 );

		if ( $bmi <= 18.5 ) {

			return [
				'result'  => 'underweight',
				'state'   => 'info',
				'message' => _x( 'Your BMI falls within the underweight range.', 'BMI: Message', 'geditorial-athlete' ),
				'report'  => _x( 'The Person is underweight.', 'BMI: Report', 'geditorial-athlete' ),
			];

		} else if ( $bmi <= 24.9 ) {

			return [
				'result'  => 'healthy',
				'state'   => 'success',
				'message' => _x( 'Your BMI falls within the normal or healthy weight range.', 'BMI: Message', 'geditorial-athlete' ),
				'report'  => _x( 'The Person has healthy weight.', 'BMI: Report', 'geditorial-athlete' ),
			];

		} else if ( $bmi <= 29.9 ) {

			return [
				'result'  => 'overweight',
				'state'   => 'warning',
				'message' => _x( 'Your BMI falls within the overweight range.', 'BMI Message', 'geditorial-athlete' ),
				'report'  => _x( 'The Person is overweight.', 'BMI: Report', 'geditorial-athlete' ),
			];

		} else {

			return [
				'result'  => 'obese',
				'state'   => 'danger',
				'message' => _x( 'Your BMI falls within the obese range.', 'BMI Message', 'geditorial-athlete' ),
				'report'  => _x( 'The Person is obese.', 'BMI: Report', 'geditorial-athlete' ),
			];
		}

		return $fallback;
	}
}

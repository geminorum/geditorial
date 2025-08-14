<?php namespace geminorum\gEditorial\Modules\Physical;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'physical';

	// @REF: https://medium.com/@hermuwyim/a-simple-bmi-calculator-using-javascript-b2365f3206e8
	// @REF: https://www.w3schools.in/php/examples/php-program-to-calculate-bmi
	// @SEE: https://www.bahesab.ir/calc/bmi/
	// @SEE: https://www.calculator.net/bmi-calculator.html
	// @SEE: https://www.medicalnewstoday.com/articles/323446
	// TODO: suggest the better range for the height
	// TODO: move to `Weight` DataType/
	public static function calculateBMI( $weight_in_kg, $height_in_cm, $age = NULL, $gender = NULL, $context = 'display', $fallback = FALSE )
	{
		if ( ! $weight_in_kg || ! $height_in_cm )
			return $fallback;

		$bmi = round( ( $weight_in_kg * 2.2 ) / ( ( $height_in_cm * 0.393701 ) * ( $height_in_cm * 0.393701 ) ) * 703, 2 );

		if ( ! $bmi )
			return $fallback;

		$data = [
			'bmi'         => $bmi,
			'age'         => $age,
			'gender'      => $gender,
			'weight'      => $weight_in_kg,
			'height'      => $height_in_cm,
			'context'     => $context,
			'ideal_start' => NULL,            // FIXME: must be range
			'ideal_end'   => NULL,            // FIXME: must be range
		];

		if ( $bmi <= 18.5 ) {

			$data['result']  = 'underweight';
			$data['state']   = 'info';
			$data['message'] = _x( 'Your BMI falls within the underweight range.', 'BMI: Message', 'geditorial-physical' );
			$data['report']  = _x( 'The Person is underweight.', 'BMI: Report', 'geditorial-physical' );

		} else if ( $bmi <= 24.9 ) {

			$data['result']  = 'healthy';
			$data['state']   = 'success';
			$data['message'] = _x( 'Your BMI falls within the normal or healthy weight range.', 'BMI: Message', 'geditorial-physical' );
			$data['report']  = _x( 'The Person has healthy weight.', 'BMI: Report', 'geditorial-physical' );

		} else if ( $bmi <= 29.9 ) {

			$data['result']  = 'overweight';
			$data['state']   = 'warning';
			$data['message'] = _x( 'Your BMI falls within the overweight range.', 'BMI Message', 'geditorial-physical' );
			$data['report']  = _x( 'The Person is overweight.', 'BMI: Report', 'geditorial-physical' );

		} else if ( $bmi <= 34.9 ) {

			$data['result']  = 'obese-class-one';
			$data['state']   = 'danger';
			$data['message'] = _x( 'Your BMI falls within the obese (class one) range.', 'BMI Message', 'geditorial-physical' );
			$data['report']  = _x( 'The Person is obese.', 'BMI: Report', 'geditorial-physical' );

		} else if ( $bmi <= 39.9 ) {

			$data['result']  = 'obese-class-two';
			$data['state']   = 'danger';
			$data['message'] = _x( 'Your BMI falls within the obese (class two) range.', 'BMI Message', 'geditorial-physical' );
			$data['report']  = _x( 'The Person is obese.', 'BMI: Report', 'geditorial-physical' );

		} else {

			$data['result']  = 'obese-class-three';
			$data['state']   = 'danger';
			$data['message'] = _x( 'Your BMI falls within the obese (class three) range.', 'BMI Message', 'geditorial-physical' );
			$data['report']  = _x( 'The Person is obese.', 'BMI: Report', 'geditorial-physical' );
		}

		return apply_filters(
			sprintf( '%s_%s_%s', static::BASE, static::MODULE, 'calculate_bmi' ),
			$data,
			$bmi,
			$weight_in_kg,
			$height_in_cm,
			$context
		);
	}
}

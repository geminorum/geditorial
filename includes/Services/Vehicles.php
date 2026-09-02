<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Vehicles extends gEditorial\Service
{
	public static function prepVIN( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( 'admin' === $context )
			return sprintf( '<span class="-vin %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
				Core\Validation::isVIN( $data ) ? '-is-valid' : '-not-valid',
				$data,
				$data,
			);

		return Lookup::htmlVIN( $data );
	}

	public static function prepPlate( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'print' === $context )
			return Core\Number::localize( $data );

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		return sprintf( '<span class="-plate %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
			Core\Validation::isPlateNumber( $data ) ? '-is-valid' : '-not-valid',
			$data,
			$data,
		);
	}
}

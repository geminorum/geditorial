<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Publications extends gEditorial\Service
{
	public static function prepISBN( mixed $input, ?string $context = NULL, null|false|string $fallback = '' ): null|false|string
	{
		if ( self::empty( $input ) )
			return $fallback;

		if ( ! $data = Core\Text::force( $input ) )
			return $fallback;

		if ( 'export' === $context )
			return Core\Number::translate( $data );

		if ( 'admin' === $context )
			return sprintf( '<span class="-isbn %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
				Core\ISBN::validate( $data ) ? '-is-valid' : '-not-valid',
				$data,
				$data,
			);

		return Lookup::htmlISBN( $data );
	}
}

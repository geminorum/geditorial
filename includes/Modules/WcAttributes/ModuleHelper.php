<?php namespace geminorum\gEditorial\Modules\WcAttributes;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'wc_attributes';

	public static function possibleAttributeForGTIN( $attribute )
	{
		if ( empty( $attribute ) )
			return FALSE;

		$name = Core\Text::trim( $attribute->get_name() );

		if ( WordPress\Strings::isEmpty( $name ) )
			return FALSE;

		$keys = [
			'شابک',
			'isbn',
			'gtin',
		];

		if ( ! in_array( strtolower( trim( $name, ':' ) ), $keys, TRUE ) )
			return FALSE;

		foreach ( $attribute->get_options() as $option ) {

			if ( WordPress\Strings::isEmpty( $option ) )
				continue;

			if ( $sanitized = Core\ISBN::discovery( $option ) )
				return $sanitized;
		}

		return FALSE;
	}
}

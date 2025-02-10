<?php namespace geminorum\gEditorial\Modules\WcIdentify;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'wc_identify';

	public static function possibleAttributeForGTIN( $attribute )
	{
		if ( empty( $attribute ) )
			return FALSE;

		$name = Core\Text::trim( $attribute->get_name() );
		$name = WordPress\Strings::cleanupChars( $name );

		if ( WordPress\Strings::isEmpty( $name ) )
			return FALSE;

		$keys = [
			'gtin',
			'isbn',
			'کد',
			'کد کتاب',
			'کد شابک',
			'شناسه',
			'شابک',
			'شماره شابک',
			'شابک کتاب',
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

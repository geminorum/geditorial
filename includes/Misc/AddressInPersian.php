<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class AddressInPersian extends Core\Base
{

	const SUPPORTED_LOCALE = [
		'fa_IR',
	];

	// NOTE: must be used in conjunction with `Services\Locations::prepAddress()`
	public static function prepExtra( $data, $context = 'display', $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		$data = Core\Number::translate( $data );

		if ( class_exists( 'geminorum\\gEditorial\\Misc\\NumbersInPersian' ) )
			$data = \geminorum\gEditorial\Misc\NumbersInPersian::textOrdinalToNumbers( $data, 100 );

		$prefixes = [
			'پلاک'   => 'پ',
			'خیابان' => 'خ',
			'بلوک'   => 'ب',
			'کوچه'   => 'ک',
			'فرعی'   => 'فرعی',
			'بلوار'  => 'بلوار',
			'بن بست' => 'بن‌بست',
		];

		foreach ( $prefixes as $from => $to ) {

			$pattern = sprintf( '/%s[\s]?([0-9۰-۹]+)/mu', preg_quote( $from ) );

			$data = preg_replace_callback( $pattern,
				static function ( $matches ) use ( $to ) {
					return sprintf( ' %s%s ', $to, Core\Text::trim( $matches[1] ) ); // padding with space
				}, $data );
		}

		return Core\Number::translatePersian( $data );
	}
}

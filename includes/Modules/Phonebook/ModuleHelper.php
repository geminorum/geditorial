<?php namespace geminorum\gEditorial\Modules\Phonebook;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'phonebook';

	public static function prepAddress( $data, $context = 'display', $fallback = FALSE )
	{
		if ( self::empty( $data ) )
			return $fallback;

		if ( ! $data = Core\Text::normalizeWhitespace( WordPress\Strings::cleanupChars( $data ) ) )
			return $fallback;

		$data = trim( $data, '.-|…' );
		$data = str_ireplace( [ '_', '|', '–', '—'  ], '-', $data );
		$data = sprintf( ' %s ', $data ); // padding with space

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			$data = Core\Number::translate( $data );

			$numbers  = new \geminorum\gEditorial\Misc\NumbersInPersian();
			$ordinals = $numbers->get_range_ordinal_reverse( 1, 100 );

			foreach ( $ordinals as $ordinal => $index ) {

				$pattern = sprintf( '/[\s,،](%s|%s)[\s,،]/mu',
					preg_quote( $ordinal ),
					preg_quote( str_ireplace( ' ', '', $ordinal ) )
				);

				$data = preg_replace_callback( $pattern,
					static function ( $matches ) use ( $index ) {
						return sprintf( ' %s ', $index ); // padding with space
					}, $data );
			}

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

			$data = Core\Number::translatePersian( $data );
		}

		$data = preg_replace( '/\s+([\,\،])/mu', '$1', $data );
		$data = preg_replace( '/\s+([\-])/mu', '$1', $data );
		$data = preg_replace( '/([\-])\s+/mu', '$1', $data );

		return Core\Text::normalizeWhitespace( $data );
	}
}

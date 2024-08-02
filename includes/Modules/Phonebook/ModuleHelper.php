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

		if ( ! $data = Core\Text::trim( WordPress\Strings::cleanupChars( $data ) ) )
			return $fallback;

		$data = trim( $data, '.-|…' );
		$data = str_ireplace( [ '_', '|', '–', '—'  ], '-', $data );
		$data = ' '.$data.' '; // padded with space

		if ( 'fa_IR' === self::const( 'GNETWORK_WPLANG' ) ) {

			$numbers = new \geminorum\gEditorial\Misc\NumbersInPersian();
			foreach ( $numbers->get_range_ordinal_reverse( 1, 100 ) as $ordinal => $index )
				$data = preg_replace_callback( '/[\s+]?('.preg_quote( $ordinal ).'|'.preg_quote( str_ireplace( ' ', '', $ordinal ) ).')[\s+]?/mu', static function ( $matches ) use ( $index ) {
					return ' '.Core\Number::localize( $index ).' '; // padded with space
				}, $data );

			$prefixes = [
				'پلاک'   => 'پ',
				'خیابان' => 'خ',
				'بلوک'   => 'ب',
				'کوچه'   => 'ک',
				'فرعی'   => 'فرعی',
				'بلوار'  => 'بلوار',
				'بن بست' => 'بن‌بست',
			];

			foreach ( $prefixes as $from => $to )
				$data = preg_replace_callback( '/'.$from.'[\s]?([0-9۰-۹]+)'.'/mu', static function ( $matches ) use ( $to ) {
					return ' '.$to.Core\Number::localize( trim( $matches[1] ) ).' '; // padded with space
				}, $data );
		}

		$data = preg_replace( '/\s+([\,\،])/mu', '$1', $data );
		$data = preg_replace( '/\s+([\-])/mu', '$1', $data );
		$data = preg_replace( '/([\-])\s+/mu', '$1', $data );

		return Core\Text::normalizeWhitespace( $data );
	}
}

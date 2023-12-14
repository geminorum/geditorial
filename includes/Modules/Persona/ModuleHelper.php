<?php namespace geminorum\gEditorial\Modules\Persona;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

use geminorum\gNetwork\Core\Orthography;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'persona';

	public static function cleanupChars( $string, $html = FALSE )
	{
		if ( empty( $string ) )
			return $string;

		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return apply_filters( 'string_format_i18n', $string );

		// return $html
		// 	? Orthography::cleanupPersianHTML( $string )
		// 	: Orthography::cleanupPersian( $string );

		return Orthography::cleanupPersianChars( $string );
	}

	// TODO: support more complex naming
	public static function makeFullname( $data, $context = 'display', $fallback = FALSE )
	{
		if ( ! $data )
			return $fallback;

		$parts = self::atts( [
			'fullname'    => '',
			'first_name'  => '',
			'last_name'   => '',
			'middle_name' => '',
			'father_name' => '',
			'mother_name' => '',
		], $data );

		foreach ( $parts as $key => $value )
			$parts[$key] = self::replaceSplits( self::cleanupChars( $value ) );

		if ( empty( $parts['last_name'] ) && empty( $parts['first_name'] ) )
			return empty( $parts['fullname'] )
				? $fallback
				: Core\Text::normalizeWhitespace( $parts['fullname'], FALSE );

		switch ( $context ) {

			case 'import':
			case 'edit':

				$fullname = vsprintf(
					/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
					_x( '%1$s %3$s %2$s', 'Helper: Make Full-name: Edit', 'geditorial-persona' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);

				break;

			case 'export':
			case 'print':

				$fullname = vsprintf(
					/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
					_x( '%1$s %3$s %2$s', 'Helper: Make Full-name: Print', 'geditorial-persona' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);

				break;

			case 'display':
			default:

				$fullname = vsprintf(
					/* translators: %1$s: first name, %2$s: last name, %3$s: middle name, %4$s: father name, %5$s: mother name */
					_x( '%2$s, %1$s %3$s', 'Helper: Make Full-name: Display', 'geditorial-persona' ),
					[
						$parts['first_name'],
						$parts['last_name'],
						$parts['middle_name'],
						$parts['father_name'],
						$parts['mother_name'],
					]
				);
		}

		return Core\Text::normalizeWhitespace( $fullname, FALSE );
	}

	public static function isValidFullname( $text )
	{
		if ( WordPress\Strings::isEmpty( $text ) )
			return FALSE;

		if ( ! $nospace = Core\Text::stripAllSpaces( $text ) )
			return FALSE;

		// already cleaned!
		// $text = self::cleanupChars( $text );

		if ( in_array( $nospace, self::getBlacklistStrings(), TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function parseFullname( $raw, $fallback = FALSE )
	{
		if ( empty( $raw ) )
			return $fallback;

		$normalized = Core\Text::normalizeWhitespaceUTF8( self::cleanupChars( $raw ), FALSE );

		if ( ! self::isValidFullname( $normalized ) )
			return $fallback;

		$parts = Core\Text::splitNormalSpaces( $normalized );

		if ( count( $parts ) < 2 )
			return $fallback;

		$notdone = $flag = TRUE;
		$parts   = implode( ' ', $parts );

		$names = [
			'raw'         => $raw,
			'fullname'    => '',
			'first_name'  => '',
			'middle_name' => '',
			'last_name'   => '',
			'father_name' => '',
			'mother_name' => '',
			'gender'      => '',
			'honorific'   => [],
		];

		if ( Core\Text::starts( $parts, 'دکتر' ) ) {

			$names['honorific'][] = 'doctor';
			$parts                = Core\Text::trim( Core\Text::leftTrim( $parts, 'دکتر', FALSE ) );
		}

		// TODO: آیت‌الله
		// TODO: حجت‌الاسلام/حجت‌الاسلام والمسلمین
		// TODO: مهندس
		// TODO: آقا/آقای
		// TODO: خانم/خانوم
		// TODO: جناب/سرکار
		// TODO: سرهنگ/سرگرد/سردار/سرباز

		// check again if its doctor something!
		$parts = Core\Text::splitNormalSpaces( $parts );

		if ( count( $parts ) < 2 )
			return $fallback;

		$parts = self::replaceSplits( $parts );

		$second_names    = self::getSecondPartNames();
		$second_families = self::getSecondPartFamilies();

		while ( $notdone ) {

			while ( $flag ) {

				if ( ! is_array( $parts ) )
					$parts = Core\Text::splitNormalSpaces( $parts );

				for ( $i = count( $parts ); $i > 0; $i-- ) {

					if ( ! isset( $parts[$i-1] ) )
						break;

					if ( ! isset( $parts[$i] ) )
						continue;

					if ( in_array( $parts[$i], $second_families, TRUE ) ) {

						if ( isset( $parts[$i-1] ) ) {

							$parts[$i] = $parts[$i-1].'‌'.$parts[$i];
							unset( $parts[$i-1] );

							$parts = implode( ' ', $parts );
							continue 2;
						}

					} else if ( in_array( $parts[$i], $second_names, TRUE ) ) {

						if ( isset( $parts[$i-1] ) ) {

							$parts[$i] = $parts[$i-1].'‌'.$parts[$i];
							unset( $parts[$i-1] );

							$parts = implode( ' ', $parts );
							continue 2;
						}
					}
				}

				$flag = FALSE;
			}

			$notdone = FALSE;
		}

		if ( is_array( $parts ) )
			$parts = implode( ' ', $parts );

		if ( Core\Text::starts( $parts, 'سیده' ) )
			$parts = 'سیده '.Core\Text::trim( Core\Text::leftTrim( $parts, 'سیده', FALSE ) );

		else if ( Core\Text::starts( $parts, 'سید' ) )
			$parts = 'سید '.Core\Text::trim( Core\Text::leftTrim( $parts, 'سید', FALSE ) );

		$names['fullname'] = Core\Text::normalizeWhitespaceUTF8( $parts, FALSE );
		$prefix = '';

		if ( Core\Text::starts( $names['fullname'], 'سیده' ) ) {

			$names['gender']      = 'female';
			$names['honorific'][] = 'saadaat';

			$prefix = 'سیده';
			$parts  = Core\Text::trim( Core\Text::leftTrim( $names['fullname'], 'سیده', FALSE ) );

		} else if ( Core\Text::starts( $names['fullname'], 'سید' ) ) {

			$names['gender']      = 'male';
			$names['honorific'][] = 'saadaat';

			$prefix = 'سید';
			$parts  = Core\Text::trim( Core\Text::leftTrim( $names['fullname'], 'سید', FALSE ) );
		}

		$parts = Core\Text::splitNormalSpaces( $parts );

		$names['first_name'] = Core\Text::trim( $prefix.' '.$parts[0] );
		unset( $parts[0] );
		$names['last_name'] = implode( ' ', $parts );

		return $names;
	}

	// NOT USED
	// `array_walk( $names, [ __CLASS__, 'normalizePart' ] );`
	public static function normalizePart( &$value, $key )
	{
		if ( 'honorific' == $key )
			return;

		if ( is_array( $value ) ) {

			$part = array_map( function( $value ) {

				if ( 'ZWNJ' == $value )
					return $value;

				// FIXME: do orthography stuff!!

				return Core\Text::trim( $value );
			}, $value );

			$part  = implode( ' ', array_reverse( $part ) );
			$part  = trim( str_ireplace( [ ' ZWNJ ', 'ZWNJ', ' ZWNJ', 'ZWNJ ' ], '‌', $part ), '‌' );

			// TODO: normalize zwnj

			$value = Core\Text::trim( $part );
		}

		else if ( is_string( $value ) )
			$value = Core\Text::trim( $value );
	}

	public static function replaceSplits( $raw )
	{
		if ( empty( $raw ) )
			return '';

		$parts  = is_array( $raw ) ? $raw : Core\Text::splitNormalSpaces( $raw );
		$splits = self::getCommonSplits();

		foreach ( $parts as &$part ) {
			foreach( $splits as $before => $after ) {
				if ( $before === $part ) {
					$part = $after;
					break;
				}
			}
		}

		return is_array( $parts ) ? implode( ' ', $parts ) : $parts;
	}

	public static function getCommonSplits()
	{
		return array_filter( [
			'علیرضا'   => 'علی‌رضا',
			'غلامرضا'  => 'غلام‌رضا',
			'غلامعلی'  => 'غلام‌علی',
			'غلامحسین' => 'غلام‌حسین',
			'روح‌الله' => 'روح‌اله',
			'عبدالله'  => 'عبداله',
			'حسینعلی'  => 'حسین‌علی',
			'حسنعلی'   => 'حسن‌علی',
		] );
	}

	public static function getBlacklistStrings()
	{
		return array_filter( [
			'نامونامخانوادگی',
			'خالی',
			'دکتر',
			'حجتالاسلام',
			'حجتالاسلاموالمسلمین',
			'آیتالله',
			'آیتاللهالعظمی',
			'مهندس',
			'آقا',
			'آقای',
			'خانوم',
			'خانم',
			'جناب',
			'سرکار',
			'سرهنگ',
			'سرگرد',
			'سردار',
			'نام',
			'نامکامل',
			'نامخانوادگی',
			'خانوادگی',
			'فامیل',
			'فامیلی',
			'name',
			'family',
			'fullname',
			'namefamily',
			'familyname',
		] );
	}

	// NOT USED
	public static function getCommonFamilies()
	{
		return array_filter( [
			'موسوی',
			'موسویان',
			'اسکندری',
		] );
	}

	public static function getSecondPartFamilies()
	{
		return array_filter( [
			'آبادی',
			'نژاد',
			'پور',
			'کیا',
			'زاده',
			'نیا',
			'نیان',
			'نسب',
			'لو',
			'تبار',
		] );
	}

	public static function getSecondPartNames()
	{
		return array_filter( [
			'اله',
			'الله',
			'الدین',
			'محمد',
			'علی',
			'حسن',
			'حسین',
			'رضا',
			'جواد',
			'مهدی',
			'رسول',
			'اکبر',
			'اصغر',
			'نقی',
			'تقی',
			'هادی',
		] );
	}
}

<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class NamesInPersian extends Core\Base
{

	const SUPPORTED_LOCALE = [
		'fa_IR',
	];

	public static function isValidFullname( $text )
	{
		if ( WordPress\Strings::isEmpty( $text ) )
			return FALSE;

		if ( ! $nospace = Core\Text::stripAllSpaces( $text ) )
			return FALSE;

		// already cleaned!
		// $text = WordPress\Strings::cleanupChars( $text );

		if ( in_array( $nospace, self::getBlacklistStrings(), TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function parseFullname( $raw, $fallback = FALSE )
	{
		if ( empty( $raw ) )
			return $fallback;

		// $normalized = Core\Text::normalizeWhitespaceUTF8( WordPress\Strings::cleanupChars( $raw ), FALSE );
		$normalized = Core\Text::singleWhitespaceUTF8( WordPress\Strings::cleanupChars( $raw ) );

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
			'honorific'   => [], // @SEE `Honored` Module
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

						// if ( isset( $parts[$i-1] ) ) {
						if ( isset( $parts[$i-1] ) && isset( $parts[$i+1] ) ) {

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

		// $names['fullname'] = Core\Text::normalizeWhitespaceUTF8( $parts, FALSE );
		$names['fullname'] = Core\Text::singleWhitespaceUTF8( $parts );
		$prefix = '';

		if ( Core\Text::starts( $names['fullname'], 'سیده' ) ) {

			$names['gender']      = 'female';
			$names['honorific'][] = 'sayyidah';

			$prefix = 'سیده';
			$parts  = Core\Text::trim( Core\Text::leftTrim( $names['fullname'], 'سیده', FALSE ) );

		} else if ( Core\Text::starts( $names['fullname'], 'سید' ) ) {

			$names['gender']      = 'male';
			$names['honorific'][] = 'sayyid';

			$prefix = 'سید';
			$parts  = Core\Text::trim( Core\Text::leftTrim( $names['fullname'], 'سید', FALSE ) );
		}

		$parts = Core\Text::splitNormalSpaces( $parts );

		$names['first_name'] = Core\Text::trim( $prefix.' '.$parts[0] );
		unset( $parts[0] );
		$names['last_name'] = implode( ' ', $parts );

		array_walk( $names, [ __CLASS__, 'normalizeName' ] );

		return $names;
	}

	public static function normalizeName( &$value, $key )
	{
		if ( is_array( $value ) )
			return;

		if ( in_array( $key, [
			'raw',
			'honorific',
			'gender',
		], TRUE ) )
			return;

		if ( WordPress\Strings::isEmpty( $value ) ) {
			$value = '';
			return;
		}

		// already cleaned!
		// $value = WordPress\Strings::cleanupChars( $value );

		$value = Core\Text::normalizeZWNJ( $value );
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
			$value = Core\Text::normalizeZWNJ( $part );

		} else if ( is_string( $value ) ) {

			$value = Core\Text::trim( $value );
		}
	}

	public static function replaceSplits( $raw )
	{
		if ( empty( $raw ) )
			return '';

		$parts  = is_array( $raw ) ? $raw : Core\Text::splitNormalSpaces( $raw );
		$bases  = self::getBaseReplaces();
		$splits = self::getCommonSplits();

		foreach ( $parts as &$part ) {

			foreach ( $bases as $before_base => $after_base )
				$part = str_ireplace( $before_base, $after_base, $part );

			foreach ( $splits as $before_split => $after_split ) {
				if ( $before_split === $part ) {
					$part = $after_split;
					break;
				}
			}
		}

		return is_array( $parts ) ? implode( ' ', $parts ) : $parts;
	}

	public static function getBaseReplaces()
	{
		return [
			'ا...' => 'الله',
			'ا…'   => 'الله',
		];
	}

	public static function getCommonSplits()
	{
		return [
			'علیرضا'   => 'علی‌رضا',
			'غلامرضا'  => 'غلام‌رضا',
			'غلامعلی'  => 'غلام‌علی',
			'غلامحسین' => 'غلام‌حسین',
			'روح‌الله' => 'روح‌اله',
			'عبدالله'  => 'عبداله',
			'امرالله'  => 'امراله',
			'فضل‌الله'  => 'فضل‌اله',
			'شمس‌الله'  => 'شمس‌اله	',
			'حسینعلی'  => 'حسین‌علی',
			'حسنعلی'   => 'حسن‌علی',
		];
	}

	public static function getBlacklistStrings()
	{
		return [
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
		];
	}

	// NOT USED
	public static function getCommonFamilies()
	{
		return [
			'موسوی',
			'موسویان',
			'اسکندری',
		];
	}

	public static function getSecondPartFamilies()
	{
		return [
			'آبادی',
			'آباد',
			'نژاد',
			'پور',
			'کیا',
			'زاده',
			'زاد',
			'نیا',
			'نیان',
			'نسب',
			'لو',
			'تبار',
			'وند',
			'مند',
			'منش',
			'پناه',
			'بخت',
			'بیگی',
			'مقدم',
			'راد',
			'گل',
			'خواه',
			'خانی',
			'الدین',
			'الدینی',
			'لو',
			'فر',
			'فرد',
		];
	}

	public static function getSecondPartNames()
	{
		return [
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
			'مجتبی',
			'یحیی',
			'عباس',
			'ناصر',
			'یاسر',
			'باقر',
			'سعید',
			'عرفان',
			'امین',
			'حسام',
		];
	}
}

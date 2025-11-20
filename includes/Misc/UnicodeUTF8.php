<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

/**
 * UTF-8 to Code Point Array Converter in PHP
 *
 * Converts between UTF-8 strings and arrays of ints representing Unicode
 * code-points (sort of UCS-4).
 * - Astral planes are supported.
 * - Surrogates are not allowed.
 * - Occurrences of the BOM are ignored.
 * - PHP multibyte string support is not required.
 *
 * For the original C++ code, see
 * http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUTF8ToUnicode.cpp
 * http://lxr.mozilla.org/seamonkey/source/intl/uconv/src/nsUnicodeToUTF8.cpp
 *
 * @version 1.0, 2003-05-30
 * @source https://hsivonen.fi/php-utf8/
 * @license NPL 1.1/GPL 2.0/LGPL 2.1
 **/

class UnicodeUTF8 extends Core\Base
{

	/**
	 * Takes a `UTF-8` string and returns an array of integers representing the
	 * Unicode characters. Astral planes are supported i.e. the integers in the
	 * output can be > 0xFFFF. Occurrences of the BOM are ignored. Surrogates
	 * are not allowed.
	 *
	 * Returns false if the input string isn't a valid `UTF-8` octet sequence.
	 **/
	public static function utf8ToUnicode( $str )
	{
		$mState = 0;     // cached expected number of octets after the current octet
		                 // until the beginning of the next `UTF-8` character sequence
		$mUcs4  = 0;     // cached Unicode character
		$mBytes = 1;     // cached expected number of octets in the current sequence

		$out = [];
		$len = strlen( $str );

		for ( $i = 0; $i < $len; $i++ ) {

			$in = ord( $str[$i] );

			if ( 0 == $mState ) {

				// When `mState` is zero we expect either an `US-ASCII` character or a
				// multi-octet sequence.
				if ( 0 == ( 0x80 & ( $in ) ) ) {

					// US-ASCII, pass straight through.
					$out[]  = $in;
					$mBytes = 1;

				} else if ( 0xC0 == ( 0xE0 & ( $in ) ) ) {

					// First octet of 2 octet sequence
					$mUcs4  = ( $in );
					$mUcs4  = ( $mUcs4 & 0x1F ) << 6;
					$mState = 1;
					$mBytes = 2;

				} else if (0xE0 == ( 0xF0 & ( $in ) ) ) {

					// First octet of 3 octet sequence
					$mUcs4  = ( $in );
					$mUcs4  = ( $mUcs4 & 0x0F ) << 12;
					$mState = 2;
					$mBytes = 3;

				} else if (0xF0 == ( 0xF8 & ( $in ) ) ) {

					// First octet of 4 octet sequence
					$mUcs4  = ( $in );
					$mUcs4  = ( $mUcs4 & 0x07 ) << 18;
					$mState = 3;
					$mBytes = 4;

				} else if (0xF8 == ( 0xFC & ( $in ) ) ) {

				   /*
					* First octet of 5 octet sequence.
					* This is illegal because the encoded code-point must be either
					* (a) not the shortest form or
					* (b) outside the Unicode range of 0-0x10FFFF.
					* Rather than trying to re-synchronize, we will carry on until the end
					* of the sequence and let the later error handling code catch it.
					*/
					$mUcs4  = ( $in );
					$mUcs4  = ( $mUcs4 & 0x03 ) << 24;
					$mState = 4;
					$mBytes = 5;

				} else if (0xFC == ( 0xFE & ( $in ) ) ) {

					// First octet of 6 octet sequence, see comments for 5 octet sequence.
					$mUcs4  = ( $in );
					$mUcs4  = ( $mUcs4 & 1 ) << 30;
					$mState = 5;
					$mBytes = 6;

				} else {

					/*
					 * Current octet is neither in the US-ASCII range nor a legal first
         			 * octet of a multi-octet sequence.
         			 */
					return FALSE;
				}

			} else {

				// When `mState` is non-zero, We expect a continuation of the multi-octet sequence
				if (0x80 == (0xC0 & ( $in))) {

					// Legal continuation.
					$shift  = ( $mState - 1 ) * 6;
					$tmp    = $in;
					$tmp    = ( $tmp & 0x0000003F ) << $shift;
					$mUcs4 |= $tmp;

					if (0 == --$mState) {
						/*
						 * End of the multi-octet sequence. `mUcs4` now contains the final
        				 * Unicode code-point to be output
         				 *
           				 * Check for illegal sequences and code-points.
           				 */

						// From Unicode 3.1, non-shortest form is illegal
						if ( ( ( 2 == $mBytes ) && ( $mUcs4 < 0x0080 ) ) ||
							( ( 3 == $mBytes ) && ( $mUcs4 < 0x0800 ) ) ||
							( ( 4 == $mBytes ) && ( $mUcs4 < 0x10000 ) ) ||
							( 4 < $mBytes ) ||
							// From Unicode 3.2, surrogate characters are illegal
							( ( $mUcs4 & 0xFFFFF800 ) == 0xD800 ) ||
							// Code-points outside the Unicode range are illegal
							( $mUcs4 > 0x10FFFF )
						) {
							return FALSE;
						}

						if ( 0xFEFF != $mUcs4 ) {
							// BOM is legal but we don't want to output it
							$out[] = $mUcs4;
						}

						// initialize `UTF-8` cache
						$mState = 0;
						$mUcs4  = 0;
						$mBytes = 1;
					}

				} else {

					/*
					 * ( ( 0xC0 & (*in) != 0x80 ) && ( mState != 0 ) )
         			 *
         			 * Incomplete multi-octet sequence.
         			 */
					return FALSE;
				}
			}
		}

		return $out;
	}

	/**
	 * Converts given array of Unicode chars into `UTF-8` string.
	 *
	 * Takes an array of integers representing the Unicode characters and returns
	 * a `UTF-8` string. Astral planes are supported i.e. the integers in the
	 * input can be > 0xFFFF. Occurrences of the BOM are ignored. Surrogates
	 * are not allowed.
	 *
	 * Returns false if the input array contains integers that represent
	 * surrogates or are outside the Unicode range.
	 *
	 * @param array $arr
	 * @return string|false
	 */
	public static function unicodeToUtf8( $arr )
	{
		$dest = '';

		foreach ( $arr as $src ) {

			if ( $src < 0 ) {

				return FALSE;

			} else if ( $src <= 0x007f ) {

				$dest .= chr( $src );

			} else if ( $src <= 0x07ff ) {

				$dest .= chr( 0xc0 | ( $src >> 6 ) );
				$dest .= chr( 0x80 | ( $src & 0x003f ) );

			} else if ( $src == 0xFEFF ) {

				// nop -- zap the BOM

			} else if ( $src >= 0xD800 && $src <= 0xDFFF ) {

				// found a surrogate
				return FALSE;

			} else if ( $src <= 0xffff ) {

				$dest .= chr( 0xe0 | ( $src >> 12 ) );
				$dest .= chr( 0x80 | ( ( $src >> 6 ) & 0x003f ) );
				$dest .= chr( 0x80 | ( $src & 0x003f ) );

			} else if ( $src <= 0x10ffff ) {

				$dest .= chr( 0xf0 | ( $src >> 18 ) );
				$dest .= chr( 0x80 | ( ( $src >> 12 ) & 0x3f ) );
				$dest .= chr( 0x80 | ( ( $src >> 6 ) & 0x3f ) );
				$dest .= chr( 0x80 | ( $src & 0x3f ) );

			} else {

				// out of range
				return FALSE;
			}
		}

		return $dest;
	}
}

<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

/**
 * Further Readings:
 * - https://php.watch/versions/8.2/utf8_encode-utf8_decode-deprecated
 * - https://php.watch/versions/8.2/mbstring-qprint-base64-uuencode-html-entities-deprecated
 */

class Encoding extends Core\Base
{
	/**
	 * Convert all applicable characters to HTML entities.
	 * NOTE: wrapper for `htmlentities()`
	 *
	 * PHP 8.1.0: flags changed from `ENT_COMPAT` to `ENT_QUOTES|ENT_SUBSTITUTE|ENT_HTML401`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function entityEncode( mixed $input ): string
	{
		return htmlentities(
			Text::force( $input ),
			ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
			'UTF-8',
		);
	}

	/**
	 * Convert HTML entities to their corresponding characters.
	 * NOTE: same as `\WP_HTML_Decoder::decode_attribute( $input );`
	 *
	 * @param mixed $input
	 * @return string
	 */
	public static function entityDecode( mixed $input ): string
	{
		return html_entity_decode(
			Core\Text::force( $input ),
			ENT_QUOTES,
			'UTF-8',
		);
	}
}

<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Strings extends Core\Base
{

	// wrapper for `wp_get_list_item_separator()` @since WP 6.0.0
	public static function separator()
	{
		if ( function_exists( 'wp_get_list_item_separator' ) )
			return wp_get_list_item_separator();

		return __( ', ' ); // _x( ', ', 'Strings: Item Seperator', 'geditorial' );
	}

	public static function isEmpty( $string, $empties = NULL )
	{
		if ( ! is_string( $string ) )
			return FALSE;

		$trimmed = Core\Text::trim( $string );

		if ( '' === $trimmed )
			return TRUE;

		if ( is_null( $empties ) )
			$empties = [
				'0', '00', '000', '0000', '00000', '000000',
				'*', '**', '***', '****', '*****', '******',
				'…', '……', '………', '…………', '……………', '………………',
				'.', '..', '...', '....', '.....', '......',
				'-', '--', '---', '----', '-----', '------',
				'–', '––', '–––', '––––', '–––––', '––––––',
				'—', '——', '———', '————', '—————', '——————',
				'0000/00/00', '0000-00-00', '00/00/00', '00-00-00',
				'<p></p>',
				'<body><p></p></body>',
				'<body></body>',
				'<body> </body>',
				'null', 'NULL', 'Null',
				'false', 'FALSE', 'False',
				'zero', 'ZERO', 'Zero',
				'ندارد',
			];

		foreach ( (array) $empties as $empty )
			if ( $empty === $trimmed )
				return TRUE;

		return FALSE;
	}

	public static function filterEmpty( $strings, $empties = NULL )
	{
		return array_filter( $strings, static function ( $value ) use ( $empties ) {

			if ( self::isEmpty( $value, $empties ) )
				return FALSE;

			return ! empty( $value );
		} );
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.Core\HTML::escape( $text ).'">'.$append.'</span>';

		return Core\Text::trimChars( $text, $length, $append );
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 *
	 * @param  string $string
	 * @param  null|string|array $delimiters
	 * @param  null|int $limit
	 * @param  string $delimiter
	 * @return array $separated
	 */
	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		if ( '0' === $string || 0 === $string )
			return [ '0' ];

		if ( empty( $string ) )
			return [];

		if ( is_array( $string ) )
			return $string;

		if ( is_null( $delimiters ) )
			$delimiters = [
				// '/',
				'،',
				'؛',
				';',
				',',
				// '-',
				// '_',
				'|',
			];

		else if ( $delimiters && is_string( $delimiters ) )
			$delimiters = Core\Arraay::prepSplitters( $delimiters, $delimiter );

		if ( ! empty( $delimiters ) )
			$string = str_ireplace( $delimiters, $delimiter, $string );

		$separated = is_null( $limit )
			? explode( $delimiter, $string )
			: explode( $delimiter, $string, $limit );

		return Core\Arraay::prepString( $separated );
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '', $separator = NULL )
	{
		if ( is_null( $separator ) )
			$separator = self::separator();

		if ( $items && count( $items ) )
			return $before.implode( $separator, $items ).$after;

		return $empty;
	}

	public static function getPiped( $items, $before = '', $after = '', $empty = '', $separator = NULL )
	{
		return self::getJoined( $items, $before, $after, $empty, $separator ?? '|' );
	}

	public static function getCounted( $count, $template = '%s' )
	{
		if ( TRUE === $template )
			$template = ' <span class="-count-wrap">(%s)</span>';

		else if ( is_null( $template ) )
			$template = '%s';

		else if ( empty( $template ) )
			return '';

		return sprintf( $template, '<span class="-count" data-count="'.$count.'">'.Core\Number::format( $count ).'</span>' );
	}

	// @SOURCE: P2
	public static function excerptedTitle( $content, $word_count )
	{
		$content = strip_tags( $content );
		$words   = preg_split( '/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/', $content, $word_count + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) > $word_count ) {
			array_pop( $words ); // remove remainder of words
			$content = implode( ' ', $words );
			$content.= '…';
		} else {
			$content = implode( ' ', $words );
		}

		$content = trim( strip_tags( $content ) );

		return $content;
	}

	/**
	 * Strips all HTML tags including script and style.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::strip_all_tags()`
	 *
	 * @param string $text The text to strip the tags from.
	 * @return string The processed string.
	 */
	public static function stripAllTags( $text )
	{
		return \wp_strip_all_tags( $text );
	}

	/**
	 * Standardize whitespace in a string.
	 *
	 * Replace line breaks, carriage returns, tabs with a space, then remove double spaces.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::standardize_whitespace()`
	 *
	 * @param string $text Text input to standardize.
	 * @return string
	 */
	public static function standardizeWhitespace( $text )
	{
		return \trim( \str_replace( '  ', ' ', \str_replace( [ "\t", "\n", "\r", "\f" ], ' ', $text ) ) );
	}

	/**
	 * First strip out registered and enclosing shortcodes using native WordPress strip_shortcodes function.
	 * Then strip out the shortcodes with a filthy regex, because people don't properly register their shortcodes.
	 *
	 * @source `Yoast\WP\SEO\Helpers\String_Helper::strip_shortcode()`
	 *
	 * @param string $text Input string that might contain shortcodes.
	 * @return string String without shortcodes.
	 */
	public static function stripShortCode( $text )
	{
		return \preg_replace( '`\[[^\]]+\]`s', '', \strip_shortcodes( $text ) );
	}

	public static function cleanupChars( $string, $html = FALSE )
	{
		if ( self::empty( $string ) )
			return $string;

		if ( ! class_exists( 'geminorum\\gNetwork\\Core\\Orthography' ) )
			return apply_filters( 'string_format_i18n', $string );

		// return $html
		// 	? \geminorum\gNetwork\Core\Orthography::cleanupPersianHTML( $string )
		// 	: \geminorum\gNetwork\Core\Orthography::cleanupPersian( $string );

		return \geminorum\gNetwork\Core\Orthography::cleanupPersianChars( $string );
	}
}

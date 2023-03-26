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

		$trimmed = trim( $string );

		if ( '' === $trimmed )
			return TRUE;

		if ( is_null( $empties ) )
			$empties = [
				'.', '..', '...',
				'-', '--', '---',
				'–', '––', '–––',
				'—', '——', '———',
				'<p></p>',
				'<body><p></p></body>',
				'<body></body>',
				'<body> </body>',
			];

		foreach ( (array) $empties as $empty )
			if ( $empty === $trimmed )
				return TRUE;

		return FALSE;
	}

	public static function filterEmpty( $strings, $empties = NULL )
	{
		return array_filter( $strings, static function( $value ) use ( $empties ) {

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

	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
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

		$string = str_ireplace( $delimiters, $delimiter, $string );

		$seperated = is_null( $limit )
			? explode( $delimiter, $string )
			: explode( $delimiter, $string, $limit );

		return Core\Arraay::prepString( $seperated );
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '', $separator = NULL )
	{
		if ( is_null( $separator ) )
			$separator = self::separator();

		if ( $items && count( $items ) )
			return $before.implode( $separator, $items ).$after;

		return $empty;
	}

	public static function getCounted( $count, $template = '%s' )
	{
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
}

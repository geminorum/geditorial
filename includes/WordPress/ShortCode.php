<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class ShortCode extends Core\Base
{

	// @SEE: https://konstantin.blog/2013/dont-do_shortcode/
	public static function apply( $text, $ignore_html = FALSE )
	{
		return empty( $text ) ? '' : apply_shortcodes( $text, $ignore_html );
	}

	/**
	 * Calls a shortcode by its tag name.
	 * @source https://wpbitz.com/dont-use-do_shortcode/
	 *
	 * Directly executes a shortcode's callback function using the shortcode's
	 * tag name. Can execute a function even if it's in an object class.
	 * Simply pass the shortcode's tag and an array of any attributes.
	 *
	 * @global array  $shortcode_tags
	 * @param  string $shortcode      The shortcode tag name.
	 * @param  array  $atts           The attributes (optional).
	 * @param  array  $content        The shortcode content (null by default).
	 *
	 * @return string|bool False on failure, the result of the shortcode on success.
	 */
	public static function tag( $shortcode, $atts = [], $content = NULL )
	{
		global $shortcode_tags;

		if ( isset( $shortcode_tags[$shortcode] ) && is_callable( $shortcode_tags[$shortcode] ) )
			return call_user_func( $shortcode_tags[$shortcode], $atts, $content, $shortcode );

		return $content;
	}

	public static function wrap( $html, $suffix = FALSE, $args = [], $block = TRUE, $extra = [], $base = '' )
	{
		if ( is_null( $html ) )
			return $html;

		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] )  ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ '-wrap' ];
		$wrap    = TRUE === $args['wrap'] ? ( $block ? 'div' : 'span' ) : $args['wrap'];

		if ( $base )
			$classes[] = sprintf( '%s-wrap-shortcode', $base );

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( ! empty( $args['class'] ) )
			$classes = Core\HTML::attrClass( $classes, $args['class'] );

		if ( $after )
			return $before.Core\HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $html ).$after;

		return Core\HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $before.$html );
	}
}

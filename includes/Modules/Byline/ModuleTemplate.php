<?php namespace geminorum\gEditorial\Modules\Byline;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{
	const MODULE = 'byline';

	public static function renderDefault( $atts = [], $post = NULL )
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		if ( ! array_key_exists( 'default', $atts ) )
			$atts['default'] = FALSE;

		$html = \gEditorial()->module( static::MODULE )->get_byline_for_post( $post, $atts, $atts['default'] );

		if ( ! $atts['echo'] )
			return $html;

		if ( $html )
			echo $html;

		else if ( $atts['default'] )
			echo $atts['default'];

		return TRUE;
	}

	public static function renderFeatured( $atts = [], $post = NULL )
	{
		if ( ! array_key_exists( 'featured', $atts ) )
			$atts['featured'] = TRUE;

		if ( ! array_key_exists( 'template', $atts ) )
			$atts['template'] = 'cards';

		if ( ! array_key_exists( 'walker', $atts ) )
			$atts['walker'] = [ __NAMESPACE__.'\\ModuleHelper', 'bylineTemplateWalker' ];

		return self::renderDefault( $atts, $post );
	}
}

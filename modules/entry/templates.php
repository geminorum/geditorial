<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEntryTemplates extends gEditorialTemplateCore
{

	const MODULE = 'entry';

	// FIXME: DEPRECATED
	public static function section_shortcode( $atts, $content = NULL, $tag = '' )
	{
		self::__dep( 'gEditorialEntry::section_shortcode()' );

		return $content;
	}
}

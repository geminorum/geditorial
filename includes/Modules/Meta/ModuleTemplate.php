<?php namespace geminorum\gEditorial\Modules\Meta;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'meta';

	public static function metaAuthor( $atts = [] )
	{
		return self::metaField( 'byline', $atts );
	}

	// BACK-COMP
	public static function metaLabel( $atts = [] )
	{
		if ( ! array_key_exists( 'field', $atts ) )
			$atts['field'] = 'label_string';

		if ( ! array_key_exists( 'taxonomy', $atts ) )
			// TODO: maybe get from labeled Module
			$atts['taxonomy'] = self::constant( 'label_taxonomy', 'label' );

		return self::metaTermField( $atts, static::MODULE, FALSE );
	}

	public static function metaLead( $atts = [] )
	{
		return self::metaFieldHTML( 'lead', $atts );
	}

	public static function metaHighlight( $atts = [] )
	{
		return self::metaFieldHTML( 'highlight', $atts );
	}
}

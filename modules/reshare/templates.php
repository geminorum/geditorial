<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshareTemplates extends gEditorialTemplateCore
{

	// FIXME: DEPRECATED
	// USE: gEditorialMetaTemplates::metaLink()
	public static function source( $atts = array() )
	{
		self::__dev_dep( 'gEditorialMetaTemplates::metaLink()' );

		if ( class_exists( 'gEditorialMetaTemplates' ) ) {

			$args = array_merge( array(
				'title_default' => _x( 'External Source', 'Modules: Reshare: Meta Link Default Title', GEDITORIAL_TEXTDOMAIN ),
				'title_meta'    => array( 'source_title', 'reshare_source_title' ),
				'url_meta'      => array( 'source_url', 'reshare_source_url' ),
				'url_default'   => FALSE,
			), $atts );

			$args['echo'] = FALSE;
			$html = gEditorialMetaTemplates::metaLink( $args );

			if ( isset( $atts['echo'] ) && ! $atts['echo'] )
				return $html;

			echo $html;
			return TRUE;
		}

		return FALSE;
	}
}

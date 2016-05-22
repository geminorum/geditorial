<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshareTemplates extends gEditorialTemplateCore
{

	public static function source( $atts = array() )
	{
		if ( class_exists( 'gEditorialMetaTemplates' ) ) {

			$args = array_merge( array(
				'title_default' => _x( 'External Source', 'Reshare: metaLink default title', GEDITORIAL_TEXTDOMAIN ),
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

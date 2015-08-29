<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialReshareTemplates extends gEditorialTemplateCore
{

	public static function source( $atts = array() )
	{
		if ( class_exists( 'gEditorialMetaTemplates' ) ) {

			$args = array_merge( array(
				'title_meta'    => 'reshare_source_title',
				'title_default' => _x( 'External Source', 'Reshare: metaLink default title', GEDITORIAL_TEXTDOMAIN ),
				'url_meta'      => 'reshare_source_url',
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

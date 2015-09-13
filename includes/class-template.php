<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTemplateCore
{

	public static function atts( $pairs, $atts )
	{
		$atts = (array) $atts;
		$out  = array();

		foreach ( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}

		return $out;
	}

	// BEFORE: term_description()
	public static function termDescription( $term, $echo_attr = FALSE )
	{
		if ( ! $term )
			return;

		if ( ! $term->description )
			return;

		// Bootstrap 3
		$desc = esc_attr( $term->name ).'"  data-toggle="popover" data-trigger="hover" data-content="'.$term->description;

		if ( ! $echo_attr )
			// return $term->name.' :: '.strip_tags( $term->description );
			return $desc;

		echo ' title="'.$desc.'"';
	}

	public static function getSearchLink( $query = FALSE )
	{
		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, $query, GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return $query ? add_query_arg( 's', $query, get_option( 'home' ) ) : get_option( 'home' );
	}
}

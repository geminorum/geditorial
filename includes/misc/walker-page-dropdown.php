<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

require_once( ABSPATH.WPINC.'/class-walker-page-dropdown.php' );

class Walker_PageDropdown extends \Walker_PageDropdown
{

	public function start_el( &$output, $page, $depth = 0, $args = [], $id = 0 )
	{
		$pad = str_repeat( '&nbsp;', $depth * 3 );

		if ( ! isset( $args['value_field'] ) || ! isset( $page->{$args['value_field']} ) )
			$args['value_field'] = 'ID';

		$output.= "\t<option class=\"level-$depth\" value=\"".esc_attr( $page->{$args['value_field']} )."\"";

		if ( $page->{$args['value_field']} == $args['selected'] ) // <---- CHANGED
			$output.= ' selected="selected"';

		$output.= '>';

		$title = $page->post_title;

		if ( ! empty( $args['title_with_meta'] ) && gEditorial()->enabled( 'meta' ) ) {

			if ( $meta = gEditorial()->meta->get_postmeta_field( $page->ID, $args['title_with_meta'] ) )
				$title = $meta;
		}

		if ( '' === $title )
			$title = sprintf( __( '#%d (no title)' ), $page->ID );

		$output.= $pad.esc_html( apply_filters( 'list_pages', $title, $page ) );
		$output.= "</option>\n";
	}
}

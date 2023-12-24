<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

require_once ABSPATH.'wp-includes/class-walker-category-dropdown.php';

class WalkerCategoryDropdown extends \Walker_CategoryDropdown
{
	public $el_opened = TRUE;

	public function start_el( &$output, $term, $depth = 0, $args = [], $id = 0 )
	{
		if ( ! empty( $args['restricted'] ) ) {

			if ( $roles = get_term_meta( $term->term_id, 'roles', TRUE ) ) {

				if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ) ) ) {

					if ( 'disabled' === $args['restricted'] ) {

						$args['disabled'] = TRUE;

					// } else if ( 'hidden' === $args['restricted'] ) {

					// 	$args['hidden'] = TRUE;

					} else {

						return;
					}
				}
			}
		}

		if ( isset( $args['value_field'] ) && isset( $term->{$args['value_field']} ) )
			$value_field = $args['value_field'];
		else
			$value_field = 'term_id';

		$output.= "\t<option class=\"level-$depth\" value=\"".esc_attr( $term->{$value_field} ).'"';

		// type-juggling causes false matches, so we force everything to a string
		if ( (string) $term->{$value_field} === (string) $args['selected'] )
			$output.= ' selected="selected"';

		if ( ! empty( $args['disabled'] ) )
			$output.= ' disabled="disabled"';

		$output.= '>';

		$name = WordPress\Term::title( $term );

		if ( ! empty( $args['title_with_meta'] ) ) {

			if ( $meta = get_term_meta( $term->term_id, $args['title_with_meta'], TRUE ) )
				$name = $meta;
		}

		if ( ! empty( $args['title_with_parent'] ) )
			$name = WordPress\Term::getParentTitles( $term, $name );
		else
			$output.= str_repeat( '&nbsp;', $depth * 3 );

		/** This filter is documented in wp-includes/category-template.php */
		$output.= apply_filters( 'list_cats', $name, $term );

		if ( $args['show_count'] )
			$output .= '&nbsp;&nbsp;(' . number_format_i18n( $term->count ) . ')';

		$output .= "</option>\n";
	}
}

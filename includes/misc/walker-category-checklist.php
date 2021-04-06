<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\User;

require_once ABSPATH.'wp-admin/includes/class-walker-category-checklist.php';

class Walker_Category_Checklist extends \Walker_Category_Checklist
{
	public $el_opened = TRUE;

	public function start_el( &$output, $term, $depth = 0, $args = [], $id = 0 )
	{
		if ( ! array_key_exists( 'atts', $args ) )
			return parent::start_el( $output, $term, $depth, $args, $id );

		$atts = $args['atts'];

		if ( empty( $atts['role'] ) )
			return $this->_start_el( $output, $term, $depth, $args, $id );

		$roles = get_term_meta( $term->term_id, 'roles', TRUE );

		if ( ! $roles )
			return $this->_start_el( $output, $term, $depth, $args, $id );

		if ( User::hasRole( array_merge( [ 'administrator' ], (array) $roles ) ) )
			return $this->_start_el( $output, $term, $depth, $args, $id );

		if ( 'disabled' == $atts['role'] ) {
			$args['disabled'] = TRUE;
			$this->_start_el( $output, $term, $depth, $args, $id );
		} else {
			$this->el_opened = FALSE;
		}

		// to avoid clearing the non visible relationship

		$taxonomy = empty( $args['taxonomy'] ) ? 'category' : $args['taxonomy'];
		$selected = empty( $args['selected_cats'] ) ? [] : $args['selected_cats'];

		if ( in_array( $term->term_id, $selected ) )
			echo '<input type="hidden" name="'
				.$this->_get_name( $atts, $taxonomy )
				.'[]" value="'.$term->term_id.'" />';
	}

	private function _get_name( $atts = [], $taxonomy = 'category' )
	{
		if ( ! empty( $atts['name'] ) )
			return $atts['name'].'['.$taxonomy.']';

		if ( $taxonomy == 'category' )
			return 'post_category';

		return 'tax_input['.$taxonomy.']';
	}

	// overrided for custom name
	private function _start_el( &$output, $term, $depth = 0, $args = [], $id = 0 )
	{
		$atts     = empty( $args['atts'] ) ? [] : (array) $args['atts'];
		$taxonomy = empty( $args['taxonomy'] ) ? 'category' : $args['taxonomy'];
		$selected = empty( $args['selected_cats'] ) ? [] : $args['selected_cats'];
		$popular  = empty( $args['popular_cats'] ) ? [] : $args['popular_cats'];
		$class    = in_array( $term->term_id, $popular ) ? ' class="popular-category"' : '';

		if ( ! empty( $args['list_only'] ) ) {

			$aria_checked = 'false';
			$inner_class  = 'category';
			$icon_after   = '';

			if ( in_array( $term->term_id, $selected ) ) {
				$inner_class.= ' selected -selected';
				$aria_checked = 'true';
				// $icon_after   = HTML::getDashicon( 'yes' ); // working but no need
			}

			$output.= '<li'.$class.'>'
				.'<div class="'.$inner_class.'" data-term-id='.$term->term_id
				.' tabindex="0" role="checkbox" aria-checked="'.$aria_checked.'">'
				.esc_html( apply_filters( 'the_category', $term->name, '', '' ) )
				.$icon_after.'</div>';

		} else {

			$output.= "<li id='{$taxonomy}-{$term->term_id}'$class>"
				.'<label class="selectit"><input value="'.$term->term_id
				.'" type="checkbox" name="'.$this->_get_name( $atts, $taxonomy )
				.'[]" id="in-'.$taxonomy.'-'.$term->term_id.'"'
				.checked( in_array( $term->term_id, $selected ), TRUE, FALSE )
				.disabled( empty( $args['disabled'] ), FALSE, FALSE ).' /> '
				.esc_html( apply_filters( 'the_category', $term->name, '', '' ) ).'</label>';
		}
	}

	public function end_el( &$output, $category, $depth = 0, $args = [] )
	{
		if ( $this->el_opened )
			$output.= "</li>\n";

		$this->el_opened = TRUE;
	}
}

<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\Taxonomy;

class Walker_User_Checklist extends \Walker
{
	public $tree_type = 'user';
	public $db_fields = [ 'parent' => 'parent', 'id' => 'ID' ];

	public function start_el( &$output, $user, $depth = 0, $args = [], $id = 0 )
	{
		$value = FALSE;

		// FIXME: waiting for WP 5.0 to use `meta_box_sanitize_cb` in the user tax
		// FIXME: alse the tax must change back to non-hierarchical
		// $value = $user->user_login;

		if ( $term = Taxonomy::getTerm( $user->user_login, $args['taxonomy'] ) )
			$value = $term->term_id;

		if ( ! empty( $args['atts']['name'] ) )
			$name = $args['atts']['name'].'['.$args['taxonomy'].']';
		else
			$name = 'tax_input['.$args['taxonomy'].']';

		if ( ! empty( $args['list_only'] ) ) {

			$aria_checked = 'false';
			$inner_class  = '-user';
			$icon_after   = '';

			if ( in_array( $user->user_login, (array) $args['selected'] ) ) {
				$inner_class.= ' selected -selected';
				$aria_checked = 'true';
				// $icon_after   = HTML::getDashicon( 'yes' ); // working but no need
			}

			$output.= '<li class="-user">'
				.'<div class="'.$inner_class.'" data-term-id='.$term->term_id
				.' tabindex="0" role="checkbox" aria-checked="'.$aria_checked.'">'
				.( get_option( 'show_avatars' ) ? get_avatar( $user->ID, 16 ) : '' )
				.'<span class="-name">'.HTML::escape( $user->display_name ).'</span> '
				.'<code class="-login">&#8206;@'.$user->user_login.'&#8207;</code>'
				.$icon_after.'</div>';

		} else {

			$output.= "\n".'<li class="-user"><label>'.
				HTML::tag( 'input', [
					'type'     => 'checkbox',
					'name'     => $name.'[]',
					'value'    => $value, // $user->user_login,
					'checked'  => in_array( $user->user_login, (array) $args['selected'] ),
					'disabled' => ! empty( $args['disabled'] ) || ! $value,
				] )
				.( get_option( 'show_avatars' ) ? get_avatar( $user->ID, 32 ) : '' )
				.' <span class="-name">'.HTML::escape( $user->display_name ).'</span>'
				.' <code class="-login">&#8206;@'.$user->user_login.'&#8207;</code>'
				.'<br /><span class="-email code">'.HTML::mailto( $user->user_email ).'</span></label>';
		}
	}

	public function end_el( &$output, $category, $depth = 0, $args = [] )
	{
		$output.= '</li>';
	}
}

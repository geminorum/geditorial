<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;

class MetaBox extends Core\Base
{

	// @SEE: https://github.com/bainternet/My-Meta-Box

	// SEE: [Use Chosen for a replacement WordPress taxonomy metabox](https://gist.github.com/helen/1573966)
	// callback for meta box for choose only tax
	// CAUTION: tax must be cat (hierarchical)
	// @SOURCE: `post_categories_meta_box()`
	public static function checklistTerms( $post, $box )
	{
		$args = self::atts( [
			'taxonomy' => 'category',
			'edit_url' => NULL,
		], empty( $box['args'] ) ? [] : $box['args'] );

		$tax_name = esc_attr( $args['taxonomy'] );
		$taxonomy = get_taxonomy( $args['taxonomy'] );

		$html = wp_terms_checklist( $post->ID, [
			'taxonomy'      => $tax_name,
			'checked_ontop' => FALSE,
			'echo'          => FALSE,
		] );

		echo '<div id="taxonomy-'.$tax_name.'" class="geditorial-admin-wrap-metabox choose-tax">';

			if ( $html ) {

				echo '<div class="field-wrap-list"><ul>'.$html.'</ul></div>';

				// allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
				echo '<input type="hidden" name="tax_input['.$tax_name.'][]" value="0" />';

			} else {
				self::fieldEmptyTaxonomy( $taxonomy, $args['edit_url'] );
			}

		echo '</div>';
	}

	public static function fieldEmptyTaxonomy( $taxonomy, $edit = NULL )
	{
		$object = is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );
		$manage = current_user_can( $object->cap->manage_terms );

		if ( $manage && is_null( $edit ) )
			$edit = WordPress::getEditTaxLink( $object->name );

		echo '<div class="field-wrap field-wrap-empty">';

			if ( $edit )
				echo HTML::tag( 'a', [
					'href'   => $edit,
					'title'  => $object->labels->add_new_item,
					'target' => '_blank',
				], $object->labels->not_found );

			else
				echo '<span>'.$object->labels->not_found.'</span>';

		echo '</div>';
	}

	public static function fieldEmptyPostType( $post_type )
	{
		$object = is_object( $post_type ) ? $post_type : get_post_type_object( $post_type );

		echo '<div class="field-wrap field-wrap-empty">';
			echo HTML::tag( 'a', [
				'href'   => add_query_arg( [ 'post_type' => $post_type ], admin_url( 'post-new.php' ) ),
				'title'  => $object->labels->add_new_item,
				'target' => '_blank',
			], $object->labels->not_found );
		echo '</div>';
	}

	public static function dropdownAssocPosts( $post_type, $selected = '', $prefix = '', $exclude = '' )
	{
		return wp_dropdown_pages( [
			'post_type'        => $post_type,
			'selected'         => $selected,
			'name'             => ( $prefix ? $prefix.'-' : '' ).$post_type.'[]',
			'id'               => ( $prefix ? $prefix.'-' : '' ).$post_type.'-'.( $selected ? $selected : '0' ),
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => Settings::showOptionNone(),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'value_field'      => 'post_name',
			'exclude'          => $exclude,
			'echo'             => 0,
			'walker'           => new Walker_PageDropdown(),
		] );
	}
}

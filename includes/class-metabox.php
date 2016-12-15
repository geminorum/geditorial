<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaBox extends gEditorialBaseCore
{

	// SEE: [Use Chosen for a replacement WordPress taxonomy metabox](https://gist.github.com/helen/1573966)
	// callback for meta box for choose only tax
	// CAUTION: tax must be cat (hierarchical)
	// @SOURCE: `post_categories_meta_box()`
	public static function checklistTerms( $post, $box )
	{
		$args = self::atts( array(
			'taxonomy' => 'category',
			'edit_url' => NULL,
		), empty( $box['args'] ) ? array() : $box['args'] );

		$tax_name = esc_attr( $args['taxonomy'] );
		$taxonomy = get_taxonomy( $args['taxonomy'] );

		$html = wp_terms_checklist( $post->ID, array(
			'taxonomy'      => $tax_name,
			'checked_ontop' => FALSE,
			'echo'          => FALSE,
		) );

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
			$edit = gEditorialWordPress::getEditTaxLink( $object->name );

		echo '<div class="field-wrap field-wrap-empty">';

			if ( $edit )
				echo gEditorialHTML::tag( 'a', array(
					'href'   => $edit,
					'title'  => $object->labels->add_new_item,
					'target' => '_blank',
				), $object->labels->not_found );

			else
				echo '<span>'.$object->labels->not_found.'</span>';

		echo '</div>';
	}

	public static function fieldEmptyPostType( $post_type )
	{
		$object = is_object( $post_type ) ? $post_type : get_post_type_object( $post_type );

		echo '<div class="field-wrap field-wrap-empty">';
			echo gEditorialHTML::tag( 'a', array(
				'href'   => add_query_arg( array( 'post_type' => $post_type ), admin_url( 'post-new.php' ) ),
				'title'  => $object->labels->add_new_item,
				'target' => '_blank',
			), $object->labels->not_found );
		echo '</div>';
	}
}

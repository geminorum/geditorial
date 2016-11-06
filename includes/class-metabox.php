<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaBox extends gEditorialBaseCore
{

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

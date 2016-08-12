<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaBox extends gEditorialBaseCore
{

	public static function fieldEmptyTaxonomy( $taxonomy )
	{
		$object = get_taxonomy( $taxonomy );

		echo '<div class="field-wrap field-wrap-empty">';
			echo self::html( 'a', array(
				'href'   => self::getEditTaxLink( $taxonomy ),
				'title'  => $object->labels->add_new_item,
				'target' => '_blank',
			), $object->labels->not_found );
		echo '</div>';
	}

	public static function fieldEmptyPostType( $post_type )
	{
		$object = get_post_type_object( $post_type );

		echo '<div class="field-wrap field-wrap-empty">';
			echo self::html( 'a', array(
				'href'   => add_query_arg( array( 'post_type' => $post_type ), admin_url( 'post-new.php' ) ),
				'title'  => $object->labels->add_new_item,
				'target' => '_blank',
			), $object->labels->not_found );
		echo '</div>';
	}
}

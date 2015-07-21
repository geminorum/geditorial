<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaTemplates
{
	public static function sanitize_field( $field )
	{
		$fields = array(
			'over-title' => 'ot',
			'sub-title'  => 'st',
		);

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return $field;
	}

	public static function meta( $field, $b = '', $a = '', $f = FALSE, $post_id = NULL, $args = array() )
	{
		global $gEditorial, $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$meta = $gEditorial->meta->get_postmeta( $post_id, self::sanitize_field( $field ), FALSE );

		if ( FALSE !== $meta ) {
			$html = $b.( $f ? $f( apply_filters( 'gmeta_meta', $meta, $field ) ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return TRUE;
		}
		return FALSE;
	}

	public static function get_meta( $field, $atts = array() )
	{
		global $gEditorial, $post;

		if ( isset( $atts['id'] ) && FALSE === $atts['id'] )
			$atts['id'] = $post->ID;

		$args = shortcode_atts( array(
			'id'  => $post->ID,
			'def' => '',
		), $atts );

		return $gEditorial->meta->get_postmeta( $args['id'], self::sanitize_field( $field ), $args['def'] );
	}

	public static function gmeta_lead( $b = '', $a = '', $f = FALSE, $args = array() )
	{
		$meta = self::get_meta( 'le', array_merge( array( 'id' => FALSE, 'def' => FALSE ), $args ) );

		if ( FALSE !== $meta ) {
			$html = $b.( $f ? $f( apply_filters( 'gmeta_lead', $meta ) ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return TRUE;
		}

		return FALSE;
	}

	public static function gmeta_author( $b = '', $a = '', $f = FALSE, $args = array() )
	{
		$meta = self::get_meta( 'as', array_merge( array( 'id' => FALSE, 'def' => FALSE ), $args ) );

		if ( FALSE !== $meta ) {
			$html = $b.( $f ? $f( $meta ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return TRUE;
		}

		return FALSE;
	}

	public static function gmeta_label( $b = '', $a = '', $f = FALSE, $args = array() )
	{
		global $gEditorial, $post;

		$id     = isset( $args['id'] ) ? $args['id'] : $post->ID;
		$ct_tax = $gEditorial->get_module_constant( 'meta', 'ct_tax', 'label' );

		$term  = gEditorialHelper::theTerm( $ct_tax, $id, TRUE );
		$title = self::get_meta( 'ch', array( 'id' => $id, 'def' => FALSE ) );
		$link  = $term ? get_term_link( $term, $ct_tax ) : ( $title ? get_option( 'home' ).'/?s='.urlencode( $title ) : FALSE );
		$desc  = $term ? $term->name.( $term->description ? strip_tags( ' :: '.$term->description ) : '' ) : sprintf( apply_filters( 'gmeta_search_link_title_attr', _x( 'Search %1$s for %2$s', GEDITORIAL_TEXTDOMAIN ) ), get_bloginfo( 'name' ), $title );

		if ( $term || $title ) {
			@$value = $title ? $title : $term->name;

			if ( $f )
				$value = $f( $value );

			if ( isset( $args['img'] ) && $args['img'] )
				$value = '<img src="'.$args['img'].'" title="'.$value.'" alt="'.$value.'" />';

			$html = $b.'<a href="'.$link.'" title="'.esc_attr( $desc ).'">'.$value.'</a>'.$a;

			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return TRUE;
		} else if ( isset( $args['def'] ) ) {
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $args['def'];
			}
			echo $args['def'];
			return FALSE;
		}
		return FALSE;
	}

}

if ( ! function_exists( 'gmeta' ) ) : function gmeta( $field, $b = '', $a = '', $f = FALSE, $id = NULL, $args = array() ){
	return gEditorialMetaTemplates::meta( $field, $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'get_gmeta' ) ) : function get_gmeta( $field, $args = array() ){
	return gEditorialMetaTemplates::get_meta( $field, $args );
} endif;

if ( ! function_exists( 'gmeta_label' ) ) : function gmeta_label( $b = '', $a = '', $f = FALSE, $args = array() ) {
	return gEditorialMetaTemplates::gmeta_label( $b, $a, $f, $args );
} endif;

if ( ! function_exists( 'gmeta_lead' ) ) : function gmeta_lead( $b = '', $a = '', $f = FALSE, $args = array() ) {
	return gEditorialMetaTemplates::gmeta_lead( $b, $a, $f, $args );
} endif;

if ( ! function_exists( 'gmeta_author' ) ) : function gmeta_author( $b = '', $a = '', $f = FALSE, $args = array() ) {
	return gEditorialMetaTemplates::gmeta_author( $b, $a, $f, $args );
	//$author = get_the_author();
	//if ( ! empty( $author ) ) echo $b.( $f ? $f( $author ) : $author ).$a;
} endif;

if ( ! function_exists( 'gmeta_thumbnail' ) ) : function gmeta_thumbnail( $place_holder, $b = '', $a = '', $f = FALSE, $args = array() ) { return; } endif;
if ( ! function_exists( 'gmeta_stats' ) ) : function gmeta_stats( $b = '', $a = '', $f = FALSE, $post_id = FALSE ) { return; } endif;

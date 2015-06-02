<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaTemplates
{

	public static function sanitize_field( $field )
	{
		$fields = array(
			'over-title' => 'ot',
			'sub-title' => 'st',
		);

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return $field;
	}

	public static function meta( $field, $b = '', $a = '', $f = false, $post_id = null, $args = array() )
	{
		global $gEditorial, $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$meta = $gEditorial->meta->get_postmeta( $post_id, self::sanitize_field( $field ), false );

		if ( false !== $meta ) {
			$html = $b.( $f ? $f( apply_filters( 'gmeta_meta', $meta, $field ) ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return true;
		}
		return false;
	}

	public static function get_meta( $field, $atts = array() )
	{
		global $gEditorial, $post;
		if ( isset( $atts['id'] ) && false === $atts['id'] )
			$atts['id'] = $post->ID;

		$args = shortcode_atts( array(
			'id' => $post->ID,
			'def' => '',
		), $atts );

		return $gEditorial->meta->get_postmeta( $args['id'], self::sanitize_field( $field ), $args['def'] );
	}

	public static function gmeta_lead( $b = '', $a = '', $f = false, $args = array() )
	{
		$meta = self::get_meta( 'le', array_merge( array( 'id' => false, 'def' => false ), $args ) );
		if ( false !== $meta ) {
			$html = $b.( $f ? $f( apply_filters( 'gmeta_lead', $meta ) ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return true;
		}
		return false;
	}

	public static function gmeta_author( $b = '', $a = '', $f = false, $args = array() )
	{
		$meta = self::get_meta( 'as', array_merge( array( 'id' => false, 'def' => false ), $args ) );
		if ( false !== $meta ) {
			$html = $b.( $f ? $f( $meta ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return true;
		}
		return false;
	}

	public static function gmeta_label( $b = '', $a = '', $f = false, $args = array() )
	{
		global $gEditorial, $post;
		$id = isset( $args['id'] ) ? $args['id'] : $post->ID;

		$term = gEditorialHelper::theTerm( $gEditorial->meta->ct_tax, $id, true );
		$title = self::get_meta( 'ch', array( 'id' => $id, 'def' => false ) );
		$link = $term ? get_term_link( $term, $gEditorial->meta->ct_tax ) : ( $title ? get_option( 'home' ).'/?s='.urlencode( $title ) : false );
		$desc = $term ? $term->name.( $term->description ? strip_tags( ' :: '.$term->description ) : '' ) : sprintf( apply_filters( 'gmeta_search_link_title_attr', _x( 'Search %1$s for %2$s', GEDITORIAL_TEXTDOMAIN ) ), get_bloginfo( 'name' ), $title );

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
			return true;
		} else if ( isset( $args['def'] ) ) {
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $args['def'];
			}
			echo $args['def'];
			return false;
		}
		return false;
	}

}

if ( ! function_exists( 'gmeta' ) ) : function gmeta( $field, $b = '', $a = '', $f = false, $id = null, $args = array() ){
	return gEditorialMetaTemplates::meta( $field, $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'get_gmeta' ) ) : function get_gmeta( $field, $args = array() ){
	return gEditorialMetaTemplates::get_meta( $field, $args );
} endif;

if ( ! function_exists( 'gmeta_label' ) ) : function gmeta_label( $b = '', $a = '', $f = false, $args = array() ) {
	return gEditorialMetaTemplates::gmeta_label( $b, $a, $f, $args );
} endif;

if ( ! function_exists( 'gmeta_lead' ) ) : function gmeta_lead( $b = '', $a = '', $f = false, $args = array() ) {
	return gEditorialMetaTemplates::gmeta_lead( $b, $a, $f, $args );
} endif;

if ( ! function_exists( 'gmeta_author' ) ) : function gmeta_author( $b = '', $a = '', $f = false, $args = array() ) {
	return gEditorialMetaTemplates::gmeta_author( $b, $a, $f, $args );
	//$author = get_the_author();
	//if ( ! empty( $author ) ) echo $b.( $f ? $f( $author ) : $author ).$a;
} endif;

if ( ! function_exists( 'gmeta_thumbnail' ) ) : function gmeta_thumbnail( $place_holder, $b = '', $a = '', $f = false, $args = array() ) { return; } endif;
if ( ! function_exists( 'gmeta_stats' ) ) : function gmeta_stats( $b = '', $a = '', $f = false, $post_id = false ) { return; } endif;

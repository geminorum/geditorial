<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaTemplates extends gEditorialTemplateCore
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

	public static function meta( $field, $before = '', $after = '', $filter = FALSE, $post_id = NULL, $args = array() )
	{
		global $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$meta = gEditorial()->meta->get_postmeta( $post_id, self::sanitize_field( $field ), FALSE );

		if ( FALSE === $meta )
			return FALSE;

		$meta = apply_filters( 'gmeta_meta', $meta, $field );

		if ( $filter && is_callable( $filter ) )
			$meta = call_user_func( $filter, $meta );

		$html = $before.$meta.$after;

		if ( isset( $args['echo'] ) && ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function get_meta( $field, $atts = array() )
	{
		global $post;

		// FIXME: check if $field is an array then use as fallback 
		
		if ( isset( $atts['id'] ) && FALSE === $atts['id'] )
			$atts['id'] = $post->ID;

		$args = self::atts( array(
			'id'  => $post->ID,
			'def' => '',
		), $atts );

		return gEditorial()->meta->get_postmeta( $args['id'], self::sanitize_field( $field ), $args['def'] );
	}

	public static function gmeta_lead( $before = '', $after = '', $filter = FALSE, $args = array() )
	{
		$meta = self::get_meta( 'le', array_merge( array( 'id' => FALSE, 'def' => FALSE ), $args ) );

		if ( FALSE === $meta )
			return FALSE;

		$meta = apply_filters( 'gmeta_lead', do_shortcode( $meta, TRUE ) );

		if ( $filter && is_callable( $filter ) )
			$meta = call_user_func( $filter, $meta );

		$html = $before.$meta.$after;

		if ( isset( $args['echo'] ) && ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function gmeta_author( $before = '', $after = '', $filter = FALSE, $args = array() )
	{
		$meta = self::get_meta( 'as', array_merge( array( 'id' => FALSE, 'def' => FALSE ), $args ) );

		if ( FALSE === $meta )
			return FALSE;

		if ( $filter && is_callable( $filter ) )
			$meta = call_user_func( $filter, $meta );

		$html = $before.$meta.$after;

		if ( isset( $args['echo'] ) && ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function metaLink( $atts = array() )
	{
		global $post;

		$args = self::atts( array(
			'id'            => $post->ID,
			'before'        => isset( $atts['b'] ) ? $atts['b'] : '',
			'after'         => isset( $atts['a'] ) ? $atts['a'] : '',
			'filter'        => isset( $atts['f'] ) ? $atts['f'] : FALSE,
			'echo'          => isset( $atts['e'] ) ? $atts['e'] : TRUE,
			'default'       => isset( $atts['def'] ) ? $atts['def'] : FALSE,
			'title_meta'    => FALSE, // meta key for title of the link
			'title_default' => _x( 'External Source', 'Meta: metaLink default title', GEDITORIAL_TEXTDOMAIN ), // default val for title of the link
			'url_meta'      => 'es', // meta key for URL of the link
			'url_default'   => FALSE, // default val for URL of the link
			'desc'          => NULL, // FALSE to disable
		), $atts );

		$title = $args['title_meta'] ? self::get_meta( $args['title_meta'], array( 'id' => $args['id'], 'def' => $args['title_default'] ) ) : $args['title_default'];
		$url   = $args['url_meta'] ? self::get_meta( $args['url_meta'], array( 'id' => $args['id'], 'def' => $args['url_default'] ) ) : $args['url_default'];

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {
			$html = $args['before'].gEditorialHelper::html( ( $url ? 'a' : 'span' ), array(
				'href'        => $url ? esc_url( $url ) : FALSE,
				'title'       => $args['title_default'], // FIXME: default title attr!
				'rel'         => 'source',
				'data' => array(
					'toggle' => 'tooltip',
				),
			), $title ).$args['after'];
		} else {
			$html = $args['default'];
		}

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function metaLabel( $atts = array() )
	{
		global $post;

		$args = self::atts( array(
			'id'      => $post->ID,
			'before'  => isset( $atts['b'] ) ? $atts['b'] : '',
			'after'   => isset( $atts['a'] ) ? $atts['a'] : '',
			'filter'  => isset( $atts['f'] ) ? $atts['f'] : FALSE,
			'echo'    => isset( $atts['e'] ) ? $atts['e'] : TRUE,
			'default' => isset( $atts['def'] ) ? $atts['def'] : FALSE,
			'img'     => FALSE,
			'link'    => NULL, // FALSE to disable
			'desc'    => NULL, // FALSE to disable
		), $atts );

		$title    = self::get_meta( 'ch', array( 'id' => $args['id'], 'def' => FALSE ) );
		$taxonomy = gEditorial()->get_constant( 'meta', 'ct_tax', 'label' );

		if ( taxonomy_exists( $taxonomy ) ) {
			$term = gEditorialHelper::theTerm( $taxonomy, $args['id'], TRUE );
			if ( $term && ! $title )
				$title = sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy, 'display' );
			if ( $term && is_null( $args['link'] ) )
				$args['link'] = get_term_link( $term, $taxonomy );
			if ( $term && is_null( $args['desc'] ) )
				$args['desc'] = self::termDescription( $term, FALSE );
		} else {
			if ( $title && is_null( $args['link'] ) )
				$args['link'] = self::getSearchLink( $title );
		}

		if ( $args['img'] ) {
			$html = gEditorialHelper::html( 'img', array(
				'src' => esc_url( $args['img'] ),
				'alt' => $title,
			) );
		} else {
			$html = $title;
		}

		if ( ! $html && $args['default'] )
			$html = $args['default'];

		if ( ! $html )
			return FALSE;

		$html = $args['before'].gEditorialHelper::html( 'a', array(
			'href'  => $args['link'],
			'title' => $args['desc'],
		), apply_filters( 'gmeta_label', $html, $args, $title, $term ) ).$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: DEPRICATED / USE: gEditorialMetaTemplates::metaLabel()
	public static function gmeta_label( $b = '', $a = '', $filter = FALSE, $args = array() )
	{
		global $post;

		$id     = isset( $args['id'] ) ? $args['id'] : $post->ID;
		$ct_tax = gEditorial()->get_constant( 'meta', 'ct_tax', 'label' );

		$term  = gEditorialHelper::theTerm( $ct_tax, $id, TRUE );
		$title = self::get_meta( 'ch', array( 'id' => $id, 'def' => FALSE ) );
		$link  = $term ? get_term_link( $term, $ct_tax ) : ( $title ? get_option( 'home' ).'/?s='.urlencode( $title ) : FALSE );
		$desc  = $term ? $term->name.( $term->description ? strip_tags( ' :: '.$term->description ) : '' ) : sprintf( apply_filters( 'gmeta_search_link_title_attr', _x( 'Search %1$s for %2$s', GEDITORIAL_TEXTDOMAIN ) ), get_bloginfo( 'name' ), $title );

		if ( $term || $title ) {
			@$value = $title ? $title : $term->name;

			if ( $filter && is_callable( $filter ) )
				$value = call_user_func( $filter, $value );

			if ( isset( $args['img'] ) && $args['img'] )
				$value = '<img src="'.$args['img'].'" title="'.$value.'" alt="'.$value.'" />';

			$html = $b.'<a href="'.$link.'" title="'.esc_attr( $desc ).'">'.$value.'</a>'.$a;

			if ( isset( $args['echo'] ) && ! $args['echo'] )
				return $html;

			echo $html;
			return TRUE;

		} else if ( isset( $args['def'] ) ) {

			if ( isset( $args['echo'] ) && ! $args['echo'] )
				return $html;

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

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMetaTemplates extends gEditorialTemplateCore
{

	const MODULE = 'meta';

	public static function metaField( $field, $atts = array() )
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$meta = self::getMetaField( $field, $atts );

		if ( ! $atts['echo'] )
			return $meta;

		echo $meta;
		return TRUE;
	}

	public static function metaAuthor( $atts = array() )
	{
		return self::metaField( 'author', $atts );
	}

	public static function metaLead( $atts = array() )
	{
		if ( ! array_key_exists( 'filter', $atts ) )
			$atts['filter'] = array( 'gEditorialHelper', 'prepDescription' );

		return self::metaField( 'lead', $atts );
	}

	// FIXME: DEPRECATED
	// USE: self::sanitizeField()
	public static function sanitize_field( $field )
	{
		if ( is_array( $field ) )
			return $field;

		$fields = array(
			'over-title' => array( 'ot', 'over-title' ),
			'sub-title'  => array( 'st', 'sub-title' ),
			'author'     => array( 'as', 'author' ),
		);

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return array( $field );
	}

	// FIXME: DEPRECATED
	// USE: self::metaField()
	public static function meta( $fields, $before = '', $after = '', $filter = FALSE, $post_id = NULL, $args = array() )
	{
		self::__dev_dep( 'gEditorialMetaTemplates::metaField()' );

		global $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		foreach ( self::sanitize_field( $fields ) as $field ) {

			$meta = gEditorial()->meta->get_postmeta( $post_id, $field, FALSE );

			if ( FALSE === $meta )
				continue; // return FALSE;

			$meta = apply_filters( 'gmeta_meta', $meta, $field );

			if ( $filter && is_callable( $filter ) )
				$meta = call_user_func( $filter, $meta );

			$html = $before.$meta.$after;

			if ( isset( $args['echo'] ) && ! $args['echo'] )
				return $html;

			echo $html;
			return TRUE;
		}

		return FALSE;
	}

	// FIXME: DEPRECATED
	// USE: self::getMetaField()
	public static function get_meta( $fields, $atts = array() )
	{
		self::__dev_dep( 'gEditorialMetaTemplates::getMetaField()' );

		global $post;

		if ( isset( $atts['id'] ) && FALSE === $atts['id'] )
			$atts['id'] = $post->ID;

		$args = self::atts( array(
			'id'  => $post->ID,
			'def' => '',
		), $atts );

		foreach ( self::sanitize_field( $fields ) as $field )
			if ( FALSE !== ( $meta = gEditorial()->meta->get_postmeta( $args['id'], $field, FALSE ) ) )
				return $meta;

		return $args['def'];
	}

	// FIXME: DEPRICATED
	// USE: self::metaLead()
	public static function gmeta_lead( $before = '', $after = '', $filter = FALSE, $args = array() )
	{
		self::__dev_dep( 'gEditorialMetaTemplates::metaLead()' );

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

	// FIXME: DEPRICATED
	// USE: self::metaAuthor()
	public static function gmeta_author( $before = '', $after = '', $filter = FALSE, $args = array() )
	{
		self::__dev_dep( 'gEditorialMetaTemplates::metaAuthor()' );

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

	// FIXME: DROP THIS
	public static function metaLink_OLD( $atts = array(), $module = NULL )
	{
		if ( is_null( $module ) && self::MODULE )
			$module = self::MODULE;

		global $post;

		$args = self::atts( array(
			'id'            => $post->ID,
			'before'        => isset( $atts['b'] ) ? $atts['b'] : '',
			'after'         => isset( $atts['a'] ) ? $atts['a'] : '',
			'filter'        => isset( $atts['f'] ) ? $atts['f'] : FALSE,
			'echo'          => isset( $atts['e'] ) ? $atts['e'] : TRUE,
			'default'       => isset( $atts['def'] ) ? $atts['def'] : FALSE,
			'title_meta'    => FALSE, // meta key for title of the link
			'title_default' => _x( 'External Source', 'Modules: Meta: Meta Link Default Title', GEDITORIAL_TEXTDOMAIN ), // default val for title of the link
			'url_meta'      => 'es', // meta key for URL of the link
			'url_default'   => FALSE, // default val for URL of the link
			'desc'          => NULL, // FALSE to disable
		), $atts );

		$title = $args['title_meta'] ? self::get_meta( $args['title_meta'], array( 'id' => $args['id'], 'def' => $args['title_default'] ) ) : $args['title_default'];
		$url   = $args['url_meta'] ? self::get_meta( $args['url_meta'], array( 'id' => $args['id'], 'def' => $args['url_default'] ) ) : $args['url_default'];

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {
			$html = $args['before'].gEditorialHTML::tag( ( $url ? 'a' : 'span' ), array(
				'href'  => $url ? esc_url( $url ) : FALSE,
				'title' => $args['title_default'], // FIXME: default title attr!
				'rel'   => $url ? 'nofollow' : 'source', // https://support.google.com/webmasters/answer/96569?hl=en
				'data'  => array( 'toggle' => 'tooltip' ),
			), $title ).$args['after'];
		} else {
			$html = $args['default'];
		}

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: DROP THIS
	public static function metaLabel_OLD( $atts = array(), $module = NULL )
	{
		global $post;

		if ( is_null( $module ) && self::MODULE )
			$module = self::MODULE;

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

		$tax   = self::constant( 'ct_tax', 'label' );
		$title = self::get_meta( 'ch', array( 'id' => $args['id'], 'def' => FALSE ) );

		if ( taxonomy_exists( $tax ) ) {
			$term = gEditorialWPTaxonomy::theTerm( $tax, $args['id'], TRUE );
			if ( $term && ! $title )
				$title = sanitize_term_field( 'name', $term->name, $term->term_id, $tax, 'display' );
			if ( $term && is_null( $args['link'] ) )
				$args['link'] = get_term_link( $term, $tax );
			if ( $term && is_null( $args['desc'] ) )
				$args['desc'] = esc_attr( trim( strip_tags( $term->description ) ) );
		} else {
			if ( $title && is_null( $args['link'] ) )
				$args['link'] = gEditorialWordPress::getSearchLink( $title );
		}

		if ( $args['img'] ) {
			$html = gEditorialHTML::tag( 'img', array(
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

		$html = $args['before'].gEditorialHTML::tag( 'a', array(
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
		self::__dev_dep( 'gEditorialMetaTemplates::metaLabel()' );

		global $post;

		$tax = self::constant( 'ct_tax', 'label' );
		$id  = isset( $args['id'] ) ? $args['id'] : $post->ID;

		$term  = gEditorialWPTaxonomy::theTerm( $tax, $id, TRUE );
		$title = self::get_meta( 'ch', array( 'id' => $id, 'def' => FALSE ) );
		$link  = $term ? get_term_link( $term, $tax ) : ( $title ? get_option( 'home' ).'/?s='.urlencode( $title ) : FALSE );
		$desc  = $term ? $term->name.( $term->description ? strip_tags( ' :: '.$term->description ) : '' ) : sprintf( apply_filters( 'gmeta_search_link_title_attr', _x( 'Search %1$s for %2$s', 'Modules: Meta', GEDITORIAL_TEXTDOMAIN ) ), get_bloginfo( 'name' ), $title );

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
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7', 'gEditorialMetaTemplates::metaField()' );
	return gEditorialMetaTemplates::meta( $field, $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'get_gmeta' ) ) : function get_gmeta( $field, $args = array() ){
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7', 'gEditorialMetaTemplates::metaField()' );
	return gEditorialMetaTemplates::get_meta( $field, $args );
} endif;

if ( ! function_exists( 'gmeta_label' ) ) : function gmeta_label( $b = '', $a = '', $f = FALSE, $args = array() ) {
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7', 'gEditorialMetaTemplates::metaLabel()' );
	return gEditorialMetaTemplates::gmeta_label( $b, $a, $f, $args );
} endif;

if ( ! function_exists( 'gmeta_lead' ) ) : function gmeta_lead( $b = '', $a = '', $f = FALSE, $args = array() ) {
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7', 'gEditorialMetaTemplates::metaLead()' );
	return gEditorialMetaTemplates::gmeta_lead( $b, $a, $f, $args );
} endif;

if ( ! function_exists( 'gmeta_author' ) ) : function gmeta_author( $b = '', $a = '', $f = FALSE, $args = array() ) {
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7', 'gEditorialMetaTemplates::metaAuthor()' );
	return gEditorialMetaTemplates::gmeta_author( $b, $a, $f, $args );
	// $author = get_the_author();
	// if ( ! empty( $author ) ) echo $b.( $f ? $f( $author ) : $author ).$a;
} endif;

if ( ! function_exists( 'gmeta_thumbnail' ) ) : function gmeta_thumbnail( $place_holder, $b = '', $a = '', $f = FALSE, $args = array() ) {
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7' );
	return;
} endif;

if ( ! function_exists( 'gmeta_stats' ) ) : function gmeta_stats( $b = '', $a = '', $f = FALSE, $post_id = FALSE ) {
	gEditorialHelper::__dev_func( __FUNCTION__, '3.9.7' );
	return;
} endif;

<?php namespace geminorum\gEditorial\Templates;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;

class Meta extends gEditorial\Template
{

	const MODULE = 'meta';

	public static function metaAuthor( $atts = [] )
	{
		return self::metaField( 'author', $atts );
	}

	public static function metaSource( $atts = [] )
	{
		if ( ! array_key_exists( 'title_field', $atts ) )
			$atts['title_field'] = 'source_title';

		if ( ! array_key_exists( 'url_field', $atts ) )
			$atts['url_field'] = 'source_url';

		return self::metaLink( $atts, 'meta', FALSE );
	}

	public static function metaLead( $atts = [] )
	{
		if ( ! array_key_exists( 'filter', $atts ) )
			$atts['filter'] = [ 'geminorum\\gEditorial\\Helper', 'prepDescription' ];

		return self::metaField( 'lead', $atts );
	}

	public static function metaHighlight( $atts = [] )
	{
		if ( ! array_key_exists( 'filter', $atts ) )
			$atts['filter'] = [ 'geminorum\\gEditorial\\Helper', 'prepDescription' ];

		return self::metaField( 'highlight', $atts );
	}

	// FIXME: DEPRECATED
	public static function sanitize_field( $field )
	{
		if ( is_array( $field ) )
			return $field;

		$fields = [
			'over-title' => [ 'ot', 'over-title' ],
			'sub-title'  => [ 'st', 'sub-title' ],
			'author'     => [ 'as', 'author' ],
		];

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return [ $field ];
	}

	// FIXME: DEPRECATED
	// USE: self::metaField()
	public static function meta( $fields, $before = '', $after = '', $filter = FALSE, $post_id = NULL, $args = [] )
	{
		self::_dev_dep( 'gEditorialMetaTemplates::metaField()' );

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
	public static function get_meta( $fields, $atts = [] )
	{
		self::_dev_dep( 'gEditorialMetaTemplates::getMetaField()' );

		global $post;

		if ( isset( $atts['id'] ) && FALSE === $atts['id'] )
			$atts['id'] = $post->ID;

		$args = self::atts( [
			'id'  => $post->ID,
			'def' => '',
		], $atts );

		foreach ( self::sanitize_field( $fields ) as $field )
			if ( FALSE !== ( $meta = gEditorial()->meta->get_postmeta( $args['id'], $field, FALSE ) ) )
				return $meta;

		return $args['def'];
	}

	// FIXME: DEPRECATED
	// USE: self::metaLead()
	public static function gmeta_lead( $before = '', $after = '', $filter = FALSE, $args = [] )
	{
		self::_dev_dep( 'gEditorialMetaTemplates::metaLead()' );

		$meta = self::get_meta( 'le', array_merge( [ 'id' => FALSE, 'def' => FALSE ], $args ) );

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

	// FIXME: DEPRECATED
	// USE: self::metaAuthor()
	public static function gmeta_author( $before = '', $after = '', $filter = FALSE, $args = [] )
	{
		self::_dev_dep( 'gEditorialMetaTemplates::metaAuthor()' );

		$meta = self::get_meta( 'as', array_merge( [ 'id' => FALSE, 'def' => FALSE ], $args ) );

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
	public static function metaLink_OLD( $atts = [], $module = NULL )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		global $post;

		$args = self::atts( [
			'id'            => $post->ID,
			'before'        => isset( $atts['b'] ) ? $atts['b'] : '',
			'after'         => isset( $atts['a'] ) ? $atts['a'] : '',
			'filter'        => isset( $atts['f'] ) ? $atts['f'] : FALSE,
			'echo'          => isset( $atts['e'] ) ? $atts['e'] : TRUE,
			'default'       => isset( $atts['def'] ) ? $atts['def'] : FALSE,
			'title_meta'    => FALSE, // meta key for title of the link
			'title_default' => _x( 'External Source', 'Title Attr', 'geditorial-meta' ), // default val for title of the link
			'url_meta'      => 'es', // meta key for URL of the link
			'url_default'   => FALSE, // default val for URL of the link
			'desc'          => NULL, // FALSE to disable
		], $atts );

		$title = $args['title_meta'] ? self::get_meta( $args['title_meta'], [ 'id' => $args['id'], 'def' => $args['title_default'] ] ) : $args['title_default'];
		$url   = $args['url_meta'] ? self::get_meta( $args['url_meta'], [ 'id' => $args['id'], 'def' => $args['url_default'] ] ) : $args['url_default'];

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {
			$html = $args['before'].HTML::tag( ( $url ? 'a' : 'span' ), [
				'href'  => $url ? esc_url( $url ) : FALSE,
				'title' => $args['title_default'], // FIXME: default title attr!
				'rel'   => $url ? 'nofollow' : 'source', // https://support.google.com/webmasters/answer/96569?hl=en
				'data'  => [ 'toggle' => 'tooltip' ],
			], $title ).$args['after'];
		} else {
			$html = $args['default'];
		}

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: DROP THIS
	public static function metaLabel_OLD( $atts = [], $module = NULL )
	{
		global $post;

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'      => $post->ID,
			'before'  => isset( $atts['b'] ) ? $atts['b'] : '',
			'after'   => isset( $atts['a'] ) ? $atts['a'] : '',
			'filter'  => isset( $atts['f'] ) ? $atts['f'] : FALSE,
			'echo'    => isset( $atts['e'] ) ? $atts['e'] : TRUE,
			'default' => isset( $atts['def'] ) ? $atts['def'] : FALSE,
			'img'     => FALSE,
			'link'    => NULL, // FALSE to disable
			'desc'    => NULL, // FALSE to disable
		], $atts );

		$tax   = self::constant( 'ct_tax', 'label' );
		$title = self::get_meta( 'ch', [ 'id' => $args['id'], 'def' => FALSE ] );

		if ( taxonomy_exists( $tax ) ) {
			$term = Taxonomy::theTerm( $tax, $args['id'], TRUE );
			if ( $term && ! $title )
				$title = sanitize_term_field( 'name', $term->name, $term->term_id, $tax, 'display' );
			if ( $term && is_null( $args['link'] ) )
				$args['link'] = get_term_link( $term, $tax );
			if ( $term && is_null( $args['desc'] ) )
				$args['desc'] = trim( strip_tags( $term->description ) );
		} else {
			if ( $title && is_null( $args['link'] ) )
				$args['link'] = WordPress::getSearchLink( $title );
		}

		if ( $args['img'] ) {
			$html = HTML::tag( 'img', [
				'src' => esc_url( $args['img'] ),
				'alt' => $title,
			] );
		} else {
			$html = $title;
		}

		if ( ! $html && $args['default'] )
			$html = $args['default'];

		if ( ! $html )
			return FALSE;

		$html = $args['before'].HTML::tag( 'a', [
			'href'  => $args['link'],
			'title' => $args['desc'],
		], apply_filters( 'gmeta_label', $html, $args, $title, $term ) ).$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: DEPRECATED / USE: gEditorialMetaTemplates::metaLabel()
	public static function gmeta_label( $b = '', $a = '', $filter = FALSE, $args = [] )
	{
		self::_dev_dep( 'gEditorialMetaTemplates::metaLabel()' );

		global $post;

		$tax = self::constant( 'ct_tax', 'label' );
		$id  = isset( $args['id'] ) ? $args['id'] : $post->ID;

		$term  = Taxonomy::theTerm( $tax, $id, TRUE );
		$title = self::get_meta( 'ch', [ 'id' => $id, 'def' => FALSE ] );
		$link  = $term ? get_term_link( $term, $tax ) : ( $title ? get_option( 'home' ).'/?s='.urlencode( $title ) : FALSE );
		/* translators: %1$s: site name, %2$s: search query */
		$desc  = $term ? $term->name.( $term->description ? strip_tags( ' :: '.$term->description ) : '' ) : sprintf( apply_filters( 'gmeta_search_link_title_attr', _x( 'Search %1$s for %2$s', 'Title Attr', 'geditorial-meta' ) ), get_bloginfo( 'name' ), $title );

		if ( $term || $title ) {
			@$value = $title ? $title : $term->name;

			if ( $filter && is_callable( $filter ) )
				$value = call_user_func( $filter, $value );

			if ( isset( $args['img'] ) && $args['img'] )
				$value = '<img src="'.$args['img'].'" title="'.$value.'" alt="'.$value.'" />';

			$html = $b.'<a href="'.$link.'" title="'.HTML::escape( $desc ).'">'.$value.'</a>'.$a;

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

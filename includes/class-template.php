<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTemplateCore extends gEditorialBaseCore
{

	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		if ( self::MODULE && gEditorial()->enabled( self::MODULE ) )
			return gEditorial()->{self::MODULE}->constant( $key, $default );

		return $default;
	}

	protected static function post_types( $post_types = NULL )
	{
		if ( self::MODULE && gEditorial()->enabled( self::MODULE ) )
			return gEditorial()->{self::MODULE}->post_types( $post_types );

		return array();
	}

	// FIXME: DRAFT
	public static function parseMarkDown( $content )
	{
		if ( ! class_exists( 'ParsedownExtra' ) )
			return $content;

		$parsedown = new \ParsedownExtra();
		return $parsedown->text( $content );
	}

	// EDITED: 10/1/2016, 5:32:32 PM
	public static function shortcodeWrap( $html, $suffix = FALSE, $args = array(), $block = TRUE )
	{
		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] ) ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = array( 'geditorial-wrap-shortcode' );

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( $after )
			return $before.gEditorialHTML::tag( $block ? 'div' : 'span', array( 'class' => $classes ), $html ).$after;

		return gEditorialHTML::tag( $block ? 'div' : 'span', array( 'class' => $classes ), $before.$html );
	}

	// EDITED: 5/5/2016, 12:05:15 AM
	public static function shortcodeTermTitle( $atts, $term = FALSE )
	{
		$args = self::atts( array(
			'title'        => NULL, // FALSE to disable
			'title_link'   => NULL, // FALSE to disable
			'title_title'  => '',
			'title_tag'    => 'h3',
			'title_anchor' => 'term-%2$s',
		), $atts );

		if ( is_null( $args['title'] ) )
			$args['title'] = $term ? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) : FALSE;

		if ( $args['title'] ) {
			if ( is_null( $args['title_link'] ) && $term )
				$args['title'] = gEditorialHTML::tag( 'a', array(
					'href'  => get_term_link( $term, $term->taxonomy ),
					'title' => $args['title_title'],
				), $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = gEditorialHTML::tag( 'a', array(
					'href'  => $args['title_link'],
					'title' => $args['title_title'],
				), $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = gEditorialHTML::tag( $args['title_tag'], array(
				'id'    => $term ? sprintf( $args['title_anchor'], $term->term_id, $term->slug ) : FALSE,
				'class' => '-title',
			), $args['title'] )."\n";

		return $args['title'];
	}

	// EDITED: 5/5/2016, 12:05:29 AM
	public static function shortcodeTermLink( $atts, $term, $before = '', $after = '' )
	{
		$args = self::atts( array(
			'li_link'       => TRUE,
			'li_before'     => '',
			'li_after'      => '',
			'li_title'      => '', // use %s for term title
			'li_anchor'     => 'term-%1$s',
		), $atts );

		$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

		if ( $term->count && $args['li_link'] )
			return $args['li_before'].gEditorialHTML::tag( 'a', array(
				'href'  => get_term_link( $term ),
				'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			), $before.$title.$after ).$args['li_after']."\n";

		else
			return $args['li_before'].gEditorialHTML::tag( 'span', array(
				'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
				'class' => $args['li_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			), $before.$title.$after ).$args['li_after']."\n";
	}

	// EDITED: 5/5/2016, 12:05:36 AM
	public static function shortcodePostLink( $atts, $post, $before = '', $after = '' )
	{
		$args = self::atts( array(
			'li_link'       => TRUE,
			'li_before'     => '',
			'li_after'      => '',
			'li_title'      => '', // use %s for post title
			'li_anchor'     => 'post-%1$s',
		), $atts );

		$title = get_the_title( $post->ID );

		if ( 'publish' == $post->post_status && $args['li_link'] )
			return $args['li_before'].gEditorialHTML::tag( 'a', array(
				'href'  => get_permalink( $post->ID ),
				'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
				'class' => '-link -posttype-'.$post->post_type,
			), $before.$title.$after ).$args['li_after']."\n";

		else
			return $args['li_before'].gEditorialHTML::tag( 'span', array(
				'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
				'class' => $args['li_link'] ? '-no-link -future -posttype-'.$post->post_type : FALSE,
			), $before.$title.$after ).$args['li_after']."\n";
	}
}

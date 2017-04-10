<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialShortCode extends gEditorialBaseCore
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	public static function wrap( $html, $suffix = FALSE, $args = [], $block = TRUE )
	{
		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] ) ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ self::BASE.'-wrap-shortcode' ];

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( $after )
			return $before.gEditorialHTML::tag( $block ? 'div' : 'span', [ 'class' => $classes ], $html ).$after;

		return gEditorialHTML::tag( $block ? 'div' : 'span', [ 'class' => $classes ], $before.$html );
	}

	public static function termTitle( $atts, $term = FALSE )
	{
		$args = self::atts( [
			'title'          => NULL, // FALSE to disable
			'title_link'     => NULL, // FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => 'term-%2$s',
			'title_class'    => '-title',
		], $atts );

		if ( is_null( $args['title'] ) )
			$args['title'] = $term ? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) : FALSE;

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $term, $atts ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], $title );

		else
			$attr = FALSE;

		if ( $args['title'] ) {
			if ( is_null( $args['title_link'] ) && $term )
				$args['title'] = gEditorialHTML::tag( 'a', [
					'href'  => get_term_link( $term, $term->taxonomy ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = gEditorialHTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = gEditorialHTML::tag( $args['title_tag'], [
				'id'    => $term ? sprintf( $args['title_anchor'], $term->term_id, $term->slug ) : FALSE,
				'class' => $args['title_class'],
			], $args['title'] )."\n";

		return $args['title'];
	}

	public static function termLink( $atts, $term, $before = '', $after = '' )
	{
		$args = self::atts( [
			'li_link'   => TRUE,
			'li_before' => '',
			'li_after'  => '',
			'li_title'  => '', // use %s for term title
		], $atts );

		$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

		if ( $term->count && $args['li_link'] )
			return $args['li_before'].gEditorialHTML::tag( 'a', [
				'href'  => get_term_link( $term ),
				'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			], $before.$title.$after ).$args['li_after']."\n";

		else
			return $args['li_before'].gEditorialHTML::tag( 'span', [
				'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
				'class' => $args['li_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			], $before.$title.$after ).$args['li_after']."\n";
	}

	public static function termWrap( $html, $atts, $term )
	{
		$args = self::atts( [
			'li_tag'    => 'li',
			'li_class'  => '-item',
			'li_anchor' => $term->taxonomy.'-%2$s',
		], $atts );

		return gEditorialHTML::tag( $args['li_tag'], [
			'id'    => sprintf( $args['li_anchor'], $term->term_id, $term->slug ),
			'class' => $args['li_class'],
		], $html );
	}

	public static function postTitle( $atts, $post = NULL )
	{
		$args = self::atts( [
			'title'          => NULL, // FALSE to disable
			'title_link'     => NULL, // FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => 'post-%2$s',
			'title_class'    => '-title',
		], $atts );

		if ( is_null( $args['title'] ) )
			$args['title'] = $post ? gEditorialHelper::getPostTitle( $post, FALSE ) : FALSE;

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $post, $atts ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $args['title'] ? $args['title'] : '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {
			if ( is_null( $args['title_link'] ) && $post )
				$args['title'] = gEditorialHTML::tag( 'a', [
					'href'  => get_permalink( $post ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = gEditorialHTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = gEditorialHTML::tag( $args['title_tag'], [
				'id'    => $post ? sprintf( $args['title_anchor'], $post->ID, $post->post_name ) : FALSE,
				'class' => $args['title_class'],
			], $args['title'] )."\n";

		return $args['title'];
	}

	public static function postOrder( $atts, $post = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return '';

		$args = self::atts( [
			'order_before'  => FALSE,
			'order_zeroise' => FALSE,
			'order_sep'     => ' &ndash; ',
		], $atts );

		if ( ! $args['order_before'] )
			return '';

		$order = $args['order_zeroise'] ? gEditorialNumber::zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order;

		return gEditorialNumber::format( $order ).( $args['order_sep'] ? $args['order_sep'] : '' );
	}

	public static function postWrap( $html, $atts, $post = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return $html;

		$args = self::atts( [
			'li_tag'    => 'li',
			'li_class'  => '-item',
			'li_anchor' => $post->post_type.'-%2$s',
		], $atts );

		return gEditorialHTML::tag( $args['li_tag'], [
			'id'    => sprintf( $args['li_anchor'], $post->ID, $post->post_name ),
			'class' => $args['li_class'],
		], $html );
	}

	public static function postLink( $atts, $post = NULL, $before = '', $after = '' )
	{
		if ( ! $post = get_post( $post ) )
			return '';

		$args = self::atts( [
			'li_link'     => TRUE,
			'li_before'   => '',
			'li_after'    => '',
			'li_title'    => '', // use %s for post title
			'li_title_cb' => FALSE, // callback for title attr
		], $atts );

		$title = gEditorialHelper::getPostTitle( $post );

		if ( $args['li_title_cb'] && is_callable( $args['li_title_cb'] ) )
			$attr = call_user_func_array( $args['li_title_cb'], [ $post, $atts ] );

		else if ( $args['li_title'] )
			$attr = sprintf( $args['li_title'], $title );

		else
			$attr = FALSE;

		if ( 'publish' == $post->post_status && $args['li_link'] )
			return $args['li_before'].gEditorialHTML::tag( 'a', [
				'href'  => get_permalink( $post ),
				'title' => $attr,
				'class' => '-link -posttype-'.$post->post_type,
			], $before.$title.$after ).$args['li_after']."\n";

		else
			return $args['li_before'].gEditorialHTML::tag( 'span', [
				'title' => $attr,
				'class' => $args['li_link'] ? '-no-link -future -posttype-'.$post->post_type : FALSE,
			], $before.$title.$after ).$args['li_after']."\n";
	}

	public static function getTermPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'slug'           => '',
			'id'             => '',
			'title'          => NULL, // FALSE to disable
			'title_link'     => NULL, // FALSE to disable
			'title_title'    => _x( 'Permanent link', 'ShortCode Helper: Term Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => $taxonomy.'-%2$s',
			'title_class'    => '-title',
			'list_tag'       => 'ul',
			'list_class'     => '-list',
			'limit'          => -1,
			'future'         => 'on',
			'li_tag'         => 'li',
			'li_link'        => TRUE,
			'li_before'      => '',
			'li_after'       => '',
			'li_title'       => '', // use %s for post title
			'li_title_cb'    => FALSE, // callback for title attr
			'li_anchor'      => $posttype.'-%2$s',
			'li_class'       => '-item',
			'order_before'   => FALSE,
			'order_zeroise'  => FALSE,
			'order_sep'      => ' &ndash; ',
			'posttypes'      => array( $posttype ),
			'orderby'        => 'date',
			'order'          => 'ASC',
			'cover'          => FALSE, // must have thumbnail
			'cb'             => FALSE,
			'context'        => NULL,
			'wrap'           => TRUE,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = $tax_query = $meta_query = '';

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $posttype );

		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] ) {

			if ( ! $term = get_term_by( 'id', $args['id'], $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $args['slug'] ) {

			if ( ! $term = get_term_by( 'slug', $args['slug'], $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_singular( $posttype ) ) {

			if ( ! $terms = get_the_terms( NULL, $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $terms, 'term_id' ),
			] ];
		}

		if ( $args['cover'] )
			$meta_query = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$args['title'] = self::termTitle( $args, $term );

		if ( 'on' == $args['future'] )
			$post_status = [ 'publish', 'future', 'draft' ];
		else
			$post_status = [ 'publish' ];

		$query_args = array(
			'tax_query'        => $tax_query,
			'meta_query'       => $meta_query,
			'posts_per_page'   => $args['limit'],
			'orderby'          => $args['orderby'] == 'page' ? 'date' : $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $args['posttypes'],
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new \WP_Query;
		$posts = $query->query( $query_args );

		if ( ! count( $posts ) )
			return $content;

		foreach ( $posts as $post ) {

			setup_postdata( $post );

			if ( $args['cb'] ) {
				$html .= call_user_func_array( $args['cb'], [ $post, $args ] );

			} else {

				$order = self::postOrder( $args, $post );
				$item  = self::postLink( $args, $post, $order );

				// TODO: add excerpt/content of the entry
				// TODO: add show/more js like series

				$html .= self::postWrap( $item, $args, $post );
			}
		}

		$html = gEditorialHTML::tag( $args['list_tag'], [
			'class' => $args['list_class'],
		], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}

	public static function getAssocPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'slug'           => '',
			'id'             => '',
			'title'          => NULL, // FALSE to disable
			'title_link'     => NULL, // FALSE to disable
			'title_title'    => _x( 'Permanent link', 'ShortCode Helper: Term Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => $taxonomy.'-%2$s',
			'title_class'    => '-title',
			'list_tag'       => 'ul',
			'list_class'     => '-list',
			'limit'          => -1,
			'future'         => 'on',
			'li_tag'         => 'li',
			'li_link'        => TRUE,
			'li_before'      => '',
			'li_after'       => '',
			'li_title'       => '', // use %s for post title
			'li_title_cb'    => FALSE, // callback for title attr
			'li_anchor'      => $posttype.'-%2$s',
			'li_class'       => '-item',
			'order_before'   => FALSE,
			'order_zeroise'  => FALSE,
			'order_sep'      => ' &ndash; ',
			'posttypes'      => array( 'post' ),
			'orderby'        => 'date',
			'order'          => 'ASC',
			'cover'          => FALSE, // must have thumbnail
			'cb'             => FALSE,
			'context'        => NULL,
			'wrap'           => TRUE,
		], $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = $tax_query = $meta_query = '';
		$post = get_post();

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $posttype );

		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] ) {

			if ( ! $term = get_term_by( 'id', $args['id'], $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $args['slug'] ) {

			if ( ! $term = get_term_by( 'slug', $args['slug'], $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_singular( $posttype ) ) {

			if ( ! $post )
				return $content;

			if ( $term_id = get_post_meta( $post->ID, '_'.$posttype.'_term_id', TRUE ) )
				$term = get_term_by( 'id', intval( $term_id ), $taxonomy );

			else
				$term = get_term_by( 'slug', $post->post_name, $taxonomy );

			if ( ! $term )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else {

			if ( ! $terms = get_the_terms( NULL, $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $terms, 'term_id' ),
			] ];
		}

		if ( $args['cover'] )
			$meta_query = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$args['title'] = self::postTitle( $args, $post );

		if ( 'on' == $args['future'] )
			$post_status = [ 'publish', 'future', 'draft' ];
		else
			$post_status = [ 'publish' ];

		$query_args = array(
			'tax_query'        => $tax_query,
			'meta_query'       => $meta_query,
			'posts_per_page'   => $args['limit'],
			'orderby'          => $args['orderby'] == 'page' ? 'date' : $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $args['posttypes'],
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new \WP_Query;
		$posts = $query->query( $query_args );

		if ( ! count( $posts ) )
			return $content;

		if ( $args['orderby'] == 'page' && count( $posts ) > 1  )
			$posts = gEditorialTemplateCore::reorderPosts( $posts );

		foreach ( $posts as $post ) {

			setup_postdata( $post );

			if ( $args['cb'] ) {
				$html .= call_user_func_array( $args['cb'], [ $post, $args ] );

			} else {

				$order = self::postOrder( $args, $post );
				$item  = self::postLink( $args, $post, $order );

				// TODO: add excerpt/content of the entry
				// TODO: add show/more js like series

				$html .= self::postWrap( $item, $args, $post );
			}
		}

		$html = gEditorialHTML::tag( $args['list_tag'], [
			'class' => $args['list_class'],
		], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}
}

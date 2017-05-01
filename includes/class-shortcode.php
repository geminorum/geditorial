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

		if ( ! empty( $args['class'] ) )
			$classes[] = $args['class'];

		if ( $after )
			return $before.gEditorialHTML::tag( $block ? 'div' : 'span', [ 'class' => $classes ], $html ).$after;

		return gEditorialHTML::tag( $block ? 'div' : 'span', [ 'class' => $classes ], $before.$html );
	}

	// term as title of the list
	public static function termTitle( $atts, $term_or_id, $taxonomy = 'category' )
	{
		if ( is_array( $term_or_id ) )
			$term_or_id = $term_or_id[0];

		if ( ! $term = gEditorialWPTaxonomy::getTerm( $term_or_id, $taxonomy ) )
			return '';

		$args = self::atts( [
			'title'          => NULL, // '<a href="%2$s">%1$s</a>', // FALSE to disable
			'title_link'     => NULL, // FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => 'term-%2$s',
			'title_class'    => '-title',
			'title_after'    => '', // '<div class="-desc">%3$s</div>',
		], $atts );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $term, $atts ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $args['title'] ? $args['title'] : '' ) );

		else
			$attr = FALSE;

		if ( is_null( $args['title'] ) ) {
			$args['title'] = $term ? sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) : FALSE;

		} else if ( $args['title'] && $term && gEditorialCoreText::has( $args['title'], '%' ) ) {

			$args['title'] = sprintf( $args['title'],
				sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
				get_term_link( $term, $term->taxonomy ),
				esc_attr( trim( strip_tags( $term->description ) ) ),
				( $attr ? $attr : '' )
			);

			$args['title_link'] = FALSE;
		}

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

		if ( $args['title_after'] ) {

			if ( $term && gEditorialCoreText::has( $args['title_after'], '%' ) )
				$args['title_after'] = sprintf( $args['title_after'],
					sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
					get_term_link( $term, $term->taxonomy ),
					gEditorialHelper::prepDescription( $term->description )
				);

			return $args['title'].$args['title_after'];
		}

		return $args['title'];
	}

	// term as item in the list
	public static function termItem( $atts, $term, $before = '', $after = '' )
	{
		$args = self::atts( [
			'item_link'     => TRUE,
			'item_title'    => '', // use %s for post title
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => $term->taxonomy.'-%2$s',
			'item_class'    => '-item',
			'item_after'    => '',
			'item_after_cb' => FALSE,
		], $atts );

		$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

		if ( $term->count && $args['item_link'] )
			$item = gEditorialHTML::tag( 'a', [
				'href'  => get_term_link( $term ),
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $title ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			], $title );

		else
			$item = gEditorialHTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $title ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			], $title );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item .= call_user_func_array( $args['item_after_cb'], [ $term, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( gEditorialCoreText::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ),
					get_term_link( $term, $term->taxonomy ),
					gEditorialHelper::prepDescription( $term->description )
				);

			$item .= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$title.$after;

		return gEditorialHTML::tag( $args['item_tag'], [
			'id'    => sprintf( $args['item_anchor'], $term->term_id, $term->slug ),
			'class' => $args['item_class'],
		], $before.$title.$after );
	}

	// post as title of the list
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

	// post as item in the list
	public static function postItem( $atts, $post = NULL, $before = '', $after = '' )
	{
		if ( ! $post = get_post( $post ) )
			return '';

		$args = self::atts( [
			'item_link'     => TRUE,
			'item_title'    => '', // use %s for post title
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => $post->post_type.'-%2$s',
			'item_class'    => '-item',
			'item_after'    => '',
			'item_after_cb' => FALSE,
			'order_before'  => FALSE,
			'order_zeroise' => FALSE,
			'order_sep'     => ' &ndash; ',
		], $atts );

		if ( $item = gEditorialHelper::getPostTitle( $post, FALSE ) ) {

			if ( $args['item_title_cb'] && is_callable( $args['item_title_cb'] ) )
				$attr = call_user_func_array( $args['item_title_cb'], [ $post, $args, $item ] );

			else if ( $args['item_title'] )
				$attr = sprintf( $args['item_title'], $item );

			else
				$attr = FALSE;

			if ( 'publish' == $post->post_status && $args['item_link'] )
				$item = gEditorialHTML::tag( 'a', [
					'href'  => get_permalink( $post ),
					'title' => $attr,
					'class' => '-link -posttype-'.$post->post_type,
				], $item );

			else
				$item = gEditorialHTML::tag( 'span', [
					'title' => $attr,
					'class' => $args['item_link'] ? '-no-link -future -posttype-'.$post->post_type : FALSE,
				], $item );


			if ( $args['order_before'] ) {
				$order = $args['order_zeroise'] ? gEditorialNumber::zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order;
				$item = gEditorialNumber::format( $order ).( $args['order_sep'] ? $args['order_sep'] : '' ).$item;
			}
		}

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item .= call_user_func_array( $args['item_after_cb'], [ $post, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( gEditorialCoreText::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					apply_filters( 'the_title', $post->post_title, $post->ID ),
					get_permalink( $post ),
					gEditorialHelper::prepDescription( $post->post_excerpt )
					// FIXME: add post_content
				);

			$item .= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return gEditorialHTML::tag( $args['item_tag'], [
			'id'    => sprintf( $args['item_anchor'], $post->ID, $post->post_name ),
			'class' => $args['item_class'],
		], $before.$item.$after );
	}

	public static function getDefaults( $posttype, $taxonomy, $posttypes = [ 'post' ] )
	{
		return [
			'id'             => '',
			'slug'           => '',
			'title'          => NULL, // FALSE to disable
			'title_link'     => NULL, // FALSE to disable
			'title_title'    => _x( 'Permanent link', 'ShortCode Helper: Term Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => $taxonomy.'-%2$s',
			'title_class'    => '-title',
			'item_cb'        => FALSE,
			'item_link'      => TRUE,
			'item_title'     => '', // use %s for post title
			'item_title_cb'  => FALSE,
			'item_tag'       => 'li',
			'item_anchor'    => $posttype.'-%2$s',
			'item_class'     => '-item',
			'item_after'     => '',
			'item_after_cb'  => FALSE,
			'order_before'   => FALSE,
			'order_zeroise'  => FALSE,
			'order_sep'      => ' &ndash; ',
			'list_tag'       => 'ul',
			'list_class'     => '-list',
			'cover'          => FALSE, // must have thumbnail
			'posttypes'      => $posttypes,
			'future'         => 'on',
			'orderby'        => 'date',
			'order'          => 'ASC',
			'order_cb'       => FALSE, // NULL for default order ( by meta, like mag )
			'limit'          => -1,
			'field_module'   => 'meta', // getting meta field from
			'context'        => NULL,
			'wrap'           => TRUE,
			'before'         => '', // html after wrap
			'after'          => '', // html before wrap
			'class'          => '', // wrap css class
		];
	}

	public static function getTermPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( self::getDefaults( $posttype, $taxonomy, [ $posttype ] ), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = $tax_query = $meta_query = '';

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $posttype );

		if ( FALSE !== $cache )
			return $cache;

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		if ( 'all' == $args['id'] ) {

			// do nothing, will collect all posttype: e.g. all entrys of all sections

		} else if ( $args['id'] ) {

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

		} else if ( is_tax( $taxonomy ) ) {

			if ( ! $term = get_queried_object() )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_singular( $posttype ) ) {

			if ( ! $term = get_the_terms( NULL, $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $term, 'term_id' ),
			] ];

		} else {
			return $content;
		}

		if ( $args['cover'] )
			$meta_query = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$args['title'] = self::termTitle( $args, $term, $taxonomy );

		if ( 'on' == $args['future'] )
			$post_status = [ 'publish', 'future', 'draft' ];
		else
			$post_status = [ 'publish' ];

		$query_args = array(
			'tax_query'        => $tax_query,
			'meta_query'       => $meta_query,
			'posts_per_page'   => $args['limit'],
			'orderby'          => $args['orderby'] == 'order' ? 'date' : $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $args['posttypes'],
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new \WP_Query;
		$posts = $query->query( $query_args );
		$count = count( $posts );

		if ( ! $count )
			return $content;

		if ( 1 == $count && is_singular( $args['posttypes'] ) )
			return $content;

		if ( $args['orderby'] == 'order' ) {

			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$posts = call_user_func_array( $args['order_cb'], [ $posts, $args, $term ] );

			else if ( is_null( $args['order_cb'] ) && $count > 1 )
				$posts = gEditorialTemplateCore::reorderPosts( $posts, $args['field_module'] );
		}

		foreach ( $posts as $post ) {

			// setup_postdata( $post );

			if ( $args['item_cb'] )
				$html .= call_user_func_array( $args['item_cb'], [ $post, $args, $term ] );

			else
				$html .= self::postItem( $args, $post );
		}

		$html = gEditorialHTML::tag( $args['list_tag'], [
			'class' => $args['list_class'],
		], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}

	public static function getAssocPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( self::getDefaults( $posttype, $taxonomy ), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = $tax_query = $meta_query = '';
		$post = get_post();

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $posttype );

		if ( FALSE !== $cache )
			return $cache;

		// FIXME: back comp / DROP THIS
		if ( 'page' == $args['orderby'] )
			$args['orderby'] = 'order';

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		if ( 'all' == $args['id'] ) {

			// do nothing, will collect all posttype: e.g. all entrys of all sections

		} else if ( $args['id'] ) {

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

		} else if ( is_tax( $taxonomy ) ) {

			if ( ! $term = get_queried_object() )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $post && is_singular( $posttype ) ) {

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

			if ( ! $term = get_the_terms( NULL, $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $term, 'term_id' ),
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
			'orderby'          => $args['orderby'] == 'order' ? 'date' : $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $args['posttypes'],
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new \WP_Query;
		$posts = $query->query( $query_args );
		$count = count( $posts );

		if ( ! $count )
			return $content;

		if ( 1 == $count && is_singular( $args['posttypes'] ) )
			return $content;

		if ( $args['orderby'] == 'order' ) {

			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$posts = call_user_func_array( $args['order_cb'], [ $posts, $args, $term ] );

			else if ( is_null( $args['order_cb'] ) && $count > 1 )
				$posts = gEditorialTemplateCore::reorderPosts( $posts, $args['field_module'] );
		}

		foreach ( $posts as $post ) {

			// setup_postdata( $post );

			if ( $args['item_cb'] )
				$html .= call_user_func_array( $args['item_cb'], [ $post, $args, $term ] );

			else
				$html .= self::postItem( $args, $post );
		}

		$html = gEditorialHTML::tag( $args['list_tag'], [
			'class' => $args['list_class'],
		], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}
}

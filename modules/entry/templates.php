<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEntryTemplates extends gEditorialTemplateCore
{

	public static function section_shortcode( $atts, $content = NULL, $tag = '' )
	{
		global $post;

		$cpt = gEditorial()->get_constant( 'entry', 'entry_cpt', 'entry' );
		$tax = gEditorial()->get_constant( 'entry', 'section_tax', 'entry_section' );
		$tag = gEditorial()->get_constant( 'entry', 'section_shortcode', $tag );

		$args = shortcode_atts( array(
			'slug'          => '',
			'id'            => '',
			'title'         => NULL, // FALSE to disable
			'title_link'    => NULL, // FALSE to disable
			'title_title'   => '',
			'title_tag'    => 'h3',
			'title_anchor'  => 'section-',
			'list'          => 'ul',
			'limit'         => -1,
			'future'        => 'on',
			'li_link'       => TRUE,
			'li_before'     => '',
			'li_title'      => '', // use %s for post title
			'li_anchor'     => 'entry-',
			'order_before'  => FALSE,
			'order_sep'     => ' - ',
			'order_zeroise' => FALSE,
			'orderby'       => 'date',
			'order'         => 'ASC',
			'cb'            => FALSE,
			'context'       => NULL,
			'wrap'          => TRUE,
		), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$error = $term = FALSE;
		$html = $tax_query = '';

		$key = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $cpt );
		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] && $args['id'] ) {

			if ( $term = get_term_by( 'id', $args['id'], $tax ) )
				$tax_query = array( array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => array( $args['id'] ),
				) );

			else
				$error = TRUE;

		} else if ( $args['slug'] && $args['slug'] ) {

			if ( $term = get_term_by( 'slug', $args['slug'], $tax ) )
				$tax_query = array( array(
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => array( $args['slug'] ),
				) );

			else
				$error = TRUE;

		} else if ( $post->post_type == $cpt ) {

			$terms = get_the_terms( $post->ID, $tax );

			if ( $terms && ! is_wp_error( $terms ) ) {

				foreach ( $terms as $term )
					$term_list[] = $term->term_id;

				$tax_query = array( array(
					'taxonomy' => $tax,
					'field'    => 'term_id',
					'terms'    => $term_list,
				) );

			} else {
				$error = TRUE;
			}
		}

		if ( $error )
			return $content;

		$args['title'] = self::shortcodeTermTitle( $args, $term );

		if ( 'on' == $args['future'] )
			$post_status = array( 'publish', 'future', 'draft' );
		else
			$post_status = array( 'publish' );

		$query_args = array(
			'tax_query'        => $tax_query,
			'posts_per_page'   => $args['limit'],
			'orderby'          => $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $cpt,
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		);

		$query = new WP_Query;
		$posts = $query->query( $query_args );

		if ( count( $posts ) ) {
			foreach ( $posts as $post ) {

				$list  = '';
				setup_postdata( $post );

				if ( $args['cb'] ) {
					$list = call_user_func_array( $args['cb'], array( $post, $args ) );

				} else {

					$title = get_the_title( $post->ID );
					$order = $args['order_before'] ? number_format_i18n( $args['order_zeroise'] ? zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order ).$args['order_sep'] : '';

					if ( 'publish' == $post->post_status && $args['li_link'] )
						$list = $args['li_before'].self::html( 'a', array(
							'href'  => get_permalink( $post->ID ),
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => '-link',
						), $order.$title );

					else
						$list = $args['li_before'].self::html( 'span', array(
							'title' => $args['li_title'] ? sprintf( $args['li_title'], $title ) : FALSE,
							'class' => $args['li_link'] ? '-future' : FALSE,
						), $order.$title );

					// TODO: add excerpt/content of the entry
					// TODO: add show/more js like series
				}

				$html .= self::html( 'li', array(
					'id'    => $args['li_anchor'].$post->ID,
					'class' => '-item',
				), $list );
			}

			$html = self::html( $args['list'], array( 'class' => '-list' ), $html );

			if ( $args['title'] )
				$html = $args['title'].$html;

			$html = self::shortcodeWrap( $html, $tag, $args );

			wp_reset_postdata();
			wp_cache_set( $key, $html, $cpt );

			return $html;
		}

		return $content;
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSeriesTemplates extends gEditorialTemplateCore
{

	public static function shortcode_multiple_series( $atts, $content = NULL, $tag = '' )
	{
		global $gEditorial, $post;

		$shortcode  = $gEditorial->get_module_constant( 'series', 'series_shortcode', 'series' );
		$multiple   = $gEditorial->get_module_constant( 'series', 'multiple_series_shortcode', 'multiple_series' );
		$series_tax = $gEditorial->get_module_constant( 'series', 'series_tax', 'series' );

		$args = shortcode_atts( array(
			'ids'       => array(),
			'title'     => '',
			'title_tag' => 'h3',
			'class'     => '',
			'order'     => 'ASC',
			'orderby'   => 'term_order, name',
			'exclude'   => true, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
			'args'      => array(),
		), $atts, $multiple );

		if ( false === $args['context'] )
			return NULL;

		if ( empty( $args['ids'] ) || ! count( $args['ids'] ) ) {
			$terms = wp_get_object_terms( (int) $post->ID, $series_tax, array(
				'order'   => $args['order'],
				'orderby' => $args['orderby'],
				'fields'  => 'ids',
			) );
			$args['ids'] = is_wp_error( $terms ) ? array() : $terms;
		}

		$output = '';
		foreach ( $args['ids'] as $id )
			$output .= self::shortcode_series( array_merge( array(
				'id' => $id,
				'title_tag' => 'h4',
			), $args['args'] ), NULL, $shortcode );

		if ( ! empty( $output ) ) {
			if ( $args['title'] )
				$output = '<'.$args['title_tag'].' class="post-series-wrap-title">'.$args['title'].'</'.$args['title_tag'].'>'.$output;
			if ( ! is_null( $args['context'] ) )
				$output = '<div class="multiple-series-'.sanitize_html_class( $args['context'], 'general' ).'">'.$output.'</div>';
			return $args['before'].$output.$args['after'];
		}

		return NULL;
	}

	// [series]
	// [series slug="wordpress-themes"]
	// [series id="146"]
	// [series title="More WordPress Theme Lists" title_wrap="h4" limit="5" list="ol" future="off" single="off"]
	public static function shortcode_series( $atts, $content = NULL, $tag = '' )
	{
		global $gEditorial, $post;
		$error = FALSE;

		$shortcode  = $gEditorial->get_module_constant( 'series', 'series_shortcode', 'series' );
		$series_tax = $gEditorial->get_module_constant( 'series', 'series_tax', 'series' );

		$args = shortcode_atts( array(
			'slug'      => '',
			'id'        => '',
			'class'     => '', // css class to the wrapper
			'title'     => '<a href="%2$s" title="%3$s">%1$s</a>',
			'title_tag' => 'h3',
			'list'      => 'ul',
			'limit'     => -1,
			'hide'      => -1, // more than this will be hided
			'future'    => TRUE,
			'single'    => TRUE,
			'meta'      => '<h6>%1$s</h6><div class="summary"><p>%2$s</p></div>', // use meta data after
			'li_before' => '',
			'orderby'   => 'order',
			'order'     => 'ASC',
			'cb'        => FALSE,
			'exclude'   => FALSE, // or array
			'before'    => '',
			'after'     => '',
			'context'   => NULL,
		), $atts, $shortcode );

		if ( FALSE === $args['context'] ) // bailing
			return NULL;

		$key = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $shortcode );
		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( TRUE === $args['exclude'] )
			$args['exclude'] = array( $post->ID );
		else if ( FALSE === $args['exclude'] )
			$args['exclude'] = array();

		if ( $args['id'] ) {

			$tax_query = array( array(
				'taxonomy' => $series_tax,
				'field'    => 'id',
				'terms'    => $args['id'],
			) );

		} else if ( $args['slug'] ) {

			if ( $the_term = get_term_by( 'slug', $args['slug'], $series_tax ) ) {
				$args['id'] = $the_term->term_id;
				$tax_query = array( array(
					'taxonomy' => $series_tax,
					// 'field'    => 'slug',
					'field'    => 'id',
					'terms'    => $args['id'],
				) );
			} else {
				return $content;
			}

		} else {

			// use post's own series tax if neither "id" nor "slug" exist
			$terms = wp_get_object_terms( (int) $post->ID, $series_tax, array( 'fields' => 'ids' ) );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$args['id'] = $terms[0];
				$tax_query = array( array(
					'taxonomy' => $series_tax,
					'field'    => 'id',
					'terms'    => $terms[0],
				) );

			} else {
				$error = TRUE;
			}
		}

		if ( ! $error ) {

			if ( $args['title'] ) {
				if ( FALSE !== strpos( $args['title'], '%' ) ) {
					if ( $the_term = get_term_by( 'id', $args['id'], $series_tax ) ) {
						$args['title'] = sprintf( $args['title'],
							sanitize_term_field( 'name', $the_term->name, $the_term->term_id, $the_term->taxonomy, 'display' ),
							get_term_link( $the_term, $the_term->taxonomy ),
							self::termDescription( $the_term )
						);
					}
				}
				$args['title'] = gEditorialHelper::html( $args['title_tag'], array(
					'class' => '-title',
				), $args['title'] );
			}

			$query_args = array(
				'tax_query'      => $tax_query,
				'order'          => $args['order'],
				'orderby'        => $args['orderby'] == 'order' ? 'date' : $args['orderby'],
				'post_status'    => $args['future'] ? array( 'publish', 'future' ) : 'publish',
				'post__not_in'   => $args['exclude'],
				'posts_per_page' => intval( $args['limit'] ),
			);

			$the_posts = get_posts( $query_args );
			$count     = count( $the_posts );

			if ( $count > 1 || ( $args['single'] && $count > 0 ) ) {

				if ( $count > 1 && 'order' == $args['orderby'] && $args['id'] ) {
					$i = 1000;
					$ordered_posts = array();

					foreach ( $the_posts as & $the_post ) {

						$the_post->series_meta = $gEditorial->series->get_postmeta( $the_post->ID, $args['id'], array() );

						if ( isset( $the_post->series_meta['in_series_order'] )
							&& $the_post->series_meta['in_series_order'] )
								$order_key = intval( $the_post->series_meta['in_series_order'] ) * $i;
						else
							$order_key = strtotime( $the_post->post_date );

						$the_post->menu_order      = $order_key;
						$ordered_posts[$order_key] = $the_post;
						$i++;
					}

					if ( $args['order'] == 'ASC' )
						ksort( $ordered_posts, SORT_NUMERIC );
					else
						krsort( $ordered_posts, SORT_NUMERIC );

					$the_posts = $ordered_posts;
					unset( $ordered_posts, $the_post, $i );
				}

				$offset = 1;
				$more   = FALSE;
				$output = '';

				foreach ( $the_posts as $post ) {
					setup_postdata( $post );
					if ( $args['cb'] ) {
						$output .= call_user_func_array( $args['cb'], array( $post, $args, $offset ) );
					} else {
						if ( $post->post_status == 'publish' )
							$link = '<span class="the-title in-series-publish"><a href="'.get_permalink( $post->ID ).'">'.get_the_title( $post->ID ).'</a>';
						else
							$link = '<span class="the-title in-series-future">'.get_the_title( $post->ID ).'</span>';

						if ( $args['hide'] > 1 && $offset > $args['hide'] ) {
							$output .= '<li class="in-series-hidden in-series-hidden-'.$args['id'].'">';
							if ( ! $more ) {
								$output .= '<li class="in-series-more" id="in-series-more-'.$args['id'].'" style="display:none;"><a href="#" title="'._x( 'More in this series', 'series hide link title', GEDITORIAL_TEXTDOMAIN ).'">'._x( 'More&hellip;', 'series hide link', GEDITORIAL_TEXTDOMAIN ).'</li>';
								$more = true;
							}
						} else {
							$output .= '<li>';
						}

						$output .= $args['li_before'].$link;

						if ( $args['meta']
							&& ( isset( $post->series_meta['in_series_title'] )
								|| isset( $post->series_meta['in_series_desc'] ) ) ) {

							if ( TRUE ===  $args['meta'] )
								$args['meta'] = '<h6>%1$s</h6><div class="summary"><p>%2$s</p></div>';

							$output .= sprintf( $args['meta'],
								isset( $post->series_meta['in_series_title'] ) ? $post->series_meta['in_series_title'] : '',
								isset( $post->series_meta['in_series_desc'] ) ? $post->series_meta['in_series_desc'] : ''
							);
						}

						$output .= '</li>';
					}
					$offset++;
				}

				wp_reset_query();

				// $the_series = get_term_by( 'id', $args['id'], $series_tax );
				// $output .= '<br />'.$the_series->name;

				$output = $args['title'].gEditorialHelper::html( $args['list'], array(
					'class' => '-list',
				), $output );

				if ( $more ) {
					$output .= '<script>
						jQuery(document).ready(function($) {
							$("li.in-series-hidden-'.$args['id'].'").slideUp();
							$("li#in-series-more-'.$args['id'].' a").click(function(e){
								e.preventDefault();
								$(this).slideUp();
								$("li.in-series-hidden-'.$args['id'].'").slideDown();
							});
						});
					</script>';
				}

				$output = $args['before'].gEditorialHelper::html( 'div', array(
					'class' => array(
						'geditorial-wrap',
						'series',
						$args['class'],
						( is_null( $args['context'] ) ? '' : 'series-'.sanitize_html_class( $args['context'], 'general' ) ),
					),
				), $output ).$args['after'];

				wp_cache_set( $key, $output, $shortcode );
				return $output;
			}
		}

		return $content;
	}
}

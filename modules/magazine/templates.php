<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazineTemplates extends gEditorialTemplateCore
{

	public static function issue_shortcode( $atts = array(), $content = NULL, $tag = '' )
	{
		global $post;

		$error  = FALSE;
		$output = '';

		$issue_cpt = gEditorial()->get_constant( 'magazine', 'issue_cpt', 'issue' );
		$issue_tax = gEditorial()->get_constant( 'magazine', 'issue_tax', 'issues' );

		$args = shortcode_atts( array(
			'slug'       => '',
			'id'         => '',
			'title'      => '',
			'title_wrap' => 'h3',
			'list'       => 'ul',
			'limit'      => -1,
			'future'     => 'on',
			'li_before'  => '',
			'orderby'    => 'date',
			'order'      => 'ASC',
			'cb'         => FALSE,
		), $atts, gEditorial()->get_constant( 'magazine', 'issue_shortcode', 'issue' ) );

		$key = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $issue_cpt );
		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] ) {
			$tax_query = array( array(
				'taxonomy' => $issue_tax,
				'field'    => 'id',
				'terms'    => array( $args['id'] ),
			) );
		} else if ( $args['slug'] ) {
			$tax_query = array( array(
				'taxonomy' => $issue_tax,
				'field'    => 'slug',
				'terms'    => $args['slug'],
			) );
		} else if ( $post->post_type == $issue_cpt ) {
			$args['id'] = get_post_meta( $post->ID, '_'.$issue_cpt.'_term_id', TRUE );
			$tax_query = array( array(
				'taxonomy' => $issue_tax,
				'field'    => 'id',
				'terms'    => array( $args['id'] )
			) );
		} else {
			// use post's own issue tax if neither "id" nor "slug" exist
			$terms = get_the_terms( $post->ID, $issue_tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term )
					$term_list[] = $term->slug;
				$tax_query = array( array(
					'taxonomy' => $issue_tax,
					'field'    => 'slug',
					'terms'    => $term_list
				) );
			} else {
				$error = TRUE;
			}
		}

		if ( $args['title'] )
			$output .= '<'.$args['title_wrap'].' class="issue-list-title">'.$args['title'].'</'.$args['title_wrap'].'>';

		if ( 'on' == $args['future'] ) {
			$post_status = array( 'publish', 'future', 'draft' );
		} else {
			$post_status = array( 'publish' );
		}

		if ( ! $error ) {

			$query_args = array(
				'tax_query'      => $tax_query,
				'posts_per_page' => $args['limit'],
				'orderby'        => ( $args['orderby'] == 'page' ? 'date' : $args['orderby'] ),
				'order'          => $args['order'],
				'post_status'    => $post_status
			);

			$the_posts = get_posts( $query_args );

			if ( count( $the_posts ) ) {

				if ( $args['orderby'] == 'page' && count( $the_posts ) > 1  ) {

					$i = 1000;
					$ordered_posts = array();

					foreach ( $the_posts as & $the_post ) {

						$in_issue_page_start = gEditorial()->meta->get_postmeta( $the_post->ID, 'in_issue_page_start', FALSE );
						$in_issue_order      = gEditorial()->meta->get_postmeta( $the_post->ID, 'in_issue_order', FALSE );

						$order_key = ( $in_issue_page_start ? ( (int) $in_issue_page_start * 10 ) : 0 );
						$order_key = ( $in_issue_order ? ( $order_key + (int) $in_issue_order ) : $order_key );
						$order_key = ( $order_key ? $order_key : ( $i * 100 ) );
						$i++;

						$the_post->menu_order      = $in_issue_page_start;
						$ordered_posts[$order_key] = $the_post;
					}

					// http://wordpress.mfields.org/2011/rekey-an-indexed-array-of-post-objects-by-post-id/
					// $the_list = wp_list_pluck( $the_posts, 'menu_order' );
					// $the_posts = array_combine( $the_list, $the_posts );

					ksort( $ordered_posts );
					$the_posts = $ordered_posts;
					unset( $ordered_posts, $the_post, $i );
				}

				$output .= '<'.$args['list'].'>';

				foreach ( $the_posts as $post ) {
					setup_postdata( $post );
					if ( $args['cb'] ) {
						$output .= call_user_func_array( $args['cb'], array( $post, $args ) );
					} else {
						if ( $post->post_status == 'publish' ) {
							$output .= '<li>'.$args['li_before'].'<a href="'.get_permalink( $post->ID ).'">'.get_the_title( $post->ID ).'</a></li>';
						} else {
							$output .= '<li>'.$args['li_before'].get_the_title( $post->ID ).'</li>';
						}
					}
				}

				$output .= '</'.$args['list'].'>';

				wp_reset_postdata();
				wp_cache_set( $key, $output, $issue_cpt );

				return $output;
			}
		}

		return $content;
	}

	public static function span_shortcode( $atts, $content = NULL, $tag = '' )
	{
		global $post;

		$error = FALSE;
		$title = '';

		$issue_cpt = gEditorial()->get_constant( 'magazine', 'issue_cpt', 'issue' );
		$span_tax  = gEditorial()->get_constant( 'magazine', 'span_tax', 'issue_span' );

		$args = shortcode_atts( array(
			'slug'        => '',
			'id'          => '',
			'title'       => '',
			'title_wrap'  => 'h3',
			'title_link'  => FALSE,
			'title_title' => _x( 'Permanent link to this span', 'Magazine Module: Span Shortcode: title attr', GEDITORIAL_TEXTDOMAIN ),
			'list'        => 'ul',
			'list_class'  => 'issue-list',
			'limit'       => -1,
			'future'      => 'on',
			'li_before'   => '',
			'orderby'     => 'date',
			'order'       => 'ASC',
			'cb'          => FALSE,
			'link'        => 'title', // not used yet
			'cover'       => FALSE,
		), $atts, gEditorial()->get_constant( 'magazine', 'span_shortcode', 'span' ) );

		$key = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $span_tax );
		if ( FALSE !== $cache )
			return $cache;

		if ( $args['cb'] && ! is_callable( $args['cb'] ) )
			$args['cb'] = FALSE;

		if ( $args['id'] ) {

			if ( 'all' == $args['id'] )
				$tax_query = array();
			else
				$tax_query = array( array(
					'taxonomy' => $span_tax,
					'field'    => 'id',
					'terms'    => array( $args['id'] ),
				) );

		} else if ( $args['slug'] ) {

			$tax_query = array( array(
				'taxonomy' => $span_tax,
				'field'    => 'slug',
				'terms'    => $args['slug'],
			) );

		} else {

			// Use post's own issue tax if neither "id" nor "slug" exist
			$terms = get_the_terms( $post->ID, $span_tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term )
					$term_list[] = $term->slug;
				$tax_query = array( array(
					'taxonomy' => $span_tax,
					'field'    => 'slug',
					'terms'    => $term_list
				) );
			} else {
				$error = TRUE;
			}
		}

		if ( $args['title'] ) {

			// TODO: if is_null: get span link / also check for module archive settings!

			if ( FALSE !== $args['title_link'] )
				$args['title'] = self::html( 'a', array(
					'href'  => $args['title_link'],
					'title' => $args['title_title'],
				), $args['title'] );

			$title = self::html( $args['title_wrap'], array(
				'class' => 'issue-list-title',
			), $args['title'] );
		}

		if ( $args['future'] == 'on' ) {
			// Include the future posts if the "future" attribute is set to "on"
			$post_status = array( 'publish', 'future', 'draft' );
		} else {
			// Exclude the future posts if the "future" attribute is set to "off"
			$post_status =  array( 'publish' );
		}

		if ( $error == FALSE ) {
			$query_args = array(
				'tax_query'      => $tax_query,
				'posts_per_page' => $args['limit'],
				'orderby'        => ( $args['orderby'] == 'page' ? 'date' : $args['orderby'] ),
				'order'          => $args['order'],
				'post_status'    => $post_status,
				'post_type'      => $issue_cpt,
			);

			if ( $args['cover'] )
				$query_args['meta_query'] = array(
					array(
						'key'     => '_thumbnail_id',
						'compare' => 'EXISTS'
					),
				);

			$the_posts = get_posts( $query_args );

			if ( count( $the_posts ) ) {
				$output = $title_output.'<'.$args['list'].' class="'.$args['list_class'].'">';
				foreach ( $the_posts as $post ) {
					setup_postdata( $post );
					if ( $args['cb'] ) {
						$output .= call_user_func_array( $args['cb'], array( $post, $args ) );
					} else {
						if ( $post->post_status == 'publish' ) {
							$output .= '<li>'.$args['li_before'].'<a href="'.get_permalink( $post->ID ).'">'.get_the_title( $post->ID ).'</a></li>';
						} else {
							$output .= '<li>'.$args['li_before'].get_the_title( $post->ID ).'</li>';
						}
					}
				}

				wp_reset_postdata();

				$output .= '</'.$args['list'].'>';

				wp_cache_set( $key, $output, $span_tax );
				return $output;
			}
		}
		return $content;
	}

	// FIXME: UNFINISHED
	public static function issue( $atts = array() )
	{
		global $post;

		$args = self::atts( array(
			'id'     => $post->ID,
			'before' => isset( $atts['b'] ) ? $atts['b'] : '',
			'after'  => isset( $atts['a'] ) ? $atts['a'] : '',
			'filter' => isset( $atts['f'] ) ? $atts['f'] : FALSE,
			'echo'   => isset( $atts['e'] ) ? $atts['e'] : FALSE,
			'def'    => FALSE,
			'img'    => FALSE,
			'link'   => NULL, // false to disable
			'desc'   => NULL, // false to disable
		), $atts );
	}

	public static function the_issue( $b = '', $a = '', $f = FALSE, $post_id = NULL, $args = array() )
	{
		global $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$the_issues = gEditorial()->magazine->get_issue_post( $post_id );
		if ( FALSE === $the_issues )
			return;

		$html = '';
		foreach ( $the_issues as $the_id => $the_link ){
			$the_issue = get_post( $the_id );
			if ( $the_issue ) {
				if ( FALSE !== $the_link )
					$html .= '<a href="'.$the_link.'">'.$the_issue->post_title.'</a>';
				else
					$html .= '<span>'.$the_issue->post_title.'</span>';
			}
		}

		if ( ! empty( $html ) )
			$html = $b.( $f ? $f( $html ) : $html ).$a;

		if ( isset( $args['echo'] ) ) {
			if ( ! $args['echo'] )
				return $html;
		}

		echo $html;
		return TRUE;
	}

	public static function get_issue_cover( $size = 'raw', $post_id = NULL, $attr = '', $default = FALSE )
	{
		$post_thumbnail_id = get_post_thumbnail_id( is_null( $post_id ) ? get_the_ID() : $post_id );

		if ( $post_thumbnail_id && $html = wp_get_attachment_image( $post_thumbnail_id, $size, FALSE, $attr ) )
			return $html;

		return $default;
	}

	public static function issue_cover_parse_arg( $args, $size = 'raw' )
	{
		return wp_parse_args( $args, array(
			'id'       => NULL,
			'attr'     => array(
				'class' => 'size-'.$size
			),
			'def'      => FALSE,
			'cb'       => FALSE,
			'echo'     => TRUE,
			'title'    => 'title',
			'fallback' => FALSE,
		));
	}

	public static function issue_cover( $b = '', $a = '', $size = 'raw', $link = 'parent', $args = array() )
	{
		$issue_cpt = gEditorial()->get_constant( 'magazine', 'issue_cpt', 'issue' );
		$args      = self::issue_cover_parse_arg( $args, $size );

		if ( 'latest' == $args['id'] )
			$args['id'] = gEditorialHelper::getLastPostOrder( $issue_cpt, '', 'ID', 'publish' );
		else if ( 'random' == $args['id'] )
			$args['id'] = self::get_random_issue();
		else if ( 'issue' == $args['id'] )
			$args['id'] = gEditorial()->magazine->get_issue_post( NULL, TRUE );

		$img = self::get_issue_cover( $size, $args['id'], $args['attr'], $args['def'] );
		if ( FALSE !== $link ) {
			$status = get_post_status( $args['id'] );
			if ( 'publish' == $status ) {
				if ( 'parent' == $link ) {
					$link = get_permalink( $args['id'] );
				} else if ( 'attachment' == $link ) {
					@$link = get_attachment_link( get_post_thumbnail_id( $args['id'] ) );
				} else if ( 'url' == $link ) {
					$link = wp_get_attachment_url( get_post_thumbnail_id( $args['id'] ) );
				}
			} else {
				$link = FALSE;
			}
		}

		if ( $args['cb'] && is_callable( $args['cb'] ) ) {
			$result = call_user_func_array( $args['cb'], array( $img, $link, $args ) );

		} else if ( $img ) {
			$result = gEditorialHelper::html( ( $link ? 'a' : 'span' ), array(
				'href'  => $link,
				'title' => self::get_issue_title( $args['title'], $args['id'], FALSE ),
				'data' => array(
					'toggle' => 'tooltip',
				),
			), $img );

		} else if ( $args['id'] && $args['fallback'] && 'publish' == get_post_status( $args['id'] ) ) {
			$result = gEditorialHelper::html( 'a', array(
				'href'  => esc_url( get_permalink( $args['id'] ) ),
				'title' => self::get_issue_title( $args['title'], $args['id'], FALSE ),
				'data' => array(
					'toggle' => 'tooltip',
				),
			), get_the_title( $args['id'] ) );

		} else {
			$result = FALSE;
		}

		if ( $result && $args['echo'] )
			echo $b.$result.$a;
		else
			return $b.$result.$a;

		if ( FALSE ===  $args['def'] )
			return FALSE;

		return $b.$args['def'].$a;
	}

	public static function the_issue_cover( $b = '', $a = '', $size = 'raw', $link = 'parent', $args = array() )
	{
		$args = self::issue_cover_parse_arg( $args, $size );
		$the_issues = gEditorial()->magazine->get_issue_post( $args['id'] );

		if ( FALSE !== $the_issues ) {
			$result = '';
			foreach ( $the_issues as $the_id => $the_link )
				$result .= self::issue_cover( '', '', $size,
					( FALSE !== $link ? $the_link : FALSE ),
					array_merge ( $args, array( 'id' => $the_id, 'echo' => FALSE ) )
				);
		} else {
			$result = $args['def'];
		}

		if ( $result && $args['echo'] )
			echo $b.$result.$a;
		else
			return FALSE;
	}

	public static function get_issue_title( $field = 'title', $id = NULL, $default = '' )
	{
		if ( 'title' == $field )
			return strip_tags( get_the_title( $id ) );

		if ( $field && function_exists( 'issue_info' ) )
			return issue_info( $field, '', '', FALSE, $id, array( 'echo' => FALSE, 'def' => $default ) );

		return $default;
	}

	// example callback to build cover with caption
	public static function issue_cover_callback( $img, $link, $args )
	{
		if ( ! $img )
			return $args['def'];

		$html = $img;

		if ( $caption = self::get_issue_title( $args['title'], $args['id'], FALSE ) )
			$html = '<figure>'.$html.'<figcaption>'.$caption.'</figcaption></figure>';

		if ( $link )
			$html = '<a title="'.esc_attr( self::get_issue_title( 'title', $args['id'] ) ).'" href="'.$link.'">'.$html.'</a>';

		return '<div class="geditorial-wrap magazine issue-cover">'.$html.'</div>';
	}

	// FIXME: DEPRECATED : for back comp
	public static function get_latest_issue()
	{
		return self::getLatestIssueID();
	}

	// helper for themes
	public static function getLatestIssueID()
	{
		$issue_cpt = gEditorial()->get_constant( 'magazine', 'issue_cpt', 'issue' );
		return gEditorialHelper::getLastPostOrder( $issue_cpt, '', 'ID', 'publish' );
	}

	public static function get_random_issue( $object = FALSE )
	{
		$the_post = get_posts( array(
			'numberposts' => 1,
			'orderby'     => 'rand', //'post_date',
			// 'order'       => 'DESC',
			'post_type'   => gEditorial()->get_constant( 'magazine', 'issue_cpt', 'issue' ),
			'post_status' => 'publish',
			// 'meta_key'    => '_thumbnail_id', // must has cover
			'meta_query'  => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS'
				),
			),
		) );

		if ( count ( $the_post ) ) {
			if ( $object )
				return $the_post[0];
			else
				return $the_post[0]->ID;
		}
		return FALSE;
	}

	public static function issue_info( $field, $b = '', $a = '', $f = FALSE, $post_id = NULL, $args = array() )
	{
		global $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$meta = gEditorial()->meta->get_postmeta( $post_id, self::sanitize_field( $field ), FALSE );

		if ( FALSE !== $meta ) {
			$html = $b.( $f ? $f( $meta ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return TRUE;
		}
		return isset( $args['def'] ) ? $args['def'] : FALSE;
	}

	public static function sanitize_field( $field )
	{
		$fields = array(
			'over-title' => 'ot',
			'sub-title'  => 'st',
			'number'     => 'issue_number_line',
			'pages'      => 'issue_total_pages',
		);

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return $field;
	}
}

if ( ! function_exists( 'issue_info' ) ) : function issue_info( $field, $b = '', $a = '', $f = FALSE, $id = NULL, $args = array() ){
	return gEditorialMagazineTemplates::issue_info( $field, $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'the_issue' ) ) : function the_issue( $b = '', $a = '', $f = FALSE, $id = NULL, $args = array() ) {
	return gEditorialMagazineTemplates::the_issue( $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'issue_cover' ) ) : function issue_cover( $b = '', $a = '', $tag = 'raw', $link = 'parent', $args = array() ) {
	return gEditorialMagazineTemplates::issue_cover( $b, $a, $tag, $link, $args );
} endif;

if ( ! function_exists( 'the_issue_cover' ) ) : function the_issue_cover( $b = '', $a = '', $tag = 'raw', $link = 'parent', $args = array() ) {
	return gEditorialMagazineTemplates::the_issue_cover( $b, $a, $tag, $link, $args );
} endif;

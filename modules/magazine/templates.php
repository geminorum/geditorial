<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazineTemplates
{

    public static function issue_shortcode( $atts, $content = null, $tag = '' )
    {
        global $gEditorial, $post;

        $error = false;
        $title_output = '';

		$issue_cpt = $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' );
		$issue_tax = $gEditorial->get_module_constant( 'magazine', 'issue_tax', 'issues' );

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
            'cb'         => false,
        ), $atts, $gEditorial->get_module_constant( 'magazine', 'issue_shortcode', 'issue' ) );

        $key = md5( serialize( $args ) );
        $cache = wp_cache_get( $key, $issue_cpt );
        if ( false !== $cache )
            return $cache;

        if ( $args['cb'] && ! is_callable( $args['cb'] ) )
            $args['cb'] = false;

        if( $args['id'] ) {
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
            $args['id'] = get_post_meta( $post->ID, '_'.$issue_cpt.'_term_id', true );
            $tax_query = array( array(
                'taxonomy' => $issue_tax,
                'field'    => 'id',
                'terms'    => array( $args['id'] )
            ) );
        } else {
            // Use post's own issue tax if neither "id" nor "slug" exist
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
                $error = true;
            }
        }

        if ( $args['title'] ) {
            // Create the title if the "title" attribute exists
            $title_output = '<'.$args['title_wrap'].' class="issue-list-title">'.$args['title'].'</'.$args['title_wrap'].'>';
        }

        if ( $args['future'] == 'on' ) {
            // Include the future posts if the "future" attribute is set to "on"
            $post_status = array( 'publish', 'future', 'draft' );
        } else {
            // Exclude the future posts if the "future" attribute is set to "off"
            $post_status =  array( 'publish' );
        }

        if ( $error == false ) {
            $query_args = array(
                'tax_query'      => $tax_query,
                'posts_per_page' => $args['limit'],
                'orderby'        => ( $args['orderby'] == 'page' ? 'date' : $args['orderby'] ),
                'order'          => $args['order'],
                'post_status'    => $post_status
            );

            $the_posts = get_posts( $query_args );
            //$the_posts = new WP_Query( $query_args );
            //gtheme_dump( $the_posts ); die();

            /* if there's more than one post with the specified "series" taxonomy, display the list. if there's just one post with the specified taxonomy, there's no need to list the only post! */
            //if ( count( $the_posts ) > 1 ) {


            if ( count( $the_posts ) ) {
                if ( function_exists( 'get_gmeta' ) && count( $the_posts ) > 1 && $args['orderby'] == 'page' ) {

                    $i = 1000;
                    $ordered_posts = array();

                    foreach( $the_posts as & $the_post ) {

                        $in_issue_page_start = get_gmeta( 'in_issue_page_start', array( 'id' => $the_post->ID, 'def' => false ) );
                        $in_issue_order = get_gmeta( 'in_issue_order', array( 'id' => $the_post->ID, 'def' => false ) );
                        $order_key = ( $in_issue_page_start ? ( (int) $in_issue_page_start * 10 ) : 0 );
                        $order_key = ( $in_issue_order ? ( $order_key + (int) $in_issue_order ) : $order_key );
                        $order_key = ( $order_key ? $order_key : ( $i * 100 ) );
                        $i++;

                        //$the_post->menu_order = $order_key;
                        $the_post->menu_order = $in_issue_page_start;

                        $ordered_posts[$order_key] = $the_post;
                        /**$the_post->menu_order = get_gmeta( 'in_issue_page_start', array(
                            'id' => $the_post->ID,
                            'def' => $the_post->menu_order,
                        ) );**/
                    }

                    // http://wordpress.mfields.org/2011/rekey-an-indexed-array-of-post-objects-by-post-id/
                    //$the_list = wp_list_pluck( $the_posts, 'menu_order' );
                    //$the_posts = array_combine( $the_list, $the_posts );

                    ksort( $ordered_posts );
                    $the_posts = $ordered_posts;
                    unset( $ordered_posts, $the_post, $i );
                }

                $output = $title_output.'<'.$args['list'].' class="post-series-list">';
                foreach( $the_posts as $post ) {
                    setup_postdata( $post );
                    if ( $args['cb'] ) {
                        $output .= call_user_func_array( $args['cb'], array( $post, $args ) );
                    } else {
                        if( $post->post_status == 'publish' ) {
                            $output .= '<li>'.$args['li_before'].'<a href="'.get_permalink( $post->ID ).'">'.get_the_title( $post->ID ).'</a></li>';
                        } else {
                            $output .= '<li>'.$args['li_before'].get_the_title( $post->ID ).'</li>';
                        }
                    }
                }

                //wp_reset_query();
                wp_reset_postdata();

                $output .= '</'.$args['list'].'>';

                wp_cache_set( $key, $output, $issue_cpt );
                return $output;
            }
        }

		return $content;
    }

    public static function span_shortcode( $atts, $content = null, $tag = '' )
    {
        global $gEditorial, $post;

		$error = false;
        $title_output = '';

		$issue_cpt = $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' );
		$span_tax = $gEditorial->get_module_constant( 'magazine', 'span_tax', 'span' );

        $args = shortcode_atts( array(
            'slug'       => '',
            'id'         => '',
            'title'      => '',
            'title_wrap' => 'h3',
            'list'       => 'ul',
            'list_class' => 'issue-series-list',
            'limit'      => -1,
            'future'     => 'on',
            'li_before'  => '',
            'orderby'    => 'date',
            'order'      => 'ASC',
            'cb'         => false,
            'link'       => 'title', //not used yet
            'cover'      => false,
        ), $atts, $gEditorial->get_module_constant( 'magazine', 'span_shortcode', 'span' ) );

        $key = md5( serialize( $args ) );
        $cache = wp_cache_get( $key, $span_tax );
        if ( false !== $cache )
            return $cache;

        if ( $args['cb'] && ! is_callable( $args['cb'] ) )
            $args['cb'] = false;

        if( $args['id'] ) {
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
                $error = true;
            }
        }

        if ( $args['title'] ) {
            // Create the title if the "title" attribute exists
            $title_output = '<'.$args['title_wrap'].' class="issue-list-title">'.$args['title'].'</'.$args['title_wrap'].'>';
        }

        if ( $args['future'] == 'on' ) {
            // Include the future posts if the "future" attribute is set to "on"
            $post_status = array( 'publish', 'future', 'draft' );
        } else {
            // Exclude the future posts if the "future" attribute is set to "off"
            $post_status =  array( 'publish' );
        }

        if ( $error == false ) {
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


            //gtheme_dump( $query_args ); die();

            $the_posts = get_posts( $query_args );

            //gtheme_dump( $the_posts ); die();

            if ( count( $the_posts ) ) {
                $output = $title_output.'<'.$args['list'].' class="'.$args['list_class'].'">';
                foreach( $the_posts as $post ) {
                    setup_postdata( $post );
                    if ( $args['cb'] ) {
                        $output .= call_user_func_array( $args['cb'], array( $post, $args ) );
                    } else {
                        if( $post->post_status == 'publish' ) {
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


    public static function the_issue( $b = '', $a = '', $f = false, $post_id = null, $args = array() )
    {
        global $gEditorial, $post;

        if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$the_issues = $gEditorial->magazine->get_issue_post( $post_id );
        if ( false === $the_issues )
            return;

        $html = '';
        foreach ( $the_issues as $the_id => $the_link ){
            $the_issue = get_post( $the_id );
            if ( $the_issue ) {
				if ( false !== $the_link )
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
        return true;
    }

    public static function get_issue_cover( $size = 'raw', $post_id = null, $attr = '', $default = false )
    {
        $post_thumbnail_id = get_post_thumbnail_id( is_null( $post_id ) ? get_the_ID() : $post_id );

        if ( $post_thumbnail_id && $html = wp_get_attachment_image( $post_thumbnail_id, $size, false, $attr ) )
            return $html;

        return $default;
    }

    public static function issue_cover_parse_arg( $args, $size = 'raw' )
    {
        return wp_parse_args( $args, array(
            'id'    => null,
            'attr'  => array( 'class' => 'issue-cover '.$size ),
            'def'   => false,
            'cb'    => false,
            'echo'  => true,
            'title' => 'title',
        ));
    }

    public static function issue_cover( $b = '', $a = '', $size = 'raw', $link = 'parent', $args = array() )
    {
        $args = self::issue_cover_parse_arg( $args, $size );

        if ( 'latest' == $args['id'] )
            $args['id'] = self::get_latest_issue();
        else if ( 'random' == $args['id'] )
            $args['id'] = self::get_random_issue();

        $img = self::get_issue_cover( $size, $args['id'], $args['attr'], $args['def'] );
        if ( false !== $link ) {
			$status = get_post_status( $args['id'] );
            if ( 'publish' == $status ) {
				if ( 'parent' == $link ) {
					$link = get_permalink( $args['id'] );
				} elseif ( 'attachment' == $link ) {
					@$link = get_attachment_link( get_post_thumbnail_id( $args['id'] ) );
				} elseif ( 'url' == $link ) {
					$link = wp_get_attachment_url( get_post_thumbnail_id( $args['id'] ) );
				}
			} else {
				$link = false;
			}
        }

        if ( $args['cb'] && is_callable( $args['cb'] ) ) {
            $result = call_user_func_array( $args['cb'], array( $img, $link, $args ) );
        } else if ( $img ) {
            $title = $args['title'] ? ' title="'.esc_attr( self::get_issue_title( $args['title'], $args['id'], '' ) ).'"' : '';
            $result = $link ? '<a href="'.$link.'"'.$title.'>'.$img.'</a>' : $img;
        } else {
            $result = false;
        }

        if ( $result && $args['echo'] )
            echo $b.$result.$a;
        else
            return $b.$result.$a;

        if ( false ===  $args['def'] )
            return false;

        return $b.$args['def'].$a;
    }

    public static function the_issue_cover( $b = '', $a = '', $size = 'raw', $link = 'parent', $args = array() )
    {
		global $gEditorial;

        $args = self::issue_cover_parse_arg( $args, $size );
		$the_issues = $gEditorial->magazine->get_issue_post( $args['id'] );

        if ( false !== $the_issues ) {
            $result = '';
            foreach ( $the_issues as $the_id => $the_link )
                $result .= self::issue_cover( '', '', $size,
                    ( false !== $link ? $the_link : false ),
                    array_merge ( $args, array( 'id' => $the_id, 'echo' => false ) )
                );
        } else {
            $result = $args['def'];
        }

        if ( $result && $args['echo'] )
            echo $b.$result.$a;
        else
            return false;
    }

    public static function get_issue_title( $field = 'title', $id = null, $default = '' )
    {
        if ( 'title' == $field )
            return strip_tags( get_the_title( $id ) );
        if ( $field && function_exists( 'issue_info' ) )
            return issue_info( $field, '', '', false, $id, array( 'echo' => false, 'def' => $default ) );
        return $default;
    }

    // example callback to build cover with caption
    public static function issue_cover_callback( $img, $link, $args )
    {
        if ( ! $img )
            return $args['def'];

        $caption = self::get_issue_title( $args['title'], $args['id'], false );

        $result = $caption ? '<figure class="issue-cover-figure cap-left">'.$img.'<figcaption>'.$caption.'</figcaption></figure>' : $img;
        return $link ? '<a class="issue-cover-link" href="'.$link.'">'.$result.'</a>' : $result;
    }

    public static function get_latest_issue( $object = false )
    {
        global $gEditorial;

        $the_post = get_posts( array(
            'numberposts' => 1,
            'orderby'     => 'menu_order', //'post_date',
            'order'       => 'DESC',
            'post_type'   => $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' ),
            'post_status' => 'publish',
        ) );
        if ( count ( $the_post ) ) {
            if ( $object )
                return $the_post[0];
            else
                return $the_post[0]->ID;
        }
        return false;
    }

    public static function get_random_issue( $object = false )
    {
        global $gEditorial;

        $the_post = get_posts( array(
            'numberposts' => 1,
            'orderby'     => 'rand', //'post_date',
            // 'order'       => 'DESC',
            'post_type'   => $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' ),
            'post_status' => 'publish',
            // 'meta_key'    => '_thumbnail_id', // must has cover
            'meta_query'  => array(
                array(
                    'key' => '_thumbnail_id',
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
        return false;
    }

	public static function issue_info( $field, $b = '', $a = '', $f = false, $post_id = null, $args = array() )
	{
		global $gEditorial, $post;

		if ( is_null( $post_id ) )
			$post_id = $post->ID;

		$meta = $gEditorial->meta->get_postmeta( $post_id, self::sanitize_field( $field ), false );

		if ( false !== $meta ) {
			$html = $b.( $f ? $f( $meta ) : $meta ).$a;
			if ( isset( $args['echo'] ) ) {
				if ( ! $args['echo'] )
					return $html;
			}
			echo $html;
			return true;
		}
		return isset( $args['def'] ) ? $args['def'] : false;
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

if ( ! function_exists( 'issue_info' ) ) : function issue_info( $field, $b = '', $a = '', $f = false, $id = null, $args = array() ){
	return gEditorialMagazineTemplates::issue_info( $field, $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'the_issue' ) ) : function the_issue( $b = '', $a = '', $f = false, $id = null, $args = array() ) {
	return gEditorialMagazineTemplates::the_issue( $b, $a, $f, $id, $args );
} endif;

if ( ! function_exists( 'issue_cover' ) ) : function issue_cover( $b = '', $a = '', $tag = 'raw', $link = 'parent', $args = array() ) {
    return gEditorialMagazineTemplates::issue_cover( $b, $a, $tag, $link, $args );
} endif;

if ( ! function_exists( 'the_issue_cover' ) ) : function the_issue_cover( $b = '', $a = '', $tag = 'raw', $link = 'parent', $args = array() ) {
    return gEditorialMagazineTemplates::the_issue_cover( $b, $a, $tag, $link, $args );
} endif;

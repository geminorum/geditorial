<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWordPress extends gEditorialBaseCore
{

	public static function getUsers( $all_fields = FALSE, $network = FALSE, $extra = array() )
	{
		$users = get_users( array_merge( array(
			'blog_id' => ( $network ? '' : $GLOBALS['blog_id'] ),
			'orderby' => 'display_name',
			'fields'  => ( $all_fields ? 'all_with_meta' : 'all' ),
		), $extra ) );

		return self::reKey( $users, 'ID' );
	}

	public static function getAttachments( $post_id, $mime_type = 'image' )
	{
		return get_children( array(
			'post_mime_type' => $mime_type,
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'numberposts'    => -1,
		) );
	}

	// TODO: use db query
	public static function getLastPostOrder( $post_type = 'post', $exclude = '', $key = 'menu_order', $status = 'publish,private,draft' )
	{
		$post = get_posts( array(
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'exclude'        => $exclude,
			'post_type'      => $post_type,
			'post_status'    => $status,
		) );

		if ( ! count( $post ) )
			return 0;

		if ( 'menu_order' == $key )
			return intval( $post[0]->menu_order );

		return $post[0]->{$key};
	}

	// TODO: use db query
	public static function getRandomPostID( $post_type, $has_thumbnail = FALSE, $object = FALSE, $status = 'publish' )
	{
		$args = array(
			'post_type'              => $post_type,
			'post_status'            => $status,
			'posts_per_page'         => 1,
			'orderby'                => 'rand',
			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
		);

		if ( ! $object )
			$args['fields'] = 'ids';

		if ( $has_thumbnail )
			$args['meta_query'] = array( array(
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			) );

		$query = new WP_Query;
		$posts = $query->query( $args );

		if ( ! count( $posts ) )
			return FALSE;

		return $posts[0];
	}

	public static function getParentPostID( $post_id = NULL, $object = FALSE )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		if ( empty( $post->post_parent ) )
			return FALSE;

		if ( $object )
			return get_post( $post->post_parent );

		return intval( $post->post_parent );
	}

	public static function getFeaturedImage( $post_id, $size = 'thumbnail', $default = FALSE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return $default;

		$post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size );
		return $post_thumbnail_img[0];
	}

	public static function getFeaturedImageHTML( $post_id, $size = 'thumbnail', $link = TRUE )
	{
		if ( ! $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
			return '';

		if ( ! $post_thumbnail_img = wp_get_attachment_image_src( $post_thumbnail_id, $size ) )
			return '';

		$image = gEditorialHTML::tag( 'img', array( 'src' => $post_thumbnail_img[0] ) );

		if ( ! $link )
			return $image;

		return gEditorialHTML::tag( 'a', array(
			'href'   => wp_get_attachment_url( $post_thumbnail_id ),
			'target' => '_blank',
		), $image );
	}

	// EDITED: 8/12/2016, 8:53:06 AM
	public static function getPostTypes( $mod = 0, $args = array( 'public' => TRUE ) )
	{
		$list = array();

		foreach ( get_post_types( $args, 'objects' ) as $post_type => $post_type_obj ) {

			// label
			if ( 0 === $mod )
				$list[$post_type] = $post_type_obj->label;

			// plural
			else if ( 1 === $mod )
				$list[$post_type] = $post_type_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$post_type] = $post_type_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$post_type] = array(
					0          => $post_type_obj->labels->singular_name,
					1          => $post_type_obj->labels->name,
					'singular' => $post_type_obj->labels->singular_name,
					'plural'   => $post_type_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				);

			// object
			else if ( 4 === $mod )
				$list[$post_type] = $post_type_obj;
		}

		return $list;
	}

	public static function newPostFromTerm( $term, $taxonomy = 'category', $post_type = 'post', $user_id = 0 )
	{
		if ( ! is_object( $term ) && ! is_array( $term ) )
			$term = get_term( $term, $taxonomy );

		$new_post = array(
			'post_title'   => $term->name,
			'post_name'    => $term->slug,
			'post_content' => $term->description,
			'post_status'  => 'draft',
			'post_author'  => $user_id ? $user_id : get_current_user_id(),
			'post_type'    => $post_type,
		);

		return wp_insert_post( $new_post );
	}

	public static function theTerm( $taxonomy, $post_id, $object = FALSE )
	{
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) )
			foreach ( $terms as $term )
				return $object ? $term : $term->term_id;

		return '0';
	}

	public static function prepareTerms( $taxonomy, $extra = array(), $terms = NULL, $key = 'term_id', $object = TRUE )
	{
		$new_terms = array();

		if ( is_null( $terms ) ) {
			$terms = get_terms( $taxonomy, array_merge( array(
				'hide_empty' => FALSE,
				'orderby'    => 'name',
				'order'      => 'ASC'
			), $extra ) );
		}

		if ( is_wp_error( $terms ) || FALSE === $terms )
			return $new_terms;

		foreach ( $terms as $term ) {

			$new = array(
				'name'        => $term->name,
				'description' => $term->description,
				'link'        => get_term_link( $term, $taxonomy ),
				'count'       => $term->count,
				'parent'      => $term->parent,
				'slug'        => $term->slug,
				'id'          => $term->term_id,
			);

			$new_terms[$term->{$key}] = $object ? (object) $new : $new;
		}

		return $new_terms;
	}

	// ADOPTED FROM: wp_count_posts()
	// EDITED: 8/12/2016, 8:53:18 AM
	public static function countPostsByTaxonomy( $taxonomy, $post_types = array( 'post' ), $user_id = 0 )
	{
		$key = md5( serialize( $taxonomy ).'_'.serialize( $post_types ).'_'.$user_id );
		$counts = wp_cache_get( $key, 'counts' );

		if ( FALSE !== $counts )
			return $counts;

		$terms = is_array( $taxonomy ) ? $taxonomy : get_terms( $taxonomy );

		if ( ! count( $terms ) )
		 	return array();

		global $wpdb;

		$counts = array();
		$totals = array_fill_keys( $post_types, 0 );

		$post_types_in = implode( ',', array_map( function( $v ){
			return "'".esc_sql( $v )."'";
		}, $post_types ) );

		$author = $user_id ? $wpdb->prepare( "AND posts.post_author = %d", $user_id ) : '';

		foreach ( $terms as $term ) {

			$counts[$term->slug] = $totals;

			$query = $wpdb->prepare("
				SELECT posts.post_type, COUNT( * ) AS total
				FROM {$wpdb->posts} AS posts, {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_id = t.term_id
				INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE t.term_id = %d
				AND tr.object_id = posts.ID
				AND posts.post_type IN ( {$post_types_in} )
				{$author}
				GROUP BY posts.post_type
			", $term->term_id );

			foreach ( (array) $wpdb->get_results( $query, ARRAY_A ) as $row )
				$counts[$term->slug][$row['post_type']] = $row['total'];
		}

		wp_cache_set( $key, $counts, 'counts' );

		return $counts;
	}

	// EDITED: 10/28/2016, 10:06:17 AM
	// ADOPTED FROM: wp_count_posts()
	public static function countPostsByPosttype( $posttype = 'post', $user_id = 0, $period = array() )
	{
		global $wpdb;

		$author = $from = $to = '';
		$counts = array_fill_keys( get_post_stati(), 0 );

		if ( $user_id )
			$author = $wpdb->prepare( "AND post_author = %d", $user_id );

		if ( ! empty( $period[0] ) )
			$from = $wpdb->prepare( "AND post_date >= '%s'", $period[0] );

		if ( ! empty( $period[1] ) )
			$to = $wpdb->prepare( "AND post_date <= '%s'", $period[1] );

		$query = $wpdb->prepare( "
			SELECT post_status, COUNT( * ) AS total
			FROM {$wpdb->posts}
			WHERE post_type = %s
			{$author}
			{$from}
			{$to}
			GROUP BY post_status
		", $posttype );

		$results = gEditorialCache::getResultsDB( $query, ARRAY_A, 'counts' );

		foreach ( (array) $results as $row )
			$counts[$row['post_status']] = $row['total'];

		return $counts;
	}

	// FIXME: DROP THIS
	public static function countPostsByPosttype_OLD( $posttype = 'post', $user_id = 0, $period = array() )
	{
		$key = md5( $posttype.'_'.$user_id );
		$counts = wp_cache_get( $key, 'counts' );

		if ( FALSE !== $counts )
			return $counts;

		global $wpdb;

		$counts = array_fill_keys( get_post_stati(), 0 );
		$author = $user_id ? $wpdb->prepare( "AND post_author = %d", $user_id ) : '';
		$date   = '';

		if ( ! empty( $period[0] ) )
			$date .= " AND post_date >='$period[0]'";

		if ( ! empty( $period[1] ) )
			$date .= " AND post_date <='$period[1]'";

		$query = $wpdb->prepare("
			SELECT post_status, COUNT( * ) AS total
			FROM {$wpdb->posts}
			WHERE post_type = %s
			{$author}
			{$date}
			GROUP BY post_status
		", $posttype );

		foreach ( (array) $wpdb->get_results( $query, ARRAY_A ) as $row )
			$counts[$row['post_status']] = $row['total'];

		wp_cache_set( $key, $counts, 'counts' );

		return $counts;
	}

	public static function getPosttypeMonths( $post_type = 'post', $args = array(), $user_id = 0 )
	{
		global $wpdb, $wp_locale;

		$author = $user_id ? $wpdb->prepare( "AND post_author = %d", $user_id ) : '';

		$extra_checks = "AND post_status != 'auto-draft'";

		if ( ! isset( $args['post_status'] )
			|| 'trash' !== $args['post_status'] )
				$extra_checks .= " AND post_status != 'trash'";

		else if ( isset( $args['post_status'] ) )
			$extra_checks = $wpdb->prepare( ' AND post_status = %s', $args['post_status'] );

		$query = $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			{$author}
			{$extra_checks}
			ORDER BY post_date DESC
		", $post_type );

		$key = md5( $query );
		$cache = wp_cache_get( 'wp_get_archives' , 'general' );

		if ( ! isset( $cache[$key] ) ) {
			$months = $wpdb->get_results( $query );
			$cache[$key] = $months;
			wp_cache_set( 'wp_get_archives', $cache, 'general' );
		} else {
			$months = $cache[$key];
		}

		$count = count( $months );
		if ( ! $count || ( 1 == $count && 0 == $months[0]->month ) )
			return FALSE;

		$list = array();

		foreach ( $months as $row ) {

			if ( 0 == $row->year )
				continue;

			$year  = $row->year;
			$month = zeroise( $row->month, 2 );

			$list[$year.$month] = sprintf( '%1$s %2$s', $wp_locale->get_month( $month ), $year );
		}

		return $list;
	}

	public static function currentPostType( $default = NULL )
	{
		global $post, $typenow, $pagenow, $current_screen;

		if ( $post && $post->post_type )
			return $post->post_type;

		if ( $typenow )
			return $typenow;

		if ( $current_screen && isset( $current_screen->post_type ) )
			return $current_screen->post_type;

		if ( isset( $_REQUEST['post_type'] ) )
			return sanitize_key( $_REQUEST['post_type'] );

		return $default;
	}

	public static function updateCountCallback( $terms, $taxonomy )
	{
		global $wpdb;

		foreach ( (array) $terms as $term ) {

			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

			do_action( 'edit_term_taxonomy', $term, $taxonomy );

			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}

	public static function redirect( $location = NULL, $status = 302 )
	{
		if ( is_null( $location ) )
			$location = add_query_arg( wp_get_referer() );

		wp_redirect( $location, $status );
		exit();
	}

	public static function redirectReferer( $message = 'updated', $key = 'message' )
	{
		if ( is_array( $message ) )
			$url = add_query_arg( $message, wp_get_referer() );
		else
			$url = add_query_arg( $key, $message, wp_get_referer() );

		self::redirect( $url );
	}

	public static function redirectLogin( $location = '', $status = 302 )
	{
		self::redirect( wp_login_url( $location, TRUE ), $status );
	}

	// @SEE: get_search_link()
	public static function getSearchLink( $query = FALSE )
	{
		if ( defined( 'GNETWORK_SEARCH_REDIRECT' ) && GNETWORK_SEARCH_REDIRECT )
			return $query ? add_query_arg( GNETWORK_SEARCH_QUERYID, urlencode( $query ), GNETWORK_SEARCH_URL ) : GNETWORK_SEARCH_URL;

		return $query ? add_query_arg( 's', urlencode( $query ), get_option( 'home' ) ) : get_option( 'home' );
	}

	// @SEE: get_edit_term_link()
	public static function getEditTaxLink( $taxonomy, $term_id = FALSE, $extra = array() )
	{
		if ( $term_id )
			return add_query_arg( array_merge( array(
				'taxonomy' => $taxonomy,
				'tag_ID'   => $term_id,
			), $extra ), admin_url( 'term.php' ) );

		else
			return add_query_arg( array_merge( array(
				'taxonomy' => $taxonomy,
			), $extra ), admin_url( 'edit-tags.php' ) );
	}

	public static function getPostTypeEditLink( $post_type, $user_id = 0, $extra = array() )
	{
		$query = array( 'post_type' => $post_type );

		if ( $user_id )
			$query['author'] = $user_id;

		return add_query_arg( array_merge( $query, $extra ), admin_url( 'edit.php' ) );
	}

	public static function getPostEditLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array( 'post' => $post_id, 'action' => 'edit' ), $extra ), admin_url( 'post.php' ) );
	}

	public static function getPostAttachmentsLink( $post_id, $extra = array() )
	{
		return add_query_arg( array_merge( array( 'post_parent' => $post_id ), $extra ), admin_url( 'upload.php' ) );
	}

	public static function getAuthorEditHTML( $post_type, $author, $extra = array() )
	{
		if ( $author_data = get_user_by( 'id', $author ) )
			return gEditorialHTML::tag( 'a', array(
				'href' => add_query_arg( array_merge( array(
					'post_type' => $post_type,
					'author'    => $author,
				), $extra ), admin_url( 'edit.php' ) ),
				'title' => $author_data->user_login,
				'class' => '-author',
			), esc_html( $author_data->display_name ) );

		return FALSE;
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPDatabase extends gEditorialBaseCore
{

	public static function getResults( $query, $output = OBJECT, $key = 'default', $group = 'geditorial' )
	{
		global $wpdb;

		$sub = md5( $query );

		$cache = wp_cache_get( $key, $group );

		if ( isset( $cache[$sub] ) )
			return $cache[$sub];

		$cache[$sub] = $wpdb->get_results( $query, $output );

		wp_cache_set( $key, $cache, $group );

		return $cache[$sub];
	}

	public static function getTaxonomies( $same_key = FALSE )
	{
		global $wpdb;

		$taxonomies = $wpdb->get_col( "
			SELECT taxonomy
			FROM $wpdb->term_taxonomy
			GROUP BY taxonomy
			ORDER BY taxonomy ASC
		" );

		return $same_key ? gEditorialArraay::sameKey( $taxonomies ) : $taxonomies;
	}

	// @SOURCE: [Custom Field Taxonomies](https://github.com/scribu/wp-custom-field-taxonomies)
	public static function getPostMetaRows( $meta_key, $limit = FALSE )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				GROUP BY post_id
				LIMIT %d
			", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "
				SELECT post_id, GROUP_CONCAT( meta_value ) as meta
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				GROUP BY post_id
			", $meta_key );

		return $wpdb->get_results( $query );
	}

	// @SOURCE: [Custom Field Taxonomies](https://github.com/scribu/wp-custom-field-taxonomies)
	public static function getPostMetaKeys( $same_key = FALSE )
	{
		global $wpdb;

		$meta_keys = $wpdb->get_col( "
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			HAVING meta_key NOT LIKE '\_%'
			ORDER BY meta_key ASC
		" );

		return $same_key ? gEditorialArraay::sameKey( $meta_keys ) : $meta_keys;
	}

	// @SEE: delete_post_meta_by_key( 'related_posts' );
	public static function deletePostMeta( $meta_key, $limit = FALSE )
	{
		global $wpdb;

		if ( $limit )
			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s LIMIT %d", $meta_key, $limit );
		else
			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key );

		return $wpdb->query( $query );
	}

	public static function deleteEmptyMeta( $meta_key )
	{
		global $wpdb;

		$query = $wpdb->prepare( "
			DELETE FROM $wpdb->postmeta
			WHERE meta_key = %s
			AND meta_value = ''
		" , $meta_key );

		return $wpdb->get_results( $query );
	}

	// @REF: https://core.trac.wordpress.org/ticket/29181
	public static function countPostsByNotTaxonomy( $taxonomy, $post_types = array( 'post' ), $user_id = 0 )
	{
		$key = md5( 'not_'.$taxonomy.'_'.serialize( $post_types ).'_'.$user_id );
		$counts = wp_cache_get( $key, 'counts' );

		if ( FALSE !== $counts )
			return $counts;

		global $wpdb;

		$counts = array_fill_keys( $post_types, 0 );

		$post_types_in = implode( ',', array_map( function( $v ){
			return "'".esc_sql( $v )."'";
		}, $post_types ) );

		$author = $user_id ? $wpdb->prepare( "AND posts.post_author = %d", $user_id ) : '';

		$query = $wpdb->prepare( "SELECT posts.post_type, COUNT( * ) AS total
			FROM {$wpdb->posts} AS posts
			WHERE posts.post_type IN ( {$post_types_in} )
			{$author}
			AND NOT EXISTS ( SELECT 1
				FROM {$wpdb->term_relationships}
				INNER JOIN {$wpdb->term_taxonomy}
				ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id
				WHERE {$wpdb->term_taxonomy}.taxonomy = %s
				AND {$wpdb->term_relationships}.object_id = posts.ID
			)
			GROUP BY posts.post_type
		", $taxonomy );

		foreach ( (array) $wpdb->get_results( $query, ARRAY_A ) as $row )
			$counts[$row['post_type']] = $row['total'];

		wp_cache_set( $key, $counts, 'counts' );

		return $counts;
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

		$results = self::getResults( $query, ARRAY_A, 'counts' );

		foreach ( (array) $results as $row )
			$counts[$row['post_status']] = $row['total'];

		return $counts;
	}

	public static function countPostsByUser( $user_id = NULL, $args = array(), $period = array() )
	{
		global $wpdb;

		$counts = array();
		$from   = $to = '';

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		$extra_checks = "AND post_status != 'auto-draft'";

		if ( ! isset( $args['post_status'] )
			|| 'trash' !== $args['post_status'] )
				$extra_checks .= " AND post_status != 'trash'";

		else if ( isset( $args['post_status'] ) )
			$extra_checks = $wpdb->prepare( 'AND post_status = %s', $args['post_status'] );

		if ( ! empty( $period[0] ) )
			$from = $wpdb->prepare( "AND post_date >= '%s'", $period[0] );

		if ( ! empty( $period[1] ) )
			$to = $wpdb->prepare( "AND post_date <= '%s'", $period[1] );

		$query = $wpdb->prepare( "
			SELECT post_type, COUNT( * ) AS total
			FROM {$wpdb->posts}
			WHERE post_author = %d
			{$extra_checks}
			{$from}
			{$to}
			GROUP BY post_type
		", $user_id );

		$results = self::getResults( $query, ARRAY_A, 'counts' );

		foreach ( (array) $results as $row )
			$counts[$row['post_type']] = $row['total'];

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
			$extra_checks = $wpdb->prepare( 'AND post_status = %s', $args['post_status'] );

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
			$month = gEditorialNumber::zeroise( $row->month, 2 );

			$list[$year.$month] = sprintf( '%1$s %2$s', $wp_locale->get_month( $month ), $year );
		}

		return $list;
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
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWordPress extends gEditorialBaseCore
{

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

	public static function count_posts_by_author()
	{

	}

	public static function count_posts_by_date()
	{

	}
}

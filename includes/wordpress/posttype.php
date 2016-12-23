<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWPPostType extends gEditorialBaseCore
{

	public static function getIDbySlug( $slug, $post_type, $url = FALSE )
	{
		static $strings = array();

		if ( $url ) {
			$slug = rawurlencode( urldecode( $slug ) );
			$slug = sanitize_title( basename( $slug ) );
		}

		$slug = trim( $slug );

		if ( isset( $strings[$post_type][$slug] ) )
			return $strings[$post_type][$slug];

		global $wpdb;

		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s",
				$slug,
				$post_type
			)
		);

		if ( is_array( $post_id ) )
			return $strings[$post_type][$slug] = $post_id[0];

		else if ( ! empty( $post_id ) )
			return $post_id;

		return $strings[$post_type][$slug] = FALSE;
	}
}

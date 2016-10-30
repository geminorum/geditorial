<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialCache extends gEditorialBaseCore
{

	public static function getResultsDB( $query, $output = OBJECT, $key = 'default', $group = 'geditorial' )
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
}

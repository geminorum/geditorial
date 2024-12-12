<?php namespace geminorum\gEditorial\Modules\Audit;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleHelper extends gEditorial\Helper
{

	const MODULE = 'audit';

	// TODO: add setting/filter for this
	public static function getAttributeSlug( $for, $default = FALSE )
	{
		switch ( $for ) {
			case 'empty_title': return 'title-empty';
			case 'empty_content': return 'text-empty';
			case 'empty_excerpt': return 'excerpt-empty';
		}

		return $default;
	}

	// @REF: https://wordpress.stackexchange.com/a/251385
	// @REF: https://stackoverflow.com/a/16395673
	public static function getPostsEmpty( $for, $attribute, $posttypes = NULL, $check_taxonomy = TRUE )
	{
		switch ( $for ) {
			case 'title':   case 'empty_title':   $callback = [ __CLASS__, 'whereEmptyTitle' ];   break;
			case 'content': case 'empty_content': $callback = [ __CLASS__, 'whereEmptyContent' ]; break;
			case 'excerpt': case 'empty_excerpt': $callback = [ __CLASS__, 'whereEmptyExcerpt' ]; break;
			default: return FALSE;
		}

		$args = [
			'post_type'   => $posttypes ?? 'any',
			'post_status' => WordPress\Status::acceptable( $posttypes ),

			'orderby' => 'none',
			'fields'  => 'ids',

			'posts_per_page'         => -1,
			'no_found_rows'          => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		if ( $check_taxonomy )
			$args['tax_query'] = [ [
				'taxonomy' => self::constant( 'main_taxonomy', 'audit_attribute' ),
				'terms'    => $attribute,
				'field'    => 'slug',
				'operator' => 'NOT IN',
			] ];

		add_filter( 'posts_where', $callback, 9999 );

		$query = new \WP_Query();
		$posts = $query->query( $args );

		remove_filter( 'posts_where', $callback, 9999 );

		return $posts;
	}

	public static function whereEmptyTitle( $where )
	{
		global $wpdb;
		return $where.= " AND (TRIM(COALESCE({$wpdb->posts}.post_title, '')) = '') ";
	}

	public static function whereEmptyContent( $where )
	{
		global $wpdb;
		return $where.= " AND (TRIM(COALESCE({$wpdb->posts}.post_content, '')) = '') ";
	}

	public static function whereEmptyExcerpt( $where )
	{
		global $wpdb;
		return $where.= " AND (TRIM(COALESCE({$wpdb->posts}.post_excerpt, '')) = '') ";
	}
}

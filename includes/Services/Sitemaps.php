<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Sitemaps extends WordPress\Main
{

	const BASE = 'geditorial';

	const VIEWABLE_TAXONOMY_PROP  = 'sitemaps_viewable';
	const VIEWABLE_POSTTYPE_PROP  = 'sitemaps_viewable'; // TODO

	public static function setup()
	{
		add_filter( 'wp_sitemaps_taxonomies_query_args', [ __CLASS__, 'taxonomies_query_args' ], 9, 2 );
	}

	/**
	 * Changes sitemap query arguments to show empty terms from
	 * configured taxonomies.
	 *
	 * NOTE: also see: `hook_taxonomy_sitemap_show_empty`
	 *
	 * @param  array  $args
	 * @param  string $taxonomy
	 * @return array  $filtered
	 */
	public static function taxonomies_query_args( $args, $taxonomy )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return $args;

		if ( ! empty ( $object->{self::VIEWABLE_TAXONOMY_PROP} ) )
			$args['hide_empty'] = FALSE;

		return $args;
	}
}

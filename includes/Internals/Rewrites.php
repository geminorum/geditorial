<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait Rewrites
{

	protected function rewrites__get_queryvar( $constant_prefix = 'main' )
	{
		return $this->constant( $constant_prefix.'_queryvar' );
	}

	protected function rewrites__get_endpoint( $constant_prefix = 'main', $query = NULL )
	{
		return $this->constant( $constant_prefix.'_endpoint',
			$query ?? $this->rewrites__get_queryvar( $constant_prefix )
		);
	}

	// $constant_prefix.'_endpoint' => 'endpoint',
	// $constant_prefix.'_queryvar' => 'endpoint',
	// @SEE: on taxonomies: https://core.trac.wordpress.org/ticket/33728
	protected function rewrites__add_endpoint( $constant_prefix = 'main' )
	{
		if ( ! $query = $this->rewrites__get_queryvar( $constant_prefix ) )
			return FALSE;

		$endpoint = $this->rewrites__get_endpoint( $constant_prefix, $query );

		add_rewrite_endpoint(
			$endpoint,
			EP_PERMALINK | EP_PAGES,
			$query
		);

		$this->rewrites__endpoint_verbose_page( $constant_prefix, $query, $endpoint );

		$this->filter_append( 'query_vars', $query );
	}

	// @source `View All Posts Pages` 0.9.4 by `Erick Hitter`
	// @link https://wordpress.org/plugins/view-all-posts-pages/
	// NOTE: Extra rules needed if verbose page rules are requested.
	protected function rewrites__endpoint_verbose_page( $constant_prefix = 'main', $query = NULL, $endpoint = NULL )
	{
		global $wp_rewrite;

		if ( ! $wp_rewrite->use_verbose_page_rules )
			return;

		$query    = $query    ?? $this->rewrites__get_queryvar( $constant_prefix );
		$endpoint = $endpoint ?? $this->rewrites__get_endpoint( $constant_prefix, $query );

		// Build regex.
		$regex  = substr( str_replace( $wp_rewrite->rewritecode, $wp_rewrite->rewritereplace, $wp_rewrite->permalink_structure ), 1 );
		$regex  = trailingslashit( $regex );
		$regex .= $endpoint.'/?$';

		// Build corresponding query string.
		$string = substr( str_replace( $wp_rewrite->rewritecode, $wp_rewrite->queryreplace, $wp_rewrite->permalink_structure ), 1 );
		$string = explode( '/', $string );
		$string = array_filter( $string );

		$i = 1;

		foreach ( $string as $key => $qv ) {
			$string[$key].= '$matches['.$i.']';
			$i++;
		}

		$string[] = $query.'=1';

		$string = implode( '&', $string );

		add_rewrite_rule(
			$regex,
			$wp_rewrite->index.'?'.$string,
			'top'
		);
	}

	protected function rewrites__add_tag( $constant_prefix = 'main', $posttypes = NULL )
	{
		if ( ! $query = $this->constant( $constant_prefix.'_queryvar' ) )
			return FALSE;

		$this->filter_append( 'query_vars', $query );

		add_rewrite_tag( '%'.$query.'%', '([^&]+)' );
		add_rewrite_rule( '^'.$query.'/([^/]*)/?', 'index.php?'.$query.'=$matches[1]', 'top' );

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( \is_string( $posttypes ) )
			$posttypes = $this->constant( $posttypes, [] );

		else if ( ! $posttypes )
			return $query;

		foreach ( (array) $posttypes as $posttype )
			add_rewrite_rule(
				'^'.$posttype.'/'.$query.'/([^/]*)/?',
				'index.php?post_type='.$posttype.'&'.$query.'=$matches[1]',
				'top'
			);

		return $query;
	}
}

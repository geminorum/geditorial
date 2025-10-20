<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait Rewrites
{

	// $constant_prefix.'_endpoint' => 'endpoint',
	// $constant_prefix.'_queryvar' => 'endpoint',
	// @SEE: on taxonomies: https://core.trac.wordpress.org/ticket/33728
	protected function rewrites__add_endpoint( $constant_prefix = 'main' )
	{
		if ( ! $query = $this->constant( $constant_prefix.'_queryvar' ) )
			return FALSE;

		add_rewrite_endpoint(
			$this->constant( $constant_prefix.'_endpoint', $query ),
			EP_PERMALINK | EP_PAGES,
			$query
		);

		$this->filter_append( 'query_vars', $query );
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

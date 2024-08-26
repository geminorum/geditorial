<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait RewriteEndpoint
{

	// $constant_prefix.'_endpoint' => 'endpoint',
	// $constant_prefix.'_queryvar' => 'endpoint',
	protected function rewriteendpoint__add( $constant_prefix = 'main' )
	{
		add_rewrite_endpoint(
			$this->constant( $constant_prefix.'_endpoint' ),
			EP_PERMALINK | EP_PAGES,
			$this->constant( $constant_prefix.'_queryvar' )
		);

		$this->filter_append( 'query_vars', $this->constant( $constant_prefix.'_queryvar' ) );
	}
}

<?php namespace geminorum\gEditorial\Templates;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class Terms extends gEditorial\Template
{

	const MODULE = 'terms';

	// FIXME: write and move it up!
	public static function termImage( $atts = [], $module = NULL )
	{
		// Taxonomy::getFeaturedImageHTML( $term_id, $size )
	}
}

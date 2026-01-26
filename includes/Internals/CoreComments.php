<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreComments
{
	protected function comments__handle_default_status( $posttype, $fallback = NULL, $comment_types = NULL, $setting = NULL )
	{
		add_filter( 'get_default_comment_status',
			function ( $status, $_posttype, $_comment_type )
				use ( $posttype, $comment_types, $fallback, $setting ) {

				if ( $posttype !== $_posttype )
					return $status;

				if ( ! in_array( $_comment_type, $comment_types ?? [ 'comment', 'pingback', 'trackback' ], TRUE ) )
					return $status;

				return $this->get_setting(
					$setting ?? 'comment_status',
					$fallback ?? 'closed'
				);

			}, 12, 3 );
	}
}

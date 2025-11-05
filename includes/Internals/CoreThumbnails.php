<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreThumbnails
{
	// TODO: same for term with image support on Terms module
	protected function corethumbnails__hook_tabloid_side_image( $constant )
	{
		add_filter( $this->hook_base( 'tabloid', 'view_data_for_post' ),
			function ( $data, $post, $context ) use ( $constant ) {

				if ( ! $this->is_posttype( $constant, $post ) )
					return $data;

				if ( ! WordPress\Post::can( $post, 'read_post' ) )
					return $data;

				$html = gEditorial\Template::postImage( [
					'id'     => $post,
					'link'   => 'edit',
					'newtab' => TRUE,
					'echo'   => FALSE,
					'wrap'   => FALSE,
				], $this->key );

				if ( $html )
					$data['___sides']['meta'].= Core\HTML::wrap( $html, '-side-image' );

				return $data;

			}, 20, 3 );

		return TRUE;
	}
}

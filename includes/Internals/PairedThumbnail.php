<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedThumbnail
{
	protected function _hook_paired_thumbnail_fallback( $posttypes = NULL )
	{
		if ( ! $this->_paired )
			return;

		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		if ( ! $this->get_setting( 'thumbnail_fallback', FALSE ) )
			return;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		// NOTE: this is a core filter @since WP 5.9.0
		add_filter( 'post_thumbnail_id',
			function ( $thumbnail_id, $post ) use ( $posttypes ) {
				return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post, $posttypes );
			}, 8, 2 );
	}

	protected function get_paired_fallback_thumbnail_id( $thumbnail_id, $post, $posttypes = NULL )
	{
		if ( $thumbnail_id || FALSE === $post )
			return $thumbnail_id;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( ! in_array( get_post_type( $post ), $posttypes, TRUE ) )
			return $thumbnail_id;

		if ( ! $parent = $this->get_linked_to_posts( $post, TRUE ) )
			return $thumbnail_id;

		if ( $parent_thumbnail = get_post_thumbnail_id( $parent ) )
			return $parent_thumbnail;

		return $thumbnail_id;
	}
}

<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Attachment extends Core\Base
{

	/**
	 * Retrieves attachments for a parent post given mime type.
	 * @old `Media::getAttachments()`
	 *
	 * @param  null|int|object $parent
	 * @param  string $mime
	 * @param  mixed $fallback
	 * @return array $attachments
	 */
	public static function get( $parent = NULL, $mime = 'image', $fallback = [] )
	{
		if ( ! $post = Post::get( $parent ) )
			return $fallback;

		return get_children( [
			'post_mime_type' => $mime,
			'post_parent'    => $post->ID,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'numberposts'    => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		] );
	}

	// public static function viewable( $attachment ) {}
	// public static function link( $attachment ) {}
	// public static function shortlink( $attachment ) {}
	// public static function summary( $attachment, $context = NULL ) {}
	// public static function select( $mime = NULL, $context = NULL ) {} // Media::selectAttachment();

	/**
	 * Retrieves mime type given a attachment ID or attachment object.
	 *
	 * @param  null|int|object $attachment
	 * @param  mixed $fallback
	 * @return string $mime
	 */
	public static function type( $attachment = NULL, $fallback = FALSE )
	{
		if ( $post = Post::get( $attachment ) )
			return $post->post_mime_type;

		return $fallback;
	}

	/**
	 * Retrieves attachment title given a attachment ID or attachment object.
	 *
	 * @param  null|int|object $attachment
	 * @param  null|string $fallback
	 * @param  bool   $filter
	 * @return string $title
	 */
	public static function title( $attachment = NULL, $fallback = NULL, $filter = TRUE )
	{
		return Post::title( $attachment, $fallback, $filter );
	}

	public static function caption( $attachment = NULL, $fallback = FALSE )
	{
		if ( $post = Post::get( $attachment ) )
			return wp_get_attachment_caption( $post->ID );

		return $fallback;
	}

	/**
	 * Retrieves attachment rest route given an attachment ID or attachment object.
	 *
	 * @param  null|int|object $post
	 * @return false|string $route
	 */
	public static function getRestRoute( $post = NULL )
	{
		return Post::getRestRoute( $post );
	}
}

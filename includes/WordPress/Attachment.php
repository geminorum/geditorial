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
	 * @return string $mime
	 */
	public static function type( $attachment = NULL )
	{
		if ( $post = Post::get( $attachment ) )
			return $post->post_mime_type;

		return FALSE;
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
		if ( ! $post = Post::get( $attachment ) )
			return '';

		$title = $filter ? apply_filters( 'the_title', $post->post_title, $post->ID ) : $post->post_title;

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return __( '(Untitled)' );

		return $fallback;
	}

	/**
	 * Retrieves post rest route given a post ID or post object.
	 *
	 * @param  null|int|object $post
	 * @return false|string $route
	 */
	public static function getRestRoute( $post = NULL )
	{
		if ( ! $post = Post::get( $post ) )
			return FALSE;

		if ( ! $object = PostType::object( $post ) )
			return FALSE;

		if ( ! $object->show_in_rest )
			return FALSE;

		return sprintf( '/%s/%s/%d', $object->rest_namespace, $object->rest_base, $post->ID );
	}
}

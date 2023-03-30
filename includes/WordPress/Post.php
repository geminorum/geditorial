<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Post extends Core\Base
{

	/**
	 * Retrieves post data given a post ID or post object.
	 *
	 * simplified `get_post()`
	 * @old `PostType::getPost()`
	 *
	 * @param  null|int|object $post
	 * @param  string $output
	 * @param  string $filter
	 * @return object $post
	 */
	public static function get( $post = NULL, $output = OBJECT, $filter = 'raw' )
	{
		if ( $post instanceof \WP_Post )
			return $post;

		// handling dummy posts!
		if ( '-9999' == $post )
			$post = NULL;

		return get_post( $post, $output, $filter );
	}

	/**
	 * Retrieves a post given its title.
	 *
	 * @see `get_page_by_title()`
	 * @source https://make.wordpress.org/core/2023/03/06/get_page_by_title-deprecated/
	 *
	 * @param  string $title
	 * @param  string $posttype
	 * @return mixed $posts
	 */
	public static function getIDsByTitle( $title, $posttype = 'post', $fields = 'ids' )
	{
		$args = [
			'title'          => $title,
			'fields'         => $fields,
			'post_type'      => $posttype,
			'post_status'    => 'all',
			'orderby'        => 'post_date ID',
			'order'          => 'ASC',
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'ignore_sticky_posts'    => TRUE,
			'update_post_term_cache' => FALSE,
			'update_post_meta_cache' => FALSE,
		];

		$query = new \WP_Query();
		return $query->query( $args );
	}

	/**
	 * Updates the posttype for the post.
	 *
	 * also accepts post and posttype objects
	 * and checks if its a different posttype
	 *
	 * @source `set_post_type()`
	 *
	 * @param  int|object $post
	 * @param  string|object $posttype
	 * @return bool $success
	 */
	public static function setPostType( $post, $posttype )
	{
		global $wpdb;

		if ( ! $posttype = PostType::object( $posttype ) )
			return FALSE;

		if ( ! $post = self::get( $post ) )
			return FALSE;

		if ( $posttype->name === $post->post_type )
			return TRUE;

		$success = $wpdb->update( $wpdb->posts,
			[ 'post_type' => sanitize_post_field( 'post_type', $posttype->name, $post->ID, 'db' ) ],
			[ 'ID'        => $post->ID ]
		);

		clean_post_cache( $post );

		return $success;
	}
}

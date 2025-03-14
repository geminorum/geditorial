<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class CustomPostTypes extends WordPress\Main
{

	const BASE = 'geditorial';

	/**
	 * Switches post-type with PAIRED API support
	 *
	 * @param  int|object $post
	 * @param  string|object $posttype
	 * @return int|false $changed
	 */
	public static function switchType( $post, $posttype )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( $posttype->name === $post->post_type )
			return TRUE;

		$paired_from = Services\Paired::isPostType( $post->post_type );
		$paired_to   = Services\Paired::isPostType( $posttype->name );

		// neither is paired
		if ( ! $paired_from && ! $paired_to )
			return WordPress\Post::setPostType( $post, $posttype );

		// bail if paired term not defined
		if ( ! $term = Services\Paired::getToTerm( $post->ID, $post->post_type, $paired_from ) )
			return WordPress\Post::setPostType( $post, $posttype );

		// NOTE: the `term_id` remains intact
		if ( ! WordPress\Term::setTaxonomy( $term, $paired_to ) )
			return FALSE;

		if ( ! WordPress\Post::setPostType( $post, $posttype ) )
			return FALSE;

		delete_post_meta( $post->ID, '_'.$post->post_type.'_term_id' );
		delete_term_meta( $term->term_id, $post->post_type.'_linked' );

		update_post_meta( $post->ID, '_'.$posttype->name.'_term_id', $term->term_id );
		update_term_meta( $term->term_id, $posttype->name.'_linked', $post->ID );

		return TRUE;
	}
}

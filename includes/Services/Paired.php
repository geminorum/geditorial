<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\Post;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Paired extends Main
{

	const BASE = 'geditorial';

	const PAIRED_TAXONOMY_PROP = 'paired_taxonomy';
	const PAIRED_POSTTYPE_PROP = 'paired_posttype';

	// public static function setup() {}

	// returns the paired taxonomy, otherwise `FALSE`
	public static function isPostType( $posttype )
	{
		if ( ! $posttype = PostType::object( $posttype ) )
			return FALSE;

		return empty( $posttype->{self::PAIRED_TAXONOMY_PROP} ) ? FALSE : $posttype->{self::PAIRED_TAXONOMY_PROP};
	}

	// returns the paired posttype, otherwise `FALSE`
	public static function isTaxonomy( $taxonomy )
	{
		if ( ! $taxonomy = Taxonomy::object( $taxonomy ) )
			return FALSE;

		return empty( $taxonomy->{self::PAIRED_POSTTYPE_PROP} ) ? FALSE : $taxonomy->{self::PAIRED_POSTTYPE_PROP};
	}

	public static function getPostTypes()
	{
		$list = [];

		foreach ( get_post_types( [ '_builtin' => FALSE ] ) as $posttype )
			if ( $paired = self::isPostType( $posttype ) )
				$list[$posttype] = $paired;

		return apply_filters( sprintf( '%s_paired_posttypes', static::BASE ), $list );
	}

	// OLD: `paired_get_to_term_direct()`
	public static function getToTerm( $post, $posttype, $taxonomy )
	{
		if ( empty( $post ) || ( ! $post = Post::get( $post ) ) )
			return FALSE;

		if ( ! $term_id = get_post_meta( $post->ID, sprintf( '_%s_term_id', $posttype ), TRUE ) )
			return FALSE;

		return get_term_by( 'id', (int) $term_id, $taxonomy );
	}
}

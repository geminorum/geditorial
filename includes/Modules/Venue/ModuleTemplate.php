<?php namespace geminorum\gEditorial\Modules\Venue;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress\PostType;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'venue';

	public static function summary( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'place_cpt', 'place' );

		return self::metaSummary( $atts );
	}

	public static function theCover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::cover( $atts );
	}

	public static function cover( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'place_cpt', 'place' );

		return parent::postImage( $atts, static::MODULE );
	}

	// TODO: add shortcode for this
	public static function map( $atts = [] )
	{
		if ( ! array_key_exists( 'default', $atts ) )
			$atts['default'] = FALSE;

		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		if ( ! $post = PostType::getPost( $atts['id'] ) )
			return $atts['default'];

		if ( $post->post_type === self::constant( 'place_cpt', 'place' ) )
			return self::metaField( 'map_embed_url', array_merge( $atts, [
				'fallback' => 'content_embed_url',
				// 'filter'   => '__do_embed_shortcode', // NO NEED: filtering the raw meta
			] ) );

		if ( ! gEditorial()->module( static::MODULE )->posttype_supported( $post->post_type ) )
			return $atts['default'];

		if ( ! $post_id = gEditorial()->module( static::MODULE )->get_linked_to_posts( $post, TRUE ) )
			return $atts['default'];

		return self::metaField( 'map_embed_url', array_merge( $atts, [
			'id'       => $post_id,
			'fallback' => 'content_embed_url',
			// 'filter'   => '__do_embed_shortcode', // NO NEED: filtering the raw meta
		] ) );
	}
}

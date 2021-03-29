<?php namespace geminorum\gEditorial\Templates;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress\PostType;

class Collect extends gEditorial\Template
{

	const MODULE = 'collect';

	public static function getLatestCollectionID()
	{
		return PostType::getLastMenuOrder( self::constant( 'collection_cpt', 'collection' ), '', 'ID', 'publish' );
	}

	public static function theCollection( $atts = [] )
	{
		if ( ! array_key_exists( 'item_title_cb', $atts ) )
			$atts['item_title_cb'] = [ __CLASS__, 'theCollectionTitleCB' ];

		if ( ! array_key_exists( 'item_tag', $atts ) )
			$atts['item_tag'] = FALSE;

		return self::pairedLink( $atts, static::MODULE );
	}

	public static function theCollectionTitleCB( $post, $args = [] )
	{
		return trim( strip_tags( self::getMetaField( 'number', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) ) );
	}

	public static function theCollectionMeta( $field = 'number', $atts = [] )
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$meta = self::getMetaField( $field, $atts );

		if ( ! $atts['echo'] )
			return $meta;

		echo $meta;
		return TRUE;
	}

	public static function thePoster( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::poster( $atts );
	}

	public static function poster( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'collection_cpt', 'collection' );

		return parent::postImage( $atts, static::MODULE );
	}
}

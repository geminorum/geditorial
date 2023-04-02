<?php namespace geminorum\gEditorial\Modules\Dossier;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress\PostType;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'dossier';

	public static function getLatestDossierID()
	{
		return PostType::getLastMenuOrder( self::constant( 'dossier_posttype', 'dossier' ), '', 'ID', 'publish' );
	}

	public static function theDossier( $atts = [] )
	{
		if ( ! array_key_exists( 'item_title_cb', $atts ) )
			$atts['item_title_cb'] = [ __CLASS__, 'theDossierTitleCB' ];

		if ( ! array_key_exists( 'item_tag', $atts ) )
			$atts['item_tag'] = FALSE;

		return self::pairedLink( $atts, static::MODULE );
	}

	public static function theDossierTitleCB( $post, $args = [] )
	{
		return trim( strip_tags( self::getMetaField( 'number_line', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) ) );
	}

	public static function theDossierMeta( $field = 'number_line', $atts = [] )
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$meta = self::getMetaField( $field, $atts );

		if ( ! $atts['echo'] )
			return $meta;

		echo $meta;
		return TRUE;
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
			$atts['type'] = self::constant( 'dossier_posttype', 'dossier' );

		return parent::postImage( $atts, static::MODULE );
	}

	public static function spanTiles( $atts = [] )
	{
		if ( ! array_key_exists( 'taxonomy', $atts ) )
			$atts['taxonomy'] = self::constant( 'span_taxonomy', 'dossier_span' );

		if ( ! array_key_exists( 'posttype', $atts ) )
			$atts['posttype'] = self::constant( 'dossier_posttype', 'dossier' );

		return parent::getSpanTiles( $atts, static::MODULE );
	}
}

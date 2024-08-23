<?php namespace geminorum\gEditorial\Modules\Directorate;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'directorate';

	public static function getLatestDepartmentID()
	{
		return WordPress\PostType::getLastMenuOrder( self::constant( 'primary_posttype', 'committee' ), '', 'ID', 'publish' );
	}

	public static function theDepartment( $atts = [] )
	{
		if ( ! array_key_exists( 'item_title_cb', $atts ) )
			$atts['item_title_cb'] = [ __CLASS__, 'theCommitteeTitleCB' ];

		if ( ! array_key_exists( 'item_tag', $atts ) )
			$atts['item_tag'] = FALSE;

		return self::pairedLink( $atts, static::MODULE );
	}

	public static function theCommitteeTitleCB( $post, $args = [] )
	{
		return trim( strip_tags( self::getMetaField( 'sub_title', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) ) );
	}

	public static function theBadge( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = NULL;

		return self::badge( $atts );
	}

	public static function badge( $atts = [] )
	{
		if ( ! array_key_exists( 'id', $atts ) )
			$atts['id'] = 'paired';

		if ( ! array_key_exists( 'type', $atts ) )
			$atts['type'] = self::constant( 'primary_posttype', 'department' );

		return parent::postImage( $atts, static::MODULE );
	}
}

<?php namespace geminorum\gEditorial\Modules\Course;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'course';

	public static function getLatestCourseID()
	{
		return WordPress\PostType::getLastMenuOrder( self::constant( 'course_posttype', 'course' ), '', 'ID', 'publish' );
	}

	public static function theCourse( $atts = [] )
	{
		if ( ! array_key_exists( 'item_title_cb', $atts ) )
			$atts['item_title_cb'] = [ __CLASS__, 'theCourseTitleCB' ];

		if ( ! array_key_exists( 'item_tag', $atts ) )
			$atts['item_tag'] = FALSE;

		return self::pairedLink( $atts, static::MODULE );
	}

	public static function theCourseTitleCB( $post, $args = [] )
	{
		return Core\Text::stripTags( self::getMetaField( 'sub_title', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) );
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
			$atts['type'] = self::constant( 'course_posttype', 'course' );

		return parent::postImage( $atts, static::MODULE );
	}

	public static function spanTiles( $atts = [] )
	{
		if ( ! array_key_exists( 'taxonomy', $atts ) )
			$atts['taxonomy'] = self::constant( 'span_taxonomy', 'course_span' );

		if ( ! array_key_exists( 'posttype', $atts ) )
			$atts['posttype'] = self::constant( 'course_posttype', 'course' );

		return parent::getSpanTiles( $atts, static::MODULE );
	}
}

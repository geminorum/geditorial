<?php namespace geminorum\gEditorial\Modules\Course;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress\PostType;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'course';

	public static function getLatestCourseID()
	{
		return PostType::getLastMenuOrder( self::constant( 'course_cpt', 'course' ), '', 'ID', 'publish' );
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
		return trim( strip_tags( self::getMetaField( 'sub_title', [
			'id'      => $post->ID,
			'default' => $args['item_title'],
		] ) ) );
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
			$atts['type'] = self::constant( 'course_cpt', 'course' );

		return parent::postImage( $atts, static::MODULE );
	}
}

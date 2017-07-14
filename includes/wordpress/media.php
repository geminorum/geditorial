<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core;

class Media extends Core\Base
{

	// core dup with posttype/taxonomy/title
	// @REF: `add_image_size()`
	public static function registerImageSize( $name, $atts = array() )
	{
		global $_wp_additional_image_sizes;

		$args = self::atts( array(
			'n' => __( 'Untitled' ),
			'w' => 0,
			'h' => 0,
			'c' => 0,
			'p' => array( 'post' ),
			't' => FALSE, // taxonomy: TRUE: all/array: taxes/FALSE: none
		), $atts );

		$_wp_additional_image_sizes[$name] = array(
			'width'     => absint( $args['w'] ),
			'height'    => absint( $args['h'] ),
			'crop'      => $args['c'],
			'post_type' => $args['p'],
			'taxonomy'  => $args['t'],
			'title'     => $args['n'],
		);
	}

	// this must be core's
	// call this late on 'after_setup_theme' hook
	public static function themeThumbnails( $post_types )
	{
		global $_wp_theme_features;
		$feature = 'post-thumbnails';
		// $post_types = (array) $post_types;

		if ( isset( $_wp_theme_features[$feature] ) ) {

			// registered for all types
			if ( TRUE === $_wp_theme_features[$feature] ) {

				// WORKING: but if it is true, it's true!
				// $post_types[] = 'post';
				// $_wp_theme_features[$feature] = [ $post_types ];

			} else if ( is_array( $_wp_theme_features[$feature][0] ) ) {
				$_wp_theme_features[$feature][0] = array_merge( $_wp_theme_features[$feature][0], $post_types );
			}

		} else {
			$_wp_theme_features[$feature] = [ $post_types ];
		}
	}

	public static function getRegisteredImageSizes( $post_type = 'post', $key = 'post_type' )
	{
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( $_wp_additional_image_sizes as $name => $size )
			if ( isset( $size[$key] ) && in_array( $post_type, $size[$key] ) )
				$sizes[$name] = $size;
			else if ( 'post' == $post_type ) // fallback
				$sizes[$name] = $size;

		return $sizes;
	}

	// PDF: 'application/pdf'
	// MP3: 'audio/mpeg'
	// CSV: 'application/vnd.ms-excel'
	public static function selectAttachment( $selected = 0, $mime = NULL, $name = 'attach_id', $empty = '' )
	{
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'numberposts'    => -1,
			'post_status'    => NULL,
			'post_mime_type' => $mime,
			'post_parent'    => NULL,
		) );

		if ( ! count( $attachments ) ) {
			echo $empty;
			return FALSE;
		}

		echo Core\HTML::dropdown(
			Core\Arraay::reKey( $attachments, 'ID' ),
			array(
				'name'       => $name,
				'none_title' => Settings::showOptionNone(),
				'class'      => '-attachment',
				'selected'   => $selected,
				'prop'       => 'post_title',
			)
		);
	}
}

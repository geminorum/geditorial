<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core;

class Media extends Core\Base
{

	// TODO: get title if html is empty
	public static function htmlAttachmentShortLink( $id, $html, $extra = '', $rel = 'attachment' )
	{
		return Core\HTML::tag( 'a', [
			'href'  => Core\WordPress::getPostShortLink( $id ),
			'rel'   => $rel,
			'class' => Core\HTML::attrClass( $extra, '-attachment' ),
			'data'  => [ 'id' => $id ],
		], $html );
	}

	// WP default sizes from options
	public static function defaultImageSizes()
	{
		static $sizes = NULL;

		if ( ! is_null( $sizes ) )
			return $sizes;

		$sizes = [
			'thumbnail' => [
				'n' => __( 'Thumbnail' ),
				'w' => get_option( 'thumbnail_size_w' ),
				'h' => get_option( 'thumbnail_size_h' ),
				'c' => get_option( 'thumbnail_crop' ),
			],
			'medium' => [
				'n' => __( 'Medium' ),
				'w' => get_option( 'medium_size_w' ),
				'h' => get_option( 'medium_size_h' ),
				'c' => 0,
			],
			// 'medium_large' => [
			// 	'n' => __( 'Medium Large' ),
			// 	'w' => get_option( 'medium_large_size_w' ),
			// 	'h' => get_option( 'medium_large_size_h' ),
			// 	'c' => 0,
			// ],
			'large' => [
				'n' => __( 'Large' ),
				'w' => get_option( 'large_size_w' ),
				'h' => get_option( 'large_size_h' ),
				'c' => 0,
			],
		];

		return $sizes;
	}

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
			'p' => array( 'post' ), // posttype: TRUE: all/array: posttypes/FALSE: none
			't' => FALSE, // taxonomy: TRUE: all/array: taxes/FALSE: none
			'f' => empty( $atts['s'] ) ? FALSE : $atts['s'], // featured
		), $atts );

		$_wp_additional_image_sizes[$name] = array(
			'width'     => absint( $args['w'] ),
			'height'    => absint( $args['h'] ),
			'crop'      => $args['c'],
			'post_type' => $args['p'],
			'taxonomy'  => $args['t'],
			'title'     => $args['n'],
			'thumbnail' => $args['f'],
		);
	}

	// this must be core's
	// call this late on 'after_setup_theme' hook
	public static function themeThumbnails( $posttypes )
	{
		global $_wp_theme_features;

		$feature = 'post-thumbnails';

		if ( isset( $_wp_theme_features[$feature] ) ) {

			// registered for all types
			if ( TRUE === $_wp_theme_features[$feature] ) {

				// WORKING: but if it is true, it's true!
				// $posttypes[] = 'post';
				// $_wp_theme_features[$feature] = [ $posttypes ];

			} else if ( is_array( $_wp_theme_features[$feature][0] ) ) {
				$_wp_theme_features[$feature][0] = array_merge( $_wp_theme_features[$feature][0], $posttypes );
			}

		} else {
			$_wp_theme_features[$feature] = [ $posttypes ];
		}
	}

	// OLD: `getRegisteredImageSizes()`
	public static function getPosttypeImageSizes( $posttype = 'post', $fallback = FALSE )
	{
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( (array) $_wp_additional_image_sizes as $name => $args ) {

			if ( array_key_exists( 'post_type', $args ) ) {

				if ( is_array( $args['post_type'] ) ) {

					if ( in_array( $posttype, $args['post_type'] ) )
						$sizes[$name] = $args;

					else if ( is_string( $fallback ) && in_array( $fallback, $args['post_type'] ) )
						$sizes[$name] = $args;

				} else if ( $args['post_type'] ) {
					$sizes[$name] = $args;
				}

			} else if ( TRUE === $fallback ) {

				$sizes[$name] = $args;
			}
		}

		return $sizes;
	}

	public static function getTaxonomyImageSizes( $taxonomy = 'category', $fallback = FALSE )
	{
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( (array) $_wp_additional_image_sizes as $name => $args ) {

			if ( array_key_exists( 'taxonomy', $args ) ) {

				if ( is_array( $args['taxonomy'] ) ) {

					if ( in_array( $taxonomy, $args['taxonomy'] ) )
						$sizes[$name] = $args;

					else if ( is_string( $fallback ) && in_array( $fallback, $args['taxonomy'] ) )
						$sizes[$name] = $args;

				} else if ( $args['taxonomy'] ) {
					$sizes[$name] = $args;
				}

			} else if ( TRUE === $fallback ) {

				$sizes[$name] = $args;
			}
		}

		return $sizes;
	}

	// @REF: `wp_import_handle_upload()`
	public static function handleImportUpload( $name = 'import' )
	{
		if ( ! isset( $_FILES[$name] ) )
			return FALSE;

		$_FILES[$name]['name'].= '.txt';

		$upload = wp_handle_upload( $_FILES[$name], [ 'test_form' => FALSE, 'test_type' => FALSE ] );

		if ( isset( $upload['error'] ) )
			return FALSE; // $upload;

		$id = wp_insert_attachment( [
			'post_title'     => Core\File::basename( $upload['file'] ),
			'post_content'   => $upload['url'],
			'post_mime_type' => $upload['type'],
			'guid'           => $upload['url'],
			'context'        => 'import',
			'post_status'    => 'private',
		], $upload['file'] );

		// schedule a cleanup for one day from now in case of failed import or missing `wp_import_cleanup()` call
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', [ $id ] );

		return [ 'file' => $upload['file'], 'id' => $id ];
	}

	public static function handleSideload( $file, $post, $desc = NULL, $data = [] )
	{
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH.'wp-admin/includes/image.php';
			require_once ABSPATH.'wp-admin/includes/file.php';
			require_once ABSPATH.'wp-admin/includes/media.php';
		}

		return media_handle_sideload( $file, $post, $desc, $data );
	}

	public static function sideloadImageData( $name, $data, $post = 0, $extra = [] )
	{
		if ( ! $temp = Core\File::tempName( $name ) )
			return FALSE; // new WP_Error( 'http_no_file', __( 'Could not create Temporary file.' ) );

		if ( ! file_put_contents( $temp, $data ) )
			return FALSE;

		$file = [ 'name' => $name, 'tmp_name' => $temp ];

		$attachment = self::handleSideload( $file, $post, NULL, $extra );

		// if error storing permanently, unlink
		if ( is_wp_error( $attachment ) ) {
			@unlink( $file['tmp_name'] );
			return $attachment;
		}

		return $attachment;
	}

	// @REF: `media_sideload_image()`
	public static function sideloadImageURL( $url, $post = 0, $extra = [] )
	{
		if ( empty( $url ) )
			return FALSE;

		// filters the list of allowed file extensions when sideloading an image from a URL @since 5.6.0
		$extensions = apply_filters( 'image_sideload_extensions', [ 'jpg', 'jpeg', 'jpe', 'png', 'gif' ], $url );

		// set variables for storage, fix file filename for query strings
		preg_match( '/[^\?]+\.(' . implode( '|', array_map( 'preg_quote', $extensions ) ) . ')\b/i', $url, $matches );

		if ( ! $matches )
			return FALSE; // new WP_Error( 'image_sideload_failed', __( 'Invalid image URL.' ) );

		// download file to temp location
		$file = [ 'tmp_name' => download_url( $url ) ];

		// if error storing temporarily, return the error
		if ( is_wp_error( $file['tmp_name'] ) )
			return $file['tmp_name'];

		$file['name'] = Core\File::basename( $matches[0] );

		// do the validation and storage stuff
		$attachment = self::handleSideload( $file, $post, NULL, $extra );

		// if error storing permanently, unlink
		if ( is_wp_error( $attachment ) ) {
			@unlink( $file['tmp_name'] );
			return $attachment;
		}

		// store the original attachment source in meta
		add_post_meta( $attachment, '_source_url', $url );

		return $attachment;
	}

	public static function upload( $post = FALSE )
	{
		if ( FALSE === $post )
			return wp_upload_dir( NULL, FALSE, FALSE );

		if ( ! $post = get_post( $post ) )
			return wp_upload_dir( NULL, TRUE, FALSE );

		if ( 'page' === $post->post_type )
			return wp_upload_dir( NULL, TRUE, FALSE );

		return wp_upload_dir( ( substr( $post->post_date, 0, 4 ) > 0 ? $post->post_date : NULL ), TRUE, FALSE );
	}

	public static function getUploadDirectory( $sub = '', $create = FALSE, $htaccess = TRUE )
	{
		$upload = wp_upload_dir( NULL, FALSE, FALSE );

		if ( ! $sub )
			return $upload['basedir'];

		$folder = Core\File::join( $upload['basedir'], $sub );

		if ( $create ) {

			if ( ! is_dir( $folder ) || ! wp_is_writable( $folder ) ) {

				if ( $htaccess )
					Core\File::putHTAccessDeny( $folder, TRUE );
				else
					wp_mkdir_p( $folder );

			} else if ( $htaccess && ! file_exists( $folder.'/.htaccess' ) ) {

				Core\File::putHTAccessDeny( $folder, FALSE );
			}
		}

		return $folder;
	}

	public static function getUploadURL( $sub = '' )
	{
		$upload = wp_upload_dir( NULL, FALSE, FALSE );
		$base   = is_ssl() ? str_ireplace( 'http://', 'https://', $upload['baseurl'] ) : $upload['baseurl'];
		return $sub ? $base.'/'.$sub : $base;
	}

	public static function getAttachments( $post_id, $mime_type = 'image' )
	{
		return get_children( array(
			'post_mime_type' => $mime_type,
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'numberposts'    => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
	}

	public static function isCustom( $attachment_id )
	{
		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_header', TRUE ) )
			return 'custom_header';

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_custom_background', TRUE ) )
			return 'custom_background';

		if ( get_post_meta( $attachment_id, '_wp_attachment_is_term_image', TRUE ) )
			return 'term_image';

		if ( $attachment_id == get_option( 'site_icon' ) )
			return 'site_icon';

		if ( $attachment_id == get_theme_mod( 'site_logo' ) )
			return 'site_logo';

		return FALSE;
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

		if ( empty( $attachments ) ) {
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

	// @REF: https://pippinsplugins.com/retrieve-attachment-id-from-image-url/
	public static function getAttachmentByURL( $url )
	{
		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid='%s';", $url ) );

		return empty( $attachment ) ? NULL : $attachment[0];
	}

	// @REF: https://wordpress.stackexchange.com/a/315447
	public static function prepAttachmentData( $attachment_id )
	{
		if ( ! $attachment_id )
			return [];

		$uploads  = self::upload();
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$prepared = [
			'mime_type' => get_post_mime_type( $attachment_id ),
			'url'       => $uploads['baseurl'].'/'.$metadata['file'],
			'sizes'     => [],
		];

		foreach ( $metadata['sizes'] as $size => $info )
			$prepared['sizes'][$size] = $uploads['baseurl'].'/'.$info['file'];

		return $prepared;
	}
}

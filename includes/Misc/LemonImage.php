<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

/**
 * Image handling class.
 * Handles downscaling, upscaling, cropping, and creating retina images.
 * @source https://github.com/aristath/Lemon_Image
 *
 * `$image  = LemonImage::create( $image_url_or_id );`
 * `$resize = $image->resize( [ 'width' => 500, 'height' => 300  );`
 * `$new_url = $resize['url'];`
 *
 * - If you only define `width` or `height` the image will be resized using
 * the original ratio. The missing value will be auto-calculated.
 *
 * - If you define both width & height then the image will be resized & cropped
 * to the defined dimensions unless `crop` is set to `FALSE`.
 *
 * - If `retina` is set to `true` (default) then an extra file will be created
 * using the `@2x` suffix.
 *
 * @package Lemon_Image
 * @since 1.0.0
 */

class LemonImage extends Core\Base
{

	/**
	 * The image ID.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var int
	 */
	protected $id;

	/**
	 * The image URL.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var string
	 */
	protected $url;

	/**
	 * The image width.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var int
	 */
	protected $width;

	/**
	 * The image height.
	 *
	 * @access protected
	 * @since 1.0.0
	 * @var int
	 */
	protected $height;

	/**
	 * An array of instances.
	 *
	 * @static
	 * @access private
	 * @since 1.0.0
	 * @var array
	 */
	private static $instances = [];

	/**
	 * Constructor.
	 *
	 * @param int|string|array $image
	 * @return void
	 */
	private function __construct( $image )
	{
		$this->id = self::get_image_id( $image );

		$src = wp_get_attachment_image_src( $this->id, 'full' );

		$this->url    = $src[0];
		$this->width  = $src[1];
		$this->height = $src[2];
	}

	/**
	 * Gets an instance of this object.
	 *
	 * @param int|string|array $image
	 * @return object
	 */
	public static function create( $image )
	{
		$id = self::get_image_id( $image );

		if ( ! isset( self::$instances[$id] ) )
			self::$instances[$id] = new self( $id );

		return self::$instances[$id];
	}

	/**
	 * Gets an image ID from its URL.
	 *
	 * @param int|string|array $image
	 * @return int
	 */
	private static function get_image_id( $image )
	{
		global $wpdb;

		if ( is_numeric( $image ) )
			return (int) $image;

		// If we got this far then the $image is a URL.
		$attachment = $wpdb->get_col( $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE guid='%s';
		", $image ) );

		if ( $attachment && is_array( $attachment ) && isset( $attachment[0] ) )
			return (int) $attachment[0];

		return 0;
	}

	/**
	 * Returns the image ID.
	 *
	 * @return int
	 */
	public function get_id()
	{
		return $this->id;
	}

	/**
	 * Returns the image URL.
	 *
	 * @return string
	 */
	public function get_url()
	{
		return $this->url;
	}

	/**
	 *Returns the image width.
	 *
	 * @return int
	 */
	public function get_width()
	{
		return $this->width;
	}

	/**
	 * Returns the image height.
	 *
	 * @return int
	 */
	public function get_height()
	{
		return $this->height;
	}

	public function resize( $atts )
	{
		$args = wp_parse_args( $atts, [
			'url'       => $this->url,
			'width'     => '',
			'height'    => '',
			'crop'      => TRUE,
			'resize'    => TRUE,
			'retina'    => FALSE,
		] );

		if ( empty( $args['url'] ) )
			return;

		if ( FALSE !== $args['retina'] ) {

			// Default retina multiplier to 2.
			if ( TRUE === $args['retina'] )
				$args['retina'] = 2;

			// If (int) 1 is used, then assume we want the multiplier to be 1
			// therefore no retina image should be created.
			if ( 1 === $args['retina'] || '1' === $args['retina'] )
				$args['retina'] = FALSE;

			// If not a boolean, make sure value is an integer.
			if ( ! is_bool( $args['retina'] ) )
				$args['retina'] = absint( $args['retina'] );
		}

		// If width or height are not specified, auto-calculate.
		if ( empty( $args['width'] ) || empty( $args['height'] ) ) {

			if ( ! empty( $args['height'] ) ) {

				$args['width'] = $args['height'] * $this->width / $this->height;
				$args['width'] = (int) $args['width'];
				$args['width'] = ( 0 >= $args['width'] ) ? '' : $args['width'];

			} else if ( ! empty( $args['width'] ) ) {

				$args['height'] = $args['width'] * $this->height / $this->width;
				$args['height'] = (int) $args['height'];
				$args['height'] = ( 0 >= $args['height'] ) ? '' : $args['height'];
			}
		}

		// Generate the @2x file if retina is enabled
		if ( FALSE !== $args['retina'] )
			$results['retina'] = self::_resize(
				$args['url'],
				$args['width'],
				$args['height'],
				$args['crop'],
				$args['retina']
			);

		return self::_resize(
			$args['url'],
			$args['width'],
			$args['height'],
			$args['crop'],
			FALSE
		);
	}

	/**
	 * Resizes an image and returns an array containing the resized URL,
	 * width, height, and file type. Uses native WordPress functionality.
	 * NOTE: This is a slightly modified version of http://goo.gl/9iS0CO
	 *
	 * @param string $url The image URL.
	 * @param int $width The image width.
	 * @param int $height The image height.
	 * @param bool $crop If we want to crop the image or not.
	 * @param bool|int $retina If we want to generate a retina image or not.
	 *                         If an integer is used then it's used as a multiplier (@2x, @3x etc.).
	 * @return array An array containing the resized image URL, width, height, and file type.
	 */
	private static function _resize( $url, $width = NULL, $height = NULL, $crop = TRUE, $retina = FALSE )
	{
		global $wpdb;

		if ( empty( $url ) )
			return new \WP_Error( 'no_image_url', 'No image URL has been entered.', $url );

		// Get default size from database
		$width  = $width  ?: get_option( 'thumbnail_size_w' );
		$height = $height ?: get_option( 'thumbnail_size_h' );

		// Allow for different retina sizes
		$retina = FALSE === $retina ? 1 : $retina;
		$retina = TRUE  === $retina ? 2 : $retina;
		$retina = (int) $retina;

		// Get the image file path
		$file_path = parse_url( $url );
		$file_path = $_SERVER['DOCUMENT_ROOT'].$file_path['path'];

		// Destination width and height variables.
		// Multiplied by the $retina int.
		$dest_width  = $width * $retina;
		$dest_height = $height * $retina;

		// File name suffix (appended to original filename)
		$suffix_width  = $dest_width / $retina;
		$suffix_height = $dest_height / $retina;
		$suffix_retina = $retina != 1 ? ( '@'.$retina.'x' ) : NULL;
		$suffix = "{$suffix_width}x{$suffix_height}{$suffix_retina}";

		// Some additional info about the image
		$info = pathinfo( $file_path );
		$dir  = $info['dirname'];
		$ext  = '';

		if ( ! empty($info['extension'] ) )
			$ext = $info['extension'];

		$name = wp_basename( $file_path, ".$ext" );

		// Suffix applied to filename
		$suffix_width  = $dest_width / $retina;
		$suffix_height = $dest_height / $retina;
		$suffix_retina = $retina != 1 ? ( '@'.$retina.'x' ) : NULL;
		$suffix        = $suffix_width.'x'.$suffix_height.$suffix_retina;

		// Get the destination file name
		$dest_file_name = "{$dir}/{$name}-{$suffix}.{$ext}";

		if ( ! file_exists( $dest_file_name ) ) {

			/**
			 * Bail if this image isn't in the Media Library.
			 * We only want to resize Media Library images, so we can be sure they get deleted correctly when appropriate.
			**/
			$query          = $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE guid='%s'", $url );
			$get_attachment = $wpdb->get_results( $query );

			if ( ! $get_attachment )
				return [
					'url'    => $url,
					'width'  => $width,
					'height' => $height,
				];

			// Load WordPress Image Editor
			$editor = wp_get_image_editor( $file_path );

			if ( is_wp_error( $editor ) )
				return [
					'url'    => $url,
					'width'  => $width,
					'height' => $height,
				];

			// Get the original image size
			$size        = $editor->get_size();
			$orig_width  = $size['width'];
			$orig_height = $size['height'];

			$src_x = 0;
			$src_y = 0;
			$src_w = $orig_width;
			$src_h = $orig_height;

			if ( $crop ) {

				$cmp_x = $orig_width / $dest_width;
				$cmp_y = $orig_height / $dest_height;

				// Calculate x or y coordinate, and width or height of source
				if ( $cmp_x > $cmp_y ) {

					$src_w = round( $orig_width / $cmp_x * $cmp_y );
					$src_x = round( ( $orig_width - ( $orig_width / $cmp_x * $cmp_y ) ) / 2 );

				} else if ( $cmp_y > $cmp_x ) {

					$src_h = round( $orig_height / $cmp_y * $cmp_x );
					$src_y = round( ( $orig_height - ( $orig_height / $cmp_y * $cmp_x ) ) / 2 );
				}
			}

			// Time to crop the image!
			$editor->crop( $src_x, $src_y, $src_w, $src_h, $dest_width, $dest_height );

			// Now let's save the image.
			$saved = $editor->save( $dest_file_name );

			// Get resized image information.
			$resized_url    = str_replace( basename( $url ), basename( $saved['path'] ), $url );
			$resized_width  = $saved['width'];
			$resized_height = $saved['height'];
			$resized_type   = $saved['mime-type'];

			// Add the resized dimensions to original image metadata
			// so we can delete our resized images when the original image is deleted
			// from the Media Library.
			$metadata = wp_get_attachment_metadata( $get_attachment[0]->ID );

			if ( isset( $metadata['image_meta'] ) ) {
				$metadata['image_meta']['resized_images'][] = $resized_width.'x'.$resized_height;
				wp_update_attachment_metadata( $get_attachment[0]->ID, $metadata );
			}

			// Create the image array
			$image_array = [
				'url'    => $resized_url,
				'width'  => $resized_width,
				'height' => $resized_height,
				'type'   => $resized_type,
			];

		} else {

			$image_array = [
				'url'    => str_replace( basename( $url ), basename( $dest_file_name ), $url ),
				'width'  => $dest_width,
				'height' => $dest_height,
				'type'   => $ext,
			];
		}

		// Return image array
		return $image_array;
	}
}

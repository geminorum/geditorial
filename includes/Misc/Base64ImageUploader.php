<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Base64ImageUploader extends Core\Base
{
	/**
	 * The Base-64 payload.
	 *
	 * @var string
	 */
	private $data;

	/**
	 * The title that the attachment will have.
	 *
	 * @var string
	 */
	private $title;

	/**
	 * The image extension.
	 *
	 * @var string
	 */
	private $extension;

	/**
	 * The image type.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The allowed file-types.
	 *
	 * @var array
	 */
	private $types = [
		'image/png'  => 'png',
		'image/jpg'  => 'jpg',
		'image/jpeg' => 'jpeg',
	];

	/**
	 * Constructs a new `Base64ImageUploader` instance.
	 *
	 * @param string $data
	 * @param string $title
	 */
	public function __construct( $data, $title )
	{
		$this->data  = $data;
		$this->title = $title;

		$typeExt = $this->findTypeAndExtension();

		$this->extension = $typeExt['extension'];
		$this->type      = $typeExt['type'];
	}

	/**
	 * Uploads the image.
	 *
	 * @return int $attachment_id
	 */
	public function upload()
	{
		if ( ! $this->isBase64Image() )
			return new \WP_Error( 'image_type_invalid', 'The given file is not an image.' );

		$upload_dir      = wp_upload_dir();
		$upload_path     = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ).DIRECTORY_SEPARATOR;
		$decoded         = base64_decode($this->strippedData());
		$filename        = 'upload.'.$this->extension;
		$hashed_filename = md5( $filename.microtime() ).'_'.$filename;
		$image_upload    = file_put_contents( $upload_path.$hashed_filename, $decoded );

		// HANDLE UPLOADED FILE
		if ( ! function_exists( 'wp_handle_sideload' ) )
			require_once( ABSPATH . 'wp-admin/includes/file.php' );

		// Without that I'm getting a debug error!?
		if ( ! function_exists( 'wp_get_current_user' ) )
			require_once( ABSPATH . 'wp-includes/pluggable.php' );

		$file             = [];
		$file['error']    = '';
		$file['tmp_name'] = $upload_path.$hashed_filename;
		$file['name']     = $hashed_filename;
		$file['type']     = $this->type;
		$file['size']     = filesize( $upload_path.$hashed_filename );

		// Upload file to server
		// @new use $file instead of $image_upload
		$file_return = wp_handle_sideload( $file, [ 'test_form' => FALSE ] );
		$filename    = $file_return['file'];
		$attachment  = [
			'post_title'     => $this->title,
			'post_mime_type' => $file_return['type'],
			'post_title'     => preg_replace('/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $upload_dir['url'].'/'.basename($filename)
		];

		$attach_id = wp_insert_attachment( $attachment, $filename, 289 );

		require_once( ABSPATH.'wp-admin/includes/image.php' );

		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );

		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	/**
	 * Checks if the data is Base-64 image.
	 *
	 * @return boolean
	 */
	public function isBase64Image()
	{
		return ! empty( $this->findTypeAndExtension()['extension'] );
	}

	/**
	 * Checks if the data is of the given file-type.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public function isOfType( $type )
	{
		return 0 === strpos( $this->data, "data:{$type};base64" );
	}

	/**
	 * Finds the type and extension from the payload.
	 *
	 * @return array
	 */
	public function findTypeAndExtension()
	{
		foreach ( $this->types as $type => $extension )
			if ( $this->isOfType($type ) )
				return compact( 'type', 'extension' );

		return [
			'type'      => '',
			'extension' => '',
		];
	}

	/**
	 * Returns the base 64 data stripped.
	 *
	 * @return string
	 */
	public function strippedData()
	{
		return str_replace(
			' ',
			'+',
			str_replace(
				"data:{$this->type};base64,",
				'',
				$this->data
			)
		);
	}
}

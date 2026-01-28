<?php namespace geminorum\gEditorial\Modules\Uploader;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Uploader extends gEditorial\Module
{
	use Internals\CoreDashboard;

	public static function module()
	{
		return [
			'name'     => 'uploader',
			'title'    => _x( 'Uploader', 'Modules: Uploader', 'geditorial-admin' ),
			'desc'     => _x( 'Upload Large Files', 'Modules: Uploader', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'upload' ],
			'access'   => 'beta',
			'frontend' => FALSE,
			'keywords' => [
				'file-upload',
				'admin-widget',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_dashboard' => [
				'dashboard_widgets',
				[
					'field'       => 'form_before',
					'type'        => 'textarea-quicktags',
					'title'       => _x( 'Before Form', 'Setting Title', 'geditorial-uploader' ),
					'description' => _x( 'Message to display before contents on admin dashbaord widget.', 'Setting Description', 'geditorial-uploader' ),
				],
			],
			'_roles' => [
				'uploads_roles' => [ NULL, $this->get_settings_default_roles() ],
			],
		];
	}

	public function init()
	{
		parent::init();

		$this->action( 'post-plupload-upload-ui', 0, 12 );
	}

	public function dashboard_widgets()
	{
		if ( ! $this->role_can( 'uploads' ) )
			return;

		if ( $this->add_dashboard_widget( 'largefile', _x( 'Large File Uploader', 'Widget Title', 'geditorial-uploader' ), 'info' ) )
			$this->enqueue_asset_js( [], $this->dotted( 'largefile' ) );
	}

	// @REF: https://github.com/deliciousbrains/wp-dbi-file-uploader
	// @REF: https://deliciousbrains.com/?p=26646
	public function render_widget_largefile( $object, $box )
	{
		if ( $this->check_hidden_metabox( $box ) )
			return;

		Core\HTML::desc( $this->get_setting( 'form_before', '' ), TRUE, '-intro' );

		$html = '<form>'.Ajax::spinner();
		$html.= '<div id="'.$this->classs( 'largefile', 'progress' ).'" class="-messages">';
		$html.= _x( 'Please select a file and click &#8220;Upload&#8221; to continue.', 'Message', 'geditorial-uploader' );
		$html.= '</div>';

		$html.= '<div><label for="'.$this->classs( 'largefile', 'input' ).'" class="button button-small">';
		$html.= _x( 'Select File', 'Button', 'geditorial-uploader' ).'</label>';

		$html.= Core\HTML::tag( 'input', [
			'id'    => $this->classs( 'largefile', 'input' ),
			'type'  => 'file',
			'style' => 'display:none',
		] );

		$html.= Core\HTML::tag( 'input', [
			'disabled' => TRUE,
			'type'     => 'submit',
			'id'       => $this->classs( 'largefile', 'submit' ),
			'value'    => _x( 'Upload', 'Button', 'geditorial-uploader' ),
			'class'    => Core\HTML::buttonClass( TRUE, 'button-primary' ),
			'data'     => [
				'nonce'    => wp_create_nonce( $this->classs( 'largefile' ) ),
				'locale'   => Core\L10n::locale(),
				/* translators: `%s`: progress percent */
				'progress' => _x( 'Uploading File - %s%', 'JS String', 'geditorial-uploader' ),
				'complete' => _x( 'Upload Complete!', 'JS String', 'geditorial-uploader' ),
			],
		] );

		$html.= '</div><code id="'.$this->classs( 'largefile', 'name' ).'" class="-filename" style="display:none"></code></form>';

		echo $this->wrap( $html, '-admin-widget -widget-form' );
	}

	protected function get_widget_largefile_info()
	{
		return _x( 'You can access uploaded files via Media Library.', 'Widget Info', 'geditorial-uploader' );
	}

	public function do_ajax()
	{
		$post = self::unslash( $_REQUEST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'upload_check':

				Ajax::checkReferer( $this->classs( 'largefile' ) );

				$stored = $this->_handle_upload_check( $post['file'], $post['mime'] );

				if ( TRUE === $stored )
					Ajax::success();
				else
					Ajax::error( $stored );

				break;

			case 'upload_chaunk':

				Ajax::checkReferer( $this->classs( 'largefile' ) );

				$stored = $this->_handle_store_chaunk( $post['file'], $post['file_data'], (int) $post['chunk'] );

				if ( TRUE === $stored )
					Ajax::success();
				else
					Ajax::error( $stored );

				break;

			case 'upload_complete':

				Ajax::checkReferer( $this->classs( 'largefile' ) );

				$completed = $this->_handle_complete_upload( $post['file'] );

				if ( $completed[0] )
					Ajax::success( $completed[1] );
				else
					Ajax::errorMessage( $completed[1] );
		}

		Ajax::errorWhat();
	}

	private function _handle_upload_check( $filename, $mime )
	{
		$type = wp_check_filetype( $filename );

		if ( empty( $type['type'] ) )
			return _x( 'The mime-type is not allowed on this site!', 'Message', 'geditorial-uploader' );

		$wpupload = WordPress\Media::upload();

		if ( FALSE !== $wpupload['error'] )
			return _x( 'Can not access upload folders!', 'Message', 'geditorial-uploader' );

		if ( ! Core\File::writable( $wpupload['path'] ) )
			return _x( 'The upload directory is not writable!', 'Message', 'geditorial-uploader' );

		$path = Core\File::join( $wpupload['path'], sanitize_file_name( $filename ) );

		if ( file_exists( $path ) )
			return _x( 'The file is already exists in upload folder!', 'Message', 'geditorial-uploader' );

		return TRUE;
	}

	// @REF: `media_handle_upload()`
	private function _handle_complete_upload( $filename, $metadata = FALSE )
	{
		$wpupload = WordPress\Media::upload();

		$file = sanitize_file_name( $filename );
		$type = wp_check_filetype( $file );

		$path = Core\File::join( $wpupload['path'], $file );
		$url  = str_replace( $wpupload['basedir'], $wpupload['baseurl'], $file );

		$ext   = pathinfo( $filename, PATHINFO_EXTENSION );
		$name  = Core\File::basename( $filename, ".$ext" );
		$title = sanitize_text_field( $name );

		$id = wp_insert_attachment( [
			'guid'           => $url,
			'post_title'     => $title,
			'post_mime_type' => $type['type'],
		], $path, 0, TRUE );

		if ( self::isError( $id ) )
			return [ FALSE, $id->get_error_message() ];

		if ( $metadata )
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );

		return [
			TRUE,
			Core\HTML::link(
				_x( 'Edit Uploaded Attachment', 'Message', 'geditorial-uploader' ),
				WordPress\Post::edit( $id ),
				TRUE
			)
		];
	}

	private function _handle_store_chaunk( $file, $data, $chunk = 0 )
	{
		if ( FALSE === ( $decoded = $this->_do_decode_chunk( $data ) ) )
			return _x( 'Something is wrong with data!', 'Message', 'geditorial-uploader' );

		$wpupload = WordPress\Media::upload();

		if ( FALSE !== $wpupload['error'] )
			return _x( 'Can not access upload folders!', 'Message', 'geditorial-uploader' );

		$path = Core\File::join( $wpupload['path'], sanitize_file_name( $file ) );

		if ( 0 === $chunk && file_exists( $path ) )
			return _x( 'The file is already exists in upload folder!', 'Message', 'geditorial-uploader' );

		if ( ! file_put_contents( $path, $decoded, FILE_APPEND ) )
			return _x( 'Can not put contents into file!', 'Message', 'geditorial-uploader' );

		return TRUE;
	}

	private function _do_decode_chunk( $data )
	{
		$parts = explode( ';base64,', $data );

		if ( ! is_array( $parts ) || ! isset( $parts[1] ) )
			return FALSE;

		return base64_decode( $parts[1] ) ?: FALSE;
	}

	// @hook: `post-plupload-upload-ui`
	public function post_plupload_upload_ui()
	{
		if ( ! $this->role_can( 'uploads' ) )
			return;

		Core\HTML::desc( sprintf(
			/* translators: `%1$s`: link markup start, `%2$s`: link markup end */
			_x( 'Alternatively, you can use %1$sLarge File Uploader%2$s widget on the dashoard.', 'Message', 'geditorial-uploader' ),
			'<a href="'.Core\HTML::escapeURL( get_dashboard_url() ).'">',
			'</a>'
		) );
	}
}

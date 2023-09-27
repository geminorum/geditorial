<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

trait MainDownload
{

	public function maindownload__get_link( $post )
	{
		$link = $url = $attachment = FALSE;

		if ( $url = Template::getMetaFieldRaw( 'main_download_url', $post->ID ) )
			$link = $url;

		else if ( $attachment = Template::getMetaFieldRaw( 'main_download_id', $post->ID ) )
			$link = wp_get_attachment_url( (int) $attachment );

		return $this->filters( 'main_download', $link, $post, $url, $attachment );
	}

	public function maindownload__get_file_data_for_latechores( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$filesize = $httpstatus = FALSE;

		if ( $url = Template::getMetaFieldRaw( 'main_download_url', $post->ID ) ) {

			$filesize   = Core\HTTP::getSize( $url );
			$httpstatus = Core\HTTP::getStatus( $url );

		} else if ( $attachment = Template::getMetaFieldRaw( 'main_download_id', $post->ID ) ) {

			if ( $url = wp_get_attachment_url( (int) $attachment ) )
				$httpstatus = Core\HTTP::getStatus( $url );

			if ( $meta = wp_get_attachment_metadata( (int) $attachment ) ) {

				if ( ! empty( $meta['filesize'] ) )
					$filesize = $meta['filesize'];
			}

			if ( ! $filesize && $url )
				$filesize = Core\HTTP::getSize( $url );
		}

		return [
			'meta_input' => [
				$this->constant( 'maindownload_filesize', '_main_download_filesize' )     => $filesize,
				$this->constant( 'maindownload_httpstatus', '_main_download_httpstatus' ) => $httpstatus,
			],
		];
	}

	// @REF: `WordPress\Media::getAttachmentFileSize()`
	public function maindownload__get_filesize( $post = NULL, $format = FALSE, $template = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$flush    = Core\WordPress::isFlush();
		$metakey  = $this->constant( 'maindownload_filesize', '_main_download_filesize' );
		$filesize = '';

		if ( ! $flush && ( $meta = get_post_meta( $post->ID, $metakey, TRUE ) ) ) {

			$filesize = $meta;

		} else if ( $url = Template::getMetaFieldRaw( 'main_download_url', $post->ID ) ) {

			if ( $filesize = Core\HTTP::getSize( $url ) )
				update_post_meta( $post->ID, $metakey, $filesize );

		} else if ( $attachment = Template::getMetaFieldRaw( 'main_download_id', $post->ID ) ) {

			if ( $meta = wp_get_attachment_metadata( (int) $attachment ) ) {

				if ( ! empty( $meta['filesize'] ) )
					$filesize = $meta['filesize'];
			}

			if ( ! $filesize && ( $url = wp_get_attachment_url( (int) $attachment ) ) )
				$filesize = Core\HTTP::getSize( $url );

			if ( $filesize )
				update_post_meta( $post->ID, $metakey, $filesize );
		}

		if ( ! $filesize )
			return '';

		return $format
			? sprintf( $template ?? '<span class="-filesize text-nowrap" title="%1$s">%2$s</span>',
				_x( 'File Size', 'Internal: Main Download: Title Attr', 'geditorial' ), Core\HTML::wrapLTR( Core\File::formatSize( $filesize ) ) )
			: $filesize;
	}

	protected function maindownload__override_loop_before()
	{
		add_filter( 'post_type_link', [ $this, 'maindownload__override_posttype_link' ], 9999, 4 );
	}

	protected function maindownload__override_loop_after()
	{
		remove_filter( 'post_type_link', [ $this, 'maindownload__override_posttype_link' ], 9999, 4 );
	}

	public function maindownload__override_posttype_link( $post_link, $post, $leavename, $sample )
	{
		if ( ! $this->maindownload__posttype_supported( $post->post_type ) )
			return $post_link;

		if ( $download = $this->maindownload__get_link( $post ) )
			return $download;

		return $post_link;
	}

	// NOTE: overrided
	protected function maindownload__posttype_supported( $posttype )
	{
		return TRUE;
	}
}

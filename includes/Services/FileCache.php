<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class FileCache extends gEditorial\Service
{
	public static function getDIR( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR )
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		$path = Core\File::normalize( GEDITORIAL_CACHE_DIR.( $base ? '/'.$base.'/' : '/' ).$sub );

		if ( file_exists( $path ) )
			return Core\URL::untrail( $path );

		if ( ! wp_mkdir_p( $path ) )
			return FALSE;

		Core\File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );
		Core\File::putDoNotBackup( $path );

		return Core\URL::untrail( $path );
	}

	public static function getURL( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR ) // correct, we check for path constant
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		return Core\URL::untrail( GEDITORIAL_CACHE_URL.( $base ? '/'.$base.'/' : '/' ).$sub );
	}
}

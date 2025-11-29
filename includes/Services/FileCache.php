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

		$base = $base ?? self::BASE;
		$path = GEDITORIAL_CACHE_DIR.( $base ? '/'.$base.'/' : '/' ).$sub;
		$path = Core\File::untrail( Core\File::normalize( $path ) );

		if ( @file_exists( $path ) )
			return $path;

		if ( ! wp_mkdir_p( $path ) )
			return FALSE;

		// FIXME: check if the folder is writable
		Core\File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );
		Core\File::putDoNotBackup( $path );

		return $path;
	}

	public static function getURL( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR ) // correct, we check for path constant
			return FALSE;

		$base = $base ?? self::BASE;

		return Core\URL::untrail( GEDITORIAL_CACHE_URL.( $base ? '/'.$base.'/' : '/' ).$sub );
	}
}

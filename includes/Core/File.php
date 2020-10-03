<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class File extends Base
{

	// normalize a filesystem path
	// on windows systems, replaces backslashes with forward slashes
	// and forces upper-case drive letters.
	// allows for two leading slashes for Windows network shares, but
	// ensures that all other duplicate slashes are reduced to a single.
	// @SOURCE: `wp_normalize_path()`
	public static function normalize( $path )
	{
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '|(?<=.)/+|', '/', $path );

		if ( ':' === substr( $path, 1, 1 ) )
			$path = ucfirst( $path );

		return $path;
	}

	// i18n friendly version of `basename()`
	// if the filename ends in suffix this will also be cut off
	// @SOURCE: `wp_basename()`
	public static function basename( $path, $suffix = '' )
	{
		return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ), $suffix ) );
	}

	// @SOURCE: `wp_tempnam()`
	public static function tempName( $name = '', $dir = '' )
	{
		if ( empty( $dir ) )
			$dir = get_temp_dir();

		if ( empty( $name ) || in_array( $name, [ '.', '/', '\\' ], TRUE ) )
			$name = uniqid();

		// use the basename of the given file without the extension
		// as the name for the temporary directory
		$temp = preg_replace( '|\.[^.]*$|', '', basename( $name ) );

		// If the folder is falsey, use its parent directory name instead.
		if ( ! $temp )
			return self::tempName( dirname( $name ), $dir );

		// Suffix some random data to avoid filename conflicts.
		$temp.= '-'.wp_generate_password( 6, FALSE );
		$temp.= '.tmp';
		$temp = $dir.wp_unique_filename( $dir, $temp );

		$fp = @fopen( $temp, 'x' );

		if ( ! $fp && is_writable( $dir ) && file_exists( $temp ) )
			return self::tempName( $name, $dir );

		if ( $fp )
			fclose( $fp );

		return $temp;
	}

	// join two filesystem paths together
	// @SOURCE: `path_join()`
	public static function join( $base, $path )
	{
		return self::isAbsolute( $path ) ? $path : rtrim( $base, '/' ).'/'.ltrim( $path, '/' );
	}

	// test if a give filesystem path is absolute
	// for example, '/foo/bar', or 'c:\windows'
	// @SOURCE: `path_is_absolute()`
	public static function isAbsolute( $path )
	{
		// this is definitive if true but fails if $path does not exist or contains a symbolic link
		if ( $path == realpath( $path ) )
			return TRUE;

		if ( 0 == strlen( $path ) || '.' == $path[0] )
			return FALSE;

		// windows allows absolute paths like this
		if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) )
			return TRUE;

		// a path starting with / or \ is absolute; anything else is relative
		return ( '/' == $path[0] || '\\' == $path[0] );
	}

	// http://stackoverflow.com/a/4994188
	// core has `sanitize_file_name()` but with certain mime types
	public static function escFilename( $path )
	{
		// everything to lower and no spaces begin or end
		$path = strtolower( trim( $path ) );

		// adding - for spaces and union characters
		$path = str_replace( array( ' ', '&', '\r\n', '\n', '+', ',' ), '-', $path );

		// delete and replace rest of special chars
		$path = preg_replace( array( '/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/' ), array( '', '-', '' ), $path );

		return $path;
	}

	// WP core `size_format()` function without `number_format_i18n()`
	public static function formatSize( $bytes, $decimals = 0 )
	{
		$quant = array(
			'TB' => 1024 * 1024 * 1024 * 1024,
			'GB' => 1024 * 1024 * 1024,
			'MB' => 1024 * 1024,
			'KB' => 1024,
			'B'  => 1,
		);

		if ( 0 === $bytes )
			return number_format( 0, $decimals ).' B';

		foreach ( $quant as $unit => $mag )
			if ( doubleval( $bytes ) >= $mag )
				return number_format( $bytes / $mag, $decimals ).' '.$unit;

		return FALSE;
	}

	// wrapper for `file_get_contents()`
	// TODO: use `$wp_filesystem`
	// @REF: https://github.com/markjaquith/feedback/issues/33
	// @REF: `$wp_filesystem->get_contents()`
	public static function getContents( $filename )
	{
		return @file_get_contents( $filename );
	}
}

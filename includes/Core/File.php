<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

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
			return '&#8206;'.number_format( 0, $decimals ).' B&#8207;';

		foreach ( $quant as $unit => $mag )
			if ( doubleval( $bytes ) >= $mag )
				return '&#8206;'.number_format( $bytes / $mag, $decimals ).' '.$unit.'&#8207;';

		return FALSE;
	}
}

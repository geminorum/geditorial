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

	// ORIGINALLY BASED ON: Secure Folder wp-content/uploads v1.2
	// BY: Daniel Satria : http://ruanglaba.com
	// puts index.html on given folder and subs
	public static function putIndexHTML( $base, $index )
	{
		copy( $index, $base.'/index.html' );

		if ( $dir = opendir( $base ) )
			while ( FALSE !== ( $file = readdir( $dir ) ) )
				if ( is_dir( $base.'/'.$file ) && $file != '.' && $file != '..' )
					self::putIndexHTML( $base.'/'. $file, $index );

		closedir( $dir );
	}

	// puts .htaccess deny from all on a given folder
	public static function putHTAccessDeny( $path, $check_folder = TRUE )
	{
		$content = '<Files ~ ".*\..*">'.PHP_EOL.
				'<IfModule mod_access.c>'.PHP_EOL.
					'Deny from all'.PHP_EOL.
				'</IfModule>'.PHP_EOL.
				'<IfModule !mod_access_compat>'.PHP_EOL.
					'<IfModule mod_authz_host.c>'.PHP_EOL.
						'Deny from all'.PHP_EOL.
					'</IfModule>'.PHP_EOL.
				'</IfModule>'.PHP_EOL.
				'<IfModule mod_access_compat>'.PHP_EOL.
					'Deny from all'.PHP_EOL.
				'</IfModule>'.PHP_EOL.
			'</Files>';

		return self::putContents( '.htaccess', $content, $path, FALSE, $check_folder );
	}

	// wrapper for `file_get_contents()`
	// TODO: use `$wp_filesystem`
	// @REF: https://github.com/markjaquith/feedback/issues/33
	// @REF: `$wp_filesystem->get_contents()`
	public static function getContents( $filename )
	{
		return @file_get_contents( $filename );
	}

	// wrapper for file_put_contents()
	public static function putContents( $filename, $contents, $path = NULL, $append = TRUE, $check_folder = FALSE )
	{
		$dir = FALSE;

		if ( is_null( $path ) ) {

			$dir = WP_CONTENT_DIR;

		} else if ( $check_folder ) {

			$dir = wp_mkdir_p( $path );

			if ( TRUE === $dir )
				$dir = $path;

		} else if ( wp_is_writable( $path ) ) {

			$dir = $path;
		}

		if ( ! $dir )
			return $dir;

		if ( $append )
			return file_put_contents( self::join( $dir, $filename ), $contents.PHP_EOL, FILE_APPEND );

		return file_put_contents( self::join( $dir, $filename ), $contents.PHP_EOL );
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
			if ( (float) $bytes >= $mag )
				return number_format( $bytes / $mag, $decimals ).' '.$unit;

		return FALSE;
	}

	public static function prepName( $suffix = NULL, $prefix = NULL )
	{
		$name = '';

		if ( $prefix )
			$name.= $prefix.'-';

		$name.= WordPress::currentSiteName().'-'.current_time( 'Y-m-d' );

		if ( $suffix )
			$name.= '-'.$suffix;

		return $name;
	}

	// @REF: https://www.hashbangcode.com/article/remove-last-line-file-php
	public static function processCSVbyLine( $file, $callback, $args = [] )
	{
		if ( ! is_callable( $callback ) )
			return FALSE;

		$rows = file( $file ); // read the file into an array

		if ( empty( $rows ) || count( $rows ) < 2 )
			return FALSE;

		$row = trim( array_pop( $rows ) );

		if ( empty( $row ) )
			return FALSE;

		$raw  = str_getcsv( $row );
		$data = array_combine( str_getcsv( $rows[0] ), $raw );

		try {

			if ( ! $results = call_user_func_array( $callback, [ $data, $raw, $row, $args ] ) )
				return $results;

		} catch ( \Exception $e ) {

			return [ $data, $raw, $row, $e->getMessage() ]; // avoid trimming the source
		}

		$handle = fopen( $file, 'w' );

		fputs( $handle, join( '', $rows ) );
		fclose( $handle );

		unset( $file, $rows );

		return TRUE;
	}

	// NOTE: WTF: this method has no use for any kind of data!
	// @REF: https://www.hashbangcode.com/article/remove-last-line-file-php
	public static function processCSVbyLine_STUPID( $file, $callback )
	{
		$handle = fopen( $file, 'r' );
		$size   = filesize( $file );
		$break  = FALSE;
		$start  = FALSE;
		$bite   = 50; // number of bytes to look at

		// put pointer to the end of the file
		fseek( $handle, 0, SEEK_END );

		while ( FALSE === $break && FALSE === $start ) {

			$pos = ftell( $handle ); // get the current file position

			if ( $pos < $bite ) {

				// if the position is less than a bite then go to the start of the file
				rewind( $handle );

			} else {

				// move back $bite characters into the file
				fseek( $handle, -$bite, SEEK_CUR );
			}

			// read $bite characters of the file into a string
			$string = fread( $handle, $bite )
				or die ( "Can't read from file " . $file . "." ); // FIXME

			// if we happen to have read to the end of the file then we need to ignore
			// the last line as this will be a new line character
			if ( $pos + $bite >= $size)
				$string = substr_replace( $string, '', -1 );

			// since we fred() forward into the file we need to back up $bite characters
			if ( $pos < $bite )
				// if the position is less than a bite then go to the start of the file
				rewind( $handle );
			else
				// move back $bite characters into the file
				fseek( $handle, -$bite, SEEK_CUR );

			// is there a line break in the string we read?
			if ( is_integer( $lb = strrpos( $string, "\n" ) ) ) {
				// set $break to true so that we break out of the loop
				$break = TRUE;
				// the last line in the file is right after the linebreak
				$line_end = ftell( $handle ) + $lb + 1;

				self::_log($line_end);
			}

			self::_log($string);

			// break out of the loop if we are at the beginning of the file
			if ( 0 == ftell( $handle ) )
				$start = TRUE;
		}

		if ( TRUE === $break ) {

			// if we have found a line break then read the file into a string
			// to writing without the last line
			rewind( $handle );
			$file_minus_lastline = fread( $handle, $line_end );

			fclose( $handle );

			// open the file in write mode and truncate it
			$handle = fopen( $file, 'w+' );
			fputs( $handle, $file_minus_lastline );
			fclose( $handle );

		} else {

			// close the file, nothing else to do.
			fclose( $handle );
		}
	}
}

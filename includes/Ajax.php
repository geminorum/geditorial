<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;

class Ajax extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	public static function checkReferer( $action = NULL, $key = 'nonce' )
	{
		return check_ajax_referer( ( is_null( $action ) ? static::BASE : $action ), $key );
	}

	public static function success( $data = NULL, $status_code = NULL )
	{
		wp_send_json_success( $data, $status_code );
	}

	public static function error( $data = NULL, $status_code = NULL )
	{
		wp_send_json_error( $data, $status_code );
	}

	public static function successHTML( $html, $status_code = NULL )
	{
		self::success( [ 'html' => $html ], $status_code );
	}

	public static function successMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Successful!', 'Ajax: Ajax Notice', 'geditorial' );

		if ( $message )
			self::success( HTML::success( $message ) );
		else
			self::success();
	}

	public static function errorHTML( $html, $status_code = NULL )
	{
		self::error( [ 'html' => $html ], $status_code );
	}

	public static function errorMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Error!', 'Ajax: Ajax Notice', 'geditorial' );

		if ( $message )
			self::error( HTML::error( $message ) );
		else
			self::error();
	}

	public static function errorUserCant()
	{
		self::errorMessage( _x( 'You\'re not authorized!', 'Ajax: Ajax Notice', 'geditorial' ) );
	}

	public static function errorWhat()
	{
		self::errorMessage( _x( 'What?!', 'Ajax: Ajax Notice', 'geditorial' ) );
	}

	// @REF: https://make.wordpress.org/core/?p=12799
	// @REF: https://austin.passy.co/2014/native-wordpress-loading-gifs/
	public static function spinner()
	{
		return is_admin()
			? '<span class="-loading spinner"></span>'
			: '<span class="-loading '.static::BASE.'-spinner"></span>';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$props = array_merge( $args, [
			'_base' => static::BASE,
			'_url'  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),

			// '_restURL'   => rest_url(),
			// '_restNonce' => wp_create_nonce( 'wp_rest' ),
		] );

	?><script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object; ?> = <?php echo wp_json_encode( $props ); ?>;
	<?php if ( WordPress::isDev() ) {
		echo 'console.log("'.$object.'", '.$object.');'."\n";
		echo "\t".'jQuery(document).on("gEditorialReady", function(e, module, app){console.log("'.$object.': "+module, app);});'."\n";
	} ?>
/* ]]> */
</script><?php
	}
}

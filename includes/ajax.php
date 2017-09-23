<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;

class Ajax extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	public static function checkReferer( $action = NULL, $key = 'nonce' )
	{
		check_ajax_referer( ( is_null( $action ) ? self::BASE : $action ), $key );
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
			$message = _x( 'Succesful!', 'Ajax: Ajax Notice', GEDITORIAL_TEXTDOMAIN );

		if ( $message )
			self::success( HTML::success( $message ) );
		else
			self::success();
	}

	public static function errorMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Error!', 'Ajax: Ajax Notice', GEDITORIAL_TEXTDOMAIN );

		if ( $message )
			self::error( HTML::error( $message ) );
		else
			self::error();
	}

	public static function errorWhat()
	{
		self::errorMessage( _x( 'What?!', 'Ajax: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
	}

	public static function spinner()
	{
		return '<span class="'.self::BASE.'-spinner"></span>';
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$props = array_merge( $args, [
			'_base'  => self::BASE,
			'_url'   => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'_api'   => esc_url_raw( rest_url() ),
			'_nonce' => wp_create_nonce( 'wp_rest' ),
			'_dev'   => WordPress::isDev(),
		] );

	?><script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object.'Modules'; ?> = {};
	var <?php echo $object; ?> = <?php echo wp_json_encode( $props ); ?>;
	<?php if ( $props['_dev'] ) echo 'console.log('.$object.');'."\n"; ?>
/* ]]> */
</script><?php
	}
}

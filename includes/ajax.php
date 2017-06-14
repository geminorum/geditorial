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

	public static function successHTML( $html )
	{
		wp_send_json_success( [ 'html' => $html ] );
	}

	public static function successMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Succesful!', 'Ajax: Ajax Notice', GEDITORIAL_TEXTDOMAIN );

		if ( $message )
			self::successHTML( HTML::success( $message ) );
		else
			wp_send_json_success();
	}

	public static function errorMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Error!', 'Ajax: Ajax Notice', GEDITORIAL_TEXTDOMAIN );

		if ( $message )
			wp_send_json_error( HTML::error( $message ) );
		else
			wp_send_json_error();
	}

	public static function errorWhat()
	{
		self::errorMessage( _x( 'What?!', 'Ajax: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
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

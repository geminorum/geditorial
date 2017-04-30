<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAjax extends gEditorialBaseCore
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	public static function checkReferer( $action = NULL, $key = 'nonce' )
	{
		check_ajax_referer( ( is_null( $action ) ? self::BASE : $action ), $key );
	}

	public static function successHTML( $html )
	{
		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function successMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Succesful!', 'Ajax Helper: Ajax Notice', GEDITORIAL_TEXTDOMAIN );

		if ( $message )
			wp_send_json_success( gEditorialHTML::success( $message ) );
		else
			wp_send_json_success();
	}

	public static function errorMessage( $message = NULL )
	{
		if ( is_null( $message ) )
			$message = _x( 'Error!', 'Ajax Helper: Ajax Notice', GEDITORIAL_TEXTDOMAIN );

		if ( $message )
			wp_send_json_error( gEditorialHTML::error( $message ) );
		else
			wp_send_json_error();
	}

	public static function errorWhat()
	{
		self::errorMessage( _x( 'What?!', 'Ajax Helper: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$args['_domain'] = self::BASE; // FIXME: DEPRICATED
		$args['_base']   = self::BASE;
		$args['_url']    = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );
		$args['_api']    = $args['_url']; // FIXME: DEPRICATED
		$args['_dev']    = gEditorialWordPress::isDev();
		$args['_nonce']  = wp_create_nonce( self::BASE ); // FIXME: DEPRICATED

	?><script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object.'Modules'; ?> = {};
	var <?php echo $object; ?> = <?php echo wp_json_encode( $args ); ?>;
	<?php if ( $args['_dev'] ) echo 'console.log('.$object.');'."\n"; ?>
/* ]]> */
</script><?php
}
	}

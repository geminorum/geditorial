<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialAjax extends gEditorialBaseCore
{

	public static function checkReferer( $action = 'geditorial', $key = 'nonce' )
	{
		check_ajax_referer( $action, $key );
	}

	public static function errorWhat()
	{
		wp_send_json_error( gEditorialHTML::error( _x( 'What?!', 'Ajax Helper: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );
	}

	public static function printJSConfig( $args, $object = 'gEditorial' )
	{
		$args['_domain'] = 'geditorial';
		$args['_api']    = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );
		$args['_dev']    = gEditorialWordPress::isDev();
		$args['_nonce']  = wp_create_nonce( 'geditorial' );

	?><script type="text/javascript">
/* <![CDATA[ */
	var <?php echo $object.'Modules'; ?> = {};
	var <?php echo $object; ?> = <?php echo wp_json_encode( $args ); ?>;
	<?php if ( $args['_dev'] ) echo 'console.log('.$object.');'."\n"; ?>
/* ]]> */
</script><?php
}
	}

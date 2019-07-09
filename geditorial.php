<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

/*
Plugin Name: gEditorial
Plugin URI: https://geminorum.ir/wordpress/geditorial
Description: Our Editorial in WordPress
Version: 3.15.6
License: GPLv3+
Author: geminorum
Author URI: https://geminorum.ir/
Network: false
Text Domain: geditorial
Domain Path: /languages
RepoGitHub: geminorum/geditorial
GitHub URI: https://github.com/geminorum/geditorial
GitHub Plugin URI: https://github.com/geminorum/geditorial
Release Asset: true
Requires WP: 4.9
Requires PHP: 5.6.20
*/

define( 'GEDITORIAL_VERSION', '3.15.6' );
define( 'GEDITORIAL_MIN_PHP', '5.6.20' );
define( 'GEDITORIAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEDITORIAL_URL', plugin_dir_url( __FILE__ ) );
define( 'GEDITORIAL_FILE', basename( GEDITORIAL_DIR ).'/'.basename( __FILE__ ) );

defined( 'GEDITORIAL_TEXTDOMAIN' ) || define( 'GEDITORIAL_TEXTDOMAIN', 'geditorial' );

if ( version_compare( GEDITORIAL_MIN_PHP, phpversion(), '>=' ) ) {

	if ( is_admin() ) {
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p dir="ltr">';
			printf( '<b>gEditorial</b> requires PHP %s or higher. Please contact your hosting provider to update your site.', GEDITORIAL_MIN_PHP ) ;
		echo '</p></div>';
	}

	return FALSE;

} else if ( file_exists( GEDITORIAL_DIR.'assets/vendor/autoload.php' ) ) {
	require_once( GEDITORIAL_DIR.'assets/vendor/autoload.php' );

	// require_once( GEDITORIAL_DIR.'includes/plugin.php' );

	function gEditorial() {
		return \geminorum\gEditorial\Plugin::instance();
	}

	// FIXME: back-compat
	global $gEditorial;

	$gEditorial = gEditorial();

} else if ( is_admin() ) {

	add_action( 'admin_notices', function(){
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p>';
			printf( '<b>gEditorial</b> is not installed correctly. go grab the latest package <a href="%s" target="_blank">here</a>.', 'https://github.com/geminorum/geditorial/releases/latest' ) ;
		echo '</p></div>';
	} );
}

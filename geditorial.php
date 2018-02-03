<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

/*
Plugin Name: gEditorial
Plugin URI: http://geminorum.ir/wordpress/geditorial
Description: Our Editorial in WordPress
Version: 3.14.0
License: GPLv3+
Author: geminorum
Author URI: http://geminorum.ir/
Network: false
Text Domain: geditorial
Domain Path: /languages
RepoGitHub: geminorum/geditorial
GitHub Plugin URI: https://github.com/geminorum/geditorial
GitHub Branch: master
Release Asset: true
Requires WP: 4.7
Requires PHP: 5.4
*/

define( 'GEDITORIAL_VERSION', '3.14.0' );
define( 'GEDITORIAL_MIN_PHP', '5.4.0' );
define( 'GEDITORIAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEDITORIAL_URL', plugin_dir_url( __FILE__ ) );
define( 'GEDITORIAL_FILE', basename( GEDITORIAL_DIR ).'/'.basename( __FILE__ ) );

defined( 'GEDITORIAL_TEXTDOMAIN' ) or define( 'GEDITORIAL_TEXTDOMAIN', 'geditorial' );

if ( version_compare( GEDITORIAL_MIN_PHP, PHP_VERSION, '>=' ) ) {

	echo '<div class="notice notice-warning notice-alt is-dismissible"><p dir="ltr">';
		printf( '<b>gEditorial</b> requires PHP %s or higher. Please contact your hosting provider to update your site.', GEDITORIAL_MIN_PHP ) ;
	echo '</p></div>';

	return FALSE;

} else if ( file_exists( GEDITORIAL_DIR.'assets/vendor/autoload.php' ) ) {
	require_once( GEDITORIAL_DIR.'assets/vendor/autoload.php' );

	require_once( GEDITORIAL_DIR.'includes/plugin.php' );

	function gEditorial() {
		return \geminorum\gEditorial\Plugin::instance();
	}

	// FIXME: back comp
	global $gEditorial;

	$gEditorial = gEditorial();

} else if ( is_admin() ) {

	add_action( 'admin_notices', function(){
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p>';
			printf( '<b>gEditorial</b> is not installed correctly. go grab the latest package <a href="%s" target="_blank">here</a>.', 'https://github.com/geminorum/geditorial/releases/latest' ) ;
		echo '</p></div>';
	} );
}

<?php defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

/*
Plugin Name: gEditorial
Plugin URI: https://geminorum.ir/wordpress/geditorial
Update URI: https://github.com/geminorum/geditorial
Description: Our Editorial in WordPress
Version: 3.33.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author: geminorum
Author URI: https://geminorum.ir/
Network: false
Text Domain: geditorial
Domain Path: /languages
RepoGitHub: geminorum/geditorial
GitHub Plugin URI: https://github.com/geminorum/geditorial
Release Asset: true
Requires WP: 5.9.0
Requires at least: 5.9.0
Requires PHP: 7.4
Tested up to: 6.9
*/

define( 'GEDITORIAL_VERSION', '3.33.0' );
define( 'GEDITORIAL_MIN_PHP', '7.4' );
define( 'GEDITORIAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEDITORIAL_URL', plugin_dir_url( __FILE__ ) );
define( 'GEDITORIAL_FILE', basename( GEDITORIAL_DIR ).'/'.basename( __FILE__ ) );

if ( version_compare( GEDITORIAL_MIN_PHP, PHP_VERSION, '>=' ) ) {

	if ( is_admin() ) {
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p dir="ltr">';
			printf( '<b>gEditorial</b> requires PHP %s or higher. Please contact your hosting provider to update your site.', GEDITORIAL_MIN_PHP );
		echo '</p></div>';
	}

	return FALSE;

} else if ( file_exists( GEDITORIAL_DIR.'assets/vendor/autoload.php' ) ) {

	require_once GEDITORIAL_DIR.'assets/vendor/autoload.php';

	function gEditorial() {
		return \geminorum\gEditorial\Plugin::instance(
			GEDITORIAL_DIR,
			GEDITORIAL_URL,
			GEDITORIAL_FILE,
			GEDITORIAL_VERSION
		);
	}

	// NOTE: `$gEditorial` is on global context
	$gEditorial = gEditorial();

} else if ( is_admin() ) {

	add_action( 'admin_notices', static function () {
		echo '<div class="notice notice-warning notice-alt is-dismissible"><p>';
			printf( '<b>gEditorial</b> is not installed correctly. go grab the latest package <a href="%s" target="_blank">here</a>.', 'https://github.com/geminorum/geditorial/releases/latest' );
		echo '</p></div>';
	} );
}

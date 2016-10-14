<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

/*
Plugin Name: gEditorial
Plugin URI: http://geminorum.ir/wordpress/geditorial
Description: Our Editorial in WordPress
Version: 3.9.2
License: GPLv3+
Author: geminorum
Author URI: http://geminorum.ir/
Network: false
TextDomain: geditorial
DomainPath: /languages
RepoGitHub: geminorum/geditorial
GitHub Plugin URI: https://github.com/geminorum/geditorial
GitHub Branch: master
Requires WP: 4.4
Requires PHP: 5.3
*/

define( 'GEDITORIAL_VERSION', '3.9.2' );
define( 'GEDITORIAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEDITORIAL_URL', plugin_dir_url( __FILE__ ) );
define( 'GEDITORIAL_FILE', basename( GEDITORIAL_DIR ).'/'.basename( __FILE__ ) );

defined( 'GEDITORIAL_TEXTDOMAIN' ) or define( 'GEDITORIAL_TEXTDOMAIN', 'geditorial' );

// if ( file_exists( GEDITORIAL_DIR.'assets/vendor/autoload.php' ) ) {
// 	require_once( GEDITORIAL_DIR.'assets/vendor/autoload.php' );
	require_once( GEDITORIAL_DIR.'includes/class-main.php' );

	function gEditorial() {
		return gEditorial::instance();
	}

	// FIXME: back comp
	global $gEditorial;

	$gEditorial = gEditorial();
// }

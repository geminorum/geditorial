<?php defined( 'ABSPATH' ) or die( 'Restricted access' );
/*
Plugin Name: gEditorial
Plugin URI: http://geminorum.ir/wordpress/geditorial
Description: Our Editorial in WordPress
Version: 3.4
License: GNU/GPL 2
Author: geminorum
Author URI: http://geminorum.ir/
TextDomain: geditorial
DomainPath: /languages
RepoGitHub: geminorum/geditorial
GitHub Plugin URI: https://github.com/geminorum/geditorial
GitHub Branch: master
Requires WP: 4.4
Requires PHP: 5.3
*/

/*
	Copyright 2016 geminorum

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'GEDITORIAL_VERSION', '3.4' );
define( 'GEDITORIAL_FILE', __FILE__ );
define( 'GEDITORIAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEDITORIAL_URL', plugin_dir_url( __FILE__ ) );
defined( 'GEDITORIAL_TEXTDOMAIN' ) or define( 'GEDITORIAL_TEXTDOMAIN', 'geditorial' );

// if ( file_exists( GEDITORIAL_DIR.'assets/vendor/autoload.php' ) ) {
// 	require_once( GEDITORIAL_DIR.'assets/vendor/autoload.php' );
	require_once( GEDITORIAL_DIR.'includes/class-main.php' );

	function gEditorial() {
		return gEditorial::instance();
	}

	// back comp
	global $gEditorial;

	$gEditorial = gEditorial();
// }

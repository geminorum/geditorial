<?php defined( 'ABSPATH' ) or die( 'Restricted access' );
/*
Plugin Name: gEditorial
Plugin URI: http://geminorum.ir/wordpress/geditorial
Description: Our Editorial.
Version: 0.2.7
License: GNU/GPL 2
Author: geminorum
Author URI: http://geminorum.ir/
TextDomain: geditorial
DomainPath: /languages
GitHub Plugin URI: https://github.com/geminorum/geditorial
GitHub Branch: develop
*/

/*
	Copyright 2015 geminorum

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

define( 'GEDITORIAL_VERSION', '0.2.7' );
define( 'GEDITORIAL_VERSION_DB', '0.1' );
define( 'GEDITORIAL_FILE', __FILE__ );
define( 'GEDITORIAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEDITORIAL_URL', plugin_dir_url( __FILE__ ) );
defined( 'DS' ) or define( 'DS', DIRECTORY_SEPARATOR );
defined( 'GEDITORIAL_TEXTDOMAIN' ) or define( 'GEDITORIAL_TEXTDOMAIN', 'geditorial' );

require_once( GEDITORIAL_DIR.'includes/class-main.php' );

global $gEditorial;
$gEditorial = new gEditorial();

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

// modified from P2P_Mustache by http://scribu.net
// http://plugins.svn.wordpress.org/posts-to-posts/trunk/admin/mustache.php

abstract class gEditorialMustache {

	private static $loader;
	private static $mustache;

	public static function init() {
		if ( !class_exists( 'Mustache' ) )
			require_once( GEDITORIAL_DIR.'assets/libs/mustache/Mustache.php');

		if ( !class_exists( 'MustacheLoader' ) )
			require_once( GEDITORIAL_DIR.'assets/libs/mustache/MustacheLoader.php' );

		self::$loader = new MustacheLoader( GEDITORIAL_DIR.'assets/layouts', 'html' );

		self::$mustache = new Mustache( null, null, self::$loader );
	}

	public static function render( $template, $data ) {
		return self::$mustache->render( self::$loader[$template], $data );
	}
}

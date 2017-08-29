<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Widgets extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'      => 'widgets',
			'title'     => _x( 'Widgets', 'Modules: Widgets', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'Carefully Customized Widgets', 'Modules: Widgets', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'welcome-widgets-menus',
			'configure' => FALSE,
		];
	}

	public function widgets_init()
	{
		$this->require_code( [
			'widgets/custom-html',
			'widgets/gcal-events',
		] );

		register_widget( '\\geminorum\\gEditorial\\Widgets\\Widgets\\CustomHTML' );
		register_widget( '\\geminorum\\gEditorial\\Widgets\\Widgets\\GCalEvents' );
	}
}

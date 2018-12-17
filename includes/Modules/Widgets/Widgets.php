<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Widgets extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'widgets',
			'title' => _x( 'Widgets', 'Modules: Widgets', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Carefully Customized Widgets', 'Modules: Widgets', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'welcome-widgets-menus',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'  => 'widgets',
					'title'  => _x( 'Widgets', 'Modules: Widgets: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'type'   => 'checkboxes',
					'values' => $this->list_widgets(),
				]
			],
		];
	}

	private function get_widgets()
	{
		return [
			'custom-html'  => 'CustomHTML',
			'gcal-events'  => 'GCalEvents',
			'wprest-posts' => 'WPRestPosts',
		];
	}

	private function list_widgets()
	{
		$list = [];

		foreach ( $this->get_widgets() as $file => $class ) {

			$this->require_code( 'widgets/'.$file );

			$widget = call_user_func( [ '\\geminorum\\gEditorial\\Widgets\\Widgets\\'.$class, 'setup' ] );

			$list[$file] = $widget['title'].': <em>'.$widget['desc'].'</em>';
		}

		return $list;
	}

	public function widgets_init()
	{
		$widgets = $this->get_setting( 'widgets', [] );

		foreach ( $this->get_widgets() as $file => $class ) {

			if ( ! in_array( $file, $widgets ) )
				continue;

			$this->require_code( 'widgets/'.$file );

			register_widget( '\\geminorum\\gEditorial\\Widgets\\Widgets\\'.$class );
		}
	}
}
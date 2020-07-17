<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Widgets extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'  => 'widgets',
			'title' => _x( 'Widgets', 'Modules: Widgets', 'geditorial' ),
			'desc'  => _x( 'Carefully Customized Widgets', 'Modules: Widgets', 'geditorial' ),
			'icon'  => 'welcome-widgets-menus',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'  => 'widgets',
					'title'  => _x( 'Widgets', 'Setting Title', 'geditorial-widgets' ),
					'type'   => 'checkboxes',
					'values' => $this->list_widgets(),
				]
			],
		];
	}

	private function get_widgets()
	{
		return [
			'Custom-HTML'  => 'CustomHTML',
			'GCal-Events'  => 'GCalEvents',
			'WPRest-Posts' => 'WPRestPosts',
		];
	}

	private function list_widgets()
	{
		$list = [];

		foreach ( $this->get_widgets() as $file => $class ) {

			$this->require_code( 'Widgets/'.$file );

			$widget = call_user_func( [ '\\geminorum\\gEditorial\\Widgets\\Widgets\\'.$class, 'setup' ] );

			$list[$file] = $widget['title'].': <em>'.$widget['desc'].'</em>';
		}

		return $list;
	}

	protected function setup( $args = [] )
	{
		parent::setup( $args );

		// override checks!
		$this->action( 'widgets_init' );
	}

	public function widgets_init()
	{
		$widgets = $this->get_setting( 'widgets', [] );

		foreach ( $this->get_widgets() as $file => $class ) {

			if ( ! in_array( $file, $widgets ) )
				continue;

			$this->require_code( 'Widgets/'.$file );

			register_widget( '\\geminorum\\gEditorial\\Widgets\\Widgets\\'.$class );
		}
	}
}

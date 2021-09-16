<?php namespace geminorum\gEditorial\Modules\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\Arraay;

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
					'values' => $this->_list_widgets(),
				]
			],
			'_frontend' => [
				[
					'field'  => 'areas',
					'type'   => 'object',
					'title'  => _x( 'Widget Areas', 'Setting Title', 'geditorial-widgets' ),
					'values' => [
						[
							'field'       => 'action',
							'type'        => 'text',
							'title'       => _x( 'Action', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'Action hook where the widget appears on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'priority',
							'type'        => 'number',
							'title'       => _x( 'Priority', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'Action priority where the widget runs on the action.', 'Setting Description', 'geditorial-widgets' ),
							'default'     => 10,
						],
						[
							'field'       => 'name',
							'type'        => 'text',
							'title'       => _x( 'Name', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'The name or title of the sidebar displayed in the Widgets interface.', 'Setting Description', 'geditorial-widgets' ),
						],
						[
							'field'       => 'before_widget',
							'type'        => 'text',
							'title'       => _x( 'Before Widget', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML opening before each widget markup on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'after_widget',
							'type'        => 'text',
							'title'       => _x( 'After Widget', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML closing after each widget markup on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'before_title',
							'type'        => 'text',
							'title'       => _x( 'Before Title', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML opening before each widget title on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
						[
							'field'       => 'after_title',
							'type'        => 'text',
							'title'       => _x( 'After Title', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML closing after each widget title on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'dir'         => 'ltr',
						],
					],
				],
			],
		];
	}

	private function get_widgets()
	{
		return [
			'Custom-HTML'   => 'CustomHTML',
			'GCal-Events'   => 'GCalEvents',
			'Search-Terms'  => 'SearchTerms',
			'WPRest-Posts'  => 'WPRestPosts',
			'WPRest-Single' => 'WPRestSingle',
		];
	}

	private function _list_widgets()
	{
		$list = [];

		foreach ( $this->get_widgets() as $key => $class ) {

			$widget = call_user_func( [ __NAMESPACE__.'\\Widgets\\'.$class, 'setup' ] );

			$list[$key] = $widget['title'].': <em>'.$widget['desc'].'</em>';
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

		foreach ( $this->get_widgets() as $key => $class )
			if ( in_array( $key, $widgets, TRUE ) )
				register_widget( __NAMESPACE__.'\\Widgets\\'.$class );

		foreach ( $this->get_setting( 'areas', [] ) as $index => $area ) {

			if ( empty( $area['action'] ) )
				continue;

			$id       = $this->classs( 'area', $index );
			$priority = empty( $area['priority'] ) ? 10 : $area['priority'];
			$name     = empty( $area['name'] ) ? _x( '[Unnamed]', 'Widget Area Unnamed', 'geditorial-widgets' ) : $area['name'];

			add_action( $area['action'], static function() use ( $id ) {
				dynamic_sidebar( $id );
			}, $priority, 0 );

			register_sidebar( array_merge( Arraay::stripByKeys( $area, [ 'action', 'priority', 'name' ] ), [
				'id'   => $id,
				/* translators: %s: widget area name */
				'name' => sprintf( _x( 'Editorial: %s', 'Widget Area Prefix', 'geditorial-widgets' ), $name ),
			] ) );
		}
	}
}

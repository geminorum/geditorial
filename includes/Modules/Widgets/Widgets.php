<?php namespace geminorum\gEditorial\Modules\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;

class Widgets extends gEditorial\Module
{

	protected $deafults = [ 'widget_support' => TRUE ];

	public static function module()
	{
		return [
			'name'     => 'widgets',
			'title'    => _x( 'Widgets', 'Modules: Widgets', 'geditorial-admin' ),
			'desc'     => _x( 'Carefully Customized Widgets', 'Modules: Widgets', 'geditorial-admin' ),
			'icon'     => 'welcome-widgets-menus',
			'i18n'     => 'adminonly',
			'access'   => 'stable',
			'keywords' => [
				'has-widgets',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'manage_roles',
					'type'        => 'checkboxes',
					'title'       => _x( 'Theme Options', 'Setting Title', 'geditorial-widgets' ),
					'description' => _x( 'Enables &ldquo;Edit Theme Options&rdquo; capability for selected roles.', 'Setting Description', 'geditorial-widgets' ),
					'values'      => $this->get_settings_default_roles( 'contributor' ),
				],
				[
					'field'  => 'widgets',
					'title'  => _x( 'Widgets', 'Setting Title', 'geditorial-widgets' ),
					'type'   => 'checkboxes-panel-expanded',
					'values' => $this->_list_widgets(),
				],
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
							'ortho'       => 'hook',
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
						],
						[
							'field'       => 'after_widget',
							'type'        => 'text',
							'title'       => _x( 'After Widget', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML closing after each widget markup on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
						[
							'field'       => 'before_title',
							'type'        => 'text',
							'title'       => _x( 'Before Title', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML opening before each widget title on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
						[
							'field'       => 'after_title',
							'type'        => 'text',
							'title'       => _x( 'After Title', 'Setting Title', 'geditorial-widgets' ),
							'description' => _x( 'HTML closing after each widget title on front-end.', 'Setting Description', 'geditorial-widgets' ),
							'field_class' => [ 'regular-text', 'code-text' ],
						],
					],
				],
			],
		];
	}

	private function get_widgets()
	{
		return [
			'Custom-HTML'     => 'CustomHTML',
			'GCal-Events'     => 'GCalEvents',
			'Post-Terms'      => 'PostTerms',
			'Search-Terms'    => 'SearchTerms',
			'Namesake-Terms'  => 'NamesakeTerms',
			'WPRest-Posts'    => 'WPRestPosts',
			'WPRest-Single'   => 'WPRestSingle',
			'Profile-Summary' => 'ProfileSummary',
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

	public function widgets_init()
	{
		if ( count( $this->get_setting( 'manage_roles', [] ) ) )
			$this->filter( 'map_meta_cap', 4 );

		$this->_register_widgets();
		$this->_register_areas();
	}

	private function _register_widgets()
	{
		$widgets = $this->get_setting( 'widgets', [] );

		foreach ( $this->get_widgets() as $key => $class )
			if ( in_array( $key, $widgets, TRUE ) )
				register_widget( __NAMESPACE__.'\\Widgets\\'.$class );
	}

	private function _register_areas()
	{
		foreach ( $this->get_setting( 'areas', [] ) as $index => $area ) {

			if ( empty( $area['action'] ) )
				continue;

			$id       = $this->classs( 'area', $index );
			$priority = empty( $area['priority'] ) ? 10 : $area['priority'];
			$name     = empty( $area['name'] ) ? _x( '[Unnamed]', 'Widget Area Unnamed', 'geditorial-widgets' ) : $area['name'];

			add_action( $area['action'], static function () use ( $id ) {
				dynamic_sidebar( $id );
			}, $priority, 0 );

			register_sidebar( array_merge( Core\Arraay::stripByKeys( $area, [ 'action', 'priority', 'name' ] ), [
				'id'   => $id,
				'name' => sprintf(
					/* translators: `%s`: widget area name */
					_x( 'Editorial: %s', 'Widget Area Prefix', 'geditorial-widgets' ),
					$name
				),
			] ) );
		}
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		switch ( $cap ) {

			case 'edit_theme_options':

				return $this->role_can( 'manage', $user_id )
					? [ 'exist' ]
					: [ 'do_not_allow' ];

				break;
		}

		return $caps;
	}
}

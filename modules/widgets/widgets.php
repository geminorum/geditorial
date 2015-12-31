<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWidgets extends gEditorialModuleCore
{
/*
	// TODO

	- check for settings for each widget
		and then enable them
		and disable the core's, if exists

	- Using widget api

		- general rss widget

		- social widget like the one on Ari theme / or: https://wordpress.org/plugins/brankic-social-media-widget/
			- add to feedly: http://www.feedly.com/factory.html
			- letterboxed profile with icon from: http://letterboxd.com/about/media-kit/
			- themoviedb profile with icon from: https://www.themoviedb.org/about/logos-attribution

		- tabbed content like the one on Hueman theme (or on gTheme?!)

		- login form: like old one on gMember


*/
	public static function module()
	{
		if ( ! self::isDev() )
			return FALSE;

		return array(
			'name'     => 'widgets',
			'title'    => _x( 'Widgets', 'Widgets Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Set of carefully customizable widgets', 'Widgets Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'welcome-widgets-menus',
		);
	}

	public function setup( $partials = array() )
	{
		// parent::setup();
	}

	public function init()
	{
		do_action( 'geditorial_widgets_init', $this->module );
		$this->do_globals();
	}

	public function admin_init()
	{
		// if ( $this->get_setting( 'group_taxonomies', FALSE ) )
	}
}

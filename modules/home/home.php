<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHome extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'  => 'home',
			'title' => _x( 'Home', 'Home Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Home Page Customized', 'Home Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-home',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
		);
	}

	public function init()
	{
		do_action( 'geditorial_home_init', $this->module );
		$this->do_globals();

		if ( ! is_admin() && count( $this->post_types() ) )
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 9 );
	}

	public function pre_get_posts( &$query )
	{
		if ( ! $query->is_main_query() )
			return;

		if ( is_home() )
			$query->set( 'post_type', $this->post_types() );

		else if ( is_search() && empty( $query->query_vars['post_type'] ) )
			$query->set( 'post_type', $this->post_types() );

		else if ( is_archive() && empty( $query->query_vars['post_type'] ) )
			$query->set( 'post_type', $this->post_types() );

		else if ( is_feed() && empty( $query->query_vars['post_type'] ) )
			$query->set( 'post_type', $this->post_types() );
	}
}

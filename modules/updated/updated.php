<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialUpdated extends gEditorialModuleCore
{
/*
	// FIXME:

	- keeps last updated timestamp of the site contents

		- menu replace string like gPersianDate

		- add update filters to
			- post publish

		- option to calculate post updated as well
		- option to clculate new comments as well

*/
	public static function module()
	{
		return array(); // FIXME

		return array(
			'name'     => 'updated',
			'title'    => _x( 'Updated', 'Updated Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Keeps last updated timestamp of the site contents', 'Updated Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'update',
		);
	}

	public function setup( $partials = array() )
	{
		// parent::setup();
	}

	public function init()
	{
		do_action( 'geditorial_updated_init', $this->module );
		$this->do_globals();
	}

	public function admin_init()
	{
		// if ( $this->get_setting( 'group_taxonomies', FALSE ) )
	}
}

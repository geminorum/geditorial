<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait Calendars
{

	public function get_calendars( $default = NULL )
	{
		return $this->get_setting( 'calendar_list', $default ?? [ Core\L10n::calendar() ] );
	}

	// @old: `$this->get_calendars()`
	public function list_calendars( $default = NULL, $filtered = TRUE )
	{
		$settings = $this->get_setting( 'calendar_list', $default ?? [ Core\L10n::calendar() ] );
		$defaults = Services\Calendars::getDefualts( $filtered );
		return array_intersect_key( $defaults, array_flip( $settings ) );
	}
}


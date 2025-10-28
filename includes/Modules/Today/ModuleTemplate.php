<?php namespace geminorum\gEditorial\Modules\Today;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'today';

	/**
	 * Outputs the HTML for calendar.
	 * @source https://www.billerickson.net/code/event-calendar-widget/
	 *
	 * TODO: style using CSS flex: https://jsfiddle.net/geminorum/hot8a4n3/
	 *
	 * @param array $atts
	 * @param array $the_day
	 * @param string $default_type
	 * @return void
	 */
	public static function calendar( $atts = [], $the_day = NULL, $default_type = NULL )
	{
		// FIXME: WTF?!
	}
}

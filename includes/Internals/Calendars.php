<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait Calendars
{

	public function get_calendar_mode( null|string|array $default = NULL ): string
	{
		return 1 === count( $this->get_calendars( $default ) ) ? 'singular' : 'multiple';
	}

	public function get_calendars( null|string|array $default = NULL ): array
	{
		return $this->get_setting( 'calendar_list', (array) ( $default ?? Core\L10n::calendar() ) );
	}

	// @old: `$this->get_calendars()`
	public function list_calendars( null|string|array $default = NULL, bool $filtered = TRUE ): array
	{
		return array_intersect_key(
			Services\Calendars::getDefualts( $filtered ),
			array_flip( $this->get_calendars( $default ) )
		);
	}
}


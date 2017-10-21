<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Misc extends Base
{

	// @REF: https://gist.github.com/boonebgorges/5537311
	public static function getTwitter( $string, $url = FALSE )
	{
		$parts = parse_url( $string );

		if ( empty( $parts['host'] ) )
			$handle = 0 === strpos( $string, '@' ) ? substr( $string, 1 ) : $string;
		else
			$handle = trim( $parts['path'], '/\\' );

		return $url ? URL::trail( 'https://twitter.com/'.$handle ) : '@'.$handle;
	}

	// @REF: https://developers.google.com/google-apps/calendar/
	// @SOURCE: https://wordpress.org/plugins/gcal-events-list/
	public static function getGoogleCalendarEvents( $atts )
	{
		$args = self::atts( array(
			'calendar_id' => FALSE,
			'api_key'     => '',
			'time_min'    => '',
			'max_results' => 5,
		), $atts );

		if ( ! $args['calendar_id'] )
			return FALSE;

		$time = $args['time_min'] && Date::isInFormat( $args['time_min'] ) ? $args['time_min'] : date( 'Y-m-d' );

		$url = 'https://www.googleapis.com/calendar/v3/calendars/'
			.urlencode( $args['calendar_id'] )
			.'/events?key='.$args['api_key']
			.'&maxResults='.$args['max_results']
			.'&orderBy=startTime'
			.'&singleEvents=true'
			.'&timeMin='.$time.'T00:00:00Z';

		return HTTP::getJSON( $url );
	}
}
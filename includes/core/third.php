<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

class Third extends Base
{

	public static function htmlTwitterIntent( $string, $thickbox = FALSE )
	{
		$handle = self::getTwitter( $string );
		$url    = URL::untrail( self::getTwitter( $string, TRUE, 'https://twitter.com/intent/user?screen_name=' ) );

		if ( $thickbox ) {

			$args  = array(
				'href'    => add_query_arg( array( 'TB_iframe' => '1' ), $url ),
				'title'   => $handle,
				'class'   => '-twitter thickbox',
				'onclick' => 'return false;',
			);

			if ( function_exists( 'add_thickbox' ) )
				add_thickbox();

		} else {

			$args = array( 'href' => $url, 'class' => '-twitter' );
		}

		return HTML::tag( 'a', $args, '&lrm;'.$handle.'&rlm;' );
	}

	// @REF: https://gist.github.com/boonebgorges/5537311
	public static function getTwitter( $string, $url = FALSE, $base = 'https://twitter.com/' )
	{
		$parts = parse_url( $string );

		if ( empty( $parts['host'] ) )
			$handle = 0 === strpos( $string, '@' ) ? substr( $string, 1 ) : $string;
		else
			$handle = trim( $parts['path'], '/\\' );

		return $url ? URL::trail( $base.$handle ) : '@'.$handle;
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

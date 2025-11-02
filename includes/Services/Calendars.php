<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Calendars extends gEditorial\Service
{
	const REWRITE_ENDPOINT_NAME  = 'ics';
	const REWRITE_ENDPOINT_QUERY = 'ical';
	const POSTTYPE_ICAL_SOURCE   = 'ical_source';
	const TAXONOMY_ICAL_SOURCE   = 'ical_source';

	public static function setup()
	{
		if ( GEDITORIAL_DISABLE_ICAL )
			return FALSE;

		add_action( 'init', [ __CLASS__, 'init' ] );

		if ( is_admin() )
			return;

		add_action( 'template_redirect', [ __CLASS__, 'template_redirect' ] );
	}

	// @SEE: on taxonomies: https://core.trac.wordpress.org/ticket/33728
	public static function init()
	{
		add_rewrite_endpoint(
			static::REWRITE_ENDPOINT_NAME,
			EP_PERMALINK | EP_PAGES | EP_CATEGORIES | EP_TAGS,
			static::REWRITE_ENDPOINT_QUERY
		);
	}

	// https://make.wordpress.org/plugins/2012/06/07/rewrite-endpoints-api/
	// https://gist.github.com/joncave/2891111
	public static function template_redirect()
	{
		global $wp_query;

		if ( ! array_key_exists( static::REWRITE_ENDPOINT_QUERY, $wp_query->query_vars ) )
			return;

		$events  = $filename = FALSE;
		$context = static::REWRITE_ENDPOINT_QUERY;

		if ( is_singular() ) {

			if ( ! $post = WordPress\Post::get() )
				return;

			if ( ! $object = WordPress\PostType::object( $post ) )
				return;

			if ( ! WordPress\PostType::viewable( $object ) )
				return;

			if ( empty( $object->{static::POSTTYPE_ICAL_SOURCE} ) )
				return;

			if ( NULL !== ( $filtered = apply_filters( static::BASE.'_calendars_post_events', NULL, $post, $context ) ) )
				$events = $filtered;

			else if ( 'paired' === $object->{static::POSTTYPE_ICAL_SOURCE} )
				$events = self::getPairedCalendar( $post, $context );

			else
				$events = self::getSingularCalendar( $post, $context );

			$filename = apply_filters( static::BASE.'_calendars_post_filename',
				Core\File::prepName( $post->post_name, $context, FALSE ),
				$post,
				$context
			);

		} else if ( is_post_type_archive() ) {

			if ( ! $posttype = WordPress\PostType::object( get_queried_object() ) )
				return;

			if ( ! WordPress\PostType::viewable( $posttype ) )
				return;

			if ( empty( $posttype->{static::POSTTYPE_ICAL_SOURCE} ) )
				return;

			if ( NULL !== ( $filtered = apply_filters( static::BASE.'_calendars_posttype_events', NULL, $posttype->name, $context ) ) )
				$events = $filtered;

			else
				$events = self::getPostTypeCalendar( $posttype, $context );

			$filename = apply_filters( static::BASE.'_calendars_posttype_filename',
				Core\File::prepName( $posttype->name, $context, FALSE ),
				$posttype->name,
				$context
			);

		} else if ( is_tax() || is_tag() || is_category() ) {

			if ( ! $term = WordPress\Term::get() )
				return;

			if ( ! $object = WordPress\Taxonomy::object( $term ) )
				return;

			if ( ! WordPress\Taxonomy::viewable( $object ) )
				return;

			if ( empty( $object->{static::TAXONOMY_ICAL_SOURCE} ) )
				return;

			if ( NULL !== ( $filtered = apply_filters( static::BASE.'_calendars_term_events', NULL, $term, $context ) ) )
				$events = $filtered;

			else
				$events = self::getTermCalendar( $term, $context );

			$filename = apply_filters( static::BASE.'_calendars_term_filename',
				Core\File::prepName( $term->slug, $context, FALSE ),
				$term,
				$context
			);
		}

		self::exitICS( $events, $filename, $context );
	}

	public static function exitICS( $events, $filename = FALSE, $context = NULL )
	{
		if ( empty( $events ) )
			return FALSE;

		if ( ! is_array( $events ) )
			$events = [ $events ];

		if ( ! $filename )
			$filename = Core\File::prepName( $context ?? 'calendar' );

		$calendar = new \Eluceo\iCal\Domain\Entity\Calendar( $events );
		$factory  = new \Eluceo\iCal\Presentation\Factory\CalendarFactory();

		Core\HTTP::headers( [
			'Content-Type'        => 'text/calendar; charset=utf-8',
			'Content-Disposition' => sprintf( 'attachment; filename="%s.ics"', $filename ),
		] );

		echo $factory->createCalendar( $calendar );
		exit;
	}

	public static function getPairedCalendar( $post = NULL, $context = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $object = WordPress\PostType::object( $post ) )
			return FALSE;

		$prop = sprintf( '%s_module', self::BASE );

		if ( empty( $object->{$prop} ) )
			return FALSE;

		if ( ! gEditorial()->enabled( $object->{$prop} ) )
			return FALSE;

		if ( ! $items = gEditorial()->module( $object->{$prop} )->paired_all_connected_to( $post, $context ) )
			return [];

		$events = [];

		foreach ( $items as $item )
			$events[] = self::getSingularCalendar( $item, $context );

		return $events;
	}

	public static function getPostTypeCalendar( $posttype, $context = NULL, $the_date = NULL )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return FALSE;

		$events = [];
		$items  = WordPress\PostType::getRecent( $posttype->name, [
			'posts_per_page' => -1,
		] );

		foreach ( $items as $item )
			$events[] = self::getSingularCalendar( $item, $context, $the_date );

		return $events;
	}

	public static function getTermCalendar( $term, $context = NULL, $the_date = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		$events = [];
		$items  = WordPress\PostType::getRecent( 'any', [
			'posts_per_page' => -1,
			'tax_query'      => [ [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			] ],
		] );

		foreach ( $items as $item )
			$events[] = self::getSingularCalendar( $item, $context, $the_date );

		return $events;
	}

	/**
	 * Retrieves calendar events based on a singular post.
	 *
	 * @param int|object $post
	 * @param string $context
	 * @param mixed $the_date
	 * @return false|object
	 */
	public static function getSingularCalendar( $post = NULL, $context = NULL, $the_date = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		/**
		 * @package `eluceo/ical`
		 * @source https://github.com/markuspoerschke/iCal
		 * @docs https://ical.poerschke.nrw/docs
		 */
		$uid   = implode( '-', [ WordPress\Site::name(), $post->post_type, $post->ID ] );
		$event = new \Eluceo\iCal\Domain\Entity\Event( new \Eluceo\iCal\Domain\ValueObject\UniqueIdentifier( $uid ) );
		$event->touch( new \Eluceo\iCal\Domain\ValueObject\Timestamp( Core\Date::getObject( $post->post_modified ) ) );
		$event->setSummary( WordPress\Post::fullTitle( $post ) );

		if ( $shortlink = WordPress\Post::shortlink( $post ) )
			$event->setUrl(
				new \Eluceo\iCal\Domain\ValueObject\Uri( $shortlink )
			);

		if ( $desc = apply_filters( static::BASE.'_calendars_post_description',
			WordPress\Strings::prepDescription( $post->post_excerpt, TRUE, FALSE ),
			$post,
			$context
		) )
			$event->setDescription( $desc );

		if ( $email = PostTypeFields::getFieldRaw( 'email_address', $post->ID, 'meta', TRUE ) ) {

			$organizer = new \Eluceo\iCal\Domain\ValueObject\Organizer(
    			new \Eluceo\iCal\Domain\ValueObject\EmailAddress( $email ) );

			$event->setOrganizer( $organizer );
		}

		if ( $venue = Locations::getSingularLocation( $post, $context ) ) {

			$location = new \Eluceo\iCal\Domain\ValueObject\Location( $venue['address'], $venue['title'] );

			if ( ! empty( $venue['latlng'] ) && Core\LatLng::is( $venue['latlng'] ) )
				$location = $location->withGeographicPosition(
					new \Eluceo\iCal\Domain\ValueObject\GeographicPosition(
						...Core\LatLng::extract( $venue['latlng'] ) ) );

			$event->setLocation( $location );
		}

		if ( $the_date ) {

			if ( is_a( $the_date, 'DateTimeInterface' ) ) {

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $the_date )
					)
				);

			} else if ( is_callable( $the_date ) && ( $called = call_user_func_array( $the_date, [ $post, $context ] ) ) ) {

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $called )
					)
				);

			} else if ( $the_date ) {

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date(
							Core\Date::getObject( $the_date )
						)
					)
				);
			}

		} else if ( ( $datestart = PostTypeFields::getFieldDate( 'datestart', $post->ID ) )
			&& ( $dateend = PostTypeFields::getFieldDate( 'dateend', $post->ID ) ) ) {

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\TimeSpan( $datestart, $dateend )
			);

		} else if ( $datetime = PostTypeFields::getFieldDate( 'datetime', $post->ID ) ) {

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $datetime )
				)
			);

		} else if ( $date = PostTypeFields::getFieldDate( 'date', $post->ID ) ) {

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $date )
				)
			);

		} else {

			// no extra field data: using the post date

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date(
						Core\Date::getObject( $post->post_date )
					)
				)
			);
		}

		return $event;
	}

	public static function linkPostCalendar( $post = NULL, $context = NULL )
	{
		if ( self::const( 'GEDITORIAL_DISABLE_ICAL' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		return apply_filters( static::BASE.'_calendars_post_link',
			WordPress\Post::endpointLink( static::REWRITE_ENDPOINT_NAME, $post ),
			$post,
			$context
		);
	}

	/**
	 * Retrieves the list of supported calendars.
	 * @see `Almanac` Module
	 * @old: `Datetime::getDefualtCalendars()`
	 * @source https://unicode-org.github.io/icu/userguide/datetime/calendar/
	 *
	 * @param bool $filtered
	 * @return array
	 */
	public static function getDefualts( $filtered = FALSE )
	{
		$calendars = [
			'gregorian'     => _x( 'Gregorian', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'japanese'      => _x( 'Japanese', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'buddhist'      => _x( 'Buddhist', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'chinese'       => _x( 'Chinese', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			'persian'       => _x( 'Persian', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'indian'        => _x( 'Indian', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			'islamic'       => _x( 'Islamic', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'islamic-civil' => _x( 'Islamic-Civil', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'coptic'        => _x( 'Coptic', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
			// 'ethiopic'      => _x( 'Ethiopic', 'Services: Calendars: Default Calendar Type', 'geditorial' ),
		];

		return $filtered ? apply_filters( static::BASE.'_default_calendars', $calendars ) : $calendars;
	}

	/**
	 * Sanitizes given calendar type string.
	 * NOTE: DEPRECATED
	 *
	 * @param string $calendar
	 * @param string $default
	 * @return string
	 */
	public static function sanitize( $calendar, $default = NULL )
	{
		self::_dev_dep( 'Core\Date::sanitizeCalendar()' );

		$default   = $default ?? Core\L10n::calendar();
		$calendars = self::getDefualts( FALSE );
		$sanitized = $calendar;

		if ( ! $calendar )
			$sanitized = $default;

		else if ( in_array( $calendar, [ 'Jalali', 'jalali', 'Persian', 'persian' ] ) )
			$sanitized = 'persian';

		else if ( in_array( $calendar, [ 'Hijri', 'hijri', 'Islamic', 'islamic' ] ) )
			$sanitized = 'islamic';

		else if ( in_array( $calendar, [ 'Gregorian', 'gregorian' ] ) )
			$sanitized = 'gregorian';

		else if ( in_array( $calendar, array_keys( $calendars ) ) )
			$sanitized = $calendar;

		else if ( $key = array_search( $calendar, $calendars ) )
			$sanitized = $key;

		else
			$sanitized = $default;

		return apply_filters( static::BASE.'_sanitize_calendar', $sanitized, $default, $calendar );
	}
}

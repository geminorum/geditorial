<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Calendars extends gEditorial\Service
{
	const REWRITE_ENDPOINT_NAME  = 'ics';
	const REWRITE_ENDPOINT_QUERY = 'ical';
	const ICAL_DEFAULT_CONTEXT   = 'calendar';
	const ICAL_TIMESPAN_CONTEXT  = 'timespan';
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

		if ( is_robots() || is_favicon() )
			return;

		if ( ! array_key_exists( static::REWRITE_ENDPOINT_QUERY, $wp_query->query_vars ) )
			return;

		$events  = $filename = FALSE;
		$context = get_query_var( static::REWRITE_ENDPOINT_QUERY ) ?: static::ICAL_DEFAULT_CONTEXT;

		if ( is_singular() ) {

			if ( ! $post = WordPress\Post::get() )
				return;

			if ( ! $object = WordPress\PostType::object( $post ) )
				return;

			if ( ! WordPress\PostType::viewable( $object ) )
				return;

			if ( empty( $object->{static::POSTTYPE_ICAL_SOURCE} ) && 'post' !== $object->name )
				return;

			if ( NULL !== ( $filtered = apply_filters( static::BASE.'_calendars_post_events', NULL, $post, $context ) ) )
				$events = $filtered;

			else if ( 'post' !== $object->name && 'paired' === $object->{static::POSTTYPE_ICAL_SOURCE} )
				$events = self::getPairedEvents( $post, $context );

			else
				$events = self::getPostEvent( $post, $context );

			$filename = apply_filters( static::BASE.'_calendars_post_filename',
				Core\File::prepName( $post->post_name, $context, FALSE ),
				$post,
				$context
			);

			self::exitICS( $events, $filename, $context );

		} else if ( is_post_type_archive() ) {

			if ( ! $posttype = WordPress\PostType::object( get_queried_object() ) )
				return;

			if ( ! WordPress\PostType::viewable( $posttype ) )
				return;

			if ( empty( $posttype->{static::POSTTYPE_ICAL_SOURCE} ) && 'post' !== $posttype )
				return;

			if ( NULL !== ( $filtered = apply_filters( static::BASE.'_calendars_posttype_events', NULL, $posttype->name, $context ) ) )
				$events = $filtered;

			else
				$events = self::getPostTypeEvents( $posttype, $context );

			$filename = apply_filters( static::BASE.'_calendars_posttype_filename',
				Core\File::prepName( $posttype->name, $context, FALSE ),
				$posttype->name,
				$context
			);

			self::exitICS( $events, $filename, $context );

		} else if ( is_tax() || is_tag() || is_category() ) {

			if ( ! $term = WordPress\Term::get() )
				return;

			if ( ! $object = WordPress\Taxonomy::object( $term ) )
				return;

			if ( ! WordPress\Taxonomy::viewable( $object ) )
				return;

			if ( empty( $object->{static::TAXONOMY_ICAL_SOURCE} ) && 'category' !== $object->name )
				return;

			if ( NULL !== ( $filtered = apply_filters( static::BASE.'_calendars_term_events', NULL, $term, $context ) ) )
				$events = $filtered;

			else
				$events = self::getTaxonomyEvents( $term, $context );

			$filename = apply_filters( static::BASE.'_calendars_term_filename',
				Core\File::prepName( $term->slug, $context, FALSE ),
				$term,
				$context
			);

			self::exitICS( $events, $filename, $context );
		}

		do_action( static::BASE.'_calendars_ical_notfound', $context );

		WordPress\Theme::set404();
	}

	// NOTE: may return empty calendar markup!
	public static function exitICS( $events, $filename = FALSE, $context = NULL )
	{
		if ( $events && ! is_array( $events ) )
			$events = [ $events ];

		if ( ! $filename )
			$filename = Core\File::prepName( $context ?? 'calendar' );

		/**
		 * @package `eluceo/ical`
		 * @source https://github.com/markuspoerschke/iCal
		 * @docs https://ical.poerschke.nrw/docs
		 */
		$calendar = new \Eluceo\iCal\Domain\Entity\Calendar( $events ?: [] );
		$factory  = new \Eluceo\iCal\Presentation\Factory\CalendarFactory();

		// NOTE: WORKING BUT: generates unnecessary data!
		// @SEE: https://ical.poerschke.nrw/docs/component-timezone
		// $calendar->addTimeZone(
		// 	\Eluceo\iCal\Domain\Entity\TimeZone::createFromPhpDateTimeZone(
		// 		new \DateTimeZone( Core\Date::currentTimeZone() )
		// 	)
		// );

		Core\HTTP::headers( [
			'Content-Type'        => 'text/calendar; charset=utf-8',
			'Content-Disposition' => sprintf( 'attachment; filename="%s.ics"', $filename ),
		] );

		echo $factory->createCalendar( $calendar );
		exit;
	}

	public static function getPairedEvents( $post = NULL, $context = NULL )
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
			$events[] = self::getPostEvent( $item, $context );

		return $events;
	}

	public static function getPostTypeEvents( $posttype, $context = NULL, $the_date = NULL )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return FALSE;

		$events = [];
		$items  = WordPress\PostType::getRecent( $posttype->name, [
			'posts_per_page' => -1,
		] );

		foreach ( $items as $item )
			$events[] = self::getPostEvent( $item, $context, $the_date );

		return $events;
	}

	public static function getTaxonomyEvents( $term, $context = NULL, $the_date = NULL )
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
			$events[] = self::getPostEvent( $item, $context, $the_date );

		return $events;
	}

	/**
	 * Retrieves calendar events based on a singular post.
	 * OLD: `Services\Calendars::getSingularCalendar()`
	 *
	 * @param int|object $post
	 * @param string $context
	 * @param mixed $the_date
	 * @param mixed $the_summary
	 * @param mixed $the_location
	 * @return false|object
	 */
	public static function getPostEvent( $post = NULL, $context = NULL, $the_date = NULL, $the_summary = NULL, $the_location = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$final = $summary = $vanue = FALSE;

		// @REF: https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.7
		$uid = implode( '-', [
			WordPress\Site::name(),
			$post->post_type,
			$post->ID, $context ?? static::ICAL_DEFAULT_CONTEXT,
		] );

		/**
		 * @package `eluceo/ical`
		 * @source https://github.com/markuspoerschke/iCal
		 * @docs https://ical.poerschke.nrw/docs
		 */
		$event = new \Eluceo\iCal\Domain\Entity\Event(
			new \Eluceo\iCal\Domain\ValueObject\UniqueIdentifier( $uid )
		);

		$event->touch(
			new \Eluceo\iCal\Domain\ValueObject\Timestamp(
				Core\Date::getObject( $post->post_modified )
			)
		);

		// NOTE: firstly check for the date for early bailing!
		if ( $the_date ) {

			if ( is_a( $the_date, 'DateTimeInterface' ) ) {

				$final = $the_date;

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $the_date )
					)
				);

			} else if ( is_callable( $the_date ) && ( $called = call_user_func_array( $the_date, [ $post, $context ] ) ) ) {

				if ( is_array( $called ) ) {

					$final = $called[0];

					$event->setOccurrence(
						new \Eluceo\iCal\Domain\ValueObject\TimeSpan( $called[0], $called[1] )
					);

				} else {

					$final = $called;

					$event->setOccurrence(
						new \Eluceo\iCal\Domain\ValueObject\SingleDay(
							new \Eluceo\iCal\Domain\ValueObject\Date( $called )
						)
					);
				}

			} else if ( $the_date ) {

				$final = Core\Date::getObject( $the_date );

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $final )
					)
				);
			}

		} else if ( ( $datestart = PostTypeFields::getFieldDate( 'datestart', $post->ID ) )
			&& ( $dateend = PostTypeFields::getFieldDate( 'dateend', $post->ID ) ) ) {

			$final = $datestart;

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\TimeSpan( $datestart, $dateend )
			);

		} else if ( $datetime = PostTypeFields::getFieldDate( 'datetime', $post->ID ) ) {

			$final = $datetime;

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $datetime )
				)
			);

		} else if ( $date = PostTypeFields::getFieldDate( 'date', $post->ID ) ) {

			$final = $date;

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $date )
				)
			);

		} else if ( $published = PostTypeFields::getFieldRaw( 'published', $post->ID, 'meta', TRUE ) ) {

			if ( $final = gEditorial\Misc\DateParser::parse( $published, PostTypeFields::getDefaultCalendar( 'meta', FALSE ) ) ) {

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $final )
					)
				);

			} else {

				$final = Core\Date::getObject( $post->post_date );

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $final )
					)
				);
			}

		} else {

			// no extra field data: using the post date
			$final = Core\Date::getObject( $post->post_date );

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $final )
				)
			);
		}

		if ( $the_summary ) {

			if ( is_array( $the_summary ) && is_callable( $the_summary ) ) {

				$summary = call_user_func_array( $the_summary, [ $post, $context, $final ] );

			} else if ( Core\Text::has( $the_summary, '{{' ) ) {

				$summary = Core\Text::replaceTokens( $the_summary, WordPress\Post::summary( $post, $context ) );

			} else {

				$summary = $the_summary;
			}

		} else {

			$summary = WordPress\Post::fullTitle( $post );
		}

		if ( $summary = apply_filters( static::BASE.'_calendars_post_summary',
			$summary,
			$post,
			$context,
			$final
		) )
			$event->setSummary( Core\Text::prepDescForICAL( $summary ) );

		if ( $link = apply_filters( static::BASE.'_calendars_post_url',
			WordPress\Post::shortlink( $post ),
			$post,
			$context,
			$final
		) )
			$event->setUrl( new \Eluceo\iCal\Domain\ValueObject\Uri( $link ) );

		if ( $desc = apply_filters( static::BASE.'_calendars_post_description',
			WordPress\Strings::prepDescription( $post->post_excerpt, TRUE, FALSE ),
			$post,
			$context,
			$final
		) )
			$event->setDescription( Core\Text::prepDescForICAL( $desc ) );

		if ( $email = PostTypeFields::getFieldRaw( 'email_address', $post->ID, 'meta', TRUE ) ) {

			$organizer = new \Eluceo\iCal\Domain\ValueObject\Organizer(
				new \Eluceo\iCal\Domain\ValueObject\EmailAddress( $email ) );

			$event->setOrganizer( $organizer );
		}

		if ( is_null( $the_location ) ) {

			$venue = Locations::getPostLocation( $post, $context );

		} else if ( $the_location ) {

			if ( is_array( $the_location ) && is_callable( $the_location ) ) {

				$venue = call_user_func_array( $the_location, [ $post, $context, $final ] );

			} else if ( Core\Text::has( $the_location, '{{' ) ) {

				$venue = Core\Text::replaceTokens( $the_location, WordPress\Post::summary( $post, $context ) );

			} else if ( ! is_array( $the_location ) ) {

				$venue = [
					'address' => $the_location,
					'title'   => '',
				];

			} else {

				$venue = self::atts( [
					'address' => '',
					'title'   => '',
					'latlng'  => '',
				], $the_location );
			}
		}

		if ( $venue ) {

			$location = new \Eluceo\iCal\Domain\ValueObject\Location( $venue['address'], $venue['title'] );

			if ( ! empty( $venue['latlng'] ) && Core\LatLng::is( $venue['latlng'] ) )
				$location = $location->withGeographicPosition(
					new \Eluceo\iCal\Domain\ValueObject\GeographicPosition(
						...Core\LatLng::extract( $venue['latlng'] ) ) );

			$event->setLocation( $location );
		}


		return $event;
	}

	/**
	 * Retrieves calendar events based on a singular term.
	 * TODO: handle `touch`
	 *
	 * @param int|object $term
	 * @param string $context
	 * @param mixed $the_date
	 * @param mixed $the_summary
	 * @param mixed $the_location
	 * @return false|object
	 */
	public static function getTermEvent( $term, $context = NULL, $the_date = NULL, $the_summary = NULL, $the_location = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		$final = $summary = $venue = FALSE;

		// @REF: https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.7
		$uid = implode( '-', [
			WordPress\Site::name(),
			$term->taxonomy,
			$term->term_id,
			$context ?? static::ICAL_DEFAULT_CONTEXT,
		] );

		/**
		 * @package `eluceo/ical`
		 * @source https://github.com/markuspoerschke/iCal
		 * @docs https://ical.poerschke.nrw/docs
		 */
		$event = new \Eluceo\iCal\Domain\Entity\Event(
			new \Eluceo\iCal\Domain\ValueObject\UniqueIdentifier( $uid )
		);

		// NOTE: firstly check for the date for early bailing!
		if ( $the_date ) {

			if ( is_a( $the_date, 'DateTimeInterface' ) ) {

				$final = $the_date;

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $the_date )
					)
				);

			} else if ( is_callable( $the_date ) && ( $called = call_user_func_array( $the_date, [ $term, $context ] ) ) ) {

				if ( is_array( $called ) ) {

					$final = $called[0];

					$event->setOccurrence(
						new \Eluceo\iCal\Domain\ValueObject\TimeSpan( $called[0], $called[1] )
					);

				} else {

					$final = $called;

					$event->setOccurrence(
						new \Eluceo\iCal\Domain\ValueObject\SingleDay(
							new \Eluceo\iCal\Domain\ValueObject\Date( $called )
						)
					);
				}

			} else if ( $the_date ) {

				$final = Core\Date::getObject( $the_date );

				$event->setOccurrence(
					new \Eluceo\iCal\Domain\ValueObject\SingleDay(
						new \Eluceo\iCal\Domain\ValueObject\Date( $final )
					)
				);
			}

		} else if ( ( $datestart = TaxonomyFields::getFieldDate( 'datestart', $term->term_id ) )
			&& ( $dateend = TaxonomyFields::getFieldDate( 'dateend', $term->term_id ) ) ) {

			$final = $datestart;

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\TimeSpan( $datestart, $dateend )
			);

		} else if ( $datetime = TaxonomyFields::getFieldDate( 'datetime', $term->term_id ) ) {

			$final = $datetime;

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $datetime )
				)
			);

		} else if ( $date = TaxonomyFields::getFieldDate( 'date', $term->term_id ) ) {

			$final = $date;

			$event->setOccurrence(
				new \Eluceo\iCal\Domain\ValueObject\SingleDay(
					new \Eluceo\iCal\Domain\ValueObject\Date( $date )
				)
			);

		} else {

			return FALSE; // no data, no event!
		}

		if ( $the_summary ) {

			if ( is_array( $the_summary ) && is_callable( $the_summary ) ) {

				$summary = call_user_func_array( $the_summary, [ $term, $context, $final ] );

			} else if ( Core\Text::has( $the_summary, '{{' ) ) {

				$summary = Core\Text::replaceTokens(
					$the_summary,
					WordPress\Term::summary( $term, $context )
				);

			} else {

				$summary = $the_summary;
			}

		} else {

			$summary = WordPress\Post::title( $term );
		}

		if ( $summary = apply_filters( static::BASE.'_calendars_term_summary',
			$summary,
			$term,
			$context,
			$final
		) )
			$event->setSummary( Core\Text::prepDescForICAL( $summary ) );

		if ( $link = apply_filters( static::BASE.'_calendars_term_url',
			WordPress\Term::shortlink( $term ),
			$term,
			$context,
			$final
		) )
			$event->setUrl( new \Eluceo\iCal\Domain\ValueObject\Uri( $link ) );

		if ( $desc = apply_filters( static::BASE.'_calendars_term_description',
			WordPress\Strings::prepDescription( $term->description, TRUE, FALSE ),
			$term,
			$context,
			$final
		) )
			$event->setDescription( Core\Text::prepDescForICAL( $desc ) );

		if ( $email = TaxonomyFields::getFieldRaw( 'email', $term->term_id, 'terms', TRUE ) ) {

			$organizer = new \Eluceo\iCal\Domain\ValueObject\Organizer(
				new \Eluceo\iCal\Domain\ValueObject\EmailAddress( $email ) );

			$event->setOrganizer( $organizer );
		}

		if ( is_null( $the_location ) ) {

			$venue = Locations::getTermLocation( $term, $context );

		} else if ( $the_location ) {

			if ( is_array( $the_location ) && is_callable( $the_location ) ) {

				$venue = call_user_func_array( $the_location, [ $term, $context, $final ] );

			} else if ( Core\Text::has( $the_location, '{{' ) ) {

				$venue = Core\Text::replaceTokens( $the_location, WordPress\Term::summary( $term, $context ) );

			} else if ( ! is_array( $the_location ) ) {

				$venue = [
					'address' => $the_location,
					'title'   => '',
				];

			} else {

				$venue = self::atts( [
					'address' => '',
					'title'   => '',
					'latlng'  => '',
				], $the_location );
			}
		}

		if ( $venue ) {

			$location = new \Eluceo\iCal\Domain\ValueObject\Location( $venue['address'], $venue['title'] );

			if ( ! empty( $venue['latlng'] ) && Core\LatLng::is( $venue['latlng'] ) )
				$location = $location->withGeographicPosition(
					new \Eluceo\iCal\Domain\ValueObject\GeographicPosition(
						...Core\LatLng::extract( $venue['latlng'] ) ) );

			$event->setLocation( $location );
		}

		return $event;
	}

	public static function sanitizeContextForLink( $context = NULL, $target = NULL, $object = NULL )
	{
		$filtered = apply_filters( static::BASE.'_calendars_sanitize_ical_context',
			$context ?? static::ICAL_DEFAULT_CONTEXT,
			$target,
			$object
		);

		if ( ! $filtered || static::ICAL_DEFAULT_CONTEXT === $filtered )
			return NULL;

		$filtered = Core\Text::trim( $filtered );

		if ( ! in_array( $filtered, [
			static::ICAL_TIMESPAN_CONTEXT,
			// 'woocommerce', // MAYBE: for products
		], TRUE ) )
			return NULL;

		return $filtered;
	}

	public static function linkPostCalendar( $post = NULL, $context = NULL )
	{
		if ( self::const( 'GEDITORIAL_DISABLE_ICAL' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$sanitized = self::sanitizeContextForLink( $context, 'post', $post );

		return apply_filters( static::BASE.'_calendars_post_link',
			WordPress\Post::endpointURL(
				static::REWRITE_ENDPOINT_NAME,
				$post,
				$sanitized
			),
			$post,
			$sanitized
		);
	}

	public static function linkTermCalendar( $term = NULL, $context = NULL )
	{
		if ( self::const( 'GEDITORIAL_DISABLE_ICAL' ) )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		$sanitized = self::sanitizeContextForLink( $context, 'term', $term );

		return apply_filters( static::BASE.'_calendars_term_link',
			WordPress\Term::endpointURL(
				static::REWRITE_ENDPOINT_NAME,
				$term,
				$sanitized
			),
			$term,
			$sanitized
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
			'gregorian'     => _x( 'Gregorian', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'japanese'      => _x( 'Japanese', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'buddhist'      => _x( 'Buddhist', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'chinese'       => _x( 'Chinese', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			'persian'       => _x( 'Persian', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'indian'        => _x( 'Indian', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			'islamic'       => _x( 'Islamic', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'islamic-civil' => _x( 'Islamic-Civil', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'coptic'        => _x( 'Coptic', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
			// 'ethiopic'      => _x( 'Ethiopic', 'Service: Calendars: Default Calendar Type', 'geditorial' ),
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

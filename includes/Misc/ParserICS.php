<?php namespace geminorum\gEditorial\Misc;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class ParserICS extends Core\Base
{

	/**
	 * Best Practices and Considerations
	 *
	 * - Error handling: Always verify that each event has a valid `DTSTART`
	 * and `DTEND`. Log or skip otherwise.
	 *
	 * - Memory limits: For very large calendars, consider processing events in
	 * batches or using streaming techniques.
	 *
	 * - Time zone handling: This example assumes all-day events using
	 * the `YYYYMMDD` format. Parsing timed events with time zone support
	 * requires additional logic.
	 *
	 * - Date formats: The output format `Y-m-d` is universally usable for
	 * comparisons and storage, but you can adjust formatting as needed.
	 *
	 * FAQ
	 *
	 * - Can this handle recurring events?
	 * Not by default. If you need to handle recurrence rules (`RRULE`), consider using a third-party library that supports recurrence expansion.
	 *
	 * - What happens if `DTEND` is before `DTSTART`?
	 * These events are ignored in the example above. You may wish to validate and skip or log them.
	 *
	 * - Can I convert these dates into a calendar view?
	 * Yes, the output array of `Y-m-d` strings can be fed into any JavaScript calendar library or custom UI component.
	 *
	 * - Are partial-day events supported?
	 * The example targets full-day spans. To support time-based events (`T` format), you’d need to parse `DTSTART:YYYYMMDDTHHmmSS` and include time handling.
	 *
	 * @source https://thewebsiteengineer.com/blog/how-to-parse-icalendar-ics-links-in-php-for-availability-or-scheduling/
	 */

	/**
	 * Normalize Line Endings and Unfold Wrapped Lines.
	 *
	 * @param string $content
	 * @return array
	 */
	public static function normalize( string $content ): array {

		$content = preg_replace( "/\r\n[ \t]/", '', $content );      // Unfold lines
		$content = str_replace( [ "\r\n", "\r" ], "\n", $content );  // Normalize line endings

		return array_filter( array_map( 'trim', explode( "\n", $content ) ) );
	}

	/**
	 * Extract `VEVENT` Blocks.
	 *
	 * @param array $lines
	 * @return array
	 */
	public static function extract( array $lines ): array {

		$events  = [];
		$current = [];
		$inEvent = FALSE;

		foreach ( $lines as $line ) {

			if ( 0 === strpos( $line, 'BEGIN:VEVENT' ) ) {
				$inEvent = TRUE;
				$current = [];
				continue;
			}

			if ( 0 === strpos( $line, 'END:VEVENT' ) ) {

				if ( isset( $current['DTSTART'], $current['DTEND'] ) )
					$events[] = $current;

				$inEvent = FALSE;
				continue;
			}

			if ( $inEvent ) {

				if ( 0 === strpos( $line, 'DTSTART' ) )
					$current['DTSTART'] = $line;

				else if ( 0 === strpos( $line, 'DTEND' ) )
					$current['DTEND'] = $line;
			}
		}

		return $events;
	}

	/**
	 * Expand Date Ranges into Individual Days.
	 *
	 * @param array $event
	 * @return array
	 */
	public static function expand( array $event ): array {

		preg_match( '/:(\d{8})/', $event['DTSTART'], $startMatch );
		preg_match( '/:(\d{8})/', $event['DTEND'],   $endMatch   );

		if ( empty( $startMatch[1] ) || empty( $endMatch[1] ) )
			return [];

		$start = \DateTime::createFromFormat( 'Ymd', $startMatch[1] );
		$end   = \DateTime::createFromFormat( 'Ymd', $endMatch[1] );

		if ( ! $start || ! $end )
			return [];

		$interval = new \DateInterval( 'P1D' );

		$end->modify( '+1 day' ); // Ensure `DTEND` (exclusive) is included

		$range = new \DatePeriod( $start, $interval, $end );

		$dates = [];

		foreach ( $range as $date )
			$dates[] = $date->format( 'Y-m-d' );

		return $dates;
	}

	/**
	 * Full Parser to Extract All Dates.
	 *
	 * @param string $content
	 * @return array
	 */
	public static function doParse( string $content ): array {

		$dates  = [];
		$lines  = self::normalize( $content );
		$events = self::extract( $lines );

		foreach ( $events as $event )
			$dates = array_merge( $dates, self::expand( $event ) );

		$dates = array_unique( $dates );

		sort( $dates );

		return $dates;
	}

	public static function getRemote( $url )
	{
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) )
			return FALSE;

		return self::doParse( wp_remote_retrieve_body( $response ) );
	}
}

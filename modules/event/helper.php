<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEventHelper extends gEditorialHelper
{

	// http://sabre.io/vobject/usage/



////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

	// https://github.com/markuspoerschke/iCal
	function createICal()
	{
		$vCalendar = new \Eluceo\iCal\Component\Calendar('www.example.com');
		$vEvent = new \Eluceo\iCal\Component\Event();


		$vEvent
			->setDtStart(new \DateTime('2012-12-24'))
			->setDtEnd(new \DateTime('2012-12-24'))
			->setNoTime(true)
			->setSummary('Christmas')
		;

		$vCalendar->addComponent($vEvent);


		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="cal.ics"');
		echo $vCalendar->render();
	}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


	// https://github.com/johngrogg/ics-parser
	public static function icalParse()
	{
		$ical = new ICal('https://www.google.com/calendar/ical/mobahesat%40gmail.com/public/basic.ics');
		// $ical = new ICal(GEDITORIAL_DIR.'modules/event/basic.ics');

		// gnetwork_dump($ical->events());

		$events= $ical->events();

		// The ical date
		$date = $events[0]['DTSTART'];
		echo $date;

		// The Unix timestamp
		echo $ical->iCalDateToUnixTimestamp($date);

		// The number of events
		echo $ical->event_count;

		// The number of events
		echo $ical->todo_count;

		foreach ($events as $event) {
			echo 'SUMMARY: ' . @$event['SUMMARY'] . "<br />\n";
			echo 'DTSTART: ' . $event['DTSTART'] . ' - UNIX-Time: ' . $ical->iCalDateToUnixTimestamp($event['DTSTART']) . "<br />\n";
			echo 'DTEND: ' . $event['DTEND'] . "<br />\n";
			echo 'DTSTAMP: ' . $event['DTSTAMP'] . "<br />\n";
			echo 'UID: ' . @$event['UID'] . "<br />\n";
			echo 'CREATED: ' . @$event['CREATED'] . "<br />\n";
			echo 'LAST-MODIFIED: ' . @$event['LAST-MODIFIED'] . "<br />\n";
			echo 'DESCRIPTION: ' . @$event['DESCRIPTION'] . "<br />\n";
			echo 'LOCATION: ' . @$event['LOCATION'] . "<br />\n";
			echo 'SEQUENCE: ' . @$event['SEQUENCE'] . "<br />\n";
			echo 'STATUS: ' . @$event['STATUS'] . "<br />\n";
			echo 'TRANSP: ' . @$event['TRANSP'] . "<br />\n";
			echo 'ORGANIZER: ' . @$event['ORGANIZER'] . "<br />\n";
			echo 'ATTENDEE(S): ' . @$event['ATTENDEE'] . "<br />\n";
			echo '<hr/>';
		}
	}


















////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

	// http://stackoverflow.com/a/18908257/4864081
	// http://severinghaus.org/projects/icv/
	// http://www.kanzaki.com/docs/ical/

	function dateToCal($timestamp) {
	  return date('Ymd\Tgis\Z', $timestamp);
	}

	function escapeString($string) {
	  return preg_replace('/([\,;])/','\\\$1', $string);
	}

	function ics(){
		$eol = "\r\n";
		$load = "BEGIN:VCALENDAR" . $eol .
		"VERSION:2.0" . $eol .
		"PRODID:-//project/author//NONSGML v1.0//EN" . $eol .
		"CALSCALE:GREGORIAN" . $eol .
		"BEGIN:VEVENT" . $eol .
		"DTEND:" . dateToCal($end) . $eol .
		"UID:" . $id . $eol .
		"DTSTAMP:" . dateToCal(time()) . $eol .
		"DESCRIPTION:" . htmlspecialchars($title) . $eol .
		"URL;VALUE=URI:" . htmlspecialchars($url) . $eol .
		"SUMMARY:" . htmlspecialchars($description) . $eol .
		"DTSTART:" . dateToCal($start) . $eol .
		"END:VEVENT" . $eol .
		"END:VCALENDAR";

		$filename="Event-".$id;

		// Set the headers
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);

		// Dump load
		echo $load;
	}

	// http://www.wikiwand.com/en/ICalendar



}

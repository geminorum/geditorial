<?php namespace geminorum\gEditorial\Modules\Today;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class ModuleTemplate extends gEditorial\Template
{

	const MODULE = 'today';

	// http://keith-wood.name/datepick.html#layout
	public static function line()
	{

	}

	// FIXME: DRAFT
	// draws a calendar
	// @REF: https://davidwalsh.name/php-calendar
	// @REF: https://davidwalsh.name/php-calendar-controls
	public static function calendar( $atts = [], $the_day = NULL, $default_type = 'gregorian' )
	{

		// $period = Datetime::monthFirstAndLast( $type, );


		/* date settings */
		$month = (int) ($_GET['month'] ? $_GET['month'] : date('m'));
		$year = (int)  ($_GET['year'] ? $_GET['year'] : date('Y'));

		/* select month control */
		$select_month_control = '<select name="month" id="month">';
		for ( $x = 1; $x <= 12; $x++ ) {
			$select_month_control.= '<option value="'.$x.'"'.($x != $month ? '' : ' selected="selected"').'>'.date('F',mktime(0,0,0,$x,1,$year)).'</option>';
		}
		$select_month_control.= '</select>';

		/* select year control */
		$year_range = 7;
		$select_year_control = '<select name="year" id="year">';
		for ( $x = ($year-floor($year_range/2)); $x <= ($year+floor($year_range/2)); $x++) {
			$select_year_control.= '<option value="'.$x.'"'.($x != $year ? '' : ' selected="selected"').'>'.$x.'</option>';
		}
		$select_year_control.= '</select>';

		/* "next month" control */
		$next_month_link = '<a href="?month='.($month != 12 ? $month + 1 : 1).'&year='.($month != 12 ? $year : $year + 1).'" class="control">Next Month >></a>';

		/* "previous month" control */
		$previous_month_link = '<a href="?month='.($month != 1 ? $month - 1 : 12).'&year='.($month != 1 ? $year : $year - 1).'" class="control"><< Previous Month</a>';

		/* bringing the controls together */
		$controls = '<form method="get">'.$select_month_control.$select_year_control.' <input type="submit" name="submit" value="Go" />      '.$previous_month_link.'     '.$next_month_link.' </form>';

		echo $controls;


		// $month, $year

		$headings = [ 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday' ];

		$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';
		$calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode( '</td><td class="calendar-day-head">', $headings ).'</td></tr>';

		// days and weeks vars now ...
		$running_day       = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );
		$days_in_month     = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		$days_in_this_week = 1;
		$day_counter       = 0;
		$dates_array       = [];

		// row for week one
		$calendar.= '<tr class="calendar-row">';

		// print "blank" days until the first of the current week
		for ( $x = 0; $x < $running_day; $x++ ) {
			$calendar.= '<td class="calendar-day-np"> </td>';
			$days_in_this_week++;
		}

		// keep going with days ...
		for ( $list_day = 1; $list_day <= $days_in_month; $list_day++ ) {

			$calendar.= '<td class="calendar-day">';

				// add in the day number
				$calendar.= '<div class="day-number">'.$list_day.'</div>';

				// QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !!
				$calendar.= str_repeat('<p> </p>',2);

			$calendar.= '</td>';

			if ( $running_day == 6 ) {

				$calendar.= '</tr>';

				if ( ( $day_counter + 1 ) != $days_in_month )
					$calendar.= '<tr class="calendar-row">';

				$running_day = -1;
				$days_in_this_week = 0;
			}

			$days_in_this_week++;
			$running_day++;
			$day_counter++;
		}

		// finish the rest of the days in the week
		if ( $days_in_this_week < 8 ) {
			for ( $x = 1; $x <= (8 - $days_in_this_week); $x++ ) {
				$calendar.= '<td class="calendar-day-np"> </td>';
			}
		}

		return $calendar.'</tr></table>';
	}

}

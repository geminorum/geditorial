<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialDate extends gEditorialBaseCore
{

	public static function monthFirstAndLast( $year, $month, $format = 'Y-m-d H:i:s' )
	{
		$start = new \DateTime( $year.'-'.$month.'-01 00:00:00' );
		$end   = $start->modify( '+1 month -1 day -1 minute' );

		return array(
			$start->format( $format ),
			$end->format( $format ),
		);
	}

	public static function htmlDateTime( $time, $gmt = NULL, $format = 'l, F j, Y', $title = FALSE )
	{
		return gEditorialHTML::tag( 'time', array(
			'datetime' => date( 'c', ( $gmt ? $gmt : $time ) ),
			'title'    => $title,
		), date_i18n( $format, $time ) );
	}
}

<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class GCalEvents extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'gcal_events';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Google Calendar', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays list of events from a public Google Calendar.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget_html( $args, $instance )
	{
		if ( ! $data = Core\Third::getGoogleCalendarEvents( $instance ) )
			return FALSE;

		$empty = empty( $instance['empty'] ) ? FALSE : $instance['empty'];

		if ( empty( $data->items ) && ! $empty )
			return TRUE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance, TRUE, $data->summary );

		if ( empty( $data->items ) ) {

			Core\HTML::desc( $empty, TRUE, '-empty' );

		} else {

			$formats = gEditorial\Datetime::dateFormats( FALSE );

			echo '<div class="-list-wrap gcal-events"><ul class="-items">';

			foreach ( $data->items as $item ) {

				$timestamp = strtotime( $item->start->date );

				echo '<li>';
					echo '<div class="event-wrapper" itemscope itemtype="http://schema.org/Event">';

					echo '<div class="event-date" itemprop="startDate" content="'.date( 'c', $timestamp ).'">'
						.Core\Date::get( $formats['daydate'], $timestamp ).'</div>';

					echo '<div class="event-title" itemprop="name">'.WordPress\Strings::prepTitle( $item->summary ).'</div>';

					if ( ! empty( $instance['display_time'] ) )
						echo '<div class="event-time">'.Core\Date::get( $formats['timeampm'], $timestamp ).'</div>';

					echo '</div>';
				echo '</li>';
			}

			echo '</ul></div>';
		}

		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_custom_code( $instance, '', 'calendar_id', _x( 'Calendar ID:', 'Widget: Google Calendar', 'geditorial-widgets' ) );
		$this->form_custom_code( $instance, '', 'api_key', _x( 'Your API key:', 'Widget: Google Calendar', 'geditorial-widgets' ) );

		Core\HTML::desc( sprintf(
			/* translators: `%s`: documents URL */
			_x( 'Get your API key <a href="%s" target="_blank">here</a>.', 'Widget: Google Calendar', 'geditorial-widgets' ),
			'https://console.developers.google.com/'
		) );

		$this->form_custom_code( $instance, '', 'time_min', _x( 'Start from (YYYY-MM-DD):', 'Widget: Google Calendar', 'geditorial-widgets' ) );
		$this->form_number( $instance, 5, 'max_results', _x( 'Max results:', 'Widget: Google Calendar', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'display_time', _x( 'Display Time', 'Widget: Google Calendar', 'geditorial-widgets' ) );

		$this->form_custom_empty( $instance, _x( 'No events scheduled.', 'Widget: Google Calendar', 'geditorial-widgets' ) );
		$this->form_context( $instance );
		$this->form_class( $instance );

		echo '<div class="-group">';
		$this->form_open_widget( $instance );
		$this->form_after_title( $instance );
		$this->form_close_widget( $instance );
		echo '</div>';

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [ 'display_time' ], [
			'calendar_id' => 'key',
			'api_key'     => 'key',
			'time_min'    => 'key',
			'time_min'    => 'key',
			'max_results' => 'digit',
		] );
	}
}

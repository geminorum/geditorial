<?php namespace geminorum\gEditorial\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Core\Date;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Third;

class GCalEvents extends gEditorial\Widget
{

	const MODULE = 'widgets';

	public static function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'gcal_events',
			'class'  => 'gcal-events',
			'title'  => _x( 'Editorial: Google Calendar', 'Modules: Widgets: Widget Title', GEDITORIAL_TEXTDOMAIN ),
			'desc'   => _x( 'Displays list of events from a public Google Calendar.', 'Modules: Widgets: Widget Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public function widget_html( $args, $instance )
	{
		if ( ! $data = Third::getGoogleCalendarEvents( $instance ) )
			return FALSE;

		$empty = empty( $instance['empty'] ) ? FALSE : $instance['empty'];

		if ( empty( $data->items ) && ! $empty )
			return TRUE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance, $data->summary );

		if ( empty( $data->items ) ) {

			HTML::desc( $empty, TRUE, '-empty' );

		} else {

			$formats = Helper::dateFormats( FALSE );

			echo '<ul>';

			foreach ( $data->items as $item ) {

				$timestamp = strtotime( $item->start->date );

				echo '<li>';
					echo '<div class="event-wrapper" itemscope itemtype="http://schema.org/Event">';

					echo '<div class="event-date" itemprop="startDate" content="'.date( 'c', $timestamp ).'">'
						.date_i18n( $formats['dateonly'], $timestamp ).'</div>';

					echo '<div class="event-title" itemprop="name">'.Helper::prepTitle( $item->summary ).'</div>';

					if ( ! empty( $instance['display_time'] ) )
						echo '<div class="event-time">'.date_i18n( $formats['timeampm'], $timestamp ).'</div>';

					echo '</div>';
				echo '</li>';
			}

			echo '</ul>';
		}

		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );

		$this->form_custom_code( $instance, '', 'calendar_id', _x( 'Calendar ID:', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_custom_code( $instance, '', 'api_key', _x( 'Your API key:', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ) );

		HTML::desc( sprintf( _x( 'Get your API key <a href="%s" target="_blank">here</a>.', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ), 'https://console.developers.google.com/' ) );

		$this->form_custom_code( $instance, '', 'time_min', _x( 'Start from (YYYY-MM-DD):', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_number( $instance, 5, 'max_results', _x( 'Max results:', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'display_time', _x( 'Display Time', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ) );

		$this->form_custom_empty( $instance, _x( 'No events scheduled.', 'Modules: Widgets: Widget: Google Calendar', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_context( $instance );
		$this->form_class( $instance );

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$instance = $old;

		$instance['title']      = sanitize_text_field( $new['title'] );
		$instance['title_link'] = strip_tags( $new['title_link'] );
		$instance['context']    = strip_tags( $new['context'] );
		$instance['class']      = strip_tags( $new['class'] );
		$instance['empty']      = wp_kses_post( $new['empty'] );

		$instance['calendar_id']  = strip_tags( $new['calendar_id'] );
		$instance['api_key']      = strip_tags( $new['api_key'] );
		$instance['time_min']     = strip_tags( $new['time_min'] );
		$instance['max_results']  = intval( $new['max_results'] );
		$instance['display_time'] = isset( $new['display_time'] );

		$this->flush_widget_cache();

		return $instance;
	}
}

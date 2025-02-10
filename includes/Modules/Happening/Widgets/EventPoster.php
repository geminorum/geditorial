<?php namespace geminorum\gEditorial\Modules\Happening\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Modules\Happening\ModuleTemplate;
use geminorum\gEditorial\WordPress;

class EventPoster extends gEditorial\Widget
{

	const MODULE = 'happening';
	const WIDGET = 'happening_event_poster';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Event Poster', 'Widget Title', 'geditorial-happening' ),
			'desc'  => _x( 'Displays selected, connected or current event poster.', 'Widget Description', 'geditorial-happening' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! $instance['latest_event']
			&& ! $instance['page_id']
			&& ! is_singular() )
				return;

		if ( ! empty( $instance['latest_event'] ) )
			$prefix = '_latest_event';

		else if ( ! empty( $instance['page_id'] ) )
			$prefix = '_event_'.$instance['page_id'];

		else
			$prefix = '_queried_'.get_queried_object_id();

		$this->widget_cache( $args, $instance, $prefix );
	}

	public function widget_html( $args, $instance )
	{
		$link = 'parent';
		$type = self::constant( 'main_posttype', 'event' );

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_event'] ) )
			$link = FALSE;

		$atts = [
			'type' => $type,
			'size' => empty( $instance['image_size'] ) ? WordPress\Media::getAttachmentImageDefaultSize( $type ) : $instance['image_size'],
			'link' => $link,
			'echo' => FALSE,
		];

		if ( ! empty( $instance['latest_event'] ) )
			$atts['id'] = (int) ModuleTemplate::getLatestEventID();

		else if ( ! empty( $instance['page_id'] ) )
			$atts['id'] = (int) $instance['page_id'];

		else if ( is_singular( $atts['type'] ) )
			$atts['id'] = NULL;

		else if ( is_singular() )
			$atts['id'] = 'paired';

		if ( ! $html = ModuleTemplate::cover( $atts ) )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $html;
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$type = self::constant( 'main_posttype', 'event' );

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $type, _x( 'The Event:', 'Widget: Event Poster', 'geditorial-happening' ) );
		$this->form_image_size( $instance, NULL, 'image_size', $type );

		$this->form_checkbox( $instance, FALSE, 'latest_event', _x( 'Always the latest event', 'Widget: Event Poster', 'geditorial-happening' ) );
		$this->form_checkbox( $instance, TRUE, 'link_event', _x( 'Link to the event', 'Widget: Event Poster', 'geditorial-happening' ) );
		$this->form_custom_link( $instance );

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

		return $this->handle_update( $new, $old, [
			'latest_event',
			'link_event',
		] );
	}
}

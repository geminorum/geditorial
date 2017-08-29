<?php namespace geminorum\gEditorial\Widgets\Widgets;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class CustomHTML extends gEditorial\Widget
{

	const MODULE = 'widgets';

	protected function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'custom_html',
			'class'  => 'custom-html',
			'title'  => _x( 'Editorial Widgets: Custom HTML', 'Modules: Widgets: Widget Title', GEDITORIAL_TEXTDOMAIN ),
			'desc'   => _x( 'Displays arbitrary HTML code with support for shortcodes and embeds.', 'Modules: Widgets: Widget Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public function widget_html( $args, $instance )
	{
		global $wp_embed;

		if ( ! $content = trim( $instance['content'] ) )
			return FALSE;

		if ( ! empty( $instance['embeds'] ) ) {
			$content = $wp_embed->run_shortcode( $content );
			$content = $wp_embed->autoembed( $content );
		}

		if ( ! empty( $instance['shortcodes'] ) )
			$content = do_shortcode( $content );

		if ( ! empty( $instance['legacy'] ) )
			$content = apply_filters( 'widget_text', $content, $instance, $this );

		if ( ! empty( $instance['filters'] ) )
			$content = apply_filters( 'widget_custom_html_content', $content, $instance, $this );

		if ( ! $content )
			return FALSE;

		if ( ! empty( $instance['autop'] ) )
			$content = wpautop( $content );

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
		echo '<div class="textwidget custom-html-widget">';
			echo $content;
		echo '</div>';
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_class( $instance );

		$this->form_content( $instance );

		echo '<div class="-group">';

		$this->form_checkbox( $instance, FALSE, 'embeds', _x( 'Process Embeds', 'Modules: Widgets: Widget: Custom HTML', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'shortcodes', _x( 'Process Shortcodes', 'Modules: Widgets: Widget: Custom HTML', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'filters', _x( 'Process Filters', 'Modules: Widgets: Widget: Custom HTML', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'legacy', _x( 'Process Filters (Legacy)', 'Modules: Widgets: Widget: Custom HTML', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'autop', _x( 'Automatic Paragraphs', 'Modules: Widgets: Widget: Custom HTML', GEDITORIAL_TEXTDOMAIN ) );

		echo '</div>';

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$instance = $old;

		$instance['title']      = strip_tags( $new['title'] );
		$instance['title_link'] = strip_tags( $new['title_link'] );
		$instance['class']      = strip_tags( $new['class'] );

		if ( current_user_can( 'unfiltered_html' ) )
			$instance['content'] = $new['content'];
		else
			$instance['content'] = wp_kses_post( $new['content'] );

		$instance['embeds']     = (bool) $new['embeds'];
		$instance['shortcodes'] = (bool) $new['shortcodes'];
		$instance['filters']    = (bool) $new['filters'];
		$instance['legacy']     = (bool) $new['legacy'];
		$instance['autop']      = (bool) $new['autop'];

		$this->flush_widget_cache();

		return $instance;
	}
}

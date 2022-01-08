<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class CustomHTML extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'custom_html';

	public static function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'custom_html',
			'class'  => 'custom-html',
			'title'  => _x( 'Editorial: Custom HTML', 'Widget Title', 'geditorial-widgets' ),
			'desc'   => _x( 'Displays arbitrary HTML code with support for shortcodes and embeds.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget_html( $args, $instance )
	{
		global $wp_embed;

		if ( empty( $instance['content'] ) || ! ( $content = trim( $instance['content'] ) ) )
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

		return empty( $instance['bypasscache'] );
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );
		$this->form_class( $instance );

		$this->form_content( $instance );

		echo '<div class="-group">';
		$this->form_checkbox( $instance, FALSE, 'embeds', _x( 'Process Embeds', 'Widget: Custom HTML', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'shortcodes', _x( 'Process Shortcodes', 'Widget: Custom HTML', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'filters', _x( 'Process Filters', 'Widget: Custom HTML', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'legacy', _x( 'Process Filters (Legacy)', 'Widget: Custom HTML', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'autop', _x( 'Automatic Paragraphs', 'Widget: Custom HTML', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'bypasscache', _x( 'Bypass Caching', 'Widget: Custom HTML', 'geditorial-widgets' ) );
		echo '</div>';

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
			'embeds',
			'shortcodes',
			'filters',
			'legacy',
			'autop',
			'bypasscache',
		] );
	}
}

<?php namespace geminorum\gEditorial\Modules\WcWidgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\WordPress;

class WCMessage extends gEditorial\Widget
{

	const MODULE = 'wc_widgets';
	const WIDGET = 'wc_message';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: WooCommerce Message', 'Widget Title', 'geditorial-wc-widgets' ),
			'desc'  => _x( 'Displays a custom message in WooCommerce mark-up.', 'Widget Description', 'geditorial-wc-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( empty( $instance['content'] ) || ! ( $content = trim( $instance['content'] ) ) )
			return FALSE;

		$notice_type = empty( $instance['notice_type'] ) ? 'notice' : $instance['notice_type'];

		if ( ! empty( $instance['shortcodes'] ) )
			$content = WordPress\ShortCode::apply( $content );

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
			wc_print_notice( $content, $notice_type );
		$this->after_widget( $args, $instance );

		return empty( $instance['bypasscache'] );
	}

	private function _get_wc_notice_types()
	{
		return [
			'notice'  => _x( 'Notice', 'Widget: WCMessage', 'geditorial-wc-widgets' ),
			'success' => _x( 'Success', 'Widget: WCMessage', 'geditorial-wc-widgets' ),
			'error'   => _x( 'Error', 'Widget: WCMessage', 'geditorial-wc-widgets' ),
		];
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_open_group();
		$this->form_content( $instance );
		$this->form_close_group();

		$this->form_open_group( 'config' );
		$this->form_dropdown( $instance, $this->_get_wc_notice_types(), 'notice', 'notice_type', _x( 'Notice Type:', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		// $this->form_checkbox( $instance, FALSE, 'dismissible', _x( 'With a Dismis Button', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'shortcodes', _x( 'Process Shortcodes', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'filters', _x( 'Process Filters', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'legacy', _x( 'Process Filters (Legacy)', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'autop', _x( 'Automatic Paragraphs', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'bypasscache', _x( 'Bypass Caching', 'Widget: WCMessage', 'geditorial-wc-widgets' ) );
		$this->form_close_group();

		$this->form_open_group( 'heading' );
		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );
		$this->form_class( $instance );
		// $this->form_context( $instance );
		$this->form_close_group();

		$this->form_open_group( 'customs' );
		$this->form_open_widget( $instance );
		$this->form_after_title( $instance );
		$this->form_close_widget( $instance );
		$this->form_close_group();

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			// 'dismissible',
			'embeds',
			'shortcodes',
			'filters',
			'legacy',
			'autop',
			'bypasscache',
		], [
			'notice_type' => 'text',
		] );
	}
}

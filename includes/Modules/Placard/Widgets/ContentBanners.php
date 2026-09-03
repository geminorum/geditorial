<?php namespace geminorum\gEditorial\Modules\Placard\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;
use geminorum\gEditorial\Modules\Placard as ParentModule;

class ContentBanners extends gEditorial\Widget
{
	const MODULE = 'placard';
	const WIDGET = 'content_banners';

	public static function setup(): array
	{
		return [
			/* translators: `%s`: system string */
			'title' => _x( '%s: Content Banners', 'Widget Title', 'geditorial-placard' ),
			'desc'  => _x( 'Displays banners from selected source.', 'Widget Description', 'geditorial-placard' ),
		];
	}

	public function widget( $args, $instance ): void
	{
		$this->widget_cache(
			$args,
			$instance,
			$instance['page_id'] ?: get_queried_object_id(),
		);
	}

	public function widget_html( array $args, array $instance ): bool
	{
		$html = gEditorial()->module( static::MODULE )->main_shortcode( [
			'id'       => $instance['page_id'] ?: FALSE,
			'context'  => $instance['context'] ?: NULL,
			'template' => $instance['template'] ?: NULL,
			'wrap'     => FALSE,
		] );

		if ( ! $html )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $html;
		$this->after_widget( $args, $instance );

		return empty( $instance['bypasscache'] );
	}

	public function form( $instance ): void
	{
		$type = self::constant( 'main_posttype', 'content_banner' );

		$this->before_form( $instance );

		$this->form_open_group( 'config' );
			$this->form_page_id( $instance, '0', 'page_id', 'posttype', $type, _x( 'Content Banners', 'Widget: Content Banners', 'geditorial-placard' ) );
			$this->form_custom_code( $instance, '', 'template', _x( 'Template:', 'Widget: Content Banners', 'geditorial-placard' ) );
			$this->form_checkbox( $instance, FALSE, 'bypasscache' );
		$this->form_close_group();

		$this->form_open_group( 'heading' );
			$this->form_title( $instance );
			$this->form_title_link( $instance );
			$this->form_title_image( $instance );
			$this->form_class( $instance );
			$this->form_context( $instance );
		$this->form_close_group();

		$this->form_open_group( 'customs' );
			$this->form_open_widget( $instance );
			$this->form_after_title( $instance );
			$this->form_close_widget( $instance );
		$this->form_close_group();

		$this->after_form( $instance );
	}

	public function update( $new, $old ): array
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			'bypasscache',
		], [
			'template' => 'key',
		] );
	}
}

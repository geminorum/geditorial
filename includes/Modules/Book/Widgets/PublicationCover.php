<?php namespace geminorum\gEditorial\Modules\Book\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Modules\Book\ModuleTemplate;

class PublicationCover extends gEditorial\Widget
{

	const MODULE = 'book';
	const WIDGET = 'book_publication_cover';

	public static function setup()
	{
		return [
			'module' => 'book',
			'name'   => 'book_publication_cover',
			'class'  => 'book-publication-cover',
			'title'  => _x( 'Editorial: Publication Cover', 'Widget Title', 'geditorial-book' ),
			'desc'   => _x( 'Displays selected, connected or current publication cover.', 'Widget Description', 'geditorial-book' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( empty( $instance['page_id'] ) && ! is_singular() )
			return;

		if ( ! empty( $instance['page_id'] ) )
			$prefix = '_publication_'.$instance['page_id'];

		else
			$prefix = '_queried_'.get_queried_object_id();

		$this->widget_cache( $args, $instance, $prefix );
	}

	public function widget_html( $args, $instance )
	{
		$link = 'parent';
		$type = self::constant( 'publication_cpt', 'publication' );

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_publication'] ) )
			$link = FALSE;

		$atts = [
			'type' => $type,
			'size' => empty( $instance['image_size'] ) ? $type.'-thumbnail' : $instance['image_size'],
			'link' => $link,
			'echo' => FALSE,
		];

		if ( ! empty( $instance['page_id'] ) )
			$atts['id'] = (int) $instance['page_id'];

		else if ( is_singular( $atts['type'] ) )
			$atts['id'] = NULL;

		else if ( is_singular() ) // FIXME: it's better to catch not supported here
			$atts['id'] = 'paired';

		else
			return FALSE;

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
		$type = self::constant( 'publication_cpt', 'publication' );

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $type, _x( 'The Publication:', 'Widget: Publication Cover', 'geditorial-book' ) );
		$this->form_image_size( $instance, $type.'-thumbnail', 'image_size', $type );

		$this->form_checkbox( $instance, TRUE, 'link_publication', _x( 'Link to the publication', 'Widget: Publication Cover', 'geditorial-book' ) );
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
			'link_publication',
		] );
	}
}

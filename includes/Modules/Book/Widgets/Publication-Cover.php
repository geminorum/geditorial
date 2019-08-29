<?php namespace geminorum\gEditorial\Book\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Templates\Book as ModuleTemplate;

class PublicationCover extends gEditorial\Widget
{

	const MODULE = 'book';

	public static function setup()
	{
		return [
			'module' => 'book',
			'name'   => 'book_publication_cover',
			'class'  => 'book-publication-cover',
			'title'  => _x( 'Editorial: Publication Cover', 'Modules: Book: Widget Title', 'geditorial' ),
			'desc'   => _x( 'Displays selected, connected or current publication cover.', 'Modules: Book: Widget Description', 'geditorial' ),
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

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_publication'] ) )
			$link = FALSE;

		$atts = [
			'type' => self::constant( 'publication_cpt', 'publication' ),
			'size' => empty( $instance['image_size'] ) ? NULL : $instance['image_size'],
			'link' => $link,
			'echo' => FALSE,
		];

		if ( ! empty( $instance['page_id'] ) )
			$atts['id'] = (int) $instance['page_id'];

		else if ( is_singular( $atts['type'] ) )
			$atts['id'] = NULL;

		else if ( is_singular() ) // FIXME: it's better to catch not supported here
			$atts['id'] = 'assoc';

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
		$cpt = self::constant( 'publication_cpt', 'publication' );

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $cpt, _x( 'The Publication:', 'Modules: Book: Widget: Publication Cover', 'geditorial' ) );
		$this->form_image_size( $instance, $cpt.'-thumbnail', 'image_size', $cpt );

		$this->form_checkbox( $instance, TRUE, 'link_publication', _x( 'Link to the publication', 'Modules: Book: Widget: Publication Cover', 'geditorial' ) );
		$this->form_custom_link( $instance );

		$this->form_context( $instance );
		$this->form_class( $instance );

		$this->after_form( $instance );
	}

	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		$instance['title']            = strip_tags( $new_instance['title'] );
		$instance['title_link']       = strip_tags( $new_instance['title_link'] );
		$instance['page_id']          = intval( $new_instance['page_id'] );
		$instance['image_size']       = isset( $new_instance['image_size'] ) ? strip_tags( $new_instance['image_size'] ) : 'thumbnail';
		$instance['link_publication'] = isset( $new_instance['link_publication'] );
		$instance['custom_link']      = strip_tags( $new_instance['custom_link'] );
		$instance['context']          = strip_tags( $new_instance['context'] );
		$instance['class']            = strip_tags( $new_instance['class'] );

		$this->flush_widget_cache();

		return $instance;
	}
}

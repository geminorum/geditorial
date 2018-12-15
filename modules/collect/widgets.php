<?php namespace geminorum\gEditorial\Widgets\Collect;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Templates\Collect as ModuleTemplate;

class CollectionPoster extends gEditorial\Widget
{

	const MODULE = 'collect';

	public static function setup()
	{
		return [
			'module' => 'collect',
			'name'   => 'collect_collection_poster',
			'class'  => 'collect-collection-poster',
			'title'  => _x( 'Editorial: Collection Poster', 'Modules: Collect: Widget Title', GEDITORIAL_TEXTDOMAIN ),
			'desc'   => _x( 'Displays latest, selected, connected or current collection poster.', 'Modules: Collect: Widget Description', GEDITORIAL_TEXTDOMAIN ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! $instance['latest_collection']
			&& ! $instance['page_id']
			&& ! is_singular() )
				return;

		if ( ! empty( $instance['latest_collection'] ) )
			$prefix = '_latest_collection';

		else if ( ! empty( $instance['page_id'] ) )
			$prefix = '_collection_'.$instance['page_id'];

		else
			$prefix = '_queried_'.get_queried_object_id();

		$this->widget_cache( $args, $instance, $prefix );
	}

	public function widget_html( $args, $instance )
	{
		$link = 'parent';

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_collection'] ) )
			$link = FALSE;

		$atts = [
			'type'  => self::constant( 'collection_cpt', 'collection' ),
			'size'  => empty( $instance['image_size'] ) ? NULL : $instance['image_size'],
			'title' => empty( $instance['number_line'] ) ? 'title' : 'number',
			'link'  => $link,
			'echo'  => FALSE,
		];

		if ( ! empty( $instance['latest_collection'] ) )
			$atts['id'] = (int) ModuleTemplate::getLatestCollectionID();

		else if ( ! empty( $instance['page_id'] ) )
			$atts['id'] = (int) $instance['page_id'];

		else if ( is_singular( $atts['type'] ) )
			$atts['id'] = NULL;

		else if ( is_singular() )
			$atts['id'] = 'assoc';

		else
			return FALSE;

		if ( ! $html = ModuleTemplate::poster( $atts ) )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $html;
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$cpt = self::constant( 'collection_cpt', 'collection' );

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $cpt, _x( 'The Collection:', 'Modules: Collect: Widget: Collection Poster', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_image_size( $instance, $cpt.'-thumbnail', 'image_size', $cpt );

		$this->form_checkbox( $instance, FALSE, 'latest_collection', _x( 'Always the latest collection', 'Modules: Collect: Widget: Collection Poster', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, TRUE, 'number_line', _x( 'Display the Number Meta', 'Modules: Collect: Widget: Collection Poster', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, TRUE, 'link_collection', _x( 'Link to the collection', 'Modules: Collect: Widget: Collection Poster', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_custom_link( $instance );

		$this->form_context( $instance );
		$this->form_class( $instance );

		$this->after_form( $instance );
	}

	public function update( $new_instance, $old_instance )
	{
		$instance = $old_instance;

		$instance['title']             = strip_tags( $new_instance['title'] );
		$instance['title_link']        = strip_tags( $new_instance['title_link'] );
		$instance['page_id']           = intval( $new_instance['page_id'] );
		$instance['image_size']        = isset( $new_instance['image_size'] ) ? strip_tags( $new_instance['image_size'] ) : 'thumbnail';
		$instance['latest_collection'] = isset( $new_instance['latest_collection'] );
		$instance['number_line']       = isset( $new_instance['number_line'] );
		$instance['link_collection']   = isset( $new_instance['link_collection'] );
		$instance['custom_link']       = strip_tags( $new_instance['custom_link'] );
		$instance['context']           = strip_tags( $new_instance['context'] );
		$instance['class']             = strip_tags( $new_instance['class'] );

		$this->flush_widget_cache();

		return $instance;
	}
}

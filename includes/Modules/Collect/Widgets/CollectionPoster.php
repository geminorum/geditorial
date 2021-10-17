<?php namespace geminorum\gEditorial\Modules\Collect\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Modules\Collect\ModuleTemplate;

class CollectionPoster extends gEditorial\Widget
{

	const MODULE = 'collect';

	public static function setup()
	{
		return [
			'module' => 'collect',
			'name'   => 'collect_collection_poster',
			'class'  => 'collect-collection-poster',
			'title'  => _x( 'Editorial: Collection Poster', 'Widget Title', 'geditorial-collect' ),
			'desc'   => _x( 'Displays latest, selected, connected or current collection poster.', 'Widget Description', 'geditorial-collect' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( empty( $instance['latest_collection'] )
			&& empty( $instance['page_id'] ) && ! is_singular() )
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
		$type = self::constant( 'collection_cpt', 'collection' )

		if ( ! empty( $instance['custom_link'] ) )
			$link = $instance['custom_link'];

		else if ( empty( $instance['link_collection'] ) )
			$link = FALSE;

		$atts = [
			'type'  => $type,
			'size'  => empty( $instance['image_size'] ) ? $type.'-thumbnail' : $instance['image_size'],
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
			$atts['id'] = 'paired';

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
		$type = self::constant( 'collection_cpt', 'collection' );

		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );

		$this->form_page_id( $instance, '0', 'page_id', 'posttype', $type, _x( 'The Collection:', 'Widget: Collection Poster', 'geditorial-collect' ) );
		$this->form_image_size( $instance, $type.'-thumbnail', 'image_size', $type );

		$this->form_checkbox( $instance, FALSE, 'latest_collection', _x( 'Always the latest collection', 'Widget: Collection Poster', 'geditorial-collect' ) );
		$this->form_checkbox( $instance, TRUE, 'number_line', _x( 'Display the Number Line', 'Widget: Collection Poster', 'geditorial-collect' ) );
		$this->form_checkbox( $instance, TRUE, 'link_collection', _x( 'Link to the collection', 'Widget: Collection Poster', 'geditorial-collect' ) );
		$this->form_custom_link( $instance );

		$this->form_context( $instance );
		$this->form_class( $instance );

		$this->after_form( $instance );
	}

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			'latest_collection',
			'number_line',
			'link_collection',
		] );
	}
}

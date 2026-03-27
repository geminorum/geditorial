<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;
use geminorum\gEditorial\Modules\Widgets as ParentModule;

class ConnectedPosts extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'connected_posts';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Connected Posts', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays the manually connected items to the currently queried post.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		$this->widget_cache( $args, $instance, get_queried_object_id() );
	}

	public function widget_html( $args, $instance )
	{
		if ( ! is_singular() && ! is_single() )
			return FALSE;

		if ( empty( $instance['connection'] ) )
			return FALSE;

		if ( ! $post_id = get_queried_object_id() )
			return FALSE;

		$html = gEditorial\ShortCode::listPosts( 'objects2objects', '', '', [
			'connection' => $instance['connection'],
			'post_id'    => $post_id,
			'title'      => FALSE,
		] );

		if ( ! $html )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $html;
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_open_group( 'config' );
			$this->form_connection( $instance );
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

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			'bypasscache',
		] );
	}
}

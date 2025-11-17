<?php namespace geminorum\gEditorial\Modules\Byline\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;
use geminorum\gEditorial\Modules\Byline;

class FeaturedCards extends gEditorial\Widget
{
	const MODULE = 'byline';
	const WIDGET = 'featured_cards';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Featured Cards', 'Widget Title', 'geditorial-byline' ),
			'desc'  => _x( 'Displays byline card rows for currently queried post.', 'Widget Description', 'geditorial-byline' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( is_singular( self::posttypes() ) )
			$this->widget_cache(
				$args,
				$instance,
				sprintf( '_queried_%d', get_queried_object_id() )
			);
	}

	public function widget_html( $args, $instance )
	{
		if ( ! $post_id = get_queried_object_id() )
			return FALSE;

		$html = Byline\ModuleTemplate::renderFeatured( [
			'default'  => FALSE,
			'echo'     => FALSE,
			'hidden'   => ! empty( $instance['hidden'] ),
			'featured' => ! empty( $instance['hidden'] ),
			'template' => empty( $instance['template'] ) ? 'featuredcards' : $instance['template'],
			'columns'  => empty( $instance['columns'] ) ? gEditorial\Template::perRowColumns() : $instance['columns'],
			// 'limit'    => empty( $instance['limit'] ) ? '-1' : $instance['limit'],
			'context'  => empty( $instance['context'] ) ? NULL : $instance['context'],
		], $post_id );

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
			$this->form_checkbox( $instance, TRUE, 'featured', _x( 'Display Featured Items', 'Widget: Featured Cards', 'geditorial-byline' ) );
			$this->form_checkbox( $instance, FALSE, 'hidden', _x( 'Display Hidden Items', 'Widget: Featured Cards', 'geditorial-byline' ) );
			// $this->form_number( $instance, '', 'limit', _x( 'Limit:', 'Widget: Featured Cards', 'geditorial-byline' ) );
			$this->form_number( $instance, '', 'columns', _x( 'Columns:', 'Widget: Featured Cards', 'geditorial-byline' ) );
			$this->form_custom_code( $instance, '', 'template', _x( 'Template:', 'Widget: Featured Cards', 'geditorial-byline' ) );
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
			'featured',
			'hidden',
		], [
			'template' => 'key',
			// 'limit'    => 'digit',
			'columns'  => 'digit',
		] );
	}
}

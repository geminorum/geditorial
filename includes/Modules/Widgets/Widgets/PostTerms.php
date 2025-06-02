<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class PostTerms extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'post_terms';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Post Terms', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays the assigned terms of the current post.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! is_singular() && ! is_single() )
			return;

		if ( ! $post = get_queried_object() )
			return;

		if ( empty( $instance['taxonomy'] ) || 'all' == $instance['taxonomy'] ) {

			$taxonomies = [];

			foreach ( get_object_taxonomies( $post->post_type, 'objects' ) as $object ) {

				if ( ! empty( $object->public ) && ! empty( $object->show_ui ) )
					$taxonomies[] = $object->name;
			}

		} else {

			$taxonomies = [ $instance['taxonomy'] ];
		}

		$html = '';

		foreach ( $taxonomies as $taxonomy ) {

			$terms   = WordPress\Taxonomy::getPostTerms( $taxonomy, $post );
			$default = WordPress\Taxonomy::getDefaultTermID( $taxonomy );

			if ( ! $terms || is_wp_error( $terms ) )
				continue;

			foreach ( $terms as $term ) {

				if ( $term->term_id === (int) $default )
					continue;

				if ( $link = WordPress\Term::htmlLink( $term ) )
					$html.= sprintf( '<li>%s</li>', $link );
			}
		}

		if ( empty( $html ) )
			return;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
		echo '<div class="-list-wrap post-terms"><ul class="-items">';
			echo $html;
		echo '</ul></div>';
		$this->after_widget( $args, $instance );
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_open_group();
			$this->form_taxonomies( $instance );
		$this->form_close_group( _x( 'Select none for all taxonomies.', 'Widget: Setting', 'geditorial-widgets' ) );

		$this->form_open_group( 'heading' );
		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );
		$this->form_class( $instance );
		$this->form_close_group();

		$this->form_open_group( 'customs' );
		$this->form_open_widget( $instance );
		$this->form_after_title( $instance );
		$this->form_close_widget( $instance );
		$this->form_close_group();

		$this->after_form( $instance );
	}
}

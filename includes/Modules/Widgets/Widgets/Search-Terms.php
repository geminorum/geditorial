<?php namespace geminorum\gEditorial\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class SearchTerms extends gEditorial\Widget
{

	const MODULE = 'widgets';

	public static function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'search_terms',
			'class'  => 'search-terms',
			'title'  => _x( 'Editorial: Search Terms', 'Widget Title', 'geditorial-widgets' ),
			'desc'   => _x( 'Displays the results of current search criteria on selected taxonomies.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! is_search() )
			return;

		// avoid filtering
		if ( ! $criteria = trim( get_query_var( 's' ) ) )
			return;

		// FIXME: optional skip if paged

		if ( empty( $instance['taxonomy'] ) || 'all' == $instance['taxonomy'] )
			$taxonomies = get_taxonomies( [ 'public' => TRUE, 'show_ui' => TRUE ] );
		else
			$taxonomies = [ $instance['taxonomy'] ];

		if ( ! empty( $instance['strip_hashtags'] ) )
			$criteria = preg_replace_callback( "/^#(.*)$/mu", function ( $matches ) {
				return str_replace( '_', ' ', $matches[1] );
			}, $criteria );

		$query = new \WP_Term_Query( [
			'search'     => $criteria, // 'name__like'
			'taxonomy'   => $taxonomies,
			'orderby'    => 'name',
			'hide_empty' => FALSE,
		] );

		if ( empty( $query->terms ) )
			return;

		$title = count( $taxonomies ) > 1;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
		echo '<div class="-list-wrap search-terms"><ul>';

		foreach ( $query->terms as $term ) {
			echo '<li>'.HTML::tag( 'a', [
				'href'  => get_term_link( $term->term_id, $term->taxonomy ),
				'title' => $title ? get_taxonomy( $term->taxonomy )->labels->singular_name : FALSE,
				'class' => [ '-term', '-taxonomy-'.$term->taxonomy ],
			], sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) ).'</li>';
		}

		echo '</ul></div>';
		$this->after_widget( $args, $instance );
	}

	public function update( $new, $old )
	{
		$instance = $old;

		$instance['title']       = sanitize_text_field( $new['title'] );
		$instance['title_link']  = strip_tags( $new['title_link'] );
		$instance['title_image'] = strip_tags( $new['title_image'] );
		$instance['class']       = strip_tags( $new['class'] );

		$instance['taxonomy'] = strip_tags( $new['taxonomy'] );

		$instance['strip_hashtags'] = isset( $new['strip_hashtags'] );

		$this->flush_widget_cache();

		return $instance;
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_title( $instance );
		$this->form_title_link( $instance );
		$this->form_title_image( $instance );
		$this->form_class( $instance );

		$this->form_taxonomy( $instance );

		$this->form_checkbox( $instance, FALSE, 'strip_hashtags', _x( 'Strip Hash-tags', 'Widget: Setting', 'geditorial-widgets' ) );

		$this->after_form( $instance );
	}
}

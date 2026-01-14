<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class SearchTerms extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'search_terms';

	public static function setup()
	{
		return [
			'title' => _x( 'Editorial: Search Terms', 'Widget Title', 'geditorial-widgets' ),
			'desc'  => _x( 'Displays the results of current search criteria on selected taxonomies.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! is_search() || ( ! empty( $instance['hide_on_paged'] ) && is_paged() ) )
			return;

		$criteria = trim( get_query_var( 's' ) ); // avoid core filtering
		$criteria = static::filters( 'criteria', $criteria, $instance, $args );

		if ( ! $criteria )
			return;

		$taxonomies = empty( $instance['taxonomies'] )
			? get_taxonomies( [ 'public' => TRUE, 'show_ui' => TRUE ] )
			: (array) $instance['taxonomies'];

		if ( ! empty( $instance['strip_hashtags'] ) )
			$criteria = preg_replace_callback( "/^#(.*)$/mu", function ( $matches ) {
				return str_replace( '_', ' ', $matches[1] );
			}, $criteria );

		$exclude = [];

		if ( ! empty( $instance['exclude_defaults'] ) )
			foreach ( $taxonomies as $taxonomy )
				$exclude[] = (int) WordPress\Taxonomy::getDefaultTermID( $taxonomy );

		$query = new \WP_Term_Query( [
			'search'     => $criteria, // 'name__like'
			'taxonomy'   => $taxonomies,
			'exclude'    => array_filter( $exclude ),
			'orderby'    => 'name',
			'hide_empty' => ! empty( $instance['include_empty'] ),
		] );

		// @hook: `geditorial_search_terms_widget_results`
		$terms = self::filters( 'widget_results',
			$query->terms,
			$criteria,
			$taxonomies,
			$args,
			$instance
		);

		if ( empty( $terms ) )
			return;

		$names = $displayed = [];
		$title = count( $taxonomies ) > 1;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
		echo '<div class="-list-wrap search-terms"><ul class="-items">';

		foreach ( $terms as $term ) {

			// The filter may add duplicated results!
			if ( in_array( $term->term_id, $displayed, TRUE ) )
				continue;

			echo '<li>';

			if ( empty( $names[$term->taxonomy] ) )
				$names[$term->taxonomy] = get_taxonomy( $term->taxonomy )->labels->singular_name;

			if ( ! empty( $instance['prefix_with_name'] ) )
				printf( '%s:&nbsp;', $names[$term->taxonomy] );

			echo Core\HTML::tag( 'a', [
				'href'  => WordPress\Term::link( $term ),
				'title' => $title && empty( $instance['prefix_with_name'] ) ? $names[$term->taxonomy] : FALSE,
				'class' => [ '-term', '-taxonomy-'.$term->taxonomy ],
			], WordPress\Term::title( $term ) );

			if ( ! empty( $instance['tax_name_hint'] ) )
				printf( '&nbsp;(%s)', $names[$term->taxonomy] );

			echo '</li>';

			$displayed[] = $term->term_id;
		}

		echo '</ul></div>';
		$this->after_widget( $args, $instance );
	}

	public function form( $instance )
	{
		$this->before_form( $instance );

		$this->form_open_group();
			$this->form_taxonomies( $instance );
		$this->form_close_group( _x( 'Select none for all taxonomies.', 'Widget: Setting', 'geditorial-widgets' ) );

		$this->form_open_group( 'config' );
		$this->form_checkbox( $instance, FALSE, 'prefix_with_name', _x( 'Prefix Terms with Taxonomy Name', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'tax_name_hint', _x( 'Append Taxonomy Name after Terms', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'strip_hashtags', _x( 'Strip Hash-tags', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'exclude_defaults', _x( 'Exclude Default Terms', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'include_empty', _x( 'Include Empty Terms', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'hide_on_paged', _x( 'Hide on Paged Results', 'Widget: Setting', 'geditorial-widgets' ) );
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
			'prefix_with_name',
			'tax_name_hint',
			'strip_hashtags',
			'exclude_defaults',
			'include_empty',
			'hide_on_paged',
		] );
	}
}

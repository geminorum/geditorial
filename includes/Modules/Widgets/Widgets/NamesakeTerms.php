<?php namespace geminorum\gEditorial\Modules\Widgets\Widgets;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\Taxonomy;

class NamesakeTerms extends gEditorial\Widget
{

	const MODULE = 'widgets';
	const WIDGET = 'namesake_terms';

	public static function setup()
	{
		return [
			'module' => 'widgets',
			'name'   => 'namesake_terms',
			'class'  => 'namesake-terms',
			'title'  => _x( 'Editorial: Namesake Terms', 'Widget Title', 'geditorial-widgets' ),
			'desc'   => _x( 'Displays the results of search for namesake to current term on selected taxonomies.', 'Widget Description', 'geditorial-widgets' ),
		];
	}

	public function widget( $args, $instance )
	{
		if ( ! ( is_tax() || is_tag() || is_category() ) || ( ! empty( $instance['hide_on_paged'] ) && is_paged() ) )
			return;

		if ( ! $term = get_queried_object() )
			return;

		$taxonomies = empty( $instance['taxonomies'] )
			? get_taxonomies( [ 'public' => TRUE, 'show_ui' => TRUE ] )
			: (array) $instance['taxonomies'];

		if ( ! in_array( $term->taxonomy, $taxonomies, TRUE ) )
			return;

		$criteria = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
		$criteria = self::filters( 'criteria', $criteria, $instance, $args, $term );

		if ( ! $criteria )
			return;

		$search  = empty( $instance['name_like_only'] ) ? 'search' : 'name__like';
		$exclude = [ $term->term_id ]; // exclude only the current not the entire taxonomy

		if ( ! empty( $instance['exclude_defaults'] ) )
			foreach ( $taxonomies as $taxonomy )
				$exclude[] = (int) Taxonomy::getDefaultTermID( $taxonomy );

		$query = new \WP_Term_Query( [
			$search      => $criteria,
			'taxonomy'   => $taxonomies,
			'exclude'    => array_filter( $exclude ),
			'orderby'    => 'name',
			'hide_empty' => ! empty( $instance['include_empty'] ),
		] );

		if ( empty( $query->terms ) )
			return;

		$names = [];
		$title = count( $taxonomies ) > 1;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
		echo '<div class="-list-wrap namesake-terms"><ul class="-items">';

		foreach ( $query->terms as $term ) {
			echo '<li>';

			if ( empty( $names[$term->taxonomy] ) )
				$names[$term->taxonomy] = get_taxonomy( $term->taxonomy )->labels->singular_name;

			if ( ! empty( $instance['prefix_with_name'] ) )
				printf( '%s:&nbsp;', $names[$term->taxonomy] );

			echo HTML::tag( 'a', [
				'href'  => get_term_link( $term->term_id, $term->taxonomy ),
				'title' => $title && empty( $instance['prefix_with_name'] ) ? $names[$term->taxonomy] : FALSE,
				'class' => [ '-term', '-taxonomy-'.$term->taxonomy ],
			], sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) );

			if ( ! empty( $instance['tax_name_hint'] ) )
				printf( '&nbsp;(%s)', $names[$term->taxonomy] );

			echo '</li>';
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
		$this->form_checkbox( $instance, FALSE, 'tax_name_hint',    _x( 'Append Taxonomy Name after Terms', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'name_like_only',   _x( 'Name-Like Only', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'exclude_defaults', _x( 'Exclude Default Terms', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'include_empty',    _x( 'Include Empty Terms', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_checkbox( $instance, FALSE, 'hide_on_paged',    _x( 'Hide on Paged Results', 'Widget: Setting', 'geditorial-widgets' ) );
		$this->form_close_group();

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

	public function update( $new, $old )
	{
		$this->flush_widget_cache();

		return $this->handle_update( $new, $old, [
			'prefix_with_name',
			'tax_name_hint',
			'name_like_only',
			'exclude_defaults',
			'include_empty',
			'hide_on_paged',
		] );
	}
}

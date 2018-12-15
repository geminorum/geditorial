<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Listtable extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	protected static function getString( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE )
	{
		return gEditorial()->{static::MODULE}->get_string( $string, $posttype, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = '', $key = NULL )
	{
		return gEditorial()->{static::MODULE}->get_postmeta( $post_id, $field, $default, $key );
	}

	public static function columnCount( $count, $title_attr = NULL )
	{
		return Helper::htmlCount( $count, $title_attr )
			.'<span class="count" data-count="'
			.( FALSE === $count ? '' : $count ).'"></span>';
	}

	public static function columnOrder( $order, $title_attr = NULL )
	{
		return Helper::htmlOrder( $order, $title_attr )
			.'<span class="order" data-order="'
			.( FALSE === $order ? '' : $order ).'"></span>';
	}

	public static function columnTerm( $object_id, $taxonomy, $title_attr = NULL, $single = TRUE )
	{
		$the_terms = wp_get_object_terms( $object_id, $taxonomy );

		if ( ! is_wp_error( $the_terms ) && count( $the_terms ) ) {

			if ( $single ) {
				return $the_terms[0]->name;

			} else {

				$terms = [];

				foreach ( $the_terms as $the_term )
					$terms[] = $the_term->name;

				return Helper::getJoined( $terms );
			}

		} else {

			if ( is_null( $title_attr ) )
				$title_attr = _x( 'No Term', 'Listtable: No Count Term Attribute', GEDITORIAL_TEXTDOMAIN );

			return sprintf( '<span title="%s" class="column-term-empty">&mdash;</span>', $title_attr );
		}
	}

	public static function parseQueryTaxonomy( &$query, $taxonomy )
	{
		if ( ! isset( $query->query_vars[$taxonomy] ) )
			return;

		if ( '-1' == $query->query_vars[$taxonomy] ) {

			$query->query_vars['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'operator' => 'NOT EXISTS',
			] ];

			unset( $query->query_vars[$taxonomy] );

		} else if ( is_numeric( $query->query_vars[$taxonomy] ) ) {

			$term = get_term_by( 'id', $query->query_vars[$taxonomy], $taxonomy );

			if ( ! empty( $term ) && ! is_wp_error( $term ) )
				$query->query_vars[$taxonomy] = $term->slug;
		}
	}

	// @SEE: https://core.trac.wordpress.org/ticket/23421
	// @SOURCE: http://scribu.net/wordpress/sortable-taxonomy-columns.html
	public static function orderClausesByTaxonomy( $pieces, $query, $taxonomy )
	{
		global $wpdb;

		$pieces['join'].= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
SQL;

		$pieces['where'].= $wpdb->prepare( " AND (taxonomy = %s OR taxonomy IS NULL)", $taxonomy );

		$pieces['orderby'] = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
		$pieces['orderby'].= ( 'ASC' == strtoupper( $query->get('order') ) ) ? 'ASC' : 'DESC';

		$pieces['groupby'] = "object_id";

		return $pieces;
	}

	public static function restrictByTaxonomy( $taxonomy, $option_all = NULL, $option_none = NULL )
	{
		global $wp_query;

		if ( ! $object = get_taxonomy( $taxonomy ) )
			return;

		$selected = isset( $wp_query->query[$taxonomy] ) ? $wp_query->query[$taxonomy] : '';

		// if selected is term_id instead of term slug
		if ( $selected && '-1' != $selected && is_numeric( $selected ) ) {

			if ( $term = get_term_by( 'id', $selected, $taxonomy ) )
				$selected = $term->slug;

			else
				$selected = '';
		}

		wp_dropdown_categories( [
			'show_option_all'  => is_null( $option_all ) ? $object->labels->all_items : $option_all,
			'show_option_none' => is_null( $option_none ) ? '('.$object->labels->no_terms.')' : $option_none,
			'taxonomy'         => $taxonomy,
			'name'             => $object->name,
			'orderby'          => 'name',
			'value_field'      => 'slug',
			'selected'         => $selected,
			'hierarchical'     => $object->hierarchical,
			'depth'            => 3,
			'show_count'       => FALSE,
			'hide_empty'       => TRUE,
			'hide_if_empty'    => TRUE,
		] );
	}

	public static function restrictByPosttype( $taxonomy, $posttype, $option_all = NULL )
	{
		if ( ! $object = get_taxonomy( $taxonomy ) )
			return;

		gEditorial()->files( 'misc/walker-page-dropdown' );

		wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '',
			'name'             => $taxonomy,
			'class'            => static::BASE.'-admin-dropbown',
			'show_option_none' => is_null( $option_all ) ? $object->labels->all_items : $option_all,
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'value_field'      => 'post_name',
			'walker'           => new Misc\Walker_PageDropdown(),
		] );
	}
}

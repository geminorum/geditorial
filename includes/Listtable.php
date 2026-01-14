<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Listtable extends WordPress\Main
{

	// TODO: move to services

	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function checkHidden( $column_id, $after = '' )
	{
		static $hidden = NULL;

		if ( ! $column_id )
			return FALSE;

		if ( is_null( $hidden ) )
			$hidden = (array) get_hidden_columns( get_current_screen() );

		if ( ! in_array( $column_id, $hidden, TRUE ) )
			return FALSE;

		$html = Core\HTML::tag( 'a', [
			'href'  => add_query_arg( 'flush', '' ),
			'class' => [ '-description', '-refresh' ],
		], _x( 'Please refresh the page to generate the data.', 'Listtable: Refresh Link', 'geditorial' ) );

		echo Core\HTML::wrap( $html, 'cell-wrap -needs-refresh' ).$after;

		return TRUE;
	}

	public static function columnCount( $count, $title_attr = NULL )
	{
		return Helper::htmlCount( $count, $title_attr )
			.'<span class="count field-count" data-count="'
			.( FALSE === $count ? '' : $count ).'"></span>';
	}

	public static function columnOrder( $order, $title_attr = NULL )
	{
		return Helper::htmlOrder( $order, $title_attr )
			.'<span class="order field-order" data-order="'
			.( FALSE === $order ? '' : $order ).'"></span>';
	}

	public static function columnTerm( $object_id, $taxonomy, $title_attr = NULL, $single = TRUE )
	{
		$terms = get_the_terms( (int) $object_id, $taxonomy );

		if ( $terms && ! is_wp_error( $terms ) && count( $terms ) )
			return $single
				? $terms[0]->name
				: WordPress\Strings::getJoined( Core\Arraay::pluck( $terms, 'name' ) );

		return Helper::htmlEmpty(
			'column-term-empty',
			$title_attr ?? _x( 'No Term', 'Listtable: No Count Term Attribute', 'geditorial' )
		);
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
		$pieces['orderby'].= ( 'ASC' == strtoupper( $query->get( 'order' ) ) ) ? 'ASC' : 'DESC';

		$pieces['groupby'] = "object_id";

		return $pieces;
	}

	// TODO: our own `wp_dropdown_categories()` using custom walker
	// @SEE: https://developer.wordpress.org/reference/functions/wp_dropdown_categories/#comment-1823
	// ALSO: trim term titles
	public static function restrictByTaxonomy( $taxonomy, $paired_posttype = FALSE, $extra = [] )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		$query_var = WordPress\Taxonomy::queryVar( $taxonomy );
		$selected  = $_GET[$query_var] ?? '';

		// selected is `term_id` instead of term `slug`
		if ( $selected && '-1' != $selected && is_numeric( $selected ) ) {

			if ( $term = get_term_by( 'id', $selected, $taxonomy->name ) )
				$selected = $term->slug;

			// for numeric slugs!
			else if ( $term = get_term_by( 'slug', $selected, $taxonomy->name ) )
				$selected = $term->slug;

			else
				$selected = '';
		}

		$reversed = empty( $taxonomy->{Services\TermHierarchy::REVERSE_ORDERED_TERMS} )
			? FALSE
			: $taxonomy->{Services\TermHierarchy::REVERSE_ORDERED_TERMS};

		$args = [
			'taxonomy'      => $taxonomy->name,
			'name'          => $query_var,
			'order'         => $reversed ? 'DESC' : 'ASC',
			'orderby'       => $reversed ?: 'name',
			'value_field'   => 'slug',
			'selected'      => $selected,
			'hierarchical'  => $taxonomy->hierarchical,
			'depth'         => 3,
			'show_count'    => FALSE,
			'hide_empty'    => TRUE,
			'hide_if_empty' => TRUE,
			// 'walker'        => new Misc\WalkerCategoryDropdown(),
		];

		if ( $posttype = WordPress\PostType::object( $paired_posttype ) ) {

			$args['show_option_all']  = Services\CustomPostType::getLabel( $posttype, 'show_option_all' );
			$args['show_option_none'] = Services\CustomPostType::getLabel( $posttype, 'show_option_no_items' );

		} else {

			$args['show_option_all']  = Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_all' );
			$args['show_option_none'] = Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_no_items' );
		}

		wp_dropdown_categories( array_merge( $args, $extra ) );
	}

	// FIXME: DEPRECATED: use `restrictByTaxonomy()`
	// WTF: `draft` status posts with no `post_name`
	public static function restrictByPosttype( $taxonomy, $posttype, $option_all = NULL )
	{
		if ( ! $object = get_taxonomy( $taxonomy ) )
			return;

		$query_var = WordPress\Taxonomy::queryVar( $object );
		$selected  = $_GET[$query_var] ?? '';

		// if selected is term_id instead of term slug
		if ( $selected && '-1' != $selected && is_numeric( $selected ) ) {

			if ( $term = get_term_by( 'id', $selected, $taxonomy ) )
				$selected = $term->slug;

			else
				$selected = '';
		}

		wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => $selected,
			'name'             => $query_var,
			'class'            => static::BASE.'-admin-dropbown',
			'show_option_none' => Services\CustomPostType::getLabel( $posttype, 'show_option_all' ),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => WordPress\Status::acceptable( $posttype, 'dropdown' ),
			'value_field'      => 'post_name',
			'walker'           => new Misc\WalkerPageDropdown(),
		] );
	}

	// @SEE: https://make.wordpress.org/core/2022/01/05/new-capability-queries-in-wordpress-5-9/
	// @SEE: https://core.trac.wordpress.org/ticket/19867
	public static function restrictByAuthor( $selected = 0, $name = 'author', $extra = [] )
	{
		if ( WordPress\User::isLargeCount() )
			return '';

		return wp_dropdown_users( array_merge( [
			'name'     => $name,
			'selected' => $selected,
			'show'     => 'display_name_with_login',

			// 'who'        => 'authors',
			'capability' => [ 'edit_posts' ],

			'show_option_all'   => _x( 'All Authors', 'Listtable: Show Option All', 'geditorial' ),
			'option_none_value' => 0,

			'hide_if_only_one_author' => TRUE,
			'include_selected'        => TRUE,
		], $extra ) );
	}

	public static function restrictByPostMeta( $metakey, $none = NULL, $extra = [] )
	{
		$list = WordPress\Database::getPostMetaForDropdown( $metakey );
		$list = Core\Arraay::sameKey( array_filter( $list ) );

		echo Core\HTML::dropdown( $list, array_merge( [
			'name'       => $metakey,
			'selected'   => self::req( $metakey ),
			'none_title' => $none ?? Settings::showOptionNone(),
			'none_value' => '',
		], $extra ) );
	}

	public static function parseQueryPostMeta( &$query, $metakey )
	{
		if ( ! $selected = self::req( $metakey ) )
			return;

		$meta_query = $query->query_vars['meta_query'] ?? [];

		$meta_query[] = [
			'key'     => $metakey,
			'value'   => $selected,
			'compare' => '=',
			'type'    => 'CHAR'
		];

		$query->set( 'meta_query', $meta_query );
	}
}

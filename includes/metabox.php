<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class MetaBox extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	protected static function getString( $string, $post_type = 'post', $group = 'titles', $fallback = FALSE )
	{
		return gEditorial()->{static::MODULE}->get_string( $string, $post_type, $group, $fallback );
	}

	protected static function getPostMeta( $post_id, $field = FALSE, $default = '', $key = NULL )
	{
		return gEditorial()->{static::MODULE}->get_postmeta( $post_id, $field, $default, $key );
	}

	// SEE: [Use Chosen for a replacement WordPress taxonomy metabox](https://gist.github.com/helen/1573966)
	// callback for meta box for choose only tax
	// CAUTION: tax must be cat (hierarchical)
	// hierarchical taxonomies save by IDs, whereas non save by slugs
	// @SOURCE: `post_categories_meta_box()`
	public static function checklistTerms( $post, $box, $wrap = TRUE )
	{
		$args = self::atts( [
			'taxonomy' => 'category',
			'edit_url' => NULL,
		], empty( $box['args'] ) ? [] : $box['args'] );

		$tax_name = esc_attr( $args['taxonomy'] );
		$taxonomy = get_taxonomy( $args['taxonomy'] );

		$html = wp_terms_checklist( $post->ID, [
			'taxonomy'      => $tax_name,
			'checked_ontop' => FALSE,
			'echo'          => FALSE,
		] );

		if ( ! $html && FALSE === $args['edit_url'] )
			return FALSE;

		if ( $wrap )
			echo '<div id="taxonomy-'.$tax_name.'" class="geditorial-admin-wrap-metabox">';

		if ( $html ) {

			echo HTML::wrap( '<ul>'.$html.'</ul>', 'field-wrap field-wrap-list' );

			// allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
			echo '<input type="hidden" name="tax_input['.$tax_name.'][]" value="0" />';

		} else {
			self::fieldEmptyTaxonomy( $taxonomy, $args['edit_url'] );
		}

		if ( $wrap )
			echo '</div>';
	}

	public static function getTermPosts( $taxonomy, $term_or_id, $exclude = [], $title = TRUE )
	{
		if ( ! $term = Taxonomy::getTerm( $term_or_id, $taxonomy ) )
			return '';

		$args = [
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ],
			'order'          => 'ASC',
			'post_status'    => [ 'publish', 'future', 'pending', 'draft' ],
			'post__not_in'   => $exclude,
			'tax_query'      => [ [
				'taxonomy' => $taxonomy,
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],
		];

		$posts = get_posts( $args );

		if ( ! count( $posts ) )
			return FALSE;

		$html     = '';
		$statuses = PostType::getStatuses();

		if ( $title )
			$html.= '<h4>'.Helper::getTermTitleRow( $term ).'</h4>';

		$html.= '<ol>';

		foreach ( $posts as $post )
			$html.= '<li>'.Helper::getPostTitleRow( $post, 'edit', $statuses ).'</li>';

		return HTML::wrap( $html.'</ol>', 'field-wrap field-wrap-list' );
	}

	public static function fieldEmptyTaxonomy( $taxonomy, $edit = NULL )
	{
		if ( ! is_object( $taxonomy ) )
			$taxonomy = get_taxonomy( $taxonomy );

		if ( is_null( $edit ) )
			$edit = WordPress::getEditTaxLink( $taxonomy->name );

		if ( $edit )
			$html = HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => $taxonomy->labels->add_new_item,
				'target' => '_blank',
			], $taxonomy->labels->not_found );

		else
			$html = '<span>'.$taxonomy->labels->not_found.'</span>';

		echo HTML::wrap( $html, 'field-wrap field-wrap-empty' );
	}

	public static function fieldEmptyPostType( $post_type )
	{
		$object = is_object( $post_type ) ? $post_type : get_post_type_object( $post_type );

		$html = HTML::tag( 'a', [
			'href'   => WordPress::getPostNewLink( $post_type ),
			'title'  => $object->labels->add_new_item,
			'target' => '_blank',
		], $object->labels->not_found );

		echo HTML::wrap( $html, 'field-wrap field-wrap-empty' );
	}

	public static function dropdownAssocPosts( $post_type, $selected = '', $prefix = '', $exclude = '' )
	{
		$html = wp_dropdown_pages( [
			'post_type'        => $post_type,
			'selected'         => $selected,
			'name'             => ( $prefix ? $prefix.'-' : '' ).$post_type.'[]',
			'id'               => ( $prefix ? $prefix.'-' : '' ).$post_type.'-'.( $selected ? $selected : '0' ),
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => Settings::showOptionNone(),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'value_field'      => 'post_name',
			'exclude'          => $exclude,
			'echo'             => 0,
			'walker'           => new Walker_PageDropdown(),
		] );

		return $html ? HTML::wrap( $html, 'field-wrap field-wrap-select' ) : FALSE;
	}

	public static function fieldPostMenuOrder( $post )
	{
		$html = HTML::tag( 'input', [
			'type'        => 'number',
			'step'        => '1',
			'size'        => '4',
			'name'        => 'menu_order',
			'id'          => 'menu_order',
			'value'       => $post->menu_order,
			'title'       => _x( 'Order', 'MetaBox: Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Order', 'MetaBox: Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'small-text',
			'data'        => [ 'ortho' => 'number' ],
		] );

		echo HTML::wrap( $html, 'field-wrap field-wrap-inputnumber' );
	}

	public static function fieldPostParent( $post_type, $post, $statuses = [ 'publish', 'future', 'draft' ] )
	{
		if ( ! get_post_type_object( $post_type )->hierarchical )
			return;

		$html = wp_dropdown_pages( [
			'post_type'        => $post_type, // alows for parent of diffrent type
			'selected'         => $post->post_parent,
			'name'             => 'parent_id',
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => _x( '&mdash; no parent &mdash;', 'MetaBox: Parent Dropdown: Select Option None', GEDITORIAL_TEXTDOMAIN ),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => $statuses,
			'exclude_tree'     => $post->ID,
			'echo'             => 0,
		] );

		if ( $html )
			echo HTML::wrap( $html, 'field-wrap field-wrap-select' );
	}

	// FIXME: finalize name/id
	public static function dropdownPostTaxonomy( $taxonomy, $post, $key = FALSE, $count = TRUE, $excludes = '', $default = '0' )
	{
		if ( ! $obj = get_taxonomy( $taxonomy ) )
			return;

		if ( $default && ! is_numeric( $default ) ) {
			if ( $term = get_term_by( 'slug', $default, $taxonomy ) )
				$default = $term->term_id;
			else
				$default = '0';
		}

		if ( ! $selected = Taxonomy::theTerm( $taxonomy, $post->ID ) )
			$selected = $default;

		$terms = wp_dropdown_categories( [
			'taxonomy'          => $taxonomy,
			'selected'          => $selected,
			'show_option_none'  => Settings::showOptionNone( $obj->labels->menu_name ),
			'option_none_value' => '0',
			'class'             => 'geditorial-admin-dropbown',
			'name'              => 'tax_input['.$taxonomy.'][]',
			'id'                => static::BASE.'-'.$taxonomy,
			// 'name'              => 'geditorial-'.$this->module->name.'-'.$taxonomy.( FALSE === $key ? '' : '['.$key.']' ),
			// 'id'                => 'geditorial-'.$this->module->name.'-'.$taxonomy.( FALSE === $key ? '' : '-'.$key ),
			'hierarchical'      => $obj->hierarchical,
			'orderby'           => 'name',
			'show_count'        => $count,
			'hide_empty'        => FALSE,
			'hide_if_empty'     => TRUE,
			'echo'              => FALSE,
			'exclude'           => $excludes,
		] );

		if ( $terms )
			echo HTML::tag( 'div', [
				'class' => '-wrap field-wrap field-wrap-select',
				'title' => $obj->labels->menu_name,
			], $terms );
		else
			self::fieldEmptyTaxonomy( $obj, NULL );
	}
}

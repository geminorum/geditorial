<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class TermHierarchy extends WordPress\Main
{

	const BASE = 'geditorial';

	const AUTO_SET_PARENT_TERMS = 'auto_set_parent_terms';
	const AUTO_SET_CHILD_TERMS  = 'auto_set_child_terms';
	const REVERSE_ORDERED_TERMS = 'reverse_ordered_terms';
	const AUTO_ASSIGNED_TERMS   = 'auto_assigned_terms';
	const SINGLE_TERM_SELECT    = 'single_term_select'; // TODO: restrict via aftercare with info from `added_term_relationship`

	public static function setup()
	{
		add_action( 'set_object_terms', [ __CLASS__, 'set_object_terms_auto_set_parent_terms' ], 9999, 6 );
		add_action( 'set_object_terms', [ __CLASS__, 'set_object_terms_auto_set_child_terms' ], 9999, 6 );
		add_filter( 'get_terms_defaults', [ __CLASS__, 'get_terms_defaults' ], 9, 2 );

		if ( ! is_admin() )
			return;

		add_action( 'current_screen', [ __CLASS__, 'current_screen' ], 999 );
	}

	public static function current_screen( $screen )
	{
		if ( 'edit' === $screen->base && ! empty( $screen->post_type ) )
			self::_hook_edit_screen_single_term_select( $screen->post_type );
	}

	private static function _hook_edit_screen_single_term_select( $posttype )
	{
		// NOTE: this is WordPress's core hook
		if ( ! apply_filters( 'quick_edit_enabled_for_post_type', TRUE, $posttype ) )
			return FALSE;

		$taxonomies = WordPress\Taxonomy::get( 4, [
			'show_in_quick_edit'       => TRUE,
			static::SINGLE_TERM_SELECT => TRUE,
		], $posttype, 'assign_terms' );

		if ( empty( $taxonomies ) )
			return FALSE;

		add_filter( 'quick_edit_show_taxonomy',
			static function ( $show, $taxonomy, $current ) use ( $posttype, $taxonomies ) {

				if ( ! $show || $current !== $posttype )
					return $show;

				return array_key_exists( $taxonomy, $taxonomies )
					? FALSE
					: $show;

			}, 12, 3 );

		add_action( 'add_inline_data',
			static function ( $post, $object ) use ( $posttype, $taxonomies ) {

				if ( $object->name !== $posttype )
					return;

				foreach ( $taxonomies as $taxonomy ) {
					echo '<div class="hidden '.static::BASE.'-singleselect-value-'.$taxonomy->name.'">';

					if ( $term = self::getSingleSelectTerm( $taxonomy, get_the_terms( $post, $taxonomy->name ) ) )
						echo $taxonomy->hierarchical ? $term->term_id : $term->slug;
					else
						echo '0';

					echo '</div>';
				}
			}, 1, 2 );

		add_action( 'quick_edit_custom_box',
			static function ( $column, $current ) use ( $posttype, $taxonomies ) {

				static $added = FALSE;

				if ( $added || $current !== $posttype )
					return;

				self::_renderCustomBoxDropdowns( $taxonomies, FALSE );

				$added = $column;

			}, 1, 2 );

		Scripts::enqueue( 'admin.singleselect.edit' );

		if ( ! Core\WordPress::isWPcompatible( '6.3.0' ) )
			return;

		add_action( 'bulk_edit_posts',
			static function ( $updated, $data ) use ( $posttype, $taxonomies ) {

				if ( empty( $data[static::SINGLE_TERM_SELECT] ) || empty( $updated ) )
					return;

				$list = Core\Arraay::pluck( $taxonomies, 'name' );

				foreach ( $data[static::SINGLE_TERM_SELECT] as $taxonomy => $terms ) {

					if ( ! in_array( $taxonomy, $list, TRUE ) )
						continue;

					// skip `0`
					if ( ! $single = reset( array_filter( $terms ) ) )
						continue;

					if ( $taxonomies[$taxonomy]->hierarchical )
						$single = \intval( $single );

					foreach ( $updated as $object_id )
						wp_set_object_terms( (int) $object_id, $single, $taxonomy, FALSE );
				}

			}, 12, 2 );

		add_action( 'bulk_edit_custom_box',
			static function ( $column, $current ) use ( $posttype, $taxonomies ) {

				static $added = FALSE;

				if ( $added || $current !== $posttype )
					return;

				// NOTE: diffrent context
				self::_renderCustomBoxDropdowns( $taxonomies, TRUE );

				$added = $column;

			}, 1, 2 );
	}

	private static function _renderCustomBoxDropdowns( $taxonomies, $bulkedit = FALSE )
	{
		$html = '';

		foreach ( $taxonomies as $taxonomy ) {

			$args = [
				'taxonomy'          => $taxonomy->name,
				'hierarchical'      => $taxonomy->hierarchical,
				'value'             => $taxonomy->hierarchical ? 'term_id' : 'slug',
				'value_field'       => $taxonomy->hierarchical ? 'term_id' : 'slug',
				// 'name'              => 'tax_input['.$taxonomy->name.'][]',
				'name'              => sprintf( '%s[%s][]', $bulkedit ? static::SINGLE_TERM_SELECT : 'tax_input', $taxonomy->name ),
				'id'                => static::BASE.'-singleselect-select-'.$taxonomy->name,
				'option_none_value' => '0',
				'show_option_none'  => Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_select' ),
				'class'             => static::BASE.'-admin-dropbown '.( $bulkedit ? '-bulkedit-custombox' : '-quickedit-custombox' ),
				'show_count'        => FALSE,
				'hide_empty'        => FALSE,
				'hide_if_empty'     => TRUE,
				'echo'              => FALSE,
			];

			if ( ! $dropdown = wp_dropdown_categories( $args ) )
				continue;

			$html.= sprintf( '<div title="%s">%s</div>',
				Core\HTML::escapeAttr( Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label' ) ), $dropdown );
		}

		if ( $html ) {
			vprintf( '<div class="%s-admin-wrap-%s" id="%s-%s-%s-wrap" data-taxonomies=\'%s\'>', [
				static::BASE,
				$bulkedit ? 'bulkedit' : 'quickedit',
				static::BASE,
				'singleselect',
				$bulkedit ? 'bulkedit' : 'quickedit',
				wp_json_encode( array_keys( Core\Arraay::pluck( $taxonomies, 'name' ) ) ),
			] );

			echo $html.'</div>';
		}
	}

	/**
	 * Checks whether the taxonomy is `SingleTerm`
	 *
	 * @param string|object $taxonomy
	 * @return bool $is
	 */
	public static function isSingleTerm( $taxonomy )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return FALSE;

		return ! empty( $object->{static::SINGLE_TERM_SELECT} );
	}

	/**
	 * Retrieves a single targeted term form a taxonomy with select-single prop.
	 *
	 * @param  string|object $taxonomy
	 * @param  array         $terms
	 * @param  bool|object   $post
	 * @return false|object  $term
	 */
	public static function getSingleSelectTerm( $taxonomy, $terms, $post = FALSE )
	{
		// if ( is_null( $terms ) )
		// 	return NULL; // maybe in the process of clearing!

		if ( ! $taxonomy || empty( $terms ) )
			return FALSE;

		if ( 1 === count( $terms ) )
			return WordPress\Term::get( reset( $terms ) );

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return WordPress\Term::get( reset( $terms ) );

		if ( empty( $object->{static::SINGLE_TERM_SELECT} ) )
			return WordPress\Term::get( reset( $terms ) );

		if ( TRUE === $object->{static::SINGLE_TERM_SELECT} )
			return apply_filters( sprintf( '%s_singleselect_term_%s', static::BASE, $object->name ),
				WordPress\Term::get( reset( $terms ) ),
				$terms,
				$taxonomy,
				$post
			);

		if ( is_callable( $object->{static::SINGLE_TERM_SELECT} ) )
			return call_user_func_array( $object->{static::SINGLE_TERM_SELECT}, [ $terms, $taxonomy, $post ] );

		return WordPress\Term::get( reset( $terms ) );
	}

	/**
	 * Automatically assigns parent taxonomy terms to posts.
	 *
	 * This function will automatically set parent taxonomy terms whenever terms are set on a post,
	 * with the option to configure specific post types, and/or taxonomies.
	 *
	 * @source https://gist.github.com/tripflex/65dbffc4342cf7077e49d641462b46ad
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function set_object_terms_auto_set_parent_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		if ( empty( $tt_ids ) )
			return;

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( empty( $object->{static::AUTO_SET_PARENT_TERMS} ) || ! $object->hierarchical )
			return;

		foreach ( $tt_ids as $tt_id )
			if ( $parent = wp_get_term_taxonomy_parent_id( $tt_id, $taxonomy ) )
				wp_set_post_terms( $object_id, [ $parent ], $taxonomy, TRUE );
	}

	/**
	 * Automatically assigns child taxonomy terms to posts.
	 *
	 * This function will automatically set child taxonomy terms whenever a parent term is set on a post,
	 * with the option to configure specific post types, and/or taxonomies.
	 *
	 * @source https://gist.github.com/tripflex/33025718246b4ffb0050058dd8a69fe3
	 *
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function set_object_terms_auto_set_child_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		if ( empty( $tt_ids ) )
			return;

		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( empty( $object->{static::AUTO_SET_CHILD_TERMS} ) || ! $object->hierarchical )
			return;

		foreach ( $tt_ids as $tt_id ) {

			$children = get_term_children( $tt_id, $taxonomy );

			if ( ! empty( $children ) )
				wp_set_post_terms( $object_id, $children, $taxonomy, TRUE );
		}
	}

	/**
	 * Filters the terms query default arguments.
	 *
	 * @param  array $defaults
	 * @param  array $taxonomies
	 * @return array $defaults
	 */
	public static function get_terms_defaults( $defaults, $taxonomies )
	{
		if ( empty( $taxonomies ) || count( (array) $taxonomies ) > 1 )
			return $defaults;

		if ( ! $object = WordPress\Taxonomy::object( reset( $taxonomies ) ) )
			return $defaults;

		if ( empty( $object->{static::REVERSE_ORDERED_TERMS} ) )
			return $defaults;

		$defaults['orderby'] = $object->{static::REVERSE_ORDERED_TERMS};
		$defaults['order']   = 'DESC';

		return $defaults;
	}
}

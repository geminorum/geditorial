<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait CoreTaxonomies
{

	// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/
	public function register_taxonomy( $constant, $atts = [], $posttypes = NULL, $caps = NULL )
	{
		$cpt_tax  = TRUE;
		$taxonomy = $this->constant( $constant );
		$plural   = str_replace( '_', '-', Core\L10n::pluralize( $taxonomy ) );

		if ( is_string( $posttypes ) && in_array( $posttypes, [ 'user', 'comment', 'taxonomy' ] ) )
			$cpt_tax = FALSE;

		else if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( ! is_array( $posttypes ) )
			$posttypes = $posttypes ? [ $this->constant( $posttypes ) ] : '';

		$args = self::recursiveParseArgs( $atts, [
			'meta_box_cb'          => FALSE,
			// @REF: https://make.wordpress.org/core/2019/01/23/improved-taxonomy-metabox-sanitization-in-5-1/
			'meta_box_sanitize_cb' => method_exists( $this, 'meta_box_sanitize_cb_'.$constant ) ? [ $this, 'meta_box_sanitize_cb_'.$constant ] : NULL,
			'hierarchical'         => FALSE,
			'public'               => TRUE,
			'show_ui'              => TRUE,
			'show_admin_column'    => FALSE,
			'show_in_quick_edit'   => FALSE,
			'show_in_nav_menus'    => FALSE,
			'show_tagcloud'        => FALSE,
			'default_term'         => FALSE,
			'capabilities'         => $this->_get_taxonomy_caps( $taxonomy, $caps, $posttypes ),
			'query_var'            => $this->constant( $constant.'_query', $taxonomy ),
			'rewrite'              => NULL,

			// 'sort' => NULL, // Whether terms in this taxonomy should be sorted in the order they are provided to `wp_set_object_terms()`.
			// 'args' => [], //  Array of arguments to automatically use inside `wp_get_object_terms()` for this taxonomy.

			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $plural ) ),
			// 'rest_namespace' => 'wp/v2', // @SEE: https://core.trac.wordpress.org/ticket/54536

			/// gEditorial Props
			WordPress\Taxonomy::TARGET_TAXONOMIES_PROP => FALSE,  // or array of taxonomies
			Services\Paired::PAIRED_POSTTYPE_PROP      => FALSE,  // @SEE: `Paired::isTaxonomy()`
		] );

		$rewrite = [

			// NOTE: we can use `example.com/cpt/tax` if cpt registered after the tax
			// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2274

			// NOTE: taxonomy prefix slugs are singular: `/category/`, `/tag/`
			'slug'         => $this->constant( $constant.'_slug', str_replace( '_', '-', $taxonomy ) ),
			'with_front'   => FALSE,
			'hierarchical' => $args['hierarchical'],
			// 'ep_mask'      => EP_NONE,
		];

		if ( is_null( $args['rewrite'] ) )
			$args['rewrite'] = $rewrite;

		else if ( is_array( $args['rewrite'] ) )
			$args['rewrite'] = array_merge( $rewrite, $args['rewrite'] );

		if ( ! $args['meta_box_cb'] && method_exists( $this, 'meta_box_cb_'.$constant ) )
			$args['meta_box_cb'] = [ $this, 'meta_box_cb_'.$constant ];

		else if ( '__checklist_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_checklist_terms_cb' ];

		else if ( '__checklist_reverse_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_checklist_reverse_terms_cb' ];

		else if ( '__checklist_restricted_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_checklist_restricted_terms_cb' ];

		else if ( '__singleselect_terms_callback' === $args['meta_box_cb'] )
			$args['meta_box_cb'] = [ $this, 'taxonomy_meta_box_singleselect_terms_cb' ];

		if ( ! array_key_exists( 'labels', $args ) )
			$args['labels'] = $this->get_taxonomy_labels( $constant );

		if ( ! array_key_exists( 'update_count_callback', $args ) ) {

			if ( $cpt_tax )
				// $args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ];
				$args['update_count_callback'] = '_update_post_term_count';

			else if ( 'user' == $posttypes )
				$args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateUserTermCountCallback' ];

			else if ( 'comment' == $posttypes )
				$args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ];

			else if ( 'taxonomy' == $posttypes )
				$args['update_count_callback'] = [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ];

			// WTF: if not else ?!

			// if ( is_admin() && ( $cpt_tax || 'user' == $posttypes || 'comment' == $posttypes ) )
			// 	$this->_hook_taxonomies_excluded( $constant, 'recount' );
		}

		if ( FALSE !== $args['default_term'] )
			$args['default_term'] = $this->_get_taxonomy_default_term( $constant, $args['default_term'] );

		// NOTE: gEditorial Prop
		if ( ! array_key_exists( 'has_archive', $args ) && $args['public'] && $args['show_ui'] )
			$args['has_archive'] = $this->constant( $constant.'_archive', $plural );

		// NOTE: gEditorial Prop
		if ( ! array_key_exists( 'menu_icon', $args ) )
			$args['menu_icon'] = $this->get_taxonomy_icon( $constant, $args['hierarchical'] );

		$object = register_taxonomy( $taxonomy, $cpt_tax ? $posttypes : '', $args );

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		return $object;
	}

	public function get_taxonomy_labels( $constant )
	{
		if ( isset( $this->strings['labels'] )
			&& array_key_exists( $constant, $this->strings['labels'] ) )
				$labels = $this->strings['labels'][$constant];
		else
			$labels = [];

		if ( FALSE === $labels )
			return FALSE;

		// DEPRECATED: back-comp
		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Helper::generateTaxonomyLabels(
				$this->strings['noops'][$constant],
				$labels,
				$this->constant( $constant )
			);

		return $labels;
	}

	public function get_taxonomy_icon( $constant = NULL, $hierarchical = FALSE, $fallback = FALSE )
	{
		$icons   = $this->get_module_icons();
		$default = $hierarchical ? 'category' : 'tag';
		$module  = $this->module->icon ?? FALSE;

		if ( is_null( $fallback ) && $module )
			$icon = $module;

		else if ( $fallback )
			$icon = $fallback;

		else
			$icon = $default;

		if ( $constant && isset( $icons['taxonomies'] ) && array_key_exists( $constant, (array) $icons['taxonomies'] ) )
			$icon = $icons['taxonomies'][$constant];

		if ( is_null( $icon ) && $module )
			$icon = $module;

		if ( is_array( $icon ) )
			$icon = Core\Icon::getBase64( $icon[1], $icon[0] );

		else if ( $icon )
			$icon = 'dashicons-'.$icon;

		return $icon ?: 'dashicons-'.$default;
	}

	protected function _get_taxonomy_caps( $taxonomy, $caps, $posttypes )
	{
		if ( is_array( $caps ) )
			return $caps;

		$custom = [
			'manage_terms' => 'manage_'.$taxonomy,
			'edit_terms'   => 'edit_'.$taxonomy,
			'delete_terms' => 'delete_'.$taxonomy,
			'assign_terms' => 'assign_'.$taxonomy,
		];

		if ( TRUE === $caps )
			return $custom;

		// core default
		if ( FALSE === $caps )
			return [
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			];

		$defaults = [
			'manage_terms' => 'edit_others_posts',
			'edit_terms'   => 'edit_others_posts',
			'delete_terms' => 'edit_others_posts',
			'assign_terms' => 'edit_posts',
		];

		// FIXME: `edit_users` is not working!
		// maybe map meta cap
		if ( 'user' == $posttypes )
			return [
				'manage_terms' => 'edit_users',
				'edit_terms'   => 'list_users',
				'delete_terms' => 'list_users',
				'assign_terms' => 'list_users',
			];

		else if ( 'taxonomy' === $posttypes )
			return $custom; // FIXME: must filter meta_cap

		else if ( 'comment' == $posttypes )
			return $defaults; // FIXME: WTF?!

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $defaults;

		if ( ! is_null( $caps ) )
			$posttype = $this->constant( $caps );

		else if ( count( $posttypes ) )
			$posttype = $posttypes[0];

		else
			return $defaults;

		if ( ! in_array( $posttype, gEditorial()->module( 'roles' )->posttypes() ) )
			return $defaults;

		$base = gEditorial()->module( 'roles' )->constant( 'base_type' );

		return [
			'manage_terms' => 'edit_others_'.$base[1],
			'edit_terms'   => 'edit_others_'.$base[1],
			'delete_terms' => 'edit_others_'.$base[1],
			'assign_terms' => 'edit_'.$base[1],
		];
	}

	// WTF: the core default term system is messed-up!
	// @REF: https://core.trac.wordpress.org/ticket/43517
	protected function _get_taxonomy_default_term( $constant, $passed_arg = NULL )
	{
		return FALSE; // FIXME <------------------------------------------------

		// disabled by settings
		if ( is_null( $passed_arg ) && ! $this->get_setting( 'assign_default_term' ) )
			return FALSE;

		if ( isset( $this->strings['defaults'] )
			&& array_key_exists( $constant, $this->strings['defaults'] ) )
				$term = $this->strings['defaults'][$constant];
		else
			$term = [];

		if ( empty( $term['name'] ) )
			$term['name'] = is_string( $passed_arg )
				? $passed_arg
				: _x( 'Uncategorized', 'Module: Taxonomy Default Term Name', 'geditorial' );

		if ( empty( $term['slug'] ) )
			$term['slug'] = is_string( $passed_arg ) ? $passed_arg : 'uncategorized';

		return $term;
	}

	// DEFAULT CALLBACK for `__checklist_terms_callback`
	public function taxonomy_meta_box_checklist_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ] );
		echo '</div>';
	}

	// DEFAULT CALLBACK for `__checklist_reverse_terms_callback`
	public function taxonomy_meta_box_checklist_reverse_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		// NOTE: getting reverse-sorted span terms to pass into checklist
		$terms = WordPress\Taxonomy::listTerms( $box['args']['taxonomy'], 'all', [ 'order' => 'DESC' ] );

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, [ 'taxonomy' => $box['args']['taxonomy'], 'posttype' => $post->post_type ], $terms );
		echo '</div>';
	}

	// DEFAULT CALLBACK for `__checklist_restricted_terms_callback`
	public function taxonomy_meta_box_checklist_restricted_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy' => $box['args']['taxonomy'],
			'posttype' => $post->post_type,
		];

		if ( $this->role_can( sprintf( 'taxonomy_%s_locking_terms', $args['taxonomy'] ), NULL, FALSE, FALSE ) )
			$args['role'] = $this->get_setting( sprintf( 'taxonomy_%s_restricted_visibility', $args['taxonomy'] ), 'disabled' );

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $args );
		echo '</div>';
	}

	// DEFAULT CALLBACK for `__singleselect_terms_callback`
	public function taxonomy_meta_box_singleselect_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::singleselectTerms( $post->ID, [
				'taxonomy' => $box['args']['taxonomy'],
				'posttype' => $post->post_type,
				// NOTE: metabox title already displays the taxonomy label
				'none'     => Settings::showOptionNone(),
				'empty'    => NULL, // displays empty box with link
			] );
		echo '</div>';
	}

	public function is_taxonomy( $constant, $term = NULL )
	{
		if ( ! $constant )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		return $this->constant( $constant ) == $term->taxonomy;
	}

	// NOTE: reversed fallback/fallback-key
	public function get_taxonomy_label( $constant, $label = 'name', $fallback = '', $fallback_key = NULL )
	{
		return Helper::getTaxonomyLabel( $this->constant( $constant, $constant ), $label, $fallback_key, $fallback );
	}

	protected function do_force_assign_parents( $post, $taxonomy )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$currents = wp_get_object_terms( $post->ID, $taxonomy, [
			'fields'                 => 'ids',
			'orderby'                => 'none',
			'hide_empty'             => FALSE,
			'update_term_meta_cache' => FALSE,
		] );

		if ( empty( $currents ) || self::isError( $currents ) )
			return FALSE;

		return wp_set_object_terms( $post->ID, WordPress\Taxonomy::appendParentTermIDs( $currents, $taxonomy ), $taxonomy, TRUE );
	}

	/**
	 * Makes available empty terms on sitempas.
	 *
	 * @param  string $constant
	 * @return bool   $hooked
	 */
	protected function hook_taxonomy_sitemap_show_empty( $constant )
	{
		if ( ! $target = $this->constant( $constant ) )
			return FALSE;

		add_filter( 'wp_sitemaps_taxonomies_query_args',
			static function ( $args, $taxonomy ) use ( $target ) {
				if ( $target === $taxonomy )
					$args['hide_empty'] = FALSE;
				return $args;
			}, 10, 2 );

		return TRUE;
	}
}

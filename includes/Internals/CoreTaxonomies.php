<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait CoreTaxonomies
{

	// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/
	public function register_taxonomy( $constant, $atts = [], $posttypes = NULL, $settings = [] )
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
			'show_in_quick_edit'   => FALSE, // TODO: support single select on quick/bulk edit
			'show_in_nav_menus'    => FALSE,
			'show_tagcloud'        => FALSE,
			'default_term'         => FALSE,
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

		$args['meta_box_cb'] = $this->determine_taxonomy_meta_box_cb( $constant, $args['meta_box_cb'], $args['hierarchical'] );

		if ( ! array_key_exists( 'labels', $args ) )
			$args['labels'] = $this->get_taxonomy_labels( $constant );

		if ( ! array_key_exists( 'update_count_callback', $args ) ) {

			if ( $cpt_tax )
				// $args['update_count_callback'] = [ WordPress\Database::class, 'updateCountCallback' ];
				$args['update_count_callback'] = '_update_post_term_count';

			else if ( 'user' == $posttypes )
				$args['update_count_callback'] = [ WordPress\Database::class, 'updateUserTermCountCallback' ];

			else if ( 'comment' == $posttypes )
				$args['update_count_callback'] = [ WordPress\Database::class, 'updateCountCallback' ];

			else if ( 'taxonomy' == $posttypes )
				$args['update_count_callback'] = [ WordPress\Database::class, 'updateCountCallback' ];

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

		$object = register_taxonomy(
			$taxonomy,
			$cpt_tax ? $posttypes : '',
			$this->apply_taxonomy_object_settings(
				$taxonomy,
				$args,
				$settings,
				$posttypes,
				$constant
			)
		);

		// TODO: `after_taxonomy_object_register()`

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		return $object;
	}

	// TODO: support for taxonomy icon: @SEE: `$args['menu_icon']`
	protected function apply_taxonomy_object_settings( $taxonomy, $args = [], $atts = [], $posttypes = NULL, $constant = FALSE )
	{
		$settings = self::atts( [
			'is_viewable'     => NULL,
			'custom_captype'  => FALSE,
			'admin_managed'   => NULL,    // psudo-setting: manage only for admins
			'auto_parents'    => FALSE,
			'auto_children'   => FALSE,
			'single_selected' => FALSE,   // TRUE or callable: @SEE: `Services\TermHierarchy::getSingleSelectTerm()`
			'reverse_ordered' => NULL,
			'auto_assigned'   => NULL,
		], $atts );

		foreach ( $settings as $setting => $value ) {

			// NOTE: `NULL` means do not touch!
			if ( is_null( $value ) )
				continue;

			switch ( $setting ) {

				case 'is_viewable':

					// NOTE: only applies if the setting is `disabled`
					if ( $value )
						break;

					$args = array_merge( $args, [
						'public'             => FALSE,
						'publicly_queryable' => FALSE, // @REF: `is_taxonomy_viewable()`
						'show_in_nav_menus'  => FALSE,
						'rewrite'            => FALSE,   // WTF?!
					] );

					// makes `Tabloid` links visible for non-viewable taxonomies
					add_filter( $this->hook_base( 'tabloid', 'is_term_viewable' ),
						static function ( $viewable, $term ) use ( $taxonomy ) {
							return $term->taxonomy === $taxonomy ? TRUE : $viewable;
						}, 12, 2 );

					// makes available on current module
					add_filter( $this->hook_base( $this->key, 'is_term_viewable' ),
						static function ( $viewable, $term ) use ( $taxonomy ) {
							return $term->taxonomy === $taxonomy ? TRUE : $viewable;
						}, 12, 2 );

					break;

				case 'custom_captype':

					if ( TRUE === $value ) {

						$captype = $this->constant_plural( $constant );

						$args['capabilities'] = [
							'manage_terms' => sprintf( 'manage_%s', $captype[1] ),
							'edit_terms'   => sprintf( 'edit_%s', $captype[1] ),
							'delete_terms' => sprintf( 'delete_%s', $captype[1] ),
							'assign_terms' => sprintf( 'assign_%s', $captype[1] ),
						];

					} else if ( self::bool( $value ) ) {

						$captype = empty( $value )
							? $this->constant_plural( $constant )
							: $value; // FIXME: WTF: what if passed `1`?!

						if ( $settings['admin_managed'] )
							$args['capabilities'] = [
								'manage_terms' => 'manage_options',
								'edit_terms'   => 'manage_options',
								'delete_terms' => 'manage_options',
								'assign_terms' => sprintf( 'edit_%s', $captype[1] ),
							];

						else
							$args['capabilities'] = [
								'manage_terms' => sprintf( 'manage_%s', $captype[1] ),
								'edit_terms'   => sprintf( 'manage_%s', $captype[1] ),
								'delete_terms' => sprintf( 'manage_%s', $captype[1] ),
								'assign_terms' => sprintf( 'edit_%s', $captype[1] ),
							];

					} else if ( 'comment' === $posttypes ) {

						// FIXME: WTF?!

					} else if ( 'taxonomy' === $posttypes ) {

						// FIXME: must filter meta_cap

					} else if ( 'user' === $posttypes ) {

						// FIXME: `edit_users` is not working!
						// maybe map meta cap

						// FIXME: WTF: maybe merge the capabilities
						// if ( ! array_key_exists( 'capabilities', $args ) )
						$args['capabilities'] = [
							'manage_terms' => 'edit_users',
							'edit_terms'   => 'list_users',
							'delete_terms' => 'list_users',
							'assign_terms' => 'list_users',
						];

					} else if ( is_array( $posttypes ) && count( $posttypes ) && gEditorial()->enabled( 'roled' ) ) {

						if ( in_array( $posttypes[0], gEditorial()->module( 'roled' )->posttypes() ) ) {

							$captype = gEditorial()->module( 'roled' )->constant( 'base_type' );

							$args['capabilities'] = [
								'manage_terms' => sprintf( 'edit_others_%s', $captype[1] ),
								'edit_terms'   => sprintf( 'edit_others_%s', $captype[1] ),
								'delete_terms' => sprintf( 'edit_others_%s', $captype[1] ),
								'assign_terms' => sprintf( 'edit_%s', $captype[1] ),
							];
						}

					} else if ( $settings['admin_managed'] ) {

						// TODO: suppport custom cap instead of `manage_options`

						if ( ! array_key_exists( 'capabilities', $args ) )
							$args['capabilities'] = [
								'manage_terms' => 'manage_options',
								'edit_terms'   => 'manage_options',
								'delete_terms' => 'manage_options',
								'assign_terms' => 'edit_posts',
							];

					} else {

						if ( ! array_key_exists( 'capabilities', $args ) )
							$args['capabilities'] = [
								'manage_terms' => 'edit_others_posts',
								'edit_terms'   => 'edit_others_posts',
								'delete_terms' => 'edit_others_posts',
								'assign_terms' => 'edit_posts',
							];
					}

					break;

				case 'auto_parents'    : $args[Services\TermHierarchy::AUTO_SET_PARENT_TERMS] = $value; break;
				case 'auto_children'   : $args[Services\TermHierarchy::AUTO_SET_CHILD_TERMS]  = $value; break;
				case 'single_selected' : $args[Services\TermHierarchy::SINGLE_TERM_SELECT]    = $value; break;
				case 'reverse_ordered' : $args[Services\TermHierarchy::REVERSE_ORDERED_TERMS] = $value; break;
				case 'auto_assigned'   : $args[Services\TermHierarchy::AUTO_ASSIGNED_TERMS]   = $value; break;

				// TODO: support combination of settings:
				// -- restricted terms
				// -- `metabox_advanced`
				// -- `selectmultiple_term`

				// TODO: support taxonomy for taxonomies
			}
		}

		return $args;
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

	// FIXME: DEPRECATED
	protected function _get_taxonomy_caps( $taxonomy, $caps, $posttypes )
	{
		if ( is_array( $caps ) )
			return $caps;

		// wp core default
		if ( FALSE === $caps )
			return [
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			];

		$custom = [
			'manage_terms' => 'manage_'.$taxonomy,
			'edit_terms'   => 'edit_'.$taxonomy,
			'delete_terms' => 'delete_'.$taxonomy,
			'assign_terms' => 'assign_'.$taxonomy,
		];

		if ( TRUE === $caps )
			return $custom;

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

		if ( ! gEditorial()->enabled( 'roled' ) )
			return $defaults;

		if ( ! is_null( $caps ) )
			$posttype = $this->constant( $caps );

		else if ( count( $posttypes ) )
			$posttype = $posttypes[0];

		else
			return $defaults;

		if ( ! in_array( $posttype, gEditorial()->module( 'roled' )->posttypes() ) )
			return $defaults;

		$base = gEditorial()->module( 'roled' )->constant( 'base_type' );

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
	public function taxonomy_meta_box_checklist_terms_cb( $post, $box = FALSE, $taxonomy = NULL )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy'   => $taxonomy ?? $box['args']['taxonomy'],
			'posttype'   => $post->post_type,
			'header'     => FALSE === $box,
			'empty_link' => FALSE === $box ? FALSE : NULL,
		];

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'before' ),
				$args['taxonomy'],
				$post,
				$box
			);

			MetaBox::checklistTerms( $post->ID, $args );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'after' ),
				$args['taxonomy'],
				$post,
				$box
			);

		if ( FALSE !== $box )
			echo '</div>';
	}

	// DEFAULT CALLBACK for `__checklist_reverse_terms_callback`
	// TODO: compliance with `reverse_ordered` setting
	public function taxonomy_meta_box_checklist_reverse_terms_cb( $post, $box = FALSE, $taxonomy = NULL )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy'   => $taxonomy ?? $box['args']['taxonomy'],
			'posttype'   => $post->post_type,
			'header'     => FALSE === $box,
			'empty_link' => FALSE === $box ? FALSE : NULL,
		];

		// NOTE: getting reverse-sorted span terms to pass into checklist
		$terms = WordPress\Taxonomy::listTerms( $args['taxonomy'], 'all', [ 'order' => 'DESC' ] );

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'before' ),
				$args['taxonomy'],
				$post,
				$box
			);

			MetaBox::checklistTerms( $post->ID, $args, $terms );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'after' ),
				$args['taxonomy'],
				$post,
				$box
			);

		if ( FALSE !== $box )
			echo '</div>';
	}

	// DEFAULT CALLBACK for `__checklist_restricted_terms_callback`
	public function taxonomy_meta_box_checklist_restricted_terms_cb( $post, $box = FALSE, $taxonomy = NULL )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy'   => $taxonomy ?? $box['args']['taxonomy'],
			'posttype'   => $post->post_type,
			'header'     => FALSE === $box,
			'empty_link' => FALSE === $box ? FALSE : NULL,
		];

		if ( $this->role_can( sprintf( 'taxonomy_%s_locking_terms', $args['taxonomy'] ), NULL, FALSE, FALSE ) )
			$args['restricted'] = $this->get_setting( sprintf( 'taxonomy_%s_restricted_visibility', $args['taxonomy'] ), 'disabled' );

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'before' ),
				$args['taxonomy'],
				$post,
				$box
			);

			MetaBox::checklistTerms( $post->ID, $args );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'after' ),
				$args['taxonomy'],
				$post,
				$box
			);

		if ( FALSE !== $box )
			echo '</div>';
	}

	// DEFAULT CALLBACK for `__singleselect_terms_callback`
	public function taxonomy_meta_box_singleselect_terms_cb( $post, $box = FALSE, $taxonomy = NULL )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy' => $taxonomy ?? $box['args']['taxonomy'],
			'posttype' => $post->post_type,
			// 'header'   => FALSE === $box, // no need for header on dropdowns
		];

		if ( FALSE !== $box ) {
			$args['none']  = Settings::showOptionNone();  // label already displayed on the metabox title
			$args['empty'] = NULL;                        // displays empty box with link
		} else {
			$args['empty_link'] = FALSE;
		}

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'before' ),
				$args['taxonomy'],
				$post,
				$box
			);

			MetaBox::singleselectTerms( $post->ID, $args );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'after' ),
				$args['taxonomy'],
				$post,
				$box
			);

		if ( FALSE !== $box )
			echo '</div>';
	}

	// DEFAULT CALLBACK for `__singleselect_restricted_terms_callback`
	public function taxonomy_meta_box_singleselect_restricted_terms_cb( $post, $box = FALSE, $taxonomy = NULL )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$args = [
			'taxonomy' => $taxonomy ?? $box['args']['taxonomy'],
			'posttype' => $post->post_type,
			// 'header'   => FALSE === $box, // no need for header on dropdowns
		];

		if ( FALSE !== $box ) {
			$args['none']  = Settings::showOptionNone();  // label already displayed on the metabox title
			$args['empty'] = NULL;                        // displays empty box with link
		} else {
			$args['empty_link'] = FALSE;
		}

		if ( $this->role_can( sprintf( 'taxonomy_%s_locking_terms', $args['taxonomy'] ), NULL, FALSE, FALSE ) )
			$args['restricted'] = $this->get_setting( sprintf( 'taxonomy_%s_restricted_visibility', $args['taxonomy'] ), 'disabled' );

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'before' ),
				$args['taxonomy'],
				$post,
				$box
			);

			MetaBox::singleselectTerms( $post->ID, $args );

			do_action( $this->hook_base( $args['taxonomy'], 'metabox', 'after' ),
				$args['taxonomy'],
				$post,
				$box
			);

		if ( FALSE !== $box )
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
		return Helper::getTaxonomyLabel(
			$this->constant( $constant, $constant ),
			$label,
			$fallback_key,
			$fallback
		);
	}

	public function is_term_viewable( $term = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		return $this->filters( 'is_term_viewable', WordPress\Term::viewable( $term ), $term );
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

	// TODO: integrate to `apply_taxonomy_object_settings()`
	protected function determine_taxonomy_meta_box_cb( $constant, $arg = NULL, $hierarchical = FALSE )
	{
		if ( ! $arg && method_exists( $this, 'meta_box_cb_'.$constant ) )
			return [ $this, 'meta_box_cb_'.$constant ];

		if ( is_null( $arg ) )
			return $hierarchical
				? [ $this, 'coretax__core_categories_metabox' ]
				: [ $this, 'coretax__core_tags_metabox' ];

		if ( ! $arg || is_array( $arg ) )
			return $arg;

		if ( '__checklist_terms_callback' === $arg )
			return [ $this, 'taxonomy_meta_box_checklist_terms_cb' ];

		if ( '__checklist_reverse_terms_callback' === $arg )
			return [ $this, 'taxonomy_meta_box_checklist_reverse_terms_cb' ];

		if ( '__checklist_restricted_terms_callback' === $arg )
			return [ $this, 'taxonomy_meta_box_checklist_restricted_terms_cb' ];

		if ( '__singleselect_terms_callback' === $arg )
			return [ $this, 'taxonomy_meta_box_singleselect_terms_cb' ];

		if ( '__singleselect_restricted_terms_callback' === $arg )
			return [ $this, 'taxonomy_meta_box_singleselect_restricted_terms_cb' ];

		return $arg;
	}

	// @REF: `register_and_do_post_meta_boxes()`
	protected function add_taxonomy_meta_box( $constant, $callback = NULL )
	{
		$taxonomy = get_taxonomy( $this->constant( $constant, $constant ) );

		add_meta_box(
			sprintf( $taxonomy->hierarchical ? '%sdiv' : 'tagsdiv-%s', $taxonomy->name ),
			$taxonomy->labels->name,
			$this->determine_taxonomy_meta_box_cb( $constant, $callback ?: FALSE, $taxonomy->hierarchical ),
			NULL,
			'side',
			'core',
			[
				'taxonomy'               => $taxonomy->name,
				'__back_compat_meta_box' => TRUE,
			]
		);
	}

	/**
	 * Sets actions/filters for given taxonomy metabox.
	 *
	 * @param  string        $constant
	 * @param  string        $posttype
	 * @param  null|callable $callback
	 * @param  int           $priority
	 * @param  null|string   $context
	 * @return bool          $hooked
	 */
	protected function hook_taxonomy_metabox_mainbox( $constant, $posttype, $callback = NULL, $priority = 80, $context = NULL )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return FALSE;

		if ( empty( $object->{MetaBox::POSTTYPE_MAINBOX_PROP} ) ) {

			add_action( 'add_meta_boxes',
				function ( $posttype, $post ) use ( $constant, $callback ) {
					$this->add_taxonomy_meta_box( $constant, $callback );
				}, 20, 2 );

			return TRUE;
		}

		$taxonomy = $this->constant( $constant );
		$callback = $this->determine_taxonomy_meta_box_cb(
			$constant, $callback, is_taxonomy_hierarchical( $taxonomy ) );

		add_action( $this->hook_base( 'metabox', $context ?? 'mainbox', $posttype ),
			function ( $post, $box, $context, $screen ) use ( $taxonomy, $callback ) {
				call_user_func_array( $callback, [ $post, FALSE, $taxonomy ] );
			}, $priority, 4 );

		return TRUE;
	}

	// TODO: apply hook on our own callbacks!
	public function coretax__core_categories_metabox( $post, $box )
	{
		$taxonomy = empty( $box['args']['taxonomy'] ) ? 'category' : $box['args']['taxonomy'];

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

		do_action( $this->hook_base( $taxonomy, 'metabox', 'before' ),
			$taxonomy,
			$post,
			$box
		);

		\post_categories_meta_box( $post, $box );

		do_action( $this->hook_base( $taxonomy, 'metabox', 'after' ),
			$taxonomy,
			$post,
			$box
		);

		if ( FALSE !== $box )
			echo '</div>';
	}

	public function coretax__core_tags_metabox( $post, $box )
	{
		$taxonomy = empty( $box['args']['taxonomy'] ) ? 'post_tag' : $box['args']['taxonomy'];

		if ( FALSE !== $box )
			echo $this->wrap_open( '-admin-metabox' );

		do_action( $this->hook_base( $taxonomy, 'metabox', 'before' ),
			$taxonomy,
			$post,
			$box
		);

		\post_tags_meta_box( $post, $box );

		do_action( $this->hook_base( $taxonomy, 'metabox', 'after' ),
			$taxonomy,
			$post,
			$box
		);

		if ( FALSE !== $box )
			echo '</div>';
	}

	protected function hook_taxonomy_parents_as_views( $screen, $constant, $setting = 'parents_as_views' )
	{
		if ( TRUE !== $setting && ! $this->get_setting( $setting ) )
			return FALSE;

		if ( ! $taxonomy = WordPress\Taxonomy::object( $this->constant( $constant ) ) )
			return FALSE;

		add_filter( "views_{$screen->id}",
			static function ( $views ) use ( $taxonomy, $screen ) {

				$terms = get_terms( [
					'taxonomy'   => $taxonomy->name,
					'parent'     => 0,
					'hide_empty' => TRUE,

					'update_term_meta_cache' => FALSE,
				] );

				if ( ! $terms || is_wp_error( $terms ) )
					return $views;

				$query = WordPress\Taxonomy::queryVar( $taxonomy );
				$label = Helper::getTaxonomyLabel( $taxonomy, 'extended_label' );

				foreach ( $terms as $term )
					// TODO: prepend counts from `Recount` module
					$views[sprintf( '%s-%s', $taxonomy->name, $term->slug )] = Core\HTML::tag( 'a', [
						'href'  => Core\WordPress::getPostTypeEditLink( $screen->post_type, 0, [ $query => $term->slug ] ),
						'title' => sprintf( '%s: %s', $label, $term->name ),
						'class' => $term->slug === self::req( $query ) ? 'current' : FALSE,
					], $term->name );

				if ( ! WordPress\Taxonomy::countPostsWithoutTerms( $taxonomy->name, $screen->post_type ) )
					return $views;

				$views[sprintf( '%s--none', $taxonomy->name )] =  Core\HTML::tag( 'a', [
					'href'  => Core\WordPress::getPostTypeEditLink( $screen->post_type, 0, [ $query => '-1' ] ),
					'title' => $label,
					'class' => '-1' === self::req( $query ) ? 'current' : FALSE,
				], Helper::getTaxonomyLabel( $taxonomy, 'show_option_no_items' ) );

				return $views;
			}, 99, 1 );


		add_action( 'parse_query',
			static function ( &$query ) use ( $taxonomy ) {
				Listtable::parseQueryTaxonomy( $query, $taxonomy->name );
			}, 12, 1 );

		return TRUE;
	}

	/**
	 * Hooks the filter for taxonomy parent terms on imports.
	 * @SEE: `pairedcore__hook_importer_term_parents()`
	 *
	 * @param  bool|string $setting
	 * @return bool        $hooked
	 */
	protected function hook_taxonomy_importer_term_parents( $taxonomy, $setting = 'force_parents' )
	{
		if ( TRUE !== $setting && ! $this->get_setting( $setting ) )
			return FALSE;

		add_filter( $this->hook_base( 'importer', 'set_terms', $taxonomy ),
			static function ( $terms, $currents, $source_id, $post_id, $oldpost, $override, $append ) use ( $taxonomy ) {

				$parents = [];

				foreach ( (array) $currents as $current )
					$parents = array_merge( $parents, WordPress\Taxonomy::getTermParents( $current, $taxonomy ) );

				foreach ( (array) $terms as $term )
					$parents = array_merge( $parents, WordPress\Taxonomy::getTermParents( $term, $taxonomy ) );

				return Core\Arraay::prepNumeral( $terms, $parents );

			}, 12, 7 );

		return TRUE;
	}

	protected function hook_taxonomy_tabloid_exclude_rendered( $constants )
	{
		$this->filter_append(
			$this->hook_base( 'tabloid', 'post_terms_exclude_rendered' ),
			$this->constants( $constants )
		);
	}
}

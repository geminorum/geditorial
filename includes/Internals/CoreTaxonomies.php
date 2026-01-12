<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreTaxonomies
{
	// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/
	public function register_taxonomy( $constant, $atts = [], $targets = NULL, $settings_atts = [] )
	{
		$taxonomy = $this->constant( $constant );
		$plural   = str_replace( '_', '-', Core\L10n::pluralize( $taxonomy ) );

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
			'query_var'            => $this->constant( $constant.'_query', $taxonomy ),
			'rewrite'              => NULL,

			// 'sort' => NULL, // Whether terms in this taxonomy should be sorted in the order they are provided to `wp_set_object_terms()`.
			// 'args' => [], //  Array of arguments to automatically use inside `wp_get_object_terms()` for this taxonomy.

			'show_in_rest'   => TRUE,
			'rest_base'      => NULL,
			// 'rest_namespace' => 'wp/v2',   // @SEE: https://core.trac.wordpress.org/ticket/54536

			/// `gEditorial` Props
			Services\Paired::PAIRED_POSTTYPE_PROP => FALSE,  // @SEE: `Paired::isTaxonomy()`
		] );

		$rewrite = [

			// NOTE: we can use `example.com/cpt/tax` if custom-post-type registered after the taxonomy
			// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2274

			// NOTE: taxonomy prefix slugs are singular: `/category/`, `/tag/`
			'slug'         => $this->constant( $constant.'_slug', str_replace( '_', '-', $taxonomy ) ),
			'with_front'   => FALSE,
			'hierarchical' => $args['hierarchical'],
			'ep_mask'      => $args['hierarchical'] ? EP_CATEGORIES : EP_TAGS, // default is `EP_NONE`
		];

		if ( is_null( $args['rewrite'] ) )
			$args['rewrite'] = $rewrite;

		else if ( is_array( $args['rewrite'] ) )
			$args['rewrite'] = array_merge( $rewrite, $args['rewrite'] );

		if ( is_null( $args['rest_base'] ) ) {

			if ( $rest_base = $this->constant( $constant.'_rest' ) )
				$args['rest_base'] = $rest_base;

			else if ( $rest_base = $this->constant( $constant.'_archive' ) )
				$args['rest_base'] = $rest_base;

			else if ( ! empty( $args[Services\Paired::PAIRED_POSTTYPE_PROP] ) )
				$args['rest_base'] = sprintf( Services\Paired::PAIRED_TAXONOMY_REST, $plural );

			else
				$args['rest_base'] = $plural;
		}

		$args['meta_box_cb'] = $this->determine_taxonomy_meta_box_cb( $constant, $args['meta_box_cb'], $args['hierarchical'] );

		// TODO: get `$args['description']` from module strings
		if ( ! array_key_exists( 'labels', $args ) )
			$args['labels'] = $this->get_taxonomy_labels( $constant );

		if ( FALSE !== $args['default_term'] )
			$args['default_term'] = $this->_get_taxonomy_default_term( $constant, $args['default_term'] );

		// NOTE: `gEditorial` Prop
		if ( ! array_key_exists( 'has_archive', $args ) && $args['public'] && $args['show_ui'] )
			$args['has_archive'] = $this->constant( $constant.'_archive', $plural );

		// NOTE: ordering here is important!
		$settings = self::atts( [
			'parent_module'   => $this->key,
			'target_object'   => 'post',   // `post`/`user`/`comment`/`taxonomy`/`none`
			'custom_icon'     => TRUE,
			'is_viewable'     => NULL,
			'custom_captype'  => FALSE,
			'admin_managed'   => NULL,     // pseudo-setting: manage only for admins
			'content_rich'    => NULL,     // pseudo-setting: the terms have additional content beside just assignment to posts
			'auto_parents'    => FALSE,
			'auto_children'   => FALSE,
			'single_selected' => FALSE,    // TRUE or callable: @SEE: `Services\TermHierarchy::getSingleSelectTerm()`
			'reverse_ordered' => NULL,     // the value used on `orderby`: `name`/`id`
			'auto_assigned'   => NULL,
			'terms_related'   => NULL,
			'archive_content' => NULL,     // to suggest on content of the archive page // TODO
			'meta_tagline'    => NULL,     // FALSE, meta-key or `TRUE` for default `tagline` // TODO
			'suitable_metas'  => NULL,     // list of meta suggested for this taxonomy: `field` => `title` / NULL // TODO
			'search_titles'   => NULL,
			'ical_source'     => TRUE,     // `TRUE`/`FALSE`/`paired`
		], $settings_atts );

		$target_object = $settings['target_object'] ?: 'post';

		foreach ( $settings as $setting => $value ) {

			// NOTE: `NULL` means do not touch!
			if ( is_null( $value ) )
				continue;

			switch ( $setting ) {

				case 'parent_module':

					// TODO: use `const`
					$args[$this->hook_base( 'module' )] = $value;
					break;

				case 'target_object':

					$callback = array_key_exists( 'update_count_callback', $args );

					if ( ! $value || 'post' === $value ) {

						if ( is_null( $targets ) )
							$targets = $this->posttypes();

						else if ( $targets && ! is_array( $targets ) )
							$targets = $targets ? [ $this->constant( $targets ) ] : '';

						if ( ! $callback )
							// $args['update_count_callback'] = [ WordPress\Database::class, 'updateCountCallback' ];
							$args['update_count_callback'] = '_update_post_term_count';

					} else if ( 'user' === $value ) {

						$target_object = 'user';

						// TODO: prefix with `users`: "{$constant}_slug" => 'users/{$taxonomy}',

						if ( ! $callback )
							$args['update_count_callback'] = [ WordPress\Database::class, 'updateUserTermCountCallback' ];

					} else if ( 'comment' === $value ) {

						$target_object = 'comment';

						if ( ! $callback )
							$args['update_count_callback'] = [ WordPress\Database::class, 'updateCountCallback' ];

					} else if ( 'taxonomy' === $value ) {

						$target_object = 'taxonomy';

						if ( is_null( $targets ) )
							$targets = $this->taxonomies();

						else if ( $targets && ! is_array( $targets ) )
							$targets = $targets ? [ $this->constant( $targets ) ] : '';

						$args[Services\TaxonomyTaxonomy::TARGET_TAXONOMIES_PROP] = $targets;

						if ( ! $callback )
							$args['update_count_callback'] = [ WordPress\Database::class, 'updateCountCallback' ];

					} else {

						$target_object = FALSE; // WTF: Unknown!
					}

					// if ( $target_object && 'post' !== $target_object && is_admin() )
					// 	$this->_hook_taxonomies_excluded( $constant, 'recount' );

					break;

				case 'custom_icon':

					/**
					 * NOTE: `menu_icon` here is `gEditorial` prop, WordPress has no icon support for taxonomies.
					 * NOTE: following is from `register_post_type()` docs:
					 *
					 * The URL to the icon to be used for this menu. Pass a
					 * `base64-encoded` SVG using a data URI, which will be
					 * colored to match the color scheme -this should begin
					 * with `data:image/svg+xml;base64,`.
					 *
					 * Pass the name of a `Dashicons` helper class to use a font
					 * icon, e.g. `dashicons-chart-pie`.
					 *
					 * Pass `none` to leave `div.wp-menu-image` empty so an icon
					 * can be added via CSS.
					 *
					 * Default is to use the posts icon.
					 */

					if ( array_key_exists( 'menu_icon', $args ) )
						break;

					if ( TRUE === $value && in_array( $constant, [
						'main_taxonomy',
						'primary_taxonomy',
						'category_taxonomy',
						'primary_paired',
					], TRUE ) )
						$icon = $this->module->icon;

					else if ( TRUE === $value && in_array( $constant, [
						'status_taxonomy',
					], TRUE ) )
						$icon = 'post-status';

					else if ( TRUE === $value && in_array( $constant, [
						'span_taxonomy',
						'year_taxonomy',
					], TRUE ) )
						$icon = 'backup';

					else if ( TRUE === $value )
						$icon = $args['hierarchical'] ? 'category' : 'tag';

					else if ( $value )
						$icon = $value;

					else
						break;

					if ( is_array( $icon ) )
						$args['menu_icon'] = Core\Icon::getBase64( $icon[1], $icon[0] );

					else
						$args['menu_icon'] = sprintf( 'dashicons-%s', $icon );

					// NOTE: passing icon on the original format: string/array
					$args[Services\Icons::MENUICON_PROP] = $icon;

					break;

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

					if ( array_key_exists( 'capabilities', $args ) )
						break;

					if ( TRUE === $value ) {

						$captype = $this->constant_plural( $constant );

						$args['capabilities'] = [
							'manage_terms' => sprintf( 'manage_%s', $captype[1] ),
							'edit_terms'   => sprintf( 'edit_%s', $captype[1] ),
							'delete_terms' => sprintf( 'delete_%s', $captype[1] ),
							'assign_terms' => sprintf( 'assign_%s', $captype[1] ),
						];

					} else if ( self::bool( $value ) ) {

						$captype = ( empty( $value ) || is_numeric( $value ) )
							? $this->constant_plural( $constant )
							: $value;

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

					} else if ( 'comment' === $target_object ) {

						// FIXME: WTF?!

					} else if ( 'taxonomy' === $target_object ) {

						// FIXME: must filter meta_cap

					} else if ( 'user' === $target_object ) {

						// FIXME: `edit_users` is not working!
						// maybe map meta cap

						$args['capabilities'] = [
							'manage_terms' => 'edit_users',
							'edit_terms'   => 'list_users',
							'delete_terms' => 'list_users',
							'assign_terms' => 'list_users',
						];

					} else if ( is_array( $targets ) && count( $targets ) && gEditorial()->enabled( 'roled' ) ) {

						if ( in_array( $targets[0], gEditorial()->module( 'roled' )->posttypes() ) ) {

							$captype = gEditorial()->module( 'roled' )->constant( 'base_type' );

							$args['capabilities'] = [
								'manage_terms' => sprintf( 'edit_others_%s', $captype[1] ),
								'edit_terms'   => sprintf( 'edit_others_%s', $captype[1] ),
								'delete_terms' => sprintf( 'edit_others_%s', $captype[1] ),
								'assign_terms' => sprintf( 'edit_%s', $captype[1] ),
							];
						}

					} else if ( $settings['admin_managed'] ) {

						$args['capabilities'] = [
							'manage_terms' => 'manage_options',
							'edit_terms'   => 'manage_options',
							'delete_terms' => 'manage_options',
							'assign_terms' => 'edit_posts',
						];

					} else {

						$args['capabilities'] = [
							'manage_terms' => 'edit_others_posts',
							'edit_terms'   => 'edit_others_posts',
							'delete_terms' => 'edit_others_posts',
							'assign_terms' => 'edit_posts',
						];
					}

					break;

				case 'content_rich':

					// even empty shows on sitemaps
					$args[Services\Sitemaps::VIEWABLE_TAXONOMY_PROP] = TRUE;

					break;

				case 'auto_parents'    : $args[Services\TermHierarchy::AUTO_SET_PARENT_TERMS] = $value; break;
				case 'auto_children'   : $args[Services\TermHierarchy::AUTO_SET_CHILD_TERMS]  = $value; break;
				case 'single_selected' : $args[Services\TermHierarchy::SINGLE_TERM_SELECT]    = $value; break;
				case 'reverse_ordered' : $args[Services\TermHierarchy::REVERSE_ORDERED_TERMS] = $value; break;
				case 'auto_assigned'   : $args[Services\TermHierarchy::AUTO_ASSIGNED_TERMS]   = $value; break;
				case 'terms_related'   : $args[Services\TermRelations::TAXONOMY_PROP]         = $value; break;
				case 'search_titles'   : $args[Services\AdvancedQueries::TAXONOMY_PROP]       = $value; break;
				case 'ical_source'     : $args[Services\Calendars::TAXONOMY_ICAL_SOURCE]      = $value; break;

				// TODO: support combination of settings:
				// -- restricted terms
				// -- `metabox_advanced`
				// -- `selectmultiple_term`

				// TODO: support taxonomy for taxonomies
			}
		}

		$object = register_taxonomy(
			$taxonomy,
			'post' === $target_object ? $targets : '',
			$args
		);

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		// TODO: `after_taxonomy_object_register()`

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
			return Services\CustomTaxonomy::generateLabels(
				gEditorial\Info::getNoop( $this->strings['noops'][$constant] ) ?: $this->strings['noops'][$constant],
				$labels,
				$this->constant( $constant )
			);

		return $labels;
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

			gEditorial\MetaBox::checklistTerms( $post->ID, $args );

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

			gEditorial\MetaBox::checklistTerms( $post->ID, $args, $terms );

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

			gEditorial\MetaBox::checklistTerms( $post->ID, $args );

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
			$args['none']  = gEditorial\Settings::showOptionNone();  // NOTE: the label already displayed on the meta-box title
			$args['empty'] = NULL;                                   // NOTE: displays empty box with link
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

			gEditorial\MetaBox::singleselectTerms( $post->ID, $args );

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
			$args['none']  = gEditorial\Settings::showOptionNone();  // NOTE: the label already displayed on the meta-box title
			$args['empty'] = NULL;                                   // NOTE: displays empty box with link
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

			gEditorial\MetaBox::singleselectTerms( $post->ID, $args );

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

	// NOTE: reversed `$fallback`/`$fallback_key`
	public function get_taxonomy_label( $constant, $label = 'name', $fallback = '', $fallback_key = NULL )
	{
		return Services\CustomTaxonomy::getLabel(
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
	 * Makes available empty terms on sitemaps.
	 *
	 * @param string $constant
	 * @return bool $hooked
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
			empty( $taxonomy->{Services\TermHierarchy::SINGLE_TERM_SELECT} ) ? $taxonomy->labels->name : $taxonomy->labels->singular_name,
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
	 * Sets actions/filters for given taxonomy meta-box.
	 *
	 * @param string $constant
	 * @param string $posttype
	 * @param callable $callback
	 * @param int $priority
	 * @param string $context
	 * @return bool $hooked
	 */
	protected function hook_taxonomy_metabox_mainbox( $constant, $posttype, $callback = NULL, $priority = 80, $context = NULL )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return FALSE;

		if ( empty( $object->{gEditorial\MetaBox::POSTTYPE_MAINBOX_PROP} ) ) {

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

	// NOTE: check for not-admin before calling
	protected function hook_adminbar_node_for_taxonomy( $constant, $parent = NULL )
	{
		if ( ! WordPress\Screen::mustRegisterUI( FALSE ) )
			return FALSE;

		if ( ! $taxonomy = $this->constant( $constant, $constant ) )
			return FALSE;

		if ( ! $edit = WordPress\Taxonomy::edit( $taxonomy ) )
			return FALSE;

		add_action( 'admin_bar_menu',
			function ( $wp_admin_bar ) use ( $taxonomy, $parent, $edit ) {

				if ( ! is_admin_bar_showing() )
					return;

				$wp_admin_bar->add_node( [
					'parent' => $parent ?? 'appearance',
					'id'     => $this->classs( $taxonomy ),
					'title'  => Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label' ),
					'href'   => $edit,
				] );

			}, 32, 1 );

		return TRUE;
	}

	protected function register_headerbutton_for_taxonomy( $constant )
	{
		if ( ! $taxonomy = $this->constant( $constant, $constant ) )
			return FALSE;

		if ( ! $edit = WordPress\Taxonomy::edit( $taxonomy ) )
			return FALSE;

		return Services\HeaderButtons::register( $this->classs( $taxonomy ), [
			'text'     => Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label' ),
			'title'    => Services\CustomTaxonomy::getLabel( $taxonomy, 'manage_description' ),
			'icon'     => Services\Icons::taxonomyMarkup( $taxonomy, NULL, TRUE ),
			'link'     => $edit,
			'priority' => 12,
		] );
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
					'parent'     => 0, // parents only
					'hide_empty' => TRUE,

					'update_term_meta_cache' => FALSE,
				] );

				if ( ! $terms || is_wp_error( $terms ) )
					return $views;

				$query = WordPress\Taxonomy::queryVar( $taxonomy );
				$label = Services\CustomTaxonomy::getLabel( $taxonomy, 'extended_label' );

				foreach ( $terms as $term )
					// TODO: prepend counts from `Recount` module
					$views[sprintf( '%s-%s', $taxonomy->name, $term->slug )] = Core\HTML::tag( 'a', [
						'href'  => WordPress\PostType::edit( $screen->post_type, [ $query => $term->slug ] ),
						'title' => sprintf( '%s: %s', $label, $term->name ),
						'class' => $term->slug === self::req( $query ) ? 'current' : FALSE,
					], $term->name );

				if ( ! WordPress\Taxonomy::countPostsWithoutTerms( $taxonomy->name, $screen->post_type ) )
					return $views;

				$views[sprintf( '%s--none', $taxonomy->name )] =  Core\HTML::tag( 'a', [
					'href'  => WordPress\PostType::edit( $screen->post_type, [ $query => '-1' ] ),
					'title' => $label,
					'class' => '-1' === self::req( $query ) ? 'current' : FALSE,
				], Services\CustomTaxonomy::getLabel( $taxonomy, 'show_option_no_items' ) );

				return $views;
			}, 99, 1 );


		add_action( 'parse_query',
			static function ( &$query ) use ( $taxonomy ) {
				gEditorial\Listtable::parseQueryTaxonomy( $query, $taxonomy->name );
			}, 12, 1 );

		return TRUE;
	}

	/**
	 * Hooks the filter for taxonomy parent terms on imports.
	 * @SEE: `pairedcore__hook_importer_term_parents()`
	 *
	 * @param bool|string $setting
	 * @return bool $hooked
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

<?php namespace geminorum\gEditorial\Modules\People;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class People extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreCapabilities;
	use Internals\CoreDashboard;
	use Internals\CoreMenuPage;
	use Internals\TaxonomyTaxonomy;
	use Internals\TemplateTaxonomy;

	public static function module(): array
	{
		return [
			'name'     => 'people',
			'title'    => _x( 'People', 'Modules: People', 'geditorial-admin' ),
			'desc'     => _x( 'The Way Individuals Involved', 'Modules: People', 'geditorial-admin' ),
			'icon'     => [ 'misc-16', 'people-fill' ],
			'access'   => 'beta',
			'keywords' => [
				'author',
				'person',
				'byline',
				'individual',
				'honorific',
				'literature',
			],
		];
	}

	protected function get_global_settings(): array
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy' ),
			'_editpost'        => [
				'metabox_advanced', // NOTE: by default no meta-box for this taxonomy
				'selectmultiple_term' => [ _x( 'Whether to assign multiple affiliations in edit panel.', 'Setting Description', 'geditorial-people' ), TRUE ],
			],
			'_frontend' => [
				'contents_viewable',
				'custom_archives',
			],
			'_content' => [
				'archive_override',
				'display_searchform',
				'empty_content',
				'archive_title' => [ NULL, $this->get_taxonomy_label( 'main_taxonomy', 'all_items' ) ],
				'archive_content',
				'archive_template',
			],
			'_constants' => [
				'main_taxonomy_constant' => [ NULL, 'people' ],
			],
		];
	}

	protected function get_global_constants(): array
	{
		return [
			'main_taxonomy'         => 'people',
			'main_taxonomy_slug'    => 'person', // NOTE: taxonomy prefix slugs are singular: `/category/`, `/tag/`
			'main_taxonomy_archive' => 'people',
			'category_taxonomy'     => 'people_affiliation',
			'type_taxonomy'         => 'people_honorific',
			'restapi_attribute'     => 'people_meta',
		];
	}

	protected function get_global_strings(): array
	{
		$strings = [
			'noops' => [
				'main_taxonomy'     => _n_noop( 'Person', 'People', 'geditorial-people' ),
				'category_taxonomy' => _n_noop( 'Affiliation', 'Affiliations', 'geditorial-people' ),
				'type_taxonomy'     => _n_noop( 'Honorific', 'Honorifics', 'geditorial-people' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'name_field_description' => _x( 'What is the full-name of the person.', 'Label: `name_field_description`', 'geditorial-people' ),
					'slug_field_description' => _x( 'Define the URL-friendly version of the name of the person.', 'Label: `slug_field_description`', 'geditorial-people' ),
					'desc_field_description' => _x( 'Describe the person in more words.', 'Label: `desc_field_description`', 'geditorial-people' ),
					'manage_description'     => _x( 'Define the individuals affiliated with this site.', 'Label: `manage_description`', 'geditorial-people' ),
				],
				'category_taxonomy' => [
					'extended_label'       => _x( 'People Affiliations', 'Label: `extended_label`', 'geditorial-people' ),
					'column_title'         => _x( 'Affiliations', 'Label: `column_title`', 'geditorial-people' ),
					'show_option_all'      => _x( 'People Affiliations', 'Label: `show_option_all`', 'geditorial-people' ),
					'show_option_no_items' => _x( '(Unaffiliated)', 'Label: `show_option_no_items`', 'geditorial-people' ),
					'assign_description'   => _x( 'Defines the affiliation of the person.', 'Label: `assign_description`', 'geditorial-people' ),
				],
				'type_taxonomy' => [
					'extended_label'       => _x( 'People Honorifics', 'Label: `extended_label`', 'geditorial-people' ),
					'column_title'         => _x( 'Honorifics', 'Label: `column_title`', 'geditorial-people' ),
					'show_option_all'      => _x( 'People Honorifics', 'Label: `show_option_all`', 'geditorial-people' ),
					'show_option_no_items' => _x( '(Undefined)', 'Label: `show_option_no_items`', 'geditorial-people' ),
					'assign_description'   => _x( 'Defines the honorifics of the person.', 'Label: `assign_description`', 'geditorial-people' ),
				],
			],
		];

		return $strings;
	}

	protected function define_default_terms(): array
	{
		return [
			'type_taxonomy' => [
				'clergy'    => _x( 'Clergy', 'Type Taxonomy: Default Term', 'geditorial-people' ),
				'doctor'    => _x( 'Doctor', 'Type Taxonomy: Default Term', 'geditorial-people' ),
				'sadat'     => _x( 'Sadat', 'Type Taxonomy: Default Term', 'geditorial-people' ),       // https://en.wikipedia.org/wiki/Sadat
				'sayyid'    => _x( 'Sayyid', 'Type Taxonomy: Default Term', 'geditorial-people' ),      // https://en.wikipedia.org/wiki/Sayyid
				'sayyidah'  => _x( 'Sayyidah', 'Type Taxonomy: Default Term', 'geditorial-people' ),
				'engineer'  => _x( 'Engineer', 'Type Taxonomy: Default Term', 'geditorial-people' ),
				'lawyer'    => _x( 'Lawyer', 'Type Taxonomy: Default Term', 'geditorial-people' ),
				'professor' => _x( 'Professor', 'Type Taxonomy: Default Term', 'geditorial-people' ),
				'ayatollah' => _x( 'Ayatollah', 'Type Taxonomy: Default Term', 'geditorial-people' ),
			],
		];
	}

	public function init(): void
	{
		parent::init();

		$taxonomy = $this->constant( 'main_taxonomy' );
		$viewable = $this->get_setting( 'contents_viewable', TRUE );

		$this->register_taxonomy( 'main_taxonomy', [
			'show_in_menu' => FALSE,
			'meta_box_cb'  => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
		], NULL, [
			'is_viewable'     => $viewable,
			'search_titles'   => $viewable,
			'terms_related'   => TRUE,
			'custom_captype'  => TRUE,
			'content_rich'    => TRUE,
			'reverse_ordered' => 'id',        // latest first
			'meta_tagline'    => TRUE,
			'suitable_metas'  => [
				'fullname' => NULL,
				'tagline'  => NULL,
				'contact'  => NULL,
				'image'    => NULL,
				'user'     => NULL,
				'born'     => NULL,
				'dead'     => NULL,
			],
		] );

		$this->register_taxonomy( 'category_taxonomy', [
			'hierarchical' => TRUE,
			'public'       => FALSE,
			'rewrite'      => FALSE,
		], 'main_taxonomy', [
			'target_object'   => 'taxonomy',
			'custom_icon'     => 'superhero-alt',
			'single_selected' => ! $this->get_setting( 'selectmultiple_term', TRUE ),
			'admin_managed'   => TRUE,
		] );

		$this->register_taxonomy( 'type_taxonomy', [
			'hierarchical' => TRUE,
			'public'       => FALSE,
			'rewrite'      => FALSE,
		], 'main_taxonomy', [
			'target_object' => 'taxonomy',
			'custom_icon'   => 'superhero',
			'admin_managed' => TRUE,
		] );

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->templatetaxonomy__hook_custom_archives( 'main_taxonomy' );
		$this->taxtax__hook_init( $taxonomy, 'category_taxonomy' );
		$this->taxtax__hook_init( $taxonomy, 'type_taxonomy' );
		$this->filter( 'searchselect_pre_query_terms', 3, 20, FALSE, $this->base );

		if ( is_admin() ) {

			$this->filter( 'prep_individual', 3, 8, 'admin', $this->base );
			$this->filter( 'taxonomy_exclude_empty', 1, 10, FALSE, 'gnetwork' );
			$this->filter( 'taxonomy_term_rewrite_slug', 3, 8, FALSE, 'gnetwork' );
			$this->filter_module( 'terms', 'sanitize_name', 3, 12 );
			$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );

		} else {

			$this->filter( 'get_terms_defaults', 2, 99, 'front' );
			$this->filter( 'single_term_title', 1, 8 );
			$this->filter( 'search_terms_widget_results', 5, 12, FALSE, $this->base );
			$this->templatetaxonomy__hook_adminbar( 'main_taxonomy' );
			$this->hook_adminbar_node_for_taxonomy( 'main_taxonomy' );
		}

		add_filter( $taxonomy.'_name', [ $this, 'people_term_name' ], 8, 3 );
		$this->filter( 'pre_term_slug', 2, 12 );
		$this->filter( 'pre_term_name', 2, 12 );
		$this->filter( 'insert_term_data', 3, 9, FALSE, 'wp' );
	}

	/**
	 * Fires after the current screen has been set.
	 *
	 * @param object $screen
	 * @return void
	 */
	public function current_screen( object $screen ): void
	{
		if ( $this->is_screen_taxonomy( 'main_taxonomy', $screen ) ) {

			$this->_hook_parentfile_for_usersphp();
			$this->modulelinks__register_headerbuttons();
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

			$this->register_headerbutton_for_taxonomy_archives( 'main_taxonomy' );
			$this->register_headerbutton_for_taxonomy( 'category_taxonomy' );
			$this->register_headerbutton_for_taxonomy( 'type_taxonomy' );
			$this->taxtax__hook_screen( $screen, 'category_taxonomy' );
			$this->taxtax__hook_screen( $screen, 'type_taxonomy' );

			$this->action( 'pre_get_terms', 1, 99, 'admin' );

		} else if ( $this->is_screen_taxonomy( 'category_taxonomy', $screen ) ) {

			$this->_hook_parentfile_for_usersphp();
			$this->modulelinks__register_headerbuttons();
			$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );

		} else if ( $this->is_screen_taxonomy( 'type_taxonomy', $screen ) ) {

			$this->_hook_parentfile_for_usersphp();
			$this->modulelinks__register_headerbuttons();
			$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' === $screen->base ) {

			} else if ( 'edit' === $screen->base ) {

				$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );
				$this->register_headerbutton_for_taxonomy_queried( 'main_taxonomy' );
			}
		}
	}

	public function admin_menu(): void
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'users.php' );
		$this->_hook_menu_taxonomy( 'category_taxonomy', 'users.php' );
		$this->_hook_menu_taxonomy( 'type_taxonomy', 'users.php' );
	}

	public function cuc( ?string $context = NULL, string $fallback_capability = '' ): bool
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback_capability );
	}

	public function dashboard_glance_items( array $items ): array
	{
		if ( $glance = $this->dashboard_glance_taxonomy( 'main_taxonomy' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( string $template ): string
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}

	public function pre_get_terms_admin( object &$wp_query ): void
	{
		if ( empty( $wp_query->query_vars['search'] ) )
			return;

		$taxonomy = $this->constant( 'main_taxonomy' );

		if ( ! in_array( $taxonomy, (array) $wp_query->query_vars['taxonomy'], TRUE ) )
			return;

		// Corrects the Arabic comma, here in case no target found for this search.
		if ( Core\Text::has( $wp_query->query_vars['search'], '،' ) )
			$wp_query->query_vars['search'] = str_ireplace( '،', ',', $wp_query->query_vars['search'] );

		// Bail if target is no different than the criteria and let the Service decide.
		if ( FALSE === ( $target = ModuleHelper::getCriteria( $wp_query->query_vars['search'] ) ) )
			return;

		// Avoids the infinite loop!
		remove_action( 'pre_get_terms', [ $this, 'pre_get_terms_admin' ], 99 );

		$side  = new \WP_Term_Query();
		$terms = $side->query( [
			'name__like' => $target,
			'taxonomy'   => $taxonomy,
			'orderby'    => 'none',
			'fields'     => 'ids',

			'hide_empty'             => FALSE,
			'update_term_meta_cache' => FALSE,
			'suppress_filters'       => TRUE,
		] );

		add_action( 'pre_get_terms', [ $this, 'pre_get_terms_admin' ], 99, 1 );

		if ( ! $terms )
			return;

		$wp_query->query_vars['include'] = $terms;
		$wp_query->query_vars['search']  = ''; // Turned out it's very important to clear the search!
	}

	public function get_name_familyfirst( $string, $term = NULL )
	{
		return $this->filters( 'format_name',
			Core\Text::nameFamilyFirst( $string ),
			$string,
			$term
		);
	}

	public function get_name_familylast( $string, $term = NULL )
	{
		return $this->filters( 'display_name',
			Core\Text::nameFamilyLast( $string ),
			$string,
			$term
		);
	}

	/**
	 * Filters a term field value before it is sanitized.
	 * NOTE: The filter is running on `db` context.
	 *
	 * @param mixed $value
	 * @param string $taxonomy
	 * @return mixed
	 */
	public function pre_term_slug( $value, $taxonomy )
	{
		return $taxonomy === $this->constant( 'main_taxonomy' )
			? Core\Text::formatSlug( $this->get_name_familylast( $value ) )
			: $value;
	}

	/**
	 * Filters a term field value before it is sanitized.
	 * NOTE: The filter is running on `db` context.
	 *
	 * @param mixed $value
	 * @param string $taxonomy
	 * @return mixed
	 */
	public function pre_term_name( $value, $taxonomy )
	{
		return $taxonomy === $this->constant( 'main_taxonomy' )
			? $this->get_name_familyfirst( $value )
			: $value;
	}

	public function searchselect_pre_query_terms( $pre, $args, $queried )
	{
		if ( empty( $queried['search'] ) )
			return $pre;

		$results  = NULL;
		$taxonomy = $this->constant( 'main_taxonomy' );

		if ( ! in_array( $taxonomy, $queried['taxonomy'] ) )
			return $pre;

		// Runs default query to get results for the queried `search`.
		if ( is_null( $pre ) ) {
			$query   = new \WP_Term_Query();
			$results = $query->query( $args );
		}

		// Bail if target is no different than the criteria and let the Service decide.
		if ( FALSE === ( $target = ModuleHelper::getCriteria( $queried['search'] ) ) )
			return $results ?: $pre; // Already `WP_Term_Query` used, so not let it waste!

		$side = new \WP_Term_Query();
		$terms = $side->query( array_merge( $args, [
			'name__like' => $target,     // clear the `search`
			'taxonomy'   => $taxonomy,   // force ours only
		] ) );

		// NOTE: `SearchSelect` will handle duplicates
		return array_merge(
			$pre ?? [],
			$results ?? [],
			$terms ?: []
		);
	}

	// @hook: `geditorial_prep_individual`
	public function prep_individual_admin( string $individual, string $raw, mixed $value )
	{
		if ( $link = WordPress\URL::searchAdminTerm( $individual, $this->constant( 'main_taxonomy' ) ) )
			return Core\HTML::link( $individual, $link, TRUE );

		return $individual;
	}

	// @hook: `gnetwork_taxonomy_exclude_empty`
	public function taxonomy_exclude_empty( array $excludes ): array
	{
		return array_merge( $excludes, [
			$this->constant( 'main_taxonomy' ),
			$this->constant( 'category_taxonomy' ),
			$this->constant( 'type_taxonomy' ),
		] );
	}

	// @hook: `gnetwork_taxonomy_term_rewrite_slug`
	public function taxonomy_term_rewrite_slug( string $name, object $term, string $taxonomy ): string
	{
		return $taxonomy === $this->constant( 'main_taxonomy' )
			? Core\Text::formatSlug( $this->get_name_familylast( $term->name ) )
			: $name;
	}

	// @hook: `geditorial_terms_sanitize_name`
	public function terms_sanitize_name( string $name, object $term, string $action ): string
	{
		return $term->taxonomy == $this->constant( 'main_taxonomy' )
			? $this->get_name_familylast( $name, $term )
			: $name;
	}

	public function get_terms_defaults_front( array $defaults, array $taxonomies )
	{
		if ( empty( $taxonomies ) || count( (array) $taxonomies ) > 1 )
			return $defaults;

		if ( $this->constant( 'main_taxonomy' ) !== reset( $taxonomies ) )
			return $defaults;

		$defaults['orderby'] = 'name';
		$defaults['order']   = 'ASC';

		return $defaults;
	}

	// @hook: `geditorial_search_terms_widget_results`
	public function search_terms_widget_results( array $terms, string $criteria, array $taxonomies, array $args, array $instance ): array
	{
		$taxonomy = $this->constant( 'main_taxonomy' );

		if ( ! in_array( $taxonomy, $taxonomies ) )
			return $terms;

		if ( FALSE === ( $target = ModuleHelper::getCriteria( $criteria ) ) )
			return $terms;

		$query = new \WP_Term_Query( [
			// 'search'     => $target,
			'name__like' => $target,
			'taxonomy'   => $taxonomy,
			'exclude'    => Core\Arraay::pluck( $terms, 'term_id' ),
			'orderby'    => 'name',
			'hide_empty' => ! empty( $instance['include_empty'] ),
		] );

		if ( empty( $query->terms ) )
			return $terms;

		return array_merge( $terms, $query->terms );
	}

	public function single_term_title( string $title ): string
	{
		return is_tax( $this->constant( 'main_taxonomy' ) )
			? $this->get_name_familylast( $title )
			: $title;
	}

	public function people_term_name( string $value, int $term_id, string $context ): string
	{
		return 'display' == $context
			? $this->get_name_familylast( $value, $term_id )
			: $value;
	}

	/**
	 * Filters term data before it is inserted into the database.
	 * NOTE: tries to make slug family last if name provided is family first.
	 * @hook: `wp_insert_term_data`
	 *
	 * @param array $data
	 * @param string $taxonomy
	 * @param array $args
	 * @return array
	 */
	public function insert_term_data( array $data, string $taxonomy, array $args ): array
	{
		if ( $this->constant( 'main_taxonomy' ) !== $taxonomy )
			return $data;

		// cleanup
		if ( ! $data['name'] = Core\Text::trim( $data['name'] ) )
			return $data;

		// slug already provided
		if ( ! empty( $args['slug'] ) )
			return $data;

		$slug = $this->get_name_familylast( $data['name'] );
		$slug = Core\Text::formatSlug( $slug );
		$slug = sanitize_title( $slug );

		// Avoids db queries if it's the same
		if ( $data['slug'] !== $slug )
			$data['slug'] = wp_unique_term_slug( $slug, (object) $args );

		return $data;
	}
}

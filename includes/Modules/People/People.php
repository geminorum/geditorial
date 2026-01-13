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

	public static function module()
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

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => $this->corecaps_taxonomy_get_roles_settings( 'main_taxonomy' ),
			'_general'         => [
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

	protected function get_global_constants()
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

	protected function get_global_strings()
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

	public function init()
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
		$this->coreadmin__ajax_taxonomy_multiple_supported_column( 'main_taxonomy' );
		$this->templatetaxonomy__hook_adminbar( 'main_taxonomy' );
		$this->templatetaxonomy__hook_custom_archives( 'main_taxonomy' );
		$this->taxtax__hook_init( $taxonomy, 'category_taxonomy' );
		$this->taxtax__hook_init( $taxonomy, 'type_taxonomy' );

		if ( is_admin() ) {

			$this->filter( 'taxonomy_exclude_empty', 1, 10, FALSE, 'gnetwork' );
			$this->filter( 'taxonomy_term_rewrite_slug', 3, 8, FALSE, 'gnetwork' );
			$this->filter_module( 'terms', 'sanitize_name', 3, 12 );

		} else {

			$this->filter( 'single_term_title', 1, 8 );
			$this->hook_adminbar_node_for_taxonomy( 'main_taxonomy' );
		}

		add_filter( $taxonomy.'_name', [ $this, 'people_term_name' ], 8, 3 );
		$this->filter( 'pre_term_name', 2, 12 );
		$this->filter( 'insert_term_data', 3, 9, FALSE, 'wp' );
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) === $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

			$this->register_headerbutton_for_taxonomy( 'category_taxonomy' );
			$this->register_headerbutton_for_taxonomy( 'type_taxonomy' );
			$this->taxtax__hook_screen( $screen, 'category_taxonomy' );
			$this->taxtax__hook_screen( $screen, 'type_taxonomy' );

		} else if ( $this->constant( 'category_taxonomy' ) === $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();
			$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );

		} else if ( $this->constant( 'type_taxonomy' ) === $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();
			$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );

		} else if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' === $screen->base ) {

			} else if ( 'edit' === $screen->base ) {

				$this->register_headerbutton_for_taxonomy( 'main_taxonomy' );
			}
		}
	}

	public function admin_menu()
	{
		$this->_hook_menu_taxonomy( 'main_taxonomy', 'users.php' );
		$this->_hook_menu_taxonomy( 'category_taxonomy', 'users.php' );
		$this->_hook_menu_taxonomy( 'type_taxonomy', 'users.php' );
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function dashboard_glance_items( $items )
	{
		if ( $glance = $this->dashboard_glance_taxonomy( 'main_taxonomy' ) )
			$items[] = $glance;

		return $items;
	}

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
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

	public function pre_term_name( $field, $taxonomy )
	{
		return $taxonomy == $this->constant( 'main_taxonomy' )
			? $this->get_name_familyfirst( $field )
			: $field;
	}

	// @FILTER: `gnetwork_taxonomy_exclude_empty`
	public function taxonomy_exclude_empty( $excludes )
	{
		return array_merge( $excludes, [
			$this->constant( 'main_taxonomy' ),
			$this->constant( 'category_taxonomy' ),
			$this->constant( 'type_taxonomy' ),
		] );
	}

	// @FILTER: `gnetwork_taxonomy_term_rewrite_slug`
	public function taxonomy_term_rewrite_slug( $name, $term, $taxonomy )
	{
		return $taxonomy == $this->constant( 'main_taxonomy' )
			? $this->get_name_familylast( $name, $term )
			: $name;
	}

	// @FILTER: `geditorial_terms_sanitize_name`
	public function terms_sanitize_name( $name, $term, $action )
	{
		return $term->taxonomy == $this->constant( 'main_taxonomy' )
			? $this->get_name_familylast( $name, $term )
			: $name;
	}

	public function single_term_title( $title )
	{
		return is_tax( $this->constant( 'main_taxonomy' ) )
			? $this->get_name_familylast( $title )
			: $title;
	}

	public function people_term_name( $value, $term_id, $context )
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
	public function insert_term_data( $data, $taxonomy, $args )
	{
		if ( $this->constant( 'main_taxonomy' ) !== $taxonomy )
			return $data;

		// cleanup
		if ( ! $data['name'] = Core\Text::trim( $data['name'] ) )
			return $data;

		// slug already provided
		if ( ! empty( $args['slug'] ) )
			return $data;

		$slug = Core\Text::nameFamilyLast( $data['name'] );
		$slug = Core\Text::formatSlug( $slug );
		$slug = sanitize_title( $slug );

		// Avoids DB queries if the same
		if ( $data['slug'] !== $slug )
			$data['slug'] = wp_unique_term_slug( $slug, (object) $args );

		return $data;
	}
}

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
				'archive_override',
			],
			'_constants' => [
				'main_taxonomy_constant'     => [ NULL, 'people' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_taxonomy'         => 'people',
			'main_taxonomy_archive' => 'people',
			'category_taxonomy'     => 'people_affiliation',
			'restapi_attribute'     => 'people_meta',
		];
	}

	protected function get_global_strings()
	{
		$strings = [
			'noops' => [
				'main_taxonomy'     => _n_noop( 'Person', 'People', 'geditorial-people' ),
				'category_taxonomy' => _n_noop( 'Affiliation', 'Affiliations', 'geditorial-people' ),
			],
			'labels' => [
				'main_taxonomy' => [
					'name_field_description' => _x( 'What is the full-name of the person.', 'Label: `name_field_description`', 'geditorial-people' ),
					'slug_field_description' => _x( 'Define the URL-friendly version of the name of the person.', 'Label: `slug_field_description`', 'geditorial-people' ),
					'desc_field_description' => _x( 'Describe the person in more words.', 'Label: `desc_field_description`', 'geditorial-people' ),
				],
				'category_taxonomy' => [
					'extended_label'       => _x( 'People Affiliations', 'Label: `extended_label`', 'geditorial-people' ),
					'show_option_all'      => _x( 'People Affiliations', 'Label: `show_option_all`', 'geditorial-people' ),
					'show_option_no_items' => _x( '(Unaffiliated)', 'Label: `show_option_no_items`', 'geditorial-people' ),
					'assign_description'   => _x( 'Defines the affiliation of the person.', 'Label: `assign_description`', 'geditorial-people' ),
				],
			],
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$taxonomy = $this->constant( 'main_taxonomy' );

		$this->register_taxonomy( 'main_taxonomy', [
			'show_in_menu' => FALSE,
			'meta_box_cb'  => $this->get_setting( 'metabox_advanced' ) ? NULL : FALSE,
		], NULL, [
			'is_viewable'     => $this->get_setting( 'contents_viewable', TRUE ),
			'custom_icon'     => $this->module->icon,
			'terms_related'   => TRUE,
			'custom_captype'  => TRUE,
			'content_rich'    => TRUE,
			'reverse_ordered' => 'id', // latest first
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

		$this->corecaps__handle_taxonomy_metacaps_roles( 'main_taxonomy' );
		$this->taxtax__hook_init( $taxonomy, 'category_taxonomy' );

		if ( is_admin() ) {

			$this->filter( 'pre_term_name', 2, 12 );
			$this->filter( 'pre_term_slug', 2, 12 );
			$this->filter( 'taxonomy_term_rewrite_slug', 3, 8, FALSE, 'gnetwork' );
			$this->filter_module( 'terms', 'sanitize_name', 3, 12 );

			add_filter( $taxonomy.'_name', [ $this, 'people_term_name' ], 8, 3 );

		} else {

			$this->filter( 'single_term_title', 1, 8 );
			add_filter( $taxonomy.'_name', [ $this, 'people_term_name' ], 8, 3 );

			$this->hook_adminbar_node_for_taxonomy( 'main_taxonomy' );
		}
	}

	public function current_screen( $screen )
	{
		if ( $this->constant( 'main_taxonomy' ) === $screen->taxonomy ) {

			$this->filter_string( 'parent_file', 'users.php' );
			$this->modulelinks__register_headerbuttons();
			$this->coreadmin__hook_taxonomy_multiple_supported_column( $screen );

			$this->register_headerbutton_for_taxonomy( 'category_taxonomy' );
			$this->taxtax__hook_screen( $screen, 'category_taxonomy' );

		} else if ( $this->constant( 'category_taxonomy' ) === $screen->taxonomy ) {

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
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc_by_taxonomy( 'main_taxonomy', $context, $fallback );
	}

	public function template_include( $template )
	{
		return $this->get_setting( 'contents_viewable', TRUE )
			? $this->templatetaxonomy__include( $template, $this->constant( 'main_taxonomy' ) )
			: $template;
	}

	public function get_name_familyfirst( $string, $term = NULL )
	{
		return $this->filters( 'format_name', Core\Text::nameFamilyFirst( $string ), $string, $term );
	}

	public function get_name_familylast( $string, $term = NULL )
	{
		return $this->filters( 'display_name', Core\Text::nameFamilyLast( $string ), $string, $term );
	}

	public function pre_term_name( $field, $taxonomy )
	{
		return $taxonomy == $this->constant( 'people_taxonomy' )
			? $this->get_name_familyfirst( $field )
			: $field;
	}

	public function pre_term_slug( $field, $taxonomy )
	{
		return $taxonomy == $this->constant( 'people_taxonomy' )
			? Core\Text::nameFamilyLast( $field )
			: $field;
	}

	// @FILTER: `gnetwork_taxonomy_term_rewrite_slug`
	public function taxonomy_term_rewrite_slug( $name, $term, $taxonomy )
	{
		return $taxonomy == $this->constant( 'people_taxonomy' )
			? $this->get_name_familylast( $name, $term )
			: $name;
	}

	// @FILTER: `geditorial_terms_sanitize_name`
	public function terms_sanitize_name( $name, $term, $action )
	{
		return $term->taxonomy == $this->constant( 'people_taxonomy' )
			? $this->get_name_familylast( $name, $term )
			: $name;
	}

	public function single_term_title( $title )
	{
		return is_tax( $this->constant( 'people_taxonomy' ) )
			? $this->get_name_familylast( $title )
			: $title;
	}

	// NOTE: non-admin only
	public function people_term_name( $value, $term_id, $context )
	{
		return 'display' == $context
			? $this->get_name_familylast( $value, $term_id )
			: $value;
	}
}

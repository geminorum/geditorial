<?php namespace geminorum\gEditorial\Modules\Grouping;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Grouping extends gEditorial\Module
{
	use Internals\CoreMenuPage;

	public static function module()
	{
		return [
			'name'   => 'grouping',
			'title'  => _x( 'Grouping', 'Modules: Grouping', 'geditorial-admin' ),
			'desc'   => _x( 'Custom Taxonomies for Users', 'Modules: Grouping', 'geditorial-admin' ),
			'icon'   => 'buddicons-tracking',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'  => 'custom_taxonomies',
					'type'   => 'object',
					'title'  => _x( 'Custom Taxonomies', 'Setting Title', 'geditorial-grouping' ),
					'values' => [
						[
							'field'       => 'name',
							'type'        => 'text',
							'title'       => _x( 'Taxonomy Name', 'Setting Title', 'geditorial-grouping' ),
							'description' => _x( '', 'Setting Description', 'geditorial-grouping' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'ortho'       => 'hook',
						],
						[
							'field'       => 'rewrite',
							'type'        => 'text',
							'title'       => _x( 'Taxonomy Slug', 'Setting Title', 'geditorial-grouping' ),
							'description' => _x( '', 'Setting Description', 'geditorial-grouping' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'ortho'       => 'slug',
						],
						[
							'field'       => 'singular',
							'type'        => 'text',
							'title'       => _x( 'Singular Label', 'Setting Title', 'geditorial-grouping' ),
							'description' => _x( '', 'Setting Description', 'geditorial-grouping' ),
						],
						[
							'field'       => 'plural',
							'type'        => 'text',
							'title'       => _x( 'Plural Label', 'Setting Title', 'geditorial-grouping' ),
							'description' => _x( '', 'Setting Description', 'geditorial-grouping' ),
						],
						[
							'field'       => 'menu',
							'type'        => 'text',
							'title'       => _x( 'Menu Label', 'Setting Title', 'geditorial-grouping' ),
							'description' => _x( '', 'Setting Description', 'geditorial-grouping' ),
						],
						[
							'field'       => 'icon',
							'type'        => 'text',
							'title'       => _x( 'Icon', 'Setting Title', 'geditorial-grouping' ),
							'description' => _x( '', 'Setting Description', 'geditorial-grouping' ),
							'field_class' => [ 'regular-text', 'code-text' ],
							'placeholder' => is_array( $this->module->icon ) ? '' : $this->module->icon,
						],
					],
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'custom_tax_rewrite' => 'users/%s',
		];
	}

	public function init()
	{
		parent::init();

		$this->init_custom_taxonomies();
	}

	public function setup_ajax()
	{
		if ( ! $taxonomy = $this->is_inline_save_taxonomy() )
			return;

		if ( ! $customs = $this->get_custom_taxonomies() )
			return;

		if ( array_key_exists( $taxonomy, $customs ) )
			$this->_edit_tags_screen( $taxonomy );
	}

	public function admin_menu()
	{
		foreach ( $this->get_custom_taxonomies() as $custom ) {

			if ( ! $taxonomy = get_taxonomy( $custom['name'] ) )
				continue;

			$menu_name = empty( $custom['menu'] )
				? $taxonomy->labels->menu_name
				: $custom['menu'];

			add_submenu_page(
				'users.php',
				Core\HTML::escape( $taxonomy->labels->name ),
				Core\HTML::escape( $menu_name ),
				$taxonomy->cap->manage_terms,
				'edit-tags.php?taxonomy='.$taxonomy->name
			);
		}
	}

	public function current_screen( $screen )
	{
		if ( ! $customs = $this->get_custom_taxonomies() )
			return;

		if ( 'users' == $screen->base ) {

			$this->action_module( 'tweaks', 'column_user', 3, 12 );
			$this->filter( 'users_list_table_query_args' );

		} else if ( 'profile' == $screen->base || 'user-edit' == $screen->base ) {

			add_action( 'show_user_profile', [ $this, 'edit_user_profile' ], 5 );
			add_action( 'edit_user_profile', [ $this, 'edit_user_profile' ], 5 );
			add_action( 'personal_options_update', [ $this, 'edit_user_profile_update' ] );
			add_action( 'edit_user_profile_update', [ $this, 'edit_user_profile_update' ] );

		} else if ( array_key_exists( $screen->taxonomy, $customs ) ) {

			$this->_hook_parentfile_for_usersphp();
			$this->modulelinks__register_headerbuttons();

			if ( 'edit-tags' == $screen->base )
				$this->_edit_tags_screen( $screen->taxonomy );
		}
	}

	private function _edit_tags_screen( $taxonomy )
	{
		add_filter( 'manage_edit-'.$taxonomy.'_columns',
			function ( $columns ) use ( $taxonomy ) {
				unset( $columns['posts'] );
				return array_merge( $columns, [
					'users' => $this->get_column_title( 'users', $taxonomy, _x( 'Users', 'Column Title', 'geditorial-grouping' ) ),
				] );
			} );

		add_action( 'manage_'.$taxonomy.'_custom_column',
			function ( $display, $column, $term_id ) use ( $taxonomy ) {
				if ( 'users' !== $column )
					return;

				if ( $this->check_hidden_column( $column ) )
					return;

				echo gEditorial\Listtable::columnCount( get_term( $term_id, $taxonomy )->count );
			}, 10, 3 );
	}

	public function tweaks_column_user( $user, $before, $after )
	{
		foreach ( $this->get_custom_taxonomies() as $custom ) {

			$icon = $this->get_column_icon(
				WordPress\Taxonomy::edit( $custom['name'] ),
				$custom['icon'] ?: NULL,
				$custom['menu']
			);

			gEditorial\Helper::renderUserTermsEditRow(
				$user->ID,
				$custom['name'],
				sprintf( $before, '-taxonomy-'.$custom['rewrite'] ).$icon,
				$after
			);
		}
	}

	public function users_list_table_query_args( $args )
	{
		$term_ids   = [];
		$taxonomies = [];

		foreach ( $this->get_custom_taxonomies() as $custom ) {

			if ( ! $query = self::req( $custom['name'] ) )
				continue;

			$term = get_term_by( 'slug', trim( $query ), $custom['name'] );

			if ( ! $term || is_wp_error( $term ) )
				continue;

			$term_ids[]   = $term->term_id;
			$taxonomies[] = $term->taxonomy;
		}

		if ( count( $term_ids ) ) {

			$users = get_objects_in_term( $term_ids, $taxonomies );

			if ( $users && ! is_wp_error( $users ) )
				$args['include'] = $users;
		}

		return $args;
	}

	public function edit_user_profile( $user )
	{
		foreach ( $this->get_custom_taxonomies() as $custom ) {

			if ( ! $taxonomy = get_taxonomy( $custom['name'] ) )
				continue;

			gEditorial\MetaBox::tableRowObjectTaxonomy(
				$taxonomy->name,
				$user->ID,
				$this->classs( $taxonomy->name, 'taxonomy' ),
				NULL,
				'<table class="form-table">',
				'</table>'
			);
		}
	}

	public function edit_user_profile_update( $user_id )
	{
		if ( ! current_user_can( 'edit_user', $user_id ) )
			return;

		foreach ( $this->get_custom_taxonomies() as $custom )
			gEditorial\MetaBox::storeObjectTaxonomy(
				$custom['name'],
				$user_id,
				self::req( $this->classs( $custom['name'], 'taxonomy' ) )
			);
	}

	private function init_custom_taxonomies()
	{
		if ( ! $customs = $this->get_custom_taxonomies() )
			return;

		foreach ( $customs as $name => $custom ) {

			if ( WordPress\Taxonomy::exists( $name ) )
				continue;

			$labels = Services\CustomTaxonomy::generateLabels( [
				'singular' => $custom['singular'],
				'plural'   => $custom['plural'],
			], [
				'menu_name' => $custom['menu'],
			], $name );

			register_taxonomy( $name, 'user', [
				'labels'      => $labels,
				'show_ui'     => TRUE,
				'public'      => TRUE,
				'meta_box_cb' => FALSE,
				'rewrite'     => [
					'slug'       => sprintf( $this->constant( 'custom_tax_rewrite' ), $custom['rewrite'] ),
					'with_front' => FALSE,
				],
			] );
		}
	}

	private function get_custom_taxonomies()
	{
		static $taxonomies = NULL;

		if ( ! is_null( $taxonomies ) )
			return $taxonomies;

		$taxonomies = $this->filters( 'custom_taxonomies', $this->get_setting( 'custom_taxonomies', [] ) );

		if ( $taxonomies )
			$taxonomies = Core\Arraay::reKey( $taxonomies, 'name' );

		return $taxonomies;
	}
}

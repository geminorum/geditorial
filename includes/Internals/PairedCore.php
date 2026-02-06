<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedCore
{
	// EXAMPLE CALLBACK
	// protected function paired_get_paired_constants()
	// {
	// 	return [
	// 		FALSE, // posttype: `primary_posttype`
	// 		FALSE, // taxonomy: `primary_paired`
	// 		FALSE, // subterm:  `primary_subterm`
	// 		FALSE, // exclude:  `primary_taxonomy`
	// 		TRUE,  // hierarchical
	// 		FALSE, // private
	// 		FALSE, // `terms_related`
	// 	];
	// }

	// wraps `paired_get_paired_constants()` with checks
	// NOTE: not checking for `$this->_paired` for maybe before `init`
	public function paired_get_constants()
	{
		if ( ! method_exists( $this, 'paired_get_paired_constants' ) )
			return FALSE;

		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( empty( $constants[2] ) )
			$constants[2] = FALSE;

		if ( empty( $constants[3] ) )
			$constants[3] = FALSE;

		if ( empty( $constants[4] ) )
			$constants[4] = TRUE;

		if ( empty( $constants[5] ) )
			$constants[5] = FALSE;

		if ( empty( $constants[6] ) )
			$constants[6] = FALSE;

		return $constants;
	}

	protected function paired_register( $extra = [], $settings = [], $subterm_settings = [], $supported = NULL )
	{
		if ( ! $paired = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $supported ) )
			$supported = $this->posttypes();

		// NOTE: better to be set per module bases
		// if ( ! array_key_exists( 'ical_source', $settings ) )
		// 	$settings['ical_source'] = 'paired';

		if ( count( $supported ) ) {

			if ( $paired[2] && $this->get_setting( 'subterms_support' ) )
				$this->register_taxonomy( $paired[2], [
					'public'            => ! $paired[5],
					'rewrite'           => $paired[5] ? FALSE : NULL,
					'hierarchical'      => TRUE,
					'meta_box_cb'       => NULL,
					'show_admin_column' => FALSE,
					'show_in_nav_menus' => TRUE,
				], array_merge( $supported, [ $this->constant( $paired[0] ) ] ), $subterm_settings );

			$captype = ( ! empty( $settings['custom_captype'] )
					&& ! self::bool( $settings['custom_captype'] ) )
				? $settings['custom_captype']
				: $this->constant_plural( $paired[0] ); // NOTE: post-type constant

			$taxonomy = [
				Services\Paired::PAIRED_POSTTYPE_PROP => $this->constant( $paired[0] ),

				'public'       => ! $paired[5],
				'rewrite'      => $paired[5] ? FALSE : NULL,
				'show_ui'      => $this->is_debug_mode(),
				'hierarchical' => $paired[4],

				'capabilities' => [
					'manage_terms' => sprintf( 'manage_paired_%s', $captype[1] ),
					'edit_terms'   => sprintf( 'edit_paired_%s', $captype[1] ),
					'delete_terms' => sprintf( 'delete_paired_%s', $captype[1] ),
					'assign_terms' => sprintf( 'assign_paired_%s', $captype[1] ),
				],
			];

			$this->register_taxonomy( $paired[1],
				$taxonomy,
				array_merge( $supported, [ $this->constant( $paired[0] ) ] ),
				array_merge( [ 'terms_related' => $paired[6] ], $settings )
			);

			$this->_paired = $this->constant( $paired[1] );
			$this->paired_handle_paired_metacaps_roles( $paired, $captype );
			$this->filter_unset( 'wp_sitemaps_taxonomies', $this->_paired );
		}

		if ( $this->get_setting( 'paired_manage_restricted' )
			&& ! array_key_exists( 'capabilities', $extra )
			&& empty( $settings['custom_captype'] ) ) {

			/**
			 * NOTE: WTF: cannot use `edit_posts` without `create_posts`
			 * @SEE: https://core.trac.wordpress.org/ticket/22895
			 */

			$create = array_key_exists( 'paired_create', $this->caps ) ? $this->caps['paired_create'] : 'manage_options';
			$delete = array_key_exists( 'paired_delete', $this->caps ) ? $this->caps['paired_delete'] : 'manage_options';

			$extra['capabilities'] = [
				'create_posts'           => $create,
				'delete_posts'           => $delete,
				'delete_private_posts'   => $delete,
				'delete_published_posts' => $delete,
				'delete_others_posts'    => $delete,
			];
		}

		if ( $paired[3] && ! array_key_exists( 'primary_taxonomy', $extra ) )
			$extra['primary_taxonomy'] = $this->constant( $paired[3] );

		$object = $this->register_posttype( $paired[0], array_merge( [
			gEditorial\MetaBox::POSTTYPE_MAINBOX_PROP => TRUE,
			Services\Paired::PAIRED_TAXONOMY_PROP     => $this->_paired,
			Services\Paired::PAIRED_SUPPORTED_PROP    => $supported,

			'public'            => ! $paired[5],
			'hierarchical'      => $paired[4],
			'show_in_nav_menus' => TRUE,
			'show_in_admin_bar' => FALSE,
			'rewrite'           => $paired[5] ? FALSE : [
				'feeds' => (bool) $this->get_setting( 'posttype_feeds', FALSE ),
				'pages' => (bool) $this->get_setting( 'posttype_pages', FALSE ),
			],
		], $extra ), $settings );

		if ( self::isError( $object ) )
			return $object;

		if ( method_exists( $this, 'pairedrest_register_rest_route' ) )
			$this->pairedrest_register_rest_route( $object );

		do_action( $this->hook_base( 'paired_registered' ),
			$this->constant( $paired[0] ), // `posttype`
			$this->constant( $paired[1] ), // `taxonomy`
			$this->constant( $paired[2] ), // `subterm`
			$this->constant( $paired[3] ), // `primary`
			$paired[4], // `hierarchical`
			$paired[5], // private
			$supported
		);

		return $object;
	}

	protected function paired_handle_paired_metacaps_roles( $paired, $captype )
	{
		add_filter( 'map_meta_cap',
			function ( $caps, $cap, $user_id, $args ) use ( $paired, $captype ) {

				switch ( $cap ) {

					case 'manage_paired_'.$captype[1]:

						// fallback to paired post-type cap
						if ( $object = WordPress\PostType::object( $this->constant( $paired[0] ) ) )
							return [ $object->cap->create_posts ];

						break;

					case 'edit_paired_'.$captype[1]:

						// fallback to paired post-type cap
						if ( $object = WordPress\PostType::object( $this->constant( $paired[0] ) ) )
							return [ $object->cap->edit_posts ];

						break;

					case 'delete_paired_'.$captype[1]:

						// fallback to paired post-type cap
						if ( $object = WordPress\PostType::object( $this->constant( $paired[0] ) ) )
							return [ $object->cap->delete_posts ];

						break;

					case 'assign_paired_'.$captype[0]:  // NOTE: DEPRECATED
					case 'assign_paired_'.$captype[1]:

						return $this->role_can( 'paired', $user_id )
							? [ 'exist' ]
							: [ 'do_not_allow' ];

						break;

					case 'assign_term':

						$term = get_term( (int) $args[0] );

						if ( ! $term || is_wp_error( $term ) )
							return $caps;

						if ( $this->constant( $paired[1] ) != $term->taxonomy )
							return $caps;

						if ( ! $roles = get_term_meta( $term->term_id, 'roles', TRUE ) )
							return $caps;

						// NOTE: Applies the customized roles stored as term meta-data via `Terms` module
						if ( ! WordPress\Role::has( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
							return [ 'do_not_allow' ];
				}

				return $caps;
			}, 10, 4 );

		return TRUE;
	}

	// NOTE: DEPRECATED
	protected function paired_register_objects( $posttype, $paired, $subterm = FALSE, $primary = FALSE, $private = FALSE, $extra = [], $supported = NULL )
	{
		self::_dep( '$this->paired_register()' );
		return $this->paired_register( $extra, [], [], $supported );
	}

	public function bulk_exports_post_taxonomies_exclude_paired( $taxonomies, $posttype, $reference, $target, $type, $context, $format )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $taxonomies;

		if ( ! $constants = $this->paired_get_constants() )
			return $taxonomies;

		$taxonomies = Core\Arraay::stripByValue( $taxonomies, $this->constant( $constants[1] ), TRUE );

		if ( $constants[2] )
			$taxonomies = Core\Arraay::stripByValue( $taxonomies, $this->constant( $constants[2] ), TRUE );

		return $taxonomies;
	}

	/**
	 * Appends List of supported posts to source paired post-type.
	 * @example `$this->filter_module( 'papered', 'view_list', 5, 10, 'paired_posttype' );`
	 *
	 * @param array $list
	 * @param object $source
	 * @param object $profile
	 * @param string $context
	 * @param array $data
	 * @return array
	 */
	public function papered_view_list_paired_posttype( $list, $source, $profile, $context, $data )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return $list;

		if ( ! $this->is_posttype( $constants[0], $source ) )
			return $list;

		return $this->paired_all_connected_to( $source, $context );
	}

	/**
	 * Renders pointers about given paired post-type.
	 * @example `$this->action_module( 'pointers', 'post', 6, 201, 'paired_posttype' );`
	 *
	 * @param object $post
	 * @param string $before
	 * @param string $after
	 * @param string $context
	 * @param object $screen
	 * @return void
	 */
	public function pointers_post_paired_posttype( $post, $before, $after, $new_post, $context, $screen )
	{
		if ( $new_post )
			return;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( ! $this->is_posttype( $constants[0], $post ) )
			return;

		if ( ! $this->role_can( 'reports' ) )
			return;

		if ( FALSE === ( $connected = $this->paired_all_connected_to( $post, 'pointers' ) ) )
			return gEditorial\Info::renderSomethingIsWrong( $before, $after );

		$count = count( $connected );

		if ( ! $count )
			return Core\HTML::desc( $before.$this->get_posttype_label( $constants[0], 'paired_no_items' ).$after, FALSE, '-empty' );

		echo $before.$this->get_column_icon().sprintf(
			$this->get_posttype_label( $constants[0], 'paired_has_items' ),
			$this->nooped_count( 'paired_item', $count )
		).$after;

		$average = apply_filters(
			$this->hook_base( 'was_born', 'mean_age' ),
			NULL,
			$post,
			$connected,
			$this->posttypes()
		);

		if ( $average )
			echo $before.$this->get_column_icon( FALSE, 'groups' ).sprintf(
				$this->get_posttype_label( $constants[0], 'paired_mean_age' ),
				Core\Number::format( $average ),
				Core\Number::format( $count ),
			).$after;
	}

	/**
	 * Renders pointers about given supported posttype.
	 * @example `$this->action_module( 'pointers', 'post', 6, 202, 'paired_supported' );`
	 *
	 * @param object $post
	 * @param string $before
	 * @param string $after
	 * @param string $context
	 * @param object $screen
	 * @return void
	 */
	public function pointers_post_paired_supported( $post, $before, $after, $new_post, $context, $screen )
	{
		if ( $new_post )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		if ( ! $this->role_can( 'reports' ) )
			return;

		if ( ! $items = $this->paired_all_connected_from( $post, $context ) )
			return;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		$canedit  = WordPress\PostType::can( $this->constant( $constants[0] ), 'edit_posts' );
		$before   = $before.$this->get_column_icon();
		$template = $this->get_posttype_label( $constants[0], 'paired_connected_to' );

		foreach ( $items as $item )
			echo $before.sprintf( $template, WordPress\Post::fullTitle( $item, $canedit ? 'overview' : TRUE ) ).$after;
	}

	/**
	 * Strips paired terms rendered for already added data into pointers.
	 * @example `$this->filter_module( 'tabloid', 'view_data_for_post', 3, 9, 'paired_supported' );`
	 *
	 * NOTE: DEPRECATED: use `$this->hook_paired_tabloid_exclude_rendered()`
	 *
	 * @param array $data
	 * @param object $post
	 * @param string $context
	 * @return array
	 */
	public function tabloid_view_data_for_post_paired_supported( $data, $post, $context )
	{
		self::_dep( 'hook_paired_tabloid_exclude_rendered()' );

		if ( ! $this->posttype_supported( $post->post_type ) || empty( $data['terms_rendered'] ) )
			return $data;

		if ( ! $constants = $this->paired_get_constants() )
			return $data;

		// NOTE: needs to be non-associative array to render via Mustache
		$data['terms_rendered'] = array_values( Core\Arraay::filter( $data['terms_rendered'], [
			'name' => $this->constant( $constants[1] ),
		], 'NOT' ) );

		return $data;
	}

	protected function hook_paired_tabloid_exclude_rendered()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$this->filter_append(
			$this->hook_base( 'tabloid', 'post_terms_exclude_rendered' ),
			$this->constant( $constants[1] )
		);
	}

	/**
	 * Appends the list of main posts for current supported.
	 * @example: `$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_supported' );`
	 *
	 * @param array $list
	 * @param array $data
	 * @param object $post
	 * @param string $context
	 * @return array
	 */
	public function tabloid_post_summaries_paired_supported( $list, $data, $post, $context )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $list;

		if ( ! $this->role_can( 'reports' ) )
			return $list;

		if ( ! $items = $this->paired_all_connected_from( $post, $context ) )
			return $list;

		/* translators: `%s`: item count */
		$default  = _x( 'Connected (%s)', 'Internal: Paired: Post Summary Title', 'geditorial-admin' );
		$template = $this->get_string( 'tabloid_paired_supported', $post->post_type, 'misc', $default );
		$posts    = [];

		foreach ( $items as $item )
			$posts[] = WordPress\Post::fullTitle( $item, 'overview' );

		$list[] = [
			'key'     => $this->key,
			'class'   => '-paired-summary',
			'title'   => sprintf( $template, WordPress\Strings::getCounted( count( $items ) ) ),
			'content' => Core\HTML::wrap( Core\HTML::rows( $posts ), 'list-columns -post-columns' ),
		];

		return $list;
	}

	/**
	 * Appends List of supported posts to current paired
	 * @example: `$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_posttype' );`
	 *
	 * @param array $list
	 * @param array $data
	 * @param object $post
	 * @param string $context
	 * @return array
	 */
	public function tabloid_post_summaries_paired_posttype( $list, $data, $post, $context )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return $list;

		if ( $post->post_type !== $this->constant( $constants[0] ) )
			return $list;

		if ( ! $this->role_can( 'reports' ) )
			return $list;

		if ( ! $items = $this->paired_all_connected_to( $post, $context ) )
			return $list;

		/* translators: `%s`: item count */
		$default  = _x( 'Connected (%s)', 'Internal: Paired: Post Summary Title', 'geditorial-admin' );
		$template = $this->get_string( 'tabloid_paired_posttype', $constants[0], 'misc', $default );
		$posts    = [];

		foreach ( $items as $item )
			$posts[] = WordPress\Post::fullTitle( $item, 'overview' );

		$list[] = [
			'key'     => $this->key,
			'class'   => '-paired-summary',
			'title'   => sprintf( $template, WordPress\Strings::getCounted( count( $items ) ) ),
			'content' => Core\HTML::wrap( Core\HTML::rows( $posts ), 'list-columns -post-columns' ),
		];

		return $list;
	}

	/**
	 * Appends the bulk export buttons for current post.
	 * @example: `$this->filter_module( 'tabloid', 'post_summaries', 4, 20, 'paired_exports' );`
	 *
	 * @param array $list
	 * @param array $data
	 * @param object $post
	 * @param string $context
	 * @return array
	 */
	public function tabloid_post_summaries_paired_exports( $list, $data, $post, $context )
	{
		if ( ! method_exists( $this, 'exports_get_export_buttons' ) )
			return $list;

		if ( ! $constants = $this->paired_get_constants() )
			return $list;

		if ( $post->post_type !== $this->constant( $constants[0] ) )
			return $list;

		if ( ! $this->role_can( 'reports' ) )
			return $list;

		$default  = _x( 'Export Options', 'Internal: Paired: Post Summary Title', 'geditorial-admin' );
		$template = $this->get_string( 'tabloid_export_buttons', $post->post_type, 'misc', $default );

		$list[] = [
			'key'     => $this->classs( 'paired', 'exports' ),
			'class'   => '-paired-exports',
			'title'   => $template,
			'content' => Core\HTML::wrap( $this->exports_get_export_buttons( $post->ID, $context, 'paired' ), 'field-wrap -buttons' ),
		];

		return $list;
	}

	protected function paired_assign_is_available( $role_check = NULL )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( $role_check && ! $this->role_can( $role_check ) )
			return FALSE;

		if ( ! WordPress\Taxonomy::hasTerms( $this->constant( $constants[1] ) ) )
			return FALSE;

		$type  = $this->constant( $constants[0] );
		$count = WordPress\PostType::countByStatuses( $type );

		if ( ! empty( $count['publish'] ) )
			return TRUE;

		if ( ! WordPress\PostType::can( $type, 'edit_posts' ) )
			return FALSE;

		if ( ! empty( $count['drafts'] ) || ! empty( $count['pending'] ) )
			return TRUE;

		return FALSE;
	}

	public function paired_count_connected_to( $post, $context, $exclude = [], $posttypes = NULL )
	{
		return $this->paired_all_connected_to( $post, $context, 'ids', $exclude, $posttypes, TRUE );
	}

	public function paired_all_connected_to( $post, $context, $fields = NULL, $exclude = [], $posttypes = NULL, $count = FALSE )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return $count ? 0 : FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return $count ? 0 : FALSE;

		if ( $post->post_type !== $this->constant( $constants[0] ) )
			return $count ? 0 : FALSE;

		$paired = $this->constant( $constants[1] );
		$terms  = WordPress\Taxonomy::getPostTerms( $paired, $post );

		if ( empty( $terms ) )
			return $count ? 0 : FALSE;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		$args = apply_filters( $this->hook_base( 'paired', 'all_connected_to', 'args' ), [

			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ], // TODO: custom order
			'order'          => 'ASC',
			'post_type'      => $posttypes,
			'post_status'    => WordPress\Status::acceptable( $posttypes, 'query', is_admin() ? [ 'pending', 'draft' ] : [] ),
			'post__not_in'   => $exclude,
			'fields'         => $count ? 'ids' : ( $fields ?? '' ), // or `ids`/`id=>parent`
			'tax_query'      => [ [
				'taxonomy' => $paired,
				'field'    => 'term_id',
				'terms'    => [ $terms[0]->term_id ],

				// @SEE: https://docs.wpvip.com/code-quality/term-queries-should-consider-include_children-false/
				'include_children' => FALSE,
			] ],

			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,

		], $post, (array) $posttypes, $context );

		$posts = get_posts( $args );

		if ( $count )
			return empty( $posts ) ? 0 : count( $posts );

		return empty( $posts ) ? [] : $posts;
	}

	public function paired_count_connected_from( $post, $context, $exclude = [] )
	{
		return $this->paired_all_connected_from( $post, $context, 'ids', $exclude, TRUE );
	}

	public function paired_all_connected_from( $post, $context, $fields = NULL, $exclude = [], $count = FALSE )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return $count ? 0 : FALSE;

		$type    = $this->constant( $constants[0] );
		$paired  = $this->constant( $constants[1] );
		$forced  = $this->get_setting( 'paired_force_parents', FALSE );
		$reports = $this->get_setting( 'paired_parent_reports', FALSE ); // TODO: maybe add support!
		$dates   = $this->get_setting( 'override_dates', FALSE );
		$parents = WordPress\Taxonomy::getPostTerms( $paired, $post, FALSE, 'parent', 'term_id' );

		if ( empty( $parents ) )
			return $count ? 0 : FALSE;

		if ( $reports ) {

			// keeps the parents only
			$terms = array_diff( array_keys( $parents ), array_keys( array_filter( $parents ) ) );

		} else {

			// strips the parents
			$terms = $forced
				? array_diff( array_keys( $parents ), array_unique( array_values( $parents ) ) )
				: array_keys( $parents );
		}

		$args = apply_filters( $this->hook_base( 'paired', 'all_connected_from', 'args' ), [

			'posts_per_page' => -1,
			'orderby'        => $dates ? 'date' : ( empty( $constants[6] ) ? [ 'menu_order', 'date' ] : 'none' ),
			'order'          => 'ASC',
			'post_type'      => $type,
			'post_status'    => WordPress\Status::acceptable( $type, 'query', is_admin() ? [ 'pending', 'draft' ] : [] ),
			'post__not_in'   => $exclude,
			'fields'         => $count ? 'ids' : ( $fields ?? '' ), // or `ids`/`id=>parent`
			'tax_query'      => [ [
				'taxonomy' => $paired,
				'field'    => 'term_id',
				'terms'    => $terms,

				// @SEE: https://docs.wpvip.com/code-quality/term-queries-should-consider-include_children-false/
				'include_children' => FALSE,
			] ],

			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,

		], $post, (array) $type, $context );

		$query = new \WP_Query();
		$posts = $query->query( $args );

		if ( $count )
			return empty( $posts ) ? 0 : count( $posts );

		if ( $dates )
			return empty( $posts ) ? [] : $posts;

		return $this->paired_sort_posts_by_term_relation( $constants, $posts, $terms, $post, $context, $fields );
	}

	protected function paired_sort_posts_by_term_relation( $constants, $posts, $terms, $post, $context, $fields = NULL )
	{
		if ( empty( $posts ) )
			return [];

		if ( empty( $constants[6] ) || count( $terms ) < 2 )
			return $posts;

		$mapping = [];
		$linked  = sprintf( '%s_linked', $this->constant( $constants[0] ) );
		$metakey = Services\TermRelations::getMetakey( Services\TermRelations::FIELD_ORDER, $post->ID );

		foreach ( $terms as $term_id ) {

			if ( ! $post_id = get_term_meta( $term_id, $linked, TRUE ) )
				continue;

			if ( ! $order = get_term_meta( $term_id, $metakey, TRUE ) )
				continue;

			$mapping[$post_id] = $order;
		}

		if ( empty( $mapping ) )
			return $posts;

		return WordPress\PostType::reorderPostsByMeta( $posts, $mapping, $fields );
	}

	// `$this->filter( 'paired_globalsummary_for_post', 3, 12, FALSE, $this->base );`
	public function paired_globalsummary_for_post( $list, $post, $context )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $list;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $list;

		if ( ! $constants = $this->paired_get_constants() )
			return $list;

		if ( $items = $this->paired_all_connected_from( $post, $context, 'ids' ) )
			$list[$this->constant( $constants[0] )] = $items;

		return $list;
	}

	/**
	 * Hooks the filter for paired parent terms on imports.
	 * @SEE: `hook_taxonomy_importer_term_parents()`
	 *
	 * @param bool|string $setting
	 * @return bool
	 */
	protected function pairedcore__hook_importer_term_parents( $setting = 'paired_force_parents' )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( TRUE !== $setting && ! $this->get_setting( $setting ) )
			return FALSE;

		$taxonomy = $this->constant( $constants[1] );

		add_filter( $this->hook_base( 'importer', 'set_terms', $taxonomy ),
			function ( $terms, $currents, $source_id, $post_id, $oldpost, $override, $append ) use ( $taxonomy ) {

				$parents = [];

				foreach ( (array) $currents as $current )
					$parents = array_merge( $parents, WordPress\Taxonomy::getTermParents( $current, $taxonomy ) );

				foreach ( (array) $terms as $term )
					$parents = array_merge( $parents, WordPress\Taxonomy::getTermParents( $term, $taxonomy ) );

				return Core\Arraay::prepNumeral( $terms, $parents );

			}, 12, 7 );

		return TRUE;
	}

	/**
	 * Hooks the action for sync paired on imports.
	 *
	 * @return bool
	 */
	protected function pairedcore__hook_importer_before_import()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_action( $this->hook_base( 'importer', 'posts_before' ),
			function ( $posttype ) use ( $constants ) {

				if ( $posttype === $this->constant( $constants[0] ) )
					$this->pairedcore__hook_sync_paired( $constants );

			}, 1, 12 );

		return TRUE;
	}

	protected function pairedcore__hook_sync_paired_for_ajax()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( $this->is_inline_save_posttype( $constants[0] ) )
			$this->pairedcore__hook_sync_paired( $constants );
	}

	// OLD: `_hook_paired_sync_primary_posttype()`
	protected function pairedcore__hook_sync_paired( $constants = NULL )
	{
		// if ( ! $this->_paired )
		// 	return;

		if ( is_null( $constants ) ) {

			if ( ! $constants = $this->paired_get_constants() )
				return FALSE;
		}

		$paired_posttype = $this->constant( $constants[0] );
		// $paired_taxonomy = $this->constant( $constants[1] );

		add_action( 'save_post_'.$paired_posttype,
			function ( $post_id, $post, $update ) use ( $constants ) {

				// we handle updates on another action, @SEE: `post_updated` action
				if ( ! $update )
					$this->paired_do_save_to_post_new( $post, $constants[0], $constants[1] );

			}, 20, 3 );

		add_action( 'post_updated',
			function ( $post_id, $post_after, $post_before ) use ( $constants ) {
				$this->paired_do_save_to_post_update( $post_after, $post_before, $constants[0], $constants[1] );
			}, 20, 3 );

		add_action( 'wp_trash_post',
			function ( $post_id ) use ( $constants ) {
				$this->paired_do_trash_to_post( $post_id, $constants[0], $constants[1] );
			} );

		add_action( 'untrash_post',
			function ( $post_id ) use ( $constants ) {
				$this->paired_do_untrash_to_post( $post_id, $constants[0], $constants[1] );
			} );

		add_action( 'before_delete_post',
			function ( $post_id ) use ( $constants ) {
				$this->paired_do_before_delete_to_post( $post_id, $constants[0], $constants[1] );
			} );
	}

	protected function paired_do_save_to_post_new( $post, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_save_post( $post, $posttype_key ) )
			return FALSE;

		$parent = $this->paired_get_to_term( $post->post_parent, $posttype_key, $taxonomy_key );

		$slug = empty( $post->post_name )
			? sanitize_title( $post->post_title )
			: $post->post_name;

		$term_args = [
			'slug'        => $slug, // TODO: maybe like: `{$post->post_type}-{$post->ID}`
			'parent'      => $parent ? $parent->term_id : 0,
			'name'        => $post->post_title,
			'description' => $post->post_excerpt,
		];

		$taxonomy = $this->constant( $taxonomy_key );

		// link to existing term
		if ( $namesake = get_term_by( 'slug', $slug, $taxonomy ) )
			$the_term = wp_update_term( $namesake->term_id, $taxonomy, $term_args );

		else
			$the_term = wp_insert_term( $post->post_title, $taxonomy, $term_args );

		if ( is_wp_error( $the_term ) )
			return FALSE;

		return $this->paired_set_to_term( $post, $the_term['term_id'], $posttype_key, $taxonomy_key );
	}

	protected function paired_do_save_to_post_update( $after, $before, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_save_post( $after, $posttype_key ) )
			return FALSE;

		if ( 'trash' == $after->post_status )
			return FALSE;

		$parent = $this->paired_get_to_term( $after->post_parent, $posttype_key, $taxonomy_key );

		if ( empty( $before->post_name ) )
			$before->post_name = sanitize_title( $before->post_title );

		if ( empty( $after->post_name ) )
			$after->post_name = sanitize_title( $after->post_title );

		$term_args = [
			'name'        => $after->post_title,
			'slug'        => $after->post_name,
			'description' => $after->post_excerpt,
			'parent'      => $parent ? $parent->term_id : 0,
		];

		$taxonomy = $this->constant( $taxonomy_key );

		if ( $paired = $this->paired_get_to_term( $after->ID, $posttype_key, $taxonomy_key ) )
			$the_term = wp_update_term( $paired->term_id, $taxonomy, $term_args );

		else if ( $before_slug = get_term_by( 'slug', $before->post_name, $taxonomy ) )
			$the_term = wp_update_term( $before_slug->term_id, $taxonomy, $term_args );

		else if ( $after_slug = get_term_by( 'slug', $after->post_name, $taxonomy ) )
			$the_term = wp_update_term( $after_slug->term_id, $taxonomy, $term_args );

		else
			$the_term = wp_insert_term( $after->post_title, $taxonomy, $term_args );

		if ( is_wp_error( $the_term ) )
			return FALSE;

		return $this->paired_set_to_term( $after, $the_term['term_id'], $posttype_key, $taxonomy_key );
	}

	protected function paired_do_trash_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) )
			wp_update_term( $the_term->term_id, $this->constant( $taxonomy_key ), [
				'name' => $the_term->name.'___TRASHED',
				'slug' => $the_term->slug.'-trashed',
			] );
	}

	protected function paired_do_untrash_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) )
			wp_update_term( $the_term->term_id, $this->constant( $taxonomy_key ), [
				'name' => str_ireplace( '___TRASHED', '', $the_term->name ),
				'slug' => str_ireplace( '-trashed', '', $the_term->slug ),
			] );
	}

	protected function paired_do_before_delete_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) ) {
			wp_delete_term( $the_term->term_id, $this->constant( $taxonomy_key ) );
			delete_metadata( 'term', $the_term->term_id, $this->constant( $taxonomy_key ).'_linked' );
		}
	}

	/**
	 * Tries to store/remove paired connections.
	 * @OLD: `paired_do_store_connection()`
	 *
	 * @param string $action
	 * @param int|array $post_ids
	 * @param int|array $paired_ids
	 * @param string $posttype_constant
	 * @param string $paired_constant
	 * @param bool $keep_olds
	 * @param bool $forced
	 * @return bool|int|array
	 */
	protected function paired_do_connection( $action, $post_ids, $paired_ids, $posttype_constant, $paired_constant, $keep_olds = FALSE, $forced = NULL )
	{
		if ( ! in_array( $action, [ 'store', 'remove' ], TRUE ) )
			return FALSE;

		$forced = $forced ?? $this->get_setting( 'paired_force_parents', FALSE );
		$terms  = $connections = [];

		foreach ( (array) $paired_ids as $paired_id ) {

			if ( ! $paired_id )
				continue;

			if ( ! $term = $this->paired_get_to_term( $paired_id, $posttype_constant, $paired_constant ) )
				continue;

			$terms[] = $term->term_id;

			if ( $forced )
				$terms = array_merge( WordPress\Taxonomy::getTermParents( $term->term_id, $term->taxonomy ), $terms );
		}

		$supported = $this->posttypes();
		$taxonomy  = $this->constant( $paired_constant );
		$terms     = Core\Arraay::prepNumeral( $terms );

		foreach ( (array) $post_ids as $post_id ) {

			if ( ! $post_id )
				continue;

			if ( ! $post = WordPress\Post::get( $post_id ) ) {
				$connections[$post_id] = FALSE;
				continue;
			}

			if ( ! in_array( $post->post_type, $supported, TRUE ) ) {
				$connections[$post_id] = FALSE;
				continue;
			}

			if ( 'remove' === $action ) {

				if ( ! $keep_olds || ! count( $terms ) )
					$result = wp_set_object_terms( $post->ID, NULL, $taxonomy, FALSE );
				else
					$result = wp_remove_object_terms( $post->ID, $terms, $taxonomy );

			} else {

				$result = wp_set_object_terms( $post->ID, $terms, $taxonomy, $keep_olds );
			}

			$connections[$post->ID] = self::isError( $result ) ? FALSE : $result;
		}

		return is_array( $post_ids ) ? $connections : reset( $connections );
	}

	// OLD: `get_linked_term()`
	public function paired_get_to_term( $post_id, $posttype_constant_key, $tax_constant_key )
	{
		return $this->paired_get_to_term_direct( $post_id,
			$this->constant( $posttype_constant_key ),
			$this->constant( $tax_constant_key )
		);
	}

	// NOTE: here so modules can override
	public function paired_get_to_term_direct( $post_id, $posttype, $taxonomy )
	{
		return Services\Paired::getToTerm( $post_id, $posttype, $taxonomy );
	}

	// OLD: `set_linked_term()`
	public function paired_set_to_term( $post, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		update_post_meta( $post->ID, '_'.$this->constant( $posttype_key ).'_term_id', $term->term_id );
		update_term_meta( $term->term_id, $this->constant( $posttype_key ).'_linked', $post->ID );

		// NO NEED: we use the main post date directly
		// update_term_meta( $term->term_id, $this->constant( 'metakey_term_date', 'datetime' ), $post->post_date );

		wp_set_object_terms( $post->ID, $term->term_id, $term->taxonomy, FALSE );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$image_metakey = $this->constant( 'metakey_term_image', 'image' );

			if ( $thumbnail = get_post_thumbnail_id( $post->ID ) )
				update_term_meta( $term->term_id, $image_metakey, $thumbnail );

			else
				delete_term_meta( $term->term_id, $image_metakey );
		}

		return TRUE;
	}

	// OLD: `remove_linked_term()`
	public function paired_remove_to_term( $post, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		if ( ! $post ) {

			$post_id = $this->paired_get_to_post_id( $term, $posttype_key, $taxonomy_key );

			if ( ! $post = WordPress\Post::get( $post_id ) )
				return FALSE;
		}

		if ( $post ) {

			delete_post_meta( $post->ID, '_'.$this->constant( $posttype_key ).'_term_id' );
			wp_set_object_terms( $post->ID, [], $term->taxonomy, FALSE );

			if ( $this->get_setting( 'thumbnail_support' ) ) {

				$image_metakey = $this->constant( 'metakey_term_image', 'image' );
				$stored        = get_term_meta( $term->term_id, $image_metakey, TRUE );
				$thumbnail     = get_post_thumbnail_id( $post->ID );

				if ( $stored && $thumbnail && $thumbnail == $stored )
					delete_term_meta( $term->term_id, $image_metakey );
			}
		}

		delete_term_meta( $term->term_id, $this->constant( $posttype_key ).'_linked' );
		delete_term_meta( $term->term_id, $this->constant( 'metakey_term_date', 'datetime' ) );

		return TRUE;
	}

	// OLD: `get_linked_post_id()`
	public function paired_get_to_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		if ( ! $term_or_id )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		$post_id = get_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', TRUE );

		if ( ! $post_id && $check_slug )
			$post_id = WordPress\Post::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	// PAIRED API: get (from) posts connected to the pair
	// NOTE: DEPRECATED: use `paired_all_connected_to()`
	public function paired_get_from_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		self::_dep( 'paired_all_connected_to()' );

		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );

		$args = [
			'tax_query' => [ [
				'taxonomy' => $this->constant( $tax_constant_key ),
				'field'    => 'term_id',
				'terms'    => [ $term_id ]
			] ],
			'post_type'   => $this->posttypes(),
			'numberposts' => -1,

			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		if ( $count )
			$args['fields'] = 'ids';

		$items = get_posts( $args );

		if ( $count )
			return count( $items );

		return $items;
	}

	// NOTE: must be public
	// NOTE: returns sorted results
	// NOTE: DEPRECATED: use `paired_all_connected_from()`
	public function paired_do_get_to_posts( $posttype_constant_key, $tax_constant_key, $post = NULL, $single = FALSE, $published = TRUE )
	{
		self::_dep( 'paired_all_connected_from()' );

		$admin   = is_admin();
		$posts   = $dates = $parents = [];
		$terms   = WordPress\Taxonomy::getPostTerms( $this->constant( $tax_constant_key ), $post );
		$forced  = $this->get_setting( 'paired_force_parents', FALSE );
		$metakey = $this->constant( 'metakey_term_date', 'datetime' );

		foreach ( $terms as $term ) {

			if ( $term->parent )
				$parents[] = $term->parent;

			// avoid if has child in the list
			if ( $forced && in_array( $term->term_id, $parents, TRUE ) )
				continue;

			if ( ! $to_post_id = $this->paired_get_to_post_id( $term, $posttype_constant_key, $tax_constant_key ) )
				continue;

			if ( $single )
				return $to_post_id;

			if ( is_null( $published ) )
				$posts[$term->term_id] = $to_post_id;

			else if ( $published && $admin && ! $this->is_post_viewable( $to_post_id ) )
				continue;

			else
				$posts[$term->term_id] = $to_post_id;

			if ( $to_post = WordPress\Post::get( $to_post_id ) )
				$dates[$term->term_id] = $to_post->post_date;
			else
				$dates[$term->term_id] = get_term_meta( $term->term_id, $metakey, TRUE ) ?: '';
		}

		$parets = Core\Arraay::prepNumeral( $parents );

		// final check if had children in the list
		if ( $forced && count( $parents ) ) {
			$posts  = Core\Arraay::stripByKeys( $posts, $parets );
			$dates  = Core\Arraay::stripByKeys( $dates, $parets );
		}

		if ( ! $count = count( $posts ) )
			return FALSE;

		if ( $count === 1 )
			return $single ? reset( $posts ) : $posts;

		array_walk( $posts,
			function ( $post_id, $term_id ) use ( $dates ) {
				return [
					'term_id' => $term_id,
					'post_id' => $post_id,
					'date'    => array_key_exists( $term_id, $dates ) ? $dates[$term_id] : '0',
				];
			} );

		$sorted = Core\Arraay::pluck(
			Core\Arraay::sortByPriority( $posts, 'date', FALSE ),
			'post_id',
			'term_id'
		);

		return $single ? reset( $sorted ) : $sorted;
	}

	// NOTE: currently supports `Personage` module only
	protected function pairedcore__hook_append_identifier_code( $fieldkey, $optionkey = NULL )
	{
		if ( ! $this->get_setting( $optionkey ?? 'append_identifier_code' ) )
			return FALSE;

		$metakey = Services\PostTypeFields::getPostMetaKey( $fieldkey, 'meta' );

		add_filter( $this->hook_base( 'personage', 'make_human_title' ),
			function ( $fullname, $context, $post, $names, $fallback, $checks ) use ( $metakey ) {

				if ( empty( $fullname ) || 'display' !== $context )
					return $fullname;

				if ( ! $this->posttype_supported( $post->post_type ) )
					return $fullname;

				if ( ! $items = $this->paired_all_connected_from( $post, $context, 'ids' ) )
					return $fullname;

				foreach ( $items as $post_id ) {

					if ( ! $code = get_post_meta( (int) $post_id, $metakey, TRUE ) )
						continue;

					// TODO: maybe `prepIdentifier()`
					// TODO: customize the template
					$fullname.= sprintf( ' [%s]', apply_filters( 'string_format_i18n', $code ) );
				}

				return $fullname;
			}, 20, 6 );

		return TRUE;
	}

	protected function hook_paired_tabloid_post_summaries_by_paired()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_filter( $this->hook_base( 'tabloid', 'post_summaries' ),
			function ( $list, $data, $post, $context ) use ( $constants ) {

				if ( ! $this->is_posttype( $constants[0], $post ) )
					return $list;

				if ( ! $this->role_can( 'reports' ) )
					return $list;

				// if ( ! $items = $this->paired_all_connected_from( $post, $context ) )
				// 	return $list;

				$paired = WordPress\Taxonomy::getPostTerms( $this->constant( $constants[1] ), $post );

				if ( empty( $paired ) )
					return $list;

				$summaries = apply_filters(
					$this->hook_base( 'paired', 'post_summaries' ),
					[],
					reset( $paired ),
					$this->constant( $constants[0] ),
					$this->constant( $constants[1] ),
					$this->posttypes(),
					$post,
					$context
					// $items,
				);

				if ( empty( $summaries ) )
					return $list;

				foreach ( $summaries as $offset => $summary ) {

					if ( empty( $summary ) )
						continue;

					if ( is_array( $summary ) )
						$list[] = $summary;

					else
						$list[] = [
							'key'     => $this->classs( 'summary', $offset ),
							'class'   => '-paired-summary',
							'title'   => '',
							'content' => Core\HTML::wrap( $summary ),
						];
				}

				return $list;

			}, 120, 4 );
	}

	protected function hook_paired_static_covers_secondaries()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_filter( $this->hook_base( 'static_covers', 'post_supported_secondary' ),
			function ( $supported, $post ) use ( $constants ) {

				if ( ! is_null( $supported ) )
					return $supported;

				if ( ! $post = WordPress\Post::get( $post ) )
					return $supported;

				if ( $post->post_type === $this->constant( $constants[0] ) )
					return TRUE;

				return $supported;
			}, 12, 2 );

		add_filter( $this->hook_base( 'static_covers', 'post_supported_secondary_posts' ),
			function ( $list, $post ) use ( $constants ) {

				if ( $connected = $this->paired_all_connected_to( $post, 'staticcovers' ) )
					return $connected;

				return $list;
			}, 12, 2 );
	}
}

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Metabox;
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

		return $constants;
	}

	protected function paired_register( $extra = [], $settings = [], $subterm_settings = [], $supported = NULL )
	{
		if ( ! $paired = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $supported ) )
			$supported = $this->posttypes();

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

			$this->register_taxonomy( $paired[1], [
				Services\Paired::PAIRED_POSTTYPE_PROP => $this->constant( $paired[0] ),

				'public'       => ! $paired[5],
				'rewrite'      => $paired[5] ? FALSE : NULL,
				'show_ui'      => FALSE,
				'show_in_rest' => FALSE,
				'hierarchical' => $paired[4],

				// the paired taxonomies are often in plural
				// FIXME: WTF: conflict on the posttype rest base!
				// 'rest_base' => $this->constant( $paired[1].'_slug', str_replace( '_', '-', $this->constant( $paired[1] ) ) ),

			], array_merge( $supported, [ $this->constant( $paired[0] ) ] ), $settings );

			$this->_paired = $this->constant( $paired[1] );
			$this->filter_unset( 'wp_sitemaps_taxonomies', $this->_paired );
		}

		if ( $this->get_setting( 'paired_manage_restricted' )
			&& ! array_key_exists( 'capabilities', $extra ) ) {

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
			Metabox::POSTTYPE_MAINBOX_PROP         => TRUE,
			Services\Paired::PAIRED_TAXONOMY_PROP  => $this->_paired,
			Services\Paired::PAIRED_SUPPORTED_PROP => $supported,

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

	// FIXME: DEPRECATED
	protected function paired_register_objects( $posttype, $paired, $subterm = FALSE, $primary = FALSE, $private = FALSE, $extra = [], $supported = NULL )
	{
		self::_dep( '$this->paired_register()' );
		return $this->paired_register( $extra, [], [], $supported );
	}

	/**
	 * Renders pointers about given paired posttype.
	 * @example `$this->action_module( 'pointers', 'post', 5, 201, 'paired_posttype' );`
	 *
	 * @param  object      $post
	 * @param  string      $before
	 * @param  string      $after
	 * @param  string      $context
	 * @param  string|null $screen
	 * @return void
	 */
	public function pointers_post_paired_posttype( $post, $before, $after, $context, $screen )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( ! $this->is_posttype( $constants[0], $post ) )
			return;

		if ( FALSE === ( $connected = $this->paired_all_connected_to( $post ) ) )
			return Info::renderSomethingIsWrong( $before, $after );

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
	 * @example `$this->action_module( 'pointers', 'post', 5, 202, 'paired_supported' );`
	 *
	 * @param  object      $post
	 * @param  string      $before
	 * @param  string      $after
	 * @param  string      $context
	 * @param  string|null $screen
	 * @return void
	 */
	public function pointers_post_paired_supported( $post, $before, $after, $context, $screen )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( ! $items = $this->paired_do_get_to_posts( $constants[0], $constants[1], $post ) )
			return;

		$canedit  = WordPress\PostType::can( $this->constant( $constants[0] ), 'edit_posts' );
		$before   = $before.$this->get_column_icon();
		$template = $this->get_posttype_label( $constants[0], 'paired_connected_to' );

		foreach ( $items as $term_id => $post_id ) {

			if ( ! $item = WordPress\Post::get( $post_id ) )
				continue;

			echo $before.sprintf( $template, WordPress\Post::fullTitle( $item, $canedit ? 'overview' : TRUE ) ).$after;
		}
	}

	/**
	 * Strips paired terms rendred for already added data into pointers.
	 * @example `$this->filter_module( 'tabloid', 'view_data', 3, 9, 'paired_supported' );`
	 *
	 * @param  array  $data
	 * @param  object $post
	 * @param  string $context
	 * @return array  $data
	 */
	public function tabloid_view_data_paired_supported( $data, $post, $context )
	{
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

	/**
	 * Appends the list of main posts for current supported.
	 * @example: `$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_supported' );`
	 *
	 * @param  array  $list
	 * @param  array  $data
	 * @param  object $post
	 * @param  string $context
	 * @return array  $list
	 */
	public function tabloid_post_summaries_paired_supported( $list, $data, $post, $context )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $list;

		if ( ! $constants = $this->paired_get_constants() )
			return $list;

		if ( ! $items = $this->paired_do_get_to_posts( $constants[0], $constants[1], $post ) )
			return $list;

		/* translators: %s: item count */
		$default  = _x( 'Connected (%s)', 'Internal: Paired: Post Summary Title', 'geditorial-admin' );
		$template = $this->get_string( 'tabloid_paired_supported', $post->post_type, 'misc', $default );
		$posts    = [];

		foreach ( $items as $item )
			$posts[] = WordPress\Post::fullTitle( $item, 'overview' );

		$list[] = [
			'key'     => $this->key,
			'class'   => '-paired-summary',
			'title'   => sprintf( $template, WordPress\Strings::getCounted( count( $items ) ) ),
			'content' => Core\HTML::wrap( Core\HTML::renderList( $posts ), 'field-wrap -list' ),
		];

		return $list;
	}

	/**
	 * Appends List of supported posts to current paired
	 * @example: `$this->filter_module( 'tabloid', 'post_summaries', 4, 90, 'paired_posttype' );`
	 *
	 * @param  array  $list
	 * @param  array  $data
	 * @param  object $post
	 * @param  string $context
	 * @return array  $list
	 */
	public function tabloid_post_summaries_paired_posttype( $list, $data, $post, $context )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return $list;

		if ( $post->post_type !== $this->constant( $constants[0] ) )
			return $list;

		if ( ! $items = $this->paired_all_connected_to( $post ) )
			return $list;

		/* translators: %s: item count */
		$default  = _x( 'Connected (%s)', 'Internal: Paired: Post Summary Title', 'geditorial-admin' );
		$template = $this->get_string( 'tabloid_paired_posttype', $constants[0], 'misc', $default );
		$posts    = [];

		foreach ( $items as $item )
			$posts[] = WordPress\Post::fullTitle( $item, 'overview' );

		$list[] = [
			'key'     => $this->key,
			'class'   => '-paired-summary',
			'title'   => sprintf( $template, WordPress\Strings::getCounted( count( $items ) ) ),
			'content' => Core\HTML::wrap( Core\HTML::renderList( $posts ), 'field-wrap -list' ),
		];

		return $list;
	}

	protected function paired_all_connected_to( $post, $exclude = [], $posttypes = NULL )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$term = $this->paired_get_to_term( $post->ID, $constants[0], $constants[1] );

		if ( ! $term || is_wp_error( $term ) )
			return FALSE;

		$args = [
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order', 'date' ], // TODO: custom order
			'order'          => 'ASC',
			'post_type'      => $posttypes ?? $this->posttypes(),
			'post_status'    => [ 'publish', 'future', 'pending', 'draft' ],
			'post__not_in'   => $exclude,
			'tax_query'      => [ [
				'taxonomy' => $this->constant( $constants[1] ),
				'field'    => 'id',
				'terms'    => [ $term->term_id ],
			] ],
		];

		$posts = get_posts( $args );

		return empty( $posts ) ? [] : $posts;
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

		add_action( 'save_post_'.$paired_posttype, function ( $post_id, $post, $update ) use ( $constants ) {

			// we handle updates on another action, @SEE: `post_updated` action
			if ( ! $update )
				$this->paired_do_save_to_post_new( $post, $constants[0], $constants[1] );

		}, 20, 3 );

		add_action( 'post_updated', function ( $post_id, $post_after, $post_before ) use ( $constants ) {
			$this->paired_do_save_to_post_update( $post_after, $post_before, $constants[0], $constants[1] );
		}, 20, 3 );

		add_action( 'wp_trash_post', function ( $post_id ) use ( $constants ) {
			$this->paired_do_trash_to_post( $post_id, $constants[0], $constants[1] );
		} );

		add_action( 'untrash_post', function ( $post_id ) use ( $constants ) {
			$this->paired_do_untrash_to_post( $post_id, $constants[0], $constants[1] );
		} );

		add_action( 'before_delete_post', function ( $post_id ) use ( $constants ) {
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
			'slug'        => $slug,
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

		return $this->paired_set_to_term( $post->ID, $the_term['term_id'], $posttype_key, $taxonomy_key );
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

		return $this->paired_set_to_term( $after->ID, $the_term['term_id'], $posttype_key, $taxonomy_key );
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
	 * @param  string    $action
	 * @param  int|array $post_ids
	 * @param  int|array $paired_ids
	 * @param  string    $posttype_constant
	 * @param  string    $paired_constant
	 * @param  bool      $keep_olds
	 * @param  null|bool $forced
	 * @return bool|int|array $connections
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
	public function paired_set_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $post_id )
			return FALSE;

		if ( ! $the_term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		update_post_meta( $post_id, '_'.$this->constant( $posttype_key ).'_term_id', $the_term->term_id );
		update_term_meta( $the_term->term_id, $this->constant( $posttype_key ).'_linked', $post_id );

		wp_set_object_terms( (int) $post_id, $the_term->term_id, $the_term->taxonomy, FALSE );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$meta_key = $this->constant( 'metakey_term_image', 'image' );

			if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
				update_term_meta( $the_term->term_id, $meta_key, $thumbnail );

			else
				delete_term_meta( $the_term->term_id, $meta_key );
		}

		return TRUE;
	}

	// OLD: `remove_linked_term()`
	public function paired_remove_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $the_term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		if ( ! $post_id )
			$post_id = $this->paired_get_to_post_id( $the_term, $posttype_key, $taxonomy_key );

		if ( $post_id ) {
			delete_post_meta( $post_id, '_'.$this->constant( $posttype_key ).'_term_id' );
			wp_set_object_terms( (int) $post_id, [], $the_term->taxonomy, FALSE );
		}

		delete_term_meta( $the_term->term_id, $this->constant( $posttype_key ).'_linked' );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$meta_key  = $this->constant( 'metakey_term_image', 'image' );
			$stored    = get_term_meta( $the_term->term_id, $meta_key, TRUE );
			$thumbnail = get_post_thumbnail_id( $post_id );

			if ( $stored && $thumbnail && $thumbnail == $stored )
				delete_term_meta( $the_term->term_id, $meta_key );
		}

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
			$post_id = WordPress\PostType::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	// PAIRED API: get (from) posts connected to the pair
	public function paired_get_from_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );

		$args = [
			'tax_query' => [ [
				'taxonomy' => $this->constant( $tax_constant_key ),
				'field'    => 'id',
				'terms'    => [ $term_id ]
			] ],
			'post_type'   => $this->posttypes(),
			'numberposts' => -1,
		];

		if ( $count ) {
			$args['fields']                 = 'ids';
			$args['no_found_rows']          = TRUE;
			$args['update_post_meta_cache'] = FALSE;
			$args['update_post_term_cache'] = FALSE;
			$args['lazy_load_term_meta']    = FALSE;
		}

		$items = get_posts( $args );

		if ( $count )
			return count( $items );

		return $items;
	}

	// NOTE: must be public
	public function paired_do_get_to_posts( $posttype_constant_key, $tax_constant_key, $post = NULL, $single = FALSE, $published = TRUE )
	{
		$admin  = is_admin();
		$posts  = $parents = [];
		$terms  = WordPress\Taxonomy::getPostTerms( $this->constant( $tax_constant_key ), $post );
		$forced = $this->get_setting( 'paired_force_parents', FALSE );

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
		}

		// final check if had children in the list
		if ( $forced && count( $parents ) )
			$posts = Core\Arraay::stripByKeys( $posts, Core\Arraay::prepNumeral( $parents ) );

		if ( ! count( $posts ) )
			return FALSE;

		return $single ? reset( $posts ) : $posts;
	}
}

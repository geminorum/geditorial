<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

trait SubContents
{

	protected function subcontent_get_strings_for_js( $extra = [] )
	{
		return array_merge( [
			'index'    => _x( '#', 'Internal: Subcontents: Javascript String: `index`', 'geditorial-admin' ),
			'plus'     => _x( '+', 'Internal: Subcontents: Javascript String: `plus`', 'geditorial-admin' ),
			'actions'  => _x( 'Actions', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'info'     => _x( 'Information', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'insert'   => _x( 'Insert', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'sort'     => _x( 'Sort', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'edit'     => _x( 'Edit', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'moveup'   => _x( 'Move Up', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'movedown' => _x( 'Move Down', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'remove'   => _x( 'Remove', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'loading'  => _x( 'Loading', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'message'  => _x( 'Here you can add, edit and manage the information.', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'edited'   => _x( 'The entry edited successfully.', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'saved'    => _x( 'New entry saved successfully.', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'sorted'   => _x( 'The Sorting saved successfully.', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			'invalid'  => _x( 'The entry data are not valid!', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),

			/* translators: %s: count number */
			'countitems'    => _x( '%s items', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),

		], $this->get_strings( 'subcontent', 'js' ), $extra );
	}

	protected function subcontent_get_comment_type()
	{
		return $this->constant( 'subcontent_type', $this->key );
	}

	protected function subcontent_get_comment_status()
	{
		return $this->constant( 'subcontent_status', 'private' );
	}

	// NOTE: on strings api: `$strings['fields']['subcontent']`
	protected function subcontent_define_fields()
	{
		return $this->get_strings( 'subcontent', 'fields' );
	}

	protected function subcontent_get_fields( $context = 'display', $settings_key = 'subcontent_fields' )
	{
		$all      = $this->subcontent_define_fields();
		// $hidden   = $this->subcontent_get_hidden_fields( $context );
		$required = $this->subcontent_get_required_fields( $context );
		$enabled  = $this->get_setting( $settings_key, array_keys( $all ) );
		$fields   = [];

		foreach ( $all as $field => $label )
			if ( in_array( $field, $required, TRUE ) || in_array( $field, $enabled, TRUE ) )
				$fields[$field] = $label;

		return $this->filters( 'fields', $fields, $enabled, $required, $context );
	}

	protected function subcontent_define_required_fields()
	{
		return [
			// 'name' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_required_fields( $context = 'display' )
	{
		return $this->filters( 'required_fields',
			$this->subcontent_define_required_fields(), $context );
	}

	protected function subcontent_define_hidden_fields()
	{
		return [
			// 'name' // <---- EXAMPLE
			'order',
		];
	}

	protected function subcontent_get_hidden_fields( $context = 'display' )
	{
		return $this->filters( 'hidden_fields',
			$this->subcontent_define_hidden_fields(), $context );
	}

	protected function subcontent_define_unique_fields()
	{
		return [
			// 'identity' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_unique_fields( $context = 'display' )
	{
		return $this->filters( 'unique_fields',
			$this->subcontent_define_unique_fields(), $context );
	}

	protected function subcontent_get_meta_mapping()
	{
		return [
			// 'name' => '_metakey', // <---- EXAMPLE
		];
	}

	protected function subcontent_base_data_mapping()
	{
		return [
			'comment_ID'           => '_id',
			'comment_parent'       => '_parent',
			'comment_post_ID'      => '_object',
			'comment_content'      => 'data',        // `text`
			'comment_author'       => 'title',       // `tinytext`
			'comment_author_url'   => 'action',      // `varchar(200)`
			'comment_author_email' => 'slug',        // `varchar(100)`
			'comment_author_IP'    => 'ip',          // `varchar(100)`
			'comment_agent'        => 'agent',       // `varchar(255)`
			'comment_karma'        => 'karma',       // `int(11)`
			'comment_type'         => '_type',       // `varchar(20)`
			'comment_approved'     => '_status',     // `varchar(20)`
			'comment_meta'         => '_meta',
			'comment_date'         => '_date',
			'comment_date_gmt'     => '_date_gmt',
			'user_id'              => '_user',
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return $this->subcontent_base_data_mapping();
	}

	protected function subcontent_update_sort( $raw = [], $post = FALSE, $mapping = NULL )
	{
		foreach ( $raw as $offset => $comment_id )
			update_comment_meta( $comment_id, 'order', $offset + 1 );

		return count( $raw );
	}

	protected function subcontent_insert_data( $raw = [], $post = FALSE, $mapping = NULL )
	{
		$data = $this->subcontent_sanitize_data( $raw, $post, $mapping );
		$data = $this->subcontent_prep_data_for_save( $data, $post, $mapping );

		if ( $post && ( $post = WordPress\Post::get( $post ) ) )
			$data['comment_post_ID'] = $post->ID;

		if ( ! array_key_exists( 'comment_ID', $data ) )
			$data['comment_ID'] = '0';

		if ( ! array_key_exists( 'user_id', $data ) )
			$data['user_id'] = get_current_user_id();

		if ( ! array_key_exists( 'comment_type', $data ) && ( $type = $this->subcontent_get_comment_type() ) )
			$data['comment_type'] = $type;

		if ( ! array_key_exists( 'comment_approved', $data ) && ( $status = $this->subcontent_get_comment_status() ) )
			$data['comment_approved'] = $status;

		if ( FALSE === ( $filtered = $this->filters( 'subcontent_insert_data', $data, $post, $mapping, $raw ) ) )
			return $this->log( 'NOTICE', 'SUBCONTENT INSERT DATA SKIPPED BY FILTER ON POST-ID:'.( $post ? $post->ID : 'UNKNOWN' ) );

		$this->subcontent_insert_data_before( $filtered, $filtered['comment_ID'] );

		$result = $filtered['comment_ID']
			? wp_update_comment( $filtered )
			: wp_insert_comment( $filtered );

		$this->subcontent_insert_data_after( $filtered, $filtered['comment_ID'] );

		return $result;
	}

	// NOTE: overrides the modifications by core
	public function subcontent_wp_update_comment_data( $data, $comment, $commentarr )
	{
		$key = $data['comment_ID'] ?: 'new';

		if ( array_key_exists( $key, $this->cache['subcontent_data'] ) )
			return $this->cache['subcontent_data'][$key];

		// fallback to manual mode!
		// NOTE: core tries to validate `comment_author_email`
		$data['comment_author_url'] = Core\Text::stripPrefix( $data['comment_author_url'], 'http://' );
		return $data;
	}

	protected function subcontent_insert_data_before( $data = [], $comment_id = FALSE )
	{
		$this->cache['subcontent_data'][( $comment_id ?: 'new' )] = $data;

		remove_filter( 'comment_save_pre', 'convert_invalid_entities' );
		remove_filter( 'comment_save_pre', 'balanceTags', 50 );

		add_filter( 'wp_update_comment_data', [ $this, 'subcontent_wp_update_comment_data' ], 99, 3 );
	}

	protected function subcontent_insert_data_after( $data = [], $comment_id = FALSE )
	{
		add_filter( 'comment_save_pre', 'convert_invalid_entities' );
		add_filter( 'comment_save_pre', 'balanceTags', 50 );

		remove_filter( 'wp_update_comment_data', [ $this, 'subcontent_wp_update_comment_data' ], 99, 3 );

		unset( $this->cache['subcontent_data'][( $comment_id ?: 'new' )] );
	}

	protected function subcontent_delete_data( $id, $post = FALSE )
	{
		return wp_delete_comment( intval( $id ), TRUE );
	}

	protected function subcontent_prep_data_for_save( $raw, $post = FALSE, $mapping = NULL, $metas = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping();

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping();

		if ( is_object( $raw ) )
			$raw = Core\Arraay::fromObject( $raw );

		$raw  = $this->filters( 'subcontent_pre_prep_data', $raw, $post, $mapping, $metas );
		$data = [ 'comment_meta' => array_key_exists( '_meta', $raw ) ? $raw['_meta'] : [] ];
		unset( $raw['_meta'] );

		foreach ( $mapping as $map_from => $map_to )
			if ( array_key_exists( $map_to, $raw ) )
				$data[$map_from] = $raw[$map_to];

		foreach ( $metas as $meta_from => $meta_to )
			if ( array_key_exists( $meta_from, $raw ) )
				$data['comment_meta'][$meta_to] = $raw[$meta_from];

		if ( ! empty( $data['comment_meta']['order'] ) )
			return $data;

		$data['comment_meta']['order'] = empty( $raw['order'] )
			? $this->subcontent_get_data_count( $post ) + 1
			: $raw['order'];

		return $data;
	}

	protected function subcontent_prep_data_from_query( $raw, $post = FALSE, $mapping = NULL, $metas = NULL, $order = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping();

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping();

		if ( is_object( $raw ) )
			$raw = Core\Arraay::fromObject( $raw );

		$data = [];

		foreach ( $mapping as $map_from => $map_to )
			if ( array_key_exists( $map_from, $raw ) )
				$data[$map_to] = $raw[$map_from];

		$meta = get_comment_meta( $data['_id'] );

		foreach ( $metas as $meta_name => $meta_key )
			if ( array_key_exists( $meta_key, $meta ) )
				$data[$meta_name] = empty( $meta[$meta_key][0] ) ? '' : $meta[$meta_key][0];
			else
				$data[$meta_name] = '';

		if ( ! empty( $data['order'] ) )
			return $data;

		if ( ! empty( $meta['order'] ) )
			$data['order'] = $meta['order'][0];

		else if ( $order )
			$data['order'] = $order;

		else
			$data['order'] = '1';

		return $data;
	}

	// @SEE: `is_protected_meta()`
	protected function subcontent_sanitize_data( $raw = [], $post = FALSE, $mapping = NULL, $metas = NULL, $skipped = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping();

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping();

		if ( is_null( $skipped ) )
			$skipped = [ 'data' ];

		$data = [];

		foreach ( $raw as $raw_key => $raw_value )
			if ( in_array( $raw_key, $skipped, TRUE ) || Core\Text::starts( $raw_key, '_' ) )
				$data[$raw_key] = $raw_value;
			else
				$data[$raw_key] = trim( Helper::kses( $raw_value ) );

		return $this->filters( 'subcontent_sanitize_data', $data, $post, $raw, $mapping, $metas, $skipped );
	}

	protected function subcontent_get_data_mapped( $items, $post = FALSE, $mapping = NULL, $metas = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping();

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping();

		$data = [];

		foreach ( $items as $offset => $item )
			$data[] = $this->subcontent_prep_data_from_query( $item, $post, $mapping, $metas, $offset + 1 );

		return $this->filters( 'subcontent_data_mapped', $data, $post, $items, $mapping, $metas );
	}

	protected function subcontent_get_data( $parent = NULL, $extra = [] )
	{
		if ( ! $post = WordPress\Post::get( $parent ) )
			return FALSE;

		$args = array_merge( [
			'post_id'   => $post->ID,
			'post_type' => 'any', // $parent->post_type,
			'status'    => 'any', // $this->subcontent_get_comment_status(),
			'type'      => $this->subcontent_get_comment_type(),
			'fields'    => '', // 'ids', // empty for all
			'number'    => '', // empty for all
			'order'     => 'ASC',

			'orderby'    => 'order_clause',
			'meta_query' => [
				// @REF: https://core.trac.wordpress.org/ticket/34996
				// @SEE: https://wordpress.stackexchange.com/a/246206
				// @SEE: https://wordpress.stackexchange.com/a/277755
				'relation' => 'OR',
				'order_clause' => [
					'key'  => 'order',
					'type' => 'NUMERIC'
				],
				[
					'key'     => 'order',
					'compare' => 'NOT EXISTS'
				],
			],

			'update_comment_meta_cache' => TRUE,
			'update_comment_post_cache' => FALSE,
		], $extra );

		$query = new \WP_Comment_Query;
		$items = $query->query( $args );

		return $this->subcontent_get_data_mapped( $items, $post );
	}

	protected function subcontent_get_data_count( $parent = NULL, $extra = [] )
	{
		if ( ! $post = WordPress\Post::get( $parent ) )
			return FALSE;

		$args = array_merge( [
			'post_id'   => $post->ID,
			'post_type' => 'any',
			'status'    => 'any',
			'type'      => $this->subcontent_get_comment_type(),
			'fields'    => '', // 'ids', // empty for all
			'number'    => '', // empty for all
			'orderby'   => 'none',
			'count'     => TRUE,

			'update_comment_meta_cache' => FALSE,
			'update_comment_post_cache' => FALSE,
		], $extra );

		$query = new \WP_Comment_Query;
		return $query->query( $args );
	}

	protected function subcontent_defaine_field_types()
	{
		$types = Core\Arraay::sameKey( array_keys( $this->subcontent_define_fields() ) );

		if ( array_key_exists( 'card', $types ) )
			$types['card'] = 'bankcard';

		if ( array_key_exists( 'desc', $types ) )
			$types['desc'] = 'html';

		return $types;
	}

	protected function subcontent_get_field_types( $context = 'display' )
	{
		return $this->filters( 'field_types', $this->subcontent_defaine_field_types(), $context );
	}

	protected function subcontent_get_prepped_data( $data, $context = 'display' )
	{
		$list  = [];
		$types = $this->subcontent_get_field_types( $context );

		foreach ( $data as $offset => $row ) {

			$prepped = [];

			foreach ( $row as $key => $value ) {

				if ( empty( $value ) || ! trim( $value ) ) {

					$prepped[$key] = Helper::htmlEmpty();

				} else {

					$prepped[$key] = $this->prep_meta_row( $value, $key, [
						'type' => array_key_exists( $key, $types ) ? $types[$key] : $key,
					], $value );
				}
			}

			$list[$offset] = $prepped;
		}

		return $this->filters( 'prepped_data', $list, $context, $data );
	}

	protected function subcontent_get_empty_notice( $context = 'display', $string_key = 'empty' )
	{
		if ( $this->is_thrift_mode() )
			return '<div class="-placeholder-empty"></div>';

		$default = _x( 'There is no information available!', 'Internal: SubContents: Empty Notice', 'geditorial' );

		return Core\HTML::tag( 'p', [
			'class' => [ 'description', '-description', '-empty' ],
		], $this->get_string( $string_key, $context, 'notices', $default ) );
	}

	protected function subcontent_get_noaccess_notice( $context = 'display', $string_key = 'noaccess' )
	{
		$default = _x( 'You have not necessary permission to manage the information.', 'Internal: SubContents: No-Access Notice', 'geditorial' );

		return Core\HTML::tag( 'p', [
			'class' => [ 'description', '-description', '-noaccess' ],
		], $this->get_string( $string_key, $context, 'notices', $default ) );
	}

	// TODO: `total`: count with html markup
	protected function subcontent_restapi_register_routes()
	{
		$namespace = $this->restapi_get_namespace();
		$arguments = [
			'linked' => Services\RestAPI::defineArgument_postid( _x( 'The id of the parent post.', 'Internal: SubContent: Rest Argument', 'geditorial-admin' ) ),
		];

		$read = function ( $request ) {

			if ( ! current_user_can( 'read_post', (int) $request['linked'] ) )
				return Services\RestAPI::getErrorForbidden();

			if ( ! $this->role_can( 'reports' ) )
				return Services\RestAPI::getErrorForbidden();

			return TRUE;
		};

		$edit = function ( $request ) {

			if ( ! current_user_can( 'edit_post', (int) $request['linked'] ) )
				return Services\RestAPI::getErrorForbidden();

			if ( ! $this->role_can( 'assign' ) )
				return Services\RestAPI::getErrorForbidden();

			return TRUE;
		};

		register_rest_route( $namespace, '/query/(?P<linked>[\d]+)', [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					$post = get_post( (int) $request['linked'] );

					if ( FALSE === $this->subcontent_insert_data( $request->get_json_params(), $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data( $post ) );
				},
				'permission_callback' => $edit,
			],
			[
				'methods'  => \WP_REST_Server::DELETABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					$post = get_post( (int) $request['linked'] );

					if ( empty( $request['_id'] ) )
						return Services\RestAPI::getErrorArgNotEmpty( '_id' );

					if ( ! $this->subcontent_delete_data( $request['_id'], $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data( $post ) );
				},

				'permission_callback' => $edit,
			],
			[
				'methods'  => \WP_REST_Server::READABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					return rest_ensure_response( $this->subcontent_get_data( (int) $request['linked'] ) );
				},
				'permission_callback' => $read,
			],
		] );

		register_rest_route( $namespace, '/markup/(?P<linked>[\d]+)', [
			[
				'methods'  => \WP_REST_Server::READABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					return rest_ensure_response(
						$this->subcontent_do_provide_markup(
							WordPress\Post::get( (int) $request['linked'] ),
							'restapi'
						)
					);
				},
				'permission_callback' => $read,
			],
		] );

		register_rest_route( $namespace, '/sort/(?P<linked>[\d]+)', [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					$post = get_post( (int) $request['linked'] );

					if ( FALSE === $this->subcontent_update_sort( $request->get_json_params(), $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data( $post ) );
				},
				'permission_callback' => $edit,
			],
		] );

		register_rest_route( $namespace, '/summary/(?P<subcontent>[\d]+)', [
			[
				'methods' => \WP_REST_Server::READABLE,
				'args'    => [
					'subcontent' => Services\RestAPI::defineArgument_commentid( _x( 'The id of the subcontent comment.', 'Internal: SubContent: Rest Argument', 'geditorial-admin' ) ),
				],
				'callback' => function ( $request ) {

					if ( FALSE === ( $data = $this->subcontent_do_provide_summary( WordPress\Comment::get( (int) $request['subcontent'] ), 'restapi' ) ) )
						return Services\RestAPI::getErrorSomethingIsWrong();

					return rest_ensure_response( $data );
				},
				'permission_callback' => function ( $request ) {

					if ( ! $subcontent = WordPress\Comment::get( (int) $request['subcontent'] ) )
						return Services\RestAPI::getErrorInvalidData();

					if ( empty( $subcontent->comment_post_ID ) )
						return Services\RestAPI::getErrorSomethingIsWrong();

					if ( ! current_user_can( 'read_post', (int) $subcontent->comment_post_ID ) )
						return Services\RestAPI::getErrorForbidden();

					if ( ! $this->role_can( 'reports' ) )
						return Services\RestAPI::getErrorForbidden();

					return TRUE;
				},
			],
		] );
	}

	protected function subcontent_do_provide_summary( $comment, $context = NULL )
	{
		$na     = gEditorial\Plugin::na( FALSE );
		$parent = WordPress\Post::get( (int) $comment->comment_post_ID ) ;
		$item   = $this->subcontent_prep_data_from_query( $comment, $parent );
		$data   = apply_filters( $this->hook_base( 'subcontent', 'provide_summary' ), NULL, $item, $parent, $context );
		$author = FALSE;

		// TODO: override by `Users` module for better profiles
		if ( ! empty( $item['_user'] ) && ( $user = get_user_by( 'id', $item['_user'] ) ) )
			$author = sprintf(
				/* translators: %s: user display-name (user email) */
				_x( 'By %s', 'Internal: Subcontents: User Row', 'geditorial-admin' ),
				WordPress\User::getTitleRow( $user )
			);

		// NOTE: like `WordPress\Post::summary()`
		$summary = array_merge( [
			'title'       => $na,
			'link'        => FALSE,
			'image'       => FALSE,
			'author'      => $author,
			'description' => '',
		], $data ?? [] );

		return $this->filters( 'provide_summary', $summary, $parent, $item, $context );
	}

	protected function subcontent_do_provide_markup( $parent, $context = NULL )
	{
		$markup = [];

		$markup['html'] = $this->subcontent_do_main_shortcode( [
			'id'      => $parent,
			'context' => 'restapi',
			'wrap'    => FALSE,
		], $this->subcontent_get_empty_notice( $context ) );

		return $this->filters( 'provide_markup', $markup, $parent, $context );
	}

	protected function subcontent_do_main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		// NOTE: fallsback into namespace
		$constant = $this->constant( 'main_shortcode', $this->constant( 'restapi_namespace' ) );

		$args = shortcode_atts( [
			'id'      => get_queried_object_id(),
			'fields'  => NULL,
			'context' => NULL,
			'wrap'    => TRUE,
			'class'   => '',
			'before'  => '',
			'after'   => '',
		], $atts, $tag ?: $constant );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		if ( ! $data = $this->subcontent_get_data( $post ) )
			return $content;

		if ( is_null( $args['fields'] ) )
			$args['fields'] = $this->subcontent_get_fields( $args['context'] );

		$data = $this->subcontent_get_prepped_data( $data, $args['context'] );
		$html = Core\HTML::tableSimple( $data, $args['fields'], FALSE );

		return ShortCode::wrap( $html, $constant, $args );
	}

	/**
	 * Appends the table summary of subcontents for current supported.
	 * @example `$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );`
	 *
	 * @param  array  $list
	 * @param  array  $data
	 * @param  object $post
	 * @param  string $context
	 * @return array  $list
	 */
	public function tabloid_post_summaries_subcontent( $list, $data, $post, $context )
	{
		if ( $this->in_setting( $post->post_type, 'subcontent_posttypes' ) && $this->role_can( 'reports' ) )
			$list[] = [
				'key'     => $this->key,
				'class'   => '-table-summary',
				'title'   => $this->get_string( 'supportedbox_title', $post->post_type, 'metabox', '' ),
				'content' => $this->main_shortcode( [
					'id'      => $post,
					'context' => $context,
					'wrap'    => FALSE,
				] ),
			];

		return $list;
	}

	protected function subcontent_do_enqueue_app( $name )
	{
		if ( ! $this->role_can( 'assign' ) )
			return;

		$this->enqueue_asset_js( [
			'strings' => $this->subcontent_get_strings_for_js(),
			'fields'  => $this->subcontent_get_fields( 'edit' ),
			'config'  => [
				'linked'    => self::req( 'linked', FALSE ),
				'required'  => $this->subcontent_get_required_fields( 'edit' ),
				'hidden'    => $this->subcontent_get_hidden_fields( 'edit' ),
				'unique'    => $this->subcontent_get_unique_fields( 'edit' ),
				'posttypes' => $this->get_setting( 'subcontent_posttypes', [] ),
			],
		], FALSE );

		Scripts::enqueueApp( $name );
	}

	protected function subcontent_do_enqueue_asset_js( $screen )
	{
		if ( ! $this->role_can( 'assign' ) )
			return;

		$this->enqueue_asset_js( [], $screen, [
			'jquery',
			'wp-api-request',
			Scripts::enqueueColorBox(),
		] );
	}

	protected function subcontent_render_metabox_data_grid( $post, $context = NULL )
	{
		echo $this->wrap( $this->subcontent_do_main_shortcode( [
			'id'      => $post,
			'context' => $context,
			'wrap'    => FALSE,
		], $this->subcontent_get_empty_notice( $context ) ), '', TRUE, $this->classs( 'data-grid' ) );
	}
}

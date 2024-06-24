<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait SubContents
{
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
		];
	}

	protected function subcontent_get_hidden_fields( $context = 'display' )
	{
		return $this->filters( 'required_fields',
			$this->subcontent_define_hidden_fields(), $context );
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

		return $data;
	}

	protected function subcontent_prep_data_from_query( $raw, $post = FALSE, $mapping = NULL, $metas = NULL )
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

		$data = array_map( function ( $item ) use ( $post, $mapping, $metas ) {
			return $this->subcontent_prep_data_from_query( $item, $post, $mapping, $metas );
		}, $items );

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
		return Core\Arraay::sameKey( $this->subcontent_define_fields() );
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
}

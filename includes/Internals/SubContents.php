<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

trait SubContents
{

	protected function subcontent_get_strings_for_js( $extra = [] )
	{
		return array_merge( [
			'index'    => _x( '#', 'Internal: Subcontents: Javascript String: `index`', 'geditorial-admin' ),
			'plus'     => _x( '+', 'Internal: Subcontents: Javascript String: `plus`', 'geditorial-admin' ),
			'search'   => _x( 'Search', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
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
			'readonly' => _x( 'The field is in read-only mode!', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),

			/* translators: %s: count number */
			'countitems' => _x( '%s items', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			/* translators: %s: time string */
			'timeago' => _x( '%s ago', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),

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

	protected function subcontent_get_fields_for_settings()
	{
		return Core\Arraay::stripByKeys(
			$this->subcontent_define_fields(),
			$this->subcontent_get_required_fields( 'settings' )
		);
	}

	protected function subcontent_get_fields( $context = 'display', $settings_key = 'subcontent_fields' )
	{
		$all        = $this->subcontent_define_fields();
		// $hidden     = $this->subcontent_get_hidden_fields( $context );
		// $unique     = $this->subcontent_get_unique_fields( $context );
		// $readonly   = $this->subcontent_get_readonly_fields( $context );
		// $searchable = $this->subcontent_get_searchable_fields( $context );
		// $importable = $this->subcontent_get_importable_fields( $context );
		$required   = $this->subcontent_get_required_fields( $context );
		$enabled    = $this->get_setting( $settings_key, array_keys( $all ) );
		$fields     = [];

		foreach ( $all as $field => $label )
			if ( in_array( $field, $required, TRUE ) || in_array( $field, $enabled, TRUE ) )
				$fields[$field] = $label;

		return $this->filters( 'subcontent_fields', $fields, $enabled, $required, $context );
	}

	protected function subcontent_define_searchable_fields()
	{
		return [
			// 'fullname' => [ 'human', 'department' ], // <---- EXAMPLE
		];
	}

	protected function subcontent_get_searchable_fields( $context = 'display' )
	{
		return $this->filters( 'searchable_fields',
			$this->subcontent_define_searchable_fields(), $context );
	}

	protected function subcontent_define_required_fields()
	{
		return [
			// 'name' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_importable_fields( $context = 'display' )
	{
		return $this->filters( 'importable_fields',
			$this->subcontent_define_required_fields(), $context );
	}

	protected function subcontent_define_importable_fields()
	{
		return [
			// 'name' => 'type', // <---- EXAMPLE: 'target_key' => 'target_column'
		];
	}

	protected function subcontent_get_required_fields( $context = 'display' )
	{
		return $this->filters( 'required_fields',
			$this->subcontent_define_required_fields(), $context );
	}

	protected function subcontent_define_readonly_fields()
	{
		return [
			// 'name' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_readonly_fields( $context = 'display' )
	{
		return $this->filters( 'readonly_fields',
			$this->subcontent_define_readonly_fields(), $context );
	}

	protected function subcontent_define_hidden_fields()
	{
		return [
			// 'name' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_hidden_fields( $context = 'display' )
	{
		return $this->filters( 'hidden_fields',
			Core\Arraay::prepString(
				$this->subcontent_define_hidden_fields(), [
				'postid',
				'order',
			] ), $context );
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
			'postid' => '_post_ref',
		];
	}

	protected function subcontent_base_data_mapping()
	{
		return [
			'comment_content' => 'desc',    // `text`
			'comment_agent'   => 'label',   // `varchar(255)`
			'comment_karma'   => 'order',   // `int(11)` // WTF: must be fixed map!

			'comment_author'       => 'fullname',   // `tinytext`
			'comment_author_url'   => 'phone',      // `varchar(200)`
			'comment_author_email' => 'email',      // `varchar(100)`
			'comment_author_IP'    => 'date',       // `varchar(100)`

			'comment_ID'       => '_id',         // `bigint(20)`
			'comment_parent'   => '_parent',     // `bigint(20)`
			'comment_post_ID'  => '_object',     // `bigint(20)`
			'comment_type'     => '_type',       // `varchar(20)`
			'comment_approved' => '_status',     // `varchar(20)`
			'comment_date'     => '_date',       // `datetime`
			'comment_date_gmt' => '_date_gmt',   // `datetime`
			'user_id'          => '_user',       // `bigint(20)`
			'comment_meta'     => '_meta',
		];
	}

	protected function subcontent_get_data_mapping()
	{
		return $this->subcontent_base_data_mapping();
	}

	protected function subcontent_update_sort( $raw = [], $post = FALSE, $mapping = NULL )
	{
		foreach ( $raw as $offset => $comment_id )
			WordPress\Comment::setKarma( $offset + 1, $comment_id );

		return count( $raw );
	}

	protected function subcontent_insert_data_row( $raw = [], $post = FALSE, $mapping = NULL )
	{
		$data = $this->subcontent_sanitize_data( $raw, $post, $mapping );
		$data = $this->subcontent_prep_data_for_save( $data, $post, $mapping );
		$meta = [];

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

		if ( FALSE === ( $filtered = $this->filters( 'subcontent_insert_data_row', $data, $post, $mapping, $raw ) ) )
			return $this->log( 'NOTICE', 'SUBCONTENT INSERT DATA SKIPPED BY FILTER ON POST-ID:'.( $post ? $post->ID : 'UNKNOWN' ) );

		if ( array_key_exists( 'comment_meta', $data ) ) {
			$meta = $data['comment_meta'];
			unset( $data['comment_meta'] );
		}

		$this->subcontent_insert_data_before( $filtered, $filtered['comment_ID'] );

		$result = $filtered['comment_ID']
			? wp_update_comment( $filtered )
			: wp_insert_comment( $filtered );

		$this->subcontent_insert_data_after( $filtered, $filtered['comment_ID'] );

		if ( empty( $meta ) || FALSE === $result || self::isError( $result ) )
			return $result;

		$comment_id = $filtered['comment_ID'] ?: $result;

		foreach ( $meta as $meta_key => $meta_value )
			if ( empty( $meta_value ) )
				delete_comment_meta( $comment_id, $meta_key );
			else
				update_comment_meta( $comment_id, $meta_key, $meta_value );

		return $comment_id;
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

	protected function subcontent_delete_data_row( $id, $post = FALSE )
	{
		return wp_delete_comment( intval( $id ), TRUE );
	}

	// NOTE: Does final preparations and additions into data before saving.
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

		if ( ! empty( $data['comment_karma'] ) )
			return $data;

		$data['comment_karma'] = empty( $raw['order'] )
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

		if ( empty( $data['order'] ) )
			$data['order'] = $order ?? '1';

		return $this->filters( 'subcontent_after_prep_data', $data, $post, $mapping, $metas );
	}

	// TODO: support for shorthand chars like `+`/`~` in date types to fill with today/now
	protected function subcontent_sanitize_data( $raw = [], $post = FALSE, $mapping = NULL, $metas = NULL, $allowed_raw = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping();

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping();

		if ( is_null( $allowed_raw ) )
			$allowed_raw = [ 'data', 'order' ];

		$types = $this->subcontent_get_field_types( 'sanitize' );
		$data  = [];

		foreach ( $raw as $raw_key => $raw_value ) {

			if ( in_array( $raw_key, $allowed_raw, TRUE )
				// @SEE: `is_protected_meta()`
				|| Core\Text::starts( $raw_key, '_' ) ) {

				$data[$raw_key] = $raw_value;
				continue;
			}

			if ( WordPress\Strings::isEmpty( $raw_value ) ) {

				if ( empty( $data[$raw_key] ) )
					$data[$raw_key] = '';

				continue;
			}

			$type = array_key_exists( $raw_key, $types ) ? $types[$raw_key] : $raw_key;

			switch ( $type ) {
				case 'phone':    $data[$raw_key] = Core\Phone::sanitize( $raw_value ); break;
				case 'mobile':   $data[$raw_key] = Core\Phone::Mobile( $raw_value ); break;
				case 'country':  $data[$raw_key] = Core\Validation::sanitizeCountry( $raw_value, TRUE ); break;   // NOTE: skips the base country
				case 'vin':      $data[$raw_key] = Core\Validation::sanitizeVIN( $raw_value ); break;
				case 'iban':     $data[$raw_key] = Core\Validation::sanitizeIBAN( $raw_value ); break;
				case 'isbn':     $data[$raw_key] = Core\ISBN::sanitize( $raw_value ); break;
				case 'bankcard': $data[$raw_key] = Core\Validation::sanitizeCardNumber( $raw_value ); break;
				case 'identity': $data[$raw_key] = Core\Validation::sanitizeIdentityNumber( $raw_value ); break;
				case 'duration': $data[$raw_key] = Core\Duration::sanitize( $raw_value ); break;

				case 'date'    : // WTF
				case 'time'    : // WTF
				case 'contact' : // WTF: maybe: phone/mobile/email/url
				case 'stature' :
				case 'mass'    :
				case 'age'     : // WTF: maybe: year-only
				case 'dob'     : // WTF: maybe: year-only
				case 'year'    :
				case 'grade'   :
				case 'days'    :
				case 'hours'   :
				case 'account' :
				case 'count'   :
					$data[$raw_key] = Core\Number::translate( Core\Text::trim( $raw_value ) );
					break;

				case 'location':
				case 'people'  :
					$data[$raw_key] = WordPress\Strings::getPiped( Helper::getSeparated( Core\Text::trim( Helper::kses( $raw_value, 'none' ) ) ) );
					break;

				case 'bankname'  :
				case 'label'     :
				case 'topic'     :
				case 'fullname'  :
				case 'relation'  :
				case 'evaluation':
				case 'occupation':
				case 'education' :
				case 'address'   :
					$data[$raw_key] = WordPress\Strings::cleanupChars( Helper::kses( $raw_value, 'none' ), TRUE );
					break;

				case 'html':
					$data[$raw_key] = Core\Text::normalizeWhitespace( Helper::kses( $raw_value, 'text' ), TRUE );
					break;

				default:
					$data[$raw_key] = Core\Text::trim( Helper::kses( $raw_value, 'none' ) );
			}
		}

		return $this->filters( 'subcontent_sanitize_data', $data, $post, $raw, $mapping, $metas, $allowed_raw );
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

	protected function subcontent_get_data_all( $parent = NULL, $extra = [], $map = TRUE )
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
			'orderby'   => 'comment_karma', // orders stored as karma!

			'update_comment_meta_cache' => TRUE,
			'update_comment_post_cache' => FALSE,
		], $extra );

		$query = new \WP_Comment_Query;
		$items = $query->query( $args );

		return $map ? $this->subcontent_get_data_mapped( $items, $post ) : $items;
	}

	protected function subcontent_get_data_count( $parent = NULL, $context = NULL, $extra = [] )
	{
		return $this->subcontent_query_data_count( $parent, $extra );
	}

	protected function subcontent_query_data_count( $parent = NULL, $extra = [] )
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

		if ( array_key_exists( 'datestart', $types ) )
			$types['datestart'] = 'date';

		if ( array_key_exists( 'dateend', $types ) )
			$types['dateend'] = 'date';

		return $types;
	}

	protected function subcontent_get_field_types( $context = 'display' )
	{
		return $this->filters( 'field_types', $this->subcontent_defaine_field_types(), $context );
	}

	/**
	 * Prepares sub-content data for display given the context.
	 *
	 * @param  array  $data
	 * @param  string $context
	 * @return array  $prepped
	 */
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

		return Core\HTML::tag( 'p', [
			'class' => [ 'description', '-description', '-empty' ],
		], $this->get_string( $string_key, $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ) );
	}

	protected function subcontent_get_noaccess_notice( $context = 'display', $string_key = 'noaccess' )
	{
		$default = _x( 'You have not necessary permission to manage the information.', 'Internal: SubContents: No-Access Notice', 'geditorial' );

		return Core\HTML::tag( 'p', [
			'class' => [ 'description', '-description', '-noaccess' ],
		], $this->get_string( $string_key, $context, 'notices', $default ) );
	}

	protected function subcontent__hook_importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2, 10, 'subcontent' );
		$this->filter_module( 'importer', 'prepare', 7, 10, 'subcontent' );
		$this->action_module( 'importer', 'saved', 2, 10, 'subcontent' );
	}

	protected function subcontent_get_importer_fields( $posttype = NULL, $object = FALSE )
	{
		$fields     = [];
		$enabled    = $this->subcontent_get_fields( 'import' );
		$importable = $this->subcontent_get_importable_fields( 'import' );
		$template   = $this->get_string( 'field_title', FALSE, 'importer',
			/* translators: %s: field title */
			_x( 'SubContent: %s', 'Internal: Subcontents: Import Title', 'geditorial-admin' )
		);

		foreach ( $enabled as $field => $title )
			if ( array_key_exists( $field, $importable ) )
				$fields[sprintf( '%s__%s', $this->key, $field )] = sprintf( $template, $title );

		return $fields;
	}

	public function importer_fields_subcontent( $fields, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $fields;

		return array_merge( $fields, $this->subcontent_get_importer_fields( $posttype ) );
	}

	public function importer_prepare_subcontent( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->posttype_supported( $posttype ) || empty( $value ) )
			return $value;

		if ( ! in_array( $field, array_keys( $this->subcontent_get_importer_fields( $posttype ) ) ) )
			return $value;

		if ( WordPress\Strings::isEmpty( $value ) )
			return Helper::htmlEmpty();

		$types   = $this->subcontent_get_field_types( 'import' );
		$current = Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) );

		return $this->prep_meta_row( $value, $current, [
			'type' => array_key_exists( $current, $types ) ? $types[$current] : $current,
		], $raw[$field] );
	}

	public function importer_saved_subcontent( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->subcontent_get_importer_fields( $post->post_type );
		$types  = $this->subcontent_get_field_types( 'import' );
		$title  = WordPress\Post::title( (int) $atts['attach_id'] );

		foreach ( $atts['map'] as $offset => $field ) {

			if ( ! in_array( $field, $fields ) )
				continue;

			if ( WordPress\Strings::isEmpty( $value = $atts['raw'][$offset] ) )
				continue;

			$column  = empty( $atts['headers'][$offset] ) ? '' : $atts['headers'][$offset];
			$current = Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) );
			$prepped = $this->prep_meta_row( $value, $current, [
				'type' => array_key_exists( $current, $types ) ? $types[$current] : $current,
			], $atts['raw'][$offset] );

			if ( FALSE === ( $data = $this->subcontent_prep_data_from_import( $prepped, $current, $post, $column, $title ) ) )
				continue;

			if ( FALSE === $this->subcontent_insert_data_row( $data, $post ) )
				$this->log( 'NOTICE', 'SUBCONTENT INSERT DATA FAILED ON POST-ID:'.( $post ? $post->ID : 'UNKNOWN' ) );
		}
	}

	protected function subcontent_prep_data_from_import( $raw, $field, $post = FALSE, $column_title = '', $source_title = '' )
	{
		if ( empty( $raw ) )
			return FALSE;

		$enabled    = $this->subcontent_get_fields( 'import' );
		$importable = $this->subcontent_get_importable_fields( 'import' );
		$data       = [ $field => $raw ];

		if ( $column_title && array_key_exists( $field, $enabled ) )
			$data[$importable[$field]] = Core\Text::normalizeWhitespace( $column_title );

		if ( $source_title && array_key_exists( 'desc', $enabled ) )
			$data['desc'] = \sprintf(
				/* translators: %s: source title */
				_x( '[Imported]: %s', 'Internal: Subcontents: Import Desc', 'geditorial-admin' ),
				Core\Text::normalizeWhitespace( $source_title )
			);

		return $this->filters( 'subcontent_prep_data_from_import', $data, $raw, $field, $post, $column_title, $source_title );
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
					$post = WordPress\Post::get( (int) $request['linked'] );

					if ( FALSE === $this->subcontent_insert_data_row( $request->get_json_params(), $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data_all( $post ) );
				},
				'permission_callback' => $edit,
			],
			[
				'methods'  => \WP_REST_Server::DELETABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					$post = WordPress\Post::get( (int) $request['linked'] );

					if ( empty( $request['_id'] ) )
						return Services\RestAPI::getErrorArgNotEmpty( '_id' );

					if ( ! $this->subcontent_delete_data_row( $request['_id'], $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data_all( $post ) );
				},

				'permission_callback' => $edit,
			],
			[
				'methods'  => \WP_REST_Server::READABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					return rest_ensure_response( $this->subcontent_get_data_all( (int) $request['linked'] ) );
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
					$post = WordPress\Post::get( (int) $request['linked'] );

					if ( FALSE === $this->subcontent_update_sort( $request->get_json_params(), $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data_all( $post ) );
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
		$parent = WordPress\Post::get( (int) $comment->comment_post_ID );
		$item   = $this->subcontent_prep_data_from_query( $comment, $parent );
		$data   = apply_filters( $this->hook_base( 'subcontent', 'provide_summary' ), NULL, $item, $parent, $context );
		$author = $datetime = $timeago = '';

		// TODO: override by `Users` module for better profiles
		if ( ! empty( $item['_user'] ) && ( $user = get_user_by( 'id', $item['_user'] ) ) )
			$author = sprintf(
				/* translators: %s: user display-name (user email) */
				_x( 'By %s', 'Internal: Subcontents: User Row', 'geditorial-admin' ),
				WordPress\User::getTitleRow( $user )
			);

		if ( ! empty( $item['_date'] ) ) {
			$datetime = Datetime::dateFormat( $item['_date'], $context );
			// $timeago  = human_time_diff( strtotime( $item['_date'] ) );
			$timeago  = Datetime::moment( strtotime( $item['_date'] ) );
		}

		// NOTE: like `WordPress\Post::summary()`
		$summary = array_merge( [
			'title'       => $na,
			'link'        => FALSE,
			'image'       => FALSE,
			'author'      => $author,
			'timeago'     => $timeago,
			'datetime'    => $datetime,
			'description' => '',

			// preserve the originals
			'comment_author'   => $author,
			'comment_datetime' => $datetime,
			'comment_timeago'  => $timeago,
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

		if ( ! $data = $this->subcontent_get_data_all( $post ) )
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
		if ( $this->in_setting_posttypes( $post->post_type, 'subcontent' ) && $this->role_can( 'reports' ) )
			$list[] = [
				'key'     => $this->key,
				'class'   => '-table-summary',
				'title'   => $this->get_string( 'supportedbox_title', $post->post_type, 'metabox', '' ),
				'content' => $this->subcontent_do_main_shortcode( [
					'id'      => $post,
					'context' => $context,
					'wrap'    => FALSE,
				] ),
			];

		return $list;
	}

	protected function subcontent_do_render_iframe_content( $app_name, $context = NULL, $assign_template = NULL, $reports_template = NULL )
	{
		if ( ! $post = self::req( 'linked' ) )
			return Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return Info::renderNoPostsAvailable();

		if ( $this->role_can( 'assign' ) ) {

			Settings::wrapOpen( $this->key, $context, sprintf( $assign_template ?? '%s', WordPress\Post::title( $post ) ) );

				Scripts::renderAppMounter( TRUE === $app_name ? 'subcontent-grid' : $app_name, $this->key );
				Scripts::noScriptMessage();

			Settings::wrapClose( FALSE );

		} else if ( $this->role_can( 'reports' ) ) {

			Settings::wrapOpen( $this->key, $context, sprintf( $reports_template ?? '%s', WordPress\Post::title( $post ) ) );

				echo $this->subcontent_do_main_shortcode( [
					'id'      => $post,
					'context' => $context,
					'class'   => '-table-content',
				], $this->subcontent_get_empty_notice( $context ) );

			Settings::wrapClose( FALSE );

		} else {

			Core\HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );
		}
	}

	// TODO: support for `options`: list of available options for each field
	protected function subcontent_do_enqueue_app( $name, $args = [] )
	{
		$args = self::atts( [
			'context'    => 'edit',
			'can'        => 'assign',
			'assetkey'   => TRUE === $name ? '_subcontent' : NULL,
			'linked'     => NULL,
			'searchable' => NULL,
			'required'   => NULL,
			'readonly'   => NULL,
			'frozen'     => NULL, // FIXME: add full support
			'strings'    => [],
		], $args );

		if ( ! $this->role_can( $args['can'] ) )
			return;

		if ( ! $linked = $args['linked'] ?? self::req( 'linked', FALSE ) )
			return;

		$asset  = [
			'strings' => $this->subcontent_get_strings_for_js( $args['strings'] ),
			'fields'  => $this->subcontent_get_fields( $args['context'] ),
			'linked'  => [
				'id'    => $linked,
				'text'  => WordPress\Post::title( $linked ),
				'extra' => Services\SearchSelect::getExtraForPost( $linked, [ 'context' => 'subcontent' ] ),
				'image' => Services\SearchSelect::getImageForPost( $linked, [ 'context' => 'subcontent' ] ),
			],
			'config' => [
				'linked'       => $linked,
				'searchselect' => Services\SearchSelect::namespace(),
				'searchable'   => $args['searchable'] ?? $this->subcontent_get_searchable_fields( $args['context'] ),
				'required'     => $args['required'] ?? $this->subcontent_get_required_fields( $args['context'] ),
				'readonly'     => $args['readonly'] ?? $this->subcontent_get_readonly_fields( $args['context'] ),
				'hidden'       => $this->subcontent_get_hidden_fields( $args['context'] ),
				'unique'       => $this->subcontent_get_unique_fields( $args['context'] ),
				'frozen'       => $args['frozen'] ?? $this->get_setting( 'subcontent_frozen', FALSE ),
			],
		];

		$this->enqueue_asset_js( $asset, FALSE, [
			Scripts::enqueueApp( TRUE === $name ? 'subcontent-grid' : $name )
		], $args['assetkey'] );
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

	protected function subcontent_delete_data_all( $post, $force_delete = TRUE )
	{
		$data = $this->subcontent_get_data_all( $post, [], FALSE );

		foreach ( $data as $comment )
			if ( FALSE === wp_delete_comment( $comment, $force_delete ) )
				return FALSE;

		return count( $data );
	}

	protected function subcontent_clone_data_all( $from, $to, $fresh = FALSE )
	{
		$data = $this->subcontent_get_data_all( $from );

		if ( $fresh && ( FALSE === $this->subcontent_delete_data_all( $to ) ) )
			return FALSE;

		foreach ( $data as $row )
			if ( FALSE === $this->subcontent_insert_data_row( $this->subcontent_copy_data_row( $row ), $to ) )
				return FALSE;

		return count( $data );
	}

	protected function subcontent_copy_data_row( $data )
	{
		return Core\Arraay::stripByKeys( $data, [
			'_id',
			'_parent',
			'_object',
			'_status',
			'_date',
			'_date_gmt',
			'_user',
			'_type',
			'_meta',
		] );
	}

	protected function tweaks_column_row_subcontent( $post, $before, $after, $module )
	{
		printf( $before, $this->classs( 'subcontent' ) );

			echo $this->get_column_icon( FALSE, NULL, NULL, $post->post_type );

			echo $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'columnrow',
			] );

			if ( $count = $this->subcontent_get_data_count( $post ) )
				printf( ' <span class="-counted">(%s)</span>', $this->nooped_count( 'row', $count ) );

		echo $after;
	}

	protected function rowaction_get_mainlink_for_post_subcontent( $post )
	{
		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'rowaction',
			] ),
		];
	}

	protected function subcontent_do_render_supportedbox_content( $post, $context )
	{
		$this->subcontent_render_metabox_data_grid( $post, $context );

		if ( $this->role_can( 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $post, [
				'context' => 'mainbutton',
				'target'  => 'grid',
			] ), 'field-wrap -buttons' );

		else
			echo $this->subcontent_get_noaccess_notice();
	}

	public function audit_auto_audit_save_post_subcontent( $terms, $post, $taxonomy, $currents, $update )
	{
		if ( ! $this->in_setting_posttypes( $post->post_type, 'subcontent' ) )
			return $terms;

		// avoid confusions!
		if ( ! $term = $this->constant( 'term_empty_subcontent_data' ) )
			return $terms;

		if ( $exists = term_exists( $term, $taxonomy ) ) {

			if ( $this->subcontent_get_data_count( $post ) )
				$terms = Core\Arraay::stripByValue( $terms, $exists['term_id'] );

			else
				$terms[] = $exists['term_id'];
		}

		return $terms;
	}
}

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
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
			'select'   => _x( 'Select', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),

			/* translators: `%s`: count number */
			'countitems' => _x( '%s items', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),
			/* translators: `%s`: time string */
			'timeago' => _x( '%s ago', 'Internal: Subcontents: Javascript String', 'geditorial-admin' ),

		], $this->get_strings( 'subcontent', 'js' ), $extra );
	}

	protected function subcontent_get_comment_type()
	{
		return $this->constant( 'subcontent_type', $this->key );
	}

	// NOTE: Allows the sub-content types to have Avatar.
	protected function subcontent__enable_comment_avatar()
	{
		$this->filter_append( 'get_avatar_comment_types',
			$this->subcontent_get_comment_type() );
	}

	protected function subcontent_is_comment_type( $data_or_comment, $type = NULL )
	{
		if ( is_null( $type ) )
			$type = $this->subcontent_get_comment_type();

		if ( is_object( $data_or_comment ) )
			return property_exists( $data_or_comment, 'comment_type' )
				&& $type === $data_or_comment->comment_type;

		else if ( is_array( $data_or_comment ) )
			return array_key_exists( '_type', $data_or_comment )
				&& $type === $data_or_comment['_type'];

		return FALSE;
	}

	protected function subcontent_get_comment_status()
	{
		return $this->constant( 'subcontent_status', 'private' );
	}

	// NOTE: on strings API: `$strings['fields']['subcontent']`
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

	protected function subcontent_get_types_for_settings()
	{
		return Core\Arraay::stripByKeys( Core\Arraay::pluck(
			$this->subcontent_define_type_options( 'settings' ),
			'title',
			'name'
		), [
			'default', // NOTE: always keep the default type option enabled
		] );
	}

	protected function subcontent_define_type_options( $context, $posttype = NULL )
	{
		return [
			/// EXAMPLE
			// [
			// 	'name'     => 'default',
			// 	'title'    => _x( 'Default', 'Type Option', 'geditorial-admin' ),
			// 	'icon'     => 'external',
			//  'logo'     => '',
			//  'desc'     => '',
			// ],
		];
	}

	protected function subcontent_get_type_options( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'type_options',
			$this->subcontent_define_type_options( $context, $posttype ),
			$context,
			$posttype
		);
	}

	protected function subcontent_available_type_options( $context = 'display', $posttype = NULL )
	{
		// Tries not to fire the filter hook twice!
		$defined = Core\Arraay::pluck( $this->subcontent_define_type_options( $context, $posttype ), 'name' );
		$enabled = \array_merge( [ 'default' ], $this->get_setting( 'subcontent_types', [] ) );

		return Core\Arraay::stripByKeys(
			Core\Arraay::reKey( $this->subcontent_get_type_options( $context, $posttype ), 'name' ),
			array_diff( $defined, $enabled ),
		);
	}

	protected function subcontent_list_type_options( $context = 'display', $posttype = NULL )
	{
		return Core\Arraay::pluck(
			$this->subcontent_available_type_options( $context, $posttype ),
			'title',
			'name'
		);
	}

	protected function subcontent_get_fields( $context = 'display', $settings_key = 'subcontent_fields' )
	{
		$all        = $this->subcontent_define_fields();
		// $hidden     = $this->subcontent_get_hidden_fields( $context );
		// $unique     = $this->subcontent_get_unique_fields( $context );
		// $readonly   = $this->subcontent_get_readonly_fields( $context );

		// $searchable = $this->subcontent_get_searchable_fields( $context );
		// $selectable = $this->subcontent_get_selectable_fields( $context );
		// $importable = $this->subcontent_get_importable_fields( $context );
		$required   = $this->subcontent_get_required_fields( $context );
		$enabled    = $this->get_setting( $settings_key, array_keys( $all ) );
		$fields     = [];

		foreach ( $all as $field => $label )
			if ( in_array( $field, $required, TRUE ) || in_array( $field, $enabled, TRUE ) )
				$fields[$field] = $label;

		return $this->filters( 'subcontent_fields', $fields, $enabled, $required, $context );
	}

	protected function subcontent_define_searchable_fields( $context = 'display', $posttype = NULL )
	{
		return [
			// 'fullname' => [ 'human', 'department' ], // <---- EXAMPLE
		];
	}

	protected function subcontent_get_searchable_fields( $context = 'display', $posttype = NULL )
	{
		$fields = $this->subcontent_define_searchable_fields();

		foreach ( $fields as $field => $targets )
			$fields[$field] = array_filter( (array) $targets, 'post_type_exists' );

		return $this->filters( 'searchable_fields', $fields, $context, $posttype );
	}

	protected function subcontent_define_required_fields()
	{
		return [
			// 'name' // <---- EXAMPLE
			'label',
		];
	}

	protected function subcontent_get_importable_fields( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'importable_fields',
			$this->subcontent_define_required_fields(),
			$context,
			$posttype
		);
	}

	protected function subcontent_define_importable_fields( $context = 'display', $posttype = NULL )
	{
		return [
			// 'name' => 'type', // <---- EXAMPLE: 'target_key' => 'target_column'
		];
	}

	protected function subcontent_get_required_fields( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'required_fields',
			$this->subcontent_define_required_fields(),
			$context,
			$posttype
		);
	}

	protected function subcontent_define_readonly_fields( $context = 'display', $posttype = NULL )
	{
		return [
			// 'name' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_readonly_fields( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'readonly_fields',
			$this->subcontent_define_readonly_fields(),
			$context,
			$posttype
		);
	}

	protected function subcontent_define_selectable_fields( $context, $posttype = NULL )
	{
		return [
			// 'field_key': { option1: 'Option 1', option2: 'Option 2' }  <---- EXAMPLE
		];
	}

	protected function subcontent_get_selectable_fields( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'selectable_fields',
			$this->subcontent_define_selectable_fields( $context, $posttype ),
			$context,
			$posttype
		);
	}

	protected function subcontent_define_hidden_fields( $context = 'display', $posttype = NULL )
	{
		return [
			// 'name' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_hidden_fields( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'hidden_fields',
			Core\Arraay::prepString(
				$this->subcontent_define_hidden_fields( $context, $posttype ), [
				'postid',
				'order',
			] ),
			$context,
			$posttype
		);
	}

	protected function subcontent_define_unique_fields( $context = 'display', $posttype = NULL )
	{
		return [
			// 'identity' // <---- EXAMPLE
		];
	}

	protected function subcontent_get_unique_fields( $context = 'display', $posttype = NULL )
	{
		return $this->filters( 'unique_fields',
			$this->subcontent_define_unique_fields(),
			$context,
			$posttype
		);
	}

	protected function subcontent_get_meta_mapping( $context = NULL, $posttype = NULL )
	{
		return [
			// 'name' => '_metakey', // <---- EXAMPLE
			'postid' => '_post_ref',
		];
	}

	// NOTE: prevents from meta preparations
	protected function subcontent_get_meta_untouchable( $context = NULL, $posttype = NULL )
	{
		return [
			// 'data',
			// 'order',
			// 'postid',
		];
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_base_data_mapping( $context = 'display', $posttype = NULL )
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

	protected function subcontent_get_data_mapping( $context = NULL, $posttype = NULL )
	{
		return $this->subcontent_base_data_mapping( $context, $posttype );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_update_sort( $raw = [], $post = FALSE, $mapping = NULL )
	{
		foreach ( $raw as $offset => $comment_id )
			WordPress\Comment::setKarma( $offset + 1, $comment_id );

		return count( $raw );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_insert_data_row( $raw = [], $context = NULL, $post = FALSE, $mapping = NULL )
	{
		$data = $this->subcontent_sanitize_data( $raw, $context, $post, $mapping );
		$data = $this->subcontent_prep_data_for_save( $data, $context, $post, $mapping );
		$meta = [];

		if ( $post && ( $post = WordPress\Post::get( $post ) ) )
			$data['comment_post_ID'] = $post->ID;

		if ( ! array_key_exists( 'comment_ID', $data ) )
			$data['comment_ID'] = '0';

		if ( ! array_key_exists( 'user_id', $data ) )
			$data['user_id'] = get_current_user_id();

		// NOTE: always update the `comment_date` to current time/treat comment date as modified
		if ( ! array_key_exists( 'comment_date', $data ) )
			$data['comment_date'] = current_time( 'mysql' );

		if ( ! array_key_exists( 'comment_type', $data ) && ( $type = $this->subcontent_get_comment_type() ) )
			$data['comment_type'] = $type;

		if ( ! array_key_exists( 'comment_approved', $data ) && ( $status = $this->subcontent_get_comment_status() ) )
			$data['comment_approved'] = $status;

		if ( FALSE === ( $filtered = $this->filters( 'subcontent_insert_data_row', $data, $context, $post, $mapping, $raw ) ) )
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

	// FIXME: Move to `DeepContents` internal
	// NOTE: overrides the modifications by core
	public function subcontent_wp_update_comment_data( $data, $comment, $commentarr )
	{
		$key = $data['comment_ID'] ?: 'new';

		if ( array_key_exists( $key, $this->cache['subcontent_data'] ) )
			return $this->cache['subcontent_data'][$key];

		// fallback to manual mode!
		// NOTE: core tries to validate `comment_author_email`
		$data['comment_author_url'] = Core\Text::stripPrefix(
			$data['comment_author_url'],
			[ 'http://', 'https://' ]
		);

		return $data;
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_insert_data_before( $data = [], $comment_id = FALSE )
	{
		$this->cache['subcontent_data'][( $comment_id ?: 'new' )] = $data;

		remove_filter( 'comment_save_pre', 'convert_invalid_entities' );
		remove_filter( 'comment_save_pre', 'balanceTags', 50 );
		remove_filter( 'pre_comment_author_name', 'sanitize_text_field' );
		remove_filter( 'pre_comment_author_email', 'trim' );
		remove_filter( 'pre_comment_author_email', 'sanitize_email' );
		remove_filter( 'pre_comment_author_url', 'sanitize_url' );

		add_filter( 'wp_update_comment_data', [ $this, 'subcontent_wp_update_comment_data' ], 99, 3 );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_insert_data_after( $data = [], $comment_id = FALSE )
	{
		add_filter( 'comment_save_pre', 'convert_invalid_entities' );
		add_filter( 'comment_save_pre', 'balanceTags', 50 );
		add_filter( 'pre_comment_author_name', 'sanitize_text_field' );
		add_filter( 'pre_comment_author_email', 'trim' );
		add_filter( 'pre_comment_author_email', 'sanitize_email' );
		add_filter( 'pre_comment_author_url', 'sanitize_url' );

		remove_filter( 'wp_update_comment_data', [ $this, 'subcontent_wp_update_comment_data' ], 99, 3 );

		unset( $this->cache['subcontent_data'][( $comment_id ?: 'new' )] );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_delete_data_row( $id, $post = FALSE )
	{
		return wp_delete_comment( intval( $id ), TRUE );
	}

	// FIXME: Move to `DeepContents` internal
	// NOTE: Does final preparations and additions into data before saving.
	protected function subcontent_prep_data_for_save( $raw, $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping( $context, $post );

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping( $context, $post );

		if ( is_object( $raw ) )
			$raw = Core\Arraay::fromObject( $raw );

		$raw  = $this->filters( 'subcontent_pre_prep_data', $raw, $context, $post, $mapping, $metas );
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

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_prep_data_from_query( $raw, $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL, $order = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping( $context, $post );

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping( $context, $post );

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

		return $this->filters( 'subcontent_after_prep_data', $data, $context, $post, $mapping, $metas );
	}

	// FIXME: **partially** Move to `DeepContents` internal
	// TODO: support for shorthand chars like `+`/`~` in date types to fill with today/now
	// TODO: support for auto-fill fields with tokens: `date: '{{now}}'`
	protected function subcontent_sanitize_data( $raw = [], $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL, $allowed_raw = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping( $context, $post );

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping( $context, $post );

		if ( is_null( $allowed_raw ) )
			$allowed_raw = [ 'data', 'order' ];

		$types = $this->subcontent_get_field_types( $context );
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
				case 'plate':    $data[$raw_key] = Core\Validation::sanitizePlateNumber( $raw_value ); break;
				case 'iban':     $data[$raw_key] = Core\Validation::sanitizeIBAN( $raw_value ); break;
				case 'isbn':     $data[$raw_key] = Core\ISBN::sanitize( $raw_value ); break;
				case 'bankcard': $data[$raw_key] = Core\Validation::sanitizeCardNumber( $raw_value ); break;
				case 'identity': $data[$raw_key] = Core\Validation::sanitizeIdentityNumber( $raw_value ); break;
				case 'distance': $data[$raw_key] = Core\Distance::sanitize( $raw_value ); break;
				case 'duration': $data[$raw_key] = Core\Duration::sanitize( $raw_value ); break;
				case 'area':     $data[$raw_key] = Core\Area::sanitize( $raw_value ); break;
				case 'cssclass': $data[$raw_key] = Core\HTML::prepClass( $raw_value ); break;

				case 'code'    : // WTF
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
					$data[$raw_key] = WordPress\Strings::getPiped( Services\Markup::getSeparated( WordPress\Strings::kses( $raw_value, 'none' ) ) );
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
					$data[$raw_key] = WordPress\Strings::cleanupChars( WordPress\Strings::kses( $raw_value, 'none' ), TRUE );
					break;

				case 'html':
					$data[$raw_key] = Core\Text::normalizeWhitespace( WordPress\Strings::kses( $raw_value, 'text' ), TRUE );
					break;

				default:
					$data[$raw_key] = WordPress\Strings::kses( $raw_value, 'none' );
			}
		}

		return $this->filters( 'subcontent_sanitize_data', $data, $context, $post, $raw, $mapping, $metas, $allowed_raw );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_get_data_mapped( $items, $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->subcontent_get_data_mapping( $context, $post );

		if ( is_null( $metas ) )
			$metas = $this->subcontent_get_meta_mapping( $context, $post );

		$data = [];

		foreach ( $items as $offset => $item )
			$data[] = $this->subcontent_prep_data_from_query( $item, $context, $post, $mapping, $metas, $offset + 1 );

		return $this->filters( 'subcontent_data_mapped', $data, $context, $post, $items, $mapping, $metas );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_get_data_all( $parent = NULL, $context = NULL, $map = TRUE, $extra = [] )
	{
		if ( ! $post = WordPress\Post::get( $parent ) )
			return FALSE;

		$args = array_merge( [
			'post_id'   => $post->ID,
			'post_type' => 'any', // $parent->post_type,
			'status'    => 'any', // $this->subcontent_get_comment_status(),
			'type'      => $this->subcontent_get_comment_type(),
			'fields'    => '', // 'ids', // empty for all
			'number'    => '', // empty for all comments
			'order'     => 'ASC',
			'orderby'   => 'comment_karma', // orders stored as karma!

			'update_comment_meta_cache' => TRUE,
			'update_comment_post_cache' => FALSE,
			'no_found_rows'             => TRUE,
		], $extra );

		$query = new \WP_Comment_Query;
		$items = $this->filters( 'pre_data_all', $query->query( $args ), $context, $post, $args );

		return $map ? $this->subcontent_get_data_mapped( $items, $context, $post ) : $items;
	}

	// NOTE: wrapper method in case we had to override the count
	protected function subcontent_get_data_count( $parent = NULL, $context = NULL, $extra = [] )
	{
		return $this->subcontent_query_data_count( $parent, $extra );
	}

	// FIXME: Move to `DeepContents` internal
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

		if ( array_key_exists( 'timestart', $types ) )
			$types['timestart'] = 'time';

		if ( array_key_exists( 'timeend', $types ) )
			$types['timeend'] = 'time';

		return $types;
	}

	protected function subcontent_get_field_types( $context = 'display' )
	{
		return $this->filters( 'field_types', $this->subcontent_defaine_field_types(), $context );
	}

	/**
	 * Prepares sub-content data for display given the context.
	 *
	 * @param array $raw
	 * @param string $context
	 * @return array
	 */
	protected function subcontent_get_prepped_data( $raw, $context = 'display', $post = NULL )
	{
		$data        = [];
		$types       = $this->subcontent_get_field_types( $context );
		$selectable  = $this->subcontent_get_selectable_fields( $context );
		$untouchable = $this->subcontent_get_meta_untouchable( $context );

		foreach ( $raw as $offset => $row ) {

			$item = [];

			foreach ( $row as $key => $value ) {

				if ( in_array( $key, $untouchable, TRUE )
					|| Core\Text::starts( $key, '_' ) ) {

					$item[$key] = $value;

				} else if ( empty( $value ) || ! trim( $value ) ) {

					if ( 'display' === $context )
						$item[$key] = gEditorial\Helper::htmlEmpty();

					else
						// also passing raw data
						$item[$key] = $item[sprintf( '__%s', $key )] = '';

				} else {

					$raw = $value;

					// also passing raw data
					if ( 'display' !== $context )
						$item[sprintf( '__%s', $key )] = $raw;

					if ( ! empty( $selectable[$key][$value] ) )
						$value = $selectable[$key][$value];

					$item[$key] = $this->prep_meta_row( $value, $key, [
						'type' => array_key_exists( $key, $types ) ? $types[$key] : $key,
					], $raw );
				}
			}

			$data[$offset] = $item;
		}

		return $this->filters( 'prepped_data', $data, $context, $post, $raw, $types );
	}

	// NOTE: use on `importer_init()`
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
			/* translators: `%s`: field title */
			_x( 'SubContent: %s', 'Internal: Subcontents: Import Title', 'geditorial-admin' )
		);

		foreach ( $enabled as $field => $title )
			if ( array_key_exists( $field, $importable ) )
				$fields[sprintf( '%s__%s', $this->key, $field )] = sprintf( $template, $title );

		return $fields;
	}

	public function importer_fields_subcontent( $fields, $posttype )
	{
		if ( ! $this->in_setting_posttypes( $posttype, 'subcontent' ) )
			return $fields;

		return array_merge( $fields, $this->subcontent_get_importer_fields( $posttype ) );
	}

	public function importer_prepare_subcontent( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->in_setting_posttypes( $posttype, 'subcontent' ) || empty( $value ) )
			return $value;

		if ( ! in_array( $field, array_keys( $this->subcontent_get_importer_fields( $posttype ) ) ) )
			return $value;

		if ( WordPress\Strings::isEmpty( $value ) )
			return gEditorial\Helper::htmlEmpty();

		$types   = $this->subcontent_get_field_types( 'import' );
		$current = Core\Text::stripPrefix( $field, sprintf( '%s__', $this->key ) );

		return $this->prep_meta_row( $value, $current, [
			'type' => array_key_exists( $current, $types ) ? $types[$current] : $current,
		], $raw[$field] );
	}

	public function importer_saved_subcontent( $post, $atts = [] )
	{
		if ( ! $post || ! $this->in_setting_posttypes( $post->post_type, 'subcontent' ) )
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

			if ( FALSE === $this->subcontent_insert_data_row( $data, 'import', $post ) )
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
				/* translators: `%s`: source title */
				_x( '[Imported]: %s', 'Internal: Subcontents: Import Desc', 'geditorial-admin' ),
				Core\Text::normalizeWhitespace( $source_title )
			);

		return $this->filters( 'subcontent_prep_data_from_import', $data, $raw, $field, $post, $column_title, $source_title );
	}

	// FIXME: **partially** Move to `DeepContents` internal
	// TODO: `total`: count with HTML markup
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

					if ( FALSE === $this->subcontent_insert_data_row( $request->get_json_params(), 'rest', $post ) )
						return Services\RestAPI::getErrorForbidden();

					return rest_ensure_response( $this->subcontent_get_data_all( $post, 'rest' ) );
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

					return rest_ensure_response( $this->subcontent_get_data_all( $post, 'rest' ) );
				},

				'permission_callback' => $edit,
			],
			[
				'methods'  => \WP_REST_Server::READABLE,
				'args'     => $arguments,
				'callback' => function ( $request ) {
					return rest_ensure_response( $this->subcontent_get_data_all( (int) $request['linked'], 'rest' ) );
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

					return rest_ensure_response( $this->subcontent_get_data_all( $post, 'rest' ) );
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
		$item   = $this->subcontent_prep_data_from_query( $comment, $context, $parent );
		$data   = apply_filters( $this->hook_base( 'subcontent', 'provide_summary' ), NULL, $item, $parent, $context );
		$author = $datetime = $timeago = '';

		// TODO: override by `Users` module for better profiles
		if ( ! empty( $item['_user'] ) && ( $user = get_user_by( 'id', $item['_user'] ) ) )
			$author = sprintf(
				/* translators: `%s`: user display-name (user email) */
				_x( 'By %s', 'Internal: Subcontents: User Row', 'geditorial-admin' ),
				WordPress\User::getTitleRow( $user )
			);

		if ( ! empty( $item['_date'] ) ) {
			$datetime = gEditorial\Datetime::dateFormat( $item['_date'], $context );
			// $timeago  = human_time_diff( strtotime( $item['_date'] ) );
			$timeago  = gEditorial\Datetime::moment( $item['_date'] );
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
		], $this->get_notice_for_empty( $context ) );

		return $this->filters( 'provide_markup', $markup, $parent, $context );
	}

	protected function subcontent_do_main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		// NOTE: falls back into name-space
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

		if ( ! $data = $this->subcontent_get_data_all( $post, $args['context'] ?? 'display' ) )
			return $content;

		if ( is_null( $args['fields'] ) )
			$args['fields'] = $this->subcontent_get_fields( $args['context'] );

		$data = $this->subcontent_get_prepped_data( $data, $args['context'], $post );
		$html = Core\HTML::tableSimple( $data, $args['fields'], FALSE );

		return gEditorial\ShortCode::wrap( $html, $constant, $args );
	}

	protected function subcontent_hook__post_tabs( $priority = NULL )
	{
		if ( ! $this->get_setting( 'tabs_support', TRUE ) )
			return FALSE;

		if ( ! gEditorial()->enabled( 'tabs' ) )
			return FALSE;

		add_filter( $this->hook_base( 'tabs', 'builtins_tabs' ),
			function ( $tabs, $posttype ) use ( $priority ) {

				if ( $this->in_setting_posttypes( $posttype, 'subcontent' ) )
					$tabs[] = [

						'name'  => $this->hook( 'subcontent', $posttype ),
						'title' => $this->get_setting_fallback( 'tab_title', $this->get_string( 'tab_title', $posttype, 'frontend', $this->module->title ) ),

						'viewable' => function ( $post ) {
							return (bool) $this->subcontent_get_data_count( $post, 'tabs' );
						},

						'callback' => function ( $post ) {
							echo $this->main_shortcode( [ // NOTE: allows for module override
								'id'      => $post,
								'context' => 'tabs',
								'wrap'    => FALSE,
								'title'   => FALSE,
							], $this->get_notice_for_empty( 'tabs' ) );
						},

						'priority' => $this->get_setting( 'tab_priority', 80 ),
					];

				return $tabs;
			}, 10, 2 );

		return TRUE;
	}

	/**
	 * Appends the table summary of sub-contents for current supported.
	 * @example `$this->filter_module( 'tabloid', 'post_summaries', 4, 40, 'subcontent' );`
	 *
	 * @param array $list
	 * @param array $data
	 * @param object $post
	 * @param string $context
	 * @return array
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

	protected function subcontent_do_render_iframe_content( $context = NULL, $assign_template = NULL, $reports_template = NULL, $custom_app = NULL )
	{
		if ( ! $post = self::req( 'linked' ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( ! $post = WordPress\Post::get( $post ) )
			return gEditorial\Info::renderNoPostsAvailable();

		if ( $this->role_can_post( $post, 'assign' ) ) {

			gEditorial\Settings::wrapOpen( $this->key, $context, sprintf( $assign_template ?? '%s', WordPress\Post::title( $post ) ) );

				gEditorial\Scripts::renderAppMounter( $custom_app ?? 'subcontent-grid', $this->key );
				gEditorial\Scripts::noScriptMessage();

			gEditorial\Settings::wrapClose( FALSE );

		} else if ( $this->role_can_post( $post, 'reports' ) ) {

			gEditorial\Settings::wrapOpen( $this->key, $context, sprintf( $reports_template ?? '%s', WordPress\Post::title( $post ) ) );

				echo $this->subcontent_do_main_shortcode( [
					'id'      => $post,
					'context' => $context,
					'class'   => '-table-content',
				], $this->get_notice_for_empty( $context ) );

			gEditorial\Settings::wrapClose( FALSE );

		} else {

			gEditorial\Settings::wrapOpen( $this->key, $context, gEditorial\Plugin::denied( FALSE ) );
				Core\HTML::dieMessage( $this->get_notice_for_noaccess() );
			gEditorial\Settings::wrapClose( FALSE );
		}
	}

	protected function subcontent_do_enqueue_app( $atts = [], $custom_app = NULL )
	{
		$args = self::atts( [
			'app'        => $custom_app ?? 'subcontent-grid',
			'asset'      => is_null( $custom_app ) ? '_subcontent' : NULL,
			'can'        => 'assign',
			'linked'     => NULL,
			'searchable' => NULL,
			'selectable' => NULL,
			'required'   => NULL,
			'readonly'   => NULL,
			'frozen'     => NULL, // FIXME: add full support
			'context'    => 'edit',
			'strings'    => [],
		], $atts );

		if ( ! $linked = $args['linked'] ?? self::req( 'linked', FALSE ) )
			return;

		if ( ! $post = WordPress\Post::get( (int) $linked ) )
			return;

		if ( ! $this->role_can_post( $post, $args['can'] ) )
			return;

		$asset = [
			'strings' => $this->subcontent_get_strings_for_js( $args['strings'] ),
			'fields'  => $this->subcontent_get_fields( $args['context'] ),
			'linked'  => [
				'id'    => $post->ID,
				'text'  => WordPress\Post::title( $post ),
				'extra' => Services\SearchSelect::getExtraForPost( $post, [ 'context' => 'subcontent' ] ),
				'image' => Services\SearchSelect::getImageForPost( $post, [ 'context' => 'subcontent' ] ),
			],
			'config' => [
				'linked'       => $post->ID,
				'searchselect' => Services\SearchSelect::namespace(),
				'searchable'   => $args['searchable'] ?? $this->subcontent_get_searchable_fields( $args['context'] ),
				'selectable'   => $args['selectable'] ?? $this->subcontent_get_selectable_fields( $args['context'] ),
				'required'     => $args['required'] ?? $this->subcontent_get_required_fields( $args['context'] ),
				'readonly'     => $args['readonly'] ?? $this->subcontent_get_readonly_fields( $args['context'] ),
				'hidden'       => $this->subcontent_get_hidden_fields( $args['context'] ),
				'unique'       => $this->subcontent_get_unique_fields( $args['context'] ),
				'frozen'       => $args['frozen'] ?? $this->get_setting( 'subcontent_frozen', FALSE ),
			],
		];

		$this->enqueue_asset_js( $asset, FALSE, [
			gEditorial\Scripts::enqueueApp( $args['app'] )
		], $args['asset'] );
	}

	protected function subcontent_do_enqueue_asset_js( $screen )
	{
		if ( ! $this->role_can( 'assign' ) )
			return;

		gEditorial\Scripts::enqueueColorBox();

		// $this->enqueue_asset_js( [], $screen, [
		// 	'jquery',
		// 	'wp-api-request',
		// 	gEditorial\Scripts::enqueueColorBox(),
		// ] );
	}

	protected function subcontent_render_metabox_data_grid( $post, $context = NULL )
	{
		echo $this->wrap( $this->subcontent_do_main_shortcode( [
			'id'      => $post,
			'context' => $context,
			'wrap'    => FALSE,
		], $this->get_notice_for_empty( $context ) ), '', TRUE, $this->classs( 'data-grid' ) );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_delete_data_all( $post, $force_delete = TRUE )
	{
		$data = $this->subcontent_get_data_all( $post, 'delete', FALSE );

		foreach ( $data as $comment )
			if ( FALSE === wp_delete_comment( $comment, $force_delete ) )
				return FALSE;

		return count( $data );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_clone_data_all( $from, $to, $fresh = FALSE )
	{
		$data = $this->subcontent_get_data_all( $from, 'clone' );

		if ( $fresh && ( FALSE === $this->subcontent_delete_data_all( $to ) ) )
			return FALSE;

		foreach ( $data as $row )
			if ( FALSE === $this->subcontent_insert_data_row( $this->subcontent_copy_data_row( $row ), 'clone', $to ) )
				return FALSE;

		return count( $data );
	}

	// FIXME: Move to `DeepContents` internal
	protected function subcontent_copy_data_row( $data )
	{
		return Core\Arraay::stripByKeys( $data, [
			'_id',
			// '_parent',  // parent comment
			'_object',  // parent post
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
		$thrift = $this->is_thrift_mode();

		printf( $before, $this->classs( 'subcontent' ) );

			if ( ! $thrift )
				echo $this->get_column_icon( FALSE, NULL, NULL, $post->post_type );

			echo $this->framepage_get_mainlink_for_post( $post, [
				'context'      => 'columnrow',
				'link_context' => 'overview',
				'text'         => $thrift ? '%1$s' : NULL,
			] );

			if ( ! $thrift && ( $count = $this->subcontent_get_data_count( $post ) ) )
				printf( ' <span class="count -counted">(%s)</span>', $this->nooped_count( 'row', $count ) );

		echo $after;
	}

	protected function rowaction_get_mainlink_for_post_subcontent( $post )
	{
		if ( ! $this->role_can_post( $post, [ 'reports', 'assign' ] ) )
			return FALSE;

		return [
			$this->classs().' hide-if-no-js' => $this->framepage_get_mainlink_for_post( $post, [
				'context'      => 'rowaction',
				'link_context' => 'overview',
			] ),
		];
	}

	protected function subcontent_do_render_supportedbox_content( $post, $context )
	{
		$this->subcontent_render_metabox_data_grid( $post, $context );

		if ( $this->role_can_post( $post, 'assign' ) )
			echo Core\HTML::wrap( $this->framepage_get_mainlink_for_post( $post, [
				'context'      => 'mainbutton',
				'link_context' => 'overview',
				'target'       => 'grid',
				'route'        => $this->restapi_get_route( 'markup', $post->ID ),
				'pot'          => '#'.$this->classs( 'data-grid' ),
				'refresh'      => 'html',
			] ), 'field-wrap -buttons' );

		else
			echo $this->get_notice_for_noaccess();
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

	public function subcontent_data_summary( $atts = [], $post = NULL )
	{
		$args = $this->filters( 'data_summary_args', self::atts( [
			'id'       => $post,
			'fields'   => NULL,
			'context'  => NULL,
			'template' => NULL,
			'default'  => FALSE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
			'render'   => NULL,
		], $atts ), $post );

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		if ( ! $data = $this->subcontent_get_data_all( $post, $args['context'] ?? 'summary' ) )
			return $args['default'];

		if ( ! $data = $this->subcontent_get_prepped_data( $data, $args['context'] ?? 'summary' ) )
			return $args['default'];

		if ( ! method_exists( $this, 'viewengine__render' ) ) {
			$this->log( 'CRITICAL', 'VIEW ENGINE NOT AVAILABLE' );
			return $args['default'];
		}

		if ( ! $view = $this->viewengine__view_by_template( $args['template'] ?? 'data-summary', 'subcontent' ) )
			return $args['default'];

		if ( ! $html = $this->viewengine__render( $view, [ 'data' => $data ], FALSE ) )
			return $args['default'];

		$html = $args['before'].$html.$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// NOTE: mocking the `Tablelist::getPosts()`
	protected function subcontent_get_data_with_pagination( $atts = [], $extra = [], $parent = FALSE, $context = NULL, $perpage = 25 )
	{
		$post  = WordPress\Post::get( $parent );
		$limit = self::limit( $perpage );
		$paged = self::paged();

		$args = [
			'offset'  => ( $paged - 1 ) * $limit,
			'number'  => $limit,
			'orderby' => self::orderby( 'comment_date' ),
			'order'   => self::order( 'DESC' ),

			'post_id'   => $post ? $post->ID : 0,
			'post_type' => 'any', // $parent->post_type,
			'status'    => 'any', // $this->subcontent_get_comment_status(),
			'type'      => $this->subcontent_get_comment_type(),
			'fields'    => '', // 'ids', // empty for all

			'update_comment_meta_cache' => TRUE,
			'update_comment_post_cache' => FALSE,
			'no_found_rows'             => FALSE, // for pagination
		];

		if ( ! empty( $_REQUEST['s'] ) )
			$args['search'] = $extra['s'] = $_REQUEST['s'];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		$query = new \WP_Comment_Query;
		$items = $this->filters( 'pre_data_all', $query->query( array_merge( $args, $atts ) ), $context, $post, $args );

		$pagination             = Core\HTML::tablePagination( $query->found_comments, $query->max_num_pages, $limit, $paged, $extra );
		$pagination['orderby']  = $args['orderby'];
		$pagination['order']    = $args['order'];

		return [ $items, $pagination ];
	}

	protected function subcontent_reports_render_table( $uri = '', $sub = NULL, $context = 'reports', $title = NULL )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		$query = [];

		list( $items, $pagination ) = $this->subcontent_get_data_with_pagination( $query, [], FALSE, $context, $this->get_sub_limit_option( $sub, $context ) );

		$pagination['before'][] = gEditorial\Tablelist::filterSearch();

		$data   = $this->subcontent_get_data_mapped( $items, $context );
		$data   = $this->subcontent_get_prepped_data( $data, $context, FALSE );
		$fields = $this->subcontent_get_fields( $context );

		$columns = [
			'_cb'     => '_id',
			'_object' => [
				'title'    => _x( 'Parent Post', 'Internal: Subcontents: Column', 'geditorial-admin' ),
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( $value && ( $parent = WordPress\Post::title( (int) $value, FALSE ) ) )
						return $parent;

					return $value ?: gEditorial\Helper::htmlEmpty();
				},
			],
		];

		return Core\HTML::tableList( array_merge( $columns, $fields ), $data, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', $title ?? _x( 'Overview of Sub-contents', 'Internal: Subcontents: Header', 'geditorial-admin' ) ),
			'empty'      => $this->get_string( 'empty', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ),
			'pagination' => $pagination,
			'extra'      => [
				'na'      => gEditorial()->na(),
				'fields'  => $fields,
				'context' => $context,
			],
		] );
	}
}

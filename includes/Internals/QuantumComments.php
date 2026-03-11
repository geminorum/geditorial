<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait QuantumComments
{
	protected function quantumcomments__filter_prefix()
	{
		return 'quantumcomments';
	}

	protected function quantumcomments__get_data_mapping( $context = NULL, $posttype = NULL )
	{
		return $this->quantumcomments__base_data_mapping( $context, $posttype );
	}

	protected function quantumcomments__base_data_mapping( $context = 'display', $posttype = NULL )
	{
		return [
			// 'comment_content' => 'desc',    // `text`
			// 'comment_agent'   => 'label',   // `varchar(255)`
			// 'comment_karma'   => 'order',   // `int(11)` // WTF: must be fixed map!

			// 'comment_author'       => 'fullname',   // `tinytext`
			// 'comment_author_url'   => 'phone',      // `varchar(200)`
			// 'comment_author_email' => 'email',      // `varchar(100)`
			// 'comment_author_IP'    => 'date',       // `varchar(100)`

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

	protected function quantumcomments__get_meta_mapping( $context = NULL, $posttype = NULL )
	{
		return [
			// 'name' => '_metakey', // <---- EXAMPLE
			'postid' => '_post_ref',
		];
	}

	protected function quantumcomments__get_comment_type()
	{
		return $this->constant( 'comment_type', $this->key );
	}

	protected function quantumcomments__get_comment_status()
	{
		return $this->constant( 'comment_status', 'private' );
	}

	// NOTE: on strings API: `$strings['fields']['subcontent']`
	// NOTE: on strings API: `$strings['fields']['quantumcomments']`
	protected function quantumcomments__define_fields()
	{
		return $this->get_strings( $this->quantumcomments__filter_prefix(), 'fields' );
	}

	// NOTE: Allows the comment types to have Avatar.
	protected function quantumcomments__enable_comment_avatar()
	{
		$this->filter_append( 'get_avatar_comment_types',
			$this->quantumcomments__get_comment_type() );
	}

	protected function quantumcomments__is_comment_type( $data_or_comment, $type = NULL )
	{
		$type = $type ?? $this->quantumcomments__get_comment_type();

		if ( is_object( $data_or_comment ) )
			return property_exists( $data_or_comment, 'comment_type' )
				&& $type === $data_or_comment->comment_type;

		else if ( is_array( $data_or_comment ) )
			return array_key_exists( '_type', $data_or_comment )
				&& $type === $data_or_comment['_type'];

		return FALSE;
	}

	// OLD: `subcontent_delete_data_row`
	protected function quantumcomments__delete_data_row( $id, $post = FALSE )
	{
		return wp_delete_comment( intval( $id ), TRUE );
	}

	protected function quantumcomments__query_data_count( $parent = NULL, $extra = [], $type = NULL )
	{
		if ( ! $post = WordPress\Post::get( $parent ) )
			return FALSE;

		$args = array_merge( [
			'post_id'   => $post->ID,
			'post_type' => 'any',
			'status'    => 'any',
			'type'      => $type ?? $this->quantumcomments__get_comment_type(),
			'fields'    => '', // 'ids', // empty for all
			'number'    => '', // empty for all
			'orderby'   => 'none',
			'count'     => TRUE,

			'update_comment_meta_cache' => FALSE,
			'update_comment_post_cache' => FALSE,
		], $extra );

		$query = new \WP_Comment_Query;
		return (int) $query->query( $args );
	}

	// OLD: `subcontent_update_sort()`
	protected function quantumcomments__update_sort( $raw = [], $post = FALSE, $mapping = NULL )
	{
		foreach ( $raw as $offset => $comment_id )
			WordPress\Comment::setKarma( $offset + 1, $comment_id );

		return count( $raw );
	}

	// OLD: `subcontent_wp_update_comment_data()`
	// NOTE: overrides the modifications by core
	public function quantumcomments__wp_update_comment_data( $data, $comment, $commentarr )
	{
		$key    = $data['comment_ID'] ?: 'new';
		$prefix = $this->quantumcomments__filter_prefix();

		if ( array_key_exists( $key, $this->cache[$prefix] ) )
			return $this->cache[$prefix][$key];

		// fallback to manual mode!
		// NOTE: core tries to validate `comment_author_email`
		$data['comment_author_url'] = Core\Text::stripPrefix(
			$data['comment_author_url'],
			[ 'http://', 'https://' ]
		);

		return $data;
	}

	// OLD: `subcontent_insert_data_before()`
	protected function quantumcomments__insert_data_before( $data = [], $comment_id = FALSE )
	{
		$prefix = $this->quantumcomments__filter_prefix();
		$this->cache[$prefix][( $comment_id ?: 'new' )] = $data;

		remove_filter( 'comment_save_pre', 'convert_invalid_entities' );
		remove_filter( 'comment_save_pre', 'balanceTags', 50 );
		remove_filter( 'pre_comment_author_name', 'sanitize_text_field' );
		remove_filter( 'pre_comment_author_email', 'trim' );
		remove_filter( 'pre_comment_author_email', 'sanitize_email' );
		remove_filter( 'pre_comment_author_url', 'sanitize_url' );

		add_filter( 'wp_update_comment_data', [ $this, 'quantumcomments__wp_update_comment_data' ], 99, 3 );
	}

	// OLD: `subcontent_insert_data_after()`
	protected function quantumcomments__insert_data_after( $data = [], $comment_id = FALSE )
	{
		$prefix = $this->quantumcomments__filter_prefix();

		add_filter( 'comment_save_pre', 'convert_invalid_entities' );
		add_filter( 'comment_save_pre', 'balanceTags', 50 );
		add_filter( 'pre_comment_author_name', 'sanitize_text_field' );
		add_filter( 'pre_comment_author_email', 'trim' );
		add_filter( 'pre_comment_author_email', 'sanitize_email' );
		add_filter( 'pre_comment_author_url', 'sanitize_url' );

		remove_filter( 'wp_update_comment_data', [ $this, 'quantumcomments__wp_update_comment_data' ], 99, 3 );

		unset( $this->cache[$prefix][( $comment_id ?: 'new' )] );
	}

	// OLD: `subcontent_insert_data_row()`
	protected function quantumcomments__insert_data_row( $raw = [], $context = NULL, $post = FALSE, $mapping = NULL )
	{
		$prefix = $this->quantumcomments__filter_prefix();
		$data   = $this->quantumcomments__sanitize_data( $raw, $context, $post, $mapping );
		$data   = $this->quantumcomments__prep_data_for_save( $data, $context, $post, $mapping );
		$meta   = [];

		if ( $post && ( $post = WordPress\Post::get( $post ) ) )
			$data['comment_post_ID'] = $post->ID;

		if ( ! array_key_exists( 'comment_ID', $data ) )
			$data['comment_ID'] = '0';

		if ( ! array_key_exists( 'user_id', $data ) )
			$data['user_id'] = get_current_user_id();

		// NOTE: always update the `comment_date` to current time/treat comment date as modified
		if ( ! array_key_exists( 'comment_date', $data ) )
			$data['comment_date'] = current_time( 'mysql' );

		if ( ! array_key_exists( 'comment_type', $data ) && ( $type = $this->quantumcomments__get_comment_type() ) )
			$data['comment_type'] = $type;

		if ( ! array_key_exists( 'comment_approved', $data ) && ( $status = $this->quantumcomments__get_comment_status() ) )
			$data['comment_approved'] = $status;

		if ( FALSE === ( $filtered = $this->filters( $prefix.'_insert_data_row', $data, $context, $post, $mapping, $raw ) ) )
			return $this->log( 'NOTICE', 'QUANTUMCOMMENTS INSERT DATA SKIPPED BY FILTER ON POST-ID:'.( $post ? $post->ID : 'UNKNOWN' ) );

		if ( array_key_exists( 'comment_meta', $data ) ) {
			$meta = $data['comment_meta'];
			unset( $data['comment_meta'] );
		}

		$this->quantumcomments__insert_data_before( $filtered, $filtered['comment_ID'] );

		$result = $filtered['comment_ID']
			? wp_update_comment( $filtered )
			: wp_insert_comment( $filtered );

		$this->quantumcomments__insert_data_after( $filtered, $filtered['comment_ID'] );

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

	// NOTE: Does final preparations and additions into data before saving.
	// OLD: `subcontent_prep_data_for_save()`
	protected function quantumcomments__prep_data_for_save( $raw, $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL )
	{
		$prefix  = $this->quantumcomments__filter_prefix();
		$mapping = $mapping ?? $this->quantumcomments__get_data_mapping( $context, $post );
		$metas   = $metas ?? $this->quantumcomments__get_meta_mapping( $context, $post );

		if ( is_object( $raw ) )
			$raw = Core\Arraay::fromObject( $raw );

		$raw  = $this->filters( $prefix.'_pre_prep_data', $raw, $context, $post, $mapping, $metas );
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
			? $this->quantumcomments__get_data_count( $post ) + 1
			: $raw['order'];

		return $data;
	}

	protected function quantumcomments__prep_data_from_query( $raw, $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL, $order = NULL )
	{
		$prefix  = $this->quantumcomments__filter_prefix();
		$mapping = $mapping  ?? $this->quantumcomments__get_data_mapping( $context, $post );
		$metas   = $metas ?? $this->quantumcomments__get_meta_mapping( $context, $post );

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

		return $this->filters( $prefix.'_after_prep_data', $data, $context, $post, $mapping, $metas );
	}

	// NOTE: wrapper method in case we had to override the count
	protected function quantumcomments__get_data_count( $parent = NULL, $context = NULL, $extra = [] )
	{
		return $this->quantumcomments__query_data_count( $parent, $extra );
	}

	protected function quantumcomments__define_field_types()
	{
		$types = Core\Arraay::sameKey( array_keys( $this->quantumcomments__define_fields() ) );

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

	protected function quantumcomments__get_field_types( $context = 'display' )
	{
		return $this->filters( 'field_types', $this->quantumcomments__define_field_types(), $context );
	}

	protected function quantumcomments__delete_data_all( $post, $force_delete = TRUE )
	{
		$data = $this->quantumcomments__get_data_all( $post, 'delete', FALSE );

		foreach ( $data as $comment )
			if ( FALSE === wp_delete_comment( $comment, $force_delete ) )
				return FALSE;

		return count( $data );
	}

	protected function quantumcomments__clone_data_all( $from, $to, $fresh = FALSE )
	{
		$data = $this->quantumcomments__get_data_all( $from, 'clone' );

		if ( $fresh && ( FALSE === $this->quantumcomments__delete_data_all( $to ) ) )
			return FALSE;

		foreach ( $data as $row )
			if ( FALSE === $this->quantumcomments__insert_data_row( $this->quantumcomments__copy_data_row( $row ), 'clone', $to ) )
				return FALSE;

		return count( $data );
	}

	protected function quantumcomments__copy_data_row( $data )
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

	// FIXME: **partially** Move to `DeepContents` internal
	// TODO: support for shorthand chars like `+`/`~` in date types to fill with today/now
	// TODO: support for auto-fill fields with tokens: `date: '{{now}}'`
	// OLD: `subcontent_sanitize_data()`
	protected function quantumcomments__sanitize_data( $raw = [], $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL, $allowed_raw = NULL )
	{
		$prefix      = $this->quantumcomments__filter_prefix();
		$mapping     = $mapping ?? $this->quantumcomments__get_data_mapping( $context, $post );
		$metas       = $metas ?? $this->quantumcomments__get_meta_mapping( $context, $post );
		$allowed_raw = $allowed_raw ?? [ 'data', 'order' ];

		$types = $this->quantumcomments__get_field_types( $context );
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

		return $this->filters( $prefix.'_sanitize_data', $data, $context, $post, $raw, $mapping, $metas, $allowed_raw );
	}

	protected function quantumcomments__get_data_all( $parent = NULL, $context = NULL, $map = TRUE, $extra = [] )
	{
		if ( ! $post = WordPress\Post::get( $parent ) )
			return FALSE;

		$args = array_merge( [
			'post_id'   => $post->ID,
			'post_type' => 'any', // $parent->post_type,
			'status'    => 'any', // $this->quantumcomments__get_comment_status(),
			'type'      => $this->quantumcomments__get_comment_type(),
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

		return $map ? $this->quantumcomments__get_data_mapped( $items, $context, $post ) : $items;
	}

	protected function quantumcomments__get_data_mapped( $items, $context = NULL, $post = FALSE, $mapping = NULL, $metas = NULL )
	{
		if ( is_null( $mapping ) )
			$mapping = $this->quantumcomments__get_data_mapping( $context, $post );

		if ( is_null( $metas ) )
			$metas = $this->quantumcomments__get_meta_mapping( $context, $post );

		$data = [];

		foreach ( $items as $offset => $item )
			$data[] = $this->quantumcomments__prep_data_from_query( $item, $context, $post, $mapping, $metas, $offset + 1 );

		return $this->filters( 'subcontent_data_mapped', $data, $context, $post, $items, $mapping, $metas );
	}
}

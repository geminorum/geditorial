<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\WordPress;

trait PostTypeFields
{

	/**
	 * Retrieves a registered field for a post-type.
	 *
	 * @param  string $field_key
	 * @param  string $posttype
	 * @return array  $field
	 */
	public function get_posttype_field_args( $field_key, $posttype )
	{
		if ( ! $posttype || ! $field_key )
			return FALSE;

		$fields = $this->get_posttype_fields( $posttype );
		$field  = array_key_exists( $field_key, $fields )
			? $fields[$field_key]
			: FALSE;

		return $this->filters( 'posttype_field_args', $field, $field_key, $posttype, $fields );
	}

	/**
	 * Retrieves the supported post-tyoes for given field key.
	 *
	 * @param  string $field_key
	 * @return array  $supported
	 */
	public function get_posttype_field_supported( $field_key )
	{
		global $gEditorialPostTypeFields;

		$supported = [];

		if ( $field_key && ! empty( $gEditorialPostTypeFields[$this->key] ) )
			foreach ( $gEditorialPostTypeFields[$this->key] as $posttype => $list )
				if ( array_key_exists( $field_key, $list ) )
					$supported[] = $posttype;

		return $this->filters( 'posttype_field_supported', $supported, $field_key );
	}

	/**
	 * Retrieves the registered fields for a post-type.
	 *
	 * @param  string $posttype
	 * @param  array  $filter
	 * @param  string $operator
	 * @return array  $fields
	 */
	public function get_posttype_fields( $posttype, $filter = [], $operator = 'AND' )
	{
		global $gEditorialPostTypeFields;

		if ( ! $posttype )
			return [];

		if ( ! isset( $gEditorialPostTypeFields[$this->key][$posttype] ) ) {

			$all     = $this->posttype_fields_all( $posttype );
			$enabled = $this->posttype_fields( $posttype );
			$fields  = $this->posttypefields_init_for_posttype( $posttype, $all, $enabled );

			$gEditorialPostTypeFields[$this->key][$posttype] = $fields;
		}

		if ( empty( $filter ) )
			return $gEditorialPostTypeFields[$this->key][$posttype];

		return Core\Arraay::filter( $gEditorialPostTypeFields[$this->key][$posttype], $filter, $operator );
	}

	/**
	 * Initiates the registered fields for a post-type.
	 * NOTE: static contexts: `nobox`, `lonebox`, `mainbox`
	 * NOTE: dynamic contexts: `listbox_{$posttype}`, `pairedbox_{$posttype}`, `pairedbox_{$module}`
	 *
	 * @param  string $posttype
	 * @param  array  $all
	 * @param  array  $enabled
	 * @return array  $fields
	 */
	public function posttypefields_init_for_posttype( $posttype, $all, $enabled )
	{
		$fields  = [];

		foreach ( $enabled as $i => $field ) {

			$args = isset( $all[$field] ) && is_array( $all[$field] ) ? $all[$field] : [];

			if ( ! array_key_exists( 'type', $args ) )
				$args['type'] = 'text';

			if ( ! array_key_exists( 'context', $args ) ) {

				if ( in_array( $args['type'], [ 'postbox_legacy', 'title_before', 'title_after' ] ) )
					$args['context'] = 'nobox'; // OLD: 'raw'

				else if ( in_array( $args['type'], [ 'postbox_html', 'postbox_tiny' ] ) )
					$args['context'] = 'lonebox'; // OLD: 'lone'
			}

			if ( ! array_key_exists( 'default', $args ) ) {

				if ( in_array( $args['type'], [ 'array' ] ) || ! empty( $args['repeat'] ) )
					$args['default'] = [];

				else if ( in_array( $args['type'], [ 'integer', 'number', 'float', 'price' ] ) )
					$args['default'] = 0;

				else
					$args['default'] = '';
			}

			if ( ! array_key_exists( 'ltr', $args ) ) {

				if ( in_array( $args['type'], [ 'code', 'phone', 'mobile', 'contact', 'identity', 'iban', 'bankcard', 'isbn', 'date', 'datetime' ], TRUE ) )
					$args['ltr'] = TRUE;
			}

			if ( ! array_key_exists( 'exclude', $args ) )
				$args['exclude'] = in_array( $args['type'], [ 'parent_post' ] ) ? NULL : FALSE;

			if ( ! array_key_exists( 'quickedit', $args ) )
				$args['quickedit'] = in_array( $args['type'], [ 'title_before', 'title_after' ] );

			if ( ! isset( $args['icon'] ) )
				$args['icon'] = $this->get_posttype_field_icon( $field, $posttype, $args );

			$fields[$field] = self::atts( [
				'type'        => 'text',
				'name'        => $field,
				'rest'        => $field, // FALSE to disable
				'title'       => $this->get_string( $field, $posttype, 'titles', $field ),
				'description' => $this->get_string( $field, $posttype, 'descriptions' ),

				'access_view'   => NULL,   // @SEE: `$this->access_posttype_field()`
				'access_edit'   => NULL,   // @SEE: `$this->access_posttype_field()`
				'access_export' => NULL,   // @SEE: `$this->access_posttype_field()`

				'metakey'     => $this->get_postmeta_key( $field ), // for referencing
				'sanitize'    => NULL, // callback
				'prep'        => NULL, // callback
				'pattern'     => NULL, // HTML5 input pattern
				'default'     => NULL, // currently only on rest
				'datatype'    => NULL, // DataType Class
				'icon'        => 'smiley',
				'context'     => 'mainbox', // OLD: 'main'
				'quickedit'   => FALSE,

				'import'         => TRUE,    // FALSE to hide on imports
				'import_ignored' => FALSE,   // TRUE to make duplicate one taht will ignored on import

				'values'      => $this->get_strings( $field, 'values', $this->get_strings( $args['type'], 'values', [] ) ),
				'none_title'  => $this->get_string( $field, $posttype, 'none', $this->get_string( $args['type'], $posttype, 'none', NULL ) ),
				'none_value'  => '',
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'taxonomy'    => FALSE,
				'posttype'    => NULL,
				'exclude'     => FALSE, // `NULL` means parent post
				'role'        => FALSE,
				'group'       => 'general',
				'order'       => 1000 + $i,
			], $args );

			$this->actions( sprintf( 'init_posttype_field_%s', $field ), $fields[$field], $field, $posttype );
		}

		return Core\Arraay::multiSort( $fields, [
			'group' => SORT_ASC,
			'order' => SORT_ASC,
		] );
	}

	/**
	 * Checks for accessing a posttype field.
	 *
	 * $arg `TRUE`/`FALSE` for public/private
	 * $arg `NULL` for posttype `read`/`edit_post` capability check
	 * $arg String for strait capability check
	 *
	 * @param  array    $field
	 * @param  mixed    $post
	 * @param  string   $context
	 * @param  null|int $user_id
	 * @return bool     $access
	 */
	public function access_posttype_field( $field, $post = NULL, $context = 'view', $user_id = NULL )
	{
		if ( ! $field )
			return FALSE; // no field, no access!

		$context = in_array( $context, [ 'view', 'edit', 'export' ], TRUE ) ? $context : 'view';
		$access  = array_key_exists( 'access_'.$context, $field )
			? $field['access_'.$context] : NULL;

		if ( TRUE !== $access && FALSE !== $access ) {

			if ( is_null( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! is_null( $access ) ) {

				$access = user_can( $user_id, $access );

			} else if ( $post = WordPress\Post::get( $post ) ) {

				if ( WordPress\SwitchSite::is()
					|| NULL === WordPress\PostType::object( $post ) ) {

					/**
					 * falls back to `post` if posttype is not registered
					 */

					$access = in_array( $context, [ 'edit' ], TRUE )
						? WordPress\PostType::can( 'post', 'edit_posts', $user_id )
						: WordPress\PostType::viewable( 'post' );
						// @SEE: https://core.trac.wordpress.org/ticket/50123
						// : WordPress\PostType::can( 'post', 'read', $user_id );

				} else {

					/**
					 * this is cap check fallback to the parent post
					 * each field is go through this check individually
					 * so no need to check for `edit_post_meta` for the field:
					 * `user_can( $user_id, 'edit_post_meta', $post->ID, $metakey )`
					 * @REF: `register_auth_callback_posttypefields()`
					 */

					$access = in_array( $context, [ 'edit' ], TRUE )
						? WordPress\Post::can( $post, 'edit_post', $user_id )
						: WordPress\Post::viewable( $post );
						// @SEE: https://core.trac.wordpress.org/ticket/50123
						// : WordPress\Post::can( $post, 'read_post', $user_id );
				}

			} else {

				// no post, no access!
				$access = FALSE;
			}
		}

		return $this->filters( 'access_posttype_field', $access, $field, $post, $context, $user_id );
	}

	/**
	 * Sanitizes given data for a post-type field.
	 *
	 * @param  mixed $data
	 * @param  array $field
	 * @param  mixed $post
	 * @return mixed $sanitized
	 */
	public function sanitize_posttype_field( $data, $field, $post = FALSE )
	{
		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) )
			return $this->filters( 'sanitize_posttype_field',
				call_user_func_array( $field['sanitize'], [ $data, $field, $post ] ),
				$field, $post, $data );

		$sanitized = $data;

		switch ( $field['type'] ) {

			case 'post':
			case 'attachment':
			case 'parent_post':

				if ( ! empty( $data ) && ( $object = get_post( (int) $data ) ) )
					$sanitized = $object->ID;

				else
					$sanitized = FALSE;

				break;

			case 'posts':
			case 'attachments':

				$sanitized = Core\Arraay::prepNumeral( $data );
				// $sanitized = array_filter( $sanitized, 'get_post' );

				if ( empty( $sanitized ) )
					$sanitized = FALSE;

				break;

			case 'user':

				if ( ! empty( $data ) && ( $object = get_user_by( 'id', (int) $data ) ) )
					$sanitized = $object->ID;

				else
					$sanitized = FALSE;

				break;

			case 'term':

				// TODO: use `WordPress\Term::get( $data, $field['taxonomy'] )`
				$sanitized = empty( $data ) ? FALSE : (int) $data;

			break;

			case 'embed':
			case 'text_source':
			case 'audio_source':
			case 'video_source':
			case 'image_source':
			case 'downloadable':
			case 'link':
				$sanitized = trim( $data );

 				// @SEE: `esc_url()`
				if ( $sanitized && ! preg_match( '/^http(s)?:\/\//', $sanitized ) )
					$sanitized = 'http://'.$sanitized;
				break;

			case 'postcode':
				$sanitized = Core\Validation::sanitizePostCode( $data );
				break;

			case 'code':
				$sanitized = trim( $data );

			break;
			case 'email':
				$sanitized = sanitize_email( trim( $data ) );

			break;
			case 'contact':
				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				break;

			case 'identity':
				$sanitized = Core\Validation::sanitizeIdentityNumber( $data );
				break;

			case 'isbn':
				$sanitized = Core\ISBN::sanitize( $data, TRUE );
				break;

			case 'iban':
				$sanitized = Core\Validation::sanitizeIBAN( $data );
				break;

			case 'bankcard':
				$sanitized = Core\Validation::sanitizeCardNumber( $data );
				break;

			case 'phone':
				$sanitized = Core\Phone::sanitize( $data );
				break;

			case 'mobile':
			 	$sanitized = Core\Mobile::sanitize( $data );
				break;

			case 'date':

				$sanitized = Core\Number::intval( trim( $data ), FALSE );

				// avoid accepting year only
				if ( strlen( $sanitized ) > 4 )
					$sanitized = Datetime::makeMySQLFromInput( $sanitized, 'Y-m-d', $this->default_calendar(), NULL, $sanitized );

				else
					$sanitized = FALSE;

					break;

			case 'time':
				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				break;

			case 'datetime':

				// @SEE: https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#dates

				$sanitized = Core\Number::intval( trim( $data ), FALSE );
				$sanitized = Datetime::makeMySQLFromInput( $sanitized, NULL, $this->default_calendar(), NULL, $sanitized );
				break;

			case 'member':
			case 'person':
			case 'day':
			case 'hour':
			case 'gram':
			case 'milimeter':
			case 'kilogram':
			case 'centimeter':
			case 'price':
			case 'number':
				$sanitized = Core\Number::intval( trim( $data ) );

			break;
			case 'float':
				$sanitized = Core\Number::floatval( trim( $data ) );

			break;
			case 'text':
			case 'venue':
			case 'datestring':
			case 'title_before':
			case 'title_after':
				$sanitized = trim( Helper::kses( $data, 'none' ) );

			break;
			case 'address':
			case 'note':
			case 'textarea':
			case 'widget': // FIXME: maybe general note fields displayed by a meta widget: `primary`/`side notes`
				$sanitized = trim( Helper::kses( $data, 'text' ) );

			break;
			case 'postbox_legacy':
			case 'postbox_tiny':
			case 'postbox_html':
				$sanitized = trim( Helper::kses( $data, 'html' ) );
		}

		return $this->filters( 'sanitize_posttype_field', $sanitized, $field, $post, $data );
	}

	// NOTE: `$data` maybe empty
	protected function posttypefields__do_action_import_data( $post, $data, $override = FALSE, $check_access = TRUE, $module = 'meta' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		do_action( sprintf( '%s_posttypefields_import_raw_data', $this->base ), $post, $data, $override, $check_access, $module );
	}

	protected function posttypefields_get_post_by( $field_key, $value, $posttype_constant, $sanitize = FALSE, $module = 'meta' )
	{
		if ( ! $field_key || ! $value || ! $posttype_constant || ! gEditorial()->enabled( $module ) )
			return FALSE;

		$metakey  = gEditorial()->module( $module )->get_postmeta_key( $field_key );
		$posttype = $this->constant( $posttype_constant, $posttype_constant );

		if ( $sanitize ) {

			if ( ! $field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $posttype ) )
				$value = Core\Number::intval( trim( $value ), FALSE );

			else
				$value = gEditorial()->module( $module )->sanitize_posttype_field( $value, $field );

			if ( ! $value )
				return FALSE;
		}

		if ( $matches = WordPress\PostType::getIDbyMeta( $metakey, $value, FALSE ) )
			foreach ( $matches as $match )
				if ( $posttype === get_post_type( intval( $match ) ) )
					return intval( $match );

		return FALSE;
	}

	protected function posttypefields_connect_paired_by( $field_key, $data, $post )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$values = Helper::getSeparated( $data );
		$list   = [];

		foreach ( $values as $value )
			if ( $parent = $this->posttypefields_get_post_by( $field_key, $value, $constants[0], TRUE ) )
				$list[] = $parent;

		if ( count( $list ) )
			$this->paired_do_connection( 'store',
				$post,
				$list,
				$constants[0],
				$constants[1],
				$this->get_setting( 'multiple_instances' )
			);

		return $list;
	}

	public function get_postid_by_field( $value, $field, $prefix = NULL )
	{
		if ( is_null( $prefix ) )
			$prefix = 'meta'; // the exception!

		if ( $post_id = WordPress\PostType::getIDbyMeta( $this->get_postmeta_key( $field, $prefix ), $value ) )
			return intval( $post_id );

		return FALSE;
	}
}

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

trait PostTypeFields
{

	/**
	 * Retrieves a registered field for a post-type.
	 *
	 * @param string $field_key
	 * @param string $posttype
	 * @return array
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
	 * Retrieves the export title for given field key.
	 *
	 * @param string $field_key
	 * @return string
	 */
	public function get_posttype_field_export_title( $field_key, $posttype )
	{
		if ( ! $field = $this->get_posttype_field_args( $field_key, $posttype ) )
			return $field;

		if ( ! empty( $field['export_title'] ) ) {

			$export_title = $field['export_title'];

		} else if ( ! empty( $field['data_unit'] ) ) {

			$unit_info    = Info::getUnit( $field['data_unit'], '' );
			$export_title = Core\Text::trim( sprintf( '%s (%s)', $field['title'], $unit_info[0] ) );

		} else if ( ! empty( $field['title'] ) ) {

			$export_title = $field['title'];

		} else {

			$export_title = $field_key;
		}

		return $this->filters( 'posttype_field_export_title', $export_title, $field_key, $field );
	}

	/**
	 * Retrieves the supported post-types for given field key.
	 *
	 * @param string $field_key
	 * @return array
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
	 * @param string $posttype
	 * @param array $filter
	 * @param string $operator
	 * @return array
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
	 * @param string $posttype
	 * @param array $all
	 * @param array $enabled
	 * @return array
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

				if ( in_array( $args['type'], [ 'code', 'phone', 'mobile', 'contact', 'identity', 'iban', 'bankcard', 'isbn', 'vin', 'year', 'date', 'datetime', 'distance', 'duration', 'area' ], TRUE ) )
					$args['ltr'] = TRUE;
			}

			if ( ! array_key_exists( 'data_length', $args ) ) {

				if ( in_array( $args['type'], [ 'date', 'identity' ], TRUE ) )
					$args['data_length'] = 10;

				else if ( in_array( $args['type'], [ 'bankcard' ], TRUE ) )
					$args['data_length'] = 16;

				else if ( in_array( $args['type'], [ 'phone', 'mobile' ], TRUE ) )
					$args['data_length'] = 13;

				else if ( in_array( $args['type'], [ 'iban' ], TRUE ) )
					$args['data_length'] = 26;

				else if ( in_array( $args['type'], [ 'gram', 'millimetre', 'kilogram', 'centimetre', 'km_per_hour', 'european_shoe', 'international_shirt', 'international_pants', 'day', 'hour', 'member', 'person' ], TRUE ) )
					$args['data_length'] = 4;
			}

			if ( ! array_key_exists( 'exclude', $args ) )
				$args['exclude'] = in_array( $args['type'], [ 'parent_post' ] ) ? NULL : FALSE;

			if ( ! array_key_exists( 'quickedit', $args ) )
				$args['quickedit'] = in_array( $args['type'], [ 'title_before', 'title_after' ] );

			if ( ! array_key_exists( 'bulkedit', $args ) ) {

				if ( in_array( $args['type'], [ 'title_before', 'title_after', 'isbn', 'iban', 'bankcard', 'identity', 'postcode', 'email', 'contact', 'phone', 'mobile' ], TRUE ) )
					$args['bulkedit'] = FALSE;
			}

			if ( ! isset( $args['icon'] ) )
				$args['icon'] = Services\PostTypeFields::getFieldIcon( $field, $args, $posttype );

			if ( array_key_exists( 'autocomplete', $args ) && FALSE === $args['autocomplete'] )
				$args['autocomplete'] = 'off';

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
				'pattern'     => NULL, // HTML input pattern
				'default'     => NULL, // currently only on rest
				'datatype'    => NULL, // DataType Class
				'icon'        => 'smiley',
				'context'     => 'mainbox', // OLD: 'main'
				'quickedit'   => FALSE,
				'bulkedit'    => NULL, // NULL to fallback to `quickedit`

				'import'         => TRUE,    // FALSE to hide on imports
				'import_ignored' => FALSE,   // TRUE to make duplicate one that will ignored on import
				'export_title'   => NULL,    // the export column title
				'data_unit'      => NULL,    // The unit which in the data is stored
				'data_length'    => NULL,    // Typical length of the data // FIXME: implement this!
				'autocomplete'   => 'off',   // NULL to drop the attribute // FIXME: implement this!
				// 'discovery'      => FALSE,   // REGEX string or callback array

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
	 * Checks for accessing a post-type field.
	 *
	 * `$arg` `TRUE`/`FALSE` for public/private
	 * `$arg` `NULL` for post-type `read`/`edit_post` capability check
	 * `$arg` String for strait capability check
	 *
	 * @param array $field
	 * @param mixed $post
	 * @param string $context
	 * @param null|int $user_id
	 * @return bool
	 */
	public function access_posttype_field( $field, $post = NULL, $context = 'view', $user_id = NULL )
	{
		if ( ! $field )
			return FALSE; // no field, no access!

		$context = in_array( $context, [ 'view', 'edit', 'export' ], TRUE ) ? $context : 'view';
		$access  = $original = array_key_exists( 'access_'.$context, $field )
			? $field['access_'.$context] : NULL;

		if ( TRUE !== $access && FALSE !== $access ) {

			if ( is_null( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! is_null( $access ) ) {

				if ( in_array( $access, [ 'edit_post', 'read_post', 'delete_post' ], TRUE ) ) {

					if ( $post = WordPress\Post::get( $post ) ) {

						$access = user_can( $user_id, $access, $post->ID );

					} else {

						// no post, no access!
						$access = FALSE;
					}

				} else {

					$access = user_can( $user_id, $access );
				}

			} else if ( $post = WordPress\Post::get( $post ) ) {

				if ( WordPress\SwitchSite::is()
					|| NULL === WordPress\PostType::object( $post ) ) {

					/**
					 * Falls back to `post` if post-type is not registered.
					 */

					$access = in_array( $context, [ 'edit' ], TRUE )
						? WordPress\PostType::can( 'post', 'edit_posts', $user_id )
						: WordPress\PostType::viewable( 'post' );
						// @SEE: https://core.trac.wordpress.org/ticket/50123
						// : WordPress\PostType::can( 'post', 'read', $user_id );

				} else {

					/**
					 * This is cap check fallback to the parent post
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

		return $this->filters( 'access_posttype_field', $access, $field, $post, $context, $user_id, $original );
	}

	/**
	 * Sanitizes given data for a post-type field.
	 *
	 * @param mixed $data
	 * @param array $field
	 * @param mixed $post
	 * @return mixed $sanitized
	 */
	public function sanitize_posttype_field( $data, $field, $post = FALSE )
	{
		if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) )
			return $this->filters( 'sanitize_posttype_field',
				call_user_func_array( $field['sanitize'], [ $data, $field, $post ] ),
				$field, $post, $data );

		$sanitized = $data;

		// TODO: support for shorthand chars like `+`/`~`/`?` in date types to fill with today/now

		switch ( $field['type'] ) {

			case 'post':
			case 'attachment':
			case 'parent_post':

				if ( ! empty( $data ) && ( $object = WordPress\Post::get( (int) $data ) ) )
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

			case 'venue':
			case 'people':

				$sanitized = WordPress\Strings::kses( $data, 'none' ) ;
				$sanitized = WordPress\Strings::getPiped( Helper::getSeparated( $sanitized ) );
				break;

			case 'embed':
			case 'text_source':
			case 'audio_source':
			case 'video_source':
			case 'image_source':
			case 'downloadable':
			case 'link':

				$sanitized = Core\URL::sanitize( $data );
				break;

			case 'postcode':

				$sanitized = Core\PostCode::sanitize( $data );
				break;

			case 'code':

				$sanitized = trim( $data );
				break;

			case 'email':

				$sanitized = Core\Email::sanitize( Core\Text::trim( $data ) );
				break;

			case 'contact':

				$sanitized = Core\Number::translate( Core\Text::trim( $data ) );
				break;

			case 'identity':

				$sanitized = Core\Validation::sanitizeIdentityNumber( $data );
				break;

			case 'isbn':

				$sanitized = Core\ISBN::sanitize( $data );
				break;

			case 'vin':

				$sanitized = Core\Validation::sanitizeVIN( $data );
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

			case 'year':

				$sanitized = Core\Number::translate( Core\Text::trim( $data ) );

				if ( strlen( $sanitized ) > 4 )
					$sanitized = substr( $sanitized, 0, 4 );

				break;

			case 'date':

				$sanitized = Core\Number::translate( Core\Text::trim( $data ) );

				// accepts year only
				if ( strlen( $sanitized ) > 4 )
					$sanitized = Datetime::makeMySQLFromInput( $sanitized, 'Y-m-d', $this->default_calendar(), NULL, FALSE );

				break;

			case 'time':

				$sanitized = Core\Number::translate( Core\Text::trim( $data ) );
				break;

			case 'datetime':

				// @SEE: https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#dates

				$sanitized = Core\Number::translate( Core\Text::trim( $data ) );
				$sanitized = Datetime::makeMySQLFromInput( $sanitized, NULL, $this->default_calendar(), NULL, FALSE );
				break;

			case 'latlng':

				$sanitized = Core\Geography::sanitizeLatLng( $data );
				break;

			case 'distance':

				$sanitized = Core\Distance::sanitize( $data, '', $field );
				break;

			case 'duration':

				$sanitized = Core\Duration::sanitize( $data );
				break;

			case 'area':

				$sanitized = Core\Area::sanitize( $data );
				break;

			case 'member':
			case 'person':
			case 'day':
			case 'hour':
			case 'gram':
			case 'kilogram':
			case 'km_per_hour':
			case 'millimetre':
			case 'centimetre':
			case 'metre':
			case 'kilometre':
			case 'price':
			case 'number':

				$sanitized = Core\Number::intval( $data );
				break;

			case 'float':

				$sanitized = Core\Number::floatval( $data );
				break;

			case 'text':
			case 'datestring':
			case 'title_before':
			case 'title_after':

				$sanitized = WordPress\Strings::kses( $data, 'none' );
				break;

			case 'address':
			case 'note':
			case 'textarea':
			case 'widget': // FIXME: maybe general note fields displayed by a meta widget: `primary`/`side notes`

				$sanitized = WordPress\Strings::kses( $data, 'text' );
				break;

			case 'postbox_legacy':
			case 'postbox_tiny':
			case 'postbox_html':

				$sanitized = WordPress\Strings::kses( $data, 'html' );
		}

		return $this->filters( 'sanitize_posttype_field', $sanitized, $field, $post, $data );
	}

	protected function posttypefields__hook_metabox( $screen, $fields = NULL, $context = NULL )
	{
		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( is_null( $context ) )
			$fields = $this->get_posttype_fields( $screen->post_type );

		// Bail if no fields enabled for this post-type
		if ( ! count( $fields ) )
			return FALSE;

		$callback = $this->filters( sprintf( '%s_callback', $context ),
			in_array( $context, Core\Arraay::column( $fields, 'context' ), TRUE ),
			$screen->post_type
		);

		if ( TRUE === $callback )
			$callback = [ $this, 'render_metabox_posttypefields' ];

		if ( $callback && is_callable( $callback ) )
			add_meta_box(
				$this->classs( $screen->post_type ),
				$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
				$callback,
				$screen,
				'side',
				'high',
				[
					'module'     => $this->module->name,
					'context'    => $context,
					'fields'     => $fields,
					'posttype'   => $screen->post_type,
				]
			);

		// NOTE: WARNING: also we have: `$this->_hook_store_metabox( $posttype, 'posttypefields' );`
		add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_metabox_posttypefields' ], 20, 3 );
		add_action( $this->hook( 'render_metabox' ), [ $this, 'render_posttype_fields' ], 10, 4 );
	}

	public function render_metabox_posttypefields( $post, $box )
	{
		echo $this->wrap_open( '-admin-metabox' );

		if ( $this->check_hidden_metabox( $box, $post->post_type, '</div>' ) )
			return;

		$fields  = $box['args']['fields'];
		$context = $box['args']['context'];

		if ( count( $fields ) )
			$this->actions( 'render_metabox', $post, $box, $fields, $context );

		else
			echo Core\HTML::wrap(
				$this->get_string( 'no_fields', $context, 'notices', gEditorial\Plugin::noinfo( FALSE ) ),
				'field-wrap -empty'
			);

		$this->actions( 'render_metabox_after', $post, $box, $fields, $context );

		echo '</div>';
	}

	public function render_posttype_fields( $post, $box = FALSE, $fields = NULL, $context = NULL, $before = '', $after = '' )
	{
		$user_id = get_current_user_id();

		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			// TODO: maybe display disabled input
			if ( ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			echo $before;

			switch ( $args['type'] ) {

				case 'european_shoe':
				case 'international_shirt':
				case 'international_pants':
				case 'bookcover':
				case 'papersize':
				case 'select':

					MetaBox::renderFieldSelect( $args, $post, $this->module->name );
					break;

				case 'title_before':
				case 'title_after':
				case 'text':
				case 'datestring':
				case 'year':
				case 'date':
				case 'datetime':
				case 'distance': // @SEE https://github.com/lvivier/meters/blob/master/index.js
				case 'duration':
				case 'area':
				case 'identity':
				case 'isbn':
				case 'vin':
				case 'iban':
				case 'bankcard':
				case 'code':
				case 'postcode':
				case 'venue':
				case 'people':
				case 'contact':
				case 'mobile':
				case 'phone':
				case 'email':
				case 'embed':
				case 'text_source':
				case 'audio_source':
				case 'video_source':
				case 'image_source':
				case 'downloadable':
				case 'link':
				case 'latlng':

					MetaBox::renderFieldInput( $args, $post, $this->module->name );
					break;

				case 'float':
				case 'price': // TODO must use custom text input + code + ortho-number + separator
				case 'number':
				case 'member':
				case 'person':
				case 'day':
				case 'hour':
				case 'gram':
				case 'km_per_hour':
				case 'millimetre':
				case 'kilogram':
				case 'centimetre':
				case 'metre':
				case 'kilometre':

					MetaBox::renderFieldNumber( $args, $post, $this->module->name );
					break;

				case 'widget': // WTF?!
				case 'address':
				case 'note':
				case 'textarea':

					MetaBox::renderFieldTextarea( $args, $post, $this->module->name );
					break;

				case 'parent_post':

					MetaBox::renderFieldPostParent( $args, $post, $this->module->name );
					break;

				case 'user':

					MetaBox::renderFieldUser( $args, $post, $this->module->name );
					break;

				case 'attachment':

					// MetaBox::renderFieldAttachment( $args, $post, $this->module->name ); // FIXME
					MetaBox::renderFieldNumber( $args, $post, $this->module->name );
					break;

				case 'post':

					MetaBox::renderFieldPost( $args, $post, $this->module->name );
					break;

				case 'term':

					if ( ! $args['taxonomy'] )
						break;

					if ( ! WordPress\Taxonomy::can( $args['taxonomy'], 'assign_terms' ) )
						break;

					if ( ! $count = WordPress\Taxonomy::hasTerms( $args['taxonomy'] ) )
						break;

					if ( $count > 15 ) // WTF: customize this!
						MetaBox::renderFieldTerm( $args, $post, $this->module->name );

					else
						MetaBox::renderFieldSelect( $args, $post, $this->module->name );
			}

			echo $after;
		}

		if ( 'mainbox' !== $context )
			return;

		$this->nonce_field( $context );
	}

	// OLD: `store_metabox()`
	public function store_metabox_posttypefields( $post_id, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		if ( ! $this->nonce_verify( 'mainbox' )
			&& ! $this->nonce_verify( 'nobox' ) )
				return;

		// here only check for cap to edit this post
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$this->store_posttype_fields( $post );
	}

	public function bulk_edit_posts_posttypefields( $updated, $data )
	{
		foreach ( $updated as $post_id )
			$this->store_posttype_fields( WordPress\Post::get( $post_id ), FALSE );
	}

	protected function store_posttype_fields( $post, $override = TRUE )
	{
		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! count( $fields ) )
			return;

		$user_id  = get_current_user_id();
		$legacy   = in_array( $this->module->name, [ 'meta' ], TRUE );
		$legacies = $legacy ? $this->get_postmeta_legacy( $post->ID ) : [];

		foreach ( $fields as $field => $args ) {

			// skip for fields that are auto-saved on admin edit-post page
			if ( in_array( $field, [ 'parent_post' ], TRUE ) )
				continue;

			if ( ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			$request = sprintf( '%s-%s-%s', $this->base, $this->module->name, $field );

			if ( FALSE !== ( $data = self::req( $request, FALSE ) ) )
				$this->posttypefields_do_import_field( $data, $args, $post, $override );

			// passing not enabled legacy data
			else if ( $legacy && array_key_exists( $field, $legacies ) )
				$this->set_postmeta_field( $post->ID, $field, $this->sanitize_posttype_field( $legacies[$field], $args, $post ) );
		}

		if ( $legacy )
			$this->clean_postmeta_legacy( $post->ID, $fields, $legacies );
	}

	protected function posttypefields__enqueue_edit_screen( $posttype, $fields = NULL )
	{
		// NOTE: this is wp-core hook
		if ( ! apply_filters( 'quick_edit_enabled_for_post_type', TRUE, $posttype ) )
			return FALSE;

		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $posttype );

		if ( ! $quickedits = Core\Arraay::filter( $fields, [ 'quickedit' => TRUE ] ) )
			return FALSE;

		$this->enqueue_asset_js( [
			'fields' => Core\Arraay::pluck( $quickedits, 'type', 'name' ),
		], $this->dotted( 'edit' ) );
	}

	protected function posttypefields__hook_setup_ajax( $posttype )
	{
		if ( ! $this->get_posttype_fields( $posttype ) )
			return;

		$this->posttypefields__hook_edit_screen( $posttype );
		$this->_hook_store_metabox( $posttype, 'posttypefields' );
	}

	protected function posttypefields__hook_edit_screen( $posttype )
	{
		if ( Core\WordPress::isWPcompatible( '6.3.0' ) ) {
			$this->action( 'bulk_edit_posts', 2, 12, 'posttypefields' );
			$this->action( 'bulk_edit_custom_box', 2, 12, 'posttypefields' );
		}

		$this->action( 'quick_edit_custom_box', 2, 12, 'posttypefields' );
		$this->filter( 'manage_posts_columns', 2, 15, 'posttypefields' );
		$this->filter( 'manage_pages_columns', 1, 15, 'posttypefields' );

		add_action( 'manage_'.$posttype.'_posts_custom_column',
			[ $this, 'posts_custom_column_posttypefields' ], 10, 2 );

		$this->posttypefields__hook_default_rows( $posttype );
	}

	public function bulk_edit_custom_box_posttypefields( $column_name, $posttype )
	{
		$this->quick_edit_custom_box_posttypefields( $column_name, $posttype, TRUE );
	}

	public function quick_edit_custom_box_posttypefields( $column_name, $posttype, $bulkedit = FALSE )
	{
		if ( $this->classs() != $column_name )
			return FALSE;

		$fields = $this->get_posttype_fields( $posttype );
		$prefix = $this->classs(); // to protect key underlines

		foreach ( $fields as $field => $args ) {

			$render = $args['quickedit'];

			if ( $bulkedit && ! is_null( $args['bulkedit'] ) )
				$render = $args['bulkedit'];

			if ( ! $render )
				continue;

			// NOTE: no need for access checks: just input/not value

			$hint  = sprintf( '%s :: %s', $args['title'] ?: $args['name'], $args['description'] ?: '' );
			$class = $prefix.'-'.( $bulkedit ? 'bulkedit' : 'quickedit' ).'-'.$field;
			$name  = $prefix.'-'.$field;

			switch ( $args['type'] ) {

				case 'address':
				case 'note':
				case 'textarea':

					echo '<label title="'.Core\HTML::escape( $hint ).'">';
						echo '<span class="title">'.$args['title'].'</span>';
						echo '<span class="input-text-wrap">';
						echo '<textarea name="'.Core\HTML::escape( $name ).'" value=""';
						echo ' class="'.Core\HTML::prepClass( $class ).'"';
						echo $args['autocomplete'] ? ( ' autocomplete="'.$args['autocomplete'].'"') : '';
						echo $args['pattern'] ? ( ' pattern="'.$args['pattern'].'"' ) : '';
						echo $args['ltr'] ? ' dir="ltr"' : '';
						echo ' rows="1" /></textarea></span>';
					echo '</label>';

					break;

				default:

					echo '<label title="'.Core\HTML::escape( $hint ).'">';
						echo '<span class="title">'.$args['title'].'</span>';
						echo '<span class="input-text-wrap">';
						echo '<input name="'.Core\HTML::escape( $name ).'" value=""';
						echo ' class="'.Core\HTML::prepClass( $class ).'"';
						echo $args['autocomplete'] ? ( ' autocomplete="'.$args['autocomplete'].'"') : '';
						echo $args['pattern'] ? ( ' pattern="'.$args['pattern'].'"' ) : '';
						echo $args['ltr'] ? ' dir="ltr"' : '';
						echo $args['type'] === 'number' ? ' type="number" ' : ' type="text" ';
						echo '></span>';
					echo '</label>';
			}
		}

		$this->nonce_field( $bulkedit ? 'bulkbox' : 'nobox' );
	}

	public function manage_pages_columns_posttypefields( $columns )
	{
		return $this->manage_posts_columns_posttypefields( $columns, 'page' );
	}

	public function manage_posts_columns_posttypefields( $columns, $posttype )
	{
		// meta only
		// if ( in_array( 'byline', $this->posttype_fields( $posttype ) ) )
		// 	unset( $columns['author'] );

		$position = $this->posttypefields_custom_column_position();

		return Core\Arraay::insert( $columns, [
			$this->classs() => $this->get_column_title( $this->module->name, $posttype ),
		], $position[0], $position[1] );
	}

	public function posts_custom_column_posttypefields( $column_name, $post_id )
	{
		if ( $this->classs() != $column_name )
			return;

		if ( ! $post = WordPress\Post::get( $post_id ) )
			return;

		$prefix   = $this->classs().'-value-';
		$user_id  = get_current_user_id();
		$fields   = $this->get_posttype_fields( $post->post_type );
		$excludes = $this->posttypefields_custom_column_excludes( $fields );

		echo '<div class="geditorial-admin-wrap-column -'.$this->module->name.'"><ul class="-rows">';

			// NOTE: DEPRECATED
			$this->actions( 'column_row', $post, $fields, $excludes );

			do_action( $this->hook( 'column_row', $post->post_type ),
				$post,
				$this->wrap_open_row( 'attr', [
					'-column-attr',
					'-type-'.$post->post_type,
					'%s', // to use by caller
				] ),
				'</li>',
				$this->module->name,
				$fields,
				$excludes
			);

		echo '</ul></div>';

		// TODO: use `$this->hidden()`
		// TODO: move this to `add_inline_data` action hook
		// NOTE: for `quickedit` enabled fields
		foreach ( Core\Arraay::filter( $fields, [ 'quickedit' => TRUE ] ) as $field => $args )
			echo '<div data-disabled="'.( $this->access_posttype_field( $args, $post, 'edit', $user_id ) ? 'false' : 'true' )
				.'" class="hidden '.$prefix.$field.'">'.
				$this->posttypefields_prep_posttype_field_for_input(
					$this->get_postmeta_field( $post->ID, $field ),
					$field,
					$args
				).'</div>';
	}

	// NOTE: for more `MetaBox::renderFieldInput()`
	protected function posttypefields_prep_posttype_field_for_input( $value, $field_key, $field )
	{
		if ( empty( $field['type'] ) )
			return $value;

		switch ( $field['type'] ) {
			case 'date'    : return $value ? Datetime::prepForInput( $value, 'Y/m/d', 'gregorian' )     : $value;
			case 'datetime': return $value ? Datetime::prepForInput( $value, 'Y/m/d H:i', 'gregorian' ) : $value;
			case 'distance': return $value ? Core\Distance::prep( $value, $field, 'input' )             : $value;
			case 'duration': return $value ? Core\Duration::prep( $value, $field, 'input' )             : $value;
			case 'area'    : return $value ? Core\Area::prep( $value, $field, 'input' )                 : $value;
		}

		return $value;
	}

	protected function posttypefields_custom_column_position()
	{
		return [ 'comments', 'before' ];
	}

	// NOTE: excludes are for other modules
	protected function posttypefields_custom_column_excludes( $fields )
	{
		return array_keys( $fields ); // By default we display all fields
	}

	// NOTE: helps devise actions to make room for other modules
	protected function posttypefields__hook_default_rows( $posttype )
	{
		// By default we display all fields
		add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_all_posttypefields' ], 5, 6 );

		// For EXAMPLE: display quick-edit only
		// `add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_quickedit_posttypefields' ], 5, 6 );`
	}

	public function column_row_all_posttypefields( $post, $before, $after, $module, $fields, $excludes )
	{
		foreach ( $fields as $field_key => $field ) {

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key ) )
				continue;

			printf( $before, sprintf( '-%s-%s', $module, $field_key ) );
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo $after;
		}
	}

	// NOTE: only renders `quickedit` enabled fields
	public function column_row_quickedit_posttypefields( $post, $before, $after, $module, $fields, $excludes )
	{
		foreach ( $fields as $field_key => $field ) {

			if ( ! $field['quickedit'] )
				continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key ) )
				continue;

			printf( $before, sprintf( '-%s-%s', $module, $field_key ) );
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo $after;
		}
	}

	// NOTE: `$data` maybe empty
	protected function posttypefields__do_action_import_data( $post, $data, $override = FALSE, $check_access = TRUE, $module = 'meta' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		do_action( $this->hook_base( 'posttypefields_import_raw_data' ), $post, $data, $override, $check_access, $module );
	}

	// NOTE: DEPRECATED
	protected function posttypefields_get_post_by( $field_key, $value, $posttype_constant, $sanitize = FALSE, $module = 'meta' )
	{
		self::_dep( 'Services\PostTypeFields::getPostByField()' );

		if ( ! $field_key || ! $value || ! $posttype_constant || ! gEditorial()->enabled( $module ) )
			return FALSE;

		$metakey  = gEditorial()->module( $module )->get_postmeta_key( $field_key );
		$posttype = $this->constant( $posttype_constant, $posttype_constant );

		if ( $sanitize ) {

			if ( ! $field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $posttype ) )
				$value = Core\Number::translate( trim( $value ) );

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

		$type   = $this->constant( $constants[0] );
		$values = Helper::getSeparated( $data );
		$list   = [];

		foreach ( $values as $value )
			if ( $parent = Services\PostTypeFields::getPostByField( $field_key, $value, $type, TRUE ) )
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

	// NOTE: DEPRECATED
	public function get_postid_by_field( $value, $field, $prefix = NULL )
	{
		self::_dep( 'Services\PostTypeFields::getPostByField()' );

		if ( is_null( $prefix ) )
			$prefix = 'meta'; // the exception!

		if ( $post_id = WordPress\PostType::getIDbyMeta( $this->get_postmeta_key( $field, $prefix ), $value ) )
			return intval( $post_id );

		return FALSE;
	}

	protected function posttypefields_support_posttypes()
	{
		$posttypes = [ 'post' ];
		$supported = get_post_types_by_support( sprintf( 'editorial-%s', $this->key ) );
		$excludes  = [
			'attachment',
			'page',
		];

		$list = array_diff( array_merge( $posttypes, $supported ), $excludes );

		return $this->filters( 'support_posttypes', $list );
	}

	// OLD: `init_meta_fields()`
	protected function posttypefields_init_meta_fields()
	{
		foreach ( $this->posttypefields_support_posttypes() as $posttype )
			$this->add_posttype_fields( $posttype, $this->fields[$this->key]['_supported'], TRUE, $this->key );

		$this->add_posttype_fields( 'page', $this->fields[$this->key]['page'] );

		$this->action( 'wp_loaded', 0, 9, 'posttypefields' );
	}

	public function wp_loaded_posttypefields()
	{
		// Initiate the post-type fields for each post-type
		foreach ( $this->posttypes() as $posttype )
			$this->get_posttype_fields( $posttype );

		$this->fields = NULL; // unload initial data
	}

	// OLD: `register_meta_fields()`
	protected function posttypefields_register_meta_fields()
	{
		$this->filter( 'pairedrest_prepped_post', 3, 9, 'posttypefields', $this->base );
		$this->filter( 'pairedimports_import_types', 4, 5, 'posttypefields', $this->base );

		$attribute = $this->constant( 'restapi_attribute', sprintf( '%s_rendered', $this->key ) );
		$is_rest   = Core\WordPress::isREST();

		foreach ( $this->posttypes() as $posttype ) {

			/**
			 * Registering general field for all meta-data
			 * mainly for display purposes
			 */
			register_rest_field( $posttype, $attribute, [
				'get_callback' => [ $this, 'attribute_get_callback_posttypefields' ],
			] );

			/**
			 * The post-type must have `custom-fields` support
			 * otherwise the meta fields will not appear in the REST API
			 */
			if ( ! post_type_supports( $posttype, 'custom-fields' ) )
				continue;

			$fields = $this->get_posttype_fields( $posttype );

			foreach ( $fields as $field => $args ) {

				if ( empty( $args['rest'] ) )
					continue;

				if ( $args['repeat'] ) {

					$defaults = [
						// NOTE: require an item schema when registering `array` meta
						'type'    => 'array',
						'single'  => FALSE,
						'default' => (array) $args['default'],
					];

				} else if ( in_array( $args['type'], [ 'integer', 'number', 'float', 'price' ] ) ) {

					$defaults = [
						'type'    => 'integer',
						'single'  => TRUE,
						'default' => $args['default'] ?: 0,
					];

				} else {

					$defaults = [
						// NOTE: valid values: `string`, `boolean`, `integer`, `number`, `array`, `object`
						'type'    => 'string',
						'single'  => TRUE,
						'default' => $args['default'] ?: '',
					];
				}

				$register_args = array_merge( $defaults, [

					/**
					 * Accepts `post`, `comment`, `term`, `user`
					 * or any other object type with an associated meta table
					 */
					'object_subtype' => $posttype,

					'description'   => sprintf( '%s: %s', $args['title'], $args['description'] ),
					'auth_callback' => [ $this, 'register_auth_callback_posttypefields' ],
					'show_in_rest'  => TRUE,

					// TODO: must prepare object scheme on repeatable fields
					// @SEE: https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/#read-and-write-a-post-meta-field-in-post-responses
					// @SEE: `rest_validate_value_from_schema()`, `wp_register_persisted_preferences_meta()`
					// 'show_in_rest'      => [ 'prepare_callback' => [ $this, 'register_prepare_callback_posttypefields' ] ],
				] );

				if ( $is_rest ) // WTF: double sanitizes along with store meta-box default sanitize
					$register_args['sanitize_callback'] = [ $this, 'register_sanitize_callback_posttypefields' ];

				if ( FALSE === $args['access_view'] )
					$register_args['show_in_rest'] = FALSE; // only for explicitly private fields

				$meta_key = $this->get_postmeta_key( $field );
				$filtered = $this->filters( 'register_field_args', $register_args, $meta_key, $posttype );

				if ( FALSE !== $filtered )
					register_meta( 'post', $meta_key, $filtered );
			}
		}
	}

	public function pairedrest_prepped_post_posttypefields( $prepped, $post, $parent )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $prepped;

		$attribute = $this->constant( 'restapi_attribute', sprintf( '%s_rendered', $this->key ) );

		return array_merge( $prepped, [
			$attribute => $this->get_posttype_fields_data( $post, TRUE, 'rest' ),
		] );
	}

	public function pairedimports_import_types_posttypefields( $types, $linked, $posttypes, $module_key )
	{
		foreach ( $this->posttypes() as $posttype ) {

			if ( ! in_array( $posttype, $posttypes, TRUE ) )
				continue;

			$fields = $this->get_posttype_fields( $posttype, [ 'import' => TRUE ] );

			if ( empty( $fields ) )
				continue;

			$types = array_merge( $types, Core\Arraay::pluck( $fields, 'title', 'name' ) );
		}

		return $types;
	}

	public function attribute_get_callback_posttypefields( $params, $attr, $request, $object_type )
	{
		return $this->get_posttype_fields_data( (int) $params['id'], FALSE, 'rest' );
	}

	/**
	 * NOTE: DEPRECATED FILTER: `geditorial_meta_disable_field_edit`
	 *
	 * - Upon no `auth_callback`, WordPress checks for `is_protected_meta()` aka underline prefix
	 * - This filter is to call when performing `edit_post_meta`, `add_post_meta`, and `delete_post_meta` capability checks
	 * - Returns `true` to have the mapped meta caps from `edit_{$object_type}` apply
	*/
	public function register_auth_callback_posttypefields( $allowed, $meta_key, $object_id, $user_id, $cap, $caps )
	{
		// FIXME: find a better way than `stripprefix()`
		if ( ! $field = $this->get_posttype_field_args( $this->stripprefix( $meta_key ), get_object_subtype( 'post', $object_id ) ) )
			return $allowed;

		return (bool) $this->access_posttype_field( $field, $object_id, 'edit', $user_id );
	}

	// WORKING BUT DISABLED
	// NO NEED: we use original key, so the core will retrieve the value
	public function register_prepare_callback_posttypefields( $value, $request, $args )
	{
		if ( ! $post = WordPress\Post::get() )
			return $value;

		$fields = $this->get_posttype_fields( $post->post_type );
		$fields = Core\Arraay::filter( $fields, [ 'rest' => $args['name'] ] );

		foreach ( $fields as $field => $field_args )
			return $this->get_postmeta_field( $post->ID, $field, $field_args['default'] );

		return $value;
	}

	public function register_sanitize_callback_posttypefields( $meta_value, $meta_key, $object_type )
	{
		$field = $this->get_posttype_field_args( $this->stripprefix( $meta_key ), $object_type );
		return $field ? $this->sanitize_posttype_field( $meta_value, $field, WordPress\Post::get() ) : $meta_value;
	}

	// NOTE: used by other modules
	public function get_posttype_fields_data( $post, $raw = FALSE, $context = 'view' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$list   = [];
		$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( empty( $args['rest'] ) )
				continue;

			$meta = Template::getMetaField( $field, [
				'id'       => $post->ID,
				'default'  => $args['default'],
				'context'  => $context,
				'noaccess' => FALSE,
			], FALSE, $this->key );

			// if no access or default is FALSE
			if ( FALSE === $meta && $meta !== $args['default'] )
				continue;

			$row = [
				'name'     => $args['rest'],
				'title'    => $args['title'],
				'rendered' => $meta,
			];

			if ( $raw )
				$row['value'] = Template::getMetaFieldRaw( $field, $post->ID, $this->key, FALSE, NULL );

			$list[] = $row;
		}

		return $list;
	}

	protected function posttypefields__hook_importer_init()
	{
		$this->filter_module( 'importer', 'fields', 2, 10, 'posttypefields' );
		$this->filter_module( 'importer', 'prepare', 7, 10, 'posttypefields' );
		$this->action_module( 'importer', 'saved', 2, 10, 'posttypefields' );
	}

	protected function posttypefields_get_importer_fields( $posttype = NULL, $object = FALSE )
	{
		$fields = [];

		$template = $this->get_string( 'field_title', FALSE, 'importer',
			/* translators: `%s`: field title */
			_x( 'Field: %s', 'Internal: PostTypeFields: Import Title', 'geditorial-admin' ) );

		$ignored = $this->get_string( 'ignored_title', FALSE, 'importer',
			/* translators: `%s`: field title */
			_x( 'Field: %s [Ignored]', 'Internal: PostTypeFields: Import Title', 'geditorial-admin' ) );

		foreach ( $this->get_posttype_fields( $posttype, [ 'import' => TRUE ] ) as $field => $args ) {

			if ( in_array( $args['type'], [ 'term' ] ) )
				continue;

			$fields[sprintf( '%s__%s', $this->key, $field )] = $object ? $args : sprintf( $template, $args['title'] );

			if ( ! empty( $args['import_ignored'] ) )
				$fields[sprintf( '%s_ignored__%s', $this->key, $field )] = $object ? $args : sprintf( $ignored, $args['title'] );
		}

		return $fields;
	}

	public function importer_fields_posttypefields( $fields, $posttype )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $fields;

		return array_merge( $fields, $this->posttypefields_get_importer_fields( $posttype ) );
	}

	public function importer_prepare_posttypefields( $value, $posttype, $field, $header, $raw, $source_id, $all_taxonomies )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return $value;

		$fields = $this->posttypefields_get_importer_fields( $posttype, TRUE );

		if ( ! array_key_exists( $field, $fields ) )
			return $value;

		return $this->sanitize_posttype_field( $value, $fields[$field] );
	}

	public function importer_saved_posttypefields( $post, $atts = [] )
	{
		if ( ! $post || ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->posttypefields_get_importer_fields( $post->post_type, TRUE );

		foreach ( $atts['map'] as $offset => $field ) {

			if ( ! array_key_exists( $field, $fields ) )
				continue;

			if ( Core\Text::starts( $field, sprintf( '%s_ignored__', $this->key ) ) ) {

				// saves only if it is a new post
				if ( empty( $atts['updated'] ) )
					$this->posttypefields_do_import_field( $atts['raw'][$offset], $fields[$field], $post );

			} else {

				$this->posttypefields_do_import_field( $atts['raw'][$offset], $fields[$field], $post, $atts['override'] );
			}
		}
	}

	// OLD: `import_posttype_field()`
	protected function posttypefields_do_import_field( $data, $field, $post, $override = TRUE )
	{
		switch ( $field['type'] ) {

			case 'parent_post':

				if ( ! $parent = WordPress\Post::get( (int) $data ) )
					return FALSE;

				if ( ! WordPress\Post::setParent( $post->ID, $parent->ID, FALSE ) )
					return FALSE;

				break;

			case 'term':

				if ( empty( $field['taxonomy'] ) )
					return FALSE;

				if ( ! WordPress\Taxonomy::can( $field['taxonomy'], 'assign_terms' ) )
					return FALSE;

				if ( ! $override && FALSE !== get_the_terms( $post, $field['taxonomy'] ) )
					return FALSE;

				$terms = $this->sanitize_posttype_field( $data, $field, $post );

				return wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $terms ), $field['taxonomy'], FALSE );

			default:

				if ( ! $override && FALSE !== $this->get_postmeta_field( $post->ID, $field['name'] ) )
					return FALSE;

				return $this->set_postmeta_field( $post->ID, $field['name'], $this->sanitize_posttype_field( $data, $field, $post ) );
		}
	}

	// OLD: `import_field_meta()`
	protected function posttypefields_do_migrate_field( $metakey, $field, $limit = FALSE )
	{
		$rows = WordPress\Database::getPostMetaRows( $metakey, $limit );

		foreach ( $rows as $row )
			$this->posttypefields_do_migrate_field_raw(
				Helper::getSeparated( $row->meta ),
				$field,
				$row->post_id
			);

		return count( $rows );
	}

	// OLD: `import_field_raw()`
	protected function posttypefields_do_migrate_field_raw( $data, $field_key, $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$key  = $this->sanitize_postmeta_field_key( $field_key )[0];
		$data = $this->filters( 'import_field_raw_pre', $data, $key, $post );

		if ( FALSE === $data )
			return FALSE;

		if ( ! $field = get_posttype_field_args( $key, $post->post_type ) )
			return FALSE;

		switch ( $field['type'] ) {

			case 'term':

				$this->posttypefields_do_migrate_field_terms( $data, $field, $post );

			break;
			default:

				$this->posttypefields_do_migrate_field_strings( $data, $field, $post );
		}

		return $post->ID;
	}

	// OLD: `import_field_raw_terms()`
	protected function posttypefields_do_migrate_field_terms( $data, $field, $post )
	{
		$terms = [];

		foreach ( (array) $data as $name ) {

			$sanitized = WordPress\Strings::kses( $name, 'none' );

			if ( empty( $sanitized ) )
				continue;

			$formatted = apply_filters( 'string_format_i18n', $sanitized );

			if ( ! $term = get_term_by( 'name', $formatted, $field['taxonomy'] ) ) {

				$term = wp_insert_term( $formatted, $field['taxonomy'] );

				if ( ! is_wp_error( $term ) )
					$terms[] = $term->term_id;

			} else {

				$terms[] = $term->term_id;
			}
		}

		$terms = $this->sanitize_posttype_field( $terms, $field, $post );

		return wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $terms ), $field['taxonomy'], FALSE );
	}

	// OLD: `import_field_raw_strings()`
	protected function posttypefields_do_migrate_field_strings( $data, $field, $post )
	{
		$strings = [];

		foreach ( (array) $data as $name ) {

			$sanitized = $this->sanitize_posttype_field( $name, $field, $post );

			if ( empty( $sanitized ) )
				continue;

			$strings[] = apply_filters( 'string_format_i18n', $sanitized );
		}

		return $this->set_postmeta_field( $post->ID, $field['name'], WordPress\Strings::getPiped( $strings ) );
	}

	public function posttypefields_import_raw_data_action( $post, $data, $override, $check_access, $module )
	{
		if ( empty( $data ) || $module !== $this->key )
			return;

		if ( ! $post = WordPress\Post::get( $post ) )
			return;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		if ( ! count( $fields ) )
			return;

		$user_id = get_current_user_id();

		foreach ( $fields as $field => $args ) {

			if ( ! array_key_exists( $field, $data ) )
				continue;

			if ( $check_access && ! $this->access_posttype_field( $args, $post, 'edit', $user_id ) )
				continue;

			$this->posttypefields_do_import_field( $data[$field], $args, $post, $override );
		}
	}

	protected function posttypefields__hook_template_newpost()
	{
		if ( is_admin() )
			return;

		$this->action( 'template_newpost_beforetitle', 6, 20, 'posttypefields_title_before', $this->base );
		$this->action( 'template_newpost_aftertitle', 6, 1, 'posttypefields_title_after', $this->base );
		$this->action( 'template_newpost_aftertitle', 6, 20, 'posttypefields_quickedit', $this->base );
	}

	public function template_newpost_beforetitle_posttypefields_title_before( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		if ( ! $fields = Core\Arraay::filter( $this->get_posttype_fields( $posttype ), [ 'type' => 'title_before' ] ) )
			return;

		$this->render_posttype_fields( $post, FALSE, $fields, 'nobox', '<div class="-form-group">', '</div>' );
	}

	public function template_newpost_aftertitle_posttypefields_title_after( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		if ( ! $fields = Core\Arraay::filter( $this->get_posttype_fields( $posttype ), [ 'type' => 'title_after' ] ) )
			return;

		$this->render_posttype_fields( $post, FALSE, $fields, 'nobox', '<div class="-form-group">', '</div>' );
	}

	public function template_newpost_aftertitle_posttypefields_quickedit( $posttype, $post, $target, $linked, $status, $meta )
	{
		if ( ! $this->posttype_supported( $posttype ) )
			return;

		if ( ! $fields = Core\Arraay::filter( $this->get_posttype_fields( $posttype ), [ 'quickedit' => TRUE ] ) )
			return;

		$this->render_posttype_fields( $post, FALSE, $fields, NULL, '<div class="-form-group">', '</div>' );
	}

	// `$this->filter( 'searchselect_result_extra_for_post', 3, 12, 'filter', $this->base );`
	public function searchselect_result_extra_for_post_filter( $data, $post, $queried )
	{
		if ( empty( $queried['context'] )
			|| in_array( $queried['context'], [ 'select2', 'pairedimports' ], TRUE ) )
			return $data;

		if ( ! $post = WordPress\Post::get( $post ) )
			return $data;

		if ( ! $this->posttype_supported( $post->post_type ) )
			return $data;

		return array_merge( $data, array_filter( Core\Arraay::pluck(
			$this->get_posttype_fields_data( $post, FALSE, 'export' ),
			'rendered',
			'name'
		) ) );
	}
}

<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class PostTypeFields extends gEditorial\Service
{
	public static function setup(): void
	{
		if ( 'fa_IR' === Core\L10n::locale( TRUE ) ) {
			add_filter( self::und( static::BASE, 'posts_search_append_meta_frontend' ), [ __CLASS__, 'posts_search_append_meta' ], 12, 3 );
			add_filter( self::und( static::BASE, 'posts_search_append_meta_backend' ),  [ __CLASS__, 'posts_search_append_meta' ], 12, 3 );
		}
	}

	// TODO: move to `Meta` Module
	// NOTE: runs only on `fa_IR` locale
	public static function posts_search_append_meta( array $meta, string $search, mixed $posttypes ): array
	{
		if ( 'any' === $posttypes || empty( $posttypes ) )
			return $meta;

		$criteria = Core\Number::translate( $search );
		$calendar = self::getDefaultCalendar( 'meta' );

		if ( $date = gEditorial\Datetime::makeMySQLFromInput( $criteria, 'Y-m-d', $calendar ) )
			foreach ( (array) $posttypes as $posttype )
				foreach ( self::getEnabled( $posttype, 'meta', [ 'type' => 'date' ] ) as $field )
					if ( $field['metakey'] && ! array_key_exists( $field['metakey'], $meta ) )
						$meta[$field['metakey']] = $date;

		if ( $datetime = gEditorial\Datetime::makeMySQLFromInput( $criteria, NULL, $calendar ) )
			foreach ( (array) $posttypes as $posttype )
				foreach ( self::getEnabled( $posttype, 'meta', [ 'type' => 'datetime' ] ) as $field )
					if ( $field['metakey'] && ! array_key_exists( $field['metakey'], $meta ) )
						$meta[$field['metakey']] = $datetime;

		return $meta;
	}

	public static function getDefaultCalendar( string $module = 'meta', bool $check = TRUE ): string
	{
		if ( $check && ! gEditorial()->enabled( $module ) )
			return Core\L10n::calendar();

		return gEditorial()->module( $module )->default_calendar();
	}

	/**
	 * Retrieves the post meta-key for given field.
	 * TODO: rename to `getMetaKey`
	 *
	 * @param string $field_key
	 * @param string $module
	 * @param bool $check
	 * @return string
	 */
	public static function getPostMetaKey( string $field_key, string $module = 'meta', bool $check = TRUE ): string
	{
		if ( ! $field_key )
			return FALSE;

		if ( $check && ! gEditorial()->enabled( $module ) )
			return FALSE;

		return gEditorial()->module( $module )->get_postmeta_key( $field_key );
	}

	/**
	 * Checks the availability of post-type field for given post-type via certain module.
	 * OLD: `Helper::isPostTypeFieldAvailable()`
	 *
	 * @param string $field_key
	 * @param string $posttype
	 * @param string $module
	 * @return false|array
	 */
	public static function isAvailable( string $field_key, string $posttype, string $module = 'meta' ): false|array
	{
		if ( ! $posttype || ! $field_key )
			return FALSE;

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		return gEditorial()->module( $module )->get_posttype_field_args( $field_key, $posttype );
	}

	/**
	 * Retrieves the export title of field for given post-type via certain module.
	 *
	 * @param string $field_key
	 * @param string $posttype
	 * @param string $module
	 * @return string
	 */
	public static function getExportTitle( string $field_key, string $posttype, string $module = 'meta' ): string
	{
		if ( ! $posttype )
			return $field_key;

		if ( ! gEditorial()->enabled( $module ) )
			return $field_key;

		return gEditorial()->module( $module )->get_posttype_field_export_title( $field_key, $posttype );
	}

	/**
	 * Retrieves the supported post-types given a field key via certain module.
	 * OLD: `Helper::getPostTypeFieldSupported()`
	 *
	 * @param string $field_key
	 * @param string $module
	 * @return array
	 */
	public static function getSupported( string $field_key, string $module = 'meta' ): array
	{
		if ( ! $field_key )
			return [];

		if ( ! gEditorial()->enabled( $module ) )
			return [];

		return gEditorial()->module( $module )->get_posttype_field_supported( $field_key );
	}

	/**
	 * Retrieves the enabled post-type fields given a post-type via certain module.
	 *
	 * @param string $posttype
	 * @param string $module
	 * @param array $filter
	 * @param string $operator
	 * @return array
	 */
	public static function getEnabled( string $posttype, string $module = 'meta', array $filter = [], string $operator = 'AND' ): array
	{
		if ( ! $posttype )
			return [];

		if ( ! gEditorial()->enabled( $module ) )
			return [];

		return gEditorial()->module( $module )->get_posttype_fields( $posttype, $filter, $operator );
	}

	/**
	 * Retrieves the post ID by field-key given a value via certain module.
	 *
	 * OLD: `posttypefields_get_post_by()`
	 *
	 * @param string $field_key
	 * @param mixed $value
	 * @param string $posttype
	 * @param bool $sanitize
	 * @param string $module
	 * @return false|int
	 */
	public static function getPostByField( string $field_key, mixed $value, string $posttype, bool $sanitize = FALSE, string $module = 'meta' ): false|int
	{
		if ( ! $field_key || ! $value || ! $posttype )
			return FALSE;

		if ( ! gEditorial()->enabled( $module ) )
			return FALSE;

		$metakey = gEditorial()->module( $module )->get_postmeta_key( $field_key );

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

	/**
	 * Retrieves the default icon given a field arguments and post-type.
	 * @old: `get_posttype_field_icon()`
	 *
	 * @param string $field_key
	 * @param array $args
	 * @param string $posttype
	 * @return string|array
	 */
	public static function getFieldIcon( string $field_key, array $args = [], ?string $posttype = NULL ): string|array
	{
		if ( ! empty( $args['icon'] ) )
			return $args['icon'];

		switch ( $field_key ) {

			case 'over_title' : return 'arrow-up-alt2';
			case 'sub_title'  : return 'arrow-down-alt2';
			case 'alt_title'  : return 'admin-site-alt';
			case 'highlight'  : return 'pressthis';
			case 'byline'     : return 'admin-users';
			case 'published'  : return 'calendar-alt';
			case 'released'   : return 'calendar-alt';
			case 'lead'       : return 'editor-paragraph';
			case 'label'      : return 'megaphone';
			case 'notes'      : return 'text-page';
			case 'reference'  : return 'editor-break';
			case 'itineraries': return 'editor-ul';
			case 'url'        : return 'admin-links';
		}

		if ( ! empty( $args['type'] ) ) {

			switch ( $args['type'] ) {

				case 'email'     : return 'email';
				case 'phone'     : return 'phone';
				case 'mobile'    : return 'smartphone';
				case 'identity'  : return 'id-alt';
				case 'iban'      : return 'bank';
				case 'isbn'      : return 'book';                 // 'menu'
				case 'date'      : return 'calendar';
				case 'time'      : return 'clock';
				case 'datetime'  : return 'calendar-alt';
				case 'datestring': return 'calendar-alt';
				case 'distance'  : return 'image-flip-vertical';
				case 'duration'  : return 'clock';
				case 'area'      : return 'fullscreen-alt';
				case 'day'       : return 'backup';
				case 'hour'      : return 'clock';
				case 'people'    : return 'groups';
				case 'address'   : return 'location';
				case 'venue'     : return 'location-alt';
				case 'embed'     : return 'embed-generic';
				case 'link'      : return 'admin-links';
				case 'latlng'    : return 'location';
				case 'color'     : return 'color-picker';

				case 'title_link'  : return 'admin-links';
				case 'text_source' : return 'media-text';
				case 'audio_source': return 'media-audio';
				case 'video_source': return 'media-video';
				case 'image_source': return 'format-image';  // 'media-document';
				case 'downloadable': return 'download';      // 'media-archive'
				case 'post'        : return 'admin-post';
				case 'attachment'  : return 'admin-media';
				case 'parent_post' : return 'admin-page';
			}
		}

		if ( ! empty( $args['data_unit'] ) )  {

			switch ( $args['type'] ) {

				case 'person':
					return 'groups';

				// weight
				case 'kilogram' :
				case 'gram'     :
				case 'milligram':
					return 'image-filter';

				// length
				case 'kilometre' :
				case 'metre'     :
				case 'centimetre':
				case 'millimetre':
					return 'image-flip-horizontal';

				// area
				case 'hectare':
					return 'image-crop';
			}
		}

		return 'admin-post';
	}

	public static function getPostDateMetaKeys( array $extra = [], string $module = 'meta', bool $check = TRUE ): array
	{
		if ( $check && ! gEditorial()->enabled( $module ) )
			return [];

		$list   = [];
		$fields = [
			'date',
			'datetime',
			'datestart',
			'dateend',
		];

		foreach ( $fields as $field )
			$list[$field] = self::getPostMetaKey( $field, $module, FALSE );

		return array_merge( $list, $extra );
	}

	// OLD: `Template::getMetaField()`
	public static function getField( mixed $field_key, array $atts = [], bool $check = TRUE, string $module = 'meta' ): mixed
	{
		$field = FALSE;
		$args  = self::parsed( [
			'id'       => NULL,
			'fallback' => FALSE,
			'default'  => FALSE,
			'noaccess' => NULL,     // returns upon no access, `NULL` for `default` argument
			'context'  => 'view',   // access checks, `FALSE` to disable checks
			'filter'   => FALSE,    // or `__do_embed_shortcode`
			'prefix'   => FALSE,    // prefix value with field prop
			'trim'     => FALSE,    // or number of chars
			'before'   => '',
			'after'    => '',
		], $atts );

		// NOTE: may come from post-type field argument
		$args['default'] = $args['default'] ?? '';

		if ( empty( $field_key ) )
			return $args['default'];

		if ( $check && ! gEditorial()->enabled( $module ) )
			return $args['default'];

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		if ( is_array( $field_key ) ) {

			if ( empty( $field_key['name'] ) )
				return $args['default'];

			$field     = $field_key;
			$field_key = $field['name'];
		}

		$meta = $raw = self::getFieldRaw( $field_key, $post->ID, $module );

		if ( FALSE === $meta && $args['fallback'] )
			return self::getField( $args['fallback'], array_merge( $atts, [ 'fallback' => FALSE ] ), FALSE );

		if ( empty( $field ) )
			$field = gEditorial()->module( $module )->get_posttype_field_args( $field_key, $post->post_type );

		// NOTE: field may be disabled or overridden
		if ( FALSE === $field )
			$field = [ 'name' => $field_key, 'type' => 'text' ];

		if ( FALSE === $meta )
			$meta = apply_filters( self::und( static::BASE, 'meta_field', 'empty' ), $meta, $field_key, $post, $args, $raw, $field, $args['context'], $module );

		if ( FALSE === $meta )
			return $args['default'];

		if ( FALSE !== $args['context'] ) {

			$access = gEditorial()->module( $module )->access_posttype_field( $field, $post, $args['context'] );

			if ( ! $access )
				return $args['noaccess'] ?? $args['default'];
		}

		$meta = apply_filters( self::und( static::BASE, 'meta_field' ), $meta, $field_key, $post, $args, $raw, $field, $args['context'], $module );
		$meta = apply_filters( self::und( static::BASE, 'meta_field', $field_key ), $meta, $field_key, $post, $args, $raw, $field, $args['context'], $module );

		if ( '__do_embed_shortcode' === $args['filter'] )
			$args['filter'] = [ gEditorial\Template::class, 'doEmbedShortCode' ];

		if ( $args['filter'] && is_callable( $args['filter'] ) )
			$meta = call_user_func( $args['filter'], $meta );

		if ( $args['prefix'] )
			$meta = sprintf( '%s: %s', $field[$args['prefix']] ?? $args['prefix'], $meta );

		if ( $meta )
			return $args['before'].( $args['trim'] ? WordPress\Strings::trimChars( $meta, $args['trim'] ) : $meta ).$args['after'];

		return $args['default'];
	}

	// OLD: `Template::getMetaFieldRaw()`
	// NOTE: does not check for `access_view` arg
	public static function getFieldRaw( string $field_key, int $post_id, string $module = 'meta', bool $check = FALSE, mixed $default = FALSE ): mixed
	{
		if ( $check ) {

			if ( ! gEditorial()->enabled( $module ) )
				return $default;

			if ( ! $post = WordPress\Post::get( $post_id ) )
				return $default;

			$post_id = $post->ID;
		}

		$meta = $module
			? gEditorial()->{$module}->get_postmeta_field( $post_id, $field_key, $default )
			: $default;

		return apply_filters( self::und( static::BASE, 'get_meta_field' ),
			$meta,
			$field_key,
			$post_id,
			$module,
			$default
		);
	}

	public static function getFieldDate( string $field_key, int $post_id, string $module = 'meta', bool $check = TRUE, mixed $default = FALSE, ?string $default_calendar = NULL ): mixed
	{
		if ( ! $date = self::getFieldRaw( $field_key, $post_id, $module, $check, $default ) )
			return $default;

		if ( ! $datetime = gEditorial\Datetime::prepForMySQL( $date, NULL, $default_calendar ?? self::getDefaultCalendar( $module, FALSE ) ) )
			return $default;

		return Core\Date::getObject( $datetime );
	}

	// OLD: `Helper::prepMetaRow()`
	// TODO: support: `dob`,`date`,`datetime`
	public static function prepFieldRow( mixed $value, ?string $field_key = NULL, array $field = [], mixed $raw = NULL, string $module = 'meta' ): mixed
	{
		$filtered = apply_filters( self::und( static::BASE, 'prep_meta_row' ), $value, $field_key, $field, $raw );

		if ( $filtered !== $value )
			return $filtered; // bail if already filtered

		// NOTE: first priority: field key
		switch ( $field_key ) {

			case 'twitter'  : return Core\Third::htmlTwitterIntent( $raw ?: $value, TRUE );
			case 'facebook' : return Core\HTML::link( Core\URL::prepTitle( $raw ?: $value ), $raw ?: $value );
			case 'instagram': return Core\Third::htmlHandle( $raw ?: $value, 'https://instagram.com/' );
			case 'telegram' : return Core\Third::htmlHandle( $value, 'https://t.me/' );
			case 'phone'    : return Core\Email::prep( $raw ?: $value, $field, 'admin' );
			case 'mobile'   : return Core\Mobile::prep( $raw ?: $value, $field, 'admin' );
			case 'username' : return sprintf( '@%s', $raw ?: $value ); // TODO: filter this for profile links

			case 'items':
			case 'total_items':

				return sprintf( gEditorial\Helper::noopedCount( $raw ?: $value, gEditorial\Info::getNoop( 'item' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'pages':
			case 'total_pages':

				return sprintf( gEditorial\Helper::noopedCount( $raw ?: $value, gEditorial\Info::getNoop( 'page' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'volumes':
			case 'total_volumes':

				return sprintf( gEditorial\Helper::noopedCount( $raw ?: $value, gEditorial\Info::getNoop( 'volume' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'discs':
			case 'total_discs':

				return sprintf( gEditorial\Helper::noopedCount( $raw ?: $value, gEditorial\Info::getNoop( 'disc' ) ),
					Core\Number::format( $raw ?: $value ) );
		}

		if ( ! empty( $field['type'] ) ) {

			// NOTE: second priority: field type
			switch ( $field['type'] ) {

				case 'venue':

					return Locations::prepVenue( $raw ?: $value );

				case 'people':

					return Individuals::prepPeople( $raw ?: $value );

				case 'day':
				case 'hour':
				case 'member':
				case 'person':

					return sprintf( gEditorial\Helper::noopedCount( $raw ?: $value, gEditorial\Info::getNoop( $field['type'] ) ),
						Core\Number::format( $raw ?: $value ) );

				case 'gram':

					return sprintf(
						/* translators: `%s`: number as gram */
						_x( '%s g', 'Helper: Number as Gram', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'kilogram':

					return sprintf(
						/* translators: `%s`: number as kilogram */
						_x( '%s kg', 'Helper: Number as Kilogram', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'millimetre':

					return sprintf(
						/* translators: `%s`: number as millimetre */
						_x( '%s mm', 'Helper: Number as Millimetre', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'centimetre':

					return sprintf(
						/* translators: `%s`: number as centimetre */
						_x( '%s cm', 'Helper: Number as Centimetre', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'km_per_hour':

					return sprintf(
						/* translators: `%s`: number as kilometres per hour */
						_x( '%s kpm', 'Helper: Number as Kilometres per Hour', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'identity':

					return sprintf( '<span class="-identity %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
						Core\Validation::isIdentityNumber( $raw ?: $value ) ? '-is-valid' : '-not-valid',
						$raw ?: $value, $raw ?: $value );

				case 'postcode':

					if ( FALSE === ( $postcode = gEditorial\Info::fromPostCode( $raw ?: $value ) ) )
						return sprintf( '<span class="-postcode %s">%s</span>', '-not-valid', $raw ?: $value );

					else
						return sprintf( '<span class="-postcode %s" title="%s">%s</span>',
							'-is-valid',
							empty( $postcode['country'] ) ? gEditorial()->na( FALSE ) : $postcode['country'],
							Core\HTML::wrapLTR( empty( $postcode['formatted'] ) ? ( $raw ?: $value ) : $postcode['formatted'] )
						);

				case 'iban':

					if ( FALSE === ( $iban = gEditorial\Info::fromIBAN( $raw ?: $value ) ) )
						return sprintf( '<span class="-iban %s">%s</span>', '-not-valid', $raw ?: $value );

					else
						return sprintf( '<span class="-iban %s" title="%s">%s</span>',
							'-is-valid',
							empty( $iban['bankname'] ) ? gEditorial()->na( FALSE ) : $iban['bankname'],
							empty( $iban['formatted'] ) ? ( $raw ?: $value ) : $iban['formatted']
						);

				case 'bankcard':

					if ( FALSE === ( $card = gEditorial\Info::fromCardNumber( $raw ?: $value ) ) )
						return sprintf( '<span class="-bankcard %s">%s</span>', '-not-valid', $raw ?: $value );

					else
						return sprintf( '<span class="-bankcard %s" title="%s">%s</span>',
							'-is-valid',
							empty( $card['bankname'] ) ? gEditorial()->na( FALSE ) : $card['bankname'],
							empty( $card['formatted'] ) ? ( $raw ?: $value ) : $card['formatted']
						);

				case 'isbn':

					// `return Lookup::htmlISBN( $raw ?: $value );`
					return sprintf( '<span class="-isbn %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
						Core\ISBN::validate( $raw ?: $value ) ? '-is-valid' : '-not-valid',
						$raw ?: $value, $raw ?: $value );

				case 'vin':

					return Lookup::htmlVIN( $raw ?: $value );

				case 'plate':

					return sprintf( '<span class="-plate %s do-clicktoclip" data-clipboard-text="%s">%s</span>',
						Core\Validation::isPlateNumber( $raw ?: $value ) ? '-is-valid' : '-not-valid',
						$raw ?: $value, $raw ?: $value );

				case 'address':

					return Locations::prepAddress( $raw ?: $value, 'display', $raw ?: $value );

				case 'year':

					return Core\Number::localize( $raw ?: $value );

				case 'date':

					return gEditorial\Datetime::prepForDisplay(
						$raw ?: $value,
						gEditorial\Datetime::dateFormats( 'default' ),
						self::getDefaultCalendar( $module )
					);

				case 'datetime':

					return gEditorial\Datetime::prepForDisplay(
						$raw ?: $value,
						gEditorial\Datetime::isDateOnly( $raw ?: $value )
							? gEditorial\Datetime::dateFormats( 'default' )
							: gEditorial\Datetime::dateFormats( 'datetime' ),
						self::getDefaultCalendar( $module )
					);

				case 'distance':

					return Core\Distance::prep( $raw ?: $value, $field );

				case 'duration':

					return Core\Duration::prep( $raw ?: $value, $field );

				case 'area':

					return Core\Area::prep( $raw ?: $value, $field );

				case 'contact_method':

					return Core\URL::isValid( $raw ?: $value )
						? Core\HTML::link( Core\URL::prepTitle( $raw ?: $value ), $raw ?: $value )
						: sprintf( '<span title="%s">@%s</span>',
							empty( $field['title'] ) ? $field_key : Core\HTML::escape( $field['title'] ),
							$raw ?: $value
						);

				case 'email':

					return Core\Email::prep( $raw ?: $value, $field, 'admin' );

				case 'phone':

					return Core\Phone::prep( $raw ?: $value, $field, 'admin' );

				case 'mobile':

					return Core\Mobile::prep( $raw ?: $value, $field, 'admin' );

				case 'embed':

					return Core\HTML::link( Core\URL::getDomain( $raw ?: $value ), $raw ?: $value, TRUE );

				case 'link':

					return Core\HTML::link( Core\URL::prepTitle( $raw ?: $value ), $raw ?: $value, TRUE );

				case 'latlng':

					return Lookup::htmlLatLng( $raw ?: $value );

				case 'title_link':
				case 'text_source':
				case 'audio_source':
				case 'video_source':
				case 'image_source':
				case 'downloadable':

					return Core\HTML::tag( 'a', [
						'href'   => $raw ?: $value,
						'title'  => Core\URL::getDomain( $raw ?: $value ),
						'class'  => Core\URL::isValid( $raw ?: $value ) ? '-is-valid' : '-not-valid',
						'target' => '_blank',
					], Core\File::basename( $raw ?: $value ) );

				case 'post':
				case 'attachment':
				case 'parent_post':

					return gEditorial\Helper::getPostTitleRow( (int) $raw ?: $value );

				// TODO
				// case 'posts':
				// case 'attachments':
				// case 'term':

				case 'user':

					return gEditorial\Helper::getAuthorsEditRow(
						(int) $raw ?: $value,
						self::req( 'post_type', 'post' ),
						'',
						'',
						FALSE,
					);
			}
		}

		// NOTE: third priority: data unit
		if ( ! empty( $field['data_unit'] ) ) {

			switch ( $field['data_unit'] ) {

				case 'shot':
				case 'line':
				case 'card':
				case 'metre':

					return sprintf( gEditorial\Helper::noopedCount( $raw ?: $value,
						gEditorial\Info::getNoop( $field['data_unit'] ) ),
						Core\Number::format( $raw ?: $value )
					);
			}
		}

		// NOTE: fourth priority: general field
		switch ( $field_key ) {

			case 'title'      : return WordPress\Strings::prepTitle( $raw ?: $value );
			case 'desc'       : return WordPress\Strings::prepDescription( $raw ?: $value );
			case 'description': return WordPress\Strings::prepDescription( $raw ?: $value );
			case 'contact'    : return gEditorial\Helper::prepContact( $raw ?: $value );
		}

		// NOTE: fifth priority: last resorts
		if ( array_key_exists( 'ltr', $field ) && $field['ltr'] )
			return sprintf( '<span dir="ltr">%s</span>', Core\HTML::escape( trim( $value ) ) );

		return Core\HTML::escape( trim( $value ) );
	}

	public static function replaceTokens( mixed $meta, array $field, object $post, ?string $context = NULL ): mixed
	{
		if ( ! $meta || ! is_string( $meta ) )
			return $meta;

		// Do bail early if it has not have tokens!
		if ( ! Core\Text::has( $meta, '{{' ) )
			return $meta;

		if ( in_array( $field['type'], [
			'integer', 'number', 'float', 'price',
			'member', 'person', 'day', 'hour',
			'gram', 'kilogram', 'millimetre', 'centimetre', 'metre', 'kilometre', 'hectare',
			'phone', 'mobile', 'contact', 'identity', 'iban', 'bankcard', 'isbn', 'vin', 'postcode',
			'post', 'attachment', 'parent_post', 'posts', 'attachments',
			'user', 'term',
		], TRUE ) )
			return $meta;

		$tokens = [
			'today',
			'thisyear',
		];

		return Core\Text::replaceTokens( $meta, $tokens, [
			'meta'       => $meta,
			'field'      => $field['name'],
			'post'       => $post,
			'context'    => $context,
		], [ __CLASS__, '_meta_field_replace_token' ] );
	}

	private static function _meta_field_replace_token( string $token, array $args ): string
	{
		switch ( strtolower( $token ) ) {

			case 'today'   : return gEditorial\Datetime::dateFormat( 'now', empty( $args['context'] ) ? 'default' : $args['context'] );
			case 'thisyear': return Core\Date::get( 'Y' );
		}

		return '';
	}
}

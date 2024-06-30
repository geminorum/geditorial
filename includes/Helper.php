<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;
use geminorum\gEditorial\Services;

class Helper extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function moduleClass( $module, $check = TRUE )
	{
		$class = '';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class.= ucfirst( $word ).'';

		$class = __NAMESPACE__.'\\Modules\\'.$class.'\\'.$class;

		if ( $check && ! class_exists( $class ) )
			return FALSE;

		return $class;
	}

	public static function moduleSlug( $module, $link = TRUE )
	{
		return $link
			? ucwords( str_replace( [ '_', ' ' ], '-', $module ), '-' )
			: ucwords( str_replace( [ '_', '-' ], ' ', $module ) );
	}

	public static function moduleLoading( $module, $stage = NULL )
	{
		if ( 'private' === $module->access && ! GEDITORIAL_LOAD_PRIVATES )
			return FALSE;

		if ( 'beta' === $module->access && ! GEDITORIAL_BETA_FEATURES )
			return FALSE;

		$stage = $stage ?? self::const( 'WP_STAGE', 'production' );  // 'development'

		if ( 'production' !== $stage )
			return TRUE;

		if ( in_array( $module->access, [ 'alpha', 'experimental', 'unknown' ], TRUE ) )
			return FALSE;

		return TRUE;
	}

	public static function moduleEnabled( $options )
	{
		$enabled = isset( $options->enabled ) ? $options->enabled : FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	public static function moduleCheckWooCommerce( $message = NULL )
	{
		return WordPress\WooCommerce::isActive()
			? FALSE
			: ( is_null( $message ) ? _x( 'Needs WooCommerce', 'Helper', 'geditorial-admin' ) : $message );
	}

	public static function moduleCheckLocale( $locale, $message = NULL )
	{
		return $locale === Core\L10n::locale( TRUE )
			? FALSE
			: ( is_null( $message ) ? _x( 'Not Available on Current Locale', 'Helper', 'geditorial-admin' ) : $message );
	}

	public static function isTaxonomyGenre( $taxonomy, $fallback = 'genre' )
	{
		return $taxonomy === gEditorial()->constant( 'genres', 'main_taxonomy', $fallback );
	}

	public static function isTaxonomyAudit( $taxonomy, $fallback = 'audit_attribute' )
	{
		return $taxonomy === gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
	}

	public static function setTaxonomyAudit( $post, $term_ids, $append = TRUE, $fallback = 'audit_attribute' )
	{
		if ( ! gEditorial()->enabled( 'audit' ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! gEditorial()->module( 'audit' )->posttype_supported( $post->post_type ) )
			return FALSE;

		if ( $append && empty( $term_ids ) )
			return FALSE;

		else if ( empty( $term_ids ) )
			$terms = NULL;

		else if ( is_string( $term_ids ) )
			$terms = $term_ids;

		else
			$terms = Core\Arraay::prepNumeral( $term_ids );

		$taxonomy = gEditorial()->constant( 'audit', 'main_taxonomy', $fallback );
		$result   = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );

		if ( is_wp_error( $result ) )
			return FALSE;

		clean_object_term_cache( $post->ID, $taxonomy );

		return $result;
	}

	public static function getIcon( $icon, $fallback = 'admin-post' )
	{
		if ( ! $icon )
			return Core\HTML::getDashicon( $fallback );

		if ( is_array( $icon ) )
			return gEditorial()->icon( $icon[1], $icon[0] );

		if ( Core\Text::starts( $icon, 'data:image/' ) )
			return Core\HTML::img( $icon, [ '-icon', '-encoded' ] );

		return Core\HTML::getDashicon( $icon );
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE, $verbose = TRUE )
	{
		return Core\HTML::linkStyleSheet( $url, $version, $media, $verbose );
	}

	public static function linkStyleSheetAdmin( $page, $verbose = TRUE )
	{
		return Core\HTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.( is_rtl() ? '-rtl' : '' ).'.css', GEDITORIAL_VERSION, 'all', $verbose );
	}

	// TODO: move to `WordPress\Strings`
	public static function kses( $text, $context = 'none', $allowed = NULL )
	{
		if ( is_null( $allowed ) ) {

			if ( 'text' == $context )
				$allowed = [
					'a'       => [ 'class' => TRUE, 'title' => TRUE, 'href' => TRUE ],
					'abbr'    => [ 'class' => TRUE, 'title' => TRUE ],
					'acronym' => [ 'class' => TRUE, 'title' => TRUE ],
					'code'    => [ 'class' => TRUE ],
					'em'      => [ 'class' => TRUE ],
					'strong'  => [ 'class' => TRUE ],
					'i'       => [ 'class' => TRUE ],
					'b'       => [ 'class' => TRUE ],
					'span'    => [ 'class' => TRUE ],
					'br'      => [],
				];

			else if ( 'html' == $context )
				$allowed = wp_kses_allowed_html();

			else if ( 'none' == $context )
				$allowed = [];
		}

		// TODO: drop filters
		return apply_filters( static::BASE.'_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	// TODO: move to `WordPress\Strings`
	public static function ksesArray( $array, $context = 'none', $allowed = NULL )
	{
		foreach ( $array as $key => $value )
			$array[$key] = self::kses( $value, $context, $allowed );

		return $array;
	}

	// TODO: move to `WordPress\Strings`
	public static function prepTitle( $text, $post_id = 0 )
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, $post_id );
		$text = apply_filters( 'string_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return trim( $text );
	}

	// TODO: move to `WordPress\Strings`
	public static function prepDescription( $text, $shortcode = TRUE, $autop = TRUE )
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = WordPress\ShortCode::apply( $text, TRUE );

		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : $text;
	}

	// FIXME: `Contact` DataType
	public static function prepContact( $value, $title = NULL, $empty = '' )
	{
		if ( empty( $value ) )
			return $empty;

		if ( Core\Email::is( $value ) )
			$prepared = Core\Email::prep( $value, [ 'title' => $title ], 'display' );

		else if ( Core\URL::isValid( $value ) )
			$prepared = Core\HTML::link( $title, Core\URL::prepTitle( $value ) );

		else if ( is_numeric( str_ireplace( [ '+', '-', '.' ], '', $value ) ) )
			$prepared = Core\Phone::prep( $value, [ 'title' => $title ], 'display' );

		else
			$prepared = Core\HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	// TODO: support: `dob`,`date`,`datetime`
	public static function prepMetaRow( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		$filtered = apply_filters( static::BASE.'_prep_meta_row', $value, $field_key, $field, $raw );

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

			case 'days':
			case 'total_days':
				return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( 'day' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'hours':
			case 'total_hours':
				return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( 'hour' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'items':
			case 'total_items':
				return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( 'item' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'pages':
			case 'total_pages':
				return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( 'page' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'volumes':
			case 'total_volumes':
				return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( 'volume' ) ),
					Core\Number::format( $raw ?: $value ) );

			case 'discs':
			case 'total_discs':
				return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( 'disc' ) ),
					Core\Number::format( $raw ?: $value ) );
		}

		if ( ! empty( $field['type'] ) ) {

			// NOTE: second priority: field type
			switch ( $field['type'] ) {

				case 'member':
				case 'person':
					return sprintf( self::noopedCount( $raw ?: $value, Info::getNoop( $field['type'] ) ),
						Core\Number::format( $raw ?: $value ) );

				case 'gram':
					return sprintf(
						/* translators: %s: number as gram */
						_x( '%s g', 'Helper: Number as Gram', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'kilogram':
					return sprintf(
						/* translators: %s: number as kilogram */
						_x( '%s kg', 'Helper: Number as Kilogram', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'milimeter':
					return sprintf(
						/* translators: %s: number as milimeter */
						_x( '%s mm', 'Helper: Number as Milimeter', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'centimeter':
					return sprintf(
						/* translators: %s: number as centimeter */
						_x( '%s cm', 'Helper: Number as Centimeter', 'geditorial' ),
						Core\Number::format( $raw ?: $value )
					);

				case 'identity':
					return sprintf( '<span class="-identity %s">%s</span>',
						Core\Validation::isIdentityNumber( $raw ?: $value ) ? '-is-valid' : '-not-valid',
						$raw ?: $value );

				case 'iban':
					return sprintf( '<span class="-iban %s">%s</span>',
						Core\Validation::isIBAN( $raw ?: $value ) ? '-is-valid' : '-not-valid',
						$raw ?: $value );

				case 'isbn':
					return Info::lookupISBN( $raw ?: $value );

				case 'date':
					return Datetime::prepForDisplay( $raw ?: $value, 'Y/m/d' );

				case 'datetime':
					return Datetime::prepForDisplay( $raw ?: $value, Datetime::isDateOnly( $raw ?: $value ) ? 'Y/m/d' : 'Y/m/d H:i' );

				case 'contact_method':
					return Core\URL::isValid( $raw ?: $value )
						? Core\HTML::link( Core\URL::prepTitle( $raw ?: $value ), $raw ?: $value )
						: sprintf( '<span title="%s">@%s</span>', empty( $field['title'] ) ? $field_key : Core\HTML::escapeAttr( $field['title'] ), $raw ?: $value );

				case 'email':
					return Core\Email::prep( $raw ?: $value, $field, 'admin' );

				case 'phone':
					return Core\Phone::prep( $raw ?: $value, $field, 'admin' );

				case 'mobile':
					return Core\Mobile::prep( $raw ?: $value, $field, 'admin' );
			}
		}

		// NOTE: third priority: general field
		switch ( $field_key ) {
			case 'title'      : return self::prepTitle( $raw ?: $value );
			case 'desc'       : return self::prepDescription( $raw ?: $value );
			case 'description': return self::prepDescription( $raw ?: $value );
			case 'contact'    : return self::prepContact( $raw ?: $value );
		}

		// NOTE: forth priority: last resort
		if ( array_key_exists( 'ltr', $field ) && $field['ltr'] )
			return sprintf( '<span dir="ltr">%s</span>', Core\HTML::escape( trim( $value ) ) );

		return Core\HTML::escape( trim( $value ) );
	}

	public static function renderPostTermsEditRow( $post, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $object->name, $post ) )
			return;

		$list = [];

		foreach ( $terms as $term ) {

			$query = [];

			if ( 'post' != $post->post_type )
				$query['post_type'] = $post->post_type;

			if ( $object->query_var ) {
				$query[$object->query_var] = $term->slug;

			} else {
				$query['taxonomy'] = $object->name;
				$query['term']     = $term->slug;
			}

			$list[] = Core\HTML::tag( 'a', [
				'href'  => add_query_arg( $query, 'edit.php' ),
				'title' => urldecode( $term->slug ),
				'class' => '-term',
			], sanitize_term_field( 'name', $term->name, $term->term_id, $object->name, 'display' ) );
		}

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	public static function renderTaxonomyTermsEditRow( $object, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $object = WordPress\Term::get( $object ) )
			return;

		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = wp_get_object_terms( $object->term_id, $taxonomy->name, [ 'update_term_meta_cache' => FALSE ] ) )
			return;

		$list = [];
		$link = sprintf( 'edit-tags.php?taxonomy=%s', $object->taxonomy );

		foreach ( $terms as $term )
			$list[] = Core\HTML::tag( 'a', [
				// better to pass the term_id instead of term slug
				'href'  => add_query_arg( [ $term->taxonomy => $term->term_id ], $link ),
				'title' => urldecode( $term->slug ),
				'class' => '-term -taxonomy-term',
			], Core\HTML::escape( sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) ) );

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	public static function renderUserTermsEditRow( $user_id, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = wp_get_object_terms( $user_id, $taxonomy->name, [ 'update_term_meta_cache' => FALSE ] ) )
			return;

		$list = [];
		$link = 'users.php?%1$s=%2$s';

		foreach ( $terms as $term )
			$list[] = Core\HTML::tag( 'a', [
				'href'  => sprintf( $link, $taxonomy->name, $term->slug ),
				'title' => urldecode( $term->slug ),
				'class' => '-term -user-term',
			], Core\HTML::escape( sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) ) );

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	public static function getAuthorsEditRow( $authors, $posttype = 'post', $before = '', $after = '' )
	{
		if ( empty( $authors ) )
			return;

		$list = [];

		foreach ( $authors as $author )
			if ( $html = Core\WordPress::getAuthorEditHTML( $posttype, $author ) )
				$list[] = $html;

		echo WordPress\Strings::getJoined( $list, $before, $after );
	}

	// NOTE: the output of `the_title()` is unescaped
	// @REF: https://make.wordpress.org/core/handbook/testing/reporting-security-vulnerabilities/#why-are-some-users-allowed-to-post-unfiltered-html
	public static function getPostTitleRow( $post, $link = 'edit', $status = FALSE, $title_attr = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return Plugin::na( FALSE );

		$title = WordPress\Post::title( $post );
		$after = '';

		if ( $status ) {

			$statuses = TRUE === $status ? WordPress\Status::get() : $status;

			if ( 'publish' != $post->post_status ) {

				if ( 'inherit' == $post->post_status && 'attachment' == $post->post_type )
					$status = '';
				else if ( isset( $statuses[$post->post_status] ) )
					$status = $statuses[$post->post_status];
				else
					$status = $post->post_status;

				if ( $status )
					$after = ' <small class="-status" title="'.Core\HTML::escape( $post->post_status ).'">('.$status.')</small>';
			}
		}

		if ( ! $link )
			return $title.$after;

		if ( 'posttype' === $title_attr )
			$title_attr = WordPress\PostType::object( $post->post_type )->label;

		$edit = current_user_can( 'edit_post', $post->ID );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => Core\WordPress::getPostEditLink( $post->ID ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], $title ).$after;

		if ( 'view' == $link && ! $edit && 'publish' != get_post_status( $post ) )
			return Core\HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
				// 'data'  => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], $title ).$after;

		if ( 'view' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => Core\WordPress::getPostShortLink( $post->ID ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], $title ).$after;

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
			'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
		], $title ).$after;
	}

	public static function getTermTitleRow( $term, $link = 'edit', $taxonomy = FALSE, $title_attr = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return Plugin::na( FALSE );

		$title = WordPress\Term::title( $term );
		$after = '';

		if ( $taxonomy )
			$after = ' <small class="-taxonomy" title="'.Core\HTML::escape( $term->taxonomy ).'">('.WordPress\Taxonomy::object( $term->taxonomy )->label.')</small>';

		if ( ! $link )
			return Core\HTML::escape( $title ).$after;

		$edit = current_user_can( 'edit_term', $term->term_id );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => Core\WordPress::getEditTaxLink( $term->taxonomy, $term->term_id ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], Core\HTML::escape( $title ) ).$after;

		if ( 'view' == $link && ! $edit && ! WordPress\Taxonomy::viewable( $term->taxonomy ) )
			return Core\HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
				// 'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], Core\HTML::escape( $title ) ).$after;

		if ( 'view' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => Core\WordPress::getTermShortLink( $term->term_id ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], Core\HTML::escape( $title ) ).$after;

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
			'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
		], Core\HTML::escape( $title ) ).$after;
	}

	// TODO: move to `Visual`
	public static function getAdminBarIcon( $icon = 'screenoptions', $style = 'margin:2px 1px 0 1px;' )
	{
		return Core\HTML::tag( 'span', [
			'class' => [
				'ab-icon',
				'dashicons',
				'dashicons-'.$icon,
			],
			'style' => $style,
		], NULL );
	}

	// TODO: move to `Visual`
	public static function getPostTypeIcon( $posttype, $fallback = 'admin-post' )
	{
		$object = WordPress\PostType::object( $posttype );

		if ( $object->menu_icon && is_string( $object->menu_icon ) ) {

			if ( Core\Text::has( $object->menu_icon, 'data:image/svg+xml;base64,' ) )
				return Core\Icon::wrapBase64( $object->menu_icon );


			if ( Core\Text::has( $object->menu_icon, 'dashicons-' ) )
				return Core\HTML::getDashicon( str_ireplace( 'dashicons-', '', $object->menu_icon ) );

			return Core\Icon::wrapURL( esc_url( $object->menu_icon ) );
		}

		return Core\HTML::getDashicon( $fallback );
	}

	// DEPRECATED
	public static function getEditorialUserID( $fallback = FALSE )
	{
		self::_dep();
		return gEditorial()->user( $fallback );
	}

	// TODO: `line-count`
	// TODO: move to `Info`
	public static function renderEditorStatusInfo( $target )
	{
		echo '<div class="-wrap -editor-status-info">';

			echo '<div data-target="'.$target.'" class="-status-count hide-if-no-js">';

				/* translators: %s: words count */
				printf( _x( 'Words: %s', 'Helper: WordCount', 'geditorial' ),
					'<span class="word-count">'.Core\Number::format( '0' ).'</span>' );

				echo '&nbsp;|&nbsp;';

				/* translators: %s: chars count */
				printf( _x( 'Chars: %s', 'Helper: WordCount', 'geditorial' ),
					'<span class="char-count">'.Core\Number::format( '0' ).'</span>' );

			echo '</div>';

			do_action( static::BASE.'_editor_status_info', $target );

		echo '</div>';
	}

	// TODO: move to `WordPress\Strings`
	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.$class.'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.Core\HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	public static function htmlCount( $count, $title_attr = NULL, $empty = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Helper: No Count Title Attribute', 'geditorial' );

		if ( is_array( $count ) )
			$count = count( $count );

		return $count
			? Core\Number::format( $count )
			: ( is_null( $empty ) ? self::htmlEmpty( 'column-count-empty', $title_attr ) : $empty );
	}

	public static function htmlOrder( $order, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Order', 'Helper: No Order Title Attribute', 'geditorial' );

		if ( $order )
			$html = Core\Number::localize( $order );
		else
			$html = sprintf( '<span title="%s" class="column-order-empty -empty">&mdash;</span>', $title_attr );

		return $html;
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( empty( $timestamp ) )
			return self::htmlEmpty();

		if ( ! Core\Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = Datetime::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.Core\HTML::escape( date_i18n( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.date_i18n( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= Core\HTML::escape( date_i18n( $formats['fulltime'], $timestamp ) ).'">';
		$html.= Datetime::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? Core\HTML::wrap( $html, $class, FALSE ) : $html;
	}

	public static function getModifiedEditRow( $post, $class = FALSE )
	{
		$timestamp = strtotime( $post->post_modified );
		$formats   = Datetime::dateFormats( FALSE );

		$html = '<span class="-date-modified" title="'.Core\HTML::escape( date_i18n( $formats['default'], $timestamp ) );
		$html.='" data-time="'.date( 'c', $timestamp ).'">'.Datetime::humanTimeDiff( $timestamp ).'</span>';

		$edit_last = get_post_meta( $post->ID, '_edit_last', TRUE );

		if ( $edit_last && $post->post_author != $edit_last )
			$html.= '&nbsp;(<span class="-edit-last">'.Core\WordPress::getAuthorEditHTML( $post->post_type, $edit_last ).'</span>)';

		return $class ? Core\HTML::wrap( $html, $class, FALSE ) : $html;
	}

	// @SOURCE: `translate_nooped_plural()`
	public static function nooped( $count, $nooped )
	{
		if ( ! array_key_exists( 'domain', $nooped ) )
			$nooped['domain'] = static::BASE;

		if ( empty( $nooped['domain'] ) )
			return _n( $nooped['singular'], $nooped['plural'], $count );

		else if ( empty( $nooped['context'] ) )
			return _n( $nooped['singular'], $nooped['plural'], $count, $nooped['domain'] );

		else
			return _nx( $nooped['singular'], $nooped['plural'], $count, $nooped['context'], $nooped['domain'] );
	}

	public static function noopedCount( $count, $nooped )
	{
		/* translators: singular/plural */
		return 'plural' == _x( 'plural', 'Helper: Nooped Count', 'geditorial' )
			? self::nooped( $count, $nooped )
			: self::nooped( 1, $nooped );
	}

	private static function getStringsFromName( $name )
	{
		if ( ! is_array( $name ) ) {

			if ( FALSE === ( $noop = Info::getNoop( $name, FALSE ) ) )
				$name = [
					'singular' => $name,
					'plural'   => Core\L10n::pluralize( $name ),
				];

			else
				$name = $noop;
		}

		if ( array_key_exists( 'domain', $name ) )
			$strings = [
				_nx( $name['singular'], $name['plural'], 2, $name['context'], $name['domain'] ),
				_nx( $name['singular'], $name['plural'], 1, $name['context'], $name['domain'] ),
			];

		else
			$strings = [
				$name['plural'],
				$name['singular'],
			];

		$strings[2] = Core\Text::strToLower( $strings[0] );
		$strings[3] = Core\Text::strToLower( $strings[1] );

		$strings[4] = '%s';

		return $strings;
	}

	/**
	 * %1$s => Camel Case / Plural   : Posts
	 * %2$s => Camel Case / Singular : Post
	 * %3$s => Lower Case / Plural   : posts
	 * %4$s => Lower Case / Singular : post
	 * %5$s => %s
	 *
	 * TODO: add raw name strings on the object
	 *
	 * @REF: `get_post_type_labels()`
	 * @REF: `_get_custom_object_labels()`
	 * @REF: `_nx_noop()`
	 * @REF: `translate_nooped_plural()`
	 */
	public static function generatePostTypeLabels( $name, $featured = FALSE, $pre = [], $posttype = NULL )
	{
		$strings = self::getStringsFromName( $name );

		$name_templates = apply_filters( static::BASE.'_posttype_labels_name_templates', [
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'name'                     => _x( '%1$s', 'Helper: CPT Generator: `name`', 'geditorial' ),
			// 'menu_name'                => _x( '%1$s', 'Helper: CPT Generator: `menu_name`', 'geditorial' ),
			// 'description'              => _x( '%1$s', 'Helper: CPT Generator: `description`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'singular_name'            => _x( '%2$s', 'Helper: CPT Generator: `singular_name`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'add_new'                  => _x( 'Add New', 'Helper: CPT Generator: `add_new`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'add_new_item'             => _x( 'Add New %2$s', 'Helper: CPT Generator: `add_new_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'edit_item'                => _x( 'Edit %2$s', 'Helper: CPT Generator: `edit_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'new_item'                 => _x( 'New %2$s', 'Helper: CPT Generator: `new_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'view_item'                => _x( 'View %2$s', 'Helper: CPT Generator: `view_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'view_items'               => _x( 'View %1$s', 'Helper: CPT Generator: `view_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'search_items'             => _x( 'Search %1$s', 'Helper: CPT Generator: `search_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'not_found'                => _x( 'No %3$s found.', 'Helper: CPT Generator: `not_found`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'not_found_in_trash'       => _x( 'No %3$s found in Trash.', 'Helper: CPT Generator: `not_found_in_trash`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'parent_item_colon'        => _x( 'Parent %2$s:', 'Helper: CPT Generator: `parent_item_colon`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'all_items'                => _x( 'All %1$s', 'Helper: CPT Generator: `all_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'archives'                 => _x( '%2$s Archives', 'Helper: CPT Generator: `archives`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'attributes'               => _x( '%2$s Attributes', 'Helper: CPT Generator: `attributes`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'insert_into_item'         => _x( 'Insert into %4$s', 'Helper: CPT Generator: `insert_into_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'uploaded_to_this_item'    => _x( 'Uploaded to this %4$s', 'Helper: CPT Generator: `uploaded_to_this_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'filter_items_list'        => _x( 'Filter %3$s list', 'Helper: CPT Generator: `filter_items_list`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'filter_by_date'           => _x( 'Filter by date', 'Helper: CPT Generator: `filter_by_date`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'items_list_navigation'    => _x( '%1$s list navigation', 'Helper: CPT Generator: `items_list_navigation`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'items_list'               => _x( '%1$s list', 'Helper: CPT Generator: `items_list`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_published'           => _x( '%2$s published.', 'Helper: CPT Generator: `item_published`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_published_privately' => _x( '%2$s published privately.', 'Helper: CPT Generator: `item_published_privately`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_reverted_to_draft'   => _x( '%2$s reverted to draft.', 'Helper: CPT Generator: `item_reverted_to_draft`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_trashed'             => _x( '%2$s trashed.', 'Helper: CPT Generator: `item_trashed`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_scheduled'           => _x( '%2$s scheduled.', 'Helper: CPT Generator: `item_scheduled`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_updated'             => _x( '%2$s updated.', 'Helper: CPT Generator: `item_updated`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_link'                => _x( '%2$s Link', 'Helper: CPT Generator: `item_link`', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_link_description'    => _x( 'A link to a %4$s.', 'Helper: CPT Generator: `item_link_description`', 'geditorial' ),
		], $posttype, $strings, $name );

		$featured_templates = apply_filters( static::BASE.'_posttype_labels_featured_templates', [
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'featured_image'        => _x( '%1$s', 'Helper: CPT Generator: `featured_image`', 'geditorial' ),
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'set_featured_image'    => _x( 'Set %2$s', 'Helper: CPT Generator: `set_featured_image`', 'geditorial' ),
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'remove_featured_image' => _x( 'Remove %2$s', 'Helper: CPT Generator: `remove_featured_image`', 'geditorial' ),
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'use_featured_image'    => _x( 'Use as %2$s', 'Helper: CPT Generator: `use_featured_image`', 'geditorial' ),
		], $posttype, $featured, $name );

		foreach ( $name_templates as $key => $template )
			if ( ! array_key_exists( $key, $pre ) )
				$pre[$key] = vsprintf( $template, $strings );

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'extended_label', $pre ) )
			$pre['extended_label'] = $pre['name'];

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'name_admin_bar', $pre ) )
			$pre['name_admin_bar'] = $strings[1];

		if ( ! array_key_exists( 'column_title', $pre ) )
			$pre['column_title'] = $strings[0];

		if ( ! array_key_exists( 'metabox_title', $pre ) )
			$pre['metabox_title'] = $strings[0];

		// EXTRA: must be in the core!
		if ( ! array_key_exists( 'author_label', $pre ) )
			$pre['author_label'] = __( 'Author' );

		// EXTRA: must be in the core!
		if ( ! array_key_exists( 'excerpt_label', $pre ) )
			$pre['excerpt_label'] = __( 'Excerpt' );

		if ( ! $featured && array_key_exists( 'featured_image', $pre ) )
			$featured = $pre['featured_image'];

		if ( $featured )
			foreach ( $featured_templates as $key => $template )
				if ( ! array_key_exists( $key, $pre ) )
					$pre[$key] = vsprintf( $template, [ $featured, Core\Text::strToLower( $featured ) ] );

		return $pre;
	}

	public static function getPostTypeLabel( $posttype, $label, $fallback_key = NULL, $fallback = '' )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return $fallback ?? Plugin::na();

		if ( isset( $object->labels->{$label} ) )
			return $object->labels->{$label};

		$name = [
			'plural'   => $object->labels->name,
			'singular' => $object->labels->singular_name,
		];

		switch ( $label ) {

			case 'noop':
				return $name;

			case 'extended_label':
				return $object->labels->name;

			case 'paired_no_items':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( 'There are no items available!', 'Helper: PostType Label: `paired_no_items`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'paired_has_items':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( 'The %2$s has %5$s.', 'Helper: PostType Label: `paired_has_items`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'paired_connected_to':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( 'Connected to %5$s', 'Helper: PostType Label: `paired_connected_to`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'paired_mean_age':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( 'The %2$s Mean-age is %5$s.', 'Helper: PostType Label: `paired_mean_age`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'show_option_no_items':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( '(No %3$s)', 'Helper: PostType Label: `show_option_no_items`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'show_option_select':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( '&ndash; Select %2$s &ndash;', 'Helper: PostType Label: `show_option_select`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'show_option_all':
				return $object->labels->all_items;

			case 'show_option_none':
				return sprintf( '&ndash; %s &ndash;', Settings::showRadioNone() );

			case 'show_option_parent':
				return sprintf( '&ndash; %s &ndash;', trim( $object->labels->parent_item_colon, ':' ) );

			case 'import_items':
				/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
				return vsprintf( _x( 'Import %1$s', 'Helper: PostType Label: `import_items`', 'geditorial' ), self::getStringsFromName( $name ) );
		}

		if ( $fallback_key && isset( $object->labels->{$fallback_key} ) )
			return $object->labels->{$fallback_key};

		return $fallback;
	}

	/**
	 * %1$s => Camel Case / Plural   : Tags
	 * %2$s => Camel Case / Singular : Tag
	 * %3$s => Lower Case / Plural   : tags
	 * %4$s => Lower Case / Singular : tag
	 * %5$s => %s
	 *
	 * @REF: `_nx_noop()`
	 * @REF: `translate_nooped_plural()`
	 */
	public static function generateTaxonomyLabels( $name, $pre = [], $taxonomy = NULL )
	{
		$strings = self::getStringsFromName( $name );

		$templates = apply_filters( static::BASE.'_taxonomy_labels_templates', [
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'name'                       => _x( '%1$s', 'Helper: Tax Generator: `name`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			// 'menu_name'                  => _x( '%1$s', 'Helper: Tax Generator: `menu_name`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'singular_name'              => _x( '%2$s', 'Helper: Tax Generator: `singular_name`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'search_items'               => _x( 'Search %1$s', 'Helper: Tax Generator: `search_items`', 'geditorial' ),
			'popular_items'              => NULL, // _x( 'Popular %1$s', 'Helper: Tax Generator: `popular_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'all_items'                  => _x( 'All %1$s', 'Helper: Tax Generator: `all_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'parent_item'                => _x( 'Parent %2$s', 'Helper: Tax Generator: `parent_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'parent_item_colon'          => _x( 'Parent %2$s:', 'Helper: Tax Generator: `parent_item_colon`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'edit_item'                  => _x( 'Edit %2$s', 'Helper: Tax Generator: `edit_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'view_item'                  => _x( 'View %2$s', 'Helper: Tax Generator: `view_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'update_item'                => _x( 'Update %2$s', 'Helper: Tax Generator: `update_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'add_new_item'               => _x( 'Add New %2$s', 'Helper: Tax Generator: `add_new_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'new_item_name'              => _x( 'New %2$s Name', 'Helper: Tax Generator: `new_item_name`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'separate_items_with_commas' => _x( 'Separate %3$s with commas', 'Helper: Tax Generator: `separate_items_with_commas`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'add_or_remove_items'        => _x( 'Add or remove %3$s', 'Helper: Tax Generator: `add_or_remove_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'choose_from_most_used'      => _x( 'Choose from the most used %3$s', 'Helper: Tax Generator: `choose_from_most_used`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'not_found'                  => _x( 'No %3$s found.', 'Helper: Tax Generator: `not_found`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'no_terms'                   => _x( 'No %3$s', 'Helper: Tax Generator: `no_terms`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'filter_by_item'             => _x( 'Filter by %4$s', 'Helper: Tax Generator: `filter_by_item`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'items_list_navigation'      => _x( '%1$s list navigation', 'Helper: Tax Generator: `items_list_navigation`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'items_list'                 => _x( '%1$s list', 'Helper: Tax Generator: `items_list`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'back_to_items'              => _x( '&larr; Back to %1$s', 'Helper: Tax Generator: `back_to_items`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'item_link'                  => _x( '%2$s Link', 'Helper: Tax Generator: `item_link`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'item_link_description'      => _x( 'A link to a %4$s.', 'Helper: Tax Generator: `item_link_description`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			// 'name_field_description'     => _x( 'The name is how it appears on your site.', 'Helper: Tax Generator: `name_field_description`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			// 'slug_field_description'     => _x( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'Helper: Tax Generator: `slug_field_description`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			// 'parent_field_description'   => _x( 'Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.', 'Helper: Tax Generator: `parent_field_description`', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			// 'desc_field_description'     => _x( 'The description is not prominent by default; however, some themes may show it.', 'Helper: Tax Generator: `desc_field_description`', 'geditorial' ),
		], $taxonomy, $strings, $name );

		foreach ( $templates as $key => $template )
			if ( $template && ! array_key_exists( $key, $pre ) )
				$pre[$key] = vsprintf( $template, $strings );

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'most_used', $pre ) )
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			$pre['most_used'] = vsprintf( _x( 'Most Used', 'Helper: Tax Generator', 'geditorial' ), $strings );

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'extended_label', $pre ) )
			$pre['extended_label'] = $pre['name'];

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'column_title', $pre ) )
			$pre['column_title'] = $strings[0];

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'metabox_title', $pre ) )
			$pre['metabox_title'] = $strings[0];

		if ( ! array_key_exists( 'desc_field_title', $pre ) )
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			$pre['desc_field_title'] = vsprintf( _x( 'Description', 'Helper: Taxonomy Label: `desc_field_title`', 'geditorial' ), $strings );

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'uncategorized', $pre ) )
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			$pre['uncategorized'] = vsprintf( _x( 'Uncategorized', 'Helper: Taxonomy Label: `uncategorized`', 'geditorial' ), $strings );

		// NOTE: this is plugin custom
		if ( ! array_key_exists( 'no_items_available', $pre ) )
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			$pre['no_items_available'] = vsprintf( _x( 'There are no %3$s available!', 'Helper: Taxonomy Label: `no_items_available`', 'geditorial' ), $strings );

		return $pre;
	}

	public static function getTaxonomyLabel( $taxonomy, $label, $fallback_key = NULL, $fallback = '' )
	{
		if ( ! $object = WordPress\Taxonomy::object( $taxonomy ) )
			return $fallback ?? Plugin::na();

		if ( isset( $object->labels->{$label} ) )
			return $object->labels->{$label};

		$name = [
			'plural'   => $object->labels->name,
			'singular' => $object->labels->singular_name,
		];

		switch ( $label ) {

			case 'noop':
				return $name;

			case 'extended_label':
				return $object->labels->name;

			case 'show_option_no_items':
				return sprintf( '(%s)', $object->labels->no_terms );

			case 'show_option_select':
				/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
				return vsprintf( _x( '&ndash; Select %2$s &ndash;', 'Helper: Taxonomy Label: `show_option_select`', 'geditorial' ), self::getStringsFromName( $name ) );

			case 'show_option_all':
				return $object->labels->all_items;

			case 'show_option_none':
				return sprintf( '&ndash; %s &ndash;', Settings::showRadioNone() );

			case 'show_option_parent':
				return sprintf( '&ndash; %s &ndash;', $object->labels->parent_item );

			case 'import_items':
				/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
				return vsprintf( _x( 'Import %1$s', 'Helper: Taxonomy Label: `import_items`', 'geditorial' ), self::getStringsFromName( $name ) );
		}

		if ( $fallback_key && isset( $object->labels->{$fallback_key} ) )
			return $object->labels->{$fallback_key};

		return $fallback;
	}

	/**
	 * %1$s => Camel Case / Plural
	 * %2$s => Camel Case / Singular
	 * %3$s => Lower Case / Plural
	 * %4$s => Lower Case / Singular
	 * %5$s => %s
	 */
	public static function generatePostTypeMessages( $name, $posttype = NULL )
	{
		global $post_type_object, $post, $post_ID;

		$strings = self::getStringsFromName( $name );

		$templates = apply_filters( static::BASE.'_posttype_message_templates', [
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'view_post'                      => _x( 'View %4$s', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'preview_post'                   => _x( 'Preview %4$s', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_updated'                   => _x( '%2$s updated.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'custom_field_updated'           => _x( 'Custom field updated.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'custom_field_deleted'           => _x( 'Custom field deleted.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_restored_to_revision_from' => _x( '%2$s restored to revision from %5$s.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_published'                 => _x( '%2$s published.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_saved'                     => _x( '%2$s saved.' , 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_submitted'                 => _x( '%2$s submitted.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_scheduled_for'             => _x( '%2$s scheduled for: %5$s.', 'Helper: PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'post_draft_updated'             => _x( '%2$s draft updated.', 'Helper: PostType Message Generator', 'geditorial' ),
		], $posttype, $strings, $name );

		$messages = [];

		foreach ( $templates as $key => $template )
			$messages[$key] = vsprintf( $template, $strings );

		if ( ! $permalink = get_permalink( $post_ID ) )
			$permalink = '';

		$preview = $scheduled = $view = '';
		$scheduled_date = Datetime::dateFormat( $post->post_date, 'datetime' );

		if ( WordPress\PostType::viewable( $post_type_object ) ) {
			$view      = ' '.Core\HTML::link( $messages['view_post'], $permalink );
			$preview   = ' '.Core\HTML::link( $messages['preview_post'], get_preview_post_link( $post ), TRUE );
			$scheduled = ' '.Core\HTML::link( $messages['preview_post'], $permalink, TRUE );
		}

		return [
			0  => '', // Unused. Messages start at index 1.
			1  => $messages['post_updated'].$view,
			2  => $messages['custom_field_updated'],
			3  => $messages['custom_field_deleted'],
			4  => $messages['post_updated'],
			5  => isset( $_GET['revision'] ) ? sprintf( $messages['post_restored_to_revision_from'], wp_post_revision_title( (int) $_GET['revision'], FALSE ) ) : FALSE,
			6  => $messages['post_published'].$view,
			7  => $messages['post_saved'],
			8  => $messages['post_submitted'].$preview,
			9  => sprintf( $messages['post_scheduled_for'], '<strong>'.$scheduled_date.'</strong>' ).$scheduled,
			10 => $messages['post_draft_updated'].$preview,
		];
	}

	/**
	 * %1$s => Camel Case / Plural
	 * %2$s => Camel Case / Singular
	 * %3$s => Lower Case / Plural
	 * %4$s => Lower Case / Singular
	 * %5$s => %s
	 */
	public static function generateBulkPostTypeMessages( $name, $counts, $posttype = NULL )
	{
		$strings = self::getStringsFromName( $name );

		$templates = apply_filters( static::BASE.'_posttype_bulk_message_templates', [
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'updated'   => _nx_noop( '%5$s %4$s updated.', '%5$s %3$s updated.', 'Helper: Bulk PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'locked'    => _nx_noop( '%5$s %4$s not updated, somebody is editing it.', '%5$s %3$s not updated, somebody is editing them.', 'Helper: Bulk PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'deleted'   => _nx_noop( '%5$s %4$s permanently deleted.', '%5$s %3$s permanently deleted.', 'Helper: Bulk PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'trashed'   => _nx_noop( '%5$s %4$s moved to the Trash.', '%5$s %3$s moved to the Trash.', 'Helper: Bulk PostType Message Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'untrashed' => _nx_noop( '%5$s %4$s restored from the Trash.', '%5$s %3$s restored from the Trash.', 'Helper: Bulk PostType Message Generator', 'geditorial' ),
		], $posttype, $strings, $name );

		$messages = [];

		foreach ( $templates as $key => $template )
			// needs to apply the role so we use noopedCount()
			$messages[$key] = vsprintf( self::noopedCount( $counts[$key], $template ), $strings );

		return $messages;
	}

	/**
	 * Switches posttype with PAIRED API support
	 *
	 * @param  int|object $post
	 * @param  string|object $posttype
	 * @return int|false $changed
	 */
	public static function switchPostType( $post, $posttype )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( $posttype->name === $post->post_type )
			return TRUE;

		$paired_from = Services\Paired::isPostType( $post->post_type );
		$paired_to   = Services\Paired::isPostType( $posttype->name );

		// neither is paired
		if ( ! $paired_from && ! $paired_to )
			return WordPress\Post::setPostType( $post, $posttype );

		// bail if paired term not defined
		if ( ! $term = Services\Paired::getToTerm( $post->ID, $post->post_type, $paired_from ) )
			return WordPress\Post::setPostType( $post, $posttype );

		// NOTE: the `term_id` remains intact
		if ( ! WordPress\Term::setTaxonomy( $term, $paired_to ) )
			return FALSE;

		if ( ! WordPress\Post::setPostType( $post, $posttype ) )
			return FALSE;

		delete_post_meta( $post->ID, '_'.$post->post_type.'_term_id' );
		delete_term_meta( $term->term_id, $post->post_type.'_linked' );

		update_post_meta( $post->ID, '_'.$posttype->name.'_term_id', $term->term_id );
		update_term_meta( $term->term_id, $posttype->name.'_linked', $post->ID );

		return TRUE;
	}

	public static function getLayout( $name, $require = FALSE, $no_cache = FALSE )
	{
		$content = WP_CONTENT_DIR.'/'.$name.'.php';
		$plugin  = GEDITORIAL_DIR.'includes/Layouts/'.$name.'.php';
		$layout  = locate_template( 'editorial/layouts/'.$name );

		if ( ! $layout && is_readable( $content ) )
			$layout = $content;

		if ( ! $layout && is_readable( $plugin ) )
			$layout = $plugin;

		if ( $no_cache && $layout )
			Core\WordPress::doNotCache();

		if ( $require && $layout )
			require_once $layout;
		else
			return $layout;
	}

	// TODO: maybe migrate to: https://github.com/jwage/easy-csv
	// @REF: https://github.com/kzykhys/PHPCsvParser
	public static function parseCSV( $file_path, $limit = NULL )
	{
		if ( empty( $file_path ) )
			return FALSE;

		// $iterator = new \SplFileObject( Core\File::normalize( $file_path ) );
		// $parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );

		$list = [];
		$args = [ 'encoding' => 'UTF-8' ];

		if ( ! is_null( $limit ) )
			$args['limit'] =  (int) $limit;

		$parser  = \KzykHys\CsvParser\CsvParser::fromFile( Core\File::normalize( $file_path ), $args );
		$items   = $parser->parse();
		$headers = $items[0];

		unset( $parser, $items[0] );

		foreach ( $items as $index => $data )
			if ( ! empty( $data ) )
				$list[] = array_combine( $headers, $data );

		unset( $headers, $items );

		return $list;
	}

	public static function parseJSON( $file_path )
	{
		if ( empty( $file_path ) )
			return FALSE;

		return json_decode( Core\File::getContents( $file_path ), TRUE );
	}

	public static function parseXML( $file_path )
	{
		if ( empty( $file_path ) || ! function_exists( 'xml_parser_create' ) )
			return FALSE;

		if ( ! $contents = Core\File::getContents( $file_path ) )
			return $contents;

		$parser = xml_parser_create();

		xml_parse_into_struct( $parser, $contents, $values, $index );
		xml_parser_free( $parser );

		return [ $values, $index ];
	}

	/**
	 * Generates simple XLSX data string from given data.
	 * @package `maksimovic/php-xlsx-writer`
	 * @REF: https://github.com/maksimovic/PHP_XLSXWriter
	 *
	 * @param  array  $data
	 * @param  array  $headers
	 * @param  string $sheet
	 * @param  array  $options
	 * @param  array  $styles
	 * @param  string $title
	 * @param  string $desc
	 * @return string $content
	 */
	public static function generateXLSX( $data, $headers = [], $sheet = NULL, $options = NULL, $styles = NULL, $title = NULL, $desc = NULL )
	{
		$writer = new \XLSXWriter();
		$writer->setTempDir( get_temp_dir() );

		if ( Core\HTML::rtl() )
			$writer->setRightToLeft( TRUE );

		if ( ! is_null( $title ) )
			$writer->setTitle( $title );

		if ( ! is_null( $desc ) )
			$writer->setDescription( $desc );

		if ( $user = gEditorial()->user() )
			$writer->setAuthor( get_userdata( $user )->display_name );

		if ( is_null( $sheet ) )
			$sheet = 'Sheet1';

		if ( is_null( $options ) )
			$options = [
				'border'      => 'left,right,top,bottom',
				'fill'        => '#eee',
				'font'        => 'Arial,Tahoma,sans-serif',
				'font-style'  => 'bold',
				'freeze_rows' => TRUE,
				'widths'      => array_fill( 0, count( $headers ), 20 ),
			];

		if ( is_null( $styles ) )
			$styles = [
				'border'    => 'left,right,top,bottom',
				'font'      => 'Segoe UI,Tahoma,sans-serif',
				'font-size' => 10,
			];

		if ( Core\Arraay::isList( $headers ) )
			$headers = array_combine( $headers, array_fill( 0, count( $headers ), 'string' ) );

		$writer->writeSheetHeader( $sheet, $headers, $options );

		foreach ( $data as $row )
			$writer->writeSheetRow( $sheet, $row, $styles );

		return $writer->writeToString();
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki
	public static function getMustache( $base = GEDITORIAL_DIR )
	{
		global $gEditorialMustache;

		if ( ! empty( $gEditorialMustache ) )
			return $gEditorialMustache;

		$gEditorialMustache = new \Mustache_Engine( [
			'template_class_prefix' => '__'.static::BASE.'_',
			'cache_file_mode'       => FS_CHMOD_FILE,
			// 'cache'                 => $base.'assets/views/cache',
			'cache'                 => get_temp_dir(),

			'loader'          => new \Mustache_Loader_FilesystemLoader( $base.'assets/views' ),
			'partials_loader' => new \Mustache_Loader_FilesystemLoader( $base.'assets/views/partials' ),
			'escape'          => static function ( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gEditorialMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = [], $verbose = TRUE )
	{
		$engine = self::getMustache();
		$html   = $engine->loadTemplate( $part )->render( $data );

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public static function mdExtra( $markdown )
	{
		global $gEditorialMarkdownExtra;

		if ( empty( $markdown ) || ! class_exists( '\Michelf\MarkdownExtra' ) )
			return $markdown;

		if ( empty( $gEditorialMarkdownExtra ) )
			$gEditorialMarkdownExtra = new \Michelf\MarkdownExtra();

		return $gEditorialMarkdownExtra->defaultTransform( $markdown );
	}

	public static function getCacheDIR( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR )
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		$path = Core\File::normalize( GEDITORIAL_CACHE_DIR.( $base ? '/'.$base.'/' : '/' ).$sub );

		if ( file_exists( $path ) )
			return Core\URL::untrail( $path );

		if ( ! wp_mkdir_p( $path ) )
			return FALSE;

		Core\File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );

		return Core\URL::untrail( $path );
	}

	public static function getCacheURL( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR ) // correct, we check for path constant
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		return Core\URL::untrail( GEDITORIAL_CACHE_URL.( $base ? '/'.$base.'/' : '/' ).$sub );
	}

	/**
	 * Hides inline/bulk edit row action.
	 * @source https://core.trac.wordpress.org/ticket/19343
	 *
	 * @param  null|object $screen
	 * @return void
	 */
	public static function disableQuickEdit( $screen = NULL )
	{
		if ( is_null( $screen ) )
			$screen = get_current_screen();

		add_filter( 'page_row_actions', static function ( $actions, $post) use ( $screen ) {
			if ( $post->post_type === $screen->post_type )
				unset( $actions['inline hide-if-no-js'] );
			return $actions;
		}, 12, 2 );

		add_filter( 'post_row_actions', static function ( $actions, $post ) use ( $screen ) {
			if ( $post->post_type === $screen->post_type )
				unset( $actions['inline hide-if-no-js'] );
			return $actions;
		}, 12, 2 );

		add_filter( 'bulk_actions-'.$screen->id, static function ( $actions ) {
			unset( $actions['edit'] );
			return $actions;
		} );
	}

	/**
	 * Separates given string by set of delimiters into an array.
	 * NOTE: applys the plugin filter on default delimiters
	 *
	 * @param  string $string
	 * @param  null|string|array $delimiters
	 * @param  null|int $limit
	 * @param  string $delimiter
	 * @return array $separated
	 */
	public static function getSeparated( $string, $delimiters = NULL, $limit = NULL, $delimiter = '|' )
	{
		return WordPress\Strings::getSeparated(
			$string,
			$delimiters ?? self::getDelimiters( $delimiter ),
			$limit,
			$delimiter
		);
	}

	/**
	 * Retrieves the list of string delimiters.
	 *
	 * @param  string $default
	 * @return null|array $delimiters
	 */
	public static function getDelimiters( $default = '|' )
	{
		return apply_filters( static::BASE.'_string_delimiters',
			Core\Arraay::prepSplitters( GEDITORIAL_STRING_DELIMITERS, $default ) );
	}

	/**
	 * Logs a message given agent, level and context.
	 *
	 * @param  string      $message
	 * @param  null|string $agent
	 * @param  null|string $level
	 * @param  array       $context
	 * @return false
	 */
	public static function log( $message, $agent = NULL, $level = \null, $context = [] )
	{
		do_action( 'gnetwork_logger_site_'.strtolower( $level ?? 'NOTICE' ),
			strtoupper( $agent ?? static::BASE ),
			$message,
			$context
		);

		return FALSE; // to help the caller!
	}
}

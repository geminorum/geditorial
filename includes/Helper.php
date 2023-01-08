<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Date;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\WooCommerce;

class Helper extends Main
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

	public static function moduleEnabled( $options )
	{
		$enabled = isset( $options->enabled ) ? $options->enabled : FALSE;

		if ( 'off' === $enabled )
			return FALSE;

		if ( 'on' === $enabled )
			return TRUE;

		return $enabled;
	}

	public static function moduleCheckWooCommerce()
	{
		return WooCommerce::isActive() ? FALSE : _x( 'Needs WooCommerce', 'Helper', 'geditorial' );
	}

	public static function getIcon( $icon, $fallback = 'admin-post' )
	{
		if ( is_array( $icon ) )
			return gEditorial()->icon( $icon[1], $icon[0] );

		if ( ! $icon )
			return HTML::getDashicon( $fallback );

		return HTML::getDashicon( $icon );
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE, $verbose = TRUE )
	{
		return HTML::linkStyleSheet( $url, $version, $media, $verbose );
	}

	public static function linkStyleSheetAdmin( $page, $verbose = TRUE )
	{
		return HTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.( is_rtl() ? '-rtl' : '' ).'.css', GEDITORIAL_VERSION, 'all', $verbose );
	}

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

		return apply_filters( static::BASE.'_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	public static function ksesArray( $array, $context = 'none', $allowed = NULL )
	{
		foreach ( $array as $key => $value )
			$array[$key] = self::kses( $value, $context, $allowed );

		return $array;
	}

	public static function prepTitle( $text, $post_id = 0 )
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, $post_id );
		$text = apply_filters( 'string_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return trim( $text );
	}

	public static function prepDescription( $text, $shortcode = TRUE, $autop = TRUE )
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = apply_shortcodes( $text, TRUE );

		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : $text;
	}

	public static function prepContact( $value, $title = NULL )
	{
		if ( is_email( $value ) )
			$prepared = HTML::mailto( $value, $title );

		else if ( URL::isValid( $value ) )
			// $prepared = HTML::link( $title, URL::untrail( $value ) );
			$prepared = HTML::link( $title, URL::prepTitle( $value ) );

		else if ( is_numeric( str_ireplace( [ '+', '-', '.' ], '', $value ) ) )
			$prepared = HTML::tel( $value, FALSE, $title );

		else
			$prepared = HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	public static function renderPostTermsEditRow( $post, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $object = Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = Taxonomy::getPostTerms( $object->name, $post ) )
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

			$list[] = HTML::tag( 'a', [
				'href'  => add_query_arg( $query, 'edit.php' ),
				'title' => urldecode( $term->slug ),
				'class' => '-term',
			], HTML::escape( sanitize_term_field( 'name', $term->name, $term->term_id, $object->name, 'display' ) ) );
		}

		echo Strings::getJoined( $list, $before, $after );
	}

	public static function renderTaxonomyTermsEditRow( $object, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $object = Taxonomy::getTerm( $object ) )
			return;

		if ( ! $taxonomy = Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = wp_get_object_terms( $object->term_id, $taxonomy->name, [ 'update_term_meta_cache' => FALSE ] ) )
			return;

		$list = [];
		$link = sprintf( 'edit-tags.php?taxonomy=%s', $object->taxonomy );

		foreach ( $terms as $term )
			$list[] = HTML::tag( 'a', [
				// better to pass the term_id instead of term slug
				'href'  => add_query_arg( [ $term->taxonomy => $term->term_id ], $link ),
				'title' => urldecode( $term->slug ),
				'class' => '-term -taxonomy-term',
			], HTML::escape( sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) ) );

		echo Strings::getJoined( $list, $before, $after );
	}

	public static function renderUserTermsEditRow( $user_id, $taxonomy, $before = '', $after = '' )
	{
		if ( ! $taxonomy = Taxonomy::object( $taxonomy ) )
			return;

		if ( ! $terms = wp_get_object_terms( $user_id, $taxonomy->name, [ 'update_term_meta_cache' => FALSE ] ) )
			return;

		$list = [];
		$link = 'users.php?%1$s=%2$s';

		foreach ( $terms as $term )
			$list[] = HTML::tag( 'a', [
				'href'  => sprintf( $link, $taxonomy->name, $term->slug ),
				'title' => urldecode( $term->slug ),
				'class' => '-term -user-term',
			], HTML::escape( sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) ) );

		echo Strings::getJoined( $list, $before, $after );
	}

	public static function getAuthorsEditRow( $authors, $posttype = 'post', $before = '', $after = '' )
	{
		if ( empty( $authors ) )
			return;

		$list = [];

		foreach ( $authors as $author )
			if ( $html = WordPress::getAuthorEditHTML( $posttype, $author ) )
				$list[] = $html;

		echo Strings::getJoined( $list, $before, $after );
	}

	public static function getPostTitleRow( $post, $link = 'edit', $status = FALSE, $title_attr = NULL )
	{
		if ( ! $post = PostType::getPost( $post ) )
			return Plugin::na( FALSE );

		$title = PostType::getPostTitle( $post );
		$after = '';

		if ( $status ) {

			$statuses = TRUE === $status ? PostType::getStatuses() : $status;

			if ( 'publish' != $post->post_status ) {

				if ( 'inherit' == $post->post_status && 'attachment' == $post->post_type )
					$status = '';
				else if ( isset( $statuses[$post->post_status] ) )
					$status = $statuses[$post->post_status];
				else
					$status = $post->post_status;

				if ( $status )
					$after = ' <small class="-status" title="'.HTML::escape( $post->post_status ).'">('.$status.')</small>';
			}
		}

		if ( ! $link )
			return HTML::escape( $title ).$after;

		if ( 'posttype' === $title_attr )
			$title_attr = PostType::object( $post->post_type )->label;

		$edit = current_user_can( 'edit_post', $post->ID );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getPostEditLink( $post->ID ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], HTML::escape( $title ) ).$after;

		if ( 'view' == $link && ! $edit && 'publish' != get_post_status( $post ) )
			return HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
				// 'data'  => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], HTML::escape( $title ) ).$after;

		if ( 'view' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getPostShortLink( $post->ID ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
			], HTML::escape( $title ) ).$after;

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
			'data'   => [ 'post' => $post->ID, 'type' => $post->post_type ],
		], HTML::escape( $title ) ).$after;
	}

	public static function getTermTitleRow( $term, $link = 'edit', $taxonomy = FALSE, $title_attr = NULL )
	{
		if ( ! $term = Taxonomy::getTerm( $term ) )
			return Plugin::na( FALSE );

		$title = Taxonomy::getTermTitle( $term );
		$after = '';

		if ( $taxonomy )
			$after = ' <small class="-taxonomy" title="'.HTML::escape( $term->taxonomy ).'">('.Taxonomy::object( $term->taxonomy )->label.')</small>';

		if ( ! $link )
			return HTML::escape( $title ).$after;

		$edit = current_user_can( 'edit_term', $term->term_id );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getEditTaxLink( $term->taxonomy, $term->term_id ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], HTML::escape( $title ) ).$after;

		if ( 'view' == $link && ! $edit && ! is_taxonomy_viewable( $term->taxonomy ) )
			return HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
				// 'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], HTML::escape( $title ) ).$after;

		if ( 'view' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getTermShortLink( $term->term_id ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
				'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
			], HTML::escape( $title ) ).$after;

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
			'data'   => [ 'term' => $term->term_id, 'taxonomy' => $term->taxonomy ],
		], HTML::escape( $title ) ).$after;
	}

	public static function getExtension( $mime_type, $extensions )
	{
		if ( FALSE === ( $key = array_search( $mime_type, $extensions ) ) )
			return FALSE;

		$ext = explode( '|', $key );
		return strtoupper( $ext[0] );
	}

	public static function getAdminBarIcon( $icon = 'screenoptions', $style = 'margin:2px 1px 0 1px;' )
	{
		return HTML::tag( 'span', [
			'class' => [
				'ab-icon',
				'dashicons',
				'dashicons-'.$icon,
			],
			'style' => $style,
		], NULL );
	}

	public static function getPostTypeIcon( $posttype, $fallback = 'admin-post' )
	{
		$object = PostType::object( $posttype );

		if ( $object->menu_icon && is_string( $object->menu_icon ) ) {

			if ( Text::has( $object->menu_icon, 'data:image/svg+xml;base64,' ) )
				return Icon::wrapBase64( $object->menu_icon );


			if ( Text::has( $object->menu_icon, 'dashicons-' ) )
				return HTML::getDashicon( str_ireplace( 'dashicons-', '', $object->menu_icon ) );

			return Icon::wrapURL( esc_url( $object->menu_icon ) );
		}

		return HTML::getDashicon( $fallback );
	}

	public static function ipLookup( $ip )
	{
		if ( function_exists( 'gnetwork_ip_lookup' ) )
			return gnetwork_ip_lookup( $ip );

		return $ip;
	}

	// DEPRECATED
	public static function getEditorialUserID( $fallback = FALSE )
	{
		self::_dep();
		return gEditorial()->user( $fallback );
	}

	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.$class.'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	public static function htmlCount( $count, $title_attr = NULL, $empty = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Helper: No Count Title Attribute', 'geditorial' );

		if ( is_array( $count ) )
			$count = count( $count );

		return $count
			? Number::format( $count )
			: ( is_null( $empty ) ? self::htmlEmpty( 'column-count-empty', $title_attr ) : $empty );
	}

	public static function htmlOrder( $order, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Order', 'Helper: No Order Title Attribute', 'geditorial' );

		if ( $order )
			$html = Number::localize( $order );
		else
			$html = sprintf( '<span title="%s" class="column-order-empty -empty">&mdash;</span>', $title_attr );

		return $html;
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( empty( $timestamp ) )
			return self::htmlEmpty();

		if ( ! Date::isTimestamp( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = Datetime::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.HTML::escape( date_i18n( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.date_i18n( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= HTML::escape( date_i18n( $formats['fulltime'], $timestamp ) ).'">';
		$html.= Datetime::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? HTML::wrap( $html, $class, FALSE ) : $html;
	}

	public static function getModifiedEditRow( $post, $class = FALSE )
	{
		$timestamp = strtotime( $post->post_modified );
		$formats   = Datetime::dateFormats( FALSE );

		$html = '<span class="-date-modified" title="'.HTML::escape( date_i18n( $formats['default'], $timestamp ) );
		$html.='" data-time="'.date( 'c', $timestamp ).'">'.Datetime::humanTimeDiff( $timestamp ).'</span>';

		$edit_last = get_post_meta( $post->ID, '_edit_last', TRUE );

		if ( $edit_last && $post->post_author != $edit_last )
			$html.= '&nbsp;(<span class="-edit-last">'.WordPress::getAuthorEditHTML( $post->post_type, $edit_last ).'</span>)';

		return $class ? HTML::wrap( $html, $class, FALSE ) : $html;
	}

	// @SOURCE: `translate_nooped_plural()`
	public static function nooped( $count, $nooped )
	{
		if ( ! array_key_exists( 'domain', $nooped ) )
			$nooped['domain'] = 'geditorial';

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
		if ( ! is_array( $name ) )
			return [
				$name.'s',
				$name,
				Text::strToLower( $name.'s' ),
				Text::strToLower( $name ),
				'%s',
			];

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

		$strings[2] = Text::strToLower( $strings[0] );
		$strings[3] = Text::strToLower( $strings[1] );

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
		], $posttype, $strings, $name );

		// TODO: add raw name strings on the object

		foreach ( $name_templates as $key => $template )
			if ( ! array_key_exists( $key, $pre ) )
				$pre[$key] = vsprintf( $template, $strings );

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'name_admin_bar', $pre ) )
			$pre['name_admin_bar'] = $strings[1];

		// EXTRA: must be in the core!
		if ( ! array_key_exists( 'author_metabox', $pre ) )
			$pre['author_metabox'] = __( 'Author' );

		// EXTRA: must be in the core!
		if ( ! array_key_exists( 'excerpt_metabox', $pre ) )
			$pre['excerpt_metabox'] = __( 'Excerpt' );

		if ( $featured )
			foreach ( $featured_templates as $key => $template )
				$pre[$key] = vsprintf( $template, [ $featured, Text::strToLower( $featured ) ] );

		return $pre;
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
			$pre['most_used'] = vsprintf( _x( 'Most Used', 'Helper: Tax Generator', 'geditorial' ), $strings );

		return $pre;
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

		if ( is_post_type_viewable( $post_type_object ) ) {
			$view      = ' '.HTML::link( $messages['view_post'], $permalink );
			$preview   = ' '.HTML::link( $messages['preview_post'], get_preview_post_link( $post ), TRUE );
			$scheduled = ' '.HTML::link( $messages['preview_post'], $permalink, TRUE );
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
			WordPress::doNotCache();

		if ( $require && $layout )
			require_once $layout;
		else
			return $layout;
	}

	// TODO: myabe migrate to: https://github.com/jwage/easy-csv
	// @REF: https://github.com/kzykhys/PHPCsvParser
	public static function parseCSV( $file_path, $limit = NULL )
	{
		if ( empty( $file_path ) )
			return FALSE;

		// $iterator = new \SplFileObject( File::normalize( $file_path ) );
		// $parser   = new \KzykHys\CsvParser\CsvParser( $iterator, [ 'encoding' => 'UTF-8' ] );

		$list = [];
		$args = [ 'encoding' => 'UTF-8' ];

		if ( ! is_null( $limit ) )
			$args['limit'] =  (int) $limit;

		$parser  = \KzykHys\CsvParser\CsvParser::fromFile( File::normalize( $file_path ), $args );
		$items   = $parser->parse();
		$headers = $items[0];

		unset( $parser, $items[0] );

		foreach ( $items as $index => $data )
			if ( ! empty( $data ) )
				$list[] = array_combine( $headers, $data );

		unset( $headers, $items );

		return $list;
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
			'escape'          => static function( $value ) {
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

		$path = File::normalize( GEDITORIAL_CACHE_DIR.( $base ? '/'.$base.'/' : '/' ).$sub );

		if ( file_exists( $path ) )
			return URL::untrail( $path );

		if ( ! wp_mkdir_p( $path ) )
			return FALSE;

		File::putIndexHTML( $path, GEDITORIAL_DIR.'index.html' );

		return URL::untrail( $path );
	}

	public static function getCacheURL( $sub, $base = NULL )
	{
		if ( ! GEDITORIAL_CACHE_DIR ) // correct, we check for path constant
			return FALSE;

		if ( is_null( $base ) )
			$base = self::BASE;

		return URL::untrail( GEDITORIAL_CACHE_URL.( $base ? '/'.$base.'/' : '/' ).$sub );
	}
}

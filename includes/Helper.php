<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Helper extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	public static function moduleClass( $module, $check = TRUE )
	{
		$class = '';

		foreach ( explode( '-', str_replace( '_', '-', $module ) ) as $word )
			$class.= ucfirst( $word ).'';

		$class = __NAMESPACE__.'\\Modules\\'.$class;

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

	public static function getIcon( $icon, $fallback = 'admin-post' )
	{
		if ( is_array( $icon ) )
			return gEditorial()->icon( $icon[1], $icon[0] );

		if ( ! $icon )
			return HTML::getDashicon( $fallback );

		return HTML::getDashicon( $icon );
	}

	// override to use plugin version
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE, $echo = TRUE )
	{
		return HTML::linkStyleSheet( $url, $version, $media, $echo );
	}

	public static function linkStyleSheetAdmin( $page, $echo = TRUE )
	{
		return HTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.( is_rtl() ? '-rtl' : '' ).'.css', GEDITORIAL_VERSION, 'all', $echo );
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
			$text = do_shortcode( $text, TRUE );

		$text = apply_filters( 'html_format_i18n', $text );
		$text = apply_filters( 'gnetwork_typography', $text );

		return $autop ? wpautop( $text ) : $text;
	}

	public static function prepContact( $value, $title = NULL )
	{
		if ( is_email( $value ) )
			$prepared = HTML::mailto( $value, $title );

		else if ( URL::isValid( $value ) )
			$prepared = HTML::link( $title, URL::untrail( $value ) );

		else if ( is_numeric( str_ireplace( [ '+', '-', '.' ], '', $value ) ) )
			$prepared = HTML::tel( $value, FALSE, $title );

		else
			$prepared = HTML::escape( $value );

		return apply_filters( static::BASE.'_prep_contact', $prepared, $value, $title );
	}

	// @SOURCE: P2
	public static function excerptedTitle( $content, $word_count )
	{
		$content = strip_tags( $content );
		$words   = preg_split( '/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/', $content, $word_count + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) > $word_count ) {
			array_pop( $words ); // remove remainder of words
			$content = implode( ' ', $words );
			$content.= '…';
		} else {
			$content = implode( ' ', $words );
		}

		$content = trim( strip_tags( $content ) );

		return $content;
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.HTML::escape( $text ).'">'.$append.'</span>';

		return Text::trimChars( $text, $length, $append );
	}

	public static function isEmptyString( $string, $empties = NULL )
	{
		if ( ! is_string( $string ) )
			return FALSE;

		$trimmed = trim( $string );

		if ( '' === $trimmed )
			return TRUE;

		if ( is_null( $empties ) )
			$empties = [
				'.', '..', '...',
				'-', '--', '---',
				'–', '––', '–––',
				'—', '——', '———',
			];

		foreach ( (array) $empties as $empty )
			if ( $empty === $trimmed )
				return TRUE;

		return FALSE;
	}

	public static function getSeperated( $string, $delimiters = NULL, $delimiter = '|' )
	{
		if ( is_array( $string ) )
			return $string;

		if ( is_null( $delimiters ) )
			$delimiters = [ '/', '،', '؛', ';', ',' ];

		$string = str_ireplace( $delimiters, $delimiter, $string );

		return array_unique( array_filter( explode( $delimiter, $string ), 'trim' ) );
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '' )
	{
		if ( count( $items ) )
			return $before.join( _x( ', ', 'Helper: Item Seperator', 'geditorial' ), $items ).$after;

		return $empty;
	}

	public static function getCounted( $count, $template = '%s' )
	{
		return sprintf( $template, '<span class="-count" data-count="'.$count.'">'.Number::format( $count ).'</span>' );
	}

	public static function getTermsEditRow( $post, $taxonomy, $before = '', $after = '' )
	{
		$object = is_object( $taxonomy ) ? $taxonomy : get_taxonomy( $taxonomy );

		if ( ! $terms = get_the_terms( $post, $object->name ) )
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

		echo self::getJoined( $list, $before, $after );
	}

	public static function getAuthorsEditRow( $authors, $posttype = 'post', $before = '', $after = '' )
	{
		if ( empty( $authors ) )
			return;

		$list = [];

		foreach ( $authors as $author )
			if ( $html = WordPress::getAuthorEditHTML( $posttype, $author ) )
				$list[] = $html;

		echo self::getJoined( $list, $before, $after );
	}

	// simplified `get_post()`
	public static function getPost( $post = NULL, $output = OBJECT, $filter = 'raw' )
	{
		if ( $post instanceof \WP_Post )
			return $post;

		return get_post( $post, $output, $filter );
	}

	public static function getPostLink( $post, $fallback = NULL, $statuses = NULL )
	{
		if ( ! $post = self::getPost( $post ) )
			return FALSE;

		$status = get_post_status( $post );

		if ( is_null( $statuses ) )
			$statuses = [ 'publish', 'inherit' ]; // MAYBE: `apply_filters()`

		if ( ! in_array( $status, (array) $statuses, TRUE ) )
			return $fallback;

		return apply_filters( 'the_permalink', get_permalink( $post ), $post );
	}

	public static function getPostTitle( $post, $fallback = NULL )
	{
		if ( ! $post = self::getPost( $post ) )
			return Plugin::na( FALSE );

		$title = apply_filters( 'the_title', $post->post_title, $post->ID );

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return _x( '(untitled)', 'Helper: Post Title', 'geditorial' );

		return $fallback;
	}

	public static function getPostTitleRow( $post, $link = 'edit', $status = FALSE, $title_attr = NULL )
	{
		if ( ! $post = self::getPost( $post ) )
			return Plugin::na( FALSE );

		$title = self::getPostTitle( $post );
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

		$edit = current_user_can( 'edit_post', $post->ID );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getPostEditLink( $post->ID ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', 'geditorial' ) : $title_attr,
			], HTML::escape( $title ) ).$after;

		if ( 'view' == $link && ! $edit && 'publish' != get_post_status( $post ) )
			return HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
			], HTML::escape( $title ) ).$after;

		if ( 'view' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getPostShortLink( $post->ID ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', 'geditorial' ) : $title_attr,
			], HTML::escape( $title ) ).$after;

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
		], HTML::escape( $title ) ).$after;
	}

	public static function getPostRowActions( $post_id, $actions = NULL )
	{
		if ( is_null( $actions ) )
			$actions = [ 'edit', 'view' ];

		$list = [];
		$edit = current_user_can( 'edit_post', $post_id );

		foreach ( $actions as $action ) {

			switch ( $action ) {

				case 'attached':

					if ( $attached = wp_get_attachment_url( $post_id ) )
						$list['attached'] = HTML::tag( 'a', [
							'href'   => $attached,
							'class'  => '-link -row-link -row-link-attached',
							'data'   => [ 'id' => $post_id, 'row' => 'attached' ],
							'target' => '_blank',
						], _x( 'Attached', 'Helper: Row Action', 'geditorial' ) );

				case 'revisions':

					if ( ! $edit )
						continue 2;

					if ( $revision_id = PostType::getLastRevisionID( $post_id ) )
						$list['revisions'] = HTML::tag( 'a', [
							'href'   => get_edit_post_link( $revision_id ),
							'class'  => '-link -row-link -row-link-revisions',
							'data'   => [ 'id' => $post_id, 'row' => 'revisions' ],
							'target' => '_blank',
						], _x( 'Revisions', 'Helper: Row Action', 'geditorial' ) );

				break;
				case 'edit':

					if ( ! $edit )
						continue 2;

					$list['edit'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostEditLink( $post_id ),
						'class'  => '-link -row-link -row-link-edit',
						'data'   => [ 'id' => $post_id, 'row' => 'edit' ],
						'target' => '_blank',
					], _x( 'Edit', 'Helper: Row Action', 'geditorial' ) );

				break;
				case 'view':

					$list['view'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostShortLink( $post_id ),
						'class'  => '-link -row-link -row-link-view',
						'data'   => [ 'id' => $post_id, 'row' => 'view' ],
						'target' => '_blank',
					], _x( 'View', 'Helper: Row Action', 'geditorial' ) );
			}
		}

		return $list;
	}

	public static function getTermTitleRow( $term, $link = 'edit' )
	{
		$term = get_term( $term );

		if ( ! $term || is_wp_error( $term ) )
			return Plugin::na( FALSE );

		$title = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

		if ( ! $link )
			return HTML::escape( $title );

		if ( 'edit' == $link ) {
			if ( ! $edit = WordPress::getEditTaxLink( $term->taxonomy, $term->term_id ) )
				$link = 'view';
		}

		if ( 'edit' == $link )
			return HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => urldecode( $term->slug ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
			], HTML::escape( $title ) );

		if ( 'view' == $link )
			return HTML::tag( 'a', [
				'href'   => get_term_link( $term->term_id, $term->taxonomy ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => _x( 'View', 'Helper: Row Action', 'geditorial' ),
			], HTML::escape( $title ) );

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
		], HTML::escape( $title ) );
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

	// WP default sizes from options
	public static function getWPImageSizes()
	{
		global $gEditorial_WPImageSizes;

		if ( ! empty( $gEditorial_WPImageSizes ) )
			return $gEditorial_WPImageSizes;

		$gEditorial_WPImageSizes = [
			'thumbnail' => [
				'n' => _x( 'Thumbnail', 'Helper', 'geditorial' ),
				'w' => get_option( 'thumbnail_size_w' ),
				'h' => get_option( 'thumbnail_size_h' ),
				'c' => get_option( 'thumbnail_crop' ),
			],
			'medium' => [
				'n' => _x( 'Medium', 'Helper', 'geditorial' ),
				'w' => get_option( 'medium_size_w' ),
				'h' => get_option( 'medium_size_h' ),
				'c' => 0,
			],
			// 'medium_large' => [
			// 	'n' => _x( 'Medium Large', 'Helper', 'geditorial' ),
			// 	'w' => get_option( 'medium_large_size_w' ),
			// 	'h' => get_option( 'medium_large_size_h' ),
			// 	'c' => 0,
			// ],
			'large' => [
				'n' => _x( 'Large', 'Helper', 'geditorial' ),
				'w' => get_option( 'large_size_w' ),
				'h' => get_option( 'large_size_h' ),
				'c' => 0,
			],
		];

		return $gEditorial_WPImageSizes;
	}

	// FIXME: must check for excludes from `Settings::posttypesExcluded()`
	public static function tableFilterPostTypes( $list = NULL, $name = 'type' )
	{
		if ( is_null( $list ) )
			$list = PostType::get( 0, [ 'show_ui' => TRUE ] );

		return HTML::dropdown( $list, [
			'name'       => $name,
			'selected'   => self::req( $name, 'any' ),
			'none_value' => 'any',
			'none_title' => _x( 'All PostTypes', 'Helper: Table Filter', 'geditorial' ),
		] );
	}

	public static function tableFilterAuthors( $list = NULL, $name = 'author' )
	{
		return Listtable::restrictByAuthor( self::req( $name, 0 ), $name, [ 'echo' => FALSE ] );
	}

	public static function tableFilterSearch( $list = NULL, $name = 's' )
	{
		return HTML::tag( 'input', [
			'type'        => 'search',
			'name'        => $name,
			'value'       => self::req( $name, '' ),
			'class'       => '-search',
			'placeholder' => _x( 'Search', 'Helper: Table Filter', 'geditorial' ),
		] );
	}

	public static function tableColumnPostID()
	{
		return _x( 'ID', 'Helper: Table Column: Post ID', 'geditorial' );
	}

	public static function tableColumnPostDate()
	{
		return [
			'title'    => _x( 'Date', 'Helper: Table Column: Post Date', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){
				return Datetime::humanTimeDiffRound( strtotime( $row->post_date ) );
			},
		];
	}

	public static function tableColumnPostDateModified( $title = NULL )
	{
		return [
			'title'    => is_null( $title ) ? _x( 'On', 'Helper: Table Column: Post Date Modified', 'geditorial' ) : $title,
			'callback' => function( $value, $row, $column, $index ){
				return Datetime::htmlHumanTime( $row->post_modified, TRUE );
			},
		];
	}

	public static function tableColumnPostType()
	{
		return [
			'title'    => _x( 'Type', 'Helper: Table Column: Post Type', 'geditorial' ),
			'args'     => [ 'types' => PostType::get( 2 ) ],
			'callback' => function( $value, $row, $column, $index ){
				return isset( $column['args']['types'][$row->post_type] )
					? $column['args']['types'][$row->post_type]
					: $row->post_type;
			},
		];
	}

	public static function tableColumnPostMime()
	{
		return [
			'title'    => _x( 'Mime', 'Helper: Table Column: Post Mime', 'geditorial' ),
			'args'     => [ 'mime_types' => wp_get_mime_types() ],
			'callback' => function( $value, $row, $column, $index ){
				if ( $ext = Helper::getExtension( $row->post_mime_type, $column['args']['mime_types'] ) )
					return '<span title="'.$row->post_mime_type.'">'.$ext.'</span>';

				return $row->post_mime_type;
			},
		];
	}

	public static function tableColumnPostTitle( $actions = NULL, $excerpt = FALSE, $custom = [] )
	{
		return [
			'title'    => _x( 'Title', 'Helper: Table Column: Post Title', 'geditorial' ),
			'args'     => [ 'statuses' => PostType::getStatuses() ],
			'callback' => function( $value, $row, $column, $index ) use( $excerpt ){

				$title = Helper::getPostTitle( $row );

				if ( 'publish' != $row->post_status ) {

					if ( 'inherit' == $row->post_status && 'attachment' == $row->post_type )
						$status = '';
					else if ( isset( $column['args']['statuses'][$row->post_status] ) )
						$status = $column['args']['statuses'][$row->post_status];
					else
						$status = $row->post_status;

					if ( $status )
						$title.= ' <small class="-status">('.$status.')</small>';
				}

				if ( 'attachment' == $row->post_type && $attached = wp_get_attachment_url( $row->ID ) )
					$title.= '<br />'.HTML::tag( 'a', [
						'href'   => $attached,
						'class'  => wp_attachment_is( 'image', $row->ID ) ? 'thickbox' : FALSE,
						'target' => '_blank',
						'dir'    => 'ltr',
					], get_post_meta( $row->ID, '_wp_attached_file', TRUE ) );

				if ( $excerpt && $row->post_excerpt )
					$title.= wpautop( Helper::prepDescription( $row->post_excerpt, FALSE, FALSE ), FALSE );

				return $title;
			},
			'actions' => function( $value, $row, $column, $index ) use( $actions, $custom ){
				return array_merge( Helper::getPostRowActions( $row->ID, $actions ), $custom );
			},
		];
	}

	public static function tableColumnPostExcerpt()
	{
		return [
			'title'    => _x( 'Excerpt', 'Helper: Table Column: Post Excerpt', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ) {
				return $row->post_excerpt
					? wpautop( Helper::prepDescription( $row->post_excerpt, FALSE, FALSE ), FALSE )
					: Helper::htmlEmpty();
			},
		];
	}

	public static function tableColumnPostTitleSummary()
	{
		return [
			'title'    => _x( 'Title', 'Helper: Table Column: Post Title', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){
				return Helper::getPostTitleRow( $row, 'edit' );
			},
		];
	}

	public static function tableColumnPostStatusSummary()
	{
		return [
			'title'    => _x( 'Status', 'Helper: Table Column: Post Title', 'geditorial' ),
			'args'     => [ 'statuses' => PostType::getStatuses() ],
			'callback' => function( $value, $row, $column, $index ){

				if ( ! $row->post_status )
					return gEditorial()->na();

				if ( isset( $column['args']['statuses'][$row->post_status] ) )
					return $column['args']['statuses'][$row->post_status];

				return HTML::tag( 'code', $row->post_status );
			},
		];
	}

	public static function tableColumnPostAuthorSummary()
	{
		return [
			'title'    => _x( 'Author', 'Helper: Table Column: Post Author', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){

				if ( current_user_can( 'edit_post', $row->ID ) )
					return WordPress::getAuthorEditHTML( $row->post_type, $row->post_author );

				if ( $author_data = get_user_by( 'id', $row->post_author ) )
					return HTML::escape( $author_data->display_name );

				return self::htmlEmpty();
			},
		];
	}

	public static function tableColumnPostTerms( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = Taxonomy::get( 4 );

		return [
			'title'    => _x( 'Terms', 'Helper: Table Column: Post Terms', 'geditorial' ),
			'args'     => [ 'taxonomies' => $taxonomies ],
			'callback' => function( $value, $row, $column, $index ){
				$html = '';

				foreach ( $column['args']['taxonomies'] as $taxonomy => $object )
					if ( $object->label ) // only public taxes
						$html.= Helper::getTermsEditRow( $row, $object, '<div>'.$object->label.': ', '</div>' );

				return $html;
			},
		];
	}

	public static function tableColumnTermID()
	{
		return _x( 'ID', 'Helper: Table Column: Term ID', 'geditorial' );
	}

	public static function tableColumnTermName()
	{
		return [
			'title'    => _x( 'Name', 'Helper: Table Column: Term Name', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){
				return Helper::getTermTitleRow( $row );
			},
		];
	}

	public static function tableColumnTermSlug()
	{
		return _x( 'Slug', 'Helper: Table Column: Term Slug', 'geditorial' );
	}

	public static function tableColumnTermDesc()
	{
		return [
			'title'    => _x( 'Description', 'Helper: Table Column: Term Desc', 'geditorial' ),
			'callback' => 'wpautop',
			'class'    => 'description',
		];
	}

	// FIXME: DEPRECATED
	public static function tableArgEmptyPosts( $wrap = FALSE )
	{
		$message = _x( 'No posts found.', 'Helper: Table Arg: Empty Posts', 'geditorial' );
		return $wrap ? HTML::warning( $message, FALSE ) : $message;
	}

	public static function htmlEmpty( $class = '', $title_attr = NULL )
	{
		return is_null( $title_attr )
			? '<span class="-empty '.$class.'">&mdash;</span>'
			: sprintf( '<span title="%s" class="'.HTML::prepClass( '-empty', $class ).'">&mdash;</span>', $title_attr );
	}

	public static function htmlCount( $count, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Helper: No Count Title Attribute', 'geditorial' );

		return $count
			? Number::format( $count )
			: self::htmlEmpty( 'column-count-empty', $title_attr );
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

		if ( ! ctype_digit( $timestamp ) )
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

		$strings[5] = '%s';

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
	public static function generatePostTypeLabels( $name, $featured = FALSE, $pre = [] )
	{
		$name_templates = [
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'name'                     => _x( '%1$s', 'Helper: CPT Generator: Name', 'geditorial' ),
			// 'menu_name'                => _x( '%1$s', 'Helper: CPT Generator: Menu Name', 'geditorial' ),
			// 'description'              => _x( '%1$s', 'Helper: CPT Generator: Description', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'singular_name'            => _x( '%2$s', 'Helper: CPT Generator: Singular Name', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'add_new'                  => _x( 'Add New', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'add_new_item'             => _x( 'Add New %2$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'edit_item'                => _x( 'Edit %2$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'new_item'                 => _x( 'New %2$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'view_item'                => _x( 'View %2$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'view_items'               => _x( 'View %1$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'search_items'             => _x( 'Search %1$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'not_found'                => _x( 'No %3$s found.', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'not_found_in_trash'       => _x( 'No %3$s found in Trash.', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'parent_item_colon'        => _x( 'Parent %2$s:', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'all_items'                => _x( 'All %1$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'archives'                 => _x( '%2$s Archives', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'attributes'               => _x( '%2$s Attributes', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'insert_into_item'         => _x( 'Insert into %4$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'uploaded_to_this_item'    => _x( 'Uploaded to this %4$s', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'filter_items_list'        => _x( 'Filter %3$s list', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'items_list_navigation'    => _x( '%1$s list navigation', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'items_list'               => _x( '%1$s list', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_published'           => _x( '%2$s published.', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_published_privately' => _x( '%2$s published privately.', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_reverted_to_draft'   => _x( '%2$s reverted to draft.', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_scheduled'           => _x( '%2$s scheduled.', 'Helper: CPT Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural posttype, %2$s: camel case / singular posttype, %3$s: lower case / plural posttype, %4$s: lower case / singular posttype, %5$s: `%s` placeholder */
			'item_updated'             => _x( '%2$s updated.', 'Helper: CPT Generator', 'geditorial' ),
		];

		$featured_templates = [
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'featured_image'        => _x( '%1$s', 'Helper: CPT Generator: Featured', 'geditorial' ),
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'set_featured_image'    => _x( 'Set %2$s', 'Helper: CPT Generator: Featured', 'geditorial' ),
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'remove_featured_image' => _x( 'Remove %2$s', 'Helper: CPT Generator: Featured', 'geditorial' ),
			/* translators: %1$s: featured camel case, %2$s: featured lower case */
			'use_featured_image'    => _x( 'Use as %2$s', 'Helper: CPT Generator: Featured', 'geditorial' ),
		];

		$strings = self::getStringsFromName( $name );

		foreach ( $name_templates as $key => $template )
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
	public static function generateTaxonomyLabels( $name, $pre = [] )
	{
		$templates = [
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'name'                       => _x( '%1$s', 'Helper: Tax Generator: Name', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			// 'menu_name'                  => _x( '%1$s', 'Helper: Tax Generator: Menu Name', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'singular_name'              => _x( '%2$s', 'Helper: Tax Generator: Singular Name', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'search_items'               => _x( 'Search %1$s', 'Helper: Tax Generator', 'geditorial' ),
			'popular_items'              => NULL, // _x( 'Popular %1$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'all_items'                  => _x( 'All %1$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'parent_item'                => _x( 'Parent %2$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'parent_item_colon'          => _x( 'Parent %2$s:', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'edit_item'                  => _x( 'Edit %2$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'view_item'                  => _x( 'View %2$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'update_item'                => _x( 'Update %2$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'add_new_item'               => _x( 'Add New %2$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'new_item_name'              => _x( 'New %2$s Name', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'separate_items_with_commas' => _x( 'Separate %3$s with commas', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'add_or_remove_items'        => _x( 'Add or remove %3$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'choose_from_most_used'      => _x( 'Choose from the most used %3$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'not_found'                  => _x( 'No %3$s found.', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'no_terms'                   => _x( 'No %3$s', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'items_list_navigation'      => _x( '%1$s list navigation', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'items_list'                 => _x( '%1$s list', 'Helper: Tax Generator', 'geditorial' ),
			/* translators: %1$s: camel case / plural taxonomy, %2$s: camel case / singular taxonomy, %3$s: lower case / plural taxonomy, %4$s: lower case / singular taxonomy, %5$s: `%s` placeholder */
			'back_to_items'              => _x( '&larr; Back to %1$s', 'Helper: Tax Generator', 'geditorial' ),
		];

		$strings = self::getStringsFromName( $name );

		foreach ( $templates as $key => $template )
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
	public static function generatePostTypeMessages( $name )
	{
		global $post_type_object, $post, $post_ID;

		$templates = [
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
		];

		$messages = [];
		$strings  = self::getStringsFromName( $name );

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
	public static function generateBulkPostTypeMessages( $name, $counts )
	{
		$templates = [
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
		];

		$messages = [];
		$strings  = self::getStringsFromName( $name );

		foreach ( $templates as $key => $template )
			// needs to apply the role so we use noopedCount()
			$messages[$key] = vsprintf( self::noopedCount( $counts[$key], $template ), $strings );

		return $messages;
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
			'escape'          => function( $value ) {
				return htmlspecialchars( $value, ENT_COMPAT, 'UTF-8' );
			},
		] );

		return $gEditorialMustache;
	}

	// @SEE: https://github.com/bobthecow/mustache.php/wiki/Mustache-Tags
	public static function renderMustache( $part, $data = [], $echo = TRUE )
	{
		$mustache = self::getMustache();
		$template = $mustache->loadTemplate( $part );

		$html = $template->render( $data );

		if ( ! $echo )
			return $html;

		echo $html;
	}
}

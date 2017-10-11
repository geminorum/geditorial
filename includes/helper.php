<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Date;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\HTTP;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
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
			$class .= ucfirst( $word ).'';

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
	public static function linkStyleSheet( $url, $version = GEDITORIAL_VERSION, $media = FALSE )
	{
		HTML::linkStyleSheet( $url, $version, $media );
	}

	public static function linkStyleSheetAdmin( $page )
	{
		HTML::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.'.$page.( is_rtl() ? '-rtl' : '' ).'.css', GEDITORIAL_VERSION );
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

	public static function prepTitle( $text )
	{
		if ( ! $text )
			return '';

		$text = apply_filters( 'the_title', $text, 0 );
		$text = apply_filters( 'gnetwork_typography', $text );

		return trim( $text );
	}

	public static function prepDescription( $text, $shortcode = TRUE )
	{
		if ( ! $text )
			return '';

		if ( $shortcode )
			$text = do_shortcode( $text, TRUE );

		$text = apply_filters( 'gnetwork_typography', $text );

		return wpautop( $text );
	}

	// @SOURCE: P2
	public static function excerptedTitle( $content, $word_count )
	{
		$content = strip_tags( $content );
		$words   = preg_split( '/([\s_;?!\/\(\)\[\]{}<>\r\n\t"]|\.$|(?<=\D)[:,.\-]|[:,.\-](?=\D))/', $content, $word_count + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) > $word_count ) {
			array_pop( $words ); // remove remainder of words
			$content = implode( ' ', $words );
			$content .= '…';
		} else {
			$content = implode( ' ', $words );
		}

		$content = trim( strip_tags( $content ) );

		return $content;
	}

	public static function trimChars( $text, $length = 45, $append = '&nbsp;&hellip;' )
	{
		$append = '<span title="'.esc_attr( $text ).'">'.$append.'</span>';

		return Text::trimChars( $text, $length, $append );
	}

	public static function getSeperated( $string, $delimiters = NULL, $delimiter = '|' )
	{
		if ( is_array( $string ) )
			return $string;

		if ( is_null( $delimiters ) )
			$delimiters = [ '/', '،', '؛', ';', ',' ];

		return explode( $delimiter, str_ireplace( $delimiters, $delimiter, $string ) );
	}

	public static function getJoined( $items, $before = '', $after = '', $empty = '' )
	{
		if ( count( $items ) )
			return $before.join( _x( ', ', 'Helper: Item Seperator', GEDITORIAL_TEXTDOMAIN ), $items ).$after;

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
			], esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, $object->name, 'display' ) ) );
		}

		echo self::getJoined( $list, $before, $after );
	}

	public static function getAuthorsEditRow( $authors, $post_type = 'post', $before = '', $after = '' )
	{
		if ( ! count( $authors ) )
			return;

		$list = [];

		foreach ( $authors as $author )
			if ( $html = WordPress::getAuthorEditHTML( $post_type, $author ) )
				$list[] = $html;

		echo self::getJoined( $list, $before, $after );
	}

	public static function getPostTitle( $post, $fallback = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return Plugin::na( FALSE );

		$title = apply_filters( 'the_title', $post->post_title, $post->ID );

		if ( ! empty( $title ) )
			return $title;

		if ( FALSE === $fallback )
			return '';

		if ( is_null( $fallback ) )
			return _x( '(untitled)', 'Helper: Post Title', GEDITORIAL_TEXTDOMAIN );

		return $fallback;
	}

	public static function getPostTitleRow( $post, $link = 'edit', $status = FALSE, $title_attr = NULL )
	{
		if ( ! $post = get_post( $post ) )
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
					$after = ' <small class="-status" title="'.esc_attr( $post->post_status ).'">('.$status.')</small>';
			}
		}

		if ( ! $link )
			return esc_html( $title ).$after;

		$edit = current_user_can( 'edit_post', $post->ID );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getPostEditLink( $post->ID ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'Edit', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) : $title_attr,
			], esc_html( $title ) ).$after;

		if ( 'view' == $link && ! $edit && 'publish' != get_post_status( $post ) )
			return HTML::tag( 'span', [
				'class' => '-row-span',
				'title' => is_null( $title_attr ) ? FALSE : $title_attr,
			], esc_html( $title ) ).$after;

		if ( 'view' == $link )
			return HTML::tag( 'a', [
				'href'   => WordPress::getPostShortLink( $post->ID ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => is_null( $title_attr ) ? _x( 'View', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) : $title_attr,
			], esc_html( $title ) ).$after;

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
			'title'  => is_null( $title_attr ) ? FALSE : $title_attr,
		], esc_html( $title ) ).$after;
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
						], _x( 'Attached', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) );

				case 'revisions':

					if ( ! $edit )
						continue;

					if ( $revision_id = PostType::getLastRevisionID( $post_id ) )
						$list['revisions'] = HTML::tag( 'a', [
							'href'   => get_edit_post_link( $revision_id ),
							'class'  => '-link -row-link -row-link-revisions',
							'data'   => [ 'id' => $post_id, 'row' => 'revisions' ],
							'target' => '_blank',
						], _x( 'Revisions', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) );

				break;
				case 'edit':

					if ( ! $edit )
						continue;

					$list['edit'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostEditLink( $post_id ),
						'class'  => '-link -row-link -row-link-edit',
						'data'   => [ 'id' => $post_id, 'row' => 'edit' ],
						'target' => '_blank',
					], _x( 'Edit', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) );

				break;
				case 'view':

					$list['view'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostShortLink( $post_id ),
						'class'  => '-link -row-link -row-link-view',
						'data'   => [ 'id' => $post_id, 'row' => 'view' ],
						'target' => '_blank',
					], _x( 'View', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ) );
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
			return esc_html( $title );

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
			], esc_html( $title ) );

		if ( 'view' == $link )
			return HTML::tag( 'a', [
				'href'   => get_term_link( $term->term_id, $term->taxonomy ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => _x( 'View', 'Helper: Row Action', GEDITORIAL_TEXTDOMAIN ),
			], esc_html( $title ) );

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
		], esc_html( $title ) );
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
		if ( ! is_object( $posttype ) )
			$posttype = get_post_type_object( $posttype );

		if ( $posttype->menu_icon && is_string( $posttype->menu_icon ) ) {

			if ( Text::has( $posttype->menu_icon, 'data:image/svg+xml;base64,' ) )
				return Icon::wrapBase64( $posttype->menu_icon );


			if ( Text::has( $posttype->menu_icon, 'dashicons-' ) )
				return HTML::getDashicon( str_ireplace( 'dashicons-', '', $posttype->menu_icon ) );

			return Icon::wrapURL( esc_url( $posttype->menu_icon ) );
		}

		return HTML::getDashicon( $fallback );
	}

	public static function registerColorBox()
	{
		wp_register_style( 'jquery-colorbox', GEDITORIAL_URL.'assets/css/admin.colorbox.css', [], '1.6.4', 'screen' );
		wp_register_script( 'jquery-colorbox', GEDITORIAL_URL.'assets/packages/jquery-colorbox/jquery.colorbox-min.js', [ 'jquery' ], '1.6.4', TRUE );
	}

	public static function enqueueColorBox()
	{
		wp_enqueue_style( 'jquery-colorbox' );
		wp_enqueue_script( 'jquery-colorbox' );
	}

	public static function enqueueScript( $asset, $dep = [ 'jquery' ], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/js' )
	{
		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$asset.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueScriptVendor( $asset, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/js/vendor' )
	{
		return self::enqueueScript( $asset, $dep, $version, $base, $path );
	}

	public static function enqueueScriptPackage( $asset, $package = NULL, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/packages' )
	{
		if ( is_null( $package ) )
			$package = $asset.'/'.$asset;

		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( $handle, $base.$path.'/'.$package.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function registerScriptPackage( $asset, $package = NULL, $dep = [], $version = GEDITORIAL_VERSION, $base = GEDITORIAL_URL, $path = 'assets/packages' )
	{
		if ( is_null( $package ) )
			$package = $asset.'/'.$asset;

		$handle  = strtolower( static::BASE.'-'.str_replace( '.', '-', $asset ) );
		$variant = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script( $handle, $base.$path.'/'.$package.$variant.'.js', $dep, $version, TRUE );

		return $handle;
	}

	public static function enqueueTimeAgo()
	{
		$callback = [ 'gPersianDateTimeAgo', 'enqueue' ];

		if ( ! is_callable( $callback ) )
			return FALSE;

		return call_user_func( $callback );
	}

	public static function ipLookup( $ip )
	{
		if ( function_exists( 'gnetwork_ip_lookup' ) )
			return gnetwork_ip_lookup( $ip );

		return $ip;
	}

	public static function getTinyMceStrings( $locale )
	{
		$strings = apply_filters( static::BASE.'_tinymce_strings', [] );

		return count( $strings ) ? 'tinyMCE.addI18n("'.$locale.'.geditorial", '.wp_json_encode( $strings ).');'."\n" : '';
	}

	// TODO: add as general option
	public static function getEditorialUserID( $fallback = FALSE )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' ) ) {
			if ( $gtheme = gtheme_get_option( 'default_user', 0 ) )
				return $gtheme;
		}

		return $fallback ? get_current_user_id() : 0;
	}

	// WP default sizes from options
	public static function getWPImageSizes()
	{
		global $gEditorial_WPImageSizes;

		if ( ! empty( $gEditorial_WPImageSizes ) )
			return $gEditorial_WPImageSizes;

		$gEditorial_WPImageSizes = [
			'thumbnail' => [
				'n' => _x( 'Thumbnail', 'Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'thumbnail_size_w' ),
				'h' => get_option( 'thumbnail_size_h' ),
				'c' => get_option( 'thumbnail_crop' ),
			],
			'medium' => [
				'n' => _x( 'Medium', 'Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'medium_size_w' ),
				'h' => get_option( 'medium_size_h' ),
				'c' => 0,
			],
			'large' => [
				'n' => _x( 'Large', 'Helper', GEDITORIAL_TEXTDOMAIN ),
				'w' => get_option( 'large_size_w' ),
				'h' => get_option( 'large_size_h' ),
				'c' => 0,
			],
		];

		return $gEditorial_WPImageSizes;
	}

	public static function tableFilterPostTypes( $list = NULL, $name = 'type' )
	{
		if ( is_null( $list ) )
			$list = PostType::get();

		return HTML::dropdown( $list, [
			'name'       => $name,
			'selected'   => self::req( $name, 'any' ),
			'none_value' => 'any',
			'none_title' => _x( 'All PostTypes', 'Helper: Table Filter', GEDITORIAL_TEXTDOMAIN ),
		] );
	}

	public static function tableColumnPostID()
	{
		return _x( 'ID', 'Helper: Table Column: Post ID', GEDITORIAL_TEXTDOMAIN );
	}

	public static function tableColumnPostDate()
	{
		return [
			'title'    => _x( 'Date', 'Helper: Table Column: Post Date', GEDITORIAL_TEXTDOMAIN ),
			'callback' => function( $value, $row, $column, $index ){
				return Helper::humanTimeDiffRound( strtotime( $row->post_date ) );
			},
		];
	}

	public static function tableColumnPostDateModified( $title = NULL )
	{
		return [
			'title'    => is_null( $title ) ? _x( 'On', 'Helper: Table Column: Post Date Modified', GEDITORIAL_TEXTDOMAIN ) : $title,
			'callback' => function( $value, $row, $column, $index ){
				return Helper::htmlHumanTime( $row->post_modified, TRUE );
			},
		];
	}

	public static function tableColumnPostType()
	{
		return [
			'title'    => _x( 'Type', 'Helper: Table Column: Post Type', GEDITORIAL_TEXTDOMAIN ),
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
			'title'    => _x( 'Mime', 'Helper: Table Column: Post Mime', GEDITORIAL_TEXTDOMAIN ),
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
			'title'    => _x( 'Title', 'Helper: Table Column: Post Title', GEDITORIAL_TEXTDOMAIN ),
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
						$title .= ' <small class="-status">('.$status.')</small>';
				}

				if ( 'attachment' == $row->post_type && $attached = wp_get_attachment_url( $row->ID ) )
					$title .= '<br />'.HTML::tag( 'a', [
						'href'   => $attached,
						'class'  => wp_attachment_is( 'image', $row->ID ) ? 'thickbox' : FALSE,
						'target' => '_blank',
						'dir'    => 'ltr',
					], get_post_meta( $row->ID, '_wp_attached_file', TRUE ) );

				if ( $excerpt && $row->post_excerpt )
					$title .= wpautop( Helper::prepDescription( $row->post_excerpt ), FALSE );

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
			'title'    => _x( 'Excerpt', 'Helper: Table Column: Post Excerpt', GEDITORIAL_TEXTDOMAIN ),
			'callback' => function( $value, $row, $column, $index ) {
				return $row->post_excerpt
					? wpautop( Helper::prepDescription( $row->post_excerpt ), FALSE )
					: '&mdash;';
			},
		];
	}

	public static function tableColumnPostTitleSummary()
	{
		return [
			'title'    => _x( 'Title', 'Helper: Table Column: Post Title', GEDITORIAL_TEXTDOMAIN ),
			'callback' => function( $value, $row, $column, $index ){
				return Helper::getPostTitleRow( $row, 'edit' );
			},
		];
	}

	public static function tableColumnPostAuthorSummary()
	{
		return [
			'title'    => _x( 'Author', 'Helper: Table Column: Post Author', GEDITORIAL_TEXTDOMAIN ),
			'callback' => function( $value, $row, $column, $index ){

				if ( current_user_can( 'edit_post', $row->ID ) )
					return WordPress::getAuthorEditHTML( $row->post_type, $row->post_author );

				if ( $author_data = get_user_by( 'id', $row->post_author ) )
					return esc_html( $author_data->display_name );

				return '<span class="-empty">&mdash;</span>';
			},
		];
	}

	public static function tableColumnPostTerms()
	{
		return [
			'title'    => _x( 'Terms', 'Helper: Table Column: Post Terms', GEDITORIAL_TEXTDOMAIN ),
			'args'     => [ 'taxonomies' => Taxonomy::get( 4 ) ],
			'callback' => function( $value, $row, $column, $index ){
				$html = '';
				foreach ( $column['args']['taxonomies'] as $taxonomy => $object )
					if ( $object->label ) // only public taxes
						$html .= Helper::getTermsEditRow( $row, $object, '<div>'.$object->label.': ', '</div>' );
				return $html;
			},
		];
	}

	public static function tableColumnTermID()
	{
		return _x( 'ID', 'Helper: Table Column: Term ID', GEDITORIAL_TEXTDOMAIN );
	}

	public static function tableColumnTermName()
	{
		return [
			'title'    => _x( 'Name', 'Helper: Table Column: Term Name', GEDITORIAL_TEXTDOMAIN ),
			'callback' => function( $value, $row, $column, $index ){
				return Helper::getTermTitleRow( $row );
			},
		];
	}

	public static function tableColumnTermSlug()
	{
		return _x( 'Slug', 'Helper: Table Column: Term Slug', GEDITORIAL_TEXTDOMAIN );
	}

	public static function tableColumnTermDesc()
	{
		return [
			'title'    => _x( 'Description', 'Helper: Table Column: Term Desc', GEDITORIAL_TEXTDOMAIN ),
			'callback' => 'wpautop',
			'class'    => 'description',
		];
	}

	public static function tableArgEmptyPosts( $wrap = TRUE )
	{
		$message = _x( 'No posts found.', 'Helper: Table Arg: Empty Posts', GEDITORIAL_TEXTDOMAIN );
		return $wrap ? HTML::warning( $message, FALSE ) : $message;
	}

	// CAUTION: must wrap in `.geditorial-wordcount-wrap` along with the textarea
	public static function htmlWordCount( $for = 'excerpt', $posttype = 'post', $data = [] )
	{
		$defaults = [
			'min' => '0',
			'max' => '0',
		];

		return HTML::tag( 'div', [
			'class' => [ static::BASE.'-wordcount', 'hide-if-no-js' ],
			'data'  => apply_filters( static::BASE.'_helper_wordcount_data', array_merge( $data, $defaults ), $for, $posttype ),
		], sprintf( _x( 'Letter Count: %s', 'Helper', GEDITORIAL_TEXTDOMAIN ), '<span class="-chars">0</span>' ) );
	}

	public static function htmlCount( $count, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Helper: No Count Title Attribute', GEDITORIAL_TEXTDOMAIN );

		if ( $count )
			$html = Number::format( $count );
		else
			$html = sprintf( '<span title="%s" class="column-count-empty -empty">&mdash;</span>', $title_attr );

		return $html;
	}

	public static function htmlOrder( $order, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Order', 'Helper: No Order Title Attribute', GEDITORIAL_TEXTDOMAIN );

		if ( $order )
			$html = Number::format( $order );
		else
			$html = sprintf( '<span title="%s" class="column-order-empty -empty">&mdash;</span>', $title_attr );

		return $html;
	}

	public static function getDateEditRow( $timestamp, $class = FALSE )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$formats = self::dateFormats( FALSE );

		$html = '<span class="-date-date" title="'.esc_attr( date_i18n( $formats['timeonly'], $timestamp ) );
		$html.= '" data-time="'.date( 'c', $timestamp ).'">'.date_i18n( $formats['default'], $timestamp ).'</span>';

		$html.= '&nbsp;(<span class="-date-diff" title="';
		$html.= esc_attr( date_i18n( $formats['fulltime'], $timestamp ) ).'">'
		$html.= self::humanTimeDiff( $timestamp ).'</span>)';

		return $class ? '<span class="'.$class.'">'.$html.'</span>' : $html;
	}

	public static function getModifiedEditRow( $post, $class = FALSE )
	{
		$timestamp = strtotime( $post->post_modified );
		$formats   = self::dateFormats( FALSE );

		$html = '<span class="-date-modified" title="'.esc_attr( date_i18n( $formats['default'], $timestamp ) );
		$html.='" data-time="'.date( 'c', $timestamp ).'">'.self::humanTimeDiff( $timestamp ).'</span>';

		$edit_last = get_post_meta( $post->ID, '_edit_last', TRUE );

		if ( $edit_last && $post->post_author != $edit_last )
			$html.= '&nbsp;(<span class="-edit-last">'.WordPress::getAuthorEditHTML( $post->post_type, $edit_last ).'</span>)';

		return $class ? '<span class="'.$class.'">'.$html.'</span>' : $html;
	}

	public static function htmlCurrent( $format = NULL, $class = FALSE, $title = FALSE )
	{
		return Date::htmlCurrent( ( is_null( $format ) ? self::dateFormats( 'datetime' ) : $format ), $class, $title );
	}

	public static function dateFormat( $timestamp, $context = 'default' )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		return date_i18n( self::dateFormats( $context ), $timestamp );
	}

	// @SEE: http://www.phpformatdate.com/
	public static function dateFormats( $context = 'default' )
	{
		static $formats;

		if ( empty( $formats ) )
			$formats = apply_filters( 'custom_date_formats', [
				'fulltime'  => _x( 'l, M j, Y @ H:i', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'datetime'  => _x( 'M j, Y @ G:i', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'dateonly'  => _x( 'l, F j, Y', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'timedate'  => _x( 'H:i - F j, Y', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'timeampm'  => _x( 'g:i a', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'timeonly'  => _x( 'H:i', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'monthday'  => _x( 'n/j', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'default'   => _x( 'm/d/Y', 'Date Format', GEDITORIAL_TEXTDOMAIN ),
				'wordpress' => get_option( 'date_format' ),
			] );

		if ( FALSE === $context )
			return $formats;

		if ( isset( $formats[$context] ) )
			return $formats[$context];

		return $formats['default'];
	}

	public static function postModified( $post = NULL, $attr = FALSE )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		$gmt   = strtotime( $post->post_modified_gmt );
		$local = strtotime( $post->post_modified );

		$format = self::dateFormats( 'dateonly' );
		$title  = _x( 'Last Modified on %s', 'Helper: Post Modified', GEDITORIAL_TEXTDOMAIN );

		return $attr
			? sprintf( $title, date_i18n( $format, $local ) )
			: Date::htmlDateTime( $local, $gmt, $format, self::humanTimeDiffRound( $local, FALSE ) );
	}

	public static function htmlHumanTime( $timestamp, $flip = FALSE )
	{
		if ( ! ctype_digit( $timestamp ) )
			$timestamp = strtotime( $timestamp );

		$now = current_time( 'timestamp', FALSE );

		if ( $flip )
			return '<span class="-date-diff" title="'
					.esc_attr( self::dateFormat( $timestamp, 'fulltime' ) ).'">'
					.self::humanTimeDiff( $timestamp, $now )
				.'</span>';

		return '<span class="-time" title="'
			.esc_attr( self::humanTimeAgo( $timestamp, $now ) ).'">'
			.self::humanTimeDiffRound( $timestamp, NULL, self::dateFormats( 'default' ), $now )
		.'</span>';
	}

	public static function humanTimeAgo( $from, $to = '' )
	{
		return sprintf( _x( '%s ago', 'Helper: Human Time Ago', GEDITORIAL_TEXTDOMAIN ), human_time_diff( $from, $to ) );
	}

	public static function humanTimeDiffRound( $local, $round = NULL, $format = NULL, $now = NULL )
	{
		if ( is_null( $now ) )
			$now = current_time( 'timestamp', FALSE );

		if ( FALSE === $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $round ) )
			$round = Date::DAY_IN_SECONDS;

		$diff = $now - $local;

		if ( $diff > 0 && $diff < $round )
			return self::humanTimeAgo( $local, $now );

		if ( is_null( $format ) )
			$format = self::dateFormats( 'default' );

		return date_i18n( $format, $local, FALSE );
	}

	public static function humanTimeDiff( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'    => _x( 'Now', 'Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),
				'_s_ago' => _x( '%s ago', 'Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),
				'in__s'  => _x( 'in %s', 'Helper: Human Time Diff', GEDITORIAL_TEXTDOMAIN ),

				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_weeks'   => _nx_noop( '%s week', '%s weeks', 'Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_months'  => _nx_noop( '%s month', '%s months', 'Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_years'   => _nx_noop( '%s year', '%s years', 'Helper: Human Time Diff: Noop', GEDITORIAL_TEXTDOMAIN ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::humanTimeDiff( $timestamp, $now, $strings );
	}

	public static function htmlFromSeconds( $seconds, $round = FALSE )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'sep' => _x( ', ', 'Helper: From Seconds: Seperator', GEDITORIAL_TEXTDOMAIN ),

				'noop_seconds' => _nx_noop( '%s second', '%s seconds', 'Helper: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_minutes' => _nx_noop( '%s min', '%s mins', 'Helper: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_hours'   => _nx_noop( '%s hour', '%s hours', 'Helper: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
				'noop_days'    => _nx_noop( '%s day', '%s days', 'Helper: From Seconds: Noop', GEDITORIAL_TEXTDOMAIN ),
			];

		return Date::htmlFromSeconds( $seconds, $round, $strings );
	}

	// not used yet!
	public static function moment( $timestamp, $now = '' )
	{
		static $strings = NULL;

		if ( is_null( $strings ) )
			$strings = [
				'now'            => _x( 'Now', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'just_now'       => _x( 'Just now', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'one_minute_ago' => _x( 'One minute ago', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_minutes_ago' => _x( '%s minutes ago', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'one_hour_ago'   => _x( 'One hour ago', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_hours_ago'   => _x( '%s hours ago', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'yesterday'      => _x( 'Yesterday', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_days_ago'    => _x( '%s days ago', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'_s_weeks_ago'   => _x( '%s weeks ago', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'last_month'     => _x( 'Last month', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'last_year'      => _x( 'Last year', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in_a_minute'    => _x( 'in a minute', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_minutes'  => _x( 'in %s minutes', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in_an_hour'     => _x( 'in an hour', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_hours'    => _x( 'in %s hours', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'tomorrow'       => _x( 'Tomorrow', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'next_week'      => _x( 'next week', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'in__s_weeks'    => _x( 'in %s weeks', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'next_month'     => _x( 'next month', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'format_l'       => _x( 'l', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
				'format_f_y'     => _x( 'F Y', 'Helper: Date: Moment', GEDITORIAL_TEXTDOMAIN ),
			];

		if ( empty( $now ) )
			$now = current_time( 'timestamp', FALSE );

		return Date::moment( $timestamp, $now, $strings );
	}

	// @REF: [Calendar Classes - ICU User Guide](http://userguide.icu-project.org/datetime/calendar)
	public static function getDefualtCalendars( $filtered = FALSE )
	{
		$calendars = [
			'gregorian'     => _x( 'Gregorian', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'japanese'      => _x( 'Japanese', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'buddhist'      => _x( 'Buddhist', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'chinese'       => _x( 'Chinese', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'persian'       => _x( 'Persian', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'indian'        => _x( 'Indian', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			'islamic'       => _x( 'Islamic', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'islamic-civil' => _x( 'Islamic-Civil', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'coptic'        => _x( 'Coptic', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
			// 'ethiopic'      => _x( 'Ethiopic', 'Helper: Default Calendar Type', GEDITORIAL_TEXTDOMAIN ),
		];

		return $filtered ? apply_filters( static::BASE.'_default_calendars', $calendars ) : $calendars;
	}

	public static function sanitizeCalendar( $calendar, $default_type = 'gregorian' )
	{
		$calendars = self::getDefualtCalendars( FALSE );
		$sanitized = $calendar;

		if ( ! $calendar )
			$sanitized = $default_type;

		else if ( in_array( $calendar, [ 'Jalali', 'jalali', 'Persian', 'persian' ] ) )
			$sanitized = 'persian';

		else if ( in_array( $calendar, [ 'Hijri', 'hijri', 'Islamic', 'islamic' ] ) )
			$sanitized = 'islamic';

		else if ( in_array( $calendar, [ 'Gregorian', 'gregorian' ] ) )
			$sanitized = 'gregorian';

		else if ( in_array( $calendar, array_keys( $calendars ) ) )
			$sanitized = $calendar;

		else if ( $key = array_search( $calendar, $calendars ) )
			$sanitized = $key;

		else
			$sanitized = $default_type;

		return apply_filters( static::BASE.'_sanitize_calendar', $sanitized, $default_type, $calendar );
	}

	// @SOURCE: `translate_nooped_plural()`
	public static function nooped( $count, $nooped )
	{
		if ( ! empty( $nooped['domain'] ) )
			$nooped['domain'] = GEDITORIAL_TEXTDOMAIN;

		if ( ! array_key_exists( 'domain', $nooped ) )
			return _n( $nooped['singular'], $nooped['plural'], $count );

		else if ( empty( $nooped['context'] ) )
			return _n( $nooped['singular'], $nooped['plural'], $count, $nooped['domain'] );

		else
			return _nx( $nooped['singular'], $nooped['plural'], $count, $nooped['context'], $nooped['domain'] );
	}

	public static function noopedCount( $count, $nooped )
	{
		$rule = _x( '%2$s', 'Helper: Nooped Count', GEDITORIAL_TEXTDOMAIN );

		$singular = self::nooped( 1, $nooped );
		$plural   = self::nooped( $count, $nooped );

		return sprintf( $rule, $singular, $plural );
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
			'name'                  => _x( '%1$s', 'Helper: CPT Generator: Name', GEDITORIAL_TEXTDOMAIN ),
			// 'menu_name'             => _x( '%1$s', 'Helper: CPT Generator: Menu Name', GEDITORIAL_TEXTDOMAIN ),
			// 'description'           => _x( '%1$s', 'Helper: CPT Generator: Description', GEDITORIAL_TEXTDOMAIN ),
			'singular_name'         => _x( '%2$s', 'Helper: CPT Generator: Singular Name', GEDITORIAL_TEXTDOMAIN ),
			'add_new'               => _x( 'Add New', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'add_new_item'          => _x( 'Add New %2$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'edit_item'             => _x( 'Edit %2$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'new_item'              => _x( 'New %2$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'view_item'             => _x( 'View %2$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'view_items'            => _x( 'View %1$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'search_items'          => _x( 'Search %1$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found'             => _x( 'No %3$s found.', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found_in_trash'    => _x( 'No %3$s found in Trash.', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item_colon'     => _x( 'Parent %2$s:', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'all_items'             => _x( 'All %1$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'archives'              => _x( '%2$s Archives', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'attributes'            => _x( '%2$s Attributes', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'insert_into_item'      => _x( 'Insert into %4$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'uploaded_to_this_item' => _x( 'Uploaded to this %4$s', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'filter_items_list'     => _x( 'Filter %3$s list', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list_navigation' => _x( '%1$s list navigation', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list'            => _x( '%1$s list', 'Helper: CPT Generator', GEDITORIAL_TEXTDOMAIN ),
		];

		$featured_templates = [
			'featured_image'        => _x( '%1$s', 'Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
			'set_featured_image'    => _x( 'Set %2$s', 'Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
			'remove_featured_image' => _x( 'Remove %2$s', 'Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
			'use_featured_image'    => _x( 'Use as %2$s', 'Helper: CPT Generator: Featured', GEDITORIAL_TEXTDOMAIN ),
		];

		$strings = self::getStringsFromName( $name );

		foreach ( $name_templates as $key => $template )
			$pre[$key] = vsprintf( $template, $strings );

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'name_admin_bar', $pre ) )
			$pre['name_admin_bar'] = $strings[1];

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
			'name'                       => _x( '%1$s', 'Helper: Tax Generator: Name', GEDITORIAL_TEXTDOMAIN ),
			// 'menu_name'                  => _x( '%1$s', 'Helper: Tax Generator: Menu Name', GEDITORIAL_TEXTDOMAIN ),
			'singular_name'              => _x( '%2$s', 'Helper: Tax Generator: Singular Name', GEDITORIAL_TEXTDOMAIN ),
			'search_items'               => _x( 'Search %1$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'popular_items'              => NULL, // _x( 'Popular %1$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'all_items'                  => _x( 'All %1$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item'                => _x( 'Parent %2$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'parent_item_colon'          => _x( 'Parent %2$s:', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'edit_item'                  => _x( 'Edit %2$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'view_item'                  => _x( 'View %2$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'update_item'                => _x( 'Update %2$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'add_new_item'               => _x( 'Add New %2$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'new_item_name'              => _x( 'New %2$s Name', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'separate_items_with_commas' => _x( 'Separate %3$s with commas', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'add_or_remove_items'        => _x( 'Add or remove %3$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'choose_from_most_used'      => _x( 'Choose from the most used %3$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'not_found'                  => _x( 'No %3$s found.', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'no_terms'                   => _x( 'No %3$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list_navigation'      => _x( '%1$s list navigation', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'items_list'                 => _x( '%1$s list', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
			'back_to_items'              => _x( '&larr; Back to %1$s', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ),
		];

		$strings = self::getStringsFromName( $name );

		foreach ( $templates as $key => $template )
			$pre[$key] = vsprintf( $template, $strings );

		if ( ! array_key_exists( 'menu_name', $pre ) )
			$pre['menu_name'] = $strings[0];

		if ( ! array_key_exists( 'most_used', $pre ) )
			$pre['most_used'] = vsprintf( _x( 'Most Used', 'Helper: Tax Generator', GEDITORIAL_TEXTDOMAIN ), $strings );

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
			'view_post'                      => _x( 'View %4$s', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'preview_post'                   => _x( 'Preview %4$s', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_updated'                   => _x( '%2$s updated.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'custom_field_updated'           => _x( 'Custom field updated.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'custom_field_deleted'           => _x( 'Custom field deleted.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_restored_to_revision_from' => _x( '%2$s restored to revision from %5$s.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_published'                 => _x( '%2$s published.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_saved'                     => _x( '%2$s saved.' , 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_submitted'                 => _x( '%2$s submitted.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_scheduled_for'             => _x( '%2$s scheduled for: %5$s.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'post_draft_updated'             => _x( '%2$s draft updated.', 'Helper: PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
		];

		$messages = [];
		$strings  = self::getStringsFromName( $name );

		foreach ( $templates as $key => $template )
			$messages[$key] = vsprintf( $template, $strings );

		if ( ! $permalink = get_permalink( $post_ID ) )
			$permalink = '';

		$preview = $scheduled = $view = '';
		$scheduled_date = self::dateFormat( $post->post_date, 'datetime' );

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
			'updated'   => _nx_noop( '%5$s %4$s updated.', '%5$s %3$s updated.', 'Helper: Bulk PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'locked'    => _nx_noop( '%5$s %4$s not updated, somebody is editing it.', '%5$s %3$s not updated, somebody is editing them.', 'Helper: Bulk PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'deleted'   => _nx_noop( '%5$s %4$s permanently deleted.', '%5$s %3$s permanently deleted.', 'Helper: Bulk PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'trashed'   => _nx_noop( '%5$s %4$s moved to the Trash.', '%5$s %3$s moved to the Trash.', 'Helper: Bulk PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
			'untrashed' => _nx_noop( '%5$s %4$s restored from the Trash.', '%5$s %3$s restored from the Trash.', 'Helper: Bulk PostType Message Generator', GEDITORIAL_TEXTDOMAIN ),
		];

		$messages = [];
		$strings  = self::getStringsFromName( $name );

		foreach ( $templates as $key => $template )
			// needs to apply the role so we use noopedCount()
			$messages[$key] = vsprintf( self::noopedCount( $counts[$key], $template ), $strings );

		return $messages;
	}

	public static function getPostTypeMonths( $calendar_type, $post_type = 'post', $args = [], $user_id = 0 )
	{
		$callback = [ __NAMESPACE__.'\\WordPress\\Database', 'getPostTypeMonths' ];

		if ( 'persian' == $calendar_type
			&& is_callable( [ 'gPersianDateWordPress', 'getPostTypeMonths' ] ) )
				$callback = [ 'gPersianDateWordPress', 'getPostTypeMonths' ];

		return call_user_func_array( $callback, [ $post_type, $args, $user_id ] );
	}

	public static function monthFirstAndLast( $calendar_type, $year, $month, $format = 'Y-m-d H:i:s' )
	{
		$callback = [ __NAMESPACE__.'\\Core\\Date', 'monthFirstAndLast' ];

		if ( is_callable( [ 'gPersianDateDate', 'monthFirstAndLast' ] ) )
			$callback = [ 'gPersianDateDate', 'monthFirstAndLast' ];

		return call_user_func_array( $callback, [ $year, $month, $format, $calendar_type ] );
	}

	// FIXME: find a better way!
	public static function getMonths( $calendar_type = 'gregorian' )
	{
		if ( is_callable( [ 'gPersianDateStrings', 'month' ] ) ) {

			$map = [
				'gregorian' => 'Gregorian',
				'persian'   => 'Jalali',
				'islamic'   => 'Hijri',
			];

			if ( ! array_key_exists( $calendar_type, $map ) )
				return [];

			return \gPersianDateStrings::month( NULL, TRUE, $map[$calendar_type] );
		}

		global $wp_locale;

		if ( 'gregorian' )
			return $wp_locale->month;

		return [];
	}

	public static function getCalendar( $calendar_type = 'gregorian', $args = [] )
	{
		if ( is_callable( [ 'gPersianDateCalendar', 'build' ] ) ) {

			$map = [
				'gregorian' => 'Gregorian',
				'persian'   => 'Jalali',
				'islamic'   => 'Hijri',
			];

			if ( ! array_key_exists( $calendar_type, $map ) )
				return FALSE;

			$args['calendar'] = $map[$calendar_type];

			return \gPersianDateCalendar::build( $args );
		}

		return FALSE;
	}

	// NOT USED
	// returns array of post date in given cal
	public static function getTheDayByPost( $post, $default_type = 'gregorian' )
	{
		$the_day = [ 'cal' => 'gregorian' ];

		// 'post_status' => 'auto-draft',

		switch ( strtolower( $default_type ) ) {

			case 'hijri':
			case 'islamic':

				$convertor = [ 'gPersianDateDateTime', 'toHijri' ];
				$the_day['cal'] = 'hijri';

			case 'jalali':
			case 'persian':

				$convertor = [ 'gPersianDateDateTime', 'toJalali' ];
				$the_day['cal'] = 'jalali';

			default:

				if ( class_exists( 'gPersianDateDateTime' )
					&& 'gregorian' != $the_day['cal'] ) {

					list(
						$the_day['year'],
						$the_day['month'],
						$the_day['day']
					) = call_user_func_array( $convertor,
						explode( '-', mysql2date( 'Y-n-j', $post->post_date, FALSE ) ) );

				} else {

					$the_day['cal'] = 'gregorian';
					$the_day['day']   = mysql2date( 'j', $post->post_date, FALSE );
					$the_day['month'] = mysql2date( 'n', $post->post_date, FALSE );
					$the_day['year']  = mysql2date( 'Y', $post->post_date, FALSE );
				}

				// FIXME: add time
		}

		return $the_day;
	}
}

class Walker_PageDropdown extends \Walker_PageDropdown
{

	public function start_el( &$output, $page, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		if ( ! isset( $args['value_field'] ) || ! isset( $page->{$args['value_field']} ) ) {
			$args['value_field'] = 'ID';
		}

		$output .= "\t<option class=\"level-$depth\" value=\"" . esc_attr( $page->{$args['value_field']} ) . "\"";
		if ( $page->{$args['value_field']} == $args['selected'] ) // <---- CHANGED
			$output .= ' selected="selected"';
		$output .= '>';

		$title = $page->post_title;
		if ( '' === $title ) {
			$title = sprintf( __( '#%d (no title)' ), $page->ID );
		}

		$title = apply_filters( 'list_pages', $title, $page );
		$output .= $pad . esc_html( $title );
		$output .= "</option>\n";
	}
}

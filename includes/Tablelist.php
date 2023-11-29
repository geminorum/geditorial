<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Tablelist extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function isAction( $action, $check_cb = FALSE )
	{
		if ( $action == self::req( 'table_action' ) || isset( $_POST[$action] ) )
			return $check_cb ? (bool) count( self::req( '_cb', [] ) ) : TRUE;

		return FALSE;
	}

	// @REF: `Taxonomy::getTerms()`
	public static function getTerms( $atts = [], $extra = [], $taxonomy = '', $perpage = 25 )
	{
		$limit  = self::limit( $perpage );
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = array_merge( [
			'taxonomy' => $taxonomy,
			'number'   => $limit,
			'offset'   => $offset,
			'orderby'  => self::orderby( 'term_id' ),
			'order'    => self::order( 'DESC' ),

			'hide_empty'       => FALSE,
			'suppress_filters' => TRUE,
		], $atts );

		if ( ! empty( $_REQUEST['s'] ) )
			$args['search'] = $extra['s'] = $_REQUEST['s'];

		$query = new \WP_Term_Query();
		$terms = $query->query( $args );

		// $pagination = Core\HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged, $extra );
		$pagination = Core\HTML::tablePagination( count( $terms ), FALSE, $limit, $paged, $extra );

		$pagination['orderby'] = $args['orderby'];
		$pagination['order']   = $args['order'];

		return [ $terms, $pagination ];
	}

	// TODO: duolicate to support the parent type: `getAttachments()`
	// - must alter query to filter the parent posttype
	public static function getPosts( $atts = [], $extra = [], $posttypes = 'any', $perpage = 25 )
	{
		$limit  = self::limit( $perpage );
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = array_merge( [
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'DESC' ),
			'post_type'        => $posttypes, // 'any',
			'post_status'      => 'any', // [ 'publish', 'future', 'draft', 'pending' ],
			'suppress_filters' => TRUE,
		], $atts );

		if ( ! empty( $_REQUEST['s'] ) )
			$args['s'] = $extra['s'] = $_REQUEST['s'];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		if ( ! empty( $_REQUEST['author'] ) )
			$args['author'] = $extra['author'] = $_REQUEST['author'];

		if ( ! empty( $_REQUEST['parent'] ) )
			$args['post_parent'] = $extra['parent'] = $_REQUEST['parent'];

		if ( 'attachment' == $args['post_type'] && is_array( $args['post_status'] ) )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query();
		$posts = $query->query( $args );

		$pagination = Core\HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged, $extra );

		$pagination['orderby'] = $args['orderby'];
		$pagination['order']   = $args['order'];

		return [ $posts, $pagination ];
	}

	// FIXME: must check for excludes from `Settings::posttypesExcluded()`
	public static function filterPostTypes( $list = NULL, $name = 'type' )
	{
		if ( is_null( $list ) )
			$list = WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] );

		return Core\HTML::dropdown( $list, [
			'name'       => $name,
			'selected'   => self::req( $name, 'any' ),
			'none_value' => 'any',
			'none_title' => _x( 'All PostTypes', 'Tablelist: Filter', 'geditorial' ),
		] );
	}

	public static function filterAuthors( $list = NULL, $name = 'author' )
	{
		return Listtable::restrictByAuthor( self::req( $name, 0 ), $name, [ 'echo' => FALSE ] );
	}

	public static function filterSearch( $list = NULL, $name = 's' )
	{
		return Core\HTML::tag( 'input', [
			'type'        => 'search',
			'name'        => $name,
			'value'       => self::req( $name, '' ),
			'class'       => '-search',
			'placeholder' => _x( 'Search', 'Tablelist: Filter', 'geditorial' ),
		] );
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
						$list['attached'] = Core\HTML::tag( 'a', [
							'href'   => $attached,
							'class'  => '-link -row-link -row-link-attached',
							'data'   => [ 'id' => $post_id, 'row' => 'attached' ],
							'target' => '_blank',
						], _x( 'Attached', 'Tablelist: Row Action: Post', 'geditorial' ) );

				case 'revisions':

					if ( ! $edit )
						continue 2;

					if ( $revision_id = WordPress\PostType::getLastRevisionID( $post_id ) )
						$list['revisions'] = Core\HTML::tag( 'a', [
							'href'   => get_edit_post_link( $revision_id ),
							'class'  => '-link -row-link -row-link-revisions',
							'data'   => [ 'id' => $post_id, 'row' => 'revisions' ],
							'target' => '_blank',
						], _x( 'Revisions', 'Tablelist: Row Action: Post', 'geditorial' ) );

				break;
				case 'edit':

					if ( ! $edit )
						continue 2;

					$list['edit'] = Core\HTML::tag( 'a', [
						'href'   => Core\WordPress::getPostEditLink( $post_id ),
						'class'  => '-link -row-link -row-link-edit',
						'data'   => [ 'id' => $post_id, 'row' => 'edit' ],
						'target' => '_blank',
					], _x( 'Edit', 'Tablelist: Row Action: Post', 'geditorial' ) );

				break;
				case 'view':

					$list['view'] = Core\HTML::tag( 'a', [
						'href'   => Core\WordPress::getPostShortLink( $post_id ),
						'class'  => '-link -row-link -row-link-view',
						'data'   => [ 'id' => $post_id, 'row' => 'view' ],
						'target' => '_blank',
					], _x( 'View', 'Tablelist: Row Action: Post', 'geditorial' ) );
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
			return Core\HTML::escape( $title );

		if ( 'edit' == $link ) {
			if ( ! $edit = Core\WordPress::getEditTaxLink( $term->taxonomy, $term->term_id ) )
				$link = 'view';
		}

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => urldecode( $term->slug ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
			], Core\HTML::escape( $title ) );

		if ( 'view' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => get_term_link( $term->term_id, $term->taxonomy ),
				'class'  => '-link -row-link -row-link-view',
				'target' => '_blank',
				'title'  => _x( 'View', 'Tablelist: Row Action', 'geditorial' ),
			], Core\HTML::escape( $title ) );

		return Core\HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
		], Core\HTML::escape( $title ) );
	}

	public static function columnPostID( $icon = TRUE )
	{
		$title = _x( 'ID', 'Tablelist: Column: Post ID', 'geditorial' );
		return $icon ? sprintf( '<span class="-column-icon %3$s" title="%2$s">%1$s</span>', $title, esc_attr( $title ), '-post-id' ) : $title;
	}

	public static function columnPostDate()
	{
		return [
			'title'    => _x( 'Date', 'Tablelist: Column: Post Date', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				return Datetime::humanTimeDiffRound( strtotime( $row->post_date ) );
			},
		];
	}

	public static function columnPostDateModified( $title = NULL )
	{
		return [
			'title'    => is_null( $title ) ? _x( 'On', 'Tablelist: Column: Post Date Modified', 'geditorial' ) : $title,
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				return Datetime::htmlHumanTime( $row->post_modified, TRUE );
			},
		];
	}

	public static function columnPostType()
	{
		return [
			'title'    => _x( 'Type', 'Tablelist: Column: Post Type', 'geditorial' ),
			'args'     => [ 'types' => WordPress\PostType::get( 2 ) ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				return isset( $column['args']['types'][$row->post_type] )
					? $column['args']['types'][$row->post_type]
					: $row->post_type;
			},
		];
	}

	public static function columnPostMime()
	{
		return [
			'title'    => _x( 'Mime', 'Tablelist: Column: Post Mime', 'geditorial' ),
			'args'     => [ 'mime_types' => wp_get_mime_types() ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				if ( $ext = WordPress\Media::getExtension( $row->post_mime_type, $column['args']['mime_types'] ) )
					return '<span title="'.$row->post_mime_type.'">'.$ext.'</span>';

				return $row->post_mime_type;
			},
		];
	}

	public static function columnPostTitle( $actions = NULL, $excerpt = FALSE, $custom = [] )
	{
		return [
			'title'    => _x( 'Title', 'Tablelist: Column: Post Title', 'geditorial' ),
			'args'     => [ 'statuses' => WordPress\Status::get() ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $excerpt ) {

				$title = WordPress\Post::title( $row );

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
					$title.= '<br />'.Core\HTML::tag( 'a', [
						'href'   => $attached,
						'class'  => wp_attachment_is( 'image', $row->ID ) ? 'thickbox' : FALSE,
						'target' => '_blank',
						'dir'    => 'ltr',
					], get_post_meta( $row->ID, '_wp_attached_file', TRUE ) );

				if ( $excerpt && $row->post_excerpt )
					$title.= wpautop( Helper::prepDescription( $row->post_excerpt, FALSE, FALSE ), FALSE );

				return $title;
			},
			'actions' => static function ( $value, $row, $column, $index, $key, $args ) use ( $actions, $custom ) {
				return array_merge( self::getPostRowActions( $row->ID, $actions ), $custom );
			},
		];
	}

	public static function columnPostExcerpt()
	{
		return [
			'title'    => _x( 'Excerpt', 'Tablelist: Column: Post Excerpt', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				return $row->post_excerpt
					? wpautop( Helper::prepDescription( $row->post_excerpt, FALSE, FALSE ), FALSE )
					: Helper::htmlEmpty();
			},
		];
	}

	public static function columnPostTitleSummary()
	{
		return [
			'title'    => _x( 'Title', 'Tablelist: Column: Post Title', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				return Helper::getPostTitleRow( $row, 'edit' );
			},
		];
	}

	public static function columnPostStatusSummary()
	{
		return [
			'title'    => _x( 'Status', 'Tablelist: Column: Post Title', 'geditorial' ),
			'args'     => [ 'statuses' => WordPress\Status::get() ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

				if ( ! $row->post_status )
					return gEditorial()->na();

				if ( isset( $column['args']['statuses'][$row->post_status] ) )
					return $column['args']['statuses'][$row->post_status];

				return Core\HTML::tag( 'code', $row->post_status );
			},
		];
	}

	public static function columnPostAuthorSummary()
	{
		return [
			'title'    => _x( 'Author', 'Tablelist: Column: Post Author', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

				if ( current_user_can( 'edit_post', $row->ID ) )
					return Core\WordPress::getAuthorEditHTML( $row->post_type, $row->post_author );

				if ( $author_data = get_user_by( 'id', $row->post_author ) )
					return Core\HTML::escape( $author_data->display_name );

				return self::htmlEmpty();
			},
		];
	}

	public static function columnPostTerms( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = WordPress\Taxonomy::get( 4 );

		return [
			'title'    => _x( 'Terms', 'Tablelist: Column: Post Terms', 'geditorial' ),
			'args'     => [ 'taxonomies' => $taxonomies ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				foreach ( $column['args']['taxonomies'] as $object )
					if ( WordPress\Taxonomy::viewable( $object ) )
						Helper::renderPostTermsEditRow( $row, $object,
							sprintf( '<div><span title="%s">%s</span>:&nbsp;', $object->name, $object->label ), '</div>' );

				return '';
			},
		];
	}

	public static function columnTermID()
	{
		return _x( 'ID', 'Tablelist: Column: Term ID', 'geditorial' );
	}

	public static function columnTermName( $actions = NULL, $description = FALSE, $custom = [], $title = NULL )
	{
		return [
			'title'    => $title ?: _x( 'Name', 'Tablelist: Column: Term Name', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $description ) {

				if ( ! $term = WordPress\Term::get( $row ) )
					return Plugin::na( FALSE );

				$html = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );

				if ( $description && $term->description )
					$html.= wpautop( Helper::prepDescription( $term->description, FALSE, FALSE ), FALSE );

				return $html;
			},
			'actions' => static function ( $value, $row, $column, $index, $key, $args ) use ( $actions, $custom ) {
				return array_merge( self::getTermRowActions( $row, $actions ), $custom );
			},
		];
	}

	// TODO: check if taxonomy has ui
	// TODO: check if taxonomy is viewable
	public static function getTermRowActions( $row, $actions = NULL )
	{
		if ( ! $term = WordPress\Term::get( $row ) )
			return [];

		if ( is_null( $actions ) )
			$actions = [ 'edit', 'view' ];

		$list = [];
		$edit = Core\WordPress::getEditTaxLink( $term->taxonomy, $term->term_id );

		foreach ( $actions as $action ) {

			switch ( $action ) {

				case 'edit':

					if ( $edit ) // already checked for cap
						$list['edit'] = Core\HTML::tag( 'a', [
							'href'   => $edit,
							'title'  => $term->term_id,
							'class'  => '-link -row-link -row-link-edit',
							'target' => '_blank',
						], _x( 'Edit', 'Tablelist: Row Action: Term', 'geditorial' ) );
					break;

				case 'view':
					$list['view'] = Core\HTML::tag( 'a', [
						'href'   => get_term_link( $term->term_id, $term->taxonomy ),
						'title'  => urldecode( $term->slug ),
						'class'  => '-link -row-link -row-link-view',
						'target' => '_blank',
					], _x( 'View', 'Tablelist: Row Action: Term', 'geditorial' ) );
					break;
			}
		}

		return $list;
	}

	public static function columnTermSlug()
	{
		return _x( 'Slug', 'Tablelist: Column: Term Slug', 'geditorial' );
	}

	public static function columnTermDesc()
	{
		return [
			'title'    => _x( 'Description', 'Tablelist: Column: Term Desc', 'geditorial' ),
			'callback' => 'wpautop',
			'class'    => 'description',
		];
	}

	public static function columnTermMetaDateStart( $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$metakey = 'datestart';

		return [
			'title'    => _x( 'Date-Start', 'Tablelist: Column: Term Meta Date-Start', 'geditorial' ),
			'class'    => 'datetime',
			'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $metakey ) {
				$html = '';

				if ( $meta = get_term_meta( $row->term_id, $metakey, TRUE ) )
					$html = Datetime::prepForDisplay( trim( $meta ), 'Y/m/d H:i' );

				return $html ?: Helper::htmlEmpty();
			},
		];
	}

	public static function columnTermMetaDateEnd( $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$metakey = 'dateend';

		return [
			'title'    => _x( 'Date-End', 'Tablelist: Column: Term Meta Date-Start', 'geditorial' ),
			'class'    => 'datetime',
			'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $metakey ) {
				$html = '';

				if ( $meta = get_term_meta( $row->term_id, $metakey, TRUE ) )
					$html = Datetime::prepForDisplay( trim( $meta ), 'Y/m/d H:i' );

				return $html ?: Helper::htmlEmpty();
			},
		];
	}
}

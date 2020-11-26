<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Tablelist extends Main
{

	const BASE = 'geditorial';

	// TODO: support the parent type
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

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged, $extra );

		$pagination['orderby'] = $args['orderby'];
		$pagination['order']   = $args['order'];

		return [ $posts, $pagination ];
	}

	// FIXME: must check for excludes from `Settings::posttypesExcluded()`
	public static function filterPostTypes( $list = NULL, $name = 'type' )
	{
		if ( is_null( $list ) )
			$list = PostType::get( 0, [ 'show_ui' => TRUE ] );

		return HTML::dropdown( $list, [
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
		return HTML::tag( 'input', [
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
						$list['attached'] = HTML::tag( 'a', [
							'href'   => $attached,
							'class'  => '-link -row-link -row-link-attached',
							'data'   => [ 'id' => $post_id, 'row' => 'attached' ],
							'target' => '_blank',
						], _x( 'Attached', 'Tablelist: Row Action', 'geditorial' ) );

				case 'revisions':

					if ( ! $edit )
						continue 2;

					if ( $revision_id = PostType::getLastRevisionID( $post_id ) )
						$list['revisions'] = HTML::tag( 'a', [
							'href'   => get_edit_post_link( $revision_id ),
							'class'  => '-link -row-link -row-link-revisions',
							'data'   => [ 'id' => $post_id, 'row' => 'revisions' ],
							'target' => '_blank',
						], _x( 'Revisions', 'Tablelist: Row Action', 'geditorial' ) );

				break;
				case 'edit':

					if ( ! $edit )
						continue 2;

					$list['edit'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostEditLink( $post_id ),
						'class'  => '-link -row-link -row-link-edit',
						'data'   => [ 'id' => $post_id, 'row' => 'edit' ],
						'target' => '_blank',
					], _x( 'Edit', 'Tablelist: Row Action', 'geditorial' ) );

				break;
				case 'view':

					$list['view'] = HTML::tag( 'a', [
						'href'   => WordPress::getPostShortLink( $post_id ),
						'class'  => '-link -row-link -row-link-view',
						'data'   => [ 'id' => $post_id, 'row' => 'view' ],
						'target' => '_blank',
					], _x( 'View', 'Tablelist: Row Action', 'geditorial' ) );
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
				'title'  => _x( 'View', 'Tablelist: Row Action', 'geditorial' ),
			], HTML::escape( $title ) );

		return HTML::tag( 'a', [
			'href'   => $link,
			'class'  => '-link -row-link -row-link-custom',
			'target' => '_blank',
		], HTML::escape( $title ) );
	}

	public static function columnPostID()
	{
		return _x( 'ID', 'Tablelist: Column: Post ID', 'geditorial' );
	}

	public static function columnPostDate()
	{
		return [
			'title'    => _x( 'Date', 'Tablelist: Column: Post Date', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){
				return Datetime::humanTimeDiffRound( strtotime( $row->post_date ) );
			},
		];
	}

	public static function columnPostDateModified( $title = NULL )
	{
		return [
			'title'    => is_null( $title ) ? _x( 'On', 'Tablelist: Column: Post Date Modified', 'geditorial' ) : $title,
			'callback' => function( $value, $row, $column, $index ){
				return Datetime::htmlHumanTime( $row->post_modified, TRUE );
			},
		];
	}

	public static function columnPostType()
	{
		return [
			'title'    => _x( 'Type', 'Tablelist: Column: Post Type', 'geditorial' ),
			'args'     => [ 'types' => PostType::get( 2 ) ],
			'callback' => function( $value, $row, $column, $index ){
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
			'callback' => function( $value, $row, $column, $index ){
				if ( $ext = Helper::getExtension( $row->post_mime_type, $column['args']['mime_types'] ) )
					return '<span title="'.$row->post_mime_type.'">'.$ext.'</span>';

				return $row->post_mime_type;
			},
		];
	}

	public static function columnPostTitle( $actions = NULL, $excerpt = FALSE, $custom = [] )
	{
		return [
			'title'    => _x( 'Title', 'Tablelist: Column: Post Title', 'geditorial' ),
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
				return array_merge( self::getPostRowActions( $row->ID, $actions ), $custom );
			},
		];
	}

	public static function columnPostExcerpt()
	{
		return [
			'title'    => _x( 'Excerpt', 'Tablelist: Column: Post Excerpt', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ) {
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
			'callback' => function( $value, $row, $column, $index ){
				return Helper::getPostTitleRow( $row, 'edit' );
			},
		];
	}

	public static function columnPostStatusSummary()
	{
		return [
			'title'    => _x( 'Status', 'Tablelist: Column: Post Title', 'geditorial' ),
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

	public static function columnPostAuthorSummary()
	{
		return [
			'title'    => _x( 'Author', 'Tablelist: Column: Post Author', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){

				if ( current_user_can( 'edit_post', $row->ID ) )
					return WordPress::getAuthorEditHTML( $row->post_type, $row->post_author );

				if ( $author_data = get_user_by( 'id', $row->post_author ) )
					return HTML::escape( $author_data->display_name );

				return self::htmlEmpty();
			},
		];
	}

	public static function columnPostTerms( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = Taxonomy::get( 4 );

		return [
			'title'    => _x( 'Terms', 'Tablelist: Column: Post Terms', 'geditorial' ),
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

	public static function columnTermID()
	{
		return _x( 'ID', 'Tablelist: Column: Term ID', 'geditorial' );
	}

	public static function columnTermName()
	{
		return [
			'title'    => _x( 'Name', 'Tablelist: Column: Term Name', 'geditorial' ),
			'callback' => function( $value, $row, $column, $index ){
				return self::getTermTitleRow( $row );
			},
		];
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
}

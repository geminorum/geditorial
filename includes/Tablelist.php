<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Tablelist extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function isAction( $actions, $check_cb = FALSE )
	{
		foreach ( (array) $actions as $action )
			if ( $action == self::req( 'table_action' ) || isset( $_POST[$action] ) )
				return $check_cb ? (bool) count( self::req( '_cb', [] ) ) : TRUE;

		return FALSE;
	}

	/**
	 * Retrieves a list of terms and pagination info given query arguments
	 * and current request.
	 * @REF: `Taxonomy::getTerms()`
	 * NOTE: false `$perpage` will result in list of terms only.
	 *
	 * @param array $atts
	 * @param array $extra
	 * @param string|array $taxonomy
	 * @param int|false $perpage
	 * @return array
	 */
	public static function getTerms( $atts = [], $extra = [], $taxonomy = '', $perpage = 25 )
	{
		if ( $perpage ) {

			$limit = self::limit( $perpage );
			$paged = self::paged();
			$pre   = [
				'number'   => $limit,
				'offset'   => ( $paged - 1 ) * $limit,
			];

		} else {

			$pre = [ 'number' => '' ];
		}

		$args = array_merge( $pre, [
			'taxonomy' => $taxonomy,
			'orderby'  => self::orderby( 'term_id' ),
			'order'    => self::order( 'DESC' ),

			'hide_empty'       => FALSE,
			'suppress_filters' => TRUE,
		], $atts );

		if ( ! empty( $_REQUEST['s'] ) )
			$args['search'] = $extra['s'] = $_REQUEST['s'];

		$query = new \WP_Term_Query();
		$terms = $query->query( $args );

		if ( ! $perpage )
			return $terms;

		$total      = WordPress\Taxonomy::hasTerms( $taxonomy, FALSE, TRUE, array_merge( $atts, [ 'search' => self::req( 's', '' ) ] ) );
		$pagination = Core\HTML::tablePagination( $total, ceil( $total / $limit ), $limit, $paged, $extra );

		$pagination['orderby'] = $args['orderby'];
		$pagination['order']   = $args['order'];

		return [ $terms, $pagination ];
	}

	// TODO: support the parent post-type: no way but alter the query to filter the parent post-type
	public static function getAttachments( $atts = [], $extra = [], $posttypes = 'attachment', $perpage = 25 )
	{
		return self::getPosts( $atts, $extra, $posttypes, $perpage );
	}

	/**
	 * Retrieves a list of posts and pagination info given query arguments
	 * and current request.
	 *
	 * NOTE: false `$perpage` will result in list of posts only.
	 *
	 * @param array $atts
	 * @param array $extra
	 * @param string|array $posttypes
	 * @param int|false $perpage
	 * @return array
	 */
	public static function getPosts( $atts = [], $extra = [], $posttypes = 'any', $perpage = 25 )
	{
		if ( $perpage ) {

			$limit = self::limit( $perpage );
			$paged = self::paged();
			$pre   = [
				'posts_per_page' => $limit,
				'offset'         => ( $paged - 1 ) * $limit,
			];

		} else {

			$pre = [ 'posts_per_page' => -1 ];
		}

		$args = array_merge( $pre, [

			// @SEE: https://make.wordpress.org/core/2014/08/29/a-more-powerful-order-by-in-wordpress-4-0/
			'orderby' => self::orderby( 'ID' ),
			'order'   => self::order( 'DESC' ),

			'post_type'   => $posttypes,   // 'any',
			'post_status' => 'any',        // WordPress\Status::acceptable( $posttypes ),

			'suppress_filters' => TRUE,
		], $atts );

		if ( ! empty( $_REQUEST['s'] ) )
			$args['s'] = $extra['s'] = $_REQUEST['s'];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		if ( ! empty( $_REQUEST['mime'] ) )
			$args['post_mime_type'] = $extra['mime'] = $_REQUEST['mime'];

		if ( ! empty( $_REQUEST['author'] ) )
			$args['author'] = $extra['author'] = $_REQUEST['author'];

		if ( ! empty( $_REQUEST['parent'] ) )
			$args['post_parent'] = $extra['parent'] = $_REQUEST['parent'];

		if ( 'attachment' == $args['post_type'] && is_array( $args['post_status'] ) )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query();
		$posts = $query->query( $args );

		if ( ! $perpage )
			return $posts;

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

					if ( $revision_id = WordPress\Post::getLastRevisionID( $post_id ) )
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
						'href'   => WordPress\Post::edit( $post_id ),
						'class'  => '-link -row-link -row-link-edit',
						'data'   => [ 'id' => $post_id, 'row' => 'edit' ],
						'target' => '_blank',
					], _x( 'Edit', 'Tablelist: Row Action: Post', 'geditorial' ) );

				break;
				case 'view':

					$list['view'] = Core\HTML::tag( 'a', [
						'href'   => WordPress\Post::shortlink( $post_id ),
						'class'  => '-link -row-link -row-link-view',
						'data'   => [ 'id' => $post_id, 'row' => 'view' ],
						'target' => '_blank',
					], _x( 'View', 'Tablelist: Row Action: Post', 'geditorial' ) );
			}
		}

		return $list;
	}

	// @SEE: `Helper::getTermTitleRow()`
	public static function getTermTitleRow( $term, $link = 'edit' )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return Plugin::na( FALSE );

		$title = WordPress\Term::title( $term );

		if ( ! $link )
			return Core\HTML::escape( $title );

		$edit = WordPress\Term::edit( $term );

		if ( 'edit' == $link && ! $edit )
			$link = 'view';

		if ( 'edit' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => $edit,
				'title'  => urldecode( $term->slug ),
				'class'  => '-link -row-link -row-link-edit',
				'target' => '_blank',
			], Core\HTML::escape( $title ) );

		if ( 'view' == $link )
			return Core\HTML::tag( 'a', [
				'href'   => WordPress\Term::link( $term ),
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
				return Datetime::humanTimeDiffRound( $row->post_date );
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

	public static function columnPostType( $post_id_prop = NULL )
	{
		return [
			'title'    => _x( 'Type', 'Tablelist: Column: Post Type', 'geditorial' ),
			'args'     => [ 'types' => WordPress\PostType::get( 2, [ 'show_ui' => TRUE ] ) ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $post_id_prop ) {

				if ( ! $post = WordPress\Post::get( $post_id_prop ? $row->{$post_id_prop} : $row ) )
					return Helper::htmlEmpty();

				return $column['args']['types'][$post->post_type] ?? $post->post_type;
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
					$title.= wpautop( WordPress\Strings::prepDescription( $row->post_excerpt, FALSE, FALSE ), FALSE );

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
					? wpautop( WordPress\Strings::prepDescription( $row->post_excerpt, FALSE, FALSE ), FALSE )
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

				return Core\HTML::code( $row->post_status );
			},
		];
	}

	public static function columnPostAuthorSummary()
	{
		return [
			'title'    => _x( 'Author', 'Tablelist: Column: Post Author', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

				if ( current_user_can( 'edit_post', $row->ID ) )
					return WordPress\PostType::authorEditMarkup( $row->post_type, $row->post_author );

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

	public static function columnTermTaxonomyCode( $link = TRUE, $title = NULL )
	{
		return [
			'title'    => $title ?? _x( 'Taxonomy', 'Tablelist: Column Title', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $link ) {
				if ( ! taxonomy_exists( $row->taxonomy ) )
					return Core\HTML::code( $row->taxonomy, FALSE, TRUE );

				return $link
					? Core\HTML::link( Core\Text::code( $row->taxonomy ), WordPress\URL::editTaxonomy( $row->taxonomy ), TRUE )
					: Core\HTML::code( $row->taxonomy, FALSE, TRUE );
			}
		];
	}

	public static function columnTermName( $actions = NULL, $description = FALSE, $custom = [], $title = NULL )
	{
		return [
			'title'    => $title ?: _x( 'Name', 'Tablelist: Column: Term Name', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $description ) {

				if ( ! $term = WordPress\Term::get( $row ) )
					return Plugin::na( FALSE );

				$html = WordPress\Term::title( $term );

				if ( $description && $term->description )
					$html.= wpautop( WordPress\Strings::prepDescription( $term->description, FALSE, FALSE ), FALSE );

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
		$edit = WordPress\Term::edit( $term );

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
						'href'   => WordPress\Term::link( $term ),
						'title'  => urldecode( $term->slug ),
						'class'  => '-link -row-link -row-link-view',
						'target' => '_blank',
					], _x( 'View', 'Tablelist: Row Action: Term', 'geditorial' ) );
			}
		}

		return $list;
	}

	public static function columnTermSlug( $link = FALSE, $title = NULL )
	{
		return [
			'title'    => $title ?? _x( 'Slug', 'Tablelist: Column Title', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $link ) {
				if ( ! taxonomy_exists( $row->taxonomy ) )
					return Core\HTML::code( urldecode( $row->slug ), FALSE, $row->slug );

				return $link
					? Core\HTML::link( Core\Text::code( urldecode( $row->slug ) ), WordPress\Term::edit( $row ), TRUE )
					: Core\HTML::code( urldecode( $row->slug ), FALSE, $row->slug );
			}
		];
	}

	public static function columnTermDesc()
	{
		return [
			'title'    => _x( 'Description', 'Tablelist: Column: Term Desc', 'geditorial' ),
			'class'    => [ 'description', '-description' ],
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				return empty( $row->description )
					? Helper::htmlEmpty()
					: WordPress\Strings::prepDescription( $row->description );
			},
		];
	}

	public static function columnTermMetaList( $title = NULL )
	{
		return [
			'title'    => $title ?? _x( 'Meta', 'Tablelist: Column: Term Desc', 'geditorial' ),
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
				if ( ! $meta = WordPress\Term::getMeta( $row ) )
					return Helper::htmlEmpty();

				return Core\HTML::wrap( self::dump( $meta, TRUE, FALSE ), '-debug-wrap' );
			},
		];
	}

	public static function columnTermMetaDateStart( $metakey = NULL, $calendar = NULL )
	{
		return [
			'title'    => _x( 'Date-Start', 'Tablelist: Column: Term Meta Date-Start', 'geditorial' ),
			'class'    => 'datetime',
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $metakey, $calendar ) {
				$html = '';

				if ( $meta = get_term_meta( $row->term_id, $metakey ?? 'datestart', TRUE ) )
					$html = Datetime::prepForDisplay( trim( $meta ), Datetime::dateFormats( 'datetime' ), $calendar );

				return $html ?: Helper::htmlEmpty();
			},
		];
	}

	public static function columnTermMetaDateEnd( $metakey = NULL, $calendar = NULL )
	{
		return [
			'title'    => _x( 'Date-End', 'Tablelist: Column: Term Meta Date-Start', 'geditorial' ),
			'class'    => 'datetime',
			'callback' => static function ( $value, $row, $column, $index, $key, $args ) use ( $metakey, $calendar ) {
				$html = '';

				if ( $meta = get_term_meta( $row->term_id, $metakey ?? 'dateend', TRUE ) )
					$html = Datetime::prepForDisplay( trim( $meta ), Datetime::dateFormats( 'datetime' ), $calendar );

				return $html ?: Helper::htmlEmpty();
			},
		];
	}
}

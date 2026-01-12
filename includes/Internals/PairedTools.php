<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedTools
{
	public static $pairedtools__action_move_from_to = 'pairedtools_do_move_from_to';

	protected function paired_tools_render_card( $uri = '', $sub = NULL, $supported_list = NULL )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		echo gEditorial\Settings::toolboxCardOpen( _x( 'Paired Move From-To', 'Internal: PairedTools: Card Title', 'geditorial-admin' ), FALSE );

			gEditorial\MetaBox::checklistTerms( 0, [
				'taxonomy'    => $this->constant( $constants[1] ),
				'name'        => 'movefrom',
				'show_count'  => TRUE,
				'minus_count' => 1,                                  // NOTE: the main post-type also get assigned
			] );

			gEditorial\MetaBox::singleselectTerms( 0, [
				'taxonomy' => $this->constant( $constants[1] ),
				'name'     => 'moveto',
			] );

			echo $this->wrap_open( '-wrap-button-row' );

			foreach ( $supported_list ?? $this->list_posttypes() as $posttype => $label )
				gEditorial\Settings::submitButton( self::$pairedtools__action_move_from_to.'['.$posttype.']', sprintf(
					/* translators: `%s`: post-type label */
					_x( 'On %s', 'Button', 'geditorial-admin' ),
					$label
				), 'small' );

			Core\HTML::desc( _x( 'Tries to move supported posts from selected to another main post.', 'Internal: PairedTools: Button Description', 'geditorial-admin' ) );

		echo '</div></div>';

		echo gEditorial\Settings::toolboxCardOpen( _x( 'Paired Tools', 'Internal: PairedTools: Card Title', 'geditorial-admin' ), FALSE );

		echo $this->wrap_open( '-wrap-button-row' );
			gEditorial\Settings::submitButton( 'sync_paired_terms', _x( 'Sync Paired Terms', 'Internal: PairedTools: Button', 'geditorial-admin' ), 'small' );
			Core\HTML::desc( _x( 'Tries to set the paired term for all the main posts.', 'Internal: PairedTools: Button Description', 'geditorial-admin' ), FALSE );
		echo '</div>';

		// NO NEED: we use the main post date directly
		// echo $this->wrap_open( '-wrap-button-row' );
		// 	gEditorial\Settings::submitButton( 'sync_paired_dates', _x( 'Sync Paired Dates', 'Internal: PairedTools: Button', 'geditorial-admin' ), 'small' );
		// 	Core\HTML::desc( _x( 'Tries to set the paired term date based on main posts.', 'Internal: PairedTools: Button Description', 'geditorial-admin' ), FALSE );
		// echo '</div>';

		echo $this->wrap_open( '-wrap-button-row' );
			gEditorial\Settings::submitButton( 'create_paired_terms', _x( 'Create Paired Terms', 'Internal: PairedTools: Button', 'geditorial-admin' ), 'small' );
			Core\HTML::desc( _x( 'Tries to create paired terms for all the main posts.', 'Internal: PairedTools: Button Description', 'geditorial-admin' ), FALSE );
		echo '</div>';

		echo '</div>';

		if ( $this->get_setting( 'paired_force_parents' ) ) {

			echo gEditorial\Settings::toolboxCardOpen( _x( 'Force Assign Paired Parents', 'Internal: PairedTools: Card Title', 'geditorial-admin' ) );

				foreach ( $supported_list ?? $this->list_posttypes() as $posttype => $label )
					gEditorial\Settings::submitButton( add_query_arg( [
						'action' => 'force_assign_parents',
						'type'   => $posttype,
						] ), sprintf(
							/* translators: `%s`: post-type label */
							_x( 'On %s', 'Button', 'geditorial-admin' ),
							$label
						),
						'link-small'
					);

				Core\HTML::desc( _x( 'Forces assignment of parents to supported posts.', 'Internal: PairedTools: Button Description', 'geditorial-admin' ) );
			echo '</div></div>';
		}
	}

	protected function paired_tools_render_before( $uri = '', $sub = NULL )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( FALSE !== $this->paired_tools_handlemove_from_to( $constants, $this->get_sub_limit_option( $sub, 'tools' ) ) )
			return FALSE;

		if ( 'force_assign_parents' === self::req( 'action' )
			&& $this->get_setting( 'paired_force_parents' ) ) {

			if ( FALSE !== $this->paired_force_assign_parents( $constants[0], $constants[1], $this->get_sub_limit_option( $sub, 'tools' ) ) )
				return FALSE;
		}
	}

	protected function paired_imports_handle_tablelist( $sub = NULL )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( gEditorial\Tablelist::isAction( 'create_paired_posts', TRUE ) ) {

			$terms = WordPress\Taxonomy::getTerms( $this->constant( $constants[1] ), FALSE, TRUE );
			$posts = [];

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! isset( $terms[$term_id] ) )
					continue;

				if ( WordPress\Post::getIDbySlug( $terms[$term_id]->slug, $this->constant( $constants[0] ) ) )
					continue;

				$posts[] = WordPress\Post::newByTerm(
					$terms[$term_id],
					$this->constant( $constants[1] ),
					$this->constant( $constants[0] ),
					gEditorial()->user( TRUE )
				);
			}

			WordPress\Redirect::doReferer( [
				'message' => 'created',
				'count'   => count( $posts ),
			] );

		} else if ( gEditorial\Tablelist::isAction( 'resync_paired_images', TRUE ) ) {

			$meta_key = $this->constant( 'metakey_term_image', 'image' );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! $post_id = $this->paired_get_to_post_id( $term_id, $constants[0], $constants[1] ) )
					continue;

				if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
					update_term_meta( $term_id, $meta_key, $thumbnail );

				else
					delete_term_meta( $term_id, $meta_key );

				++$count;
			}

			WordPress\Redirect::doReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'resync_paired_descs', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! $post_id = $this->paired_get_to_post_id( $term_id, $constants[0], $constants[1] ) )
					continue;

				if ( ! $post = WordPress\Post::get( $post_id ) )
					continue;

				if ( wp_update_term( $term_id, $this->constant( $constants[1] ), [ 'description' => $post->post_excerpt ] ) )
					++$count;
			}

			WordPress\Redirect::doReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'store_paired_orders', TRUE ) ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				WordPress\Redirect::doReferer( 'wrong' );

			$count = 0;
			$field = $this->constant( 'field_paired_order', sprintf( 'in_%s_order', $this->constant( $constants[0] ) ) );

			foreach ( $_POST['_cb'] as $term_id ) {

				// FIXME: use alternative version of `paired_all_connected_to()` with just `term_id`
				foreach ( $this->paired_get_from_posts( NULL, $constants[0], $constants[1], FALSE, $term_id ) as $post ) {

					if ( $post->menu_order )
						continue;

					if ( $order = gEditorial()->module( 'meta' )->get_postmeta_field( $post->ID, $field ) ) {

						wp_update_post( [
							'ID'         => $post->ID,
							'menu_order' => $order,
						] );

						++$count;
					}
				}
			}

			WordPress\Redirect::doReferer( [
				'message' => 'ordered',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'empty_paired_descs', TRUE ) ) {

			$taxonomy = $this->constant( $constants[1] );
			$args     = [ 'description' => '' ];
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id )
				if ( wp_update_term( $term_id, $taxonomy, $args ) )
					++$count;

			WordPress\Redirect::doReferer( [
				'message' => 'purged',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'connect_paired_posts', TRUE ) ) {

			$posttype = $this->constant( $constants[0] );
			$terms    = WordPress\Taxonomy::getTerms( $this->constant( $constants[1] ), FALSE, TRUE );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! isset( $terms[$term_id] ) )
					continue;

				if ( ! $post_id = WordPress\Post::getIDbySlug( $terms[$term_id]->slug, $posttype ) )
					continue;

				if ( $this->paired_set_to_term( $post_id, $terms[$term_id], $constants[0], $constants[1] ) )
					++$count;
			}

			WordPress\Redirect::doReferer( [
				'message' => 'updated',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'delete_paired_terms', TRUE ) ) {

			$taxonomy = $this->constant( $constants[1] );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( $this->paired_remove_to_term( FALSE, $term_id, $constants[0], $constants[1] ) ) {

					$deleted = wp_delete_term( $term_id, $taxonomy );

					if ( $deleted && ! is_wp_error( $deleted ) )
						++$count;
				}
			}

			WordPress\Redirect::doReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );
		}

		return TRUE;
	}

	protected function paired_tools_handle_tablelist( $sub = NULL )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( gEditorial\Tablelist::isAction( 'sync_paired_terms' ) ) {

			if ( FALSE === ( $count = $this->paired_sync_paired_terms( $constants[0], $constants[1] ) ) )
				WordPress\Redirect::doReferer( 'wrong' );

			WordPress\Redirect::doReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'sync_paired_dates' ) ) {

			if ( FALSE === ( $count = $this->paired_sync_paired_dates( $constants[0], $constants[1] ) ) )
				WordPress\Redirect::doReferer( 'wrong' );

			WordPress\Redirect::doReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( gEditorial\Tablelist::isAction( 'create_paired_terms' ) ) {

			if ( FALSE === ( $count = $this->paired_create_paired_terms( $constants[0], $constants[1] ) ) )
				WordPress\Redirect::doReferer( 'wrong' );

			WordPress\Redirect::doReferer( [
				'message' => 'created',
				'count'   => $count,
			] );
		}

		return TRUE;
	}

	protected function paired_imports_render_tablelist( $uri = '', $sub = NULL, $actions = NULL, $title = NULL )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$context = 'tools';
		$columns = [
			'_cb'  => 'term_id',
			'name' => gEditorial\Tablelist::columnTermName(),

			'related' => [
				'title'    => _x( 'Slugged / Paired', 'Internal: PairedTools: Table Column', 'geditorial-admin' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) use ( $constants ) {

					if ( $post_id = WordPress\Post::getIDbySlug( $row->slug, $this->constant( $constants[0] ) ) )
						$html = gEditorial\Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';
					else
						$html = gEditorial\Helper::htmlEmpty();

					$html.= '<hr />';

					if ( $post_id = $this->paired_get_to_post_id( $row, $constants[0], $constants[1], FALSE ) )
						$html.= gEditorial\Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';
					else
						$html.= gEditorial\Helper::htmlEmpty();

					return $html;
				},
			],

			'description' => [
				'title'    => _x( 'Desc. / Exce.', 'Internal: PairedTools: Table Column', 'geditorial-admin' ),
				'class'    => 'html-column',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) use ( $constants ) {

					if ( empty( $row->description ) )
						$html = gEditorial\Helper::htmlEmpty();
					else
						$html = WordPress\Strings::prepDescription( $row->description );

					if ( $post_id = $this->paired_get_to_post_id( $row, $constants[0], $constants[1], FALSE ) ) {

						$html.= '<hr />';

						if ( ! $post = WordPress\Post::get( $post_id ) )
							return $html.gEditorial()->na();

						if ( empty( $post->post_excerpt ) )
							$html.= gEditorial\Helper::htmlEmpty();
						else
							$html.= WordPress\Strings::prepDescription( $post->post_excerpt );
					}

					return $html;
				},
			],

			'count' => [
				'title'    => _x( 'Count', 'Internal: PairedTools: Table Column', 'geditorial-admin' ),
				'callback' => function ( $value, $row, $column, $index, $key, $args ) use ( $constants, $context ) {

					if ( $post_id = WordPress\Post::getIDbySlug( $row->slug, $this->constant( $constants[0] ) ) )
						return Core\Number::format( $this->paired_count_connected_to( $post_id, $context ) );

					return Core\Number::format( $row->count );
				},
			],

			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Internal: PairedTools: Table Column', 'geditorial-admin' ),
				'class'    => 'image-column',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) use ( $constants ) {
					$html = '';

					if ( $post_id = $this->paired_get_to_post_id( $row, $constants[0], $constants[1], FALSE ) )
						$html = WordPress\PostType::htmlFeaturedImage( $post_id, [ 45, 72 ] );

					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],

			'term_image' => [
				'title'    => _x( 'Image', 'Internal: PairedTools: Table Column', 'geditorial-admin' ),
				'class'    => 'image-column',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$html = WordPress\Taxonomy::htmlFeaturedImage( $row->term_id, [ 45, 72 ] );
					return $html ?: gEditorial\Helper::htmlEmpty();
				},
			],
		];

		list( $data, $pagination ) = gEditorial\Tablelist::getTerms( [], [], $this->constant( $constants[1] ) );

		if ( FALSE !== $actions ) {

			if ( is_null( $actions ) )
				$actions = [];

			$pagination['actions'] = array_merge( [
				'create_paired_posts'  => _x( 'Create Paired Posts', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
				'connect_paired_posts' => _x( 'Connect Paired Posts', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
				'resync_paired_images' => _x( 'Re-Sync Paired Images', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
				'resync_paired_descs'  => _x( 'Re-Sync Paired Descriptions', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
				'empty_paired_descs'   => _x( 'Empty Paired Descriptions', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
				'store_paired_orders'  => _x( 'Store Paired Orders', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
				'delete_paired_terms'  => _x( 'Delete Paired Terms', 'Internal: PairedTools: Table Action', 'geditorial-admin' ),
			], $actions );
		}

		$pagination['before'][] = gEditorial\Tablelist::filterSearch();

		if ( is_null( $title ) )
			$title = sprintf(
				/* translators: `%s`: post-type label */
				_x( 'Overview of %s', 'Header', 'geditorial-admin' ),
				$this->get_posttype_label( $constants[0], 'extended_label', 'name' )
			);

		$args = [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', $title ),
			'empty'      => _x( 'There are no terms available!', 'Internal: PairedTools: Message', 'geditorial-admin' ),
			'pagination' => $pagination,
		];

		return Core\HTML::tableList( $columns, $data, $args );
	}

	protected function paired_sync_paired_terms( $posttype_key, $taxonomy_key )
	{
		$count    = 0;
		$taxonomy = $this->constant( $taxonomy_key );
		$metakey  = sprintf( '%s_linked', $this->constant( $posttype_key ) );

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => FALSE,
			'orderby'    => 'none',
			'fields'     => 'ids',
		] );

		if ( ! $terms || is_wp_error( $terms ) )
			return FALSE;

		$this->raise_resources( count( $terms ) );

		foreach ( $terms as $term_id ) {

			if ( ! $post_id = get_term_meta( $term_id, $metakey, TRUE ) )
				continue;

			$result = wp_set_object_terms( (int) $post_id, $term_id, $taxonomy, FALSE );

			if ( ! is_wp_error( $result ) )
				++$count;
		}

		return $count;
	}

	// NO NEED
	protected function paired_sync_paired_dates( $posttype_key, $taxonomy_key )
	{
		$count    = 0;
		$taxonomy = $this->constant( $taxonomy_key );
		$metakey  = sprintf( '%s_linked', $this->constant( $posttype_key ) );
		$datetime = $this->constant( 'metakey_term_date', 'datetime' );

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => FALSE,
			'orderby'    => 'none',
			'fields'     => 'ids',
		] );

		if ( ! $terms || is_wp_error( $terms ) )
			return FALSE;

		$this->raise_resources( count( $terms ) );

		foreach ( $terms as $term_id ) {

			if ( ! $post = WordPress\Post::get( get_term_meta( $term_id, $metakey, TRUE ) ) )
				continue;

			$result = update_term_meta( $term_id, $datetime, $post->post_date );

			if ( ! is_wp_error( $result ) )
				++$count;
		}

		return $count;
	}

	protected function paired_create_paired_terms( $posttype_key, $taxonomy_key )
	{
		$count = 0;
		$args  = [
			'orderby'     => 'none',
			'post_status' => 'any',
			'post_type'   => $this->constant( $posttype_key ),
			'tax_query'   => [ [
				'taxonomy' => $this->constant( $taxonomy_key ),
				'operator' => 'NOT EXISTS',
			] ],
			'suppress_filters' => TRUE,
			'posts_per_page'   => -1,
		];

		$query = new \WP_Query();
		$posts = $query->query( $args );

		if ( empty( $posts ) )
			return FALSE;

		$this->raise_resources( count( $posts ) );

		foreach ( $posts as $post )
			if ( $this->paired_do_save_to_post_new( $post, $posttype_key, $taxonomy_key ) )
				++$count;

		return $count;
	}

	protected function paired_tools_handlemove_from_to( $constants, $limit )
	{
		if ( ! $posttype = self::req( self::$pairedtools__action_move_from_to ) )
			return FALSE; // must print nothing

		if ( is_array( $posttype ) )
			$posttype = Core\Arraay::keyFirst( $posttype );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		if ( ! $movefrom = self::req( 'movefrom' ) )
			return ! gEditorial\Info::renderSomethingIsWrong(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		if ( ! $moveto = self::req( 'moveto' ) )
			return ! gEditorial\Info::renderSomethingIsWrong(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		$taxonomy = $this->constant( $constants[1] );
		$movefrom = Core\Arraay::prepNumeral( is_array( $movefrom ) ? $movefrom : explode( ',', $movefrom ) );
		$moveto   = Core\Arraay::prepNumeral( is_array( $moveto ) ? $moveto : explode( ',', $moveto ) );

		if ( empty( $movefrom ) || empty( $moveto ) )
			return FALSE;

		$this->raise_resources( $limit );

		$query = [
			'tax_query' => [ [
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $movefrom,
			] ],
		];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return gEditorial\Settings::processingAllDone( [
				self::$pairedtools__action_move_from_to,
				'movefrom',
				'moveto',
				'paged',
			] );

		if ( $this->get_setting( 'paired_force_parents' ) ) {
			$movefrom = WordPress\Taxonomy::appendParentTermIDs( $movefrom, $taxonomy );
			$moveto   = WordPress\Taxonomy::appendParentTermIDs( $moveto, $taxonomy );
		}

		echo gEditorial\Settings::processingListOpen();

		foreach ( $posts as $post )
			$this->paired__do_post_move_from_to( $post, $taxonomy, $movefrom, $moveto, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			self::$pairedtools__action_move_from_to => $posttype,

			'movefrom' => implode( ',', $movefrom ),
			'moveto'   => implode( ',', $moveto ),
			'paged'    => self::paged() + 1,
		] ) );
	}

	protected function paired__do_post_move_from_to( $post, $taxonomy, $movefrom, $moveto, $verbose = FALSE )
	{
		$currents = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );
		$terms    = Core\Arraay::prepNumeral( array_diff( array_merge( $currents, $moveto ), $movefrom ) );

		if ( $this->get_setting( 'paired_force_parents' ) )
			$terms = WordPress\Taxonomy::appendParentTermIDs( $terms, $taxonomy );

		$result = wp_set_object_terms( $post->ID, $terms, $taxonomy, FALSE );

		if ( self::isError( $result ) )
			return gEditorial\Settings::processingListItem( $verbose,
				/* translators: `%1$s`: post title, `%2$s`: error message */
				_x( 'Something is wrong for &ldquo;%1$s&rdquo;: %2$s', 'Internal: PairedTools: Notice', 'geditorial-admin' ), [
					WordPress\Post::title( $post ),
					$result->get_error_message(),
				] );

		return gEditorial\Settings::processingListItem( $verbose,
			/* translators: `%1$s`: count terms, `%2$s`: post title */
			_x( '%1$s terms set for &ldquo;%2$s&rdquo;!', 'Internal: PairedTools: Notice', 'geditorial-admin' ), [
				Core\HTML::code( count( $result ) ),
				WordPress\Post::title( $post ),
			], TRUE );
	}

	protected function paired_force_assign_parents( $posttype_key, $taxonomy_key, $limit )
	{
		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				gEditorial\Settings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources( $limit );

		$taxonomy = $this->constant( $taxonomy_key );
		$query    = [
			'tax_query' => [ [
				'taxonomy' => $taxonomy,
				'operator' => 'EXISTS',
			] ],
		];

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return gEditorial\Settings::processingAllDone();

		echo gEditorial\Settings::processingListOpen();

		foreach ( $posts as $post )
			$this->paired__do_force_assign_parents( $post, $taxonomy, TRUE );

		echo '</ul></div>';

		return WordPress\Redirect::doJS( add_query_arg( [
			'action' => 'force_assign_parents',
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );
	}

	protected function paired__do_force_assign_parents( $post, $taxonomy, $verbose = FALSE )
	{
		if ( FALSE === ( $result = $this->do_force_assign_parents( $post, $taxonomy ) ) )
			return gEditorial\Settings::processingListItem( $verbose,
				/* translators: `%s`: post title */
				_x( 'Something is wrong for &ldquo;%s&rdquo;!', 'Internal: PairedTools: Notice', 'geditorial-admin' ), [
					WordPress\Post::title( $post ),
				] );

		if ( self::isError( $result ) )
			return gEditorial\Settings::processingListItem( $verbose,
				/* translators: `%1$s`: post title, `%2$s`: error message */
				_x( 'Something is wrong for &ldquo;%1$s&rdquo;: %2$s', 'Internal: PairedTools: Notice', 'geditorial-admin' ), [
					WordPress\Post::title( $post ),
					$result->get_error_message(),
				] );

		return gEditorial\Settings::processingListItem( $verbose,
			/* translators: `%1$s`: count terms, `%2$s`: post title */
			_x( '%1$s terms set for &ldquo;%2$s&rdquo;!', 'Internal: PairedTools: Notice', 'geditorial-admin' ), [
				Core\HTML::code( count( $result ) ),
				WordPress\Post::title( $post ),
			], TRUE );
	}
}

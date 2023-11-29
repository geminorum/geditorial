<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

trait PairedTools
{

	protected function paired_tools_render_card( $uri = '', $sub = NULL, $supported_list = NULL )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
		Core\HTML::h4( _x( 'Paired Tools', 'Internal: PairedTools: Card Title', 'geditorial' ), 'title' );

		echo $this->wrap_open( '-wrap-button-row' );
			Settings::submitButton( 'sync_paired_terms', _x( 'Sync Paired Terms', 'Internal: PairedTools: Button', 'geditorial' ), 'small' );
			Core\HTML::desc( _x( 'Tries to set the paired term for all the main posts.', 'Internal: PairedTools: Button Description', 'geditorial' ), FALSE );
		echo '</div>';

		echo $this->wrap_open( '-wrap-button-row' );
			Settings::submitButton( 'create_paired_terms', _x( 'Create Paired Terms', 'Internal: PairedTools: Button', 'geditorial' ), 'small' );
			Core\HTML::desc( _x( 'Tries to create paired terms for all the main posts.', 'Internal: PairedTools: Button Description', 'geditorial' ), FALSE );
		echo '</div>';

		echo '</div>';

		if ( $this->get_setting( 'paired_force_parents' ) ) {

			echo $this->wrap_open( [ 'card', '-toolbox-card' ] );
			Core\HTML::h4( _x( 'Force Assign Paired Parents', 'Internal: PairedTools: Card Title', 'geditorial' ), 'title' );

			echo $this->wrap_open( '-wrap-button-row' );

				foreach ( $supported_list ?? $this->list_posttypes() as $posttype => $label )
					Settings::submitButton( add_query_arg( [
						'action' => 'force_assign_parents',
						'type'   => $posttype,
					/* translators: %s: posttype label */
					] ), sprintf( _x( 'On %s', 'Internal: PairedTools: Button', 'geditorial' ), $label ), 'link-small' );

				Core\HTML::desc( _x( 'Forces assignment of parents to supported posts.', 'Internal: PairedTools: Button Description', 'geditorial' ) );
			echo '</div></div>';
		}
	}

	protected function paired_tools_render_before( $uri = '', $sub = NULL )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( 'force_assign_parents' === self::req( 'action' )
			&& $this->get_setting( 'paired_force_parents' ) ) {

			if ( $this->paired_force_assign_parents( $constants[0], $constants[1], $this->get_sub_limit_option( $sub ) ) )
				return FALSE;
		}
	}

	protected function paired_tools_handle_tablelist( $sub = NULL )
	{
		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( Tablelist::isAction( 'create_paired_posts', TRUE ) ) {

			$terms = WordPress\Taxonomy::getTerms( $this->constant( $constants[1] ), FALSE, TRUE );
			$posts = [];

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! isset( $terms[$term_id] ) )
					continue;

				if ( WordPress\PostType::getIDbySlug( $terms[$term_id]->slug, $this->constant( $constants[0] ) ) )
					continue;

				$posts[] = WordPress\PostType::newPostFromTerm(
					$terms[$term_id],
					$this->constant( $constants[1] ),
					$this->constant( $constants[0] ),
					gEditorial()->user( TRUE )
				);
			}

			Core\WordPress::redirectReferer( [
				'message' => 'created',
				'count'   => count( $posts ),
			] );

		} else if ( Tablelist::isAction( 'resync_paired_images', TRUE ) ) {

			$meta_key = $this->constant( 'metakey_term_image', 'image' );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! $post_id = $this->paired_get_to_post_id( $term_id, $constants[0], $constants[1] ) )
					continue;

				if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
					update_term_meta( $term_id, $meta_key, $thumbnail );

				else
					delete_term_meta( $term_id, $meta_key );

				$count++;
			}

			Core\WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'resync_paired_descs', TRUE ) ) {

			$count = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! $post_id = $this->paired_get_to_post_id( $term_id, $constants[0], $constants[1] ) )
					continue;

				if ( ! $post = WordPress\Post::get( $post_id ) )
					continue;

				if ( wp_update_term( $term_id, $this->constant( $constants[1] ), [ 'description' => $post->post_excerpt ] ) )
					$count++;
			}

			Core\WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'store_paired_orders', TRUE ) ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				Core\WordPress::redirectReferer( 'wrong' );

			$count = 0;
			$field = $this->constant( 'field_paired_order', sprintf( 'in_%s_order', $this->constant( $constants[0] ) ) );

			foreach ( $_POST['_cb'] as $term_id ) {

				foreach ( $this->paired_get_from_posts( NULL, $constants[0], $constants[1], FALSE, $term_id ) as $post ) {

					if ( $post->menu_order )
						continue;

					if ( $order = gEditorial()->module( 'meta' )->get_postmeta_field( $post->ID, $field ) ) {

						wp_update_post( [
							'ID'         => $post->ID,
							'menu_order' => $order,
						] );

						$count++;
					}
				}
			}

			Core\WordPress::redirectReferer( [
				'message' => 'ordered',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'empty_paired_descs', TRUE ) ) {

			$taxonomy = $this->constant( $constants[1] );
			$args     = [ 'description' => '' ];
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id )
				if ( wp_update_term( $term_id, $taxonomy, $args ) )
					$count++;

			Core\WordPress::redirectReferer( [
				'message' => 'purged',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'connect_paired_posts', TRUE ) ) {

			$posttype = $this->constant( $constants[0] );
			$terms    = WordPress\Taxonomy::getTerms( $this->constant( $constants[1] ), FALSE, TRUE );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( ! isset( $terms[$term_id] ) )
					continue;

				if ( ! $post_id = WordPress\PostType::getIDbySlug( $terms[$term_id]->slug, $posttype ) )
					continue;

				if ( $this->paired_set_to_term( $post_id, $terms[$term_id], $constants[0], $constants[1] ) )
					$count++;
			}

			Core\WordPress::redirectReferer( [
				'message' => 'updated',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'delete_paired_terms', TRUE ) ) {

			$taxonomy = $this->constant( $constants[1] );
			$count    = 0;

			foreach ( $_POST['_cb'] as $term_id ) {

				if ( $this->paired_remove_to_term( NULL, $term_id, $constants[0], $constants[1] ) ) {

					$deleted = wp_delete_term( $term_id, $taxonomy );

					if ( $deleted && ! is_wp_error( $deleted ) )
						$count++;
				}
			}

			Core\WordPress::redirectReferer( [
				'message' => 'deleted',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'sync_paired_terms' ) ) {

			if ( FALSE === ( $count = $this->paired_sync_paired_terms( $constants[0], $constants[1] ) ) )
				Core\WordPress::redirectReferer( 'wrong' );

			Core\WordPress::redirectReferer( [
				'message' => 'synced',
				'count'   => $count,
			] );

		} else if ( Tablelist::isAction( 'create_paired_terms' ) ) {

			if ( FALSE === ( $count = $this->paired_create_paired_terms( $constants[0], $constants[1] ) ) )
				Core\WordPress::redirectReferer( 'wrong' );

			Core\WordPress::redirectReferer( [
				'message' => 'created',
				'count'   => $count,
			] );
		}

		return TRUE;
	}

	protected function paired_tools_render_tablelist( $uri = '', $sub = NULL, $actions = NULL, $title = NULL )
	{
		if ( ! $this->_paired || ! $constants = $this->paired_get_constants() ) {
			if ( $title ) echo Core\HTML::tag( 'h3', $title );
			Core\HTML::desc( gEditorial()->na(), TRUE, '-empty' );
			return FALSE;
		}

		$columns = [
			'_cb'  => 'term_id',
			'name' => Tablelist::columnTermName(),

			'related' => [
				'title'    => _x( 'Slugged / Paired', 'Internal: PairedTools: Table Column', 'geditorial' ),
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $constants ) {

					if ( $post_id = WordPress\PostType::getIDbySlug( $row->slug, $this->constant( $constants[0] ) ) )
						$html = Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';
					else
						$html = Helper::htmlEmpty();

					$html.= '<hr />';

					if ( $post_id = $this->paired_get_to_post_id( $row, $constants[0], $constants[1], FALSE ) )
						$html.= Helper::getPostTitleRow( $post_id ).' &ndash; <small>'.$post_id.'</small>';
					else
						$html.= Helper::htmlEmpty();

					return $html;
				},
			],

			'description' => [
				'title'    => _x( 'Desc. / Exce.', 'Internal: PairedTools: Table Column', 'geditorial' ),
				'class'    => 'html-column',
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $constants ) {

					if ( empty( $row->description ) )
						$html = Helper::htmlEmpty();
					else
						$html = Helper::prepDescription( $row->description );

					if ( $post_id = $this->paired_get_to_post_id( $row, $constants[0], $constants[1], FALSE ) ) {

						$html.= '<hr />';

						if ( ! $post = WordPress\Post::get( $post_id ) )
							return $html.gEditorial()->na();

						if ( empty( $post->post_excerpt ) )
							$html.= Helper::htmlEmpty();
						else
							$html.= Helper::prepDescription( $post->post_excerpt );
					}

					return $html;
				},
			],

			'count' => [
				'title'    => _x( 'Count', 'Internal: PairedTools: Table Column', 'geditorial' ),
				'callback' => function( $value, $row, $column, $index, $key, $args ) use ( $constants ) {

					if ( $post_id = WordPress\PostType::getIDbySlug( $row->slug, $this->constant( $constants[0] ) ) )
						return Core\Number::format( $this->paired_get_from_posts( $post_id, $constants[0], $constants[1], TRUE ) );

					return Core\Number::format( $row->count );
				},
			],

			'thumb_image' => [
				'title'    => _x( 'Thumbnail', 'Internal: PairedTools: Table Column', 'geditorial' ),
				'class'    => 'image-column',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) use ( $constants ) {
					$html = '';

					if ( $post_id = $this->paired_get_to_post_id( $row, $constants[0], $constants[1], FALSE ) )
						$html = WordPress\PostType::htmlFeaturedImage( $post_id, [ 45, 72 ] );

					return $html ?: Helper::htmlEmpty();
				},
			],

			'term_image' => [
				'title'    => _x( 'Image', 'Internal: PairedTools: Table Column', 'geditorial' ),
				'class'    => 'image-column',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$html = WordPress\Taxonomy::htmlFeaturedImage( $row->term_id, [ 45, 72 ] );
					return $html ?: Helper::htmlEmpty();
				},
			],
		];

		list( $data, $pagination ) = Tablelist::getTerms( [], [], $this->constant( $constants[1] ) );

		if ( FALSE !== $actions ) {

			if ( is_null( $actions ) )
				$actions = [];

			$pagination['actions'] = array_merge( [
				'create_paired_posts'  => _x( 'Create Paired Posts', 'Internal: PairedTools: Table Action', 'geditorial' ),
				'connect_paired_posts' => _x( 'Connect Paired Posts', 'Internal: PairedTools: Table Action', 'geditorial' ),
				'resync_paired_images' => _x( 'Re-Sync Paired Images', 'Internal: PairedTools: Table Action', 'geditorial' ),
				'resync_paired_descs'  => _x( 'Re-Sync Paired Descriptions', 'Internal: PairedTools: Table Action', 'geditorial' ),
				'empty_paired_descs'   => _x( 'Empty Paired Descriptions', 'Internal: PairedTools: Table Action', 'geditorial' ),
				'store_paired_orders'  => _x( 'Store Paired Orders', 'Internal: PairedTools: Table Action', 'geditorial' ),
				'delete_paired_terms'  => _x( 'Delete Paired Terms', 'Internal: PairedTools: Table Action', 'geditorial' ),
			], $actions );
		}

		$pagination['before'][] = Tablelist::filterSearch();

		$args = [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', $title ?: _x( 'Paired Terms Tools', 'Internal: PairedTools: Header', 'geditorial' ) ),
			'empty'      => _x( 'There are no terms available!', 'Internal: PairedTools: Message', 'geditorial' ),
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
				$count++;
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
				$count++;

		return $count;
	}

	protected function paired_force_assign_parents( $posttype_key, $taxonomy_key, $limit )
	{
		if ( ! $posttype = self::req( 'type' ) )
			return Info::renderEmptyPosttype();

		if ( ! $this->posttype_supported( $posttype ) )
			return Info::renderNotSupportedPosttype();

		if ( method_exists( $this, '_raise_resources' ) )
			$this->_raise_resources( $limit );

		else
			$this->raise_resources( $limit );

		$taxonomy = $this->constant( $taxonomy_key );
		$query    = [
			'tax_query' => [ [
				'taxonomy' => $taxonomy,
				'operator' => 'EXISTS',
			] ],
		];

		list( $posts, $pagination ) = Tablelist::getPosts( $query, [], $posttype, $limit );

		if ( empty( $posts ) )
			return FALSE;

		echo Settings::processingListOpen();

		foreach ( $posts as $post )
			$this->paired__do_force_assign_parents( $post, $taxonomy, TRUE );

		echo '</ul></div>';

		Core\WordPress::redirectJS( add_query_arg( [
			'action' => 'force_assign_parents',
			'type'   => $posttype,
			'paged'  => self::paged() + 1,
		] ) );

		return TRUE;
	}

	protected function paired__do_force_assign_parents( $post, $taxonomy, $verbose = FALSE )
	{
		if ( FALSE === ( $result = $this->do_force_assign_parents( $post, $taxonomy ) ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %s: post title */
				_x( 'Something is wrong for &ldquo;%s&rdquo;', 'Internal: PairedTools: Notice', 'geditorial' ) ),
				WordPress\Post::title( $post ) ) : TRUE ) && FALSE;

		if ( self::isError( $result ) )
			return ( $verbose ? printf( Core\HTML::tag( 'li',
				/* translators: %1$s: post title, %2$s: error message */
				_x( 'Something is wrong for &ldquo;%1$s&rdquo;: %2$s', 'Internal: PairedTools: Notice', 'geditorial' ) ),
				WordPress\Post::title( $post ), $result->get_error_message() ) : TRUE ) && FALSE;

		if ( $verbose )
			echo Core\HTML::tag( 'li',
				/* translators: %1$s: count terms, %2$s: post title */
				sprintf( _x( '%1$s terms set for &ldquo;%2$s&rdquo;', 'Internal: PairedTools: Notice', 'geditorial' ),
				Core\HTML::code( count( $result ) ),
				WordPress\Post::title( $post )
			) );

		return TRUE;
	}
}

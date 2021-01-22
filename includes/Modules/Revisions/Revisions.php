<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;

class Revisions extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;
	protected $textdomain_frontend  = FALSE;

	protected $caps = [
		'purge'   => 'delete_post',
		'delete'  => 'delete_post',
		'edit'    => 'edit_others_posts',
		'reports' => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'     => 'revisions',
			'title'    => _x( 'Revisions', 'Modules: Revisions', 'geditorial' ),
			'desc'     => _x( 'Revision Management', 'Modules: Revisions', 'geditorial' ),
			'icon'     => 'backup',
			'frontend' => FALSE,
			'disabled' => defined( 'WP_POST_REVISIONS' ) && ! WP_POST_REVISIONS
				? _x( 'Deactivated by Constant', 'Modules: Revisions', 'geditorial' )
				: FALSE,
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				'admin_bulkactions',
				[
					'field'       => 'revision_summary',
					'title'       => _x( 'Revision Count', 'Setting Title', 'geditorial-revisions' ),
					'description' => _x( 'Displays revision summary of the post on the attributes column', 'Setting Description', 'geditorial-revisions' ),
				],
				[
					'field'       => 'revision_wordcount',
					'title'       => _x( 'Revision Word Count', 'Setting Title', 'geditorial-revisions' ),
					'description' => _x( 'Displays revision word count of the post title, content and excerpt', 'Setting Description', 'geditorial-revisions' ),
				],
				[
					'field'       => 'revision_maxcount',
					'type'        => 'number',
					'title'       => _x( 'Revision Max Count', 'Setting Title', 'geditorial-revisions' ),
					/* translators: %s: code placeholder */
					'description' => sprintf( _x( 'The maximum number of revisions to save for each post. %s for every revision.', 'Setting Description', 'geditorial-revisions' ), '<code>-1</code>' ),
					'default'     => '-1',
					'min_attr'    => '-1',
				],
			],
		];
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function admin_init()
	{
		add_action( 'admin_post_'.$this->hook( 'purge' ), [ $this, 'admin_post' ] );
		$this->filter( 'wp_revisions_to_keep', 2, 12 );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base
				&& ( $post = self::req( 'post', FALSE ) )
				&& ! PostType::supportBlocks( $screen->post_type ) ) {

				$this->_admin_enabled();

				$this->action( 'post_submitbox_misc_actions', 1, 12 );
				$this->filter( 'wp_post_revision_title_expanded', 3, 12 );

				$this->enqueue_asset_js( [
					'_nonce' => wp_create_nonce( $this->hook( $post ) ),
				], $screen );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->get_setting( 'admin_bulkactions' ) && $this->cuc( 'edit' ) ) {
					add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
					add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );
					$this->action( 'admin_notices' );
				}

				if ( $this->get_setting( 'revision_summary', FALSE ) )
					$this->action_module( 'tweaks', 'column_attr', 1, 100 );
			}
		}
	}

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, [ 'purgerevisions' => _x( 'Purge Revisions', 'Bulk Action', 'geditorial-revisions' ) ] );
	}

	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		if ( 'purgerevisions' != $doaction )
			return $redirect_to;

		if ( ! $this->cuc( 'edit' ) )
			return $redirect_to;

		$purged = 0;

		foreach ( $post_ids as $post_id )
			if ( $this->purge( $post_id ) )
				$purged++;

		return add_query_arg( $this->hook( 'purged' ), $purged, $redirect_to );
	}

	public function admin_notices()
	{
		if ( ! $purged = self::req( $this->hook( 'purged' ) ) )
			return;

		$_SERVER['REQUEST_URI'] = remove_query_arg( $this->hook( 'purged' ), $_SERVER['REQUEST_URI'] );

		/* translators: %s: revisions count */
		$message = _x( '%s items(s) revisions purged!', 'Message', 'geditorial-revisions' );
		echo HTML::success( sprintf( $message, Number::format( $purged ) ) );
	}

	public function tweaks_column_attr( $post )
	{
		if ( wp_revisions_enabled( $post ) ) {

			$revisions = wp_get_post_revisions( $post->ID, [ 'check_enabled' => FALSE ] );

			$last  = key( $revisions );
			$count = count( $revisions );

			if ( $count > 1 ) {

				$authors = array_unique( array_map( function( $r ){
					return $r->post_author;
				}, $revisions ) );

				$edit = current_user_can( 'edit_post', $last );

				echo '<li class="-row -revisions -count">';

					echo $this->get_column_icon( FALSE, 'backup', _x( 'Revisions', 'Row Icon Title', 'geditorial-revisions' ) );

					/* translators: %s: revisions count */
					$title = sprintf( _nx( '%s Revision', '%s Revisions', $count, 'Row', 'geditorial-revisions' ), Number::format( $count ) );

					if ( current_user_can( 'edit_post', $last ) )
						echo HTML::tag( 'a', [
							'href'   => get_edit_post_link( $last ),
							'title'  => _x( 'View the last revision', 'Title Attr', 'geditorial-revisions' ),
							'target' => '_blank',
						], $title );
					else
						echo $title;

					Helper::getAuthorsEditRow( $authors, $post->post_type, ' <span class="-authors">(', ')</span>' );

				echo '</li>';
			}
		}
	}

	protected function author( $user_id )
	{
		if ( isset( $this->authors[$user_id] ) )
			return $this->authors[$user_id];

		return $this->authors[$user_id] = [
			'name'   => get_the_author_meta( 'display_name', $user_id ),
			'avatar' => get_avatar( $user_id, 24 ),
			// FIXME: add link to user profile
		];
	}

	public static function wordCount( $revision )
	{
		return vsprintf( '[<span class="-wordcount" title="%4$s">%1$s/%2$s/%3$s</span>]', [
			Helper::htmlCount( Text::wordCountUTF8( $revision->post_title ), NULL, '&ndash;' ),
			Helper::htmlCount( Text::wordCountUTF8( $revision->post_content ), NULL, '&ndash;' ),
			Helper::htmlCount( Text::wordCountUTF8( $revision->post_excerpt ), NULL, '&ndash;' ),
			_x( 'Title/Content/Excerpt Word Count', 'Title Attr', 'geditorial-revisions' ),
		] );
	}

	public function wp_post_revision_title_expanded( $revision_date_author, $revision, $link )
	{
		$parts = [];
		$autosave = FALSE;

		$author = $this->author( $revision->post_author );
		$parts['author'] = sprintf( '%1$s %2$s &ndash;', $author['avatar'], $author['name'] );

		$time = strtotime( $revision->post_modified );
		$date = Datetime::dateFormat( $time, 'datetime' );

		$parts['timediff'] = sprintf( '<span class="-timediff" title="%2$s">%1$s</span>', Datetime::humanTimeDiffRound( $time, FALSE ), $date );
		$parts['datetime'] = sprintf( '<span class="-datetime">(<small>%s</small>)</span>', $date );

		if ( $this->get_setting( 'revision_wordcount', FALSE ) )
			$parts['wordcount'] = self::wordCount( $revision );

		if ( ! wp_is_post_revision( $revision ) ) {
			$parts['current'] = _x( '[Current]', 'Indicator', 'geditorial-revisions' );
			$link = FALSE;
		} else if ( wp_is_post_autosave( $revision ) ) {
			$autosave = TRUE;
		}

		if ( $link && current_user_can( 'edit_post', $revision->ID ) ) {
			$parts['edit']   = sprintf( '<a class="button button-small" href="%1$s" title="%3$s">%2$s<span class="-text"> %3$s</span></a>', get_edit_post_link( $revision->ID ), HTML::getDashicon( 'backup' ), _x( 'Browse', 'Title Attr', 'geditorial-revisions' ) );
			$parts['delete'] = sprintf( '<a class="button button-small -delete" href="#" data-id="%1$s" data-parent="%2$s" title="%4$s">%3$s<span class="-text"> %4$s</span></a>', $revision->ID, $revision->post_parent, HTML::getDashicon( 'trash' ), _x( 'Delete', 'Title Attr', 'geditorial-revisions' ) );
		} else {
			$link = FALSE;
		}

		if ( $autosave )
			$parts['autosave'] = _x( '[Autosave]', 'Indicator', 'geditorial-revisions' );

		if ( $link )
			$parts['loading'] = Ajax::spinner();

		return sprintf( '<span class="geditorial-admin-wrap-inline -revisions">%s</span>', implode( ' ', $parts ) );
	}

	public function post_submitbox_misc_actions( $post )
	{
		if ( ! current_user_can( $this->caps['purge'], $post->ID ) )
			return;

		$revisions = wp_get_post_revisions( $post->ID );

		$last  = key( $revisions );
		$count = count( $revisions );

		if ( $count < 2 )
			return;

		echo '<div class="misc-pub-section geditorial-admin-wrap -revisions">';

			echo HTML::tag( 'a', [
				'id'    => $this->hook( 'purge' ),
				'class' => 'button button-small -purge hide-if-no-js', // works with no js but need for style
				'title' => _x( 'Purge all revisions', 'Title Attr', 'geditorial-revisions' ),
				'data'  => [ 'parent' => $post->ID ],
				'href'  => WordPress::getAdminPostLink( $this->hook( 'purge' ), [
					'post_id'  => $post->ID,
					'_wpnonce' => $this->nonce_create( 'purge_'.$post->ID ),
				] ),
			], _x( 'Purge Revisions', 'Button', 'geditorial-revisions' ) );

			echo Ajax::spinner();

			echo HTML::tag( 'a', [
				'id'    => $this->hook( 'browse' ),
				'class' => 'button button-small -browse hide-if-no-js',
				'title' => _x( 'Browse all revisions', 'Title Attr', 'geditorial-revisions' ),
				'href'  => get_edit_post_link( $last ),
			/* translators: %s: revisions count */
		], sprintf( _x( 'Browse %s Revisions', 'Button', 'geditorial-revisions' ),
				'<b>'.Number::format( $count ).'</b>' ) );

		echo '</div>';
	}

	private function purge( $post_id )
	{
		$count = 0;

		foreach ( wp_get_post_revisions( $post_id ) as $revision )
			if ( ! is_wp_error( wp_delete_post_revision( $revision ) ) )
				$count++;

		return $count;
	}

	public function wp_revisions_to_keep( $num, $post )
	{
		// if not supported then no revisions
		if ( ! $this->posttype_supported( $post->post_type ) )
			return 0;

		return $this->get_setting( 'revision_maxcount', $num );
	}

	// FIXME: use `bulk_post_updated_messages` for notices
	public function admin_post()
	{
		if ( empty( $_REQUEST['post_id'] ) )
			WordPress::redirectReferer( 'wrong' );

		if ( ! $this->nonce_verify( 'purge_'.$_REQUEST['post_id'], $_REQUEST['_wpnonce'] ) )
			self::cheatin();

		if ( ! current_user_can( $this->caps['purge'], $_REQUEST['post_id'] ) )
			WordPress::redirectReferer( 'noaccess' );

		WordPress::redirectReferer( [
			$this->hook( 'purged' ) => $this->purge( $_REQUEST['post_id'] ),
		] );
	}

	public function ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		if ( empty( $post['post_id'] ) )
			Ajax::errorMessage();

		Ajax::checkReferer( $this->hook( $post['post_id'] ) );

		switch ( $what ) {

			case 'purge':

				if ( ! current_user_can( $this->caps['purge'], $post['post_id'] ) )
					Ajax::errorUserCant();

				Ajax::success( [ 'count' => $this->purge( $post['post_id'] ) ] );

			break;
			case 'delete':

				if ( empty( $post['revision_id'] ) )
					Ajax::errorMessage();

				if ( ! current_user_can( $this->caps['delete'], $post['post_id'] ) )
					Ajax::errorUserCant();

				if ( is_wp_error( wp_delete_post_revision( $post['revision_id'] ) ) )
					Ajax::errorMessage();

				Ajax::successMessage();
		}

		Ajax::errorWhat();
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( Tablelist::isAction( 'cleanup_revisions', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( wp_is_post_revision( $post_id )
							&& wp_delete_post_revision( $post_id ) )
								$count++;
						else
							$count += $this->purge( $post_id );
					}

					WordPress::redirectReferer( [
						'message' => 'cleaned',
						'count'   => $count,
					] );
				}
			}

			$this->add_sub_screen_option( $sub );
			// $this->register_button( 'cleanup_revisions', _x( 'Cleanup Revisions', 'Button', 'geditorial-revisions' ) );
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list = $this->list_posttypes();

		list( $posts, $pagination ) = $this->getPostArray();

		$pagination['actions']['cleanup_revisions'] = _x( 'Cleanup Revisions', 'Table Action', 'geditorial-revisions' );
		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterAuthors( $list );

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle( [ 'edit', 'view', 'revisions' ] ),
			'revisons' => [
				'title'    => _x( 'Revisions', 'Table Column', 'geditorial-revisions' ),
				'callback' => function( $value, $row, $column, $index ){

					$html = '';

					if ( ! $revisions = wp_get_post_revisions( $row->ID ) )
						return Helper::htmlEmpty();

					foreach ( $revisions as $revision ) {

						$block = '<input type="checkbox" name="_cb[]" value="'.$revision->ID.'" title="'.$revision->ID.'" />';
						$block.= ' '.self::wordCount( $revision );
						$block.= ' &ndash; '.Datetime::humanTimeDiffRound( strtotime( $revision->post_modified ), FALSE );
						$block.= ' &ndash; '.get_the_author_meta( 'display_name', $revision->post_author );

						$html.= '<div>'.$block.'</div>';
					}

					return $html;
				},
			],
			'terms' => Tablelist::columnPostTerms(),
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Post Revisions', 'Header', 'geditorial-revisions' ) ),
			'empty'      => _x( 'No post revisions found.', 'Message', 'geditorial-revisions' ),
			'pagination' => $pagination,
		] );
	}

	// FIXME: better to use `Tablelist::getPosts()`
	protected function getPostArray()
	{
		global $wpdb;

		$where  = '';
		$extra  = [];
		$limit  = $this->get_sub_limit_option();
		$order  = self::order( 'asc' );
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		if ( $user = self::req( 'author' ) ) {
			$where.= $wpdb->prepare( "AND post_author = %d", (int) $user );
			$extra['author'] = $user;
		}

		// $this->posttypes(),
		// FIXME: must limit to selected posttypes
		// `WHERE post_type IN ( '".implode( "', '", esc_sql( (array) $posttypes ) )."' )`

		$ids = $wpdb->get_col( "
			SELECT DISTINCT post_parent
			FROM {$wpdb->posts}
			WHERE post_type = 'revision'
			{$where}
			ORDER BY post_parent {$order}
		" );

		$total = count( $ids );

		if ( ! $total )
			return [ [], [] ];

		$args = [
			'posts_per_page'   => $limit,
			'post__in'         => array_slice( $ids, $offset, $limit ),
			'orderby'          => 'post__in',
			'post_type'        => 'any',
			'post_status'      => 'any',
			'suppress_filters' => TRUE,
		];

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		if ( 'attachment' == $args['post_type'] )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = HTML::tablePagination( $total, ceil( $total / $limit ), $limit, $paged, $extra );

		$pagination['order'] = $order;

		return [ $posts, $pagination ];
	}
}

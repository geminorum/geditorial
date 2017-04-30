<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialRevisions extends gEditorialModuleCore
{

	protected $caps = array(
		'ajax'    => 'edit_posts',
		'purge'   => 'delete_post',
		'delete'  => 'delete_post',
		'edit'    => 'edit_others_posts',
		'reports' => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'     => 'revisions',
			'title'    => _x( 'Revisions', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Revision Management', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'backup',
			'frontend' => FALSE,
		);
	}

	public function settings_intro_after( $module )
	{
		if ( ! WP_POST_REVISIONS )
			gEditorialHTML::warning( _x( 'Revisions are deactivated on this site, this module has no reason to be activated.', 'Modules: Revisions: Setting Section Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
			'_general' => array(
				array(
					'field'       => 'revision_summary',
					'title'       => _x( 'Revision Count', 'Modules: Revisions: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays revision summary of the post on the attributes column', 'Modules: Revisions: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'revision_wordcount',
					'title'       => _x( 'Revision Word Count', 'Modules: Revisions: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays revision word count of the post title, content and excerpt', 'Modules: Revisions: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'revision_maxcount',
					'type'        => 'number',
					'title'       => _x( 'Revision Max Count', 'Modules: Revisions: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'The maximum number of revisions to save for each post. <code>-1</code> for every revision.', 'Modules: Revisions: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '-1',
					'min_attr'    => '-1',
				),
			),
		);
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function admin_init()
	{
		add_action( 'admin_post_'.$this->hook( 'purge' ), array( $this, 'admin_post' ) );
		$this->filter( 'wp_revisions_to_keep', 2, 12 );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base
				&& $post = self::req( 'post', FALSE ) ) {

				$this->_admin_enabled();

				$this->action( 'post_submitbox_misc_actions', 1, 12 );
				$this->filter( 'wp_post_revision_title_expanded', 3, 12 );

				$this->enqueue_asset_js( [
					'_nonce' => wp_create_nonce( $this->hook( $post ) ),
				], $screen );

			} else if ( 'edit' == $screen->base ) {

				if ( $this->cuc( 'edit' ) ) {
					add_filter( 'bulk_actions-'.$screen->id, [ $this, 'bulk_actions' ] );
					add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'handle_bulk_actions' ], 10, 3 );
					$this->action( 'admin_notices' );
				}

				if ( $this->get_setting( 'revision_summary', FALSE ) )
					add_action( 'geditorial_tweaks_column_attr', array( $this, 'column_attr' ), 100 );
			}
		}
	}

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, array( 'purgerevisions' => _x( 'Purge Revisions', 'Modules: Revisions: Bulk Action', GEDITORIAL_TEXTDOMAIN ) ) );
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

		$message = _x( '%s items(s) revisions purged!', 'Modules: Revisions: Message', GEDITORIAL_TEXTDOMAIN );
		gEditorialHTML::success( sprintf( $message, gEditorialNumber::format( $purged ) ), TRUE );
	}

	public function column_attr( $post )
	{
		if ( wp_revisions_enabled( $post ) ) {

			$revisions = wp_get_post_revisions( $post->ID, array( 'check_enabled' => FALSE ) );

			$last  = key( $revisions );
			$count = count( $revisions );

			if ( $count > 1 ) {

				$authors = array_unique( array_map( function( $r ){
					return $r->post_author;
				}, $revisions ) );

				$edit = current_user_can( 'edit_post', $last );

				echo '<li class="-attr -revisions -count">';

					echo $this->get_column_icon( FALSE, 'backup', _x( 'Revisions', 'Modules: Revisions: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

					$title = sprintf( _nx( '%s Revision', '%s Revisions', $count, 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ), gEditorialNumber::format( $count ) );

					if ( current_user_can( 'edit_post', $last ) )
						echo gEditorialHTML::tag( 'a', array(
							'href'   => get_edit_post_link( $last ),
							'title'  => _x( 'View the last revision', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
							'target' => '_blank',
						), $title );
					else
						echo $title;

					gEditorialHelper::getAuthorsEditRow( $authors, $post->post_type, ' <span class="-authors">(', ')</span>' );

				echo '</li>';
			}
		}
	}

	protected function author( $user_id )
	{
		if ( isset( $this->authors[$user_id] ) )
			return $this->authors[$user_id];

		return $this->authors[$user_id] = array(
			'name'   => get_the_author_meta( 'display_name', $user_id ),
			'avatar' => get_avatar( $user_id, 24 ),
			// FIXME: add link to user profile
		);
	}

	public static function wordCount( $revision )
	{
		$title   = gEditorialCoreText::wordCountUTF8( $revision->post_title );
		$content = gEditorialCoreText::wordCountUTF8( $revision->post_content );
		$excerpt = gEditorialCoreText::wordCountUTF8( $revision->post_excerpt );

		return vsprintf( '[<span class="-wordcount" title="%4$s">%1$s &ndash; %2$s &ndash; %3$s</span>]', array(
			gEditorialHelper::htmlCount( $title ),
			gEditorialHelper::htmlCount( $content ),
			gEditorialHelper::htmlCount( $excerpt ),
			_x( 'Title/Content/Excerpt Word Count', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
		) );
	}

	public function wp_post_revision_title_expanded( $revision_date_author, $revision, $link )
	{
		$parts = array();
		$autosave = FALSE;

		$author = $this->author( $revision->post_author );
		$parts['author'] = sprintf( '%1$s %2$s &ndash;', $author['avatar'], $author['name'] );

		$time = strtotime( $revision->post_modified );
		$parts['timediff'] = gEditorialHelper::humanTimeDiffRound( $time, FALSE );
		$parts['datetime'] = '('.date_i18n( _x( 'M j, Y @ H:i', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ), $time ).')';

		if ( $this->get_setting( 'revision_wordcount', FALSE ) )
			$parts['wordcount'] = self::wordCount( $revision );

		if ( ! wp_is_post_revision( $revision ) ) {
			$parts['current'] = _x( '[Current]', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN );
			$link = FALSE;
		} else if ( wp_is_post_autosave( $revision ) ) {
			$autosave = TRUE;
		}

		if ( $link && current_user_can( 'edit_post', $revision->ID ) ) {
			$parts['edit']   = sprintf( '<a class="button button-small" href="%1$s">%2$s %3$s</a>', get_edit_post_link( $revision->ID ), gEditorialHTML::getDashicon( 'edit' ), _x( 'Edit', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ) );
			$parts['delete'] = sprintf( '<a class="button button-small -delete" href="#" data-id="%1$s" data-parent="%2$s">%3$s %4$s</a>', $revision->ID, $revision->post_parent, gEditorialHTML::getDashicon( 'trash' ), _x( 'Delete', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ) );
		} else {
			$link = FALSE;
		}

		if ( $autosave )
			$parts['autosave'] = _x( '[Autosave]', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN );

		if ( $link )
			$parts['loading'] = '<span class="-loading spinner"></span>';

		return sprintf( '<span class="geditorial-admin-wrap-inline -revisions">%s</span>', implode( $parts, ' ' ) );
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

			echo gEditorialHTML::tag( 'a', array(
				'id'    => $this->hook( 'purge' ),
				'class' => 'button button-small -purge',
				'title' => _x( 'Purge all revisions', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
				'data'  => array( 'parent' => $post->ID ),
				'href'  => gEditorialWordPress::getAdminPostLink( $this->hook( 'purge' ), array(
					'post_id'  => $post->ID,
					'_wpnonce' => $this->nonce_create( 'purge_'.$post->ID ),
				) ),
			), _x( 'Purge Revisions', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ) );

			echo '<span class="-loading spinner"></span>';

			echo gEditorialHTML::tag( 'a', array(
				'id'    => $this->hook( 'browse' ),
				'class' => 'button b1utton-small -browse hide-if-no-js',
				'title' => _x( 'Browse all revisions', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
				'href'  => get_edit_post_link( $last ),
			), sprintf( _x( 'Browse %s Revisions', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ),
				'<b>'.gEditorialNumber::format( $count ).'</b>' ) );

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
		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return 0;

		return $this->get_setting( 'revision_maxcount', $num );
	}

	// FIXME: use `bulk_post_updated_messages` for notices
	public function admin_post()
	{
		if ( empty( $_REQUEST['post_id'] ) )
			gEditorialWordPress::redirectReferer( 'wrong' );

		if ( ! $this->nonce_verify( NULL, 'purge_'.$_REQUEST['post_id'] ) )
			self::cheatin();

		if ( ! current_user_can( $this->caps['purge'], $_REQUEST['post_id'] ) )
			gEditorialWordPress::redirectReferer( 'noaccess' );

		gEditorialWordPress::redirectReferer( array(
			$this->hook( 'purged' ) => $this->purge( $_REQUEST['post_id'] ),
		) );
	}

	public function ajax()
	{
		if ( ! $this->cuc( 'ajax' ) )
			self::cheatin();

		$post = wp_unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		if ( empty( $post['post_id'] ) )
			wp_send_json_error();

		gEditorialAjax::checkReferer( $this->hook( $post['post_id'] ) );

		switch ( $what ) {

			case 'purge':

				if ( ! current_user_can( $this->caps['purge'], $post['post_id'] ) )
					wp_send_json_error();

				wp_send_json_success( array(
					'count' => $this->purge( $post['post_id'] ),
				) );

			break;
			case 'delete':

				if ( empty( $post['revision_id'] ) )
					wp_send_json_error();

				if ( ! current_user_can( $this->caps['delete'], $post['post_id'] ) )
					wp_send_json_error();

				if ( is_wp_error( wp_delete_post_revision( $post['revision_id'] ) ) )
					wp_send_json_error();

				wp_send_json_success();
		}

		gEditorialAjax::errorWhat();
	}

	public function reports_settings( $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			return;

		if ( $this->module->name == $sub ) {

			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'reports' );

				if ( isset( $_POST['cleanup_revisions'] )
					&& isset( $_POST['_cb'] )
					&& count( $_POST['_cb'] ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id ) {

						if ( wp_is_post_revision( $post_id )
							&& wp_delete_post_revision( $post_id ) )
								$count++;
						else
							$count += $this->purge( $post_id );
					}

					gEditorialWordPress::redirectReferer( array(
						'message' => 'cleaned',
						'count'   => $count,
					) );
				}
			}

			add_action( 'geditorial_reports_sub_'.$sub, array( $this, 'reports_sub' ), 10, 2 );

			$this->screen_option( $sub );
			$this->register_button( 'cleanup_revisions', _x( 'Cleanup Revisions', 'Modules: Revisions: Setting Button', GEDITORIAL_TEXTDOMAIN ) );
		}

		add_filter( 'geditorial_reports_subs', array( $this, 'append_sub' ), 10, 2 );
	}

	public function reports_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE, FALSE );

			if ( $this->tableSummary() )
				$this->settings_buttons();

		$this->settings_form_after( $uri, $sub );
	}

	private function tableSummary()
	{
		list( $posts, $pagination ) = $this->getPostArray();

		return gEditorialHTML::tableList( array(
			'_cb'   => 'ID',
			'ID'    => gEditorialHelper::tableColumnPostID(),
			'date'  => gEditorialHelper::tableColumnPostDate(),
			'type'  => gEditorialHelper::tableColumnPostType(),
			'title' => gEditorialHelper::tableColumnPostTitle(),
			'revisons' => array(
				'title'    => _x( 'Revisions', 'Modules: Revisions: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'callback' => function( $value, $row, $column, $index ){

					$html = '';

					if ( ! $revisions = wp_get_post_revisions( $row->ID ) )
						return '&mdash;';

					foreach ( $revisions as $revision ) {

						$block = '<input type="checkbox" name="_cb[]" value="'.$revision->ID.'" title="'.$revision->ID.'" />';

						$block .= ' '.self::wordCount( $revision );
						$block .= ' &ndash; '.gEditorialHelper::humanTimeDiffRound( strtotime( $revision->post_modified ), FALSE );
						$block .= ' &ndash; '.get_the_author_meta( 'display_name', $revision->post_author );

						$html .= '<div>'.$block.'</div>';
					}

					return $html;
				},
			),
			'terms' => gEditorialHelper::tableColumnPostTerms(),
		), $posts, array(
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => gEditorialHTML::tag( 'h3', _x( 'Overview of Post Revisions', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ) ),
			'empty'      => gEditorialHTML::warning( _x( 'No Posts!', 'Modules: Revisions', GEDITORIAL_TEXTDOMAIN ) ),
			'pagination' => $pagination,
		) );
	}

	protected function getPostArray()
	{
		global $wpdb;

		$limit  = $this->limit_sub();
		$order  = self::order( 'asc' );
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$ids = $wpdb->get_col( "
			SELECT DISTINCT post_parent
			FROM {$wpdb->posts}
			WHERE post_type = 'revision'
			ORDER BY post_parent {$order}
		" );

		$total = count( $ids );

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
			$args['post_type'] = $_REQUEST['type'];

		if ( 'attachment' == $args['post_type'] )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = gEditorialHTML::tablePagination( $total, ceil( $total / $limit ), $limit, $paged );

		return [ $posts, $pagination ];
	}
}

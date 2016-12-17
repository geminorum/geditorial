<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialRevisions extends gEditorialModuleCore
{

	protected $caps = array(
		'ajax'   => 'edit_posts',
		'purge'  => 'delete_post',
		'delete' => 'delete_post',
	);

	public static function module()
	{
		return array(
			'name'  => 'revisions',
			'title' => _x( 'Revisions', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Revision Management', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'backup',
		);
	}

	public function settings_intro_after( $module )
	{
		if ( ! WP_POST_REVISIONS )
			gEditorialHTML::warning( _x( 'Revisions are deactivated on this site, this module has no reason to be activated.', 'Revisions Module: Setting Section Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
			'_general' => array(
				array(
					'field'       => 'revision_summary',
					'title'       => _x( 'Revision Count', 'Revisions Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays revision summary of the post on the attributes column', 'Revisions Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'revision_wordcount',
					'title'       => _x( 'Revision Word Count', 'Revisions Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays revision word count of the post title, content and excerpt', 'Revisions Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	public function setup_ajax( $request )
	{
		add_action( 'wp_ajax_geditorial_revisions', array( $this, 'ajax' ) );
	}

	public function init()
	{
		$this->actions( 'init', $this->module );
		$this->do_globals();

		if ( is_admin() )
			add_action( 'admin_post_'.$this->hook( 'purge' ), array( $this, 'admin_post' ) );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				$this->_admin_enabled();

				$this->action( 'post_submitbox_misc_actions', 1, 12 );
				$this->filter( 'wp_post_revision_title_expanded', 3, 12 );

				$this->enqueue_asset_js( $screen->base );

			} else if ( 'edit' == $screen->base ) {

				$this->filter( 'bulk_actions-'.$screen->id, 1, 10, 'bulk_actions' );
				$this->filter( 'handle_bulk_actions-'.$screen->id, 3, 10, 'handle_bulk_actions' );
				$this->action( 'admin_notices' );

				if ( $this->get_setting( 'revision_summary', FALSE ) )
					add_action( 'geditorial_tweaks_column_attr', array( $this, 'column_attr' ), 100 );
			}
		}
	}

	public function bulk_actions( $actions )
	{
		return array_merge( $actions, array( 'purgerevisions' => _x( 'Purge Revisions', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ) ) );
	}

	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		if ( 'purgerevisions' != $doaction )
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

		$message = _x( '%s items(s) revisions purged!', 'Revisions Module: Message', GEDITORIAL_TEXTDOMAIN );
		gEditorialHTML::success( sprintf( $message, number_format_i18n( $purged ) ), TRUE );
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

					echo $this->get_column_icon( FALSE, 'backup', _x( 'Revisions', 'Revisions Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

					$title = sprintf( _nx( '%s Revision', '%s Revisions', $count, 'Revisions Module', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $count ) );

					if ( current_user_can( 'edit_post', $last ) )
						echo gEditorialHTML::tag( 'a', array(
							'href'   => get_edit_post_link( $last ),
							'title'  => _x( 'View the last revision', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
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

	protected function wordcount( $revision )
	{
		$title   = gEditorialCoreText::wordCountUTF8( $revision->post_title );
		$content = gEditorialCoreText::wordCountUTF8( $revision->post_content );
		$excerpt = gEditorialCoreText::wordCountUTF8( $revision->post_excerpt );

		return vsprintf( '[<span class="-wordcount" title="%4$s">%1$s &ndash; %2$s &ndash; %3$s</span>]', array(
			gEditorialHelper::htmlCount( $title ),
			gEditorialHelper::htmlCount( $content ),
			gEditorialHelper::htmlCount( $excerpt ),
			_x( 'Title/Content/Excerpt Word Count', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
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
		$parts['datetime'] = '('.date_i18n( _x( 'M j, Y @ H:i', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ), $time ).')';

		if ( $this->get_setting( 'revision_wordcount', FALSE ) )
			$parts['wordcount'] = $this->wordcount( $revision );

		if ( ! wp_is_post_revision( $revision ) ) {
			$parts['current'] = _x( '[Current]', 'Revisions Module', GEDITORIAL_TEXTDOMAIN );
			$link = FALSE;
		} else if ( wp_is_post_autosave( $revision ) ) {
			$autosave = TRUE;
		}

		if ( $link && current_user_can( 'edit_post', $revision->ID ) ) {
			$parts['edit']   = sprintf( '<a class="button button-small" href="%1$s">%2$s %3$s</a>', get_edit_post_link( $revision->ID ), gEditorialHTML::getDashicon( 'edit' ), _x( 'Edit', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ) );
			$parts['delete'] = sprintf( '<a class="button button-small -delete" href="#" data-id="%1$s" data-parent="%2$s">%3$s %4$s</a>', $revision->ID, $revision->post_parent, gEditorialHTML::getDashicon( 'trash' ), _x( 'Delete', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ) );
		} else {
			$link = FALSE;
		}

		if ( $autosave )
			$parts['autosave'] = _x( '[Autosave]', 'Revisions Module', GEDITORIAL_TEXTDOMAIN );

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
				'title' => _x( 'Purge all revisions', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
				'data'  => array( 'parent' => $post->ID ),
				'href'  => gEditorialWordPress::getAdminPostLink( $this->hook( 'purge' ), array(
					'post_id'  => $post->ID,
					'_wpnonce' => $this->nonce_create( 'purge_'.$post->ID ),
				) ),
			), _x( 'Purge Revisions', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ) );

			echo '<span class="-loading spinner"></span>';

			echo gEditorialHTML::tag( 'a', array(
				'id'    => $this->hook( 'browse' ),
				'class' => 'button b1utton-small -browse hide-if-no-js',
				'title' => _x( 'Browse all revisions', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
				'href'  => get_edit_post_link( $last ),
			), sprintf( _x( 'Browse %s Revisions', 'Revisions Module', GEDITORIAL_TEXTDOMAIN ),
				'<b>'.number_format_i18n( $count ).'</b>' ) );

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
		gEditorialAjax::checkReferer();

		if ( ! $this->cuc( 'ajax' ) )
			self::cheatin();

		$post = wp_unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'purge':

				if ( empty( $post['post_id'] ) )
					wp_send_json_error();

				if ( ! current_user_can( $this->caps['purge'], $post['post_id'] ) )
					wp_send_json_error();

				wp_send_json_success( array(
					'count' => $this->purge( $post['post_id'] ),
				) );

			break;
			case 'delete':

				if ( empty( $post['revision_id'] )
					|| empty( $post['post_id'] ) )
						wp_send_json_error();

				if ( ! current_user_can( $this->caps['delete'], $post['post_id'] ) )
					wp_send_json_error();

				if ( is_wp_error( wp_delete_post_revision( $post['revision_id'] ) ) )
					wp_send_json_error();

				wp_send_json_success();
		}

		gEditorialAjax::errorWhat();
	}
}

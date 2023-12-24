<?php namespace geminorum\gEditorial\Modules\Unavailable;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;

class Unavailable extends gEditorial\Module
{

	// TODO: archived support for posttypes: only selected roles can view/edit content
	// https://wordpress.org/plugins/archived-post-status/
	// https://github.com/joshuadavidnelson/archived-post-status/

	public static function module()
	{
		return [
			'name'     => 'unavailable',
			'title'    => _x( 'Unavailable', 'Modules: Unavailable', 'geditorial-admin' ),
			'desc'     => _x( 'Editorial Archived Contents', 'Modules: Unavailable', 'geditorial-admin' ),
			'icon'     => 'archive',
			'frontend' => FALSE,
			'access'   => 'beta',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'archived_comments',
					'title'       => _x( 'Archived Comments', 'Settings', 'geditorial-unavailable' ),
					'description' => _x( 'Activates archived status for comments and hides them from the counts.', 'Settings', 'geditorial-unavailable' ),
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'comment_status' => 'archived',
		];
	}

	public function current_screen( $screen )
	{
		if ( 'edit-comments' == $screen->base ) {

			if ( $this->get_setting( 'archived_comments' ) ) {

				$this->filter( 'comment_status_links', 2, 1 );
				$this->filter( 'comment_row_actions', 2, 15 );

				$this->filter( 'bulk_actions-edit-comments' );
				$this->filter( 'handle_bulk_actions-edit-comments', 3 );

				$this->action( 'pre_get_comments' );
				$this->filter( 'comments_list_table_query_args' );

				$this->enqueue_asset_js( [], $screen );
			}
		}
	}

	public function do_ajax()
	{
		$post = self::unslash( $_REQUEST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		if ( empty( $post['c'] ) )
			Ajax::errorMessage();

		Ajax::checkReferer( $this->hook( $post['c'] ) );

		switch ( $what ) {

			case 'comment_unarchive':

				if ( empty( $post['c'] ) )
					Ajax::errorMessage();

				if ( ! current_user_can( 'edit_comment', $post['c'] ) )
					Ajax::errorUserCant();

				if ( ! $this->comment_archive( $post['c'], FALSE ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Restored', 'Message', 'geditorial-unavailable' ) );

			break;
			case 'comment_doarchive':

				if ( empty( $post['c'] ) )
					Ajax::errorMessage();

				if ( ! current_user_can( 'edit_comment', $post['c'] ) )
					Ajax::errorUserCant();

				if ( ! $this->comment_archive( $post['c'], TRUE ) )
					Ajax::errorMessage();

				Ajax::success( _x( 'Archived', 'Message', 'geditorial-unavailable' ) );
		}

		Ajax::errorWhat();
	}

	public function pre_get_comments( &$query )
	{
		if ( $this->constant( 'comment_status' ) == self::req( 'comment_status' ) )
			$query->query_vars['status'] = $this->constant( 'comment_status' );
	}

	public function comments_list_table_query_args( $args )
	{
		if ( $this->constant( 'comment_status' ) == self::req( 'comment_status' ) )
			$args['status'] = $this->constant( 'comment_status' );

		return $args;
	}

	public function comment_status_links( $status_links )
	{
		global $comment_type;

		$args   = [ 'comment_status' => $this->constant( 'comment_status' ) ];
		$status = isset( $_REQUEST['comment_status'] ) ? $_REQUEST['comment_status'] : 'all';

		if ( ! empty( $comment_type ) && 'all' != $comment_type )
			$args['comment_type'] = $comment_type;

		if ( $status == $args['comment_status'] )
			$status_links['all'] = str_ireplace( ' class="current"', '', $status_links['all'] );

		return Core\Arraay::insert( $status_links, [
			$this->constant( 'comment_status' ) => Core\HTML::tag( 'a', [
				'href'  => add_query_arg( $args, admin_url( 'edit-comments.php' ) ),
				'class' => $this->constant( 'comment_status' ) === $status ? 'current' : FALSE,
			], _x( 'Archived', 'Status Link', 'geditorial-unavailable' ) ),
		], 'approved', 'after' );

		return $status_links;
	}

	public function comment_row_actions( $actions, $comment )
	{
		$args = [
			'action' => $this->hook(),
			'nonce'  => wp_create_nonce( $this->hook( $comment->comment_ID ) ),
			'c'      => $comment->comment_ID,
			'what'   => 'comment_unarchive',
		];

		$data = [
			'id'      => $comment->comment_ID,
			'loading' => _x( 'Loading', 'Action', 'geditorial-unavailable' ),
			'error'   => _x( 'Error!', 'Action', 'geditorial-unavailable' ),
		];

		if ( $this->constant( 'comment_status' ) == $comment->comment_approved )
			return Core\Arraay::insert( $actions, [
				'comment_unarchive' => Core\HTML::tag( 'a', [
					'data'       => $data,
					'href'       => add_query_arg( $args, admin_url( 'admin-ajax.php' ) ),
					'aria-label' => _x( 'Restore this comment from the Archived', 'Action', 'geditorial-unavailable' ),
				], _x( 'Restore', 'Action', 'geditorial-unavailable' ) ),
			], 'edit', 'after' );

		$args['what'] = 'comment_doarchive';

		return Core\Arraay::insert( $actions, [
			'comment_doarchive' => Core\HTML::tag( 'a', [
				'data'       => $data,
				'href'       => add_query_arg( $args, admin_url( 'admin-ajax.php' ) ),
				'aria-label' => _x( 'Send this comment to the Archived', 'Action', 'geditorial-unavailable' ),
			], _x( 'Archive', 'Action', 'geditorial-unavailable' ) ),
		], 'edit', 'after' );
	}

	public function bulk_actions_edit_comments( $actions )
	{
		if ( $this->constant( 'comment_status' ) === self::req( 'comment_status' ) )
			$actions['comment_unarchive'] = _x( 'Restore from Archived', 'Action', 'geditorial-unavailable' );

		else
			$actions['comment_doarchive'] = _x( 'Move to Archived', 'Action', 'geditorial-unavailable' );

		return $actions;
	}

	public function handle_bulk_actions_edit_comments( $redirect_to, $doaction, $comment_ids )
	{
		if ( ! in_array( $doaction, [ 'comment_doarchive', 'comment_unarchive' ] ) )
			return $redirect_to;

		$archived = 0;

		foreach ( $comment_ids as $comment_id )
			if ( $this->comment_archive( $comment_id, ( $doaction == 'comment_doarchive' ? TRUE : FALSE ) ) )
				$archived++;

		return add_query_arg( 'archived', $archived, $redirect_to );
	}

	// @REF: `wp_set_comment_status()`
	protected function comment_archive( $comment_id, $archive = TRUE )
	{
		global $wpdb;

		// unarchived comments must be moderated
		$status = $archive ? $this->constant( 'comment_status' ) : '0';
		$old    = clone get_comment( $comment_id );

		$updated = $wpdb->update( $wpdb->comments,
			[ 'comment_approved' => $status ],
			[ 'comment_ID' => $old->comment_ID ],
		);

		if ( ! $updated )
			return FALSE;

		clean_comment_cache( $old->comment_ID );

		$new = get_comment( $old->comment_ID );

		do_action( 'wp_set_comment_status', $new->comment_ID, $status );

		wp_transition_comment_status( $status, $old->comment_approved, $new );

		wp_update_comment_count( $new->comment_post_ID );

		return TRUE;
	}
}

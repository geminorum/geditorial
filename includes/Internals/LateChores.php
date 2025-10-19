<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait LateChores
{

	/**
	 * Initiates the after-care process for given post-types.
	 * NOTE: must be called on `init` e.g. always!
	 *
	 * @param string|array $posttypes
	 * @return false|string
	 */
	protected function latechores__init_post_aftercare( $posttypes )
	{
		if ( empty( $posttypes ) || ! method_exists( $this, 'latechores_post_aftercare' ) )
			return FALSE;

		$action = $this->hook( 'latechores', 'post_aftercare' );
		add_action( $action, [ $this, 'latechores__do_post_aftercare' ], 10, 2 );

		// if ( Core\WordPress::isCRON() )
		// 	return $action;

		$collectors = [];

		foreach ( (array) $posttypes as $posttype ) {
			$collectors[$posttype] = sprintf( 'save_post_%s', $posttype );
			add_action( $collectors[$posttype], [ $this, 'latechores__collector_post_aftercare' ], 20, 3 );
		}

		add_action( 'shutdown', function () use ( $action, $collectors ) {

			if ( ! empty( $this->process_disabled['aftercare'] ) )
				return;

			$this->latechores__schedule_post_aftercare( $action, $collectors );
		} );

		return $action;
	}

	// EXAMPLE CALLBACK
	// `protected function latechores_post_aftercare( $post ) {}`

	public function latechores__collector_post_aftercare( $post_id, $post, $update )
	{
		if ( ! empty( $this->process_disabled['aftercare'] ) )
			return;

		if ( ! in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			$this->latechores__collect_post_aftercare( $post_id );
	}

	public function latechores__do_post_aftercare( $list, $collectors )
	{
		foreach ( (array) $collectors as $collector )
			remove_action( $collector, [ $this, 'latechores__collector_post_aftercare' ], 20, 3 );

		if ( empty( $list ) )
			return;

		$count = 0;

		foreach ( $list as $post )
			if ( $this->latechores__do_post_aftercare_single( $post ) )
				$count++;

		$this->log( 'NOTICE', sprintf( 'after-care process of posts (%s): %s', $count, implode( ',', $list ) ), $list );
	}

	private function latechores__do_post_aftercare_single( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( TRUE === ( $data = $this->latechores_post_aftercare( $post ) ) )
			return TRUE; // already OK!

		if ( empty( $data ) || ! is_array( $data ) )
			return FALSE; // something's wrong

		$updated = wp_update_post( array_merge( $data, [ 'ID' => $post->ID ] ) );

		if ( ! $updated || self::isError( $updated ) )
			return $this->log( 'FAILED', sprintf( 'after-care process of post #%s', $post->ID ), $post->ID );

		return TRUE;
	}

	public function latechores__collect_post_aftercare( $post_ids )
	{
		global $gEditorialLateChores;

		if ( empty( $gEditorialLateChores ) )
			$gEditorialLateChores = [];

		if ( empty( $gEditorialLateChores[$this->key] ) )
			$gEditorialLateChores[$this->key] = [];

		if ( empty( $gEditorialLateChores[$this->key]['post_aftercare'] ) )
			$gEditorialLateChores[$this->key]['post_aftercare'] = [];

		$gEditorialLateChores[$this->key]['post_aftercare'] = array_merge(
			$gEditorialLateChores[$this->key]['post_aftercare'],
			(array) $post_ids
		);
	}

	private function latechores__schedule_post_aftercare( $action, $collectors = [] )
	{
		global $gEditorialLateChores;

		if ( empty( $gEditorialLateChores[$this->key]['post_aftercare'] ) )
			return;

		$ref  = FALSE;
		$list = Core\Arraay::prepNumeral( $gEditorialLateChores[$this->key]['post_aftercare'] );

		if ( ! empty( $list ) )
			$ref = Services\LateChores::scheduleSingle( $action, [ $list, $collectors ], $this->classs() );

		$gEditorialLateChores[$this->key]['post_aftercare'] = []; // reset!

		return $ref;
	}

	protected function latechores__hook_admin_bulkactions( $screen, $cap_check = NULL )
	{
		if ( ! $this->get_setting( 'admin_bulkactions' ) )
			return FALSE;

		if ( ! method_exists( $this, 'latechores_post_aftercare' ) )
			return FALSE;

		if ( FALSE === $cap_check )
			return FALSE;

		if ( TRUE !== $cap_check && ! WordPress\PostType::can( $screen->post_type, is_null( $cap_check ) ? 'edit_posts' : $cap_check ) )
			return FALSE;

		add_filter( 'bulk_actions-'.$screen->id, [ $this, 'latechores_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-'.$screen->id, [ $this, 'latechores_handle_bulk_actions' ], 20, 3 );
		add_action( 'admin_notices', [ $this, 'latechores_admin_notices' ] );
	}

	public function latechores_bulk_actions( $actions )
	{
		return array_merge( $actions, [
			$this->hook( 'aftercare' ) => sprintf(
				/* translators: `%s`: module title */
				_x( '[%s] Force After-Care', 'Late Chores: Bulk Action', 'geditorial-admin' ),
				$this->module->title
			),
		] );
	}

	public function latechores_handle_bulk_actions( $redirect_to, $doaction, $post_ids )
	{
		if ( $this->hook( 'aftercare' ) != $doaction )
			return $redirect_to;

		$saved = 0;

		foreach ( $post_ids as $post_id )
			if ( FALSE !== ( $data = $this->latechores_post_aftercare( (int) $post_id ) ) )
				if ( wp_update_post( array_merge( $data, [ 'ID' => (int) $post_id ] ) ) )
					$saved++;

		return add_query_arg( $this->hook( 'aftercaremsg' ), $saved, $redirect_to );
	}

	public function latechores_admin_notices()
	{
		if ( ! $saved = self::req( $this->hook( 'aftercaremsg' ) ) )
			return;

		$_SERVER['REQUEST_URI'] = remove_query_arg( $this->hook( 'aftercaremsg' ), $_SERVER['REQUEST_URI'] );

		/* translators: `%s`: post count */
		$message = _x( '%s posts after-cared!', 'Late Chores: Message', 'geditorial-admin' );
		echo Core\HTML::success( sprintf( $message, Core\Number::format( $saved ) ) );
	}
}

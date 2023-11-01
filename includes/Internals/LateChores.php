<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait LateChores
{

	/**
	 * Initiates the after-care procees for given posttypes.
	 * NOTE: must be called on `init` eg: always!
	 *
	 * @param  string|array $posttypes
	 * @return false|string $action
	 */
	protected function latechores__init_post_aftercare( $posttypes )
	{
		if ( empty( $posttypes ) || ! method_exists( $this, 'latechores_post_aftercare' ) )
			return FALSE;

		$action = $this->hook( 'latechores', 'post_aftercare' );
		add_action( $action, [ $this, 'latechores__do_post_aftercare' ], 10, 2 );

		if ( Core\WordPress::isCRON() )
			return $action;

		$collectors = [];

		foreach ( (array) $posttypes as $posttype ) {
			$collectors[$posttype] = sprintf( 'save_post_%s', $posttype );
			add_action( $collectors[$posttype], [ $this, 'latechores__collector_post_aftercare' ], 20, 3 );
		}

		add_action( 'shutdown', function () use ( $action, $collectors ) {
			$this->latechores__schedule_post_aftercare( $action, $collectors );
		} );

		return $action;
	}

	// EXAMPLE CALLBACK
	// protected function latechores_post_aftercare( $post ) {}

	public function latechores__collector_post_aftercare( $post_id, $post, $update )
	{
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

		$data = $this->latechores_post_aftercare( $post );

		if ( empty( $data ) || ! is_array( $data ) )
			return FALSE;

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
}

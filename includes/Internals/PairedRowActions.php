<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedRowActions
{
	protected function _hook_paired_taxonomy_bulk_actions( $posttype_origin, $taxonomy_origin )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( TRUE !== $posttype_origin && ! $this->posttype_supported( $posttype_origin ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		// $paired_taxonomy = $this->constant( $constants[1] );

		if ( ! in_array( $taxonomy_origin, get_object_taxonomies( $paired_posttype ), TRUE ) )
			return FALSE;

		$label = $this->get_posttype_label( $constants[0], 'add_new_item' );
		$key   = sprintf( 'paired_add_new_%s', $paired_posttype );

		add_filter( 'gnetwork_taxonomy_bulk_actions',
			static function ( $actions, $taxonomy ) use ( $taxonomy_origin, $key, $label ) {
				return $taxonomy === $taxonomy_origin
					? array_merge( $actions, [ $key => $label ] )
					: $actions;
			}, 20, 2 );

		add_filter( 'gnetwork_taxonomy_bulk_input',
			function ( $callback, $action, $taxonomy ) use ( $taxonomy_origin, $key ) {
				return ( $taxonomy === $taxonomy_origin && $action === $key )
					? [ $this, 'paired_bulk_input_add_new_item' ]
					: $callback;
			}, 20, 3 );

		add_filter( 'gnetwork_taxonomy_bulk_callback',
			function ( $callback, $action, $taxonomy ) use ( $taxonomy_origin, $key ) {
				return ( $taxonomy === $taxonomy_origin && $action === $key )
					? [ $this, 'paired_bulk_action_add_new_item' ]
					: $callback;
			}, 20, 3 );

		return $key;
	}

	public function paired_bulk_input_add_new_item( $taxonomy, $action )
	{
		printf(
			/* translators: `%s`: clone into input */
			_x( 'as: %s', 'Module: Taxonomy Bulk Input Label', 'geditorial-admin' ),
			'<input name="'.$this->classs( 'paired-add-new-item-target' ).'" type="text" placeholder="'
			._x( 'New Item Title', 'Module: Taxonomy Bulk Input PlaceHolder', 'geditorial-admin' ).'" /> '
		);

		echo Core\HTML::dropdown( [
			'separeted-terms' => _x( 'Separeted Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial-admin' ),
			'cross-terms'     => _x( 'Cross Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial-admin' ),
			'union-terms'     => _x( 'Union Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial-admin' ),
		], [
			'name'     => $this->classs( 'paired-add-new-item-type' ),
			'style'    => 'float:none',
			'selected' => 'separeted-terms',
		] );
	}

	public function paired_bulk_action_add_new_item( $term_ids, $taxonomy, $action )
	{
		global $wpdb;

		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		$paired_taxonomy = $this->constant( $constants[1] );

		if ( $action !== sprintf( 'paired_add_new_%s', $paired_posttype ) )
			return FALSE;

		$target    = self::req( $this->classs( 'paired-add-new-item-target' ) );
		$supported = $this->posttypes();

		switch ( self::req( $this->classs( 'paired-add-new-item-type' ) ) ) {

			case 'cross-terms':

				// Bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// Bail if post with same slug exists
				if ( WordPress\Post::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
					return FALSE;

				$object_lists = [];

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					$object_lists[] = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );
				}

				$object_ids = Core\Arraay::prepNumeral( array_intersect( ...$object_lists ) );

				// Bail if cross term results are empty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = WordPress\Post::newByTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				WordPress\Taxonomy::disableTermCounting();
				Services\LateChores::termCountCollect();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;

			case 'union-terms':

				// Bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// Bail if post with same slug exists
				if ( WordPress\Post::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
					return FALSE;

				$object_lists = [];

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					$object_lists[] = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );
				}

				$object_ids = Core\Arraay::prepNumeral( ...$object_lists );

				// Bail if cross term results are empty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = WordPress\Post::newByTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				WordPress\Taxonomy::disableTermCounting();
				Services\LateChores::termCountCollect();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;
			default:
			case 'separeted-terms':

				WordPress\Taxonomy::disableTermCounting();
				Services\LateChores::termCountCollect();

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					// Bail if post with same slug exists
					if ( WordPress\Post::getIDbySlug( $term->slug, $paired_posttype ) )
						continue;

					$current_objects = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );

					// Bail if the term is empty
					if ( empty( $current_objects ) )
						continue;

					$name = $target ? sprintf( '%s (%s)', $target, $term->name ): $term->name;

					$inserted = wp_insert_term( $name, $paired_taxonomy, [
						'slug'        => $term->slug,
						'description' => $term->description,
					] );

					if ( self::isError( $inserted ) )
						continue;

					$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
					$post_id = WordPress\Post::newByTerm( $cloned, $paired_taxonomy, $paired_posttype );

					if ( self::isError( $post_id ) )
						continue;

					if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
						continue;

					foreach ( $current_objects as $current_object )
						wp_set_object_terms( $current_object, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!
				}
		}

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	protected function pairedrowactions__hook_for_supported_posttypes( $screen, $cap_check = NULL )
	{
		if ( ! $this->get_setting( 'admin_bulkactions' ) )
			return;

		if ( FALSE === $cap_check )
			return;

		if ( ! $constants = $this->paired_get_constants() )
			return;

		if ( TRUE !== $cap_check && ! WordPress\PostType::can( $this->constant( $constants[0] ), is_null( $cap_check ) ? 'edit_posts' : $cap_check ) )
			return;

		add_filter( 'bulk_actions-'.$screen->id,
			function ( $actions ) use ( $constants ) {
				return array_merge( $actions, [

					// bulk action to strip all paired terms for selected supported posts
					$this->classs( 'do_abandon' ) => sprintf(
						/* translators: `%s`: post-type plural label  */
						_x( 'Abandon All %s', 'Internal: PairedRowAction: Action', 'geditorial-admin' ),
						$this->get_posttype_label( $constants[0] )
					)
				] );
			} );

		add_filter( 'handle_bulk_actions-'.$screen->id,
			function ( $redirect_to, $doaction, $post_ids ) use ( $constants ) {

				if ( $this->classs( 'do_abandon' ) !== $doaction )
					return $redirect_to;

				$count  = 0;
				$paired = $this->constant( $constants[1] );

				foreach ( $post_ids as $post_id ) {

					if ( ! current_user_can( 'edit_post', (int) $post_id ) )
						continue;

					$result = wp_set_object_terms( (int) $post_id, NULL, $paired );

					if ( ! is_wp_error( $result ) )
						++$count;
				}

				return add_query_arg( $this->hook( 'processed' ), $count, $redirect_to );

			}, 10, 3 );

		add_action( 'admin_notices',
			function () {
				$hook = $this->hook( 'processed' );

				if ( ! $count = self::req( $hook ) )
					return;

				$_SERVER['REQUEST_URI'] = remove_query_arg( $hook, $_SERVER['REQUEST_URI'] );

				echo Core\HTML::success( sprintf(
					/* translators: `%s`: count */
					_x( '%s items(s) processed!', 'Message', 'geditorial-admin' ),
					Core\Number::format( $count ) )
				);
			} );
	}
}

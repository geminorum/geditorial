<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait PairedCore
{

	// NOTE: `paired_get_paired_constants()` with checks
	// NOTE: not checking for `$this->_paired` for maybe before `init`
	protected function paired_get_constants()
	{
		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( empty( $constants[2] ) )
			$constants[2] = FALSE;

		if ( empty( $constants[3] ) )
			$constants[3] = FALSE;

		return $constants;
	}

	protected function pairedcore__hook_sync_paired_for_ajax()
	{
		$constants = $this->paired_get_paired_constants();

		if ( empty( $constants[0] ) || empty( $constants[1] ) )
			return FALSE;

		if ( $this->is_inline_save_posttype( $constants[0] ) )
			$this->pairedcore__hook_sync_paired( $constants );
	}

	// OLD: `_hook_paired_sync_primary_posttype()`
	protected function pairedcore__hook_sync_paired( $constants = NULL )
	{
		if ( ! $this->_paired )
			return;

		if ( is_null( $constants ) ) {

			$constants = $this->paired_get_paired_constants();

			if ( empty( $constants[0] ) || empty( $constants[1] ) )
				return FALSE;
		}

		$paired_posttype = $this->constant( $constants[0] );
		// $paired_taxonomy = $this->constant( $constants[1] );

		add_action( 'save_post_'.$paired_posttype, function( $post_id, $post, $update ) use ( $constants ) {

			// we handle updates on another action, @SEE: `post_updated` action
			if ( ! $update )
				$this->paired_do_save_to_post_new( $post, $constants[0], $constants[1] );

		}, 20, 3 );

		add_action( 'post_updated', function( $post_id, $post_after, $post_before ) use ( $constants ) {
			$this->paired_do_save_to_post_update( $post_after, $post_before, $constants[0], $constants[1] );
		}, 20, 3 );

		add_action( 'wp_trash_post', function( $post_id ) use ( $constants ) {
			$this->paired_do_trash_to_post( $post_id, $constants[0], $constants[1] );
		} );

		add_action( 'untrash_post', function( $post_id ) use ( $constants ) {
			$this->paired_do_untrash_to_post( $post_id, $constants[0], $constants[1] );
		} );

		add_action( 'before_delete_post', function( $post_id ) use ( $constants ) {
			$this->paired_do_before_delete_to_post( $post_id, $constants[0], $constants[1] );
		} );
	}

	protected function paired_do_save_to_post_new( $post, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_save_post( $post, $posttype_key ) )
			return FALSE;

		$parent = $this->paired_get_to_term( $post->post_parent, $posttype_key, $taxonomy_key );

		$slug = empty( $post->post_name )
			? sanitize_title( $post->post_title )
			: $post->post_name;

		$term_args = [
			'slug'        => $slug,
			'parent'      => $parent ? $parent->term_id : 0,
			'name'        => $post->post_title,
			'description' => $post->post_excerpt,
		];

		$taxonomy = $this->constant( $taxonomy_key );

		// link to existing term
		if ( $namesake = get_term_by( 'slug', $slug, $taxonomy ) )
			$the_term = wp_update_term( $namesake->term_id, $taxonomy, $term_args );

		else
			$the_term = wp_insert_term( $post->post_title, $taxonomy, $term_args );

		if ( is_wp_error( $the_term ) )
			return FALSE;

		return $this->paired_set_to_term( $post->ID, $the_term['term_id'], $posttype_key, $taxonomy_key );
	}

	protected function paired_do_save_to_post_update( $after, $before, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_save_post( $after, $posttype_key ) )
			return FALSE;

		if ( 'trash' == $after->post_status )
			return FALSE;

		$parent = $this->paired_get_to_term( $after->post_parent, $posttype_key, $taxonomy_key );

		if ( empty( $before->post_name ) )
			$before->post_name = sanitize_title( $before->post_title );

		if ( empty( $after->post_name ) )
			$after->post_name = sanitize_title( $after->post_title );

		$term_args = [
			'name'        => $after->post_title,
			'slug'        => $after->post_name,
			'description' => $after->post_excerpt,
			'parent'      => $parent ? $parent->term_id : 0,
		];

		$taxonomy = $this->constant( $taxonomy_key );

		if ( $paired = $this->paired_get_to_term( $after->ID, $posttype_key, $taxonomy_key ) )
			$the_term = wp_update_term( $paired->term_id, $taxonomy, $term_args );

		else if ( $before_slug = get_term_by( 'slug', $before->post_name, $taxonomy ) )
			$the_term = wp_update_term( $before_slug->term_id, $taxonomy, $term_args );

		else if ( $after_slug = get_term_by( 'slug', $after->post_name, $taxonomy ) )
			$the_term = wp_update_term( $after_slug->term_id, $taxonomy, $term_args );

		else
			$the_term = wp_insert_term( $after->post_title, $taxonomy, $term_args );

		if ( is_wp_error( $the_term ) )
			return FALSE;

		return $this->paired_set_to_term( $after->ID, $the_term['term_id'], $posttype_key, $taxonomy_key );
	}

	protected function paired_do_trash_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) )
			wp_update_term( $the_term->term_id, $this->constant( $taxonomy_key ), [
				'name' => $the_term->name.'___TRASHED',
				'slug' => $the_term->slug.'-trashed',
			] );
	}

	protected function paired_do_untrash_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) )
			wp_update_term( $the_term->term_id, $this->constant( $taxonomy_key ), [
				'name' => str_ireplace( '___TRASHED', '', $the_term->name ),
				'slug' => str_ireplace( '-trashed', '', $the_term->slug ),
			] );
	}

	protected function paired_do_before_delete_to_post( $post_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $this->is_posttype( $posttype_key, $post_id ) )
			return;

		if ( $the_term = $this->paired_get_to_term( $post_id, $posttype_key, $taxonomy_key ) ) {
			wp_delete_term( $the_term->term_id, $this->constant( $taxonomy_key ) );
			delete_metadata( 'term', $the_term->term_id, $this->constant( $taxonomy_key ).'_linked' );
		}
	}
}

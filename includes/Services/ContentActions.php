<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class ContentActions extends gEditorial\Service
{
	public static function setup()
	{
		if ( is_admin() ) {

			add_action( 'post_submitbox_start', [ __CLASS__, 'post_submitbox_start' ], 999, 1 );
			add_action( 'save_post', [ __CLASS__, 'save_post' ], 99, 3 );

		} else {

			add_filter( 'the_content', [ __CLASS__, 'the_content' ], 998, 1 );
		}
	}

	// @example: `$this->filter( 'post_actions', 2, 10, FALSE, $this->base );`
	public static function post_submitbox_start( $post )
	{
		if ( ! $actions = apply_filters( self::und( static::BASE, 'post_actions' ), [], $post ) )
			return;

		printf( '<div class="-wrap %s-wrap -post-actions">', static::BASE );

			echo Core\HTML::dropdown( $actions, [
				'none_title' => _x( 'Post Actions', 'Service: ContentActions: None Title', 'geditorial-admin' ),
				'name'       => self::classs( 'post-action' ),
			] );

			// TODO: add `Do` button

			wp_nonce_field(
				self::und( static::BASE, 'post_action' ),
				sprintf( '_%s_post_action', static::BASE ),
				FALSE,
				TRUE
			);

		echo '</div>';
	}

	// @example: `$this->action( 'post_actions_{$action}', 3, 10, FALSE, $this->base );`
	public static function save_post( $post_id, $post, $update )
	{
		if ( ! $action = self::req( self::classs( 'post-action' ) ) )
			return;

		$action = sanitize_text_field( self::unslash( $action ) );
		$hook   = self::und( static::BASE, 'post_action', $action );

		if ( did_action( $hook ) )
			return;

		check_admin_referer(
			self::und( static::BASE, 'post_action' ),
			sprintf( '_%s_post_action', static::BASE )
		);

		do_action( $hook, $post, $action );
	}

	public static function the_content( $content )
	{
		if ( defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			&& GEDITORIAL_DISABLE_CONTENT_ACTIONS )
				return $content;

		$before = $after = '';

		if ( has_action( self::und( static::BASE, 'content_before' ) ) ) {
			ob_start();
				do_action( self::und( static::BASE, 'content_before' ), $content );
			$before = ob_get_clean();

			if ( trim( $before ) )
				$before = '<div class="'.static::BASE.'-wrap-actions content-before">'.$before.'</div>';
		}

		if ( has_action( self::und( static::BASE, 'content_after' ) ) ) {
			ob_start();
				do_action( self::und( static::BASE, 'content_after' ), $content );
			$after = ob_get_clean();

			if ( trim( $after ) )
				$after = '<div class="'.static::BASE.'-wrap-actions content-after">'.$after.'</div>';
		}

		return $before.$content.$after;
	}
}

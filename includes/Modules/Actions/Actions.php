<?php namespace geminorum\gEditorial\Modules\Actions;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class Actions extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'       => 'actions',
			'title'      => _x( 'Actions', 'Modules: Actions', 'geditorial' ),
			'desc'       => _x( 'Editorial Content Actions', 'Modules: Actions', 'geditorial' ),
			'textdomain' => FALSE,
			'configure'  => FALSE,
			'autoload'   => TRUE,
			'access'     => 'stable',
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() ) {

			$this->action( 'add_meta_boxes', 2, 9 );
			$this->action( 'post_submitbox_start', 1, 999 );
			$this->action( 'save_post', 3, 99 );

		} else {

			$this->filter( 'the_content', 1, 998 );
		}
	}

	// @example: `$this->filter_module( 'actions', 'post_actions', 2 );`
	public function post_submitbox_start( $post )
	{
		$actions = $this->filters( 'post_actions', [], $post );

		if ( empty( $actions ) )
			return;

		echo $this->wrap_open( '-post-actions' );

			echo Core\HTML::dropdown( $actions, [
				'none_title' => _x( 'Post Actions', 'Modules: Actions: None Title', 'geditorial' ),
				'name'       => $this->classs( 'post-action' ),
			] );

			// TODO: add `Do` button

			$this->nonce_field( 'postaction' );

		echo '</div>';
	}

	// @example: `$this->action_module( 'actions', 'post_action_{$action}', 3 );`
	public function save_post( $post_id, $post, $update )
	{
		if ( ! $action = self::req( $this->classs( 'post-action' ) ) )
			return;

		$action = sanitize_text_field( self::unslash( $action ) );
		$hook   = $this->hook( 'post_action', $action );

		if ( did_action( $hook ) )
			return;

		$this->nonce_check( 'postaction' );
		do_action( $hook, $post, $action );
	}

	// @REF: https://wpartisan.me/?p=434
	// @REF: https://core.trac.wordpress.org/ticket/45283
	public function add_meta_boxes( $posttype, $post )
	{
		if ( WordPress\PostType::supportBlocksByPost( $post ) )
			return;

		$this->action( 'edit_form_after_title' );
	}

	public function edit_form_after_title( $post )
	{
		echo '<div id="postbox-container-after-title" class="postbox-container">';
			do_meta_boxes( get_current_screen(), 'after_title', $post );
		echo '</div>';
	}

	public function the_content( $content )
	{
		if ( defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			&& GEDITORIAL_DISABLE_CONTENT_ACTIONS )
				return $content;

		$before = $after = '';

		if ( has_action( $this->base.'_content_before' ) ) {
			ob_start();
				do_action( $this->base.'_content_before', $content );
			$before = ob_get_clean();

			if ( trim( $before ) )
				$before = '<div class="'.$this->base.'-wrap-actions content-before">'.$before.'</div>';
		}

		if ( has_action( $this->base.'_content_after' ) ) {
			ob_start();
				do_action( $this->base.'_content_after', $content );
			$after = ob_get_clean();

			if ( trim( $after ) )
				$after = '<div class="'.$this->base.'-wrap-actions content-after">'.$after.'</div>';
		}

		return $before.$content.$after;
	}
}

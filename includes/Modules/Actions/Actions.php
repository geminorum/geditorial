<?php namespace geminorum\gEditorial\Modules\Actions;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
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

		} else {

			$this->filter( 'the_content', 1, 998 );
		}
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

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait MetaBoxMain
{

	protected function _hook_general_mainbox( $screen, $constant_key = 'post', $remove_parent_order = TRUE, $context = NULL, $metabox_context = 'side', $extra = [] )
	{
		$context = $context ?? 'mainbox';

		if ( ! empty( $screen->post_type ) && method_exists( $this, 'store_'.$context.'_metabox_'.$screen->post_type ) )
			add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_'.$context.'_metabox_'.$screen->post_type ], 20, 3 );

		else if ( method_exists( $this, 'store_'.$context.'_metabox' ) )
			add_action( 'save_post', [ $this, 'store_'.$context.'_metabox' ], 20, 3 );

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$metabox  = $this->classs( $context );
		$callback = function ( $post, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $post->post_type )
				);

				do_action( $this->hook_base( 'meta', 'render_metabox' ), $post, $box, NULL );

				$this->_render_mainbox_content( $post, $box, $context, $screen );

				do_action(
					// @HOOK: `geditorial_metabox_mainbox_{current_posttype}`
					$this->hook_base( 'metabox', $context, $post->post_type ),
					$post,
					$box,
					$context,
					$screen
				);

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			'default'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ),
			function ( $classes ) use ( $context, $extra ) {
				return Core\Arraay::prepString( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-'.$this->key,
					'-'.$this->key.'-'.$context,
				], $extra );
			} );
	}

	// DEFAULT METHOD
	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$context = $context ?? 'mainbox';

		gEditorial\MetaBox::fieldPostMenuOrder( $object );
		gEditorial\MetaBox::fieldPostParent( $object );
	}
}

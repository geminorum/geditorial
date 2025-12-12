<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait MetaBoxList
{
	protected function _hook_children_listbox( $screen, $posttypes = NULL, $context = NULL, $metabox_context = NULL, $extra = [] )
	{
		$context   = $context ?? 'listbox';
		$posttypes = $posttypes ?? $this->posttypes();
		$metabox   = $this->classs( $context );

		$callback = function ( $post, $box ) use ( $context, $screen, $posttypes ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			if ( gEditorial\MetaBox::checkDraftMetaBox( $box, $post ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $post->post_type )
				);

				if ( $list = gEditorial\MetaBox::getChildrenPosts( $post, $posttypes ) )
					echo $list;

				else
					echo Core\HTML::wrap( $this->strings_metabox_noitems_via_posttype( $screen->post_type, $context ), 'field-wrap -empty' );

				$this->_render_children_listbox_extra( $post, $box, $context, $screen );

				do_action(
					// @HOOK: `geditorial_metabox_listbox_{current_posttype}`
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
			$metabox_context ?? 'advanced',
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
	protected function _render_children_listbox_extra( $object, $box, $context = NULL, $screen = NULL )
	{
		$context  = $context ?? 'listbox';

		// WTF?!
	}
}

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait MetaBoxSupported
{
	protected function _hook_term_supportedbox( $screen, $context = NULL, $metabox_context = NULL, $metabox_priority = NULL, $extra = [] )
	{
		$context  = $context ?? 'supportedbox';
		$metabox  = $this->classs( $context );
		$callback = function ( $object, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->taxonomy ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox_before', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->taxonomy )
			);

			$this->_render_supportedbox_content( $object, $box, $context, $screen );

			$this->actions(
				sprintf( 'render_%s_metabox_after', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->taxonomy )
			);

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_taxonomy( $screen->taxonomy, $context ),
			$callback,
			$screen,
			$metabox_context ?? 'side',
			$metabox_priority ?? 'default'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ),
			function ( $classes ) use ( $context, $extra ) {
				return array_merge( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-'.$this->key,
					'-'.$this->key.'-'.$context,
				], (array) $extra );
			} );
	}

	protected function _hook_general_supportedbox( $screen, $context = NULL, $metabox_context = NULL, $metabox_priority = NULL, $extra = [] )
	{
		$context  = $context ?? 'supportedbox';
		$metabox  = $this->classs( $context );
		$callback = function ( $object, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox_before', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->post_type )
			);

			$this->_render_supportedbox_content( $object, $box, $context, $screen );

			$this->actions(
				sprintf( 'render_%s_metabox_after', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->post_type )
			);

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context ?? 'side',
			$metabox_priority ?? 'default'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ),
			function ( $classes ) use ( $context, $extra ) {
				return array_merge( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-'.$this->key,
					'-'.$this->key.'-'.$context,
				], (array) $extra );
			} );
	}

	// DEFAULT METHOD
	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		$context = $context ?? 'supportedbox';
		$screen  = $screen  ?? get_current_screen();

		if ( 'post' === $screen->base )
			$action_context = sprintf( '%s_%s', $context, $object->post_type );

		else if ( 'term' === $screen->base )
			$action_context = sprintf( '%s_%s', $context, $object->taxonomy );

		else
			$action_context = sprintf( '%s_%s', $context, 'unknown' );

		$this->actions(
			sprintf( 'render_%s_metabox', $context ),
			$object,
			$box,
			$screen,
			$action_context
		);
	}
}

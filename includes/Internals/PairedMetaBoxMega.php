<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait PairedMetaBoxMega
{
	// NOTE: alternative to `listbox` for `multipaired`
	protected function pairedmetabox__hook_megabox(
		object       $screen,
		?string      $context         = NULL,
		?string      $metabox_context = NULL,
		string|array $extra           = [],
	): false|string {

		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$context ??= 'megabox';

		$callback = function ( $object, $box )
			use ( $constants, $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			if ( gEditorial\MetaBox::checkDraftMetaBox( $box, $object ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions( self::und( 'render', $context, 'metabox' ),
				$object,
				$box,
				NULL,
				self::und( $context, $this->constant( $constants[0] ) ),
			);

			$term = $this->paired_get_to_term( $object->ID, $constants[0], $constants[1] );

			// if ( method_exists( $this, '_get_supported_paired_fields' ) )
			// 	$fields = $this->_get_supported_paired_fields( $context, $object->post_type );

			if ( ! empty( $fields ) )
				$this->_render_paired_megabox_list(
					$object,
					$term,
					$fields,
					$box,
					$context,
					$screen,
					$this->strings_metabox_noitems_via_posttype( $constants[0], $context ),
				);

			// FIXME: new list with custom callback for each item
			else if ( $list = gEditorial\MetaBox::getTermPosts( $this->constant( $constants[1] ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo Core\HTML::wrap(
					$this->strings_metabox_noitems_via_posttype( $constants[0], $context ),
					'field-wrap -empty',
				);

			$this->_render_paired_megabox_extra(
				$object,
				$term,
				$box,
				$context,
				$screen,
			);

			echo '</div>';

			$this->nonce_field( $context );
		};

		$metabox = $this->classs( $context );

		add_meta_box( $metabox,
			$this->strings_metabox_title_via_posttype( $constants[0], $context ),
			$callback,
			$screen,
			$metabox_context ?? 'advanced',
			'low',
		);

		add_filter( self::und( 'postbox', 'classes', $screen->id, $metabox ),
			function ( $classes ) use ( $context, $extra ) {
				return Core\Arraay::prepString( $classes, [
					$this->base.'-wrap',
					'-admin-postbox',
					'-'.$this->key,
					'-'.$this->key.'-'.$context,
				], $extra );
			} );

		if ( $this->role_can( 'import', NULL, TRUE ) )
			gEditorial\Scripts::enqueueColorBox();

		return $metabox;
	}

	protected function _render_paired_megabox_list(
		object $paired_post,
		object $paired_term,
		array $fields,
		false|array $box = FALSE,
		?string $context = NULL,
		?object $screen  = NULL,
		?string $noitems = NULL,
	): void {

		$context ??= 'megabox';

		// WTF?!
	}

	// DEFAULT METHOD
	// https://codepen.io/geminorum/pen/yLQqYVX
	// NOTE: alternative to `listbox`
	protected function _render_paired_megabox_extra(
		object $post,
		object $term,
		false|array $box = FALSE,
		?string $context = NULL,
		?object $screen  = NULL,
	): void {

		$context ??= 'megabox';

		if ( method_exists( $this, '_render_paired_listbox_extra' ) )
			$this->_render_paired_listbox_extra(
				$post,
				$term,
				$box,
				$context,
				$screen,
			);
	}
}

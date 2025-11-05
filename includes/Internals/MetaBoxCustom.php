<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait MetaBoxCustom
{

	public function metaboxcustom_add_metabox_author( $constant, $callback = 'post_author_meta_box' )
	{
		$posttype = WordPress\PostType::object( $this->constant( $constant ) );

		if ( WordPress\PostType::supportBlocks( $posttype->name ) )
			return;

		if ( ! apply_filters( $this->hook_base( 'module', 'metabox_author' ), TRUE, $posttype->name ) )
			return;

		if ( ! current_user_can( $posttype->cap->edit_others_posts ) )
			return;

		add_meta_box( 'authordiv', // same as core to override
			$this->get_posttype_label( $constant, 'author_label', __( 'Author' ) ),
			$callback,
			NULL,
			'normal',
			'core'
		);
	}

	public function metaboxcustom_add_metabox_excerpt( $constant, $metabox_context = NULL, $callback = NULL )
	{
		$posttype = $this->constant( $constant );

		if ( WordPress\PostType::supportBlocks( $posttype ) )
			return FALSE;

		if ( ! apply_filters( $this->hook_base( 'module', 'metabox_excerpt' ), TRUE, $posttype ) )
			return FALSE;

		$screen = get_current_screen();
		$label  = $this->get_posttype_label( $constant, 'excerpt_label', __( 'Excerpt' ) );

		add_meta_box( 'postexcerpt', // same as core to override
			$label,
			$callback ?? [ $this, 'metaboxcustom_do_metabox_excerpt' ],
			$screen,
			$metabox_context ?? 'normal', // 'after_title'
			'high',
			[
				'constant' => $constant,
				'posttype' => $posttype,
				'label'    => $label,
			]
		);

		gEditorial\MetaBox::classEditorBox( $screen, 'postexcerpt' );

		return TRUE;
	}

	public function metaboxcustom_do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		gEditorial\MetaBox::fieldEditorBox(
			$post->post_excerpt,
			'excerpt',
			$box['args']['label']
		);
	}
}

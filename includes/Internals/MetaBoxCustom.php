<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait MetaBoxCustom
{

	public function metaboxcustom_add_metabox_author( object $screen, string $constant, ?string $metabox_context = NULL, bool $check_Support = TRUE, ?callable $callback = NULL ): false|string
	{
		if ( ! empty( $screen->is_block_editor ) )
			return FALSE;

		$posttype = WordPress\PostType::object( $this->constant( $constant ) );

		if ( $check_Support && ! post_type_supports( $posttype->name, 'author' ) )
			return FALSE;

		if ( ! apply_filters( $this->hook_base( 'module', 'metabox_author' ), TRUE, $posttype->name ) )
			return FALSE;

		if ( ! current_user_can( $posttype->cap->edit_others_posts ) )
			return FALSE;

		$metabox = 'authordiv'; // same as core to override
		$label   = $this->get_posttype_label( $constant, 'author_label', __( 'Author' ) );

		add_meta_box( $metabox,
			$label,
			$callback ?? 'post_author_meta_box', // core's default
			$screen,
			$metabox_context ?? 'normal', // 'after_title'
			'core'
		);

		return $metabox;
	}

	public function metaboxcustom_add_metabox_excerpt( object $screen, string $constant, ?string $metabox_context = NULL, bool $check_Support = TRUE, ?callable $callback = NULL ): false|string
	{
		if ( ! empty( $screen->is_block_editor ) )
			return FALSE;

		$posttype = $this->constant( $constant );

		if ( $check_Support && ! post_type_supports( $posttype, 'excerpt' ) )
			return FALSE;

		if ( ! apply_filters( $this->hook_base( 'module', 'metabox_excerpt' ), TRUE, $posttype ) )
			return FALSE;

		$metabox = 'postexcerpt'; // same as core to override
		$label   = $this->get_posttype_label( $constant, 'excerpt_label', __( 'Excerpt' ) );

		add_meta_box( $metabox,
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

		return $metabox;
	}

	public function metaboxcustom_do_metabox_excerpt( object $post, false|array $box ): void
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

<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\WordPress;

trait MetaBoxCustom
{

	public function metaboxcustom_add_metabox_author( $constant, $callback = 'post_author_meta_box' )
	{
		$posttype = WordPress\PostType::object( $this->constant( $constant ) );

		if ( WordPress\PostType::supportBlocks( $posttype->name ) )
			return;

		if ( ! apply_filters( $this->base.'_module_metabox_author', TRUE, $posttype->name ) )
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

	public function metaboxcustom_add_metabox_excerpt( $constant, $callback = 'post_excerpt_meta_box' )
	{
		$posttype = $this->constant( $constant );

		if ( WordPress\PostType::supportBlocks( $posttype ) )
			return;

		if ( ! apply_filters( $this->base.'_module_metabox_excerpt', TRUE, $posttype ) )
			return;

		add_meta_box( 'postexcerpt', // same as core to override
			$this->get_posttype_label( $constant, 'excerpt_label', __( 'Excerpt' ) ),
			$callback,
			NULL,
			'normal',
			'high'
		);
	}
}

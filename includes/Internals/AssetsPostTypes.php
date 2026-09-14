<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait AssetsPostTypes
{
	use PostMeta;

	// `public function assetsposttypes_enqueue_by_posttype( mixed $post, string|object $posttype, ?string $field = NULL, ?string $context = NULL ): bool`

	public function assetsposttypes_enqueue_by_taxonomy( mixed $post, string|object $taxonomy, ?string $field = NULL, ?string $context = NULL ): bool
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		$context ??= 'display';

		if ( ! $terms = WordPress\Taxonomy::getPostTerms( $taxonomy, $post ) )
			return FALSE;

		foreach ( $terms as $term )
			gEditorial\Template::enqueueTermStyles( $term, $field, $context );

		return TRUE;
	}

	protected function assetsposttypes_hook_codebox_fields( string $constant, ?array $fields = NULL, ?string $context = NULL ): bool
	{
		if ( ! $posttype = $this->constant( $constant, $constant ) )
			return FALSE;

		$context ??= 'codebox';

		if ( ! $fields ??= $this->get_strings( $posttype, $context, [], TRUE ) )
			return FALSE;

		return add_action( self::und( 'save_post', $posttype ),
			function ( int $post_id, object $post, bool $update )
				use ( $context, $fields ) {

				if ( empty( $_POST ) )
					return;

				foreach ( $fields as $field => $title ) {

					$name = $this->classs( $context, $field );

					if ( ! array_key_exists( $name, $_POST ) )
						continue;

					if ( ! $this->nonce_verify( self::dsh( $context, $field ) ) )
						continue;

					$this->store_postmeta(
						$post->ID,
						Core\Text::normalizeWhitespace( $_POST[$name] ) ?: FALSE,
						$this->get_postmeta_key( $field ),
					);
				}

			}, 20, 3 );
	}

	protected function assetsposttypes_register_codebox_fields( object $screen, bool $handle_content = FALSE, ?array $fields = NULL, ?string $context = NULL ): bool
	{
		$context ??= 'codebox';

		if ( ! $fields ??= $this->get_strings( $screen->post_type, $context, [], TRUE ) )
			return FALSE;

		$selectors = [];

		foreach ( $fields as $field => $title ) {

			// `$title.= WordPress\MetaBox::markupTitleHelp( $args['description'] );`

			$metabox = $this->classs( $screen->post_type, $field );

			gEditorial\MetaBox::classEditorBox( $screen, $metabox );

			add_meta_box( $metabox,
				$title,
				[ $this, 'assetsposttypes_render_codebox_metabox' ],
				$screen,
				'advanced',
				'high',
				[
					'posttype'    => $screen->post_type,
					'metabox'     => $metabox,
					'field_key'   => $field,
					'field_title' => $title,
					'field_id'    => $this->classs( $context, $field ),   // input id attribute
					'field_name'  => $this->classs( $context, $field ),   // input name attribute
					'field_nonce' => self::dsh( $context, $field ),
					'context'     => $context,
				]
			);

			$selectors[] = sprintf( '#qt_%s-%s-%s-%s_textdirection',
				$this->base,
				$this->key,
				$context,
				Core\Text::sanitizeBase( $field ),
			);
		}

		if ( ! Core\L10n::rtl() )
			return TRUE;

		if ( $handle_content )
			$selectors[] = '#qt_content_textdirection'; // default content editor

		if ( ! count( $selectors ) )
			return FALSE;

		// NOTE: triggering the direction on `RTL` environment.
		gEditorial\Scripts::inlineScript(
			$this->classs( $context, 'quicktags' ),
			'jQuery(function($){$(window).on("load",function(){$("'.implode( ',', $selectors ).'").click();});});'
		);

		return TRUE;
	}

	public function assetsposttypes_render_codebox_metabox( object $post, false|array $box ): void
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$field = $box['args']['field_key'];

		gEditorial\MetaBox::fieldEditorBox(
			$this->fetch_postmeta( $post->ID, '', $this->get_postmeta_key( $field ) ) ?: '',
			$box['args']['field_id'],
			$box['args']['field_title'] ?: $field,
			[
				'textarea_name' => $box['args']['field_name'],
				'editor_class'  => 'editor-status-counts textarea-autosize',
				'tinymce'       => FALSE,
			],
		);

		$this->nonce_field( $box['args']['field_nonce'] );
	}
}

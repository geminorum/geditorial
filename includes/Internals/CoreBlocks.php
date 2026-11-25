<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CoreBlocks
{

	// https://github.com/webtechhardik/wpvibes-acf-blocks/blob/main/wpvibes-acf-blocks.php
	protected function coreblocks__init_blocktyps( ?array $blocktypes = NULL ): void
	{
		$blocktypes ??= $this->get_module_blocktypes();

		foreach ( $blocktypes as $blocktype => $blocktypes_args )
			if ( FALSE === $this->coreblocks__register_blocktype( $blocktype, $blocktypes_args ) )
				$this->log( 'FAILED', sprintf( 'CANNOT REGISTER BLOCKTYPE: %s', $blocktype ), $blocktype );
	}

	// https://core.trac.wordpress.org/ticket/64639
	// https://getbutterfly.com/php-only-block-registration-in-wordpress/
	// https://make.wordpress.org/core/2026/03/03/php-only-block-registration/
	// https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/
	// https://wordpress.github.io/block-development-examples/
	protected function coreblocks__register_blocktype( string $name, array $arguments = [] ): false|object
	{
		$hook      = Core\Text::sanitizeHook( $name );
		$blocktype = sprintf( '%s-%s/%s',
			$this->base,
			$this->key,
			Core\Text::sanitizeBase( $name ),
		);

		$args = self::recursiveParseArgs( $arguments, [
			'apiVersion'  => 3,
			'title'       => $this->coreblocks__get_title( $name ),
			'description' => $this->coreblocks__get_description( $name ),
			'supports'    => [
				'autoRegister' => TRUE,
			],

			// TODO: if `icon` is null then set the module icon
			// 'icon' => 'clock', // omit `dashicons-` prefix!
			// 'category' => 'widgets',
		] );

		if ( ! array_key_exists( 'attributes', $args ) ) {

			if ( ! method_exists( $this, self::und( 'blocktype', $hook, 'attributes' ) ) )
				$args['attributes'] = $this->coreblocks__define_attributes( $name );

			else
				$args['attributes'] = call_user_func_array(
					[ $this, self::und( 'blocktype', $hook, 'attributes' ) ],
					[
						$name,
					],
				);
		}

		if ( ! array_key_exists( 'render_callback', $args ) ) {

			if ( method_exists( $this, self::und( 'blocktype', $hook, 'render_callback' ) ) )
				$args['render_callback'] = [ $this, self::und( 'blocktype', $hook, 'render_callback' ) ];

			else
				$args['render_callback'] = function ( $attributes, $content ) use ( $name ) {
					return $this->coreblocks__default_render_callback(
						$name,
						(array) $attributes ?: [],
						(string )$content ?: '',
					);
				};
		}

		return register_block_type( $blocktype, $args );
	}

	protected function coreblocks__default_render_callback(
		string $name,
		array  $attributes = [],
		string $content    = '',
	): string {

		return $this->coreblocks__wrap_rendered(
			gEditorial\Plugin::na(),
			$name,
			[
				'data-context' => $attributes['context'] ?? '',
			],
		);
	}

	// @SEE: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-attributes/
	protected function coreblocks__define_attributes( string $name ): array
	{
		switch ( $name ) {
			default: return $this->coreblocks__default_attributes( $name );
		}
	}

	// DEFAULT SET of ATTRIBUTES
	protected function coreblocks__default_attributes( string $name ): array
	{
		return [
			'context' => [
				'label'   => _x( 'Context', 'Internal: CoreBlocks: Label', 'geditorial-admin' ),
				'type'    => 'string',
				'default' => '',
			],

			// 'align' => [
			// 	'type' => 'string',
			// 	'enum' => [ 'left', 'center', 'right', 'wide', 'full' ],
			// ],
		];
	}

	protected function coreblocks__get_title( string $name ): string
	{
		$deafult_template = Core\Text::glued( [
			'{{system}}',
			'{{module}}',
			'{{label}}',
		], ': ' );

		return Core\Text::replaceTokens(
			$this->get_string( 'block_title', $name, 'blocks', $deafult_template ),
			[
				'name'   => $name,
				'label'  => WordPress\Strings::makeLabel( $name ),
				'system' => gEditorial\Plugin::system( FALSE ),
				'module' => $this->module->title,
			],
		);
	}

	protected function coreblocks__get_description( string $name ): string
	{
		$deafult_template = '';

		return Core\Text::replaceTokens(
			$this->get_string( 'block_description', $name, 'blocks', $deafult_template ),
			[
				'name'   => $name,
				'label'  => WordPress\Strings::makeLabel( $name ),
				'system' => gEditorial\Plugin::system( FALSE ),
				'module' => $this->module->title,
			],
		);
	}

	protected function coreblocks__wrap_rendered( mixed $html, ?string $blocktype = NULL, array $extra = [] ): string
	{
		return sprintf( '<div %1$s>%2$s</div>',
			$this->coreblocks__wrapper_attributes( $extra, $blocktype ),
			Core\Text::force( $html ),
		);
	}

	protected function coreblocks__wrapper_attributes( array $extra = [], ?string $blocktype = NULL ): string
	{
		return get_block_wrapper_attributes( array_merge( [
			'class' => $this->classs( $blocktype ),
		], $extra ) );
	}
}

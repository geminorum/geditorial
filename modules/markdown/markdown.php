<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;

class Markdown extends gEditorial\Module
{

	protected $parser;

	public static function module()
	{
		return [
			'name'     => 'markdown',
			'title'    => _x( 'Markdown', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Write Posts in Markdown', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => [ 'old', 'markdown' ],
			'frontend' => FALSE,
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
		];
	}

	protected function get_global_constants()
	{
		return [
			'metakey_is_markdown' => '_is_markdown',
		];
	}

	public function init()
	{
		parent::init();

		foreach ( $this->post_types() as $posttype )
			add_post_type_support( $posttype, 'editorial-markdown' );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {
			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2 );
				$this->filter( 'edit_post_content', 2 );
			}
		}
	}

	// @REF: https://github.com/michelf/php-markdown
	private function process( $content, $id )
	{
		// $content is slashed, but Markdown parser hates it precious.
		$content = stripslashes( $content );

		if ( ! $this->parser )
			$this->parser = new \Michelf\MarkdownExtra;

		$content = $this->parser->defaultTransform( $content );

		// reference the post_id to make footnote ids unique
		$content = preg_replace( '/fn(ref)?:/', "fn$1-$id:", $content );

		// WordPress expects slashed data. Put needed ones back.
		return addslashes( $content );
	}

	private function is_markdown( $post_id )
	{
		return (bool) get_post_meta( $post_id, $this->constant( 'metakey_is_markdown' ), TRUE );
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		$post_id = empty( $postarr['ID'] ) ? 0 : $postarr['ID'];

		$data['post_content_filtered'] = $data['post_content'];
		$data['post_content'] = $this->process( $data['post_content'], $post_id );

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		if ( $post_id )
			update_post_meta( $post_id, $this->constant( 'metakey_is_markdown' ), TRUE );

		return $data;
	}

	public function edit_post_content( $value, $post_id )
	{
		if ( ! $this->is_markdown( $post_id ) )
			return $value;

		if ( $post = get_post( $post_id )
			&& ! empty( $post->post_content_filtered ) )
				return $post->post_content_filtered;

		return $value;
	}
}

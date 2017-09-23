<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;

class Markdown extends gEditorial\Module
{

	protected $parser;
	protected $convertor;

	public static function module()
	{
		return [
			'name'  => 'markdown',
			'title' => _x( 'Markdown', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Write Posts in Markdown', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => [ 'old', 'markdown' ],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general' => [
				[
					'field'       => 'wiki_linking',
					'title'       => _x( 'Wiki Linking', 'Modules: Markdown: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Converts wiki like markup to internal links.', 'Modules: Markdown: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
			],
			'_frontend' => [
				'adminbar_summary',
			],
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

	public function init_ajax()
	{
		$this->_hook_ajax();
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

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->post_types() ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Markdown Summary', 'Modules: Markdown: Adminbar', GEDITORIAL_TEXTDOMAIN ),
			'parent' => $parent,
			'href'   => Settings::subURL( $this->key, 'reports' ),
		];

		if ( ! $this->is_markdown( $post_id ) ) {

			$nodes[] = [
				'id'     => $this->classs( 'convert' ),
				'title'  => _x( 'Markdown Convert', 'Modules: Markdown: Adminbar', GEDITORIAL_TEXTDOMAIN ),
				'parent' => $this->classs(),
				'href'   => '#',
			];

		} else {

			$nodes[] = [
				'id'     => $this->classs( 'process' ),
				'title'  => _x( 'Markdown Process', 'Modules: Markdown: Adminbar', GEDITORIAL_TEXTDOMAIN ),
				'parent' => $this->classs(),
				'href'   => '#',
			];

			$nodes[] = [
				'id'     => $this->classs( 'cleanup' ),
				'title'  => _x( 'Markdown Cleanup', 'Modules: Markdown: Adminbar', GEDITORIAL_TEXTDOMAIN ),
				'parent' => $this->classs(),
				'href'   => '#',
			];

			$nodes[] = [
				'id'     => $this->classs( 'discard' ),
				'title'  => _x( 'Markdown Discard', 'Modules: Markdown: Adminbar', GEDITORIAL_TEXTDOMAIN ),
				'parent' => $this->classs(),
				'href'   => '#',
			];
		}

		// $this->enqueue_asset_js();
	}

	public function ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		if ( empty( $post['post_id'] ) )
			Ajax::errorMessage();

		if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
			Ajax::errorMessage();

		if ( ! $this->nonce_verify( $post['post_id'], $post['nonce'] ) )
			self::cheatin();

		switch ( $what ) {

			case 'process':

				if ( ! $this->process_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable process Makrdown content into HTML. Please try again.', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ) );

				Ajax::success();

			break;
			case 'convert':

				if ( ! $this->convert_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable convert content into Makrdown. Please try again.', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ) );

				Ajax::success();

			break;
			case 'cleanup':


				if ( ! $this->cleanup_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable cleanup Makrdown content. Please try again.', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ) );

				Ajax::success();

			break;
			case 'discard':

				if ( ! $this->discard_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable discard Makrdown content into HTML. Please try again.', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ) );

				Ajax::success();
		}

		Ajax::errorWhat();
	}


	// @REF: https://github.com/michelf/php-markdown
	private function process_content( $content, $id )
	{
		// $content is slashed, but Markdown parser hates it precious.
		$content = stripslashes( $content );

		if ( ! $this->parser )
			$this->parser = new \Michelf\MarkdownExtra;

		$content = $this->parser->defaultTransform( $content );

		if ( $this->get_setting( 'wiki_linking' ) )
			$content = $this->linking( $content, get_post( $id ) );

		// reference the post_id to make footnote ids unique
		$content = preg_replace( '/fn(ref)?:/', "fn$1-$id:", $content );

		$content = Text::removeP( $content );

		// WordPress expects slashed data. Put needed ones back.
		return addslashes( $content );
	}

	// @REF: https://github.com/thephpleague/html-to-markdown
	private function convert_content( $content, $id )
	{
		$content = stripslashes( $content );

		if ( ! $this->convertor )
			$this->convertor = new \League\HTMLToMarkdown\HtmlConverter;

		$content = $this->convertor->convert( $content );

		return addslashes( $content );
	}

	// FIXME: do the cleanup!
	private function cleanup_content( $content, $id )
	{
		return $content;
	}

	// @REF: https://en.wikipedia.org/wiki/Help:Wiki_markup#Links_and_URLs
	private function linking( $content, $post )
	{
		// $pattern = '/\[\[(.+?)\]\]/u';
		$pattern = '/\[\[(.*?)\]\]/u';

		return preg_replace_callback( $pattern, function( $match ) use( $content, $post ){

			list( $text, $link, $slug, $post_id ) = $this->make_link( $match[1], $post, $content );
			$html = '<a href="'.$link.'" data-slug="'.$slug.'">'.$text.'</a>';

			return $this->filters( 'linking', $html, $text, $link, $slug, $post_id, $match, $post, $content );
		}, $content );
	}

	// TODO: namespase with `:` for taxonomies
	public function make_link( $text, $post, $content )
	{
		$slug = $text;
		$link = $post_id = FALSE;

		if ( Text::has( $text, '|' ) )
			list( $text, $slug ) = explode( '|', $text );

		$slug = preg_replace( '/\s+/', '-', $slug );

		if ( $post_id = PostType::getIDbySlug( $slug, $post->post_type, TRUE ) )
			$link = WordPress::getPostShortLink( $post_id );

		else
			$link = add_query_arg( [
				'post_type' => $post->post_type,
				'name'      => $slug,
			], get_bloginfo( 'url' ) );

		return [ $text, $link, $slug, $post_id ];
	}

	public function process_post( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( empty( $post->post_content_filtered ) )
			return FALSE;

		$data = [
			'ID'           => $post->ID,
			'post_content' => $this->process_content( $post->post_content_filtered, $post->ID ),
		];

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		if ( ! wp_insert_post( $data ) )
			return FALSE;

		update_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ), TRUE );

		return TRUE;
	}

	public function convert_post( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( $this->is_markdown( $post->ID ) )
			return FALSE;

		if ( empty( $post->post_content ) )
			return FALSE;

		$data = [
			'ID'                    => $post->ID,
			'post_content_filtered' => $post->post_content,
			'post_content'          => $this->convert_content( $post->post_content, $post->ID ),
		];

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		update_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ), TRUE );

		return TRUE;
	}

	public function cleanup_post( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( ! $this->is_markdown( $post->ID ) )
			return FALSE;

		if ( empty( $post->post_content_filtered ) )
			return FALSE;

		$cleaned = $this->cleanup_content( $post->post_content_filtered, $post->ID );

		$data = [
			'ID'                    => $post->ID,
			'post_content_filtered' => $cleaned,
			'post_content'          => $this->process_content( $cleaned, $post->ID ),
		];

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		update_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ), TRUE );

		return TRUE;
	}

	public function discard_post( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		if ( empty( $post->post_content_filtered ) )
			return FALSE;

		$data = [
			'ID'                    => $post->ID,
			'post_content'          => $post->post_content_filtered,
			'post_content_filtered' => '',
		];

		if ( ! wp_insert_post( $data ) )
			return FALSE;

		delete_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ) );

		return TRUE;
	}

	private function is_markdown( $post_id )
	{
		return (bool) get_post_meta( $post_id, $this->constant( 'metakey_is_markdown' ), TRUE );
	}

	public function wp_insert_post_data( $data, $postarr )
	{
		$post_id = empty( $postarr['ID'] ) ? 0 : $postarr['ID'];

		$data['post_content_filtered'] = $data['post_content'];
		$data['post_content'] = $this->process_content( $data['post_content'], $post_id );

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

		$post = get_post( $post_id );

		if ( $post && ! empty( $post->post_content_filtered ) )
			return $post->post_content_filtered;

		return $value;
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {

			if ( ! empty( $_POST ) && isset( $_POST['_cb'] ) && count( $_POST['_cb'] ) ) {

				$this->nonce_check( 'reports', $sub );

				$count = 0;

				if ( isset( $_POST['convert_markdown'] ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->convert_post( $post_id ) )
							$count++;

					if ( $count )
						WordPress::redirectReferer( [
							'message' => 'converted',
							'count'   => $count,
						] );

				} else if ( isset( $_POST['process_markdown'] ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->process_post( $post_id ) )
							$count++;

					if ( $count )
						WordPress::redirectReferer( [
							'message' => 'changed',
							'count'   => $count,
						] );

				} else if ( isset( $_POST['cleanup_markdown'] ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->cleanup_post( $post_id ) )
							$count++;

					if ( $count )
						WordPress::redirectReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );

				} else if ( isset( $_POST['discard_markdown'] ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->discard_post( $post_id ) )
							$count++;

					if ( $count )
						WordPress::redirectReferer( [
							'message' => 'purged',
							'count'   => $count,
						] );
				}
			}

			$this->screen_option( $sub );
		}
	}

	public function reports_sub( $uri, $sub )
	{
		$this->settings_form_before( $uri, $sub, 'bulk', 'reports', FALSE, FALSE );

			$this->tableSummary();

		$this->settings_form_after( $uri, $sub );
	}

	private function tableSummary()
	{
		list( $posts, $pagination ) = $this->getTablePosts();

		$pagination['actions']['convert_markdown'] = _x( 'Convert into Markdown', 'Modules: Markdown: Table Action', GEDITORIAL_TEXTDOMAIN );
		$pagination['actions']['process_markdown'] = _x( 'Re-Process Markdown', 'Modules: Markdown: Table Action', GEDITORIAL_TEXTDOMAIN );
		$pagination['actions']['cleanup_markdown'] = _x( 'Cleanup Markdown', 'Modules: Markdown: Table Action', GEDITORIAL_TEXTDOMAIN );
		$pagination['actions']['discard_markdown'] = _x( 'Discard Markdown', 'Modules: Markdown: Table Action', GEDITORIAL_TEXTDOMAIN );
		$pagination['before'][] = Helper::tableFilterPostTypes( $this->list_post_types() );

		return HTML::tableList( [
			'_cb'      => 'ID',
			'ID'       => Helper::tableColumnPostID(),
			'date'     => Helper::tableColumnPostDate(),
			'type'     => Helper::tableColumnPostType(),
			'markdown' => [
				'title'    => _x( 'Markdown', 'Modules: Markdown: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'class'    => [ '-icon-column' ],
				'callback' => function( $value, $row, $column, $index ){
					return $this->is_markdown( $row->ID ) ? Helper::getIcon( $this->module->icon ) : '';
				},
			],
			'title' => Helper::tableColumnPostTitle(),
			'terms' => Helper::tableColumnPostTerms(),
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Markdown Posts', 'Modules: Markdown', GEDITORIAL_TEXTDOMAIN ) ),
			'empty'      => Helper::tableArgEmptyPosts(),
			'pagination' => $pagination,
		] );
	}
}

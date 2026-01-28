<?php namespace geminorum\gEditorial\Modules\Markdown;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Markdown extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	private $parser;
	private $convertor;

	public static function module()
	{
		return [
			'name'     => 'markdown',
			'title'    => _x( 'Markdown', 'Modules: Markdown', 'geditorial-admin' ),
			'desc'     => _x( 'Write Posts in Markdown', 'Modules: Markdown', 'geditorial-admin' ),
			'icon'     => [ 'misc-32', 'markdown' ],
			'access'   => 'beta',
			'keywords' => [
				'has-adminbar',
			],
		];
	}

	protected function get_global_settings()
	{
		$roles  = $this->get_settings_default_roles();

		return [
			'posttypes_option' => 'posttypes_option',
			'_roles'           => [
				'reports_roles' => [ NULL, $roles ],
				'reports_post_edit',
			],
			'_frontend' => [
				'adminbar_summary',
				[
					'field'       => 'wiki_linking',
					'title'       => _x( 'Wiki Linking', 'Setting Title', 'geditorial-markdown' ),
					'description' => _x( 'Converts wiki like markup into internal links.', 'Setting Description', 'geditorial-markdown' ),
				],
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

		foreach ( $this->posttypes() as $posttype )
			add_post_type_support( $posttype, 'editorial-markdown' );
	}

	public function current_screen( $screen )
	{
		if ( $this->posttype_supported( $screen->post_type ) ) {

			if ( 'post' == $screen->base ) {

				$this->filter( 'wp_insert_post_data', 2 );
				$this->filter( 'edit_post_content', 2 );
			}
		}
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id = get_queried_object_id();
		$classs  = $this->classs();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$nodes[] = [
			'id'     => $classs,
			'parent' => $parent,
			'title'  => _x( 'Markdown Summary', 'Adminbar', 'geditorial-markdown' ),
			'href'   => $this->get_module_url(),
		];

		if ( ! $this->is_markdown( $post_id ) ) {

			$nodes[] = [
				'id'     => $this->classs( 'convert' ),
				'parent' => $classs,
				'title'  => _x( 'Markdown: Convert', 'Adminbar', 'geditorial-markdown' ).Ajax::spinner(),
				'href'   => '#',
				'meta'   => [
					'rel'   => 'convert',
					'class' => $this->class_for_adminbar_node( [ '-has-loading', '-action', $classs ] ),
				],
			];

		} else {

			$nodes[] = [
				'id'     => $this->classs( 'process' ),
				'parent' => $classs,
				'title'  => _x( 'Markdown: Process', 'Adminbar', 'geditorial-markdown' ).Ajax::spinner(),
				'href'   => '#',
				'meta'   => [
					'rel'   => 'process',
					'class' => $this->class_for_adminbar_node( [ '-has-loading', '-action', $classs ] ),
				],
			];

			$nodes[] = [
				'id'     => $this->classs( 'cleanup' ),
				'parent' => $classs,
				'title'  => _x( 'Markdown: Cleanup', 'Adminbar', 'geditorial-markdown' ).Ajax::spinner(),
				'href'   => '#',
				'meta'   => [
					'rel'   => 'cleanup',
					'class' => $this->class_for_adminbar_node( [ '-has-loading', '-action', $classs ] ),
				],
			];

			$nodes[] = [
				'id'     => $this->classs( 'discard' ),
				'parent' => $classs,
				'title'  => _x( 'Markdown: Discard', 'Adminbar', 'geditorial-markdown' ).Ajax::spinner(),
				'href'   => '#',
				'meta'   => [
					'rel'   => 'discard',
					'class' => $this->class_for_adminbar_node( [ '-has-loading', '-action', '-danger', $classs ] ),
				],
			];
		}

		$this->enqueue_asset_js( [
			'post_id' => $post_id,
			'_nonce'  => wp_create_nonce( $this->hook( $post_id ) ),
		] );
	}

	public function do_ajax()
	{
		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		if ( empty( $post['post_id'] ) )
			Ajax::errorMessage();

		if ( ! current_user_can( 'edit_post', $post['post_id'] ) )
			Ajax::errorUserCant();

		Ajax::checkReferer( $this->hook( $post['post_id'] ) );

		switch ( $what ) {

			case 'process':

				if ( ! $this->process_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to process Markdown content into HTML. Please try again.', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage();
				break;

			case 'convert':

				if ( ! $this->convert_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to convert content into Markdown. Please try again.', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage();
				break;

			case 'cleanup':

				if ( ! $this->cleanup_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to cleanup Markdown content. Please try again.', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage();
				break;

			case 'discard':

				if ( ! $this->discard_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to discard Markdown content back into HTML. Please try again.', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage();
		}

		Ajax::errorWhat();
	}

	// @REF: https://github.com/michelf/php-markdown
	private function process_content( $content, $id )
	{
		// `$content` is slashed, but Markdown parser hates it precious.
		$content = stripslashes( $content );

		if ( ! $this->parser )
			$this->parser = new \Michelf\MarkdownExtra();

		$content = $this->parser->defaultTransform( $content );

		if ( $this->get_setting( 'wiki_linking' ) )
			$content = $this->linking( $content, WordPress\Post::get( $id ) );

		// Reference the `post_id` to make footnote ids unique
		$content = preg_replace( '/fn(ref)?:/', "fn$1-$id:", $content );

		$content = Core\Text::removeP( $content );

		// WordPress expects slashed data. Put needed ones back.
		return addslashes( $content );
	}

	// @REF: https://github.com/thephpleague/html-to-markdown
	private function convert_content( $content, $id )
	{
		if ( ! $this->convertor )
			$this->convertor = new \League\HTMLToMarkdown\HtmlConverter();

		return $this->convertor->convert( wpautop( $content ) );
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

		return preg_replace_callback( $pattern,
			function ( $match ) use ( $content, $post ) {

				list( $text, $link, $slug, $post_id ) = $this->make_link( $match[1], $post, $content );
				$html = '<a href="'.$link.'" data-slug="'.$slug.'" class="-wikilink'.( $post_id ? '' : ' -notfound' ).'">'.$text.'</a>';

				return $this->filters( 'linking', $html, $text, $link, $slug, $post_id, $match, $post, $content );
			}, $content );
	}

	// TODO: name-space with `:` for taxonomies
	public function make_link( $text, $post, $content )
	{
		$slug = $text;
		$link = $post_id = FALSE;

		if ( Core\Text::has( $text, '|' ) )
			list( $text, $slug ) = explode( '|', $text, 2 );

		$slug = preg_replace( '/\s+/', '-', $slug );

		if ( $post_id = WordPress\Post::getIDbyURL( $slug, $post->post_type ) )
			$link = WordPress\Post::shortlink( $post_id );

		else
			$link = add_query_arg( [
				'post_type' => $post->post_type,
				'name'      => $slug,
			], get_bloginfo( 'url' ) );

		return [ $text, $link, $slug, $post_id ];
	}

	public function process_post( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( empty( $post->post_content_filtered ) )
			return FALSE;

		$data = [
			'ID'           => $post->ID,
			'post_content' => $this->process_content( $post->post_content_filtered, $post->ID ),
		];

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		if ( ! wp_update_post( $data ) )
			return FALSE;

		update_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ), TRUE );

		return TRUE;
	}

	public function convert_post( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( $this->is_markdown( $post->ID ) )
			return FALSE;

		if ( empty( $post->post_content ) )
			return FALSE;

		$data = [
			'ID'                    => $post->ID,
			'post_content_filtered' => $this->convert_content( $post->post_content, $post->ID ),
		];

		$data['post_content'] = $this->process_content( $data['post_content_filtered'], $post->ID );

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		if ( ! wp_update_post( $data ) )
			return FALSE;

		update_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ), TRUE );

		return TRUE;
	}

	public function cleanup_post( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $this->is_markdown( $post->ID ) )
			return FALSE;

		if ( empty( $post->post_content_filtered ) )
			return FALSE;

		$data = [
			'ID'                    => $post->ID,
			'post_content_filtered' => $this->cleanup_content( $post->post_content_filtered, $post->ID ),
		];

		$data['post_content'] = $this->process_content( $data['post_content_filtered'], $post->ID );

		if ( ! current_user_can( 'unfiltered_html' ) )
			$data['post_content'] = wp_kses_post( $data['post_content'] );

		if ( ! wp_update_post( $data ) )
			return FALSE;

		update_post_meta( $post->ID, $this->constant( 'metakey_is_markdown' ), TRUE );

		return TRUE;
	}

	public function discard_post( $post )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( empty( $post->post_content_filtered ) )
			return FALSE;

		$data = [
			'ID'                    => $post->ID,
			'post_content'          => $post->post_content_filtered,
			'post_content_filtered' => '',
		];

		if ( ! wp_update_post( $data ) )
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

		$post = WordPress\Post::get( $post_id );

		if ( $post && ! empty( $post->post_content_filtered ) )
			return $post->post_content_filtered;

		return $value;
	}

	public function cuc( $context = 'settings', $fallback = '' )
	{
		return $this->_override_module_cuc( $context, $fallback );
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				$count = 0;

				if ( Tablelist::isAction( 'convert_markdown', TRUE ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->convert_post( $post_id ) )
							++$count;

					if ( $count )
						WordPress\Redirect::doReferer( [
							'message' => 'converted',
							'count'   => $count,
						] );

				} else if ( Tablelist::isAction( 'process_markdown', TRUE ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->process_post( $post_id ) )
							++$count;

					if ( $count )
						WordPress\Redirect::doReferer( [
							'message' => 'changed',
							'count'   => $count,
						] );

				} else if ( Tablelist::isAction( 'cleanup_markdown', TRUE ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->cleanup_post( $post_id ) )
							++$count;

					if ( $count )
						WordPress\Redirect::doReferer( [
							'message' => 'cleaned',
							'count'   => $count,
						] );

				} else if ( Tablelist::isAction( 'discard_markdown', TRUE ) ) {

					foreach ( $_POST['_cb'] as $post_id )
						if ( $this->discard_post( $post_id ) )
							++$count;

					if ( $count )
						WordPress\Redirect::doReferer( [
							'message' => 'purged',
							'count'   => $count,
						] );
				}
			}
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list = $this->list_posttypes();

		list( $posts, $pagination ) = Tablelist::getPosts( [], [], array_keys( $list ), $this->get_sub_limit_option( $sub, 'reports' ) );

		$pagination['actions']['convert_markdown'] = _x( 'Convert into Markdown', 'Table Action', 'geditorial-markdown' );
		$pagination['actions']['process_markdown'] = _x( 'Re-Process Markdown', 'Table Action', 'geditorial-markdown' );
		$pagination['actions']['cleanup_markdown'] = _x( 'Cleanup Markdown', 'Table Action', 'geditorial-markdown' );
		$pagination['actions']['discard_markdown'] = _x( 'Discard Markdown', 'Table Action', 'geditorial-markdown' );
		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'      => 'ID',
			'ID'       => Tablelist::columnPostID(),
			'date'     => Tablelist::columnPostDate(),
			'type'     => Tablelist::columnPostType(),
			'markdown' => [
				'title'    => _x( 'Markdown', 'Table Column', 'geditorial-markdown' ),
				'class'    => [ '-icon-column' ],
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					return $this->is_markdown( $row->ID ) ? Services\Icons::get( $this->module->icon ) : '';
				},
			],
			'title' => Tablelist::columnPostTitle(),
			'terms' => Tablelist::columnPostTerms(),
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Markdown Posts', 'Header', 'geditorial-markdown' ) ),
			'empty'      => _x( 'No markdown posts found.', 'Message', 'geditorial-markdown' ),
			'pagination' => $pagination,
		] );
	}
}

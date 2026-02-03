<?php namespace geminorum\gEditorial\Modules\Markdown;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Markdown extends gEditorial\Module
{
	use Internals\RestAPI;

	protected $disable_no_posttypes = TRUE;
	protected $priority_adminbar_init = 210;

	public static function module()
	{
		return [
			'name'     => 'markdown',
			'title'    => _x( 'Markdown', 'Modules: Markdown', 'geditorial-admin' ),
			'desc'     => _x( 'Write Posts in Markdown', 'Modules: Markdown', 'geditorial-admin' ),
			'icon'     => [ 'misc-32', 'markdown' ],
			'access'   => 'beta',
			'keywords' => [
				'has-public-api',
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
				'adminbar_tools',
				[
					'field'       => 'wiki_linking',
					'title'       => _x( 'Wiki Linking', 'Setting Title', 'geditorial-markdown' ),
					'description' => _x( 'Converts wiki like markup into internal links.', 'Setting Description', 'geditorial-markdown' ),
				],
				'restapi_restricted',
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

		if ( $this->get_setting( 'adminbar_tools' ) )
			$this->action( 'admin_bar_menu', 1, 1200 );
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

	public function setup_restapi()
	{
		$this->restapi_register_route( 'from', 'POST' );  // HTML to Markdown
		$this->restapi_register_route( 'to', 'POST' );    // Markdown to HTML
	}

	public function restapi_from_post_callback( $request )
	{
		$data = '';
		$type = $request->get_content_type();

		if ( in_array( $type['value'], [ 'text/html', 'text/plain', 'text/markdown' ], TRUE ) )
			$data = $request->get_body();

		else if ( in_array( $type['value'], [ 'application/json' ], TRUE ) )
			$data = $request['data'];

		else if ( WordPress\IsIt::dev() )
			self::_log( $type );

		$response = Services\Markup::markdownFromHTML( $data, TRUE );

		return rest_ensure_response( $response );
	}

	public function restapi_to_post_callback( $request )
	{
		$data = '';
		$type = $request->get_content_type();

		if ( in_array( $type['value'], [ 'text/html', 'text/plain', 'text/markdown' ], TRUE ) )
			$data = $request->get_body();

		else if ( in_array( $type['value'], [ 'application/json' ], TRUE ) )
			$data = $request['data'];

		else if ( WordPress\IsIt::dev() )
			self::_log( $type );

		$response = Services\Markup::mdExtra( $data, TRUE, FALSE );

		return rest_ensure_response( $response );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$node_id = $this->classs( 'toolbox' );
		$icon    = $this->adminbar__get_icon();
		$spinner = $this->adminbar__get_spinner();

		$wp_admin_bar->add_menu( [
			'id'    => $node_id,
			'href'  => $this->get_module_url(),
			'title' => $icon,
			'meta'  => [
				'class' => $this->adminbar__get_css_class( [], TRUE ),
				'title' => _x( 'Markdown Syntax', 'Node: Title', 'geditorial-markdown' ),
			],
		] );


		if ( ! $this->is_markdown( $post->ID ) ) {

			$wp_admin_bar->add_menu( [
				'parent' => $node_id,
				'id'     => $this->classs( 'pot' ),
				'title'  => _x( 'This content is written in HTML.', 'Node: Notice', 'geditorial-markdown' ),
				'meta'   => [
					'class' => $this->adminbar__get_css_class( '-notice-pot' ),
				],
			] );

			$wp_admin_bar->add_menu( [
				'parent' => $node_id,
				'id'     => $this->classs( 'convert' ),
				'title'  => $this->adminbar__get_icon( 'update-alt' ).
					_x( 'Convert Markdown to HTML', 'Node: Title', 'geditorial-markdown' ).$spinner,
				'meta'   => [
					'rel'   => 'convert',
					'class' => $this->adminbar__get_css_class( [ '-has-loading', '-do-action' ] ),
					'title' => _x( 'Tries to convert Markdown to HTML from raw content.', 'Node: Title', 'geditorial-markdown' ),
				],
			] );

		} else {

			$wp_admin_bar->add_menu( [
				'parent' => $node_id,
				'id'     => $this->classs( 'pot' ),
				'title'  => _x( 'This content is written in Markdown.', 'Node: Notice', 'geditorial-markdown' ),
				'meta'   => [
					'class' => $this->adminbar__get_css_class( '-notice-pot' ),
				],
			] );

			$wp_admin_bar->add_menu( [
				'parent' => $node_id,
				'id'     => $this->classs( 'cleanup' ),
				'href'   => '#', // better to be linked
				'title'  => $this->adminbar__get_icon( 'admin-appearance' ).
					_x( 'Clean-up Content', 'Node: Title', 'geditorial-markdown' ).$spinner,
				'meta'   => [
					'rel'   => 'cleanup',
					'class' => $this->adminbar__get_css_class( [ '-has-loading', '-do-action' ] ),
					'title' => _x( 'Tries to clean-up and convert from raw content.', 'Node: Title', 'geditorial-markdown' ),
				],
			] );

			$wp_admin_bar->add_menu( [
				'parent' => $node_id,
				'id'     => $this->classs( 'process' ),
				'href'   => '#', // better to be linked
				'title'  => $this->adminbar__get_icon( 'redo' ).
					_x( 'Process Content Again', 'Node: Title', 'geditorial-markdown' ).$spinner,
				'meta'   => [
					'rel'   => 'process',
					'class' => $this->adminbar__get_css_class( [ '-has-loading', '-do-action' ] ),
					'title' => _x( 'Tries to re-process Markdown to HTML from raw content.', 'Node: Title', 'geditorial-markdown' ),
				],
			] );

			$wp_admin_bar->add_menu( [
				'parent' => $node_id,
				'id'     => $this->classs( 'discard' ),
				'href'   => '#', // better to be linked
				'title'  => $this->adminbar__get_icon( 'undo' ).
					_x( 'Discard Converted HTML', 'Node: Title', 'geditorial-markdown' ).$spinner,
				'meta'   => [
					'rel'   => 'discard',
					'class' => $this->adminbar__get_css_class( [ '-has-loading', '-do-action', '-danger' ] ),
					'title' => _x( 'Tries to discard processed Markdown and restore raw content.', 'Node: Title', 'geditorial-markdown' ),
				],
			] );
		}

		$this->enqueue_asset_js( [
			'post_id' => $post->ID,
			'_nonce'  => wp_create_nonce( $this->hook( $post->ID ) ),
		], $this->dotted( 'adminbar' ) );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$node_id = $this->classs();
		$icon    = $this->adminbar__get_icon();
		$reports = $this->role_can_post( $post, 'reports' );

		if ( ! $this->is_markdown( $post->ID ) ) {

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'href'   => $reports ? $this->get_module_url( 'reports', NULL, [ 'id' => $post->ID ] ) : FALSE,
				'title'  => $icon._x( 'Written in HTML', 'Node: Notice', 'geditorial-markdown' ),
				'meta'   => [
					'class' => $this->adminbar__get_css_class( $reports ? [] : '-not-linked' ),
					'title' => sprintf(
						/* translators: `%s`: singular post-type label */
						_x( 'The content of this %s is written in conventional HTML.', 'Node: Title', 'geditorial-markdown' ),
						Services\CustomPostType::getLabel( $post, 'singular_name' )
					),
				],
			];

		} else {

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'title'  => $icon._x( 'Written in Markdown', 'Node: Notice', 'geditorial-markdown' ),
				'href'   => $reports ? $this->get_module_url( 'reports', NULL, [ 'id' => $post->ID ] ) : FALSE,
				'meta'   => [
					'class' => $this->adminbar__get_css_class( $reports ? [] : '-not-linked' ),
					'title' => sprintf(
						/* translators: `%s`: singular post-type label */
						_x( 'The content of this %s is written in Markdown syntax.', 'Node: Title', 'geditorial-markdown' ),
						Services\CustomPostType::getLabel( $post, 'singular_name' )
					),
				],
			];
		}
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
					Ajax::errorMessage( _x( 'Unable to process Markdown content into HTML!', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage( _x( 'Processed successfully. Reloading &hellip;', 'Message', 'geditorial-markdown' ) );
				break;

			case 'convert':

				if ( ! $this->convert_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to convert content into Markdown!', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage( _x( 'Converted successfully. Reloading &hellip;', 'Message', 'geditorial-markdown' ) );
				break;

			case 'cleanup':

				if ( ! $this->cleanup_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to cleanup Markdown content!', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage( _x( 'Cleaned-up successfully. Reloading &hellip;', 'Message', 'geditorial-markdown' ) );
				break;

			case 'discard':

				if ( ! $this->discard_post( $post['post_id'] ) )
					Ajax::errorMessage( _x( 'Unable to discard Markdown content back into HTML!', 'Message', 'geditorial-markdown' ) );

				Ajax::successMessage( _x( 'Discarded successfully. Reloading &hellip;', 'Message', 'geditorial-markdown' ) );
		}

		Ajax::errorWhat();
	}

	// @REF: https://github.com/michelf/php-markdown
	private function process_content( $content, $id )
	{
		// `$content` is slashed, but Markdown parser hates it precious.
		$content = stripslashes( $content );
		$content = Services\Markup::mdExtra( $content, TRUE, FALSE );

		if ( $this->get_setting( 'wiki_linking' ) )
			$content = $this->_do_wiki_linking( $content, WordPress\Post::get( $id ) );

		// Reference the `post_id` to make footnote ids unique
		$content = preg_replace( '/fn(ref)?:/', "fn$1-$id:", $content );
		$content = Core\Text::removeP( $content );

		// WordPress expects slashed data. Put needed ones back.
		return addslashes( $content );
	}

	private function convert_content( $content, $id )
	{
		return Services\Markup::markdownFromHTML( $content, TRUE );
	}

	// FIXME: do the cleanup!
	private function cleanup_content( $content, $id )
	{
		return $content;
	}

	// @REF: https://en.wikipedia.org/wiki/Help:Wiki_markup#Links_and_URLs
	private function _do_wiki_linking( $content, $post )
	{
		// $pattern = '/\[\[(.+?)\]\]/u';
		$pattern = '/\[\[(.*?)\]\]/u';

		return preg_replace_callback( $pattern,
			function ( $match ) use ( $content, $post ) {

				list( $text, $link, $slug, $post_id ) = $this->_make_the_link( $match[1], $post, $content );

				$html = '<a href="'.$link.'" data-slug="'.$slug.'" class="-wikilink'.( $post_id ? '' : ' -notfound' ).'">'.$text.'</a>';

				return $this->filters( 'linking', $html, $text, $link, $slug, $post_id, $match, $post, $content );
			}, $content );
	}

	// TODO: name-space with `:` for taxonomies
	public function _make_the_link( $text, $post, $content )
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

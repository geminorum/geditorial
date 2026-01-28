<?php namespace geminorum\gEditorial\Modules\Shortcodes;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Shortcodes extends gEditorial\Module
{
	protected $priority_adminbar_init = 20;

	public static function module()
	{
		return [
			'name'     => 'shortcodes',
			'title'    => _x( 'Shortcodes', 'Modules: Shortcodes', 'geditorial-admin' ),
			'desc'     => _x( 'Shortcode Enhancements', 'Modules: Shortcodes', 'geditorial-admin' ),
			'icon'     => 'media-code',
			'access'   => 'stable',
			'keywords' => [
				'shortcode',
				'has-adminbar',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'          => 'shortcodes',
					'title'          => _x( 'Shortcodes', 'Setting Title', 'geditorial-shortcodes' ),
					'description'    => _x( 'Enables the use of the selected short-codes.', 'Setting Description', 'geditorial-shortcodes' ),
					'type'           => 'checkboxes-values',
					'values'         => $this->_list_shortcodes(),
					'template_value' => '[%s]',
				],
			],
			'_frontend' => [
				'adminbar_summary',
				[
					'field'       => 'remove_empty_p_tags',
					'title'       => _x( 'Remove Empty Paragraphs', 'Setting Title', 'geditorial-shortcodes' ),
					'description' => _x( 'Strips empty paragraph tags around short-codes from post content.', 'Setting Description', 'geditorial-shortcodes' ),
				],
				[
					'field'       => 'remove_orphaned',
					'title'       => _x( 'Remove Orphaned', 'Setting Title', 'geditorial-shortcodes' ),
					'description' => _x( 'Strips unregistered shortcode tags from post content.', 'Setting Description', 'geditorial-shortcodes' ),
				],
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	private function _list_shortcodes()
	{
		return [
			'display-terms'   => _x( 'Display Terms', 'Shortcode Name', 'geditorial-shortcodes' ),
			'term-tiles'      => _x( 'Term Tiles', 'Shortcode Name', 'geditorial-shortcodes' ),
			'posts-assigned'  => _x( 'Posts Assigned', 'Shortcode Name', 'geditorial-shortcodes' ),
			'circle-progress' => _x( 'Circle Progress', 'Shortcode Name', 'geditorial-shortcodes' ),
		];
	}

	protected function get_global_constants()
	{
		return [
			'display_terms_shortcode'   => 'display-terms',
			'term_tiles_shortcode'      => 'term-tiles',
			'posts_assigned_shortcode'  => 'posts-assigned',
			'circle_progress_shortcode' => 'circle-progress',
		];
	}

	public function init()
	{
		parent::init();

		foreach ( $this->get_setting( 'shortcodes', [] ) as $shortcode )
			$this->register_shortcode( sprintf( '%s_shortcode', Core\Text::sanitizeHook( $shortcode ) ), NULL, TRUE );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'remove_empty_p_tags' ) )
			$this->filter( 'the_content' );

		if ( $this->get_setting( 'remove_orphaned' ) )
			$this->filter( 'the_content', 1, 8, 'orphaned' );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$pattern = get_shortcode_regex();

		if ( ! preg_match_all( '/'.$pattern.'/s', $post->post_content, $matches ) )
			return;

		$node_id = $this->classs();
		$icon    = $this->adminbar__get_icon();
		$reports = $this->role_can( 'reports' );
		$niches  = [];

		$nodes[] = [
			'parent' => $parent,
			'id'     => $node_id,
			'title'  => $icon._x( 'Short-codes', 'Node: Title', 'geditorial-shortcodes' ),
			'href'   => $reports ? $this->get_module_url( 'reports' ) : FALSE,
			'meta'   => [
				'class' => $this->adminbar__get_css_class(),
				'title' => $reports ? sprintf(
					/* translators: `%s`: singular post-type label */
					_x( 'View Short-code Reports for this %s', 'Node: Title', 'geditorial-shortcodes' ),
					Services\CustomPostType::getLabel( $post, 'singular_name' )
				) : '',
			],
		];

		foreach ( $matches[0] as $offset => $matched ) {

			if ( ! $shortcode = $matches[2][$offset] )
				continue;

			if ( ! array_key_exists( $shortcode, $niches ) ) {

				$niches[$shortcode] = $this->classs( 'shortcode', $shortcode );

				$nodes[] = [
					'parent' => $node_id,
					'id'     => $niches[$shortcode],
					'title'  => $shortcode,
					'href'   => FALSE,
					'meta'   => [
						'dir'   => 'ltr',
						'rel'   => $shortcode,
						'class' => $this->adminbar__get_css_class( '-not-linked' ),
					],
				];
			}


			$nodes[] = [
				'parent' => $niches[$shortcode],
				'id'     => $this->classs( 'matched', $offset ),
				'title'  => WordPress\Strings::trimChars( Core\Text::stripTags( $matched ), 125 ),
				'href'   => FALSE,
				'meta'   => [
					'dir'   => 'ltr',
					'rel'   => $shortcode,
					'class' => $this->adminbar__get_css_class( '-not-linked' ),
				],
			];

		}
	}

	public function reports_settings( $sub )
	{
		$this->check_settings( $sub, 'reports', 'per_page' );
	}

	protected function render_reports_html( $uri, $sub )
	{
		$list      = $this->list_posttypes();
		$query     = $extra = [];
		$shortcode = self::req( 'shortcode', 'none' );

		if ( 'none' != $shortcode ) {
			$query['s'] = '['.$shortcode;
			$extra['shortcode'] = $shortcode;
		}

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, array_keys( $list ), $this->get_sub_limit_option( $sub, 'reports' ) );

		$pagination['before'][] = Core\HTML::dropdown(
			$this->get_shortcode_list(), [
				'name'       => 'shortcode',
				'selected'   => self::req( 'shortcode', 'none' ),
				'none_value' => 'none',
				'none_title' => _x( 'All Shortcodes', 'None Title', 'geditorial-shortcodes' ),
			] );

		$pagination['before'][] = Tablelist::filterPostTypes( $list );
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		return Core\HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Tablelist::columnPostID(),
			'date'  => Tablelist::columnPostDate(),
			'type'  => Tablelist::columnPostType(),
			'title' => Tablelist::columnPostTitle(),
			'shortcodes' => [
				'title'    => _x( 'Shortcodes', 'Table Column', 'geditorial-shortcodes' ),
				'args'     => [ 'regex' => get_shortcode_regex() ],
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					$html = '<div dir="ltr">';

					if ( ! preg_match_all( '/'.$column['args']['regex'].'/', $row->post_content, $matches ) )
						return $html.'&mdash;</div>';

					foreach ( $matches[0] as $offset => $shortcode )
						$html.= Core\HTML::wrap( Core\HTML::code( $matches[2][$offset] ).' '.WordPress\Strings::trimChars( $shortcode, 145 ) );

					return $html.'</div>';
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Post Shortcodes', 'Header', 'geditorial-shortcodes' ) ),
			'empty'      => Services\CustomPostType::getLabel( 'post', 'not_found' ),
			'pagination' => $pagination,
		] );
	}

	protected function get_shortcode_list()
	{
		global $shortcode_tags;

		$list = [];

		foreach ( $shortcode_tags as $shortcode => $callback )
			$list[$shortcode] = $shortcode; // sprintf( '[%s]', $shortcode ); // for search

		return $list;
	}

	// TODO: add table action
	protected function remove_shortcode( $post_id, $shortcode )
	{
		if ( ! $post = WordPress\Post::get( $post_id ) )
			return FALSE;

		$pattern = '#\['.$shortcode.'[^\]]*\]#i';

		if ( ! preg_match_all( $pattern, $post->post_content, $matches ) )
			return FALSE;

		return wp_update_post( [
			'ID'           => $post->ID,
			'post_content' => preg_replace( $pattern, '', $post->post_content ),
		] );
	}

	// @REF: https://gist.github.com/wpscholar/8969bb6e1cedb9be92140cc2efa9febb
	public function the_content( $content )
	{
		return strtr( $content, [
			'<p>['    => '[',
			']</p>'   => ']',
			']<br />' => ']',
		] );
	}

	/**
	 * Strips Orphan Short-codes
	 * Author: Meks - v1.2
	 *
	 * @source https://wordpress.org/plugins/remove-orphan-shortcodes/
	 *
	 * @param string $content
	 * @return string
	 */
	public function the_content_orphaned( $content )
	{
		global $shortcode_tags;

		if ( ! Core\Text::has( $content, '[' ) )
			return $content;

		// Check for active short-codes
		$active_shortcodes = ( is_array( $shortcode_tags ) && ! empty( $shortcode_tags ) )
			? array_keys( $shortcode_tags ) : [];

		// Avoid `/` chars in content breaks `preg_replace`
		$hack1   = md5( microtime( TRUE ) );
		$content = str_replace( '[/', $hack1, $content );
		$hack2   = md5( microtime( TRUE ) + 1 );
		$content = str_replace( '/', $hack2, $content );
		$content = str_replace( $hack1, '[/', $content );

		if ( ! empty( $active_shortcodes ) ) {

			// Be sure to keep active short-codes
			$keep_active = implode( '|', $active_shortcodes );
			$content     = preg_replace( "~(?:\[/?)(?!(?:$keep_active))[^/\]]+/?\]~s", '', $content );

		} else {

			// Strip all short-codes
			$content = preg_replace( "~(?:\[/?)[^/\]]+/?\]~s", '', $content );
		}

		// Set `/` back to its place
		return str_replace( $hack2, "/", $content );
	}

	// @SEE: https://github.com/seothemes/display-terms-shortcode/blob/master/display-terms-shortcode.php
	public function display_terms_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listTerms( 'allitems',
			'',
			array_merge( [
				'title' => FALSE,
			], (array) $atts ),
			$content,
			$this->constant( 'display_terms_shortcode', $tag ),
			$this->key
		);
	}

	public function term_tiles_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listTerms( 'allitems',
			'',
			array_merge( [
				'item_image_tile' => TRUE,
				'item_title'      => '%s',   // `%s` for term title
			], (array) $atts ),
			$content,
			$this->constant( 'term_tiles_shortcode', $tag ),
			$this->key
		);
	}

	public function posts_assigned_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'assigned',
			'',
			'',
			$atts,
			$content,
			$this->constant( 'posts_assigned_shortcode', $tag ),
			$this->key
		);
	}

	public function circle_progress_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'completed' => FALSE,
			'total'     => FALSE,
			'hint'      => FALSE,
			'template'  => NULL,
			'context'   => NULL,
			'wrap'      => TRUE,
			'class'     => '',
			'before'    => '',
			'after'     => '',
		], $atts, $tag ?: $this->constant( 'circle_progress_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $args['completed'] || ! $args['total'] )
			return $content;

		$html = self::buffer( [ 'geminorum\\gEditorial\\Services\\Markup', 'renderCircleProgress' ], [
			$args['completed'],
			$args['total'],
			$args['hint'] ?: FALSE,
			$args['template'] ?: NULL
		] );

		return gEditorial\ShortCode::wrap(
			$html,
			$this->constant( 'circle_progress_shortcode' ),
			$args,
			FALSE
		);
	}
}

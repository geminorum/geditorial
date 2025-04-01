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

	public static function module()
	{
		return [
			'name'   => 'shortcodes',
			'title'  => _x( 'Shortcodes', 'Modules: Shortcodes', 'geditorial-admin' ),
			'desc'   => _x( 'Shortcode Tools', 'Modules: Shortcodes', 'geditorial-admin' ),
			'icon'   => 'media-code',
			'access' => 'stable',
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
			'display-terms' => _x( 'Display Terms', 'Shortcode Name', 'geditorial-shortcodes' ),
			'term-tiles'    => _x( 'Term Tiles', 'Shortcode Name', 'geditorial-shortcodes' ),
		];
	}

	protected function get_global_constants()
	{
		return [
			'display_terms_shortcode' => 'display-terms',
			'term_tiles_shortcode'    => 'term-tiles',
		];
	}

	public function init()
	{
		parent::init();

		foreach ( $this->get_setting( 'shortcodes', [] ) as $shortcode )
			$this->register_shortcode( sprintf( '%s_shortcode', $this->sanitize_hook( $shortcode ) ), NULL, TRUE );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'remove_empty_p_tags' ) )
			$this->filter( 'the_content' );

		if ( $this->get_setting( 'remove_orphaned' ) )
			$this->filter( 'the_content', 1, 8, 'orphaned' );
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		if ( ! $post = get_queried_object() )
			return;

		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$pattern = get_shortcode_regex();

		if ( ! preg_match_all( '/'.$pattern.'/s', $post->post_content, $matches ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Shortcodes', 'Title Attr', 'geditorial-shortcodes' ),
			'parent' => $parent,
			'href'   => $this->get_module_url(),
		];

		// TODO: niche for each short-code tag
		foreach ( $matches[0] as $offset => $shortcode )
			$nodes[] = [
				'id'     => $this->classs( 'shortcode', $offset ),
				'title'  => '<span dir="ltr">'.$matches[2][$offset].': '.WordPress\Strings::trimChars( strip_tags( $shortcode ), 125 ).'</span>',
				'parent' => $this->classs(),
				'href'   => FALSE,
			];
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

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, array_keys( $list ), $this->get_sub_limit_option( $sub ) );

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
	 * @return string $content
	 */
	public function the_content_orphaned( $content )
	{
		global $shortcode_tags;

		if ( ! Core\Text::has( $content, '[' ) )
			return $content;

		// Check for active short-codes
		$active_shortcodes = ( is_array( $shortcode_tags ) && ! empty( $shortcode_tags ) )
			? array_keys( $shortcode_tags ) : [];

		// Avoid "/" chars in content breaks `preg_replace`
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

		// Set "/" back to its place
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
			], (array) $atts ),
			$content,
			$this->constant( 'term_tiles_shortcode', $tag ),
			$this->key
		);
	}
}

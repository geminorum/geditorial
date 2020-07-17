<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;

class Shortcodes extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;
	protected $textdomain_frontend  = FALSE;

	public static function module()
	{
		return [
			'name'  => 'shortcodes',
			'title' => _x( 'Shortcodes', 'Modules: Shortcodes', 'geditorial' ),
			'desc'  => _x( 'Shortcode Tools', 'Modules: Shortcodes', 'geditorial' ),
			'icon'  => 'media-code',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'adminbar_summary',
			],
			'posttypes_option' => 'posttypes_option',
		];
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

		foreach ( $matches[0] as $offset => $shortcode )
			$nodes[] = [
				'id'     => $this->classs( 'shortcode', $offset ),
				'title'  => '<span dir="ltr">'.$matches[2][$offset].': '.Helper::trimChars( strip_tags( $shortcode ), 125 ).'</span>',
				'parent' => $this->classs(),
				'href'   => FALSE,
			];
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) )
			$this->screen_option( $sub );
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

		list( $posts, $pagination ) = $this->getTablePosts( $query, $extra );

		$pagination['before'][] = HTML::dropdown(
			$this->get_shortcode_list(), [
				'name'       => 'shortcode',
				'selected'   => self::req( 'shortcode', 'none' ),
				'none_value' => 'none',
				'none_title' => _x( 'All Shortcodes', 'None Title', 'geditorial-shortcodes' ),
			] );

		$pagination['before'][] = Helper::tableFilterPostTypes( $list );
		$pagination['before'][] = Helper::tableFilterAuthors( $list );
		$pagination['before'][] = Helper::tableFilterSearch( $list );

		return HTML::tableList( [
			'_cb'   => 'ID',
			'ID'    => Helper::tableColumnPostID(),
			'date'  => Helper::tableColumnPostDate(),
			'type'  => Helper::tableColumnPostType(),
			'title' => Helper::tableColumnPostTitle(),
			'shortcodes' => [
				'title'    => _x( 'Shortcodes', 'Table Column', 'geditorial-shortcodes' ),
				'args'     => [ 'regex' => get_shortcode_regex() ],
				'callback' => function( $value, $row, $column, $index ){

					$html = '<div dir="ltr">';

					if ( ! preg_match_all( '/'.$column['args']['regex'].'/', $row->post_content, $matches ) )
						return $html.'&mdash;</div>';

					foreach ( $matches[0] as $offset => $shortcode )
						$html.= HTML::wrap( '<code>'.$matches[2][$offset].'</code> '.Helper::trimChars( $shortcode, 145 ) );

					return $html.'</div>';
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Post Shortcodes', 'Header', 'geditorial-shortcodes' ) ),
			'empty'      => $this->get_posttype_label( 'post', 'not_found' ),
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

	// FIXME: add table action
	protected function remove_shortcode( $post_id, $shortcode )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		$pattern = '#\['.$shortcode.'[^\]]*\]#i';

		if ( ! preg_match_all( $pattern, $post->post_content, $matches ) )
			return FALSE;

		return wp_update_post( [
			'ID'           => $post->ID,
			'post_content' => preg_replace( $pattern, '', $post->post_content ),
		] );
	}
}

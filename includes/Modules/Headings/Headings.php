<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;

class Headings extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	private $anchors  = [];
	private $toc      = [];

	public static function module()
	{
		return [
			'name'  => 'headings',
			'title' => _x( 'Headings', 'Modules: Headings', 'geditorial' ),
			'desc'  => _x( 'Table of Contents', 'Modules: Headings', 'geditorial' ),
			'icon'  => 'tablet',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				[
					'field'       => 'toc_title',
					'type'        => 'text',
					'title'       => _x( 'ToC Title', 'Modules: Headings: Setting Title', 'geditorial' ),
					'description' => _x( 'Default text on the ToC box', 'Modules: Headings: Setting Description', 'geditorial' ),
					'default'     => _x( 'Table of Contents', 'Modules: Headings: Setting Default', 'geditorial' ),
				],
				[
					'field'       => 'anchor_title',
					'type'        => 'text',
					'title'       => _x( 'Anchor Title', 'Modules: Headings: Setting Title', 'geditorial' ),
					'description' => _x( 'Default text on the anchor link', 'Modules: Headings: Setting Description', 'geditorial' ),
					'default'     => _x( 'Permalink to this title', 'Modules: Headings: Setting Default', 'geditorial' ),
				],
				[
					'field'       => 'min_headings',
					'type'        => 'number',
					'title'       => _x( 'Minimum Headings', 'Modules: Headings: Setting Title', 'geditorial' ),
					'description' => _x( 'Threshold to Display ToC', 'Modules: Headings: Setting Description', 'geditorial' ),
					'default'     => '2',
				],
				'insert_content',
				'insert_priority',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->filter( 'the_content' );

		if ( $this->hook_insert_content( -25 ) )
			$this->enqueue_styles(); // widget must add this itself!
	}

	public function the_content( $content )
	{
		if ( ! $content )
			return $content;

		if ( ! $this->is_content_insert( FALSE ) )
			return $content;

		// @SOURCE: https://wordpress.org/plugins/add-ids-to-header-tags/
		// $pattern = '#(?P<full_tag><(?P<tag_name>h\d)(?P<tag_extra>[^>]*)>(?P<tag_contents>[^<]*)</h\d>)#';
		$pattern = "/<h([0-9])(.*?)>(.*?)<\/h([0-9])>/imu";

		return preg_replace_callback( $pattern, [ $this, 'toc_callback' ], $content );
	}

	public function toc_callback( $match )
	{
		$title = trim( $match[3] );

		if ( ! $title )
			return $match[0];

		if ( $match[2] )
			$atts = HTML::parseAtts( $match[2], [ 'id' => '', 'class' => '' ] );

		if ( ! empty( $atts['class'] )
			&& in_array( 'numeral-section-title', HTML::attrClass( $atts['class'] ) ) )
				return $match[0];

		if ( ! empty( $atts['id'] ) ) {
			$slug = $atts['id'];

		} else {
			$slug = $temp = sanitize_title( $title );

			$i = 2;
			while ( FALSE !== in_array( $slug, $this->anchors ) )
				$slug = sprintf( '%s-%d', $temp, $i++ );
		}

		$this->anchors[] = $slug;

		$this->toc[] = [
			'slug'  => $slug,
			'title' => $title,
			'niche' => $match[1],
			'page'  => $GLOBALS['page'],
		];

		$html = HTML::tag( 'a', [
			'href'  => '#'.$slug,
			'class' => 'anchor-link anchorlink dashicons-before',
			'title' => $this->get_setting( 'anchor_title', '' ),
		], NULL );

		$html = HTML::tag( 'h'.$match[1], [
			'id'    => $slug,
			'class' => 'anchor-title',
		], $title.$html );

		return $html;
	}

	public function insert_content( $content )
	{
		if ( empty( $this->toc ) )
			return;

		if ( ! $this->is_content_insert( FALSE ) )
			return;

		$this->render_headings( NULL, '-before' );
	}

	public function render_headings( $title = NULL, $class = '' )
	{
		if ( count( $this->toc ) < $this->get_setting( 'min_headings', '2' ) )
			return;

		if ( is_null( $title ) )
			$title = $this->get_setting( 'toc_title', '' );

		$tree = [];
		$last = FALSE;

		foreach ( $this->toc as $heading ) {

			if ( $GLOBALS['page'] == $heading['page'] || 1 == $heading['page'] )
				$heading['page'] = FALSE;

			if ( ! $last || $heading['niche'] <= $last['niche'] ) {
				$tree[] = $heading;
				$last = $heading;
			} else {
				$keys = array_keys( $tree );
				$key = end( $keys );

				$tree[$key]['children'][] = $heading;
				$last = $tree[$key];
			}
		}

		echo $this->wrap_open( '-toc-box '.$class );

			HTML::h3( $title, '-toc-title' );

			HTML::menu( $tree, function( $item ) {

				if ( FALSE === $item['page'] )
					return HTML::link( $item['title'], '#'.$item['slug'] );

				return rtrim( _wp_link_page( $item['page'] ), '">' )
					.'#'.$item['slug'].'">'.$item['title'].'</a>';
			} );

		echo '</div>';
	}
}

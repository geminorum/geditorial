<?php namespace geminorum\gEditorial\Modules\Headings;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Headings extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	private $anchors  = [];
	private $toc      = [];

	public static function module()
	{
		return [
			'name'     => 'headings',
			'title'    => _x( 'Headings', 'Modules: Headings', 'geditorial-admin' ),
			'desc'     => _x( 'Table of Contents', 'Modules: Headings', 'geditorial-admin' ),
			'icon'     => 'tablet',
			'i18n'     => 'adminonly',
			'access'   => 'stable',
			'keywords' => [
				'toc',
				'table',
			],
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option' => 'posttypes_option',
			'_general'         => [
				[
					'field'       => 'toc_title',
					'type'        => 'text',
					'title'       => _x( 'ToC Title', 'Setting Title', 'geditorial-headings' ),
					'description' => _x( 'Default text on the ToC box', 'Setting Description', 'geditorial-headings' ),
					'default'     => _x( 'Table of Contents', 'Setting Default', 'geditorial-headings' ),
				],
				[
					'field'       => 'anchor_title',
					'type'        => 'text',
					'title'       => _x( 'Anchor Title', 'Setting Title', 'geditorial-headings' ),
					'description' => _x( 'Default text on the anchor link', 'Setting Description', 'geditorial-headings' ),
					'default'     => _x( 'Permalink to this title', 'Setting Default', 'geditorial-headings' ),
				],
				[
					'field'       => 'min_headings',
					'type'        => 'number',
					'title'       => _x( 'Minimum Headings', 'Setting Title', 'geditorial-headings' ),
					'description' => _x( 'Threshold to Display ToC', 'Setting Description', 'geditorial-headings' ),
					'default'     => '2',
				],
				'insert_content',
				'insert_priority',
			],
		];
	}

	public function template_redirect()
	{
		if ( ! is_singular( $this->posttypes() ) )
			return;

		$this->filter( 'the_content' );

		if ( $this->hook_content_insert( -25 ) )
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
		$title = Core\Text::stripTags( $match[3] );

		if ( ! $title )
			return $match[0];

		if ( $match[2] )
			$atts = Core\HTML::parseAtts( $match[2], [ 'id' => '', 'class' => '' ] );

		if ( ! empty( $atts['class'] )
			&& in_array( 'numeral-section-title', Core\HTML::attrClass( $atts['class'] ) ) )
				return $match[0];

		if ( ! empty( $atts['id'] ) ) {

			$slug = $atts['id'];

		} else {

			$slug = $temp = sanitize_title( $title );
			$i    = 2;

			while ( FALSE !== in_array( $slug, $this->anchors, TRUE ) )
				$slug = sprintf( '%s-%d', $temp, $i++ );
		}

		$this->anchors[] = $slug;

		$this->toc[] = [
			'slug'  => $slug,
			'title' => $title,
			'niche' => $match[1],
			'page'  => $GLOBALS['page'],
		];

		$html = Core\HTML::tag( 'a', [
			'href'  => '#'.$slug,
			'class' => 'anchor-link anchorlink dashicons-before',
			'title' => $this->get_setting( 'anchor_title', FALSE ),
		], NULL );

		$html = Core\HTML::tag( 'h'.$match[1], [
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
		$toc = $this->filters( 'toc', $this->toc );

		if ( count( $toc ) < $this->get_setting( 'min_headings', '2' ) )
			return;

		$tree = [];
		$last = FALSE;

		foreach ( $toc as $heading ) {

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

		$this->toc = []; // reset to avoid double rendering

		echo $this->wrap_open( '-toc-box '.$class );

			Core\HTML::h3( $title ?? $this->get_setting( 'toc_title', '' ), '-toc-title' );

			Core\HTML::menu( $tree, static function ( $item ) {

				if ( FALSE === $item['page'] )
					return Core\HTML::link( $item['title'], '#'.$item['slug'] );

				return rtrim( _wp_link_page( $item['page'] ), '">' )
					.'#'.$item['slug'].'">'.$item['title'].'</a>';
			}, 'ul', 'children', '-headings-toc' );

		echo '</div>';
	}
}

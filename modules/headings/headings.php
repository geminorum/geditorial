<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

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
			'title' => _x( 'Headings', 'Modules: Headings', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Table of Contents', 'Modules: Headings', GEDITORIAL_TEXTDOMAIN ),
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
					'title'       => _x( 'ToC Title', 'Modules: Headings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Default text on the ToC box', 'Modules: Headings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Table of Contents', 'Modules: Headings: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'anchor_title',
					'type'        => 'text',
					'title'       => _x( 'Anchor Title', 'Modules: Headings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Default text on the anchor link', 'Modules: Headings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Permalink to this title', 'Modules: Headings: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'min_headings',
					'type'        => 'number',
					'title'       => _x( 'Minimum Headings', 'Modules: Headings: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Threshold to Display ToC', 'Modules: Headings: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '2',
				],
				'insert_content_before',
				'insert_priority',
			],
			'posttypes_option' => 'posttypes_option',
		];
	}

	public function init()
	{
		parent::init();

		if ( is_admin() )
			return;

		$this->filter( 'the_content' );

		if ( $this->get_setting( 'insert_content_before', FALSE ) )
			add_action( 'gnetwork_themes_content_before', [ $this, 'content_before' ],
				$this->get_setting( 'insert_priority', -25 ) );

		$this->enqueue_styles();
	}

	public function the_content( $content )
	{
		if ( ! is_singular( $this->post_types() ) || '' == $content )
			return $content;

		// FIXME: temp: skip on paginated posts
		global $pages;
		if ( 1 != count( $pages ) )
			return $content;

		// @SOURCE: [Add IDs to Header Tags](https://wordpress.org/plugins/add-ids-to-header-tags/)
		// $pattern = '#(?P<full_tag><(?P<tag_name>h\d)(?P<tag_extra>[^>]*)>(?P<tag_contents>[^<]*)</h\d>)#';

		$pattern = "/<h([0-9])(.*?)>(.*?)<\/h([0-9])>/imu";
		return preg_replace_callback( $pattern, [ $this, 'toc_callback' ], $content );
	}

	public function toc_callback( $match )
	{
		global $page;

		$title = trim( $match[3] );

		if ( ! $title )
			return $match[0];

		if ( $match[2] )
			$atts = HTML::getAtts( $match[2], [ 'id' => '' ] );

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
			'page'  => $page,
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

	public function content_before( $content, $posttypes = NULL )
	{
		if ( empty( $this->toc ) )
			return;

		if ( ! $this->is_content_insert( NULL ) )
			return;

		$this->render_headings( NULL, '-content-before' );
	}

	public function render_headings( $title = NULL, $class = '' )
	{
		global $page;

		if ( count( $this->toc ) < $this->get_setting( 'min_headings', '2' ) )
			return;

		if ( is_null( $title ) )
			$title = $this->get_setting( 'toc_title', '' );

		$tree = [];
		$last = FALSE;

		foreach ( $this->toc as $heading ) {

			if ( $page == $heading['page'] || 1 == $heading['page'] )
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

			if ( $title )
				HTML::h3( $title, '-toc-title' );

			HTML::menu( $tree, function(){
				if ( FALSE === $item['page'] )
					return HTML::link( $item['title'], '#'.$item['slug'] );
				return rtrim( _wp_link_page( $item['page'] ), '">').'#'.$item['slug'].'">'.$item['title'].'</a>';
			} );

		echo '</div>';
	}
}

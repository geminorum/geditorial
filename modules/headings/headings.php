<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialHeadings extends gEditorialModuleCore
{

	private $anchors  = array();
	private $toc      = array();

	public static function module()
	{
		return array(
			'name'  => 'headings',
			'title' => _x( 'Headings', 'Headings Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Table of Contents', 'Headings Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'tablet',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'toc_title',
					'type'        => 'text',
					'title'       => _x( 'ToC Title', 'Headings Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Default text on the ToC box', 'Headings Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Table of Contents', 'Headings Module: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'anchor_title',
					'type'        => 'text',
					'title'       => _x( 'Anchor Title', 'Headings Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Default text on the anchor link', 'Headings Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Permalink to this title', 'Headings Module: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'min_headings',
					'type'        => 'number',
					'title'       => _x( 'Minimum Headings', 'Headings Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Threshold to Display ToC', 'Headings Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '2',
				),
				'insert_content_before',
				'insert_priority',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	public function init()
	{
		do_action( 'geditorial_headings_init', $this->module );
		$this->do_globals();

		if ( ! is_admin() && count( $this->post_types() ) ) {

			add_filter( 'the_content', array( $this, 'the_content' ) );

			if ( $this->get_setting( 'insert_content_before', FALSE ) )
				add_action( 'gnetwork_themes_content_before', array( $this, 'content_before' ),
					$this->get_setting( 'insert_priority', -25 ) );

			$this->enqueue_styles();
		}
	}

	public function the_content( $content )
	{
		if ( ! is_singular( $this->post_types() ) || '' == $content )
			return $content;

		// FIXME: temp: skip on paginated posts
		global $pages;
		if ( 1 != count( $pages ) )
			return $content;

		$pattern = "/<h([0-9])(.*?)>(.*?)<\/h([0-9])>/imu";
		return preg_replace_callback( $pattern, array( $this, 'toc_callback' ), $content );
	}

	public function toc_callback( $match )
	{
		global $page;

		$title = trim( $match[3] );

		if ( ! $title )
			return $match[0];

		if ( $match[2] )
			$atts = gEditorialHTML::getAtts( $match[2], array( 'id' => '' ) );

		if ( ! empty( $atts['id'] ) ) {
			$slug = $atts['id'];

		} else {
			$slug = $temp = sanitize_title( $title );

			$i = 2;
			while ( FALSE !== in_array( $slug, $this->anchors ) )
				$slug = sprintf( '%s-%d', $temp, $i++ );
		}

		$this->anchors[] = $slug;

		$this->toc[] = array(
			'slug'  => $slug,
			'title' => $title,
			'niche' => $match[1],
			'page'  => $page,
		);

		$html = gEditorialHTML::tag( 'a', array(
			'href'  => '#'.$slug,
			'class' => 'anchor-link anchorlink dashicons-before',
			'title' => $this->get_setting( 'anchor_title', '' ),
		), NULL );

		$html = gEditorialHTML::tag( 'h'.$match[1], array(
			'id'    => $slug,
			'class' => 'anchor-title',
		), $title.$html );

		return $html;
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( ! count( $this->toc ) )
			return;

		if ( is_singular( $this->post_types() )
			&& in_the_loop() && is_main_query() )
				$this->render_headings( NULL, '-content-before' );
	}

	public function render_headings( $title = NULL, $class = '' )
	{
		global $page;

		if ( count( $this->toc ) < $this->get_setting( 'min_headings', '2' ) )
			return;

		if ( is_null( $title ) )
			$title = $this->get_setting( 'toc_title', '' );

		$tree = array();
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

		echo '<div class="geditorial-wrap -headings -toc-box '.$class.'">';

			if ( $title )
				echo gEditorialHTML::tag( 'h3', array( 'class' => '-toc-title' ), $title );

			gEditorialHTML::menu( $tree, function(){
				if ( FALSE === $item['page'] )
					return gEditorialHTML::tag( 'a', array( 'href' => '#'.$item['slug'] ), $item['title'] );
				return rtrim( _wp_link_page( $item['page'] ), '">').'#'.$item['slug'].'">'.$item['title'].'</a>';
			} );

		echo '</div>';
	}
}

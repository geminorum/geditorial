<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\Theme;

class Archives extends gEditorial\Module
{

	protected $priority_init = 99; // after all taxonomies registered

	public static function module()
	{
		return [
			'name'  => 'archives',
			'title' => _x( 'Archives', 'Modules: Archives', 'geditorial' ),
			'desc'  => _x( 'Content Archives Pages', 'Modules: Archives', 'geditorial' ),
			'icon'  => 'editor-ul',
		];
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_content' => [
				'display_searchform' => _x( 'Prepends a search form to the posttype archive pages.', 'Setting Description', 'geditorial-archives' ),
				[
					'field'       => 'posttype_content',
					'type'        => 'text',
					'title'       => _x( 'Posttype Content', 'Setting Title', 'geditorial-archives' ),
					'description' => _x( 'Used as default content on the posttype archive pages.', 'Setting Description', 'geditorial-archives' ),
					'default'     => '[alphabet-posts post_type="%s" /]', // FIXME: provide for fallback shortcode
					'dir'         => 'ltr',
				],
				[
					'field'       => 'taxonomy_content',
					'type'        => 'text',
					'title'       => _x( 'Taxonomy Content', 'Setting Title', 'geditorial-archives' ),
					'description' => _x( 'Used as default content on the taxonomy archive pages.', 'Setting Description', 'geditorial-archives' ),
					'default'     => '[alphabet-terms taxonomy="%s" /]', // FIXME: provide for fallback shortcode
					'dir'         => 'ltr',
				],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'taxonomy_query' => 'taxonomy_archives',
		];
	}

	public function init()
	{
		parent::init();

		$this->do_add_rewrite_rules();
		$this->filter( 'query_vars' );

		$this->filter_module( 'countables', 'taxonomy_countbox_tokens', 4, 9 );
	}

	private function do_add_rewrite_rules()
	{
		foreach ( $this->taxonomies() as $taxonomy )
			if ( $slug = $this->taxonomy_archive_slug( $taxonomy ) )
				add_rewrite_rule( $slug.'/?$', sprintf( 'index.php?%s=%s', $this->constant( 'taxonomy_query' ), $taxonomy ), 'top' );
	}

	private function taxonomy_archive_slug( $taxonomy )
	{
		if ( ! $object = Taxonomy::object( $taxonomy ) )
			return FALSE;

		if ( ! empty( $object->rest_base ) )
			return $object->rest_base;

		if ( ! empty( $object->rewrite['slug'] ) )
			return $object->rewrite['slug'];

		return $taxonomy;
	}

	public function query_vars( $vars )
	{
		$vars[] = $this->constant( 'taxonomy_query' );

		return $vars;
	}

	public function template_include( $template )
	{
		if ( $taxonomy = get_query_var( $this->constant( 'taxonomy_query' ) ) ) {

			$this->current = $taxonomy;

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->taxonomy_archive_title( $taxonomy ),
				'post_type'  => 'page',
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], [ $this, 'template_taxonomy_archives' ] );

			$this->template_include_extra( [ 'taxonomy-archives', 'taxonomy-archives-'.$taxonomy ] );

			return get_page_template();

		} else if ( is_embed() || is_search() || ! ( $posttype = $GLOBALS['wp_query']->get( 'post_type' ) ) ) {

			return $template;
		}

		if ( $this->posttype_supported( $posttype ) && is_post_type_archive( $posttype ) ) {

			$this->current = $posttype;

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_archive_title( $posttype ),
				'post_type'  => $posttype,
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], [ $this, 'template_archive_content' ] );

			$this->template_include_extra( 'archive-entry' );
			$this->filter( 'post_type_archive_title', 2 );
			$this->filter( 'gtheme_navigation_crumb_archive', 2 );

			// return get_single_template();
			return get_page_template();
		}

		return $template;
	}

	private function template_include_extra( $classes )
	{
		$this->filter_append( 'post_class', $classes );

		$this->filter_empty_string( 'previous_post_link' );
		$this->filter_empty_string( 'next_post_link' );

		$this->enqueue_styles();

		defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' )
			or define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );

		defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			or define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );
	}

	public function post_type_archive_title( $name, $posttype )
	{
		return $this->template_get_archive_title( $posttype );
	}

	public function gtheme_navigation_crumb_archive( $crumb, $args )
	{
		return $this->template_get_archive_title( $this->current );
	}

	public function template_get_archive_title( $posttype )
	{
		return $this->filters( 'posttype_archive_title', PostType::object( $posttype )->labels->all_items, $posttype );
	}

	public function template_get_archive_content()
	{
		$setting = $this->get_setting( 'posttype_content', '[alphabet-posts post_type="%s" /]' );

		$form = $this->get_search_form( [ 'post_type[]' => $this->current ] );
		$html = do_shortcode( sprintf( $setting, $this->current ) );
		$html = $this->filters( 'posttype_archive_content', $html, $this->current );

		return HTML::wrap( $form.$html, '-posttype-archives-content' );
	}

	public function taxonomy_archive_title( $taxonomy )
	{
		return $this->filters( 'taxonomy_archive_title', Taxonomy::object( $taxonomy )->labels->all_items, $taxonomy );
	}

	public function template_taxonomy_archives( $content )
	{
		$setting = $this->get_setting( 'taxonomy_content', '[alphabet-terms taxonomy="%s" /]' );

		$html = do_shortcode( sprintf( $setting, $this->current ) );
		$html = $this->filters( 'taxonomy_archive_content', $html, $this->current );

		return HTML::wrap( $html, '-taxonomy-archives-content' );
	}

	public function get_taxonomy_archive_link( $taxonomy )
	{
		if ( ! in_array( $taxonomy, $this->taxonomies() ) )
			return FALSE;

		if ( ! $slug = $this->taxonomy_archive_slug( $taxonomy ) )
			return FALSE;

		$link = sprintf( '%s/%s', get_bloginfo( 'url' ), $slug );

		return $this->filters( 'taxonomy_archive_link', $link, $taxonomy, $slug );
	}

	public function countables_taxonomy_countbox_tokens( $tokens, $taxonomy, $count, $args )
	{
		if ( $link = $this->get_taxonomy_archive_link( $taxonomy ) )
			$tokens['link'] = $link;

		return $tokens;
	}
}

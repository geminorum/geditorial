<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

trait TemplateTaxonomy
{

	/**
	 * Hooks override mechanism for custom *main* archives page of
	 * given taxonomy.
	 *
	 * @param string $constant
	 * @param string $option
	 * @return false|string
	 */
	protected function templatetaxonomy__hook_custom_archives( $constant, $option = 'custom_archives' )
	{
		if ( ! $custom = $this->get_setting( $option ) )
			return FALSE;

		if ( ! $taxonomy = $this->constant( $constant ) )
			return FALSE;

		add_filter( $this->hook_base( 'taxonomy_archive_link' ),
			function ( $false, $tax ) use ( $taxonomy, $custom ) {
				return $tax === $taxonomy ? $custom : $false;
			}, 10, 2 );

		add_filter( 'gnetwork_taxonomy_archive_link',
			function ( $false, $tax ) use ( $taxonomy, $custom ) {
				return $tax === $taxonomy ? $custom : $false;
			}, 10, 2 );

		add_filter( 'gtheme_navigation_taxonomy_archive_link',
			function ( $false, $tax ) use ( $taxonomy, $custom ) {
				return $tax === $taxonomy ? $custom : $false;
			}, 9, 2 );

		return $custom;
	}

	protected function templatetaxonomy__include( $template, $taxonomies, $empty_callback = NULL, $archive_callback = NULL )
	{
		global $wp_query;

		if ( ! isset( $wp_query ) )
			return $template;

		if ( ! $this->get_setting( 'archive_override', TRUE ) )
			return $template;

		if ( $wp_query->is_embed() || $wp_query->is_search() )
			return $template;

		if ( ! $taxonomy = $wp_query->get( 'taxonomy' ) )
			return $template;

		if ( ! in_array( $taxonomy, (array) $taxonomies, TRUE ) )
			return $template;

		if ( ! $wp_query->is_404() && ! $wp_query->is_tax( $taxonomy ) )
			return $template;

		// avoid on WooCommerce products
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
			return $template;

		if ( $wp_query->is_404() ) {

			// if new term disabled
			if ( FALSE === $empty_callback )
				return $template;

			// helps with 404 redirection
			if ( ! is_user_logged_in() )
				return $template;

			if ( is_null( $empty_callback ) )
				$empty_callback = [ $this, 'templatetaxonomy_empty_content' ];

			nocache_headers();
			// Core\WordPress::doNotCache();

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templatetaxonomy_get_empty_title( $taxonomy ),
				'post_type'  => 'page',
				'is_single'  => TRUE,
				'is_404'     => TRUE,
				'taxonomy'   => $taxonomy,
			], $empty_callback );

			$this->filter_append( 'post_class', [ 'empty-term', 'empty-term-'.$taxonomy ] );

			// $template = get_singular_template();
			$template = get_single_template();

		} else {

			if ( is_null( $archive_callback ) )
				$archive_callback = [ $this, 'templatetaxonomy_archive_content' ];

			// stored for late use
			$this->current_queried = get_queried_object_id();

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templatetaxonomy_get_archive_title( $taxonomy ),
				'post_type'  => 'page',
				'is_page'    => TRUE,
				'is_archive' => TRUE,
				'is_tax'     => TRUE,
				'taxonomy'   => $taxonomy,
			], $archive_callback );

			$this->filter_append( 'post_class', [ 'archive-term', 'archive-term-'.$taxonomy ] );
			// $this->filter( 'single_term_title' ); // no need on terms
			// $this->filter( 'gtheme_navigation_crumb_archive', 2 );
			$this->filter_false( 'gtheme_navigation_crumb_archive' );

			$this->filter_false( 'feed_links_extra_show_tax_feed' );

			$template = WordPress\Theme::getTemplate( $this->get_setting( 'archive_template' ) );
		}

		$this->filter_empty_string( 'previous_post_link' );
		$this->filter_empty_string( 'next_post_link' );

		$this->enqueue_styles();

		self::define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );
		self::define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );

		return $template;
	}

	// title for overridden empty page
	public function templatetaxonomy_get_empty_title( $taxonomy, $fallback = NULL )
	{
		if ( $title = Core\URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'term' ) ) )
			return $title;

		if ( is_null( $fallback ) )
			return _x( '[Untitled]', 'Module: Template Title', 'geditorial' );

		return $fallback;
	}

	// DEFAULT METHOD: content for overridden empty page
	public function templatetaxonomy_get_empty_content( $taxonomy, $atts = [] )
	{
		if ( $content = $this->get_setting( 'empty_content' ) )
			return Core\Text::autoP( trim( $content ) );

		return '';
	}

	// DEFAULT METHOD: title for overridden archive page
	public function templatetaxonomy_get_archive_title( $taxonomy )
	{
		// return $this->get_setting_fallback( 'archive_title',
		// 	WordPress\Term::title( $this->current_queried ) );

		return ''; // NOTE: `renderTermIntro` will display the title;
	}

	// DEFAULT METHOD: content for overridden empty items
	public function templatetaxonomy_get_empty_items( $taxonomy, $atts = [] )
	{
		if ( $content = $this->get_setting( 'archive_empty_items' ) )
			return Core\Text::autoP( trim( $content ) );

		return '';
	}

	public function gtheme_navigation_crumb_archive( $crumb, $args )
	{
		return $this->get_setting_fallback( 'archive_title',
			WordPress\Term::title( $this->current_queried ) );
	}

	public function single_term_title( $name )
	{
		return $this->get_setting_fallback( 'archive_title',
			WordPress\Term::title( $this->current_queried ) );
	}

	// DEFAULT METHOD: content for overridden archive page
	public function templatetaxonomy_get_archive_content( $taxonomy )
	{
		$setting = $this->get_setting_fallback( 'archive_content', NULL );

		if ( ! is_null( $setting ) )
			return $setting; // might be empty string

		// NOTE: here to avoid further process
		if ( $default = $this->templatetaxonomy_get_archive_content_default( $taxonomy ) )
			return $default;

		if ( ! is_tax() )
			return '';

		$html = self::buffer( [ 'geminorum\\gEditorial\\Template', 'renderTermIntro' ], [
			$this->current_queried,
			[],
			$this->key,
		] );

		$list = ShortCode::listPosts( 'assigned',
			'',
			$taxonomy,
			[
				'context' => 'template_taxonomy',
				'orderby' => 'menu_order',             // WTF: must apply to `assigned`
				'term_id' => $this->current_queried,
				'future'  => 'off',
				'title'   => FALSE,
				'wrap'    => FALSE,
			]
		);

		$html.= $list ?: $this->templatetaxonomy_get_empty_items( $taxonomy );

		return $html;
	}

	public function templatetaxonomy_get_archive_content_default( $taxonomy )
	{
		return '';
	}

	// DEFAULT METHOD: button for overridden empty/archive page
	public function templatetaxonomy_get_add_new( $taxonomy, $title = FALSE, $label = NULL )
	{
		if ( ! $edit = WordPress\Taxonomy::edit( $taxonomy, [ 'name' => $title ] ) )
			return '';

		$object = WordPress\Taxonomy::object( $taxonomy );

		return Core\HTML::tag( 'a', [
			'href'          => $edit, // FIXME: `name` not passing into input
			'class'         => [ 'button', '-add-term', '-add-term-'.$object->name ],
			'target'        => '_blank',
			'data-taxonomy' => $object->name,
		], $label ?: $object->labels->add_new_item );
	}

	// will hook to `the_content` filter on 404
	public function templatetaxonomy_empty_content( $content )
	{
		if ( ! $taxonomy = get_query_var( 'taxonomy' ) )
			return $content;

		$title = $this->templatetaxonomy_get_empty_title( $taxonomy, '' );
		$html  = $this->templatetaxonomy_get_empty_content( $taxonomy );
		$html .= $this->get_search_form( [ 'taxonomy[]' => $taxonomy ], $title );

		if ( $add_new = $this->templatetaxonomy_get_add_new( $taxonomy, $title ) )
			$html.= '<p class="-actions">'.$add_new.'</p>';

		return Core\HTML::wrap( $html, $this->base.'-empty-content' );
	}

	// will hook to `the_content` filter on archive
	public function templatetaxonomy_archive_content( $content )
	{
		return Core\HTML::wrap(
			$this->templatetaxonomy_get_archive_content( get_query_var( 'taxonomy' ) ),
			$this->base.'-archive-content'
		);
	}
}

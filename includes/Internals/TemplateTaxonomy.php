<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait TemplateTaxonomy
{

	// NOTE: since we override the archive, there are no admin-bar edit link available on the archives!
	// NOTE: using on `init` since hooking on `template_include` is too late for `admin_bar_init`.
	// @SEE: `hook_adminbar_node_for_taxonomy()` for admin taxonomy management
	protected function templatetaxonomy__hook_adminbar( $constant, $option = 'archive_override' )
	{
		if ( is_admin() )
			return FALSE;

		if ( ! $this->get_setting( $option, TRUE ) )
			return FALSE;

		if ( ! $taxonomy = $this->constant( $constant ) )
			return FALSE;

		// NOTE: WTF: here `is_tax()`/`get_query_var()` are not available!

		add_action( $this->hook_base( 'adminbar' ),
			function ( &$nodes, $parent ) use ( $taxonomy ) {

				if ( ! $term = WordPress\Term::get( get_queried_object_id() ) )
					return;

				if ( $taxonomy !== WordPress\Term::taxonomy( $term ) )
					return;

				if ( ! $edit = WordPress\Term::edit( $term ) )
					return;

				$nodes[] = [
					'id'     => 'edit', // NOTE: will convert to `wp-admin-bar-edit` to use the core icon
					'href'   => $edit,
					'title'  => Services\CustomTaxonomy::getLabel( $term, 'edit_item' ),
					'meta'   => [
						'class' => $this->class_for_adminbar_node( '-edit-item' ),
					],
				];

			}, 99, 2 );

		return TRUE;
	}

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

		if ( empty( $wp_query ) )
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

			// if empty template disabled
			if ( FALSE === $empty_callback )
				return $template;

			// helps with 404 redirection
			if ( ! is_user_logged_in() )
				return $template;

			do_action( $this->hook_base( 'template', 'taxonomy', '404', 'init' ), $taxonomy );

			$this->current_queried = $taxonomy;

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templatetaxonomy_get_empty_title( $taxonomy ),
				'post_type'  => 'page',
				'is_single'  => TRUE,
				'is_404'     => TRUE,
				'taxonomy'   => $taxonomy,
			], [
				'disable_robots' => TRUE,
				'disable_cache'  => TRUE,
			], $empty_callback ?? [ $this, 'templatetaxonomy_empty_content' ] );

			$this->filter_append( 'post_class', [ 'empty-term', 'empty-term-'.$taxonomy ] );

			$template = get_page_template();

		} else {

			do_action( $this->hook_base( 'template', 'taxonomy', 'archive', 'init' ), $taxonomy );

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
			], [], $archive_callback ?? [ $this, 'templatetaxonomy_archive_content' ] );

			$this->filter_append( 'post_class', [ 'archive-term', 'archive-term-'.$taxonomy ] );
			// $this->filter( 'single_term_title' ); // no need on terms
			// $this->filter( 'gtheme_navigation_crumb_archive', 2 );

			$template = WordPress\Theme::getTemplate( $this->get_setting( 'archive_template' ) );
		}

		$this->enqueue_styles();

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
			return $this->templatetaxonomy_process_empty_content( $content, $taxonomy );

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
			return $this->templatetaxonomy_process_empty_content( $content, $taxonomy );

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

	protected function templatetaxonomy_process_empty_content( $content, $queried, $wrap = FALSE )
	{
		if ( ! $content )
			return $content;

		$html = $content;

		if ( $data = WordPress\Taxonomy::object( $queried ) )
			$html = Core\Text::replaceTokens( $html, $data );

		$html = WordPress\ShortCode::apply( sprintf( $html, $queried ?? '' ) );
		$html = Core\Text::autoP( $html );

		return $wrap ? Core\HTML::wrap( $html, '-taxonomy-empty-content' ) : $html;
	}

	protected function templatetaxonomy_process_archive_content( $content, $queried, $wrap = FALSE )
	{
		if ( ! $content )
			return $content;

		$html = $content;

		if ( $term = WordPress\Term::get( $queried ) )
			$html = Core\Text::replaceTokens( $html, WordPress\Term::summary( $term ) );

		$html = WordPress\ShortCode::apply( sprintf( $html, $queried ?? '' ) );

		return $wrap ? Core\HTML::wrap( $html, '-taxonomy-archives-content' ) : $html;
	}

	// DEFAULT METHOD: content for overridden archive page
	public function templatetaxonomy_get_archive_content( $taxonomy )
	{
		$setting = $this->get_setting_fallback( 'archive_content', NULL );

		if ( ! is_null( $setting ) ) // might be empty string
			return $this->templatetaxonomy_process_archive_content( $setting, $this->current_queried );

		// NOTE: here to avoid further process
		if ( $default = $this->templatetaxonomy_get_archive_content_default( $taxonomy ) )
			return $this->templatetaxonomy_process_archive_content( $default, $this->current_queried );

		if ( ! is_tax() )
			return '';

		$html = self::buffer( [ 'geminorum\\gEditorial\\Template', 'renderTermIntro' ], [
			$this->current_queried,
			[],
			$this->key,
		] );

		$list = gEditorial\ShortCode::listPosts( 'assigned',
			'',
			$taxonomy,
			[
				'context' => 'template_taxonomy',
				'orderby' => 'menu_order',             // WTF: must apply to `assigned`
				'term_id' => $this->current_queried,
				'future'  => 'off',
				'wrap'    => FALSE,

				// NOTE: `renderTermIntro` absent!
				'title'      => $html ? FALSE : NULL,
				'title_link' => $html ? FALSE : WordPress\Taxonomy::link( $taxonomy, FALSE ),
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
		$object = WordPress\Taxonomy::object( $taxonomy );

		if ( ! WordPress\Taxonomy::can( $object, 'manage_terms' ) )
			return '';

		$extra = apply_filters(
			$this->hook_base( 'template', 'taxonomy', 'addnew', 'extra' ),
			[
				'name' => $title,
			],
			$object->name,
			$title,
			$this->key
		);

		return Core\HTML::button(
			$label ?? Services\CustomTaxonomy::getLabel( $object, 'add_new_item' ),
			WordPress\Taxonomy::edit( $object, $extra )
		);
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

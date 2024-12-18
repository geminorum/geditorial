<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

trait TemplatePostType
{

	protected function templateposttype__include( $template, $posttypes, $empty_callback = NULL, $archive_callback = NULL )
	{
		global $wp_query;

		if ( ! isset( $wp_query ) )
			return $template;

		if ( ! $this->get_setting( 'archive_override', TRUE ) )
			return $template;

		if ( $wp_query->is_embed() || $wp_query->is_search() )
			return $template;

		if ( ! $posttype = $wp_query->get( 'post_type' ) )
			return $template;

		if ( ! in_array( $posttype, (array) $posttypes, TRUE ) )
			return $template;

		// TODO: support singulars
		// FIXME: support new posts
		if ( ! $wp_query->is_404() && ! $wp_query->is_post_type_archive( $posttype ) )
			return $template;

		if ( $wp_query->is_404() ) {

			// if new posttype disabled
			if ( FALSE === $empty_callback )
				return $template;

			// helps with 404 redirections
			if ( ! is_user_logged_in() )
				return $template;

			if ( is_null( $empty_callback ) )
				$empty_callback = [ $this, 'templateposttype_empty_content' ];

			nocache_headers();
			// Core\WordPress::doNotCache();

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templateposttype_get_empty_title( $posttype ),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
				'is_404'     => TRUE,
			], $empty_callback );

			$this->filter_append( 'post_class', [ 'empty-posttype', 'empty-'.$posttype ] );

			// $template = get_singular_template();
			$template = get_single_template();

		} else {

			// if new posttype disabled
			if ( FALSE === $archive_callback )
				return $template;

			if ( is_null( $archive_callback ) )
				$archive_callback = [ $this, 'templateposttype_archive_content' ];

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templateposttype_get_archive_title( $posttype ),
				'post_type'  => $posttype,
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], $archive_callback );

			$this->filter_append( 'post_class', [ 'archive-posttype', 'archive-'.$posttype ] );
			$this->filter( 'post_type_archive_title', 2 );
			// $this->filter( 'gtheme_navigation_crumb_archive', 2 );
			$this->filter_false( 'gtheme_navigation_crumb_archive' );

			$template = WordPress\Theme::getTemplate( $this->get_setting( 'archive_template' ) );
		}

		$this->filter_empty_string( 'previous_post_link' );
		$this->filter_empty_string( 'next_post_link' );

		$this->enqueue_styles();

		self::define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );
		self::define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );

		return $template;
	}

	// DEFAULT METHOD: title for overrided empty page
	public function templateposttype_get_empty_title( $posttype, $fallback = NULL )
	{
		if ( $title = Core\URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'name' ) ) )
			return $title;

		if ( is_null( $fallback ) )
			return _x( '[Untitled]', 'Module: Template Title', 'geditorial' );

		return $fallback;
	}

	// DEFAULT METHOD: content for overrided empty page
	public function templateposttype_get_empty_content( $posttype, $atts = [] )
	{
		if ( $content = $this->get_setting( 'empty_content' ) )
			return Core\Text::autoP( trim( $content ) );

		return '';
	}

	// DEFAULT METHOD: title for overrided archive page
	public function templateposttype_get_archive_title( $posttype )
	{
		return $this->get_setting_fallback( 'archive_title',
			Helper::getPostTypeLabel( $posttype, 'all_items' ) );
	}

	public function gtheme_navigation_crumb_archive( $crumb, $args )
	{
		return $this->get_setting_fallback( 'archive_title', $crumb );
	}

	// no need to check for posttype
	public function post_type_archive_title( $name, $posttype )
	{
		return $this->get_setting_fallback( 'archive_title', $name );
	}

	// DEFAULT METHOD: content for overrided archive page
	public function templateposttype_get_archive_content( $posttype )
	{
		$setting = $this->get_setting_fallback( 'archive_content', NULL );

		if ( ! is_null( $setting ) )
			return $setting; // might be empty string

		// NOTE: here to avoid further process
		if ( $default = $this->templateposttype_get_archive_content_default( $posttype ) )
			return $default;

		// TODO: add widget area

		if ( is_post_type_archive() )
			return ShortCode::listPosts( 'assigned',
				$posttype, // WordPress\PostType::current(),
				'',
				[
					'context' => 'template_posttype',
					'orderby' => 'menu_order', // WTF: must apply to `assigned`
					'id'      => 'all',
					'future'  => 'off',
					'title'   => FALSE,
					'wrap'    => FALSE,
				]
			);

		return '';
	}

	public function templateposttype_get_archive_content_default( $posttype )
	{
		return '';
	}

	// DEFAULT METHOD: button for overrided empty/archive page
	public function templateposttype_get_add_new( $posttype, $title = FALSE, $label = NULL )
	{
		$object = WordPress\PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return '';

		// FIXME: must check if post is unpublished

		$extra = apply_filters(
			$this->hook_base( 'templateposttype', 'addnew_extra' ),
			[
				'post_title' => $title,
			],
			$object->name
		);

		return Core\HTML::tag( 'a', [
			'href'          => Core\WordPress::getPostNewLink( $object->name, $extra ),
			'class'         => [ 'button', '-add-posttype', '-add-posttype-'.$object->name ],
			'target'        => '_blank',
			'data-posttype' => $object->name,
		], $label ?: $object->labels->add_new_item );
	}

	// will hook to `the_content` filter on 404
	public function templateposttype_empty_content( $content )
	{
		if ( ! $post = WordPress\Post::get() )
			return $content;

		$title = $this->templateposttype_get_empty_title( $post->post_type, '' );
		$html  = $this->templateposttype_get_empty_content( $post->post_type );
		$html .= $this->get_search_form( [ 'post_type[]' => $post->post_type ], $title );

		// TODO: list other entries that linked to this title via content

		if ( $add_new = $this->templateposttype_get_add_new( $post->post_type, $title ) )
			$html.= '<p class="-actions">'.$add_new.'</p>';

		return Core\HTML::wrap( $html, $this->base.'-empty-content' );
	}

	// will hook to `the_content` filter on archive
	public function templateposttype_archive_content( $content )
	{
		return Core\HTML::wrap(
			$this->templateposttype_get_archive_content( get_query_var( 'post_type' ) ),
			$this->base.'-archive-content'
		);
	}
}

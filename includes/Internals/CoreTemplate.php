<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\WordPress;

trait CoreTemplate
{

	protected function coretemplate__include_for_posttype( $template, $posttype, $empty_callback = NULL, $archive_callback = NULL )
	{
		if ( ! $this->get_setting( 'archive_override', TRUE ) )
			return $template;

		if ( is_embed() || is_search() )
			return $template;

		if ( $posttype != $GLOBALS['wp_query']->get( 'post_type' ) )
			return $template;

		if ( ! is_404() && ! is_post_type_archive( $posttype ) )
			return $template;

		if ( is_404() ) {

			// if new posttype disabled
			if ( FALSE === $empty_callback )
				return $template;

			// helps with 404 redirections
			if ( ! is_user_logged_in() )
				return $template;

			if ( is_null( $empty_callback ) )
				$empty_callback = [ $this, 'template_empty_content' ];

			nocache_headers();
			// Core\WordPress::doNotCache();

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_empty_title(),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
				'is_404'     => TRUE,
			], $empty_callback );

			$this->filter_append( 'post_class', [ 'empty-posttype', 'empty-'.$posttype ] );

			// $template = get_singular_template();
			$template = get_single_template();

		} else {

			if ( is_null( $archive_callback ) )
				$archive_callback = [ $this, 'template_archive_content' ];

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_archive_title( $posttype ),
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

		defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' )
			or define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );

		defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			or define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );

		return $template;
	}

	// DEFAULT METHOD: title for overrided empty page
	public function template_get_empty_title( $fallback = NULL )
	{
		if ( $title = Core\URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'name' ) ) )
			return $title;

		if ( is_null( $fallback ) )
			return _x( '[Untitled]', 'Module: Template Title', 'geditorial' );

		return $fallback;
	}

	// DEFAULT METHOD: content for overrided empty page
	public function template_get_empty_content( $atts = [] )
	{
		if ( $content = $this->get_setting( 'empty_content' ) )
			return Core\Text::autoP( trim( $content ) );

		return '';
	}

	// DEFAULT METHOD: title for overrided archive page
	public function template_get_archive_title( $posttype )
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
	public function template_get_archive_content()
	{
		$setting = $this->get_setting_fallback( 'archive_content', NULL );

		if ( ! is_null( $setting ) )
			return $setting; // might be empty string

		// NOTE: here to avoid further process
		if ( $default = $this->template_get_archive_content_default() )
			return $default;

		if ( is_post_type_archive() )
			return ShortCode::listPosts( 'assigned',
				WordPress\PostType::current(),
				'',
				[
					'orderby' => 'menu_order', // WTF: must apply to `assigned`
					'id'      => 'all',
					'future'  => 'off',
					'title'   => FALSE,
					'wrap'    => FALSE,
				]
			);

		return '';
	}

	public function template_get_archive_content_default()
	{
		return '';
	}

	// DEFAULT METHOD: button for overrided empty/archive page
	public function template_get_add_new( $posttype, $title = FALSE, $label = NULL )
	{
		$object = WordPress\PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return '';

		// FIXME: must check if post is unpublished

		return HTML::tag( 'a', [
			'href'          => Core\WordPress::getPostNewLink( $object->name, [ 'post_title' => $title ] ),
			'class'         => [ 'button', '-add-posttype', '-add-posttype-'.$object->name ],
			'target'        => '_blank',
			'data-posttype' => $object->name,
		], $label ?: $object->labels->add_new_item );
	}

	// DEFAULT FILTER
	public function template_empty_content( $content )
	{
		if ( ! $post = WordPress\Post::get() )
			return $content;

		$title = $this->template_get_empty_title( '' );
		$html  = $this->template_get_empty_content();
		$html .= $this->get_search_form( [ 'post_type[]' => $post->post_type ], $title );

		// TODO: list other entries that linked to this title via content

		if ( $add_new = $this->template_get_add_new( $post->post_type, $title ) )
			$html.= '<p class="-actions">'.$add_new.'</p>';

		return Core\HTML::wrap( $html, $this->base.'-empty-content' );
	}

	// DEFAULT FILTER
	public function template_archive_content( $content )
	{
		return Core\HTML::wrap( $this->template_get_archive_content(), $this->base.'-archive-content' );
	}
}

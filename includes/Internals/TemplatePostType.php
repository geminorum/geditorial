<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait TemplatePostType
{

	protected function templateposttype__include( $template, $posttypes, $empty_callback = NULL, $archive_callback = NULL, $newpost_callback = NULL )
	{
		global $wp_query;

		if ( empty( $wp_query ) )
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
		if ( ! $wp_query->is_404() && ! $wp_query->is_post_type_archive( $posttype ) )
			return $template;

		if ( $wp_query->is_404() ) {

			// if empty template disabled
			if ( FALSE === $empty_callback )
				return $template;

			// helps with 404 redirection
			if ( ! is_user_logged_in() )
				return $template;

			do_action( $this->hook_base( 'template', 'posttype', '404', 'init' ), $posttype );

			$this->current_queried = $posttype;

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templateposttype_get_empty_title( $posttype ),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
				'is_404'     => TRUE,
			], [
				'disable_robots' => TRUE,
				'disable_cache'  => TRUE,
			], $empty_callback ?? [ $this, 'templateposttype_empty_content' ] );

			$this->filter_append( 'post_class', [ 'empty-posttype', 'empty-'.$posttype ] );

			$template = get_page_template();

		} else if ( isset( $_GET['newpost'] ) ) {

			// if new post-type disabled
			if ( FALSE === $newpost_callback )
				return $template;

			do_action( $this->hook_base( 'template', 'newpost', 'init' ), $posttype );

			$this->current_queried = $posttype;

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templateposttype_get_newpost_title( $posttype ),
				'post_type'  => $posttype,
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], [
				'disable_robots' => TRUE,
				'disable_cache'  => TRUE,
			], $newpost_callback ?? [ $this, 'templateposttype_newpost_content' ] );

			$this->filter_append( 'post_class', [ 'newpost-posttype', 'newpost-'.$posttype ] );
			$this->filter( 'post_type_archive_title', 2, 0, 'templateposttype_newpost' );
			// $this->filter( 'gtheme_navigation_crumb_archive', 2, 10, 'templateposttype_newpost' );

			$template = WordPress\Theme::getTemplate( $this->get_setting( 'newpost_template' ) );

			$this->enqueue_asset_js( [
				'strings' => $this->get_strings( 'newpost_template', 'js_strings', [
					'notarget' => _x( 'Cannot handle the target window!', 'Internal: Template Post-Type', 'geditorial' ),
					'willgo'   => _x( 'Your Data saved successfully. will redirect you in moments &hellip;', 'Internal: Template Post-Type', 'geditorial' ),
				] ),
				'config' => [
					'posttype' => $posttype,
				],
			], 'template.newpost', [
				'jquery',
				'wp-api-request',
			], '_template' );

		} else {

			// if new post-type disabled
			if ( FALSE === $archive_callback )
				return $template;

			do_action( $this->hook_base( 'template', 'posttype', 'archive', 'init' ), $posttype );

			WordPress\Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->templateposttype_get_archive_title( $posttype ),
				'post_type'  => $posttype,
				'is_page'    => TRUE,
				'is_archive' => TRUE,
			], [], $archive_callback ?? [ $this, 'templateposttype_archive_content' ] );

			$this->filter_append( 'post_class', [ 'archive-posttype', 'archive-'.$posttype ] );
			$this->filter( 'post_type_archive_title', 2 );
			// $this->filter( 'gtheme_navigation_crumb_archive', 2 );

			$template = WordPress\Theme::getTemplate( $this->get_setting( 'archive_template' ) );
		}

		$this->enqueue_styles();

		return $template;
	}

	// DEFAULT METHOD: title for overridden empty page
	public function templateposttype_get_empty_title( $posttype, $fallback = NULL )
	{
		if ( $title = Core\URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'name' ) ) )
			return $title;

		if ( is_null( $fallback ) )
			return _x( '[Untitled]', 'Module: Template Title', 'geditorial' );

		return $fallback;
	}

	// DEFAULT METHOD: content for overridden empty page
	public function templateposttype_get_empty_content( $posttype, $atts = [] )
	{
		if ( $content = $this->get_setting( 'empty_content' ) )
			return Core\Text::autoP( trim( $content ) );

		return '';
	}

	// DEFAULT METHOD: title for overridden archive page
	public function templateposttype_get_archive_title( $posttype )
	{
		return $this->get_setting_fallback( 'archive_title',
			Services\CustomPostType::getLabel( $posttype, 'all_items' ) );
	}

	public function gtheme_navigation_crumb_archive( $crumb, $args )
	{
		return $this->get_setting_fallback( 'archive_title', $crumb );
	}

	public function gtheme_navigation_crumb_archive_templateposttype_newpost( $crumb, $args )
	{
		return $this->get_setting_fallback( 'newpost_title', $crumb );
	}

	// no need to check for post-type
	public function post_type_archive_title( $name, $posttype )
	{
		return $this->get_setting_fallback( 'archive_title', $name );
	}

	// no need to check for post-type
	public function post_type_archive_title_templateposttype_newpost( $name, $posttype )
	{
		return $this->get_setting_fallback( 'newpost_title',
			Services\CustomPostType::getLabel( $posttype, 'add_new_item', NULL, $name ) );
	}

	// DEFAULT METHOD: content for overridden archive page
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
			return gEditorial\ShortCode::listPosts( 'assigned',
				$posttype,
				'',
				[
					'context' => 'template_posttype',
					'orderby' => 'menu_order',          // WTF: must apply to `assigned`
					'term_id' => 'all',
					// 'future'  => WordPress\PostType::can( $posttype, 'publish_posts' ) ? 'on' : 'off',
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

	// DEFAULT METHOD: button for overridden empty/archive page
	public function templateposttype_get_add_new( $posttype, $title = FALSE, $label = NULL )
	{
		$object = WordPress\PostType::object( $posttype );

		if ( ! WordPress\PostType::can( $object, 'create_posts' ) )
			return '';

		// FIXME: must check if post is unpublished

		$extra = apply_filters(
			$this->hook_base( 'template', 'posttype', 'addnew', 'extra' ),
			[
				'post_title' => $title,
			],
			$object->name,
			$title,
			$this->key
		);

		return Core\HTML::button(
			$label ?? Services\CustomPostType::getLabel( $object, 'add_new_item' ),
			WordPress\PostType::newLink( $object->name, $extra )
		);
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

	// will hook to `the_content` filter on new-post
	public function templateposttype_newpost_content( $content )
	{
		if ( ! $post = WordPress\Post::get() )
			return $content;

		$html = $this->templateposttype_newpost_form( $post->post_type );

		return Core\HTML::wrap( $html, $this->base.'-newpost-content' );
	}

	protected function templateposttype_newpost_form( $posttype )
	{
		$status = $this->get_setting( 'post_status', 'pending' );  // `draft`
		$target = 'none';                                          // self::req( 'target', 'none' );
		$linked = NULL;                                            // self::req( 'linked', FALSE );
		$meta   = [];                                              // self::req( 'meta', [] );
		$object = WordPress\PostType::object( $posttype );
		$post   = WordPress\Post::defaultToEdit( $posttype );

		if ( ! WordPress\PostType::can( $object, 'create_posts' ) )
			return Core\HTML::dieMessage( $this->get_notice_for_noaccess() );

		ob_start();

		$meta = apply_filters( $this->hook_base( 'template', 'newpost', 'meta' ),
			$meta,
			$posttype,
			$target,
			$linked,
			$status
		);

		echo $this->wrap_open( '-newpost-layout' );
		echo '<div class="row"><div class="col-6"><form class="-form-form">';

		do_action( $this->hook_base( 'template', 'newpost', 'beforetitle' ),
			$posttype,
			$post,
			$target,
			$linked,
			$status,
			$meta
		);

		if ( $this->is_posttype_support( $posttype, 'title' ) ) {

			$field = $this->classs( $posttype, 'title' );
			$label = $this->get_string( 'post_title', $posttype, 'newpost', __( 'Add title' ) );
			$value = apply_filters( $this->hook_base( 'template', 'newpost', 'title' ),
				'',
				$posttype,
				$target,
				$linked,
				$status,
				$meta
			);

			$html = Core\HTML::tag( 'input', [
				'type'        => 'text',
				'class'       => [ 'large-text', 'form-control' ],
				'id'          => $field,
				'name'        => 'title',
				'value'       => $value,
				'placeholder' => apply_filters( 'enter_title_here', $label, $post ),
			] );

			echo '<div class="-form-group">';
				Core\HTML::label( $html, $field, FALSE );
			echo '</div>';
		}

		do_action( $this->hook_base( 'template', 'newpost', 'aftertitle' ),
			$posttype,
			$post,
			$target,
			$linked,
			$status,
			$meta
		);

		if ( $this->is_posttype_support( $posttype, 'excerpt' ) ) {

			$field = $this->classs( $posttype, 'excerpt' );
			$label = $this->get_string( 'post_excerpt', $posttype, 'newpost', __( 'Excerpt' ) );
			$value = apply_filters( $this->hook_base( 'template', 'newpost', 'excerpt' ),
				'',
				$posttype,
				$target,
				$linked,
				$status,
				$meta
			);

			$html = Core\HTML::tag( 'textarea', [
				'id'           => $field,
				'name'         => 'excerpt',
				'placeholder'  => $label,
				'class'        => [ 'mceEditor', 'large-text', 'form-control' ],
				'rows'         => 2,
				// 'cols'         => 15,
				'autocomplete' => 'off',
			], Core\HTML::escapeTextarea( $value ) );

			echo '<div class="-form-group">';
				Core\HTML::label( $html, $field, FALSE );
			echo '</div>';
		}

		if ( $this->is_posttype_support( $posttype, 'editor' ) ) {

			$field = $this->classs( $posttype, 'content' );
			$label = $this->get_string( 'post_content', $posttype, 'newpost', __( 'Content' ) );
			$value = apply_filters( $this->hook_base( 'template', 'newpost', 'content' ),
				'',
				$posttype,
				$target,
				$linked,
				$status,
				$meta
			);

			$html = Core\HTML::tag( 'textarea', [
				'id'           => $field,
				'name'         => 'content',
				'placeholder'  => $label,
				'class'        => [ 'mceEditor', 'large-text', 'form-control' ],
				'rows'         => 6,
				// 'cols'         => 15,
				'autocomplete' => 'off',
			], Core\HTML::escapeTextarea( $value ) );

			echo '<div class="-form-group">';
				Core\HTML::label( $html, $field, FALSE );
			echo '</div>';
		}

		if ( $object->hierarchical )
			MetaBox::fieldPostParent( $post, FALSE, 'parent' );

		do_action( $this->hook_base( 'template', 'newpost', 'aftercontent' ),
			$posttype,
			$post,
			$target,
			$linked,
			$status,
			$meta
		);

		Core\HTML::inputHidden( 'type', $posttype );
		// Core\HTML::inputHidden( 'status', $status === 'publish' ? 'publish' : 'draft' ); // only publish/draft
		Core\HTML::inputHidden( 'status', $status );
		Core\HTML::inputHiddenArray( $meta, 'meta' );

		echo $this->wrap_open_buttons();

		echo '<span class="-message"></span>';
		echo gEditorial\Ajax::spinner();

			do_action( $this->hook_base( 'template', 'newpost', 'buttons' ),
				$posttype,
				$post,
				$target,
				$linked,
				$status,
				$meta
			);

		echo Core\HTML::tag( 'a', [
			'href'  => '#',
			'class' => Core\HTML::buttonClass( FALSE, [ '-form-save-draft', 'disabled' ] ),
			'data'  => [
				'target'   => $target,
				'type'     => $posttype,
				'linked'   => $linked,
				'endpoint' => rest_url( WordPress\PostType::getRestRoute( $object ) ),
			],
		], _x( 'Save Draft', 'Internal: Template Post-Type', 'geditorial' ) );

		echo '</p></form></div><div class="col-6">';

			do_action( $this->hook_base( 'template', 'newpost', 'side' ),
				$posttype,
				$post,
				$target,
				$linked,
				$status,
				$meta
			);

		echo '</div></div>';

		return ob_get_clean();
	}

	// DEFAULT METHOD: title for overridden new-post page
	public function templateposttype_get_newpost_title( $posttype )
	{
		return $this->get_setting_fallback( 'newpost_title',
			Services\CustomPostType::getLabel( $posttype, 'add_new_item', NULL, $posttype ) );
	}
}

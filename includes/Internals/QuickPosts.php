<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Template;
use geminorum\gEditorial\WordPress;

trait QuickPosts
{

	public function render_newpost_adminpage()
	{
		$this->render_default_mainpage( 'newpost', 'insert' );
	}

	// TODO: link to edit posttype screen
	// TODO: get default posttype/status somehow!
	protected function render_newpost_content()
	{
		$posttype = self::req( 'type', 'post' );
		$status   = self::req( 'status', 'draft' );
		$target   = self::req( 'target', 'none' );
		$linked   = self::req( 'linked', FALSE );
		$meta     = self::req( 'meta', [] );
		$object   = get_post_type_object( $posttype );
		$post     = get_default_post_to_edit( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return Core\HTML::desc( gEditorial\Plugin::denied( FALSE ), TRUE, '-denied' );

		$meta = $this->filters( 'newpost_content_meta', $meta, $posttype, $target, $linked, $status );

		echo $this->wrap_open( '-newpost-layout' );
		echo '<div class="-main">';

		$this->actions( 'newpost_content_before_title', $posttype, $post, $target, $linked, $status, $meta );

		if ( $this->is_posttype_support( $posttype, 'title' ) ) {

			$field = $this->classs( $posttype, 'title' );
			$label = $this->get_string( 'post_title', $posttype, 'newpost', __( 'Add title' ) );

			$html = Core\HTML::tag( 'input', [
				'type'        => 'text',
				'class'       => 'large-text',
				'id'          => $field,
				'name'        => 'title',
				'placeholder' => apply_filters( 'enter_title_here', $label, $post ),
			] );

			Core\HTML::label( $html, $field );
		}

		$this->actions( 'newpost_content_after_title', $posttype, $post, $target, $linked, $status, $meta );

		if ( $this->is_posttype_support( $posttype, 'excerpt' ) ) {

			$field = $this->classs( $posttype, 'excerpt' );
			$label = $this->get_string( 'post_excerpt', $posttype, 'newpost', __( 'Excerpt' ) );

			$html = Core\HTML::tag( 'textarea', [
				'id'           => $field,
				'name'         => 'excerpt',
				'placeholder'  => $label,
				'class'        => [ 'mceEditor', 'large-text' ],
				'rows'         => 2,
				'cols'         => 15,
				'autocomplete' => 'off',
			], '' );

			Core\HTML::label( $html, $field );
		}

		if ( $this->is_posttype_support( $posttype, 'editor' ) ) {

			$field = $this->classs( $posttype, 'content' );
			$label = $this->get_string( 'post_content', $posttype, 'newpost', __( 'What&#8217;s on your mind?' ) );

			$html = Core\HTML::tag( 'textarea', [
				'id'           => $field,
				'name'         => 'content',
				'placeholder'  => $label,
				'class'        => [ 'mceEditor', 'large-text' ],
				'rows'         => 6,
				'cols'         => 15,
				'autocomplete' => 'off',
			], '' );

			Core\HTML::label( $html, $field );
		}

		if ( $object->hierarchical )
			MetaBox::fieldPostParent( $post, FALSE, 'parent' );

		$this->actions( 'newpost_content', $posttype, $post, $target, $linked, $status, $meta );

		Core\HTML::inputHidden( 'type', $posttype );
		Core\HTML::inputHidden( 'status', $status === 'publish' ? 'publish' : 'draft' ); // only publish/draft
		Core\HTML::inputHiddenArray( $meta, 'meta' );

		echo $this->wrap_open_buttons();

		echo '<span class="-message"></span>';
		echo Ajax::spinner();

		echo Core\HTML::tag( 'a', [
			'href'  => '#',
			'class' => [ 'button', '-save-draft', 'disabled' ],
			'data'  => [
				'target'   => $target,
				'type'     => $posttype,
				'linked'   => $linked,
				'endpoint' => rest_url( WordPress\PostType::getRestRoute( $object ) ),
			],
		], _x( 'Save Draft & Close', 'Module', 'geditorial-admin' ) );

		echo '</p></div><div class="-side">';
		echo '<div class="-recents">';

			// FIXME: do actions here
			// FIXME: move recents to pre-conf action
			// FIXME: correct the selectors
			// TODO: hook action from Book module: suggestd the book by passed meta

			/* translators: `%s`: posttype singular name */
			$hint = sprintf( _x( 'Or select this %s', 'Module: Recents', 'geditorial-admin' ), $object->labels->singular_name );

			Template::renderRecentByPosttype( $object, '#', NULL, $hint, [
				'post_status' => WordPress\Status::acceptable( $posttype, 'recent', [ 'pending' ] ),
			] );

		echo '</div>';

			/* translators: `%s`: posttype name */
			Core\HTML::desc( sprintf( _x( 'Or select one from Recent %s.', 'Module: Recents', 'geditorial-admin' ), $object->labels->name ) );

		echo '</div></div>';

		$this->enqueue_asset_js( [
			'strings' => [
				'noparent' => _x( 'This frame has no parent window!', 'Module: NewPost: JS String', 'geditorial-admin' ),
				'notarget' => _x( 'Cannot handle the target window!', 'Module: NewPost: JS String', 'geditorial-admin' ),
				'closeme'  => _x( 'New post has been saved you may close this frame!', 'Module: NewPost: JS String', 'geditorial-admin' ),
			],
		], 'module.newpost', [
			'jquery',
			'wp-api-request',
		], '_newpost' );
	}

	// DEFAULT FILTER
	// USAGE: `$this->action_self( 'newpost_content', 4, 99, 'menu_order' );`
	public function newpost_content_menu_order( $posttype, $post, $target, $linked )
	{
		Core\HTML::inputHidden( 'menu_order', WordPress\PostType::getLastMenuOrder( $posttype, $post->ID ) + 1 );
	}

	// LEGACY: do not use thickbox anymore!
	// NOTE: must `add_thickbox()` on load
	// FIXME: use color box api
	public function do_render_thickbox_newpostbutton( $post, $constant, $context = 'newpost', $extra = [], $inline = FALSE, $width = '600' )
	{
		$posttype = $this->constant( $constant );
		$object   = WordPress\PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return FALSE;

		// for inline only
		// modal id must be: `{$base}-{$module}-thickbox-{$context}`
		if ( $inline && $context && method_exists( $this, 'admin_footer_'.$context ) )
			$this->action( 'admin_footer', 0, 20, $context );

		/* translators: `%1$s`: current post title, `%2$s`: posttype singular name */
		$title = $this->get_string( 'mainbutton_title', $constant, 'newpost', _x( 'Quick New %2$s', 'Module: Button Title', 'geditorial-admin' ) );
		$text  = $this->get_string( 'mainbutton_text', $constant, 'newpost', sprintf( '%s %s', '%1$s', $object->labels->add_new_item ) );
		$name  = $object->labels->singular_name;

		if ( $inline )
			// WTF: thickbox bug: does not process the arg after `TB_inline`!
			$link = '#TB_inline?dummy=dummy&width='.$width.'&inlineId='.$this->classs( 'thickbox', $context ).( $extra ? '&'.http_build_query( $extra ) : '' ); // &modal=true
		else
			// WTF: thickbox bug: does not pass the args after `TB_iframe`!
			$link = $this->get_adminpage_url( TRUE, array_merge( [
				'type'     => $posttype,
				'linked'   => $post->ID,
				'noheader' => 1,
				'width'    => $width,
			], $extra, [ 'TB_iframe' => 'true' ] ), $context );

		$html = Core\HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'newpostbutton', $context ),
			'class' => [ 'button', '-button', '-button-full', '-button-icon', '-newpostbutton', 'thickbox' ],
			'title' => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Helper::getIcon( $this->module->icon ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons hide-if-no-js' );
	}
}

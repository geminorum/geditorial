<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait QuickPosts
{
	use AdminPage;

	public function render_newpost_adminpage()
	{
		$this->render_default_mainpage( 'newpost', 'insert' );
	}

	// TODO: link to edit post-type screen
	// TODO: get default post-type/status somehow!
	protected function render_newpost_content()
	{
		$posttype = self::req( 'type', 'post' );
		$status   = self::req( 'status', 'draft' );
		$target   = self::req( 'target', 'none' );
		$linked   = self::req( 'linked', FALSE );
		$recents  = self::req( 'recents', TRUE );
		$meta     = self::req( 'meta', [] );
		$object   = get_post_type_object( $posttype );
		$post     = get_default_post_to_edit( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return Core\HTML::dieMessage( $this->get_notice_for_noaccess() );

		// OLD HOOK: `{$base}_{$module}_newpost_content_meta`
		$meta = apply_filters( $this->hook_base( 'template', 'newpost', 'meta' ),
			$meta,
			$posttype,
			$target,
			$linked,
			$status
		);

		echo $this->wrap_open( '-newpost-layout'.( $recents ? ' -has-recents' : '' ) );
		echo '<div class="-main">';

		// OLD HOOK: `{$base}_{$module}_newpost_content_before_title`
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
				'class'       => 'large-text',
				'id'          => $field,
				'name'        => 'title',
				'value'       => $value,
				'placeholder' => apply_filters( 'enter_title_here', $label, $post ),
			] );

			Core\HTML::label( $html, $field );
		}

		// OLD HOOK: `{$base}_{$module}_newpost_content_after_title`
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
				'class'        => [ 'mceEditor', 'large-text' ],
				'rows'         => 2,
				'cols'         => 15,
				'autocomplete' => 'off',
			], Core\HTML::escapeTextarea( $value ) );

			Core\HTML::label( $html, $field );
		}

		if ( $this->is_posttype_support( $posttype, 'editor' ) ) {

			$field = $this->classs( $posttype, 'content' );
			$label = $this->get_string( 'post_content', $posttype, 'newpost', __( 'What&#8217;s on your mind?' ) );
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
				'class'        => [ 'mceEditor', 'large-text' ],
				'rows'         => 6,
				'cols'         => 15,
				'autocomplete' => 'off',
			], Core\HTML::escapeTextarea( $value ) );

			Core\HTML::label( $html, $field );
		}

		if ( $object->hierarchical )
			gEditorial\MetaBox::fieldPostParent( $post, FALSE, 'parent' );

		// OLD HOOK: `{$base}_{$module}_newpost_content`
		do_action( $this->hook_base( 'template', 'newpost', 'aftercontent' ),
			$posttype,
			$post,
			$target,
			$linked,
			$status,
			$meta
		);

		Core\HTML::inputHidden( 'type', $posttype );
		Core\HTML::inputHidden( 'status', $status === 'publish' ? 'publish' : 'draft' ); // only publish/draft
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
				'class' => Core\HTML::buttonClass( FALSE, [ '-save-draft', 'disabled' ] ),
				'data'  => [
					'target'   => $target,
					'type'     => $posttype,
					'linked'   => $linked,
					'endpoint' => rest_url( WordPress\PostType::getRestRoute( $object ) ),
				],
			], _x( 'Save Draft & Close', 'Module', 'geditorial-admin' ) );

		echo '</p></div>';

		if ( $recents || has_action( $this->hook_base( 'template', 'newpost', 'side' ) ) ) {

			echo '<div class="-side">';

				if ( $recents ) {
					echo '<div class="-recents">';

						// FIXME: move `recents` to pre-configured action
						// FIXME: correct the selectors

						gEditorial\Template::renderRecentByPosttype( $object, '#', NULL, sprintf(
							/* translators: `%s`: post-type singular name */
							_x( 'Or select this %s', 'Module: Recents', 'geditorial-admin' ),
							$object->labels->singular_name
						), [
							'post_status' => WordPress\Status::acceptable( $posttype, 'recent', [ 'pending' ] ),
						] );

					echo '</div>';

					Core\HTML::desc( sprintf(
						/* translators: `%s`: post-type name */
						_x( 'Or select one from Recent %s.', 'Module: Recents', 'geditorial-admin' ),
						$object->labels->name
					) );
				}

				do_action( $this->hook_base( 'template', 'newpost', 'side' ),
					$posttype,
					$post,
					$target,
					$linked,
					$status,
					$meta
				);

			echo '</div>';
		}

		echo '</div>';

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
	// USAGE: `$this->action_self( 'newpost_aftercontent', 4, 99, 'menu_order' );`
	public function newpost_aftercontent_menu_order( $posttype, $post, $target, $linked )
	{
		Core\HTML::inputHidden( 'menu_order', WordPress\PostType::getLastMenuOrder( $posttype, $post->ID ) + 1 );
	}

	// LEGACY: do not use thick-box anymore!
	// NOTE: must `add_thickbox()` on load
	// FIXME: use color box API
	public function do_render_thickbox_newpostbutton( $post, $constant, $context = 'newpost', $extra = [], $inline = FALSE, $width = '600' )
	{
		$posttype = $this->constant( $constant );
		$object   = WordPress\PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return FALSE;

		// NOTE: for inline only
		// modal id must be: `{$base}-{$module}-thickbox-{$context}`
		if ( $inline && $context && method_exists( $this, 'admin_footer_'.$context ) )
			$this->action( 'admin_footer', 0, 20, $context );

		/* translators: `%1$s`: current post title, `%2$s`: post-type singular name */
		$title = $this->get_string( 'mainbutton_title', $constant, 'newpost', _x( 'Quick New %2$s', 'Module: Button Title', 'geditorial-admin' ) );
		$text  = $this->get_string( 'mainbutton_text', $constant, 'newpost', Core\Text::spaced( '%1$s', $object->labels->add_new_item ) );
		$name  = $object->labels->singular_name;

		if ( $inline )
			// NOTE: WTF: thick-box bug: does not process the argument after `TB_inline`!
			$link = '#TB_inline?dummy=dummy&width='.$width.'&inlineId='.$this->classs( 'thickbox', $context ).( $extra ? '&'.http_build_query( $extra ) : '' ); // &modal=true
		else
			// NOTE: WTF: thick-box bug: does not pass the args after `TB_iframe`!
			$link = $this->get_adminpage_url( TRUE, array_merge( [
				'type'     => $posttype,
				'linked'   => $post->ID,
				'noheader' => 1,
				'width'    => $width,
			], $extra, [ 'TB_iframe' => 'true' ] ), $context );

		$html = Core\HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'newpostbutton', $context ),
			'class' => Core\HTML::buttonClass( FALSE, [ '-button-full', '-button-icon', 'thickbox' ] ),
			'title' => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Services\Icons::get( $this->module->icon ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons hide-if-no-js' );
	}
}

<?php namespace geminorum\gEditorial\Modules\Attachments;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Attachments extends gEditorial\Module
{
	use Internals\CoreAdmin;
	use Internals\CoreRestrictPosts;

	protected $priority_adminbar_init = 110;

	public static function module()
	{
		return [
			'name'     => 'attachments',
			'title'    => _x( 'Attachments', 'Modules: Attachments', 'geditorial-admin' ),
			'desc'     => _x( 'Media Enhancements', 'Modules: Attachments', 'geditorial-admin' ),
			'icon'     => 'paperclip',
			'access'   => 'stable',
			'keywords' => [
				'media',
				'has-adminbar',
				'has-shortcodes',
			],
		];
	}

	protected function settings_help_tabs( $context = 'settings' )
	{
		return array_merge(
			ModuleInfo::getHelpTabs( $context ),
			parent::settings_help_tabs( $context )
		);
	}

	protected function get_global_settings()
	{
		return [
			'_general' => [
				'comment_status' => [ _x( 'Determines the default status of the new attachment comments.', 'Setting Description', 'geditorial-attachments' ) ],
				[
					'field'       => 'comment_control',
					'title'       => _x( 'Comment Control', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Displays comments status check-box for each attachment.', 'Setting Description', 'geditorial-attachments' ),
				],
				[
					'field'       => 'auto_rotation',
					'title'       => _x( 'Auto Rotation', 'Setting Title', 'geditorial-attachments' ),
					'description' => sprintf(
						/* translators: `%s`: `EXIF` */
						_x( 'Fixes the rotation of JPEG images using %s extension, immediately after the upload.', 'Setting Description', 'geditorial-attachments' ),
						Core\HTML::code( 'EXIF' )
					),
				],
				[
					'field' => 'fallback_alt_to_title',
					'title' => sprintf(
						/* translators: `%s`: `alt` */
						_x( 'Fallback %s to Title', 'Setting Title', 'geditorial-attachments' ),
						Core\HTML::code( 'alt' )
					),
					'description' => _x( 'Tries to fill empty alt attribute with attachment title on images.', 'Setting Description', 'geditorial-attachments' ),
				],
			],
			'_frontend' => [
				'adminbar_summary',
				[
					'field'       => 'rewrite_permalink',
					'title'       => _x( 'Rewrite Permalinks', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Changes default permalinks into attachment id.', 'Setting Description', 'geditorial-attachments' ),
				],
				[
					'field'       => 'prefix_permalink',
					'type'        => 'text',
					'title'       => _x( 'Prefix Permalinks', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Adds to the permalink of attachments, before id.', 'Setting Description', 'geditorial-attachments' ),
					'field_class' => [ 'medium-text', 'code-text' ],
					'placeholder' => 'media',
					'ortho'       => 'slug',
				],
			],
			'_editlist' => [
				[
					'field'       => 'attachment_count',
					'title'       => _x( 'Attachment Count', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Displays attachment summary of the post.', 'Setting Description', 'geditorial-attachments' ),
				],
				'admin_restrict' => _x( 'Enhances author restrictions on media library list view.', 'Setting Description', 'geditorial-attachments' ),
				[
					'field'       => 'restrict_library',
					'title'       => _x( 'Restrict Library', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Restricts Media Library access to user\'s own uploads.', 'Setting Description', 'geditorial-attachments' ),
				],
			],
			'posttypes_option' => 'posttypes_option',
			'_shortcode' => [
				'shortcode_support',
				[
					'field'       => 'shortcode_caption',
					'title'       => _x( 'ShortCode Caption', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Displays the attachment caption after each item on the list.', 'Setting Description', 'geditorial-attachments' ),
				],
				[
					'field'       => 'shortcode_description',
					'title'       => _x( 'ShortCode Description', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Displays the attachment description after each item on the list.', 'Setting Description', 'geditorial-attachments' ),
					'default'     => '1',
				],
			],
			'_constants' => [
				'main_shortcode_constant' => [ NULL, 'attachments' ],
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'main_shortcode' => 'attachments',
		];
	}

	public function init()
	{
		parent::init();

		$this->filter( 'get_default_comment_status', 3, 12 );

		if ( $this->get_setting( 'comment_control' ) ) {
			$this->filter( 'attachment_fields_to_edit', 2, 8 );
			$this->filter( 'attachment_fields_to_save', 2, 8 );
		}

		if ( $this->get_setting( 'auto_rotation' ) )
			$this->action( 'add_attachment', 1, 4, 'auto_rotation' );

		if ( $this->get_setting( 'rewrite_permalink' ) ) {
			$this->add_rewrite_rule();
			$this->filter( 'attachment_link', 2, 20 );
		}

		$this->action_module( 'pointers', 'post', 5, 999 );
		$this->register_shortcode( 'main_shortcode' );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'fallback_alt_to_title' ) )
			$this->filter( 'get_attachment_image_attributes', 3, 8, FALSE, 'wp' );
	}

	public function setup_ajax()
	{
		if ( $this->get_setting( 'restrict_library' ) )
			$this->filter( 'ajax_query_attachments_args' );

		if ( ! $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			return;

		if ( $this->get_setting( 'attachment_count', FALSE ) )
			$this->coreadmin__hook_tweaks_column_attr( $posttype, 20 );
	}

	public function current_screen( $screen )
	{
		if ( 'edit' === $screen->base
			&& $this->posttype_supported( $screen->post_type ) ) {

			if ( $this->get_setting( 'attachment_count' ) )
				$this->coreadmin__hook_tweaks_column_attr( $screen->post_type, 20 );

		} else if ( 'media' === $screen->base && 'add' === $screen->action ) {

			$this->action( 'post-plupload-upload-ui', 0, 8 );

		} else if ( 'upload' === $screen->base ) {

			$this->corerestrictposts__hook_screen_authors();
		}
	}

	public function post_plupload_upload_ui()
	{
		if ( ! $parent = self::req( 'post_id' ) )
			return;

		if ( ! $post = WordPress\Post::get( (int) $parent ) )
			return;

		$edit = WordPress\Post::edit( $post );

		echo Core\HTML::warning( sprintf(
			/* translators: `%1$s`: mark-up start, `%2$s`: mark-up end, `%3$s`: post title, `%4$s`: post-type singular label */
			_x( 'You are about to upload attachments into %1$s%3$s%2$s %4$s.', 'Message', 'geditorial-attachments' ),
			$edit ? '<a href="'.Core\HTML::escapeURL( $edit ).'"><strong>' : '<strong>',
			$edit ? '</strong></a>' : '</strong>',
			WordPress\Post::title( $post ),
			Services\CustomPostType::getLabel( $post, 'singular_name' )
		) );
	}

	/**
	 * Filters the default comment status for the attachment post-type.
	 *
	 * @param string $status
	 * @param string $posttype
	 * @param string $comment_type
	 * @return string
	 */
	public function get_default_comment_status( $status, $posttype, $comment_type )
	{
		if ( 'attachment' !== $posttype )
			return $status;

		if ( ! in_array( $comment_type, [ 'comment', 'pingback', 'trackback' ], TRUE ) )
			return $status;

		return $this->get_setting( 'comment_status', 'closed' );
	}

	/**
	 * Filters the attachment fields to edit.
	 *
	 * @param array $form_fields
	 * @param object $post
	 * @return array
	 */
	public function attachment_fields_to_edit( $form_fields, $post )
	{
		$html = '';

		$name  = sprintf( 'attachments[%d][comment_status]', $post->ID );
		$field = '<label for="'.$name.'" class="selectit">';
		$field.= '<input type="checkbox" value="open" '.checked( $post->comment_status, 'open', FALSE ).' id="'.$name.'" name="'.$name.'" />';
		$field.= '&nbsp;'.__( 'Allow comments' );
		$field.= '</label>';
		$html .= Core\HTML::wrap( $field, 'field-wrap -checkbox' );

		$name  = sprintf( 'attachments[%d][ping_status]', $post->ID );
		$field = '<label for="'.$name.'" class="selectit">';
		$field.= '<input type="checkbox" value="open" '.checked( $post->ping_status, 'open', FALSE ).' id="'.$name.'" name="'.$name.'" />';
		$field.= '&nbsp;'.sprintf(
			/* translators: `%s`: Documentation URL */
			__( 'Allow <a href="%s">trackbacks and pingbacks</a>' ),
			__( 'https://wordpress.org/support/article/introduction-to-blogging/#comments' )
		);
		$field.= '</label>';
		$html .= Core\HTML::wrap( $field, 'field-wrap -checkbox' );

		$form_fields['comment_control'] = [
			'label' => _x( 'Comment Control', 'Field Label', 'geditorial-attachments' ), // cannot be empty, will be hidden via CSS
			'helps' => _x( 'Applies to the attachment page associated with this media.', 'Field Help', 'geditorial-attachments' ),
			'input' => 'html',
			'html'  => $html,

			'show_in_modal' => TRUE,
			'show_in_edit'  => FALSE, // already has a meta-box
		];

		return $form_fields;
	}

	/**
	 * Filters the attachment fields to be saved.
	 *
	 * @param array $post
	 * @param array $attachment
	 * @return array
	 */
	public function attachment_fields_to_save( $post, $attachment )
	{
		if ( ! empty( $attachment['comment_status'] ) )
			$post['comment_status'] = 'open' === $attachment['comment_status'] ? 'open' : 'closed';

		if ( ! empty( $attachment['ping_status'] ) )
			$post['ping_status'] = 'open' === $attachment['ping_status'] ? 'open' : 'closed';

		return $post;
	}

	/**
	 * Action: Fires once an attachment has been added.
	 * @hook `add_attachment`
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function add_attachment_auto_rotation( $post_id )
	{
		if ( ! $attachment = WordPress\Post::get( $post_id ) )
			return FALSE;

		if ( 'image/jpeg' !== $attachment->post_mime_type )
			return FALSE;

		$filepath = Core\URL::toPath( $attachment->guid );

		if ( ! Core\Image::rotationJPEG( $filepath ) )
			return FALSE;

		$metadata = wp_generate_attachment_metadata( $attachment->ID, $filepath );
		wp_update_attachment_metadata( $attachment->ID, $metadata );
	}

	private function get_prefix_permalink()
	{
		$prefix = $this->get_setting( 'prefix_permalink' );
		$prefix = $prefix ?: 'media';
		return ltrim( rtrim( $prefix, '/' ), '/' );
	}

	private function add_rewrite_rule()
	{
		add_rewrite_rule(
			'(.+)/'.$this->get_prefix_permalink().'/([0-9]{1,})/?$',
			'index.php?attachment_id=$matches[2]',
			'top'
		);
	}

	// @REF: https://wordpress.stackexchange.com/a/187817
	public function attachment_link( $link, $attachment_id )
	{
		if ( ! $attachment = WordPress\Post::get( $attachment_id ) )
			return $link;

		if ( empty( $attachment->post_parent ) )
			return $link;

		$prefix = $this->get_prefix_permalink();
		$parent = get_permalink( $attachment->post_parent );

		return Core\URL::untrail( $parent ).'/'.$prefix.'/'.$attachment_id;
	}

	// @REF: http://wpbeg.in/2yZXJ2n
	public function ajax_query_attachments_args( $query )
	{
		$user_id = get_current_user_id();

		if ( $user_id && ! current_user_can( 'edit_others_posts' ) )
			$query['author'] = $user_id;

		return $query;
	}

	public function adminbar_init( &$nodes, $parent )
	{
		if ( ! $post = $this->adminbar__check_singular_post( NULL, 'edit_post' ) )
			return;

		$node_id = $this->classs();

		if ( $attachments = WordPress\Attachment::list( $post, '' ) ) {

			$icon = $this->adminbar__get_icon();

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'title'  => $icon._x( 'Attachments Summary', 'Node: Title', 'geditorial-attachments' ),
				'href'   => WordPress\Post::mediaURL( $post ) ?: FALSE,
				'meta'   => [
					'class' => $this->adminbar__get_css_class(),
				],
			];

			$thumbnail_id  = get_post_meta( $post->ID, '_thumbnail_id', TRUE );
			$gtheme_images = get_post_meta( $post->ID, '_gtheme_images', TRUE );
			$gtheme_terms  = get_post_meta( $post->ID, '_gtheme_images_terms', TRUE );

			foreach ( $attachments as $attachment ) {

				$title = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE );

				if ( $thumbnail_id == $attachment->ID )
					$title.= ' &ndash; [thumbnail]';

				if ( $gtheme_images && in_array( $attachment->ID, $gtheme_images, TRUE ) )
					$title.= ' &ndash; [tagged: '.array_search( $attachment->ID, $gtheme_images ).']';

				if ( $gtheme_terms && in_array( $attachment->ID, $gtheme_terms, TRUE ) )
					$title.= ' &ndash; [for term: '.array_search( $attachment->ID, $gtheme_terms ).']';

				$nodes[] = [
					'parent' => $node_id,
					'id'     => $this->classs( 'attachment', $attachment->ID ),
					'title'  => $title,
					'href'   => wp_get_attachment_url( $attachment->ID ),
					'meta'   => [
						'dir'   => 'ltr',
						'title' => WordPress\Attachment::title( $attachment ),
						'class' => $this->adminbar__get_css_class( '-attachment' ),
					],
				];
			}

		} else if ( $upload = WordPress\Post::mediaUploadURL( $post ) ) {

			$icon = $this->adminbar__get_icon( 'upload' );

			$nodes[] = [
				'parent' => $parent,
				'id'     => $node_id,
				'title'  => $icon._x( 'Upload Attachments', 'Node: Title', 'geditorial-attachments' ),
				'href'   => $upload,
				'meta'   => [
					'class' => $this->adminbar__get_css_class(),
					'title' => sprintf(
						/* translators: `%s`: singular post-type label */
						_x( 'Upload Attachments for this %s.', 'Node: Title', 'geditorial-attachments' ),
						Services\CustomPostType::getLabel( $post, 'singular_name' )
					),
				],
			];
		}
	}

	public function tweaks_column_attr( $post, $before, $after )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$this->_render_summary_row( $post, $before, $after );
	}

	public function pointers_post( $post, $before, $after, $context, $screen )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return;

		$this->_render_summary_row( $post, $before, $after );
	}

	private function _render_summary_row( $post, $before, $after )
	{
		$attachments = WordPress\Attachment::list( $post->ID, '' );

		if ( ! $count = count( $attachments ) )
			return;

		$extensions = wp_get_mime_types();
		$mime_types = array_unique( Core\Arraay::pluck( $attachments, 'post_mime_type' ) );

		printf( $before, '-attachment-count' );

			echo $this->get_column_icon( FALSE, 'images-alt2', _x( 'Attachments', 'Row Icon Title', 'geditorial-attachments' ) );

			$title = sprintf(
				/* translators: `%s`: attachments count */
				_nx( '%s Attachment', '%s Attachments', $count, 'Noop', 'geditorial-attachments' ),
				Core\Number::format( $count )
			);

			if ( $media = WordPress\Post::mediaURL( $post ) )
				echo Core\HTML::tag( 'a', [
					'title'  => _x( 'View the list of attachments', 'Title Attr', 'geditorial-attachments' ),
					'href'   => $media,
					'target' => '_blank',
				], $title );
			else
				echo $title;

			if ( count( $mime_types ) ) {

				$list = [];

				foreach ( $mime_types as $mime_type )
					if ( $ext = WordPress\Media::getExtension( $mime_type, $extensions ) )
						$list[] = $ext;

				echo WordPress\Strings::getJoined( $list, ' <span class="-mime-types">(', ')</span>' );
			}

		echo $after;
	}

	public function get_attachment_image_attributes( $attr, $attachment, $size )
	{
		if ( is_array( $attr ) && array_key_exists( 'alt', $attr ) && '' == $attr['alt'] )
			$attr['alt'] = get_the_title( $attachment );

		return $attr;
	}

	public function main_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return gEditorial\ShortCode::listPosts( 'attached',
			'attachment',
			'',
			array_merge( [
				'post_id'       => NULL,
				'title'         => _x( 'Attachments', 'Shortcode', 'geditorial-attachments' ),
				/* translators: `%s`: attachment parent title */
				'title_title'   => _x( 'Attachments of %s', 'Shortcode', 'geditorial-attachments' ),
				'title_anchor'  => 'attachments',
				'title_link'    => FALSE,
				'item_title'    => 'caption',
				'item_after_cb' => [ $this, 'main_shortcode_item_after_cb' ],
			], (array) $atts ),
			$content,
			$this->constant( 'main_shortcode', $tag ),
			$this->key
		);
	}

	// @SEE: `wp_prepare_attachment_for_js()`
	public function main_shortcode_item_after_cb( $post, $args, $item )
	{
		$html = '';

		// NOTE: usually caption is the same as attachment title
		if ( $this->get_setting( 'shortcode_caption' )
			&& ( $caption = wp_get_attachment_caption( $post->ID ) ) )
			$html.= sprintf( '<div class="-caption">%s</div>',
				WordPress\Strings::prepDescription( $caption ) );

		if ( $this->get_setting( 'shortcode_description', TRUE )
			&& ! empty( $post->post_content ) )
			$html.= sprintf( '<div class="-description">%s</div>',
				WordPress\Strings::prepDescription( $post->post_content ) );

		return $html;
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( gEditorial\Tablelist::isAction( 'delete_permanently', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( wp_delete_attachment( $post_id, TRUE ) )
							++$count;

					WordPress\Redirect::doReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );

				} else if ( gEditorial\Tablelist::isAction( 'empty_metadata', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( WordPress\Media::emptyAttachmentImageMeta( $post_id ) )
							++$count;

					WordPress\Redirect::doReferer( [
						'message' => 'emptied',
						'count'   => $count,
					] );

				} else if ( gEditorial\Tablelist::isAction( 'delete_sizes', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( WordPress\Media::deleteImageSizes( $post_id ) )
							++$count;

					WordPress\Redirect::doReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );

				} else {

					WordPress\Redirect::doReferer( 'huh' );
				}
			}

			gEditorial\Scripts::enqueueThickBox();
		}
	}

	// TODO: check and validate parent id for attachments
	protected function render_reports_html( $uri, $sub )
	{
		$query = $extra = [];
		$list  = $this->list_posttypes();

		list( $posts, $pagination ) = gEditorial\Tablelist::getPosts( $query, $extra, 'attachment', $this->get_sub_limit_option( $sub, 'reports' ) );

		// $pagination['before'][] = gEditorial\Tablelist::filterPostTypes( $list, 'type_parent' ); // FIXME: no support for parent type yet!
		$pagination['before'][] = gEditorial\Tablelist::filterAuthors( $list );
		$pagination['before'][] = gEditorial\Tablelist::filterSearch( $list );

		$pagination['actions'] = [
			'delete_permanently' => _x( 'Delete Permanently', 'Table Action', 'geditorial-attachments' ),
			'empty_metadata'     => _x( 'Empty Meta-data', 'Table Action', 'geditorial-attachments' ),
			'delete_sizes'       => _x( 'Delete Sizes', 'Table Action', 'geditorial-attachments' ),
			// TODO: bulk comment status: open/closed
			// TODO: bulk ping status: open/closed
		];

		$actions = [
			// FIXME: must add ajax
			// 'override-name' => Core\HTML::link( _x( 'Override Name', 'Table Action', 'geditorial-attachments' ) ),

			// rename filenames
			// move filenames
		];

		return Core\HTML::tableList( [
			'_cb'    => 'ID',
			'mime'   => gEditorial\Tablelist::columnPostMime(),
			'custom' => [
				'title'    => _x( 'Custom', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-custom',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( $custom = WordPress\Media::isCustom( $row->ID ) )
						return strtoupper( str_replace( '_', ' ', $custom ) );

					return gEditorial\Helper::htmlEmpty();
				},
			],
			'title'  => gEditorial\Tablelist::columnPostTitle( NULL, TRUE, $actions ),
			'search' => [
				'title'    => _x( 'Search', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-search -has-list',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {
					$list = [];

					if ( $row->post_parent )
						$list[] = sprintf(
							/* translators: `%s`: linked post title */
							_x( '[Parent]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							gEditorial\Helper::getPostTitleRow( $row->post_parent, 'view', TRUE, 'posttype' )
						);

					foreach ( WordPress\PostType::isThumbnail( $row->ID ) as $post_id )
						$list[] = sprintf(
							/* translators: `%s`: linked post title */
							_x( '[Thumb]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							gEditorial\Helper::getPostTitleRow( $post_id, 'view', TRUE, 'posttype' )
						);

					foreach ( WordPress\Taxonomy::isThumbnail( $row->ID ) as $term_id )
						$list[] = sprintf(
							/* translators: `%s`: linked term title */
							_x( '[Term]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							gEditorial\Helper::getTermTitleRow( $term_id, 'view', TRUE )
						);

					foreach ( WordPress\Media::searchAttachment( $row->ID ) as $post_id )
						$list[] = sprintf(
							/* translators: `%s`: linked post title */
							_x( '[Content]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							gEditorial\Helper::getPostTitleRow( $post_id, 'view', TRUE, 'posttype' )
						);

					return $list ? Core\HTML::rows( $list ) : gEditorial\Helper::htmlEmpty();
				},
			],
			'sizes' => [
				'title'    => _x( 'Sizes', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-sizes -has-table -has-table-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return gEditorial\Helper::htmlEmpty();

					$sizes = [];

					if ( ! empty( $meta['filesize'] ) )
						$sizes['FILESIZE'] = Core\File::formatSize( $meta['filesize'] );

					if ( ! empty( $meta['file'] ) && wp_attachment_is( 'image', $row->ID ) )
						$sizes['ORIGINAL'] = sprintf( '<span title="%s">%s&times;%s</span>', $meta['file'], $meta['width'] ?? '', $meta['height'] ?? '' );

					if ( ! empty( $meta['sizes'] ) )
						foreach ( $meta['sizes'] as $size_name => $size_args )
							$sizes[$size_name] = sprintf( '<span title="%s">%s&times;%s</span>', $size_args['file'], $size_args['width'], $size_args['height'] );

					if ( ! empty( $meta['original_image'] ) )
						$sizes['CONVERTED'] = Core\HTML::small( $meta['original_image'] );

					return Core\HTML::tableCode( $sizes, TRUE );
				},
			],

			'meta' => [
				'title'    => _x( 'Meta', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-meta -has-table -has-table-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return gEditorial\Helper::htmlEmpty();

					if ( isset( $meta['image_meta'] ) )
						return Core\HTML::tableCode( $meta['image_meta'] );

					if ( wp_attachment_is( 'audio', $row->ID )
						|| wp_attachment_is( 'video', $row->ID ) )
							return Core\HTML::tableCode( $meta );

					return gEditorial\Helper::htmlEmpty();
				},
			],

		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => Core\HTML::tag( 'h3', _x( 'Overview of Attachments', 'Header', 'geditorial-attachments' ) ),
			'empty'      => _x( 'No attachments found.', 'Message', 'geditorial-attachments' ),
			'pagination' => $pagination,
		] );
	}

	public function tools_settings( $sub )
	{
		$this->check_settings( $sub, 'tools', 'per_page' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		echo gEditorial\Settings::toolboxColumnOpen( _x( 'Attachment Tools', 'Header', 'geditorial-attachments' ) );

		$available = FALSE;
		$posttypes = $this->list_posttypes();
		$mimetypes = WordPress\Media::availableMIMETypes( 'attachment' );

		if ( count( $posttypes ) ) {

			ModuleSettings::renderCard_reattach_thumbnails( $posttypes );
			ModuleSettings::renderCard_empty_raw_metadata( $posttypes );

			$available = TRUE;
		}

		if ( count( $mimetypes ) ) {

			ModuleSettings::renderCard_deletion_by_mime( $mimetypes, wp_get_mime_types() );

			$available = TRUE;
		}

		if ( ! $available )
			gEditorial\Info::renderNoToolsAvailable();

		echo '</div>';
	}

	protected function render_tools_html_before( $uri, $sub )
	{
		if ( $this->_do_tool_reattach_thumbnails( $sub ) )
			return FALSE; // avoid further UI

		if ( $this->_do_tool_empty_raw_metadata( $sub ) )
			return FALSE; // avoid further UI

		if ( $this->_do_tool_deletion_by_mime( $sub ) )
			return FALSE; // avoid further UI
	}

	private function _do_tool_deletion_by_mime( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_DELETION_BY_MIME ) )
			return FALSE;

		if ( ! $mimetype = self::req( 'mime' ) )
			return gEditorial\Info::renderEmptyMIMEtype();

		$this->raise_resources();

		return ModuleSettings::handleTool_deletion_by_mime(
			$mimetype,
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}

	private function _do_tool_empty_raw_metadata( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_EMPTY_RAW_METADATA ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleTool_empty_raw_metadata(
			$posttype,
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}

	private function _do_tool_reattach_thumbnails( $sub )
	{
		if ( ! self::do( ModuleSettings::ACTION_REATTACH_THUMBNAILS ) )
			return FALSE;

		if ( ! $posttype = self::req( 'type' ) )
			return ! gEditorial\Info::renderEmptyPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		if ( ! $this->posttype_supported( $posttype ) )
			return ! gEditorial\Info::renderNotSupportedPosttype(
				ModuleSettings::processingErrorOpen(), '</div></div>' );

		$this->raise_resources();

		return ModuleSettings::handleTool_reattach_thumbnails(
			$posttype,
			$this->get_sub_limit_option( $sub, 'tools' )
		);
	}
}

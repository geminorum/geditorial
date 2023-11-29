<?php namespace geminorum\gEditorial\Modules\Attachments;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Attachments extends gEditorial\Module
{
	use Internals\CoreRestrictPosts;

	public static function module()
	{
		return [
			'name'   => 'attachments',
			'title'  => _x( 'Attachments', 'Modules: Attachments', 'geditorial-admin' ),
			'desc'   => _x( 'Attachment Management', 'Modules: Attachments', 'geditorial-admin' ),
			'icon'   => 'paperclip',
			'access' => 'stable',
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
					'field_class' => [ 'medium-text', 'code' ],
					'placeholder' => 'media',
					'dir'         => 'ltr',
				],
				[
					'field'       => 'fallback_alt_to_title',
					'title'       => _x( 'Fallback Alt to Title', 'Setting Title', 'geditorial-attachments' ),
					'description' => _x( 'Tries to fill empty alt attribute with attachment title on images.', 'Setting Description', 'geditorial-attachments' ),
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
			'_supports' => [
				'shortcode_support',
			],
		];
	}

	protected function get_global_constants()
	{
		return [
			'attachments_shortcode' => 'attachments',
		];
	}

	public function init()
	{
		parent::init();

		if ( $this->get_setting( 'rewrite_permalink' ) ) {
			$this->add_rewrite_rule();
			$this->filter( 'attachment_link', 2, 20 );
		}

		$this->register_shortcode( 'attachments_shortcode' );

		if ( is_admin() )
			return;

		if ( $this->get_setting( 'fallback_alt_to_title' ) )
			$this->filter( 'wp_get_attachment_image_attributes', 3, 8 );
	}

	public function setup_ajax()
	{
		if ( $this->get_setting( 'restrict_library' ) )
			$this->filter( 'ajax_query_attachments_args' );
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base && $this->posttype_supported( $screen->post_type ) ) {

			if ( $this->get_setting( 'attachment_count' ) )
				$this->action_module( 'tweaks', 'column_attr', 1, 20 );

		} else if ( 'upload' == $screen->base ) {
			$this->corerestrictposts__hook_screen_authors();
		}
	}

	private function get_prefix_permalink()
	{
		$prefix = $this->get_setting( 'prefix_permalink' );
		$prefix = $prefix ?: 'media';
		return ltrim( rtrim( $prefix, '/' ), '/' );
	}

	// TODO: add custom endpoint like `thumbnail` or `cover` for posttype thumbnail
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
		if ( is_admin() || ! is_singular( $this->posttypes() ) )
			return;

		$post_id = get_queried_object_id();

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$attachments = WordPress\Media::getAttachments( $post_id, '' );

		if ( empty( $attachments ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Attachment Summary', 'Adminbar', 'geditorial-attachments' ),
			'parent' => $parent,
			'href'   => Core\WordPress::getPostAttachmentsLink( $post_id ),
		];

		$thumbnail_id  = get_post_meta( $post_id, '_thumbnail_id', TRUE );
		$gtheme_images = get_post_meta( $post_id, '_gtheme_images', TRUE );
		$gtheme_terms  = get_post_meta( $post_id, '_gtheme_images_terms', TRUE );

		foreach ( $attachments as $attachment ) {

			$title = get_post_meta( $attachment->ID, '_wp_attached_file', TRUE );

			if ( $thumbnail_id == $attachment->ID )
				$title.= ' &ndash; <b>thumbnail</b>';

			if ( $gtheme_images && in_array( $attachment->ID, $gtheme_images ) )
				$title.= ' &ndash; tagged: '.array_search( $attachment->ID, $gtheme_images );

			if ( $gtheme_terms && in_array( $attachment->ID, $gtheme_terms ) )
				$title.= ' &ndash; for term: '.array_search( $attachment->ID, $gtheme_terms );

			$nodes[] = [
				'id'     => $this->classs( 'attachment', $attachment->ID ),
				'title'  => '<div dir="ltr" style="text-align:left">'.$title.'</div>',
				'parent' => $this->classs(),
				'href'   => wp_get_attachment_url( $attachment->ID ),
			];

			// TODO: add submenu for: title/caption/description/sizes
		}
	}

	public function tweaks_column_attr( $post )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$attachments = WordPress\Media::getAttachments( $post->ID, '' );

		if ( ! $count = count( $attachments ) )
			return;

		$extensions = wp_get_mime_types();
		$mime_types = array_unique( Core\Arraay::pluck( $attachments, 'post_mime_type' ) );

		echo '<li class="-row tweaks-attachment-count">';

			echo $this->get_column_icon( FALSE, 'images-alt2', _x( 'Attachments', 'Row Icon Title', 'geditorial-attachments' ) );

			/* translators: %s: attachments count */
			$title = sprintf( _nx( '%s Attachment', '%s Attachments', $count, 'Noop', 'geditorial-attachments' ), Core\Number::format( $count ) );

			if ( current_user_can( 'upload_files' ) )
				echo Core\HTML::tag( 'a', [
					'href'   => Core\WordPress::getPostAttachmentsLink( $post->ID ),
					'title'  => _x( 'View the list of attachments', 'Title Attr', 'geditorial-attachments' ),
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

		echo '</li>';
	}

	public function wp_get_attachment_image_attributes( $attr, $attachment, $size )
	{
		if ( is_array( $attr ) && array_key_exists( 'alt', $attr ) && '' == $attr['alt'] )
			$attr['alt'] = get_the_title( $attachment );

		return $attr;
	}

	public function attachments_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts( 'attached',
			'attachment',
			'',
			array_merge( [
				'id'            => NULL,
				'title'         => _x( 'Attachments', 'Shortcode', 'geditorial-attachments' ),
				/* translators: %s: attachment parent title */
				'title_title'   => _x( 'Attachments of %s', 'Shortcode', 'geditorial-attachments' ),
				'title_anchor'  => 'attachments',
				'title_link'    => FALSE,
				'item_after_cb' => [ $this, 'attachments_shortcode_item_after_cb' ],
			], (array) $atts ),
			$content,
			$this->constant( 'attachments_shortcode', $tag ),
			$this->key
		);
	}

	// @SEE: `wp_prepare_attachment_for_js()`
	public function attachments_shortcode_item_after_cb( $post, $args, $item )
	{
		$html = '';

		// TODO: default enabled on settings
		if ( $caption = wp_get_attachment_caption( $post->ID ) )
			$html.= sprintf( '<div class="-caption">%s</div>', Helper::prepDescription( $caption ) );

		// TODO: default enabled on settings
		if ( ! empty( $post->post_content ) )
			$html.= sprintf( '<div class="-description">%s</div>', Helper::prepDescription( $post->post_content ) );

		return $html;
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports', 'per_page' ) ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'reports', $sub );

				if ( Tablelist::isAction( 'delete_permanently', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( wp_delete_attachment( $post_id, TRUE ) )
							$count++;

					Core\WordPress::redirectReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'empty_metadata', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( WordPress\Media::emptyAttachmentImageMeta( $post_id ) )
							$count++;

					Core\WordPress::redirectReferer( [
						'message' => 'emptied',
						'count'   => $count,
					] );

				} else if ( Tablelist::isAction( 'remove_thumbnails', TRUE ) ) {

					$count = 0;

					foreach ( $_POST['_cb'] as $post_id )
						if ( WordPress\Media::deleteAttachmentThumbnails( $post_id ) )
							$count++;

					Core\WordPress::redirectReferer( [
						'message' => 'deleted',
						'count'   => $count,
					] );
				}
			}

			Scripts::enqueueThickBox();
		}
	}

	// TODO: check and validate parent id for attachments
	protected function render_reports_html( $uri, $sub )
	{
		$query = $extra = [];
		$list  = $this->list_posttypes();

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, 'attachment', $this->get_sub_limit_option( $sub ) );

		// $pagination['before'][] = Tablelist::filterPostTypes( $list, 'type_parent' ); // FIXME: no support for parent type yet!
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		$pagination['actions'] = [
			'delete_permanently' => _x( 'Delete Permanently', 'Table Action', 'geditorial-attachments' ),
			'empty_metadata'     => _x( 'Empty Meta-data', 'Table Action', 'geditorial-attachments' ),
			'remove_thumbnails'  => _x( 'Remove Thumbnails', 'Table Action', 'geditorial-attachments' ),
		];

		$actions = [
			// FIXME: must add ajax
			// 'override-name' => Core\HTML::link( _x( 'Override Name', 'Table Action', 'geditorial-attachments' ) ),

			// rename filenames
			// move filenames
		];

		return Core\HTML::tableList( [
			'_cb'    => 'ID',
			'ID'     => Tablelist::columnPostID(),
			'date'   => Tablelist::columnPostDate(),
			'mime'   => Tablelist::columnPostMime(),
			'custom' => [
				'title'    => _x( 'Custom', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-custom',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( $custom = WordPress\Media::isCustom( $row->ID ) )
						return strtoupper( str_replace( '_', ' ', $custom ) );

					return Helper::htmlEmpty();
				},
			],
			'title'  => Tablelist::columnPostTitle( NULL, TRUE, $actions ),
			'search' => [
				'title'    => _x( 'Search', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-search -has-list',
				'callback' => function ( $value, $row, $column, $index, $key, $args ) {
					$list = [];

					if ( $row->post_parent )
						/* translators: %s: linked post title */
						$list[] = sprintf( _x( '[Parent]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							Helper::getPostTitleRow( $row->post_parent, 'view', TRUE, 'posttype' ) );

					foreach ( WordPress\PostType::isThumbnail( $row->ID ) as $post_id )
						/* translators: %s: linked post title */
						$list[] = sprintf( _x( '[Thumb]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							Helper::getPostTitleRow( $post_id, 'view', TRUE, 'posttype' ) );

					foreach ( WordPress\Taxonomy::isThumbnail( $row->ID ) as $term_id )
						/* translators: %s: linked term title */
						$list[] = sprintf( _x( '[Term]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							Helper::getTermTitleRow( $term_id, 'view', TRUE ) );

					foreach ( $this->search_attachment( $row->ID ) as $post_id )
						/* translators: %s: linked post title */
						$list[] = sprintf( _x( '[Content]: %s', 'Search Result Prefix', 'geditorial-attachments' ),
							Helper::getPostTitleRow( $post_id, 'view', TRUE, 'posttype' ) );

					return $list ? Core\HTML::renderList( $list ) : Helper::htmlEmpty();
				},
			],
			'sizes' => [
				'title'    => _x( 'Sizes', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-sizes -has-table -has-table-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return Helper::htmlEmpty();
					$sizes = [];

					if ( ! empty( $meta['filesize'] ) )
						$sizes['FILESIZE'] = Core\File::formatSize( $meta['filesize'] );

					if ( wp_attachment_is( 'image', $row->ID ) )
						$sizes['ORIGINAL'] = sprintf( '<span title="%s">%s&times;%s</span>', $meta['file'], $meta['width'], $meta['height'] );

					if ( ! empty( $meta['sizes'] ) )
						foreach ( $meta['sizes'] as $size_name => $size_args )
							$sizes[$size_name] = sprintf( '<span title="%s">%s&times;%s</span>', $size_args['file'], $size_args['width'], $size_args['height'] );

					return Core\HTML::tableCode( $sizes, TRUE );
				},
			],

			'meta' => [
				'title'    => _x( 'Meta', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-meta -has-table -has-table-ltr',
				'callback' => static function ( $value, $row, $column, $index, $key, $args ) {

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return Helper::htmlEmpty();

					if ( isset( $meta['image_meta'] ) )
						return Core\HTML::tableCode( $meta['image_meta'] );

					if ( wp_attachment_is( 'audio', $row->ID )
						|| wp_attachment_is( 'video', $row->ID ) )
							return Core\HTML::tableCode( $meta );

					return Helper::htmlEmpty();
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

	// searches only for portion of the attached file
	// like: `2021/10/filename` where `filename.ext` is the filename
	// FIXME: move up
	public function search_attachment( $attachment_id )
	{
		if ( ! $file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE ) )
			return [];

		$filetype = wp_check_filetype( Core\File::basename( $file ) );
		$pathfile = Core\File::join( dirname( $file ), Core\File::basename( $file, '.'.$filetype['ext'] ) );

		return WordPress\PostType::getIDsBySearch( $pathfile );
	}
}

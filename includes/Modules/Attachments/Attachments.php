<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;

class Attachments extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'attachments',
			'title' => _x( 'Attachments', 'Modules: Attachments', 'geditorial' ),
			'desc'  => _x( 'Attachment Management', 'Modules: Attachments', 'geditorial' ),
			'icon'  => 'paperclip',
		];
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
	}

	public function init_ajax()
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

			if ( $this->get_setting( 'admin_restrict', FALSE ) )
				$this->action( 'restrict_manage_posts', 2, 12 );
		}
	}

	public function restrict_manage_posts( $posttype, $which )
	{
		$this->do_restrict_manage_posts_authors( $posttype );
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
		if ( ! $attachment = get_post( $attachment_id ) )
			return $link;

		if ( empty( $attachment->post_parent ) )
			return $link;

		$prefix = $this->get_prefix_permalink();
		$parent = get_permalink( $attachment->post_parent );

		return URL::untrail( $parent ).'/'.$prefix.'/'.$attachment_id;
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

		$attachments = Media::getAttachments( $post_id, '' );

		if ( empty( $attachments ) )
			return;

		$nodes[] = [
			'id'     => $this->classs(),
			'title'  => _x( 'Attachment Summary', 'Adminbar', 'geditorial-attachments' ),
			'parent' => $parent,
			'href'   => WordPress::getPostAttachmentsLink( $post_id ),
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
		}
	}

	public function tweaks_column_attr( $post )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$attachments = Media::getAttachments( $post->ID, '' );

		if ( ! $count = count( $attachments ) )
			return;

		$extensions = wp_get_mime_types();
		$mime_types = array_unique( array_map( function( $r ){
			return $r->post_mime_type;
		}, $attachments ) );

		echo '<li class="-row tweaks-attachment-count">';

			echo $this->get_column_icon( FALSE, 'images-alt2', _x( 'Attachments', 'Row Icon Title', 'geditorial-attachments' ) );

			/* translators: %s: attachments count */
			$title = sprintf( _nx( '%s Attachment', '%s Attachments', $count, 'Noop', 'geditorial-attachments' ), Number::format( $count ) );

			if ( current_user_can( 'upload_files' ) )
				echo HTML::tag( 'a', [
					'href'   => WordPress::getPostAttachmentsLink( $post->ID ),
					'title'  => _x( 'View the list of attachments', 'Title Attr', 'geditorial-attachments' ),
					'target' => '_blank',
				], $title );
			else
				echo $title;

			if ( count( $mime_types ) ) {

				$list = [];

				foreach ( $mime_types as $mime_type )
					if ( $ext = Helper::getExtension( $mime_type, $extensions ) )
						$list[] = $ext;

				echo Helper::getJoined( $list, ' <span class="-mime-types">(', ')</span>' );
			}

		echo '</li>';
	}

	public function attachments_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		return ShortCode::listPosts(
			'attached',
			'attachment',
			'',
			array_merge( [
				'title'        => _x( 'Attachments', 'Shortcode', 'geditorial-attachments' ),
				/* translators: %s: attachment parent title */
				'title_title'  => _x( 'Attachments of %s', 'Shortcode', 'geditorial-attachments' ),
				'title_anchor' => 'attachments',
				'title_link'   => FALSE,
			], (array) $atts ),
			$content,
			$this->constant( 'attachments_shortcode' )
		);
	}

	public function reports_settings( $sub )
	{
		if ( $this->check_settings( $sub, 'reports' ) ) {
			Scripts::enqueueThickBox();
			$this->add_sub_screen_option( $sub );
		}
	}

	protected function render_reports_html( $uri, $sub )
	{
		$query = $extra = [];
		$list  = $this->list_posttypes();

		list( $posts, $pagination ) = Tablelist::getPosts( $query, $extra, 'attachment', $this->get_sub_limit_option( $sub ) );

		// $pagination['before'][] = Tablelist::filterPostTypes( $list, 'type_parent' ); // FIXME: no support for parent type yet!
		$pagination['before'][] = Tablelist::filterAuthors( $list );
		$pagination['before'][] = Tablelist::filterSearch( $list );

		$actions = [
			// FIXME: must add ajax
			// 'override-name' => HTML::link( _x( 'Override Name', 'Table Action', 'geditorial-attachments' ) ),

			// empty meta
			// remove thumbnails
			// rename filenames
			// move filenames
		];

		return HTML::tableList( [
			'_cb'    => 'ID',
			'ID'     => Tablelist::columnPostID(),
			'date'   => Tablelist::columnPostDate(),
			'mime'   => Tablelist::columnPostMime(),
			'custom' => [
				'title'    => _x( 'Custom', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-custom',
				'callback' => function( $value, $row, $column, $index ){
					if ( $custom = Media::isCustom( $row->ID ) )
						return strtoupper( str_replace( '_', ' ', $custom ) );

					return Helper::htmlEmpty();
				},
			],
			'title'  => Tablelist::columnPostTitle( NULL, TRUE, $actions ),
			'search' => [
				'title'    => _x( 'Search', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-search -has-list',
				'callback' => function( $value, $row, $column, $index ){
					$list = [];

					foreach ( $this->search_attachment( $row->ID ) as $post_id )
						$list[] = Helper::getPostTitleRow( $post_id );

					return $list ? HTML::renderList( $list ) : Helper::htmlEmpty();
				},
			],
			'sizes' => [
				'title'    => _x( 'Sizes', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-sizes -has-table -has-table-ltr',
				'callback' => function( $value, $row, $column, $index ){

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return Helper::htmlEmpty();

					$sizes = [];

					if ( wp_attachment_is( 'image', $row->ID ) )
						$sizes['ORIGINAL'] = sprintf( '%s&times;%s', $meta['width'], $meta['height'] );

					if ( ! empty( $meta['sizes'] ) )
						foreach ( $meta['sizes'] as $size => $args )
							$sizes[$size] = sprintf( '%s&times;%s', $args['width'], $args['height'] );

					return HTML::tableCode( $sizes, TRUE );
				},
			],
			'meta' => [
				'title'    => _x( 'Meta', 'Table Column', 'geditorial-attachments' ),
				'class'    => '-attachment-meta -has-table -has-table-ltr',
				'callback' => function( $value, $row, $column, $index ){

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return Helper::htmlEmpty();

					if ( wp_attachment_is( 'audio', $row->ID ) )
						return HTML::tableCode( $meta );

					if ( isset( $meta['image_meta'] ) )
						return HTML::tableCode( $meta['image_meta'] );

					return Helper::htmlEmpty();
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Attachments', 'Header', 'geditorial-attachments' ) ),
			'empty'      => _x( 'No attachments found.', 'Message', 'geditorial-attachments' ),
			'pagination' => $pagination,
		] );
	}

	public function search_attachment( $attachment_id )
	{
		if ( ! $file = get_post_meta( $attachment_id, '_wp_attached_file', TRUE ) )
			return [];

		$filetype = wp_check_filetype( File::basename( $file ) );
		$pathfile = File::join( dirname( $file ), File::basename( $file, '.'.$filetype['ext'] ) );

		return PostType::getIDsBySearch( $pathfile );
	}
}

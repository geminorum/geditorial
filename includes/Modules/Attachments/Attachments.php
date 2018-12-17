<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;

class Attachments extends gEditorial\Module
{

	protected $disable_no_posttypes = TRUE;

	public static function module()
	{
		return [
			'name'  => 'attachments',
			'title' => _x( 'Attachments', 'Modules: Attachments', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Attachment Management', 'Modules: Attachments', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'paperclip',
		];
	}

	protected function get_global_settings()
	{
		return [
			'_frontend' => [
				'adminbar_summary',
			],
			'_editlist' => [
				[
					'field'       => 'attachment_count',
					'title'       => _x( 'Attachment Count', 'Modules: Attachments: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays attachment summary of the post.', 'Modules: Attachments: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'restrict_library',
					'title'       => _x( 'Restrict Library', 'Modules: Attachments: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Restricts Media Library access to user’s own uploads.', 'Modules: Attachments: Setting Description', GEDITORIAL_TEXTDOMAIN ),
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

		$this->register_shortcode( 'attachments_shortcode' );
	}

	public function init_ajax()
	{
		if ( $this->get_setting( 'restrict_library' ) )
			$this->filter( 'ajax_query_attachments_args' );
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base && in_array( $screen->post_type, $this->posttypes() ) ) {

			$this->action_module( 'tweaks', 'column_attr', 1, 20 );
		}
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
			'title'  => _x( 'Attachment Summary', 'Modules: Attachments: Adminbar', GEDITORIAL_TEXTDOMAIN ),
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

			echo $this->get_column_icon( FALSE, 'images-alt2', _x( 'Attachments', 'Modules: Attachments: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

			$title = sprintf( _nx( '%s Attachment', '%s Attachments', $count, 'Modules: Attachments', GEDITORIAL_TEXTDOMAIN ), Number::format( $count ) );

			if ( current_user_can( 'upload_files' ) )
				echo HTML::tag( 'a', [
					'href'   => WordPress::getPostAttachmentsLink( $post->ID ),
					'title'  => _x( 'View the list of attachments', 'Modules: Attachments', GEDITORIAL_TEXTDOMAIN ),
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
				'title'        => _x( 'Attachments', 'Modules: Attachments: Shortcode', GEDITORIAL_TEXTDOMAIN ),
				'title_title'  => _x( 'Attachments of %s', 'Modules: Attachments: Shortcode', GEDITORIAL_TEXTDOMAIN ),
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
			add_thickbox();
			$this->screen_option( $sub );
		}
	}

	public function reports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'reports', FALSE );

			$this->tableSummary();

		$this->render_form_end( $uri, $sub );
	}

	private function tableSummary()
	{
		$query = $extra = [];

		list( $posts, $pagination ) = $this->getTablePosts( $query, $extra, 'attachment' );

		// $pagination['before'][] = Helper::tableFilterPostTypes( $this->list_posttypes(), 'type_parent' );

		$custom = [
			// FIXME: must add ajax
			// 'override-name' => HTML::link( _x( 'Override Name', 'Modules: Attachments: Table Action', GEDITORIAL_TEXTDOMAIN ) ),
		];

		return HTML::tableList( [
			'_cb'    => 'ID',
			'ID'     => Helper::tableColumnPostID(),
			'date'   => Helper::tableColumnPostDate(),
			'mime'   => Helper::tableColumnPostMime(),
			'custom' => [
				'title'    => _x( 'Custom', 'Modules: Attachments: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'class'    => '-attachment-custom',
				'callback' => function( $value, $row, $column, $index ){
					if ( $custom = Media::isCustom( $row->ID ) )
						return strtoupper( str_replace( '_', ' ', $custom ) );

					return '&mdash;';
				},
			],
			'title' => Helper::tableColumnPostTitle( NULL, TRUE, $custom ),
			'sizes' => [
				'title'    => _x( 'Sizes', 'Modules: Attachments: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'class'    => '-attachment-sizes -has-table -has-table-ltr',
				'callback' => function( $value, $row, $column, $index ){

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return '&mdash;';

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
				'title'    => _x( 'Meta', 'Modules: Attachments: Table Column', GEDITORIAL_TEXTDOMAIN ),
				'class'    => '-attachment-meta -has-table -has-table-ltr',
				'callback' => function( $value, $row, $column, $index ){

					if ( ! $meta = wp_get_attachment_metadata( $row->ID ) )
						return '&mdash;';

					if ( wp_attachment_is( 'audio', $row->ID ) )
						return HTML::tableCode( $meta );

					if ( isset( $meta['image_meta'] ) )
						return HTML::tableCode( $meta['image_meta'] );

					return '&mdash;';
				},
			],
		], $posts, [
			'navigation' => 'before',
			'search'     => 'before',
			'title'      => HTML::tag( 'h3', _x( 'Overview of Attachments', 'Modules: Attachments', GEDITORIAL_TEXTDOMAIN ) ),
			'empty'      => Helper::tableArgEmptyPosts(),
			'pagination' => $pagination,
		] );
	}
}
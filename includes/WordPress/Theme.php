<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class Theme extends Core\Base
{

	// core duplicate with extra action/filter hooks
	// @REF: `get_template_part()`
	public static function getPart( $slug, $name = NULL, $locate = TRUE )
	{
		$templates = array();
		$name      = (string) $name;

		if ( '' !== $name )
			$templates[] = "{$slug}-{$name}.php";

		$templates[] = "{$slug}.php";

		$templates = apply_filters( 'get_template_part', $templates, $slug, $name );

		if ( ! $locate )
			return $templates;

		do_action( "get_template_part_{$slug}", $slug, $name, $templates );

		locate_template( $templates, TRUE, FALSE );

		do_action( "get_template_part_{$slug}_after", $slug, $name, $templates );
	}

	public static function restPost( $data, $setup = FALSE, $network = FALSE )
	{
		$dummy = array(
			'ID'                    => -9999,
			'post_status'           => $data->status,
			'post_author'           => $network ? $data->author : 0,
			'post_parent'           => 0,
			'post_type'             => $data->type,
			'post_date'             => date( 'Y-m-d H:i:s', strtotime( $data->date ) ),
			'post_date_gmt'         => gmdate( 'Y-m-d H:i:s', strtotime( $data->date_gmt ) ),
			'post_modified'         => date( 'Y-m-d H:i:s', strtotime( $data->modified ) ),
			'post_modified_gmt'     => gmdate( 'Y-m-d H:i:s', strtotime( $data->modified_gmt ) ),
			'post_content'          => $data->content->rendered,
			'post_title'            => str_replace( '&nbsp;', ' ', $data->title->rendered ),
			'post_excerpt'          => $data->excerpt->rendered,
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => $data->slug,
			'guid'                  => $data->guid->rendered,
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => isset( $data->ping_status ) ? $data->ping_status : 'closed',
			'comment_status'        => isset( $data->comment_status ) ? $data->comment_status : 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',

			// extra
			'link' => $data->link,
		);

		$post = new \WP_Post( (object) $dummy );

		if ( $setup ) {
			$GLOBALS['post'] = $post;
			setup_postdata( $post );
		}

		return $post;
	}

	// add_filter( 'post_link', [ '\geminorum\\gEditorial\\WordPress\\Theme', 'restPost_permalink' ], 1 );
	// remove_filter( 'post_link', [ '\geminorum\\gEditorial\\WordPress\\Theme', 'restPost_permalink' ], 1 );
	public static function restPost_permalink( $permalink )
	{
		return $GLOBALS['post']->link;
	}

	// @SOURCE: `bp_set_theme_compat_active()`
	public static function compatActive( $set = TRUE )
	{
		global $gEditorialWPThemeCompatActive;
		return $gEditorialWPThemeCompatActive = $set;
	}

	// @SOURCE: `bp_theme_compat_reset_post()`
	public static function resetQuery( $args = array(), $content_callback = FALSE )
	{
		global $wp_query, $post;

		// switch defaults if post is set.
		if ( isset( $wp_query->post ) ) {

			$dummy = self::atts( array(
				'ID'                    => $wp_query->post->ID,
				'post_status'           => $wp_query->post->post_status,
				'post_author'           => $wp_query->post->post_author,
				'post_parent'           => $wp_query->post->post_parent,
				'post_type'             => $wp_query->post->post_type,
				'post_date'             => $wp_query->post->post_date,
				'post_date_gmt'         => $wp_query->post->post_date_gmt,
				'post_modified'         => $wp_query->post->post_modified,
				'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
				'post_content'          => $wp_query->post->post_content,
				'post_title'            => $wp_query->post->post_title,
				'post_excerpt'          => $wp_query->post->post_excerpt,
				'post_content_filtered' => $wp_query->post->post_content_filtered,
				'post_mime_type'        => $wp_query->post->post_mime_type,
				'post_password'         => $wp_query->post->post_password,
				'post_name'             => $wp_query->post->post_name,
				'guid'                  => $wp_query->post->guid,
				'menu_order'            => $wp_query->post->menu_order,
				'pinged'                => $wp_query->post->pinged,
				'to_ping'               => $wp_query->post->to_ping,
				'ping_status'           => $wp_query->post->ping_status,
				'comment_status'        => $wp_query->post->comment_status,
				'comment_count'         => $wp_query->post->comment_count,
				'filter'                => $wp_query->post->filter,

				'is_404'     => FALSE,
				'is_page'    => FALSE,
				'is_single'  => FALSE,
				'is_archive' => FALSE,
				'is_tax'     => FALSE,
			), $args );

		} else {

			$dummy = self::atts( array(
				'ID'                    => -9999,
				'post_status'           => 'publish',
				'post_author'           => 0,
				'post_parent'           => 0,
				'post_type'             => 'page',
				'post_date'             => 0,
				'post_date_gmt'         => 0,
				'post_modified'         => 0,
				'post_modified_gmt'     => 0,
				'post_content'          => '',
				'post_title'            => '',
				'post_excerpt'          => '',
				'post_content_filtered' => '',
				'post_mime_type'        => '',
				'post_password'         => '',
				'post_name'             => '',
				'guid'                  => '',
				'menu_order'            => 0,
				'pinged'                => '',
				'to_ping'               => '',
				'ping_status'           => '',
				'comment_status'        => 'closed',
				'comment_count'         => 0,
				'filter'                => 'raw',

				'is_404'     => FALSE,
				'is_page'    => FALSE,
				'is_single'  => FALSE,
				'is_archive' => FALSE,
				'is_tax'     => FALSE,
			), $args );
		}

		// set the $post global.
		$post = new \WP_Post( (object) $dummy );

		// copy the new post global into the main $wp_query.
		$wp_query->post  = $post;
		$wp_query->posts = array( $post );

		// prevent comments form from appearing.
		$wp_query->post_count = 1;
		$wp_query->is_404     = $dummy['is_404'];
		$wp_query->is_page    = $dummy['is_page'];
		$wp_query->is_single  = $dummy['is_single'];
		$wp_query->is_archive = $dummy['is_archive'];
		$wp_query->is_tax     = $dummy['is_tax'];

		unset( $dummy );

		// force the header back to 200 status if not a deliberate 404.
		// @REF: https://bbpress.trac.wordpress.org/ticket/1973
		if ( ! $wp_query->is_404() )
			status_header( 200 );

		// if we are resetting a post, we are in theme compat.
		self::compatActive( TRUE );

		if ( $content_callback && is_callable( $content_callback ) )
			add_filter( 'the_content', $content_callback );

		// if we are in theme compat, we don't need the 'Edit' post link.
		add_filter( 'get_edit_post_link', function( $edit_link = '', $post_id = 0 ){
			return 0 === $post_id ? FALSE : $edit_link;
		}, 10, 2 );
	}
}

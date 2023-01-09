<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class ShortCode extends Main
{

	const BASE = 'geditorial';

	public static function wrap( $html, $suffix = FALSE, $args = [], $block = TRUE, $extra = [] )
	{
		if ( is_null( $html ) )
			return $html;

		$before = empty( $args['before'] ) ? '' : $args['before'];
		$after  = empty( $args['after'] )  ? '' : $args['after'];

		if ( empty( $args['wrap'] ) )
			return $before.$html.$after;

		$classes = [ '-wrap', static::BASE.'-wrap-shortcode' ];
		$wrap    = TRUE === $args['wrap'] ? ( $block ? 'div' : 'span' ) : $args['wrap'];

		if ( $suffix )
			$classes[] = 'shortcode-'.$suffix;

		if ( isset( $args['context'] ) && $args['context'] )
			$classes[] = 'context-'.$args['context'];

		if ( ! empty( $args['class'] ) )
			$classes = HTML::attrClass( $classes, $args['class'] );

		if ( $after )
			return $before.HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $html ).$after;

		return HTML::tag( $wrap, array_merge( [ 'class' => $classes ], $extra ), $before.$html );
	}

	// term as title of the list
	public static function termTitle( $term_or_id, $taxonomy = 'category', $atts = [] )
	{
		// FIXME: WTF?!
		if ( is_array( $term_or_id ) )
			$term_or_id = $term_or_id[0];

		if ( ! $term = Taxonomy::getTerm( $term_or_id, $taxonomy ) )
			return '';

		$args = self::atts( [
			'title'          => NULL, // '<a href="%2$s">%1$s</a>', // FALSE to disable
			'title_cb'       => FALSE, // callback for title
			'title_link'     => NULL, // `anchor` for slug anchor, FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => 'term-%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_after'    => '', // '<div class="-desc">%3$s</div>',
		], $atts );

		$text = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
		$link = get_term_link( $term, $term->taxonomy );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $term, $atts, $text, $link ] );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $term, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( is_null( $args['title'] ) ) {

			$args['title'] = $text;

		} else if ( $args['title'] && Text::has( $args['title'], '%' ) ) {

			$args['title'] = sprintf( $args['title'],
				$text,
				$link,
				HTML::escape( trim( strip_tags( $term->description ) ) ),
				( $attr ?: '' )
			);

			if ( 'anchor' !== $args['title_link'] )
				$args['title_link'] = FALSE;
		}

		if ( $args['title'] ) {

			if ( is_null( $args['title_link'] ) && $term )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] && $term )
				$args['title'] = HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $term->term_id, $term->slug ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = HTML::tag( $args['title_tag'], [
				'id'    => $term ? sprintf( $args['title_anchor'], $term->term_id, $term->slug ) : FALSE,
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		if ( $args['title_after'] ) {

			if ( $term && Text::has( $args['title_after'], '%' ) )
				$args['title_after'] = sprintf( $args['title_after'],
					$text,
					$link,
					Helper::prepDescription( $term->description )
				);

			return $args['title'].$args['title_after'];
		}

		return $args['title'];
	}

	// term as an item on the list
	public static function termItem( $term, $atts = [], $before = '', $after = '', $fallback = '' )
	{
		if ( ! $term = Taxonomy::getTerm( $term ) )
			return $fallback;

		$args = self::atts( [
			'item_link'     => TRUE,
			'item_text'     => NULL, // callback or use %s for post title
			'item_wrap'     => '', // use %s for item title / or html tag
			'item_title'    => '', // use %s for post title
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => FALSE, // $term->taxonomy.'-%2$s',
			'item_class'    => '-item do-sincethen',
			'item_dummy'    => '<span class="-dummy"></span>',
			'item_after'    => '',
			'item_after_cb' => FALSE,
		], $atts );

		$text = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
		$link = get_term_link( $term );

		if ( is_null( $args['item_text'] ) )
			$title = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$title = call_user_func_array( $args['item_text'], [ $term, $args, $text ] );

		else if ( $args['item_text'] && Text::has( $args['item_text'], '%' ) )
			$title = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$title = $args['item_text'];

		else
			$title = ''; // FIXME: WTF: better to bail here!

		if ( $term->count && $args['item_link'] )
			$item = HTML::tag( 'a', [
				'href'  => $link,
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			], $title );

		else
			$item = HTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			], $title );

		if ( $args['item_wrap'] && Text::has( $args['item_wrap'], '%' ) )
			$item = sprintf( $args['item_wrap'], $item );

		else if ( $args['item_wrap'] )
			$item = HTML::tag( $args['item_wrap'], $item );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $term, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					HTML::escapeURL( $link ),
					Helper::prepDescription( $term->description ),
					Text::trimChars( $term->description )
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return HTML::tag( $args['item_tag'], [
			'id'    => $args['item_anchor'] ? sprintf( $args['item_anchor'], $term->term_id, $term->slug ) : FALSE,
			'class' => $args['item_class'],
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	// term as an image on the list
	public static function termImage( $term, $atts = [], $before = '', $after = '', $fallback = '' )
	{
		if ( ! $term = Taxonomy::getTerm( $term ) )
			return $fallback;

		$args = self::atts( [
			'item_link'           => TRUE,
			'item_text'           => NULL,                             // callback or use %s for term title
			'item_wrap'           => '',                               // use %s for item title / or html tag
			'item_title'          => '',                               // use %s for term title
			'item_title_cb'       => FALSE,
			'item_tag'            => 'li',
			'item_anchor'         => FALSE,                            // $term->taxonomy.'-%2$s',
			'item_class'          => '-item -tile do-sincethen',
			'item_dummy'          => '<span class="-dummy"></span>',
			'item_after'          => '',
			'item_after_cb'       => FALSE,
			'item_image_tile'     => NULL,
			'item_image_metakey'  => NULL,
			'item_image_size'     => NULL,
			'item_image_loading'  => 'lazy',                           // FALSE to disable
			'item_image_decoding' => 'async',                          // FALSE to disable
			'item_image_empty'    => FALSE,
		], $atts );

		if ( ! $image_id = Taxonomy::getThumbnailID( $term->term_id, $args['item_image_metakey'] ) )
			return $fallback;

		if ( is_null( $args['item_image_size'] ) )
			$args['item_image_size'] = Media::getAttachmentImageDefaultSize( NULL, $term->taxonomy );

		if ( ! $thumbnail_img = Media::htmlAttachmentSrc( $image_id, $args['item_image_size'], FALSE ) )
			return $fallback;

		$text = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
		$link = get_term_link( $term );

		if ( is_null( $args['item_text'] ) )
			$title = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$title = call_user_func_array( $args['item_text'], [ $term, $args, $text ] );

		else if ( $args['item_text'] && Text::has( $args['item_text'], '%' ) )
			$title = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$title = $args['item_text'];

		else
			$title = '';

		$image = HTML::tag( 'img', [
			'src'      => $thumbnail_img,
			'alt'      => Media::getAttachmentImageAlt( $image_id, $title ),
			'loading'  => $args['item_image_loading'],
			'decoding' => $args['item_image_decoding'],
		] );

		if ( $term->count && $args['item_link'] )
			$item = HTML::tag( 'a', [
				'href'  => $link,
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			], $image );

		else
			$item = HTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			], $image );

		if ( $args['item_wrap'] && Text::has( $args['item_wrap'], '%' ) )
			$item = sprintf( $args['item_wrap'], $item );

		else if ( $args['item_wrap'] )
			$item = HTML::tag( $args['item_wrap'], $item );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $term, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					HTML::escapeURL( $link ),
					Helper::prepDescription( $term->description ),
					Text::trimChars( $term->description )
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return HTML::tag( $args['item_tag'], [
			'id'    => $args['item_anchor'] ? sprintf( $args['item_anchor'], $term->term_id, $term->slug ) : FALSE,
			'class' => $args['item_class'],
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	// posttype as title of the list
	public static function posttypeTitle( $posttype = NULL, $atts = [] )
	{
		if ( ! $posttype = PostType::object( $posttype ) )
			return '';

		$args = self::atts( [
			'title'          => NULL, // FALSE to disable
			'title_cb'       => FALSE, // callback for title
			'title_link'     => NULL, // `anchor` for slug anchor, FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => '%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_label'    => 'all_items',
		], $atts );

		if ( 'posttype' === $args['title'] )
			$args['title'] = NULL; // reset

		$text = $posttype->labels->{$args['title_label']};
		$link = PostType::getArchiveLink( $posttype->name );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $posttype, $atts, $text, $link ] );

		if ( is_null( $args['title'] ) )
			$args['title'] = $text;

		else if ( $args['title'] && Text::has( $args['title'], '%' ) )
			$args['title'] = sprintf( $args['title'], ( $text ?: '' ) );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $posttype, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {

			if ( is_null( $args['title_link'] ) && $link && ! is_post_type_archive( $posttype->name ) )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $posttype->name, $posttype->rest_base ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = HTML::tag( $args['title_tag'], [
				'id'    => sprintf( $args['title_anchor'], $posttype->name, $posttype->rest_base ),
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		return $args['title'];
	}

	// taxonomy as title of the list
	public static function taxonomyTitle( $taxonomy = NULL, $atts = [] )
	{
		if ( ! $taxonomy = Taxonomy::object( $taxonomy ) )
			return '';

		$args = self::atts( [
			'title'          => NULL, // FALSE to disable
			'title_cb'       => FALSE, // callback for title
			'title_link'     => NULL, // `anchor` for slug anchor, FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => '%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_label'    => 'all_items',
		], $atts );

		if ( 'taxonomy' === $args['title'] )
			$args['title'] = NULL; // reset

		$text = $taxonomy->labels->{$args['title_label']};
		$link = Taxonomy::getArchiveLink( $taxonomy->name );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $taxonomy, $atts, $text, $link ] );

		if ( is_null( $args['title'] ) )
			$args['title'] = $text;

		else if ( $args['title'] && Text::has( $args['title'], '%' ) )
			$args['title'] = sprintf( $args['title'], ( $text ?: '' ) );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $taxonomy, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {

			if ( is_null( $args['title_link'] ) && $link )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $taxonomy->name, $taxonomy->rest_base ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = HTML::tag( $args['title_tag'], [
				'id'    => sprintf( $args['title_anchor'], $taxonomy->name, $taxonomy->rest_base ),
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		return $args['title'];
	}

	public static function postTitle( $post = NULL, $atts = [] )
	{
		$args = self::atts( [
			'title'          => NULL, // FALSE to disable
			'title_cb'       => FALSE, // callback for title
			'title_link'     => NULL, // `anchor` for slug anchor, FALSE to disable
			'title_title'    => '',
			'title_title_cb' => FALSE, // callback for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => 'post-%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
		], $atts );

		$text = PostType::getPostTitle( $post, FALSE );
		$link = PostType::getPostLink( $post, '' );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $post, $atts, $text, $link ] );

		if ( is_null( $args['title'] ) )
			$args['title'] = $text;

		else if ( $args['title'] && Text::has( $args['title'], '%' ) )
			$args['title'] = sprintf( $args['title'], ( $text ?: '' ) );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $post, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {

			// only if post present, avoid linking to itself
			if ( is_null( $args['title_link'] ) && $link && $post )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] && $post )
				$args['title'] = HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $post->ID, $post->post_name ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = HTML::tag( $args['title_tag'], [
				'id'    => $post ? sprintf( $args['title_anchor'], $post->ID, $post->post_name ) : FALSE,
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		return $args['title'];
	}

	// post as item in the list
	public static function postItem( $post = NULL, $atts = [], $before = '', $after = '' )
	{
		if ( ! $post = PostType::getPost( $post ) )
			return '';

		$args = self::atts( [
			'item_link'     => TRUE,
			'item_text'     => NULL,  // callback or use %s for post title
			'item_wrap'     => '', // use %s for item title / or html tag
			'item_title'    => '', // use %s for post title
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => FALSE, // $post->post_type.'-%2$s',
			'item_class'    => '-item do-sincethen',
			'item_dummy'    => '<span class="-dummy"></span>',
			'item_after'    => '',
			'item_after_cb' => FALSE,
			'item_download' => TRUE, // only for attachments
			'item_filesize' => TRUE, // only for attachments // OLD: `item_size`
			'trim_chars'    => FALSE,
			'order_before'  => FALSE,
			'order_zeroise' => FALSE,
			'order_sep'     => ' &ndash; ',
		], $atts );

		$text = PostType::getPostTitle( $post );
		$link = PostType::getPostLink( $post, '' );

		if ( ! $link && current_user_can( 'edit_post', $post->ID ) )
			$link = get_preview_post_link( $post );

		if ( is_null( $args['item_text'] ) )
			$item = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$item = call_user_func_array( $args['item_text'], [ $post, $args, $text ] );

		else if ( $args['item_text'] && Text::has( $args['item_text'], '%' ) )
			$item = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$item = $args['item_text'];

		else
			$item = '';

		if ( $item ) {

			if ( $args['trim_chars'] )
				$item = Text::trimChars( $item, ( TRUE === $args['trim_chars'] ? 45 : $args['trim_chars'] ) );

			if ( $args['item_title_cb'] && is_callable( $args['item_title_cb'] ) )
				$attr = call_user_func_array( $args['item_title_cb'], [ $post, $args, $text ] );

			else if ( $args['item_title'] )
				$attr = sprintf( $args['item_title'], $text );

			else if ( $args['trim_chars'] )
				$attr = $text;

			else
				$attr = FALSE;

			if ( TRUE === $args['item_link'] ) {

				// avoid linking to the same page
				if ( is_singular() && ( $current = PostType::getPost() ) )
					$args['item_link'] = $current->ID !== $post->ID;
			}

			if ( $args['item_link'] && 'attachment' == $post->post_type && $args['item_download'] )
				$item = HTML::tag( 'a', [
					'href'     => wp_get_attachment_url( $post->ID ),
					'download' => apply_filters( static::BASE.'_shortcode_attachement_download', basename( get_attached_file( $post->ID ) ), $post ),
					'title'    => $attr,
					'class'    => '-download',
					'rel'      => 'attachment',
				], $item );

			else if ( $args['item_link'] && $link )
				$item = HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
					'class' => '-link -posttype-'.$post->post_type,
				], $item );

			else
				$item = HTML::tag( 'span', [
					'title' => $attr,
					'class' => $args['item_link'] ? '-no-link -future -posttype-'.$post->post_type : '-no-link',
				], $item );

			if ( $args['item_wrap'] && Text::has( $args['item_wrap'], '%' ) )
				$item = sprintf( $args['item_wrap'], $item );

			else if ( $args['item_wrap'] )
				$item = HTML::tag( $args['item_wrap'], $item );

			if ( $args['order_before'] ) {
				$order = $args['order_zeroise'] ? Number::zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order;
				$item = Number::localize( $order ).( $args['order_sep'] ? $args['order_sep'] : '' ).$item;
			}
		}

		if ( 'attachment' == $post->post_type && $args['item_filesize'] ) {
			$size = TRUE === $args['item_filesize'] ? '&nbsp;<span class="-filesize">(%s)</span>' : $args['item_filesize'];
			$item.= sprintf( $size, HTML::wrapLTR( File::formatSize( filesize( get_attached_file( $post->ID ) ), 2 ) ) );
		}

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $post, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					HTML::escapeURL( $link ),
					Helper::prepDescription( $post->post_excerpt ),
					Text::trimChars( $post->post_excerpt )
					// FIXME: add post_content
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return HTML::tag( $args['item_tag'], [
			'id'       => $args['item_anchor'] ? sprintf( $args['item_anchor'], $post->ID, $post->post_name ) : FALSE,
			'class'    => $args['item_class'],
			'datetime' => 'publish' == $post->post_status ? date( 'c', strtotime( $post->post_date_gmt ) ) : FALSE,
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	// post as an image on the list
	public static function postImage( $post = NULL, $atts = [], $before = '', $after = '', $fallback = '' )
	{
		if ( ! $post = PostType::getPost( $post ) )
			return $fallback;

		$args = self::atts( [
			'item_link'           => TRUE,
			'item_text'           => NULL,                             // callback or use %s for post title
			'item_wrap'           => '',                               // use %s for item title / or html tag
			'item_title'          => '',                               // use %s for post title
			'item_title_cb'       => FALSE,
			'item_tag'            => 'li',
			'item_anchor'         => FALSE,                            // $post->post_type.'-%2$s',
			'item_class'          => '-item -tile do-sincethen',
			'item_dummy'          => '<span class="-dummy"></span>',
			'item_after'          => '',
			'item_after_cb'       => FALSE,
			'item_image_tile'     => NULL,
			'item_image_metakey'  => NULL,
			'item_image_size'     => NULL,
			'item_image_loading'  => 'lazy',                           // FALSE to disable
			'item_image_decoding' => 'async',                          // FALSE to disable
			'item_image_empty'    => FALSE,
		], $atts );

		if ( ! $image_id = PostType::getThumbnailID( $post->ID, $args['item_image_metakey'] ) )
			return $fallback;

		if ( is_null( $args['item_image_size'] ) )
			$args['item_image_size'] = Media::getAttachmentImageDefaultSize( $post->post_type );

		if ( ! $thumbnail_img = Media::htmlAttachmentSrc( $image_id, $args['item_image_size'], FALSE ) )
			return $fallback;

		$text = PostType::getPostTitle( $post, FALSE );
		$link = PostType::getPostLink( $post, '' );

		if ( is_null( $args['item_text'] ) )
			$title = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$title = call_user_func_array( $args['item_text'], [ $post, $args, $text ] );

		else if ( $args['item_text'] && Text::has( $args['item_text'], '%' ) )
			$title = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$title = $args['item_text'];

		else
			$title = '';

		$image = HTML::tag( 'img', [
			'src'      => $thumbnail_img,
			'alt'      => Media::getAttachmentImageAlt( $image_id, $title ),
			'loading'  => $args['item_image_loading'],
			'decoding' => $args['item_image_decoding'],
		] );

		if ( $link && $args['item_link'] )
			$item = HTML::tag( 'a', [
				'href'  => $link,
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => '-link -type-'.$post->post_type,
			], $image );

		else
			$item = HTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -type-'.$post->post_type : FALSE,
			], $image );

		if ( $args['item_wrap'] && Text::has( $args['item_wrap'], '%' ) )
			$item = sprintf( $args['item_wrap'], $item );

		else if ( $args['item_wrap'] )
			$item = HTML::tag( $args['item_wrap'], $item );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $post, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					HTML::escapeURL( $link ),
					Helper::prepDescription( $post->post_excerpt ),
					Text::trimChars( $post->post_excerpt )
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return HTML::tag( $args['item_tag'], [
			'id'    => $args['item_anchor'] ? sprintf( $args['item_anchor'], $post->ID, $post->post_name ) : FALSE,
			'class' => $args['item_class'],
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	public static function getDefaults( $posttype = '', $taxonomy = '', $posttypes = [], $taxonomies = [], $module = NULL )
	{
		return [
			'taxonomy'            => $taxonomy,
			'taxonomies'          => $taxonomies,
			'posttype'            => $posttype,
			'posttypes'           => $posttypes,
			'id'                  => '',
			'slug'                => '',
			'title'               => NULL,                                                            // FALSE to disable
			'title_cb'            => FALSE,
			'title_link'          => NULL,                                                            // `anchor` for slug anchor, FALSE to disable
			'title_title'         => _x( 'Permanent link', 'ShortCode: Title Attr', 'geditorial' ),
			'title_title_cb'      => FALSE,                                                           // callback for title attr
			'title_tag'           => 'h3',
			'title_anchor'        => $taxonomy.'-%2$s',
			'title_class'         => '-title',
			'title_dummy'         => '<span class="-dummy"></span>',
			'title_after'         => '',
			'title_label'         => 'all_items',                                                     // label key for posttype/taxonomy object
			'item_cb'             => FALSE,
			'item_link'           => TRUE,
			'item_text'           => NULL,                                                            // callback or use %s for post title
			'item_wrap'           => '',                                                              // use %s for item title / or html tag
			'item_title'          => '',                                                              // use %s for post title
			'item_title_cb'       => FALSE,
			'item_tag'            => 'li',
			'item_anchor'         => FALSE,                                                           // $posttype.'-%2$s',
			'item_class'          => '-item do-sincethen',
			'item_dummy'          => '<span class="-dummy"></span>',
			'item_after'          => '',
			'item_after_cb'       => FALSE,
			'item_download'       => TRUE,                                                            // only for attachments
			'item_filesize'       => TRUE,                                                            // only for attachments // OLD: `item_size`
			'item_image_tile'     => NULL,
			'item_image_metakey'  => empty( $posttype ) ? 'image' : '_thumbnail_id',
			'item_image_size'     => NULL,
			'item_image_loading'  => 'lazy',                                                          // FALSE to disable
			'item_image_decoding' => 'async',                                                         // FALSE to disable
			'item_image_empty'    => FALSE,
			'order_before'        => FALSE,
			'order_zeroise'       => FALSE,
			'order_sep'           => ' &ndash; ',
			'list_tag'            => 'ul',
			'list_class'          => '-list',
			'cover'               => FALSE,                                                           // must have thumbnail
			'future'              => 'on',
			'mime_type'           => '',                                                              // only for attachments / like: `image`
			'connection'          => '',                                                              // only for o2o
			'orderby'             => '',                                                              // empty for default
			'order'               => 'ASC',
			'order_cb'            => FALSE,                                                           // NULL for default order by paired meta
			'order_start'         => 'start',                                                         // meta field for ordering
			'order_order'         => 'order',                                                         // meta field for ordering
			'limit'               => -1,
			'module'              => $module,                                                         // main caller module
			'field_module'        => 'meta',                                                          // getting meta field from
			'context'             => NULL,
			'wrap'                => TRUE,
			'before'              => '',                                                              // html after wrap
			'after'               => '',                                                              // html before wrap
			'class'               => '',                                                              // wrap css class
		];
	}

	// list: `assigned`: posts by terms
	// list: `paired`: posts by meta (PAIRED API)
	// list: `connected`: posts by o2o
	// list: `attached`: posts by inheritance
	// list: `alphabetized`: posts sorted by alphabet // TODO!
	// list: `custom`: posts by id list // TODO!
	public static function listPosts( $list, $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '', $module = NULL )
	{
		// $defs = self::getDefaults( $posttype, $taxonomy, [ $posttype ], [], $module );
		$defs = self::getDefaults( $posttype, $taxonomy, [], [], $module );
		$args = shortcode_atts( $defs, $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		// back-comp
		if ( 'associated' == $list )
			$list = 'paired';

		$key   = self::hash( $list, $args );
		$group = sprintf( '%s_list_posts', static::BASE );
		$cache = wp_cache_get( $key, $group );

		if ( FALSE !== $cache )
			return $cache;

		$html = $ref = $post = $term = $parent = $skip = '';

		if ( is_null( $args['module'] ) && static::MODULE )
			$args['module'] = static::MODULE;

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		$query = [
			'orderby'          => 'date',
			'order'            => $args['order'],
			'posts_per_page'   => $args['limit'],
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		];

		if ( ! empty( $args['posttypes'] ) )
			$query['post_type'] = $args['posttypes'];

		// NOTE: back-compat / DROP THIS
		if ( 'page' == $args['orderby'] )
			$args['orderby'] = 'order';

		if ( $args['orderby'] && 'order' != $args['orderby'] )
			$query['orderby'] = $args['orderby'];

		if ( 'on' == $args['future'] )
			$query['post_status'] = [ 'publish', 'future', 'draft' ];
		else
			$query['post_status'] = [ 'publish' ];

		if ( 'attached' == $list ) {

			$query['post_type'] = 'attachment';
			$query['post_status'] = [ 'inherit' ];

			if ( $parent = PostType::getPost( $args['id'] ) )
				$query['post_parent'] = $parent->ID;

			if ( $args['mime_type'] )
				$query['post_mime_type'] = $args['mime_type'];

			if ( ! $args['orderby'] )
				$query['orderby'] = 'menu_order';

		} else if ( 'connected' == $list ) {

			if ( is_post_type_archive( $posttype ) ) {

				$query['post_type'] = $posttype;

			} else if ( $post = PostType::getPost( $args['id'] ) ) {

				$query['connected_type']  = $args['connection'];
				$query['connected_items'] = $post;

			} else {

				return $content;
			}

		} else if ( 'all' == $args['id'] ) {

			if ( empty( $query['post_type'] ) )
				$query['post_type'] = $posttype;

		} else if ( $args['id'] ) {

			if ( ! $taxonomy )
				return $content;

			if ( ! $term = get_term_by( 'id', $args['id'], $taxonomy ) )
				return $content;

			$query['tax_query'] = [ [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $args['slug'] ) {

			if ( ! $taxonomy )
				return $content;

			if ( ! $term = get_term_by( 'slug', $args['slug'], $taxonomy ) )
				return $content;

			$query['tax_query'] = [ [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_post_type_archive( $posttype ) ) {

			$query['post_type'] = $posttype;

		} else if ( is_tax( $taxonomy ) ) {

			if ( ! $term = get_queried_object() )
				return $content;

			$query['tax_query'] = [ [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( 'paired' == $list && is_singular( $posttype ) ) {

			// gets the list of supported posts paired to this post

			if ( ! $post = PostType::getPost() )
				return $content;

			if ( $args['module'] ) {

				if ( ! $term = gEditorial()->module( $args['module'] )->paired_get_to_term_direct( $post->ID, $posttype, $taxonomy ) )
					return $content;

			} else {

				self::_dep( 'must pass module into ShortCode::listPosts( \'paired\' )' );

				if ( $term_id = get_post_meta( $post->ID, '_'.$posttype.'_term_id', TRUE ) )
					$term = get_term_by( 'id', (int) $term_id, $taxonomy );

				else
					$term = get_term_by( 'slug', $post->post_name, $taxonomy );

				if ( ! $term )
					return $content;
			}

			$query['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

			if ( is_null( $args['title'] ) )
				$args['title'] = FALSE; // disable on main post singular

			$query['ignore_sticky_posts'] = TRUE;
			$query['post__not_in'] = [ $post->ID ]; // exclude current

			// exclude paired posttype
			if ( empty( $args['posttypes'] ) )
				$query['post_type'] = Arraay::stripByValue( Taxonomy::object( $taxonomy )->object_type, $posttype );

		} else if ( 'paired' === $list && is_singular( $args['posttypes'] ) ) {

			// gets the list of main posts paired to this supported post

			if ( ! $args['module'] ) {

				self::_dep( 'must pass module into ShortCode::listPosts( \'paired\' )' );

				return $content;
			}

			if ( ! $paired_posts = gEditorial()->module( $args['module'] )->get_linked_to_posts( NULL, FALSE, NULL ) )
				return $content;

			$query['post_type'] = $posttype; // override with main posttype
			$query['post__in'] = $paired_posts;
			$query['ignore_sticky_posts'] = TRUE;

		} else if ( 'paired' == $list || ( 'assigned' == $list && is_singular( $posttype ) ) ) {

			// gets the list of posts by the taxonomy

			if ( ! $post = PostType::getPost() )
				return $content;

			// NOTE: also used later!
			if ( ! $term = Taxonomy::getPostTerms( $taxonomy, $post ) )
				return $content;

			$query['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $term, 'term_id' ),
			] ];

			$skip = TRUE; // maybe queried itself!

		} else if ( 'assigned' == $list ) {

			return $content;
		}

		if ( $args['item_image_tile']
			&& FALSE === $args['item_image_empty'] ) {

			$query['meta_query'] = [ [
				'key'     => $args['item_image_metakey'], // '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];
		}

		$class = new \WP_Query();
		$items = $class->query( $query );
		$count = count( $items );

		if ( ! $count || ( 1 == $count && $skip ) )
			return $content;

		if ( 'assigned' == $list ) {

			if ( is_null( $args['title'] ) )
				$args['title'] = 'all' === $args['id']
					? self::posttypeTitle( $posttype, $args )
					: self::termTitle( $term, $taxonomy, $args );

			$ref = $term;

		} else if ( 'paired' == $list || 'connected' == $list ) {

			if ( is_null( $args['title'] ) )
				$args['title'] = self::postTitle( $post, $args );

			$ref = $post;

		} else if ( 'attached' == $list ) {

			if ( is_null( $args['title'] ) )
				$args['title'] = self::postTitle( $parent, $args );

			$ref = $parent;
		}

		if ( $args['orderby'] == 'order' ) {

			// calback may change items, so even if one item
			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$items = call_user_func_array( $args['order_cb'], [ $items, $args, $ref ] );

			else if ( is_null( $args['order_cb'] ) && $count > 1 )
				$items = Template::reorderPosts( $items,
					$args['field_module'],
					$args['order_start'],
					$args['order_order'] );
		}

		foreach ( $items as $item ) {

			// NOTE: callback must setup postdata
			// @REF: https://developer.wordpress.org/?p=2837#comment-874
			// `$GLOBALS['post'] = $item;`
			// `setup_postdata( $item );`

			if ( $args['item_cb'] )
				$html.= call_user_func_array( $args['item_cb'], [ $item, $args, $ref ] );

			else if ( $args['item_image_tile'] )
				$html.= self::postImage( $item, $args );

			else
				$html.= self::postItem( $item, $args );
		}

		if ( $args['list_tag'] )
			$html = HTML::tag( $args['list_tag'], [
				'class' => HTML::attrClass( $args['list_class'], '-posts-list', sprintf( '-type-%s', $posttype ) ),
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// wp_reset_postdata();
		wp_cache_set( $key, $html, $group );

		return $html;
	}

	// FIXME: DEPRECATED: use `Shortcode::listPosts( 'assigned' )`
	public static function getTermPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		self::_dep( 'ShortCode::listPosts()' );

		$args = shortcode_atts( self::getDefaults( $posttype, $taxonomy, [ $posttype ] ), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = '';

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $posttype );

		if ( FALSE !== $cache )
			return $cache;

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		$query_args = [
			'post_type'        => $args['posttypes'],
			'orderby'          => 'date',
			'order'            => $args['order'],
			'posts_per_page'   => $args['limit'],
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		];

		if ( $args['orderby'] && 'order' != $args['orderby'] )
			$query_args['orderby'] = $args['orderby'];

		if ( 'on' == $args['future'] )
			$query_args['post_status'] = [ 'publish', 'future', 'draft' ];
		else
			$query_args['post_status'] = [ 'publish' ];

		if ( in_array( 'attachment', (array) $args['posttypes'] ) ) {

			if ( $post = PostType::getPost( $args['id'] ) )
				$query_args['post_parent'] = $post->ID;

			if ( $args['mime_type'] )
				$query_args['post_mime_type'] = $args['mime_type'];

			if ( ! $args['orderby'] )
				$query_args['orderby'] = 'menu_order';

			$query_args['post_status'] = [ 'inherit' ];

		} else if ( 'all' == $args['id'] ) {

			// do nothing, will collect all posttype: e.g. all entries of all sections

		} else if ( $args['id'] ) {

			if ( ! $term = get_term_by( 'id', $args['id'], $taxonomy ) )
				return $content;

			$query_args['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $args['slug'] ) {

			if ( ! $term = get_term_by( 'slug', $args['slug'], $taxonomy ) )
				return $content;

			$query_args['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_tax( $taxonomy ) ) {

			if ( ! $term = get_queried_object() )
				return $content;

			$query_args['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_singular( $posttype ) ) {

			// NOTE: also used late
			if ( ! $term = Taxonomy::getPostTerms( $taxonomy ) )
				return $content;

			$query_args['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $term, 'term_id' ),
			] ];

		} else {

			return $content;
		}

		if ( $args['cover'] )
			$query_args['meta_query'] = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$query = new \WP_Query();
		$items = $query->query( $query_args );
		$count = count( $items );

		if ( ! $count )
			return $content;

		if ( 1 == $count && is_singular( $args['posttypes'] ) )
			return $content;

		$args['title'] = self::termTitle( $term, $taxonomy, $args );

		if ( $args['orderby'] == 'order' ) {

			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$items = call_user_func_array( $args['order_cb'], [ $items, $args, $term ] );

			else if ( is_null( $args['order_cb'] ) && $count > 1 )
				$items = Template::reorderPosts( $items, $args['field_module'], $args['order_start'], $args['order_order'] );
		}

		foreach ( $items as $item ) {

			// caller must setup postdata
			// REF: https://developer.wordpress.org/?p=2837#comment-874
			// $GLOBALS['post'] = $item;
			// setup_postdata( $item );

			if ( $args['item_cb'] )
				$html.= call_user_func_array( $args['item_cb'], [ $item, $args, $term ] );

			else
				$html.= self::postItem( $item, $args );
		}

		if ( $args['list_tag'] )
			$html = HTML::tag( $args['list_tag'], [
				'class' => $args['list_class'],
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}

	// FIXME: DEPRECATED: use `Shortcode::listPosts( 'paired' )`
	// OLD: `::getAssocPosts()`
	public static function getPairedPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		self::_dep( 'ShortCode::listPosts( \'paired\' )' );

		$args = shortcode_atts( self::getDefaults( $posttype, $taxonomy ), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = $tax_query = $meta_query = '';
		$post = PostType::getPost();

		$key   = md5( serialize( $args ) );
		$cache = wp_cache_get( $key, $posttype );

		if ( FALSE !== $cache )
			return $cache;

		// FIXME: back-compat / DROP THIS
		if ( 'page' == $args['orderby'] )
			$args['orderby'] = 'order';

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		if ( 'all' == $args['id'] ) {

			// do nothing, will collect all posttype: e.g. all entries of all sections

		} else if ( $args['id'] ) {

			if ( ! $term = get_term_by( 'id', $args['id'], $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $args['slug'] ) {

			if ( ! $term = get_term_by( 'slug', $args['slug'], $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( is_tax( $taxonomy ) ) {

			if ( ! $term = get_queried_object() )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else if ( $post && is_singular( $posttype ) ) {

			if ( $term_id = get_post_meta( $post->ID, '_'.$posttype.'_term_id', TRUE ) )
				$term = get_term_by( 'id', (int) $term_id, $taxonomy );

			else
				$term = get_term_by( 'slug', $post->post_name, $taxonomy );

			if ( ! $term )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			] ];

		} else {

			// NOTE: also used later
			if ( ! $term = Taxonomy::getPostTerms( $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => wp_list_pluck( $term, 'term_id' ),
			] ];
		}

		if ( $args['cover'] )
			$meta_query = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$args['title'] = self::postTitle( $post, $args );

		if ( 'on' == $args['future'] )
			$post_status = [ 'publish', 'future', 'draft' ];
		else
			$post_status = [ 'publish' ];

		$query_args = [
			'tax_query'        => $tax_query,
			'meta_query'       => $meta_query,
			'posts_per_page'   => $args['limit'],
			'orderby'          => $args['orderby'] == 'order' ? 'date' : $args['orderby'],
			'order'            => $args['order'],
			'post_type'        => $args['posttypes'],
			'post_status'      => $post_status,
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
		];

		$query = new \WP_Query();
		$posts = $query->query( $query_args );
		$count = count( $posts );

		if ( ! $count )
			return $content;

		if ( 1 == $count && is_singular( $args['posttypes'] ) )
			return $content;

		if ( $args['orderby'] == 'order' ) {

			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$posts = call_user_func_array( $args['order_cb'], [ $posts, $args, $term ] );

			else if ( is_null( $args['order_cb'] ) && $count > 1 )
				$posts = Template::reorderPosts( $posts, $args['field_module'], $args['order_start'], $args['order_order'] );
		}

		foreach ( $posts as $post ) {

			// setup_postdata( $post );

			if ( $args['item_cb'] )
				$html.= call_user_func_array( $args['item_cb'], [ $post, $args, $term ] );

			else
				$html.= self::postItem( $post, $args );
		}

		if ( $args['list_tag'] )
			$html = HTML::tag( $args['list_tag'], [
				'class' => $args['list_class'],
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}

	// list: `allitems`: terms for display
	// list: `thepost`: terms for the post
	// list: `alphabetized`: terms sorted by alphabet // TODO!
	public static function listTerms( $list, $taxonomy, $atts = [], $content = NULL, $tag = '', $module = NULL )
	{
		$defs = self::getDefaults( '', $taxonomy, [], [], $module );
		$args = shortcode_atts( $defs, $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$key   = self::hash( $list, $args );
		$group = sprintf( '%s_list_terms', static::BASE );
		$cache = wp_cache_get( $key, $group );

		if ( FALSE !== $cache )
			return $cache;

		$html = $ref = $parent = '';

		if ( is_null( $args['module'] ) && static::MODULE )
			$args['module'] = static::MODULE;

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		$query = [
			'order'   => $args['order'],
			'orderby' => $args['orderby'] ?: 'name',
		];

		// FIXME: support sort by meta
		if ( 'order' == $args['orderby'] )
			$query['orderby'] = 'none'; // later sorting

		if ( ! empty( $args['taxonomy'] ) ) {

			$query['taxonomy']   = $args['taxonomy'];
			$query['hide_empty'] = FALSE;

			// $query['update_term_meta_cache'] = FALSE;

			$ref = Taxonomy::object( $args['taxonomy'] );

		} else if ( 'allitems' === $list ) {

			return $content;
		}

		if ( 'thepost' == $list ) {

			if ( ! $parent = PostType::getPost( $args['id'] ) )
				return $content;

			$query['object_ids'] = [ $parent->ID ];
			$ref = $parent;
		}

		if ( $args['item_image_tile']
			&& FALSE === $args['item_image_empty'] ) {

			$query['meta_query'] = [ [
				'key'     => $args['item_image_metakey'], // 'image',
				'compare' => 'EXISTS'
			] ];
		}

		$class = new \WP_Term_Query( $query );
		$items = $class->terms;
		$count = count( $items );

		if ( ! $count )
			return $content;

		if ( FALSE === $args['title'] ) {

			// do nothing, title is disabled by the args

		} else if ( 'taxonomy' === $args['title'] ) {

			$args['title'] = $args['taxonomy'] ? self::taxonomyTitle( $ref, $args ) : FALSE;

		} else if ( is_null( $args['title'] ) && 'thepost' === $list ) {

			$args['title'] = self::postTitle( $parent, $args );
		}

		if ( $args['orderby'] == 'order' ) {

			// calback may change items, so even if one item
			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$items = call_user_func_array( $args['order_cb'], [ $items, $args, $ref ] );

			// else if ( is_null( $args['order_cb'] ) && $count > 1 )
			// 	$items = Template::reorderPosts( $items, $args['field_module'], $args['order_start'], $args['order_order'] );
		}

		foreach ( $items as $item ) {

			if ( $args['item_cb'] )
				$html.= call_user_func_array( $args['item_cb'], [ $item, $args, $ref ] );

			else if ( $args['item_image_tile'] )
				$html.= self::termImage( $item, $args );

			else
				$html.= self::termItem( $item, $args );
		}

		if ( $args['list_tag'] )
			$html = HTML::tag( $args['list_tag'], [
				'class' => HTML::attrClass( $args['list_class'], ( 'tiles' == $list ? '-term-tiles' : '-terms-list' ) ),
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );
		wp_cache_set( $key, $html, $group );

		return $html;
	}
}

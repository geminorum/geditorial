<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class ShortCode extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function wrap( $html, $suffix = FALSE, $args = [], $block = TRUE, $extra = [] )
	{
		return WordPress\ShortCode::wrap( $html, $suffix, $args, $block, $extra, static::BASE );
	}

	/**
	 * Retrieves given term as title of the list.
	 *
	 * @param int|object $term_or_id
	 * @param string $taxonomy
	 * @param array $atts
	 * @return string
	 */
	public static function termTitle( $term_or_id, $taxonomy = 'category', $atts = [] )
	{
		if ( ! $term = WordPress\Term::get( $term_or_id, $taxonomy ) )
			return '';

		$args = self::atts( [
			'title'          => NULL,                             // '<a href="%2$s">%1$s</a>', // `FALSE` to disable
			'title_cb'       => FALSE,                            // Call-back for title
			'title_link'     => NULL,                             // `anchor` for slug anchor, `FALSE` to disable
			'title_title'    => '',
			'title_title_cb' => FALSE,                            // Call-back for title attr
			'title_tag'      => 'h3',
			'title_anchor'   => 'term-%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_after'    => '',                               // '<div class="-desc">%3$s</div>',
		], $atts );

		$text = WordPress\Term::title( $term );
		$link = WordPress\Term::link( $term );

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

		} else if ( $args['title'] && Core\Text::has( $args['title'], '%' ) ) {

			$args['title'] = sprintf( $args['title'],
				$text,
				$link,
				Core\HTML::escape( Core\Text::stripTags( $term->description ) ),
				( $attr ?: '' )
			);

			if ( 'anchor' !== $args['title_link'] )
				$args['title_link'] = FALSE;
		}

		if ( $args['title'] ) {

			if ( is_null( $args['title_link'] ) && $term )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] && $term )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $term->term_id, $term->slug ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = Core\HTML::tag( $args['title_tag'], [
				'id'    => $term ? sprintf( $args['title_anchor'], $term->term_id, $term->slug ) : FALSE,
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		if ( $args['title_after'] ) {

			if ( $term && Core\Text::has( $args['title_after'], '%' ) )
				$args['title_after'] = sprintf( $args['title_after'],
					$text,
					$link,
					WordPress\Strings::prepDescription( $term->description )
				);

			return $args['title'].$args['title_after'];
		}

		return $args['title'];
	}

	/**
	 * Retrieves given term as an item on the list.
	 *
	 * @param int|object $term
	 * @param array $atts
	 * @param string $before
	 * @param string $after
	 * @param string $fallback
	 * @return string
	 */
	public static function termItem( $term, $atts = [], $before = '', $after = '', $fallback = '' )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return $fallback;

		$args = self::atts( [
			'item_link'     => TRUE,
			'item_text'     => NULL,                             // Call-back or use `%s` for post title
			'item_wrap'     => '',                               // Use `%s` for item title / or HTML tag
			'item_title'    => '',                               // Use `%s` for post title
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => FALSE,                            // $term->taxonomy.'-%2$s',
			'item_class'    => '-item do-sincethen',
			'item_dummy'    => '<span class="-dummy"></span>',
			'item_after'    => '',
			'item_after_cb' => FALSE,
		], $atts );

		$text = WordPress\Term::title( $term );
		$link = WordPress\Term::link( $term );

		if ( is_null( $args['item_text'] ) )
			$title = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$title = call_user_func_array( $args['item_text'], [ $term, $args, $text ] );

		else if ( $args['item_text'] && Core\Text::has( $args['item_text'], '%' ) )
			$title = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$title = $args['item_text'];

		else
			$title = ''; // FIXME: WTF: better to bail here!

		if ( $term->count && $args['item_link'] )
			$item = Core\HTML::tag( 'a', [
				'href'  => $link,
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			], $title );

		else
			$item = Core\HTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			], $title );

		if ( $args['item_wrap'] && Core\Text::has( $args['item_wrap'], '%' ) )
			$item = sprintf( $args['item_wrap'], $item );

		else if ( $args['item_wrap'] )
			$item = Core\HTML::tag( $args['item_wrap'], $item );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $term, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Core\Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					Core\HTML::escapeURL( $link ),
					WordPress\Strings::prepDescription( $term->description ),
					Core\Text::trimChars( $term->description )
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return Core\HTML::tag( $args['item_tag'], [
			'id'    => $args['item_anchor'] ? sprintf( $args['item_anchor'], $term->term_id, $term->slug ) : FALSE,
			'class' => $args['item_class'],
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	/**
	 * Retrieves given term as an image on the list.
	 *
	 * @param int|object $term
	 * @param array $atts
	 * @param string $before
	 * @param string $after
	 * @param string $fallback
	 * @return string
	 */
	public static function termImage( $term, $atts = [], $before = '', $after = '', $fallback = '' )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return $fallback;

		$args = self::atts( [
			'item_link'           => TRUE,
			'item_text'           => NULL,                             // Call-back or use `%s` for term title
			'item_wrap'           => '',                               // Use `%s` for item title / or HTML tag
			'item_title'          => '',                               // Use `%s` for term title
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
			'item_image_loading'  => 'lazy',                           // `FALSE` to disable
			'item_image_decoding' => 'async',                          // `FALSE` to disable
			'item_image_empty'    => FALSE,
		], $atts );

		if ( ! $image_id = WordPress\Taxonomy::getThumbnailID( $term->term_id, $args['item_image_metakey'] ) )
			return $fallback;

		if ( is_null( $args['item_image_size'] ) )
			$args['item_image_size'] = WordPress\Media::getAttachmentImageDefaultSize( NULL, $term->taxonomy );

		if ( ! $thumbnail_img = WordPress\Media::getAttachmentSrc( $image_id, $args['item_image_size'], FALSE ) )
			return $fallback;

		$text = WordPress\Term::title( $term );
		$link = WordPress\Term::link( $term );

		if ( is_null( $args['item_text'] ) )
			$title = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$title = call_user_func_array( $args['item_text'], [ $term, $args, $text ] );

		else if ( $args['item_text'] && Core\Text::has( $args['item_text'], '%' ) )
			$title = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$title = $args['item_text'];

		else
			$title = '';

		$image = Core\HTML::tag( 'img', [
			'src'      => $thumbnail_img,
			'alt'      => WordPress\Media::getAttachmentImageAlt( $image_id, $title ),
			'class'    => apply_filters( 'get_image_tag_class', '-term-image img-fluid', $image_id, 'none', $args['item_image_size'] ),
			'loading'  => $args['item_image_loading'],
			'decoding' => $args['item_image_decoding'],
		] );

		if ( $term->count && $args['item_link'] )
			$item = Core\HTML::tag( 'a', [
				'href'  => $link,
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => '-link -tax-'.$term->taxonomy,
			], $image );

		else
			$item = Core\HTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -tax-'.$term->taxonomy : FALSE,
			], $image );

		if ( $args['item_wrap'] && Core\Text::has( $args['item_wrap'], '%' ) )
			$item = sprintf( $args['item_wrap'], $item );

		else if ( $args['item_wrap'] )
			$item = Core\HTML::tag( $args['item_wrap'], $item );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $term, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Core\Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					Core\HTML::escapeURL( $link ),
					WordPress\Strings::prepDescription( $term->description ),
					Core\Text::trimChars( $term->description )
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return Core\HTML::tag( $args['item_tag'], [
			'id'    => $args['item_anchor'] ? sprintf( $args['item_anchor'], $term->term_id, $term->slug ) : FALSE,
			'class' => $args['item_class'],
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	/**
	 * Retrieves given post-type as title of the list.
	 *
	 * @param string|object $posttype
	 * @param array $atts
	 * @return string
	 */
	public static function posttypeTitle( $posttype = NULL, $atts = [] )
	{
		if ( ! $posttype = WordPress\PostType::object( $posttype ) )
			return '';

		$args = self::atts( [
			'title'          => NULL,                             // `FALSE` to disable
			'title_cb'       => FALSE,                            // The callback for title.
			'title_link'     => NULL,                             // `anchor` for slug anchor, `FALSE` to disable
			'title_title'    => '',
			'title_title_cb' => FALSE,                            // The callback for title attribute.
			'title_tag'      => 'h3',
			'title_anchor'   => '%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_label'    => 'all_items',
		], $atts );

		if ( 'posttype' === $args['title'] )
			$args['title'] = NULL; // reset

		$text = Services\CustomPostType::getLabel( $posttype, $args['title_label'] );
		$link = WordPress\PostType::link( $posttype->name );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $posttype, $atts, $text, $link ] );

		if ( is_null( $args['title'] ) )
			$args['title'] = $text;

		else if ( $args['title'] && Core\Text::has( $args['title'], '%' ) )
			$args['title'] = sprintf( $args['title'], ( $text ?: '' ) );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $posttype, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {

			if ( is_null( $args['title_link'] ) && $link && ! is_post_type_archive( $posttype->name ) )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $posttype->name, $posttype->rest_base ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = Core\HTML::tag( $args['title_tag'], [
				'id'    => sprintf( $args['title_anchor'], $posttype->name, $posttype->rest_base ),
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		return $args['title'];
	}

	/**
	 * Retrieves given taxonomy as title of the list.
	 *
	 * @param string|object $taxonomy
	 * @param array $atts
	 * @return string
	 */
	public static function taxonomyTitle( $taxonomy = NULL, $atts = [] )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $taxonomy ) )
			return '';

		$args = self::atts( [
			'title'          => NULL,                             // `FALSE` to disable
			'title_cb'       => FALSE,                            // The callback for title.
			'title_link'     => NULL,                             // `anchor` for slug anchor, `FALSE` to disable
			'title_title'    => '',
			'title_title_cb' => FALSE,                            // The callback for title attribute.
			'title_tag'      => 'h3',
			'title_anchor'   => '%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_label'    => 'all_items',
		], $atts );

		if ( 'taxonomy' === $args['title'] )
			$args['title'] = NULL; // reset

		$text = Services\CustomTaxonomy::getLabel( $taxonomy, $args['title_label'] );
		$link = WordPress\Taxonomy::link( $taxonomy->name );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $taxonomy, $atts, $text, $link ] );

		if ( is_null( $args['title'] ) )
			$args['title'] = $text;

		else if ( $args['title'] && Core\Text::has( $args['title'], '%' ) )
			$args['title'] = sprintf( $args['title'], ( $text ?: '' ) );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $taxonomy, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {

			if ( is_null( $args['title_link'] ) && $link )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $taxonomy->name, $taxonomy->rest_base ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = Core\HTML::tag( $args['title_tag'], [
				'id'    => sprintf( $args['title_anchor'], $taxonomy->name, $taxonomy->rest_base ),
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		return $args['title'];
	}

	/**
	 * Retrieves given post as title of the list.
	 *
	 * @param int|object $post
	 * @param array $atts
	 * @return string
	 */
	public static function postTitle( $post = NULL, $atts = [] )
	{
		$args = self::atts( [
			'title'          => NULL,                             // `FALSE` to disable
			'title_cb'       => FALSE,                            // Call-back for title
			'title_link'     => NULL,                             // `anchor` for slug anchor, `FALSE` to disable
			'title_title'    => '',
			'title_title_cb' => FALSE,                            // Call-back for title attribute
			'title_tag'      => 'h3',
			'title_anchor'   => 'post-%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
		], $atts );

		$text = WordPress\Post::title( $post, FALSE );
		$link = WordPress\Post::link( $post, '' );

		if ( $args['title_cb'] && is_callable( $args['title_cb'] ) )
			$args['title'] = call_user_func_array( $args['title_cb'], [ $post, $atts, $text, $link ] );

		if ( is_null( $args['title'] ) )
			$args['title'] = $text;

		else if ( $args['title'] && Core\Text::has( $args['title'], '%' ) )
			$args['title'] = sprintf( $args['title'], ( $text ?: '' ) );

		if ( $args['title_title_cb'] && is_callable( $args['title_title_cb'] ) )
			$attr = call_user_func_array( $args['title_title_cb'], [ $post, $atts, $text ] );

		else if ( $args['title_title'] )
			$attr = sprintf( $args['title_title'], ( $text ?: '' ) );

		else
			$attr = FALSE;

		if ( $args['title'] ) {

			// NOTE: only if post present, avoid linking to itself.
			if ( is_null( $args['title_link'] ) && $link && $post )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
				], $args['title'] );

			else if ( 'anchor' === $args['title_link'] && $post )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => '#'.sprintf( $args['title_anchor'], $post->ID, $post->post_name ),
					'title' => $attr,
				], $args['title'] );

			else if ( $args['title_link'] )
				$args['title'] = Core\HTML::tag( 'a', [
					'href'  => $args['title_link'],
					'title' => $attr,
				], $args['title'] );
		}

		if ( $args['title'] && $args['title_tag'] )
			$args['title'] = Core\HTML::tag( $args['title_tag'], [
				'id'    => $post ? sprintf( $args['title_anchor'], $post->ID, $post->post_name ) : FALSE,
				'class' => $args['title_class'],
			], $args['title'].( $args['title_dummy'] ?: '' ) )."\n";

		return $args['title'];
	}

	/**
	 * Retrieves given post as an item on the list.
	 *
	 * @param int|object $post
	 * @param array $atts
	 * @param string $before
	 * @param string $after
	 * @return string
	 */
	public static function postItem( $post = NULL, $atts = [], $before = '', $after = '' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return '';

		$args = self::atts( [
			'item_link'     => TRUE,
			'item_text'     => NULL,                             // Call-back or use `%s` for post title
			'item_wrap'     => '',                               // Use `%s` for item title / or HTML tag
			'item_title'    => '',                               // Use `%s` for post title / or `caption` for attachment caption
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => FALSE,                            // `$post->post_type.'-%2$s'`,
			'item_class'    => '-item do-sincethen',
			'item_dummy'    => '<span class="-dummy"></span>',
			'item_after'    => '',
			'item_after_cb' => FALSE,
			'item_download' => TRUE,                             // only for attachments
			'item_filesize' => TRUE,                             // only for attachments // OLD: `item_size`
			'trim_chars'    => FALSE,
			'order_before'  => FALSE,
			'order_zeroise' => FALSE,
			'order_sep'     => ' &ndash; ',
		], $atts );

		$text = WordPress\Post::title( $post );
		$link = WordPress\Post::link( $post, '' );

		if ( ! $link && current_user_can( 'edit_post', $post->ID ) )
			$link = get_preview_post_link( $post );

		if ( is_null( $args['item_text'] ) )
			$item = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$item = call_user_func_array( $args['item_text'], [ $post, $args, $text ] );

		else if ( $args['item_text'] && Core\Text::has( $args['item_text'], '%' ) )
			$item = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$item = $args['item_text'];

		else
			$item = '';

		if ( $item ) {

			if ( $args['trim_chars'] )
				$item = Core\Text::trimChars( $item, ( TRUE === $args['trim_chars'] ? 45 : $args['trim_chars'] ) );

			if ( $args['item_title_cb'] && is_callable( $args['item_title_cb'] ) )
				$attr = call_user_func_array( $args['item_title_cb'], [ $post, $args, $text ] );

			else if ( 'caption' === $args['item_title'] )
				$attr = WordPress\Attachment::caption( $post, '' );

			else if ( $args['item_title'] )
				$attr = sprintf( $args['item_title'], $text );

			else if ( $args['trim_chars'] )
				$attr = $text;

			else
				$attr = FALSE;

			if ( TRUE === $args['item_link'] ) {

				// Avoid linking to the same page.
				if ( is_singular() && ( $current = WordPress\Post::get() ) )
					$args['item_link'] = $current->ID !== $post->ID;
			}

			if ( $args['item_link'] && 'attachment' == $post->post_type && $args['item_download'] )
				$item = Core\HTML::tag( 'a', [
					'href'     => wp_get_attachment_url( $post->ID ),
					'download' => apply_filters( static::BASE.'_shortcode_attachment_download', basename( get_attached_file( $post->ID ) ), $post ),
					'title'    => $attr,
					'class'    => '-download',
					'rel'      => 'attachment',
				], $item );

			else if ( $args['item_link'] && $link )
				$item = Core\HTML::tag( 'a', [
					'href'  => $link,
					'title' => $attr,
					'class' => '-link -posttype-'.$post->post_type,
				], $item );

			else
				$item = Core\HTML::tag( 'span', [
					'title' => $attr,
					'class' => $args['item_link'] ? '-no-link -future -posttype-'.$post->post_type : '-no-link',
				], $item );

			if ( $args['item_wrap'] && Core\Text::has( $args['item_wrap'], '%' ) )
				$item = sprintf( $args['item_wrap'], $item );

			else if ( $args['item_wrap'] )
				$item = Core\HTML::tag( $args['item_wrap'], $item );

			if ( $args['order_before'] ) {
				$order = $args['order_zeroise'] ? Core\Number::zeroise( $post->menu_order, $args['order_zeroise'] ) : $post->menu_order;
				$item = Core\Number::localize( $order ).( $args['order_sep'] ? $args['order_sep'] : '' ).$item;
			}
		}

		if ( $args['item_filesize'] && 'attachment' === $post->post_type )
			$item.= WordPress\Media::getAttachmentFileSize(
				$post->ID,
				TRUE,
				TRUE === $args['item_filesize']
					? '&nbsp;<span class="-filesize">(%s)</span>'
					: $args['item_filesize']
			);

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $post, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Core\Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					Core\HTML::escapeURL( $link ),
					WordPress\Strings::prepDescription( $post->post_excerpt ),
					Core\Text::trimChars( $post->post_excerpt )
					// FIXME: add post_content
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return Core\HTML::tag( $args['item_tag'], [
			'id'       => $args['item_anchor'] ? sprintf( $args['item_anchor'], $post->ID, $post->post_name ) : FALSE,
			'class'    => $args['item_class'],
			'datetime' => 'publish' == $post->post_status ? date( 'c', strtotime( $post->post_date_gmt ) ) : FALSE,
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	/**
	 * Retrieves given post as an image on the list.
	 *
	 * @param object $post
	 * @param array $atts
	 * @param string $before
	 * @param string $after
	 * @param string $fallback
	 * @return string
	 */
	public static function postImage( $post = NULL, $atts = [], $before = '', $after = '', $fallback = '' )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return $fallback;

		$args = self::atts( [
			'item_link'           => TRUE,
			'item_text'           => NULL,                             // Call-back or use `%s` for post title
			'item_wrap'           => '',                               // Use `%s` for item title / or HTML tag
			'item_title'          => '',                               // Use `%s` for post title
			'item_title_cb'       => FALSE,
			'item_tag'            => 'li',
			'item_anchor'         => FALSE,                            // `$post->post_type.'-%2$s'`,
			'item_class'          => '-item -tile do-sincethen',
			'item_dummy'          => '<span class="-dummy"></span>',
			'item_after'          => '',
			'item_after_cb'       => FALSE,
			'item_image_tile'     => NULL,
			'item_image_metakey'  => NULL,
			'item_image_size'     => NULL,
			'item_image_loading'  => 'lazy',                           // `FALSE` to disable
			'item_image_decoding' => 'async',                          // `FALSE` to disable
			'item_image_empty'    => FALSE,
		], $atts );

		if ( ! $image_id = WordPress\PostType::getThumbnailID( $post->ID, $args['item_image_metakey'] ) )
			return $fallback;

		if ( is_null( $args['item_image_size'] ) )
			$args['item_image_size'] = WordPress\Media::getAttachmentImageDefaultSize( $post->post_type );

		if ( ! $thumbnail_img = WordPress\Media::getAttachmentSrc( $image_id, $args['item_image_size'], FALSE ) )
			return $fallback;

		$text = WordPress\Post::title( $post, FALSE );
		$link = WordPress\Post::link( $post, '' );

		if ( is_null( $args['item_text'] ) )
			$title = $text;

		else if ( $args['item_text'] && is_callable( $args['item_text'] ) )
			$title = call_user_func_array( $args['item_text'], [ $post, $args, $text ] );

		else if ( $args['item_text'] && Core\Text::has( $args['item_text'], '%' ) )
			$title = sprintf( $args['item_text'], $text );

		else if ( $args['item_text'] )
			$title = $args['item_text'];

		else
			$title = '';

		$image = Core\HTML::tag( 'img', [
			'src'      => $thumbnail_img,
			'alt'      => WordPress\Media::getAttachmentImageAlt( $image_id, $title ),
			'class'    => apply_filters( 'get_image_tag_class', '-post-image img-fluid', $image_id, 'none', $args['item_image_size'] ),
			'loading'  => $args['item_image_loading'],
			'decoding' => $args['item_image_decoding'],
		] );

		if ( $link && $args['item_link'] )
			$item = Core\HTML::tag( 'a', [
				'href'  => $link,
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => '-link -type-'.$post->post_type,
			], $image );

		else
			$item = Core\HTML::tag( 'span', [
				'title' => $args['item_title'] ? sprintf( $args['item_title'], $text ) : FALSE,
				'class' => $args['item_link'] ? '-no-link -empty -type-'.$post->post_type : FALSE,
			], $image );

		if ( $args['item_wrap'] && Core\Text::has( $args['item_wrap'], '%' ) )
			$item = sprintf( $args['item_wrap'], $item );

		else if ( $args['item_wrap'] )
			$item = Core\HTML::tag( $args['item_wrap'], $item );

		if ( $args['item_after_cb'] && is_callable( $args['item_after_cb'] ) ) {
			$item.= call_user_func_array( $args['item_after_cb'], [ $post, $args, $item ] );

		} else if ( $args['item_after'] ) {

			if ( Core\Text::has( $args['item_after'], '%' ) )
				$args['item_after'] = sprintf( $args['item_after'],
					$text,
					Core\HTML::escapeURL( $link ),
					WordPress\Strings::prepDescription( $post->post_excerpt ),
					Core\Text::trimChars( $post->post_excerpt )
				);

			if ( is_string( $args['item_after'] ) )
				$item.= $args['item_after'];
		}

		if ( ! $args['item_tag'] )
			return $before.$item.$after;

		return Core\HTML::tag( $args['item_tag'], [
			'id'    => $args['item_anchor'] ? sprintf( $args['item_anchor'], $post->ID, $post->post_name ) : FALSE,
			'class' => $args['item_class'],
		], $before.$item.( $args['item_dummy'] ?: '' ).$after );
	}

	/**
	 * Retrieves default arguments for post/term listing.
	 *
	 * @param string $posttype
	 * @param string $taxonomy
	 * @param array $posttypes
	 * @param array $taxonomies
	 * @param string $module
	 * @return array
	 */
	public static function getDefaults( $posttype = '', $taxonomy = '', $posttypes = [], $taxonomies = [], $module = NULL )
	{
		return [
			'taxonomy'           => $taxonomy,
			'taxonomies'         => $taxonomies,
			'posttype'           => $posttype,
			'posttypes'          => $posttypes,
			'exclude_posttypes'  => [],
			'exclude_taxonomies' => [],

			'post_id' => '',
			'term_id' => '',
			'id'      => '',   // DEPRECATED
			'slug'    => '',
			'metakey' => '',

			'title'          => NULL,                                                            // `FALSE` to disable
			'title_cb'       => FALSE,
			'title_link'     => NULL,                                                            // `anchor` for slug anchor, `FALSE` to disable
			'title_title'    => _x( 'Permanent link', 'ShortCode: Title Attr', 'geditorial' ),
			'title_title_cb' => FALSE,                                                           // The callback for title attribute.
			'title_tag'      => 'h3',
			'title_anchor'   => $taxonomy.'-%2$s',
			'title_class'    => '-title',
			'title_dummy'    => '<span class="-dummy"></span>',
			'title_after'    => '',
			'title_label'    => 'all_items',                                                     // The `label` key for post-type/taxonomy object.

			'item_cb'       => FALSE,
			'item_link'     => TRUE,
			'item_text'     => NULL,                             // Call-back or use `%s` for post title
			'item_wrap'     => '',                               // Use `%s` for item title / or HTML tag
			'item_title'    => '',                               // Use `%s` for post title
			'item_title_cb' => FALSE,
			'item_tag'      => 'li',
			'item_anchor'   => FALSE,                            // $posttype.'-%2$s',
			'item_class'    => '-item do-sincethen',
			'item_dummy'    => '<span class="-dummy"></span>',
			'item_after'    => '',
			'item_after_cb' => FALSE,
			'item_download' => TRUE,                             // only for attachments
			'item_filesize' => TRUE,                             // only for attachments // OLD: `item_size`

			'item_image_tile'     => NULL,
			'item_image_metakey'  => empty( $posttype ) ? 'image' : '_thumbnail_id',
			'item_image_size'     => NULL,
			'item_image_loading'  => 'lazy',                                           // `FALSE` to disable
			'item_image_decoding' => 'async',                                          // `FALSE` to disable
			'item_image_empty'    => FALSE,

			'list_tag'   => 'ul',
			'list_class' => '-list',

			'limit'         => -1,
			'paged'         => 0,
			'order'         => 'ASC',
			'orderby'       => '',            // empty for default
			'order_cb'      => FALSE,         // `NULL` for default order by paired meta
			'order_start'   => 'start',       // meta field for ordering
			'order_order'   => 'order',       // meta field for ordering
			'order_before'  => FALSE,
			'order_zeroise' => FALSE,
			'order_sep'     => ' &ndash; ',

			'cover'      => FALSE,   // must have thumbnail
			'future'     => 'on',
			'mime_type'  => '',      // only for attachments / like: `image`
			'connection' => '',      // only for `o2o`/`p2p`

			'module'       => $module,   // main caller module
			'field_module' => 'meta',    // getting meta field from

			'context' => NULL,
			'wrap'    => TRUE,
			'before'  => '',     // HTML after wrap
			'after'   => '',     // HTML before wrap
			'class'   => '',     // wrap CSS class
		];
	}

	// list: `assigned`: posts by terms
	// list: `paired`: posts by meta (PAIRED API)
	// list: `objects2objects`: posts by `p2p`/`o2o`
	// list: `metadata`: posts by metadata comparison
	// list: `children`: posts by parent of another post-type.
	// list: `attached`: posts by inheritance
	// list: `alphabetized`: posts sorted by alphabet // TODO!
	// list: `custom`: posts by id list // TODO!
	public static function listPosts( $list, $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '', $module = NULL )
	{
		$args = shortcode_atts(
			self::getDefaults(
				$posttype,
				$taxonomy,
				[],
				[],
				$module
			),
			$atts,
			$tag
		);

		if ( FALSE === $args['context'] )
			return NULL;

		// NOTE: back-compatibility
		if ( 'associated' == $list )
			$list = 'paired';

		$key   = static::hash( $list, $args );
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
			'paged'            => $args['paged'],
			'posts_per_page'   => $args['limit'],
			'suppress_filters' => TRUE,
			'no_found_rows'    => TRUE,
			'tax_query'        => [],
			'meta_query'       => [],
		];

		if ( ! empty( $args['posttypes'] ) )
			$query['post_type'] = WordPress\Strings::getSeparated( $args['posttypes'] );

		// NOTE: back-compatibility / DROP THIS
		if ( 'page' == $args['orderby'] )
			$args['orderby'] = 'order';

		if ( $args['orderby'] && 'order' != $args['orderby'] )
			$query['orderby'] = $args['orderby'];

		if ( 'on' == $args['future'] )
			$query['post_status'] = [ 'publish', 'future' ];
		else
			$query['post_status'] = [ 'publish' ];

		if ( 'attached' === $list ) {

			$query['post_type'] = 'attachment';
			$query['post_status'] = [ 'inherit' ];

			if ( $parent = WordPress\Post::get( $args['post_id'] ) )
				$query['post_parent'] = $parent->ID;

			if ( $args['mime_type'] )
				$query['post_mime_type'] = $args['mime_type'];

			if ( ! $args['orderby'] )
				$query['orderby'] = 'menu_order';

		} else if ( 'objects2objects' === $list ) {

			// if ( $posttype && is_post_type_archive( $posttype ) ) {

			// 	$query['post_type'] = $posttype;

			// } else if ( $post = WordPress\Post::get( $args['post_id'] ) ) {
			if ( $post = WordPress\Post::get( $args['post_id'] ) ) {

				$query['connected_type']  = $args['connection'];
				$query['connected_items'] = $post;

			} else {

				return $content;
			}

		} else if ( 'metadata' === $list ) {

			if ( empty( $args['metakey'] ) )
				return $content;

			if ( $args['post_id'] ) {

				$post = WordPress\Post::get( $args['post_id'] );

			} else if ( $posttype && is_singular( $posttype ) ) {

				$post = WordPress\Post::get( get_queried_object_id() );

			} else {

				return $content;
			}

			if ( ! $post )
				return $content;

			$query['meta_query']['relation'] = 'AND';
			$query['meta_query'][] = [
				'key'     => $args['metakey'],
				'value'   => $post->ID,
				'compare' => '=',
			];

			if ( $args['exclude_posttypes'] )
				$query['post_type'] = array_diff(
					WordPress\Strings::getSeparated( $args['posttypes'] ),
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

		} else if ( 'children' === $list ) {

			if ( $args['post_id'] ) {

				$post = WordPress\Post::get( $args['post_id'] );

			} else if ( $posttype && is_singular( $posttype ) ) {

				$post = WordPress\Post::get( get_queried_object_id() );

			} else {

				return $content;
			}

			if ( ! $post )
				return $content;

			$query['post_parent'] = $post->ID;

			if ( $args['exclude_posttypes'] )
				$query['post_type'] = array_diff(
					WordPress\Strings::getSeparated( $args['posttypes'] ),
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

		} else if ( 'all' === $args['id'] || 'all' === $args['term_id'] ) {

			if ( $posttype && empty( $query['post_type'] ) )
				$query['post_type'] = $posttype;

		} else if ( $taxonomy && ( $args['id'] || $args['term_id'] ) ) {

			if ( ! $term = get_term_by( 'id', $args['term_id'] ?: $args['id'], $taxonomy ) )
				return $content;

			$query['tax_query'][] = [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			];

			if ( $args['exclude_posttypes'] )
				$query['post_type'] = array_diff(
					WordPress\Taxonomy::types( $term ),
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

		} else if ( $args['slug'] ) {

			if ( ! $taxonomy )
				return $content;

			if ( ! $term = get_term_by( 'slug', $args['slug'], $taxonomy ) )
				return $content;

			$query['tax_query'][] = [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			];

			if ( $args['exclude_posttypes'] )
				$query['post_type'] = array_diff(
					WordPress\Taxonomy::types( $term ),
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

		} else if ( $posttype && is_post_type_archive( $posttype ) ) {

			$query['post_type'] = $posttype;

		} else if ( $taxonomy && is_tax( $taxonomy ) ) {

			if ( ! $term = get_queried_object() )
				return $content;

			$query['tax_query'][] = [
				'taxonomy' => $term->taxonomy,
				'terms'    => [ $term->term_id ],
			];

			if ( $args['exclude_posttypes'] )
				$query['post_type'] = array_diff(
					WordPress\Taxonomy::types( $term ),
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

		} else if ( 'paired' === $list && $posttype && is_singular( $posttype ) ) {

			// Gets the list of supported posts paired to this post.

			if ( ! $post = WordPress\Post::get( $args['post_id'] ?: NULL ) )
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

			$query['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'terms'    => [ $term->term_id ],
			];

			if ( is_null( $args['title'] ) )
				$args['title'] = FALSE; // disable on main post singular

			$query['ignore_sticky_posts'] = TRUE;
			$query['post__not_in'] = [ $post->ID ]; // exclude current

			// exclude paired post-type
			if ( empty( $args['posttypes'] ) )
				$query['post_type'] = array_diff(
					WordPress\Taxonomy::types( $taxonomy ),
					Core\Arraay::prepString( $posttype, WordPress\Strings::getSeparated( $args['exclude_posttypes'] ) ),
				);

		} else if ( 'paired' === $list && is_singular( $args['posttypes'] ) ) {

			// Gets the list of main posts paired to this supported post.

			if ( ! $args['module'] ) {

				self::_dep( 'must pass module into ShortCode::listPosts( \'paired\' )' );

				return $content;
			}

			if ( ! $paired_posts = gEditorial()->module( $args['module'] )->paired_all_connected_from( $args['post_id'] ?: NULL, 'query', 'ids' ) )
				return $content;

			$query['post_type']           = [ $posttype ];      // override with main post-type
			$query['post__in']            = $paired_posts;
			$query['ignore_sticky_posts'] = TRUE;

		} else if ( 'assigned' === $list && $args['term_id'] ) {

			// Gets the list of posts by the term,

			if ( ! $term = WordPress\Term::get( $args['term_id'] ) )
				return $content;

			$query['tax_query'][] = [
				'taxonomy' => $taxonomy ?: $term->taxonomy,
				'terms'    => [ $term->term_id ],
			];

			if ( $args['exclude_posttypes'] )
				$query['post_type'] = array_diff(
					WordPress\Taxonomy::types( $term ),
					WordPress\Strings::getSeparated( $args['exclude_posttypes'] )
				);

		} else if ( 'paired' === $list || ( 'assigned' === $list && $posttype && is_singular( $posttype ) ) ) {

			// Gets the list of posts by the taxonomy.

			if ( ! $post = WordPress\Post::get( $args['post_id'] ?: NULL ) )
				return $content;

			// NOTE: also used later!
			if ( ! $term = WordPress\Taxonomy::getPostTerms( $taxonomy, $post ) )
				return $content;

			$query['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'terms'    => Core\Arraay::pluck( $term, 'term_id' ),
			];

			$skip = TRUE; // maybe queried itself!

		} else if ( 'assigned' === $list ) {

			return $content;
		}

		if ( $args['item_image_tile']
			&& FALSE === $args['item_image_empty'] ) {

			$query['meta_query'][] = [
				'key'     => $args['item_image_metakey'], // '_thumbnail_id',
				'compare' => 'EXISTS'
			];
		}

		$class = new \WP_Query();
		$items = $class->query( $query );
		$count = count( $items );

		if ( ! $count || ( 1 == $count && $skip ) )
			return $content;

		if ( 'assigned' == $list ) {

			if ( is_null( $args['title'] ) || $args['title'] )
				$args['title'] = ( 'all' === $args['id'] || 'all' === $args['term_id'] )
					? self::posttypeTitle( $posttype, $args )
					: self::termTitle( is_array( $term ) ? array_values($term)[0] : $term, $taxonomy, $args );

			$ref = $term;

		} else if ( in_array( $list, [ 'paired', 'objects2objects', 'metadata', 'children' ], TRUE ) ) {

			if ( is_null( $args['title'] ) || $args['title'] )
				$args['title'] = self::postTitle( $post, $args );

			$ref = $post;

		} else if ( 'attached' == $list ) {

			if ( is_null( $args['title'] ) || $args['title'] )
				$args['title'] = self::postTitle( $parent, $args );

			$ref = $parent;
		}

		if ( $args['orderby'] == 'order' ) {

			// NOTE: the callback may change items, so applies even if only one item exists.
			if ( $args['order_cb'] && is_callable( $args['order_cb'] ) )
				$items = call_user_func_array( $args['order_cb'], [ $items, $args, $ref ] );

			else if ( is_null( $args['order_cb'] ) && $count > 1 )
				$items = Template::reorderPosts( $items,
					$args['field_module'],
					$args['order_start'],
					$args['order_order'] );
		}

		foreach ( $items as $item ) {

			if ( $args['item_cb'] ) {

				// NOTE: no need for call-back to setup post-data
				// @REF: https://developer.wordpress.org/?p=2837#comment-874
				$GLOBALS['post'] = $item;
				setup_postdata( $item );

				$html.= call_user_func_array( $args['item_cb'], [ $item, $args, $ref ] );

			} else if ( $args['item_image_tile'] ) {

				$html.= self::postImage( $item, $args );

			} else {

				$html.= self::postItem( $item, $args );
			}
		}

		if ( $args['list_tag'] )
			$html = Core\HTML::tag( $args['list_tag'], [
				'class' => Core\HTML::attrClass( $args['list_class'], '-posts-list', sprintf( '-type-%s', is_array( $posttype ) ? $posttype[0] : $posttype ) ),
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// NOTE: since callback used setup post data
		if ( $args['item_cb'] )
			wp_reset_postdata();

		wp_cache_set( $key, $html, $group );

		return $html;
	}

	// NOTE: DEPRECATED: use `ShortCode::listPosts( 'assigned' )`
	public static function getTermPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		self::_dep( 'ShortCode::listPosts( \'assigned\' )' );

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
			$query_args['post_status'] = [ 'publish', 'future' ];
		else
			$query_args['post_status'] = [ 'publish' ];

		if ( in_array( 'attachment', (array) $args['posttypes'] ) ) {

			if ( $post = WordPress\Post::get( $args['id'] ) )
				$query_args['post_parent'] = $post->ID;

			if ( $args['mime_type'] )
				$query_args['post_mime_type'] = $args['mime_type'];

			if ( ! $args['orderby'] )
				$query_args['orderby'] = 'menu_order';

			$query_args['post_status'] = [ 'inherit' ];

		} else if ( 'all' == $args['id'] ) {

			// DO NOTHING: will collect all posttype: e.g. all entries of all sections.

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
			if ( ! $term = WordPress\Taxonomy::getPostTerms( $taxonomy ) )
				return $content;

			$query_args['tax_query'] = [ [
				'taxonomy' => $taxonomy,
				'terms'    => Core\Arraay::pluck( $term, 'term_id' ),
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

		$args['title'] = self::termTitle( is_array( $term ) ? array_values($term)[0] : $term, $taxonomy, $args );

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
			$html = Core\HTML::tag( $args['list_tag'], [
				'class' => $args['list_class'],
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );

		// wp_reset_postdata();
		wp_cache_set( $key, $html, $posttype );

		return $html;
	}

	// NOTE: DEPRECATED: use `ShortCode::listPosts( 'paired' )`
	// OLD: `::getAssocPosts()`
	public static function getPairedPosts( $posttype, $taxonomy, $atts = [], $content = NULL, $tag = '' )
	{
		self::_dep( 'ShortCode::listPosts( \'paired\' )' );

		$args = shortcode_atts( self::getDefaults( $posttype, $taxonomy ), $atts, $tag );

		if ( FALSE === $args['context'] )
			return NULL;

		$html = $term = $tax_query = $meta_query = '';
		$post = WordPress\Post::get();

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
			if ( ! $term = WordPress\Taxonomy::getPostTerms( $taxonomy ) )
				return $content;

			$tax_query = [ [
				'taxonomy' => $taxonomy,
				'terms'    => Core\Arraay::pluck( $term, 'term_id' ),
			] ];
		}

		if ( $args['cover'] )
			$meta_query = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$args['title'] = self::postTitle( $post, $args );

		if ( 'on' == $args['future'] )
			$post_status = [ 'publish', 'future' ];
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
			$html = Core\HTML::tag( $args['list_tag'], [
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
		$args = shortcode_atts(
			self::getDefaults(
				'',
				$taxonomy,
				[],
				[],
				$module
			),
			$atts,
			$tag
		);

		if ( FALSE === $args['context'] )
			return NULL;

		$key   = static::hash( $list, $args );
		$group = sprintf( '%s_list_terms', static::BASE );
		$cache = wp_cache_get( $key, $group );

		if ( FALSE !== $cache )
			return $cache;

		$query = [];
		$html  = $ref = $parent = '';

		if ( is_null( $args['module'] ) && static::MODULE )
			$args['module'] = static::MODULE;

		if ( $args['item_cb'] && ! is_callable( $args['item_cb'] ) )
			$args['item_cb'] = FALSE;

		if ( ! empty( $args['taxonomy'] ) ) {

			if ( ! $ref = WordPress\Taxonomy::object( $args['taxonomy'] ) )
				return $content;

			// NOTE: core filter on term queries
			$filtered = apply_filters( 'get_terms_defaults', [], [ $args['taxonomy'] ] );

			if ( ! empty( $filtered['order'] ) )
				$query['order'] = $filtered['order'];

			if ( ! empty( $filtered['orderby'] ) )
				$query['orderby'] = $filtered['orderby'];

			$query['taxonomy']   = $ref->name;
			$query['hide_empty'] = FALSE;

			// $query['update_term_meta_cache'] = FALSE;

		} else if ( 'allitems' === $list ) {

			return $content;
		}

		if ( 'thepost' == $list ) {

			if ( ! $parent = WordPress\Post::get( $args['id'] ) )
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

		$query['order']   = $query['order']   ?? $args['order'];
		$query['orderby'] = $query['orderby'] ?? ( $args['orderby'] ?: 'name' );

		// FIXME: support sort by meta
		if ( 'order' === $args['orderby'] )
			$query['orderby'] = 'none'; // later sorting

		$class = new \WP_Term_Query( $query );
		$items = $class->terms;

		if ( ! $items || ! count( $items ) )
			return $content;

		if ( FALSE === $args['title'] ) {

			// DO NOTHING, title is disabled by the `args`

		} else if ( 'taxonomy' === $args['title'] ) {

			$args['title'] = $args['taxonomy'] ? self::taxonomyTitle( $ref, $args ) : FALSE;

		} else if ( is_null( $args['title'] ) && 'thepost' === $list ) {

			$args['title'] = self::postTitle( $parent, $args );
		}

		if ( $args['orderby'] == 'order' ) {

			// NOTE: the callback may change items, so even if one item.
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
			$html = Core\HTML::tag( $args['list_tag'], [
				'class' => Core\HTML::attrClass( $args['list_class'], ( 'tiles' == $list ? '-term-tiles' : '-terms-list' ) ),
			], $html );

		if ( $args['title'] )
			$html = $args['title'].$html;

		$html = self::wrap( $html, $tag, $args );
		wp_cache_set( $key, $html, $group );

		return $html;
	}
}

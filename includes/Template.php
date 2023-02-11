<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Main;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Strings;
use geminorum\gEditorial\WordPress\Taxonomy;

class Template extends Main
{

	const BASE = 'geditorial';

	public static function getTermImageSrc( $size = NULL, $term_id = NULL, $taxonomy = '' )
	{
		if ( ! $term = Taxonomy::getTerm( $term_id, $taxonomy ) )
			return FALSE;

		if ( ! $term_image_id = get_term_meta( $term->term_id, 'image', TRUE ) )
			return FALSE;

		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( NULL, self::getTermTaxonomy( $term, NULL ) );

		if ( ! $image = image_downsize( $term_image_id, $size ) )
			return FALSE;

		if ( isset( $image[0] ) )
			return $image[0];

		return FALSE;
	}

	public static function getTermImageTag( $atts = [] )
	{
		$args = self::atts( [
			'id'       => NULL,
			'taxonomy' => '',
			'size'     => NULL,
			'alt'      => FALSE,
			'class'    => '-term-image',
		], $atts );

		if ( $src = self::getTermImageSrc( $args['size'], $args['id'], $args['taxonomy'] ) )
			return HTML::img( $src, apply_filters( 'get_image_tag_class', $args['class'], $args['id'], 'none', $args['size'] ), $args['alt'] );

		return FALSE;
	}

	public static function termImageCallback( $image, $link, $args, $status, $title, $module )
	{
		if ( ! $image )
			return $args['default'];

		$html = $image;

		if ( $link )
			$html = '<a title="'.HTML::escape( $args['figure'] ? self::getTermField( 'title', $args['id'], $args['taxonomy'] ) : $title ).'" href="'.$link.'">'.$html.'</a>';

		// enable custom caption
		if ( FALSE === $args['figure'] && $args['caption_text'] )
			$args['figure'] = TRUE;

		if ( $title && $args['figure'] ) {

			$caption = trim( ( $args['caption_text'] ?: $title ) );

			if ( TRUE === $args['caption_link'] && $link )
				$caption = HTML::link( $caption, $link );

			else if ( $args['caption_link'] )
				$caption = HTML::link( $caption, $args['caption_link'] );

			$html = '<figure'.( TRUE === $args['figure'] ? '' : ' class="'.HTML::prepClass( $args['figure'] ).'"' ).'>'.$html.'<figcaption>'.$caption.'</figcaption></figure>';
		}

		if ( empty( $args['wrap'] ) )
			return $html;

		return '<div class="'.HTML::prepClass( static::BASE.'-wrap', ( $module ? '-'.$module : '' ), '-term-image-wrap' ).'">'.$html.'</div>';
	}

	public static function termImage( $atts = [], $module = NULL )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'field'        => 'image',
			'id'           => NULL,
			'size'         => NULL,
			'alt'          => NULL, // null for $args['title']
			'class'        => '-term-image',
			'taxonomy'     => '',
			'link'         => 'archive',
			'title'        => 'name',
			'data'         => [ 'toggle' => 'tooltip' ],
			'callback'     => [ __CLASS__, 'termImageCallback' ],
			'figure'       => FALSE, // or class of the figure
			'caption_text' => FALSE, // custom figcaption text
			'caption_link' => FALSE, // custom figcaption link / TRUE for default
			'fallback'     => FALSE,
			'default'      => FALSE,
			'before'       => '',
			'after'        => '',
			'echo'         => TRUE,
		], $atts );

		if ( FALSE === $args['id'] )
			return $args['default'];

		if ( ! $term = Taxonomy::getTerm( $args['id'], $args['taxonomy'] ) )
			return $args['default'];

		$args['id']       = $term->term_id;
		$args['taxonomy'] = $term->taxonomy;

		$title    = self::getTermField( $args['title'], $term, $args['taxonomy'], FALSE );
		$viewable = Taxonomy::isViewable( $args['taxonomy'] );
		$meta     = get_term_meta( $args['id'], $args['field'], TRUE );

		if ( $args['link'] ) {

			if ( 'archive' == $args['link'] )
				$args['link'] = $viewable ? get_term_link( $args['id'], $args['taxonomy'] ) : FALSE;

			else if ( 'attachment' == $args['link'] )
				$args['link'] = ( $meta && $viewable ) ? get_attachment_link( $meta ) : FALSE;

			else if ( 'url' == $args['link'] )
				$args['link'] = ( $meta && $viewable ) ? wp_get_attachment_url( $meta ) : FALSE;
		}

		if ( is_null( $args['alt'] ) )
			$args['alt'] = $title;

		$html  = '';
		$image = self::getTermImageTag( $args );

		if ( $args['callback'] && is_callable( $args['callback'] ) ) {

			$html = call_user_func_array( $args['callback'], [ $image, $args['link'], $args, $viewable, $title, $module ] );

		} else if ( $image ) {

			$html = HTML::tag( ( $args['link'] ? 'a' : 'span' ), [
				'href'  => $args['link'],
				'title' => $title,
				'data'  => $args['link'] ? $args['data'] : FALSE,
			], $image );

		} else if ( $args['fallback'] && $viewable ) {

			$html = HTML::tag( 'a', [
				'href'  => get_term_link( $args['id'], $args['taxonomy'] ),
				'title' => $title,
				'data'  => $args['data'],
			], sanitize_term_field( 'name', $term->name, $args['id'], $args['taxonomy'], 'display' ) );
		}

		if ( $html ) {

			$html = $args['before'].$html.$args['after'];

			if ( ! $args['echo'] )
				return $html;

			echo $html;
			return TRUE;
		}

		if ( $args['default'] )
			return $args['before'].$args['default'].$args['after'];

		return FALSE;
	}

	public static function termContact( $atts = [], $module = NULL )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'field'    => 'contact',
			'id'       => NULL,
			'class'    => '-term-contact',
			'taxonomy' => '',
			'title'    => _x( 'Contact', 'Template: Term Contact Title', 'geditorial' ), // or term core/meta field
			'default'  => FALSE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
		], $atts );

		if ( FALSE === $args['id'] )
			return $args['default'];

		if ( ! $term = Taxonomy::getTerm( $args['id'], $args['taxonomy'] ) )
			return $args['default'];

		$args['id']       = $term->term_id;
		$args['taxonomy'] = $term->taxonomy;

		$title    = self::getTermField( $args['title'], $term, $args['taxonomy'], FALSE );
		$viewable = Taxonomy::isViewable( $args['taxonomy'] );
		$meta     = get_term_meta( $args['id'], $args['field'], TRUE );

		if ( $html = Helper::prepContact( $meta, $title ) ) {

			$html = $args['before'].$html.$args['after'];

			if ( ! $args['echo'] )
				return $html;

			echo $html;
			return TRUE;
		}

		if ( $args['default'] )
			return $args['before'].$args['default'].$args['after'];

		return FALSE;
	}

	public static function getPostImageSrc( $thumbnail_id = NULL, $size = NULL, $post_id = NULL )
	{
		if ( ! $post = PostType::getPost( $post_id ) )
			return FALSE;

		if ( is_null( $thumbnail_id ) )
			$thumbnail_id = PostType::getThumbnailID( $post->ID );

		if ( ! $thumbnail_id )
			return FALSE;

		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( $post->post_type );

		if ( ! $image = image_downsize( $thumbnail_id, $size ) )
			return FALSE;

		if ( isset( $image[0] ) )
			return $image[0];

		return FALSE;
	}

	public static function getPostImageTag( $atts = [] )
	{
		$html = FALSE;

		$args = self::atts( [
			'id'           => NULL,
			'thumbnail'    => NULL,
			'size'         => NULL,
			'alt'          => NULL,
			'alt_fallback' => 'parent_title',
			'class'        => '-post-image',
		], $atts );

		if ( is_null( $args['alt'] ) && $args['thumbnail'] )
			$args['alt'] = Media::getAttachmentImageAlt( $args['thumbnail'], FALSE );

		if ( ! $args['alt'] && 'parent_title' === $args['alt_fallback'] )
			$args['alt'] = trim( strip_tags( get_the_title( $args['id'] ) ) );

		if ( $src = self::getPostImageSrc( $args['thumbnail'], $args['size'], $args['id'] ) )
			$html = HTML::img( $src, apply_filters( 'get_image_tag_class', $args['class'], $args['id'], 'none', $args['size'] ), $args['alt'] );

		return $html;
	}

	public static function postImageCallback( $image, $link, $args, $status, $title, $module )
	{
		if ( ! $image )
			return $args['default'];

		$html = $image;

		if ( $link )
			$html = '<a title="'.HTML::escape( $args['figure'] ? self::getPostField( 'title', $args['id'] ) : $title ).'" href="'.$link.'">'.$html.'</a>';

		if ( $title && $args['figure'] ) {

			if ( TRUE === $args['caption_link'] && $link )
				$caption = HTML::link( $title, $link );

			else if ( $args['caption_link'] )
				$caption = HTML::link( $title, $args['caption_link'] );

			else
				$caption = $title;

			$html = '<figure'.( TRUE === $args['figure'] ? '' : ' class="'.HTML::prepClass( $args['figure'] ).'"' ).'>'.$html.'<figcaption>'.$caption.'</figcaption></figure>';
		}

		if ( ! $args['wrap'] )
			return $html;

		return '<div class="'.HTML::prepClass( static::BASE.'-wrap', ( $module ? '-'.$module : '' ), '-post-image-wrap' ).'">'.$html.'</div>';
	}

	public static function postImage( $atts = [], $module = NULL )
	{
		$html = '';

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'           => NULL,
			'thumbnail'    => NULL,
			'size'         => NULL,
			'alt'          => NULL, // `FALSE` to disable
			'alt_fallback' => 'parent_title', // `FALSE` to disable
			'class'        => '-post-image',
			'type'         => 'post',
			'link'         => 'parent',
			'title'        => 'title',
			'data'         => [ 'toggle' => 'tooltip' ],
			'callback'     => [ __CLASS__, 'postImageCallback' ],
			'figure'       => FALSE, // or class of the figure
			'caption_link' => FALSE, // custom figcaption link / TRUE for default
			'fallback'     => FALSE,
			'default'      => FALSE,
			'wrap'         => TRUE,
			'before'       => '',
			'after'        => '',
			'echo'         => TRUE,
		], $atts );

		if ( 'latest' == $args['id'] )
			$args['id'] = PostType::getLastMenuOrder( $args['type'], '', 'ID', [ 'publish' ] );

		else if ( 'random' == $args['id'] )
			$args['id'] = PostType::getRandomPostID( $args['type'], TRUE );

		else if ( 'parent' == $args['id'] )
			$args['id'] = PostType::getParentPostID();

		else if ( $module && in_array( $args['id'], [ 'assoc', 'linked', 'paired' ] ) )
			$args['id'] = gEditorial()->module( $module )->get_linked_to_posts( NULL, TRUE );

		if ( FALSE === $args['id'] )
			return $args['default'] ? $args['before'].$args['default'].$args['after'] : $args['default'];

		if ( ! $post = PostType::getPost( $args['id'] ) )
			return $args['default'] ? $args['before'].$args['default'].$args['after'] : $args['default'];

		$title  = self::getPostField( $args['title'], $post->ID, FALSE );
		$status = get_post_status( $post );

		if ( is_null( $args['thumbnail'] ) )
			$args['thumbnail'] = PostType::getThumbnailID( $post->ID );

		if ( $args['link'] ) {

			if ( 'parent' == $args['link'] )
				$args['link'] = in_array( $status, [ 'publish', 'inherit' ] ) ? apply_filters( 'the_permalink', get_permalink( $post ), $post ) : FALSE;

			else if ( 'attachment' == $args['link'] )
				$args['link'] = ( $args['thumbnail'] && in_array( $status, [ 'publish', 'inherit' ] ) ) ? get_attachment_link( $args['thumbnail'] ) : FALSE;

			else if ( 'url' == $args['link'] )
				$args['link'] = ( $args['thumbnail'] && in_array( $status, [ 'publish', 'inherit' ] ) ) ? wp_get_attachment_url( $args['thumbnail'] ) : FALSE;
		}

		$args  = apply_filters( static::BASE.'_template_post_image_args', $args, $post, $module, $title );
		$image = self::getPostImageTag( $args );

		if ( $args['callback'] && is_callable( $args['callback'] ) ) {

			$html = call_user_func_array( $args['callback'], [ $image, $args['link'], $args, $status, $title, $module ] );

		} else if ( $image ) {

			$html = HTML::tag( ( $args['link'] ? 'a' : 'span' ), [
				'href'  => $args['link'] ?: FALSE,
				'title' => $title,
				'data'  => $args['link'] ? $args['data'] : FALSE,
			], $image );

		} else if ( $args['fallback'] && in_array( $status, [ 'publish', 'inherit' ] ) ) {

			$html = HTML::tag( 'a', [
				'href'  => apply_filters( 'the_permalink', get_permalink( $post ), $post ),
				'title' => $title,
				'data'  => $args['data'],
			], get_the_title( $post ) );
		}

		if ( ! $html )
			return $args['default'] ? $args['before'].$args['default'].$args['after'] : $args['default'];

		$html = $args['before'].$html.$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: DEPRECATED
	public static function assocLink( $atts = [], $module = NULL )
	{
		self::_dep( 'Template::pairedLink()' );

		return self::pairedLink( $atts, $module );
	}

	// TODO: duplicate to `pairedList()`
	public static function pairedLink( $atts = [], $module = NULL )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		if ( ! $module )
			return FALSE;

		$args = self::atts( [
			'id'            => NULL,
			'published'     => TRUE,
			'single'        => FALSE,
			'item_tag'      => 'span',
			'item_title'    => '', // use %s for post title
			'item_title_cb' => FALSE, // callback for title attr
			'default'       => FALSE,
			'before'        => '',
			'after'         => '',
			'echo'          => TRUE,
		], $atts );

		if ( ! $posts = gEditorial()->module( $module )->get_linked_to_posts( $args['id'], $args['single'], $args['published'] ) )
			return $args['default'];

		$links = [];

		foreach ( (array) $posts as $post_id )
			if ( $link = ShortCode::postItem( $post_id, $args ) )
				$links[] = $link;

		if ( $html = Strings::getJoined( $links, $args['before'], $args['after'] ) ) {

			if ( ! $args['echo'] )
				return $html;

			echo $html;
			return TRUE;
		}

		if ( $args['default'] )
			return $args['before'].$args['default'].$args['after'];

		return FALSE;
	}

	public static function getTermField( $field = 'name', $term = NULL, $taxonomy = '', $default = '' )
	{
		if ( is_null( $term ) )
			$term = Taxonomy::getTerm( $term, $taxonomy );

		if ( ! $term )
			return $default;

		if ( in_array( $field, [ 'name', 'description', 'slug', 'count' ], TRUE ) )
			return trim( strip_tags( sanitize_term_field( $field, $term->{$field}, $term->term_id, $term->taxonomy, 'display' ) ) );

		// NOTE: meta field supported by Terms module
		if ( ! in_array( $field, [ 'order', 'tagline', 'contact', 'image', 'author', 'color', 'role', 'roles', 'posttype', 'posttypes' ], TRUE ) )
			return $default;

		if ( $meta = get_term_meta( $term->term_id, $field, TRUE ) )
			return trim( $meta );

		return $default;
	}

	public static function getPostField( $field = 'title', $post = NULL, $default = '' )
	{
		if ( 'title' == $field )
			return trim( strip_tags( get_the_title( $post ) ) );

		if ( $field )
			return self::getMetaField( $field, [ 'id' => $post, 'default' => $default ] );

		return $default;
	}

	public static function metaFieldHTML( $field, $atts = [] )
	{
		if ( ! array_key_exists( 'filter', $atts ) )
			$atts['filter'] = [ 'geminorum\\gEditorial\\Helper', 'prepDescription' ];

		return self::metaField( $field, $atts );
	}

	public static function metaField( $field, $atts = [] )
	{
		if ( ! array_key_exists( 'echo', $atts ) )
			$atts['echo'] = TRUE;

		$meta = self::getMetaField( $field, $atts );

		if ( ! $atts['echo'] )
			return $meta;

		echo $meta;
		return TRUE;
	}

	public static function getMetaField( $field, $atts = [], $check = TRUE )
	{
		$args = self::atts( [
			'id'       => NULL,
			'fallback' => FALSE,
			'default'  => FALSE,
			'filter'   => FALSE, // or `__do_embed_shortcode`
			'trim'     => FALSE, // or number of chars
			'before'   => '',
			'after'    => '',
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = PostType::getPost( $args['id'] ) )
			return $args['default'];

		$meta = $raw = self::getMetaFieldRaw( $field, $post->ID, 'meta' );

		if ( FALSE === $meta && $args['fallback'] )
			return self::getMetaField( $args['fallback'], array_merge( $atts, [ 'fallback' => FALSE ] ), FALSE );

		if ( FALSE === $meta )
			return $args['default'];

		$meta = apply_filters( static::BASE.'_meta_field', $meta, $field, $post, $args, $raw );
		$meta = apply_filters( static::BASE.'_meta_field_'.$field, $meta, $field, $post, $args, $raw );

		if ( '__do_embed_shortcode' === $args['filter'] )
			$args['filter'] = [ __CLASS__, 'doEmbedShortCode' ];

		if ( $args['filter'] && is_callable( $args['filter'] ) )
			$meta = call_user_func( $args['filter'], $meta );

		if ( $meta )
			return $args['before'].( $args['trim'] ? Strings::trimChars( $meta, $args['trim'] ) : $meta ).$args['after'];

		return $args['default'];
	}

	public static function getMetaFieldRaw( $field, $post_id, $module = 'meta', $check = FALSE )
	{
		if ( $check ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				return FALSE;

			if ( ! $post = PostType::getPost( $post_id ) )
				return FALSE;

			$post_id = $post->ID;
		}

		$meta = gEditorial()->{$module}->get_postmeta_field( $post_id, $field );

		return apply_filters( static::BASE.'_get_meta_field', $meta, $field, $post_id, $module );
	}

	/**
	 * Applies WordPress embed mechanisem on given url.
	 *
	 * @source https://wordpress.stackexchange.com/a/23213/
	 *
	 * @param string $meta
	 * @return string $html
	 */
	public static function doEmbedShortCode( $meta )
	{
		global $wp_embed;

		if ( ! URL::isValid( $meta ) )
			return $meta;

		return $wp_embed->run_shortcode( sprintf( '[embed src="%s"]%s[/embed]', trim( $meta ), trim( $meta ) ) );
	}

	// FIXME: DEPRECATED
	public static function metaLabel( $atts = [] )
	{
		self::_dep( 'Must extend in sub template!' );
		return self::metaTermField( $atts );
	}

	public static function metaTermField( $atts = [], $module = NULL, $check = TRUE )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'          => NULL,
			'default'     => FALSE,
			'filter'      => FALSE,
			'before'      => '',
			'after'       => '',
			'echo'        => TRUE,
			'field'       => 'label', // FALSE to disable
			'taxonomy'    => gEditorial()->constant( $module, 'label_tax', 'label' ),
			'context'     => NULL, // to use `taxonomy`
			'image'       => FALSE,
			'link'        => NULL, // FALSE to disable
			'description' => NULL, // FALSE to disable
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = PostType::getPost( $args['id'] ) )
			return $args['default'];

		$context = $args['context'] ?: $args['taxonomy'];

		$meta = $args['field'] ? self::getMetaField( $args['field'], [
			'id'     => $post->ID,
			'filter' => $args['filter'],
		], FALSE ) : FALSE;

		if ( $term = Taxonomy::theTerm( $args['taxonomy'], $post->ID, TRUE ) ) {

			if ( ! $meta )
				$meta = sanitize_term_field( 'name', $term->name, $term->term_id, $args['taxonomy'], 'display' );

			if ( is_null( $args['link'] ) )
				$args['link'] = get_term_link( $term, $args['taxonomy'] );

			if ( is_null( $args['description'] ) )
				$args['description'] = trim( strip_tags( $term->description ) );

		} else if ( $meta && is_null( $args['link'] ) ) {

			$args['link'] = WordPress::getSearchLink( $meta );

			if ( is_null( $args['description'] ) )
				/* translators: %s: search query */
				$args['description'] = sprintf( _x( 'Search for %s', 'Template: Search Link Title Attr', 'geditorial' ), $meta );
		}

		$html = $args['image'] ? HTML::img( $args['image'], '-'.$context.'-image', $meta ) : $meta;

		if ( ! $html && $args['default'] )
			$html = $args['default'];

		if ( ! $html )
			return FALSE;

		if ( $args['link'] ) {

			$html = $args['before'].HTML::tag( 'a', [
				'href'  => $args['link'],
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-'.$context.'-link',
				'data'  => $args['description'] ? [ 'toggle' => 'tooltip' ] : FALSE,
			], $html ).$args['after'];

		} else {

			$html = $args['before'].HTML::tag( 'span', [
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-'.$context.'-span',
				'data'  => $args['description'] ? [ 'toggle' => 'tooltip' ] : FALSE,
			], $html ).$args['after'];
		}

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function metaLink( $atts = [], $module = NULL, $check = TRUE )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'            => NULL,
			'default'       => FALSE,
			'filter'        => FALSE,
			'before'        => '',
			'after'         => '',
			'echo'          => TRUE,
			'title_default' => '',
			'title_attr'    => '',
			'title_field'   => 'source_title',
			'url_field'     => 'source_url',
			'url_default'   => FALSE,
			'url_filter'    => FALSE,
			'span_class'    => FALSE,
			'link_class'    => FALSE,
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = PostType::getPost( $args['id'] ) )
			return $args['default'];

		$url = $args['url_field'] ? self::getMetaField( $args['url_field'], [
			'id'      => $post->ID,
			'filter'  => $args['url_filter'],
			'default' => $args['url_default'],
		], FALSE ) : $args['url_default'];

		$prepared = $url ? URL::prepTitle( $url ) : '';

		$title = $args['title_field'] ? self::getMetaField( $args['title_field'], [
			'id'      => $post->ID,
			'filter'  => $args['filter'],
			'default' => $args['title_default'] ?: $prepared,
		], FALSE ) : ( $args['title_default'] ?: $prepared );

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {

			$html = $args['before'].HTML::tag( ( $url ? 'a' : 'span' ), [
				'href'  => $url,
				'class' => $url ? $args['link_class'] : $args['span_class'],
				'title' => $url ? $args['title_attr'] : FALSE,
				'rel'   => $url ? 'nofollow' : 'source', // https://support.google.com/webmasters/answer/96569?hl=en
				'data'  => $url && $args['title_attr'] ? [ 'toggle' => 'tooltip' ] : FALSE,
			], $title ).$args['after'];

		} else {

			$html = $args['default'];
		}

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function metaSource( $atts = [] )
	{
		if ( ! array_key_exists( 'title_field', $atts ) )
			$atts['title_field'] = 'source_title';

		if ( ! array_key_exists( 'url_field', $atts ) )
			$atts['url_field'] = 'source_url';

		if ( ! array_key_exists( 'title_default', $atts ) )
			$atts['title_default'] = _x( 'External Source', 'Template: Meta Link Default Title', 'geditorial' );

		if ( ! array_key_exists( 'title_attr', $atts ) )
			$atts['title_attr'] = _x( 'Visit external source', 'Template: Meta Link Default Title Attr', 'geditorial' );

		return self::metaLink( $atts, 'meta', FALSE );
	}

	public static function metaAction( $atts = [] )
	{
		if ( ! array_key_exists( 'title_field', $atts ) )
			$atts['title_field'] = 'action_title';

		if ( ! array_key_exists( 'url_field', $atts ) )
			$atts['url_field'] = 'action_url';

		if ( ! array_key_exists( 'link_class', $atts ) && ! is_admin() )
			$atts['link_class'] = 'button -button';

		return self::metaLink( $atts, 'meta', FALSE );
	}

	public static function metaSummary( $atts = [], $module = NULL, $check = TRUE )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'       => NULL,
			'fields'   => NULL,
			'excludes' => NULL,
			'type'     => NULL, // default to current post
			'default'  => FALSE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = PostType::getPost( $args['id'] ) )
			return $args['default'];

		$rows     = [];
		$posttype = $args['type'] ?: $post->post_type;
		$fields   = gEditorial()->module( 'meta' )->get_posttype_fields( $posttype );
		$list     = $args['fields'] ?: wp_list_pluck( $fields, 'title', 'name' );
		$excludes = is_null( $args['excludes'] ) ? [
			'over_title',
			'sub_title',
			// 'byline',
			'highlight',
			'dashboard',
			'abstract',
			'foreword',
			'source_title',
			'source_url',
			'action_title',
			'action_url',
			'cover_blurb',
			'content_embed_url',
			'audio_source_url',
			'video_source_url',
			'map_embed_url',
			'parent_complex',
			'geo_latitude',
			'geo_longitude',
		] : (array) $args['excludes'];

		foreach ( $list as $key => $title ) {

			if ( in_array( $key, $excludes ) )
				continue;

			if ( ! array_key_exists( $key, $fields ) ) {

				// fallback to taxonomy, only on custom set of fields
				if ( is_null( $args['fields'] ) )
					continue;

				if ( taxonomy_exists( $key ) ) {

					if ( ! is_object_in_taxonomy( $posttype, $key ) )
						continue;

					if ( ! $terms = Taxonomy::getTheTermList( $key, $post ) )
						continue;

					if ( is_null( $title ) )
						$title = Taxonomy::object( $key )->labels->singular_name;

					$rows[$title] = $terms;
				}

				continue;
			}

			$field = $fields[$key];

			if ( is_null( $title ) )
				$title = $field['title'];

			if ( 'term' == $field['type'] )
				$meta = self::metaTermField( [
					'id'       => $post,
					'field'    => FALSE,
					'link'     => FALSE,
					'echo'     => FALSE,
					'taxonomy' => $field['taxonomy'],
				], 'meta', FALSE );

			else
				$meta = self::getMetaField( $key, [
					'id' => $post,
				], FALSE );

			if ( $meta )
				$rows[$title] = $meta;
		}

		$rows = apply_filters( static::BASE.'_meta_summary_rows', $rows, $list, $post, $args );

		if ( empty( $rows ) )
			return $args['default'];

		$html = $args['before'].self::getTableSummary( $rows ).$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	// FIXME: temp!
	public static function getTableSummary( $data )
	{
		$html = '<table class="table table-bordered">';

		foreach ( $data as $key => $value ) {
			$html.= '<tr><td>';
				$html.= $key;
			$html.= '</td><td>';
				$html.= $value;
			$html.= '</td></tr>';
		}

		return $html.'</table>';
	}

	// FIXME: DEPRECATED
	public static function sanitizeField( $field )
	{
		self::_dep( 'NO NEED!' );

		if ( is_array( $field ) )
			return $field;

		$fields = [
			'over-title'           => [ 'over_title', 'ot' ],
			'sub-title'            => [ 'sub_title', 'st' ],
			'label'                => [ 'ch', 'label', 'column_header' ],
			'lead'                 => [ 'le', 'lead' ],
			'author'               => [ 'as', 'author' ],
			'number'               => [ 'number_line', 'issue_number_line', 'number' ],
			'pages'                => [ 'issue_total_pages', 'pages' ],
			'start'                => [ 'in_issue_page_start', 'start' ],
			'order'                => [ 'in_issue_order', 'in_collection_order', 'in_series_order', 'order' ],
			'reshare_source_title' => [ 'source_title', 'reshare_source_title' ],
			'reshare_source_url'   => [ 'source_url', 'reshare_source_url' ],
		];

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return [ $field ];
	}

	public static function reorderPosts( $posts, $field_module = 'meta', $field_start = 'start', $field_order = 'order' )
	{
		$i = 1000;
		$o = [];

		foreach ( $posts as $post ) {

			$start = self::getMetaFieldRaw( $field_start, $post->ID, $field_module );
			$order = self::getMetaFieldRaw( $field_order, $post->ID, $field_module );

			$key = $start ? ( (int) $start * 10 ) : 0;
			$key = $order ? ( $key + (int) $order ) : $key;
			$key = $key ? $key : ( $i * 100 );

			$i++;
			// $post->menu_order = $start;

			$o[$key] = $post;
		}

		unset( $posts, $post );
		ksort( $o );

		return $o;
	}

	public static function renderRecentByPosttype( $posttype, $link = 'view', $empty = NULL, $title_attr = NULL, $extra = [] )
	{
		if ( ! $object = PostType::object( $posttype ) )
			return;

		$posts = PostType::getRecent( $object->name, $extra, current_user_can( $object->cap->edit_posts ) );

		if ( count( $posts ) ) {

			$list = [];

			foreach ( $posts as $post )
				$list[] = Helper::getPostTitleRow( $post, $link, FALSE, $title_attr );

			/* translators: %s: posttype name */
			HTML::h3( sprintf( _x( 'Recent %s', 'Template: Recents', 'geditorial' ), $object->labels->name ) );
			echo HTML::renderList( $list );

		} else if ( is_null( $empty ) ) {

			/* translators: %s: posttype name */
			HTML::h3( sprintf( _x( 'Recent %s', 'Template: Recents', 'geditorial' ), $object->labels->name ) );
			HTML::desc( $object->labels->not_found, TRUE, '-empty -empty-posttype-'.$object->name );

		} else if ( $empty ) {

			echo $empty;
		}
	}

	// @EXAMPLE: https://dastan.ourmag.ir/archives/issues/
	public static function getSpanTiles( $atts = [], $module = NULL )
	{
		$html = '';

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'taxonomy' => NULL,
			'posttype' => NULL,
			'fallback' => '',
			'before'   => '',
			'after'    => '',
			'wrap'     => TRUE,
		], $atts );

		if ( empty( $args['posttype'] ) || empty( $args['taxonomy'] ) )
			return $args['fallback'];

		$terms = Taxonomy::listTerms( $args['taxonomy'], 'ids', [ 'order' => 'DESC' ] );

		foreach ( $terms as $term_id ) {

			$span = ShortCode::listPosts( 'assigned',
				$args['posttype'],
				$args['taxonomy'], [
					'id'              => $term_id,
					'wrap'            => FALSE,
					'future'          => 'off',
					'list_class'      => '-tiles',
					'title_anchor'    => '%2$s',
					'title_link'      => 'anchor',
					'item_image_tile' => TRUE,
				],
				NULL,
				'',
				$module
			);

			if ( ! empty( $span ) )
				$html.= HTML::wrap( $span, '-tile-row' );
		}

		if ( empty( $html ) )
			return $args['fallback'];

		$html = $args['before'].$html.$args['after'];

		return $args['wrap'] ? HTML::wrap( $html, static::BASE.'-span-tiles' ) : $html;
	}
}

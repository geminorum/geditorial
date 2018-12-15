<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Taxonomy;

class Template extends Core\Base
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	protected static function post_types( $posttypes = NULL )
	{
		if ( static::MODULE && gEditorial()->enabled( static::MODULE ) )
			return gEditorial()->{static::MODULE}->posttypes( $posttypes );

		return [];
	}

	public static function getTermImageSrc( $size = NULL, $term_id = NULL, $taxonomy = '' )
	{
		if ( ! $term = Taxonomy::getTerm( $term_id, $taxonomy ) )
			return FALSE;

		if ( ! $term_image_id = get_term_meta( $term->term_id, 'image', TRUE ) )
			return FALSE;

		if ( is_null( $size ) )
			$size = 'thumbnail';

		if ( ! $image = image_downsize( $term_image_id, $size ) )
			return FALSE;

		if ( isset( $image[0] ) )
			return $image[0];

		return FALSE;
	}

	public static function getTermImageTag( $atts = [] )
	{
		$html = FALSE;

		$args = self::atts( [
			'id'       => NULL,
			'taxonomy' => '',
			'size'     => NULL,
			'alt'      => FALSE,
			'class'    => '-term-image',
		], $atts );

		if ( $src = self::getTermImageSrc( $args['size'], $args['id'], $args['taxonomy'] ) )
			$html = HTML::img( $src, apply_filters( 'get_image_tag_class', $args['class'], $args['id'], 'none', $args['size'] ), $args['alt'] );

		return $html;
	}

	public static function termImageCallback( $image, $link, $args, $status, $title, $module )
	{
		if ( ! $image )
			return $args['default'];

		$html = $image;

		if ( $link )
			$html = '<a title="'.HTML::escape( $args['figure'] ? self::getTermField( 'title', $args['id'], $args['taxonomy'] ) : $title ).'" href="'.$link.'">'.$html.'</a>';

		if ( $title && $args['figure'] )
			$html = '<figure'.( TRUE === $args['figure'] ? '' : ' class="'.$args['figure'].'"' ).'>'.$html.'<figcaption>'.$title.'</figcaption></figure>';

		if ( ! $args['wrap'] )
			return $html;

		return '<div class="'.static::BASE.'-wrap'.( $module ? ' -'.$module : ' ' ).' -term-image-wrap">'.$html.'</div>';
	}

	public static function termImage( $atts = [], $module = NULL )
	{
		$html = '';

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'       => NULL,
			'size'     => NULL,
			'alt'      => FALSE,
			'class'    => '-term-image',
			'taxonomy' => '',
			'link'     => 'archive',
			'title'    => 'name',
			'data'     => [ 'toggle' => 'tooltip' ],
			'callback' => [ __CLASS__, 'termImageCallback' ],
			'figure'   => FALSE, // or class of the figure
			'fallback' => FALSE,
			'default'  => FALSE,
			'wrap'     => TRUE,
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

		$title     = self::getTermField( $args['title'], $term, $args['taxonomy'], FALSE );
		$viewable  = Taxonomy::isViewable( $args['taxonomy'] );
		$thumbnail = get_term_meta( $args['id'], 'image', TRUE );

		if ( $args['link'] ) {

			if ( 'archive' == $args['link'] )
				$args['link'] = $viewable ? get_term_link( $args['id'], $args['taxonomy'] ) : FALSE;

			else if ( 'attachment' == $args['link'] )
				$args['link'] = ( $thumbnail && $viewable ) ? get_attachment_link( $thumbnail ) : FALSE;

			else if ( 'url' == $args['link'] )
				$args['link'] = ( $thumbnail && $viewable ) ? wp_get_attachment_url( $thumbnail ) : FALSE;
		}

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

	public static function getPostImageSrc( $size = NULL, $post_id = NULL )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		if ( ! $thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', TRUE ) )
			return FALSE;

		if ( is_null( $size ) )
			$size = $post->post_type.'-thumbnail';

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
			'id'    => NULL,
			'size'  => NULL,
			'alt'   => FALSE,
			'class' => '-post-image',
		], $atts );

		if ( $src = self::getPostImageSrc( $args['size'], $args['id'] ) )
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

		if ( $title && $args['figure'] )
			$html = '<figure'.( TRUE === $args['figure'] ? '' : ' class="'.$args['figure'].'"' ).'>'.$html.'<figcaption>'.$title.'</figcaption></figure>';

		if ( ! $args['wrap'] )
			return $html;

		return '<div class="'.static::BASE.'-wrap'.( $module ? ' -'.$module : ' ' ).' -post-image-wrap">'.$html.'</div>';
	}

	public static function postImage( $atts = [], $module = NULL )
	{
		$html = '';

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'       => NULL,
			'size'     => NULL,
			'alt'      => FALSE,
			'class'    => '-post-image',
			'type'     => 'post',
			'link'     => 'parent',
			'title'    => 'title',
			'data'     => [ 'toggle' => 'tooltip' ],
			'callback' => [ __CLASS__, 'postImageCallback' ],
			'figure'   => FALSE, // or class of the figure
			'fallback' => FALSE,
			'default'  => FALSE,
			'wrap'     => TRUE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
		], $atts );

		if ( 'latest' == $args['id'] )
			$args['id'] = WordPress::getLastPostOrder( $args['type'], '', 'ID', [ 'publish' ] );

		else if ( 'random' == $args['id'] )
			$args['id'] = WordPress::getRandomPostID( $args['type'], TRUE );

		else if ( 'parent' == $args['id'] )
			$args['id'] = WordPress::getParentPostID();

		else if ( 'assoc' == $args['id'] && $module )
			$args['id'] = gEditorial()->{$module}->get_assoc_post( NULL, TRUE );

		if ( FALSE === $args['id'] )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		$args['id'] = $post->ID;

		$title     = self::getPostField( $args['title'], $args['id'], FALSE );
		$status    = get_post_status( $args['id'] );
		$thumbnail = get_post_meta( $args['id'], '_thumbnail_id', TRUE );

		if ( $args['link'] ) {

			if ( 'parent' == $args['link'] )
				$args['link'] = 'publish' == $status ? get_permalink( $args['id'] ) : FALSE;

			else if ( 'attachment' == $args['link'] )
				$args['link'] = ( $thumbnail && 'publish' == $status ) ? get_attachment_link( $thumbnail ) : FALSE;

			else if ( 'url' == $args['link'] )
				$args['link'] = ( $thumbnail && 'publish' == $status ) ? wp_get_attachment_url( $thumbnail ) : FALSE;
		}

		$image = self::getPostImageTag( $args );

		if ( $args['callback'] && is_callable( $args['callback'] ) ) {

			$html = call_user_func_array( $args['callback'], [ $image, $args['link'], $args, $status, $title, $module ] );

		} else if ( $image ) {

			$html = HTML::tag( ( $args['link'] ? 'a' : 'span' ), [
				'href'  => $args['link'],
				'title' => $title,
				'data'  => $args['link'] ? $args['data'] : FALSE,
			], $image );

		} else if ( $args['fallback'] && 'publish' == $status ) {

			$html = HTML::tag( 'a', [
				'href'  => get_permalink( $args['id'] ),
				'title' => $title,
				'data'  => $args['data'],
			], get_the_title( $args['id'] ) );
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

	public static function assocLink( $atts = [], $module = NULL )
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

		if ( ! $posts = gEditorial()->{$module}->get_assoc_post( $args['id'], $args['single'], $args['published'] ) )
			return $args['default'];

		$links = [];

		foreach ( (array) $posts as $post_id )
			if ( $link = ShortCode::postItem( $post_id, $args ) )
				$links[] = $link;

		if ( $html = Helper::getJoined( $links, $args['before'], $args['after'] ) ) {

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

		if ( 'name' == $field )
			return trim( strip_tags( sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' ) ) );

		// TODO: get other meta fields

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

	public static function getMetaField( $fields, $atts = [], $check = TRUE )
	{
		$args = self::atts( [
			'id'      => NULL,
			'default' => FALSE,
			'filter'  => FALSE,
			'trim'    => FALSE, // or number of chars
			'before'  => '',
			'after'   => '',
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		foreach ( self::sanitizeField( $fields ) as $field ) {

			$meta = gEditorial()->meta->get_postmeta( $post->ID, $field, FALSE );

			if ( FALSE === $meta )
				continue;

			$meta = apply_filters( static::BASE.'_meta_field', $meta, $field, $post, $args );

			if ( $args['filter'] && is_callable( $args['filter'] ) )
				$meta = call_user_func( $args['filter'], $meta );

			if ( $meta )
				return $args['before'].( $args['trim'] ? Helper::trimChars( $meta, $args['trim'] ) : $meta ).$args['after'];
		}

		return $args['default'];
	}

	// WARNING: caller must check for module
	public static function getMetaFieldRaw( $fields, $post_id, $module = 'meta' )
	{
		foreach ( self::sanitizeField( $fields ) as $field ) {

			$meta = gEditorial()->{$module}->get_postmeta( $post_id, $field, FALSE );

			if ( FALSE !== $meta )
				return $meta;
		}
	}

	public static function metaLabel( $atts = [], $module = NULL, $chack = TRUE )
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
			'field'       => 'label',
			'taxonomy'    => gEditorial()->constant( $module, 'ct_tax', 'label' ),
			'image'       => FALSE,
			'link'        => NULL, // FALSE to disable
			'description' => NULL, // FALSE to disable
		], $atts );

		if ( $chack && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		$meta = self::getMetaField( $args['field'], [
			'id'     => $post->ID,
			'filter' => $args['filter'],
		], FALSE );

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
				$args['description'] = sprintf( _x( 'Search for %s', 'Template: Search Link Title Attr', GEDITORIAL_TEXTDOMAIN ), $meta );
		}

		$html = $args['image'] ? HTML::img( $args['image'], '-label-image', $meta ) : $meta;

		if ( ! $html && $args['default'] )
			$html = $args['default'];

		if ( ! $html )
			return FALSE;

		if ( $args['link'] ) {

			$html = $args['before'].HTML::tag( 'a', [
				'href'  => $args['link'],
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-label-link',
				'data'  => $args['description'] ? [ 'toggle' => 'tooltip' ] : FALSE,
			], $html ).$args['after'];

		} else {

			$html = $args['before'].HTML::tag( 'span', [
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-label-span',
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
			'title_field'   => 'source_title',
			'title_default' => _x( 'External Source', 'Template: Meta Link Default Title', GEDITORIAL_TEXTDOMAIN ),
			'title_attr'    => _x( 'Visit external source', 'Template: Meta Link Default Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'url_field'     => 'source_url',
			'url_default'   => FALSE,
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		$title = $args['title_field'] ? self::getMetaField( $args['title_field'], [
			'id'      => $post->ID,
			'filter'  => $args['filter'],
			'default' => $args['title_default'],
		], FALSE ) : $args['title_default'];

		$url = $args['url_field'] ? self::getMetaField( $args['url_field'], [
			'id'      => $post->ID,
			'default' => $args['url_default'],
		], FALSE ) : $args['url_default'];

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {
			$html = $args['before'].HTML::tag( ( $url ? 'a' : 'span' ), [
				'href'  => $url,
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

	public static function sanitizeField( $field )
	{
		if ( is_array( $field ) )
			return $field;

		$fields = [
			'over-title'           => [ 'over_title', 'ot' ],
			'sub-title'            => [ 'sub_title', 'st' ],
			'label'                => [ 'ch', 'label', 'column_header' ],
			'lead'                 => [ 'le', 'lead' ],
			'author'               => [ 'as', 'author' ],
			'number'               => [ 'issue_number_line', 'number' ],
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
}

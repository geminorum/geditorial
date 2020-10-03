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

		if ( ! $args['wrap'] )
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
			'size'         => NULL,
			'alt'          => FALSE,
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

		$title     = self::getPostField( $args['title'], $post->ID, FALSE );
		$status    = get_post_status( $post );
		$thumbnail = get_post_meta( $post->ID, '_thumbnail_id', TRUE );

		if ( $args['link'] ) {

			if ( 'parent' == $args['link'] )
				$args['link'] = in_array( $status, [ 'publish', 'inherit' ] ) ? apply_filters( 'the_permalink', get_permalink( $post ), $post ) : FALSE;

			else if ( 'attachment' == $args['link'] )
				$args['link'] = ( $thumbnail && in_array( $status, [ 'publish', 'inherit' ] ) ) ? get_attachment_link( $thumbnail ) : FALSE;

			else if ( 'url' == $args['link'] )
				$args['link'] = ( $thumbnail && in_array( $status, [ 'publish', 'inherit' ] ) ) ? wp_get_attachment_url( $thumbnail ) : FALSE;
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

		} else if ( $args['fallback'] && in_array( $status, [ 'publish', 'inherit' ] ) ) {

			$html = HTML::tag( 'a', [
				'href'  => apply_filters( 'the_permalink', get_permalink( $post ), $post ),
				'title' => $title,
				'data'  => $args['data'],
			], get_the_title( $post ) );
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

		$meta = self::getMetaFieldRaw( $field, $post->ID, 'meta' );

		if ( FALSE === $meta )
			return $args['default'];

		$meta = apply_filters( static::BASE.'_meta_field', $meta, $field, $post, $args );

		if ( $args['filter'] && is_callable( $args['filter'] ) )
			$meta = call_user_func( $args['filter'], $meta );

		if ( $meta )
			return $args['before'].( $args['trim'] ? Helper::trimChars( $meta, $args['trim'] ) : $meta ).$args['after'];

		return $args['default'];
	}

	public static function getMetaFieldRaw( $field, $post_id, $module = 'meta', $check = FALSE )
	{
		if ( $check ) {

			if ( ! gEditorial()->enabled( 'meta' ) )
				return FALSE;

			if ( ! $post = get_post( $post_id ) )
				return FALSE;

			$post_id = $post->ID;
		}

		return gEditorial()->{$module}->get_postmeta_field( $post_id, $field );
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

		if ( ! $post = get_post( $args['id'] ) )
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
			'title_field'   => 'source_title',
			'title_default' => _x( 'External Source', 'Template: Meta Link Default Title', 'geditorial' ),
			'title_attr'    => _x( 'Visit external source', 'Template: Meta Link Default Title Attr', 'geditorial' ),
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

	public static function metaSummary( $atts = [], $module = NULL, $check = TRUE )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'id'      => NULL,
			'fields'  => NULL,
			'type'    => NULL, // default to current post
			'default' => FALSE,
			'before'  => '',
			'after'   => '',
			'echo'    => TRUE,
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		$posttype = $args['type'] ?: $post->post_type;
		$fields   = gEditorial()->meta->get_posttype_fields( $posttype );
		$list     = $args['fields'] ?: wp_list_pluck( $fields, 'title', 'name' );
		$rows     = [];

		foreach ( $list as $key => $title ) {

			if ( ! array_key_exists( $key, $fields ) ) {

				// fallback to taxonomy, only if fields are passed
				if ( is_null( $args['fields'] ) )
					continue;

				if ( taxonomy_exists( $key ) && is_object_in_taxonomy( $posttype, $key ) ) {

					if ( ! $term = Taxonomy::theTerm( $key, $post->ID, TRUE ) )
						continue;

					if ( is_null( $title ) )
						$title = Taxonomy::object( $key )->labels->singular_name;

					if ( $meta = sanitize_term_field( 'name', $term->name, $term->term_id, $key, 'display' ) )
						$rows[$title] = HTML::link( $meta, get_term_link( $term, $key ) );
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
					'taxonomy' => $field['tax'],
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
}

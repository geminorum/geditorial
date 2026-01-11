<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Template extends WordPress\Main
{

	const BASE = 'geditorial';

	public static function factory()
	{
		return gEditorial();
	}

	public static function perPageItems( $default = NULL )
	{
		if ( WordPress\WooCommerce::isActive() )
			return WordPress\WooCommerce::getDefaultColumns() * WordPress\WooCommercegetDefaultRows();

		return get_option( 'posts_per_page', $default ?? 10 );
	}

	public static function perRowColumns( $default = NULL )
	{
		if ( WordPress\WooCommerce::isActive() )
			return WordPress\WooCommerce::getDefaultColumns( $default );

		return $default ?? ''; // maybe add `Customizer` control
	}

	public static function getTermImageSrc( $size = NULL, $term_id = NULL, $taxonomy = '', $metakey = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term_id, $taxonomy ) )
			return FALSE;

		if ( ! $term_image_id = get_term_meta( $term->term_id, $metakey ?? 'image', TRUE ) )
			return FALSE;

		if ( is_null( $size ) )
			$size = WordPress\Media::getAttachmentImageDefaultSize( NULL, WordPress\Term::taxonomy( $term ) ?: NULL );

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
			'field'    => NULL,
			'size'     => NULL,
			'alt'      => FALSE,
			'class'    => '-term-image img-fluid',
		], $atts );

		if ( $src = self::getTermImageSrc( $args['size'], $args['id'], $args['taxonomy'], $args['field'] ) )
			return Core\HTML::img( $src, apply_filters( 'get_image_tag_class', $args['class'], $args['id'], 'none', $args['size'] ), $args['alt'] );

		return FALSE;
	}

	public static function termImageCallback( $image, $link, $args, $status, $title, $module )
	{
		if ( ! $image )
			return $args['default'];

		$html = $image;

		if ( $link )
			$html = '<a title="'.Core\HTML::escape( $args['figure'] ? self::getTermField( 'title', $args['id'], $args['taxonomy'] ) : $title ).'" href="'.$link.'">'.$html.'</a>';

		// enable custom caption
		if ( FALSE === $args['figure'] && $args['caption_text'] )
			$args['figure'] = TRUE;

		if ( $title && $args['figure'] ) {

			$caption = trim( ( $args['caption_text'] ?: $title ) );

			if ( TRUE === $args['caption_link'] && $link )
				$caption = Core\HTML::link( $caption, $link );

			else if ( $args['caption_link'] )
				$caption = Core\HTML::link( $caption, $args['caption_link'] );

			$html = '<figure'.( TRUE === $args['figure'] ? '' : ' class="'.Core\HTML::prepClass( $args['figure'] ).'"' ).'>'.$html.'<figcaption>'.$caption.'</figcaption></figure>';
		}

		if ( empty( $args['wrap'] ) )
			return $html;

		return '<div class="'.Core\HTML::prepClass( static::BASE.'-wrap', ( $module ? '-'.$module : '' ), '-term-image-wrap' ).'">'.$html.'</div>';
	}

	public static function termImage( $atts = [], $module = NULL )
	{
		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'field'        => NULL,                                 // NULL for `image`
			'id'           => NULL,
			'size'         => NULL,
			'alt'          => NULL,                                 // NULL for `$args['title']`
			'class'        => '-term-image img-fluid',
			'taxonomy'     => '',
			'link'         => 'archive',
			'title'        => 'name',
			'data'         => [ 'toggle' => 'tooltip' ],
			'callback'     => [ __CLASS__, 'termImageCallback' ],
			'figure'       => FALSE,                                // or class of the figure
			'caption_text' => FALSE,                                // custom `figcaption` text
			'caption_link' => FALSE,                                // custom `figcaption` link / TRUE for default
			'fallback'     => FALSE,
			'default'      => FALSE,
			'context'      => NULL,
			'before'       => '',
			'after'        => '',
			'echo'         => TRUE,
		], $atts );

		if ( FALSE === $args['id'] )
			return $args['default'];

		if ( ! $term = WordPress\Term::get( $args['id'], $args['taxonomy'] ) )
			return $args['default'];

		$args['id']       = $term->term_id;
		$args['taxonomy'] = $term->taxonomy;

		$title    = self::getTermField( $args['title'], $term, $args['taxonomy'], FALSE );
		$viewable = WordPress\Taxonomy::viewable( $args['taxonomy'] );
		$meta     = get_term_meta( $args['id'], $args['field'] ?? 'image', TRUE );

		if ( $args['link'] ) {

			if ( 'archive' === $args['link'] )
				$args['link'] = $viewable ? WordPress\Term::link( (int) $args['id'], FALSE ) : FALSE;

			else if ( 'attachment' === $args['link'] )
				$args['link'] = ( $meta && $viewable ) ? get_attachment_link( (int) $meta ) : FALSE;

			else if ( 'url' === $args['link'] )
				$args['link'] = ( $meta && $viewable ) ? wp_get_attachment_url( (int) $meta ) : FALSE;
		}

		if ( is_null( $args['alt'] ) )
			$args['alt'] = $title;

		$html  = '';
		$image = self::getTermImageTag( $args );

		if ( $args['callback'] && is_callable( $args['callback'] ) ) {

			$html = call_user_func_array( $args['callback'], [ $image, $args['link'], $args, $viewable, $title, $module ] );

		} else if ( $image ) {

			$html = Core\HTML::tag( ( $args['link'] ? 'a' : 'span' ), [
				'href'  => $args['link'],
				'title' => $title,
				'data'  => $args['link'] ? $args['data'] : FALSE,
			], $image );

		} else if ( $args['fallback'] && $viewable ) {

			$html = Core\HTML::tag( 'a', [
				'href'  => WordPress\Term::link( (int) $args['id'] ),
				'title' => $title,
				'data'  => $args['data'],
			], WordPress\Term::title( $term ) );
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
			'title'    => _x( 'Contact', 'Template: Term Contact Title', 'geditorial' ),   // or term core/meta field
			'default'  => FALSE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
		], $atts );

		if ( FALSE === $args['id'] )
			return $args['default'];

		if ( ! $term = WordPress\Term::get( $args['id'], $args['taxonomy'] ) )
			return $args['default'];

		$args['id']       = $term->term_id;
		$args['taxonomy'] = $term->taxonomy;

		$title = self::getTermField( $args['title'], $term, $args['taxonomy'], FALSE );
		$meta  = get_term_meta( $args['id'], $args['field'], TRUE );

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
		self::_dep( 'WordPress\Post::image()' );

		if ( ! $post = WordPress\Post::get( $post_id ) )
			return FALSE;

		if ( is_null( $thumbnail_id ) )
			$thumbnail_id = WordPress\PostType::getThumbnailID( $post->ID );

		if ( ! $thumbnail_id )
			return FALSE;

		if ( is_null( $size ) )
			$size = WordPress\Media::getAttachmentImageDefaultSize( $post->post_type );

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
			'class'        => '-post-image img-fluid',
		], $atts );

		if ( is_null( $args['alt'] ) && $args['thumbnail'] )
			$args['alt'] = WordPress\Media::getAttachmentImageAlt( $args['thumbnail'], FALSE );

		if ( ! $args['alt'] && 'parent_title' === $args['alt_fallback'] )
			$args['alt'] = Core\Text::stripTags( get_the_title( $args['id'] ) );

		if ( $src = WordPress\Post::image( $args['id'], NULL, $args['size'], $args['thumbnail'] ) )
			$html = Core\HTML::img( $src, apply_filters( 'get_image_tag_class', $args['class'], $args['id'], 'none', $args['size'] ), $args['alt'] );

		return $html;
	}

	public static function postImageCallback( $image, $link, $args, $status, $title, $module )
	{
		if ( ! $image )
			return $args['default'];

		$html = $image;

		if ( $link )
			$html = '<a title="'.Core\HTML::escape( $args['figure'] ? self::getPostField( 'title', $args['id'] ) : $title ).'"'.( $args['newtab'] ? ' target="_blank"' : ' ' ).'href="'.$link.'">'.$html.'</a>';

		if ( $title && $args['figure'] ) {

			if ( TRUE === $args['caption_link'] && $link )
				$caption = Core\HTML::link( $title, $link );

			else if ( $args['caption_link'] )
				$caption = Core\HTML::link( $title, $args['caption_link'] );

			else
				$caption = $title;

			$html = '<figure'.( TRUE === $args['figure'] ? '' : ' class="'.Core\HTML::prepClass( $args['figure'] ).'"' ).'>'.$html.'<figcaption>'.$caption.'</figcaption></figure>';
		}

		if ( ! $args['wrap'] )
			return $html;

		return '<div class="'.Core\HTML::prepClass( static::BASE.'-wrap', ( $module ? '-'.$module : '' ), '-post-image-wrap' ).'">'.$html.'</div>';
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
			'alt'          => NULL,                                 // `FALSE` to disable
			'alt_fallback' => 'parent_title',                       // `FALSE` to disable
			'class'        => '-post-image img-fluid',
			'type'         => 'post',
			'link'         => 'parent',
			'newtab'       => FALSE,
			'title'        => 'title',
			'data'         => [ 'toggle' => 'tooltip' ],
			'callback'     => [ __CLASS__, 'postImageCallback' ],
			'figure'       => FALSE,                                // or class of the figure
			'caption_link' => FALSE,                                // custom `figcaption` link / TRUE for default
			'fallback'     => FALSE,
			'default'      => FALSE,
			'wrap'         => TRUE,
			'before'       => '',
			'after'        => '',
			'echo'         => TRUE,
		], $atts );

		if ( 'latest' == $args['id'] )
			$args['id'] = WordPress\PostType::getLastMenuOrder( $args['type'], '', 'ID', [ 'publish' ] );

		else if ( 'random' == $args['id'] )
			$args['id'] = WordPress\PostType::getRandomPostID( $args['type'], TRUE );

		else if ( 'parent' == $args['id'] )
			$args['id'] = WordPress\Post::getParent( NULL, FALSE );

		else if ( $module && in_array( $args['id'], [ 'assoc', 'linked', 'paired' ] ) )
			$args['id'] = gEditorial()->module( $module )->get_linked_to_posts( NULL, TRUE );

		if ( FALSE === $args['id'] )
			return $args['default'] ? $args['before'].$args['default'].$args['after'] : $args['default'];

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'] ? $args['before'].$args['default'].$args['after'] : $args['default'];

		$title  = self::getPostField( $args['title'], $post->ID, FALSE );
		$status = get_post_status( $post );

		if ( is_null( $args['thumbnail'] ) )
			$args['thumbnail'] = WordPress\PostType::getThumbnailID( $post->ID );

		if ( $args['link'] ) {

			if ( 'parent' == $args['link'] )
				$args['link'] = in_array( $status, [ 'publish', 'inherit' ], TRUE )
					? apply_filters( 'the_permalink', get_permalink( $post ), $post )
					: FALSE;

			else if ( 'attachment' == $args['link'] )
				$args['link'] = ( $args['thumbnail']
					&& in_array( $status, [ 'publish', 'inherit' ], TRUE ) )
						? get_attachment_link( $args['thumbnail'] )
						: FALSE;

			else if ( 'url' == $args['link'] )
				$args['link'] = ( $args['thumbnail']
					&& in_array( $status, [ 'publish', 'inherit' ], TRUE ) )
						? wp_get_attachment_url( $args['thumbnail'] )
						: FALSE;

			else if ( 'edit' == $args['link'] )
				$args['link'] = $args['thumbnail']
					? WordPress\Attachment::edit( $args['thumbnail'] )
					: FALSE;
		}

		$args  = apply_filters( static::BASE.'_template_post_image_args', $args, $post, $module, $title );
		$image = self::getPostImageTag( $args );

		if ( $args['callback'] && is_callable( $args['callback'] ) ) {

			$html = call_user_func_array( $args['callback'], [ $image, $args['link'], $args, $status, $title, $module ] );

		} else if ( $image ) {

			$html = Core\HTML::tag( ( $args['link'] ? 'a' : 'span' ), [
				'href'   => $args['link'] ?: FALSE,
				'target' => $args['newtab'] ? '_blank' : FALSE,
				'title'  => $title,
				'data'   => $args['link'] ? $args['data'] : FALSE,
			], $image );

		} else if ( $args['fallback'] && in_array( $status, [ 'publish', 'inherit' ] ) ) {

			$html = Core\HTML::tag( 'a', [
				'href'   => apply_filters( 'the_permalink', get_permalink( $post ), $post ),
				'target' => $args['newtab'] ? '_blank' : FALSE,
				'title'  => $title,
				'data'   => $args['data'],
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

	// NOTE: DEPRECATED
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
			'item_title'    => '',       // use `%s` for post title
			'item_title_cb' => FALSE,    // callback for title attribute
			'default'       => FALSE,
			'before'        => '',
			'after'         => '',
			'echo'          => TRUE,
		], $atts );

		if ( ! $posts = gEditorial()->module( $module )->get_linked_to_posts( $args['id'], $args['single'], $args['published'] ) )
			return $args['default'];

		$links = [];

		foreach ( (array) $posts as $post )
			if ( $link = ShortCode::postItem( $post, $args ) )
				$links[] = $link;

		if ( $html = WordPress\Strings::getJoined( $links, $args['before'], $args['after'] ) ) {

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
			$term = WordPress\Term::get( $term, $taxonomy );

		if ( ! $term )
			return $default;

		if ( in_array( $field, [ 'name', 'description', 'slug', 'count' ], TRUE ) )
			return Core\Text::stripTags( sanitize_term_field( $field, $term->{$field}, $term->term_id, $term->taxonomy, 'display' ) );

		if ( $meta = Services\TaxonomyFields::getField( $field, [ 'id' => $term, 'default' => $default ] ) )
			return trim( $meta );

		return $default;
	}

	// TODO: support more post-type props
	public static function getPostField( $field = 'title', $post = NULL, $default = '' )
	{
		if ( 'title' === $field )
			return Core\Text::stripTags( get_the_title( $post ) );

		return self::getMetaField( $field, [ 'id' => $post, 'default' => $default ] );
	}

	public static function metaFieldHTML( $field, $atts = [] )
	{
		if ( ! array_key_exists( 'filter', $atts ) )
			$atts['filter'] = [ __NAMESPACE__.'\\WordPress\\Strings', 'prepDescription' ];

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

	// NOTE: wraps the service method
	public static function getMetaField( $field_key, $atts = [], $check = TRUE, $module = 'meta' )
	{
		return Services\PostTypeFields::getField( $field_key, $atts, $check, $module );
	}

	// NOTE: wraps the service method
	public static function getMetaFieldRaw( $field_key, $post_id, $module = 'meta', $check = FALSE, $default = FALSE )
	{
		return Services\PostTypeFields::getFieldRaw( $field_key, $post_id, $module, $check, $default );
	}

	/**
	 * Applies WordPress embed mechanism on given URL.
	 * @source https://wordpress.stackexchange.com/a/23213/
	 *
	 * @param string $meta
	 * @return string $html
	 */
	public static function doEmbedShortCode( $meta, $post = FALSE, $context = 'display' )
	{
		global $wp_embed;

		$url = trim( $meta );

		if ( ! Core\URL::isValid( $url ) )
			return $meta;

		return $wp_embed->run_shortcode( sprintf( '[embed src="%s" context="%s"]%s[/embed]', $url, $context, $url ) );
	}

	public static function doMediaShortCode( $meta, $type = NULL, $post = FALSE, $context = 'display' )
	{
		$html = trim( $meta );

		if ( $html && is_null( $type ) ) {

			$file = Core\File::type( $html );
			$type = $file['type'] ?: 'embed'; // Fall-back if no type by URL
		}

		switch ( $type ) {

			case 'embed':

				$html = self::doEmbedShortCode( $meta, $post, $context );
				break;

			// case 'application': // TODO: support `application` media link

			case 'text':

				$file = Core\File::type( $html );

				if ( 'text/plain' === $file['type'] )
					$html = WordPress\ShortCode::tag( 'raw', [
						'context' => $context,
						'url'     => $meta,
					], Core\HTML::link( Core\URL::prepTitle( $meta ), $meta ) );

				else if ( 'text/csv' === $file['type'] )
					$html = WordPress\ShortCode::tag( 'csv', [
						'context' => $context,
						'url'     => $meta,
					], Core\HTML::link( Core\URL::prepTitle( $meta ), $meta ) );

				else if ( 'application/pdf' === $file['type'] )
					$html = WordPress\ShortCode::tag( 'pdf', [
						'context' => $context,
						'url'     => $meta,
					], Core\HTML::link( Core\URL::prepTitle( $meta ), $meta ) );

				else if ( 'text/markdown' === $file['type'] )
					$html = WordPress\ShortCode::tag( 'markdown', [
						'context' => $context,
						'url'     => $meta,
					], Core\HTML::link( Core\URL::prepTitle( $meta ), $meta ) );

				else
					$html = Core\HTML::tag( 'a', [
						'href'   => $meta,
						'class'  => '-media-text-link',
						'target' => '_blank',
					], _x( 'Text', 'Template: File Label', 'geditorial' ) );

				break;

			case 'text/plain':
			case 'txt':
			case 'raw':

				$html = WordPress\ShortCode::tag( 'raw', [
					'context' => $context,
					'url'     => $meta,
				], Core\HTML::link( _x( 'Plain Text', 'Template: File Label', 'geditorial' ), $meta ) );

				break;

			case 'text/csv':
			case 'csv':

				$html = WordPress\ShortCode::tag( 'csv', [
					'context' => $context,
					'url'     => $meta,
				], Core\HTML::link( _x( 'CSV Data', 'Template: File Label', 'geditorial' ), $meta ) );

				break;

			case 'application/pdf':
			case 'pdf':

				$html = WordPress\ShortCode::tag( 'pdf', [
					'context' => $context,
					'url'     => $meta,
				], Core\HTML::link( _x( 'PDF Document', 'Template: File Label', 'geditorial' ), $meta ) );

				break;

			case 'text/markdown':
			case 'markdown':

				$html = WordPress\ShortCode::tag( 'markdown', [
					'context' => $context,
					'url'     => $meta,
				], Core\HTML::link( _x( 'Markdown Text', 'Template: File Label', 'geditorial' ), $meta ) );

				break;

			case 'audio':

				// @SEE: https://wordpress.org/documentation/article/audio-shortcode/
				$html = WordPress\ShortCode::tag( 'audio', [
					'context' => $context,
					'src'     => $meta,
				], $meta );

				break;

			case 'video':

				// @SEE: https://wordpress.org/documentation/article/video-shortcode/
				$html = WordPress\ShortCode::tag( 'video', [
					'context' => $context,
					'src'     => $meta,
				], $meta );

				break;

			case 'image':

				$html = WordPress\ShortCode::tag( 'image', [
					'context' => $context,
					'src'     => $meta,
					'alt'     => WordPress\Post::title( $post ),
				], $meta );

				break;
		}

		return apply_filters( static::BASE.'_media_shortcode', $html, $meta, $type, $context );
	}

	// NOTE: DEPRECATED
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
			'field'       => '',
			'taxonomy'    => FALSE,
			'context'     => NULL, // to use `taxonomy`
			'image'       => FALSE,
			'link'        => NULL, // FALSE to disable
			'description' => NULL, // FALSE to disable
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		$context = $args['context'] ?: ( $args['taxonomy'] ?: 'term' );

		$meta = $args['field'] ? self::getMetaField( $args['field'], [
			'id'     => $post->ID,
			'filter' => $args['filter'],
		], FALSE ) : FALSE;

		if ( $args['taxonomy'] && ( $term = WordPress\Taxonomy::theTerm( $args['taxonomy'], $post->ID, TRUE ) ) ) {

			if ( ! $meta )
				$meta = WordPress\Term::title( $term );

			if ( is_null( $args['link'] ) )
				$args['link'] = WordPress\Term::link( $term );

			if ( is_null( $args['description'] ) )
				$args['description'] = Core\Text::stripTags( $term->description );

		} else if ( $meta && is_null( $args['link'] ) ) {

			$args['link'] = WordPress\URL::search( $meta );

			if ( is_null( $args['description'] ) )
				$args['description'] = sprintf(
					/* translators: `%s`: search query */
					_x( 'Search for %s', 'Template: Search Link Title Attr', 'geditorial' ),
					$meta
				);
		}

		if ( ! $meta )
			return $args['default'];

		$html = $args['image'] ? Core\HTML::img( $args['image'], '-'.$context.'-image', $meta ) : $meta;

		if ( ! $html && $args['default'] )
			$html = $args['default'];

		if ( ! $html )
			return FALSE;

		if ( $args['link'] ) {

			$html = $args['before'].Core\HTML::tag( 'a', [
				'href'  => $args['link'],
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-'.$context.'-link',
				'data'  => $args['description'] ? [ 'toggle' => 'tooltip' ] : FALSE,
			], $html ).$args['after'];

		} else {

			$html = $args['before'].Core\HTML::tag( 'span', [
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
			'title_swap'    => FALSE,
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

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		if ( $args['url_field'] ) {

			if ( $url = self::getMetaFieldRaw( $args['url_field'], $post->ID ) ) {

				if ( $args['url_filter'] && is_callable( $args['url_filter'] ) )
					$url = call_user_func( $args['url_filter'], $url );
			}

		} else {

			$url = $args['url_default'];
		}

		$prepared = $url ? Core\URL::prepTitle( $url ) : '';

		$title = $args['title_field'] ? self::getMetaField( $args['title_field'], [
			'id'      => $post->ID,
			'filter'  => $args['filter'],
			'default' => $args['title_default'] ?: $prepared,
		], FALSE ) : ( $args['title_default'] ?: $prepared );

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {

			if ( $args['title_swap'] && $args['title_default'] ) {
				$args['title_attr'] = $title;
				$title = $args['title_default'];
			}

			$html = $args['before'].Core\HTML::tag( ( $url ? 'a' : 'span' ), [
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
			'render'   => NULL,
			'context'  => NULL,
		], $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $args['default'];

		$rows     = [];
		$posttype = $args['type'] ?: $post->post_type;
		$fields   = gEditorial()->module( 'meta' )->get_posttype_fields( $posttype );
		$list     = $args['fields'] ?: Core\Arraay::pluck( $fields, 'title', 'name' );
		$excludes = is_null( $args['excludes'] ) ? [
			'over_title',
			'sub_title',
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
			'text_source_url',
			'audio_source_url',
			'video_source_url',
			'image_source_url',
			'map_embed_url',
			'parent_complex',
			'geo_latitude',   // NOTE: DEPRECATED
			'geo_longitude',  // NOTE: DEPRECATED
		] : (array) $args['excludes'];

		if ( Services\Modulation::hasByline( $post, $args['context'] ?? 'metasummary' ) )
			$excludes = array_merge( $excludes, [
				'publication_byline',
				'featured_people',
				'byline',
			] );

		foreach ( $list as $key => $title ) {

			if ( in_array( $key, $excludes ) )
				continue;

			if ( ! array_key_exists( $key, $fields ) ) {

				// Falls-back to taxonomy, only on custom set of fields
				if ( is_null( $args['fields'] ) )
					continue;

				if ( WordPress\Taxonomy::exists( $key ) ) {

					if ( ! is_object_in_taxonomy( $posttype, $key ) )
						continue;

					if ( ! $terms = WordPress\Taxonomy::getTheTermList( $key, $post ) )
						continue;

					if ( is_null( $title ) )
						$title = Services\CustomTaxonomy::getLabel( $key, 'singular_name' );

					$rows[$title] = WordPress\Strings::getJoined( $terms );
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

		if ( is_null( $args['render'] ) )
			$args['render'] = [ __CLASS__, 'metaSummary__render_callback' ];

		$callback_args = [
			$rows,
			$args,
			$post,
			$module,
		];

		if ( ! $html = self::buffer( $args['render'], $callback_args ) )
			return $args['default'];

		$html = $args['before'].$html.$args['after'];

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function metaSummary__render_callback( $data, $args, $post = NULL, $module = NULL )
	{
		Core\HTML::tableDouble(
			array_values( $data ),
			array_keys( $data ),
			TRUE,
			// 'table table-bordered',
			'table',
		);
	}

	// NOTE: DEPRECATED
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

			++$i;
			// $post->menu_order = $start;

			$o[$key] = $post;
		}

		unset( $posts, $post );
		ksort( $o );

		return $o;
	}

	public static function renderRecentByPosttype( $posttype, $link = 'view', $empty = NULL, $title_attr = NULL, $extra = [] )
	{
		if ( ! $object = WordPress\PostType::object( $posttype ) )
			return;

		$posts = WordPress\PostType::getRecent( $object->name, $extra, current_user_can( $object->cap->edit_posts ) );

		if ( count( $posts ) ) {

			$list = [];

			foreach ( $posts as $post )
				$list[] = Helper::getPostTitleRow( $post, $link, FALSE, $title_attr );

			Core\HTML::h3( sprintf(
				/* translators: `%s`: post-type name */
				_x( 'Recent %s', 'Template: Recents', 'geditorial' ),
				$object->labels->name
			) );

			echo Core\HTML::rows( $list );

		} else if ( is_null( $empty ) ) {

			Core\HTML::h3( sprintf(
				/* translators: `%s`: post-type name */
				_x( 'Recent %s', 'Template: Recents', 'geditorial' ),
				$object->labels->name
			) );

			Core\HTML::desc( $object->labels->not_found, TRUE, '-empty -empty-posttype-'.$object->name );

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

		$terms = WordPress\Taxonomy::listTerms( $args['taxonomy'], 'ids', [ 'order' => 'DESC' ] );

		foreach ( $terms as $term_id ) {

			$span = ShortCode::listPosts( 'assigned',
				$args['posttype'],
				$args['taxonomy'],
				[
					'term_id'         => $term_id,
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
				$html.= Core\HTML::wrap( $span, '-tile-row' );
		}

		if ( empty( $html ) )
			return $args['fallback'];

		$html = $args['before'].$html.$args['after'];

		return $args['wrap'] ? Core\HTML::wrap( $html, static::BASE.'-span-tiles' ) : $html;
	}

	// TODO: link back to taxonomy archive via `Archives` Module
	// `WordPress\Taxonomy::link( $term->taxonomy )`
	public static function renderTermIntro( $term, $atts = [], $module = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return;

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'heading'     => '3',     // heading level or `FALSE` to disable
			'secondary'   => NULL,    // `NULL` for filter: `geditorial_term_intro_title_suffix`
			'image_link'  => 'url',
			'image_field' => Services\TaxonomyFields::getTermMetaKey( 'image', $term->taxonomy ),
			'name_only'   => FALSE,   // Avoids the name-only data (no description/image)
			'context'     => NULL,
			'before'      => '',
			'after'       => '',
		], $atts );

		$wrap   = $args['before'].'<div class="-wrap '.static::BASE.'-wrap row -term-introduction'.( $args['context'] ? ( ' -'.$args['context'] ) : '' ).'">';
		$desc   = WordPress\Strings::isEmpty( $term->description ) ? FALSE : $term->description;
		$suffix = $args['secondary'] ?? apply_filters( sprintf( '%s_term_intro_title_suffix', static::BASE ), '', $term, $desc, $args, $module );

		$image = self::termImage( [
			'id'       => $term,
			'taxonomy' => $term->taxonomy,
			'context'  => $args['context'],
			'link'     => $args['image_link'],
			'field'    => $args['image_field'],
			'before'   => $wrap.'<div class="col-sm-4 text-center -term-thumbnail">',
			'after'    => '</div>',
		], $module );

		if ( ! $image && ! $desc && ( ! $args['heading'] || ! $args['name_only'] ) )
			return;

		if ( ! $image )
			echo $wrap;

		echo '<div class="'.( $image ? 'col-sm-8 -term-has-image' : 'col -term-no-image' ).' -term-details">';

			do_action( sprintf( '%s_term_intro_title_before', static::BASE ), $term, $desc, (bool) $image, $args, $module );

			if ( $args['heading'] )
				Core\HTML::heading( $args['heading'], WordPress\Term::title( $term, FALSE ).Core\HTML::small( $suffix, '-secondary', TRUE ) );

			do_action( sprintf( '%s_term_intro_description_before', static::BASE ), $term, $desc, (bool) $image, $args, $module );

			echo Core\HTML::wrap(
				WordPress\Strings::prepDescription(
					WordPress\Strings::kses( $desc, 'html' )
				), 'term-description -term-description' );

			do_action( sprintf( '%s_term_intro_description_after', static::BASE ), $term, $desc, (bool) $image, $args, $module );

		echo '</div>'; // `.col`
		echo '</div>'; // `.row`
		echo $args['after'];
	}

	public static function renderTermSubTerms( $term, $atts = [], $module = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return;

		if ( ! WordPress\Taxonomy::hierarchical( $term->taxonomy ) )
			return;

		if ( is_null( $module ) && static::MODULE )
			$module = static::MODULE;

		$args = self::atts( [
			'hide_empty' => TRUE,
			'context'    => NULL,
			'before'     => '',
			'after'      => '',
		], $atts );

		$extra   = [
			'parent'     => $term->term_id,
			'hide_empty' => $args['hide_empty'],
		];

		$terms = WordPress\Taxonomy::getTerms( $term->taxonomy, FALSE, TRUE, 'term_id', $extra );

		if ( empty( $terms ) )
			return;

		$wrap = '<div class="-wrap '.static::BASE.'-wrap -term-listsubterms'.( $args['context'] ? ( ' -'.$args['context'] ) : '' ).'">';
		$list = [];

		foreach ( $terms as $term )
			$list[] = WordPress\Term::htmlLink( $term );

		echo $args['before'].$wrap.Core\HTML::rows( $list ).'</div>'.$args['after'];
	}

	// @source https://github.com/billerickson/BE-Events-Calendar/wiki
	public static function eventDate( $start, $end )
	{
		// Only a start date
		if ( empty( $end ) )
			$date = wp_date( 'F j, Y', $start );

		// Same day
		else if ( date( 'F j', $start ) == date( 'F j', $end ) )
			$date = wp_date( 'F j Y', $start );

		// Same Month
		else if ( date( 'F', $start ) == date( 'F', $end ) )
			$date = wp_date( 'F j', $start ).'&mdash;'.wp_date( 'j, Y', $end );

		// Same Year
		else if ( date( 'Y', $start ) == date( 'Y', $end ) )
			$date = wp_date( 'F j', $start ).'&mdash;'.wp_date( 'F j, Y', $end );

		// Any other dates
		else
			$date = wp_date( 'F j, Y', $start ).'&mdash;'.wp_date( 'F j, Y', $end );

		return $date;
	}

	// @source https://github.com/billerickson/BE-Events-Calendar/wiki
	public static function eventTime( $start, $end )
	{
		// Same date, same am/pm
		if ( date( 'F j', $start ) == date( 'F j', $end ) && date( 'a', $start ) == date( 'a', $end ) )
			$time = wp_date( 'g:i', $start ).'&mdash;'.wp_date( 'g:i a', $end );

		// Same date, different am/pm
		else if ( date( 'F j', $start ) == date( 'F j', $end ) )
			$time = wp_date( 'g:i a', $start ).'&mdash;'.wp_date( 'g:i a', $end );

		// different date
		else
			$time = wp_date( 'g:i a', $start ).'&mdash;'.wp_date( 'F j, g:i a', $end );

		return $time;
	}
}

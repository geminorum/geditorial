<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTemplateCore extends gEditorialBaseCore
{

	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( self::MODULE, $key, $default );
	}

	protected static function post_types( $post_types = NULL )
	{
		if ( self::MODULE && gEditorial()->enabled( self::MODULE ) )
			return gEditorial()->{self::MODULE}->post_types( $post_types );

		return array();
	}

	// FIXME: DRAFT
	public static function parseMarkDown( $content )
	{
		if ( ! class_exists( 'ParsedownExtra' ) )
			return $content;

		$parsedown = new \ParsedownExtra();
		return $parsedown->text( $content );
	}

	// FIXME: DEPRECATED
	public static function termDescription( $term, $echo_attr = FALSE )
	{
		self::__dev_dep();

		if ( ! $term )
			return;

		if ( ! $term->description )
			return;

		// Bootstrap 3
		$desc = esc_attr( $term->name ).
			'"  data-toggle="popover" data-trigger="hover" data-content="'.
			esc_attr( trim( strip_tags( $term->description ) ) );

		if ( ! $echo_attr )
			return $desc;

		echo ' title="'.$desc.'"';
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

	public static function getPostImageTag( $atts = array() )
	{
		$html = FALSE;

		$args = self::atts( array(
			'id'    => NULL,
			'size'  => NULL,
			'alt'   => FALSE,
			'class' => '-post-image',
		), $atts );

		if ( $src = self::getPostImageSrc( $args['size'], $args['id'] ) )
			$html = gEditorialHTML::tag( 'img', array(
				'src'   => $src,
				'alt'   => $args['alt'],
				'class' => apply_filters( 'get_image_tag_class', $args['class'], $args['id'], 'none', $args['size'] ),
			) );

		return $html;
	}

	public static function postImageCallback( $image, $link, $args, $status, $title, $module )
	{
		if ( ! $image )
			return $args['default'];

		$html = $image;

		if ( $title && $args['figure'] )
			$html = '<figure>'.$html.'<figcaption>'.$title.'</figcaption></figure>';

		if ( $link )
			$html = '<a title="'.esc_attr( $args['figure'] ? self::getPostField( 'title', $args['id'] ) : $title ).'" href="'.$link.'">'.$html.'</a>';

		return '<div class="geditorial-wrap'.( $module ? ' -'.$module : ' ' ).' -post-image-wrap">'.$html.'</div>';
	}

	public static function postImage( $atts = array(), $module = NULL )
	{
		$html = '';

		if ( is_null( $module ) && self::MODULE )
			$module = self::MODULE;

		$args = self::atts( array(
			'id'       => NULL,
			'size'     => NULL,
			'alt'      => FALSE,
			'class'    => '-post-image',
			'type'     => 'post',
			'link'     => 'parent',
			'title'    => 'title',
			'data'     => array( 'toggle' => 'tooltip' ),
			'callback' => array( __CLASS__, 'postImageCallback' ),
			'figure'   => FALSE,
			'fallback' => FALSE,
			'default'  => FALSE,
			'before'   => '',
			'after'    => '',
			'echo'     => TRUE,
		), $atts );

		if ( 'latest' == $args['id'] )
			$args['id'] = gEditorialWordPress::getLastPostOrder( $args['type'], '', 'ID', array( 'publish' ) );

		else if ( 'random' == $args['id'] )
			$args['id'] = gEditorialWordPress::getRandomPostID( $args['type'], TRUE );

		else if ( 'parent' == $args['id'] )
			$args['id'] = gEditorialWordPress::getParentPostID();

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

			if ( 'publish' == $status ) {

				if ( 'parent' == $args['link'] )
					$args['link'] = get_permalink( $args['id'] );

				else if ( 'attachment' == $args['link'] && $thumbnail )
					$args['link'] = get_attachment_link( $thumbnail );

				else if ( 'url' == $args['link'] && $thumbnail )
					$args['link'] = wp_get_attachment_url( $thumbnail );

			} else {
				$args['link'] = FALSE;
			}
		}

		$image = self::getPostImageTag( $args );

		if ( $args['callback'] && is_callable( $args['callback'] ) ) {

			$html = call_user_func_array( $args['callback'], array( $image, $args['link'], $args, $status, $title, $module ) );

		} else if ( $image ) {

			$html = gEditorialHTML::tag( ( $args['link'] ? 'a' : 'span' ), array(
				'href'  => $args['link'],
				'title' => $title,
				'data'  => $args['link'] ? $args['data'] : FALSE,
			), $image );

		} else if ( $args['fallback'] && 'publish' == $status ) {

			$html = gEditorialHTML::tag( 'a', array(
				'href'  => get_permalink( $args['id'] ),
				'title' => $title,
				'data'  => $args['data'],
			), get_the_title( $args['id'] ) );
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

	public static function assocLink( $atts = array(), $module = NULL )
	{
		if ( is_null( $module ) && self::MODULE )
			$module = self::MODULE;

		if ( ! $module )
			return FALSE;

		$args = self::atts( [
			'id'          => NULL,
			'published'   => TRUE,
			'single'      => FALSE,
			'li_title'    => '', // use %s for post title
			'li_title_cb' => FALSE, // callback for title attr
			'default'     => FALSE,
			'before'      => '',
			'after'       => '',
			'echo'        => TRUE,
		], $atts );

		if ( ! $posts = gEditorial()->{$module}->get_assoc_post( $args['id'], $args['single'], $args['published'] ) )
			return $args['default'];

		$links = [];

		foreach ( (array) $posts as $post_id )
			if ( $link = gEditorialShortCode::postLink( $args, $post_id ) )
				$links[] = $link;

		if ( $html = gEditorialHelper::getJoined( $links, $args['before'], $args['after'] ) ) {

			if ( ! $args['echo'] )
				return $html;

			echo $html;
			return TRUE;
		}

		if ( $args['default'] )
			return $args['before'].$args['default'].$args['after'];

		return FALSE;
	}

	public static function getPostField( $field = 'title', $id = NULL, $default = '' )
	{
		if ( 'title' == $field )
			return trim( strip_tags( get_the_title( $id ) ) );

		if ( $field )
			return self::getMetaField( $field, array( 'id' => $id, 'default' => $default ) );

		return $default;
	}

	public static function getMetaField( $fields, $atts = array(), $check = TRUE )
	{
		$args = self::atts( array(
			'id'      => NULL,
			'default' => FALSE,
			'filter'  => FALSE,
			'before'  => '',
			'after'   => '',
		), $atts );

		if ( $check && ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		foreach ( self::sanitizeField( $fields ) as $field ) {

			$meta = gEditorial()->meta->get_postmeta( $post->ID, $field, FALSE );

			if ( FALSE === $meta )
				continue;

			$meta = apply_filters( 'geditorial_meta_field', $meta, $field, $post, $args );

			if ( $args['filter'] && is_callable( $args['filter'] ) )
				$meta = call_user_func( $args['filter'], $meta );

			if ( $meta )
				return $args['before'].$meta.$args['after'];
		}

		return $args['default'];
	}

	public static function getMetaFieldRaw( $fields, $post_id )
	{
		foreach ( self::sanitizeField( $fields ) as $field ) {

			$meta = gEditorial()->meta->get_postmeta( $post_id, $field, FALSE );

			if ( FALSE !== $meta )
				return $meta;
		}
	}

	public static function metaLabel( $atts = array(), $module = NULL )
	{
		if ( is_null( $module ) && self::MODULE )
			$module = self::MODULE;

		$args = self::atts( array(
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
		), $atts );

		if ( ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		$meta = self::getMetaField( $args['field'], array(
			'id'     => $post->ID,
			'filter' => $args['filter'],
		), FALSE );

		if ( $term = gEditorialWPTaxonomy::theTerm( $args['taxonomy'], $post->ID, TRUE ) ) {

			if ( ! $meta )
				$meta = sanitize_term_field( 'name', $term->name, $term->term_id, $args['taxonomy'], 'display' );

			if ( is_null( $args['link'] ) )
				$args['link'] = get_term_link( $term, $args['taxonomy'] );

			if ( is_null( $args['description'] ) )
				$args['description'] = trim( strip_tags( $term->description ) );

		} else if ( $meta && is_null( $args['link'] ) ) {

			$args['link'] = gEditorialWordPress::getSearchLink( $meta );

			if ( is_null( $args['description'] ) )
				$args['description'] = sprintf( _x( 'Search for %s', 'Template: Search Link Title Attr', GEDITORIAL_TEXTDOMAIN ), $meta );
		}

		$html = $args['image'] ? gEditorialHTML::tag( 'img', array(
			'src'   => $args['image'],
			'alt'   => $meta,
			'class' => '-label-image',
		) ) : $meta;

		if ( ! $html && $args['default'] )
			$html = $args['default'];

		if ( ! $html )
			return FALSE;

		if ( $args['link'] ) {

			$html = $args['before'].gEditorialHTML::tag( 'a', array(
				'href'  => $args['link'],
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-label-link',
				'data'  => $args['description'] ? array( 'toggle' => 'tooltip' ) : FALSE,
			), $html ).$args['after'];

		} else {

			$html = $args['before'].gEditorialHTML::tag( 'span', array(
				'title' => $args['description'] ? $args['description'] : FALSE,
				'class' => '-label-span',
				'data'  => $args['description'] ? array( 'toggle' => 'tooltip' ) : FALSE,
			), $html ).$args['after'];
		}

		if ( ! $args['echo'] )
			return $html;

		echo $html;
		return TRUE;
	}

	public static function metaLink( $atts = array(), $module = NULL )
	{
		if ( is_null( $module ) && self::MODULE )
			$module = self::MODULE;

		$args = self::atts( array(
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
		), $atts );

		if ( ! gEditorial()->enabled( 'meta' ) )
			return $args['default'];

		if ( ! $post = get_post( $args['id'] ) )
			return $args['default'];

		$title = $args['title_field'] ? self::getMetaField( $args['title_field'], array(
			'id'      => $post->ID,
			'filter'  => $args['filter'],
			'default' => $args['title_default'],
		), FALSE ) : $args['title_default'];

		$url = $args['url_field'] ? self::getMetaField( $args['url_field'], array(
			'id'      => $post->ID,
			'default' => $args['url_default'],
		), FALSE ) : $args['url_default'];

		if ( $title && $url || ! $url && $title != $args['title_default'] ) {
			$html = $args['before'].gEditorialHTML::tag( ( $url ? 'a' : 'span' ), array(
				'href'  => $url,
				'title' => $url ? $args['title_attr'] : FALSE,
				'rel'   => $url ? 'nofollow' : 'source', // https://support.google.com/webmasters/answer/96569?hl=en
				'data'  => $url ? array( 'toggle' => 'tooltip' ) : FALSE,
			), $title ).$args['after'];
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

		$fields = array(
			'over-title'           => array( 'ot', 'over-title' ),
			'sub-title'            => array( 'st', 'sub-title' ),
			'label'                => array( 'ch', 'label', 'column_header' ),
			'lead'                 => array( 'le', 'lead' ),
			'author'               => array( 'as', 'author' ),
			'number'               => array( 'issue_number_line', 'number' ),
			'pages'                => array( 'issue_total_pages', 'pages' ),
			'start'                => array( 'in_issue_page_start', 'start' ),
			'order'                => array( 'in_issue_order', 'order' ),
			'reshare_source_title' => array( 'source_title', 'reshare_source_title' ),
			'reshare_source_url'   => array( 'source_url', 'reshare_source_url' ),
		);

		if ( isset( $fields[$field] ) )
			return $fields[$field];

		return array( $field );
	}

	public static function reorderPosts( $posts )
	{
		$i = 1000;
		$ordered = [];

		foreach ( $posts as &$post ) {

			$start = self::getMetaFieldRaw( 'start', $post->ID );
			$order = self::getMetaFieldRaw( 'order', $post->ID );

			$key = $start ? ( (int) $start * 10 ) : 0;
			$key = $order ? ( $key + (int) $order ) : $key;
			$key = $key ? $key : ( $i * 100 );

			$i++;
			// $post->menu_order = $start;

			$ordered[$key] = $post;
		}

		unset( $posts, $post );
		ksort( $ordered );

		return $ordered;
	}
}

<?php namespace geminorum\gEditorial\Modules\StaticCovers;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;
use geminorum\gEditorial\ShortCode;
use geminorum\gEditorial\Settings;

class StaticCovers extends gEditorial\Module
{

	public static function module()
	{
		return [
			'name'   => 'static_covers',
			'title'  => _x( 'Static Covers', 'Modules: Static Covers', 'geditorial-admin' ),
			'desc'   => _x( 'Alternative Cover Management', 'Modules: Static Covers', 'geditorial-admin' ),
			'icon'   => 'cover-image',
			'access' => 'beta',
		];
	}

	protected function get_global_settings()
	{
		$settings        = [];
		$posttype_tokens = $this->_get_posttype_template_tokens();
		$taxonomy_tokens = $this->_get_taxonomy_template_tokens();

		$settings['posttypes_option'] = 'posttypes_option';

		foreach ( $this->list_posttypes() as $posttype_name => $posttype_label ) {

			$default_metakey = $this->filters( 'default_posttype_reference_metakey', '', $posttype_name );

			$settings['_posttypes'][] = [
				'field'       => $posttype_name.'_posttype_reference_metakey',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Reference Meta-key for %s', 'Setting Title', 'geditorial-static-covers' ), '<i>'.$posttype_label.'</i>' ),
				'description' => _x( 'Defines reference meta-key for the post-type.', 'Setting Description', 'geditorial-static-covers' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $default_metakey, 'code' ),
				'placeholder' => $default_metakey,
				'default'     => $default_metakey,
			];

			$settings['_posttypes'][] = [
				'field'        => $posttype_name.'_posttype_url_template',
				'type'         => 'text',
				/* translators: %s: supported object label */
				'title'        => sprintf( _x( 'URL Template for %s', 'Setting Title', 'geditorial-static-covers' ), '<i>'.$posttype_label.'</i>' ),
				/* translators: %s: supported object tokens */
				'description'  => sprintf( _x( 'Defines default URL template for the post-type. Available tokens are %s.', 'Setting Description', 'geditorial-static-covers' ), $posttype_tokens ),
				'field_class'  => [ 'semi-large-text', 'code-text' ],
			];

			$settings['_posttypes'][] = [
				'field'        => $posttype_name.'_posttype_path_template',
				'type'         => 'text',
				/* translators: %s: supported object label */
				'title'        => sprintf( _x( 'Path Template for %s', 'Setting Title', 'geditorial-static-covers' ), '<i>'.$posttype_label.'</i>' ),
				/* translators: %s: supported object tokens */
				'description'  => sprintf( _x( 'Defines default path template for the post-type. Available tokens are %s.', 'Setting Description', 'geditorial-static-covers' ), $posttype_tokens ),
				'field_class'  => [ 'semi-large-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( Core\File::normalize( ABSPATH ), 'code' ),
			];
		}

		$settings['taxonomies_option'] = 'taxonomies_option';

		foreach ( $this->list_taxonomies() as $taxonomy_name => $taxonomy_label ) {

			$default_metakey = $this->filters( 'default_taxonomy_reference_metakey', '', $taxonomy_name );

			$settings['_taxonomies'][] = [
				'field'       => $taxonomy_name.'_taxonomy_reference_metakey',
				'type'        => 'text',
				/* translators: %s: supported object label */
				'title'       => sprintf( _x( 'Reference Meta-key for %s', 'Setting Title', 'geditorial-static-covers' ), '<i>'.$taxonomy_label.'</i>' ),
				'description' => _x( 'Defines reference meta-key for the taxonomy.', 'Setting Description', 'geditorial-static-covers' ),
				'field_class' => [ 'regular-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( $default_metakey, 'code' ),
				'placeholder' => $default_metakey,
				'default'     => $default_metakey,
			];

			$settings['_taxonomies'][] = [
				'field'        => $taxonomy_name.'_taxonomy_url_template',
				'type'         => 'text',
				/* translators: %s: supported object label */
				'title'        => sprintf( _x( 'URL Template for %s', 'Setting Title', 'geditorial-static-covers' ), '<i>'.$taxonomy_label.'</i>' ),
				/* translators: %s: supported object tokens */
				'description'  => sprintf( _x( 'Defines default URL template for the taxonomy. Available tokens are %s.', 'Setting Description', 'geditorial-static-covers' ), $taxonomy_tokens ),
				'field_class'  => [ 'semi-large-text', 'code-text' ],
			];

			$settings['_taxonomies'][] = [
				'field'        => $taxonomy_name.'_taxonomy_path_template',
				'type'         => 'text',
				/* translators: %s: supported object label */
				'title'        => sprintf( _x( 'Path Template for %s', 'Setting Title', 'geditorial-static-covers' ), '<i>'.$taxonomy_label.'</i>' ),
				/* translators: %s: supported object tokens */
				'description'  => sprintf( _x( 'Defines default path template for the taxonomy. Available tokens are %s.', 'Setting Description', 'geditorial-static-covers' ), $taxonomy_tokens ),
				'field_class'  => [ 'semi-large-text', 'code-text' ],
				'after'       => Settings::fieldAfterText( Core\File::normalize( ABSPATH ), 'code' ),
			];
		}

		$settings['_defaults'] = [
			[
				'field'       => 'counter_threshold',
				'type'        => 'number',
				'title'       => _x( 'Counter Threshold', 'Setting Title', 'geditorial-static-covers' ),
				'description' => _x( 'Defines digit places number needs to be to not have zeros added to the counter token.', 'Setting Description', 'geditorial-static-covers' ),
				'default'     => 2,
				'min_attr'    => 1,
			],
		];
		$settings['_supports'] = [
			'shortcode_support',
		];

		return $settings;
	}

	protected function get_global_constants()
	{
		return [
			'post_cover_shortcode' => 'post-cover',
			'term_cover_shortcode' => 'term-cover',

			'metakey_reference_posttype' => 'cover',
			'metakey_reference_taxonomy' => 'cover',

			'restapi_attribute' => 'static-cover',
		];
	}

	protected function get_global_strings()
	{
		$strings = [];

		if ( ! is_admin() )
			return $strings;

		$strings['metabox'] = [
			'supportedbox_title' => _x( 'Cover', 'MetaBox Title', 'geditorial-static-covers' ),
		];

		return $strings;
	}

	public function init()
	{
		parent::init();

		$this->filter( 'pairedrest_prepped_post', 3, 99, FALSE, $this->base );
		$this->filter_module( 'tabloid', 'view_data', 3, 20 );
		$this->register_shortcode( 'post_cover_shortcode' );
		$this->register_shortcode( 'term_cover_shortcode' );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			$this->filter_module( 'tweaks', 'column_thumb', 3, 9 );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->base, [ 'post', 'edit' ], TRUE ) ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {

				if ( 'post' == $screen->base ) {

					$this->_hook_general_supportedbox( $screen, NULL, 'side', 'high' );

				} else if ( 'edit' == $screen->base ) {

					$this->filter_module( 'tweaks', 'column_thumb', 3, 9 );
				}
			}

		} else if ( in_array( $screen->base, [ 'edit-tags', 'term' ], TRUE ) ) {

			if ( $this->taxonomy_supported( $screen->taxonomy ) ) {

				if ( 'term' == $screen->base ) {
					$this->_hook_term_supportedbox( $screen, NULL, 'side', 'high' );
				}
			}
		}
	}

	public function tweaks_column_thumb( $html, $post_id, $size )
	{
		if ( $html )
			return $html;

		if ( ! WordPress\Post::can( $post_id, 'read_post' ) )
			return $html;

		if ( $src = $this->_get_posttype_image( $post_id ) )
			return $this->_get_html_image( $src, WordPress\Post::title( $post_id ), '-attachment-image' );

		return $html;
	}

	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		if ( is_null( $screen ) )
			$screen = get_current_screen();

		$src = $title = FALSE;

		if ( 'post' === $screen->base ) {
			$src   = $this->_get_posttype_image( $object );
			$title = WordPress\Post::title( $object );

		} else if ( 'term' === $screen->base ) {
			$src = $this->_get_taxonomy_image( $object );
			$title = WordPress\Term::title( $object );
		}

		if ( $src ) {

			echo Core\HTML::wrap( $this->_get_html_image( $src, $title ), 'field-wrap -image' );

		} else {

			echo gEditorial\Plugin::na();
		}
	}

	// TODO: do the actual count!
	private function _get_counter( $start = 1 )
	{
		return Core\Number::zeroise( $start, $this->get_setting( 'counter_threshold', 2 ) );
	}

	private function _get_html_image( $src, $title = FALSE, $class = '' )
	{
		return Core\HTML::tag( 'a', [
			'href'   => $src,
			'title'  => $title,
			'class'  => 'thickbox',
			'target' => '_blank',
		], Core\HTML::img( $src, $class ) );
	}

	private function _get_posttype_image( $post, $metakey = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		if ( ! $url_template = $this->get_setting( $post->post_type.'_posttype_url_template' ) )
			return FALSE;

		if ( is_null( $metakey ) )
			$metakey = $this->_get_posttype_metakey( $post->post_type );

		if ( ! $reference = get_post_meta( $post->ID, $metakey, TRUE ) )
			return FALSE;

		$tokens = [
			'counter'   => $this->_get_counter(),
			'reference' => $reference,
			'post_id'   => $post->ID,
			'post_type' => $post->post_type,
		];

		if ( $path_template = $this->get_setting( $post->post_type.'_posttype_path_template' ) ) {

			$path = Core\Text::replaceTokens( $path_template, $tokens );

			if ( ! file_exists( ABSPATH.$path ) )
				return FALSE;

			$url = Core\Text::replaceTokens( $url_template, $tokens );

		} else {

			$url = Core\Text::replaceTokens( $url_template, $tokens );

			if ( 200 !== Core\HTTP::getStatus( $url, FALSE ) )
				return FALSE;
		}

		return $this->filters( 'get_posttype_image', $url, $post, $reference, $metakey );
	}

	private function _get_taxonomy_image( $term, $metakey = NULL )
	{
		if ( ! $term = WordPress\Term::get( $term ) )
			return FALSE;

		if ( ! $url_template = $this->get_setting( $term->taxonomy.'_taxonomy_url_template' ) )
			return FALSE;

		if ( is_null( $metakey ) )
			$metakey = $this->_get_posttype_metakey( $term->taxonomy );

		if ( ! $reference = get_term_meta( $term->term_id, $metakey, TRUE ) )
			return FALSE;

		$tokens = [
			'counter'   => $this->_get_counter(),
			'reference' => $reference,
			'term_id'   => $term->term_id,
			'taxonomy'  => $term->taxonomy,
		];

		if ( $path_template = $this->get_setting( $term->taxonomy.'_taxonomy_path_template' ) ) {

			$path = Core\Text::replaceTokens( $path_template, $tokens );

			if ( ! file_exists( ABSPATH.$path ) )
				return FALSE;

			$url = Core\Text::replaceTokens( $url_template, $tokens );

		} else {

			$url = Core\Text::replaceTokens( $url_template, $tokens );

			if ( 200 !== Core\HTTP::getStatus( $url, FALSE ) )
				return FALSE;
		}

		return $this->filters( 'get_taxonomy_image', $url, $term, $reference, $metakey );
	}

	private function _get_posttype_template_tokens( $joined = TRUE )
	{
		$tokens = [
			'counter',
			'reference',
			'post_id',
			'post_type',
		];

		$list = [];

		foreach ( $tokens as $token )
			$list[] = Core\HTML::code( sprintf( '{{%s}}', $token ) );

		return $joined ? WordPress\Strings::getJoined( $list ) : $list;
	}

	private function _get_taxonomy_template_tokens( $joined = TRUE )
	{
		$tokens = [
			'counter',
			'reference',
			'term_id',
			'taxonomy',
		];

		$list = [];

		foreach ( $tokens as $token )
			$list[] = Core\HTML::code( sprintf( '{{%s}}', $token ) );

		return $joined ? WordPress\Strings::getJoined( $list ) : $list;
	}

	private function _get_posttype_metakey( $posttype )
	{
		if ( $setting = $this->get_setting( $posttype.'_posttype_reference_metakey' ) )
			return $setting;

		if ( $default = $this->filters( 'default_posttype_reference_metakey', '', $posttype ) )
			return $default;

		return $this->constant( 'metakey_reference_posttype' );
	}

	public function pairedrest_prepped_post( $prepped, $post, $parent )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $prepped;

		return array_merge( $prepped, [
			$this->constant( 'restapi_attribute' ) => $this->_get_posttype_image( $post ),
		] );
	}

	public function tabloid_view_data( $data, $post, $context )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $data;

		if ( ! WordPress\Post::can( $post, 'read_post' ) )
			return $data;

		if ( ! $src = $this->_get_posttype_image( $post ) )
			return $data;

		$data['___hooks']['after-post'].= '<div class="-wrap side-wrap">'.$this->wrap( Core\HTML::img( $src ), '-side-image' ).'<div class="-side-table">';
		$data['___hooks']['after-meta'].= '</div></div>';

		return $data;
	}

	public function post_cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id' => is_singular() ? get_queried_object_id() : NULL,

			'check'     => NULL,     // cap check, `NULL` for default, `FALSE` to disable
			'link'      => NULL,     // `parent`/`image`/`FALSE`
			'size'      => '',       // empty means raw
			'width'     => FALSE,
			'height'    => FALSE,
			'style'     => FALSE,
			'img_class' => FALSE,
			'figure'    => TRUE,     // TODO: add settings for default
			'caption'   => NULL,     // null for getting from queried object
			'alt'       => NULL,     // null for getting from queried object
			'load'      => 'lazy',
			'context'   => NULL,
			'wrap'      => TRUE,
			'class'     => '',
			'before'    => '',
			'after'     => '',
		], $atts, $tag ?: $this->constant( 'post_cover_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $post = WordPress\Post::get( $args['id'] ) )
			return $content;

		if ( is_null( $args['check'] ) )
			$args['check'] = 'read_post';

		if ( $args['check'] && ! WordPress\Post::can( $post, $args['check'] ) )
			return $content;

		if ( ! $src = $this->_get_posttype_image( $post ) )
			return $content;

		$title = WordPress\Post::title( $post );

		if ( is_null( $args['alt'] ) )
			$args['alt'] = $title;

		$html = Core\HTML::tag( 'img', [
			'src'     => $src,
			'alt'     => $args['alt'],
			'width'   => $args['width'],
			'height'  => $args['height'],
			'loading' => $args['load'],
			'style'   => $args['style'],
			'class'   => Core\HTML::attrClass( 'img-fluid', $args['figure'] ? 'figure-img' : '', $args['img_class'] ),
		] );

		if ( 'image' === $args['link'] )
			$args['link'] = $src;

		else if ( 'parent' === $args['link'] )
			$args['link'] = WordPress\Post::link( $post );

		if ( $args['link'] )
			$html = Core\HTML::link( $html, $args['link'] );

		if ( $args['figure'] ) {

			if ( is_null( $args['caption'] ) )
				$args['caption'] = $title;

			if ( $args['caption'] )
				$html.= '<figcaption class="figure-caption">'.$args['caption'].'</figcaption>';

			$html = '<figure class="'.Core\HTML::prepClass( 'figure', $args['figure'] ).'">'.$html.'</figure>';
		}

		return ShortCode::wrap( $html, $this->constant( 'post_cover_shortcode' ), $args );
	}

	public function term_cover_shortcode( $atts = [], $content = NULL, $tag = '' )
	{
		$args = shortcode_atts( [
			'id' => ( is_tax() || is_tag() || is_category() ) ? get_queried_object_id() : NULL,

			'check'     => NULL,     // cap check, `NULL` for default, `FALSE` to disable
			'link'      => NULL,     // `parent`/`image`/`FALSE`
			'size'      => '',       // empty means raw
			'width'     => FALSE,
			'height'    => FALSE,
			'style'     => FALSE,
			'img_class' => FALSE,
			'figure'    => TRUE,     // TODO: add settings for default
			'caption'   => NULL,     // null for getting from queried object
			'alt'       => NULL,     // null for getting from queried object
			'load'      => 'lazy',
			'context'   => NULL,
			'wrap'      => TRUE,
			'class'     => '',
			'before'    => '',
			'after'     => '',
		], $atts, $tag ?: $this->constant( 'term_cover_shortcode' ) );

		if ( FALSE === $args['context'] )
			return NULL;

		if ( ! $term = WordPress\Term::get( $args['id'] ) )
			return $content;

		if ( is_null( $args['check'] ) )
			$args['check'] = FALSE; // default is viewable

		if ( $args['check'] && ! WordPress\Term::can( $term, $args['check'] ) )
			return $content;

		if ( ! $src = $this->_get_taxonomy_image( $term ) )
			return $content;

		$title = WordPress\Term::title( $term );

		if ( is_null( $args['alt'] ) )
			$args['alt'] = $title;

		$html = Core\HTML::tag( 'img', [
			'src'     => $src,
			'alt'     => $args['alt'],
			'width'   => $args['width'],
			'height'  => $args['height'],
			'loading' => $args['load'],
			'style'   => $args['style'],
			'class'   => Core\HTML::attrClass( 'img-fluid', $args['figure'] ? 'figure-img' : '', $args['img_class'] ),
		] );

		if ( 'image' === $args['link'] )
			$args['link'] = $src;

		else if ( 'parent' === $args['link'] )
			$args['link'] = WordPress\Term::link( $term );

		if ( $args['link'] )
			$html = Core\HTML::link( $html, $args['link'] );

		if ( $args['figure'] ) {

			if ( is_null( $args['caption'] ) )
				$args['caption'] = $title;

			if ( $args['caption'] )
				$html.= '<figcaption class="figure-caption">'.$args['caption'].'</figcaption>';

			$html = '<figure class="'.Core\HTML::prepClass( 'figure', $args['figure'] ).'">'.$html.'</figure>';
		}

		return ShortCode::wrap( $html, $this->constant( 'term_cover_shortcode' ), $args );
	}
}

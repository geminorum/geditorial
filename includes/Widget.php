<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Widget extends \WP_Widget
{

	const BASE   = 'geditorial';
	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( static::MODULE, $key, $default );
	}

	public function __construct()
	{
		$args = Template::atts( [
			'module'  => FALSE,
			'name'    => FALSE,
			'class'   => '',
			'title'   => '',
			'desc'    => '',
			'control' => [],
			'flush'   => [],
		], $this->setup() );

		if ( ! $args['name'] || ! $args['module'] )
			return FALSE;

		parent::__construct( static::BASE.'_'.$args['name'], $args['title'], [
			'description' => $args['desc'],
			'classname'   => '{GEDITORIAL_WIDGET_CLASSNAME}'.'widget-'.static::BASE.'-'.$args['class'],
		], $args['control'] );

		$this->alt_option_name = 'widget_'.static::BASE.'_'.$args['name'];
		$this->parent_module   = $args['module'];

		foreach ( $args['flush'] as $action )
			add_action( $action, [ $this, 'flush_widget_cache' ] );
	}

	public static function setup()
	{
		return [
			'name'  => '',
			'class' => '',
			'title' => '',
			'desc'  => '',
			'flush' => [
				'save_post',
				'deleted_post',
				'switch_theme',
			],

			// FIXME: help tab data for the admin widget screen
		];
	}

	// override this to bypass caching
	public function widget( $args, $instance )
	{
		$this->widget_cache( $args, $instance );
	}

	// override this for diffrent types of caching
	protected function widget_cache_key( $instance = [] )
	{
		return $this->alt_option_name;
	}

	public function widget_cache( $args, $instance, $prefix = '' )
	{
		if ( $this->is_preview() )
			return $this->widget_html( $args, $instance );

		$key = $this->widget_cache_key( $instance );

		if ( WordPress::isFlush() )
			delete_transient( $key );

		if ( FALSE === ( $cache = get_transient( $key ) ) )
			$cache = [];

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[$args['widget_id'].$prefix] ) )
			return print $cache[$args['widget_id'].$prefix];

		ob_start();

		if ( $this->widget_html( $args, $instance ) )
			$cache[$args['widget_id'].$prefix] = ob_get_flush();

		else
			return ob_end_flush();

		set_transient( $key, $cache, 12 * HOUR_IN_SECONDS );
	}

	// FIXME: DROP THIS
	public function widget_cache_OLD( $args, $instance, $prefix = '' )
	{
		$key   = $this->widget_cache_key( $instance );
		$cache = $this->is_preview() ? [] : wp_cache_get( $key, 'widget' );

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( is_array( $cache ) && isset( $cache[$args['widget_id'].$prefix] ) ) {
			echo $cache[$args['widget_id'].$prefix];
			return;
		}

		ob_start();

		if ( $this->widget_html( $args, $instance ) ) {
			if ( ! $this->is_preview() ) {
				$cache[$args['widget_id'].$prefix] = ob_get_flush();
				wp_cache_set( $key, $cache, 'widget' );
			} else {
				ob_end_flush();
			}
		} else {
			ob_end_flush();
		}
	}

	public function widget_html( $args, $instance )
	{
		return FALSE;
	}

	public function before_widget( $args, $instance, $extra_class = '', $echo = TRUE )
	{
		$classes = isset( $instance['context'] ) && $instance['context'] ? 'context-'.sanitize_html_class( $instance['context'], 'general' ).' ' : '';
		$classes.= isset( $instance['class'] ) && $instance['class'] ? $instance['class'].' ' : '';
		$classes.= $extra_class.' ';

		$html = preg_replace( '%{GEDITORIAL_WIDGET_CLASSNAME}%', $classes, $args['before_widget'] );

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public function after_widget( $args, $instance, $echo = TRUE )
	{
		if ( ! $echo )
			return $args['after_widget'];

		echo $args['after_widget'];
	}

	public function widget_title( $args, $instance, $default = '', $echo = TRUE )
	{
		$title = apply_filters( 'widget_title',
			empty( $instance['title'] ) ? $default : $instance['title'],
			$instance,
			$this->id_base
		);

		if ( ! $title )
			return '';

		if ( ! empty( $instance['title_link'] ) )
			$title = HTML::link( $title, $instance['title_link'] );

		$html = $args['before_title'].$title.$args['after_title'];

		if ( ! $echo )
			return $html;

		echo $html;
	}

	// NOTE: may not flush properly with no instance info
	public function flush_widget_cache()
	{
		$key = $this->widget_cache_key();
		// wp_cache_delete( $key, 'widget' );
		delete_transient( $key );
	}

	public function get_images_sizes( $posttype )
	{
		$images = [];
		$sizes  = gEditorial()->{$this->parent_module}->get_image_sizes( $posttype );

		if ( count( $sizes ) ) {
			foreach ( $sizes as $name => $size ) {
				$images[$name] = $size['n'].' ('.Number::localize( $size['w'] ).'&nbsp;&times;&nbsp;'.Number::localize( $size['h'] ).')';
			}
		} else {

			$sizes = Media::getPosttypeImageSizes( $posttype );

			if ( count( $sizes ) ) {
				foreach ( $sizes as $name => $size )
					$images[$name] = ( isset( $size['title'] ) ? $size['title'] : $name )
						.' ('.Number::localize( $size['width'] )
						.'&nbsp;&times;&nbsp;'
						.Number::localize( $size['height'] ).')';

			} else {
				// foreach ( Helper::getWPImageSizes() as $name => $size ) {
				// 	$images[$posttype.'-'.$name] = $size['n'].' ('.Number::localize( $size['w'] ).'&nbsp;&times;&nbsp;'.Number::localize( $size['h'] ).')';
				// }
			}
		}

		return $images;
	}

	public function before_form( $instance, $echo = TRUE )
	{
		$classes = [ static::BASE.'-wrap', '-wrap', '-admin-widgetform' ];

		if ( static::MODULE )
			$classes[] = '-'.static::MODULE;

		$html = '<div class="'.HTML::prepClass( $classes ).'">';

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public function after_form( $instance, $echo = TRUE )
	{
		$html = '</div>';

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public function form_content( $instance, $default = '', $field = 'content', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Custom HTML:', 'Widget Core', 'geditorial' );

		echo '<p>';

		HTML::label( $label, $this->get_field_id( $field ), FALSE );

		echo HTML::tag( 'textarea', [
			'rows'  => '3',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'class' => 'widefat code textarea-autosize',
		], isset( $instance[$field] ) ? $instance[$field] : $default );

		echo '</p>';
	}

	public function form_number( $instance, $default = '10', $field = 'number', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Number of posts to show:', 'Widget Core', 'geditorial' );

		$html = HTML::tag( 'input', [
			'type'  => 'number',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		] );

		HTML::label( $label.' '.$html, $this->get_field_id( $field ) );
	}

	public function form_context( $instance, $default = '', $field = 'context' )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		] );

		HTML::label( _x( 'Context:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_class( $instance, $default = '', $field = 'class' )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		] );

		HTML::label( _x( 'Class:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_post_type( $instance, $default = 'post', $field = 'post_type', $any = TRUE )
	{
		$html = '';
		$type = isset( $instance[$field] ) ? $instance[$field] : $default;

		if ( $any )
			$html.= HTML::tag( 'option', [
				'value'    => 'any',
				'selected' => $type == 'any',
			], _x( '&ndash; (Any)', 'Widget Core', 'geditorial' ) );

		foreach ( PostType::get() as $name => $title )
			$html.= HTML::tag( 'option', [
				'value'    => $name,
				'selected' => $type == $name,
			], $title );

		$html = HTML::tag( 'select', [
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		], $html );

		HTML::label( _x( 'PostType:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_taxonomy( $instance, $default = 'all', $field = 'taxonomy', $posttype_field = 'post_type', $posttype_default = 'any', $option_all = 'all' )
	{
		$html = '';
		$type = isset( $instance[$posttype_field] ) ? $instance[$posttype_field] : $posttype_default;
		$tax  = isset( $instance[$field] ) ? $instance[$field] : $default;

		if ( $option_all )
			$html.= HTML::tag( 'option', [
				'value' => $option_all,
			], _x( '&ndash; All Taxonomies &ndash;', 'Widget Core', 'geditorial' ) );

		foreach ( Taxonomy::get( 0, [], $type ) as $name => $title )
			$html.= HTML::tag( 'option', [
				'value'    => $name,
				'selected' => $tax == $name,
			], $title );

		$html = HTML::tag( 'select', [
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		], $html );

		HTML::label( _x( 'Taxonomy:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_title( $instance, $default = '', $field = 'title' )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		] );

		HTML::label( _x( 'Title:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_title_link( $instance, $default = '', $field = 'title_link' )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'url',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		] );

		HTML::label( _x( 'Title Link:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_custom_link( $instance, $default = '', $field = 'custom_link', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Custom Link:', 'Widget Core', 'geditorial' );

		$html = HTML::tag( 'input', [
			'type'  => 'url',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		] );

		HTML::label( $label.$html, $this->get_field_id( $field ) );
	}

	public function form_custom_code( $instance, $default = '', $field = 'custom_code', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Custom Code:', 'Widget Core', 'geditorial' );

		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat', 'code' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		] );

		HTML::label( $label.$html, $this->get_field_id( $field ) );
	}

	public function form_custom_empty( $instance, $default = '', $field = 'empty', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Empty Message:', 'Widget Core', 'geditorial' );

		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		] );

		HTML::label( $label.$html, $this->get_field_id( $field ) );
	}

	public function form_avatar_size( $instance, $default = '32', $field = 'avatar_size' )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'text',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		] );

		HTML::label( _x( 'Avatar Size:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}

	public function form_image_size( $instance, $default = 'thumbnail', $field = 'image_size', $posttype = 'post' )
	{
		$sizes = $this->get_images_sizes( $posttype );

		if ( count( $sizes ) ) {

			$selected = isset( $instance[$field] ) ? $instance[$field] : $default;
			$html     = '';

			foreach ( $sizes as $size => $title )
				$html.= HTML::tag( 'option', [
					'value'    => $size,
					'selected' => $selected == $size,
				], $title );

			$html = HTML::tag( 'select', [
				'class' => 'widefat',
				'name'  => $this->get_field_name( $field ),
				'id'    => $this->get_field_id( $field ),
			], $html );

			HTML::label( _x( 'Image Size:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );

		} else {
			HTML::desc( _x( 'No Image Size Available!', 'Widget Core', 'geditorial' ) );
		}
	}

	public function form_checkbox( $instance, $default = FALSE, $field = 'checked', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Checked', 'Widget Core', 'geditorial' );

		$html = HTML::tag( 'input', [
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( $field ),
			'id'      => $this->get_field_id( $field ),
			'checked' => isset( $instance[$field] ) ? $instance[$field] : $default,
		] );

		HTML::label( $html.'&nbsp;'.$label, $this->get_field_id( $field ) );
	}

	// only works on hierarchical
	public function form_page_id( $instance, $default = '0', $field = 'page_id', $posttype_field = 'posttype', $posttype_default = 'page', $label = NULL )
	{
		$posttype = isset( $instance[$posttype_field] ) ? $instance[$posttype_field] : $posttype_default;
		$page_id   = isset( $instance[$field] ) ? $instance[$field] : $default;

		if ( is_null( $label ) )
			$label = _x( 'Page:', 'Widget Core', 'geditorial' );

		$html = wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => $page_id,
			'name'             => $this->get_field_name( $field ),
			'id'               => $this->get_field_id( $field ),
			'class'            => 'widefat',
			'show_option_none' => Settings::showOptionNone(),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => FALSE,
		] );

		if ( ! $html ) {
			$html = ' '.Plugin::na();
			HTML::inputHidden( $this->get_field_name( $field ), $page_id );
		}

		HTML::label( $label.$html, $this->get_field_id( $field ) );
	}

	public function form_term_id( $instance, $default = '0', $field = 'term_id', $taxonomy_field = 'taxonomy', $taxonomy_default = 'post_tag' )
	{
		$taxonomy = isset( $instance[$taxonomy_field] ) ? $instance[$taxonomy_field] : $taxonomy_default;
		$term_id  = isset( $instance[$field] ) ? $instance[$field] : $default;

		$html = HTML::tag( 'option', [
			'value'    => '0',
			'selected' => $term_id == '0',
		], Settings::showOptionNone() );

		foreach ( get_terms( [ 'taxonomy' => $taxonomy ] ) as $term )
			$html.= HTML::tag( 'option', [
				'value'    => $term->term_id,
				'selected' => $term_id == $term->term_id,
			], $term->name );

		$html = HTML::tag( 'select', [
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		], $html );

		HTML::label( _x( 'Term:', 'Widget Core', 'geditorial' ).$html, $this->get_field_id( $field ) );
	}
}

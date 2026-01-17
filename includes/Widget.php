<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

#[\AllowDynamicProperties]
class Widget extends \WP_Widget
{
	const BASE   = 'geditorial';
	const MODULE = FALSE;
	const WIDGET = FALSE;

	public static function factory()
	{
		return gEditorial();
	}

	protected static function constant( $key, $default = FALSE, $module = NULL )
	{
		return static::factory()->constant( $module ?? static::MODULE, $key, $default );
	}

	protected static function filters( $hook, ...$args )
	{
		return apply_filters( sprintf( '%s_%s_%s',
			static::BASE,
			static::WIDGET, // NOTE: usually the widget name also contain the module name
			$hook
		), ...$args );
	}

	protected static function actions( $hook, ...$args )
	{
		return do_action( sprintf( '%s_%s_%s',
			static::BASE,
			static::WIDGET, // NOTE: usually the widget name also contain the module name
			$hook
		), ...$args );
	}

	protected static function posttypes( $posttypes = NULL, $check = FALSE, $module = NULL )
	{
		if ( ! $module = $module ?? static::MODULE )
			return [];

		if ( $check && ! static::factory()->enabled( $module ) )
			return [];

		return static::factory()->module( $module )->posttypes( $posttypes );
	}

	public function __construct()
	{
		$args = Template::atts( [
			'module' => static::MODULE,
			'name'   => static::WIDGET,
			'class'   => '',
			'title'   => '',
			'desc'    => '',
			'control' => [],
			'flush'   => [],
		], $this->setup() );

		if ( ! $args['name'] || ! $args['module'] )
			return FALSE;

		if ( empty( $args['class'] ) )
			$args['class'] = str_replace( '_', '-', $args['name'] );

		parent::__construct( static::BASE.'_'.$args['name'], $args['title'], [
			'description' => $args['desc'],
			'classname'   => '{GEDITORIAL_WIDGET_CLASSNAME} '.'widget-'.static::BASE.'-'.$args['class'],

			'customize_selective_refresh' => TRUE,
			'show_instance_in_rest'       => TRUE,
		], $args['control'] );

		$this->alt_option_name = 'widget_'.static::BASE.'_'.$args['name'];
		$this->parent_module   = $args['module'];
		$this->_widget_args    = $args;

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

			// TODO: help tab data for the admin widget screen
		];
	}

	// override this to bypass caching
	public function widget( $args, $instance )
	{
		$this->widget_cache( $args, $instance );
	}

	// override this for different types of caching
	protected function widget_cache_key( $instance = [] )
	{
		return $this->alt_option_name;
	}

	public function widget_cache( $args, $instance, $prefix = '' )
	{
		if ( $this->is_preview() )
			return $this->widget_html( $args, $instance );

		$key = $this->widget_cache_key( $instance );

		if ( WordPress\IsIt::flush() )
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

	public function widget_html( $args, $instance )
	{
		return FALSE;
	}

	public function before_widget( $args, $instance, $verbose = TRUE, $extra = '' )
	{
		$classes = [];

		if ( ! empty( $instance['context'] ) )
			$classes[] = 'context-'.$instance['context'];

		if ( ! empty( $instance['class'] ) )
			$classes[] = $instance['class'];

		if ( ! empty( $instance['title_image'] ) )
			$classes[] = '-has-title-image';

		$html = preg_replace( '%{GEDITORIAL_WIDGET_CLASSNAME}%', Core\HTML::prepClass( $classes, $extra ), $args['before_widget'] );

		if ( ! empty( $instance['open_widget_html'] ) )
			$html.= trim( $instance['open_widget_html'] );

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public function after_widget( $args, $instance, $verbose = TRUE )
	{
		$html = $args['after_widget'];

		if ( ! empty( $instance['close_widget_html'] ) )
			$html = trim( $instance['close_widget_html'] ).$html;

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public function widget_title( $args, $instance, $verbose = TRUE, $default = '' )
	{
		$title = apply_filters( 'widget_title',
			empty( $instance['title'] ) ? $default : $instance['title'],
			$instance,
			$this->id_base
		);

		if ( ! $title )
			return '';

		if ( ! empty( $instance['title_image'] ) )
			$title = Core\HTML::img( $instance['title_image'], '-title-image', $title );

		if ( ! empty( $instance['title_link'] ) )
			$title = Core\HTML::link( $title, $instance['title_link'] );

		$html = $args['before_title'].$title.$args['after_title'];

		if ( ! empty( $instance['after_title_html'] ) )
			$html.= trim( $instance['after_title_html'] );

		if ( ! $verbose )
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

	public function update( $new, $old )
	{
		$this->flush_widget_cache();
		return $this->handle_update( $new, $old );
	}

	public function handle_update( $new, $old, $checkboxes = [], $extra = [] )
	{
		$fields = array_merge( [
			'title'        => 'text',
			'custom_title' => 'text',

			'title_link'  => 'url',
			'title_image' => 'url',
			'custom_link' => 'url',

			'content'           => 'html',
			'empty'             => 'html',
			'open_widget_html'  => 'html',
			'after_title_html'  => 'html',
			'close_widget_html' => 'html',

			'class'      => 'key',
			'post_type'  => 'key',
			'posttype'   => 'key',
			'taxonomy'   => 'key',
			'context'    => 'key',
			'image_size' => 'key',

			'posttypes'  => 'keys',
			'taxonomies' => 'keys',

			'post_id'     => 'digit',
			'page_id'     => 'digit',
			'term_id'     => 'digit',
			'number'      => 'digit',
			'trim_chars'  => 'digit',
			'avatar_size' => 'digit',
		], $extra );

		$instance   = $new; // Apparently all necessary fields will pass on the new!
		$unfiltered = current_user_can( 'unfiltered_html' );

		foreach ( $fields as $field => $type ) {

			if ( ! array_key_exists( $field, $new ) )
				continue;

			switch ( $type ) {
				case 'key':   $instance[$field] = Core\Text::stripTags( $new[$field] ); break;
				case 'keys':  $instance[$field] = array_keys( array_filter( $new[$field] ) ); break;
				case 'digit': $instance[$field] = (int) $new[$field]; break;
				case 'text':  $instance[$field] = sanitize_text_field( $new[$field] ); break;
				case 'url':   $instance[$field] = Core\Text::stripTags( $new[$field] ); break;
				case 'html':  $instance[$field] = trim( $unfiltered ? $new[$field] : wp_kses_post( $new[$field] ) ); break;

				default: $instance[$field] = Core\Text::stripTags( $new[$type] );
			}
		}

		// checkbox keys will not passed
		foreach ( $checkboxes as $checkbox )
			$instance[$checkbox] = isset( $new[$checkbox] );

		return $instance;
	}

	public function get_images_sizes( $posttype )
	{
		$images   = [];
		$sizes    = gEditorial()->module( $this->parent_module )->get_image_sizes_for_posttype( $posttype );
		$template = '%s (%s&nbsp;&times;&nbsp;%s)';

		if ( count( $sizes ) ) {

			foreach ( $sizes as $name => $size )
				$images[$name] = vsprintf( $template, [
					$size['n'],
					Core\Number::localize( $size['w'] ),
					Core\Number::localize( $size['h'] ),
				] );

		} else {

			$sizes = WordPress\Media::getPosttypeImageSizes( $posttype );

			if ( count( $sizes ) ) {

				foreach ( $sizes as $name => $size )
					$images[$name] = vsprintf( $template, [
						$size['title'] ?? $name,
						Core\Number::localize( $size['width'] ),
						Core\Number::localize( $size['height'] ),
					] );

			} else {
				// foreach ( WordPress\Media::defaultImageSizes() as $name => $size ) {
				// 	$images[$posttype.'-'.$name] = $size['n'].' ('.Core\Number::localize( $size['w'] ).'&nbsp;&times;&nbsp;'.Core\Number::localize( $size['h'] ).')';
				// }
			}
		}

		return $images;
	}

	public function before_form( $instance, $verbose = TRUE )
	{
		$classes = [ static::BASE.'-wrap', '-wrap', '-admin-widgetform' ];

		if ( static::MODULE )
			$classes[] = '-'.static::MODULE;

		$html = '<div class="'.Core\HTML::prepClass( $classes ).'">';

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public function after_form( $instance, $verbose = TRUE )
	{
		$html = '</div>';

		if ( ! $verbose )
			return $html;

		echo $html;
	}

	public function form_open_group( $title = FALSE )
	{
		echo '<div class="-group">';

		if ( ! $title )
			return;

		switch ( $title ) {
			case 'heading': Core\HTML::h4( _x( 'Heading', 'Widget Core: Group', 'geditorial-admin' ),       '-title' ); break;
			case 'misc'   : Core\HTML::h4( _x( 'Miscellaneous', 'Widget Core: Group', 'geditorial-admin' ), '-title' ); break;
			case 'customs': Core\HTML::h4( _x( 'Customs', 'Widget Core: Group', 'geditorial-admin' ),       '-title' ); break;
			case 'config' : Core\HTML::h4( _x( 'Configuration', 'Widget Core: Group', 'geditorial-admin' ), '-title' ); break;
			default       : Core\HTML::h4( $title, '-title' ); break;
		}
	}

	public function form_close_group( $desc = FALSE )
	{
		Core\HTML::desc( $desc );
		echo '</div>';
	}

	public function form_content( $instance, $default = '', $field = 'content', $label = NULL )
	{
		echo '<p>';

		Core\HTML::label(
			$label ?? _x( 'Custom HTML:', 'Widget Core', 'geditorial-admin' ),
			$this->get_field_id( $field ),
			FALSE
		);

		echo Core\HTML::tag( 'textarea', [
			'rows'  => '3',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'class' => [ 'widefat', 'code', 'textarea-autosize' ],
		], $instance[$field] ?? $default );

		echo '</p>';
	}

	public function form_open_widget( $instance, $default = '', $field = 'open_widget_html', $label = NULL )
	{
		echo '<p>';

		Core\HTML::label(
			$label ?? _x( 'Custom HTML for Opening Widget:', 'Widget Core', 'geditorial-admin' ),
			$this->get_field_id( $field ),
			FALSE
		);

		echo Core\HTML::tag( 'textarea', [
			'rows'  => '1',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'class' => [ 'widefat', 'code', 'textarea-autosize' ],
		], $instance[$field] ?? $default );

		echo '</p>';
	}

	public function form_close_widget( $instance, $default = '', $field = 'close_widget_html', $label = NULL )
	{
		echo '<p>';

		Core\HTML::label(
			$label ?? _x( 'Custom HTML for Closing Widget:', 'Widget Core', 'geditorial-admin' ),
			$this->get_field_id( $field ),
			FALSE
		);

		echo Core\HTML::tag( 'textarea', [
			'rows'  => '1',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'class' => [ 'widefat', 'code', 'textarea-autosize' ],
		], $instance[$field] ?? $default );

		echo '</p>';
	}

	public function form_after_title( $instance, $default = '', $field = 'after_title_html', $label = NULL )
	{
		echo '<p>';

		Core\HTML::label(
			$label ?? _x( 'Custom HTML for After Title:', 'Widget Core', 'geditorial-admin' ),
			$this->get_field_id( $field ),
			FALSE
		);

		echo Core\HTML::tag( 'textarea', [
			'rows'  => '1',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'class' => [ 'widefat', 'code', 'textarea-autosize' ],
		], $instance[$field] ?? $default );

		echo '</p>';
	}

	public function form_number( $instance, $default = '10', $field = 'number', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'number',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Number of posts to show:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_trim_chars( $instance, $default = '', $field = 'trim_chars', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'number',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Trim Characters:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_context( $instance, $default = '', $field = 'context', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat', 'code-text' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Context:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_class( $instance, $default = '', $field = 'class', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat', 'code-text' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Class:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_post_type( $instance, $default = 'post', $field = 'post_type', $any = TRUE, $label = NULL )
	{
		$html = '';
		$type = $instance[$field] ?? $default;

		if ( $any )
			$html.= Core\HTML::tag( 'option', [
				'value'    => 'any',
				'selected' => $type == 'any',
			], _x( '&ndash; (Any)', 'Widget Core', 'geditorial-admin' ) );

		foreach ( WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ) as $name => $title )
			$html.= Core\HTML::tag( 'option', [
				'value'    => $name,
				'selected' => $type == $name,
			], $title );

		$html = Core\HTML::tag( 'select', [
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		], $html );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'PostType:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_taxonomies( $instance, $default = '', $field = 'taxonomies', $posttype_field = 'post_type', $posttype_default = 'any', $label = NULL )
	{
		$type  = $instance[$posttype_field] ?? $posttype_default;
		$taxes = $instance[$field] ?? $default;

		$name = $this->get_field_name( $field );
		$id   = $this->get_field_id( $field );

		printf( '<div class="-label">%s</div>', $label ?? _x( 'Taxonomies:', 'Widget Core', 'geditorial-admin' ) );
		echo Settings::tabPanelOpen();

		foreach ( WordPress\Taxonomy::get( 0, [], $type ) as $value_name => $value_title ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'id'      => $id.'-'.$value_name,
				'name'    => $name.'['.$value_name.']',
				'checked' => in_array( $value_name, (array) $taxes ),
				'value'   => '1',
			] );

			Core\HTML::label(
				$html.'&nbsp;'.$value_title,
				$id.'-'.$value_name,
				'li'
			);
		}

		echo '</ul></div>';
	}

	public function form_taxonomy( $instance, $default = 'all', $field = 'taxonomy', $posttype_field = 'post_type', $posttype_default = 'any', $option_all = 'all', $label = NULL )
	{
		$html = '';
		$type = $instance[$posttype_field] ?? $posttype_default;
		$tax  = $instance[$field] ?? $default;

		if ( $option_all )
			$html.= Core\HTML::tag( 'option', [
				'value' => $option_all,
			], _x( '&ndash; All Taxonomies &ndash;', 'Widget Core', 'geditorial-admin' ) );

		foreach ( WordPress\Taxonomy::get( 0, [], $type ) as $name => $title )
			$html.= Core\HTML::tag( 'option', [
				'value'    => $name,
				'selected' => $tax == $name,
			], $title );

		$html = Core\HTML::tag( 'select', [
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		], $html );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Taxonomy:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_title( $instance, $default = '', $field = 'title', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Title:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_title_link( $instance, $default = '', $field = 'title_link', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text', // `url` will not work on relative URLs
			'class' => [ 'widefat', 'code-text' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Title Link:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_title_image( $instance, $default = '', $field = 'title_image', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'url',
			'class' => [ 'widefat', 'code-text' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Title Image:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_custom_link( $instance, $default = '', $field = 'custom_link', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text', // `url` will not work on relative URLs
			'class' => [ 'widefat', 'code-text' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Custom Link:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_custom_code( $instance, $default = '', $field = 'custom_code', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat', 'code-text' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Custom Code:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_custom_title( $instance, $default = '', $field = 'custom_title', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Custom Title:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_custom_empty( $instance, $default = '', $field = 'empty', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'class' => [ 'widefat' ],
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? _x( 'Empty Message:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_avatar_size( $instance, $default = '32', $field = 'avatar_size' )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'text',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', _x( 'Avatar Size:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_image_size( $instance, $default = NULL, $field = 'image_size', $posttype = 'post' )
	{
		$sizes   = $this->get_images_sizes( $posttype );
		$default = $default ?? WordPress\Media::getAttachmentImageDefaultSize( $posttype );

		if ( count( $sizes ) ) {

			$selected = $instance[$field] ?? $default;
			$html     = '';

			foreach ( $sizes as $size => $title )
				$html.= Core\HTML::tag( 'option', [
					'value'    => $size,
					'selected' => $selected == $size,
				], $title );

			$html = Core\HTML::tag( 'select', [
				'class' => 'widefat',
				'name'  => $this->get_field_name( $field ),
				'id'    => $this->get_field_id( $field ),
			], $html );

			Core\HTML::label(
				sprintf( '%s %s', _x( 'Image Size:', 'Widget Core', 'geditorial-admin' ), $html ),
				$this->get_field_id( $field )
			);

		} else {

			Core\HTML::desc( _x( 'There are no image sizes available!', 'Widget Core', 'geditorial-admin' ) );
		}
	}

	public function form_dropdown( $instance, $values, $default = '', $field = 'selected', $label = NULL )
	{
		$html = Core\HTML::dropdown( $values, [
			'class'      => 'widefat',
			'name'       => $this->get_field_name( $field ),
			'id'         => $this->get_field_id( $field ),
			'none_title' => _x( '&ndash; Select &ndash;', 'Widget Core', 'geditorial-admin' ),
			'selected'   => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s %s', $label ?? '', $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_checkbox( $instance, $default = FALSE, $field = 'checked', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( $field ),
			'id'      => $this->get_field_id( $field ),
			'checked' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s&nbsp;%s', $html, $label ?? _x( 'Checked', 'Widget Core', 'geditorial-admin' ) ),
			$this->get_field_id( $field )
		);
	}

	// only works on hierarchical
	public function form_page_id( $instance, $default = '0', $field = 'page_id', $posttype_field = 'posttype', $posttype_default = 'page', $label = NULL )
	{
		$posttype = $instance[$posttype_field] ?? $posttype_default;
		$page_id  = $instance[$field] ?? $default;

		$html = wp_dropdown_pages( [
			'post_type'        => $posttype,
			'selected'         => $page_id,
			'name'             => $this->get_field_name( $field ),
			'id'               => $this->get_field_id( $field ),
			'class'            => 'widefat',
			'show_option_none' => Services\CustomPostType::getLabel( $posttype, 'show_option_select' ),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => FALSE,
		] );

		if ( ! $html ) {
			$html = ' '.Plugin::na();
			Core\HTML::inputHidden( $this->get_field_name( $field ), $page_id );
		}

		Core\HTML::label(
			sprintf( '%s %s', _x( 'Page:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_term_id( $instance, $default = '0', $field = 'term_id', $taxonomy_field = 'taxonomy', $taxonomy_default = 'post_tag' )
	{
		$taxonomy = $instance[$taxonomy_field] ?? $taxonomy_default;
		$term_id  = $instance[$field] ?? $default;

		if ( 'all' == $taxonomy )
			return Core\HTML::desc( '<br />'._x( 'Select taxonomy first!', 'Widget Core', 'geditorial-admin' ), TRUE, '-empty' );

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => FALSE,
		] );

		if ( is_wp_error( $terms ) )
			return Core\HTML::desc( '<br />'._x( 'The taxonomy is not available!', 'Widget Core', 'geditorial-admin' ), TRUE, '-empty' );

		if ( empty( $terms ) )
			return Core\HTML::desc( '<br />'._x( 'There are no terms available!', 'Widget Core', 'geditorial-admin' ), TRUE, '-empty' );

		$html = Core\HTML::tag( 'option', [
			'value'    => '0',
			'selected' => $term_id == '0',
		], Settings::showOptionNone() );

		foreach ( $terms as $term )
			$html.= Core\HTML::tag( 'option', [
				'value'    => $term->term_id,
				'selected' => $term_id == $term->term_id,
			], $term->name );

		$html = Core\HTML::tag( 'select', [
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		], $html );

		Core\HTML::label(
			sprintf( '%s %s', _x( 'Term:', 'Widget Core', 'geditorial-admin' ), $html ),
			$this->get_field_id( $field )
		);
	}

	public function form_has_thumbnail( $instance, $default = FALSE, $field = 'has_thumbnail', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( $field ),
			'id'      => $this->get_field_id( $field ),
			'checked' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s&nbsp;%s', $html, $label ?? _x( 'Must has Thumbnail Image', 'Widget Core', 'geditorial-admin' ) ),
			$this->get_field_id( $field )
		);
	}

	public function form_wrap_as_items( $instance, $default = TRUE, $field = 'wrap_as_items', $label = NULL )
	{
		$html = Core\HTML::tag( 'input', [
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( $field ),
			'id'      => $this->get_field_id( $field ),
			'checked' => $instance[$field] ?? $default,
		] );

		Core\HTML::label(
			sprintf( '%s&nbsp;%s', $html, $label ?? _x( 'Wrap as List Items', 'Widget Core', 'geditorial-admin' ) ),
			$this->get_field_id( $field )
		);
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialWidgetCore extends WP_Widget
{

	const MODULE = FALSE;

	protected static function constant( $key, $default = FALSE )
	{
		return gEditorial()->constant( self::MODULE, $key, $default );
	}

	public function __construct()
	{
		$args = gEditorialTemplateCore::atts( array(
			'module' => FALSE,
			'name'   => FALSE,
			'class'  => '',
			'title'  => '',
			'desc'   => '',
			'flush'  => array(),
		), $this->setup() );

		if ( ! $args['name'] || ! $args['module'] )
			return FALSE;

		parent::__construct( 'geditorial_'.$args['name'], $args['title'], array(
			'description' => $args['desc'],
			'classname'   => '{GEDITORIAL_WIDGET_CLASSNAME}'.'widget-geditorial-'.$args['class'],
		) );

		$this->alt_option_name = 'widget_geditorial_'.$args['name'];
		$this->parent_module   = $args['module'];

		foreach ( $args['flush'] as $action )
			add_action( $action, array( $this, 'flush_widget_cache' ) );
	}

	protected function setup()
	{
		return array(
			'name'  => '',
			'class' => '',
			'title' => '',
			'desc'  => '',
			'flush' => array(
				'save_post',
				'deleted_post',
				'switch_theme',
			),

			// FIXME: help tab data for the admin widget screen
		);
	}

	public function widget( $args, $instance )
	{
		$this->widget_cache( $args, $instance );
	}

	public function widget_cache( $args, $instance, $prefix = '' )
	{
		$cache = $this->is_preview() ? array() : wp_cache_get( $this->alt_option_name, 'widget' );

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
				wp_cache_set( $this->alt_option_name, $cache, 'widget' );
			} else {
				ob_end_flush();
			}
		} else {
			ob_end_flush();
		}
	}

	public function before_widget( $args, $instance, $echo = TRUE )
	{
		$classes = isset( $instance['context'] ) && $instance['context'] ? 'context-'.sanitize_html_class( $instance['context'], 'general' ).' ' : '';
		$classes .= isset( $instance['class'] ) && $instance['class'] ? $instance['class'].' ' : '';

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

	public function widget_title( $args, $instance, $echo = TRUE )
	{
		$title = apply_filters( 'widget_title',
			empty( $instance['title'] ) ? '' : $instance['title'],
			$instance,
			$this->id_base
		);

		if ( $title && isset( $instance['title_link'] ) && $instance['title_link'] )
			$title = gEditorialHTML::tag( 'a', array(
				'href' => $instance['title_link'],
			), $title );

		if ( ! $title )
			return '';

		$html = $args['before_title'].$title.$args['after_title'];

		if ( ! $echo )
			return $html;

		echo $html;
	}

	public function flush_widget_cache()
	{
		wp_cache_delete( $this->alt_option_name, 'widget' );
	}

	public function get_images_sizes( $post_type )
	{
		$images = array();
		$sizes  = gEditorial()->{$this->parent_module}->get_image_sizes( $post_type );

		if ( count( $sizes ) ) {
			foreach ( $sizes as $name => $size ) {
				$images[$name] = $size['n'].' ('.gEditorialNumber::format( $size['w'] ).'&nbsp;&times;&nbsp;'.gEditorialNumber::format( $size['h'] ).')';
			}
		} else {

			$sizes = gEditorialHelper::getRegisteredImageSizes( $post_type );

			if ( count( $sizes ) ) {
				foreach ( $sizes as $name => $size )
					$images[$name] = ( isset( $size['title'] ) ? $size['title'] : $name )
						.' ('.gEditorialNumber::format( $size['width'] )
						.'&nbsp;&times;&nbsp;'
						.gEditorialNumber::format( $size['height'] ).')';

			} else {
				// foreach ( gEditorialHelper::getWPImageSizes() as $name => $size ) {
				// 	$images[$post_type.'-'.$name] = $size['n'].' ('.gEditorialNumber::format( $size['w'] ).'&nbsp;&times;&nbsp;'.gEditorialNumber::format( $size['h'] ).')';
				// }
			}
		}

		return $images;
	}

	public function form_number( $instance, $default = '10', $field = 'number' )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'text',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Number of posts to show:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).' '.$html ).'</p>';
	}

	public function form_context( $instance, $default = '', $field = 'context' )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Context:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_class( $instance, $default = '', $field = 'class' )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Class:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_post_type( $instance, $default = 'post', $field = 'post_type' )
	{
		$html = '';
		$type = isset( $instance[$field] ) ? $instance[$field] : $default;

		foreach ( gEditorialWPPostType::get() as $name => $title )
			$html .= gEditorialHTML::tag( 'option', array(
				'value'    => $name,
				'selected' => $type == $name,
			), $title );

		$html = gEditorialHTML::tag( 'select', array(
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		), $html );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'PostType:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_taxonomy( $instance, $default = 'post_tag', $field = 'taxonomy' )
	{
		$html = '';
		$tax = isset( $instance[$field] ) ? $instance[$field] : $default;

		foreach ( gEditorialWPTaxonomy::get( 5 ) as $name => $title )
			$html .= gEditorialHTML::tag( 'option', array(
				'value'    => $name,
				'selected' => $tax == $name,
			), $title );

		$html = gEditorialHTML::tag( 'select', array(
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		), $html );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Taxonomy:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_title( $instance, $default = '', $field = 'title' )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Title:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_title_link( $instance, $default = '', $field = 'title_link' )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
			'dir'   => 'ltr',
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Title Link:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_avatar_size( $instance, $default = '32', $field = 'avatar_size' )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'text',
			'size'  => 3,
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'value' => isset( $instance[$field] ) ? $instance[$field] : $default,
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Avatar Size:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}

	public function form_image_size( $instance, $default = 'thumbnail', $field = 'image_size', $post_type = 'post' )
	{
		$sizes = $this->get_images_sizes( $post_type );

		if ( count( $sizes ) ) {

			$selected = isset( $instance[$field] ) ? $instance[$field] : $default;
			$html     = '';

			foreach ( $sizes as $size => $title )
				$html .= gEditorialHTML::tag( 'option', array(
					'value'    => $size,
					'selected' => $selected == $size,
				), $title );

			$html = gEditorialHTML::tag( 'select', array(
				'class' => 'widefat',
				'name'  => $this->get_field_name( $field ),
				'id'    => $this->get_field_id( $field ),
			), $html );

			echo '<p>'. gEditorialHTML::tag( 'label', array(
				'for' => $this->get_field_id( $field ),
			), _x( 'Image Size:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';

		} else {
			echo '<p>'._x( 'No Image Size Available!', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).'</p>';
		}
	}

	public function form_checkbox( $instance, $default = FALSE, $field = 'checked', $label = NULL )
	{
		if ( is_null( $label ) )
			$label = _x( 'Checked:', 'Widget Core', GEDITORIAL_TEXTDOMAIN );

		$html = gEditorialHTML::tag( 'input', array(
			'type'    => 'checkbox',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
			'checked' => isset( $instance[$field] ) ? $instance[$field] : $default,
		) );

		echo '<p>'.$html.'&nbsp;'.gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), $label ).'</p>';
	}

	public function form_post_id( $instance, $default = '0', $field = 'post_id', $post_type_field = 'posttype', $post_type_default = 'page', $label = NULL )
	{
		$post_type = isset( $instance[$post_type_field] ) ? $instance[$post_type_field] : $post_type_default;
		$post_id  = isset( $instance[$field] ) ? $instance[$field] : $default;

		if ( is_null( $label ) )
			$label = _x( 'Page:', 'Widget Core', GEDITORIAL_TEXTDOMAIN );

		$html = wp_dropdown_pages( array(
			'post_type'        => $post_type,
			'selected'         => $post_id,
			'name'             => $this->get_field_name( $field ),
			'id'               => $this->get_field_id( $field ),
			'class'            => 'widefat',
			'show_option_none' => gEditorialSettingsCore::showOptionNone(),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => FALSE,
		) );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), $label.$html ).'</p>';
	}

	public function form_term_id( $instance, $default = '0', $field = 'term_id', $taxonomy_field = 'taxonomy', $taxonomy_default = 'post_tag' )
	{
		$taxonomy = isset( $instance[$taxonomy_field] ) ? $instance[$taxonomy_field] : $taxonomy_default;
		$term_id  = isset( $instance[$field] ) ? $instance[$field] : $default;

		$html = gEditorialHTML::tag( 'option', array(
			'value'    => '0',
			'selected' => $term_id == '0',
		), _x( '&mdash; Select &mdash;', 'Widget Core', GEDITORIAL_TEXTDOMAIN ) );

		foreach ( get_terms( $taxonomy ) as $term )
			$html .= gEditorialHTML::tag( 'option', array(
				'value'    => $term->term_id,
				'selected' => $term_id == $term->term_id,
			), $term->name );

		$html = gEditorialHTML::tag( 'select', array(
			'class' => 'widefat',
			'name'  => $this->get_field_name( $field ),
			'id'    => $this->get_field_id( $field ),
		), $html );

		echo '<p>'. gEditorialHTML::tag( 'label', array(
			'for' => $this->get_field_id( $field ),
		), _x( 'Term:', 'Widget Core', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';
	}
}

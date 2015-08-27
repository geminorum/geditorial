<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazineWidget_IssueCover extends WP_Widget
{

	public function __construct()
	{
		parent::__construct( 'geditorialmagazine_issue_cover_widget',
			_x( 'Issue Cover', 'Magazine: Widget: title', GEDITORIAL_TEXTDOMAIN ), array(
				'classname'   => 'widget-geditorialmagazine-issue-cover',
				'description' => _x( 'Displays selected issue cover', 'Magazine: Widget: description', GEDITORIAL_TEXTDOMAIN ),
		) );
		$this->alt_option_name = 'widget_geditorialmagazine_issue_cover';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );
	}

	public function widget( $args, $instance )
	{
		global $gEditorial;

		if ( ! $instance['latest'] && empty ( $instance['issue'] ) && ! is_singular() )
			return;

		$id = false;

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( ! empty( $instance['latest'] ) ) {
			$key = $args['widget_id'].'_latest';
		} else if ( ! empty ( $instance['issue'] ) ) {
			$key = $args['widget_id'].'_issue_'.$instance['issue'];
		} else {
			$id = get_queried_object_id();
			$key = $args['widget_id'].'_queried_'.$id;
		}

		$cache = wp_cache_get( $this->alt_option_name, 'widget' );

		if ( ! is_array( $cache ) )
			$cache = array();

		if ( isset( $cache[$key] ) ) {
			echo $cache[$key];
			return;
		}

		$issue_func = array( 'gEditorialMagazineTemplates', 'issue_cover' );
		$title = apply_filters( 'widget_title',
			empty( $instance['title'] ) ? '' : $instance['title'],
			$instance,
			$this->id_base
		);

		if ( $title )
			$title = $args['before_title'].$title.$args['after_title'];

		if ( ! empty( $instance['latest'] ) ) {
			$the_post = get_posts( array(
				'numberposts' => 1,
				'orderby'     => 'menu_order', //'post_date',
				'order'       => 'DESC',
				'post_type'   => $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' ),
				'post_status' => 'publish',
			) );
			if ( count ( $the_post ) )
				$id = $the_post[0]->ID;
		} else if ( ! empty ( $instance['issue'] ) ) {
			$id = $instance['issue'];
		} else {
			$issue_cpt = $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' );
			if ( $issue_cpt != get_post_type( $id ) )
				$issue_func = array( 'gEditorialMagazineTemplates', 'the_issue_cover' );
		}

		if ( false === $id || ! is_callable( $issue_func ) )
			return;

		$issue_func_args = array( '', '',
			( empty( $instance['size'] ) ? 'issue-thumbnail' : $instance['size'] ),
			( $instance['link'] ? 'parent' : false ), // WHATIF? : custom link?
			array(
				'id'    => $id,
				'echo'  => 'false',
				'cb'    => apply_filters( 'geditorial_magazine_widget_issue_cover_cb',
					array( 'gEditorialMagazineTemplates', 'issue_cover_callback' ), $instance, $this->id_base ),
				'title' => ( $instance['number'] ? 'number' : false ), // INFO: or false / 'title'
			),
		);

		$result = call_user_func_array( $issue_func, $issue_func_args );

		if ( ! $result )
			return;

		$result = $args['before_widget'].$title.$result.$args['after_widget'];

		$cache[$key] = $result;
		wp_cache_set( $this->alt_option_name, $cache, 'widget' );
		echo $result;
	}

	public function update( $new_instance, $old_instance )
	{
		$instance           = $new_instance;
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['latest'] = isset( $new_instance['latest'] );
		$instance['link']   = isset( $new_instance['link'] );
		$instance['number'] = isset( $new_instance['number'] );

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions[$this->alt_option_name] ) )
			delete_option( $this->alt_option_name );

		return $instance;
	}

	public function flush_widget_cache()
	{
		wp_cache_delete( $this->alt_option_name, 'widget' );
	}

	public function get_images_sizes()
	{
		global $gEditorial;

		$images = array();
		foreach ( $gEditorial->magazine->get_image_sizes() as $name => $size )
			$images[$name] = $size['n'].' ('.number_format_i18n( $size['w'] ).'&nbsp;&times;&nbsp;'.number_format_i18n( $size['h'] ).')';
		return $images;
	}

	public function form( $instance )
	{
		global $gEditorial;

		echo '<div class="geditorial-admin-wrap-widgetform">';

		$html = gEditorialHelper::html( 'input', array(
			'type'  => 'text',
			'class' => 'widefat',
			'name'  => $this->get_field_name( 'title' ),
			'id'    => $this->get_field_id( 'title' ),
			'value' => isset( $instance['title'] ) ? $instance['title'] : __( 'The Latest Issue', GEDITORIAL_TEXTDOMAIN ),
		) );

		echo '<p>'.gEditorialHelper::html( 'label', array(
			'for' => $this->get_field_id( 'title' ),
		), __( 'Title:', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';

		$html = wp_dropdown_pages( array(
			'post_type'        => $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' ),
			'selected'         => isset( $instance['issue'] ) ? $instance['issue'] : '0',
			'name'             => $this->get_field_name( 'issue' ),
			'id'               => $this->get_field_id( 'issue' ),
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => __( '&mdash; Select an Issue &mdash;', GEDITORIAL_TEXTDOMAIN ),
			// 'hierarchical'     => 0,
			'sort_column'      => 'menu_order, post_title',
			'echo'             => 0,
		) );

		echo '<p>'.gEditorialHelper::html( 'label', array(
			'for' => $this->get_field_id( 'issue' ),
		), _x( 'The Issue:', 'Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';

		$html = '';
		$value = isset( $instance['size'] ) ? $instance['size'] : 'issue-thumbnail';

		foreach ( self::get_images_sizes() as $image_size => $image_size_title )
			$html .= gEditorialHelper::html( 'option', array(
				'value'    => $image_size,
				'selected' => $image_size == $value,
			), esc_html( $image_size_title ) );

		$html = gEditorialHelper::html( 'select', array(
			'class' => 'widefat',
			'name'  => $this->get_field_name( 'size' ),
			'id'    => $this->get_field_id( 'size' ),
		), $html );

		echo '<p>'.gEditorialHelper::html( 'label', array(
			'for' => $this->get_field_id( 'size' ),
		), _x( 'Image Size:', 'Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ).$html ).'</p>';

		$html = gEditorialHelper::html( 'input', array(
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( 'latest' ),
			'id'      => $this->get_field_id( 'latest' ),
			'checked' => isset( $instance['latest'] ) ? $instance['latest'] : false,
		) );

		echo '<p>'.$html.'&nbsp;'.gEditorialHelper::html( 'label', array(
			'for' => $this->get_field_id( 'latest' ),
		), _x( 'Always the latest issue', 'Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) ).'</p>';

		$html = gEditorialHelper::html( 'input', array(
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( 'link' ),
			'id'      => $this->get_field_id( 'link' ),
			'checked' => isset( $instance['link'] ) ? $instance['link'] : true,
		) );

		echo '<p>'.$html.'&nbsp;'.gEditorialHelper::html( 'label', array(
			'for' => $this->get_field_id( 'link' ),
		), _x( 'Link to the issue', 'Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) ).'</p>';

		$html = gEditorialHelper::html( 'input', array(
			'type'    => 'checkbox',
			'name'    => $this->get_field_name( 'number' ),
			'id'      => $this->get_field_id( 'number' ),
			'checked' => isset( $instance['number'] ) ? $instance['number'] : false,
		) );

		echo '<p>'.$html.'&nbsp;'.gEditorialHelper::html( 'label', array(
			'for' => $this->get_field_id( 'number' ),
		), _x( 'Display the number meta', 'Magazine: Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) ).'</p>';

		echo '</div>';
	}
}

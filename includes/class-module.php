<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialModuleCore
{

	var $enabled = false;
	var $meta_key = '_ge';
	var $cookie = 'geditorial';

	var $_post_types_excluded = array();
	var $_taxonomies_excluded = array();
	var $_kses_allowed = array();

	var $_settings_buttons = array();

	public function __construct() { }

	// returns whether the module with the given name is enabled.
	public static function enabled( $slug )
	{
		global $gEditorial;
		return isset( $gEditorial->$slug ) && $gEditorial->$slug->module->options->enabled == 'on';
	}

	// enabled post types for this module
	public function post_types()
	{
		$post_types = array();

		if ( isset( $this->module->options->post_types )
			&& is_array( $this->module->options->post_types ) ) {

				foreach( $this->module->options->post_types as $post_type => $value ) {

					if ( 'off' === $value )
						$value = false;

					if ( in_array( $post_type, $this->_post_types_excluded ) )
						$value = false;

					if ( $value )
						$post_types[] = $post_type;
				}
		}

		return $post_types;
	}

	// applicable post types for this module
	public function all_post_types()
	{
		$registered = get_post_types( array(
			'_builtin' => false,
			'public' => true,
		), 'objects' );

		$post_types = array(
			'post' => __( 'Posts' ),
			'page' => __( 'Pages' ),
		);

		foreach ( $registered as $post_type => $args )
			$post_types[$post_type] = $args->label;

		if ( count( $this->_post_types_excluded ) )
			$post_types = array_diff_key( $post_types, array_flip( $this->_post_types_excluded ) );

		return $post_types;
	}

	// enabled post types for this module
	public function taxonomies()
	{
		$taxonomies = array();

		if ( isset( $this->module->options->taxonomies )
			&& is_array( $this->module->options->taxonomies ) ) {

				foreach( $this->module->options->taxonomies as $taxonomy => $value ) {

					if ( 'off' === $value )
						$value = false;

					if ( in_array( $taxonomy, $this->_taxonomies_excluded ) )
						$value = false;

					if ( $value )
						$taxonomies[] = $taxonomy;
				}
		}

		return $taxonomies;
	}

	public function all_taxonomies()
	{
		$tax_list = get_taxonomies( array(
			// 'show_ui' => true,
		), 'objects' );

		$taxonomies = array();

		foreach ( $tax_list as $tax => $tax_obj )
		$taxonomies[$tax] = $tax_obj->label;

		if ( count( $this->_taxonomies_excluded ) )
			$taxonomies = array_diff_key( $taxonomies, array_flip( $this->_taxonomies_excluded ) );

		return $taxonomies;
	}

	// for supporting late registered custom post types
	public function sanitize_post_types( $module_post_types = array() )
	{
		$normalized = array();

		foreach( $this->all_post_types() as $post_type => $post_type_label ) {
			if ( isset( $module_post_types[$post_type] )
				&& $module_post_types[$post_type]
				&& 'off' !== $module_post_types[$post_type] )
					$normalized[$post_type] = true;
			else
				$normalized[$post_type] = false;
		}

		return $normalized;
	}

	// DEPRECATED
	// Gets an array of allowed post types for a module
	// @return array post-type-slug => post-type-label
	public function get_all_post_types( $module = null )
	{
		if ( gEditorialHelper::is_dev() )
			_deprecated_function( __FUNCTION__, GEDITORIAL_VERSION, 'all_post_types' );

		$allowed = array(
			'post' => __( 'Posts' ),
			'page' => __( 'Pages' ),
		);

		foreach( $this->get_supported_post_types_for_module( $module ) as $post_type => $args ) {
			$allowed[$post_type] = $args->label;
		}
		return $allowed;
	}

	// DEPRECATED
	/**
	 * Cleans up the 'on' and 'off' for post types on a given module (so we don't get warnings all over)
	 * For every post type that doesn't explicitly have the 'on' value, turn it 'off'
	 * If add_post_type_support() has been used anywhere (legacy support), inherit the state
	 */
	public function clean_post_type_options( $module_post_types = array(), $post_type_support = null )
	{
		if ( gEditorialHelper::is_dev() )
			_deprecated_function( __FUNCTION__, GEDITORIAL_VERSION, 'sanitize_post_types' );

		$normalized = array();

		foreach( $this->get_all_post_types() as $post_type => $post_type_label ) {
			if ( isset( $module_post_types[$post_type] )
				&& $module_post_types[$post_type] == 'on' )
					$normalized[$post_type] = 'on';
			else
				$normalized[$post_type] = 'off';
		}
		return $normalized;
	}

	// DEPRECATED
	// get all of the possible post types that can be used with a given module
	public function get_supported_post_types_for_module( $module = null )
	{
		if ( gEditorialHelper::is_dev() )
			_deprecated_function( __FUNCTION__, GEDITORIAL_VERSION, 'post_types' );

		$args = apply_filters( 'geditorial_supported_module_post_types_args', array(
			'_builtin' => false,
			'public' => true,
		), $module );

		$post_types = get_post_types( $args, 'objects' );

		if ( count( $this->_post_types_excluded ) )
			$post_types = array_diff_key( $post_types, array_flip( $this->_post_types_excluded ) );

		return $post_types;
	}

	public function settings_post_types_option( $section )
	{
		foreach( $this->all_post_types() as $post_type => $label ) {
			$html = gEditorialHelper::html( 'input', array(
				'type' => 'checkbox',
				'id' => 'type-'.$post_type,
				'name' => $this->module->options_group_name.'[post_types]['.$post_type.']',
				'checked' => $this->module->options->post_types[$post_type],
			) );

			echo '<p>'.gEditorialHelper::html( 'label', array(
				'for' => 'type-'.$post_type,
			), $html.'&nbsp;'.esc_html( $label ) ).'</p>';
		}
	}

	public function settings_taxonomies_option( $section )
	{
		foreach( $this->all_taxonomies() as $taxonomy => $label ) {
			$html = gEditorialHelper::html( 'input', array(
				'type' => 'checkbox',
				'id' => 'tax-'.$taxonomy,
				'name' => $this->module->options_group_name.'[taxonomies]['.$taxonomy.']',
				'checked' => isset( $this->module->options->taxonomies[$taxonomy] ) && $this->module->options->taxonomies[$taxonomy],
			) );
			echo '<p>'.gEditorialHelper::html( 'label', array(
				'for' => 'tax-'.$taxonomy,
			), $html.'&nbsp;'.esc_html( $label ) ).'</p>';
		}
	}

	// DEPRECATED
	// collect all of the active post types for a given module
	public function get_post_types_for_module( $module )
	{
		$post_types = array();
		if ( isset( $module->options->post_types )
			&& is_array( $module->options->post_types ) ) {

				foreach( $module->options->post_types as $post_type => $value )
					if ( 'on' == $value )
						$post_types[] = $post_type;
		}
		return $post_types;
	}

	// MUST MOVE TO : helper
	// Get the publicly accessible URL for the module based on the filename
	public function get_module_url( $file )
	{
		return trailingslashit( plugins_url( '/', $file ) );
	}

	// DEPRECATED
	public function settings_help()
	{
		$screen = get_current_screen();

		if ( isset( $this->module->settings_help_tab['id'] ) )
			$screen->add_help_tab( $this->module->settings_help_tab );
		else if ( is_array( $this->module->settings_help_tab ) )
			foreach ( $this->module->settings_help_tab as $tab )
				$screen->add_help_tab( $tab );
		else
			return;

		if ( isset( $this->module->settings_help_sidebar ) )
			$screen->set_help_sidebar( $this->module->settings_help_sidebar );
	}

	// get store post meta by the field
	// TODO : sanatize?!
	// USED-ON: Meta, Resource, Series
	public function get_postmeta( $post_id, $field = false, $default = '', $key = null )
	{
		if ( is_null( $key ) )
			$key = $this->meta_key;

		$postmeta = get_metadata( 'post', $post_id, $key, true );

        if ( empty( $postmeta ) )
            return $default;

		if ( false === $field )
			return $postmeta;

		if ( isset( $postmeta[$field] ) )
			return $postmeta[$field];

		return $default;
	}

	// DEPRECATED
	// MINE
	// Moved here form : Meta
	public function get_post_types( $module, $enabled = true )
	{
		if ( gEditorialHelper::is_dev() )
			_deprecated_function( __FUNCTION__, GEDITORIAL_VERSION, 'post_types' );

		$all_post_types = $this->get_all_post_types( $module );

		if ( false === $enabled )
			return $all_post_types;

		$enabled_post_types = array();

		foreach( $this->get_post_types_for_module( $module ) as $post_type )
			if ( isset( $all_post_types[$post_type] ) )
				$enabled_post_types[$post_type] = $all_post_types[$post_type];

		return $enabled_post_types;
	}

	public function register_settings_post_types_option()
	{
		$section = $this->module->options_group_name.'_posttypes';
		add_settings_section( $section, false, '__return_false', $this->module->options_group_name );
		add_settings_field( 'post_types',
			__( 'Enable for these post types:', GEDITORIAL_TEXTDOMAIN ),
			array( $this, 'settings_post_types_option' ),
			$this->module->options_group_name,
			$section
		);
	}

	public function register_settings_taxonomies_option()
	{
		$section = $this->module->options_group_name.'_taxonomies';
		add_settings_section( $section, false, '__return_false', $this->module->options_group_name );
		add_settings_field( 'taxonomies',
			__( 'Enable for these taxonomies:', GEDITORIAL_TEXTDOMAIN ),
			array( $this, 'settings_taxonomies_option' ),
			$this->module->options_group_name,
			$section
		);
	}

	public function register_settings_post_types_fields()
	{
		$all = $this->all_post_types();

		foreach( $this->post_types() as $post_type ) {

			add_settings_section( $post_type.'_fields',
				sprintf( __( 'Fields for %s', GEDITORIAL_TEXTDOMAIN ), $all[$post_type] ),
				'__return_false', // before description
				$this->module->options_group_name
			);

			$all_fields = $this->post_type_all_fields( $post_type );

			if ( count( $all_fields ) ) {
				foreach ( $all_fields as $field )
					add_settings_field( $post_type.'_'.$field,
						'', // $this->get_string( $field, $post_type ),
						array( $this, 'do_post_type_fields_option' ),
						$this->module->options_group_name,
						$post_type.'_fields',
						array(
							// 'label_for' => $post_type.'_'.$field, // NO NEED beacause we use check box and label is better to be on next to it
							'id'        => $post_type.'_'.$field,
							'title'     => $this->get_string( $field, $post_type ),
							'post_type' => $post_type,
							'field'     => $field,
						)
					);
			} else {
				add_settings_field( $post_type.'_nofields',
					sprintf( __( 'No fields supported for %s', GEDITORIAL_TEXTDOMAIN ), $all[$post_type] ),
					'__return_false',
					$this->module->options_group_name,
					$post_type.'_fields'
				);
			}
		}
	}

	public function do_post_type_fields_option( $args )
	{
		//$fields = $this->get_post_type_fields( $this->module, $args['post_type'] );

		echo '<label class="selectit" for="'.esc_attr( $args['id'] ).'">';
		echo '<input id="'.esc_attr( $args['id'] ).'" name="'.$this->module->options_group_name.'['.esc_attr( $args['post_type'] ).'_fields]['.esc_attr( $args['field'] ).']"';

		if ( isset( $this->module->options->{$args['post_type'].'_fields'}[$args['field']] ) )
			checked( $this->module->options->{$args['post_type'].'_fields'}[$args['field']], 'on' );

		echo ' type="checkbox" />&nbsp;'.esc_html( $args['title'] )
			.'<p class="description">';
		echo $this->get_string( $args['field'], $args['post_type'], 'descriptions',
			__( 'No description available.', GEDITORIAL_TEXTDOMAIN ) ).'</p></label>';
	}

	public function print_configure_view()
	{
		$action = add_query_arg( 'page', $this->module->settings_slug, get_admin_url( null, 'admin.php' ) );
		echo '<form class="basic-settings" action="'.$action.'" method="post">';

			settings_fields( $this->module->options_group_name );
			do_settings_sections( $this->module->options_group_name );
			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.esc_attr( $this->module->name ).'" />';

			echo '<p class="submit">';

				foreach ( $this->_settings_buttons as $action => $button ) {
					submit_button( $button['value'], $button['type'], $action, false, $button['atts'] );
					echo '&nbsp;&nbsp;';
				}

			echo '<a class="button" href="'.gEditorialHelper::settingsURL().'">'
				.__( 'Back to Editorial', GEDITORIAL_TEXTDOMAIN ).'</a></p>';

		echo '</form>';

		if ( gEditorialHelper::is_dev() ) {
			//@gEditorialHelper::dump( $this->module->default_options );
			@gEditorialHelper::dump( $this->module->options );
			//@gEditorialHelper::dump( $this->module->strings );
		}
	}

	public function register_settings_button( $key, $value, $atts = array(), $type = 'secondary' )
	{
		$this->_settings_buttons[$key] = array(
			'value' => $value,
			'atts'  => $atts,
			'type'  => $type,
		);
	}

	// Validate our user input as the settings are being saved
	public function settings_validate( $new_options )
	{
		// TODO : all modules must be compatible and then disable chacking the not settings!
		if ( ! isset( $this->module->settings ) || isset( $this->module->settings['post_types_option'] ) ) {
			if ( ! isset( $new_options['post_types'] ) )
				$new_options['post_types'] = array();
			$new_options['post_types'] = $this->sanitize_post_types( $new_options['post_types'] );
		}

		if ( ! isset( $this->module->settings ) || isset( $this->module->settings['post_types_fields'] ) ) {
			foreach( $this->post_types() as $post_type ) {
				foreach ( $this->post_type_all_fields( $post_type ) as $field )
					if ( ! isset( $new_options[$post_type.'_fields'][$field] )
						|| $new_options[$post_type.'_fields'][$field] != 'on' )
							$new_options[$post_type.'_fields'][$field] = 'off';
			}
		}

		return $new_options;
	}

	// get enabled fields for a post type
	public function post_type_fields( $post_type = 'post' )
	{
		$key = $post_type.'_fields';
		$fields = array();

		if ( isset( $this->module->options->{$key} ) && is_array( $this->module->options->{$key} ) ) {
			foreach( $this->module->options->{$key} as $field => $value )
				if ( $value && 'off' !== $value )
					$fields[] = $field;
		}
		return $fields;
	}

	public function post_type_fields_list( $post_type = 'post', $extra = array() )
	{
		$list = array();
		foreach ( $this->post_type_fields( $post_type ) as $field )
			$list[$field] = $this->get_string( $field, $post_type );
		foreach ( $extra as $key => $val )
			$list[$key] = $this->get_string( $val, $post_type );
		return $list;
	}

	// DEPRECATED : use post_type_fields()
	// get enabled fields for a post type
	// Moved here form : Meta
	public function get_post_type_fields( $module, $post_type = 'post', $all = false )
	{
		$key = $post_type.'_fields';
		$fields = array();

		if ( isset( $module->options->{$key} ) && is_array( $module->options->{$key} ) ) {
			foreach( $module->options->{$key} as $field => $value )
				if ( $all )
					$fields[] = $field;
				else if ( $value && 'off' !== $value )
					$fields[] = $field;
		}
		return $fields;
	}

	public function post_type_all_fields( $post_type = 'post' )
	{
		$key = $post_type.'_fields';
		$fields = array();
		if ( isset( $this->module->default_options[$key] ) && is_array( $this->module->default_options[$key] ) )
			foreach( $this->module->default_options[$key] as $field => $value )
				$fields[] = $field;
		return $fields;
	}

	// DEPRECATED : use post_type_all_fields()
	public function get_post_type_supported_fields( $module, $post_type = 'post' )
	{
		$key = $post_type.'_fields';
		$fields = array();
		if ( isset( $module->default_options[$key] ) && is_array( $module->default_options[$key] ) )
			foreach( $module->default_options[$key] as $field => $value )
				$fields[] = $field;
		return $fields;
	}

	public function get_string( $string, $post_type = 'post', $group = 'titles', $fallback = false )
	{
		if( isset( $this->module->strings[$group][$post_type][$string] ) )
			return $this->module->strings[$group][$post_type][$string];

		if( isset( $this->module->strings[$group]['post'][$string] ) )
			return $this->module->strings[$group]['post'][$string];

		if( isset( $this->module->strings[$group][$string] ) )
			return $this->module->strings[$group][$string];

		if ( false === $fallback )
			return $string;

		return $fallback;
	}

	public function do_filters()
	{
		if ( has_filter( 'geditorial_'.$this->module_name.'_strings' ) )
			$this->module->strings = apply_filters( 'geditorial_'.$this->module_name.'_strings', $this->module->strings );

		if ( has_filter( 'geditorial_'.$this->module_name.'_constants' ) )
			$this->module->constants = apply_filters( 'geditorial_'.$this->module_name.'_constants', $this->module->constants );
	}

	// convert the numbers in other language into english
	public function intval( $text, $intval = true )
	{
		$number = apply_filters( 'number_format_i18n_back', $text );
		if ( $intval )
			return intval( $number );
		return $number;
	}

	public function kses( $text, $allowed = array(), $context = 'display' )
	{
		if ( is_null( $allowed ) )
			$allowed = array();
		else if ( ! count( $allowed ) )
			$allowed = $this->_kses_allowed;

		return apply_filters( 'geditorial_kses', wp_kses( $text, $allowed ) );
	}

	public function user_can( $action = 'view', $field = '', $post_type = 'post' )
	{
		global $geditorial_modules_caps;
		if ( empty( $geditorial_modules_caps ) && isset( $geditorial_modules_caps[$this->module_name] ) )
			$geditorial_modules_caps[$this->module_name] = apply_filters( 'geditorial_'.$this->module_name.'_caps', array() );

		if ( isset( $geditorial_modules_caps[$this->module_name][$action][$post_type][$field] ) )
			return current_user_can( $geditorial_modules_caps[$this->module_name][$action][$post_type][$field] );

		return true;
	}

	public function register_settings( $page = null )
	{
		if ( ! isset( $this->module->settings ) )
			return;

		foreach ( $this->module->settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				$section = $this->module->options_group_name.$section_suffix;
				add_settings_section( $section, false, '__return_false', $this->module->options_group_name );
				foreach ( $fields as $field ) {
					$args = array_merge( $field, array( 'section' => $section ) );
					$this->add_settings_field( $args );
				}

			// for pre internal custom options
			} else if ( is_callable( array( $this, 'register_settings_'.$fields ) ) ) {
				call_user_func( array( $this, 'register_settings_'.$fields ) );
			}
		}

		$this->register_settings_button( 'submit', __( 'Save Changes', GEDITORIAL_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		$this->register_settings_button( 'reset-settings', __( 'Reset Settings', GEDITORIAL_TEXTDOMAIN ), sprintf( 'onclick="return confirm( \'%s\' )"', __( 'Are you sure? This operation can not be undone.', GEDITORIAL_TEXTDOMAIN ) ) );

		$screen = get_current_screen();

		if ( isset( $this->module->settings_help_tabs )
			&& count( $this->module->settings_help_tabs ) ) {
				foreach ( $this->module->settings_help_tabs as $tab )
					$screen->add_help_tab( $tab );
		}

		if ( isset( $this->module->settings_help_sidebar ) )
			$screen->set_help_sidebar( $this->module->settings_help_sidebar );
	}

	public function add_settings_field( $r )
	{
		$args = array_merge( array(
			'page' => $this->module->options_group_name,
			'section' => $this->module->options_group_name.'_general',
			'field' => false,
			//'label_for' => '',
			'title' => '',
			'description' => '',
			'callback' => array( $this, 'do_settings_field' ),
		), $r );

		if ( ! $args['field'] )
			return;

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		add_settings_field( $args['field'], $args['title'], $args['callback'], $args['page'], $args['section'], $args );
	}

	public function do_settings_field( $r )
	{
		$args = shortcode_atts( array(
			'type' => 'enabled',
			'field' => false,
			'values' => array(),
			'filter' => false, // will use via sanitize
			'dir' => false,
			'default' => '',
			'description' => '',
			'class' => 'geditorial-settings-field',

			'name_group' => 'settings',
		), $r );

		if ( ! $args['field'] )
			return;

		$id = esc_attr( $this->module->options_group_name.'-'.$args['field'] );
		$name = $this->module->options_group_name.'['.esc_attr( $args['name_group'] ).']['.esc_attr( $args['field'] ).']';

		if ( isset( $this->module->options->settings[$args['field']] ) )
			$option = $this->module->options->settings[$args['field']];
		else if ( ! empty( $args['default'] ) )
			$option = $args['default'];
		else if ( isset( $this->module->default_options['settings'][$args['field']] ) )
			$option = $this->module->default_options['settings'][$args['field']];
		else
			$option = null;

		switch ( $args['type'] ) {
			case 'enabled' :
				?><select name="<?php echo $name; ?>" id="<?php echo $id; ?>" class="<?php echo $args['class']; ?>" >
					<option value="0" <?php selected( $option, 0 ); ?>><?php esc_html_e( 'Disabled', GEDITORIAL_TEXTDOMAIN ); ?></option>
					<option value="1" <?php selected( $option, 1 ); ?>><?php esc_html_e( 'Enabled', GEDITORIAL_TEXTDOMAIN ); ?></option>
				</select> <?php

				if ( $args['description'] )
					echo '<p class="description">'.esc_html( $args['description'] ).'</p>';

			break;

			case 'text' :
				?><input type="text" class="regular-text <?php echo $args['class']; ?>"
					name="<?php echo $name; ?>" id="<?php echo $id; ?>"
					value="<?php echo esc_attr( $option ); ?>"
					<?php if ( $args['dir'] ) echo 'dir="'.$args['dir'].'"'; ?> /> <?php
				if ( $args['description'] )
					echo '<p class="description">'.esc_html( $args['description'] ).'</p>';

			break;

			case 'checkbox' :
				if ( count( $args['values'] ) ) {
					foreach( $args['values'] as $value_name => $value_title ) {
						?><input type="checkbox" name="<?php echo $name.'['.esc_attr( $value_name ).']'; ?>" id="<?php echo $id.'-'.esc_attr( $value_name ); ?>" value="1"  class="<?php echo $args['class']; ?>" <?php
						checked( true, in_array( $value_name, ( array ) $option ) );?> /><?php
						?>&nbsp;<span><?php echo esc_html( $value_title ); ?></span><br /> <?php
					}
				} else {
					?><input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="1"  class="<?php echo $args['class']; ?>" <?php checked( 1, $option );?> /><?php
				}
				if ( $args['description'] )
					echo ' <span class="description" style="vertical-align:base;">'.esc_html( $args['description'] ).'</span>';

			break;

			case 'select' :
				// alow hiding
				if ( false !== $args['values'] ) {
					?><select name="<?php echo $name; ?>" id="<?php echo $id; ?>" class="<?php echo $args['class']; ?>"><?php
						foreach ( $args['values'] as $value_name => $value_title ) {
							?><option value="<?php echo esc_attr( $value_name ); ?>" <?php selected( $value_name, $option );?>><?php echo esc_html( $value_title ); ?></option><?php
						}
					?></select> <?php
					if ( $args['description'] )
						echo '<p class="description">'.$args['description'].'</p>';
				}
			break;


			default :
				_e( 'Error: setting type undefined.', GEDITORIAL_TEXTDOMAIN );
				if ( $args['description'] )
					echo '<p class="description">'.esc_html( $args['description'] ).'</p>';

		}
	}

	public function get_setting( $field, $default = null )
	{
		if ( isset( $this->module->options->settings[$field] ) )
			return $this->module->options->settings[$field];
		else if ( isset( $this->module->default_options['settings'][$field] ) )
			return $this->module->default_options['settings'][$field];
		else
			return $default;
	}

	public function set_cookie( $array, $append = true, $expire = '+ 365 day' )
	{
		if ( $append ) {
			$old = isset( $_COOKIE[$this->cookie] ) ? json_decode( wp_unslash( $_COOKIE[$this->cookie] ) ) : array();
			$new = wp_json_encode( gEditorialHelper::parse_args_r( $array, $old ) );
		} else {
			$new = wp_json_encode( $array );
		}

		setcookie( $this->cookie, $new, strtotime( $expire ), COOKIEPATH, COOKIE_DOMAIN, false );
	}

	public function get_cookie()
	{
		return isset( $_COOKIE[$this->cookie] ) ? json_decode( wp_unslash( $_COOKIE[$this->cookie] ), true ) : array();
	}

	// MAYBE: add general options for gEditorial
	public static function getEditorialUserID( $fallback = true )
	{
		if ( defined( 'GNETWORK_SITE_USER_ID' ) && constant( 'GNETWORK_SITE_USER_ID' ) )
			return GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' ) ) {
			$gtheme_user = gtheme_get_option( 'default_user', 0 );
			if ( $gtheme_user )
				return $gtheme_user;
		}

		if ( $fallback )
			return get_current_user_id();

		return 0;
	}

	// will extended by module
	public function register_post_types() {}
	public function register_taxonomies() {}

	// this must be wp core future!!
	// call this late on after_setup_theme
	public static function themeThumbnails( $post_types )
	{
		global $_wp_theme_features;
		$feature = 'post-thumbnails';
		//$post_types = (array) $post_types;

		if ( isset( $_wp_theme_features[$feature] )
			&& true !== $_wp_theme_features[$feature]
			&& is_array( $_wp_theme_features[$feature][0] ) ) {
				$_wp_theme_features[$feature][0] = array_merge( $_wp_theme_features[$feature][0], $post_types );
		} else {
			$_wp_theme_features[$feature] = array( $post_types );
		}

	}

	// FRONT ONLY: cause will called from 'wp_footer'
	// WARNING: every asset must have a .min copy
	public function enqueue_asset_js( $args = array(), $name = null, $deps = array( 'jquery' ), $handle = null )
    {
		global $gEditorial;

		if ( is_null( $name ) )
			$name = $this->module_name;

		$prefix = is_admin() ? 'admin' : 'front';
		$suffix = ( ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || gEditorialHelper::isDev() ) ? '' : '.min' );

		wp_enqueue_script(
			( $handle ? $handle : 'geditorial-'.$name ),
			GEDITORIAL_URL.'assets/js/geditorial/'.$prefix.'.'.$name.$suffix.'.js',
			$deps,
			GEDITORIAL_VERSION );

		$gEditorial->enqueue_asset_config( $args, $this->module_name );
    }

	// FRONT ONLY: combined global styles
	// TODO: also we need api for module specified css
	public function enqueue_styles()
	{
		global $gEditorial;
		$gEditorial->enqueue_styles();
	}
}

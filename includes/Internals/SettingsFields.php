<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait SettingsFields
{

	public function register_settings_fields_option( $section_title = NULL )
	{
		if ( is_null( $section_title ) )
			/* translators: %s: posttype */
			$section_title = _x( 'Fields for %s', 'Module', 'geditorial-admin' );

		$all = $this->all_posttypes();

		foreach ( $this->posttypes() as $posttype ) {

			$fields  = $this->posttype_fields_all( $posttype );
			$section = $posttype.'_fields';

			if ( count( $fields ) ) {

				Settings::addModuleSection( $this->hook_base( $this->module->name ), [
					'id'            => $section,
					'title'         => sprintf( $section_title, $all[$posttype] ),
					'section_class' => 'fields_option_section fields_option-'.$posttype,
				] );

				$this->add_settings_field( [
					'field'     => $posttype.'_fields_all',
					'post_type' => $posttype,
					'section'   => $section,
					'title'     => '&nbsp;',
					'callback'  => [ $this, 'settings_fields_option_all' ],
				] );

				foreach ( $fields as $field => $atts ) {

					$field_title = isset( $atts['title'] ) ? $atts['title'] : $this->get_string( $field, $posttype );

					$args = [
						'field'       => $field,
						'post_type'   => $posttype,
						'section'     => $section,
						'field_title' => sprintf( '%s &mdash; <code>%s</code>', $field_title, $field ),
						'description' => isset( $atts['description'] ) ? $atts['description'] : $this->get_string( $field, $posttype, 'descriptions' ),
						'callback'    => [ $this, 'settings_fields_option' ],
					];

					if ( is_array( $atts ) )
						$args = array_merge( $args, $atts );

					if ( $args['field_title'] == $args['description'] )
						unset( $args['description'] );

					$args['title'] = '&nbsp;';

					$this->add_settings_field( $args );
				}

			} else if ( isset( $all[$posttype] ) ) {

				Settings::addModuleSection( $this->hook_base( $this->module->name ), [
					'id'            => $section,
					'title'         => sprintf( $section_title, $all[$posttype] ),
					'callback'      => [ $this, 'settings_fields_option_none' ],
					'section_class' => 'fields_option_section fields_option_none',
				] );
			}
		}
	}

	// helps with renamed fields
	private function get_settings_fields_option_val( $args )
	{
		$fields = array_reverse( $this->sanitize_postmeta_field_key( $args['field'] ) );

		foreach ( $fields as $field_key )
			if ( isset( $this->options->fields[$args['post_type']][$field_key] ) )
				return $this->options->fields[$args['post_type']][$field_key];

		if ( isset( $this->options->fields[$args['post_type']][$args['field']] ) )
			return $this->options->fields[$args['post_type']][$args['field']];

		if ( ! empty( $args['default'] ) )
			return $args['default'];

		return FALSE;
	}

	public function settings_fields_option( $args )
	{
		$name  = $this->hook_base( $this->module->name ).'[fields]['.$args['post_type'].']['.$args['field'].']';
		$id    = $this->hook_base( $this->module->name ).'-fields-'.$args['post_type'].'-'.$args['field'];
		$value = $this->get_settings_fields_option_val( $args );

		$html = Core\HTML::tag( 'input', [
			'type'    => 'checkbox',
			'value'   => 'enabled',
			'class'   => 'fields-check',
			'name'    => $name,
			'id'      => $id,
			'checked' => $value,
		] );

		echo '<div>';
			Core\HTML::label( $html.'&nbsp;'.$args['field_title'], $id, FALSE );
			Core\HTML::desc( $args['description'] );
		echo '</div>';
	}

	public function settings_fields_option_all( $args )
	{
		$html = Core\HTML::tag( 'input', [
			'type'  => 'checkbox',
			'class' => 'fields-check-all',
			'id'    => $args['post_type'].'_fields_all',
		] );

		$html.= '&nbsp;<span class="description">'._x( 'Select All Fields', 'Module', 'geditorial-admin' ).'</span>';

		Core\HTML::label( $html, $args['post_type'].'_fields_all', FALSE );
	}

	public function settings_fields_option_none( $args )
	{
		Settings::moduleSectionEmpty( _x( 'No fields supported', 'Module', 'geditorial-admin' ) );
	}

	public function posttype_fields_all( $posttype = 'post', $module = NULL )
	{
		return WordPress\PostType::supports( $posttype, ( is_null( $module ) ? $this->module->name : $module ).'_fields' );
	}

	public function posttype_fields_list( $posttype = 'post', $extra = [] )
	{
		$list = [];

		foreach ( $this->posttype_fields_all( $posttype ) as $field => $args )
			$list[$field] = array_key_exists( 'title', $args )
				? $args['title']
				: $this->get_string( $field, $posttype, 'titles', $field );

		foreach ( $extra as $key => $val )
			$list[$key] = $this->get_string( $val, $posttype );

		return $list;
	}

	// enabled fields for a posttype
	public function posttype_fields( $posttype = 'post', $js = FALSE )
	{
		$fields = [];

		if ( isset( $this->options->fields[$posttype] )
			&& is_array( $this->options->fields[$posttype] ) ) {

			foreach ( $this->options->fields[$posttype] as $field_key => $enabled ) {

				$sanitized = $this->sanitize_postmeta_field_key( $field_key )[0];

				if ( $js )
					$fields[$sanitized] = (bool) $enabled;

				else if ( $enabled )
					$fields[] = $sanitized;
			}
		}

		return $fields;
	}

	// for fields only in connection to the caller module
	public function add_posttype_fields_supported( $posttypes = NULL, $fields = NULL, $append = TRUE, $type = 'meta' )
	{
		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( is_null( $fields ) ) {

			if ( $type && isset( $this->fields[$type]['_supported'] ) )
				$fields = $this->fields[$type]['_supported'];

			else if ( array_key_exists( '_supported', $this->fields ) )
				$fields = $this->fields['_supported'];

			else
				$fields = [];
		}

		if ( empty( $fields ) )
			return;

		foreach ( $posttypes as $posttype )
			$this->add_posttype_fields( $posttype, $fields, $append, $type );
	}

	public function add_posttype_fields( $posttype, $fields = NULL, $append = TRUE, $type = 'meta' )
	{
		if ( is_null( $fields ) ) {

			if ( $type && isset( $this->fields[$type][$posttype] ) )
				$fields = $this->fields[$type][$posttype];

			else if ( array_key_exists( $posttype, $this->fields ) )
				$fields = $this->fields[$posttype];

			else
				$fields = [];
		}

		if ( empty( $fields ) )
			return;

		if ( $append )
			$fields = array_merge( WordPress\PostType::supports( $posttype, $type.'_fields' ), $fields );

		add_post_type_support( $posttype, [ $type.'_fields' ], $fields );
		add_post_type_support( $posttype, 'custom-fields' ); // must for rest meta fields
	}

	public function has_posttype_fields_support( $constant, $type = 'meta' )
	{
		return post_type_supports( $this->constant( $constant, $constant ), $type.'_fields' );
	}
}

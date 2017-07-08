<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Module as Base;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;

class Module extends Base
{

	public $module;
	public $options;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

	protected $cookie     = 'geditorial';
	protected $field_type = 'meta';
	protected $icon_group = 'genericons-neue';

	protected $priority_init          = 10;
	protected $priority_init_ajax     = 10;
	protected $priority_adminbar_init = 10;

	protected $constants = [];
	protected $strings   = [];
	protected $supports  = [];
	protected $fields    = [];

	protected $partials        = [];
	protected $partials_remote = [];

	protected $post_types_excluded = [ 'attachment' ];
	protected $taxonomies_excluded = [];

	protected $image_sizes  = [];
	protected $kses_allowed = [];

	protected $scripts = [];
	protected $buttons = [];
	protected $errors  = [];

	protected $caps = [
		'default'  => 'manage_options',
		'settings' => 'manage_options',
		'reports'  => 'edit_others_posts',
		'tools'    => 'edit_others_posts',
		'adminbar' => 'edit_others_posts',
	];

	protected $root_key = FALSE; // ROOT CONSTANT
	protected $p2p      = FALSE; // P2P ENABLED/Connection Type
	protected $o2o      = FALSE; // O2O ENABLED/Connection Type

	protected $scripts_printed = FALSE;

	public function __construct( &$module, &$options )
	{
		$this->base = 'geditorial';
		$this->key  = $module->name;

		$this->module  = $module;
		$this->options = $options;

		if ( $this->remote() )
			$this->setup_remote();

		else
			$this->setup();
	}

	protected function setup_remote( $args = [] )
	{
		$this->require_code( $this->partials_remote );
	}

	protected function setup( $args = [] )
	{
		$this->require_code( $this->partials );

		$ajax = WordPress::isAJAX();
		$ui   = WordPress::mustRegisterUI( FALSE );

		if ( method_exists( $this, 'p2p_init' ) )
			$this->action( 'p2p_init' );

		if ( method_exists( $this, 'o2o_init' ) )
			$this->action( 'o2o_init' );

		if ( method_exists( $this, 'widgets_init' ) )
			$this->action( 'widgets_init' );

		if ( ! $ajax && method_exists( $this, 'tinymce_strings' ) )
			add_filter( 'geditorial_tinymce_strings', [ $this, 'tinymce_strings' ] );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( 'geditorial_meta_init', [ $this, 'meta_init' ] );

		add_action( 'after_setup_theme', [ $this, '_after_setup_theme' ], 1 );

		if ( method_exists( $this, 'after_setup_theme' ) )
			$this->action( 'after_setup_theme', 0, 20 );

		$this->action( 'init', 0, $this->priority_init );

		if ( $ui && method_exists( $this, 'adminbar_init' ) && $this->get_setting( 'adminbar_summary' ) )
			add_action( 'geditorial_adminbar', [ $this, 'adminbar_init' ], $this->priority_adminbar_init, 2 );

		if ( is_admin() ) {

			if ( method_exists( $this, 'admin_init' ) )
				$this->action( 'admin_init' );

			if ( $ajax && method_exists( $this, 'init_ajax' ) )
				$this->action( 'init', 0, $this->priority_init_ajax, 'ajax' );

			if ( $ui && method_exists( $this, 'dashboard_glance_items' ) )
				$this->filter( 'dashboard_glance_items' );

			if ( ( $ui || $ajax ) && method_exists( $this, 'register_shortcode_ui' ) )
				$this->action( 'register_shortcode_ui' );

			if ( $ui && method_exists( $this, 'current_screen' ) )
				$this->action( 'current_screen' );

			if ( $ui )
				add_action( 'geditorial_settings_load', [ $this, 'register_settings' ] );

			if ( $ui && method_exists( $this, 'reports_settings' ) )
				add_action( 'geditorial_reports_settings', [ $this, 'reports_settings' ] );

			if ( $ui && method_exists( $this, 'tools_settings' ) )
				add_action( 'geditorial_tools_settings', [ $this, 'tools_settings' ] );
		}
	}

	public function _after_setup_theme()
	{
		$this->constants = $this->filters( 'constants', $this->get_global_constants(), $this->module );
		$this->supports  = $this->filters( 'supports', $this->get_global_supports(), $this->module ); // FIXME: DEPRICATED
		$this->fields    = $this->filters( 'fields', $this->get_global_fields(), $this->module );
	}

	// MUST ALWAYS CALL THIS
	public function init()
	{
		$this->actions( 'init', $this->module );

		$this->strings = $this->filters( 'strings', $this->get_global_strings(), $this->module );

		foreach ( $this->get_module_templates() as $constant => $templates ) {

			if ( ! count( $templates ) )
				continue;

			$list = [];
			$type = $this->constant( $constant );

			foreach ( $templates as $slug => $title )
				$list[$this->key.'-'.$slug.'.php'] = $title;

			add_filter( 'theme_'.$type.'_templates',
				function( $filtered ) use( $list ) {
					return array_merge( $filtered, $list );
				}
			);
		}
	}

	protected function get_global_settings() { return []; }
	protected function get_global_constants() { return []; }
	protected function get_global_strings() { return []; }
	protected function get_global_supports() { return []; } // FIXME: DEPRICATED
	protected function get_global_fields() { return []; }

	protected function get_module_templates() { return []; }
	protected function get_module_icons() { return []; }

	protected function settings_help_tabs()
	{
		return Settings::settingsHelpContent( $this->module );
	}

	protected function settings_help_sidebar()
	{
		return Settings::settingsHelpLinks( $this->module );
	}

	// check if this module loaded as remote for another blog's editorial module
	public function remote()
	{
		if ( ! $this->root_key )
			return FALSE;

		if ( ! defined( $this->root_key ) )
			return FALSE;

		if ( constant( $this->root_key ) == get_current_blog_id() )
			return FALSE;

		return TRUE;
	}

	// enabled post types for this module
	public function post_types( $post_types = NULL )
	{
		if ( is_null( $post_types ) )
			$post_types = [];

		else if ( ! is_array( $post_types ) )
			$post_types = [ $this->constant( $post_types ) ];

		if ( isset( $this->options->post_types )
			&& is_array( $this->options->post_types ) ) {

				foreach ( $this->options->post_types as $post_type => $value ) {

					if ( 'off' === $value )
						$value = FALSE;

					if ( in_array( $post_type, $this->post_types_excluded ) )
						$value = FALSE;

					if ( $value )
						$post_types[] = $post_type;
				}
		}

		return $post_types;
	}

	public function list_post_types( $pre = NULL, $post_types = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All PostTypes', 'Module', GEDITORIAL_TEXTDOMAIN ) ];

		$all = PostType::get();

		foreach ( $this->post_types( $post_types ) as $post_type )
			$pre[$post_type] = empty( $all[$post_type] ) ? $post_type : $all[$post_type];

		return $pre;
	}

	public function all_post_types( $exclude = TRUE )
	{
		$post_types = PostType::get();

		if ( $exclude && count( $this->post_types_excluded ) )
			$post_types = array_diff_key( $post_types, array_flip( $this->post_types_excluded ) );

		return $post_types;
	}

	// enabled post types for this module
	public function taxonomies()
	{
		$taxonomies = [];

		if ( isset( $this->options->taxonomies )
			&& is_array( $this->options->taxonomies ) ) {

				foreach ( $this->options->taxonomies as $taxonomy => $value ) {

					if ( 'off' === $value )
						$value = FALSE;

					if ( in_array( $taxonomy, $this->taxonomies_excluded ) )
						$value = FALSE;

					if ( $value )
						$taxonomies[] = $taxonomy;
				}
		}

		return $taxonomies;
	}

	public function all_taxonomies()
	{
		$taxonomies = Taxonomy::get();

		if ( count( $this->taxonomies_excluded ) )
			$taxonomies = array_diff_key( $taxonomies, array_flip( $this->taxonomies_excluded ) );

		return $taxonomies;
	}

	public function settings_posttypes_option( $section )
	{
		if ( $before = $this->get_string( 'post_types_before', 'post', 'settings', NULL ) )
			HTML::desc( $before );

		foreach ( $this->all_post_types() as $post_type => $label ) {
			$html = HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$post_type,
				'name'    => $this->module->group.'[post_types]['.$post_type.']',
				'checked' => isset( $this->options->post_types[$post_type] ) && $this->options->post_types[$post_type],
			] );

			echo '<p>'.HTML::tag( 'label', [
				'for' => 'type-'.$post_type,
			], $html.'&nbsp;'.esc_html( $label ).' &mdash; <code>'.$post_type.'</code>' ).'</p>';
		}

		if ( $after = $this->get_string( 'post_types_after', 'post', 'settings', NULL ) )
			HTML::desc( $after );
	}

	public function settings_taxonomies_option( $section )
	{
		if ( $before = $this->get_string( 'taxonomies_before', 'post', 'settings', NULL ) )
			HTML::desc( $before );

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->module->group.'[taxonomies]['.$taxonomy.']',
				'checked' => isset( $this->options->taxonomies[$taxonomy] ) && $this->options->taxonomies[$taxonomy],
			] );

			echo '<p>'.HTML::tag( 'label', [
				'for' => 'tax-'.$taxonomy,
			], $html.'&nbsp;'.esc_html( $label ).' &mdash; <code>'.$taxonomy.'</code>' ).'</p>';
		}

		if ( $after = $this->get_string( 'taxonomies_after', 'post', 'settings', NULL ) )
			HTML::desc( $after );
	}

	protected function settings_supports_option( $constant, $defaults = NULL, $excludes = NULL )
	{
		$supports = $this->filters( $constant.'_supports', Settings::supportsOptions() );

		// has custom fields
		if ( isset( $this->fields[$this->constant( $constant )] ) )
			unset( $supports['editorial-meta'] );

		if ( ! is_null( $excludes ) )
			$supports = array_diff_key( $supports, array_flip( (array) $excludes ) );

		if ( is_null( $defaults ) )
			$defaults = $this->supports[$constant];

		else if ( TRUE === $defaults )
			$defaults = array_keys( $supports );

		$singular = translate_nooped_plural( $this->strings['noops'][$constant], 1 );

		return [
			'field'       => $constant.'_supports',
			'type'        => 'checkbox', // FIXME: add as setting type with `code` after title
			'title'       => sprintf( _x( '%s Supports', 'Module: Setting Title', GEDITORIAL_TEXTDOMAIN ), $singular ),
			'description' => sprintf( _x( 'Support core and extra features for %s posttype.', 'Module: Setting Description', GEDITORIAL_TEXTDOMAIN ), $singular ),
			'default'     => $defaults,
			'values'      => $supports,
		];
	}

	protected function settings_insert_priority_option( $default = 10, $prefix = FALSE )
	{
		return [
			'field'   => 'insert_priority'.( $prefix ? '_'.$prefix : '' ),
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => $default,
		];
	}

	// get stored post meta by the field
	public function get_postmeta( $post_id, $field = FALSE, $default = '', $key = NULL )
	{
		global $gEditorialPostMeta;

		if ( is_null( $key ) )
			$key = $this->meta_key;

		if ( ! isset( $gEditorialPostMeta[$post_id][$key] ) )
			$gEditorialPostMeta[$post_id][$key] = get_metadata( 'post', $post_id, $key, TRUE );

		if ( empty( $gEditorialPostMeta[$post_id][$key] ) )
			return $default;

		if ( FALSE === $field )
			return $gEditorialPostMeta[$post_id][$key];

		foreach ( $this->sanitize_meta_field( $field ) as $field_key )
			if ( isset( $gEditorialPostMeta[$post_id][$key][$field_key] ) )
				return $gEditorialPostMeta[$post_id][$key][$field_key];

		return $default;
	}

	public function sanitize_meta_field( $field )
	{
		return (array) $field;
	}

	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		global $gEditorialPostMeta;

		if ( $postmeta && count( $postmeta ) )
			update_post_meta( $post_id, $this->meta_key.$key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $this->meta_key.$key_suffix );

		unset( $gEditorialPostMeta[$post_id][$this->meta_key.$key_suffix] );
	}

	public function register_settings_posttypes_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'post_types_title', 'post', 'settings',
				_x( 'Enable for Post Types', 'Module', GEDITORIAL_TEXTDOMAIN ) );

		$section = $this->module->group.'_posttypes';

		Settings::addModuleSection( $this->module->group, [
			'id'            => $section,
			'section_class' => 'posttypes_option_section',
		] );

		add_settings_field( 'post_types',
			$title,
			[ $this, 'settings_posttypes_option' ],
			$this->module->group,
			$section
		);
	}

	public function register_settings_taxonomies_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'taxonomies_title', 'post', 'settings',
				_x( 'Enable for Taxonomies', 'Module', GEDITORIAL_TEXTDOMAIN ) );

		$section = $this->module->group.'_taxonomies';

		Settings::addModuleSection( $this->module->group, [
			'id'            => $section,
			'section_class' => 'taxonomies_option_section',
		] );

		add_settings_field( 'taxonomies',
			$title,
			[ $this, 'settings_taxonomies_option' ],
			$this->module->group,
			$section
		);
	}

	public function register_settings_fields_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = _x( 'Fields for %s', 'Module', GEDITORIAL_TEXTDOMAIN );

		$all = $this->all_post_types();

		foreach ( $this->post_types() as $post_type ) {

			$fields  = $this->post_type_all_fields( $post_type );
			$section = $post_type.'_fields';

			if ( count( $fields ) ) {

				Settings::addModuleSection( $this->module->group, [
					'id'            => $section,
					'title'         => sprintf( $title, $all[$post_type] ),
					'section_class' => 'fields_option_section fields_option-'.$post_type,
				] );

				$this->add_settings_field( [
					'field'     => $post_type.'_fields_all',
					'post_type' => $post_type,
					'section'   => $section,
					'title'     => '&nbsp;',
					'callback'  => [ $this, 'settings_fields_option_all' ],
				] );

				foreach ( $fields as $field => $atts ) {

					$args = [
						'field'       => $field,
						'post_type'   => $post_type,
						'section'     => $section,
						'field_title' => isset( $atts['title'] ) ? $atts['title'] : $this->get_string( $field, $post_type ),
						'description' => isset( $atts['description'] ) ? $atts['description'] : $this->get_string( $field, $post_type, 'descriptions' ),
						'callback'    => [ $this, 'settings_fields_option' ],
					];

					if ( is_array( $atts ) )
						$args = array_merge( $args, $atts );

					$args['title'] = '&nbsp;';

					$this->add_settings_field( $args );
				}

			} else if ( isset( $all[$post_type] ) ) {

				Settings::addModuleSection( $this->module->group, [
					'id'            => $section,
					'title'         => sprintf( $title, $all[$post_type] ),
					'callback'      => [ $this, 'settings_fields_option_none' ],
					'section_class' => 'fields_option_section fields_option_none',
				] );
			}
		}
	}

	public function settings_fields_option( $args )
	{
		$name = $this->module->group.'[fields]['.$args['post_type'].']['.$args['field'].']';
		$id   = $this->module->group.'-fields-'.$args['post_type'].'-'.$args['field'];

		if ( isset( $this->options->fields[$args['post_type']][$args['field']] ) )
			$value = $this->options->fields[$args['post_type']][$args['field']];

		else if ( ! empty( $args['default'] ) )
			$value = $args['default'];

		else
			$value = FALSE;

		$html = HTML::tag( 'input', [
			'type'    => 'checkbox',
			'value'   => 'enabled',
			'class'   => 'fields-check',
			'name'    => $name,
			'id'      => $id,
			'checked' => $value,
		] );

		echo '<div>'.HTML::tag( 'label', [
			'for' => $id,
		], $html.'&nbsp;'.$args['field_title'] );

		HTML::desc( $args['description'] );

		echo '</div>';
	}

	public function settings_fields_option_all( $args )
	{
		$html = HTML::tag( 'input', [
			'type'  => 'checkbox',
			'class' => 'fields-check-all',
			'id'    => $args['post_type'].'_fields_all',
		] );

		echo HTML::tag( 'label', [
			'for' => $args['post_type'].'_fields_all',
		], $html.'&nbsp;<span class="description">'._x( 'Select All Fields', 'Module', GEDITORIAL_TEXTDOMAIN ).'</span>' );
	}

	public function settings_fields_option_none( $args )
	{
		Settings::moduleSectionEmpty( _x( 'No fields supported', 'Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function print_configure_view()
	{
		echo '<form action="'.$this->get_url_settings().'" method="post">';

			// FIXME: USE: `$this->settings_fields()`
			settings_fields( $this->module->group );

			Settings::moduleSections( $this->module->group );

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.esc_attr( $this->module->name ).'" />';

			$this->settings_buttons();

		echo '</form>';

		if ( WordPress::isDev() )
			self::dump( $this->options );
	}

	public function default_buttons( $page = NULL )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );

		if ( method_exists( $this, 'reports_settings' ) )
			foreach ( $this->append_sub( [], 'reports' ) as $sub => $title )
				$this->register_button( Settings::subURL( $sub, 'reports' ),
					sprintf( _x( 'Jump to Reports: %s', 'Module: Button', GEDITORIAL_TEXTDOMAIN ), $title ), 'link' );

		if ( method_exists( $this, 'tools_settings' ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$this->register_button( Settings::subURL( $sub, 'tools' ),
					sprintf( _x( 'Jump to Tools: %s', 'Module: Button', GEDITORIAL_TEXTDOMAIN ), $title ), 'link' );
	}

	public function register_button( $key, $value = NULL, $type = FALSE, $atts = [] )
	{
		if ( is_null( $value ) )
			$value = $this->get_string( $key, 'buttons', 'settings', NULL );

		$this->buttons[] = [
			'key'   => $key,
			'value' => $value,
			'type'  => $type,
			'atts'  => $atts,
		];
	}

	protected function settings_buttons( $page = NULL, $wrap = '' )
	{
		if ( FALSE !== $wrap )
			echo '<p class="submit '.$this->base.'-wrap-buttons '.$wrap.'">';

		foreach ( $this->buttons as $button )
			Settings::submitButton( $button['key'], $button['value'], $button['type'], $button['atts'] );

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function submit_button( $name = '', $primary = FALSE, $text = NULL, $atts = [] )
	{
		if ( $name && is_null( $text ) )
			$text = $this->get_string( $name, 'buttons', 'settings' );

		Settings::submitButton( $name, $text, $primary, $atts );
	}

	protected function settings_form_before( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		if ( is_null( $sub ) )
			$sub = $this->module->name;

		$class = $this->base.'-form -'.$this->module->name.' -sub-'.$sub;

		if ( $check && $sidebox = method_exists( $this, 'settings_sidebox' ) )
			$class .= ' has-sidebox';

		echo '<form class="'.$class.'" method="post" action="">';

			$this->settings_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="settings-sidebox -'.$this->module->name.' settings-sidebox-'.$sub.'">';
					$this->settings_sidebox( $sub, $uri, $context );
				echo '</div>';
			}
	}

	protected function settings_form_after( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		echo '</form>';
	}

	protected function settings_form_req( $defaults, $context = 'settings' )
	{
		$req = empty( $_REQUEST[$this->module->group][$context] )
			? []
			: $_REQUEST[$this->module->group][$context];

		return self::atts( $defaults, $req );
	}

	protected function settings_fields( $sub, $action = 'update', $context = 'settings' )
	{
		HTML::inputHidden( 'base', $this->base );
		HTML::inputHidden( 'key', $this->key );
		HTML::inputHidden( 'context', $context );
		HTML::inputHidden( 'sub', $sub );
		HTML::inputHidden( 'action', $action );

		$this->nonce_field( $context, $sub );
	}

	// DEFAULT METHOD
	public function append_sub( $subs, $page = 'settings' )
	{
		if ( ! $this->cuc( $page ) )
			return $subs;

		return array_merge( $subs, [ $this->module->name => $this->module->title ] );
	}

	// FIXME: DEPRICATED
	// USE: `$this->settings_fields()`
	protected function settings_field_referer( $sub = NULL, $page = 'settings' )
	{
		$this->nonce_field( $page, $sub );
	}

	protected function settings_check_referer( $sub = NULL, $page = 'settings' )
	{
		$this->nonce_check( $page, $sub );
	}

	public function settings_validate( $options )
	{
		$this->init_settings();

		if ( isset( $this->settings['posttypes_option'] ) ) {

			if ( ! isset( $options['post_types'] ) )
				$options['post_types'] = [];

			foreach ( $this->all_post_types() as $post_type => $post_type_label )
				if ( ! isset( $options['post_types'][$post_type] )
					|| $options['post_types'][$post_type] != 'enabled' )
						unset( $options['post_types'][$post_type] );
				else
					$options['post_types'][$post_type] = TRUE;

			if ( ! count( $options['post_types'] ) )
				unset( $options['post_types'] );
		}

		if ( isset( $this->settings['taxonomies_option'] ) ) {

			if ( ! isset( $options['taxonomies'] ) )
				$options['taxonomies'] = [];

			foreach ( $this->all_taxonomies() as $taxonomy => $label )
				if ( ! isset( $options['taxonomies'][$taxonomy] )
					|| $options['taxonomies'][$taxonomy] != 'enabled' )
						unset( $options['taxonomies'][$taxonomy] );
				else
					$options['taxonomies'][$taxonomy] = TRUE;

			if ( ! count( $options['taxonomies'] ) )
				unset( $options['taxonomies'] );
		}

		if ( isset( $this->settings['fields_option'] ) ) {

			if ( ! isset( $options['fields'] ) )
				$options['fields'] = [];

			foreach ( $this->post_types() as $post_type ) {

				if ( ! isset( $options['fields'][$post_type] ) )
					$options['fields'][$post_type] = [];

				foreach ( $this->post_type_all_fields( $post_type ) as $field => $args ) {

					if ( ! isset( $options['fields'][$post_type][$field] )
						|| $options['fields'][$post_type][$field] != 'enabled' )
							unset( $options['fields'][$post_type][$field] );
					else
						$options['fields'][$post_type][$field] = TRUE;
				}

				if ( ! count( $options['fields'][$post_type] ) )
					unset( $options['fields'][$post_type] );
			}

			if ( ! count( $options['fields'] ) )
				unset( $options['fields'] );
		}

		if ( isset( $options['settings'] ) ) {

			foreach ( (array) $options['settings'] as $setting => $option ) {

				$args = $this->get_settings_field( $setting );

				// disabled select
				if ( array_key_exists( 'values', $args ) && FALSE === $args['values'] )
					continue;

				if ( ! array_key_exists( 'type', $args ) || 'enabled' == $args['type'] )
					$options['settings'][$setting] = (bool) $option;

				// multiple checkboxes
				else if ( is_array( $option ) )
					$options['settings'][$setting] = array_keys( $option );

				else
					$options['settings'][$setting] = trim( stripslashes( $option ) );
			}

			if ( ! count( $options['settings'] ) )
				unset( $options['settings'] );
		}

		return $options;
	}

	protected function get_settings_field( $setting )
	{
		foreach ( $this->settings as $section )
			if ( is_array( $section ) )
				foreach ( $section as $field )
					if ( is_array( $field ) ) {

						if ( isset( $field['field'] ) && $setting == $field['field'] )
							return $field;

					} else if ( $setting == $field ) {

						if ( method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field ) )
							return call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ], [ NULL ] );

						return [];
					}

		return [];
	}

	// enabled fields for a post type
	public function post_type_fields( $post_type = 'post', $js = FALSE )
	{
		$fields = [];

		if ( isset( $this->options->fields[$post_type] )
			&& is_array( $this->options->fields[$post_type] ) )
				foreach ( $this->options->fields[$post_type] as $field => $enabled )
					if ( $js )
						$fields[$field] = (bool) $enabled;
					else if ( $enabled )
						$fields[] = $field;

		return $fields;
	}

	// enabled fields with args for a post type
	public function post_type_field_types( $post_type = 'post' )
	{
		global $gEditorialPostTypeFields;

		if ( isset( $gEditorialPostTypeFields[$post_type] ) )
			return $gEditorialPostTypeFields[$post_type];

		$fields = [];

		$all = $this->post_type_all_fields( $post_type );
		$enabled = $this->post_type_fields( $post_type );

		foreach ( $enabled as $i => $field ) {

			$args = isset( $all[$field] ) && is_array( $all[$field] ) ? $all[$field] : [];

			if ( ! isset( $args['context'] ) && isset( $args['type'] ) )
				$args['context'] = in_array( $args['type'], [ 'box', 'title_before', 'title_after' ] )
					? 'dbx' : 'box';

			if ( ! isset( $args['icon'] ) )
				$args['icon'] = $this->get_field_icon( $field, $post_type, $args );

			$fields[$field] = self::atts( [
				'title'       => $this->get_string( $field, $post_type, 'titles', $field ),
				'description' => $this->get_string( $field, $post_type, 'descriptions' ),
				'icon'        => 'smiley',
				'type'        => 'text',
				'context'     => 'box',
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'tax'         => FALSE,
				'group'       => 'general',
				'order'       => 10+$i,
			], $args );
		}

		$gEditorialPostTypeFields[$post_type] = Arraay::multiSort( $fields, [
			'group' => SORT_ASC,
			'order' => SORT_ASC,
		] );

		return $gEditorialPostTypeFields[$post_type];
	}

	// HELPER: for importer tools
	public function post_type_fields_list( $post_type = 'post', $extra = [] )
	{
		$list = [];

		foreach ( $this->post_type_fields( $post_type ) as $field )
			$list[$field] = $this->get_string( $field, $post_type );

		foreach ( $extra as $key => $val )
			$list[$key] = $this->get_string( $val, $post_type );

		return $list;
	}

	public function add_post_type_fields( $post_type, $fields = NULL, $type = 'meta', $append = TRUE )
	{
		if ( is_null( $fields ) )
			$fields = $this->fields[$post_type];

		if ( ! count( $fields ) )
			return;

		if ( $append )
			$fields = array_merge( PostType::supports( $post_type, $type.'_fields' ), $fields );

		add_post_type_support( $post_type, [ $type.'_fields' ], $fields );
	}

	public function post_type_all_fields( $post_type = 'post', $field_type = NULL )
	{
		return PostType::supports( $post_type, ( is_null( $field_type ) ? $this->field_type : $field_type ).'_fields' );
	}

	public function get_string( $string, $post_type = 'post', $group = 'titles', $fallback = FALSE )
	{
		if ( isset( $this->strings[$group][$post_type][$string] ) )
			return $this->strings[$group][$post_type][$string];

		if ( isset( $this->strings[$group]['post'][$string] ) )
			return $this->strings[$group]['post'][$string];

		if ( isset( $this->strings[$group][$string] ) )
			return $this->strings[$group][$string];

		if ( FALSE === $fallback )
			return $string;

		return $fallback;
	}

	public function get_noop( $constant )
	{
		if ( ! empty( $this->strings['noops'][$constant] ) )
			return $this->strings['noops'][$constant];

		if ( 'post' == $constant )
			return _nx_noop( '%s Post', '%s Posts', 'Module: Noop', GEDITORIAL_TEXTDOMAIN );

		if ( 'connected' == $constant )
			return _nx_noop( '%s Item Connected', '%s Items Connected', 'Module: Noop', GEDITORIAL_TEXTDOMAIN );

		if ( 'word' == $constant )
			return _nx_noop( '%s Word', '%s Words', 'Module: Noop', GEDITORIAL_TEXTDOMAIN );

		$noop = [
			'plural'   => $constant,
			'singular' => $constant,
			// 'context'  => ucwords( $module->name ).' Module: Noop', // no need
			'domain'   => GEDITORIAL_TEXTDOMAIN,
		];

		if ( ! empty( $this->strings['labels'][$constant]['name'] ) )
			$noop['plural'] = $this->strings['labels'][$constant]['name'];

		if ( ! empty( $this->strings['labels'][$constant]['singular_name'] ) )
			$noop['singular'] = $this->strings['labels'][$constant]['singular_name'];

		return $noop;
	}

	// NOT USED
	public function nooped( $constant, $count )
	{
		return Helper::nooped( $count, $this->get_noop( $constant ) );
	}

	public function nooped_count( $constant, $count )
	{
		return sprintf( Helper::noopedCount( $count, $this->get_noop( $constant ) ), Number::format( $count ) );
	}

	public function constant( $key, $default = FALSE )
	{
		if ( isset( $this->constants[$key] ) )
			return $this->constants[$key];

		if ( 'post_cpt' == $key )
			return 'post';

		if ( 'page_cpt' == $key )
			return 'page';

		return $default;
	}

	public function slug()
	{
		return str_replace( '_', '-', $this->module->name );
	}

	protected function insert_default_terms( $constant )
	{
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->module->group.'-options' ) )
			return;

		$added = Taxonomy::insertDefaultTerms(
			$this->constant( $constant ),
			$this->strings['terms'][$constant]
		);

		WordPress::redirectReferer( ( FALSE === $added ? 'wrong' : [
			'message' => 'created',
			'count'   => $added,
		] ) );
	}

	protected function check_settings( $sub, $context = 'tools' )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		add_filter( 'geditorial_'.$context.'_subs', [ $this, 'append_sub' ], 10, 2 );

		if ( $this->key != $sub )
			return FALSE;

		add_action( 'geditorial_'.$context.'_sub_'.$sub, [ $this, $context.'_sub' ], 10, 2 );

		if ( 'settings' != $context )
			add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );

		return TRUE;
	}

	public function init_settings()
	{
		if ( ! isset( $this->settings ) )
			$this->settings = $this->filters( 'settings', $this->get_global_settings(), $this->module );
	}

	public function register_settings( $page = NULL )
	{
		if ( $page != $this->module->settings )
			return;

		$this->init_settings();

		if ( method_exists( $this, 'before_settings' ) )
			$this->before_settings( $page );

		foreach ( $this->settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				$section = $this->module->group.$section_suffix;

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$callback = [ $this, 'settings_section'.$section_suffix ];
				else
					$callback = '__return_false';

				Settings::addModuleSection( $this->module->group, [
					'id'            => $section,
					'callback'      => $callback,
					'section_class' => 'settings_section',
				] );

				foreach ( $fields as $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_array( $field ) )
						$args = array_merge( $field, [ 'section' => $section ] );

					else if ( method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field ) )
						$args = call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ], [ $section ] );

					else
						continue;

					$this->add_settings_field( $args );
				}

			} else if ( method_exists( $this, 'register_settings_'.$section_suffix ) ) {
				$title = $section_suffix == $fields ? NULL : $fields;
				call_user_func_array( [ $this, 'register_settings_'.$section_suffix ], [ $title ] );
			}
		}

		$this->default_buttons( $page );

		$screen = get_current_screen();

		foreach ( $this->settings_help_tabs() as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar() )
			$screen->set_help_sidebar( $sidebar );

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );
	}

	protected function settings_footer( $module )
	{
		if ( 'settings' == $module->name )
			Settings::settingsCredits();
	}

	protected function settings_signature( $module = NULL, $page = 'settings' )
	{
		Settings::settingsSignature();
	}

	public function add_settings_field( $r = [] )
	{
		$args = array_merge( [
			'page'        => $this->module->group,
			'section'     => $this->module->group.'_general',
			'field'       => FALSE,
			'label_for'   => '',
			'title'       => '',
			'description' => '',
			'callback'    => [ $this, 'do_settings_field' ],
		], $r );

		if ( ! $args['field'] )
			return;

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		add_settings_field( $args['field'], $args['title'], $args['callback'], $args['page'], $args['section'], $args );
	}

	public function settings_id_name_cb( $args )
	{
		return [
			( $args['id_attr'] ? $args['id_attr'] : $args['option_base'].'-'.$args['option_group'].'-'.$args['field'] ),
			( $args['name_attr'] ? $args['name_attr'] : $args['option_base'].'['.$args['option_group'].']['.$args['field'].']' ),
		];
	}

	public function do_settings_field( $atts = [] )
	{
		$args = array_merge( [
			'options'      => isset( $this->options->settings ) ? $this->options->settings : [],
			'option_base'  => $this->module->group,
			'option_group' => 'settings',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
		], $atts );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		Settings::fieldType( $args, $this->scripts );
	}

	// FIXME: TEST THIS!
	public function do_post_field( $atts = [], $post = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return;

		$args = array_merge( [
			'option_base'  => $this->module->group,
			'option_group' => 'meta',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
		], $atts );

		if ( ! array_key_exists( 'options', $args ) )
			$args['options'] = $this->get_postmeta( $post->ID, FALSE, [] );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		Settings::fieldType( $args, $this->scripts );
	}

	public function settings_print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts ) )
			HTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

		$this->scripts_printed = TRUE;
	}

	public function get_setting( $field, $default = NULL )
	{
		if ( isset( $this->options->settings[$field] ) )
			return $this->options->settings[$field];

		return $default;
	}

	public function update_option( $key, $value )
	{
		global $gEditorial;
		return $gEditorial->update_module_option( $this->module->name, $key, $value );
	}

	public function set_cookie( $array, $append = TRUE, $expire = '+ 365 day' )
	{
		if ( $append ) {
			$old = isset( $_COOKIE[$this->cookie] ) ? json_decode( self::unslash( $_COOKIE[$this->cookie] ) ) : [];
			$new = wp_json_encode( self::recursiveParseArgs( $array, $old ) );
		} else {
			$new = wp_json_encode( $array );
		}

		setcookie( $this->cookie, $new, strtotime( $expire ), COOKIEPATH, COOKIE_DOMAIN, FALSE );
	}

	public function get_cookie()
	{
		return isset( $_COOKIE[$this->cookie] ) ? json_decode( self::unslash( $_COOKIE[$this->cookie] ), TRUE ) : [];
	}

	public function delete_cookie()
	{
		setcookie( $this->cookie, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, FALSE );
	}

	public function get_post_type_labels( $constant )
	{
		if ( ! empty( $this->strings['labels'][$constant] ) )
			return $this->strings['labels'][$constant];

		$labels = [];

		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Helper::generatePostTypeLabels(
				$this->strings['noops'][$constant],
				$this->get_string( 'featured', $constant, 'misc', NULL ),
				$labels
			);

		return $labels;
	}

	protected function get_post_type_supports( $constant )
	{
		if ( isset( $this->options->settings[$constant.'_supports'] ) )
			return $this->options->settings[$constant.'_supports'];

		if ( isset( $this->supports[$constant] ) )
			return $this->supports[$constant];

		return array_keys( Settings::supportsOptions() );
	}

	// FIXME: use: `Icon::getPosttypeMenu()`
	public function get_posttype_icon( $constant, $default = 'welcome-write-blog' )
	{
		$icons  = $this->get_module_icons();
		$module = $this->module->icon ? $this->module->icon : 'welcome-write-blog';

		if ( empty( $icons['post_types'] ) )
			return $module;

		if ( isset( $icons['post_types'][$constant] ) )
			return $icons['post_types'][$constant];

		return $module;
	}

	public function get_posttype_cap_type( $constant )
	{
		$default = $this->constant( $constant.'_cap_type', 'post' );

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $default;

		if ( ! in_array( $this->constant( $constant ), gEditorial()->roles->post_types() ) )
			return $default;

		return gEditorial()->roles->constant( 'base_type' );
	}

	public function register_post_type( $constant, $atts = [], $taxonomies = [ 'post_tag' ] )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$post_type = $this->constant( $constant );

		$args = self::recursiveParseArgs( $atts, [
			'taxonomies'           => $taxonomies,
			'labels'               => $this->get_post_type_labels( $constant ),
			'supports'             => $this->get_post_type_supports( $constant ),
			'description'          => isset( $this->strings['labels'][$constant]['description'] ) ? $this->strings['labels'][$constant]['description'] : '',
			'register_meta_box_cb' => method_exists( $this, 'add_meta_box_cb_'.$constant ) ? [ $this, 'add_meta_box_cb_'.$constant ] : NULL,
			'menu_icon'            => 'dashicons-'.$this->get_posttype_icon( $constant ),
			'has_archive'          => $this->constant( $constant.'_archive', FALSE ),
			'query_var'            => $this->constant( $constant.'_query_var', $post_type ),
			'capability_type'      => $this->get_posttype_cap_type( $constant ),
			'rewrite'              => [
				'slug'       => $this->constant( $constant.'_slug', $post_type ),
				'with_front' => FALSE,
				'feeds'      => TRUE,
				'pages'      => TRUE,
				'ep_mask'    => EP_PERMALINK, // https://make.wordpress.org/plugins?p=29
			],
			'hierarchical'     => FALSE,
			'public'           => TRUE,
			'show_ui'          => TRUE,
			'map_meta_cap'     => TRUE,
			'can_export'       => TRUE,
			'delete_with_user' => FALSE,
			'menu_position'    => 4,

			// @SEE: https://core.trac.wordpress.org/ticket/39023
			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $post_type ) ),
			// 'rest_controller_class' => 'WP_REST_Posts_Controller',

			// SEE: https://github.com/torounit/custom-post-type-permalinks
			// 'cptp_permalink_structure' => $this->constant( $constant.'_permalink', '/%post_id%' ),
			// Only `%post_id%` and `%postname%` | SEE: https://github.com/torounit/simple-post-type-permalinks
			// 'sptp_permalink_structure' => $this->constant( $constant.'_permalink', '/%post_id%' ),
		] );

		return register_post_type( $post_type, $args );
	}

	public function get_taxonomy_labels( $constant )
	{
		if ( ! empty( $this->strings['labels'][$constant] ) )
			return $this->strings['labels'][$constant];

		$labels = [];

		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Helper::generateTaxonomyLabels( $this->strings['noops'][$constant], $labels );

		return $labels;
	}

	public function get_taxonomy_caps( $caps, $posttypes )
	{
		if ( is_array( $caps ) )
			return $caps;

		if ( FALSE === $caps )
			return [
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			];

		$defaults = [
			'manage_terms' => 'edit_others_posts',
			'edit_terms'   => 'edit_others_posts',
			'delete_terms' => 'edit_others_posts',
			'assign_terms' => 'edit_posts',
		];

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $defaults;

		if ( ! is_null( $caps ) )
			$posttype = $this->constant( $caps );
		else if ( count( $posttypes ) )
			$posttype = $posttypes[0];
		else
			return $defaults;

		if ( ! in_array( $posttype, gEditorial()->roles->post_types() ) )
			return $defaults;

		$base = gEditorial()->roles->constant( 'base_type' );

		return [
			'manage_terms' => 'edit_others_'.$base[1],
			'edit_terms'   => 'edit_others_'.$base[1],
			'delete_terms' => 'edit_others_'.$base[1],
			'assign_terms' => 'edit_'.$base[1],
		];
	}

	public function register_taxonomy( $constant, $atts = [], $post_types = NULL, $caps = NULL )
	{
		$taxonomy = $this->constant( $constant );

		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		else if ( ! is_array( $post_types ) )
			$post_types = [ $this->constant( $post_types ) ];

		$args = self::recursiveParseArgs( $atts, [
			'labels'                => $this->get_taxonomy_labels( $constant ),
			'update_count_callback' => [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ],
			'meta_box_cb'           => method_exists( $this, 'meta_box_cb_'.$constant ) ? [ $this, 'meta_box_cb_'.$constant ] : FALSE,
			'hierarchical'          => FALSE,
			'public'                => TRUE,
			'show_ui'               => TRUE,
			'show_admin_column'     => FALSE,
			'show_in_quick_edit'    => FALSE,
			'show_in_nav_menus'     => FALSE,
			'show_tagcloud'         => FALSE,
			'capabilities'          => $this->get_taxonomy_caps( $caps, $post_types ),
			'query_var'             => $this->constant( $constant.'_query', $taxonomy ),
			'rewrite'               => [
				'slug'       => $this->constant( $constant.'_slug', $taxonomy ), // can use : 'cpt/tax' if cpt registered after tax: https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2274
				'with_front' => FALSE,
			],

			// @SEE: https://core.trac.wordpress.org/ticket/39023
			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $taxonomy ),
			// 'rest_controller_class' => 'WP_REST_Terms_Controller',
		] );

		return register_taxonomy( $taxonomy, $post_types, $args );
	}

	protected function get_post_updated_messages( $constant )
	{
		return [ $this->constant( $constant ) => Helper::generatePostTypeMessages( $this->get_noop( $constant ) ) ];
	}

	protected function get_bulk_post_updated_messages( $constant, $bulk_counts )
	{
		return [ $this->constant( $constant ) => Helper::generateBulkPostTypeMessages( $this->get_noop( $constant ), $bulk_counts ) ];
	}

	public function get_image_sizes( $post_type )
	{
		if ( ! isset( $this->image_sizes[$post_type] ) ) {

			$sizes = $this->filters( $post_type.'_image_sizes', [] );

			if ( FALSE === $sizes ) {
				$this->image_sizes[$post_type] = []; // no sizes

			} else if ( count( $sizes ) ) {
				$this->image_sizes[$post_type] = $sizes; // custom sizes

			} else {
				foreach ( Helper::getWPImageSizes() as $size => $args )
					$this->image_sizes[$post_type][$post_type.'-'.$size] = $args;
			}
		}

		return $this->image_sizes[$post_type];
	}

	public function get_image_size_key( $constant, $size = 'thumbnail' )
	{
		$post_type = $this->constant( $constant );

		if ( isset( $this->image_sizes[$post_type][$post_type.'-'.$size] ) )
			return $post_type.'-'.$size;

		if ( isset( $this->image_sizes[$post_type]['post-'.$size] ) )
			return 'post-'.$size;

		return $size;
	}

	// use this on 'after_setup_theme'
	public function register_post_type_thumbnail( $constant )
	{
		$post_type = $this->constant( $constant );

		Helper::themeThumbnails( [ $post_type ] );

		foreach ( $this->get_image_sizes( $post_type ) as $name => $size )
			Helper::registerImageSize( $name, array_merge( $size, [ 'p' => [ $post_type ] ] ) );
	}

	// WARNING: every script must have a .min copy
	public function enqueue_asset_js( $args = [], $name = NULL, $deps = [ 'jquery' ], $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->key;

		// screen passed
		else if ( is_object( $name ) )
			$name = $this->key.'.'.$name->base;

		if ( TRUE === $args ) {
			$args = [];

		} else if ( ! is_array( $args ) && $args ) {
			$name .= '.'.$args;
			$args = [];
		}

		$name = str_replace( '_', '-', $name );

		if ( is_null( $handle ) )
			$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

		$prefix = is_admin() ? 'admin.' : 'front.';
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			$handle,
			GEDITORIAL_URL.'assets/js/'.$prefix.$name.$suffix.'.js',
			$deps,
			GEDITORIAL_VERSION,
			TRUE
		);

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( $this->hook() );

		gEditorial()->enqueue_asset_config( $args, $this->key );

		return $handle;
	}

	// combined global styles
	// CAUTION: front only
	// TODO: also we need api for module specified css
	public function enqueue_styles()
	{
		gEditorial()->enqueue_styles();
	}

	public function register_editor_button( $plugin, $settings_key = 'editor_button' )
	{
		if ( ! $this->get_setting( $settings_key, TRUE ) )
			return;

		gEditorial()->register_editor_button( $this->hook( $plugin ),
			'assets/js/tinymce/'.$this->module->name.'.'.$plugin.'.js' );
	}

	protected function register_shortcode( $constant, $callback = NULL )
	{
		if ( ! $this->get_setting( 'shortcode_support', TRUE ) )
			return;

		if ( is_null( $callback ) && method_exists( $this, $constant ) )
			$callback = [ $this, $constant ];

		$shortcode = $this->constant( $constant );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( 'geditorial_shortcode_'.$shortcode, $callback, 10, 3 );
	}

	// CAUTION: tax must be cat (hierarchical)
	// hierarchical taxonomies save by IDs, whereas non save by slugs
	// TODO: supporting tag (non-hierarchical)
	public function add_meta_box_checklist_terms( $constant, $post_type, $type = FALSE )
	{
		$taxonomy = $this->constant( $constant );
		$object   = get_taxonomy( $taxonomy );
		$manage   = current_user_can( $object->cap->manage_terms );
		$edit_url = $manage ? WordPress::getEditTaxLink( $taxonomy ) : FALSE;

		if ( $type )
			$this->remove_meta_box( $constant, $post_type, $type );

		add_meta_box( $this->classs( $taxonomy ),
			$this->get_meta_box_title( $constant, $edit_url, TRUE ),
			[ __NAMESPACE__.'\\MetaBox', 'checklistTerms' ],
			NULL,
			'side',
			'default',
			[
				'taxonomy' => $taxonomy,
				'edit_url' => $edit_url,
			]
		);
	}

	public function add_meta_box_author( $constant, $callback = 'post_author_meta_box' )
	{
		$post_type = $this->constant( $constant );
		$object    = get_post_type_object( $post_type );

		if ( current_user_can( $object->cap->edit_others_posts ) ) {

			remove_meta_box( 'authordiv', $post_type, 'normal' );

			add_meta_box( 'authordiv',
				$this->get_string( 'author_box_title', $constant, 'misc', __( 'Author' ) ),
				$callback,
				NULL,
				'normal',
				'core'
			);
		}
	}

	public function add_meta_box_excerpt( $constant, $callback = 'post_excerpt_meta_box' )
	{
		$post_type = $this->constant( $constant );

		remove_meta_box( 'postexcerpt', $post_type, 'normal' );

		add_meta_box( 'postexcerpt',
			$this->get_string( 'excerpt_box_title', $constant, 'misc', __( 'Excerpt' ) ),
			$callback,
			$post_type,
			'normal',
			'high'
		);
	}

	public function remove_meta_box( $constant, $post_type, $type = 'tag' )
	{
		if ( 'tag' == $type )
			remove_meta_box( 'tagsdiv-'.$this->constant( $constant ), $post_type, 'side' );

		else if ( 'cat' == $type )
			remove_meta_box( $this->constant( $constant ).'div', $post_type, 'side' );

		else if ( 'parent' == $type )
			remove_meta_box( 'pageparentdiv', $post_type, 'side' );

		else if ( 'image' == $type )
			remove_meta_box( 'postimagediv', $this->constant( $constant ), 'side' );

		else if ( 'author' == $type )
			remove_meta_box( 'authordiv', $this->constant( $constant ), 'normal' );

		else if ( 'excerpt' == $type )
			remove_meta_box( 'postexcerpt', $post_type, 'normal' );
	}

	public function get_meta_box_title( $constant = 'post', $url = NULL, $edit_cap = 'manage_options', $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', _x( 'Settings', 'Module: MetaBox default title', GEDITORIAL_TEXTDOMAIN ) );

		if ( FALSE === $url )
			return $title;

		if ( TRUE === $edit_cap || current_user_can( $edit_cap ) ) {

			if ( is_null( $url ) )
				$url = $this->get_url_settings();

			$action = $this->get_string( 'meta_box_action', $constant, 'misc', _x( 'Configure', 'Module: MetaBox default action', GEDITORIAL_TEXTDOMAIN ) );
			$title .= ' <span class="postbox-title-action geditorial-postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant, $fallback = NULL )
	{
		return $this->get_string( $column.'_column_title', $constant, 'misc', ( is_null( $fallback ) ? $column : $fallback ) );
	}

	public function get_url_settings( $extra = [] )
	{
		return WordPress::getAdminPageLink( $this->module->settings, $extra );
	}

	public function get_url_tax_edit( $constant, $term_id = FALSE, $extra = [] )
	{
		return WordPress::getEditTaxLink( $this->constant( $constant ), $term_id, $extra );
	}

	public function get_url_post_edit( $constant, $extra = [], $author_id = 0 )
	{
		return WordPress::getPostTypeEditLink( $this->constant( $constant ), $author_id, $extra );
	}

	public function get_url_post_new( $constant, $extra = [] )
	{
		return WordPress::getPostNewLink( $this->constant( $constant ), $extra );
	}

	protected function require_code( $filenames = 'templates' )
	{
		$module = $this->slug();

		foreach ( (array) $filenames as $filename )
			require_once( GEDITORIAL_DIR.'modules/'.$module.'/'.$filename.'.php' );
	}

	public function is_current_posttype( $constant )
	{
		return WordPress::currentPostType() == $this->constant( $constant );
	}

	public function is_save_post( $post, $constant = FALSE )
	{
		if ( wp_is_post_autosave( $post ) )
			return FALSE;

		if ( wp_is_post_revision( $post ) )
			return FALSE;

		if ( empty( $_POST ) )
			return FALSE;

		if ( is_array( $constant )
			&& ! in_array( $post->post_type, $constant ) )
				return FALSE;

		if ( $constant
			&& ! is_array( $constant )
			&& $post->post_type != $this->constant( $constant ) )
				return FALSE;

		return TRUE;
	}

	// for ajax calls on quick edit
	public function is_inline_save( $request, $constant = FALSE )
	{
		if ( empty( $request['action'] )
			|| 'inline-save' != $request['action'] )
				return FALSE;

		if ( empty( $request['screen'] )
			|| empty( $request['post_type'] ) )
				return FALSE;

		if ( is_array( $constant )
			&& ! in_array( $request['post_type'], $constant ) )
				return FALSE;

		if ( $constant
			&& ! is_array( $constant )
			&& $request['post_type'] != $this->constant( $constant ) )
				return FALSE;

		return TRUE;
	}

	public function get_linked_term( $post_id, $posttype_constant_key, $tax_constant_key )
	{
		$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );
		return get_term_by( 'id', intval( $term_id ), $this->constant( $tax_constant_key ) );
	}

	public function set_linked_term( $post_id, $term_or_id, $posttype_constant_key, $tax_constant_key )
	{
		if ( ! $term = Taxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		update_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', $term->term_id );

		if ( function_exists( 'update_term_meta' ) )
			update_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', $post_id );

		return TRUE;
	}

	public function rev_linked_term( $post_id, $term_or_id, $posttype_constant_key, $tax_constant_key )
	{
		if ( ! $term = Taxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		if ( ! $post_id )
			$post_id = $this->get_linked_post_id( $term, 'issue_cpt', 'issue_tax' );

		if ( $post_id )
			delete_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id' );

		if ( function_exists( 'delete_term_meta' ) )
			delete_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked' );

		return TRUE;
	}

	public function get_linked_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		if ( ! $term = Taxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		$post_id = FALSE;

		if ( function_exists( 'get_term_meta' ) )
			$post_id = get_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', TRUE );

		if ( ! $post_id && $check_slug )
			$post_id = PostType::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	public function get_linked_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );

		$args = [
			'tax_query' => [ [
				'taxonomy' => $this->constant( $tax_constant_key ),
				'field'    => 'id',
				'terms'    => [ $term_id ]
			] ],
			'post_type'   => $this->post_types(),
			'numberposts' => -1,
		];

		if ( $count ) {
			$args['fields'] = 'ids';
			$args['update_post_meta_cache'] = FALSE;
			$args['update_post_term_cache'] = FALSE;
			$args['lazy_load_term_meta'] = FALSE;
		}

		$items = get_posts( $args );

		if ( $count )
			return count( $items );

		return $items;
	}

	// DEFAULT METHOD
	// used for issue/book/etc.
	public function get_assoc_post( $post_id = NULL, $single = FALSE )
	{
		return FALSE;
	}

	protected function do_trash_post( $post_id, $posttype_constant_key, $taxonomy_constant_key )
	{
		if ( $term = $this->get_linked_term( $post_id, $posttype_constant_key, $taxonomy_constant_key ) ) {
			wp_update_term( $term->term_id, $this->constant( $taxonomy_constant_key ), [
				'name' => $term->name.'___TRASHED',
				'slug' => $term->slug.'-trashed',
			] );
		}
	}

	protected function do_untrash_post( $post_id, $posttype_constant_key, $taxonomy_constant_key )
	{
		if ( $term = $this->get_linked_term( $post_id, $posttype_constant_key, $taxonomy_constant_key ) ) {
			wp_update_term( $term->term_id, $this->constant( $taxonomy_constant_key ), [
				'name' => str_ireplace( '___TRASHED', '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			] );
		}
	}

	protected function do_before_delete_post( $post_id, $posttype_constant_key, $taxonomy_constant_key )
	{
		if ( $term = $this->get_linked_term( $post_id, $posttype_constant_key, $taxonomy_constant_key ) ) {
			wp_delete_term( $term->term_id, $this->constant( $taxonomy_constant_key ) );
			delete_metadata( 'term', $term->term_id, $this->constant( $posttype_constant_key ).'_linked' );
		}
	}

	protected function do_restrict_manage_posts_taxes( $taxes, $posttype_constant_key = TRUE )
	{
		global $wp_query;

		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant ) {

				$tax = $this->constant( $constant );
				if ( $obj = get_taxonomy( $tax ) ) {

					$selected = isset( $wp_query->query[$tax] ) ? $wp_query->query[$tax] : '';

					// if selected is term_id instead of term slug
					if ( $selected && '-1' != $selected && is_numeric( $selected ) ) {

						if ( $term = get_term_by( 'id', $selected, $tax ) )
							$selected = $term->slug;

						else
							$selected = '';
					}

					wp_dropdown_categories( [
						'show_option_all'  => $this->get_string( 'show_option_all', $constant, 'misc', $obj->labels->all_items ),
						'show_option_none' => $this->get_string( 'show_option_none', $constant, 'misc', '('.$obj->labels->no_terms.')' ),
						'taxonomy'         => $tax,
						'name'             => $obj->name,
						'orderby'          => 'name',
						'value_field'      => 'slug',
						'selected'         => $selected,
						'hierarchical'     => $obj->hierarchical,
						'depth'            => 3,
						'show_count'       => FALSE,
						'hide_empty'       => TRUE,
						'hide_if_empty'    => TRUE,
					] );
				}
			}
		}
	}

	protected function do_restrict_manage_posts_posts( $tax_constant_key, $posttype_constant_key )
	{
		$tax_obj = get_taxonomy( $tax = $this->constant( $tax_constant_key ) );

		wp_dropdown_pages( [
			'post_type'        => $this->constant( $posttype_constant_key ),
			'selected'         => isset( $_GET[$tax] ) ? $_GET[$tax] : '',
			'name'             => $tax,
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => $tax_obj->labels->all_items,
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => [ 'publish', 'future', 'draft', 'pending' ],
			'value_field'      => 'post_name',
			'walker'           => new Walker_PageDropdown(),
		] );
	}

	protected function do_parse_query_taxes( &$query, $taxes, $posttype_constant_key = TRUE )
	{
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant ) {

				$tax = $this->constant( $constant );

				if ( isset( $query->query_vars[$tax] ) ) {

					if ( '-1' == $query->query_vars[$tax] ) {

						$query->query_vars['tax_query'] = [ [
							'taxonomy' => $tax,
							'operator' => 'NOT EXISTS',
						] ];

						unset( $query->query_vars[$tax] );

					} else if ( is_numeric( $query->query_vars[$tax] ) ) {

						$term = get_term_by( 'id', $query->query_vars[$tax], $tax );

						if ( ! empty( $term ) && ! is_wp_error( $term ) )
							$query->query_vars[$tax] = $term->slug;
					}
				}
			}
		}
	}

	// @SEE: https://core.trac.wordpress.org/ticket/23421
	// @SOURCE: http://scribu.net/wordpress/sortable-taxonomy-columns.html
	protected function do_posts_clauses_taxes( $pieces, &$wp_query, $taxes, $posttype_constant_key = TRUE )
	{
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			global $wpdb;

			foreach ( (array) $taxes as $constant ) {
				$tax = $this->constant( $constant );

				if ( isset( $wp_query->query['orderby'] )
					&& 'taxonomy-'.$tax == $wp_query->query['orderby'] ) {

						$pieces['join'] .= <<<SQL
LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
SQL;

					$pieces['where']   .= $wpdb->prepare( " AND (taxonomy = '%s' OR taxonomy IS NULL)", $tax );
					$pieces['groupby']  = "object_id";
					$pieces['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
					$pieces['orderby'] .= ( 'ASC' == strtoupper( $wp_query->get('order') ) ) ? 'ASC' : 'DESC';

					break;
				}
			}
		}

		return $pieces;
	}

	protected function dashboard_glance_post( $posttype_constant_key, $edit_cap = 'edit_posts' )
	{
		$posttype = $this->constant( $posttype_constant_key );
		$posts    = wp_count_posts( $posttype );

		if ( ! $posts->publish )
			return FALSE;

		$class  = 'geditorial-glance-item -'.$this->slug().' -posttype -posttype-'.$posttype;
		$format = current_user_can( $edit_cap ) ? '<a class="'.$class.'" href="edit.php?post_type=%3$s">%1$s %2$s</a>' : '<div class="'.$class.'">%1$s %2$s</div>';
		$text   = Helper::noopedCount( $posts->publish, $this->get_noop( $posttype_constant_key ) );

		return sprintf( $format, Number::format( $posts->publish ), $text, $posttype );
	}

	protected function dashboard_glance_tax( $tax_constant_key, $edit_cap = 'manage_categories' )
	{
		$taxonomy = $this->constant( $tax_constant_key );
		$terms    = wp_count_terms( $taxonomy );

		if ( ! $terms )
			return FALSE;

		$class  = 'geditorial-glance-item -'.$this->slug().' -tax -taxonomy-'.$taxonomy;
		$format = current_user_can( $edit_cap ) ? '<a class="'.$class.'" href="edit-tags.php?taxonomy=%3$s">%1$s %2$s</a>' : '<div class="'.$class.'">%1$s %2$s</div>';
		$text   = Helper::noopedCount( $terms, $this->get_noop( $tax_constant_key ) );

		return sprintf( $format, Number::format( $terms ), $text, $taxonomy );
	}

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL )
	{
		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( is_null( $title ) )
			$title = $this->get_string( 'column_icon_title', $icon, 'misc', '' );

		return HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ? $link : FALSE,
			'title'  => $title ? $title : FALSE,
			'class'  => [ '-icon', ( $link ? '-link' : '-info' ) ],
			'target' => $link ? '_blank' : FALSE,
		], HTML::getDashicon( $icon ) );
	}

	public function column_thumb( $post_id, $size = [ 45, 72 ] )
	{
		echo $this->filters( 'column_thumb', WordPress::getFeaturedImageHTML( $post_id, $size ), $post_id, $size );
	}

	// TODO: override $title_attr based on passed constant key
	public function column_count( $count, $title_attr = NULL )
	{
		echo Helper::htmlCount( $count, $title_attr );
	}

	public function column_term( $object_id, $tax_constant_key, $title_attr = NULL, $single = TRUE )
	{
		$the_terms = wp_get_object_terms( $object_id, $this->constant( $tax_constant_key ) );

		if ( ! is_wp_error( $the_terms ) && count( $the_terms ) ) {

			if ( $single ) {
				echo $the_terms[0]->name;

			} else {

				$terms = [];

				foreach ( $the_terms as $the_term )
					$terms[] = $the_term->name;

				echo Helper::getJoined( $terms );
			}

		} else {

			if ( is_null( $title_attr ) )
				$title_attr = _x( 'No Term', 'Module: No Count Term Attribute', GEDITORIAL_TEXTDOMAIN );

			printf( '<span title="%s" class="column-term-empty">&mdash;</span>', $title_attr );
		}
	}

	// adds the module enabled class to body in admin
	public function _admin_enabled()
	{
		add_action( 'admin_body_class', [ $this, 'admin_body_class_enabled' ] );
	}

	public function admin_body_class_enabled( $classes )
	{
		return ' '.$this->classs( 'enabled' ).$classes;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/Connection-information
	public function p2p_register( $constant, $post_types = NULL )
	{
		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		if ( ! count( $post_types ) )
			return FALSE;

		$to  = $this->constant( $constant );
		$p2p = $this->constant( $constant.'_p2p' );

		$args = array_merge( [
			'name'         => $p2p,
			'from'         => $post_types,
			'to'           => $to,
			'admin_column' => 'from', // 'any', 'from', 'to', FALSE
			'admin_box'    => [
				'show'    => 'from',
				'context' => 'advanced',
			],
		], $this->strings['p2p'][$constant] );

		$hook = 'geditorial_'.$this->module->name.'_'.$to.'_p2p_args';

		if ( $args = apply_filters( $hook, $args, $post_types ) )
			if ( p2p_register_connection_type( $args ) )
				$this->p2p = $p2p;
	}

	public function o2o_register( $constant, $post_types = NULL )
	{
		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		if ( ! count( $post_types ) )
			return FALSE;

		$to  = $this->constant( $constant );
		$o2o = $this->constant( $constant.'_o2o' );

		$args = array_merge( [
			'name'         => $o2o,
			'from'         => $post_types,
			'to'           => $to,
			'admin_column' => 'from', // 'any', 'from', 'to', FALSE
			'admin_box'    => [
				'show'    => 'from',
				'context' => 'advanced',
			],
		], $this->strings['o2o'][$constant] );

		$hook = 'geditorial_'.$this->module->name.'_'.$to.'_o2o_args';

		if ( $args = apply_filters( $hook, $args, $post_types ) )
			if ( o2o_register_connection_type( $args ) )
				$this->o2o = $o2o;
	}

	public function p2p_get_meta( $p2p_id, $meta_key, $before = '', $after = '', $title = FALSE )
	{
		if ( ! $meta = p2p_get_meta( $p2p_id, $meta_key, TRUE ) )
			return '';

		$html = apply_filters( 'string_format_i18n', $meta );

		if ( $title )
			$html = '<span title="'.esc_attr( $title ).'">'.$html.'</span>';

		return $before.$html.$after;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/Creating-connections-programmatically
	public function p2p_connect( $constant, $from, $to, $meta = [] )
	{
		$type = p2p_type( $this->constant( $constant.'_p2p' ) );
		// $id   = $type->connect( $from, $to, [ 'date' => current_time( 'mysql' ) ] );
		$id   = $type->connect( $from, $to, $meta );

		if ( is_wp_error( $id ) )
			return FALSE;

		// foreach ( $meta as $key => $value )
		// 	p2p_add_meta( $id, $key, $value );

		return TRUE;
	}

	// should we insert content?
	public function is_content_insert( $posttypes = '', $first_page = TRUE )
	{
		if ( is_embed() )
			return FALSE;

		if ( ! is_main_query() )
			return FALSE;

		if ( ! in_the_loop() )
			return FALSE;

		if ( is_null( $posttypes ) )
			$posttypes = $this->post_types();

		else if ( $posttypes && ! is_array( $posttypes ) )
			$posttypes = $this->constant( $posttypes );

		if ( ! is_singular( $posttypes ) )
			return FALSE;

		if ( $first_page && 1 != $GLOBALS['page'] )
			return FALSE;

		return TRUE;
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( FALSE !== $posttypes
			&& ! $this->is_content_insert( $posttypes ) )
				return;

		if ( $before = $this->get_setting( 'before_content', FALSE ) )
			echo '<div class="geditorial-wrap -'.$this->module->name.' -content-before">'
				.do_shortcode( $before ).'</div>';
	}

	public function content_after( $content, $posttypes = NULL )
	{
		if ( FALSE !== $posttypes
			&& ! $this->is_content_insert( $posttypes ) )
				return;

		if ( $after = $this->get_setting( 'after_content', FALSE ) )
			echo '<div class="geditorial-wrap -'.$this->module->name.' -content-after">'
				.do_shortcode( $after ).'</div>';
	}

	// DEFAULT FILTER
	public function get_default_comment_status( $status, $post_type, $comment_type )
	{
		return $this->get_setting( 'comment_status', $status );
	}

	protected function _hook_ajax( $nopriv = FALSE )
	{
		add_action( 'wp_ajax_'.$this->hook(), [ $this, 'ajax' ] );

		if ( $nopriv )
			add_action( 'wp_ajax_nopriv_'.$this->hook(), [ $this, 'ajax' ] );
	}

	// DEFAULT FILTER
	public function ajax()
	{
		Ajax::errorWhat();
	}

	protected function _tweaks_taxonomy()
	{
		add_filter( 'geditorial_tweaks_taxonomy_info', [ $this, 'tweaks_taxonomy_info' ], 10, 3 );
	}

	// DEFAULT FILTER
	public function tweaks_taxonomy_info( $info, $object, $post_type )
	{
		$icons = $this->get_module_icons();

		if ( empty( $icons['taxonomies'] ) )
			return $info;

		foreach ( $icons['taxonomies'] as $tax => $icon )
			if ( $object->name == $this->constant( $tax ) )
				return [
					'icon'  => is_null( $icon ) ? $this->module->icon : $icon,
					'title' => $this->get_column_title( 'tweaks', $tax ),
					'edit'  => NULL,
				];

		return $info;
	}

	public function get_field_icon( $field, $post_type = 'post', $args = [] )
	{
		switch ( $field ) {
			case 'ot': return 'arrow-up-alt2';
			case 'st': return 'arrow-down-alt2';
			case 'as': return 'admin-users';
		}

		return 'admin-post';
	}

	protected function limit_sub( $sub = NULL, $default = 25, $key = 'limit', $option = 'per_page' )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		$per_page = (int) get_user_option( $this->base.'_'.$sub.'_'.$option );

		if ( empty( $per_page ) || $per_page < 1 )
			$per_page = $default;

		return intval( self::req( $key, $per_page ) );
	}

	public function screen_option( $sub = NULL, $option = 'per_page', $default = 25, $label = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		add_screen_option( $option, [
			'default' => $default,
			'option'  => $this->base.'_'.$sub.'_'.$option,
			'label'   => $label,
		] );
	}

	public function get_icon( $name, $group = NULL )
	{
		return gEditorial()->icon( $name, ( is_null( $group ) ? $this->icon_group : $group ) );
	}
}

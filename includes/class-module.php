<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialModuleCore extends gEditorialWPModule
{

	public $module;
	public $options;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

	protected $cookie     = 'geditorial';
	protected $field_type = 'meta';

	protected $priority_init      = 10;
	protected $priority_init_ajax = 10;

	protected $constants = array();
	protected $strings   = array();
	protected $supports  = array();
	protected $fields    = array();

	protected $partials        = array();
	protected $partials_remote = array();

	protected $post_types_excluded = array( 'attachment' );
	protected $taxonomies_excluded = array();

	protected $image_sizes  = array();
	protected $kses_allowed = array();

	protected $scripts = array();
	protected $buttons = array();
	protected $errors  = array();

	protected $caps = array(
		'reports'  => 'edit_others_posts',
		'settings' => 'manage_options',
		'tools'    => 'edit_others_posts',
	);

	protected $root_key = FALSE; // ROOT CONSTANT
	protected $p2p      = FALSE; // P2P ENABLED/Connection Type
	protected $meta     = FALSE; // META ENABLED
	protected $tweaks   = FALSE; // TWEAKS ENABLED

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

		if ( self::isAJAX() && method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax( $_REQUEST );
	}

	protected function setup_remote( $args = array() )
	{
		$this->require_code( $this->partials_remote );
	}

	protected function setup( $args = array() )
	{
		$this->require_code( $this->partials );

		$ajax = gEditorialWordPress::isAJAX();
		$ui   = gEditorialWordPress::mustRegisterUI( FALSE );

		if ( method_exists( $this, 'p2p_init' ) )
			add_action( 'p2p_init', array( $this, 'p2p_init' ) );

		if ( method_exists( $this, 'widgets_init' ) )
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		if ( ! $ajax && method_exists( $this, 'tinymce_strings' ) )
			add_filter( 'geditorial_tinymce_strings', array( $this, 'tinymce_strings' ) );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( 'geditorial_meta_init', array( $this, 'meta_init' ) );

		if ( method_exists( $this, 'meta_post_types' ) )
			add_filter( 'geditorial_meta_support_post_types', array( $this, 'meta_post_types' ) );

		if ( method_exists( $this, 'gpeople_support' ) )
			add_filter( 'gpeople_remote_support_post_types', array( $this, 'gpeople_support' ) );

		add_action( 'after_setup_theme', array( $this, '_after_setup_theme' ), 1 );

		if ( method_exists( $this, 'after_setup_theme' ) )
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 20 );

		if ( $ajax && method_exists( $this, 'init_ajax' ) )
			add_action( 'init', array( $this, 'init_ajax' ), $this->priority_init_ajax );

		add_action( 'init', array( $this, 'init' ), $this->priority_init );

		if ( is_admin() ) {

			if ( method_exists( $this, 'admin_init' ) )
				add_action( 'admin_init', array( $this, 'admin_init' ) );

			if ( $ui && method_exists( $this, 'dashboard_glance_items' ) )
				add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ) );

			if ( $ui && method_exists( $this, 'current_screen' ) )
				add_action( 'current_screen', array( $this, 'current_screen' ) );

			if ( $ui )
				add_action( 'geditorial_settings_load', array( $this, 'register_settings' ) );

			if ( method_exists( $this, 'tweaks_strings' ) )
				add_filter( 'geditorial_tweaks_strings', array( $this, 'tweaks_strings' ) );

			if ( $ui && method_exists( $this, 'reports_settings' ) )
				add_action( 'geditorial_reports_settings', array( $this, 'reports_settings' ) );

			if ( $ui && method_exists( $this, 'tools_settings' ) )
				add_action( 'geditorial_tools_settings', array( $this, 'tools_settings' ) );
		}
	}

	public function _after_setup_theme()
	{
		$this->constants = apply_filters( 'geditorial_'.$this->module->name.'_constants', $this->get_global_constants(), $this->module );
		$this->supports  = apply_filters( 'geditorial_'.$this->module->name.'_supports', $this->get_global_supports(), $this->module );
		$this->fields    = apply_filters( 'geditorial_'.$this->module->name.'_fields', $this->get_global_fields(), $this->module );
	}

	protected function get_global_settings() { return array(); }
	protected function get_global_constants() { return array(); }
	protected function get_global_strings() { return array(); }
	protected function get_global_supports() { return array(); }
	protected function get_global_fields() { return array(); }

	protected function settings_help_tabs()
	{
		return gEditorialSettingsCore::settingsHelpContent( $this->module );
	}

	protected function settings_help_sidebar()
	{
		return gEditorialSettingsCore::settingsHelpLinks( $this->module );
	}

	protected function do_globals()
	{
		$this->strings = apply_filters( 'geditorial_'.$this->module->name.'_strings', $this->get_global_strings(), $this->module );
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
			$post_types = array();

		else if ( ! is_array( $post_types ) )
			$post_types = array( $this->constant( $post_types ) );

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
			$pre = array();

		else if ( TRUE === $pre )
			$pre = array( 'all' => _x( 'All PostTypes', 'Module Core', GEDITORIAL_TEXTDOMAIN ) );

		$all = gEditorialWPPostType::get();

		foreach ( $this->post_types( $post_types ) as $post_type )
			$pre[$post_type] = empty( $all[$post_type] ) ? $post_type : $all[$post_type];

		return $pre;
	}

	public function all_post_types( $exclude = TRUE )
	{
		$post_types = gEditorialWPPostType::get();

		if ( $exclude && count( $this->post_types_excluded ) )
			$post_types = array_diff_key( $post_types, array_flip( $this->post_types_excluded ) );

		return $post_types;
	}

	// enabled post types for this module
	public function taxonomies()
	{
		$taxonomies = array();

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
		$taxonomies = gEditorialWPTaxonomy::get();

		if ( count( $this->taxonomies_excluded ) )
			$taxonomies = array_diff_key( $taxonomies, array_flip( $this->taxonomies_excluded ) );

		return $taxonomies;
	}

	public function settings_posttypes_option( $section )
	{
		if ( $before = $this->get_string( 'post_types_before', 'post', 'settings', NULL ) )
			echo '<p class="description">'.$before.'</p>';

		foreach ( $this->all_post_types() as $post_type => $label ) {
			$html = gEditorialHTML::tag( 'input', array(
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$post_type,
				'name'    => $this->module->group.'[post_types]['.$post_type.']',
				'checked' => isset( $this->options->post_types[$post_type] ) && $this->options->post_types[$post_type],
			) );

			echo '<p>'.gEditorialHTML::tag( 'label', array(
				'for' => 'type-'.$post_type,
			), $html.'&nbsp;'.esc_html( $label ).' &mdash; <code>'.$post_type.'</code>' ).'</p>';
		}

		if ( $after = $this->get_string( 'post_types_after', 'post', 'settings', NULL ) )
			echo '<p class="description">'.$after.'</p>';
	}

	public function settings_taxonomies_option( $section )
	{
		if ( $before = $this->get_string( 'taxonomies_before', 'post', 'settings', NULL ) )
			echo '<p class="description">'.$before.'</p>';

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = gEditorialHTML::tag( 'input', array(
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->module->group.'[taxonomies]['.$taxonomy.']',
				'checked' => isset( $this->options->taxonomies[$taxonomy] ) && $this->options->taxonomies[$taxonomy],
			) );

			echo '<p>'.gEditorialHTML::tag( 'label', array(
				'for' => 'tax-'.$taxonomy,
			), $html.'&nbsp;'.esc_html( $label ).' &mdash; <code>'.$taxonomy.'</code>' ).'</p>';
		}

		if ( $after = $this->get_string( 'taxonomies_after', 'post', 'settings', NULL ) )
			echo '<p class="description">'.$after.'</p>';
	}

	// get stored post meta by the field
	public function get_postmeta( $post_id, $field = FALSE, $default = '', $key = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->meta_key;

		$postmeta = get_metadata( 'post', $post_id, $key, TRUE );

		if ( empty( $postmeta ) )
			return $default;

		if ( FALSE === $field )
			return $postmeta;

		foreach ( $this->sanitize_meta_field( $field ) as $field_key )
			if ( isset( $postmeta[$field_key] ) )
				return $postmeta[$field_key];

		return $default;
	}

	public function sanitize_meta_field( $field )
	{
		return (array) $field;
	}

	public function set_meta( $post_id, $postmeta, $key_suffix = '' )
	{
		if ( $postmeta && count( $postmeta ) )
			update_post_meta( $post_id, $this->meta_key.$key_suffix, $postmeta );
		else
			delete_post_meta( $post_id, $this->meta_key.$key_suffix );
	}

	public function register_settings_posttypes_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'post_types_title', 'post', 'settings',
				_x( 'Enable for Post Types', 'Module Core', GEDITORIAL_TEXTDOMAIN ) );

		$section = $this->module->group.'_posttypes';

		gEditorialSettingsCore::addModuleSection( $this->module->group, array(
			'id'            => $section,
			'section_class' => 'posttypes_option_section',
		) );

		add_settings_field( 'post_types',
			$title,
			array( $this, 'settings_posttypes_option' ),
			$this->module->group,
			$section
		);
	}

	public function register_settings_taxonomies_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'taxonomies_title', 'post', 'settings',
				_x( 'Enable for Taxonomies', 'Module Core', GEDITORIAL_TEXTDOMAIN ) );

		$section = $this->module->group.'_taxonomies';

		gEditorialSettingsCore::addModuleSection( $this->module->group, array(
			'id'            => $section,
			'section_class' => 'taxonomies_option_section',
		) );

		add_settings_field( 'taxonomies',
			$title,
			array( $this, 'settings_taxonomies_option' ),
			$this->module->group,
			$section
		);
	}

	public function register_settings_fields_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = _x( 'Fields for %s', 'Module Core', GEDITORIAL_TEXTDOMAIN );

		$all = $this->all_post_types();

		foreach ( $this->post_types() as $post_type ) {

			$fields  = $this->post_type_all_fields( $post_type );
			$section = $post_type.'_fields';

			if ( count( $fields ) ) {

				gEditorialSettingsCore::addModuleSection( $this->module->group, array(
					'id'            => $section,
					'title'         => sprintf( $title, $all[$post_type] ),
					'section_class' => 'fields_option_section fields_option-'.$post_type,
				) );

				$this->add_settings_field( array(
					'field'     => $post_type.'_fields_all',
					'post_type' => $post_type,
					'section'   => $section,
					'title'     => '&nbsp;',
					'callback'  => array( $this, 'settings_fields_option_all' ),
				) );

				foreach ( $fields as $field => $atts ) {

					$args = array(
						'field'       => $field,
						'post_type'   => $post_type,
						'section'     => $section,
						'field_title' => isset( $atts['title'] ) ? $atts['title'] : $this->get_string( $field, $post_type ),
						'description' => isset( $atts['description'] ) ? $atts['description'] : $this->get_string( $field, $post_type, 'descriptions' ),
						'callback'    => array( $this, 'settings_fields_option' ),
					);

					if ( is_array( $atts ) )
						$args = array_merge( $args, $atts );

					$args['title'] = '&nbsp;';

					$this->add_settings_field( $args );
				}

			} else if ( isset( $all[$post_type] ) ) {

				gEditorialSettingsCore::addModuleSection( $this->module->group, array(
					'id'            => $section,
					'title'         => sprintf( $title, $all[$post_type] ),
					'callback'      => array( $this, 'settings_fields_option_none' ),
					'section_class' => 'fields_option_section fields_option_none',
				) );
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

		$html = gEditorialHTML::tag( 'input', array(
			'type'    => 'checkbox',
			'value'   => 'enabled',
			'class'   => 'fields-check',
			'name'    => $name,
			'id'      => $id,
			'checked' => $value,
		) );

		echo '<div>'.gEditorialHTML::tag( 'label', array(
			'for' => $id,
		), $html.'&nbsp;'.$args['field_title'] );

		if ( $args['description'] )
			echo gEditorialHTML::tag( 'p', array(
				'class' => 'description',
			), $args['description'] );

		echo '</div>';
	}

	public function settings_fields_option_all( $args )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'  => 'checkbox',
			'class' => 'fields-check-all',
			'id'    => $args['post_type'].'_fields_all',
		) );

		echo gEditorialHTML::tag( 'label', array(
			'for' => $args['post_type'].'_fields_all',
		), $html.'&nbsp;<span class="description">'._x( 'Select All Fields', 'Module Core', GEDITORIAL_TEXTDOMAIN ).'</span>' );
	}

	public function settings_fields_option_none( $args )
	{
		gEditorialSettingsCore::moduleSectionEmpty( _x( 'No fields supported', 'Module Core', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function print_configure_view()
	{
		echo '<form action="'.$this->get_url_settings().'" method="post">';

			// FIXME: USE: `$this->settings_fields()`
			settings_fields( $this->module->group );

			gEditorialSettingsCore::moduleSections( $this->module->group );

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.esc_attr( $this->module->name ).'" />';

			$this->settings_buttons();

		echo '</form>';

		if ( self::isDev() )
			self::dump( $this->options );
	}

	public function default_buttons( $page = NULL )
	{
		$this->register_button( 'submit', _x( 'Save Changes', 'Module Core', GEDITORIAL_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		$this->register_button( 'reset-settings', _x( 'Reset Settings', 'Module Core', GEDITORIAL_TEXTDOMAIN ), sprintf( 'onclick="return confirm( \'%s\' )"', _x( 'Are you sure? This operation can not be undone.', 'Module Core', GEDITORIAL_TEXTDOMAIN ) ) );
	}

	public function register_button( $key, $value = NULL, $atts = array(), $type = 'secondary' )
	{
		if ( is_null( $value ) )
			$value = $this->get_string( $key, 'buttons', 'settings' );

		$this->buttons[$key] = array(
			'value' => is_null( $value ) ? $key : $value,
			'atts'  => $atts,
			'type'  => $type,
		);
	}

	protected function settings_buttons( $page = NULL, $wrap = '' )
	{
		if ( FALSE !== $wrap )
			echo '<p class="submit '.$this->base.'-wrap-buttons '.$wrap.'">';

		foreach ( $this->buttons as $action => $button ) {
			echo get_submit_button( $button['value'], $button['type'], $action, FALSE, $button['atts'] );
			echo '&nbsp;&nbsp;';
		}

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function submit_button( $name = '', $primary = FALSE, $text = NULL, $atts = array() )
	{
		if ( $name && is_null( $text ) )
			$text = $this->get_string( $name, 'buttons', 'settings' );

		if ( $primary )
			$atts['default'] = 'default';

		echo get_submit_button( $text, ( $primary ? 'primary' : 'secondary' ), $name, FALSE, $atts );
		echo '&nbsp;&nbsp;';
	}

	protected function settings_fields( $sub, $action = 'update', $context = 'settings' )
	{
		gEditorialHTML::inputHidden( 'base', $this->base );
		gEditorialHTML::inputHidden( 'key', $this->key );
		gEditorialHTML::inputHidden( 'context', $context );
		gEditorialHTML::inputHidden( 'sub', $sub );
		gEditorialHTML::inputHidden( 'action', $action );

		$this->nonce_field( $context, $sub );
	}

	// DEFAULT METHOD
	public function append_sub( $subs, $page = 'settings' )
	{
		if ( ! $this->cuc( $page ) )
			return $subs;

		return array_merge( $subs, array( $this->module->name => $this->module->title ) );
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
				$options['post_types'] = array();

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
				$options['taxonomies'] = array();

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
				$options['fields'] = array();

			foreach ( $this->post_types() as $post_type ) {

				if ( ! isset( $options['fields'][$post_type] ) )
					$options['fields'][$post_type] = array();

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
				if ( isset( $args['values'] ) && FALSE === $args['values'] )
					continue;

				if ( ! isset( $args['type'] )
					|| 'enabled' == $args['type'] ) {

					$options['settings'][$setting] = (bool) $option;

				// multiple checkboxes
				} else if ( 'checkbox' == $args['type'] ) {

					if ( is_array( $option ) )
						$options['settings'][$setting] = array_keys( $option );

				} else {
					$options['settings'][$setting] = trim( stripslashes( $option ) );
				}
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

						if ( method_exists( 'gEditorialSettingsCore', 'getSetting_'.$field ) )
							return call_user_func_array( array( 'gEditorialSettingsCore', 'getSetting_'.$field ), array( NULL ) );

						return array();
					}

		return array();
	}

	// enabled fields for a post type
	public function post_type_fields( $post_type = 'post', $is_constant = FALSE )
	{
		if ( $is_constant )
			$post_type = $this->constant( $post_type );

		$fields = array();

		if ( isset( $this->options->fields[$post_type] )
			&& is_array( $this->options->fields[$post_type] ) )
				foreach ( $this->options->fields[$post_type] as $field => $enabled )
					if ( $enabled )
						$fields[] = $field;

		return $fields;
	}

	// enabled fields with args for a post type
	public function post_type_field_types( $post_type = 'post', $sort = FALSE )
	{
		$fields = array();

		$all = $this->post_type_all_fields( $post_type );
		$enabled = $this->post_type_fields( $post_type );

		foreach ( $enabled as $i => $field )
			$fields[$field] = self::atts( array(
				'title'       => $this->get_string( $field, $post_type, 'titles', $field ),
				'description' => $this->get_string( $field, $post_type, 'descriptions' ),
				'type'        => 'text',
				'context'     => 'box',
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'tax'         => FALSE,
				'group'       => 10,
				'order'       => 10+$i,
			), ( isset( $all[$field] ) && is_array( $all[$field] ) ? $all[$field] : array() ) );

		if ( ! $sort )
			return $fields;

		return gEditorialArraay::multiSort( $fields, array(
			'group' => SORT_ASC,
			'order' => SORT_ASC,
		) );
	}

	// HELPER: for importer tools
	public function post_type_fields_list( $post_type = 'post', $extra = array() )
	{
		$list = array();

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
			$fields = array_merge( $this->post_type_supports( $post_type, $type.'_fields', FALSE ), $fields );

		add_post_type_support( $post_type, array( $type.'_fields' ), $fields );
	}

	// like WP core but returns the actual array!
	public function post_type_supports( $post_type, $feature, $is_constant = FALSE )
	{
		if ( $is_constant )
			$post_type = $this->constant( $post_type );

		$all = get_all_post_type_supports( $post_type );

		if ( isset( $all[$feature][0] )
			&& is_array( $all[$feature][0] ) )
				return $all[$feature][0];

		return array();
	}

	public function post_type_all_fields( $post_type = 'post' )
	{
		$fields = array();

		foreach ( $this->post_type_supports( $post_type, $this->field_type.'_fields', FALSE ) as $field => $args )
			$fields[$field] = $args;

		return $fields;
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

	public function get_noop( $constant_key )
	{
		if ( ! empty( $this->strings['noops'][$constant_key] ) )
			return $this->strings['noops'][$constant_key];

		if ( 'post' == $constant_key )
			return _nx_noop( '%s Post', '%s Posts', 'Module Core: Noop', GEDITORIAL_TEXTDOMAIN );

		if ( 'connected' == $constant_key )
			return _nx_noop( '%s Item Connected', '%s Items Connected', 'Module Core: Noop', GEDITORIAL_TEXTDOMAIN );

		if ( 'word' == $constant_key )
			return _nx_noop( '%s Word', '%s Words', 'Module Core: Noop', GEDITORIAL_TEXTDOMAIN );

		$noop = array(
			'plural'   => $constant_key,
			'singular' => $constant_key,
			// 'context'  => ucwords( $module->name ).' Module: Noop', // no need
			'domain'   => GEDITORIAL_TEXTDOMAIN,
		);

		if ( ! empty( $this->strings['labels'][$constant_key]['name'] ) )
			$noop['plural'] = $this->strings['labels'][$constant_key]['name'];

		if ( ! empty( $this->strings['labels'][$constant_key]['singular_name'] ) )
			$noop['singular'] = $this->strings['labels'][$constant_key]['singular_name'];

		return $noop;
	}

	// NOT USED
	public function nooped( $constant_key, $count )
	{
		return gEditorialHelper::nooped( $count, $this->get_noop( $constant_key ) );
	}

	public function nooped_count( $constant_key, $count )
	{
		return sprintf( gEditorialHelper::noopedCount( $count, $this->get_noop( $constant_key ) ), gEditorialNumber::format( $count ) );
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

	// converts back numbers into english
	public function intval( $text, $intval = TRUE )
	{
		self::__dep();

		$number = apply_filters( 'number_format_i18n_back', $text );

		if ( $intval )
			return intval( $number );

		return $number;
	}

	public function kses( $text, $allowed = array(), $context = 'display' )
	{
		self::__dep();

		if ( is_null( $allowed ) )
			$allowed = array();

		else if ( ! count( $allowed ) )
			$allowed = $this->kses_allowed;

		return apply_filters( 'geditorial_kses', wp_kses( $text, $allowed ), $allowed, $context );
	}

	public function user_can( $action = 'view', $field = '', $post_type = 'post' )
	{
		global $geditorial_modules_caps;

		if ( empty( $geditorial_modules_caps )
			&& isset( $geditorial_modules_caps[$this->module->name] ) )
				$geditorial_modules_caps[$this->module->name] = apply_filters( 'geditorial_'.$this->module->name.'_caps', array() );

		if ( isset( $geditorial_modules_caps[$this->module->name][$action][$post_type][$field] ) )
			return current_user_can( $geditorial_modules_caps[$this->module->name][$action][$post_type][$field] );

		return TRUE;
	}

	protected function insert_default_terms( $constant_key )
	{
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->module->group.'-options' ) )
			return;

		$added = gEditorialWPTaxonomy::insertDefaultTerms(
			$this->constant( $constant_key ),
			$this->strings['terms'][$constant_key]
		);

		gEditorialWordPress::redirectReferer( ( FALSE === $added ? 'wrong' : array(
			'message' => 'created',
			'count'   => $added,
		) ) );
	}

	public function is_register_settings( $page )
	{
		return $page == $this->module->settings;
	}

	public function init_settings()
	{
		if ( ! isset( $this->settings ) )
			$this->settings = apply_filters( 'geditorial_'.$this->module->name.'_settings', $this->get_global_settings(), $this->module );
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		$this->init_settings();

		foreach ( $this->settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				$section = $this->module->group.$section_suffix;

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$callback = array( $this, 'settings_section'.$section_suffix );
				else
					$callback = '__return_false';

				gEditorialSettingsCore::addModuleSection( $this->module->group, array(
					'id'            => $section,
					'callback'      => $callback,
					'section_class' => 'settings_section',
				) );

				foreach ( $fields as $field ) {

					if ( is_array( $field ) )
						$args = array_merge( $field, array( 'section' => $section ) );

					else if ( method_exists( 'gEditorialSettingsCore', 'getSetting_'.$field ) )
						$args = call_user_func_array( array( 'gEditorialSettingsCore', 'getSetting_'.$field ), array( $section ) );

					else
						continue;

					$this->add_settings_field( $args );
				}

			} else if ( method_exists( $this, 'register_settings_'.$section_suffix ) ) {
				$title = $section_suffix == $fields ? NULL : $fields;
				call_user_func_array( array( $this, 'register_settings_'.$section_suffix ), array( $title ) );
			}
		}

		$this->default_buttons( $page );

		$screen = get_current_screen();

		foreach ( $this->settings_help_tabs() as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar() )
			$screen->set_help_sidebar( $sidebar );

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', array( $this, 'settings_print_scripts' ), 99 );
	}

	protected function settings_footer( $module )
	{
		if ( 'settings' == $module->name )
			gEditorialSettingsCore::settingsCredits();
	}

	protected function settings_signature( $module = NULL, $page = 'settings' )
	{
		gEditorialSettingsCore::settingsSignature();
	}

	public function add_settings_field( $r = array() )
	{
		$args = array_merge( array(
			'page'        => $this->module->group,
			'section'     => $this->module->group.'_general',
			'field'       => FALSE,
			'label_for'   => '',
			'title'       => '',
			'description' => '',
			'callback'    => array( $this, 'do_settings_field' ),
		), $r );

		if ( ! $args['field'] )
			return;

		if ( empty( $args['title'] ) )
			$args['title'] = $args['field'];

		add_settings_field( $args['field'], $args['title'], $args['callback'], $args['page'], $args['section'], $args );
	}

	public function settings_id_name_cb( $args )
	{
		return array(
			( $args['id_attr'] ? $args['id_attr'] : $args['option_base'].'-'.$args['option_group'].'-'.$args['field'] ),
			( $args['name_attr'] ? $args['name_attr'] : $args['option_base'].'['.$args['option_group'].']['.$args['field'].']' ),
		);
	}

	public function do_settings_field( $atts = array() )
	{
		$args = array_merge( array(
			'options'      => isset( $this->options->settings ) ? $this->options->settings : array(),
			'option_base'  => $this->module->group,
			'option_group' => 'settings',
			'id_name_cb'   => array( $this, 'settings_id_name_cb' ),
		), $atts );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		gEditorialSettingsCore::fieldType( $args, $this->scripts );
	}

	public function settings_print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts ) )
			gEditorialHTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

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
			$old = isset( $_COOKIE[$this->cookie] ) ? json_decode( wp_unslash( $_COOKIE[$this->cookie] ) ) : array();
			$new = wp_json_encode( self::recursiveParseArgs( $array, $old ) );
		} else {
			$new = wp_json_encode( $array );
		}

		setcookie( $this->cookie, $new, strtotime( $expire ), COOKIEPATH, COOKIE_DOMAIN, FALSE );
	}

	public function get_cookie()
	{
		return isset( $_COOKIE[$this->cookie] ) ? json_decode( wp_unslash( $_COOKIE[$this->cookie] ), TRUE ) : array();
	}

	public function delete_cookie()
	{
		setcookie( $this->cookie, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, FALSE );
	}

	public function get_post_type_labels( $constant_key )
	{
		if ( ! empty( $this->strings['labels'][$constant_key] ) )
			return $this->strings['labels'][$constant_key];

		$labels = array();

		if ( $menu_name = $this->get_string( 'menu_name', $constant_key, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( ! empty( $this->strings['noops'][$constant_key] ) )
			return gEditorialHelper::generatePostTypeLabels(
				$this->strings['noops'][$constant_key],
				$this->get_string( 'featured', $constant_key, 'misc', NULL ),
				$labels
			);

		return $labels;
	}

	public function register_post_type( $constant_key, $atts = array(), $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$post_type = $this->constant( $constant_key );

		$args = self::recursiveParseArgs( $atts, array(
			'taxonomies'           => $taxonomies,
			'labels'               => $this->get_post_type_labels( $constant_key ),
			'supports'             => isset( $this->supports[$constant_key] ) ? $this->supports[$constant_key] : array( 'title', 'editor' ),
			'description'          => isset( $this->strings['labels'][$constant_key]['description'] ) ? $this->strings['labels'][$constant_key]['description'] : '',
			'register_meta_box_cb' => method_exists( $this, 'add_meta_box_cb_'.$constant_key ) ? array( $this, 'add_meta_box_cb_'.$constant_key ) : NULL,
			'menu_icon'            => $this->module->icon ? 'dashicons-'.$this->module->icon : 'dashicons-welcome-write-blog',
			'has_archive'          => $this->constant( $constant_key.'_archive', FALSE ),
			'query_var'            => $this->constant( $constant_key.'_query_var', $post_type ),
			'capability_type'      => $this->constant( $constant_key.'_cap_type', 'post' ),
			'rewrite'              => array(
				'slug'       => $this->constant( $constant_key.'_slug', $post_type ),
				'with_front' => FALSE,
				'feeds'      => TRUE,
				'pages'      => TRUE,
				'ep_mask'    => EP_PERMALINK, // https://make.wordpress.org/plugins?p=29
			),
			'hierarchical'     => FALSE,
			'public'           => TRUE,
			'show_ui'          => TRUE,
			'map_meta_cap'     => TRUE,
			'can_export'       => TRUE,
			'delete_with_user' => FALSE,
			'menu_position'    => 4,

			// @SEE: https://core.trac.wordpress.org/ticket/39023
			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant_key.'_rest', $post_type ),
			// 'rest_controller_class' => 'WP_REST_Posts_Controller',

			// SEE: https://github.com/torounit/custom-post-type-permalinks
			// 'cptp_permalink_structure' => $this->constant( $constant_key.'_permalink', '/%post_id%' ),
			// Only `%post_id%` and `%postname%` | SEE: https://github.com/torounit/simple-post-type-permalinks
			// 'sptp_permalink_structure' => $this->constant( $constant_key.'_permalink', '/%post_id%' ),
		) );

		return register_post_type( $post_type, $args );
	}

	public function get_taxonomy_labels( $constant_key )
	{
		if ( ! empty( $this->strings['labels'][$constant_key] ) )
			return $this->strings['labels'][$constant_key];

		$labels = array();

		if ( $menu_name = $this->get_string( 'menu_name', $constant_key, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( ! empty( $this->strings['noops'][$constant_key] ) )
			return gEditorialHelper::generateTaxonomyLabels( $this->strings['noops'][$constant_key], $labels );

		return $labels;
	}

	public function register_taxonomy( $constant_key, $atts = array(), $post_types = NULL )
	{
		$taxonomy = $this->constant( $constant_key );

		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		else if ( ! is_array( $post_types ) )
			$post_types = array( $this->constant( $post_types ) );

		$args = self::recursiveParseArgs( $atts, array(
			'labels'                => $this->get_taxonomy_labels( $constant_key ),
			'update_count_callback' => array( 'gEditorialWPDatabase', 'updateCountCallback' ),
			'meta_box_cb'           => method_exists( $this, 'meta_box_cb_'.$constant_key ) ? array( $this, 'meta_box_cb_'.$constant_key ) : FALSE,
			'hierarchical'          => FALSE,
			'public'                => TRUE,
			'show_ui'               => TRUE,
			'show_admin_column'     => FALSE,
			'show_in_quick_edit'    => FALSE,
			'show_in_nav_menus'     => FALSE,
			'show_tagcloud'         => FALSE,
			'query_var'             => $this->constant( $constant_key.'_query', $taxonomy ),
			'rewrite'               => array(
				'slug'       => $this->constant( $constant_key.'_slug', $taxonomy ),
				'with_front' => FALSE,
			),
			'capabilities' => array(
				'manage_terms' => 'edit_others_posts', // 'manage_categories',
				'edit_terms'   => 'edit_others_posts', // 'manage_categories',
				'delete_terms' => 'edit_others_posts', // 'manage_categories',
				'assign_terms' => 'edit_posts', // 'edit_published_posts',
			),

			// @SEE: https://core.trac.wordpress.org/ticket/39023
			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant_key.'_rest', $taxonomy ),
			// 'rest_controller_class' => 'WP_REST_Terms_Controller',
		) );

		return register_taxonomy( $taxonomy, $post_types, $args );
	}

	protected function get_post_updated_messages( $constant_key )
	{
		return gEditorialHelper::generatePostTypeMessages( $this->get_noop( $constant_key ) );
	}

	public function get_image_sizes( $post_type )
	{
		if ( ! isset( $this->image_sizes[$post_type] ) ) {

			$sizes = apply_filters( 'geditorial_'.$this->module->name.'_'.$post_type.'_image_sizes', array() );

			if ( FALSE === $sizes ) {
				$this->image_sizes[$post_type] = array(); // no sizes

			} else if ( count( $sizes ) ) {
				$this->image_sizes[$post_type] = $sizes; // custom sizes

			} else {
				foreach ( gEditorialHelper::getWPImageSizes() as $size => $args )
					$this->image_sizes[$post_type][$post_type.'-'.$size] = $args;
			}
		}

		return $this->image_sizes[$post_type];
	}

	public function get_image_size_key( $constant_key, $size = 'thumbnail' )
	{
		$post_type = $this->constant( $constant_key );

		if ( isset( $this->image_sizes[$post_type][$post_type.'-'.$size] ) )
			return $post_type.'-'.$size;

		if ( isset( $this->image_sizes[$post_type]['post-'.$size] ) )
			return 'post-'.$size;

		return $size;
	}

	// use this on 'after_setup_theme'
	public function register_post_type_thumbnail( $constant_key )
	{
		$post_type = $this->constant( $constant_key );

		self::themeThumbnails( array( $post_type ) );

		foreach ( $this->get_image_sizes( $post_type ) as $name => $size )
			self::registerImageSize( $name, array_merge( $size, array( 'p' => array( $post_type ) ) ) );
	}

	// WARNING: every script must have a .min copy
	public function enqueue_asset_js( $args = array(), $name = NULL, $deps = array( 'jquery' ), $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->module->name;

		if ( TRUE === $args ) {
			$args = array();

		} else if ( ! is_array( $args ) ) {
			$name .= '.'.$args;
			$args = array();
		}

		$name = str_replace( '_', '-', $name );

		if ( is_null( $handle ) )
			$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

		$prefix = is_admin() ? 'admin.' : 'front.';
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			$handle,
			GEDITORIAL_URL.'assets/js/'.$this->base.'/'.$prefix.$name.$suffix.'.js',
			$deps,
			GEDITORIAL_VERSION,
			TRUE
		);

		gEditorial()->enqueue_asset_config( $args, $this->module->name );

		return $handle;
	}

	// combined global styles
	// CAUTION: front only
	// TODO: also we need api for module specified css
	public function enqueue_styles()
	{
		gEditorial()->enqueue_styles();
	}

	public function register_editor_button( $settings_key = 'editor_button' )
	{
		if ( ! $this->get_setting( $settings_key, TRUE ) )
			return;

		gEditorial()->register_editor_button( 'ge_'.$this->module->name, 'assets/js/geditorial/tinymce.'.$this->module->name.'.js' );
	}

	protected function register_shortcode( $constant_key, $callback = NULL )
	{
		if ( ! $this->get_setting( 'shortcode_support', TRUE ) )
			return;

		if ( is_null( $callback ) && method_exists( $this, $constant_key ) )
			$callback = array( $this, $constant_key );

		$shortcode = $this->constant( $constant_key );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( 'geditorial_shortcode_'.$shortcode, $callback, 10, 3 );
	}

	public function field_post_tax( $constant_key, $post, $key = FALSE, $count = TRUE, $excludes = '', $default = '0' )
	{
		$tax = $this->constant( $constant_key );
		if ( $obj = get_taxonomy( $tax ) ) {

			if ( $default && ! is_numeric( $default ) ) {
				if ( $default_term = get_term_by( 'slug', $default, $tax ) )
					$default = $default_term->term_id;
				else
					$default = '0';
			}

			if ( ! $selected = gEditorialWPTaxonomy::theTerm( $tax, $post->ID ) )
				$selected = $default;

			echo '<div class="field-wrap" title="'.esc_attr( $obj->labels->menu_name ).'">';

			wp_dropdown_categories( array(
				'taxonomy'          => $tax,
				'selected'          => $selected,
				'show_option_none'  => gEditorialSettingsCore::showOptionNone( $obj->labels->menu_name ),
				'option_none_value' => '0',
				'class'             => 'geditorial-admin-dropbown',
				'name'              => 'geditorial-'.$this->module->name.'-'.$tax.( FALSE === $key ? '' : '['.$key.']' ),
				'id'                => 'geditorial-'.$this->module->name.'-'.$tax.( FALSE === $key ? '' : '-'.$key ),
				'hierarchical'      => $obj->hierarchical,
				'orderby'           => 'name',
				'show_count'        => $count,
				'hide_empty'        => FALSE,
				'hide_if_empty'     => TRUE,
				'echo'              => TRUE,
				'exclude'           => $excludes,
			) );

			echo '</div>';
		}
	}

	public function field_post_order( $constant_key, $post )
	{
		$html = gEditorialHTML::tag( 'input', array(
			'type'        => 'number',
			'step'        => '1',
			'size'        => '4',
			'name'        => 'menu_order',
			'id'          => 'menu_order',
			'value'       => $post->menu_order,
			'title'       => _x( 'Order', 'Module Core: Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Order', 'Module Core: Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'small-text',
			'data'        => array(
				'ortho' => 'number',
			),
		) );

		echo gEditorialHTML::tag( 'div', array(
			'class' => array(
				'field-wrap',
				'field-wrap-inputnumber',
			),
		), $html );
	}

	public function field_post_parent( $constant_key, $post, $status = 'publish,private,draft' )
	{
		$pages = wp_dropdown_pages( array(
			'post_type'        => $this->constant( $constant_key ), // alows for parent of diffrent type
			'selected'         => $post->post_parent,
			'name'             => 'parent_id',
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => _x( '&mdash; no parent &mdash;', 'Module Core: MetaBox Parent Dropdown: Select Option None', GEDITORIAL_TEXTDOMAIN ),
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => $status,
			'exclude_tree'     => $post->ID,
			'echo'             => 0,
		));

		if ( $pages )
			echo gEditorialHTML::tag( 'div', array(
				'class' => 'field-wrap',
			), $pages );
	}

	// CAUTION: tax must be cat (hierarchical)
	// TODO: supporting tag (non-hierarchical)
	public function add_meta_box_checklist_terms( $constant_key, $post_type, $type = FALSE )
	{
		$taxonomy = $this->constant( $constant_key );
		$object   = get_taxonomy( $taxonomy );
		$manage   = current_user_can( $object->cap->manage_terms );
		$edit_url = $manage ? gEditorialWordPress::getEditTaxLink( $taxonomy ) : FALSE;

		if ( $type )
			$this->remove_meta_box( $constant_key, $post_type, $type );

		add_meta_box( 'geditorial-'.$this->module->name.'-'.$taxonomy,
			$this->get_meta_box_title( $constant_key, $edit_url, TRUE ),
			array( 'gEditorialMetaBox', 'checklistTerms' ),
			NULL,
			'side',
			'default',
			array(
				'taxonomy' => $taxonomy,
				'edit_url' => $edit_url,
			)
		);
	}

	public function add_meta_box_author( $constant_key, $callback = 'post_author_meta_box' )
	{
		$post_type = $this->constant( $constant_key );
		$object    = get_post_type_object( $post_type );

		if ( current_user_can( $object->cap->edit_others_posts ) ) {

			remove_meta_box( 'authordiv', $post_type, 'normal' );

			add_meta_box( 'authordiv',
				$this->get_string( 'author_box_title', $constant_key, 'misc', __( 'Author' ) ),
				$callback,
				NULL,
				'normal',
				'core'
			);
		}
	}

	public function add_meta_box_excerpt( $constant_key, $callback = 'post_excerpt_meta_box' )
	{
		$post_type = $this->constant( $constant_key );

		remove_meta_box( 'postexcerpt', $post_type, 'normal' );

		add_meta_box( 'postexcerpt',
			$this->get_string( 'excerpt_box_title', $constant_key, 'misc', __( 'Excerpt' ) ),
			$callback,
			$post_type,
			'normal',
			'high'
		);
	}

	public function remove_meta_box( $constant_key, $post_type, $type = 'tag' )
	{
		if ( 'tag' == $type )
			remove_meta_box( 'tagsdiv-'.$this->constant( $constant_key ), $post_type, 'side' );

		else if ( 'cat' == $type )
			remove_meta_box( $this->constant( $constant_key ).'div', $post_type, 'side' );

		else if ( 'parent' == $type )
			remove_meta_box( 'pageparentdiv', $post_type, 'side' );

		else if ( 'image' == $type )
			remove_meta_box( 'postimagediv', $this->constant( $constant_key ), 'side' );

		else if ( 'author' == $type )
			remove_meta_box( 'authordiv', $this->constant( $constant_key ), 'normal' );

		else if ( 'excerpt' == $type )
			remove_meta_box( 'postexcerpt', $post_type, 'normal' );
	}

	public function get_meta_box_title( $constant_key = 'post', $url = NULL, $edit_cap = 'manage_options', $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant_key, 'misc', _x( 'Settings', 'Module Core: MetaBox default title', GEDITORIAL_TEXTDOMAIN ) );

		if ( FALSE === $url )
			return $title;

		if ( TRUE === $edit_cap || current_user_can( $edit_cap ) ) {

			if ( is_null( $url ) )
				$url = $this->get_url_settings();

			$action = $this->get_string( 'meta_box_action', $constant_key, 'misc', _x( 'Configure', 'Module Core: MetaBox default action', GEDITORIAL_TEXTDOMAIN ) );
			$title .= ' <span class="postbox-title-action geditorial-postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant_key, $fallback = NULL )
	{
		return $this->get_string( $column.'_column_title', $constant_key, 'misc', ( is_null( $fallback ) ? $column : $fallback ) );
	}

	public function get_url_settings( $extra = array() )
	{
		return gEditorialWordPress::getAdminPageLink( $this->module->settings, $extra );
	}

	public function get_url_tax_edit( $constant_key, $term_id = FALSE, $extra = array() )
	{
		return gEditorialWordPress::getEditTaxLink( $this->constant( $constant_key ), $term_id, $extra );
	}

	public function get_url_post_edit( $constant_key, $extra = array(), $author_id = 0 )
	{
		return gEditorialWordPress::getPostTypeEditLink( $this->constant( $constant_key ), $author_id, $extra );
	}

	public function get_url_post_new( $constant_key, $extra = array() )
	{
		return gEditorialWordPress::getPostNewLink( $this->constant( $constant_key ), $extra );
	}

	protected function require_code( $filenames = 'templates' )
	{
		$module = $this->slug();

		foreach ( (array) $filenames as $filename )
			require_once( GEDITORIAL_DIR.'modules/'.$module.'/'.$filename.'.php' );
	}

	public function is_current_posttype( $constant_key )
	{
		return gEditorialWordPress::currentPostType() == $this->constant( $constant_key );
	}

	public function is_save_post( $post, $constant_key = FALSE )
	{
		if ( wp_is_post_autosave( $post ) )
			return FALSE;

		if ( wp_is_post_revision( $post ) )
			return FALSE;

		if ( empty( $_POST ) )
			return FALSE;

		if ( is_array( $constant_key )
			&& ! in_array( $post->post_type, $constant_key ) )
				return FALSE;

		if ( $constant_key
			&& ! is_array( $constant_key )
			&& $post->post_type != $this->constant( $constant_key ) )
				return FALSE;

		return TRUE;
	}

	// for ajax calls on quick edit
	public function is_inline_save( $request, $constant_key = FALSE )
	{
		if ( empty( $request['action'] )
			|| 'inline-save' != $request['action'] )
				return FALSE;

		if ( empty( $request['screen'] )
			|| empty( $request['post_type'] ) )
				return FALSE;

		if ( is_array( $constant_key )
			&& ! in_array( $request['post_type'], $constant_key ) )
				return FALSE;

		if ( $constant_key
			&& ! is_array( $constant_key )
			&& $request['post_type'] != $this->constant( $constant_key ) )
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
		if ( ! $term = gEditorialWPTaxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		update_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', $term->term_id );

		if ( function_exists( 'update_term_meta' ) )
			update_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', $post_id );

		return TRUE;
	}

	public function get_linked_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key )
	{
		if ( ! $term = gEditorialWPTaxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		$post_id = FALSE;

		if ( function_exists( 'get_term_meta' ) )
			$post_id = get_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', TRUE );

		if ( ! $post_id )
			$post_id = gEditorialWPPostType::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	public function get_linked_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );

		$args = array(
			'tax_query' => array( array(
				'taxonomy' => $this->constant( $tax_constant_key ),
				'field'    => 'id',
				'terms'    => array( $term_id )
			) ),
			'post_type'   => $this->post_types(),
			'numberposts' => -1,
		);

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
			wp_update_term( $term->term_id, $this->constant( $taxonomy_constant_key ), array(
				'name' => $term->name.'___TRASHED',
				'slug' => $term->slug.'-trashed',
			) );
		}
	}

	protected function do_untrash_post( $post_id, $posttype_constant_key, $taxonomy_constant_key )
	{
		if ( $term = $this->get_linked_term( $post_id, $posttype_constant_key, $taxonomy_constant_key ) ) {
			wp_update_term( $term->term_id, $this->constant( $taxonomy_constant_key ), array(
				'name' => str_ireplace( '___TRASHED', '', $term->name ),
				'slug' => str_ireplace( '-trashed', '', $term->slug ),
			) );
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

			foreach ( (array) $taxes as $constant_key ) {

				$tax = $this->constant( $constant_key );
				if ( $obj = get_taxonomy( $tax ) ) {

					$selected = isset( $wp_query->query[$tax] ) ? $wp_query->query[$tax] : '';

					// if selected is term_id instead of term slug
					if ( $selected && '-1' != $selected && is_numeric( $selected ) ) {

						if ( $term = get_term_by( 'id', $selected, $tax ) )
							$selected = $term->slug;

						else
							$selected = '';
					}

					wp_dropdown_categories( array(
						'show_option_all'  => $this->get_string( 'show_option_all', $constant_key, 'misc', $obj->labels->all_items ),
						'show_option_none' => $this->get_string( 'show_option_none', $constant_key, 'misc', '('.$obj->labels->no_terms.')' ),
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
					) );
				}
			}
		}
	}

	protected function do_restrict_manage_posts_posts( $tax_constant_key, $posttype_constant_key )
	{
		$tax_obj = get_taxonomy( $tax = $this->constant( $tax_constant_key ) );

		wp_dropdown_pages( array(
			'post_type'        => $this->constant( $posttype_constant_key ),
			'selected'         => isset( $_GET[$tax] ) ? $_GET[$tax] : '',
			'name'             => $tax,
			'class'            => 'geditorial-admin-dropbown',
			'show_option_none' => $tax_obj->labels->all_items,
			'sort_column'      => 'menu_order',
			'sort_order'       => 'desc',
			'post_status'      => array( 'publish', 'future', 'draft', 'pending' ),
			'value_field'      => 'post_name',
			'walker'           => new gEditorial_Walker_PageDropdown(),
		));
	}

	protected function do_parse_query_taxes( &$query, $taxes, $posttype_constant_key = TRUE )
	{
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant_key ) {

				$tax = $this->constant( $constant_key );

				if ( isset( $query->query_vars[$tax] ) ) {

					if ( '-1' == $query->query_vars[$tax] ) {

						$query->query_vars['tax_query'] = array( array(
							'taxonomy' => $tax,
							'operator' => 'NOT EXISTS',
						) );

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

			foreach ( $taxes as $constant_key ) {
				$tax = $this->constant( $constant_key );

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
		$text   = gEditorialHelper::noopedCount( $posts->publish, $this->get_noop( $posttype_constant_key ) );

		return sprintf( $format, gEditorialNumber::format( $posts->publish ), $text, $posttype );
	}

	protected function dashboard_glance_tax( $tax_constant_key, $edit_cap = 'manage_categories' )
	{
		$taxonomy = $this->constant( $tax_constant_key );
		$terms    = wp_count_terms( $taxonomy );

		if ( ! $terms )
			return FALSE;

		$class  = 'geditorial-glance-item -'.$this->slug().' -tax -taxonomy-'.$taxonomy;
		$format = current_user_can( $edit_cap ) ? '<a class="'.$class.'" href="edit-tags.php?taxonomy=%3$s">%1$s %2$s</a>' : '<div class="'.$class.'">%1$s %2$s</div>';
		$text   = gEditorialHelper::noopedCount( $terms, $this->get_noop( $tax_constant_key ) );

		return sprintf( $format, gEditorialNumber::format( $terms ), $text, $taxonomy );
	}

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL )
	{
		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( is_null( $title ) )
			$title = $this->get_string( 'column_icon_title', $icon, 'misc', '' );

		return gEditorialHTML::tag( ( $link ? 'a' : 'span' ), array(
			'href'   => $link ? $link : FALSE,
			'title'  => $title ? $title : FALSE,
			'class'  => array( '-icon', ( $link ? '-link' : '-info' ) ),
			'target' => $link ? '_blank' : FALSE,
		), gEditorialHTML::getDashicon( $icon ) );
	}

	public function column_thumb( $post_id, $size = array( 45, 72 ) )
	{
		echo gEditorialWordPress::getFeaturedImageHTML( $post_id, $size );
	}

	// TODO: override $title_attr based on passed constant key
	public function column_count( $count, $title_attr = NULL )
	{
		echo gEditorialHelper::htmlCount( $count, $title_attr );
	}

	public function column_term( $object_id, $tax_constant_key, $title_attr = NULL, $single = TRUE )
	{
		$the_terms = wp_get_object_terms( $object_id, $this->constant( $tax_constant_key ) );

		if ( ! is_wp_error( $the_terms ) && count( $the_terms ) ) {

			if ( $single ) {
				echo $the_terms[0]->name;

			} else {

				$terms = array();

				foreach ( $the_terms as $the_term )
					$terms[] = $the_term->name;

				echo gEditorialHelper::getJoined( $terms );
			}

		} else {

			if ( is_null( $title_attr ) )
				$title_attr = _x( 'No Term', 'Module Core: No Count Term Attribute', GEDITORIAL_TEXTDOMAIN );

			printf( '<span title="%s" class="column-term-empty">&mdash;</span>', $title_attr );
		}
	}

	// adds the module enabled class to body in admin
	public function _admin_enabled()
	{
		add_action( 'admin_body_class', array( $this, 'admin_body_class_enabled' ) );
	}

	public function admin_body_class_enabled( $classes )
	{
		return ' '.$this->classs( 'enabled' ).$classes;
	}

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki/Connection-information
	public function p2p_register( $constant_key, $post_types = NULL )
	{
		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		$to  = $this->constant( $constant_key );
		$p2p = $this->constant( $constant_key.'_p2p' );

		$args = array_merge( array(
			'name'         => $p2p,
			'from'         => $post_types,
			'to'           => $to,
			'admin_column' => 'from', // 'any', 'from', 'to', FALSE
			'admin_box'    => array(
				'show'    => 'from',
				'context' => 'advanced',
			),
		), $this->strings['p2p'][$constant_key] );

		$hook = 'geditorial_'.$this->module->name.'_'.$to.'_p2p_args';

		if ( $args = apply_filters( $hook, $args, $post_types ) )
			if ( p2p_register_connection_type( $args ) )
				$this->p2p = $p2p;
	}

	public function p2p_get_meta( $p2p_id, $meta_key, $before = '', $after = '', $title = FALSE )
	{
		if ( $meta = p2p_get_meta( $p2p_id, $meta_key, TRUE ) ) {

			$html = apply_filters( 'string_format_i18n', $meta );

			if ( $title )
				$html = '<span title="'.esc_attr( $title ).'">'.$html.'</span>';

			return $before.$html.$after;
		}
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( FALSE !== $posttypes ) {

			if ( ! in_the_loop() || ! is_main_query() )
				return;

			if ( is_null( $posttypes ) )
				$posttypes = $this->post_types();

			if ( ! is_singular( $posttypes ) )
				return;
		}

		if ( $before = $this->get_setting( 'before_content', FALSE ) )
			echo '<div class="geditorial-wrap -'.$this->module->name.' -content-before">'
				.do_shortcode( $before ).'</div>';
	}

	public function content_after( $content, $posttypes = NULL )
	{
		if ( FALSE !== $posttypes ) {

			if ( ! in_the_loop() || ! is_main_query() )
				return;

			if ( is_null( $posttypes ) )
				$posttypes = $this->post_types();

			if ( ! is_singular( $posttypes ) )
				return;
		}

		if ( $after = $this->get_setting( 'after_content', FALSE ) )
			echo '<div class="geditorial-wrap -'.$this->module->name.' -content-after">'
				.do_shortcode( $after ).'</div>';
	}

	// DEFAULT FILTER
	public function get_default_comment_status( $status, $post_type, $comment_type )
	{
		return $this->get_setting( 'comment_status', $status );
	}
}

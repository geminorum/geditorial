<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialModuleCore extends gEditorialBaseCore
{

	public $module;
	public $options;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

	protected $cookie     = 'geditorial';
	protected $field_type = 'meta';
	protected $tools_cap  = 'import';

	protected $priority_init = 10;

	protected $constants = array();
	protected $strings   = array();
	protected $supports  = array();
	protected $fields    = array();

	protected $post_types_excluded = array();
	protected $taxonomies_excluded = array();
	protected $kses_allowed        = array();
	protected $settings_buttons    = array();
	protected $image_sizes         = array();
	protected $errors              = array();

	protected $geditorial_meta = FALSE; // META ENABLED?
	protected $root_key        = FALSE; // ROOT CONSTANT

	public function __construct( &$module, &$options )
	{
		$this->module  = $module;
		$this->options = $options;

		if ( $this->remote() )
			$this->setup_remote();

		else
			$this->setup();

		if ( self::isAJAX() && method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax( $_REQUEST );
	}

	// DEFAULT METHOD
	public static function module()
	{
		return array();
	}

	public function setup_remote( $partials = array() )
	{
		foreach ( $partials as $partial )
			$this->require_code( $partial );
	}

	public function setup( $partials = array() )
	{
		if ( method_exists( $this, 'p2p_init' ) )
			add_action( 'p2p_init', array( $this, 'p2p_init' ) );

		if ( method_exists( $this, 'widgets_init' ) )
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		if ( method_exists( $this, 'tinymce_strings' ) )
			add_filter( 'geditorial_tinymce_strings', array( $this, 'tinymce_strings' ) );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( 'geditorial_meta_init', array( $this, 'meta_init' ) );

		if ( method_exists( $this, 'tweaks_strings' ) )
			add_filter( 'geditorial_tweaks_strings', array( $this, 'tweaks_strings' ) );

		if ( method_exists( $this, 'meta_post_types' ) )
			add_filter( 'geditorial_meta_support_post_types', array( $this, 'meta_post_types' ) );

		if ( method_exists( $this, 'gpeople_support' ) )
			add_filter( 'gpeople_remote_support_post_types', array( $this, 'gpeople_support' ) );

		foreach ( $partials as $partial )
			$this->require_code( $partial );

		add_action( 'after_setup_theme', array( $this, 'after_setup_theme_early' ), 1 );

		if ( method_exists( $this, 'after_setup_theme' ) )
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 20 );

		add_action( 'init', array( $this, 'init' ), $this->priority_init );

		if ( is_admin() ) {

			if ( method_exists( $this, 'admin_init' ) )
				add_action( 'admin_init', array( $this, 'admin_init' ) );

			if ( method_exists( $this, 'dashboard_glance_items' ) )
				add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ) );

			if ( method_exists( $this, 'current_screen' ) )
				add_action( 'current_screen', array( $this, 'current_screen' ) );

			add_action( 'geditorial_settings_load', array( $this, 'register_settings' ) );

			if ( method_exists( $this, 'tools_settings' ) )
				add_action( 'geditorial_tools_settings', array( $this, 'tools_settings' ) );
		}
	}

	public function after_setup_theme_early()
	{
		$this->settings  = apply_filters( 'geditorial_'.$this->module->name.'_settings', $this->get_global_settings(), $this->module );
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
		return gEditorialHelper::settingsHelpContent( $this->module );
	}

	protected function settings_help_sidebar()
	{
		return gEditorialHelper::settingsHelpLinks( $this->module );
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

	// applicable post types for this module
	public function all_post_types()
	{
		$registered = get_post_types( array(
			'_builtin' => FALSE,
			'public'   => TRUE,
		), 'objects' );

		$post_types = array(
			'post' => _x( 'Posts', 'Module Core', GEDITORIAL_TEXTDOMAIN ),
			'page' => _x( 'Pages', 'Module Core', GEDITORIAL_TEXTDOMAIN ),
		);

		foreach ( $registered as $post_type => $args )
			$post_types[$post_type] = $args->label;

		if ( count( $this->post_types_excluded ) )
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
		$tax_list = get_taxonomies( array(
			// 'show_ui' => TRUE,
		), 'objects' );

		$taxonomies = array();

		foreach ( $tax_list as $tax => $tax_obj )
		$taxonomies[$tax] = $tax_obj->label;

		if ( count( $this->taxonomies_excluded ) )
			$taxonomies = array_diff_key( $taxonomies, array_flip( $this->taxonomies_excluded ) );

		return $taxonomies;
	}

	public function settings_posttypes_option( $section )
	{
		foreach ( $this->all_post_types() as $post_type => $label ) {
			$html = self::html( 'input', array(
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$post_type,
				'name'    => $this->module->group.'[post_types]['.$post_type.']',
				'checked' => isset( $this->options->post_types[$post_type] ) && $this->options->post_types[$post_type],
			) );

			echo '<p>'.self::html( 'label', array(
				'for' => 'type-'.$post_type,
			), $html.'&nbsp;'.esc_html( $label ).' &mdash; <code>'.$post_type.'</code>' ).'</p>';
		}
	}

	public function settings_taxonomies_option( $section )
	{
		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = self::html( 'input', array(
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->module->group.'[taxonomies]['.$taxonomy.']',
				'checked' => isset( $this->options->taxonomies[$taxonomy] ) && $this->options->taxonomies[$taxonomy],
			) );

			echo '<p>'.self::html( 'label', array(
				'for' => 'tax-'.$taxonomy,
			), $html.'&nbsp;'.esc_html( $label ).' &mdash; <code>'.$taxonomy.'</code>' ).'</p>';
		}
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
			$title = _x( 'Enable for Post Types', 'Module Core', GEDITORIAL_TEXTDOMAIN );

		$section = $this->module->group.'_posttypes';

		add_settings_section( $section, FALSE, '__return_false', $this->module->group );
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
			$title = _x( 'Enable for Taxonomies', 'Module Core', GEDITORIAL_TEXTDOMAIN );

		$section = $this->module->group.'_taxonomies';

		add_settings_section( $section, FALSE, '__return_false', $this->module->group );
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

				add_settings_section( $section,
					sprintf( $title, $all[$post_type] ),
					'__return_false',
					$this->module->group
				);

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

				add_settings_section( $section,
					sprintf( $title, $all[$post_type] ),
					array( $this, 'settings_fields_option_none' ),
					$this->module->group
				);
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
		else if ( isset( $this->module->defaults['fields'][$args['post_type']][$args['field']] ) )
			$value = $this->module->defaults['fields'][$args['post_type']][$args['field']];
		else
			$value = FALSE;

		$html = self::html( 'input', array(
			'type'    => 'checkbox',
			'value'   => 'enabled',
			'class'   => 'fields-check',
			'name'    => $name,
			'id'      => $id,
			'checked' => $value,
		) );

		echo '<div>'.self::html( 'label', array(
			'for' => $id,
		), $html.'&nbsp;'.$args['field_title'] );

		if ( $args['description'] )
			echo self::html( 'p', array(
				'class' => 'description',
			), $args['description'] );

		echo '</div>';
	}

	public function settings_fields_option_all( $args )
	{
		$html = self::html( 'input', array(
			'type'  => 'checkbox',
			'class' => 'fields-check-all',
			'id'    => $args['post_type'].'_fields_all',
		) );

		echo self::html( 'label', array(
			'for' => $args['post_type'].'_fields_all',
		), $html.'&nbsp;<span class="description">'._x( 'Select All Fields', 'Module Core', GEDITORIAL_TEXTDOMAIN ).'</span>' );
	}

	public function settings_fields_option_none( $args )
	{
		echo self::html( 'p', array(
			'class' => 'description no-fields',
		), _x( 'No fields supported', 'Module Core', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function print_configure_view()
	{
		echo '<form action="'.$this->get_url_settings().'" method="post">';

			settings_fields( $this->module->group );
			do_settings_sections( $this->module->group );
			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.esc_attr( $this->module->name ).'" />';

			echo '<p class="submit">';

				foreach ( $this->settings_buttons as $action => $button ) {
					submit_button( $button['value'], $button['type'], $action, FALSE, $button['atts'] );
					echo '&nbsp;&nbsp;';
				}

			echo '<a class="button" href="'.gEditorialHelper::settingsURL().'">'
				._x( 'Back to Editorial', 'Module Core', GEDITORIAL_TEXTDOMAIN ).'</a></p>';

		echo '</form>';

		if ( self::isDev() ) {
			// @self::dump( $this->module );
			@self::dump( $this->options );
			// @self::dump( $this->strings );
			// @self::dump( get_all_post_type_supports( 'reshare' ) );
			// @self::dump( $this->post_type_supports( 'reshare', 'meta_fields', FALSE ) );
		}
	}

	public function register_settings_button( $key, $value, $atts = array(), $type = 'secondary' )
	{
		$this->settings_buttons[$key] = array(
			'value' => $value,
			'atts'  => $atts,
			'type'  => $type,
		);
	}

	// DEFAULT METHOD
	public function tools_subs( $subs )
	{
		$subs[$this->module->name] = $this->module->title;
		return $subs;
	}

	// HELPER
	protected function tools_field_referer( $sub = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->module->name;

		wp_nonce_field( 'geditorial-tools-'.$sub );
	}

	// HELPER
	protected function tools_check_referer( $sub = NULL )
	{
		if ( is_null( $sub ) )
			$sub = $this->module->name;

		check_admin_referer( 'geditorial-tools-'.$sub );
	}

	public function settings_validate( $options )
	{
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

		return $options;
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
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'tax'         => FALSE,
				'group'       => 10,
				'order'       => 10+$i,
			), ( isset( $all[$field] ) && is_array( $all[$field] ) ? $all[$field] : array() ) );

		// @REF: http://stackoverflow.com/a/4582659
		if ( $sort && count( $fields ) ) {

			foreach ( $fields as $field => $args ) {
				$group[$field] = $args['group'];
				$order[$field] = $args['order'];
			}

			array_multisort( $group, SORT_ASC, $order, SORT_ASC, $fields );
		}

		return $fields;
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

	public function nooped( $constant_key, $count, $domain = GEDITORIAL_TEXTDOMAIN )
	{
		$nooped = $this->get_noop( $constant_key );

		if ( $nooped['context'] )
			return _nx( $nooped['singular'], $nooped['plural'], $count, $nooped['context'], $domain );
		else
			return _n( $nooped['singular'], $nooped['plural'], $count, $domain );
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

		$added = self::insertDefaultTerms(
			$this->constant( $constant_key ),
			$this->strings['terms'][$constant_key]
		);

		self::redirect( add_query_arg( 'message', $added ? 'added_default_terms' : 'error_default_terms' ) );
	}

	public function get_settings_editor_button( $section )
	{
		return array(
			'field'   => 'editor_button',
			'title'   => _x( 'Editor Button', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '1',
			'section' => $section,
		);
	}

	public function get_settings_shortcode_support( $section )
	{
		return array(
			'field'   => 'shortcode_support',
			'title'   => _x( 'Default Shortcodes', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '1',
			'section' => $section,
		);
	}

	public function get_settings_markdown_support( $section )
	{
		return array(
			'field'   => 'markdown_support',
			'title'   => _x( 'Markdown Support', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
			'section' => $section,
		);
	}

	public function get_settings_multiple_instances( $section )
	{
		return array(
			'field'   => 'multiple_instances',
			'title'   => _x( 'Multiple Instances', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'default' => '0',
			'section' => $section,
		);
	}

	public function get_settings_rewrite_prefix( $section )
	{
		return array(
			'field'       => 'rewrite_prefix',
			'type'        => 'text',
			'title'       => _x( 'URL Base Prefix', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'String before the permalink structure', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '',
			'dir'         => 'ltr',
			'placeholder' => 'wiki',
			'section'     => $section,
		);
	}

	public function get_settings_redirect_archives( $section )
	{
		return array(
			'field'       => 'redirect_archives',
			'type'        => 'text',
			'title'       => _x( 'Redirect Archives', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Redirect Post Type Archives to a URL', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '',
			'dir'         => 'ltr',
			'placeholder' => 'http://example.com/archives/',
			'section'     => $section,
		);
	}

	public function get_settings_insert_content( $section )
	{
		return array(
			'field'       => 'insert_content',
			'type'        => 'select',
			'title'       => _x( 'Insert in Content', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Put html automatically on the content', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => 'none',
			'section'     => $section,
			'values'      => array(
				'none'   => _x( 'No', 'Module Core: Insert in Content Option', GEDITORIAL_TEXTDOMAIN ),
				'before' => _x( 'Before', 'Module Core: Insert in Content Option', GEDITORIAL_TEXTDOMAIN ),
				'after'  => _x( 'After', 'Module Core: Insert in Content Option', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function get_settings_before_content( $section )
	{
		return array(
			'field'       => 'before_content',
			'type'        => 'textarea',
			'title'       => _x( 'Before Content', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> content to the start of all the supported posttypes', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public function get_settings_after_content( $section )
	{
		return array(
			'field'       => 'after_content',
			'type'        => 'textarea',
			'title'       => _x( 'After Content', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Adds <code>HTML</code> content to the end of all the supported posttypes', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'section'     => $section,
		);
	}

	public function get_settings_posttype_feeds( $section )
	{
		return array(
			'field'       => 'posttype_feeds',
			'title'       => _x( 'Feeds', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting feeds on the posttype', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
		);
	}

	public function get_settings_posttype_pages( $section )
	{
		return array(
			'field'       => 'posttype_pages',
			'title'       => _x( 'Pages', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'description' => _x( 'Supporting pagination on the posttype', 'Module Core: Setting Description', GEDITORIAL_TEXTDOMAIN ),
			'default'     => '0',
			'section'     => $section,
		);
	}

	public function get_settings_calendar_type( $section )
	{
		return array(
			'field'   => 'calendar_type',
			'title'   => _x( 'Default Calendar', 'Module Core: Setting Title', GEDITORIAL_TEXTDOMAIN ),
			'type'    => 'select',
			'default' => 'gregorian',
			'values'  => gEditorialHelper::getDefualtCalendars( TRUE ),
			'section' => $section,
		);
	}

	public function is_register_settings( $page )
	{
		if ( isset( $this->settings ) && $page == $this->module->settings )
			return TRUE;

		return FALSE;
	}

	public function register_settings( $page = NULL )
	{
		if ( ! $this->is_register_settings( $page ) )
			return;

		foreach ( $this->settings as $section_suffix => $fields ) {
			if ( is_array( $fields ) ) {

				$section = $this->module->group.$section_suffix;
				add_settings_section( $section, FALSE, '__return_false', $this->module->group );

				foreach ( $fields as $field ) {

					if ( is_array( $field ) )
						$args = array_merge( $field, array( 'section' => $section ) );

					else if ( method_exists( $this, 'get_settings_'.$field ) )
						$args = call_user_func_array( array( $this, 'get_settings_'.$field ), array( $section ) );

					else
						continue;

					$this->add_settings_field( $args );
				}

			// for pre internal custom options
			// } else if ( is_callable( array( $this, 'register_settings_'.$section_suffix ) ) ) {
			} else if ( method_exists( $this, 'register_settings_'.$section_suffix ) ) {
				$title = $section_suffix == $fields ? NULL : $fields;
				call_user_func_array( array( $this, 'register_settings_'.$section_suffix ), array( $title ) );
			}
		}

		$this->register_settings_button( 'submit', _x( 'Save Changes', 'Module Core', GEDITORIAL_TEXTDOMAIN ), array( 'default' => 'default' ), 'primary' );
		$this->register_settings_button( 'reset-settings', _x( 'Reset Settings', 'Module Core', GEDITORIAL_TEXTDOMAIN ), sprintf( 'onclick="return confirm( \'%s\' )"', _x( 'Are you sure? This operation can not be undone.', 'Module Core', GEDITORIAL_TEXTDOMAIN ) ) );

		$screen = get_current_screen();

		if ( method_exists( $this, 'settings_help_tabs' ) )
			foreach ( $this->settings_help_tabs() as $tab )
				$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar() )
			$screen->set_help_sidebar( $sidebar );
	}

	protected function settings_footer( $module )
	{
		if ( 'settings' == $module->name )
			gEditorialHelper::settingsCredits();
	}

	protected function settings_signature( $module = NULL )
	{
		gEditorialHelper::settingsSignature();
		// echo '<div class="clear"></div></div>';
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

	public function do_settings_field( $r = array() )
	{
		$args = self::atts( array(
			'type'        => 'enabled',
			'field'       => FALSE,
			'values'      => array(),
			'exclude'     => '',
			'filter'      => FALSE, // will use via sanitize
			'dir'         => FALSE,
			'disabled'    => FALSE,
			'default'     => '',
			'description' => '',
			'before'      => '', // html to print before field
			'after'       => '', // html to print after field
			'field_class' => '', // formally just class!
			'class'       => '', // now used on wrapper
			'name_group'  => 'settings',
			'name_attr'   => FALSE, // override
			'id_attr'     => FALSE, // override
			'placeholder' => FALSE,
		), $r );

		if ( ! $args['field'] )
			return;

		$html    = '';
		$id      = $args['id_attr'] ? $args['id_attr'] : $this->module->group.'-'.$args['name_group'].'-'.$args['field'];
		$name    = $args['name_attr'] ? $args['name_attr'] : $this->module->group.'['.$args['name_group'].']['.$args['field'].']';
		$exclude = $args['exclude'] && ! is_array( $args['exclude'] ) ? array_filter( explode( ',', $args['exclude'] ) ) : array();

		if ( isset( $this->options->settings[$args['field']] ) )
			$value = $this->options->settings[$args['field']];
		else if ( ! empty( $args['default'] ) )
			$value = $args['default'];
		else if ( isset( $this->module->defaults['settings'][$args['field']] ) )
			$value = $this->module->defaults['settings'][$args['field']];
		else
			$value = NULL;

		if ( $args['before'] )
			echo $args['before'].'&nbsp;';

		switch ( $args['type'] ) {

			case 'enabled' :

				$html = self::html( 'option', array(
					'value'    => '0',
					'selected' => '0' == $value,
				), ( isset( $args['values'][0] ) ? $args['values'][0] : _x( 'Disabled', 'Module Core: Settings Field Option', GEDITORIAL_TEXTDOMAIN ) ) );

				$html .= self::html( 'option', array(
					'value'    => '1',
					'selected' => '1' == $value,
				), ( isset( $args['values'][1] ) ? $args['values'][1] : _x( 'Enabled', 'Module Core: Settings Field Option', GEDITORIAL_TEXTDOMAIN ) ) );

				echo self::html( 'select', array(
					'class' => $args['field_class'],
					'name'  => $name,
					'id'    => $id,
				), $html );

			break;
			case 'text' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'regular-text';

				echo self::html( 'input', array(
					'type'        => 'text',
					'class'       => $args['field_class'],
					'name'        => $name,
					'id'          => $id,
					'value'       => $value,
					'dir'         => $args['dir'],
					'disabled'    => $args['disabled'],
					'placeholder' => $args['placeholder'],
				) );

			break;
			case 'number' :

				if ( ! $args['field_class'] )
					$args['field_class'] = 'small-text';

				if ( ! $args['dir'] )
					$args['dir'] = 'ltr';

				echo self::html( 'input', array(
					'type'        => 'number',
					'class'       => $args['field_class'],
					'name'        => $name,
					'id'          => $id,
					'value'       => $value,
					'step'        => '1', // FIXME: get from args
					'min'         => '0', // FIXME: get from args
					'dir'         => $args['dir'],
					'disabled'    => $args['disabled'],
					'placeholder' => $args['placeholder'],
				) );

			break;
			case 'checkbox' :

				if ( count( $args['values'] ) ) {
					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= self::html( 'input', array(
							'type'    => 'checkbox',
							'class'   => $args['field_class'],
							'name'    => $name.'['.$value_name.']',
							'id'      => $id.'-'.$value_name,
							'value'   => '1',
							'checked' => in_array( $value_name, ( array ) $value ),
							'dir'     => $args['dir'],
						) );

						echo '<p>'.self::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}

				} else {

					$html = self::html( 'input', array(
						'type'    => 'checkbox',
						'class'   => $args['field_class'],
						'name'    => $name,
						'id'      => $id,
						'value'   => '1',
						'checked' => $value,
						'dir'     => $args['dir'],
					) );

					echo '<p>'.self::html( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.$args['description'] ).'</p>';

					$args['description'] = FALSE;
				}

			break;
			case 'select' :

				if ( FALSE !== $args['values'] ) { // alow hiding
					foreach ( $args['values'] as $value_name => $value_title ) {

						if ( in_array( $value_name, $exclude ) )
							continue;

						$html .= self::html( 'option', array(
							'value'    => $value_name,
							'selected' => $value == $value_name,
						), esc_html( $value_title ) );
					}

					echo self::html( 'select', array(
						'class' => $args['field_class'],
						'name'  => $name,
						'id'    => $id,
					), $html );
				}

			break;
			case 'textarea' :

				echo self::html( 'textarea', array(
					'class' => array(
						'large-text',
						// 'textarea-autosize',
						$args['field_class'],
					),
					'name'  => $name,
					'id'    => $id,
					'rows'  => 5,
					'cols'  => 45,
				// ), esc_textarea( $value ) );
				), $value );

			break;
			case 'page' :

				if ( ! $args['values'] )
					$args['values'] = 'page';

				wp_dropdown_pages( array(
					'post_type'        => $args['values'],
					'selected'         => $value,
					'name'             => $name,
					'id'               => $id,
					'class'            => $args['field_class'],
					'exclude'          => implode( ',', $exclude ),
					'show_option_none' => _x( '&mdash; Select Page &mdash;', 'Module Core: WP Dropdown Pages Option None', GEDITORIAL_TEXTDOMAIN ),
					'sort_column'      => 'menu_order',
					'sort_order'       => 'asc',
					'post_status'      => 'publish,private,draft',
				));

			break;
			case 'button' :

				submit_button(
					$value,
					( empty( $args['field_class'] ) ? 'secondary' : $args['field_class'] ),
					$id,
					FALSE
				);

			break;
			case 'file' :

				echo self::html( 'input', array(
					'type'  => 'file',
					'class' => $args['field_class'],
					'name'  => $id, // $name,
					'id'    => $id,
					// 'value' => $value,
					'dir'   => $args['dir'],
				) );

			break;
			case 'custom' :

				if ( ! is_array( $args['values'] ) )
					echo $args['values'];
				else
					echo $value;

			break;
			case 'debug' :

				self::dump( $this->options );

			break;
			default :

				_ex( 'Error: settings type undefined.', 'Module Core', GEDITORIAL_TEXTDOMAIN );
		}

		if ( $args['after'] )
			echo '&nbsp;'.$args['after'];

		if ( $args['description'] && FALSE !== $args['values'] )
			echo self::html( 'p', array(
				'class' => 'description',
			), $args['description'] );
	}

	public function get_setting( $field, $default = NULL )
	{
		if ( isset( $this->options->settings[$field] ) )
			return $this->options->settings[$field];

		else if ( isset( $this->module->defaults['settings'][$field] ) )
			return $this->module->defaults['settings'][$field];

		else
			return $default;
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

	public function get_post_type_labels( $constant_key )
	{
		if ( ! empty( $this->strings['labels'][$constant_key] ) )
			return $this->strings['labels'][$constant_key];

		if ( ! empty( $this->strings['noops'][$constant_key] ) )
			return gEditorialHelper::generatePostTypeLabels( $this->strings['noops'][$constant_key],
				$this->get_string( 'featured', $constant_key, 'misc', FALSE ) );

		return array();
	}

	public function register_post_type( $constant_key, $atts = array(), $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$post_type = $this->constant( $constant_key );

		$args = self::recursiveParseArgs( $atts, array(
			'taxonomies'  => $taxonomies,
			'labels'      => $this->get_post_type_labels( $constant_key ),
			'description' => isset( $this->strings['labels'][$constant_key]['description'] ) ? $this->strings['labels'][$constant_key]['description'] : '',
			// FIXME: check every module
			'register_meta_box_cb'  => method_exists( $this, 'add_meta_box_cb_'.$constant_key ) ? array( $this, 'add_meta_box_cb_'.$constant_key ) : NULL,
			'menu_icon'   => $this->module->dashicon ? 'dashicons-'.$this->module->dashicon : 'dashicons-welcome-write-blog',
			'supports'    => isset( $this->supports[$constant_key] ) ? $this->supports[$constant_key] : array( 'title', 'editor' ),
			'has_archive' => $this->constant( $constant_key.'_archive', FALSE ),
			'query_var'   => $post_type,
			'rewrite'     => array(
				'slug'       => $this->constant( $constant_key.'_slug', $post_type ),
				'with_front' => FALSE,
				'feeds'      => TRUE,
				'pages'      => TRUE,
				'ep_mask'    => EP_PERMALINK, // https://make.wordpress.org/plugins?p=29
			),
			'capability_type' => 'post',
			'hierarchical'    => FALSE,
			'public'          => TRUE,
			'show_ui'         => TRUE,
			'map_meta_cap'    => TRUE,
			'can_export'      => TRUE,
			'menu_position'   => 4,

			// SEE: https://github.com/torounit/custom-post-type-permalinks
			'cptp_permalink_structure' => $this->constant( $constant_key.'_permalink', '/%post_id%' ),
			// Only `%post_id%` and `%postname%` | SEE: https://github.com/torounit/simple-post-type-permalinks
			'sptp_permalink_structure' => $this->constant( $constant_key.'_permalink', '/%post_id%' ),
		) );

		register_post_type( $post_type, $args );
	}

	public function get_taxonomy_labels( $constant_key )
	{
		if ( ! empty( $this->strings['labels'][$constant_key] ) )
			return $this->strings['labels'][$constant_key];

		if ( ! empty( $this->strings['noops'][$constant_key] ) )
			return gEditorialHelper::generateTaxonomyLabels( $this->strings['noops'][$constant_key] );

		return array();
	}

	public function register_taxonomy( $constant_key, $atts = array(), $post_types = NULL )
	{
		if ( is_null( $post_types ) )
			$post_types = $this->post_types();
		else if ( ! is_array( $post_types ) )
			$post_types = array( $this->constant( $post_types ) );

		$taxonomy = $this->constant( $constant_key );

		$args = self::recursiveParseArgs( $atts, array(
			'labels'                => $this->get_taxonomy_labels( $constant_key ),
			'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
			// FIXME: meta_box_cb default must be FALSE / check every module before!
			'meta_box_cb'           => method_exists( $this, 'meta_box_cb_'.$constant_key ) ? array( $this, 'meta_box_cb_'.$constant_key ) : NULL,
			'hierarchical'          => FALSE,
			'show_tagcloud'         => FALSE,
			'public'                => TRUE,
			'show_ui'               => TRUE,
			'show_in_quick_edit'    => FALSE, // FIXME: check this for all taxes
			'query_var'             => $taxonomy,
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
		) );

		register_taxonomy( $taxonomy, $post_types, $args );
	}

	protected function get_post_updated_messages( $constant_key )
	{
		global $post, $post_ID;

		$singular_name = isset( $this->strings['labels'][$constant_key]['singular_name'] )
			? $this->strings['labels'][$constant_key]['singular_name']
			: $this->constant( $constant_key );

		$singular_lower = self::strToLower( $singular_name );
		$link = get_permalink( $post_ID );

		return array(
			0  => '', // Unused. Messages start at index 1.

			1  => vsprintf( _x( '%1$s updated. <a href="%3$s">View %2$s</a>', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), array(
				$singular_name,
				$singular_lower,
				esc_url( $link ),
			) ),

			2  => _x( 'Custom field updated.', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ),
			3  => _x( 'Custom field deleted.', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ),

			4  => sprintf( _x( '%s updated.', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), $singular_name ),

			5  => isset( $_GET['revision'] ) ? vsprintf( _x( '%1$s restored to revision from %2$s', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), array(
				$singular_name,
				wp_post_revision_title( (int) $_GET['revision'], FALSE )
			) ) : FALSE,

			6  => vsprintf( _x( '%1$s published. <a href="%3$s">View %2$s</a>', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), array(
				$singular_name,
				$singular_lower,
				esc_url( $link ),
			) ),

			7  => sprintf( _x( '%s saved.', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), $singular_name ),

			8  => vsprintf( _x( '%1$s submitted. <a target="_blank" href="%3$s">Preview %2$s</a>', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), array(
				$singular_name,
				$singular_lower,
				esc_url( add_query_arg( 'preview', 'true', $link ) ),
			) ),

			9  => vsprintf( _x( '%1$s scheduled for: <strong>%4$s</strong>. <a target="_blank" href="%3$s">Preview %2$s</a>', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), array(
				$singular_name,
				$singular_lower,
				esc_url( $link ),
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ),
			) ),

			10 => vsprintf( _x( '%1$s draft updated. <a target="_blank" href="%3$s">Preview %2$s</a>', 'Module Core: Post Updated Messages', GEDITORIAL_TEXTDOMAIN ), array(
				$singular_name,
				$singular_lower,
				esc_url( add_query_arg( 'preview', 'true', $link ) ),
			) ),
		);
	}

	// SEE: [Use Chosen for a replacement WordPress taxonomy metabox](https://gist.github.com/helenhousandi/1573966)
	// callback for meta box for choose only tax
	public function meta_box_choose_tax( $post, $box )
	{
		$atts = isset( $box['args'] ) && is_array( $box['args'] ) ? $box['args'] : array();
		$args = wp_parse_args( $atts, array(
			'taxonomy' => 'category',
			'edit_url' => FALSE,
		) );

		$tax_name = esc_attr( $args['taxonomy'] );
		$taxonomy = get_taxonomy( $args['taxonomy'] );

		$html = wp_terms_checklist( $post->ID, array(
			'taxonomy' => $tax_name,
			'echo'     => FALSE,
		) );

		if ( $html ) {

			echo '<div id="taxonomy-'.$tax_name.'" class="geditorial-admin-wrap-metabox choose-tax">';

			// allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
			echo '<input type="hidden" name="tax_input['.$tax_name.'][]" value="0" />';

			echo '<div class="field-wrap-list"><ul>'.$html.'</ul></div></div>';

		} else if ( $args['edit_url']
			&& current_user_can( $taxonomy->cap->manage_terms ) ) {

				echo self::html( 'a', array(
					'href'   => $args['edit_url'],
					'title'  => $taxonomy->labels->menu_name,
					'class'  => 'add-new-item',
					'target' => '_blank',
				), $taxonomy->labels->add_new_item );
		}
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

	// use this on 'after_setup_theme'
	public function register_post_type_thumbnail( $constant_key )
	{
		$post_type = $this->constant( $constant_key );

		self::themeThumbnails( array( $post_type ) );

		foreach ( $this->get_image_sizes( $post_type ) as $name => $size )
			self::registerImageSize( $name, array_merge( $size, array( 'p' => array( $post_type ) ) ) );
	}

	// WARNING: every asset must have a .min copy
	public function enqueue_asset_js( $args = array(), $name = NULL, $deps = array( 'jquery' ), $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->module->name;

		$prefix = is_admin() ? 'admin.' : 'front.';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			( $handle ? $handle : 'geditorial-'.$name ),
			GEDITORIAL_URL.'assets/js/geditorial/'.$prefix.$name.$suffix.'.js',
			$deps,
			GEDITORIAL_VERSION, TRUE );

		if ( is_array( $args ) && ! count( $args ) )
			return TRUE;

		if ( TRUE === $args )
			$args = array();

		return gEditorial()->enqueue_asset_config( $args, $this->module->name );
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

			if ( ! $selected = gEditorialHelper::theTerm( $tax, $post->ID ) )
				$selected = $default;

			echo '<div class="field-wrap" title="'.esc_attr( $obj->labels->menu_name ).'">';

			wp_dropdown_categories( array(
				'taxonomy'          => $tax,
				'selected'          => $selected,
				'show_option_none'  => sprintf( _x( '&mdash; Select %s &mdash;', 'Module Core: MetaBox Tax Dropdown: Select Option None', GEDITORIAL_TEXTDOMAIN ), $obj->labels->menu_name ),
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
		$html = self::html( 'input', array(
			'type'        => 'number',
			'step'        => '1',
			'size'        => '4',
			'name'        => 'menu_order',
			'id'          => 'menu_order',
			'value'       => $post->menu_order,
			'title'       => _x( 'Order', 'Module Core: Title Attr', GEDITORIAL_TEXTDOMAIN ),
			'placeholder' => _x( 'Order', 'Module Core: Placeholder', GEDITORIAL_TEXTDOMAIN ),
			'class'       => 'small-text',
		) );

		echo self::html( 'div', array(
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
			echo self::html( 'div', array(
				'class' => 'field-wrap',
			), $pages );
	}

	// CAUTION: tax must be cat (hierarchical)
	// TODO: supporting tag (non-hierarchical)
	public function add_meta_box_choose_tax( $constant_key, $post_type, $type = 'cat' )
	{
		$tax = $this->constant( $constant_key );
		$edit_url = $this->get_url_tax_edit( $constant_key );

		$this->remove_meta_box( $constant_key, $post_type, $type );

		add_meta_box( 'geditorial-'.$this->module->name.'-'.$tax,
			$this->get_meta_box_title( $constant_key, $edit_url, 'edit_others_posts' ),
			array( $this, 'meta_box_choose_tax' ),
			NULL,
			'side',
			'default',
			array(
				'taxonomy' => $tax,
				'edit_url' => $edit_url,
			)
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

		if ( current_user_can( $edit_cap ) && FALSE !== $url ) {

			if ( is_null( $url ) )
				$url = $this->get_url_settings();

			$action = $this->get_string( 'meta_box_action', $constant_key, 'misc', _x( 'Configure', 'Module Core: MetaBox default action', GEDITORIAL_TEXTDOMAIN ) );
			$title .= ' <span class="geditorial-admin-action-metabox"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant_key )
	{
		return $this->get_string( $column.'_column_title', $constant_key, 'misc', $column );
	}

	public function get_url_settings()
	{
		return add_query_arg( 'page', $this->module->settings, admin_url( 'admin.php' ) );
	}

	public function get_url_tax_edit( $constant_key )
	{
		return self::getEditTaxLink( $this->constant( $constant_key ) );
	}

	public function get_url_post_edit( $constant_key )
	{
		return add_query_arg( 'post_type', $this->constant( $constant_key ), admin_url( 'edit.php' ) );
	}

	public static function redirect( $location, $status = 302 )
	{
		wp_redirect( $location, $status );
		exit();
	}

	protected function require_code( $filename = 'templates' )
	{
		require_once( GEDITORIAL_DIR.'modules/'.$this->module->name.'/'.$filename.'.php' );
	}

	public function is_current_posttype( $constant_key )
	{
		return self::getCurrentPostType() == $this->constant( $constant_key );
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

	public function get_linked_term( $post_id, $posttype_constant_key, $tax_constant_key )
	{
		$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );
		return get_term_by( 'id', intval( $term_id ), $this->constant( $tax_constant_key ) );
	}

	public function get_linked_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
	{
		if ( is_null( $term_id ) )
			$term_id = get_post_meta( $post_id, '_'.$this->constant( $posttype_constant_key ).'_term_id', TRUE );

		$items = get_posts( array(
			'tax_query' => array( array(
				'taxonomy' => $this->constant( $tax_constant_key ),
				'field'    => 'id',
				'terms'    => array( $term_id )
			) ),
			'post_type'   => $this->post_types(),
			'numberposts' => -1,
		) );

		if ( $count )
			return count( $items );

		return $items;
	}

	public function do_restrict_manage_posts_taxes( $taxes, $posttype_constant_key )
	{
		global $wp_query;

		if ( $this->is_current_posttype( $posttype_constant_key ) ) {
			foreach ( $taxes as $constant_key ) {

				$tax = $this->constant( $constant_key );
				if ( $obj = get_taxonomy( $tax ) ) {

					wp_dropdown_categories( array(
						'show_option_all' => $obj->labels->all_items,
						'taxonomy'        => $tax,
						'name'            => $obj->name,
						'orderby'         => 'name',
						'selected'        => ( isset( $wp_query->query[$tax] ) ? $wp_query->query[$tax] : '' ),
						'hierarchical'    => $obj->hierarchical,
						'depth'           => 3,
						'show_count'      => FALSE,
						'hide_empty'      => TRUE,
						'hide_if_empty'   => TRUE,
					) );
				}
			}
		}
	}

	public function do_parse_query_taxes( &$qv, $taxes, $posttype_constant_key )
	{
		if ( $this->is_current_posttype( $posttype_constant_key ) ) {
			foreach ( $taxes as $constant_key ) {
				$tax = $this->constant( $constant_key );
				if ( isset( $qv[$tax] )	&& is_numeric( $qv[$tax] ) ) {
					$term = get_term_by( 'id', $qv[$tax], $tax );
					if ( ! empty( $term ) && ! is_wp_error( $term ) )
						$qv[$tax] = $term->slug;
				}
			}
		}
	}

	protected function dashboard_glance_post( $posttype_constant_key, $edit_cap = 'edit_posts' )
	{
		$posttype = $this->constant( $posttype_constant_key );
		$template = current_user_can( $edit_cap ) ? '<a class="geditorial-glance-item -post -posttype-'.$posttype.'" href="edit.php?post_type=%3$s">%1$s %2$s</a>' : '%1$s %2$s';
		$posts    = wp_count_posts( $posttype );
		$singular = $this->nooped( $posttype_constant_key, 1 );
		$nooped   = $this->nooped( $posttype_constant_key, $posts->publish );

		$text = sprintf( _x( '%1$s', 'Module Core: At a Glance: Nooped String', GEDITORIAL_TEXTDOMAIN ), $nooped, $singular );

		return sprintf( $template, number_format_i18n( $posts->publish ), $text, $posttype );
	}

	public function column_thumb( $post_id, $size = array( 45, 72 ) )
	{
		echo self::getFeaturedImageHTML( $post_id, $size );
	}

	public function column_count( $count, $title_attr = NULL )
	{
		if ( is_null( $title_attr ) )
			$title_attr = _x( 'No Count', 'Module Core: No Count Title Attribute', GEDITORIAL_TEXTDOMAIN );

		if ( $count )
			echo number_format_i18n( $count );
		else
			printf( '<span title="%s" class="column-count-empty">&mdash;</span>', $title_attr );
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

				echo join( _x( ', ', 'Module Core: Term Seperator', GEDITORIAL_TEXTDOMAIN ), $terms );
			}

		} else {

			if ( is_null( $title_attr ) )
				$title_attr = _x( 'No Term', 'Module Core: No Count Term Attribute', GEDITORIAL_TEXTDOMAIN );

			printf( '<span title="%s" class="column-term-empty">&mdash;</span>', $title_attr );
		}
	}

	public function set_postmeta_field_string( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		self::__dep();

		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = $this->kses( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public function set_postmeta_field_number( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		self::__dep();

		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = $this->intval( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public function set_postmeta_field_url( &$postmeta, $field, $prefix = 'geditorial-meta-' )
	{
		self::__dep();

		if ( isset( $_POST[$prefix.$field] ) && strlen( $_POST[$prefix.$field] ) > 0 )
			$postmeta[$field] = esc_url( $_POST[$prefix.$field] );

		else if ( isset( $postmeta[$field] ) && isset( $_POST[$prefix.$field] ) )
			unset( $postmeta[$field] );
	}

	public function set_postmeta_field_term( $post_id, $field, $constant_key, $prefix = 'geditorial-meta-' )
	{
		self::__dep();

		if ( isset( $_POST[$prefix.$field] ) && '0' != $_POST[$prefix.$field] )
			wp_set_object_terms( $post_id, intval( $_POST[$prefix.$field] ), $this->constant( $constant_key ), FALSE );

		else if ( isset( $_POST[$prefix.$field] ) && '0' == $_POST[$prefix.$field] )
			wp_set_object_terms( $post_id, NULL, $this->constant( $constant_key ), FALSE );
	}

	// @SEE: https://github.com/scribu/wp-posts-to-posts/wiki/Connection-information
	public function register_p2p( $constant_key, $post_types = NULL )
	{
		if ( is_null( $post_types ) )
			$post_types = $this->post_types();

		$args = array_merge( array(
			'name'  => $this->constant( $constant_key.'_p2p' ),
			'from'  => $post_types,
			'to'    => $this->constant( $constant_key ),
		), $this->strings['p2p'][$constant_key] );

		$args = apply_filters( 'geditorial_'.$this->module->name.'_'.$this->constant( $constant_key ).'_p2p_args', $args, $post_types );
		if ( $args )
			p2p_register_connection_type( $args );
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( ! is_singular() )
			return;

		$post = get_post();

		if ( is_null( $posttypes ) )
			$posttypes = $this->post_types();

		if ( ! in_array( $post->post_type, $posttypes ) )
			return;

		if ( $before = $this->get_setting( 'before_content', FALSE ) )
			echo '<div class="geditorial-wrap '.$this->module->name.' -before">'.$before.'</div>';
	}

	public function content_after( $content, $posttypes = NULL )
	{
		if ( ! is_singular() )
			return;

		$post = get_post();

		if ( is_null( $posttypes ) )
			$posttypes = $this->post_types();

		if ( ! in_array( $post->post_type, $posttypes ) )
			return;

		if ( $after = $this->get_setting( 'after_content', FALSE ) )
			echo '<div class="geditorial-wrap '.$this->module->name.' -after">'.$after.'</div>';
	}
}

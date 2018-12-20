<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\File;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Module as Base;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\Taxonomy;
use geminorum\gEditorial\WordPress\Theme;
use geminorum\gEditorial\WordPress\User;

class Module extends Base
{

	public $module;
	public $options;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

	protected $cookie     = 'geditorial';
	protected $icon_group = 'genericons-neue';

	protected $priority_init              = 10;
	protected $priority_init_ajax         = 10;
	protected $priority_admin_menu        = 10;
	protected $priority_adminbar_init     = 10;
	protected $priority_template_redirect = 10;
	protected $priority_template_include  = 10;

	protected $constants = [];
	protected $strings   = [];
	protected $supports  = [];
	protected $fields    = [];

	protected $partials        = [];
	protected $partials_remote = [];

	protected $disable_no_customs    = FALSE; // not hooking module if has no posttypes/taxonomies
	protected $disable_no_posttypes  = FALSE; // not hooking module if has no posttypes
	protected $disable_no_taxonomies = FALSE; // not hooking module if has no taxonomies

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

	protected $scripts_printed = FALSE;

	public function __construct( &$module, &$options, $path )
	{
		$this->base = 'geditorial';
		$this->key  = $module->name;
		$this->path = File::normalize( $path );

		$this->module  = $module;
		$this->options = $options;

		if ( FALSE !== $module->disabled )
			return;

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
		$admin = is_admin();
		$ajax  = WordPress::isAJAX();
		$ui    = WordPress::mustRegisterUI( FALSE );

		if ( $admin && $ui && $this->module->configure )
			add_action( $this->base.'_settings_load', [ $this, 'register_settings' ] );

		if ( $this->setup_disabled() )
			return FALSE;

		$this->require_code( $this->partials );

		if ( method_exists( $this, 'p2p_init' ) )
			$this->action( 'p2p_init' );

		if ( method_exists( $this, 'wp_loaded' ) )
			$this->action( 'wp_loaded' );

		if ( method_exists( $this, 'widgets_init' ) )
			$this->action( 'widgets_init' );

		if ( ! $ajax && method_exists( $this, 'tinymce_strings' ) )
			add_filter( $this->base.'_tinymce_strings', [ $this, 'tinymce_strings' ] );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( $this->base.'_meta_init', [ $this, 'meta_init' ], 10, 2 );

		add_action( 'after_setup_theme', [ $this, '_after_setup_theme' ], 1 );

		if ( method_exists( $this, 'after_setup_theme' ) )
			$this->action( 'after_setup_theme', 0, 20 );

		$this->action( 'init', 0, $this->priority_init );

		if ( $ui && method_exists( $this, 'adminbar_init' ) && $this->get_setting( 'adminbar_summary' ) )
			add_action( $this->base.'_adminbar', [ $this, 'adminbar_init' ], $this->priority_adminbar_init, 2 );

		if ( $admin ) {

			if ( method_exists( $this, 'admin_init' ) )
				$this->action( 'admin_init' );

			if ( $ui && method_exists( $this, 'admin_menu' ) )
				$this->action( 'admin_menu', 0, $this->priority_admin_menu );

			if ( $ajax && method_exists( $this, 'init_ajax' ) )
				$this->action( 'init', 0, $this->priority_init_ajax, 'ajax' );

			if ( $ui )
				add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard' ] );

			if ( ( $ui || $ajax ) && method_exists( $this, 'register_shortcode_ui' ) )
				$this->action( 'register_shortcode_ui' );

			if ( $ui && method_exists( $this, 'add_meta_boxes' ) )
				$this->action( 'add_meta_boxes', 2, 12 );

			if ( $ui && method_exists( $this, 'current_screen' ) )
				$this->action( 'current_screen' );

			if ( $ui && method_exists( $this, 'reports_settings' ) )
				add_action( $this->base.'_reports_settings', [ $this, 'reports_settings' ] );

			if ( $ui && method_exists( $this, 'tools_settings' ) )
				add_action( $this->base.'_tools_settings', [ $this, 'tools_settings' ] );

		} else {

			if ( $ui && method_exists( $this, 'template_redirect' ) )
				add_action( 'template_redirect', [ $this, 'template_redirect' ], $this->priority_template_redirect );

			if ( $ui && method_exists( $this, 'template_include' ) )
				add_filter( 'template_include', [ $this, 'template_include' ], $this->priority_template_include );
		}

		return TRUE;
	}

	protected function setup_disabled()
	{
		if ( $this->disable_no_customs && ! count( $this->posttypes() ) && ! count( $this->taxonomies() ) )
			return TRUE;

		if ( $this->disable_no_posttypes && ! count( $this->posttypes() ) )
			return TRUE;

		if ( $this->disable_no_taxonomies && ! count( $this->taxonomies() ) )
			return TRUE;

		return FALSE;
	}

	public function setup_dashboard()
	{
		if ( method_exists( $this, 'dashboard_glance_items' ) )
			$this->filter( 'dashboard_glance_items' );

		if ( method_exists( $this, 'dashboard_widgets' )
			&& $this->get_setting( 'dashboard_widgets', FALSE ) )
				$this->dashboard_widgets();
	}

	public function _after_setup_theme()
	{
		$this->constants = $this->filters( 'constants', $this->get_global_constants(), $this->module );
		$this->supports  = $this->filters( 'supports', $this->get_global_supports(), $this->module ); // FIXME: DEPRECATED
		$this->fields    = $this->filters( 'fields', $this->get_global_fields(), $this->module );
	}

	// MUST ALWAYS CALL THIS
	public function init()
	{
		$this->actions( 'init', $this->options, $this->module );

		$this->strings = $this->filters( 'strings', $this->get_global_strings(), $this->module );

		if ( ! is_admin() )
			return;

		foreach ( $this->get_module_templates() as $constant => $templates ) {

			if ( empty( $templates ) )
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
	protected function get_global_supports() { return []; } // FIXME: DEPRECATED
	protected function get_global_fields() { return []; }

	protected function get_module_templates() { return []; }
	protected function get_module_icons() { return []; }

	protected function settings_help_tabs( $context = 'settings' )
	{
		return Settings::helpContent( $this->module );
	}

	protected function settings_help_sidebar( $context = 'settings' )
	{
		return Settings::helpSidebar( $this->get_module_links() );
	}

	protected function get_module_links()
	{
		$links  = [];
		$screen = get_current_screen();

		if ( ( $list = $this->get_adminmenu( FALSE ) ) && method_exists( $this, 'admin_menu' ) && ! Settings::isDashboard( $screen ) )
			if ( $this->role_can( 'adminmenu' ) )
				$links[] = [
					'context' => 'listtable',
					'sub'     => $this->key,
					'text'    => $this->module->title,
					'url'     => $list,
					'title'   => sprintf( _x( '%s', 'Module: Extra Link: Listtable', GEDITORIAL_TEXTDOMAIN ), $this->module->title ),
				];

		if ( method_exists( $this, 'reports_settings' ) && ! Settings::isReports( $screen ) )
			foreach ( $this->append_sub( [], 'reports' ) as $sub => $title )
				$links[] = [
					'context' => 'reports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'reports', $sub ),
					'title'   => sprintf( _x( '%s Reports', 'Module: Extra Link: Reports', GEDITORIAL_TEXTDOMAIN ), $title ),
				];

		if ( method_exists( $this, 'tools_settings' ) && ! Settings::isTools( $screen ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$links[] = [
					'context' => 'tools',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'tools', $sub ),
					'title'   => sprintf( _x( '%s Tools', 'Module: Extra Link: Tools', GEDITORIAL_TEXTDOMAIN ), $title ),
				];

		if ( isset( $this->caps['settings'] ) && ! Settings::isSettings( $screen ) && $this->cuc( 'settings' ) )
			$links[] = [
				'context' => 'settings',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $this->get_module_url( 'settings' ),
				'title'   => sprintf( _x( '%s Settings', 'Module: Extra Link: Settings', GEDITORIAL_TEXTDOMAIN ), $this->module->title ),
			];

		if ( $docs = $this->get_module_url( 'docs' ) )
			$links[] = [
				'context' => 'docs',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $docs,
				'title'   => sprintf( _x( '%s Documentation', 'Module: Extra Link: Documentation', GEDITORIAL_TEXTDOMAIN ), $this->module->title ),
			];

		if ( 'config' != $this->module->name )
			$links[] = [
				'context' => 'docs',
				'sub'     => FALSE,
				'text'    => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', GEDITORIAL_TEXTDOMAIN ),
				'url'     => Settings::getModuleDocsURL( FALSE ),
				'title'   => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', GEDITORIAL_TEXTDOMAIN ),
			];

		return $links;
	}

	public function get_module_url( $context = 'reports', $sub = NULL, $extra = [] )
	{
		if ( is_null( $sub ) )
			$sub = $this->key;

		switch ( $context ) {
			case 'config'    : $url = Settings::settingsURL(); break;
			case 'reports'   : $url = Settings::reportsURL(); break;
			case 'tools'     : $url = Settings::toolsURL(); break;
			case 'docs'      : $url = Settings::getModuleDocsURL( $this->module ); $sub = FALSE; break;
			case 'settings'  : $url = add_query_arg( 'module', $this->module->name, Settings::settingsURL() ); $sub = FALSE; break;
			case 'listtable' : $url = $this->get_adminmenu( FALSE ); $sub = FALSE; break;
			default          : $url = URL::current();
		}

		if ( FALSE === $url )
			return FALSE;

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	// OVERRIDE: if has no admin menu but using the hook
	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		if ( $page )
			return $this->classs();

		$url = get_admin_url( NULL, 'index.php' );

		return add_query_arg( array_merge( [ 'page' => $this->classs() ], $extra ), $url );
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

	protected function posttypes_excluded()
	{
		return Settings::posttypesExcluded();
	}

	// enabled post types for this module
	public function posttypes( $posttypes = NULL )
	{
		$loaded = did_action( 'wp_loaded' );

		if ( is_null( $posttypes ) )
			$posttypes = [];

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $this->constant( $posttypes ) ];

		if ( empty( $this->options->post_types ) )
			return $posttypes;

		$filtered = array_filter( $this->options->post_types );

		if ( ! $loaded )
			return array_keys( $filtered );

		foreach ( $filtered as $posttype => $value )
			if ( post_type_exists( $posttype ) )
				$posttypes[] = $posttype;

		return $posttypes;
	}

	public function list_posttypes( $pre = NULL, $posttypes = NULL, $args = [ 'show_ui' => TRUE ] )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All PostTypes', 'Module', GEDITORIAL_TEXTDOMAIN ) ];

		$all = PostType::get( 0, $args );

		foreach ( $this->posttypes( $posttypes ) as $posttype )
			$pre[$posttype] = empty( $all[$posttype] ) ? $posttype : $all[$posttype];

		return $pre;
	}

	public function all_posttypes( $exclude = TRUE, $args = [ 'show_ui' => TRUE ] )
	{
		$posttypes = PostType::get( 0, $args );
		$excluded  = $this->posttypes_excluded();

		return empty( $excluded )
			? $posttypes
			: array_diff_key( $posttypes, array_flip( $excluded ) );
	}

	protected function taxonomies_excluded()
	{
		return Settings::taxonomiesExcluded();
	}

	// enabled post types for this module
	public function taxonomies()
	{
		return empty( $this->options->taxonomies )
			? []
			: array_keys( array_filter( $this->options->taxonomies ) );
	}

	public function all_taxonomies( $args = [] )
	{
		$taxonomies = Taxonomy::get( 0, $args );
		$excluded   = $this->taxonomies_excluded();

		return empty( $excluded )
			? $taxonomies
			: array_diff_key( $taxonomies, array_flip( $excluded ) );
	}

	public function settings_posttypes_option()
	{
		if ( $before = $this->get_string( 'post_types_before', 'post', 'settings', NULL ) )
			HTML::desc( $before );

		foreach ( $this->all_posttypes() as $posttype => $label ) {

			$html = HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$posttype,
				'name'    => $this->base.'_'.$this->module->name.'[post_types]['.$posttype.']',
				'checked' => ! empty( $this->options->post_types[$posttype] ),
			] );

			$html.= '&nbsp;'.HTML::escape( $label );
			$html.= ' &mdash; <code>'.$posttype.'</code>';

			HTML::label( $html, 'type-'.$posttype );
		}

		if ( $after = $this->get_string( 'post_types_after', 'post', 'settings', NULL ) )
			HTML::desc( $after );
	}

	public function settings_taxonomies_option()
	{
		if ( $before = $this->get_string( 'taxonomies_before', 'post', 'settings', NULL ) )
			HTML::desc( $before );

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->base.'_'.$this->module->name.'[taxonomies]['.$taxonomy.']',
				'checked' => ! empty( $this->options->taxonomies[$taxonomy] ),
			] );

			$html.= '&nbsp;'.HTML::escape( $label );
			$html.= ' &mdash; <code>'.$taxonomy.'</code>';

			HTML::label( $html, 'tax-'.$taxonomy );
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

		// default excludes
		if ( is_null( $excludes ) )
			$excludes = [ 'post-formats', 'trackbacks' ];

		if ( count( $excludes ) )
			$supports = array_diff_key( $supports, array_flip( (array) $excludes ) );

		if ( is_null( $defaults ) )
			$defaults = $this->supports[$constant];

		else if ( TRUE === $defaults )
			$defaults = array_keys( $supports );

		$singular = translate_nooped_plural( $this->strings['noops'][$constant], 1 );

		return [
			'field'       => $constant.'_supports',
			'type'        => 'checkboxes-values',
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

		$option = $this->base.'_'.$this->module->name;

		Settings::addModuleSection( $option, [
			'id'            => $option.'_posttypes',
			'section_class' => 'posttypes_option_section',
		] );

		add_settings_field( 'post_types',
			$title,
			[ $this, 'settings_posttypes_option' ],
			$option,
			$option.'_posttypes'
		);
	}

	public function register_settings_taxonomies_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'taxonomies_title', 'post', 'settings',
				_x( 'Enable for Taxonomies', 'Module', GEDITORIAL_TEXTDOMAIN ) );

		$option = $this->base.'_'.$this->module->name;

		Settings::addModuleSection( $option, [
			'id'            => $option.'_taxonomies',
			'section_class' => 'taxonomies_option_section',
		] );

		add_settings_field( 'taxonomies',
			$title,
			[ $this, 'settings_taxonomies_option' ],
			$option,
			$option.'_taxonomies'
		);
	}

	public function register_settings_fields_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = _x( 'Fields for %s', 'Module', GEDITORIAL_TEXTDOMAIN );

		$all = $this->all_posttypes();

		foreach ( $this->posttypes() as $posttype ) {

			$fields  = $this->posttype_fields_all( $posttype );
			$section = $posttype.'_fields';

			if ( count( $fields ) ) {

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'title'         => sprintf( $title, $all[$posttype] ),
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

					$args = [
						'field'       => $field,
						'post_type'   => $posttype,
						'section'     => $section,
						'field_title' => isset( $atts['title'] ) ? $atts['title'] : $this->get_string( $field, $posttype ),
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

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'title'         => sprintf( $title, $all[$posttype] ),
					'callback'      => [ $this, 'settings_fields_option_none' ],
					'section_class' => 'fields_option_section fields_option_none',
				] );
			}
		}
	}

	public function settings_fields_option( $args )
	{
		$name = $this->base.'_'.$this->module->name.'[fields]['.$args['post_type'].']['.$args['field'].']';
		$id   = $this->base.'_'.$this->module->name.'-fields-'.$args['post_type'].'-'.$args['field'];

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

		echo '<div>';
			HTML::label( $html.'&nbsp;'.$args['field_title'], $id, FALSE );
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

		$html.= '&nbsp;<span class="description">'._x( 'Select All Fields', 'Module', GEDITORIAL_TEXTDOMAIN ).'</span>';

		HTML::label( $html, $args['post_type'].'_fields_all', FALSE );
	}

	public function settings_fields_option_none( $args )
	{
		Settings::moduleSectionEmpty( _x( 'No fields supported', 'Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	public function settings_from()
	{
		echo '<form class="'.$this->base.'-form -form -'.$this->module->name
			.'" action="'.$this->get_module_url( 'settings' ).'" method="post">';

			$this->render_form_fields( $this->module->name );

			Settings::moduleSections( $this->base.'_'.$this->module->name );

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.HTML::escape( $this->module->name ).'" />';

			$this->render_form_buttons();

		echo '</form>';

		if ( WordPress::isDev() )
			self::dump( $this->options );
	}

	public function default_buttons( $module = FALSE )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );
		$this->register_button( 'disable', _x( 'Disable Module', 'Module: Button', GEDITORIAL_TEXTDOMAIN ), 'danger' );

		foreach ( $this->get_module_links() as $link )
			if ( ! empty( $link['context'] ) && in_array( $link['context'], [ 'tools', 'reports', 'listtable' ] ) )
				$this->register_button( $link['url'], $link['title'], 'link' );
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

	protected function render_form_buttons( $module = FALSE, $wrap = '' )
	{
		if ( FALSE !== $wrap )
			echo $this->wrap_open_buttons( $wrap );

		foreach ( $this->buttons as $button )
			Settings::submitButton( $button['key'], $button['value'], $button['type'], $button['atts'] );

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function render_form_start( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		if ( is_null( $sub ) )
			$sub = $this->module->name;

		$class = $this->base.'-form -form -'.$this->module->name.' -sub-'.$sub;

		if ( $check && $sidebox = method_exists( $this, 'settings_sidebox' ) )
			$class.= ' has-sidebox';

		echo '<form enctype="multipart/form-data" class="'.$class.'" method="post" action="">';

			$this->render_form_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="-sidebox -'.$this->module->name.' -sidebox-'.$sub.'">';
					$this->settings_sidebox( $sub, $uri, $context );
				echo '</div>';
			}
	}

	protected function render_form_end( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		echo '</form>';
	}

	// DEFAULT METHOD: tools sub html
	public function tools_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'tools', FALSE );

			if ( $this->render_tools_html( $uri, $sub ) )
				$this->render_form_buttons();

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for tools default sub html
	protected function render_tools_html( $uri, $sub ) {}

	// DEFAULT METHOD: reports sub html
	public function reports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'reports', FALSE );

			if ( $this->render_reports_html( $uri, $sub ) )
				$this->render_form_buttons();

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for reports default sub html
	protected function render_reports_html( $uri, $sub ) {}

	protected function get_current_form( $defaults, $context = 'settings' )
	{
		$req = empty( $_REQUEST[$this->base.'_'.$this->module->name][$context] )
			? []
			: $_REQUEST[$this->base.'_'.$this->module->name][$context];

		return self::atts( $defaults, $req );
	}

	protected function render_form_fields( $sub, $action = 'update', $context = 'settings' )
	{
		HTML::inputHidden( 'base', $this->base );
		HTML::inputHidden( 'key', $this->key );
		HTML::inputHidden( 'context', $context );
		HTML::inputHidden( 'sub', $sub );
		HTML::inputHidden( 'action', $action );

		WordPress::fieldReferer();
		$this->nonce_field( $context, $sub );
	}

	// DEFAULT METHOD
	// `$extra` arg is for extending in modules
	public function append_sub( $subs, $context = 'settings', $extra = [] )
	{
		if ( ! $this->cuc( $context ) )
			return $subs;

		return array_merge( $subs, [ $this->module->name => $this->module->title ], $extra );
	}

	public function settings_validate( $options )
	{
		$this->init_settings();

		if ( isset( $this->settings['posttypes_option'] ) ) {

			if ( ! isset( $options['post_types'] ) )
				$options['post_types'] = [];

			foreach ( $this->all_posttypes() as $posttype => $posttype_label )
				if ( ! isset( $options['post_types'][$posttype] )
					|| $options['post_types'][$posttype] != 'enabled' )
						unset( $options['post_types'][$posttype] );
				else
					$options['post_types'][$posttype] = TRUE;

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

			foreach ( $this->posttypes() as $posttype ) {

				if ( ! isset( $options['fields'][$posttype] ) )
					$options['fields'][$posttype] = [];

				foreach ( $this->posttype_fields_all( $posttype ) as $field => $args ) {

					if ( ! isset( $options['fields'][$posttype][$field] )
						|| $options['fields'][$posttype][$field] != 'enabled' )
							unset( $options['fields'][$posttype][$field] );
					else
						$options['fields'][$posttype][$field] = TRUE;
				}

				if ( ! count( $options['fields'][$posttype] ) )
					unset( $options['fields'][$posttype] );
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

				if ( ! array_key_exists( 'type', $args ) || 'enabled' == $args['type'] ) {

					$options['settings'][$setting] = (bool) $option;

				} else if ( is_array( $option ) ) {

					if ( array_key_exists( 'type', $args ) && 'text' == $args['type'] ) {

						// multiple texts
						$options['settings'][$setting] = [];

						foreach ( $option as $key => $value )
							if ( $string = trim( self::unslash( $value ) ) )
								$options['settings'][$setting][sanitize_key( $key )] = $string;

					} else {

						// multiple checkboxes
						$options['settings'][$setting] = array_keys( $option );
					}

				} else {

					$options['settings'][$setting] = trim( self::unslash( $option ) );
				}
			}

			if ( ! count( $options['settings'] ) )
				unset( $options['settings'] );
		}

		return $options;
	}

	protected function get_settings_field( $setting )
	{
		foreach ( $this->settings as $section ) {

			if ( is_array( $section ) ) {

				foreach ( $section as $suffix => $field ) {

					if ( is_array( $field ) ) {

						if ( isset( $field['field'] ) && $setting == $field['field'] )
							return $field;

					} else if ( is_string( $suffix ) && $setting == $suffix ) {

						if ( method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$suffix ) )
							return call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$suffix ], [ $field ] );

						return [];

					} else if ( $setting == $field ) {

						if ( method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field ) )
							return call_user_func( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ] );

						return [];
					}
				}
			}
		}

		return [];
	}

	public function posttype_fields_all( $posttype = 'post', $module = NULL )
	{
		return PostType::supports( $posttype, ( is_null( $module ) ? $this->module->name : $module ).'_fields' );
	}

	public function posttype_fields_list( $posttype = 'post', $extra = [] )
	{
		$list = [];

		foreach ( $this->posttype_fields( $posttype ) as $field )
			$list[$field] = $this->get_string( $field, $posttype );

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

			foreach ( $this->options->fields[$posttype] as $field => $enabled ) {

				if ( $js )
					$fields[$field] = (bool) $enabled;

				else if ( $enabled )
					$fields[] = $field;
			}
		}

		return $fields;
	}

	// this module enabled fields with args for a posttype
	public function get_posttype_fields( $posttype = 'post' )
	{
		global $gEditorialPostTypeFields;

		if ( isset( $gEditorialPostTypeFields[$posttype] ) )
			return $gEditorialPostTypeFields[$posttype];

		$all     = $this->posttype_fields_all( $posttype );
		$enabled = $this->posttype_fields( $posttype );
		$fields  = [];

		foreach ( $enabled as $i => $field ) {

			$args = isset( $all[$field] ) && is_array( $all[$field] ) ? $all[$field] : [];

			if ( ! isset( $args['context'] ) && isset( $args['type'] ) )
				$args['context'] = in_array( $args['type'], [ 'box', 'title_before', 'title_after' ] )
					? 'dbx' : 'box';

			if ( ! isset( $args['icon'] ) )
				$args['icon'] = $this->get_field_icon( $field, $posttype, $args );

			$fields[$field] = self::atts( [
				'title'       => $this->get_string( $field, $posttype, 'titles', $field ),
				'description' => $this->get_string( $field, $posttype, 'descriptions' ),
				'icon'        => 'smiley',
				'type'        => 'text',
				'context'     => 'box',
				'values'      => [],
				'repeat'      => FALSE,
				'ltr'         => FALSE,
				'tax'         => FALSE,
				'group'       => 'general',
				'order'       => 10 + $i,
			], $args );
		}

		$gEditorialPostTypeFields[$posttype] = Arraay::multiSort( $fields, [
			'group' => SORT_ASC,
			'order' => SORT_ASC,
		] );

		return $gEditorialPostTypeFields[$posttype];
	}

	public function add_posttype_fields( $posttype, $fields = NULL, $type = 'meta', $append = TRUE )
	{
		if ( is_null( $fields ) )
			$fields = $this->fields[$posttype];

		if ( empty( $fields ) )
			return;

		if ( $append )
			$fields = array_merge( PostType::supports( $posttype, $type.'_fields' ), $fields );

		add_post_type_support( $posttype, [ $type.'_fields' ], $fields );
	}

	public function get_string( $string, $posttype = 'post', $group = 'titles', $fallback = FALSE )
	{
		if ( isset( $this->strings[$group][$posttype][$string] ) )
			return $this->strings[$group][$posttype][$string];

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

	public function constants( $keys, $pre = [] )
	{
		foreach ( (array) $keys as $key )
			if ( $constant = $this->constant( $key ) )
				$pre = array_merge( $pre, $constant );

		return $pre;
	}

	public function slug()
	{
		return str_replace( '_', '-', $this->module->name );
	}

	protected function help_tab_default_terms( $constant )
	{
		$tab = [
			'id'    => $this->classs( 'help-default-terms', $this->constant( $constant ) ),
			'title' => _x( 'Default Terms', 'Module', GEDITORIAL_TEXTDOMAIN ),
		];

		if ( ! empty( $this->strings['terms'][$constant] ) )
			$tab['content'] = HTML::wrap( HTML::tableCode( $this->strings['terms'][$constant], TRUE ), '-info' );

		else
			$tab['content'] = HTML::wrap( _x( 'No Default Terms', 'Module', GEDITORIAL_TEXTDOMAIN ), '-info' );

		get_current_screen()->add_help_tab( $tab );
	}

	protected function insert_default_terms( $constant, $terms = NULL )
	{
		if ( ! $this->nonce_verify( 'settings' ) )
			return;

		if ( is_null( $terms ) && isset( $this->strings['terms'][$constant] ) )
			$terms = $this->strings['terms'][$constant];

		if ( empty( $terms ) )
			$message = 'noadded';

		else if ( $added = Taxonomy::insertDefaultTerms( $this->constant( $constant ), $terms ) )
			$message = [
				'message' => 'created',
				'count'   => $added,
			];

		else
			$message = 'wrong';

		WordPress::redirectReferer( $message );
	}

	protected function check_settings( $sub, $context = 'tools', $key = NULL )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		if ( is_null( $key ) )
			$key = $this->key;

		if ( $key == $this->key )
			add_filter( $this->base.'_'.$context.'_subs', [ $this, 'append_sub' ], 10, 2 );

		if ( $key != $sub )
			return FALSE;

		add_action( $this->base.'_'.$context.'_sub_'.$sub, [ $this, $context.'_sub' ], 10, 2 );

		if ( 'settings' != $context ) {

			$this->register_help_tabs( NULL, $context );

			add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );
		}

		return TRUE;
	}

	public function init_settings()
	{
		if ( ! isset( $this->settings ) )
			$this->settings = $this->filters( 'settings', $this->get_global_settings(), $this->module );
	}

	public function register_settings( $module = FALSE )
	{
		if ( $module != $this->module->name )
			return;

		$this->init_settings();

		// FIXME: find a better way
		if ( $this->setup_disabled() )
			$this->strings = $this->filters( 'strings', $this->get_global_strings(), $this->module );

		if ( method_exists( $this, 'before_settings' ) )
			$this->before_settings( $module );

		foreach ( $this->settings as $section_suffix => $fields ) {

			if ( is_array( $fields ) ) {

				$section = $this->base.'_'.$this->module->name.$section_suffix;

				if ( method_exists( $this, 'settings_section'.$section_suffix ) )
					$callback = [ $this, 'settings_section'.$section_suffix ];
				else
					$callback = '__return_false';

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'callback'      => $callback,
					'section_class' => 'settings_section',
				] );

				foreach ( $fields as $suffix => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_array( $field ) )
						$args = $field;

					// passing as custom description
					else if ( is_string( $suffix ) && method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$suffix ) )
						$args = call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$suffix ], [ $field ] );

					else if ( method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field ) )
						$args = call_user_func( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ] );

					else
						continue;

					$this->add_settings_field( array_merge( $args, [ 'section' => $section ] ) );
				}

			} else if ( method_exists( $this, 'register_settings_'.$section_suffix ) ) {

				$title = $section_suffix == $fields ? NULL : $fields;

				call_user_func_array( [ $this, 'register_settings_'.$section_suffix ], [ $title ] );
			}
		}

		$this->default_buttons( $module );
		$this->register_help_tabs();

		// register settings on the settings page only
		add_action( 'admin_print_footer_scripts', [ $this, 'settings_print_scripts' ], 99 );
	}

	protected function register_help_tabs( $screen = NULL, $context = 'settings' )
	{
		if ( ! WordPress::mustRegisterUI( FALSE ) )
			return;

		if ( is_null( $screen ) )
			$screen = get_current_screen();

		foreach ( $this->settings_help_tabs( $context ) as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar( $context ) )
			$screen->set_help_sidebar( $sidebar );
	}

	public function settings_header()
	{
		$back = $count = $flush = $filters = FALSE;

		if ( 'config' == $this->module->name ) {
			$title   = NULL;
			$count   = gEditorial()->count();
			$flush   = WordPress::maybeFlushRules();
			$filters = TRUE;
		} else {
			$title = sprintf( _x( 'Editorial: %s', 'Module', GEDITORIAL_TEXTDOMAIN ), $this->module->title );
			$back  = Settings::settingsURL();
		}

		Settings::wrapOpen( $this->module->name );

			Settings::headerTitle( $title, $back, NULL, $this->module->icon, $count, TRUE, $filters );
			Settings::message();

			if ( $flush )
				echo HTML::warning( _x( 'You need to flush rewrite rules!', 'Module', GEDITORIAL_TEXTDOMAIN ), FALSE );

			echo '<div class="-header">';

			if ( isset( $this->module->desc ) && $this->module->desc )
				echo '<h4>'.$this->module->desc.'</h4>';

			if ( method_exists( $this, 'settings_intro' ) )
				$this->settings_intro();

		Settings::wrapClose();
	}

	protected function settings_footer()
	{
		if ( 'config' == $this->module->name )
			Settings::settingsCredits();

		else
			$this->settings_signature( 'settings' );
	}

	protected function settings_signature( $context = 'settings' )
	{
		Settings::settingsSignature();
	}

	public function settings_section_defaults()
	{
		Settings::fieldSection(
			_x( 'Defaults', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_frontend()
	{
		Settings::fieldSection(
			_x( 'Front-end', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_content()
	{
		Settings::fieldSection(
			_x( 'Generated Contents', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_dashboard()
	{
		Settings::fieldSection(
			_x( 'Admin Dashboard', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_editlist()
	{
		Settings::fieldSection(
			_x( 'Admin Edit List', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_columns()
	{
		Settings::fieldSection(
			_x( 'Admin List Columns', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_editpost()
	{
		Settings::fieldSection(
			_x( 'Admin Edit Post', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_comments()
	{
		Settings::fieldSection(
			_x( 'Admin Comment List', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function settings_section_roles()
	{
		Settings::fieldSection(
			_x( 'Accessibility', 'Module: Setting Section Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'Though Administrators have it all!', 'Module: Setting Section Description', GEDITORIAL_TEXTDOMAIN )
		);
	}

	public function add_settings_field( $r = [] )
	{
		$args = array_merge( [
			'page'        => $this->base.'_'.$this->module->name,
			'section'     => $this->base.'_'.$this->module->name.'_general',
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
		if ( $args['option_group'] )
			return [
				( $args['id_attr'] ? $args['id_attr'] : $args['option_base'].'-'.$args['option_group'].'-'.$args['field'] ),
				( $args['name_attr'] ? $args['name_attr'] : $args['option_base'].'['.$args['option_group'].']['.$args['field'].']' ),
			];

		return [
			( $args['id_attr'] ? $args['id_attr'] : $args['option_base'].'-'.$args['field'] ),
			( $args['name_attr'] ? $args['name_attr'] : $args['option_base'].'['.$args['field'].']' ),
		];
	}

	public function do_settings_field( $atts = [] )
	{
		$args = array_merge( [
			'options'      => isset( $this->options->settings ) ? $this->options->settings : [],
			'option_base'  => $this->base.'_'.$this->module->name,
			'option_group' => 'settings',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
		], $atts );

		if ( empty( $args['cap'] ) )
			$args['cap'] = empty( $this->caps[$args['option_group']] ) ? NULL : $this->caps[$args['option_group']];

		Settings::fieldType( $args, $this->scripts );
	}

	public function do_posttype_field( $atts = [], $post = NULL )
	{
		if ( ! $post = get_post( $post ) )
			return;

		$args = array_merge( [
			'option_base'  => $this->hook(),
			'option_group' => 'fields',
			'id_name_cb'   => [ $this, 'settings_id_name_cb' ],
			'cap'          => TRUE,
		], $atts );

		if ( ! array_key_exists( 'options', $args ) )
			$args['options'] = get_post_meta( $post->ID ); //  $this->get_postmeta( $post->ID, FALSE, [] );

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

	// for out of context manipulations
	public function update_option( $key, $value )
	{
		return gEditorial()->update_module_option( $this->module->name, $key, $value );
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

	public function get_posttype_labels( $constant )
	{
		if ( ! empty( $this->strings['labels'][$constant] ) )
			return $this->strings['labels'][$constant];

		$labels = [];

		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		if ( $author_metabox = $this->get_string( 'author_metabox', $constant, 'misc', NULL ) )
			$labels['author_metabox'] = $author_metabox;

		if ( $excerpt_metabox = $this->get_string( 'excerpt_metabox', $constant, 'misc', NULL ) )
			$labels['excerpt_metabox'] = $excerpt_metabox;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Helper::generatePostTypeLabels(
				$this->strings['noops'][$constant],
				$this->get_string( 'featured', $constant, 'misc', NULL ),
				$labels
			);

		return $labels;
	}

	protected function get_posttype_supports( $constant )
	{
		if ( isset( $this->options->settings[$constant.'_supports'] ) )
			return $this->options->settings[$constant.'_supports'];

		if ( isset( $this->supports[$constant] ) )
			return $this->supports[$constant];

		return array_keys( Settings::supportsOptions() );
	}

	public function get_posttype_icon( $constant = NULL, $default = 'welcome-write-blog' )
	{
		$icon  = $this->module->icon ? $this->module->icon : $default;
		$icons = $this->get_module_icons();

		if ( $constant && isset( $icons['post_types'][$constant] ) )
			$icon = $icons['post_types'][$constant];

		if ( is_array( $icon ) )
			$icon = Icon::getBase64( $icon[1], $icon[0] );

		else if ( $icon )
			$icon = 'dashicons-'.$icon;

		return $icon ? $icon : 'dashicons-'.$default;
	}

	public function get_posttype_cap_type( $constant )
	{
		$default = $this->constant( $constant.'_cap_type', 'post' );

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $default;

		if ( ! in_array( $this->constant( $constant ), gEditorial()->roles->posttypes() ) )
			return $default;

		return gEditorial()->roles->constant( 'base_type' );
	}

	public function register_posttype( $constant, $atts = [], $taxonomies = [ 'post_tag' ], $block_editor = FALSE )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = $this->taxonomies();

		$posttype = $this->constant( $constant );
		$cap_type = $this->get_posttype_cap_type( $constant );

		$args = [
			'taxonomies'    => $taxonomies,
			'labels'        => $this->get_posttype_labels( $constant ),
			'supports'      => $this->get_posttype_supports( $constant ),
			'description'   => isset( $this->strings['labels'][$constant]['description'] ) ? $this->strings['labels'][$constant]['description'] : '',
			'menu_icon'     => $this->get_posttype_icon( $constant ),
			'menu_position' => 4,

			'query_var'   => $this->constant( $constant.'_query_var', $posttype ),
			'has_archive' => $this->constant( $constant.'_archive', FALSE ),
			'rewrite'     => [
				'slug'       => $this->constant( $constant.'_slug', $posttype ),
				'ep_mask'    => $this->constant( $constant.'_endpoint', EP_PERMALINK | EP_PAGES ), // https://make.wordpress.org/plugins?p=29
				'with_front' => FALSE,
				'feeds'      => TRUE,
				'pages'      => TRUE,
			],

			'hierarchical' => FALSE,
			'public'       => TRUE,
			'show_ui'      => TRUE,

			'capability_type' => $cap_type,
			'map_meta_cap'    => TRUE,

			'register_meta_box_cb' => method_exists( $this, 'add_meta_box_cb_'.$constant ) ? [ $this, 'add_meta_box_cb_'.$constant ] : NULL,

			'show_in_rest' => $this->get_setting( 'restapi_support', TRUE ),
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $posttype ) ),

			'can_export'       => TRUE,
			'delete_with_user' => FALSE,

			// @SEE: https://github.com/torounit/custom-post-type-permalinks
			'cptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink

			// only `%post_id%` and `%postname%`
			// @SEE: https://github.com/torounit/simple-post-type-permalinks
			'sptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink
		];

		// @ALSO SEE: https://core.trac.wordpress.org/ticket/22895
		if ( 'post' != $cap_type )
			$args['capabilities'] = [ 'create_posts' => is_array( $cap_type ) ? 'create_'.$cap_type[1] : 'create_'.$cap_type.'s' ];

		$object = register_post_type( $posttype, self::recursiveParseArgs( $atts, $args ) );

		if ( is_wp_error( $object ) )
			return FALSE;

		if ( ! $block_editor )
			add_filter( 'use_block_editor_for_post_type', function( $edit, $type ) use( $posttype ) {
				return $posttype === $type ? FALSE : $edit;
			}, 12, 2 );

		return $object;
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

		if ( ! in_array( $posttype, gEditorial()->roles->posttypes() ) )
			return $defaults;

		$base = gEditorial()->roles->constant( 'base_type' );

		return [
			'manage_terms' => 'edit_others_'.$base[1],
			'edit_terms'   => 'edit_others_'.$base[1],
			'delete_terms' => 'edit_others_'.$base[1],
			'assign_terms' => 'edit_'.$base[1],
		];
	}

	public function register_taxonomy( $constant, $atts = [], $posttypes = NULL, $caps = NULL )
	{
		$taxonomy = $this->constant( $constant );

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $this->constant( $posttypes ) ];

		$args = self::recursiveParseArgs( $atts, [
			'labels'                => $this->get_taxonomy_labels( $constant ),
			'update_count_callback' => [ __NAMESPACE__.'\\WordPress\\Database', 'updateCountCallback' ],
			'meta_box_cb'           => method_exists( $this, 'meta_box_cb_'.$constant ) ? [ $this, 'meta_box_cb_'.$constant ] : FALSE,
			'meta_box_sanitize_cb'  => NULL, // @REF: https://core.trac.wordpress.org/ticket/36514
			'hierarchical'          => FALSE,
			'public'                => TRUE,
			'show_ui'               => TRUE,
			'show_admin_column'     => FALSE,
			'show_in_quick_edit'    => FALSE,
			'show_in_nav_menus'     => FALSE,
			'show_tagcloud'         => FALSE,
			'capabilities'          => $this->get_taxonomy_caps( $caps, $posttypes ),
			'query_var'             => $this->constant( $constant.'_query', $taxonomy ),
			'rewrite'               => [

				// we can use `cpt/tax` if cpt registered after the tax
				// @REF: https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-2274
				'slug'       => $this->constant( $constant.'_slug', $taxonomy ),
				'with_front' => FALSE,
			],

			'show_in_rest' => $this->get_setting( 'restapi_support', TRUE ),
			'rest_base'    => $this->constant( $constant.'_rest', $taxonomy ),
		] );

		$object = register_taxonomy( $taxonomy, $posttypes, $args );

		if ( is_wp_error( $object ) )
			return FALSE;

		return $object;
	}

	protected function get_post_updated_messages( $constant )
	{
		return [ $this->constant( $constant ) => Helper::generatePostTypeMessages( $this->get_noop( $constant ) ) ];
	}

	protected function get_bulk_post_updated_messages( $constant, $bulk_counts )
	{
		return [ $this->constant( $constant ) => Helper::generateBulkPostTypeMessages( $this->get_noop( $constant ), $bulk_counts ) ];
	}

	public function get_image_sizes( $posttype )
	{
		if ( ! isset( $this->image_sizes[$posttype] ) ) {

			$sizes = $this->filters( $posttype.'_image_sizes', [] );

			if ( FALSE === $sizes ) {
				$this->image_sizes[$posttype] = []; // no sizes

			} else if ( count( $sizes ) ) {
				$this->image_sizes[$posttype] = $sizes; // custom sizes

			} else {
				foreach ( Helper::getWPImageSizes() as $size => $args )
					$this->image_sizes[$posttype][$posttype.'-'.$size] = $args;
			}
		}

		return $this->image_sizes[$posttype];
	}

	public function get_image_size_key( $constant, $size = 'thumbnail' )
	{
		$posttype = $this->constant( $constant );

		if ( isset( $this->image_sizes[$posttype][$posttype.'-'.$size] ) )
			return $posttype.'-'.$size;

		if ( isset( $this->image_sizes[$posttype]['post-'.$size] ) )
			return 'post-'.$size;

		return $size;
	}

	// use this on 'after_setup_theme'
	public function register_posttype_thumbnail( $constant )
	{
		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		$posttype = $this->constant( $constant );

		Media::themeThumbnails( [ $posttype ] );

		foreach ( $this->get_image_sizes( $posttype ) as $name => $size )
			Media::registerImageSize( $name, array_merge( $size, [ 'p' => [ $posttype ] ] ) );
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
			$name.= '.'.$args;
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
		if ( ! $this->get_setting( 'shortcode_support', FALSE ) )
			return;

		if ( is_null( $callback ) && method_exists( $this, $constant ) )
			$callback = [ $this, $constant ];

		$shortcode = $this->constant( $constant );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( 'geditorial_shortcode_'.$shortcode, $callback, 10, 3 );
	}

	public function get_calendars( $default = [ 'gregorian' ], $filtered = TRUE )
	{
		$settings = $this->get_setting( 'calendar_list', $default );
		$defaults = Datetime::getDefualtCalendars( $filtered );
		return array_intersect_key( $defaults, array_flip( $settings ) );
	}

	public function default_calendar( $default = 'gregorian' )
	{
		return $this->get_setting( 'calendar_type', $default );
	}

	public function get_search_form( $constant_or_hidden = [], $search_query = FALSE )
	{
		if ( ! $this->get_setting( 'display_searchform' ) )
			return '';

		if ( $search_query )
			add_filter( 'get_search_query', function( $query ) use( $search_query ){
				return $query ? $query : $search_query;
			} );

		$form = get_search_form( FALSE );

		if ( $constant_or_hidden && ! is_array( $constant_or_hidden ) )
			$constant_or_hidden = [ 'post_type[]' => $this->constant( $constant_or_hidden ) ];

		if ( ! count( $constant_or_hidden ) )
			return $form;

		$form = str_replace( '</form>', '', $form );

		foreach ( $constant_or_hidden as $name => $value )
			$form.= '<input type="hidden" name="'.HTML::escape( $name ).'" value="'.HTML::escape( $value ).'" />';

		return $form.'</form>';
	}

	protected function role_can( $what = 'supported', $user_id = NULL, $fallback = FALSE, $admins = TRUE, $prefix = '_roles' )
	{
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( ! $user_id )
			return $fallback;

		$setting = $this->get_setting( $what.$prefix, [] );

		if ( $admins )
			$setting = array_merge( $setting, [ 'administrator' ] );

		if ( User::hasRole( $setting, $user_id ) )
			return TRUE;

		if ( $admins && User::isSuperAdmin( $user_id ) )
			return TRUE;

		return $fallback;
	}

	// DEFAULT METHOD
	public function render_metabox( $post, $box, $fields = NULL, $context = 'box' )
	{
		if ( is_null( $fields ) )
			$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			echo '<div class="-wrap field-wrap -setting-field" title="'.HTML::escape( $args['description'] ).'">';

			$atts = [
				'field'       => $this->constant( 'metakey_'.$field, $field ),
				'type'        => $args['type'],
				'title'       => $args['title'],
				'placeholder' => $args['title'],
				'values'      => $args['values'],
			];

			if ( 'checkbox' == $atts['type'] )
				$atts['description'] = $atts['title'];

			$this->do_posttype_field( $atts, $post );

			echo '</div>';
		}
	}

	// DEFAULT METHOD
	// INTENDED HOOK: `save_post`, `save_post_[post_type]`
	public function store_metabox( $post_id, $post, $update, $context = 'box' )
	{
		if ( ! $this->is_save_post( $post, $this->posttypes() ) )
			return;

		$fields = $this->get_posttype_fields( $post->post_type );

		foreach ( $fields as $field => $args ) {

			if ( $context != $args['context'] )
				continue;

			$key = $this->constant( 'metakey_'.$field, $field );

			// FIXME: DO THE SAVINGS!
		}
	}

	// FIXME: DEPRECATED
	// CAUTION: tax must be hierarchical
	public function add_meta_box_checklist_terms( $constant, $posttype, $role = NULL, $type = FALSE )
	{
		$taxonomy = $this->constant( $constant );
		$metabox  = $this->classs( $taxonomy );
		$edit     = WordPress::getEditTaxLink( $taxonomy );

		if ( $type )
			$this->remove_meta_box( $constant, $posttype, $type );

		add_meta_box( $metabox,
			$this->get_meta_box_title( $constant, $edit, TRUE ),
			[ $this, 'add_meta_box_checklist_terms_cb' ],
			NULL,
			'side',
			'default',
			[
				'taxonomy' => $taxonomy,
				'metabox'  => $metabox,
				'edit'     => $edit,
				'role'     => $role,
			]
		);
	}

	public function add_meta_box_checklist_terms_cb( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		echo $this->wrap_open( '-admin-metabox' );
			MetaBox::checklistTerms( $post->ID, $box['args'] );
		echo '</div>';
	}

	public function add_meta_box_author( $constant, $callback = 'post_author_meta_box' )
	{
		$posttype = PostType::object( $this->constant( $constant ) );

		if ( PostType::supportBlocks( $posttype->name ) )
			return;

		if ( ! apply_filters( $this->base.'_module_metabox_author', TRUE, $posttype->name ) )
			return;

		if ( ! current_user_can( $posttype->cap->edit_others_posts ) )
			return;

		add_meta_box( 'authordiv', // same as core to override
			$this->get_string( 'author_metabox', $constant, 'misc', __( 'Author' ) ),
			$callback,
			NULL,
			'normal',
			'core'
		);
	}

	public function add_meta_box_excerpt( $constant, $callback = 'post_excerpt_meta_box' )
	{
		$posttype = $this->constant( $constant );

		if ( PostType::supportBlocks( $posttype ) )
			return;

		if ( ! apply_filters( $this->base.'_module_metabox_excerpt', TRUE, $posttype ) )
			return;

		add_meta_box( 'postexcerpt', // same as core to override
			$this->get_string( 'excerpt_metabox', $constant, 'misc', __( 'Excerpt' ) ),
			$callback,
			NULL,
			'normal',
			'high'
		);
	}

	// FIXME: DEPRECATED
	public function remove_meta_box( $constant, $posttype, $type = 'tag' )
	{
		if ( 'tag' == $type )
			remove_meta_box( 'tagsdiv-'.$this->constant( $constant ), $posttype, 'side' );

		else if ( 'cat' == $type )
			remove_meta_box( $this->constant( $constant ).'div', $posttype, 'side' );

		else if ( 'parent' == $type )
			remove_meta_box( 'pageparentdiv', $posttype, 'side' );

		else if ( 'image' == $type )
			remove_meta_box( 'postimagediv', $this->constant( $constant ), 'side' );

		else if ( 'author' == $type )
			remove_meta_box( 'authordiv', $this->constant( $constant ), 'normal' );

		else if ( 'excerpt' == $type )
			remove_meta_box( 'postexcerpt', $posttype, 'normal' );

		else if ( 'submit' == $type )
			remove_meta_box( 'submitdiv', $posttype, 'side' );
	}

	protected function class_metabox( $screen, $context = 'main' )
	{
		add_filter( 'postbox_classes_'.$screen->id.'_'.$this->classs( $context ), function( $classes ) use ( $context ) {
			return array_merge( $classes, [ $this->base.'-wrap', '-admin-postbox', '-'.$this->key, '-'.$this->key.'-'.$context ] );
		} );
	}

	public function get_meta_box_title( $constant = 'post', $url = NULL, $edit_cap = NULL, $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', _x( 'Settings', 'Module: MetaBox Default Title', GEDITORIAL_TEXTDOMAIN ) );

		// problems with block editor
		return $title;

		if ( $info = $this->get_string( 'meta_box_info', $constant, 'misc', NULL ) )
			$title.= ' <span class="postbox-title-info" data-title="info" title="'.HTML::escape( $info ).'">'.HTML::getDashicon( 'editor-help' ).'</span>';

		if ( FALSE === $url || FALSE === $edit_cap )
			return $title;

		if ( is_null( $edit_cap ) )
			$edit_cap = isset( $this->caps['settings'] ) ? $this->caps['settings'] : 'manage_options';

		if ( TRUE === $edit_cap || current_user_can( $edit_cap ) ) {

			if ( is_null( $url ) )
				$url = $this->get_module_url( 'settings' );

			$action = $this->get_string( 'meta_box_action', $constant, 'misc', _x( 'Configure', 'Module: MetaBox Default Action', GEDITORIAL_TEXTDOMAIN ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_meta_box_title_tax( $constant, $url = NULL, $title = NULL )
	{
		$taxonomy = $this->constant( $constant );
		$object   = get_taxonomy( $taxonomy );

		if ( is_null( $title ) )
			$title = $object->labels->name;

		if ( $info = $this->get_string( 'meta_box_info', $constant, 'misc', NULL ) )
			$title.= ' <span class="postbox-title-info" data-title="info" title="'.HTML::escape( $info ).'">'.HTML::getDashicon( 'info' ).'</span>';

		// problems with block editor
		return $title;

		if ( is_null( $url ) )
			$url = WordPress::getEditTaxLink( $taxonomy );

		if ( $url ) {
			$action = $this->get_string( 'meta_box_action', $constant, 'misc', _x( 'Manage', 'Module: MetaBox Default Action', GEDITORIAL_TEXTDOMAIN ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_meta_box_title_posttype( $constant, $url = NULL, $title = NULL )
	{
		$object = PostType::object( $this->constant( $constant ) );

		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', $object->labels->name );

		// problems with block editor
			return $title;

		if ( $info = $this->get_string( 'meta_box_info', $constant, 'misc', NULL ) )
			$title.= ' <span class="postbox-title-info" data-title="info" title="'.HTML::escape( $info ).'">'.HTML::getDashicon( 'info' ).'</span>';

		if ( current_user_can( $object->cap->edit_others_posts ) ) {

			if ( is_null( $url ) )
				$url = WordPress::getPostTypeEditLink( $object->name );

			$action = $this->get_string( 'meta_box_action', $constant, 'misc', _x( 'Manage', 'Module: MetaBox Default Action', GEDITORIAL_TEXTDOMAIN ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant = NULL, $fallback = NULL )
	{
		return $this->get_string( $column.'_column_title', $constant, 'misc', ( is_null( $fallback ) ? $column : $fallback ) );
	}

	protected function require_code( $filenames = 'Templates' )
	{
		foreach ( (array) $filenames as $filename )
			require_once( $this->path.$filename.'.php' );
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

		delete_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked' );

		return TRUE;
	}

	public function get_linked_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		if ( ! $term = Taxonomy::getTerm( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

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
			'post_type'   => $this->posttypes(),
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
	public function get_assoc_post( $post = NULL, $single = FALSE, $published = TRUE )
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
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant )
				Listtable::restrictByTaxonomy(
					$this->constant( $constant ),
					$this->get_string( 'show_option_all', $constant, 'misc', NULL ),
					$this->get_string( 'show_option_none', $constant, 'misc', NULL )
				);
		}
	}

	protected function do_parse_query_taxes( &$query, $taxes, $posttype_constant_key = TRUE )
	{
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant )
				Listtable::parseQueryTaxonomy( $query, $this->constant( $constant ) );
		}
	}

	protected function do_restrict_manage_posts_posts( $tax_constant_key, $posttype_constant_key )
	{
		Listtable::restrictByPosttype(
			$this->constant( $tax_constant_key ),
			$this->constant( $posttype_constant_key )
		);
	}

	protected function do_posts_clauses_taxes( $pieces, $query, $taxes, $posttype_constant_key = TRUE )
	{
		if ( TRUE === $posttype_constant_key ||
			$this->is_current_posttype( $posttype_constant_key ) ) {

			foreach ( (array) $taxes as $constant ) {

				$taxonomy = $this->constant( $constant );

				if ( isset( $query->query['orderby'] ) && 'taxonomy-'.$taxonomy == $query->query['orderby'] )
					return Listtable::orderClausesByTaxonomy( $pieces, $query, $taxonomy );
			}
		}

		return $pieces;
	}

	protected function dashboard_glance_post( $constant )
	{
		return MetaBox::glancePosttype(
			$this->constant( $constant ),
			$this->get_noop( $constant ),
			'-'.$this->slug()
		);
	}

	protected function dashboard_glance_tax( $constant )
	{
		return MetaBox::glanceTaxonomy(
			$this->constant( $constant ),
			$this->get_noop( $constant ),
			'-'.$this->slug()
		);
	}

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL, $posttype = 'post' )
	{
		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( is_null( $title ) )
			$title = $this->get_string( 'column_icon_title', $posttype, 'misc', '' );

		return HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ? $link : FALSE,
			'title'  => $title ? $title : FALSE,
			'class'  => [ '-icon', ( $link ? '-link' : '-info' ) ],
			'target' => $link ? '_blank' : FALSE,
		], Helper::getIcon( $icon ) );
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

	// @REF: https://github.com/scribu/wp-posts-to-posts/wiki
	public function p2p_register( $constant, $posttypes = NULL )
	{
		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( empty( $posttypes ) )
			return FALSE;

		$to  = $this->constant( $constant );
		$p2p = $this->constant( $constant.'_p2p' );

		$args = array_merge( [
			'name'            => $p2p,
			'from'            => $posttypes,
			'to'              => $to,
			'can_create_post' => FALSE,
			'admin_column'    => 'from', // 'any', 'from', 'to', FALSE
			'admin_box'       => [
				'show'    => 'from',
				'context' => 'advanced',
			],
		], $this->strings['p2p'][$constant] );

		$hook = 'geditorial_'.$this->module->name.'_'.$to.'_p2p_args';

		if ( $args = apply_filters( $hook, $args, $posttypes ) )
			if ( p2p_register_connection_type( $args ) )
				$this->p2p = $p2p;
	}

	public function p2p_get_meta( $p2p_id, $meta_key, $before = '', $after = '', $args = [] )
	{
		if ( ! $meta = p2p_get_meta( $p2p_id, $meta_key, TRUE ) )
			return '';

		if ( ! empty( $args['type'] ) && 'text' == $args['type'] )
			$meta = apply_filters( 'string_format_i18n', $meta );

		if ( ! empty( $args['template'] ) )
			$meta = sprintf( $args['template'], $meta );

		if ( ! empty( $args['title'] ) )
			$meta = '<span title="'.HTML::escape( $args['title'] ).'">'.$meta.'</span>';

		return $before.$meta.$after;
	}

	public function p2p_get_meta_row( $constant, $p2p_id, $before = '', $after = '' )
	{
		$row = '';

		if ( ! empty( $this->strings['p2p'][$constant]['fields'] ) )
			foreach ( $this->strings['p2p'][$constant]['fields'] as $field => $args )
				$row.= $this->p2p_get_meta( $p2p_id, $field, $before, $after, $args );

		return $row;
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

	protected function column_row_p2p_to_posttype( $constant, $post )
	{
		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];
		$type  = $this->constant( $constant.'_p2p' );

		if ( ! $p2p_type = p2p_type( $type ) )
			return;

		$p2p   = $p2p_type->get_connected( $post, $extra, 'abstract' );
		$count = count( $p2p->items );

		if ( ! $count )
			return;

		if ( empty( $this->cache_column_icon ) )
			$this->cache_column_icon = $this->get_column_icon( FALSE,
				NULL, $this->strings['p2p'][$constant]['title']['to'] );

		if ( empty( $this->cache_posttypes ) )
			$this->cache_posttypes = PostType::get( 2 );

		$posttypes = array_unique( array_map( function( $r ){
			return $r->post_type;
		}, $p2p->items ) );

		$args = [
			'connected_direction' => 'to',
			'connected_type'      => $type,
			'connected_items'     => $post->ID,
		];

		echo '<li class="-row -book -p2p -connected">';

			echo $this->cache_column_icon;

			echo '<span class="-counted">'.$this->nooped_count( 'connected', $count ).'</span>';

			$list = [];

			foreach ( $posttypes as $posttype )
				$list[] = HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $posttype, 0, $args ),
					'title'  => _x( 'View the connected list', 'Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				], $this->cache_posttypes[$posttype] );

			echo Helper::getJoined( $list, ' <span class="-posttypes">(', ')</span>' );

		echo '</li>';
	}

	protected function column_row_p2p_from_posttype( $constant, $post )
	{
		if ( empty( $this->cache_column_icon ) )
			$this->cache_column_icon = $this->get_column_icon( FALSE,
				NULL, $this->strings['p2p'][$constant]['title']['from'] );

		$extra = [ 'p2p:per_page' => -1, 'p2p:context' => 'admin_column' ];
		$type  = $this->constant( $constant.'_p2p' );

		if ( ! $p2p_type = p2p_type( $type ) )
			return;

		$p2p = $p2p_type->get_connected( $post, $extra, 'abstract' );

		foreach ( $p2p->items as $item ) {
			echo '<li class="-row -book -p2p -connected">';

				if ( current_user_can( 'edit_post', $item->get_id() ) )
					echo $this->get_column_icon( get_edit_post_link( $item->get_id() ),
						NULL, $this->strings['p2p'][$constant]['title']['from'] );
				else
					echo $this->cache_column_icon;

				$args = [
					'connected_direction' => 'to',
					'connected_type'      => $type,
					'connected_items'     => $item->get_id(),
				];

				echo HTML::tag( 'a', [
					'href'   => WordPress::getPostTypeEditLink( $post->post_type, 0, $args ),
					'title'  => _x( 'View all connected', 'Module: P2P', GEDITORIAL_TEXTDOMAIN ),
					'target' => '_blank',
				], Helper::trimChars( $item->get_title(), 85 ) );

				echo $this->p2p_get_meta_row( $constant, $item->p2p_id, ' &ndash; ', '' );

			echo '</li>';
		}
	}

	// should we insert content?
	public function is_content_insert( $posttypes = '', $first_page = TRUE, $embed = FALSE )
	{
		if ( ! $embed && is_embed() )
			return FALSE;

		if ( ! is_main_query() )
			return FALSE;

		if ( ! in_the_loop() )
			return FALSE;

		if ( $first_page && 1 != $GLOBALS['page'] )
			return FALSE;

		if ( FALSE === $posttypes )
			return TRUE;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		else if ( $posttypes && ! is_array( $posttypes ) )
			$posttypes = $this->constant( $posttypes );

		if ( ! is_singular( $posttypes ) )
			return FALSE;

		return TRUE;
	}

	protected function hook_insert_content( $default_priority = 50 )
	{
		$insert = $this->get_setting( 'insert_content', 'none' );

		if ( 'none' == $insert )
			return FALSE;

		add_action( $this->base.'_content_'.$insert,
			[ $this, 'insert_content' ],
			$this->get_setting( 'insert_priority', $default_priority )
		);

		return TRUE;
	}

	// DEFAULT FILTER
	public function get_default_comment_status( $status, $posttype, $comment_type )
	{
		return $this->get_setting( 'comment_status', $status );
	}

	// DEFAULT FILTER
	// increases last menu_order for new posts
	public function wp_insert_post_data_menu_order( $data, $postarr )
	{
		if ( ! $data['menu_order'] && $postarr['post_type'] )
			$data['menu_order'] = WordPress::getLastPostOrder( $postarr['post_type'],
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	protected function _hook_ajax( $nopriv = FALSE, $hook = NULL )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		add_action( 'wp_ajax_'.$hook, [ $this, 'ajax' ] );

		if ( $nopriv )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, 'ajax' ] );
	}

	// DEFAULT FILTER
	public function ajax()
	{
		Ajax::errorWhat();
	}

	// DEFAULT FILTER
	public function tweaks_taxonomy_info( $info, $object, $posttype )
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

	public function get_field_icon( $field, $posttype = 'post', $args = [] )
	{
		switch ( $field ) {
			case 'ot': return 'arrow-up-alt2';
			case 'st': return 'arrow-down-alt2';
			case 'as': return 'admin-users';
		}

		return 'admin-post';
	}

	// DEFAULT FILTER
	public function meta_column_row( $post, $fields, $meta )
	{
		foreach ( $fields as $field => $args ) {

			if ( empty( $meta[$field] ) )
				continue;

			echo '<li class="-row -'.$this->module->name.' -field-'.$field.'">';
				echo $this->get_column_icon( FALSE, $args['icon'], $args['title'] );
				echo $this->display_meta( $meta[$field], $field, $args );
			echo '</li>';
		}
	}

	// DEFAULT METHOD
	public function display_meta( $value, $key = NULL, $field = [] )
	{
		return HTML::escape( $value );
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

	public function icon( $name, $group = NULL )
	{
		return gEditorial()->icon( $name, ( is_null( $group ) ? $this->icon_group : $group ) );
	}

	public function getTablePosts( $atts = [], $extra = [], $posttypes = NULL )
	{
		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		$limit  = $this->limit_sub();
		$paged  = self::paged();
		$offset = ( $paged - 1 ) * $limit;

		$args = array_merge( [
			'posts_per_page'   => $limit,
			'offset'           => $offset,
			'orderby'          => self::orderby( 'ID' ),
			'order'            => self::order( 'DESC' ),
			'post_type'        => $posttypes, // 'any',
			'post_status'      => 'any', // [ 'publish', 'future', 'draft', 'pending' ],
			'suppress_filters' => TRUE,
		], $atts );

		if ( ! empty( $_REQUEST['id'] ) )
			$args['post__in'] = explode( ',', maybe_unserialize( $_REQUEST['id'] ) );

		if ( ! empty( $_REQUEST['type'] ) )
			$args['post_type'] = $extra['type'] = $_REQUEST['type'];

		if ( ! empty( $_REQUEST['parent'] ) )
			$args['post_parent'] = $extra['parent'] = $_REQUEST['parent'];

		if ( 'attachment' == $args['post_type'] && is_array( $args['post_status'] ) )
			$args['post_status'][] = 'inherit';

		$query = new \WP_Query;
		$posts = $query->query( $args );

		$pagination = HTML::tablePagination( $query->found_posts, $query->max_num_pages, $limit, $paged, $extra );

		$pagination['orderby'] = $args['orderby'];
		$pagination['order']   = $args['order'];

		return [ $posts, $pagination ];
	}

	// checks to bail early if metabox/widget is hidden
	protected function check_hidden_metabox( $box, $posttype = FALSE, $after = '' )
	{
		return MetaBox::checkHidden( ( empty( $box['id'] ) ? $this->classs( $box ) : $box['id'] ), $posttype, $after );
	}

	protected function get_blog_users( $fields = NULL, $list = FALSE, $admins = FALSE )
	{
		if ( is_null( $fields ) )
			$fields = [
				'ID',
				'display_name',
				'user_login',
				'user_email',
			];

		$excludes = $this->get_setting( 'excluded_roles', [] );

		if ( $admins )
			$excludes[] = 'administrator';

		$args = [
			'number'       => -1,
			'orderby'      => 'post_count',
			'fields'       => $fields,
			'role__not_in' => $excludes,
			'count_total'  => FALSE,
		];

		if ( $list )
			$args['include'] = (array) $list;

		$query = new \WP_User_Query( $args );

		return $query->get_results();
	}

	protected function do_template_include( $template, $constant )
	{
		if ( is_embed() || is_search() )
			return $template;

		$posttype = $this->constant( $constant );

		if ( $posttype != $GLOBALS['wp_query']->get( 'post_type' ) )
			return $template;

		if ( ! is_404() && ! is_post_type_archive( $posttype ) )
			return $template;

		if ( is_404() ) {

			nocache_headers();
			// WordPress::doNotCache();

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_empty_title(),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
				'is_404'     => TRUE,
			], [ $this, 'template_empty_content' ] );

			$this->filter_append( 'post_class', 'empty-entry' );

		} else {

			Theme::resetQuery( [
				'ID'         => 0,
				'post_title' => $this->template_get_archive_title( $posttype ),
				'post_type'  => $posttype,
				'is_single'  => TRUE,
				'is_archive' => TRUE,
			], [ $this, 'template_archive_content' ] );

			$this->filter_append( 'post_class', 'archive-entry' );
		}

		$this->enqueue_styles();

		defined( 'GNETWORK_DISABLE_CONTENT_ACTIONS' )
			or define( 'GNETWORK_DISABLE_CONTENT_ACTIONS', TRUE );

		defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			or define( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS', TRUE );

		// look again for template
		// return get_singular_template();
		return get_single_template();
	}

	// DEFAULT METHOD: title for overrided empty page
	public function template_get_empty_title( $fallback = NULL )
	{
		if ( $title = URL::prepTitleQuery( $GLOBALS['wp_query']->get( 'name' ) ) )
			return $title;

		if ( is_null( $fallback ) )
			return _x( '[Untitled]', 'Module: Template Title', GEDITORIAL_TEXTDOMAIN );

		return $fallback;
	}

	// DEFAULT METHOD: content for overrided empty page
	public function template_get_empty_content( $atts = [] )
	{
		$text = $this->get_setting( 'empty_content', _x( 'There are no content by this title. Search again or create one.', 'Module: Template Empty', GEDITORIAL_TEXTDOMAIN ) );
		return Text::autoP( trim( $text ) );
	}

	// DEFAULT METHOD: title for overrided archive page
	public function template_get_archive_title( $posttype )
	{
		if ( $title = $this->get_setting( 'archive_title', FALSE ) )
			return $title;

		$object = PostType::object( $posttype );
		return $object->labels->all_items;
	}

	// DEFAULT METHOD: content for overrided archive page
	public function template_get_archive_content( $atts = [] )
	{
		return '';
	}

	// DEFAULT METHOD: button for overrided empty/archive page
	public function template_get_add_new( $posttype, $title = FALSE, $label = NULL )
	{
		$object = PostType::object( $posttype );

		if ( ! current_user_can( $object->cap->create_posts ) )
			return '';

		return HTML::tag( 'a', [
			'href'          => WordPress::getPostNewLink( $object->name, [ 'post_title' => $title ] ),
			'class'         => [ 'button', '-add-posttype', '-add-posttype-'.$object->name ],
			'target'        => '_blank',
			'data-posttype' => $object->name,
		], $label ?: $object->labels->add_new_item );
	}

	// DEFAULT FILTER
	public function template_empty_content( $content )
	{
		$post  = get_post();
		$title = $this->template_get_empty_title( '' );

		$html = $this->template_get_empty_content();
		$html.= $this->get_search_form( [ 'post_type[]' => $post->post_type ], $title );

		// TODO: list other entries that linked to this title

		if ( $add_new = $this->template_get_add_new( $post->post_type, $title ) )
			$html.= '<p class="-actions">'.$add_new.'</p>';

		return HTML::wrap( $html, $this->base.'-empty-content' );
	}

	// DEFAULT FILTER
	public function template_archive_content( $content )
	{
		return HTML::wrap( $this->template_get_archive_content(), $this->base.'-archive-content' );
	}
}

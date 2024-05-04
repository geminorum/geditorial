<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\WordPress;

class Module extends WordPress\Module
{
	use Internals\Assets;
	use Internals\CoreIncludes;
	use Internals\CorePostTypes;
	use Internals\CoreTaxonomies;
	use Internals\DefaultTerms;
	use Internals\SettingsCore;
	use Internals\SettingsFields;
	use Internals\SettingsHelp;
	use Internals\SettingsPostTypes;
	use Internals\SettingsTaxonomies;
	use Internals\Strings;

	public $module;
	public $options;
	public $settings;

	public $enabled  = FALSE;
	public $meta_key = '_ge'; // FIXME: DEPRECATED

	protected $icon_group = 'genericons-neue';

	protected $rest_api_version = 'v1';

	protected $priority_init              = 10;
	protected $priority_init_ajax         = 12;
	protected $priority_restapi_init      = 10;
	protected $priority_current_screen    = 10;
	protected $priority_admin_menu        = 10;
	protected $priority_adminbar_init     = 10;
	protected $priority_template_redirect = 10;
	protected $priority_template_include  = 10;

	protected $positions = []; // menu positions by context/constant
	protected $deafults  = []; // default settings

	protected $constants = [];
	protected $strings   = [];
	protected $features  = [];
	protected $fields    = [];

	protected $partials         = [];
	protected $partials_remote  = [];
	protected $process_disabled = [];

	protected $disable_no_customs    = FALSE; // not hooking module if has no posttypes/taxonomies
	protected $disable_no_posttypes  = FALSE; // not hooking module if has no posttypes
	protected $disable_no_taxonomies = FALSE; // not hooking module if has no taxonomies

	protected $image_sizes  = [];
	protected $kses_allowed = [];

	protected $scripts = [];
	protected $buttons = [];
	protected $errors  = [];
	protected $cache   = [];

	protected $caps = [
		'default'   => 'manage_options',
		'settings'  => 'manage_options',
		'reports'   => 'edit_others_posts',
		'tools'     => 'edit_others_posts',
		'roles'     => 'edit_users',
		'adminbar'  => 'edit_others_posts',
		'dashboard' => 'edit_others_posts',

		// 'paired_create' => 'manage_options', // to restrict main post
		// 'paired_delete' => 'manage_options', // to restrict main post
	];

	protected $root_key = FALSE; // ROOT CONSTANT
	protected $_p2p     = FALSE; // P2P ENABLED/Connection Type
	protected $_o2o     = FALSE; // O2O ENABLED/Connection Type
	protected $_paired  = FALSE; // PAIRED API ENABLED/taxonomy paired

	protected $scripts_printed = FALSE;
	protected $current_queried = FALSE; // usually contains `get_queried_object_id()`

	public function __construct( &$module, &$options, $root, $locale = NULL )
	{
		$this->base = 'geditorial';
		$this->key  = $module->name;
		$this->path = Core\File::normalize( $root.$module->folder.'/' );
		$this->site = get_current_blog_id();

		$this->module  = $module;
		$this->options = $options;

		if ( FALSE !== $module->disabled )
			return;

		$this->setup_textdomain( $locale );

		if ( $this->remote() )
			$this->setup_remote();

		else
			$this->setup();
	}

	protected function setup_textdomain( $locale = NULL, $domain = NULL )
	{
		global $wp_textdomain_registry;

		if ( FALSE === $this->module->i18n )
			return FALSE;

		if ( 'adminonly' === $this->module->i18n && ! is_admin() )
			return FALSE;

		if ( 'restonly' === $this->module->i18n && ! Core\WordPress::isREST() )
			return FALSE;

		if ( 'frontonly' === $this->module->i18n && is_admin() )
			return FALSE;

		if ( is_null( $domain ) )
			$domain = $this->get_textdomain();

		if ( ! $domain )
			return FALSE;

		if ( is_null( $locale ) )
			$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), $this->base );

		$path = GEDITORIAL_DIR."languages/{$this->module->folder}";
		$wp_textdomain_registry->set_custom_path( $domain, $path );

		return load_textdomain( $domain, $path."/{$locale}.mo" );
	}

	protected function setup_remote( $args = [] )
	{
		$this->require_code( $this->partials_remote );
	}

	protected function setup( $args = [] )
	{
		$admin = is_admin();
		$ajax  = Core\WordPress::isAJAX();
		$ui    = Core\WordPress::mustRegisterUI( FALSE );

		if ( $admin && $ui && ( TRUE === $this->module->configure || 'settings' === $this->module->configure ) )
			add_action( $this->hook_base( 'settings_load' ), [ $this, 'register_settings' ] );

		if ( $this->setup_disabled() )
			return FALSE;

		$this->require_code( $this->partials );

		if ( method_exists( $this, 'plugin_loaded' ) )
			add_action( sprintf( '%s_loaded', $this->base ), [ $this, 'plugin_loaded' ] );

		if ( method_exists( $this, 'o2o_init' ) )
			$this->action( 'o2o_init' ); // NOTE: runs on `wp_loaded`

		else if ( method_exists( $this, 'p2p_init' ) )
			$this->action( 'p2p_init' ); // NOTE: runs on `wp_loaded`

		if ( method_exists( $this, 'widgets_init' ) && $this->get_setting( 'widget_support' ) )
			$this->action( 'widgets_init' );

		if ( ! $ajax && method_exists( $this, 'tinymce_strings' ) )
			add_filter( $this->hook_base( 'tinymce_strings' ), [ $this, 'tinymce_strings' ] );

		if ( method_exists( $this, 'meta_init' ) )
			add_action( $this->hook_base( 'meta_init' ), [ $this, 'meta_init' ], 10, 2 );

		if ( method_exists( $this, 'importer_init' ) )
			add_action( $this->hook_base( 'importer_init' ), [ $this, 'importer_init' ], 10, 2 );

		if ( method_exists( $this, 'elementor_register' ) )
			add_action( 'elementor/widgets/register', [ $this, 'elementor_register' ], 10, 1 );

		add_action( 'after_setup_theme', [ $this, '_after_setup_theme' ], 1 );
		add_action( 'rest_api_init', [ $this, '_rest_api_init' ], $this->priority_restapi_init );

		if ( method_exists( $this, 'after_setup_theme' ) )
			$this->action( 'after_setup_theme', 0, 20 );

		$this->action( 'init', 0, $this->priority_init );

		if ( $ui && method_exists( $this, 'adminbar_init' ) && $this->get_setting( 'adminbar_summary' ) )
			add_action( $this->hook_base( 'adminbar' ), [ $this, 'adminbar_init' ], $this->priority_adminbar_init, 2 );

		if ( $admin ) {

			add_action( 'admin_init', [ $this, '_admin_init' ], 1 );

			if ( method_exists( $this, 'admin_init' ) )
				$this->action( 'admin_init' );

			if ( $ui && method_exists( $this, 'admin_menu' ) )
				$this->action( 'admin_menu', 0, $this->priority_admin_menu );

			if ( $ajax )
				add_action( 'init', [ $this, '_init_ajax' ], $this->priority_init_ajax );

			if ( $ui )
				add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard' ] );

			if ( ( $ui || $ajax ) && method_exists( $this, 'register_shortcode_ui' ) )
				$this->action( 'register_shortcode_ui' );

			if ( $ui && method_exists( $this, 'add_meta_boxes' ) )
				$this->action( 'add_meta_boxes', 2, 12 );

			if ( $ui && method_exists( $this, 'current_screen' ) )
				$this->action( 'current_screen', 1, $this->priority_current_screen );

			if ( $ui && method_exists( $this, 'reports_settings' ) )
				add_action( $this->hook_base( 'reports_settings' ), [ $this, 'reports_settings' ] );

			if ( $ui && method_exists( $this, 'tools_settings' ) )
				add_action( $this->hook_base( 'tools_settings' ), [ $this, 'tools_settings' ] );

			if ( $ui && method_exists( $this, 'roles_settings' ) )
				add_action( $this->hook_base( 'roles_settings' ), [ $this, 'roles_settings' ] );

			if ( $ui && method_exists( $this, 'imports_settings' ) )
				add_action( $this->hook_base( 'imports_settings' ), [ $this, 'imports_settings' ] );

			if ( $ui && method_exists( $this, 'tool_box_content' ) )
				$this->action( 'tool_box' );

		} else {

			if ( $ui && method_exists( $this, 'template_redirect' ) )
				add_action( 'template_redirect', [ $this, 'template_redirect' ], $this->priority_template_redirect );

			if ( $ui && method_exists( $this, 'template_include' ) )
				add_filter( 'template_include', [ $this, 'template_include' ], $this->priority_template_include );
		}

		return TRUE;
	}

	public function setup_disabled()
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

	// NOTE: ALWAYS HOOKED
	public function _after_setup_theme()
	{
		$this->constants = $this->filters( 'constants', $this->get_global_constants(), $this->module );
		$this->fields    = $this->filters( 'fields', $this->get_global_fields(), $this->module );
	}

	// NOTE: ALWAYS HOOKED
	public function _rest_api_init()
	{
		if ( method_exists( $this, 'setup_restapi' ) )
			$this->setup_restapi();

		if ( method_exists( $this, 'pairedcore__hook_sync_paired' ) )
			$this->pairedcore__hook_sync_paired();
	}

	// NOTE: ALWAYS HOOKED: PRIORITY: `12`
	public function _init_ajax()
	{
		if ( method_exists( $this, 'setup_ajax' ) )
			$this->setup_ajax();

		if ( method_exists( $this, 'pairedcore__hook_sync_paired_for_ajax' ) )
			$this->pairedcore__hook_sync_paired_for_ajax();

		if ( method_exists( $this, 'do_ajax' ) )
			$this->_hook_ajax();
	}

	// NOTE: MUST ALWAYS CALLED BY THE MODULE
	public function init()
	{
		$this->actions( 'init', $this->options, $this->module );

		$this->features = $this->filters( 'features', $this->get_global_features(), $this->module );
		$this->strings  = $this->filters( 'strings', $this->get_global_strings(), $this->module );
	}

	// NOTE: ALWAYS HOOKED
	public function _admin_init()
	{
		if ( method_exists( $this, 'exports_do_check_requests' ) )
			$this->exports_do_check_requests();

		$this->init_default_terms();

		$prefix   = self::sanitize_base( $this->key );
		$callback = static function ( $key, $value ) use ( $prefix ) {
			return [ sprintf( '%s-%s.php', $prefix, $key ) => $value ];
		};

		foreach ( $this->get_module_templates() as $constant => $templates ) {

			if ( empty( $templates ) )
				continue;

			$this->filter_set(
				sprintf( 'theme_%s_templates', $this->constant( $constant ) ),
				Core\Arraay::mapAssoc( $callback, $templates )
			);
		}
	}

	protected function get_textdomain()
	{
		if ( NULL === $this->module->textdomain )
			return $this->base;

		if ( FALSE === $this->module->textdomain )
			return FALSE;

		return empty( $this->module->textdomain )
			? $this->classs()
			: $this->module->textdomain;
	}

	protected function get_global_settings() { return []; }
	protected function get_global_constants() { return []; }
	protected function get_global_strings() { return []; }
	protected function get_global_features() { return []; }
	protected function get_global_fields() { return []; }

	protected function get_module_templates() { return []; }
	protected function get_module_icons() { return []; }

	// FIXME: get dashboard menu for the module
	protected function get_module_links()
	{
		$links  = [];
		$screen = get_current_screen();

		if ( method_exists( $this, 'reports_settings' ) && ! Settings::isReports( $screen ) )
			foreach ( $this->append_sub( [], 'reports' ) as $sub => $title )
				$links[] = [
					'context' => 'reports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'reports', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Reports', 'Module: Extra Link: Reports', 'geditorial-admin' ), $title ),
				];

		if ( method_exists( $this, 'tools_settings' ) && ! Settings::isTools( $screen ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$links[] = [
					'context' => 'tools',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'tools', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Tools', 'Module: Extra Link: Tools', 'geditorial-admin' ), $title ),
				];

		if ( method_exists( $this, 'roles_settings' ) && ! Settings::isRoles( $screen ) )
			foreach ( $this->append_sub( [], 'roles' ) as $sub => $title )
				$links[] = [
					'context' => 'roles',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'roles', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Roles', 'Module: Extra Link: Roles', 'geditorial-admin' ), $title ),
				];

		if ( method_exists( $this, 'imports_settings' ) && ! Settings::isImports( $screen ) )
			foreach ( $this->append_sub( [], 'imports' ) as $sub => $title )
				$links[] = [
					'context' => 'imports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'imports', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Imports', 'Module: Extra Link: Tools', 'geditorial-admin' ), $title ),
				];

		if ( isset( $this->caps['settings'] ) && ! Settings::isSettings( $screen ) && $this->cuc( 'settings' ) )
			$links[] = [
				'context' => 'settings',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $this->get_module_url( 'settings' ),
				/* translators: %s: module title */
				'title'   => sprintf( _x( '%s Settings', 'Module: Extra Link: Settings', 'geditorial-admin' ), $this->module->title ),
			];

		if ( $docs = $this->get_module_url( 'docs' ) )
			$links[] = [
				'context' => 'docs',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $docs,
				/* translators: %s: module title */
				'title'   => sprintf( _x( '%s Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ), $this->module->title ),
			];

		if ( 'config' != $this->module->name )
			$links[] = [
				'context' => 'docs',
				'sub'     => FALSE,
				'text'    => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
				'url'     => Settings::getModuleDocsURL( FALSE ),
				'title'   => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial-admin' ),
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
			case 'rols'      : $url = Settings::rolesURL(); break;
			case 'imports'   : $url = Settings::importsURL(); break;
			case 'docs'      : $url = Settings::getModuleDocsURL( $this->module ); $sub = FALSE; break;
			case 'settings'  : $url = add_query_arg( 'module', $this->module->name, Settings::settingsURL() ); $sub = FALSE; break;
			case 'listtable' : $url = $this->get_adminpage_url( TRUE, [], 'adminmenu' ); $sub = FALSE; break;
			default          : $url = Core\URL::current();
		}

		if ( FALSE === $url )
			return FALSE;

		return add_query_arg( array_merge( [
			'sub' => $sub,
		], $extra ), $url );
	}

	protected function get_adminpage_url( $full = TRUE, $extra = [], $context = 'mainpage', $admin_base = NULL )
	{
		$page = in_array( $context, [ 'mainpage', 'adminmenu' ], TRUE )
			? $this->classs()
			: $this->classs( $context );

		if ( ! $full )
			return $page;

		if ( is_null( $admin_base ) )
			$admin_base = in_array( $context, [ 'adminmenu', 'printpage', 'framepage', 'newpost', 'importitems' ], TRUE )
				? get_admin_url( NULL, 'index.php' )
				: get_admin_url( NULL, 'admin.php' );

		return add_query_arg( array_merge( [ 'page' => $page ], $extra ), $admin_base );
	}

	// LEGACY: do not use thickbox anymore!
	// NOTE: must `add_thickbox()` on load
	public function do_render_thickbox_mainbutton( $post, $context = 'framepage', $extra = [], $inline = FALSE, $width = '800' )
	{
		// for inline only
		// modal id must be: `{$base}-{$module}-thickbox-{$context}`
		if ( $inline && $context && method_exists( $this, 'admin_footer_'.$context ) )
			$this->action( 'admin_footer', 0, 20, $context );

		$name  = Helper::getPostTypeLabel( $post->post_type, 'singular_name' );
		$title = $this->get_string( 'mainbutton_title', $context ?: $post->post_type, 'metabox', NULL );
		$text  = $this->get_string( 'mainbutton_text', $context ?: $post->post_type, 'metabox', $name );

		if ( $inline )
			// WTF: thickbox bug: does not process the arg after `TB_inline`!
			$link = '#TB_inline?dummy=dummy&width='.$width.'&inlineId='.$this->classs( 'thickbox', $context ).( $extra ? '&'.http_build_query( $extra ) : '' ); // &modal=true
		else
			// WTF: thickbox bug: does not pass the args after `TB_iframe`!
			$link = $this->get_adminpage_url( TRUE, array_merge( [
				'linked'   => $post->ID,
				'noheader' => 1,
				'width'    => $width,
			], $extra, [ 'TB_iframe' => 'true' ] ), $context );

		$html = Core\HTML::tag( 'a', [
			'href'  => $link,
			'id'    => $this->classs( 'mainbutton', $context ),
			'class' => [ 'button', '-button', '-button-full', '-button-icon', '-mainbutton', 'thickbox' ],
			'title' => $title ? sprintf( $title, WordPress\Post::title( $post, $name ), $name ) : FALSE,
		], sprintf( $text, Helper::getIcon( $this->module->icon ), $name ) );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}

	// check if this module loaded as remote for another blog's editorial module
	public function remote()
	{
		if ( ! $this->root_key )
			return FALSE;

		if ( ! defined( $this->root_key ) )
			return FALSE;

		if ( constant( $this->root_key ) == $this->site )
			return FALSE;

		return TRUE;
	}

	public function constant_in( $constant, $array )
	{
		if ( ! $constant )
			return FALSE;

		if ( ! $key = $this->constant( $constant ) )
			return FALSE;

		return in_array( $key, $array, TRUE );
	}

	protected function settings_insert_priority_option( $default = 10, $prefix = FALSE )
	{
		return [
			'field'   => 'insert_priority'.( $prefix ? '_'.$prefix : '' ),
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Module: Setting Title', 'geditorial-admin' ),
			'default' => $default,
		];
	}

	public function constant( $key, $default = FALSE )
	{
		if ( ! $key )
			return $default;

		if ( isset( $this->constants[$key] ) )
			return $this->constants[$key];

		if ( 'post_cpt' === $key || 'post_posttype' === $key )
			return 'post';

		if ( 'page_cpt' === $key || 'page_posttype' === $key )
			return 'page';

		return $default;
	}

	public function constants( $keys, $pre = [] )
	{
		foreach ( (array) $keys as $key )
			if ( $constant = $this->constant( $key ) )
				$pre[] = $constant;

		return Core\Arraay::prepString( $pre );
	}

	public function constant_plural( $key, $default = FALSE )
	{
		if ( ! $key )
			return $default;

		if ( ! $singular = $this->constant( $key ) )
			return $default;

		if ( is_array( $singular ) )
			return $singular; // already defined

		if ( ! $plural = $this->constant( sprintf( '%s_plural', $key ) ) )
			return [ $singular, Core\L10n::pluralize( $singular ) ];

		return [ $singular, $plural ];
	}

	public function slug()
	{
		return str_replace( '_', '-', $this->module->name );
	}

	// NOTE: features are `TRUE` by default
	public function get_feature( $field, $fallback = TRUE )
	{
		$settings = isset( $this->options->settings ) ? $this->options->settings : [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->features ) )
			return $this->features[$field];

		return $fallback;
	}

	public function get_setting( $field, $fallback = NULL )
	{
		$settings = isset( $this->options->settings ) ? $this->options->settings : [];

		if ( array_key_exists( $field, $settings ) )
			return $settings[$field];

		if ( array_key_exists( $field, $this->deafults ) )
			return $this->deafults[$field];

		return $fallback;
	}

	public function get_setting_fallback( $field, $fallback, $empty = '' )
	{
		$settings = isset( $this->options->settings ) ? $this->options->settings : [];

		if ( array_key_exists( $field, $settings ) ) {

			if ( '0' === $settings[$field] )
				return $empty;

			if ( ! empty( $settings[$field] ) )
				return $settings[$field];
		}

		if ( array_key_exists( $field, $this->deafults ) )
			return $this->deafults[$field];

		return $fallback;
	}

	// check arrays with support of old settings
	public function in_setting( $item, $field, $default = [] )
	{
		$setting = $this->get_setting( $field );

		if ( FALSE === $setting || TRUE === $setting )
			return $setting;

		if ( is_null( $setting ) )
			$setting = $default;

		return in_array( $item, (array) $setting, TRUE );
	}

	/**
	 * Updates module options upon out-of-context manipulations.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return bool $updated
	 */
	public function update_option( $key, $value )
	{
		return gEditorial()->update_module_option( $this->module->name, $key, $value );
	}

	protected function _hook_post_updated_messages( $constant )
	{
		add_filter( 'post_updated_messages', function ( $messages ) use ( $constant ) {

			$posttype  = $this->constant( $constant );
			$generated = Helper::generatePostTypeMessages( Helper::getPostTypeLabel( $posttype, 'noop' ), $posttype );

			return array_merge( $messages, [ $posttype => $generated ] );
		} );
	}

	protected function _hook_bulk_post_updated_messages( $constant )
	{
		add_filter( 'bulk_post_updated_messages', function ( $messages, $counts ) use ( $constant ) {

			$posttype  = $this->constant( $constant );
			$generated = Helper::generateBulkPostTypeMessages( Helper::getPostTypeLabel( $posttype, 'noop' ), $counts, $posttype );

			return array_merge( $messages, [ $posttype => $generated ] );
		}, 10, 2 );
	}

	protected function register_shortcode( $constant, $callback = NULL, $force = FALSE )
	{
		if ( ! $force && ! $this->get_setting( 'shortcode_support', FALSE ) )
			return;

		if ( is_null( $callback ) && method_exists( $this, $constant ) )
			$callback = [ $this, $constant ];

		$shortcode = $this->constant( $constant );

		remove_shortcode( $shortcode );
		add_shortcode( $shortcode, $callback );

		add_filter( $this->hook_base( 'shortcode', $shortcode ), $callback, 10, 3 );
	}

	// DEFAULT FILTER
	public function calendar_post_row_title( $title, $post, $the_day, $calendar_args )
	{
		if ( ! $this->posttype_supported( $post->post_type ) )
			return $title;

		if ( ! $linked = $this->get_linked_to_posts( $post->ID, TRUE ) )
			return $title;

		return $title.' – '.WordPress\Post::title( $linked );
	}

	// NOTE: better to be on module-core than the internal
	public function default_calendar( $default = 'gregorian' )
	{
		return $this->get_setting( 'calendar_type', $default );
	}

	public function get_search_form( $constant_or_hidden = [], $search_query = FALSE )
	{
		if ( ! $this->get_setting( 'display_searchform' ) )
			return '';

		if ( $search_query )
			add_filter( 'get_search_query', static function ( $query ) use ( $search_query ) {
				return $query ? $query : $search_query;
			} );

		$form = get_search_form( FALSE );

		if ( $constant_or_hidden && ! is_array( $constant_or_hidden ) )
			$constant_or_hidden = [ 'post_type[]' => $this->constant( $constant_or_hidden ) ];

		if ( ! count( $constant_or_hidden ) )
			return $form;

		$form = str_replace( '</form>', '', $form );

		foreach ( $constant_or_hidden as $name => $value )
			$form.= '<input type="hidden" name="'.Core\HTML::escape( $name ).'" value="'.Core\HTML::escape( $value ).'" />';

		return $form.'</form>';
	}

	/**
	 * Gets default roles for use in settings.
	 *
	 * @param  array $extra_excludes
	 * @param  bool  $filtered
	 * @return array $rols
	 */
	protected function get_settings_default_roles( $extra_excludes = [], $force_include = [], $filtered = TRUE )
	{
		$supported = WordPress\User::getAllRoleList( $filtered );
		$excluded  = Settings::rolesExcluded( $extra_excludes );

		return array_merge( array_diff_key( $supported, array_flip( $excluded ), (array) $force_include ) );
	}

	// NOTE: accepts array and performs `OR` check
	protected function role_can( $whats = 'supported', $user_id = NULL, $fallback = FALSE, $admins = TRUE, $prefix = '_roles' )
	{
		if ( is_null( $whats ) )
			return TRUE;

		if ( FALSE === $whats )
			return FALSE;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		if ( ! $user_id )
			return $fallback;

		if ( $admins && WordPress\User::isSuperAdmin( $user_id ) )
			return TRUE;

		foreach ( (array) $whats as $what ) {

			$setting = $this->get_setting( $what.$prefix, [] );

			if ( TRUE === $setting )
				return $setting;

			if ( FALSE === $setting || ( empty( $setting ) && ! $admins ) )
				continue; // check others

			if ( $admins )
				$setting = array_merge( $setting, [ 'administrator' ] );

			if ( WordPress\User::hasRole( $setting, $user_id ) )
				return TRUE;
		}

		return $fallback;
	}

	protected function _metabox_remove_subterm( $screen, $subterms = FALSE )
	{
		if ( $subterms )
			remove_meta_box( $subterms.'div', $screen->post_type, 'side' );
	}

	protected function _hook_store_metabox( $posttype )
	{
		if ( $posttype )
			add_action( sprintf( 'save_post_%s', $posttype ), [ $this, 'store_metabox' ], 20, 3 );
	}

	protected function class_metabox( $screen, $context = 'mainbox' )
	{
		add_filter( 'postbox_classes_'.$screen->id.'_'.$this->classs( $context ), function ( $classes ) use ( $context ) {
			return array_merge( $classes, [ $this->base.'-wrap', '-admin-postbox', '-'.$this->key, '-'.$this->key.'-'.$context ] );
		} );
	}

	// TODO: filter the results
	// FIXME MUST DEPRECATE
	public function get_meta_box_title( $constant = 'post', $url = NULL, $edit_cap = NULL, $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'metabox_title', $constant, 'metabox', NULL );

		// DEPRECATED: for back-comp only
		if ( is_null( $title ) )
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', _x( 'Settings', 'Module: MetaBox Default Title', 'geditorial-admin' ) );

		return $title; // <-- // FIXME: problems with block editor

		// TODO: 'metabox_icon'
		if ( $info = $this->get_string( 'metabox_info', $constant, 'metabox', NULL ) )
			$title.= ' <span class="postbox-title-info" style="display:none" data-title="info" title="'.Core\HTML::escape( $info ).'">'.Core\HTML::getDashicon( 'editor-help' ).'</span>';

		if ( FALSE === $url || FALSE === $edit_cap )
			return $title;

		if ( is_null( $edit_cap ) )
			$edit_cap = isset( $this->caps['settings'] ) ? $this->caps['settings'] : 'manage_options';

		if ( TRUE === $edit_cap || current_user_can( $edit_cap ) ) {

			if ( is_null( $url ) )
				$url = $this->get_module_url( 'settings' );

			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Configure', 'Module: MetaBox Default Action', 'geditorial-admin' ) );
			$title.= ' <span class="postbox-title-action"><a href="'.esc_url( $url ).'" target="_blank">'.$action.'</a></span>';
		}

		return $title;
	}

	public function get_column_title( $column, $constant = NULL, $fallback = NULL )
	{
		$title = $this->get_string( $column.'_column_title', $constant, 'misc', ( is_null( $fallback ) ? $column : $fallback ) );
		return $this->filters( 'column_title', $title, $column, $constant, $fallback );
	}

	public function get_column_title_posttype( $constant, $taxonomy = FALSE, $fallback = NULL )
	{
		$title = Helper::getPostTypeLabel( $this->constant( $constant ), 'column_title', 'name', $fallback );
		return $this->filters( 'column_title', $title, $taxonomy, $constant, $fallback );
	}

	public function get_column_title_taxonomy( $constant, $posttype = FALSE, $fallback = NULL )
	{
		$title = Helper::getTaxonomyLabel( $this->constant( $constant ), 'column_title', 'name', $fallback );
		return $this->filters( 'column_title', $title, $posttype, $constant, $fallback );
	}

	public function get_column_title_icon( $column, $constant = NULL, $fallback = NULL )
	{
		$title = $this->get_column_title( $column, $constant, $fallback );
		return sprintf( '<span class="-column-icon %3$s" title="%2$s">%1$s</span>', $title, esc_attr( $title ), $this->classs( $column ) );
	}

	public function is_save_post( $post, $constant = FALSE )
	{
		if ( $constant ) {

			if ( is_array( $constant ) && ! in_array( $post->post_type, $constant, TRUE ) )
				return FALSE;

			if ( ! is_array( $constant ) && $post->post_type != $this->constant( $constant ) )
				return FALSE;
		}

		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) )
			return FALSE;

		return TRUE;
	}

	// for ajax calls on quick edit
	// OLD: `is_inline_save()`
	public function is_inline_save_posttype( $target = FALSE, $request = NULL, $key = 'post_type' )
	{
		if ( ! Core\WordPress::isAdminAJAX() )
			return FALSE;

		if ( is_null( $request ) )
			$request = $_REQUEST;

		if ( empty( $request['action'] )
			|| 'inline-save' != $request['action'] )
				return FALSE;

		if ( empty( $request[$key] ) )
			return FALSE;

		if ( is_array( $target )
			&& ! in_array( $request[$key], $target, TRUE ) )
				return FALSE;

		if ( $target
			&& ! is_array( $target )
			&& $request[$key] != $this->constant( $target ) )
				return FALSE;

		return $request[$key];
	}

	// for ajax calls on quick edit
	public function is_inline_save_taxonomy( $target = FALSE, $request = NULL, $key = 'taxonomy' )
	{
		if ( ! Core\WordPress::isAdminAJAX() )
			return FALSE;

		if ( is_null( $request ) )
			$request = $_REQUEST;

		if ( empty( $request['action'] )
			|| ! in_array( $request['action'], [ 'add-tag', 'inline-save-tax' ], TRUE ) )
				return FALSE;

		if ( empty( $request[$key] ) )
			return FALSE;

		if ( is_array( $target )
			&& ! in_array( $request[$key], $target, TRUE ) )
				return FALSE;

		if ( $target
			&& ! is_array( $target )
			&& $request[$key] != $this->constant( $target ) )
				return FALSE;

		return $request[$key];
	}

	// DEFAULT METHOD
	public function get_linked_to_posts( $post = NULL, $single = FALSE, $published = TRUE )
	{
		if ( $this->_paired && method_exists( $this, 'paired_get_constants' ) ) {

			if ( $constants = $this->paired_get_constants() )
				return $this->paired_do_get_to_posts( $constants[0], $constants[1], $post, $single, $published );
		}

		return FALSE;
	}

	// @REF: https://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
	protected function _hook_editform_readonly_title()
	{
		add_action( 'edit_form_after_title', function ( $post ) {
			$html = WordPress\Post::title( $post );
			$info = Settings::fieldAfterIcon( '#', _x( 'This Title is Auto-Generated', 'Module: ReadOnly Title Info', 'geditorial-admin' ) );
			echo $this->wrap(
				$html.' '.$info,
				'-readonly-title',
				TRUE,
				sprintf( '%s-readonlytitle', $this->base )
			);
		}, 1, 1 );
	}

	protected function _hook_editform_meta_summary( $fields = NULL )
	{
		add_action( 'edit_form_after_title', function ( $post ) use ( $fields ) {
			echo $this->wrap( Template::metaSummary( [
				'echo'   => FALSE,
				'id'     => $post->ID,
				'type'   => $post->post_type,
				'fields' => $fields,
			] ), '-meta-summary' );
		}, 1, 9 );
	}

	public function get_column_icon( $link = FALSE, $icon = NULL, $title = NULL, $posttype = 'post', $extra = [] )
	{
		if ( is_null( $icon ) )
			$icon = $this->module->icon;

		if ( is_null( $title ) )
			$title = $this->get_string( 'column_icon_title', $posttype, 'misc', '' );

		return Core\HTML::tag( ( $link ? 'a' : 'span' ), [
			'href'   => $link ?: FALSE,
			'title'  => $title ?: FALSE,
			'class'  => array_merge( [ '-icon', ( $link ? '-link' : '-info' ) ], (array) $extra ),
			'target' => $link ? '_blank' : FALSE,
		], Helper::getIcon( $icon ) );
	}

	// NOTE: adds the `{$module_key}-enabled` class to body in admin
	public function _admin_enabled( $extra = [] )
	{
		add_action( 'admin_body_class', function ( $classes ) use ( $extra ) {
			return trim( $classes ).' '.Core\HTML::prepClass( $this->classs( 'enabled' ), $extra );
		} );
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

		add_action( $this->hook_base( 'content', $insert ),
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
	// USAGE: `$this->filter( 'wp_insert_post_data', 2, 9, 'menu_order' );`
	public function wp_insert_post_data_menu_order( $data, $postarr )
	{
		if ( ! $data['menu_order'] && $postarr['post_type'] )
			$data['menu_order'] = WordPress\PostType::getLastMenuOrder( $postarr['post_type'],
				( isset( $postarr['ID'] ) ? $postarr['ID'] : '' ) ) + 1;

		return $data;
	}

	protected function _hook_ajax( $auth = TRUE, $hook = NULL, $method = 'do_ajax' )
	{
		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'wp_ajax_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'wp_ajax_nopriv_'.$hook, [ $this, $method ] );
	}

	protected function _hook_post( $auth = TRUE, $hook = NULL, $method = 'post' )
	{
		if ( ! is_admin() )
			return;

		if ( is_null( $hook ) )
			$hook = $this->hook();

		if ( is_null( $auth ) || TRUE === $auth )
			add_action( 'admin_post_'.$hook, [ $this, $method ] );

		if ( is_null( $auth ) || FALSE === $auth )
			add_action( 'admin_post_nopriv_'.$hook, [ $this, $method ] );
	}

	// DEFAULT FILTER
	public function post()
	{
		wp_die();
	}

	// MAYBE: move to `Visual` Main
	public function get_posttype_field_icon( $field, $posttype = 'post', $args = [] )
	{
		switch ( $field ) {
			case 'over_title': return 'arrow-up-alt2';
			case 'sub_title' : return 'arrow-down-alt2';
			case 'highlight' : return 'pressthis';
			case 'byline'    : return 'admin-users';
			case 'published' : return 'calendar-alt';
			case 'lead'      : return 'editor-paragraph';
			case 'label'     : return 'megaphone';
			case 'days'      : return 'backup';
			case 'hours'     : return 'clock';
		}

		if ( ! empty( $args['type'] ) ) {
			switch ( $args['type'] ) {
				case 'email'    : return 'email';
				case 'phone'    : return 'phone';
				case 'mobile'   : return 'smartphone';
				case 'identity' : return 'id-alt';
				case 'iban'     : return 'bank';
				case 'isbn'     : return 'book';
				case 'date'     : return 'calendar';
				case 'datetime' : return 'calendar-alt';
			}
		}

		return 'admin-post';
	}

	public function icon( $name, $group = NULL )
	{
		return gEditorial()->icon( $name, ( is_null( $group ) ? $this->icon_group : $group ) );
	}

	// checks to bail early if metabox/widget is hidden
	protected function check_hidden_metabox( $box, $posttype = FALSE, $after = '' )
	{
		if ( FALSE === $box )
			return FALSE;

		return MetaBox::checkHidden( ( empty( $box['id'] ) ? $this->classs( $box ) : $box['id'] ), $posttype, $after );
	}

	// TODO: move to `MetaBox` main
	protected function check_draft_metabox( $box, $post, $message = NULL )
	{
		if ( ! in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			return FALSE;

		if ( is_null( $message ) )
			$message = _x( 'You can see the contents once you\'ve saved this post for the first time.', 'Module: Draft Metabox', 'geditorial-admin' );

		Core\HTML::desc( $message, TRUE, 'field-wrap -empty' );

		return TRUE;
	}

	// TODO: customize column position/sorting
	// NOTE: appends custom meta fields into Terms Module
	protected function _hook_terms_meta_field( $constant, $field, $args = [] )
	{
		if ( ! gEditorial()->enabled( 'terms' ) )
			return FALSE;

		$taxonomy = $this->constant( $constant );
		$title    = $this->get_string( 'field_title', $field, 'terms_meta_field', $field );
		$desc     = $this->get_string( 'field_desc', $field, 'terms_meta_field', '' );

		add_filter( $this->hook_base( 'terms', 'supported_fields' ),
			static function ( $list, $tax ) use ( $taxonomy, $field ) {

				if ( FALSE === $tax || $tax === $taxonomy )
					$list[] = $field;

				return $list;
			}, 12, 2 );

		add_filter( $this->hook_base( 'terms', 'list_supported_fields' ),
			static function ( $list, $tax ) use ( $taxonomy, $field, $title ) {

				if ( FALSE === $tax || $tax === $taxonomy )
					$list[$field] = $title;

				return $list;
			}, 12, 2 );

		add_filter( $this->hook_base( 'terms', 'supported_field_taxonomies' ),
			static function ( $taxonomies, $_field ) use ( $taxonomy, $field ) {

				if ( $_field === $field )
					$taxonomies[] = $taxonomy;

				return $taxonomies;
			}, 12, 2 );

		if ( ! is_admin() )
			return;

		$this->filter_string( $this->hook_base( 'terms', 'field', $field, 'title' ), $title );
		$this->filter_string( $this->hook_base( 'terms', 'field', $field, 'desc' ), $desc );

		add_filter( $this->hook_base( 'terms', 'column_title' ),
			static function ( $_title, $column, $constant, $fallback ) use ( $taxonomy, $field, $title ) {

				if ( $column === $field )
					return $title;

				return $_title;
			}, 12, 4 );
	}

	protected function log( $level, $message = '', $context = [] )
	{
		return Helper::log( $message, $this->classs(), $level, $context );
	}

	protected function raise_resources( $count = 1, $per = 60, $context = NULL )
	{
		gEditorial()->disable_process( 'audit', $context ?? 'import' );
		gEditorial()->disable_process( 'personage', 'aftercare' );
		gEditorial()->disable_process( 'was_born', 'aftercare' );

		WordPress\Media::disableThumbnailGeneration();
		WordPress\Taxonomy::disableTermCounting();
		Services\LateChores::termCountCollect();
		wp_defer_comment_counting( TRUE );

		if ( ! Core\WordPress::isDev() )
			do_action( 'qm/cease' ); // QueryMonitor: Cease data collections

		return $this->raise_memory_limit( $count, $per, $context ?? 'import' );
	}

	public function disable_process( $context = 'import' )
	{
		return $this->process_disabled[$context] = TRUE;
	}

	public function enable_process( $context = 'import' )
	{
		return $this->process_disabled[$context] = FALSE;
	}

	public function is_thrift_mode()
	{
		if ( self::const( 'GEDITORIAL_THRIFT_MODE' ) )
			return TRUE;

		return $this->get_setting( 'thrift_mode', FALSE );
	}

	public function is_debug_mode()
	{
		if ( self::const( 'GEDITORIAL_DEBUG_MODE' ) )
			return TRUE;

		return $this->get_setting( 'debug_mode', FALSE );
	}
}

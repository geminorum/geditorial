<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Module extends WordPress\Module
{
	use Internals\CoreIncludes;
	use Internals\CorePostTypes;
	use Internals\CoreTaxonomies;
	use Internals\SettingsFields;
	use Internals\SettingsPostTypes;
	use Internals\SettingsTaxonomies;
	use Internals\Strings;

	public $module;
	public $options;
	public $settings;

	public $enabled  = FALSE;
	public $meta_key = '_ge';

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

		return load_textdomain( $domain, GEDITORIAL_DIR."languages/{$this->module->folder}/{$locale}.mo" );
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

		// auto-hook register default terms
		// helps if strings filtered
		if ( ! empty( $this->strings['default_terms'] ) )
			foreach ( array_keys( $this->strings['default_terms'] ) as $taxonomy_constant )
				$this->register_default_terms( $taxonomy_constant );

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

	protected function settings_help_tabs( $context = 'settings' )
	{
		return Settings::helpContent( $this->module );
	}

	protected function settings_help_sidebar( $context = 'settings' )
	{
		return Settings::helpSidebar( $this->get_module_links() );
	}

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
					'title'   => sprintf( _x( '%s Reports', 'Module: Extra Link: Reports', 'geditorial' ), $title ),
				];

		if ( method_exists( $this, 'tools_settings' ) && ! Settings::isTools( $screen ) )
			foreach ( $this->append_sub( [], 'tools' ) as $sub => $title )
				$links[] = [
					'context' => 'tools',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'tools', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Tools', 'Module: Extra Link: Tools', 'geditorial' ), $title ),
				];

		if ( method_exists( $this, 'imports_settings' ) && ! Settings::isImports( $screen ) )
			foreach ( $this->append_sub( [], 'imports' ) as $sub => $title )
				$links[] = [
					'context' => 'imports',
					'sub'     => $sub,
					'text'    => $title,
					'url'     => $this->get_module_url( 'imports', $sub ),
					/* translators: %s: sub title */
					'title'   => sprintf( _x( '%s Imports', 'Module: Extra Link: Tools', 'geditorial' ), $title ),
				];

		if ( isset( $this->caps['settings'] ) && ! Settings::isSettings( $screen ) && $this->cuc( 'settings' ) )
			$links[] = [
				'context' => 'settings',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $this->get_module_url( 'settings' ),
				/* translators: %s: module title */
				'title'   => sprintf( _x( '%s Settings', 'Module: Extra Link: Settings', 'geditorial' ), $this->module->title ),
			];

		if ( $docs = $this->get_module_url( 'docs' ) )
			$links[] = [
				'context' => 'docs',
				'sub'     => $this->key,
				'text'    => $this->module->title,
				'url'     => $docs,
				/* translators: %s: module title */
				'title'   => sprintf( _x( '%s Documentation', 'Module: Extra Link: Documentation', 'geditorial' ), $this->module->title ),
			];

		if ( 'config' != $this->module->name )
			$links[] = [
				'context' => 'docs',
				'sub'     => FALSE,
				'text'    => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial' ),
				'url'     => Settings::getModuleDocsURL( FALSE ),
				'title'   => _x( 'Editorial Documentation', 'Module: Extra Link: Documentation', 'geditorial' ),
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

	protected function settings_supports_option( $constant, $defaults = TRUE, $excludes = NULL )
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

		if ( FALSE === $defaults )
			$defaults = [];

		else if ( TRUE === $defaults )
			$defaults = array_keys( $supports );

		// NOTE: filtered noop strings may omit context/domain keys!
		$singular = translate_nooped_plural( array_merge( [
			'context' => NULL,
			'domain'  => $this->get_textdomain() ?: 'default',
		], $this->strings['noops'][$constant] ), 1 );

		return [
			'field'       => $constant.'_supports',
			'type'        => 'checkboxes-values',
			/* translators: %s: singular posttype name */
			'title'       => sprintf( _x( '%s Supports', 'Module: Setting Title', 'geditorial' ), $singular ),
			/* translators: %s: singular posttype name */
			'description' => sprintf( _x( 'Support core and extra features for %s posttype.', 'Module: Setting Description', 'geditorial' ), $singular ),
			'default'     => $defaults,
			'values'      => $supports,
		];
	}

	protected function settings_insert_priority_option( $default = 10, $prefix = FALSE )
	{
		return [
			'field'   => 'insert_priority'.( $prefix ? '_'.$prefix : '' ),
			'type'    => 'priority',
			'title'   => _x( 'Insert Priority', 'Module: Setting Title', 'geditorial' ),
			'default' => $default,
		];
	}

	public function settings_from()
	{
		echo '<form class="'.$this->base.'-form -form -'.$this->module->name
			.'" action="'.$this->get_module_url( 'settings' ).'" method="post">';

			$this->render_form_fields( $this->module->name );

			Settings::moduleSections( $this->base.'_'.$this->module->name );

			echo '<input id="geditorial_module_name" name="geditorial_module_name" type="hidden" value="'.Core\HTML::escape( $this->module->name ).'" />';

			$this->render_form_buttons();

		echo '</form>';

		if ( Core\WordPress::isDev() )
			self::dump( $this->options );
	}

	public function default_buttons( $module = FALSE )
	{
		$this->register_button( 'submit', NULL, TRUE );
		$this->register_button( 'reset', NULL, 'reset', TRUE );

		if ( ! $this->module->autoload )
			$this->register_button( 'disable', _x( 'Disable Module', 'Module: Button', 'geditorial' ), 'danger' );

		foreach ( $this->get_module_links() as $link )
			if ( ! empty( $link['context'] ) && in_array( $link['context'], [ 'tools', 'reports', 'imports', 'listtable' ] ) )
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

	protected function render_form_buttons( $module = FALSE, $wrap = '', $buttons = NULL )
	{
		if ( FALSE !== $wrap )
			echo $this->wrap_open_buttons( $wrap );

		if ( is_null( $buttons ) )
			$buttons = $this->buttons;

		foreach ( $buttons as $button )
			Settings::submitButton( $button['key'], $button['value'], $button['type'], $button['atts'] );

		if ( FALSE !== $wrap )
			echo '</p>';
	}

	protected function render_form_start( $uri, $sub = NULL, $action = 'update', $context = 'settings', $check = TRUE )
	{
		if ( is_null( $sub ) )
			$sub = $this->module->name;

		$class = [
			$this->base.'-form',
			'-form',
			'-'.$this->module->name,
			'-sub-'.$sub,
		];

		if ( $check && $sidebox = method_exists( $this, 'settings_sidebox' ) )
			$class[] = 'has-sidebox';

		echo '<form enctype="multipart/form-data" class="'.Core\HTML::prepClass( $class ).'" method="post" action="">';

			if ( in_array( $context, [ 'settings', 'tools', 'reports', 'imports' ] ) )
				$this->render_form_fields( $sub, $action, $context );

			if ( $check && $sidebox ) {
				echo '<div class="'.Core\HTML::prepClass( '-sidebox', '-'.$this->module->name, '-sidebox-'.$sub ).'">';
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

			if ( FALSE === $this->render_tools_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_tools_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_tools_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for tools default sub html
	protected function render_tools_html( $uri, $sub ) {}
	protected function render_tools_html_before( $uri, $sub ) {}
	protected function render_tools_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: reports sub html
	public function reports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'reports', FALSE );

			if ( FALSE === $this->render_reports_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_reports_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_reports_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for reports default sub html
	protected function render_reports_html( $uri, $sub ) {}
	protected function render_reports_html_before( $uri, $sub ) {}
	protected function render_reports_html_after( $uri, $sub ) {}

	// DEFAULT METHOD: imports sub html
	public function imports_sub( $uri, $sub )
	{
		$this->render_form_start( $uri, $sub, 'bulk', 'imports', FALSE );

			if ( FALSE === $this->render_imports_html_before( $uri, $sub ) )
				return $this->render_form_end( $uri, $sub ); // bail if explicitly FALSE

			if ( $this->render_imports_html( $uri, $sub ) )
				$this->render_form_buttons();

			$this->render_imports_html_after( $uri, $sub );

		$this->render_form_end( $uri, $sub );
	}

	// DEFAULT METHOD: used for imports default sub html
	protected function render_imports_html( $uri, $sub ) {}
	protected function render_imports_html_before( $uri, $sub ) {}
	protected function render_imports_html_after( $uri, $sub ) {}

	protected function get_current_form( $defaults, $context = 'settings' )
	{
		$req = empty( $_REQUEST[$this->base.'_'.$this->module->name][$context] )
			? []
			: $_REQUEST[$this->base.'_'.$this->module->name][$context];

		return self::atts( $defaults, $req );
	}

	protected function fields_current_form( $fields, $context = 'settings', $excludes = [] )
	{
		foreach ( $fields as $key => $value ) {

			if ( in_array( $key, $excludes ) )
				continue;

			Core\HTML::inputHidden( $this->base.'_'.$this->module->name.'['.$context.']['.$key.']', $value );
		}
	}

	protected function render_form_fields( $sub, $action = 'update', $context = 'settings' )
	{
		Core\HTML::inputHidden( 'base', $this->base );
		Core\HTML::inputHidden( 'key', $this->key );
		Core\HTML::inputHidden( 'context', $context );
		Core\HTML::inputHidden( 'sub', $sub );
		Core\HTML::inputHidden( 'action', $action );

		Core\WordPress::fieldReferer();
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

				// skip disabled settings
				if ( array_key_exists( 'values', $args ) && FALSE === $args['values'] )
					continue;

				if ( ! array_key_exists( 'type', $args ) || 'enabled' == $args['type'] ) {

					$options['settings'][$setting] = (bool) $option;

				} else if ( 'object' == $args['type'] ) {

					if ( empty( $option ) || ! is_array( $option ) || empty( $args['values'] ) ) {

						unset( $options['settings'][$setting] );

					} else {

						$sanitized = [];
						$first_key = Core\Arraay::keyFirst( $option );

						foreach ( $option[$first_key] as $index => $unused ) {

							// first one is empty
							if ( ! $index )
								continue;

							$group = [];

							foreach ( $args['values'] as $field ) {

								if ( empty( $field['field'] ) )
									continue;

								$key  = $field['field'];
								$type = empty( $field['type'] ) ? 'text' : $field['type'];

								switch ( $type ) {

									case 'number':
										$group[$key] = Core\Number::intval( trim( $option[$key][$index] ) );
										break;

									case 'text':
									default:
										$group[$key] = trim( self::unslash( $option[$key][$index] ) );
								}
							}

							if ( count( $group ) )
								$sanitized[] = $group;
						}

						if ( count( $sanitized ) )
							$options['settings'][$setting] = $sanitized;

						else
							unset( $options['settings'][$setting] );
					}

				} else if ( is_array( $option ) ) {

					if ( 'text' == $args['type'] ) {

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

				foreach ( $section as $key => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_string( $key ) && $setting == $key )
						return method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$key )
							? call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$key ], (array) $field )
							: [];

					else if ( is_string( $field ) && $setting == $field )
						return method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field )
							? call_user_func( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ] )
							: [];

					else if ( is_array( $field ) && isset( $field['field'] ) && $setting == $field['field'] )
						return $field;
				}
			}
		}

		return [];
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

		return array_unique( $pre );
	}

	public function slug()
	{
		return str_replace( '_', '-', $this->module->name );
	}

	protected function help_tab_default_terms( $constant )
	{
		if ( ! $taxonomy = WordPress\Taxonomy::object( $this->constant( $constant ) ) )
			return;

		/* translators: %s: taxonomy object label */
		$title  = sprintf( _x( 'Default Terms for %s', 'Module', 'geditorial' ), $taxonomy->label );
		/* translators: %s: taxonomy object label */
		$edit   = sprintf( _x( 'Edit Terms for %s', 'Module', 'geditorial' ), $taxonomy->label );
		$terms  = $this->get_default_terms( $constant );
		$link   = Core\WordPress::getEditTaxLink( $taxonomy->name );
		$before = Core\HTML::tag( 'p', $title );
		$after  = Core\HTML::tag( 'p', Core\HTML::link( $edit, $link, TRUE ) );
		$args   = [ 'title' => $taxonomy->label, 'id' => $this->classs( 'help-default-terms', '-'.$taxonomy->name ) ];

		if ( empty( $terms ) )
			$args['content'] = $before.Core\HTML::wrap( _x( 'No Default Terms', 'Module', 'geditorial' ), '-info' ).$after;

		else if ( Core\Arraay::allStringValues( $terms ) )
			$args['content'] = $before.Core\HTML::wrap( Core\HTML::tableCode( $terms, TRUE ), '-info' ).$after;

		else
			$args['content'] = $before.Core\HTML::wrap( Core\HTML::tableCode( Core\Arraay::pluck( $terms, 'name', 'slug' ), TRUE ), '-info' ).$after;

		get_current_screen()->add_help_tab( $args );
	}

	// DEPRECATED: use `$this->register_default_terms()`
	protected function insert_default_terms( $constant, $terms = NULL )
	{
		if ( ! $this->nonce_verify( 'settings' ) )
			return;

		$taxonomy = $this->constant( $constant );

		if ( ! taxonomy_exists( $taxonomy ) )
			return;

		if ( is_null( $terms ) )
			$terms = $this->get_default_terms( $constant );

		if ( empty( $terms ) )
			$message = 'noadded';

		else if ( $added = WordPress\Taxonomy::insertDefaultTerms( $taxonomy, $terms ) )
			$message = [
				'message' => 'created',
				'count'   => count( $added ),
			];

		else
			$message = 'wrong';

		Core\WordPress::redirectReferer( $message );
	}

	// NOTE: hook filter before `init` on `after_setup_theme`
	protected function get_default_terms( $constant )
	{
		// constant is not defined (in case custom terms are for another modules)
		if ( ! $this->constant( $constant ) )
			return [];

		if ( ! empty( $this->strings['default_terms'][$constant] ) )
			$terms = $this->strings['default_terms'][$constant];

		// DEPRECATED: use `default_terms` key
		else if ( ! empty( $this->strings['terms'][$constant] ) )
			$terms = $this->strings['terms'][$constant];

		else
			$terms = [];

		return $this->filters( 'get_default_terms', $terms, $this->constant( $constant ) );
	}

	protected function register_default_terms( $constant, $terms = NULL )
	{
		if ( ! defined( 'GNETWORK_VERSION' ) )
			return FALSE;

		if ( ! is_admin() )
			return FALSE;

		if ( is_null( $terms ) )
			$terms = $this->get_default_terms( $constant );

		if ( empty( $terms ) )
			return FALSE;

		add_filter( 'gnetwork_taxonomy_default_terms_'.$this->constant( $constant ),
			static function ( $pre ) use ( $terms ) {
				return array_merge( $pre, Core\Arraay::isAssoc( $terms ) ? $terms : Core\Arraay::sameKey( $terms ) );
			} );
	}

	protected function check_settings( $sub, $context = 'tools', $screen_option = FALSE, $extra = [], $key = NULL )
	{
		if ( ! $this->cuc( $context ) )
			return FALSE;

		if ( is_null( $key ) )
			$key = $this->key;

		if ( $key == $this->key )
			add_filter( $this->hook_base( $context, 'subs' ), [ $this, 'append_sub' ], 10, 2 );

		$subs = array_merge( [ $key ], (array) $extra );

		if ( ! in_array( $sub, $subs ) )
			return FALSE;

		foreach ( $subs as $supported )
			add_action( $this->hook_base( $context, 'sub', $supported ), [ $this, $context.'_sub' ], 10, 2 );

		if ( 'settings' != $context ) {

			$this->register_help_tabs( NULL, $context );

			if ( $screen_option )
				$this->add_sub_screen_option( $sub, TRUE === $screen_option ? 'per_page' : $screen_option );

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
				else if ( method_exists( __NAMESPACE__.'\\Settings', 'settings_section'.$section_suffix ) )
					$callback = [ __NAMESPACE__.'\\Settings', 'settings_section'.$section_suffix ];
				else
					$callback = '__return_false';

				Settings::addModuleSection( $this->base.'_'.$this->module->name, [
					'id'            => $section,
					'callback'      => $callback,
					'section_class' => 'settings_section',
				] );

				foreach ( $fields as $key => $field ) {

					if ( FALSE === $field )
						continue;

					if ( is_string( $key ) && method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$key ) )
						$args = call_user_func_array( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$key ], (array) $field );

					else if ( is_string( $field ) && method_exists( __NAMESPACE__.'\\Settings', 'getSetting_'.$field ) )
						$args = call_user_func( [ __NAMESPACE__.'\\Settings', 'getSetting_'.$field ] );

					else if ( ! is_string( $key ) && is_array( $field ) )
						$args = $field;

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
		if ( ! Core\WordPress::mustRegisterUI( FALSE ) || self::req( 'noheader' ) )
			return;

		if ( is_null( $screen ) )
			$screen = get_current_screen();

		foreach ( $this->settings_help_tabs( $context ) as $tab )
			$screen->add_help_tab( $tab );

		if ( $sidebar = $this->settings_help_sidebar( $context ) )
			$screen->set_help_sidebar( $sidebar );

		if ( ! in_array( $context, [ 'settings' ], TRUE ) )
			return;

		if ( ! empty( $this->strings['default_terms'] ) )
			foreach ( array_keys( $this->strings['default_terms'] ) as $taxonomy_constant )
				$this->help_tab_default_terms( $taxonomy_constant );
	}

	public function settings_header()
	{
		$back = $count = $flush = $filters = FALSE;

		if ( 'config' == $this->module->name ) {
			$title   = NULL;
			$count   = gEditorial()->count();
			$flush   = Core\WordPress::maybeFlushRules();
			$filters = TRUE;
		} else {
			/* translators: %s: module title */
			$title = sprintf( _x( 'Editorial: %s', 'Module', 'geditorial' ), $this->module->title );
			$back  = Settings::settingsURL();
		}

		Settings::wrapOpen( $this->module->name );

			Settings::headerTitle( $title, $back, NULL, $this->module->icon, $count, TRUE, $filters );
			Settings::message();

			if ( $flush )
				echo Core\HTML::warning( _x( 'You need to flush rewrite rules!', 'Module', 'geditorial' ), FALSE );

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
			_x( 'Defaults', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_misc()
	{
		Settings::fieldSection(
			_x( 'Miscellaneous', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_frontend()
	{
		Settings::fieldSection(
			_x( 'Front-end', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_backend()
	{
		Settings::fieldSection(
			_x( 'Back-end', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_content()
	{
		Settings::fieldSection(
			_x( 'Generated Contents', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_dashboard()
	{
		Settings::fieldSection(
			_x( 'Admin Dashboard', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_editlist()
	{
		Settings::fieldSection(
			_x( 'Admin Edit List', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_columns()
	{
		Settings::fieldSection(
			_x( 'Admin List Columns', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_editpost()
	{
		Settings::fieldSection(
			_x( 'Admin Edit Post', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_edittags()
	{
		Settings::fieldSection(
			_x( 'Admin Edit Terms', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_comments()
	{
		Settings::fieldSection(
			_x( 'Admin Comment List', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_strings()
	{
		Settings::fieldSection(
			_x( 'Custom Strings', 'Module: Setting Section Title', 'geditorial' )
		);
	}

	public function settings_section_roles()
	{
		Settings::fieldSection(
			_x( 'Availability', 'Module: Setting Section Title', 'geditorial' ),
			_x( 'Though Administrators have it all!', 'Module: Setting Section Description', 'geditorial' )
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

	public function settings_print_scripts()
	{
		if ( $this->scripts_printed )
			return;

		if ( count( $this->scripts ) )
			Core\HTML::wrapjQueryReady( implode( "\n", $this->scripts ) );

		$this->scripts_printed = TRUE;
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

	// TODO: move to `Internals\PairedRowActions`
	protected function _hook_paired_taxonomy_bulk_actions( $posttype_orogin, $taxonomy_origin )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( TRUE !== $posttype_orogin && ! $this->posttype_supported( $posttype_orogin ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		// $paired_taxonomy = $this->constant( $constants[1] );

		if ( ! in_array( $taxonomy_origin, get_object_taxonomies( $paired_posttype ), TRUE ) )
			return FALSE;

		$label = $this->get_posttype_label( $constants[0], 'add_new_item' );
		$key   = sprintf( 'paired_add_new_%s', $paired_posttype );

		add_filter( 'gnetwork_taxonomy_bulk_actions', static function ( $actions, $taxonomy ) use ( $taxonomy_origin, $key, $label ) {
			return $taxonomy === $taxonomy_origin ? array_merge( $actions, [ $key => $label ] ) : $actions;
		}, 20, 2 );

		add_filter( 'gnetwork_taxonomy_bulk_input', function ( $callback, $action, $taxonomy )  use ( $taxonomy_origin, $key ) {
			return ( $taxonomy === $taxonomy_origin && $action === $key ) ? [ $this, 'paired_bulk_input_add_new_item' ] : $callback;
		}, 20, 3 );

		add_filter( 'gnetwork_taxonomy_bulk_callback', function ( $callback, $action, $taxonomy )  use ( $taxonomy_origin, $key ) {
			return ( $taxonomy === $taxonomy_origin && $action === $key ) ? [ $this, 'paired_bulk_action_add_new_item' ] : $callback;
		}, 20, 3 );

		return $key;
	}

	// TODO: move to `Internals\PairedRowActions`
	public function paired_bulk_input_add_new_item( $taxonomy, $action )
	{
		/* translators: %s: clone into input */
		printf( _x( 'as: %s', 'Module: Taxonomy Bulk Input Label', 'geditorial' ),
			'<input name="'.$this->classs( 'paired-add-new-item-target' ).'" type="text" placeholder="'
			._x( 'New Item Title', 'Module: Taxonomy Bulk Input PlaceHolder', 'geditorial' ).'" /> ' );

		echo Core\HTML::dropdown( [
			'separeted-terms' => _x( 'Separeted Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
			'cross-terms'     => _x( 'Cross Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
			'union-terms'     => _x( 'Union Terms', 'Module: Taxonomy Bulk Input Option', 'geditorial' ),
		], [
			'name'     => $this->classs( 'paired-add-new-item-type' ),
			'style'    => 'float:none',
			'selected' => 'separeted-terms',
		] );
	}

	// TODO: move to `Internals\PairedRowActions`
	public function paired_bulk_action_add_new_item( $term_ids, $taxonomy, $action )
	{
		global $wpdb;

		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		$paired_posttype = $this->constant( $constants[0] );
		$paired_taxonomy = $this->constant( $constants[1] );

		if ( $action !== sprintf( 'paired_add_new_%s', $paired_posttype ) )
			return FALSE;

		$target    = self::req( $this->classs( 'paired-add-new-item-target' ) );
		$supported = $this->posttypes();

		switch ( self::req( $this->classs( 'paired-add-new-item-type' ) ) ) {

			case 'cross-terms':

				// bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// bail if post with same slug exists
				if ( WordPress\PostType::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
					return FALSE;

				$object_lists = [];

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					$object_lists[] = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );
				}

				$object_ids = Core\Arraay::prepNumeral( array_intersect( ...$object_lists ) );

				// bail if cross term results are ampty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = WordPress\PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				WordPress\Taxonomy::disableTermCounting();
				Services\LateChores::termCountCollect();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;

			case 'union-terms':

				// bail if no target given
				if ( empty( $target ) )
					return FALSE;

				// bail if post with same slug exists
				if ( WordPress\PostType::getIDbySlug( sanitize_title( $target ), $paired_posttype ) )
					return FALSE;

				$object_lists = [];

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					$object_lists[] = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );
				}

				$object_ids = Core\Arraay::prepNumeral( ...$object_lists );

				// bail if cross term results are ampty
				if ( empty( $object_ids ) )
					return FALSE;

				$inserted = wp_insert_term( $target, $paired_taxonomy, [
					'slug' => sanitize_title( $target ),
				] );

				if ( self::isError( $inserted ) )
					return FALSE;

				$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
				$post_id = WordPress\PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

				if ( self::isError( $post_id ) )
					return FALSE;

				if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
					return FALSE;

				WordPress\Taxonomy::disableTermCounting();
				Services\LateChores::termCountCollect();

				foreach ( $object_ids as $object_id )
					wp_set_object_terms( $object_id, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!

				break;
			default:
			case 'separeted-terms':

				WordPress\Taxonomy::disableTermCounting();
				Services\LateChores::termCountCollect();

				foreach ( $term_ids as $term_id ) {

					$term = get_term( $term_id, $taxonomy );

					// bail if post with same slug exists
					if ( WordPress\PostType::getIDbySlug( $term->slug, $paired_posttype ) )
						continue;

					$current_objects = (array) $wpdb->get_col( $wpdb->prepare( "
						SELECT object_id FROM {$wpdb->term_relationships} t
						LEFT JOIN {$wpdb->posts} p ON p.ID = t.object_id
						WHERE t.term_taxonomy_id = %d
						AND p.post_type IN ( '".implode( "', '", esc_sql( $supported ) )."' )
					", $term->term_taxonomy_id ) );

					// bail if the term is empty
					if ( empty( $current_objects ) )
						continue;

					$name = $target ? sprintf( '%s (%s)', $target, $term->name ): $term->name;

					$inserted = wp_insert_term( $name, $paired_taxonomy, [
						'slug'        => $term->slug,
						'description' => $term->description,
					] );

					if ( self::isError( $inserted ) )
						continue;

					$cloned  = get_term( $inserted['term_id'], $paired_taxonomy );
					$post_id = WordPress\PostType::newPostFromTerm( $cloned, $paired_taxonomy, $paired_posttype );

					if ( self::isError( $post_id ) )
						continue;

					if ( ! $this->paired_set_to_term( $post_id, $cloned, $constants[0], $constants[1] ) )
						continue;

					foreach ( $current_objects as $current_object )
						wp_set_object_terms( $current_object, $cloned->term_id, $paired_taxonomy, FALSE ); // overrides!
				}
		}

		// flush the deferred term counts
		wp_update_term_count( NULL, NULL, TRUE );

		return TRUE;
	}

	// excludes paired posttype from subterm archives
	// TODO: move to `Internals\PairedFront`
	protected function _hook_paired_exclude_from_subterm()
	{
		if ( ! $this->get_setting( 'subterms_support' ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_action( 'pre_get_posts', function ( &$wp_query ) use ( $constants ) {

			$subterms = $this->constant( $constants[2] );

			if ( ! $wp_query->is_main_query() || ! $wp_query->is_tax( $subterms ) )
				return;

			$primaries = WordPress\PostType::getIDs( $this->constant( $constants[0] ), [
				'tax_query' => [ [
					'taxonomy' => $subterms,
					'terms'    => [ get_queried_object_id() ],
				] ],
			] );

			if ( count( $primaries ) ) {

				if ( $not = $wp_query->get( 'post__not_in' ) )
					$primaries = Core\Arraay::prepNumeral( $not, $primaries );

				$wp_query->set( 'post__not_in', $primaries );
			}
		}, 8 );
	}

	// TODO: move to `Internals\PairedFront`
	protected function _hook_paired_override_term_link()
	{
		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_filter( 'term_link', function ( $link, $term, $taxonomy ) use ( $constants ) {

			if ( $taxonomy !== $this->constant( $constants[1] ) )
				return $link;

			if ( $post_id = $this->paired_get_to_post_id( $term, $constants[0], $constants[1] ) )
				return get_permalink( $post_id );

			return $link;

		}, 9, 3 );
	}

	// TODO: move to `Internals\PairedThumbnail`
	protected function _hook_paired_thumbnail_fallback( $posttypes = NULL )
	{
		if ( ! $this->_paired )
			return;

		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		if ( ! $this->get_setting( 'thumbnail_fallback', FALSE ) )
			return;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		// core filter @since WP 5.9.0
		add_filter( 'post_thumbnail_id', function ( $thumbnail_id, $post ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post, $posttypes );
		}, 8, 2 );

		// no need @since WP 5.9.0
		if ( Core\WordPress::isWPcompatible( '5.9.0' ) )
			return;

		add_filter( 'geditorial_get_post_thumbnail_id', function ( $thumbnail_id, $post_id ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post_id, $posttypes );
		}, 8, 2 );

		add_filter( 'gtheme_image_get_thumbnail_id', function ( $thumbnail_id, $post_id ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post_id, $posttypes );
		}, 8, 2 );

		add_filter( 'gnetwork_rest_thumbnail_id', function ( $thumbnail_id, $post_array ) use ( $posttypes ) {
			return $this->get_paired_fallback_thumbnail_id( $thumbnail_id, $post_array['id'], $posttypes );
		}, 8, 2 );
	}

	// TODO: move to `Internals\PairedThumbnail`
	protected function get_paired_fallback_thumbnail_id( $thumbnail_id, $post, $posttypes = NULL )
	{
		if ( $thumbnail_id || FALSE === $post )
			return $thumbnail_id;

		if ( is_null( $posttypes ) )
			$posttypes = $this->posttypes();

		if ( ! in_array( get_post_type( $post ), $posttypes, TRUE ) )
			return $thumbnail_id;

		if ( ! $parent = $this->get_linked_to_posts( $post, TRUE ) )
			return $thumbnail_id;

		if ( $parent_thumbnail = get_post_thumbnail_id( $parent ) )
			return $parent_thumbnail;

		return $thumbnail_id;
	}

	public function enqueue_asset_style( $name = NULL, $deps = [], $handle = NULL )
	{
		if ( is_null( $name ) )
			$name = $this->key;

		// screen passed
		else if ( is_object( $name ) )
			$name = $name->base;

		else
			$name = $this->key.'.'.$name;

		$name = str_replace( '_', '-', $name );

		if ( is_null( $handle ) )
			$handle = strtolower( $this->base.'-'.str_replace( '.', '-', $name ) );

		$prefix = is_admin() ? 'admin.' : 'front.';

		wp_enqueue_style( $handle, GEDITORIAL_URL.'assets/css/'.$prefix.$name.'.css', $deps, GEDITORIAL_VERSION, 'all' );
		wp_style_add_data( $handle, 'rtl', 'replace' );

		return $handle;
	}

	// NOTE: each script must have a `.min` version
	public function enqueue_asset_js( $args = [], $name = NULL, $deps = [ 'jquery' ], $key = NULL, $handle = NULL )
	{
		if ( is_null( $key ) )
			$key = $this->key;

		if ( is_null( $name ) )
			$name = $key;

		else if ( $name instanceof \WP_Screen )
			$name = $key.'.'.$name->base;

		if ( TRUE === $args ) {
			$args = [];

		} else if ( $args && $name && is_string( $args ) ) {
			$name.= '.'.$args;
			$args = [];
		}

		if ( $name ) {

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
		}

		if ( ! array_key_exists( '_rest', $args ) && method_exists( $this, 'restapi_get_namespace' ) )
			$args['_rest'] = $this->restapi_get_namespace();

		if ( ! array_key_exists( '_nonce', $args ) && is_user_logged_in() )
			$args['_nonce'] = wp_create_nonce( $this->hook() );

		gEditorial()->enqueue_asset_config( $args, $key );

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

	/**
	 * Returns post ids with selected terms from settings.
	 * results will be excluded form dropdown on supported post-types.
	 *
	 * TODO: move to `Internals\PairedMetaBox`
	 *
	 * @return array
	 */
	protected function paired_get_dropdown_excludes()
	{
		if ( ! $terms = $this->get_setting( 'paired_exclude_terms' ) )
			return [];

		if ( ! $constants = $this->paired_get_constants() )
			return [];

		$args = [
			'post_type' => $this->constant( $constants[0] ),
			'tax_query' => [ [
				'taxonomy' => $this->constant( $constants[3] ),
				'terms'    => $terms,
			] ],
			'fields'         => 'ids',
			'posts_per_page' => -1,

			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		$query = new \WP_Query();
		return $query->query( $args );
	}

	// NOTE: subterms must be hierarchical
	// OLD: `do_render_metabox_assoc()`
	// TODO: move to `Internals\PairedMetaBox`
	protected function paired_do_render_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE, $display_empty = FALSE )
	{
		$subterm   = FALSE;
		$dropdowns = $displayed = $parents = [];
		$excludes  = $this->paired_get_dropdown_excludes();
		$multiple  = $this->get_setting( 'multiple_instances', FALSE );
		$forced    = $this->get_setting( 'paired_force_parents', FALSE );
		$posttype  = $this->constant( $posttype_constant );
		$paired    = $this->constant( $paired_constant );
		$terms     = WordPress\Taxonomy::getPostTerms( $paired, $post );
		$none_main = Helper::getPostTypeLabel( $posttype, 'show_option_select' );
		$prefix    = $this->classs();

		if ( $subterm_constant && $this->get_setting( 'subterms_support' ) ) {

			$subterm  = $this->constant( $subterm_constant );
			$none_sub = Helper::getTaxonomyLabel( $subterm, 'show_option_select' );
			$subterms = WordPress\Taxonomy::getPostTerms( $subterm, $post, FALSE );
		}

		foreach ( $terms as $term ) {

			if ( $term->parent )
				$parents[] = $term->parent;

			// avoid if has child in the list
			if ( $forced && in_array( $term->term_id, $parents, TRUE ) )
				continue;

			if ( ! $to_post_id = $this->paired_get_to_post_id( $term, $posttype_constant, $paired_constant ) )
				continue;

			if ( in_array( $to_post_id, $excludes, TRUE ) )
				continue;

			$dropdown = MetaBox::paired_dropdownToPosts( $posttype, $paired, $to_post_id, $prefix, $excludes, $none_main, $display_empty );

			if ( $subterm ) {

				if ( $multiple ) {

					$sub_meta = get_post_meta( $post->ID, sprintf( '_%s_subterm_%s', $posttype, $to_post_id ), TRUE );
					$selected = ( $sub_meta && $subterms && in_array( $sub_meta, $subterms ) ) ? $sub_meta : 0;

				} else {

					$selected = $subterms ? array_pop( $subterms ) : 0;
				}

				$dropdown.= MetaBox::paired_dropdownSubTerms( $subterm, $to_post_id, $this->classs( $subterm ), $selected, $none_sub );

				if ( $multiple )
					$dropdown.= '<hr />';
			}

			$dropdowns[$term->term_id] = $dropdown;
			$displayed[] = $to_post_id;
		}

		// final check if had children in the list
		if ( $forced && count( $parents ) )
			$dropdowns = Core\Arraay::stripByKeys( $dropdowns, Core\Arraay::prepNumeral( $parents ) );

		$excludes = Core\Arraay::prepNumeral( $excludes, $displayed );

		if ( empty( $dropdowns ) )
			$dropdowns[0] = MetaBox::paired_dropdownToPosts( $posttype, $paired, '0', $prefix, $excludes, $none_main, $display_empty );

		else if ( $multiple )
			$dropdowns[0] = MetaBox::paired_dropdownToPosts( $posttype, $paired, '0', $prefix, $excludes, $none_main, $display_empty );

		foreach ( $dropdowns as $dropdown )
			if ( $dropdown )
				echo $dropdown;

		// TODO: support for clear all button via js, like `subterms`

		if ( $subterm )
			$this->enqueue_asset_js( 'subterms', 'module' );
	}

	protected function _metabox_remove_subterm( $screen, $subterms = FALSE )
	{
		if ( $subterms )
			remove_meta_box( $subterms.'div', $screen->post_type, 'side' );
	}

	protected function _hook_term_supportedbox( $screen, $context = NULL, $metabox_context = 'side', $metabox_priority = 'default', $extra = [] )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$metabox  = $this->classs( $context );
		$callback = function( $object, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->taxonomy ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox_before', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->taxonomy )
			);

			$this->_render_supportedbox_content( $object, $box, $context, $screen );

			$this->actions(
				sprintf( 'render_%s_metabox_after', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->taxonomy )
			);

			echo '</div>';
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_taxonomy( $screen->taxonomy, $context ),
			$callback,
			$screen,
			$metabox_context,
			$metabox_priority
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	protected function _hook_general_supportedbox( $screen, $context = NULL, $metabox_context = 'side', $metabox_priority = 'default', $extra = [] )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$metabox  = $this->classs( $context );
		$callback = function( $object, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox_before', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->post_type )
			);

			$this->_render_supportedbox_content( $object, $box, $context, $screen );

			$this->actions(
				sprintf( 'render_%s_metabox_after', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $object->post_type )
			);

			echo '</div>';
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			$metabox_priority
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	// DEFAULT METHOD
	protected function _render_supportedbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'supportedbox';

		$this->actions(
			sprintf( 'render_%s_metabox', $context ),
			$object,
			$box,
			NULL,
			sprintf( '%s_%s', $context, $object->post_type )
		);
	}

	protected function _hook_general_mainbox( $screen, $constant_key = 'post', $remove_parent_order = TRUE, $context = NULL, $metabox_context = 'side', $extra = [] )
	{
		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( ! empty( $screen->post_type ) && method_exists( $this, 'store_'.$context.'_metabox_'.$screen->post_type ) )
			add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_'.$context.'_metabox_'.$screen->post_type ], 20, 3 );

		else if ( method_exists( $this, 'store_'.$context.'_metabox' ) )
			add_action( 'save_post', [ $this, 'store_'.$context.'_metabox' ], 20, 3 );

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$metabox  = $this->classs( $context );
		$callback = function( $post, $box ) use ( $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $post->post_type )
				);

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_content( $post, $box, $context, $screen );

				do_action(
					// @HOOK: `geditorial_mainbox_{current_posttype}`
					$this->hook_base( 'metabox', $context, $post->post_type ),
					$post,
					$box,
					$context,
					$screen
				);

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			'default'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	// TODO: move to `Internals\PairedMetaBox`
	protected function _hook_paired_mainbox( $screen, $remove_parent_order = TRUE, $context = NULL, $metabox_context = 'side', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'mainbox';

		if ( method_exists( $this, 'store_'.$context.'_metabox_'.$screen->post_type ) )
			add_action( sprintf( 'save_post_%s', $screen->post_type ), [ $this, 'store_'.$context.'_metabox_'.$screen->post_type ], 20, 3 );

		else if ( method_exists( $this, 'store_'.$context.'_metabox' ) )
			add_action( 'save_post', [ $this, 'store_'.$context.'_metabox' ], 20, 3 );

		$this->filter_false_module( 'meta', 'mainbox_callback', 12 );

		if ( $remove_parent_order ) {
			$this->filter_false_module( 'tweaks', 'metabox_menuorder' );
			$this->filter_false_module( 'tweaks', 'metabox_parent' );
			remove_meta_box( 'pageparentdiv', $screen, 'side' );
		}

		$metabox  = $this->classs( $context );
		$callback = function( $post, $box ) use ( $constants, $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

				$this->actions(
					sprintf( 'render_%s_metabox', $context ),
					$post,
					$box,
					NULL,
					sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
				);

				do_action( 'geditorial_meta_render_metabox', $post, $box, NULL );

				$this->_render_mainbox_content( $post, $box, $context, $screen );

				do_action(
					// @HOOK: `geditorial_mainbox_{paired_posttype}_{current_posttype}`
					$this->hook_base( 'metabox', $context, $this->constant( $constants[0] ), $post->post_type ),
					$post,
					$box,
					$context,
					$screen
				);

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			'high'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );
	}

	// DEFAULT METHOD
	protected function _render_mainbox_content( $object, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'mainbox';

		MetaBox::fieldPostMenuOrder( $object );
		MetaBox::fieldPostParent( $object );
	}

	// TODO: move to `Internals\PairedMetaBox`
	protected function _hook_paired_listbox( $screen, $context = NULL, $metabox_context = 'advanced', $extra = [] )
	{
		if ( ! $this->_paired || empty( $screen->post_type ) )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'listbox';

		$metabox  = $this->classs( $context );
		$callback = function( $object, $box ) use ( $constants, $context, $screen ) {

			if ( $this->check_hidden_metabox( $box, $object->post_type ) )
				return;

			if ( $this->check_draft_metabox( $box, $object ) )
				return;

			echo $this->wrap_open( '-admin-metabox' );

			$this->actions(
				sprintf( 'render_%s_metabox', $context ),
				$object,
				$box,
				NULL,
				sprintf( '%s_%s', $context, $this->constant( $constants[0] ) )
			);

			$term = $this->paired_get_to_term( $object->ID, $constants[0], $constants[1] );

			if ( $list = MetaBox::getTermPosts( $this->constant( $constants[1] ), $term, $this->posttypes() ) )
				echo $list;

			else
				echo Core\HTML::wrap( $this->strings_metabox_noitems_via_posttype( $screen->post_type, $context ), 'field-wrap -empty' );

			$this->_render_listbox_extra( $object, $box, $context, $screen );

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $screen->post_type, $context ),
			$callback,
			$screen,
			$metabox_context,
			'low'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		if ( $this->role_can( 'import', NULL, TRUE ) )
			Scripts::enqueueColorBox();
	}

	// DEFAULT METHOD
	// TODO: support for `post_actions` on Actions module
	protected function _render_listbox_extra( $post, $box, $context = NULL, $screen = NULL )
	{
		if ( is_null( $context ) )
			$context = 'listbox';

		$html = '';

		if ( $this->_paired && $this->role_can( 'import', NULL, TRUE ) && method_exists( $this, 'pairedimports_get_import_buttons' ) )
			$html.= $this->pairedimports_get_import_buttons( $post, $context );

		if ( $this->role_can( 'export', NULL, TRUE ) && method_exists( $this, 'exports_get_export_buttons' ) )
			$html.= $this->exports_get_export_buttons( $post, $context );

		echo Core\HTML::wrap( $html, 'field-wrap -buttons' );
	}

	// TODO: move to `Internals\PairedMetaBox`
	protected function _hook_paired_pairedbox( $screen, $menuorder = FALSE, $context = NULL, $extra = [] )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		if ( is_null( $context ) )
			$context = 'pairedbox';

		$metabox  = $this->classs( $context );
		$action   = sprintf( 'render_%s_metabox', $context );
		$callback = function( $post, $box ) use ( $constants, $context, $action, $menuorder ) {

			if ( $this->check_hidden_metabox( $box, $post->post_type ) )
				return;

			$action_context = sprintf( '%s_%s', $context, $this->constant( $constants[0] ) );

			echo $this->wrap_open( '-admin-metabox' );

			if ( $this->get_setting( 'quick_newpost' ) ) {

				$this->actions(
					$action,
					$post,
					$box,
					NULL,
					$action_context
				);

			} else {

				if ( ! WordPress\Taxonomy::hasTerms( $this->constant( $constants[1] ) ) )
					MetaBox::fieldEmptyPostType( $this->constant( $constants[0] ) );

				else
					$this->actions(
						$action,
						$post,
						$box,
						NULL,
						$action_context
					);
			}

			do_action(
				sprintf( '%s_meta_render_metabox', $this->base ),
				$post,
				$box,
				NULL,
				$action_context
			);

			if ( $menuorder )
				MetaBox::fieldPostMenuOrder( $post );

			echo '</div>';

			$this->nonce_field( $context );
		};

		add_meta_box(
			$metabox,
			$this->strings_metabox_title_via_posttype( $this->constant( $constants[0] ), $context ),
			$callback,
			$screen,
			'side'
		);

		add_filter( sprintf( 'postbox_classes_%s_%s', $screen->id, $metabox ), function ( $classes ) use ( $context, $extra ) {
			return array_merge( $classes, [
				$this->base.'-wrap',
				'-admin-postbox',
				'-'.$this->key,
				'-'.$this->key.'-'.$context,
			], (array) $extra );
		} );

		add_action( $this->hook( $action ), function ( $post, $box, $fields = NULL, $action_context = NULL ) use ( $constants, $context ) {

			if ( ( $newpost = $this->get_setting( 'quick_newpost' ) ) && method_exists( $this, 'do_render_thickbox_newpostbutton' ) )
				$this->do_render_thickbox_newpostbutton( $post, $constants[0], 'newpost', [ 'target' => 'paired' ] );

			$this->paired_do_render_metabox( $post, $constants[0], $constants[1], $constants[2], $newpost );

		}, 10, 4 );

		if ( $this->get_setting( 'quick_newpost' ) )
			Scripts::enqueueThickBox();
	}

	// TODO: move to `Internals\PairedMetaBox`
	// NOTE: logic separeted for the use on edit screen
	protected function _hook_paired_store_metabox( $posttype )
	{
		if ( ! $this->_paired )
			return FALSE;

		if ( ! $constants = $this->paired_get_constants() )
			return FALSE;

		add_action( sprintf( 'save_post_%s', $posttype ), function ( $post_id, $post, $update ) use ( $constants ) {

			if ( ! $this->is_save_post( $post, $this->posttypes() ) )
				return;

			$this->paired_do_store_metabox( $post, $constants[0], $constants[1], $constants[2] );

		}, 20, 3 );

		return TRUE;
	}

	// OLD: `do_store_metabox_assoc()`
	// TODO: move to `Internals\PairedMetaBox`
	protected function paired_do_store_metabox( $post, $posttype_constant, $paired_constant, $subterm_constant = FALSE )
	{
		$posttype = $this->constant( $posttype_constant );
		$paired   = self::req( $this->classs( $posttype ), FALSE );

		if ( FALSE === $paired )
			return;

		$terms = $this->paired_do_connection( 'store', $post->ID, $paired, $posttype_constant, $paired_constant );

		if ( FALSE === $terms )
			return FALSE;

		if ( ! $subterm_constant || ! $this->get_setting( 'subterms_support' ) )
			return;

		$subterm = $this->constant( $subterm_constant );

		// no post, no subterm
		if ( ! count( $terms ) )
			return wp_set_object_terms( $post->ID, [], $subterm, FALSE );

		$request = self::req( $this->classs( $subterm ), FALSE );

		if ( FALSE === $request || ! is_array( $request ) )
			return;

		$subterms = [];
		$multiple = $this->get_setting( 'multiple_instances', FALSE );

		foreach ( (array) $paired as $paired_id ) {

			if ( ! $paired_id )
				continue;

			if ( ! array_key_exists( $paired_id, $request ) )
				continue;

			$sub_paired = $request[$paired_id];

			if ( $multiple ) {

				$sub_metakey = sprintf( '_%s_subterm_%s', $posttype, $paired_id );

				if ( $sub_paired )
					update_post_meta( $post->ID, $sub_metakey, (int) $sub_paired );
				else
					delete_post_meta( $post->ID, $sub_metakey );
			}

			if ( $sub_paired )
				$subterms[] = (int) $sub_paired;
		}

		wp_set_object_terms( $post->ID, Core\Arraay::prepNumeral( $subterms ), $subterm, FALSE );
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
			$title = $this->get_string( 'meta_box_title', $constant, 'misc', _x( 'Settings', 'Module: MetaBox Default Title', 'geditorial' ) );

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

			$action = $this->get_string( 'metabox_action', $constant, 'metabox', _x( 'Configure', 'Module: MetaBox Default Action', 'geditorial' ) );
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

	// OLD: `get_linked_term()`
	// TODO: move to `Internals\PairedCore`
	public function paired_get_to_term( $post_id, $posttype_constant_key, $tax_constant_key )
	{
		return $this->paired_get_to_term_direct( $post_id,
			$this->constant( $posttype_constant_key ),
			$this->constant( $tax_constant_key )
		);
	}

	// NOTE: here so modules can override
	// TODO: move to `Internals\PairedCore`
	public function paired_get_to_term_direct( $post_id, $posttype, $taxonomy )
	{
		return Services\Paired::getToTerm( $post_id, $posttype, $taxonomy );
	}

	// OLD: `set_linked_term()`
	// TODO: move to `Internals\PairedCore`
	public function paired_set_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $post_id )
			return FALSE;

		if ( ! $the_term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		update_post_meta( $post_id, '_'.$this->constant( $posttype_key ).'_term_id', $the_term->term_id );
		update_term_meta( $the_term->term_id, $this->constant( $posttype_key ).'_linked', $post_id );

		wp_set_object_terms( (int) $post_id, $the_term->term_id, $the_term->taxonomy, FALSE );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$meta_key = $this->constant( 'metakey_term_image', 'image' );

			if ( $thumbnail = get_post_thumbnail_id( $post_id ) )
				update_term_meta( $the_term->term_id, $meta_key, $thumbnail );

			else
				delete_term_meta( $the_term->term_id, $meta_key );
		}

		return TRUE;
	}

	// OLD: `remove_linked_term()`
	// TODO: move to `Internals\PairedCore`
	public function paired_remove_to_term( $post_id, $term_or_id, $posttype_key, $taxonomy_key )
	{
		if ( ! $the_term = WordPress\Term::get( $term_or_id, $this->constant( $taxonomy_key ) ) )
			return FALSE;

		if ( ! $post_id )
			$post_id = $this->paired_get_to_post_id( $the_term, $posttype_key, $taxonomy_key );

		if ( $post_id ) {
			delete_post_meta( $post_id, '_'.$this->constant( $posttype_key ).'_term_id' );
			wp_set_object_terms( (int) $post_id, [], $the_term->taxonomy, FALSE );
		}

		delete_term_meta( $the_term->term_id, $this->constant( $posttype_key ).'_linked' );

		if ( $this->get_setting( 'thumbnail_support' ) ) {

			$meta_key  = $this->constant( 'metakey_term_image', 'image' );
			$stored    = get_term_meta( $the_term->term_id, $meta_key, TRUE );
			$thumbnail = get_post_thumbnail_id( $post_id );

			if ( $stored && $thumbnail && $thumbnail == $stored )
				delete_term_meta( $the_term->term_id, $meta_key );
		}

		return TRUE;
	}

	// OLD: `get_linked_post_id()`
	// TODO: move to `Internals\PairedCore`
	public function paired_get_to_post_id( $term_or_id, $posttype_constant_key, $tax_constant_key, $check_slug = TRUE )
	{
		if ( ! $term_or_id )
			return FALSE;

		if ( ! $term = WordPress\Term::get( $term_or_id, $this->constant( $tax_constant_key ) ) )
			return FALSE;

		$post_id = get_term_meta( $term->term_id, $this->constant( $posttype_constant_key ).'_linked', TRUE );

		if ( ! $post_id && $check_slug )
			$post_id = WordPress\PostType::getIDbySlug( $term->slug, $this->constant( $posttype_constant_key ) );

		return $post_id;
	}

	// PAIRED API: get (from) posts connected to the pair
	// TODO: move to `Internals\PairedCore`
	public function paired_get_from_posts( $post_id, $posttype_constant_key, $tax_constant_key, $count = FALSE, $term_id = NULL )
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
			$args['fields']                 = 'ids';
			$args['no_found_rows']          = TRUE;
			$args['update_post_meta_cache'] = FALSE;
			$args['update_post_term_cache'] = FALSE;
			$args['lazy_load_term_meta']    = FALSE;
		}

		$items = get_posts( $args );

		if ( $count )
			return count( $items );

		return $items;
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

	// TODO: move to `Internals\PairedCore`
	// must be public
	public function paired_do_get_to_posts( $posttype_constant_key, $tax_constant_key, $post = NULL, $single = FALSE, $published = TRUE )
	{
		$admin  = is_admin();
		$posts  = $parents = [];
		$terms  = WordPress\Taxonomy::getPostTerms( $this->constant( $tax_constant_key ), $post );
		$forced = $this->get_setting( 'paired_force_parents', FALSE );

		foreach ( $terms as $term ) {

			if ( $term->parent )
				$parents[] = $term->parent;

			// avoid if has child in the list
			if ( $forced && in_array( $term->term_id, $parents, TRUE ) )
				continue;

			if ( ! $to_post_id = $this->paired_get_to_post_id( $term, $posttype_constant_key, $tax_constant_key ) )
				continue;

			if ( $single )
				return $to_post_id;

			if ( is_null( $published ) )
				$posts[$term->term_id] = $to_post_id;

			else if ( $published && $admin && ! $this->is_post_viewable( $to_post_id ) )
				continue;

			else
				$posts[$term->term_id] = $to_post_id;
		}

		// final check if had children in the list
		if ( $forced && count( $parents ) )
			$posts = Core\Arraay::stripByKeys( $posts, Core\Arraay::prepNumeral( $parents ) );

		if ( ! count( $posts ) )
			return FALSE;

		return $single ? reset( $posts ) : $posts;
	}

	// @REF: https://make.wordpress.org/core/2012/12/01/more-hooks-on-the-edit-screen/
	protected function _hook_editform_readonly_title()
	{
		add_action( 'edit_form_after_title', function ( $post ) {
			$html = WordPress\Post::title( $post );
			$info = Settings::fieldAfterIcon( '#', _x( 'This Title is Auto-Generated', 'Module: ReadOnly Title Info', 'geditorial' ) );
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

	// DEFAULT FILTER
	// NOTE: used when module defines `_supported` meta fields
	public function meta_column_row( $post, $fields, $excludes )
	{
		foreach ( $fields as $field_key => $field ) {

			if ( in_array( $field_key, $excludes ) )
				continue;

			if ( ! $value = $this->get_postmeta_field( $post->ID, $field_key ) )
				continue;

			echo '<li class="-row -'.$this->module->name.' -field-'.$field_key.'">';
				echo $this->get_column_icon( FALSE, $field['icon'], $field['title'] );
				echo $this->prep_meta_row( $value, $field_key, $field, $value );
			echo '</li>';
		}
	}

	// DEFAULT METHOD
	public function prep_meta_row( $value, $field_key = NULL, $field = [], $raw = NULL )
	{
		if ( ! empty( $field['prep'] ) && is_callable( $field['prep'] ) )
			return call_user_func_array( $field['prep'], [ $value, $field_key, $field, $raw ] );

		if ( method_exists( $this, 'prep_meta_row_module' ) ) {

			$prepped = $this->prep_meta_row_module( $value, $field_key, $field, $raw );

			if ( $prepped !== $value )
				return $prepped; // bail if already prepped
		}

		return Helper::prepMetaRow( $value, $field_key, $field, $raw );
	}

	public function icon( $name, $group = NULL )
	{
		return gEditorial()->icon( $name, ( is_null( $group ) ? $this->icon_group : $group ) );
	}

	// checks to bail early if metabox/widget is hidden
	protected function check_hidden_metabox( $box, $posttype = FALSE, $after = '' )
	{
		return MetaBox::checkHidden( ( empty( $box['id'] ) ? $this->classs( $box ) : $box['id'] ), $posttype, $after );
	}

	// TODO: move to `MetaBox` main
	protected function check_draft_metabox( $box, $post, $message = NULL )
	{
		if ( ! in_array( $post->post_status, [ 'trash', 'private', 'auto-draft' ], TRUE ) )
			return FALSE;

		if ( is_null( $message ) )
			$message = _x( 'You can see the contents once you\'ve saved this post for the first time.', 'Module: Draft Metabox', 'geditorial' );

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

	// self::dump( ini_get( 'memory_limit' ) );
	protected function raise_resources( $count = 1, $per = 60, $context = NULL )
	{
		$limit = $count ? ( 300 + ( $per * $count ) ) : 0;

		@set_time_limit( $limit );
		// @ini_set( 'max_execution_time', $limit ); // maybe `-1`
		// @ini_set( 'max_input_time', $limit ); // maybe `-1`

		if ( is_null( $context ) )
			$context = $this->base;

		return wp_raise_memory_limit( $context );
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
}

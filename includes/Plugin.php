<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

#[\AllowDynamicProperties] // TODO: implement the magic methods `__get()` and `__set()`
class Plugin
{

	const BASE = 'geditorial';

	private $asset_styles   = FALSE;
	private $asset_config   = FALSE;
	private $asset_darkmode = 0;
	private $asset_jsargs   = [];
	private $asset_icons    = [];
	private $editor_buttons = [];
	private $adminbar_nodes = [];

	private $_path;
	private $_options;
	private $_modules;

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new Plugin();
			$instance->setup();
		}

		return $instance;
	}

	public function __construct() {}

	private function setup()
	{
		if ( is_network_admin() || is_user_admin() )
			return FALSE;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			$referer = wp_get_raw_referer();

			if ( FALSE !== strpos( $referer, '/wp-admin/network/' ) )
				return FALSE;

			if ( FALSE !== strpos( $referer, '/wp-admin/user/' ) )
				return FALSE;
		}

		$this->_path    = GEDITORIAL_DIR.'includes/Modules/';
		$this->_modules = new \stdClass();
		$this->_options = new \stdClass();

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 20 );
		add_action( 'init', [ $this, 'init_late' ], 999 );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_bar_init', [ $this, 'admin_bar_init' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 999 );
		add_filter( 'mce_external_languages', [ $this, 'mce_external_languages' ] );
		add_filter( 'wp_default_autoload_value', [ $this, 'wp_default_autoload_value' ], 20, 4 );

		add_filter( static::BASE.'_markdown_to_html', [ $this, 'markdown_to_html' ] );

		if ( ! is_admin() ) {

			add_action( 'wp_footer', [ $this, 'footer_asset_config' ], 1 );
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
			add_filter( 'template_include', [ $this, 'template_include' ], 98 ); // before gTheme
		}

		do_action( sprintf( '%s_loaded', static::BASE ) );
	}

	public function files( $stack, $check = TRUE, $base = GEDITORIAL_DIR )
	{
		foreach ( (array) $stack as $path )

			if ( ! $check )
				require_once $base.'includes/'.$path.'.php';

			else if ( Core\File::readable( $base.'includes/'.$path.'.php' ) )
				require_once $base.'includes/'.$path.'.php';
	}

	public function admin_init()
	{
		add_action( 'doing_dark_mode', [ $this, 'doing_dark_mode' ] );
		add_action( 'admin_print_styles', [ $this, 'admin_print_styles' ] );
		add_action( 'admin_print_footer_scripts', [ $this, 'footer_asset_config' ], 9 );
	}

	public function plugins_loaded()
	{
		$this->load_textdomains( GEDITORIAL_DIR );
		$this->define_constants();
		$this->load_modules();
		$this->load_options();
		$this->init_modules();
		$this->setup_services();

		// \TenUp\ContentConnect\Plugin::instance();
	}

	// NOTE: `custom path` once set by `load_plugin_textdomain()`
	// NOTE: assumes the plugin directory is the same as the textdomain
	private function load_textdomains( $path )
	{
		load_plugin_textdomain( static::BASE, FALSE, static::BASE.'/languages' );

		if ( ! is_admin() )
			return;

		$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), static::BASE );
		load_textdomain( static::BASE.'-admin', $path."languages/admin-{$locale}.mo", $locale );
	}

	private function define_constants()
	{
		$constants = [
			'GEDITORIAL_BETA_FEATURES'     => TRUE,
			'GEDITORIAL_LOAD_PRIVATES'     => FALSE,
			'GEDITORIAL_DEBUG_MODE'        => FALSE,
			'GEDITORIAL_THRIFT_MODE'       => FALSE,
			'GEDITORIAL_DISABLE_CREDITS'   => FALSE,
			'GEDITORIAL_DISABLE_HELP_TABS' => FALSE,
			'GEDITORIAL_STRING_DELIMITERS' => NULL,

			'GEDITORIAL_CACHE_DIR' => WP_CONTENT_DIR.'/cache', // FALSE to disable
			'GEDITORIAL_CACHE_URL' => WP_CONTENT_URL.'/cache',
			'GEDITORIAL_CACHE_TTL' => 60 * 60 * 12, // 12 hours
		];

		foreach ( $constants as $key => $val )
			defined( $key ) || define( $key, $val );
	}

	private function load_modules()
	{
		foreach ( scandir( $this->_path ) as $module ) {

			if ( in_array( $module, [ '.', '..' ] ) )
				continue;

			if ( ! file_exists( $this->_path.$module.'/'.$module.'.php' ) )
				continue;

			if ( $class = Helper::moduleClass( $module ) )
				$this->register_module( call_user_func( [ $class, 'module' ] ), $module, $class );
		}
	}

	public function register_module( $args = [], $folder = FALSE, $class = NULL )
	{
		if ( FALSE === $args )
			return FALSE;

		if ( ! isset( $args['name'], $args['title'] ) )
			return FALSE;

		$defaults = [
			'folder'     => $folder,
			'class'      => $class ?: Helper::moduleClass( $args['name'], FALSE ),
			'icon'       => 'screenoptions', // `dashicons` class / SVG icon array
			'textdomain' => sprintf( '%s-%s', static::BASE, Core\Text::sanitizeBase( $args['name'] ) ), // or `NULL` for plugin base
			'configure'  => TRUE,  // or `settings`, `tools`, `reports`, `imports`, `customs`, `FALSE` to disable
			'i18n'       => TRUE,  // or `FALSE`, `adminonly`, `frontonly`, `restonly`
			'frontend'   => TRUE,  // Whether or not the module should be loaded on the frontend
			'autoload'   => FALSE, // Auto-loading a module will remove the ability to enable/disable it
			'disabled'   => FALSE, // FALSE or string explaining why the module is not available
			'access'     => 'unknown', // or `private`, `stable`, `beta`, `alpha`, `beta`, `deprecated`, `planned`
			'keywords'   => [],
		];

		$this->_modules->{$args['name']} = (object) array_merge( $defaults, $args );

		return TRUE;
	}

	private function load_options()
	{
		$frontend = ! is_admin();
		$options  = get_option( static::BASE.'_options' );

		foreach ( $this->_modules as $mod_name => &$module ) {

			// Skip on the frontend?
			if ( $frontend && ! $module->frontend )
				continue;

			if ( ! isset( $options[$mod_name] ) || FALSE === $options[$mod_name] )
				$this->_options->{$mod_name} = new \stdClass();
			else
				$this->_options->{$mod_name} = $options[$mod_name];
		}
	}

	private function init_modules()
	{
		$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), static::BASE );
		$stage  = Helper::const( 'WP_STAGE', 'production' ); // 'development'

		foreach ( $this->_modules as $mod_name => &$module ) {

			if ( ! isset( $this->_options->{$mod_name} ) )
				continue;

			if ( ! Helper::moduleLoading( $module, $stage ) )
				continue;

			if ( $module->autoload || Helper::moduleEnabled( $this->_options->{$mod_name} ) ) {

				$class = $module->class;
				$this->{$mod_name} = new $class( $module, $this->_options->{$mod_name}, $this->_path, $locale );
			}
		}

		// unloading memory!
		unset( $this->_path, $this->_options );

		if ( ! is_admin() )
			$this->_modules = FALSE;
	}

	private function setup_services()
	{
		$available = [
			'AdminScreen',
			'AdvancedQueries',
			'Barcodes',
			// 'BinaryPond',
			// 'Calendars',
			'CustomPostType',
			'CustomTaxonomy',
			'HeaderButtons',
			'Individuals',
			'LateChores',
			'LineDiscovery',
			'Locations',
			'ObjectHints',
			'ObjectToObject',
			'Paired',
			'PostTypeFields',
			'PrimaryTaxonomy',
			// 'PublicInterface',
			'RestAPI',
			'SearchSelect',
			// 'ShortMessages',
			'Sitemaps',
			'TaxonomyTaxonomy',
			'TermHierarchy',
			'TermRelations',
		];

		foreach ( $available as $service )
			if ( is_callable( [ __NAMESPACE__.'\\Services\\'.$service, 'setup' ] ) )
				call_user_func( [ __NAMESPACE__.'\\Services\\'.$service, 'setup' ] );
	}

	public function init_late()
	{
		if ( count( $this->editor_buttons )
			&& 'true' == get_user_option( 'rich_editing' ) ) {

			add_filter( 'mce_external_plugins', [ $this, 'mce_external_plugins' ] );
			add_filter( 'mce_buttons', [ $this, 'mce_buttons' ] );
		}

		if ( ! is_admin() )
			return;

		$this->_handle_set_screen_options();
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|' );

		foreach ( $this->editor_buttons as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		foreach ( $this->editor_buttons as $plugin => $filepath )
			if ( $filepath )
				$plugin_array[$plugin] = $filepath;

		return $plugin_array;
	}

	// TODO: move this to `AdminScreen` Service
	private function _handle_set_screen_options()
	{
		add_filter( 'screen_settings', [ $this, 'screen_settings' ], 12, 2 );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 12, 3 );

		if ( ! $posttype = Core\Base::req( 'post_type', 'post' ) )
			return FALSE;

		$name = sprintf( '%s-restrict-%s', static::BASE, $posttype );

		if ( ! isset( $_POST[$name] ) )
			return FALSE;

		check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

		return update_user_option( get_current_user_id(), sprintf( '%s_restrict_%s', static::BASE, $posttype ), array_filter( array_keys( $_POST[$name] ) ) );
	}

	// NOTE: see `corerestrictposts__hook_screen_taxonomies()`
	public function screen_settings( $settings, $screen )
	{
		$taxonomies = apply_filters( static::BASE.'_screen_restrict_taxonomies', [], $screen );

		if ( empty( $taxonomies ) )
			return $settings;

		$selected = get_user_option( sprintf( '%s_restrict_%s', static::BASE, $screen->post_type ) );
		$name     = sprintf( '%s-restrict-%s', static::BASE, $screen->post_type );

		$html = '<fieldset><legend>'._x( 'Restrictions', 'Plugin: Main: Screen Settings Title', 'geditorial-admin' ).'</legend>';

		$html.= Core\HTML::multiSelect( array_map( 'get_taxonomy', $taxonomies ), [
			'item_tag' => FALSE, // 'span',
			'prop'     => 'label',
			'value'    => 'name',
			'id'       => static::BASE.'-tax-restrictions',
			'name'     => $name,
			'selected' => FALSE === $selected ? $taxonomies : $selected,
		] );

		// hidden to clear the settings
		$html.= '<input type="hidden" name="'.$name.'[0]" value="1" /></fieldset>';

		return $settings.$html;
	}

	// lets our screen options passing through
	// @since WP 5.4.2 Only applied to options ending with '_page',
	// or the 'layout_columns' option
	// @REF: https://core.trac.wordpress.org/changeset/47951
	public function set_screen_option( $false, $option, $value )
	{
		return Core\Text::starts( $option, static::BASE ) ? $value : $false;
	}

	// HELPER
	public function get_module_by( $key, $value )
	{
		if ( empty( $this->_modules ) )
			return FALSE;

		foreach ( $this->_modules as $mod_name => &$module ) {

			if ( $key == 'name' && $value == $mod_name )
				return $module;

			foreach ( $module as $mod_data_key => $mod_data_value )
				if ( $mod_data_key == $key && $mod_data_value == $value )
					return $module;
		}

		return FALSE;
	}

	public function enabled( $module, $setup_check = TRUE )
	{
		if ( empty( $module ) )
			return FALSE;

		if ( ! isset( $this->{$module} ) )
			return FALSE;

		if ( $setup_check && $this->{$module}->setup_disabled() )
			return FALSE;

		return TRUE;
	}

	public function disable_process( $module, $context = 'import' )
	{
		// already not enabled!
		if ( ! $this->enabled( $module ) )
			return TRUE;

		return $this->{$module}->disable_process( $context );
	}

	public function count()
	{
		return empty( $this->_modules ) ? 0 : count( get_object_vars( $this->_modules ) );
	}

	public function modules( $orderby = FALSE )
	{
		if ( empty( $this->_modules ) )
			return [];

		if ( FALSE === $orderby || Core\Base::const( 'GEDITORIAL_THRIFT_MODE' ) )
			return (array) $this->_modules;

		if ( in_array( Core\L10n::locale(), [ 'fa', 'fa_IR', 'fa_AF' ] ) )
			return Core\L10n::sortAlphabet( (array) $this->_modules, $orderby );

		return wp_list_sort( (array) $this->_modules, $orderby );
	}

	// NOTE: must check for enabled before this!
	public function module( $name )
	{
		return $this->{$name};
	}

	public function constant( $module, $key, $default = NULL )
	{
		if ( $module && self::enabled( $module ) )
			return $this->{$module}->constant( $key, $default );

		return $default;
	}

	// NOTE: DEPRECATED
	public function get_constant( $module, $key, $default = NULL )
	{
		return $this->{$module}->constant( $key, $default );
	}

	public function list_modules( $enabled_only = FALSE, $orderby = 'title' )
	{
		$list = [];

		if ( empty( $this->_modules ) )
			return $list;

		foreach ( $this->modules( $orderby ) as $module )
			if ( ! $enabled_only || $this->enabled( $module->name, FALSE ) )
				$list[$module->name] = $module->title;

		return $list;
	}

	public function update_module_option( $name, $key, $value )
	{
		if  ( ! $options = get_option( 'geditorial_options' ) )
			$options = [];

		if ( isset( $options[$name] ) )
			$module_options = (object) $options[$name]; // NOTE: upon import will convert into arrays
		else
			$module_options = new \stdClass();

		$module_options->{$key} = $value;
		$options[$name] = $module_options;

		return update_option( 'geditorial_options', $options, TRUE );
	}

	public function update_all_module_options( $name, $new_options )
	{
		$options = get_option( 'geditorial_options' );

		if ( is_array( $new_options ) )
			$new_options = (object) $new_options;

		$options[$name] = $new_options;
		return update_option( 'geditorial_options', $options, TRUE );
	}

	// FIXME: DROP THIS
	public function audit_options()
	{
		$options = [];

		if ( empty( $this->_modules ) )
			return $options;

		foreach ( $this->_modules as $name => $enabled )
			$options[$name] = get_option( static::BASE.'_'.$name.'_options', '{{NO-OPTIONS}}' );

		$options['{{GLOBAL}}'] = get_option( 'geditorial_options', FALSE );

		return $options;
	}

	// FIXME: DROP THIS
	public function upgrade_old_options()
	{
		$options  = get_option( 'geditorial_options' );
		$upgraded = [];
		$update   = FALSE;

		if ( empty( $this->_modules ) )
			return $upgraded;

		foreach ( $this->_modules as $mod_name => &$module ) {

			$key = static::BASE.'_'.$mod_name.'_options';
			$old = get_option( $key );

			if ( isset( $options[$mod_name] ) ) {
				$upgraded[$mod_name] = delete_option( $key );

			} else if ( $old ) {
				$upgraded[$mod_name] = delete_option( $key );
				$options[$mod_name]  = $old;

				$update = TRUE;
			}
		}

		if ( $update )
			update_option( 'geditorial_options', $options, TRUE );

		return $upgraded;
	}

	// @REF: https://github.com/danieltj27/Dark-Mode/wiki/Help:-Plugin-Compatibility-Guide
	public function doing_dark_mode( $user_id )
	{
		$this->asset_darkmode = $user_id;
	}

	public function admin_print_styles()
	{
		$screen = get_current_screen();

		// NOTE: renders before this plugin styles
		if ( ! defined( 'GNETWORK_VERSION' ) )
			Helper::linkStyleSheetAdmin( 'gnetwork' );

		if ( Core\WordPress::isIFrame() )
			Helper::linkStyleSheetAdmin( 'iframe' );

		else if ( in_array( $screen->base, [
			'post',
			'edit',
			'widgets',
			'term',
			'edit-tags',
			'edit-comments',
			'users',
		] ) )
			Helper::linkStyleSheetAdmin( $screen->base );

		else if ( Core\Text::starts( $screen->base, 'woocommerce_page' ) )
			Helper::linkStyleSheetAdmin( 'woocommerce' );

		else if ( Settings::isReports( $screen ) )
			Helper::linkStyleSheetAdmin( 'reports' );

		else if ( Settings::isTools( $screen ) )
			Helper::linkStyleSheetAdmin( 'tools' );

		else if ( Settings::isImports( $screen ) )
			Helper::linkStyleSheetAdmin( 'imports' );

		else if ( Settings::isCustoms( $screen ) )
			Helper::linkStyleSheetAdmin( 'customs' );

		else if ( Settings::isSettings( $screen ) )
			Helper::linkStyleSheetAdmin( 'settings' );

		else if ( Settings::isDashboard( $screen ) )
			Helper::linkStyleSheetAdmin( 'dashboard' );

		if ( $this->asset_darkmode )
			Helper::linkStyleSheetAdmin( 'darkmode' );
	}

	public function mce_external_languages( $languages )
	{
		return array_merge( $languages, [ 'geditorial' => GEDITORIAL_DIR.'includes/Misc/TinyMceStrings.php' ] );
	}

	public function wp_default_autoload_value( $autoload, $option, $value, $serialized_value )
	{
		return $option === static::BASE.'_options' ? TRUE : $autoload;
	}

	public function template_include( $template )
	{
		if ( ! $custom = get_page_template_slug() )
			return $template;

		if ( $in_theme = locate_template( 'editorial/templates/'.$custom ) )
			return $in_theme;

		if ( file_exists( GEDITORIAL_DIR.'includes/templates/'.$custom ) )
			return GEDITORIAL_DIR.'includes/templates/'.$custom;

		return $template;
	}

	public function get_header( $name = 'editorial' )
	{
		if ( defined( 'GTHEME_VERSION' ) )
			return;

		get_header( $name );
	}

	public function get_footer( $name = 'editorial' )
	{
		if ( defined( 'GTHEME_VERSION' ) )
			return;

		get_footer( $name );
	}

	public function markdown_to_html( $raw )
	{
		return Helper::mdExtra( $raw );
	}

	public function enqueue_styles()
	{
		$this->asset_styles = TRUE;
	}

	public function wp_enqueue_scripts()
	{
		if ( count( $this->adminbar_nodes ) && is_admin_bar_showing() ) {
			wp_enqueue_style( static::BASE.'-adminbar', GEDITORIAL_URL.'assets/css/adminbar.all.css', [], GEDITORIAL_VERSION );
			wp_style_add_data( static::BASE.'-adminbar', 'rtl', 'replace' );
		}

		if ( ! $this->asset_styles )
			return;

		if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
			return;

		wp_enqueue_style( static::BASE.'-front', GEDITORIAL_URL.'assets/css/front.all.css', [], GEDITORIAL_VERSION );
		wp_style_add_data( static::BASE.'-front', 'rtl', 'replace' );

		if ( WordPress\WooCommerce::isActive() ) {
			wp_enqueue_style( static::BASE.'-woocommerce-front', GEDITORIAL_URL.'assets/css/front.woocommerce.css', [], GEDITORIAL_VERSION );
			wp_style_add_data( static::BASE.'-woocommerce-front', 'rtl', 'replace' );
		}

		if ( defined( 'GNETWORK_VERSION' ) )
			return;

		wp_enqueue_style( 'gnetwork-front', GEDITORIAL_URL.'assets/css/front.gnetwork.css', [], GEDITORIAL_VERSION );
		wp_style_add_data( 'gnetwork-front', 'rtl', 'replace' );
	}

	public function enqueue_asset_config( $args = [], $module = NULL )
	{
		$this->asset_config = TRUE;

		if ( empty( $args ) )
			return TRUE;

		if ( is_null( $module ) )
			$this->asset_jsargs = array_merge( $this->asset_jsargs, $args );

		else if ( isset( $this->asset_jsargs[$module] ) )
			$this->asset_jsargs[$module] = array_merge( $this->asset_jsargs[$module], $args );

		else
			$this->asset_jsargs[$module] = $args;

		return TRUE;
	}

	// used in front & admin
	public function footer_asset_config()
	{
		if ( $this->asset_config )
			Scripts::printJSConfig( $this->asset_jsargs );

		Core\Icon::printSprites( $this->asset_icons );
	}

	public function icon( $name, $group, $enqueue = TRUE )
	{
		if ( $icon = Core\Icon::get( $name, $group ) ) {

			if ( ! $enqueue )
				return $icon;

			$key = $group.'_'.$name;

			if ( ! isset( $this->asset_icons[$key] ) )
				$this->asset_icons[$key] = [
					'icon'    => $name,
					'group'   => $group,
				];

			return $icon;
		}

		return FALSE;
	}

	public function register_editor_button( $button, $filepath )
	{
		$this->editor_buttons[$button] = GEDITORIAL_URL.$filepath;
	}

	public function admin_bar_init()
	{
		do_action_ref_array( 'geditorial_adminbar', [ &$this->adminbar_nodes, static::BASE ] );
	}

	public function admin_bar_menu( $wp_admin_bar )
	{
		if ( empty( $this->adminbar_nodes ) )
			return;

		if ( in_array( static::BASE, Core\Arraay::column( $this->adminbar_nodes, 'parent' ) ) ) {

			if ( ! is_user_logged_in() )
				$link = FALSE;

			else if ( WordPress\User::cuc( 'manage_options' ) )
				$link = Settings::settingsURL();

			else if ( WordPress\User::cuc( 'edit_others_posts' ) )
				$link = Settings::reportsURL();

			else
				$link = FALSE;

			$wp_admin_bar->add_node( [
				'id'     => static::BASE,
				'title'  => Visual::getAdminBarIconMarkup(),
				// 'parent' => 'top-secondary',
				'href'   => $link,
				'meta'   => [ 'title' => _x( 'Editorial', 'Plugin: Main: Adminbar Node', 'geditorial' ) ],
			] );
		}

		foreach ( $this->adminbar_nodes as $node )
			$wp_admin_bar->add_node( $node );
	}

	// @OLD: `Core\WordPress::getEditorialUserID()`
	public function user( $fallback = FALSE )
	{
		if ( function_exists( 'gNetwork' ) )
			return gNetwork()->user( $fallback );

		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return (int) GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' )
			&& ( $user_id = gtheme_get_option( 'default_user', 0 ) ) )
				return (int) $user_id;

		if ( $fallback )
			return get_current_user_id();

		return 0;
	}

	public function base_country()
	{
		if ( FALSE !== ( $country = Core\Base::const( 'GCORE_DEFAULT_COUNTRY_CODE', FALSE ) ) )
			return $country;

		if ( \function_exists( 'WC' ) )
			return WC()->countries->get_base_country();

		return 'IR';
	}

	// TODO: move following into Info!
	public static function na( $wrap = 'code' )
	{
		$message = __( 'N/A', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => '-na', 'title' => __( 'Not Available', 'geditorial' ) ], $message ) : $message;
	}

	public static function untitled( $wrap = 'span' )
	{
		$message = __( '(Untitled)', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => '-untitled', 'title' => __( 'No Title Available!', 'geditorial' ) ], $message ) : $message;
	}

	public static function denied( $wrap = 'p' )
	{
		$message = __( 'You don&#8217;t have permission to do this.', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => [ 'description', '-description', '-denied' ] ], $message ) : $message;
	}

	public static function wrong( $wrap = 'p' )
	{
		$message = __( 'Something&#8217;s wrong!', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => [ 'description', '-description', '-wrong' ] ], $message ) : $message;
	}

	public static function moment( $wrap = 'p' )
	{
		$message = __( 'Wait for a moment &hellip;', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => [ 'description', '-description', '-moment' ] ], $message ) : $message;
	}

	public static function invalid( $wrap = 'p' )
	{
		$message = __( 'Invalid data provided!', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => [ 'description', '-description', '-invalid' ] ], $message ) : $message;
	}

	public static function noinfo( $wrap = 'p' )
	{
		$message = __( 'There is no information available!', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => [ 'description', '-description', '-empty', '-noinfo' ] ], $message ) : $message;
	}

	public static function done( $wrap = 'p' )
	{
		$message = __( 'All Done!', 'geditorial' );
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => [ 'description', '-description', '-empty', '-done' ] ], $message ) : $message;
	}
}

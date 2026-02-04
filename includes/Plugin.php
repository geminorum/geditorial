<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

#[\AllowDynamicProperties]
class Plugin extends WordPress\Plugin
{
	public $base = 'geditorial';

	private $asset_adminbar = FALSE;
	private $asset_styles   = FALSE;
	private $asset_config   = FALSE;
	private $asset_jsargs   = [];
	private $asset_icons    = [];
	private $editor_buttons = [];
	private $adminbar_nodes = [];

	private $_path;
	private $_options;
	private $_modules;

	const CAPABILITY_CUSTOMS  = 'editorial_customs';
	const CAPABILITY_IMPORTS  = 'editorial_imports';
	const CAPABILITY_REPORTS  = 'editorial_reports';
	const CAPABILITY_ROLES    = 'editorial_roles';
	const CAPABILITY_SETTINGS = 'editorial_settings';
	const CAPABILITY_TESTS    = 'editorial_tests';
	const CAPABILITY_TOOLS    = 'editorial_tools';

	protected function setup_check()
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

		return TRUE;
	}

	protected function initialize()
	{
		$this->_path    = sprintf( '%sincludes/Modules/', $this->__dir );
		$this->_modules = new \stdClass();
		$this->_options = new \stdClass();
	}

	protected function actions()
	{
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 20 );
		add_action( 'init', [ $this, 'init_late' ], 999 );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_bar_init', [ $this, 'admin_bar_init' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 999 );
		add_filter( 'mce_external_languages', [ $this, 'mce_external_languages' ] );
		add_filter( 'wp_default_autoload_value', [ $this, 'wp_default_autoload_value' ], 20, 4 );

		if ( ! is_admin() ) {

			add_action( 'wp_footer', [ $this, 'footer_asset_config' ], 1 );
			add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
			add_filter( 'template_include', [ $this, 'template_include' ], 98 );  // before `gTheme`
		}
	}

	public function admin_init()
	{
		add_action( 'admin_print_styles', [ $this, 'admin_print_styles' ], 999 );
		add_action( 'admin_print_footer_scripts', [ $this, 'footer_asset_config' ], 9 );
	}

	public function plugins_loaded()
	{
		$this->setup_services();
		$this->load_modules();
		$this->load_options();
		$this->init_modules();

		// \TenUp\ContentConnect\Plugin::instance();
	}

	// NOTE: `custom path` once set by `load_plugin_textdomain()`
	// NOTE: assumes the plugin directory is the same as the `textdomain`
	protected function textdomains()
	{
		parent::textdomains();

		if ( ! is_admin() )
			return;

		$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), $this->base );
		load_textdomain( $this->base.'-admin', $this->__dir."languages/admin-{$locale}.mo", $locale );
	}

	protected function late_constants()
	{
		return [
			'GEDITORIAL_SYSTEM_TITLE'      => NULL,
			'GEDITORIAL_SYSTEM_DESC'       => NULL,
			'GEDITORIAL_BETA_FEATURES'     => TRUE,
			'GEDITORIAL_LOAD_PRIVATES'     => FALSE,
			'GEDITORIAL_DEBUG_MODE'        => FALSE,
			'GEDITORIAL_THRIFT_MODE'       => FALSE,
			'GEDITORIAL_DISABLE_ICAL'      => FALSE,
			'GEDITORIAL_DISABLE_CREDITS'   => FALSE,
			'GEDITORIAL_DISABLE_HELP_TABS' => FALSE,
			'GEDITORIAL_STRING_DELIMITERS' => NULL,

			'GEDITORIAL_CACHE_DIR' => sprintf( '%s/cache', WP_CONTENT_DIR ),   // FALSE to disable
			'GEDITORIAL_CACHE_URL' => sprintf( '%s/cache', WP_CONTENT_URL ),
			'GEDITORIAL_CACHE_TTL' => 60 * 60 * 12,                            // 12 hours
		];
	}

	private function load_modules()
	{
		foreach ( scandir( $this->_path ) as $module ) {

			if ( in_array( $module, [ '.', '..', 'index.html' ] ) )
				continue;

			if ( ! file_exists( $this->_path.$module.'/'.$module.'.php' ) )
				continue;

			if ( $class = Services\Modulation::moduleClass( $module, TRUE, __NAMESPACE__.'\\Modules' ) )
				$this->register_module( call_user_func( [ $class, 'module' ] ), $module, $class );
		}
	}

	// NOTE: public interface to register internal/external modules
	public function register_module( $args = [], $folder = FALSE, $class = NULL )
	{
		if ( ! $registration = Services\Modulation::moduleObject( $args, $folder, $class ) )
			return FALSE;

		$this->_modules->{$registration[0]} = $registration[1];
	}

	private function load_options()
	{
		$frontend = ! is_admin();
		$options  = get_option( $this->base.'_options' );

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
		$locale = apply_filters( 'plugin_locale', Core\L10n::locale(), $this->base );
		$stage  = Helper::const( 'WP_STAGE', 'production' ); // 'development'

		foreach ( $this->_modules as $mod_name => &$module ) {

			if ( ! isset( $this->_options->{$mod_name} ) )
				continue;

			if ( ! Services\Modulation::moduleLoading( $module, $stage ) )
				continue;

			if ( $module->autoload || Services\Modulation::moduleEnabled( $this->_options->{$mod_name} ) ) {

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
			'Avatars',
			'Barcodes',
			'Calendars',
			'ContentActions',
			'ContentBrand',
			'CustomPostType',
			'CustomTaxonomy',
			'FileCache',
			'FrontSettings',
			'HeaderButtons',
			'Icons',
			'Individuals',
			'LateChores',
			'LineDiscovery',
			'Locations',
			'Markup',
			'ObjectHints',
			'ObjectsToObjects',
			'Paired',
			'PostTypeFields',
			'PrimaryTaxonomy',
			'RestAPI',
			'SearchSelect',
			'SemiSecure',
			'Sitemaps',
			'SystemHeartbeat',
			'TaxonomyFields',
			'TaxonomyTaxonomy',
			'TermHierarchy',
			'TermRelations',
		];

		foreach ( $available as $service )
			if ( is_callable( [ __NAMESPACE__.'\\Services\\'.$service, 'setup' ] ) )
				call_user_func( [ __NAMESPACE__.'\\Services\\'.$service, 'setup' ] );
	}

	// TODO: Move to `ClassicEditor` Service
	public function init_late()
	{
		if ( count( $this->editor_buttons )
			&& 'true' == get_user_option( 'rich_editing' ) ) {

			add_filter( 'mce_external_plugins', [ $this, 'mce_external_plugins' ] );
			add_filter( 'mce_buttons', [ $this, 'mce_buttons' ] );
		}
	}

	// TODO: Move to `ClassicEditor` Service
	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|' );

		foreach ( $this->editor_buttons as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	// TODO: Move to `ClassicEditor` Service
	public function mce_external_plugins( $plugin_array )
	{
		foreach ( $this->editor_buttons as $plugin => $filepath )
			if ( $filepath )
				$plugin_array[$plugin] = $filepath;

		return $plugin_array;
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
			return Misc\Alphabet::sort( (array) $this->_modules, $orderby );

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
			$options[$name] = get_option( $this->base.'_'.$name.'_options', '{{NO-OPTIONS}}' );

		$options['{{GLOBAL}}'] = get_option( 'geditorial_options', FALSE );

		return $options;
	}

	public function upgrade_old_options()
	{
		$options  = get_option( 'geditorial_options' );
		$upgraded = [];
		$update   = FALSE;

		if ( empty( $this->_modules ) )
			return $upgraded;

		foreach ( $this->_modules as $mod_name => &$module ) {

			$key = $this->base.'_'.$mod_name.'_options';
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

	// TODO: Move to `AdminScreen` Service
	public function admin_print_styles()
	{
		$screen = get_current_screen();

		// NOTE: renders before this plugin styles
		if ( ! defined( 'GNETWORK_VERSION' ) )
			Helper::linkStyleSheetAdmin( 'gnetwork' );

		// NOTE: must check before IFRAME
		if ( WordPress\IsIt::customize() )
			Helper::linkStyleSheetAdmin( 'customize' );

		else if ( WordPress\IsIt::iFrame() )
			Helper::linkStyleSheetAdmin( 'iframe' );

		else if ( in_array( $screen->base, [
			'post',
			'edit',
			'widgets',
			'term',
			'edit-tags',
			'edit-comments',
			'users',
			'dashboard',
		] ) )
			Helper::linkStyleSheetAdmin( $screen->base );

		else if ( Core\Text::starts( $screen->base, 'dashboard_page' )
			&& ! Core\Text::ends( $screen->base, [ 'reports' ] ) ) // NOTE: contexts displaying under dashboard-page
			Helper::linkStyleSheetAdmin( 'dashboard' );

		else if ( Core\Text::starts( $screen->base, 'woocommerce_page' ) )
			Helper::linkStyleSheetAdmin( 'woocommerce' );

		else {

			foreach ( [
				'reports',
				'tools',
				'imports',
				'customs',
				'settings',
				'dashboard',
			] as $context )
				if ( Settings::isScreenContext( $context, $screen ) ) {
					Helper::linkStyleSheetAdmin( $context );
					break;
				}
		}

		// Always add admin-bar styles to admin
		if ( is_admin_bar_showing() )
			Helper::linkStyleSheetAdmin( 'all', TRUE, 'adminbar' );
	}

	// TODO: Move to `ClassicEditor` Service
	public function mce_external_languages( $languages )
	{
		return array_merge( $languages, [ 'geditorial' => GEDITORIAL_DIR.'includes/Misc/TinyMceStrings.php' ] );
	}

	public function wp_default_autoload_value( $autoload, $option, $value, $serialized_value )
	{
		return $option === $this->base.'_options' ? TRUE : $autoload;
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

	// TODO: Move to `AssetRegistry` Service
	// NOTE: `enqueue` means just the styles, not the admin-bar itself!
	public function enqueue_adminbar( $value = NULL )
	{
		return $this->asset_adminbar = $value ?? $this->asset_adminbar;
	}

	// TODO: Move to `AssetRegistry` Service
	// NOTE: passing `NULL` returns the current state.
	// FIXME: default must be `NULL` / caller must pass the intended value!
	public function enqueue_styles( $value = TRUE )
	{
		return $this->asset_adminbar = $value ?? $this->asset_adminbar;
	}

	// TODO: Move to `AssetRegistry` Service
	public function wp_enqueue_scripts()
	{
		$this->enqueue_asset_adminbar();

		if ( ! $this->asset_styles )
			return;

		if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
			return;

		Scripts::enqueueStyleSrc(
			sprintf( '%sassets/css/%s.%s.css', $this->get_url(), 'front', 'all' ),
			implode( '-', [ $this->base, 'front' ] ),
			$this->get_ver()
		);

		if ( WordPress\WooCommerce::isActive() )
			Scripts::enqueueStyleSrc(
				sprintf( '%sassets/css/%s.%s.css', $this->get_url(), 'front', 'woocommerce' ),
				implode( '-', [ $this->base, 'woocommerce', 'front' ] ),
				$this->get_ver()
			);

		if ( defined( 'GNETWORK_VERSION' ) )
			return;

		Scripts::enqueueStyleSrc(
			sprintf( '%sassets/css/%s.%s.css', $this->get_url(), 'front', 'gnetwork' ),
			implode( '-', [ $this->base, 'gnetwork', 'front' ] ),
			$this->get_ver()
		);
	}

	// TODO: Move to `AssetRegistry` Service
	public function enqueue_asset_adminbar()
	{
		if ( ! is_admin_bar_showing() )
			return FALSE;

		if ( count( $this->adminbar_nodes ) )
			$this->asset_adminbar = TRUE;

		if ( ! $this->asset_adminbar )
			return FALSE;

		return Scripts::enqueueStyleSrc(
			sprintf( '%sassets/css/%s.%s.css', $this->get_url(), 'adminbar', 'all' ),
			implode( '-', [ $this->base, 'adminbar' ] ),
			$this->get_ver()
		);
	}

	// TODO: Move to `AssetRegistry` Service
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
	// TODO: Move to `AssetRegistry` Service
	public function footer_asset_config()
	{
		if ( $this->asset_config )
			Scripts::printJSConfig( $this->asset_jsargs );

		Core\Icon::printSprites( $this->asset_icons );
	}

	// TODO: Move to `AssetRegistry` Service
	public function icon( $name, $group, $extra = [], $enqueue = TRUE )
	{
		if ( $icon = Core\Icon::get( $name, $group, $extra ) ) {

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

	// TODO: Move to `ClassicEditor` Service
	public function register_editor_button( $button, $filepath )
	{
		$this->editor_buttons[$button] = sprintf( '%s%S', $this->get_url(), $filepath );
	}

	// TODO: Move to `AdminbarRegistry` Service
	public function admin_bar_init()
	{
		do_action_ref_array( 'geditorial_adminbar', [ &$this->adminbar_nodes, $this->base ] );
	}

	// TODO: Move to `AdminbarRegistry` Service
	public function admin_bar_menu( $wp_admin_bar )
	{
		do_action_ref_array( 'geditorial_adminbar_lastcall', [ &$this->adminbar_nodes, $this->base ] );

		if ( empty( $this->adminbar_nodes ) )
			return;

		if ( in_array( $this->base, Core\Arraay::column( $this->adminbar_nodes, 'parent' ) ) ) {

			if ( ! is_user_logged_in() )
				$link = FALSE;

			else if ( current_user_can( 'manage_options' ) )
				$link = Settings::getURLbyContext( 'settings' );

			else if ( current_user_can( 'edit_others_posts' ) )
				$link = Settings::getURLbyContext( 'reports' );

			else
				$link = FALSE;

			$wp_admin_bar->add_node( [
				// 'parent' => 'top-secondary',
				'id'     => $this->base,
				'title'  => Services\Icons::adminBarMarkup(),
				'href'   => $link,
				'meta'   => [
					'title' => self::system() ?: _x( 'Editorial', 'Plugin: Main: Adminbar Node', 'geditorial' ),
					'class' => implode( '-', [ $this->base, 'adminbar', 'node', 'icononly' ] ),
				],
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

	// TODO: Move to `Locations` Service / keep wrapper method on plugin object
	public function base_country()
	{
		if ( FALSE !== ( $country = Core\Base::const( 'GCORE_DEFAULT_COUNTRY_CODE', FALSE ) ) )
			return $country;

		if ( \function_exists( 'WC' ) )
			return WC()->countries->get_base_country();

		return 'IR';
	}

	// TODO: move following into Info!
	public static function system( $wrap = FALSE, $fallback = '' )
	{
		if ( ! $message = self::const( 'GEDITORIAL_SYSTEM_TITLE', NULL ) ?? _x( 'Editorial', 'Plugin: System Title', 'geditorial' ) ) return $fallback;
		return $wrap ? Core\HTML::tag( $wrap, [ 'class' => '-system', 'title' => self::const( 'GEDITORIAL_SYSTEM_DESC', NULL ) ?? _x( 'Our Editorial in WordPress', 'Plugin: System Description', 'geditorial' ) ], $message ) : $message;
	}

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

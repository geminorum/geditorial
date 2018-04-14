<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\User;

class Plugin
{

	const BASE = 'geditorial';

	private $asset_styles   = FALSE;
	private $asset_config   = FALSE;
	private $asset_jsargs   = [];
	private $asset_icons    = [];
	private $editor_buttons = [];
	private $adminbar_nodes = [];

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new Plugin;
			$instance->setup();
		}

		return $instance;
	}

	public function __construct() { }

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

		$this->modules = new \stdClass();
		$this->options = new \stdClass();

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 20 );
		add_action( 'init', [ $this, 'init_late' ], 999 );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_bar_init', [ $this, 'admin_bar_init' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 999 );
		add_filter( 'mce_external_languages', [ $this, 'mce_external_languages' ] );

		if ( is_admin() )
			return;

		add_action( 'wp_footer', [ $this, 'footer_asset_config' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
		add_filter( 'template_include', [ $this, 'template_include' ], 98 ); // before gTheme
	}

	public function files( $stack, $check = TRUE, $base = GEDITORIAL_DIR )
	{
		foreach ( (array) $stack as $path )

			if ( ! $check )
				require_once( $base.'includes/'.$path.'.php' );

			else if ( file_exists( $base.'includes/'.$path.'.php' ) )
				require_once( $base.'includes/'.$path.'.php' );
	}

	private function require_core()
	{
		$this->files( [
			'Core/Base',

			'Core/Arraay',
			'Core/Color',
			'Core/Date',
			'Core/File',
			'Core/HTML',
			'Core/Icon',
			'Core/HTTP',
			'Core/L10n',
			'Core/Number',
			'Core/Text',
			'Core/Third',
			'Core/URL',
			'Core/WordPress',

			'O2O/API',
			'O2O/Utils',
			'O2O/Exception',
			'O2O/ConnectionTypeFactory',
			'O2O/ConnectionType',
			'O2O/DirectionStrategy',
			'O2O/DirectedConnectionType',
			'O2O/DeterminateConnectionType',
			'O2O/IndeterminateConnectionType',
			'O2O/IndeterminateDirectedConnectionType',
			'O2O/ReciprocalConnectionType',
			'O2O/Side',
			'O2O/SidePost',
			'O2O/SideAttachment',
			'O2O/SideUser',
			'O2O/Item',
			'O2O/ItemAny',
			'O2O/ItemPost',
			'O2O/ItemAttachment',
			'O2O/ItemUser',
			'O2O/Query',
			'O2O/ListItems',
			'O2O/ListRenderer',

			'WordPress/Database',
			'WordPress/DBTable',
			'WordPress/Media',
			'WordPress/Module',
			'WordPress/PostType',
			'WordPress/Taxonomy',
			'WordPress/Theme',
			'WordPress/User',
		] );
	}

	private function require_plugin()
	{
		$this->files( [
			'Ajax',
			'Helper',
			'Listtable',
			'Scripts',
			'MetaBox',
			'Relation',
			'Settings',
			'Template',
			'ShortCode',
			'Widget',
			'Module',
		] );
	}

	public function admin_init()
	{
		add_action( 'edit_form_after_title', [ $this, 'edit_form_after_title' ] );
		add_action( 'admin_print_styles', [ $this, 'admin_print_styles' ] );
		add_action( 'admin_print_footer_scripts', [ $this, 'footer_asset_config' ], 9 );
	}

	public function plugins_loaded()
	{
		load_plugin_textdomain( GEDITORIAL_TEXTDOMAIN, FALSE, 'geditorial/languages' );

		// $this->require_core();
		// $this->require_plugin();

		foreach ( scandir( GEDITORIAL_DIR.'modules/' ) as $module ) {

			if ( in_array( $module, [ '.', '..' ] ) )
				continue;

			if ( file_exists( GEDITORIAL_DIR.'modules/'.$module.'/'.$module.'.php' ) ) {
				include_once( GEDITORIAL_DIR.'modules/'.$module.'/'.$module.'.php' );

				if ( $class = Helper::moduleClass( $module ) )
					$this->register_module( call_user_func( [ $class, 'module' ] ) );
			}
		}

		$this->load_module_options();

		foreach ( $this->modules as $mod_name => &$module ) {

			if ( ! isset( $this->options->{$mod_name} ) )
				continue;

			if ( $module->autoload || Helper::moduleEnabled( $this->options->{$mod_name} ) ) {

				$class = $module->class;
				$this->{$mod_name} = new $class( $module, $this->options->{$mod_name} );
			}
		}

		// Relation::setup();
	}

	public function register_module( $args = [] )
	{
		if ( FALSE === $args )
			return FALSE;

		if ( ! isset( $args['name'], $args['title'] ) )
			return FALSE;

		$defaults = [
			'class'     => Helper::moduleClass( $args['name'], FALSE ),
			'icon'      => 'screenoptions', // dashicon class / svg icon array
			'configure' => TRUE,
			'frontend'  => TRUE,  // whether or not the module should be loaded on the frontend
			'autoload'  => FALSE, // autoloading a module will remove the ability to enable/disable it
			'disabled'  => FALSE, // or string explaining why the module is not available
		];

		$this->modules->{$args['name']} = (object) array_merge( $defaults, $args );

		return TRUE;
	}

	private function load_module_options()
	{
		$options = get_option( 'geditorial_options' );

		foreach ( $this->modules as $mod_name => &$module ) {

			// skip on the frontend?
			if ( ! is_admin() && ! $module->frontend )
				continue;

			if ( ! isset( $options[$mod_name] ) || FALSE === $options[$mod_name] )
				$this->options->{$mod_name} = new \stdClass;
			else
				$this->options->{$mod_name} = $options[$mod_name];
		}
	}

	public function init_late()
	{
		if ( count( $this->editor_buttons )
			&& 'true' == get_user_option( 'rich_editing' ) ) {

			add_filter( 'mce_external_plugins', [ $this, 'mce_external_plugins' ] );
			add_filter( 'mce_buttons', [ $this, 'mce_buttons' ] );
		}
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
			$plugin_array[$plugin] = $filepath;

		return $plugin_array;
	}

	// HELPER
	public function get_module_by( $key, $value )
	{
		foreach ( $this->modules as $mod_name => &$module ) {

			if ( $key == 'name' && $value == $mod_name )
				return $module;

			foreach ( $module as $mod_data_key => $mod_data_value )
				if ( $mod_data_key == $key && $mod_data_value == $value )
					return $module;
		}

		return FALSE;
	}

	public function enabled( $module )
	{
		return isset( $this->{$module} );
	}

	public function count()
	{
		return count( get_object_vars( $this->modules ) );
	}

	public function modules( $orderby = FALSE )
	{
		if ( empty( $this->modules ) )
			return [];

		if ( FALSE === $orderby )
			return (array) $this->modules;

		$callback = [ __NAMESPACE__.'\\Modules\Alphabet', 'sort' ];

		if ( ! is_callable( $callback ) || 'fa_IR' != get_locale() )
			return wp_list_sort( (array) $this->modules, $orderby );

		return call_user_func_array( $callback, [ (array) $this->modules, $orderby ] );
	}

	public function constant( $module, $key, $default = NULL )
	{
		if ( $module && self::enabled( $module ) )
			return $this->{$module}->constant( $key, $default );

		return $default;
	}

	// FIXME: DEPRECATED
	public function get_constant( $module, $key, $default = NULL )
	{
		return $this->{$module}->constant( $key, $default );
	}

	public function list_modules( $enabled_only = FALSE, $orderby = 'title' )
	{
		$list = [];

		foreach ( $this->modules( $orderby ) as $module )
			if ( ! $enabled_only || $this->enabled( $module->name ) )
				$list[$module->name] = $module->title;

		return $list;
	}

	public function update_module_option( $name, $key, $value )
	{
		$options = get_option( 'geditorial_options' );

		if ( isset( $options[$name] ) )
			$module_options = $options[$name];
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

		foreach ( $this->modules as $name => $enabled )
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

		foreach ( $this->modules as $mod_name => &$module ) {

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

	// @REF: https://wpartisan.me/?p=434
	public function edit_form_after_title()
	{
		do_meta_boxes( get_current_screen(), 'after_title', $GLOBALS['post'] );
	}

	public function admin_print_styles()
	{
		$screen = get_current_screen();

		if ( WordPress::isIFrame() )
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

		else if ( Settings::isReports( $screen ) )
			Helper::linkStyleSheetAdmin( 'reports' );

		else if ( Settings::isTools( $screen ) )
			Helper::linkStyleSheetAdmin( 'tools' );

		else if ( Settings::isSettings( $screen ) )
			Helper::linkStyleSheetAdmin( 'settings' );

		else if ( Settings::isDashboard( $screen ) )
			Helper::linkStyleSheetAdmin( 'dashboard' );

		if ( ! defined( 'GNETWORK_VERSION' ) )
			Helper::linkStyleSheetAdmin( 'gnetwork' );
	}

	public function mce_external_languages( $languages )
	{
		return array_merge( $languages, [ 'geditorial' => GEDITORIAL_DIR.'includes/misc/editor-languages.php' ] );
	}

	public function template_include( $template )
	{
		if ( ! $custom = get_page_template_slug() )
			return $template;

		if ( $in_theme = locate_template( 'editorial/'.$custom ) )
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

	public function enqueue_styles()
	{
		$this->asset_styles = TRUE;
	}

	public function wp_enqueue_scripts()
	{
		if ( count( $this->adminbar_nodes ) && is_admin_bar_showing() )
			wp_enqueue_style( 'geditorial-adminbar', GEDITORIAL_URL.'assets/css/adminbar.all'.( is_rtl() ? '-rtl' : '' ).'.css', [], GEDITORIAL_VERSION );

		if ( ! $this->asset_styles )
			return;

		if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
			return;

		wp_enqueue_style( 'geditorial-front', GEDITORIAL_URL.'assets/css/front.all'.( is_rtl() ? '-rtl' : '' ).'.css', [], GEDITORIAL_VERSION );
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
			Ajax::printJSConfig( $this->asset_jsargs );

		Icon::printSprites( $this->asset_icons );
	}

	public function icon( $name, $group, $enqueue = TRUE )
	{
		if ( $icon = Icon::get( $name, $group ) ) {

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

		if ( in_array( static::BASE, Arraay::column( $this->adminbar_nodes, 'parent' ) ) ) {

			if ( ! is_user_logged_in() )
				$link = FALSE;

			else if ( User::cuc( 'manage_options' ) )
				$link = Settings::settingsURL();

			else if ( User::cuc( 'edit_others_posts' ) )
				$link = Settings::reportsURL();

			else
				$link = FALSE;

			$wp_admin_bar->add_node( [
				'id'     => static::BASE,
				'title'  => Helper::getAdminBarIcon(),
				// 'parent' => 'top-secondary',
				'href'   => $link,
				'meta'   => [ 'title' => _x( 'Editorial', 'Plugin: Main: Adminbar Node', GEDITORIAL_TEXTDOMAIN ) ],
			] );
		}

		foreach ( $this->adminbar_nodes as $node )
			$wp_admin_bar->add_node( $node );
	}

	// @OLD: `WordPress::getEditorialUserID()`
	public function user( $fallback = FALSE )
	{
		if ( function_exists( 'gNetwork' ) )
			return gNetwork()->user( $fallback );

		if ( defined( 'GNETWORK_SITE_USER_ID' ) && GNETWORK_SITE_USER_ID )
			return intval( GNETWORK_SITE_USER_ID );

		if ( function_exists( 'gtheme_get_option' )
			&& ( $user_id = gtheme_get_option( 'default_user', 0 ) ) )
				return intval( $user_id );

		if ( $fallback )
			return intval( get_current_user_id() );

		return 0;
	}

	public static function na( $wrap = 'code' )
	{
		$na = __( 'N/A', GEDITORIAL_TEXTDOMAIN );
		return $wrap ? HTML::tag( $wrap, [ 'title' => __( 'Not Available', GEDITORIAL_TEXTDOMAIN ) ], $na ) : $na;
	}
}

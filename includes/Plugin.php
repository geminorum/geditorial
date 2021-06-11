<?php namespace geminorum\gEditorial;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core\Arraay;
use geminorum\gEditorial\Core\Base;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\L10n;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\User;

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

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new Plugin;
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

		if ( is_admin() )
			return;

		add_action( 'wp_footer', [ $this, 'footer_asset_config' ], 1 );
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
		add_filter( 'template_include', [ $this, 'template_include' ], 98 ); // before gTheme
		add_filter( 'the_content', [ $this, 'the_content' ], 998 );
	}

	public function files( $stack, $check = TRUE, $base = GEDITORIAL_DIR )
	{
		foreach ( (array) $stack as $path )

			if ( ! $check )
				require_once $base.'includes/'.$path.'.php';

			else if ( is_readable( $base.'includes/'.$path.'.php' ) )
				require_once $base.'includes/'.$path.'.php';
	}

	public function admin_init()
	{
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 9, 2 );
		add_action( 'doing_dark_mode', [ $this, 'doing_dark_mode' ] );
		add_action( 'admin_print_styles', [ $this, 'admin_print_styles' ] );
		add_action( 'admin_print_footer_scripts', [ $this, 'footer_asset_config' ], 9 );
	}

	public function plugins_loaded()
	{
		load_plugin_textdomain( 'geditorial', FALSE, 'geditorial/languages' );

		$this->load_modules();
		$this->load_options();
		$this->init_modules();

		// Relation::setup();
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
			'folder'    => $folder,
			'class'     => $class ?: Helper::moduleClass( $args['name'], FALSE ),
			'icon'      => 'screenoptions', // dashicon class / svg icon array
			'configure' => TRUE,
			'frontend'  => TRUE,  // whether or not the module should be loaded on the frontend
			'autoload'  => FALSE, // autoloading a module will remove the ability to enable/disable it
			'disabled'  => FALSE, // or string explaining why the module is not available
		];

		$this->_modules->{$args['name']} = (object) array_merge( $defaults, $args );

		return TRUE;
	}

	private function load_options()
	{
		$frontend = ! is_admin();
		$options  = get_option( 'geditorial_options' );

		foreach ( $this->_modules as $mod_name => &$module ) {

			// skip on the frontend?
			if ( $frontend && ! $module->frontend )
				continue;

			if ( ! isset( $options[$mod_name] ) || FALSE === $options[$mod_name] )
				$this->_options->{$mod_name} = new \stdClass;
			else
				$this->_options->{$mod_name} = $options[$mod_name];
		}
	}

	private function init_modules()
	{
		$locale = apply_filters( 'plugin_locale', determine_locale(), static::BASE );

		foreach ( $this->_modules as $mod_name => &$module ) {

			if ( ! isset( $this->_options->{$mod_name} ) )
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

	private function _handle_set_screen_options()
	{
		add_filter( 'screen_settings', [ $this, 'screen_settings' ], 12, 2 );
		add_filter( 'set-screen-option', [ $this, 'set_screen_option' ], 12, 3 );

		if ( ! $posttype = Base::req( 'post_type', 'post' ) )
			return FALSE;

		$name = sprintf( '%s-restrict-%s', static::BASE, $posttype );

		if ( ! isset( $_POST[$name] ) )
			return FALSE;

		check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

		return update_user_option( get_current_user_id(), sprintf( '%s_restrict_%s', static::BASE, $posttype ), array_filter( array_keys( $_POST[$name] ) ) );
	}

	public function screen_settings( $settings, $screen )
	{
		$taxonomies = apply_filters( static::BASE.'_screen_restrict_taxonomies', [], $screen );

		if ( empty( $taxonomies ) )
			return $settings;

		$selected = get_user_option( sprintf( '%s_restrict_%s', static::BASE, $screen->post_type ) );
		$name     = sprintf( '%s-restrict-%s', static::BASE, $screen->post_type );

		$html = '<fieldset><legend>'._x( 'Restrictions', 'Plugin: Main: Screen Settings Title', 'geditorial' ).'</legend>';

		$html.= HTML::multiSelect( array_map( 'get_taxonomy', $taxonomies ), [
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
		return Text::start( $option, static::BASE ) ? $value : $false;
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

	public function enabled( $module )
	{
		return isset( $this->{$module} );
	}

	public function count()
	{
		return empty( $this->_modules ) ? 0 : count( get_object_vars( $this->_modules ) );
	}

	public function modules( $orderby = FALSE )
	{
		if ( empty( $this->_modules ) )
			return [];

		if ( FALSE === $orderby )
			return (array) $this->_modules;

		if ( in_array( get_locale(), [ 'fa', 'fa_IR', 'fa_AF' ] ) )
			return L10n::sortAlphabet( (array) $this->_modules, $orderby );

		return wp_list_sort( (array) $this->_modules, $orderby );
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

		if ( empty( $this->_modules ) )
			return $list;

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

	// @REF: https://wpartisan.me/?p=434
	// @REF: https://core.trac.wordpress.org/ticket/45283
	public function add_meta_boxes( $posttype, $post )
	{
		if ( PostType::supportBlocksByPost( $post ) )
			return;

		add_action( 'edit_form_after_title', [ $this, 'edit_form_after_title' ] );
	}

	public function edit_form_after_title( $post )
	{
		echo '<div id="postbox-container-after-title" class="postbox-container">';
			do_meta_boxes( get_current_screen(), 'after_title', $post );
		echo '</div>';
	}

	// @REF: https://github.com/danieltj27/Dark-Mode/wiki/Help:-Plugin-Compatibility-Guide
	public function doing_dark_mode( $user_id )
	{
		$this->asset_darkmode = $user_id;
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

		if ( $this->asset_darkmode )
			Helper::linkStyleSheetAdmin( 'darkmode' );
	}

	public function mce_external_languages( $languages )
	{
		return array_merge( $languages, [ 'geditorial' => GEDITORIAL_DIR.'includes/Misc/TinyMceStrings.php' ] );
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

	public function the_content( $content )
	{
		if ( defined( 'GEDITORIAL_DISABLE_CONTENT_ACTIONS' )
			&& GEDITORIAL_DISABLE_CONTENT_ACTIONS )
				return $content;

		$before = $after = '';

		if ( has_action( static::BASE.'_content_before' ) ) {
			ob_start();
				do_action( static::BASE.'_content_before', $content );
			$before = ob_get_clean();

			if ( trim( $before ) )
				$before = '<div class="'.static::BASE.'-wrap-actions content-before">'.$before.'</div>';
		}

		if ( has_action( static::BASE.'_content_after' ) ) {
			ob_start();
				do_action( static::BASE.'_content_after', $content );
			$after = ob_get_clean();

			if ( trim( $after ) )
				$after = '<div class="'.static::BASE.'-wrap-actions content-after">'.$after.'</div>';
		}

		return $before.$content.$after;
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
				'meta'   => [ 'title' => _x( 'Editorial', 'Plugin: Main: Adminbar Node', 'geditorial' ) ],
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
			return (int) GNETWORK_SITE_USER_ID;

		if ( function_exists( 'gtheme_get_option' )
			&& ( $user_id = gtheme_get_option( 'default_user', 0 ) ) )
				return (int) $user_id;

		if ( $fallback )
			return get_current_user_id();

		return 0;
	}

	public static function na( $wrap = 'code' )
	{
		$na = __( 'N/A', 'geditorial' );
		return $wrap ? HTML::tag( $wrap, [ 'title' => __( 'Not Available', 'geditorial' ) ], $na ) : $na;
	}
}

<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorial
{

	private $group = 'geditorial_';

	private $asset_styles   = FALSE;
	private $asset_config   = FALSE;
	private $asset_args     = array();
	private $editor_buttons = array();

	public static function instance()
	{
		static $instance = NULL;

		if ( NULL === $instance ) {
			$instance = new gEditorial;
			$instance->setup();
		}

		return $instance;
	}

	public function __construct() { }

	private function setup()
	{
		if ( is_network_admin() )
			return;

		load_plugin_textdomain( GEDITORIAL_TEXTDOMAIN, FALSE, 'geditorial/languages' );

		$this->modules = new stdClass();
		$this->options = new stdClass();

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 20 );
		add_action( 'init', array( $this, 'init_late' ), 999 );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_footer', array( $this, 'footer_asset_config' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_filter( 'mce_external_languages', array( $this, 'mce_external_languages' ) );
	}

	public function admin_init()
	{
		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'footer_asset_config' ), 999 );
	}

	public function plugins_loaded()
	{
		$includes = array(
			'base',
			'helper',
			'template',
			'widget',
			'module',
			// 'listtable',
			// 'shortcode',
		);

		foreach ( $includes as $include )
			require_once( GEDITORIAL_DIR.'includes/class-'.$include.'.php' );

		foreach ( scandir( GEDITORIAL_DIR.'modules/' ) as $module ) {

			if ( file_exists( GEDITORIAL_DIR.'modules/'.$module.'/'.$module.'.php' ) ) {
				include_once( GEDITORIAL_DIR.'modules/'.$module.'/'.$module.'.php' );

				if ( $class = gEditorialHelper::moduleClass( $module ) )
					$this->register_module( call_user_func( array( $class, 'module' ) ) );
			}
		}

		$this->load_module_options();

		foreach ( $this->modules as $mod_name => &$module ) {

			if ( ! isset( $this->options->{$mod_name} ) )
				continue;

			if ( $module->autoload || gEditorialHelper::moduleEnabled( $this->options->{$mod_name} ) ) {

				$class = $module->class;
				$this->{$mod_name} = new $class( $module, $this->options->{$mod_name} );
			}
		}
	}

	public function register_module( $args = array() )
	{
		if ( ! isset( $args['name'], $args['title'] ) )
			return FALSE;

		$defaults = array(
            'class'     => gEditorialHelper::moduleClass( $args['name'], FALSE ),
            'group'     => $this->group.$args['name'],
            'settings'  => 'geditorial-settings-'.$args['name'],
            'dashicon'  => 'smiley', // dashicon class
			'configure' => 'print_configure_view',
			'defaults'  => array(),
			'frontend'  => TRUE, // whether or not the module should be loaded on the frontend too
            'autoload'  => FALSE, // autoloading a module will remove the ability to enable or disable it
		);

		$this->modules->{$args['name']} = (object) array_merge( $defaults, $args );

		return TRUE;
	}

	private function load_module_options()
	{
		$options = get_option( 'geditorial_options' );

		foreach ( $this->modules as $mod_name => &$module ) {

			// don't load modules on the frontend unless they're explictly defined as such
			if ( ! is_admin() && ! $module->frontend )
				continue;

			if ( ! isset( $options[$mod_name] ) || FALSE === $options[$mod_name] )
				$this->options->{$mod_name} = new stdClass;
			else
				$this->options->{$mod_name} = $options[$mod_name];

			foreach ( $module->defaults as $key => $value )
				if ( ! isset( $this->options->{$mod_name}->{$key} ) )
					$this->options->{$mod_name}->{$key} = $value;
		}
	}

	public function init_late()
	{
		if ( count( $this->editor_buttons )
			&& 'true' == get_user_option( 'rich_editing' ) ) {

			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
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

	// HELPER
	public function enabled( $module )
	{
		return isset( $this->{$module} );
	}

	// HELPER
	public function get_constant( $module, $key, $default = NULL )
	{
		return $this->{$module}->constant( $key, $default );
	}

	// HELPER
	public function get_all_modules( $enabled_only = FALSE )
	{
		$modules = array();

		foreach ( $this->modules as $mod_name => &$module )
			if ( ! $enabled_only || isset( $this->{$name} ) )
				$modules[$mod_name] = $module->title;

		return $modules;
	}

	public function update_module_option( $name, $key, $value )
	{
		$options = get_option( 'geditorial_options' );

		if ( isset( $options[$name] ) )
			$module_options = $options[$name];
		else
			$module_options = new stdClass();

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
		$options = array();

		foreach ( $this->modules as $name => $enabled )
			$options[$name] = get_option( $this->group.$name.'_options', '{{NO-OPTIONS}}' );

		$options['{{GLOBAL}}'] = get_option( 'geditorial_options', FALSE );

		return $options;
	}

	// FIXME: DROP THIS
	public function upgrade_old_options()
	{
		$options  = get_option( 'geditorial_options' );
		$upgraded = array();
		$update   = FALSE;

		foreach ( $this->modules as $mod_name => &$module ) {

			$key = $this->group.$mod_name.'_options';
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

	public function admin_print_styles()
	{
		$screen = get_current_screen();

		if ( in_array( $screen->base, array( 'post', 'edit', 'widgets', 'edit-tags' ) ) )
			gEditorialHelper::linkStyleSheetAdmin( $screen->base );

		else if ( gEditorialHelper::isSettings( $screen ) )
			gEditorialHelper::linkStyleSheetAdmin( 'settings' );

		else if ( gEditorialHelper::isTools( $screen ) )
			gEditorialHelper::linkStyleSheetAdmin( 'tools' );
	}

	public function mce_external_languages( $languages )
	{
		$languages['geditorial'] = GEDITORIAL_DIR.'includes/mce-languages.php';
		return $languages;
	}

	public function enqueue_styles()
	{
		$this->asset_styles = TRUE;
	}

	public function wp_enqueue_scripts()
	{
		if ( ! $this->asset_styles )
			return;

		if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
			return;

		wp_enqueue_style( 'geditorial-front-all', GEDITORIAL_URL.'assets/css/front.all.css', array(), GEDITORIAL_VERSION );
	}

	public function enqueue_asset_config( $args = array(), $module = NULL )
	{
		$this->asset_config = TRUE;

		if ( count( $args ) ) {
			if ( is_null( $module ) )
				$this->asset_args = array_merge( $this->asset_args, $args );
			else
				$this->asset_args = array_merge( $this->asset_args, array( $module => $args ) );
		}

		return $this->asset_config;
	}

	// used in front & admin
	public function footer_asset_config()
	{
		if ( ! $this->asset_config )
			return;

		gEditorialHelper::printJSConfig( $this->asset_args );
	}

	public function register_editor_button( $button, $filepath )
	{
		$this->editor_buttons[$button] = GEDITORIAL_URL.$filepath;
	}
}

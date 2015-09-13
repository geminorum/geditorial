<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorial
{

	var $options_group      = 'geditorial_';
	var $options_group_name = 'geditorial_options';

	var $_asset_styles   = FALSE;
	var $_asset_config   = FALSE;
	var $_asset_args     = array();
	var $_editor_buttons = array();

	function __construct()
	{
		load_plugin_textdomain( GEDITORIAL_TEXTDOMAIN, FALSE, 'geditorial/languages' );

		$this->modules = new stdClass();

		add_action( 'plugins_loaded', function(){
			// allow dependent plugins and core actions to attach themselves in a safe way
			do_action( 'geditorial_loaded' );
		}, 20 );

		// load all of our modules. 'geditorial_loaded' happens after 'plugins_loaded' so other plugins can hook into the action we have at the end
		add_action( 'geditorial_loaded', array( &$this, 'geditorial_loaded' ) );

		// Load the module options later on, and offer a function to happen way after init
		add_action( 'init', function(){
			do_action( 'geditorial_init' );
		} );

		add_action( 'init'      , array( &$this, 'init_late'  ), 999 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'wp_footer' , array( &$this, 'footer_asset_config'  ), 999 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );
		add_filter( 'mce_external_languages', array( &$this, 'mce_external_languages' ) );
	}

	public function admin_init()
	{
		add_action( 'admin_print_styles', array( &$this, 'admin_print_styles' ) );
		add_action( 'admin_print_footer_scripts', array( &$this, 'footer_asset_config' ), 999 );
	}

	// include the common resources to Edit Flow and dynamically load the modules
	public function geditorial_loaded()
	{
		if ( is_admin() ) {
			require_once( GEDITORIAL_DIR.'includes/class-mustache.php' );
			gEditorialMustache::init();
		}

		require_once( GEDITORIAL_DIR.'includes/class-helper.php' );
		require_once( GEDITORIAL_DIR.'includes/class-template.php' );
		require_once( GEDITORIAL_DIR.'includes/class-module.php' );

		// if ( ! class_exists( 'WP_List_Table' ) )
		// 	require_once( ABSPATH.'wp-admin/includes/class-wp-list-table.php' );

		// scan the modules directory and include any modules that exist there
		$module_dirs = apply_filters( 'geditorial_modules', scandir( GEDITORIAL_DIR.'modules/' ) );
		$class_names = array();

		foreach ( $module_dirs as $module_dir ) {
			if ( file_exists( GEDITORIAL_DIR.'modules/'.$module_dir.'/'.$module_dir.'.php' ) ) {
				include_once( GEDITORIAL_DIR.'modules/'.$module_dir.'/'.$module_dir.'.php' );
				// Prepare the class name because it should be standardized
				$tmp = explode( '-', $module_dir );
				$class_name = '';
				$slug_name = '';
				foreach ( $tmp as $word ) {
					$class_name .= ucfirst( $word ).'';
					$slug_name .= $word.'';
				}
				$class_names[$slug_name] = 'gEditorial'.$class_name;
			}
		}

		foreach ( $class_names as $slug => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->$slug = new $class_name();
			}
		}

		do_action( 'geditorial_modules_loaded' );

		$this->load_module_options();

		foreach ( $this->modules as $mod_name => $mod_data ) {

			if ( gEditorialHelper::moduleEnabled( $mod_data->options ) ) {

				$this->{$mod_name}->enabled = TRUE;

				if ( method_exists( $this->{$mod_name}, 'setup' ) )
					$this->{$mod_name}->setup();
				else if ( method_exists( $this->{$mod_name}, 'init' ) )
					add_action( 'init', array( $this->{$mod_name}, 'init' ) );

			} else {
				$this->{$mod_name}->enabled = FALSE;
			}
		}
	}

	// register a new module with the pluign
	public function register_module( $name, $args = array() )
	{
		// A title and name is required for every module
		if ( ! isset( $args['title'], $name ) )
			return FALSE;

		$defaults = array(
			'title'                => '',
			'short_description'    => '',
			'extended_description' => '',
			'slug'                 => '',
			'img_url'              => FALSE,
			'dashicon'             => FALSE, // dashicon class

			'options'             => FALSE,
			'configure_page_cb'   => FALSE,
			'configure_link_text' => __( 'Configure', GEDITORIAL_TEXTDOMAIN ),
			'autoload'            => FALSE, // autoloading a module will remove the ability to enable or disable it
			'load_frontend'       => FALSE, // Whether or not the module should be loaded on the frontend too

			'default_options' => array(),
			'constants'       => array(),
			'strings'         => array(),
			'supports'        => array(),
		);

		$args = array_merge( $defaults, $args );

		$args['name']               = $name;
		$args['options_group_name'] = $this->options_group.$name.'_options';

		if ( ! isset( $args['settings_slug'] ) )
			$args['settings_slug'] = 'geditorial-settings-'.$args['slug'];

		$this->modules->$name = (object) $args;

		do_action( 'geditorial_module_registered', $name );

		return $this->modules->$name;
	}

	// Load all of the module options from the database
	// If a given option isn't yet set, then set it to the module's default (upgrades, etc.)
	public function load_module_options()
	{
		$options = get_option( 'geditorial_options' );

		foreach ( $this->modules as $mod_name => $mod_data ) {

			// don't load modules on the frontend unless they're explictly defined as such
			if ( ! is_admin() && ! $mod_data->load_frontend )
				continue;

			// make changes to default options before loading
			$mod_data->default_options = apply_filters(
				'geditorial_module_defaults_'.$mod_name,
				$mod_data->default_options,
				$mod_name,
				$mod_data
			);

			if ( ! isset( $options[$mod_name] ) )
				$options[$mod_name] = get_option( $this->options_group.$mod_name.'_options', FALSE ); // TODO: MUST DROP: in the next major version

			if ( FALSE !== $options[$mod_name] )
				$this->modules->{$mod_name}->options = $options[$mod_name];
			else
				$this->modules->{$mod_name}->options = new stdClass;

			foreach ( $mod_data->default_options as $default_key => $default_value )
				if ( ! isset( $this->modules->{$mod_name}->options->$default_key ) )
					$this->modules->{$mod_name}->options->$default_key = $default_value;

			if ( ! isset( $this->{$mod_name} ) )
				$this->{$mod_name} = new stdClass;

			$this->{$mod_name}->module = $this->modules->{$mod_name};
		}

		do_action( 'geditorial_module_options_loaded' );
	}

	public function init_late()
	{
		if ( 'true' == get_user_option( 'rich_editing' ) && count( $this->_editor_buttons ) ) {
			add_filter( 'mce_external_plugins', array( &$this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( &$this, 'mce_buttons' ) );
		}

		// Load the post type options again so we give add_post_type_support() a chance to work
		// @see http://dev.editflow.org/2011/11/17/geditorial-v0-7-alpha2-notes/#comment-232
		foreach ( $this->modules as $mod_name => $mod_data ) {

			if ( ! is_admin() && ! $mod_data->load_frontend )
				continue;

			if ( ! $this->{$mod_name}->enabled )
				continue;

			if ( isset( $this->modules->{$mod_name}->options->post_types ) ) {
				$this->modules->{$mod_name}->options->post_types = $this->{$mod_name}->sanitize_post_types( $this->modules->{$mod_name}->options->post_types );
				$this->{$mod_name}->module = $this->modules->{$mod_name};
			}
		}
	}

	public function mce_buttons( $buttons )
	{
		array_push( $buttons, '|' );

		foreach ( $this->_editor_buttons as $plugin => $filepath )
			array_push( $buttons, $plugin );

		return $buttons;
	}

	public function mce_external_plugins( $plugin_array )
	{
		foreach ( $this->_editor_buttons as $plugin => $filepath )
			$plugin_array[$plugin] = $filepath;

		return $plugin_array;
	}

	// get a module by one of its descriptive values
	public function get_module_by( $key, $value )
	{
		$module = FALSE;

		foreach ( $this->modules as $mod_name => $mod_data ) {

			if ( $key == 'name' && $value == $mod_name ) {
				$module = $this->modules->{$mod_name};
			} else {
				foreach ( $mod_data as $mod_data_key => $mod_data_value ) {
					if ( $mod_data_key == $key && $mod_data_value == $value )
						$module = $this->modules->{$mod_name};
				}
			}
		}

		return $module;
	}

	public function get_module_constant( $module, $key, $default = NULL )
	{
		if ( isset( $this->modules->{$module}->constants[$key] ) )
			return $this->modules->{$module}->constants[$key];

		return $default;
	}

	public function update_module_option( $mod_name, $key, $value )
	{
		$options = get_option( 'geditorial_options' );

		$this->modules->{$mod_name}->options->$key = $value;
		$this->{$mod_name}->module = $this->modules->{$mod_name};
		$options[$mod_name] = $this->modules->{$mod_name}->options;

		// return update_option( $this->options_group.$mod_name.'_options', $this->modules->{$mod_name}->options );
		return update_option( 'geditorial_options', $options, TRUE );
	}

	public function update_all_module_options( $mod_name, $new_options )
	{
		$options = get_option( 'geditorial_options' );

		if ( is_array( $new_options ) )
			$new_options = (object) $new_options;

		$options[$mod_name] = $new_options;
		return update_option( 'geditorial_options', $options, TRUE );

		// $this->modules->{$mod_name}->options = $new_options;
		// $this->{$mod_name}->module = $this->modules->{$mod_name};
		// return update_option( $this->options_group.$mod_name.'_options', $this->modules->{$mod_name}->options );
	}

	// global styles
	public function admin_print_styles()
	{
		$screen = get_current_screen();

		if ( 'post' == $screen->base )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.post.css' );
		else if ( 'edit' == $screen->base )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.edit.css' );
		else if ( 'widgets' == $screen->base )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.widgets.css' );
		else if ( gEditorialHelper::isSettings( $screen ) )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.settings.css' );
		else if ( gEditorialHelper::isTools( $screen ) )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.tools.css' );
		else {
			// gEditorialHelper::dump( $screen ); die();
		}
	}

	public function mce_external_languages( $languages )
	{
		$languages['geditorial'] = GEDITORIAL_DIR.'includes/mce-languages.php';
		return $languages;
	}

	public function enqueue_styles()
	{
		$this->_asset_styles = TRUE;
	}

	public function wp_enqueue_scripts()
	{
		if ( ! $this->_asset_styles )
			return;

		if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
			return;

		wp_enqueue_style( 'geditorial-front-all', GEDITORIAL_URL.'assets/css/front.all.css', array(), GEDITORIAL_VERSION );
	}

	public function enqueue_asset_config( $args = array(), $module = NULL )
	{
		$this->_asset_config = TRUE;

		if ( count( $args ) ) {
			if ( is_null( $module ) )
				$this->_asset_args = array_merge( $this->_asset_args, $args );
			else
				$this->_asset_args = array_merge( $this->_asset_args, array( $module => $args ) );
		}
	}

	// front & admin
	public function footer_asset_config()
	{
		if ( ! $this->_asset_config )
			return;

		gEditorialHelper::printJSConfig( $this->_asset_args );
	}

	public function register_editor_button( $button, $filepath )
	{
		$this->_editor_buttons[$button] = GEDITORIAL_URL.$filepath;
	}
}

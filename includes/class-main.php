<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorial
{

    var $options_group      = 'geditorial_';
	var $options_group_name = 'geditorial_options';

    var $_asset_styles      = false;
    var $_asset_config      = false;

	function __construct()
    {
        load_plugin_textdomain( GEDITORIAL_TEXTDOMAIN, false, 'geditorial/languages' );

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
        add_action( 'wp_footer' , array( &$this, 'wp_footer'  ), 999 );
        add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );
	}

    public function admin_init()
    {
        add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
    }

	// include the common resources to Edit Flow and dynamically load the modules
	public function geditorial_loaded()
    {
        if ( is_admin() ) {
            require_once( GEDITORIAL_DIR.'includes/class-mustache.php' );
            gEditorialMustache::init();
        }

        require_once( GEDITORIAL_DIR.'includes/class-helper.php' );
        require_once( GEDITORIAL_DIR.'includes/class-module.php' );

		if ( ! class_exists( 'WP_List_Table' ) )
			require_once( ABSPATH.'wp-admin/includes/class-wp-list-table.php' );

		// scan the modules directory and include any modules that exist there
		$module_dirs = apply_filters( 'geditorial_modules', scandir( GEDITORIAL_DIR.'modules/' ) );
		$class_names = array();

		foreach( $module_dirs as $module_dir ) {
			if ( file_exists( GEDITORIAL_DIR."modules/{$module_dir}/$module_dir.php" ) ) {
				include_once( GEDITORIAL_DIR."modules/{$module_dir}/$module_dir.php" );
				// Prepare the class name because it should be standardized
				$tmp = explode( '-', $module_dir );
				$class_name = '';
				$slug_name = '';
				foreach( $tmp as $word ) {
					$class_name .= ucfirst( $word ).'';
					$slug_name .= $word.'';
				}
                $class_names[$slug_name] = 'gEditorial'.$class_name;
			}
		}

		// DEPRECATED
		//$this->helpers = new gEditorialModuleCore();

		foreach( $class_names as $slug => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->$slug = new $class_name();
			}
		}

		// Supplementary plugins can hook into this, include their own modules and add them to the object
		do_action( 'geditorial_modules_loaded' );

		// Loads options for each registered module and then initializes it if it's active
		$this->load_module_options();

		// Load all of the modules that are enabled.
		// Modules won't have an options value if they aren't enabled
		foreach ( $this->modules as $mod_name => $mod_data ) {
			if ( isset( $mod_data->options->enabled ) && $mod_data->options->enabled == 'on' ) {
				$this->$mod_name->enabled = true;

				if ( method_exists( $this->$mod_name, 'setup' ) )
					$this->$mod_name->setup();
				else if ( method_exists( $this->$mod_name, 'init' ) )
					add_action( 'init', array( $this->$mod_name, 'init' ) );

			} else {
				$this->$mod_name->enabled = false;
			}
		}

	}

	// register a new module with the pluign
	public function register_module( $name, $args = array() )
    {
		// A title and name is required for every module
		if ( ! isset( $args['title'], $name ) )
			return false;

		$defaults = array(
			'title'                => '',
			'short_description'    => '',
			'extended_description' => '',
            'slug'                 => '',
            'img_url'              => false,
			'dashicon'             => false, // dashicon class

			//'post_type_support' => '',

			'options'                   => false,
			'configure_pageditorial_cb' => false,
			'configure_link_text'       => __( 'Configure', GEDITORIAL_TEXTDOMAIN ),
            'autoload'                  => false, // autoloading a module will remove the ability to enable or disable it
            'load_frontend'             => false, // Whether or not the module should be loaded on the frontend too

            'default_options' => array(),
			'constants'       => array(),
			'strings'         => array(),

			'messages' => array(
				'settings-updated'    => __( 'Settings updated.', GEDITORIAL_TEXTDOMAIN ),
				'form-error'          => __( 'Please correct your form errors below and try again.', GEDITORIAL_TEXTDOMAIN ),
				'nonce-failed'        => __( 'Cheatin&#8217; uh?', GEDITORIAL_TEXTDOMAIN ),
				'invalid-permissions' => __( 'You do not have necessary permissions to complete this action.', GEDITORIAL_TEXTDOMAIN ),
				'missing-post'        => __( 'Post does not exist', GEDITORIAL_TEXTDOMAIN ),
			),
		);

		if ( isset( $args['messages'] ) )
			$args['messages'] = array_merge( (array)$args['messages'], $defaults['messages'] );

		$args = array_merge( $defaults, $args );
		$args['name'] = $name;
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
		foreach ( $this->modules as $mod_name => $mod_data ) {

			// don't load modules on the frontend unless they're explictly defined as such
			if ( ! is_admin() && ! $mod_data->load_frontend )
				continue;

			// MINE / make changes to default options before loading
			$mod_data->default_options = apply_filters(
				'geditorial_module_defaults_'.$mod_name,
				$mod_data->default_options,
				$mod_name,
				$mod_data
			);

			$saved_options = get_option( $this->options_group.$mod_name.'_options' );
			if ( $saved_options )
				$this->modules->$mod_name->options = $saved_options;
			else
				$this->modules->$mod_name->options = new stdClass;

			foreach ( $mod_data->default_options as $default_key => $default_value )
				if ( ! isset( $this->modules->$mod_name->options->$default_key ) )
					$this->modules->$mod_name->options->$default_key = $default_value;

			/** must move to after init
			// so we don't get warnings all over
			if ( isset( $this->modules->$mod_name->options->post_types ) )
				$this->modules->$mod_name->options->post_types = $this->helpers->clean_post_type_options( $this->modules->$mod_name->options->post_types, $mod_data->post_type_support );
			**/

            if ( ! isset( $this->$mod_name ) )
                $this->$mod_name = new stdClass;

			$this->$mod_name->module = $this->modules->$mod_name;
		}

		do_action( 'geditorial_module_options_loaded' );
	}

	// Load the post type options again so we give add_post_type_support() a chance to work
	// @see http://dev.editflow.org/2011/11/17/geditorial-v0-7-alpha2-notes/#comment-232
	public function init_late()
    {
		foreach ( $this->modules as $mod_name => $mod_data ) {
			// don't load modules on the frontend unless they're explictly defined as such
			if ( ! is_admin() && ! $mod_data->load_frontend )
				continue;

			if ( ! $this->$mod_name->enabled )
				continue;

			if ( isset( $this->modules->$mod_name->options->post_types ) ) {

				// DEPRECATED
				//$this->modules->$mod_name->options->post_types = $this->helpers->clean_post_type_options( $this->modules->$mod_name->options->post_types, $mod_data->post_type_support );

				// MINE
				$this->modules->$mod_name->options->post_types = $this->$mod_name->sanitize_post_types( $this->modules->$mod_name->options->post_types );

				$this->$mod_name->module = $this->modules->$mod_name;

			}
		}
	}

    // get a module by one of its descriptive values
	public function get_module_by( $key, $value )
    {
		$module = false;

		foreach ( $this->modules as $mod_name => $mod_data ) {

			if ( $key == 'name' && $value == $mod_name ) {
				$module =  $this->modules->$mod_name;
			} else {
				foreach( $mod_data as $mod_data_key => $mod_data_value ) {
					if ( $mod_data_key == $key && $mod_data_value == $value )
						$module = $this->modules->$mod_name;
				}
			}
		}

		return $module;
	}

	// mine
	public function get_module_constant( $module, $key, $default = null )
    {
		if ( isset( $this->modules->{$module}->constants[$key] ) )
			return $this->modules->{$module}->constants[$key];

		return $default;
	}

	// update the object with new value and save to the database
	public function update_module_option( $mod_name, $key, $value )
	{
		$this->modules->$mod_name->options->$key = $value;
		$this->$mod_name->module = $this->modules->$mod_name;

		return update_option( $this->options_group.$mod_name.'_options', $this->modules->$mod_name->options );
	}

	public function update_all_module_options( $mod_name, $new_options )
	{
		if ( is_array( $new_options ) )
			$new_options = (object) $new_options;

		$this->modules->$mod_name->options = $new_options;
		$this->$mod_name->module = $this->modules->$mod_name;

		return update_option( $this->options_group.$mod_name.'_options', $this->modules->$mod_name->options );
	}

	// geditorial global styles
	// just for the sake of simplicity!
    public function admin_print_styles()
	{
		$screen = get_current_screen();

		if ( 'post' == $screen->base )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.post.css' );
		else if ( 'edit' == $screen->base )
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.edit.css' );
        else if ( gEditorialHelper::isSettings( $screen ) )
            gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.settings.css' );
        else if ( gEditorialHelper::isTools( $screen ) )
            gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.tools.css' );
        else {
            // gEditorialHelper::dump( $screen ); die();
        }
	}

    public function enqueue_styles()
    {
        $this->_asset_styles = true;
    }

    public function wp_enqueue_scripts()
    {
        if ( ! $this->_asset_styles )
            return;

        if ( defined( 'GEDITORIAL_DISABLE_FRONT_STYLES' ) && GEDITORIAL_DISABLE_FRONT_STYLES )
            return;

        wp_enqueue_style( 'geditorial-front-all', GEDITORIAL_URL.'assets/css/front.all.css', array(), GEDITORIAL_VERSION );
    }

    // see it working on like module
    // TODO: accept an array of vars to include via the gEditorial js object
    public function enqueue_asset_config( $vars = array() )
    {
        $this->_asset_config = true;
    }

    // must be better!
    public function wp_footer()
    {
        if ( ! $this->_asset_config )
            return;

        $endpoint = defined( 'GNETWORK_AJAX_ENDPOINT' ) && GNETWORK_AJAX_ENDPOINT ? GNETWORK_AJAX_ENDPOINT : admin_url( 'admin-ajax.php' );

        ?>
<script type="text/javascript">
/* <![CDATA[ */
    var gEditorial = {"api": "<?php echo esc_js($endpoint); ?>" };
/* ]]> */
</script>
<?php

    }
}

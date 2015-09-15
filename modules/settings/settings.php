<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSettings extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'settings';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'             => __( 'Editorial', GEDITORIAL_TEXTDOMAIN ),
			'short_description' => __( 'WordPress, Magazine Style.', GEDITORIAL_TEXTDOMAIN ),
			'slug'              => 'settings',
			'settings_slug'     => 'geditorial-settings',
			'configure_page_cb' => 'print_default_settings',
			'autoload'          => TRUE,

			'default_options' => array(
				'enabled' => TRUE,
			),
		);

		$this->module = $gEditorial->register_module( 'settings', $args );
	}

	public function setup()
	{
		if ( ! is_admin() )
			return;

		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'wp_ajax_geditorial_settings', array( &$this, 'ajax_settings' ) );
	}

	public function admin_menu()
	{
		global $gEditorial;

		$hook_settings = add_menu_page( $this->module->title,
			$this->module->title,
			'manage_options',
			$this->module->settings_slug,
			array( &$this, 'admin_settings_page' ),
			'dashicons-screenoptions'
		);

		$hook_tools = add_submenu_page( $this->module->settings_slug,
			__( 'gEditorial Tools', GEDITORIAL_TEXTDOMAIN ),
			_x( 'Tools', 'Admin Tools Menu Title', GEDITORIAL_TEXTDOMAIN ),
			'manage_options',
			'geditorial-tools',
			array( &$this, 'admin_tools_page' )
		);

		add_action( 'load-'.$hook_settings, array( &$this, 'admin_settings_load' ) );
		add_action( 'load-'.$hook_tools, array( &$this, 'admin_tools_load' ) );

		foreach ( $gEditorial->modules as $mod_name => $mod_data ) {

			if ( gEditorialHelper::moduleEnabled( $mod_data->options )
				&& $mod_data->configure_page_cb
				&& $mod_name != $this->module->name ) {

					$hook_module = add_submenu_page( $this->module->settings_slug,
						$mod_data->title,
						$mod_data->title,
						'manage_options',
						$mod_data->settings_slug,
						array( &$this, 'admin_settings_page' )
					);

					add_action( 'load-'.$hook_module, array( &$this, 'admin_settings_load' ) );
			}
		}
	}

	public function admin_tools_page()
	{
		$uri   = gEditorialHelper::toolsURL( FALSE );
		$sub   = isset( $_GET['sub'] ) ? trim( $_GET['sub'] ) : 'general';
		$count = isset( $_REQUEST['count'] ) ? $_REQUEST['count'] : 0;

		$subs = apply_filters( 'geditorial_tools_subs', array(
			'overview' => _x( 'Overview', 'gEditorial Tools', GEDITORIAL_TEXTDOMAIN ),
			'general'  => _x( 'General', 'gEditorial Tools', GEDITORIAL_TEXTDOMAIN ),
		) );

		if ( is_super_admin() )
			$subs['console'] = _x( 'Console', 'gEditorial Tools', GEDITORIAL_TEXTDOMAIN );

		$messages = apply_filters( 'geditorial_tools_messages', array(
			'emptied' => gEditorialHelper::notice( sprintf( __( '%s Meta rows Emptied!', GEDITORIAL_TEXTDOMAIN ), $count ), 'updated fade', FALSE ),
		), $sub );

		echo '<div class="wrap geditorial-admin-wrap geditorial-tools geditorial-tools-'.$sub.'">';

			printf( '<h1>%s</h1>', __( 'gEditorial Tools', GEDITORIAL_TEXTDOMAIN ) );

			gEditorialHelper::headerNav( $uri, $sub, $subs );

			if ( isset( $_GET['message'] ) ) {
				if ( isset( $messages[$_GET['message']] ) )
					echo $messages[$_GET['message']];
				else
					gEditorialHelper::notice( $_GET['message'] );
				$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message' ), $_SERVER['REQUEST_URI'] );
			}

			if ( file_exists( GEDITORIAL_DIR.'admin/admin.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'admin/admin.'.$sub.'.php' );
			else
				do_action( 'geditorial_tools_sub_'.$sub, $uri, $sub );

			$this->print_default_signature();

		echo '<div class="clear"></div></div>';
	}

	public function admin_tools_load()
	{
		global $gEditorial;

		$sub = isset( $_REQUEST['sub'] ) ? $_REQUEST['sub'] : 'general';

		if ( 'general' == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->tools_check_referer( $sub );

				$post = isset( $_POST[$this->module->options_group_name]['tools'] ) ? $_POST[$this->module->options_group_name]['tools'] : array();

				if ( isset( $_POST['custom_fields_empty'] ) ) {

					if ( isset( $post['empty_module'] ) && isset( $gEditorial->{$post['empty_module']}->meta_key ) ) {

						$result = gEditorialHelper::deleteEmptyMeta( $gEditorial->{$post['empty_module']}->meta_key );

						if ( count( $result ) ) {
							wp_redirect( add_query_arg( array(
								'message' => 'emptied',
								'count'   => count( $result ),
							), wp_get_referer() ) );
							exit();
						}
					}
				}
			}

			add_action( 'geditorial_tools_sub_general', array( &$this, 'tools_sub' ), 10, 2 );
		}

		do_action( 'geditorial_tools_settings', $sub );
	}

	public function tools_sub( $settings_uri, $sub )
	{
		global $gEditorial;

		$post = isset( $_POST[$this->module->options_group_name]['tools'] ) ? $_POST[$this->module->options_group_name]['tools'] : array();

		echo '<form method="post" action="">';

			$this->tools_field_referer( $sub );

			echo '<h3>'.__( 'General Tools', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'.__( 'Maintenance Tasks', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				$this->do_settings_field( array(
					'type'       => 'select',
					'field'      => 'empty_module',
					'values'     => $gEditorial->get_all_modules(),
					'default'    => ( isset( $post['empty_module'] ) ? $post['empty_module'] : 'meta' ),
					'name_group' => 'tools',
				) );

				echo '<p class="submit">';
					submit_button( __( 'Empty', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'custom_fields_empty', FALSE ); echo '&nbsp;&nbsp;';

					echo gEditorialHelper::html( 'span', array(
						'class' => 'description',
					), __( 'Will delete empty meta values, solves common problems with imported posts.', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}

	public function ajax_settings()
	{
		global $gEditorial;

		if ( ! current_user_can( 'manage_options' ) )
			wp_die( __( 'Cheatin&#8217; uh?' ) );

		$sub = isset( $_POST['sub'] ) ? trim( $_POST['sub'] ) : 'default';
		switch ( $sub ) {
			case 'module_state' :
				if ( ! wp_verify_nonce( $_POST['module_nonce'], 'geditorial-module-nonce' ) )
					wp_die( __( 'Cheatin&#8217; uh?' ) );

				if ( ! isset( $_POST['module_action'], $_POST['module_slug'] ) )
					wp_send_json_error( gEditorialHelper::notice( _x( 'No Action of Slug!', 'Ajax Notice', GEDITORIAL_TEXTDOMAIN ), 'error', FALSE ) );

				$module = $gEditorial->get_module_by( 'slug', sanitize_key( $_POST['module_slug'] ) );
				if ( ! $module )
					wp_send_json_error( gEditorialHelper::notice( _x( 'Cannot find the module!', 'Ajax Notice', GEDITORIAL_TEXTDOMAIN ), 'error', FALSE ) );

				$enabled = 'enable' == sanitize_key( $_POST['module_action'] ) ? TRUE : FALSE;
				if ( $gEditorial->update_module_option( $module->name, 'enabled', $enabled ) )
					wp_send_json_success( gEditorialHelper::notice( _x( 'Module state succesfully changed', 'Ajax Notice', GEDITORIAL_TEXTDOMAIN ), 'updated', FALSE ) );
				else
					wp_send_json_error( gEditorialHelper::notice( _x( 'Cannot change module state', 'Ajax Notice', GEDITORIAL_TEXTDOMAIN ), 'error', FALSE ) );

			break;

			default :
				wp_send_json_error( gEditorialHelper::notice( _x( 'Waht?!', 'Ajax Notice', GEDITORIAL_TEXTDOMAIN ), 'error', FALSE ) );
		}

		die();
	}

	public function admin_settings_page()
	{
		global $gEditorial;

		$requested_module = $gEditorial->get_module_by( 'settings_slug', $_GET['page'] );
		if ( ! $requested_module )
			wp_die( __( 'Not a registered Editorial module', GEDITORIAL_TEXTDOMAIN ) );

		$configure_callback = $requested_module->configure_page_cb;
		$requested_module_name = $requested_module->name;

		if ( gEditorialHelper::moduleEnabledBySlug( $requested_module_name ) ) {

			$this->print_default_header( $requested_module );
			$gEditorial->$requested_module_name->$configure_callback();
			$this->print_default_footer( $requested_module );
			$this->print_default_signature( $requested_module );

		} else {

			gEditorialHelper::notice( sprintf( __( 'Module not enabled. Please enable it from the <a href="%1$s">Editorial settings page</a>.', GEDITORIAL_TEXTDOMAIN ), gEditorialHelper::settingsURL() ) );

		}
	}

	public function print_default_header( $current_module )
	{
		global $gEditorial;

		if ( 'settings' == $current_module->name )
			$title = __( 'Editorial', GEDITORIAL_TEXTDOMAIN );
		else
			$title = sprintf( __( 'Editorial: %s', GEDITORIAL_TEXTDOMAIN ), $current_module->title )
				.'&nbsp;<a href="'.gEditorialHelper::settingsURL().'" class="page-title-action">'
				.__( 'Back to Editorial', GEDITORIAL_TEXTDOMAIN ).'</a>';

		echo '<div class="wrap geditorial-admin-wrap geditorial-settings"><h1>'.$title.'</h1>';

		if ( isset( $_REQUEST['message'] ) && isset( $current_module->messages[$_REQUEST['message']] ) )
			gEditorialHelper::notice( $current_module->messages[$_REQUEST['message']] );

		if ( isset( $_REQUEST['error'] ) && isset( $current_module->messages[$_REQUEST['error']] ) )
			gEditorialHelper::notice( $current_module->messages[$_REQUEST['error']], 'error' );

		echo '<div class="explanation">';

		if ( $current_module->short_description )
			echo '<h4>'.$current_module->short_description.'</h4>';

		if ( $current_module->extended_description )
			echo wpautop( $current_module->extended_description );

		if ( method_exists( $gEditorial->{$current_module->name}, 'extended_description_after' ) )
			$gEditorial->{$current_module->name}->extended_description_after();

		echo '<div class="clear"></div></div>';
	}

	private function print_default_settings()
	{
		?><div class="modules"><?php
			$this->print_modules();
		?></div> <?php
	}

	private function print_default_footer( $current_module )
	{
		if ( 'settings' == $current_module->slug ) {
			?><div class="credits">
				<p>You're using gEditorial v<?php echo GEDITORIAL_VERSION; ?>
				<br /><a href="https://github.com/geminorum/geditorial/issues">feedback, ideas, bug reports</a>
				<br />gEditorial is a fork in structure of <a href="http://editflow.org/">EditFlow</a>
				</p>
			</div> <?php
		}
	}

	private function print_default_signature( $current_module = NULL )
	{
		?><div class="signature"><p><?php
			printf( __( '<a href="%1$s" title="Editorial">gEditorial</a> is a <a href="%2$s">geminorum</a> project.'),
				'http://github.com/geminorum/geditorial',
				'http://geminorum.ir/' );
		?></p></div><div class="clear"></div></div> <?php
	}

	private function print_modules()
	{
		global $gEditorial;

		if ( count( $gEditorial->modules ) ) {

			foreach ( $gEditorial->modules as $mod_name => $mod_data ) {

				if ( $mod_data->autoload )
					continue;

				$enabled = gEditorialHelper::moduleEnabled( $mod_data->options );

				$classes = array(
					'module',
					( $enabled ? 'module-enabled' : 'module-disabled' ),
					( $mod_data->configure_page_cb ? 'has-configure-link' : 'no-configure-link' ),
				);

				echo '<div class="'.implode( ' ', $classes ).'" id="'.$mod_data->slug.'">';

				if ( $mod_data->dashicon )
					echo '<div class="dashicons dashicons-'.$mod_data->dashicon.'"></div>';
				else if ( $mod_data->img_url )
					echo '<img src="'.esc_url( $mod_data->img_url ).'" class="icon" />';

				echo '<form method="get" action="'.get_admin_url( NULL, 'options.php' ).'">';

					echo '<h3>'.esc_html( $mod_data->title ).'</h3>';
					echo '<p>'.esc_html( $mod_data->short_description ).'</p>';

					echo '<p class="actions">';

						if ( $mod_data->configure_page_cb ) {
							$configure_url = add_query_arg( 'page', $mod_data->settings_slug, get_admin_url( NULL, 'admin.php' ) );
							echo '<a href="'.$configure_url.'" class="button-configure button button-primary';
							if ( ! $enabled )
								echo ' hidden" style="display:none;';
							echo '">'.$mod_data->configure_link_text.'</a>';
						}

						echo '<input type="submit" class="button button-primary button-toggle"';
							if ( $enabled )
								echo ' style="display:none;"';
							echo ' value="'.__( 'Enable', GEDITORIAL_TEXTDOMAIN ).'" />';

						echo '<input type="submit" class="button button-secondary button-toggle button-remove"';
							if ( ! $enabled )
								echo ' style="display:none;"';
							echo ' value="'.__( 'Disable', GEDITORIAL_TEXTDOMAIN ).'" />';

					echo '</p>';

					wp_nonce_field( 'geditorial-module-nonce', 'module-nonce', FALSE );
				echo '</form></div>';
			}

			echo '<div class="clear"></div>';

		} else {

			gEditorialHelper::notice( __( 'There are no Editorial modules registered', GEDITORIAL_TEXTDOMAIN ), 'error' );

		}
	}

	public function admin_settings_load()
	{
		$page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : NULL;

		$this->admin_settings_reset( $page );
		$this->admin_settings_save( $page );
		do_action( 'geditorial_settings_load', $page );

		// DEPRECATED: use 'geditorial_settings_load'
		do_action( 'geditorial_settings_register_settings' );

		$this->enqueue_asset_js();
	}

	private function admin_settings_verify( $options_group_name )
	{
		if ( ! current_user_can( 'manage_options' ) )
			return FALSE;

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $options_group_name.'-options' ) )
			return FALSE;

		return TRUE;
	}

	public function admin_settings_reset( $page = NULL )
	{
		if ( ! isset( $_POST['reset-settings'], $_POST['geditorial_module_name'] ) )
			return;

		global $gEditorial;
		$module_name = sanitize_key( $_POST['geditorial_module_name'] );

		if ( ! $this->admin_settings_verify( $gEditorial->$module_name->module->options_group_name ) )
			wp_die( __( 'Cheatin&#8217; uh?' ) );

		$gEditorial->update_all_module_options( $gEditorial->$module_name->module->name, array(
			'enabled' => TRUE,
		) );

		wp_redirect( add_query_arg( 'message', 'settings-reset', remove_query_arg( array( 'message' ), wp_get_referer() ) ) );
		exit;
	}

	public function admin_settings_save( $page = NULL )
	{
		if ( ! isset(
			$_POST['_wpnonce'],
			$_POST['_wp_http_referer'],
			$_POST['action'],
			$_POST['option_page'],
			$_POST['geditorial_module_name'],
			$_POST['submit']
		) )
			return FALSE;

		global $gEditorial;

		$module_name = sanitize_key( $_POST['geditorial_module_name'] );

		if ( $_POST['action'] != 'update'
			|| $_POST['option_page'] != $gEditorial->$module_name->module->options_group_name )
			return FALSE;

		//if ( ! current_user_can( 'manage_options' ) || !wp_verify_nonce( $_POST['_wpnonce'], $gEditorial->$module_name->module->options_group_name.'-options' ) )
		if ( ! $this->admin_settings_verify( $gEditorial->$module_name->module->options_group_name ) )
			wp_die( __( 'Cheatin&#8217; uh?' ) );

		$new_options = ( isset( $_POST[$gEditorial->$module_name->module->options_group_name] ) ) ? $_POST[$gEditorial->$module_name->module->options_group_name] : array();

		// Only call the validation callback if it exists?
		if ( method_exists( $gEditorial->$module_name, 'settings_validate' ) )
			$new_options = $gEditorial->$module_name->settings_validate( $new_options );

		// Cast our object and save the data.
		$new_options = (object) array_merge( (array) $gEditorial->$module_name->module->options, $new_options );
		$gEditorial->update_all_module_options( $gEditorial->$module_name->module->name, $new_options );

		// Redirect back to the settings page that was submitted without any previous messages
		$goback = add_query_arg( 'message', 'settings-updated',  remove_query_arg( array( 'message' ), wp_get_referer() ) );
		wp_redirect( $goback );
		exit;
	}
}

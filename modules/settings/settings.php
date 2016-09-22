<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSettings extends gEditorialModuleCore
{

	public static function module()
	{
		return array(
			'name'      => 'settings',
			'title'     => _x( 'Editorial', 'Settings Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'WordPress, Magazine Style.', 'Settings Module', GEDITORIAL_TEXTDOMAIN ),
			'settings'  => 'geditorial-settings',
			'configure' => 'print_default_settings',
			'autoload'  => TRUE,
		);
	}

	public function setup( $partials = array() )
	{
		if ( ! is_admin() )
			return;

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_geditorial_settings', array( $this, 'ajax' ) );
	}

	public function admin_menu()
	{
		global $gEditorial;

		$hook_settings = add_menu_page(
			$this->module->title,
			$this->module->title,
			'manage_options',
			$this->module->settings,
			array( $this, 'admin_settings_page' ),
			'dashicons-screenoptions'
		);

		$hook_tools = add_submenu_page(
			$this->module->settings,
			_x( 'gEditorial Tools', 'Settings Module', GEDITORIAL_TEXTDOMAIN ),
			_x( 'Tools', 'Settings Module: Admin Tools Menu Title', GEDITORIAL_TEXTDOMAIN ),
			'manage_options',
			'geditorial-tools',
			array( $this, 'admin_tools_page' )
		);

		add_action( 'load-'.$hook_settings, array( $this, 'admin_settings_load' ) );
		add_action( 'load-'.$hook_tools, array( $this, 'admin_tools_load' ) );

		foreach ( $gEditorial->modules as $name => &$module ) {

			if ( isset( $gEditorial->{$name} )
				&& $module->configure
				&& $name != $this->module->name ) {

					if ( $hook_module = add_submenu_page( $this->module->settings,
						$module->title,
						$module->title,
						'manage_options',
						$module->settings,
						array( $this, 'admin_settings_page' )
					) ) add_action( 'load-'.$hook_module, array( $this, 'admin_settings_load' ) );
			}
		}
	}

	public function admin_tools_page()
	{
		$uri = gEditorialHelper::toolsURL( FALSE );
		$sub = gEditorialSettingsCore::sub();

		$subs = apply_filters( 'geditorial_tools_subs', array(
			'overview' => _x( 'Overview', 'Settings Module: Tools Sub', GEDITORIAL_TEXTDOMAIN ),
			'general'  => _x( 'General', 'Settings Module: Tools Sub', GEDITORIAL_TEXTDOMAIN ),
		), 'tools' );

		if ( is_super_admin() )
			$subs['console'] = _x( 'Console', 'Settings Module: Tools Sub', GEDITORIAL_TEXTDOMAIN );

		$messages = apply_filters( 'geditorial_tools_messages', gEditorialSettingsCore::messages(), $sub );

		echo '<div class="wrap geditorial-admin-wrap geditorial-tools geditorial-tools-'.$sub.'">';

			gEditorialSettingsCore::headerTitle( _x( 'gEditorial Tools', 'Settings Module: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			gEditorialSettingsCore::headerNav( $uri, $sub, $subs );
			gEditorialSettingsCore::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' );
			else
				do_action( 'geditorial_tools_sub_'.$sub, $uri, $sub );

			$this->settings_signature( NULL, 'tools' );

		echo '<div class="clear"></div></div>';
	}

	public function admin_tools_load()
	{
		global $gEditorial, $wpdb;

		$sub = gEditorialSettingsCore::sub();

		if ( 'general' == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->tools_check_referer( $sub );

				$post = isset( $_POST[$this->module->group]['tools'] ) ? $_POST[$this->module->group]['tools'] : array();

				if ( isset( $_POST['upgrade_old_options'] ) ) {

					$result = $gEditorial->upgrade_old_options();

					if ( count( $result ) )
						self::redirect( add_query_arg( array(
							'message' => 'upgraded',
							'count'   => count( $result ),
						), wp_get_referer() ) );

				} else if ( isset( $_POST['delete_all_options'] ) ) {

					if ( delete_option( 'geditorial_options' ) )
						self::redirect( add_query_arg( array(
							'message' => 'deleted',
						), wp_get_referer() ) );

				} else if ( isset( $_POST['custom_fields_empty'] ) ) {

					if ( isset( $post['empty_module'] ) && isset( $gEditorial->{$post['empty_module']}->meta_key ) ) {

						$result = self::deleteEmptyMeta( $gEditorial->{$post['empty_module']}->meta_key );

						if ( count( $result ) )
							self::redirect( add_query_arg( array(
								'message' => 'emptied',
								'count'   => count( $result ),
							), wp_get_referer() ) );
					}

				} else if ( isset( $_POST['orphaned_terms'] ) ) {

					if ( ! empty( $post['dead_tax'] )
						&& ! empty( $post['live_tax'] ) ) {

						$result = $wpdb->query( $wpdb->prepare( "
							UPDATE $wpdb->term_taxonomy SET taxonomy = %s WHERE taxonomy = '%s'
						", trim( $post['live_tax'] ), trim( $post['dead_tax'] ) ) );

						if ( count( $result ) )
							self::redirect( add_query_arg( array(
								'message' => 'converted',
								'count'   => count( $result ),
							), wp_get_referer() ) );
					}
				}
			}

			add_action( 'geditorial_tools_sub_general', array( $this, 'tools_sub' ), 10, 2 );
		}

		do_action( 'geditorial_tools_settings', $sub );
	}

	public function tools_sub( $settings_uri, $sub )
	{
		global $gEditorial;

		$post = isset( $_POST[$this->module->group]['tools'] ) ? $_POST[$this->module->group]['tools'] : array();

		echo '<form method="post" action="">';

			$this->tools_field_referer( $sub );

			echo '<h3>'._x( 'Maintenance Tasks', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			// TODO: tool for installing default terms for each module

			echo '<tr><th scope="row">'._x( 'Options', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				echo '<p>';
					submit_button( _x( 'Upgrade Old Options', 'Settings Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'upgrade_old_options', FALSE ); echo '&nbsp;&nbsp;';

					echo self::html( 'span', array(
						'class' => 'description',
					), _x( 'Will check for old options and upgrade, also delete old options', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

				if ( self::isDev() || is_super_admin() ) {
					echo '<br /><p>';
						submit_button( _x( 'Delete All Options', 'Settings Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'delete_all_options', FALSE ); echo '&nbsp;&nbsp;';

						echo self::html( 'span', array(
							'class' => 'description',
						), _x( 'Deletes all editorial options on current site', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
					echo '</p>';
				}

			echo '</td></tr>';
			echo '<tr><th scope="row">'._x( 'Empty Meta Fields', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				$this->do_settings_field( array(
					'type'       => 'select',
					'field'      => 'empty_module',
					'values'     => $gEditorial->get_all_modules(),
					'default'    => ( isset( $post['empty_module'] ) ? $post['empty_module'] : 'meta' ),
					'name_group' => 'tools',
				) );

				echo '<p class="submit">';
					submit_button( _x( 'Empty', 'Settings Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'custom_fields_empty', FALSE ); echo '&nbsp;&nbsp;';

					echo self::html( 'span', array(
						'class' => 'description',
					), _x( 'Will delete empty meta values, solves common problems with imported posts.', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

			echo '</td></tr>';
			echo '<tr><th scope="row">'._x( 'Orphaned Terms', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				$all_tax  = gEditorialHelper::getDBTermTaxonomies( TRUE );
				$live_tax = gEditorialHelper::getTaxonomies( 'name' );

				$this->do_settings_field( array(
					'type'       => 'select',
					'field'      => 'dead_tax',
					'values'     => array_diff_key( $all_tax, $live_tax ),
					'default'    => ( isset( $post['dead_tax'] ) ? $post['dead_tax'] : 'post_tag' ),
					'name_group' => 'tools',
				) );

				$this->do_settings_field( array(
					'type'       => 'select',
					'field'      => 'live_tax',
					'values'     => $live_tax,
					'default'    => ( isset( $post['live_tax'] ) ? $post['live_tax'] : 'post_tag' ),
					'name_group' => 'tools',
				) );

				echo '<p class="submit">';
					submit_button( _x( 'Convert', 'Settings Module', GEDITORIAL_TEXTDOMAIN ), 'secondary', 'orphaned_terms', FALSE ); echo '&nbsp;&nbsp;';

					echo self::html( 'span', array(
						'class' => 'description',
					), _x( 'Converts orphaned terms into currently registered taxonomies', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}

	public function ajax()
	{
		global $gEditorial;

		if ( ! current_user_can( 'manage_options' ) )
			self::cheatin();

		$sub = isset( $_POST['sub'] ) ? trim( $_POST['sub'] ) : 'default';

		switch ( $sub ) {

			case 'module_state' :

				if ( ! wp_verify_nonce( $_POST['module_nonce'], 'geditorial-module-nonce' ) )
					self::cheatin();

				if ( ! isset( $_POST['module_action'], $_POST['module_slug'] ) )
					wp_send_json_error( self::error( _x( 'No Action of Slug!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );

				$module = $gEditorial->get_module_by( 'settings', sanitize_key( $_POST['module_slug'] ) );

				if ( ! $module )
					wp_send_json_error( self::error( _x( 'Cannot find the module!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );

				$enabled = 'enable' == sanitize_key( $_POST['module_action'] ) ? TRUE : FALSE;

				if ( $gEditorial->update_module_option( $module->name, 'enabled', $enabled ) )
					wp_send_json_success( self::success( _x( 'Module state succesfully changed', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );
				else
					wp_send_json_error( self::error( _x( 'Cannot change module state', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );

			break;

			default :
				wp_send_json_error( self::error( _x( 'What?!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );
		}

		die();
	}

	public function admin_settings_page()
	{
		global $gEditorial;

		if ( ! $module = $gEditorial->get_module_by( 'settings', $_GET['page'] ) )
			wp_die( _x( 'Not a registered Editorial module', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );

		if ( isset( $gEditorial->{$module->name} ) ) {

			$this->print_default_header( $module );
			$gEditorial->{$module->name}->{$module->configure}();
			$this->settings_footer( $module );
			$this->settings_signature( $module );

		} else {

			self::notice( sprintf(
				_x( 'Module not enabled. Please enable it from the <a href="%1$s">Editorial settings page</a>.', 'Settings Module', GEDITORIAL_TEXTDOMAIN ),
				gEditorialHelper::settingsURL()
			) );
		}
	}

	public function print_default_header( $current_module )
	{
		global $gEditorial;

		if ( 'settings' == $current_module->name ) {
			$title = _x( 'Editorial', 'Settings Module', GEDITORIAL_TEXTDOMAIN );
			$back  = FALSE;
		} else {
			$title = sprintf( _x( 'Editorial: %s', 'Settings Module', GEDITORIAL_TEXTDOMAIN ), $current_module->title );
			$back  = gEditorialHelper::settingsURL();
		}

		echo '<div class="wrap geditorial-admin-wrap geditorial-settings">';

			gEditorialSettingsCore::headerTitle( $title, $back );

		if ( isset( $_REQUEST['message'] ) && isset( $current_module->messages[$_REQUEST['message']] ) )
			self::notice( $current_module->messages[$_REQUEST['message']] );

		if ( isset( $_REQUEST['error'] ) && isset( $current_module->messages[$_REQUEST['error']] ) )
			self::notice( $current_module->messages[$_REQUEST['error']], 'error' );

		echo '<div class="explanation">';

		if ( isset( $current_module->desc ) && $current_module->desc )
			echo '<h4>'.$current_module->desc.'</h4>';

		if ( isset( $current_module->intro ) && $current_module->intro )
			echo wpautop( $current_module->intro );

		if ( method_exists( $gEditorial->{$current_module->name}, 'intro_after' ) )
			$gEditorial->{$current_module->name}->intro_after();

		echo '<div class="clear"></div></div>';
	}

	private function print_default_settings()
	{
		echo '<div class="modules">';
			$this->print_modules();
		echo '</div>';
	}

	private function print_modules()
	{
		global $gEditorial;

		if ( count( $gEditorial->modules ) ) {

			foreach ( $gEditorial->modules as $name => &$module ) {

				if ( $module->autoload )
					continue;

				$classes = array(
					'module',
					( isset( $gEditorial->{$name} ) ? 'module-enabled' : 'module-disabled' ),
					( $module->configure ? 'has-configure-link' : 'no-configure-link' ),
				);

				echo '<div class="'.implode( ' ', $classes ).'" id="'.$module->settings.'">';

				if ( $module->dashicon )
					echo '<div class="dashicons dashicons-'.$module->dashicon.'"></div>';

				echo '<form method="get" action="'.get_admin_url( NULL, 'options.php' ).'">';

					echo self::html( 'h3', $module->title );
					echo self::html( 'p', $module->desc );

					echo '<p class="actions">';

						if ( $module->configure ) {
							$configure_url = add_query_arg( 'page', $module->settings, get_admin_url( NULL, 'admin.php' ) );
							echo '<a href="'.$configure_url.'" class="button-configure button button-primary';
							if ( ! isset( $gEditorial->{$name} ) )
								echo ' hidden" style="display:none;';
							echo '">'._x( 'Configure', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</a>';
						}

						echo '<input type="submit" class="button button-primary button-toggle"';
							if ( isset( $gEditorial->{$name} ) )
								echo ' style="display:none;"';
							echo ' value="'._x( 'Enable', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'" />';

						echo '<input type="submit" class="button button-secondary button-toggle button-remove"';
							if ( ! isset( $gEditorial->{$name} ) )
								echo ' style="display:none;"';
							echo ' value="'._x( 'Disable', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'" />';

					echo '</p>';

					wp_nonce_field( 'geditorial-module-nonce', 'module-nonce', FALSE );
				echo '</form></div>';
			}

			echo '<div class="clear"></div>';

		} else {

			self::notice( _x( 'There are no editorial modules registered', 'Settings Module', GEDITORIAL_TEXTDOMAIN ), 'error' );
		}
	}

	public function admin_settings_load()
	{
		$page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : NULL;

		$this->admin_settings_reset( $page );
		$this->admin_settings_save( $page );

		$screen = get_current_screen();

		foreach ( gEditorialSettingsCore::settingsHelpContent() as $tab )
			$screen->add_help_tab( $tab );

		do_action( 'geditorial_settings_load', $page );

		$this->enqueue_asset_js();  // FIXME: the js not using the internal api!
	}

	private function admin_settings_verify( $group )
	{
		if ( ! current_user_can( 'manage_options' ) )
			return FALSE;

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $group.'-options' ) )
			return FALSE;

		return TRUE;
	}

	public function admin_settings_reset( $page = NULL )
	{
		if ( ! isset( $_POST['reset-settings'], $_POST['geditorial_module_name'] ) )
			return;

		global $gEditorial;
		$name = sanitize_key( $_POST['geditorial_module_name'] );

		if ( ! $this->admin_settings_verify( $gEditorial->{$name}->module->group ) )
			self::cheatin();

		$gEditorial->update_all_module_options( $gEditorial->{$name}->module->name, array(
			'enabled' => TRUE,
		) );

		// self::redirect( add_query_arg( 'message', 'settings-reset', remove_query_arg( array( 'message' ), wp_get_referer() ) ) );
		self::redirect( add_query_arg( 'message', 'settings-reset', wp_get_referer() ) );
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

		$name  = sanitize_key( $_POST['geditorial_module_name'] );
		$group = $gEditorial->{$name}->module->group;

		if ( $_POST['action'] != 'update'
			|| $_POST['option_page'] != $group )
				return FALSE;

		// if ( ! current_user_can( 'manage_options' ) || !wp_verify_nonce( $_POST['_wpnonce'], $group.'-options' ) )
		if ( ! $this->admin_settings_verify( $group ) )
			self::cheatin();

		$options = $gEditorial->{$name}->settings_validate( ( isset( $_POST[$group] ) ? $_POST[$group] : array() ) );

		// cast our object and save the data.
		$options = (object) array_merge( (array) $gEditorial->{$name}->options, $options );
		$gEditorial->update_all_module_options( $gEditorial->{$name}->module->name, $options );

		// redirect back to the settings page that was submitted without any previous messages
		self::redirect( add_query_arg( 'message', 'settings-updated', remove_query_arg( array( 'message' ), wp_get_referer() ) ) );
	}
}

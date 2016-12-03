<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialSettings extends gEditorialModuleCore
{

	protected $caps = array(
		'reports'  => 'edit_others_posts',
		'settings' => 'manage_options',
		'tools'    => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'      => 'settings',
			'title'     => _x( 'Editorial', 'Settings Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'WordPress, Magazine Style.', 'Settings Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'screenoptions',
			'settings'  => 'geditorial-settings',
			'configure' => 'print_default_settings',
			'frontend'  => FALSE,
			'autoload'  => TRUE,
		);
	}

	public function setup( $partials = array() )
	{
		if ( is_admin() )
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function setup_ajax( $request )
	{
		add_action( 'wp_ajax_geditorial_settings', array( $this, 'ajax' ) );
	}

	public function admin_menu()
	{
		global $gEditorial;

		$can  = $this->cuc( 'settings' );
		$page = 'index.php';

		$hook_reports = add_submenu_page(
			$page,
			_x( 'Editorial Reports', 'Settings Module: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'Editorial Reports', 'Settings Module: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			$this->caps['reports'],
			'geditorial-reports',
			array( $this, 'admin_reports_page' )
		);

		$hook_settings = add_menu_page(
			$this->module->title,
			$this->module->title,
			$this->caps['settings'],
			$this->module->settings,
			array( $this, 'admin_settings_page' ),
			'dashicons-'.$this->module->icon
		);

		$hook_tools = add_submenu_page(
			( $can ? $this->module->settings : $page ),
			_x( 'Editorial Tools', 'Settings Module: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			( $can
				? _x( 'Tools', 'Settings Module: Menu Title', GEDITORIAL_TEXTDOMAIN )
				: _x( 'Editorial Tools', 'Settings Module: Menu Title', GEDITORIAL_TEXTDOMAIN )
			),
			$this->caps['tools'],
			'geditorial-tools',
			array( $this, 'admin_tools_page' )
		);

		add_action( 'load-'.$hook_reports, array( $this, 'admin_reports_load' ) );
		add_action( 'load-'.$hook_settings, array( $this, 'admin_settings_load' ) );
		add_action( 'load-'.$hook_tools, array( $this, 'admin_tools_load' ) );

		foreach ( $gEditorial->modules as $name => &$module ) {

			if ( isset( $gEditorial->{$name} )
				&& $module->configure
				&& $name != $this->module->name ) {

					if ( $hook_module = add_submenu_page( $this->module->settings,
						$module->title,
						$module->title,
						$this->caps['settings'], // FIXME: get from module
						$module->settings,
						array( $this, 'admin_settings_page' )
					) ) add_action( 'load-'.$hook_module, array( $this, 'admin_settings_load' ) );
			}
		}
	}

	public function admin_reports_page()
	{
		$can = $this->cuc( 'reports' );
		$uri = gEditorialSettingsCore::reportsURL( FALSE, ! $can );
		$sub = gEditorialSettingsCore::sub();

		$subs = array( 'overview' => _x( 'Overview', 'Settings Module: Reports Sub', GEDITORIAL_TEXTDOMAIN ) );

		if ( $can )
			$subs['general'] = _x( 'General', 'Settings Module: Reports Sub', GEDITORIAL_TEXTDOMAIN );

		$subs = apply_filters( 'geditorial_reports_subs', $subs, 'reports' );

		if ( is_super_admin() )
			$subs['console'] = _x( 'Console', 'Settings Module: Reports Sub', GEDITORIAL_TEXTDOMAIN );

		$messages = apply_filters( 'geditorial_reports_messages', gEditorialSettingsCore::messages(), $sub );

		echo '<div class="wrap geditorial-admin-wrap geditorial-reports geditorial-reports-'.$sub.'">';

			gEditorialSettingsCore::headerTitle( _x( 'Editorial Reports', 'Settings Module: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			gEditorialSettingsCore::headerNav( $uri, $sub, $subs );
			gEditorialSettingsCore::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/reports.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/reports.'.$sub.'.php' );
			else
				do_action( 'geditorial_reports_sub_'.$sub, $uri, $sub );

			$this->settings_signature( NULL, 'reports' );

		echo '<div class="clear"></div></div>';
	}

	public function admin_tools_page()
	{
		$can = $this->cuc( 'settings' );
		$uri = gEditorialSettingsCore::toolsURL( FALSE, ! $can );
		$sub = gEditorialSettingsCore::sub( ( $can ? 'general' : 'overview' ) );

		$subs = array( 'overview' => _x( 'Overview', 'Settings Module: Tools Sub', GEDITORIAL_TEXTDOMAIN ) );

		if ( $can )
			$subs['general'] = _x( 'General', 'Settings Module: Tools Sub', GEDITORIAL_TEXTDOMAIN );

		$subs = apply_filters( 'geditorial_tools_subs', $subs, 'tools' );

		if ( is_super_admin() )
			$subs['console'] = _x( 'Console', 'Settings Module: Tools Sub', GEDITORIAL_TEXTDOMAIN );

		$messages = apply_filters( 'geditorial_tools_messages', gEditorialSettingsCore::messages(), $sub );

		echo '<div class="wrap geditorial-admin-wrap geditorial-tools geditorial-tools-'.$sub.'">';

			gEditorialSettingsCore::headerTitle( _x( 'Editorial Tools', 'Settings Module: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			gEditorialSettingsCore::headerNav( $uri, $sub, $subs );
			gEditorialSettingsCore::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' );
			else
				do_action( 'geditorial_tools_sub_'.$sub, $uri, $sub );

			$this->settings_signature( NULL, 'tools' );

		echo '<div class="clear"></div></div>';
	}

	public function admin_reports_load()
	{
		global $gEditorial, $wpdb;

		$sub = gEditorialSettingsCore::sub();

		if ( 'general' == $sub ) {
			add_action( 'geditorial_reports_sub_general', array( $this, 'reports_sub' ), 10, 2 );
		}

		do_action( 'geditorial_reports_settings', $sub );
	}

	public function admin_tools_load()
	{
		global $gEditorial, $wpdb;

		$sub = gEditorialSettingsCore::sub();

		if ( 'general' == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'tools' );

				$post = isset( $_POST[$this->module->group]['tools'] ) ? $_POST[$this->module->group]['tools'] : array();

				if ( isset( $_POST['upgrade_old_options'] ) ) {

					$result = $gEditorial->upgrade_old_options();

					if ( count( $result ) )
						gEditorialWordPress::redirectReferer( array(
							'message' => 'upgraded',
							'count'   => count( $result ),
						) );

				} else if ( isset( $_POST['delete_all_options'] ) ) {

					if ( delete_option( 'geditorial_options' ) )
						gEditorialWordPress::redirectReferer( 'purged' );

				} else if ( isset( $_POST['custom_fields_empty'] ) ) {

					if ( isset( $post['empty_module'] ) && isset( $gEditorial->{$post['empty_module']}->meta_key ) ) {

						$result = self::deleteEmptyMeta( $gEditorial->{$post['empty_module']}->meta_key );

						if ( count( $result ) )
							gEditorialWordPress::redirectReferer( array(
								'message' => 'emptied',
								'count'   => count( $result ),
							) );
					}

				} else if ( isset( $_POST['orphaned_terms'] ) ) {

					if ( ! empty( $post['dead_tax'] )
						&& ! empty( $post['live_tax'] ) ) {

						$result = $wpdb->query( $wpdb->prepare( "
							UPDATE $wpdb->term_taxonomy SET taxonomy = %s WHERE taxonomy = '%s'
						", trim( $post['live_tax'] ), trim( $post['dead_tax'] ) ) );

						if ( count( $result ) )
							gEditorialWordPress::redirectReferer( array(
								'message' => 'changed',
								'count'   => count( $result ),
							) );
					}
				}
			}

			add_action( 'geditorial_tools_sub_general', array( $this, 'tools_sub' ), 10, 2 );
		}

		do_action( 'geditorial_tools_settings', $sub );
	}

	public function reports_sub( $settings_uri, $sub )
	{
		if ( 'general' != $sub )
			return;

		if ( ! $this->cuc( 'reports' ) )
			self::cheatin();

		echo 'Comming Soon!';
	}

	public function tools_sub( $settings_uri, $sub )
	{
		if ( 'general' == $sub )
			return $this->tools_sub_general( $settings_uri, $sub );

		// TODO: sub for installing default terms for each module
		// @SEE: https://make.wordpress.org/core/?p=20650
		// if ( 'defaults' == $sub )
		// 	return $this->tools_sub_defaults( $settings_uri, $sub );
	}

	private function tools_sub_general( $settings_uri, $sub )
	{
		global $gEditorial;

		if ( ! $this->cuc( 'settings' ) )
			self::cheatin();

		$post = isset( $_POST[$this->module->group]['tools'] ) ? $_POST[$this->module->group]['tools'] : array();

		echo '<form class="settings-form" method="post" action="">';

			$this->settings_field_referer( $sub, 'tools' );

			echo '<h3>'._x( 'Maintenance Tasks', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'Options', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				echo '<p>';
					$this->submit_button( 'upgrade_old_options', FALSE, _x( 'Upgrade Old Options', 'Settings Module: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

					echo gEditorialHTML::tag( 'span', array(
						'class' => 'description',
					), _x( 'Will check for old options and upgrade, also delete old options', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

				if ( self::isDev() || is_super_admin() ) {
					echo '<br /><p>';
						$this->submit_button( 'delete_all_options', FALSE, _x( 'Delete All Options', 'Settings Module: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

						echo gEditorialHTML::tag( 'span', array(
							'class' => 'description',
						), _x( 'Deletes all editorial options on current site', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
					echo '</p>';
				}

			echo '</td></tr>';
			echo '<tr><th scope="row">'._x( 'Empty Meta Fields', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				$this->do_settings_field( array(
					'type'         => 'select',
					'field'        => 'empty_module',
					'values'       => $gEditorial->get_all_modules(),
					'default'      => ( isset( $post['empty_module'] ) ? $post['empty_module'] : 'meta' ),
					'option_group' => 'tools',
				) );

				echo '<p class="submit">';
					$this->submit_button( 'custom_fields_empty', FALSE, _x( 'Empty', 'Settings Module: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

					echo gEditorialHTML::tag( 'span', array(
						'class' => 'description',
					), _x( 'Will delete empty meta values, solves common problems with imported posts.', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
				echo '</p>';

			echo '</td></tr>';

			$db_taxes   = gEditorialWPTaxonomy::getDBTaxonomies( TRUE );
			$live_taxes = gEditorialHelper::getTaxonomies( 'name' );
			$dead_taxes = array_diff_key( $db_taxes, $live_taxes );

			if ( count( $dead_taxes ) ) {

				echo '<tr><th scope="row">'._x( 'Orphaned Terms', 'Settings Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

					$this->do_settings_field( array(
						'type'         => 'select',
						'field'        => 'dead_tax',
						'values'       => $dead_taxes,
						'default'      => ( isset( $post['dead_tax'] ) ? $post['dead_tax'] : 'post_tag' ),
						'option_group' => 'tools',
					) );

					$this->do_settings_field( array(
						'type'         => 'select',
						'field'        => 'live_tax',
						'values'       => $live_taxes,
						'default'      => ( isset( $post['live_tax'] ) ? $post['live_tax'] : 'post_tag' ),
						'option_group' => 'tools',
					) );

					echo '<p class="submit">';
						$this->submit_button( 'orphaned_terms', FALSE, _x( 'Convert', 'Settings Module: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

						echo gEditorialHTML::tag( 'span', array(
							'class' => 'description',
						), _x( 'Converts orphaned terms into currently registered taxonomies', 'Settings Module', GEDITORIAL_TEXTDOMAIN ) );
					echo '</p>';

				echo '</td></tr>';
			}

			echo '</table>';
		echo '</form>';
	}

	public function ajax()
	{
		global $gEditorial;

		if ( ! $this->cuc( 'settings' ) )
			self::cheatin();

		$post = wp_unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'state' :

				gEditorialHelper::checkAjaxReferer();

				if ( ! isset( $_POST['doing'], $_POST['name'] ) )
					wp_send_json_error( gEditorialHTML::error( _x( 'No action or name!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );

				if ( ! $module = $gEditorial->get_module_by( 'name', sanitize_key( $_POST['name'] ) ) )
					wp_send_json_error( gEditorialHTML::error( _x( 'Cannot find the module!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );

				$enabled = 'enable' == sanitize_key( $_POST['doing'] ) ? TRUE : FALSE;

				if ( $gEditorial->update_module_option( $module->name, 'enabled', $enabled ) )
					wp_send_json_success( gEditorialHTML::success( _x( 'Module state succesfully changed.', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );
				else
					wp_send_json_error( gEditorialHTML::error( _x( 'Cannot change module state!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );

			break;

			default :
				wp_send_json_error( gEditorialHTML::error( _x( 'What?!', 'Settings Module: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) ) );
		}

		die();
	}

	public function admin_settings_page()
	{
		global $gEditorial;

		if ( ! $module = $gEditorial->get_module_by( 'settings', $_GET['page'] ) )
			wp_die( _x( 'Not a registered Editorial module', 'Settings Module: Page Notice', GEDITORIAL_TEXTDOMAIN ) );

		if ( isset( $gEditorial->{$module->name} ) ) {

			$this->print_default_header( $module );
			$gEditorial->{$module->name}->{$module->configure}();
			$this->settings_footer( $module );
			$this->settings_signature( $module );

		} else {

			gEditorialHTML::warning( _x( 'Module not enabled. Please enable it from the Editorial settings page.', 'Settings Module: Page Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
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
			$back  = gEditorialSettingsCore::settingsURL();
		}

		echo '<div class="wrap geditorial-admin-wrap geditorial-settings">';

			gEditorialSettingsCore::headerTitle( $title, $back, NULL, $current_module->icon );
			gEditorialSettingsCore::message();

			echo '<div class="-header">';

			if ( isset( $current_module->desc ) && $current_module->desc )
				echo '<h4>'.$current_module->desc.'</h4>';

			if ( isset( $current_module->intro ) && $current_module->intro )
				echo wpautop( $current_module->intro );

			if ( method_exists( $gEditorial->{$current_module->name}, 'settings_intro_after' ) )
				$gEditorial->{$current_module->name}->settings_intro_after( $current_module );

			echo '<div class="clear"></div>';
		echo '</div>';
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

				$enabled = isset( $gEditorial->{$name} );

				echo '<div class="module '.( $enabled ? 'module-enabled' : 'module-disabled' )
					.'" id="'.$module->settings.'" data-module="'.$module->name.'">';

				if ( $module->icon )
					echo '<div class="dashicons dashicons-'.$module->icon.'"></div>';

				echo '<span class="spinner"></span>';

				echo '<form action="">';

					gEditorialSettingsCore::moduleInfo( $module );

					echo '<p class="actions">';

						gEditorialSettingsCore::moduleConfigure( $module, $enabled );
						gEditorialSettingsCore::moduleButtons( $module, $enabled );

					echo '</p>';
				echo '</form></div>';
			}

			echo '<div class="clear"></div>';

		} else {

			gEditorialHTML::warning( _x( 'There are no editorial modules registered!', 'Settings Module: Page Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
		}
	}

	public function admin_settings_load()
	{
		$page = self::req( 'page', NULL );

		$this->admin_settings_reset( $page );
		$this->admin_settings_save( $page );

		$screen = get_current_screen();

		foreach ( gEditorialSettingsCore::settingsHelpContent() as $tab )
			$screen->add_help_tab( $tab );

		do_action( 'geditorial_settings_load', $page );

		// need the all fields check
		// if ( gEditorialSettingsCore::SETTINGS == $page )
			$this->enqueue_asset_js( TRUE );
	}

	private function admin_settings_verify( $group )
	{
		if ( ! $this->cuc( 'settings' ) )
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

		gEditorialWordPress::redirectReferer( 'resetting' );
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

		if ( ! $this->admin_settings_verify( $group ) )
			self::cheatin();

		$options = $gEditorial->{$name}->settings_validate( ( isset( $_POST[$group] ) ? $_POST[$group] : array() ) );

		$options = (object) array_merge( (array) $gEditorial->{$name}->options, $options );
		$gEditorial->update_all_module_options( $gEditorial->{$name}->module->name, $options );

		gEditorialWordPress::redirectReferer();
	}
}

<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\User;

class Config extends gEditorial\Module
{

	protected $caps = [
		'reports'  => 'publish_posts',
		'settings' => 'manage_options',
		'tools'    => 'edit_others_posts',
	];

	public static function module()
	{
		return [
			'name'      => 'config',
			'title'     => _x( 'Editorial', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ),
			'desc'      => _x( 'WordPress in Magazine Style', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ),
			'icon'      => 'screenoptions',
			'settings'  => 'geditorial-settings',
			'configure' => 'print_default_settings',
			'frontend'  => FALSE,
			'autoload'  => TRUE,
		];
	}

	public function setup( $partials = [] )
	{
		if ( is_admin() ) {
			$this->action( 'admin_menu' );
			$this->filter( 'set-screen-option', 3 );
		}

		if ( WordPress::isAJAX() )
			$this->_hook_ajax();
	}

	public function admin_menu()
	{
		global $gEditorial;

		$can  = $this->cuc( 'settings' );
		$page = 'index.php';

		$hook_reports = add_submenu_page(
			$page,
			_x( 'Editorial Reports', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'My Reports', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			$this->caps['reports'],
			'geditorial-reports',
			[ $this, 'admin_reports_page' ]
		);

		$hook_settings = add_menu_page(
			$this->module->title,
			$this->module->title,
			$this->caps['settings'],
			$this->module->settings,
			[ $this, 'admin_settings_page' ],
			$this->get_posttype_icon()
		);

		$hook_tools = add_submenu_page(
			( $can ? $this->module->settings : $page ),
			_x( 'Editorial Tools', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			( $can
				? _x( 'Tools', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN )
				: _x( 'My Tools', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN )
			),
			$this->caps['tools'],
			'geditorial-tools',
			[ $this, 'admin_tools_page' ]
		);

		add_action( 'load-'.$hook_reports, [ $this, 'admin_reports_load' ] );
		add_action( 'load-'.$hook_settings, [ $this, 'admin_settings_load' ] );
		add_action( 'load-'.$hook_tools, [ $this, 'admin_tools_load' ] );

		foreach ( $gEditorial->modules as $name => &$module ) {

			if ( isset( $gEditorial->{$name} )
				&& $module->configure
				&& $name != $this->module->name ) {

				$hook_module = add_submenu_page( $this->module->settings,
					$module->title,
					$module->title,
					$this->caps['settings'], // FIXME: get from module
					$module->settings,
					[ $this, 'admin_settings_page' ]
				);

				if ( $hook_module )
					add_action( 'load-'.$hook_module, [ $this, 'admin_settings_load' ] );
			}
		}
	}

	// lets our screen options passing through
	public function set_screen_option( $false, $option, $value )
	{
		return Text::has( $option, $this->base ) ? $value : $false;
	}

	public function admin_reports_page()
	{
		$can = $this->cuc( 'reports' );
		$uri = Settings::reportsURL( FALSE, ! $can );
		$sub = Settings::sub();

		$subs = [ 'overview' => _x( 'Overview', 'Modules: Config: Reports Sub', GEDITORIAL_TEXTDOMAIN ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Modules: Config: Reports Sub', GEDITORIAL_TEXTDOMAIN );

		$subs = apply_filters( 'geditorial_reports_subs', $subs, 'reports' );

		$messages = apply_filters( 'geditorial_reports_messages', Settings::messages(), $sub );

		Settings::wrapOpen( $sub, $this->base, 'reports' );

			Settings::headerTitle( _x( 'Editorial Reports', 'Modules: Config: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/reports.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/reports.'.$sub.'.php' );
			else
				do_action( 'geditorial_reports_sub_'.$sub, $uri, $sub );

			$this->settings_signature( NULL, 'reports' );

		Settings::wrapClose();
	}

	public function admin_tools_page()
	{
		$can = $this->cuc( 'settings' );
		$uri = Settings::toolsURL( FALSE, ! $can );
		$sub = Settings::sub( ( $can ? 'general' : 'overview' ) );

		$subs = [ 'overview' => _x( 'Overview', 'Modules: Config: Tools Sub', GEDITORIAL_TEXTDOMAIN ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Modules: Config: Tools Sub', GEDITORIAL_TEXTDOMAIN );

		$subs = apply_filters( 'geditorial_tools_subs', $subs, 'tools' );

		if ( User::isSuperAdmin() ) {
			$subs['options'] = _x( 'Options', 'Modules: Config: Tools Sub', GEDITORIAL_TEXTDOMAIN );
			$subs['console'] = _x( 'Console', 'Modules: Config: Tools Sub', GEDITORIAL_TEXTDOMAIN );
		}

		$messages = apply_filters( 'geditorial_tools_messages', Settings::messages(), $sub );

		Settings::wrapOpen( $sub, $this->base, 'tools' );

			Settings::headerTitle( _x( 'Editorial Tools', 'Modules: Config: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			Settings::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' );
			else
				do_action( 'geditorial_tools_sub_'.$sub, $uri, $sub );

			$this->settings_signature( NULL, 'tools' );

		Settings::wrapClose();
	}

	public function admin_reports_load()
	{
		global $gEditorial, $wpdb;

		$sub = Settings::sub();

		if ( 'general' == $sub ) {
			add_action( 'geditorial_reports_sub_general', [ $this, 'reports_sub' ], 10, 2 );
		}

		do_action( 'geditorial_reports_settings', $sub );
	}

	public function admin_tools_load()
	{
		global $gEditorial, $wpdb;

		$sub = Settings::sub();

		if ( 'general' == $sub ) {
			if ( ! empty( $_POST ) ) {

				$this->settings_check_referer( $sub, 'tools' );

				$post = isset( $_POST[$this->module->group]['tools'] ) ? $_POST[$this->module->group]['tools'] : [];

				if ( isset( $_POST['upgrade_old_options'] ) ) {

					$result = $gEditorial->upgrade_old_options();

					if ( count( $result ) )
						WordPress::redirectReferer( [
							'message' => 'upgraded',
							'count'   => count( $result ),
						] );

				} else if ( isset( $_POST['delete_all_options'] ) ) {

					if ( delete_option( 'geditorial_options' ) )
						WordPress::redirectReferer( 'purged' );

				} else if ( isset( $_POST['custom_fields_empty'] ) ) {

					if ( isset( $post['empty_module'] ) && isset( $gEditorial->{$post['empty_module']}->meta_key ) ) {

						$result = Database::deleteEmptyMeta( $gEditorial->{$post['empty_module']}->meta_key );

						if ( count( $result ) )
							WordPress::redirectReferer( [
								'message' => 'emptied',
								'count'   => count( $result ),
							] );
					}
				}
			}

			add_action( 'geditorial_tools_sub_general', [ $this, 'tools_sub' ], 10, 2 );
		}

		do_action( 'geditorial_tools_settings', $sub );
	}

	public function reports_sub( $uri, $sub )
	{
		if ( 'general' != $sub )
			return;

		if ( ! $this->cuc( 'reports' ) )
			self::cheatin();

		// FIXME
		HTML::warning( 'Comming Soon!', TRUE );
	}

	public function tools_sub( $uri, $sub )
	{
		if ( 'general' == $sub )
			return $this->tools_sub_general( $uri, $sub );

		// TODO: sub for installing default terms for each module
		// @SEE: https://make.wordpress.org/core/?p=20650
		// if ( 'defaults' == $sub )
		// 	return $this->tools_sub_defaults( $uri, $sub );
	}

	private function tools_sub_general( $uri, $sub )
	{
		global $gEditorial;

		if ( ! $this->cuc( 'settings' ) )
			self::cheatin();

		$post = isset( $_POST[$this->module->group]['tools'] ) ? $_POST[$this->module->group]['tools'] : [];

		$this->settings_form_before( $uri, $sub, 'bulk', 'tools', FALSE, FALSE );

			HTML::h3( _x( 'Maintenance Tasks', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'Options', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				echo '<p>';
					Settings::submitButton( 'upgrade_old_options',
						_x( 'Upgrade Old Options', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

					HTML::desc( _x( 'Will check for old options and upgrade, also delete old options', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), FALSE );
				echo '</p>';

				if ( User::isSuperAdmin() || WordPress::isDev() ) {
					echo '<br /><p>';
						Settings::submitButton( 'delete_all_options',
							_x( 'Delete All Options', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'delete', TRUE );

						HTML::desc( _x( 'Deletes all editorial options on current site', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), FALSE );
					echo '</p>';
				}

			echo '</td></tr>';
			echo '<tr><th scope="row">'._x( 'Empty Meta Fields', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'empty_module',
					'values'       => $gEditorial->get_all_modules(),
					'default'      => ( isset( $post['empty_module'] ) ? $post['empty_module'] : 'meta' ),
					'option_group' => 'tools',
				] );

				echo '&nbsp;&nbsp;';

				Settings::submitButton( 'custom_fields_empty',
					_x( 'Empty', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'delete', TRUE );

				HTML::desc( _x( 'Will delete empty meta values, solves common problems with imported posts.', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';
			echo '</table>';

		$this->settings_form_after( $uri, $sub );
	}

	public function ajax()
	{
		global $gEditorial;

		if ( ! $this->cuc( 'settings' ) )
			self::cheatin();

		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'state':

				Ajax::checkReferer( $this->hook() );

				if ( ! isset( $_POST['doing'], $_POST['name'] ) )
					Ajax::errorMessage( _x( 'No action or name!', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );

				if ( ! $module = $gEditorial->get_module_by( 'name', sanitize_key( $_POST['name'] ) ) )
					Ajax::errorMessage( _x( 'Cannot find the module!', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );

				$enabled = 'enable' == sanitize_key( $_POST['doing'] ) ? TRUE : FALSE;

				if ( $gEditorial->update_module_option( $module->name, 'enabled', $enabled ) )
					Ajax::successMessage( _x( 'Module state succesfully changed.', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
				else
					Ajax::errorMessage( _x( 'Cannot change module state!', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
		}

		Ajax::errorWhat();
	}

	public function admin_settings_page()
	{
		global $gEditorial;

		if ( ! $module = $gEditorial->get_module_by( 'settings', $_GET['page'] ) )
			wp_die( _x( 'Not a registered Editorial module', 'Modules: Config: Page Notice', GEDITORIAL_TEXTDOMAIN ) );

		if ( isset( $gEditorial->{$module->name} ) ) {

			$this->print_default_header( $module );
			$gEditorial->{$module->name}->{$module->configure}();
			$this->settings_footer( $module );
			$this->settings_signature( $module );

		} else {

			HTML::warning( _x( 'Module not enabled. Please enable it from the Editorial settings page.', 'Modules: Config: Page Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
		}
	}

	public function print_default_header( $current_module )
	{
		global $gEditorial;

		$back = $count = FALSE;

		if ( 'config' == $current_module->name ) {
			$title = _x( 'Editorial', 'Modules: Config', GEDITORIAL_TEXTDOMAIN );
			$count = count( get_object_vars( $gEditorial->modules ) );
		} else {
			$title = sprintf( _x( 'Editorial: %s', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), $current_module->title );
			$back  = Settings::settingsURL();
		}

		Settings::wrapOpen( $current_module->name, $this->base, 'settings' );

			Settings::headerTitle( $title, $back, NULL, $current_module->icon, $count, TRUE );
			Settings::message();

			echo '<div class="-header">';

			if ( isset( $current_module->desc ) && $current_module->desc )
				echo '<h4>'.$current_module->desc.'</h4>';

			if ( isset( $current_module->intro ) && $current_module->intro )
				echo wpautop( $current_module->intro );

			if ( method_exists( $gEditorial->{$current_module->name}, 'settings_intro_after' ) )
				$gEditorial->{$current_module->name}->settings_intro_after( $current_module );

		Settings::wrapClose();
	}

	private function print_default_settings()
	{
		echo '<div class="modules -list">';
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
					echo Helper::getIcon( $module->icon );

				echo '<span class="spinner"></span>';

				echo '<form action="">';

					Settings::moduleInfo( $module );

					echo '<p class="actions">';

						Settings::moduleConfigure( $module, $enabled );
						Settings::moduleButtons( $module, $enabled );

					echo '</p>';
				echo '</form></div>';
			}

			echo '<div class="clear"></div>';

		} else {

			HTML::warning( _x( 'There are no editorial modules registered!', 'Modules: Config: Page Notice', GEDITORIAL_TEXTDOMAIN ), TRUE );
		}
	}

	public function admin_settings_load()
	{
		$page = self::req( 'page', NULL );

		$this->admin_settings_reset( $page );
		$this->admin_settings_save( $page );

		$screen = get_current_screen();

		foreach ( Settings::settingsHelpContent() as $tab )
			$screen->add_help_tab( $tab );

		do_action( 'geditorial_settings_load', $page );

		$listjs = Helper::registerScriptPackage( 'listjs',
			'list.js/list', [], '1.5.0' );

		$this->enqueue_asset_js( [], NULL, [ 'jquery', $listjs ] );
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
		if ( ! isset( $_POST['reset'], $_POST['geditorial_module_name'] ) )
			return;

		global $gEditorial;
		$name = sanitize_key( $_POST['geditorial_module_name'] );

		if ( ! $this->admin_settings_verify( $gEditorial->{$name}->module->group ) )
			self::cheatin();

		$gEditorial->update_all_module_options( $gEditorial->{$name}->module->name, [ 'enabled' => TRUE ] );

		WordPress::redirectReferer( 'resetting' );
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

		$options = $gEditorial->{$name}->settings_validate( ( isset( $_POST[$group] ) ? $_POST[$group] : [] ) );

		// $options = (object) array_merge( (array) $gEditorial->{$name}->options, $options );
		$options['enabled'] = TRUE;

		$gEditorial->update_all_module_options( $gEditorial->{$name}->module->name, $options );

		WordPress::redirectReferer();
	}
}

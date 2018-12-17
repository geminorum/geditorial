<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Text;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\User;
use geminorum\gEditorial\O2O;

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
			'name'     => 'config',
			'title'    => _x( 'Editorial', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'WordPress in Magazine Style', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ),
			'frontend' => FALSE,
			'autoload' => TRUE,
		];
	}

	protected function setup( $args = [] )
	{
		parent::setup();

		if ( is_admin() )
			$this->filter( 'set-screen-option', 3 );

		if ( WordPress::isAJAX() )
			$this->_hook_ajax();
	}

	public function admin_menu()
	{
		$can = $this->cuc( 'settings' );

		$hook_reports = add_submenu_page(
			'index.php',
			_x( 'Editorial Reports', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			_x( 'My Reports', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			$this->caps['reports'],
			$this->base.'-reports',
			[ $this, 'admin_reports_page' ]
		);

		$hook_settings = add_menu_page(
			$this->module->title,
			$this->module->title,
			$this->caps['settings'],
			$this->base.'-settings',
			[ $this, 'admin_settings_page' ],
			$this->get_posttype_icon()
		);

		$hook_tools = add_submenu_page(
			( $can ? $this->base.'-settings' : 'tools.php' ),
			_x( 'Editorial Tools', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN ),
			( $can
				? _x( 'Tools', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN )
				: _x( 'Editorial Tools', 'Modules: Config: Menu Title', GEDITORIAL_TEXTDOMAIN )
			),
			$this->caps['tools'],
			$this->base.'-tools',
			[ $this, 'admin_tools_page' ]
		);

		add_action( 'load-'.$hook_reports, [ $this, 'admin_reports_load' ] );
		add_action( 'load-'.$hook_settings, [ $this, 'admin_settings_load' ] );
		add_action( 'load-'.$hook_tools, [ $this, 'admin_tools_load' ] );

		foreach ( gEditorial()->modules( 'title' ) as $module ) {

			if ( ! $module->configure )
				continue;

			if ( $module->name == $this->module->name )
				continue;

			if ( FALSE !== $module->disabled )
				continue;

			if ( ! gEditorial()->enabled( $module->name ) )
				continue;

			add_submenu_page(
				$this->base.'-settings',
				$module->title,
				$module->title,
				$this->caps['settings'],
				$this->base.'-settings&module='.$module->name,
				[ $this, 'admin_settings_page' ]
			);
		}
	}

	public function get_adminmenu( $page = TRUE, $extra = [] )
	{
		return FALSE;
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

		Settings::wrapOpen( $sub, 'reports' );

			Settings::headerTitle( _x( 'Editorial Reports', 'Modules: Config: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			HTML::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/reports.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/reports.'.$sub.'.php' );
			else
				do_action( 'geditorial_reports_sub_'.$sub, $uri, $sub );

			$this->settings_signature( 'reports' );

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

		Settings::wrapOpen( $sub, 'tools' );

			Settings::headerTitle( _x( 'Editorial Tools', 'Modules: Config: Page Title', GEDITORIAL_TEXTDOMAIN ) );
			HTML::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( file_exists( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' ) )
				require_once( GEDITORIAL_DIR.'includes/settings/tools.'.$sub.'.php' );
			else
				do_action( 'geditorial_tools_sub_'.$sub, $uri, $sub );

			$this->settings_signature( 'tools' );

		Settings::wrapClose();
	}

	public function admin_reports_load()
	{
		$sub = Settings::sub();

		if ( 'general' == $sub ) {

			add_action( 'geditorial_reports_sub_general', [ $this, 'reports_sub' ], 10, 2 );

			$this->register_help_tabs();
		}

		do_action( 'geditorial_reports_settings', $sub );
	}

	public function admin_tools_load()
	{
		$sub = Settings::sub();

		if ( 'general' == $sub ) {

			if ( ! empty( $_POST ) ) {

				$this->nonce_check( 'tools', $sub );

				$post = $this->get_current_form( [
					'empty_module' => FALSE,
				], 'tools' );

				if ( isset( $_POST['upgrade_old_options'] ) ) {

					$result = gEditorial()->upgrade_old_options();

					if ( count( $result ) )
						WordPress::redirectReferer( [
							'message' => 'upgraded',
							'count'   => count( $result ),
						] );

				} else if ( isset( $_POST['delete_all_options'] ) ) {

					if ( delete_option( 'geditorial_options' ) )
						WordPress::redirectReferer( 'purged' );

				} else if ( isset( $_POST['custom_fields_empty'] ) ) {

					if ( $post['empty_module'] && isset( gEditorial()->{$post['empty_module']}->meta_key ) ) {

						$result = Database::deleteEmptyMeta( gEditorial()->{$post['empty_module']}->meta_key );

						if ( count( $result ) )
							WordPress::redirectReferer( [
								'message' => 'emptied',
								'count'   => count( $result ),
							] );
					}

				} else if ( isset( $_POST['convert_connection_type'] ) ) {

					if ( empty( $_POST['old_o2o_type'] )
						|| empty( $_POST['new_o2o_type'] ) )
							WordPress::redirectReferer( 'wrong' );

					$result = O2O\API::convertConnection( $_POST['old_o2o_type'], $_POST['new_o2o_type'] );

					if ( FALSE === $result )
						WordPress::redirectReferer( 'wrong' );

					else
						WordPress::redirectReferer( [
							'message' => 'converted',
							'count'   => $count,
						] );

				} else {

					WordPress::redirectReferer( 'huh' );
				}
			}

			add_action( 'geditorial_tools_sub_'.$sub, [ $this, 'tools_sub' ], 10, 2 );

			$this->register_help_tabs();
		}

		do_action( 'geditorial_tools_settings', $sub );
	}

	public function reports_sub( $uri, $sub )
	{
		if ( 'general' != $sub )
			return;

		if ( ! $this->cuc( 'reports' ) )
			self::cheatin();

		HTML::h3( _x( 'General Editorial Reports', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ) );
		HTML::desc( _x( 'No reports available!', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), TRUE, '-empty' );
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
		if ( ! $this->cuc( 'tools' ) )
			self::cheatin();

		$post = $this->get_current_form( [
			'empty_module' => 'meta',
		], 'tools' );

		$this->render_form_start( $uri, $sub, 'bulk', 'tools', TRUE );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'Options', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				echo '<p>';
					Settings::submitButton( 'upgrade_old_options',
						_x( 'Upgrade Old Options', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ) );

					HTML::desc( _x( 'Checks for old options and upgrade them. Also deletes the old options.', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), FALSE );
				echo '</p>';

				if ( User::isSuperAdmin() || WordPress::isDev() ) {
					echo '<br /><p>';
						Settings::submitButton( 'delete_all_options',
							_x( 'Delete All Options', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'danger', TRUE );

						HTML::desc( _x( 'Deletes all editorial options on current site', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), FALSE );
					echo '</p>';
				}

			echo '</td></tr></table>';

			HTML::h2( _x( 'Maintenance Tasks', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ) );

			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'Empty Meta Fields', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'empty_module',
					'values'       => gEditorial()->list_modules(),
					'default'      => $post['empty_module'],
					'option_group' => 'tools',
				] );

				echo '&nbsp;&nbsp;';

				Settings::submitButton( 'custom_fields_empty',
					_x( 'Empty', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'danger', TRUE );

				HTML::desc( _x( 'Deletes empty meta values. This solves common problems with imported posts.', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ) );

			echo '</td></tr>';

			echo '<tr><th scope="row">'._x( 'Orphan Connections', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			// $counts = O2O\API::getConnectionCounts();

			if ( empty( $counts ) ) {

				HTML::desc( _x( 'No orphaned types found in the database.', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), TRUE, '-empty' );

			} else {

				$types = O2O\ConnectionTypeFactory::get_all_instances();

				foreach ( $counts as $type => $count ) {

					if ( O2O\API::type( $type ) )
						continue;

					echo '<code>'.$type.'</code> ('.$count.') ';

					$this->do_settings_field( [
						'type'         => 'select',
						'field'        => 'new_o2o_type',
						'values'       => array_keys( $types ),
						// 'default'      => $post['empty_module'],
						'option_group' => 'tools',
					] );

					echo '&nbsp;&nbsp;';

					Settings::submitButton( 'convert_connection_type',
						_x( 'Convert', 'Modules: Config: Setting Button', GEDITORIAL_TEXTDOMAIN ), 'danger', TRUE );
				}
			}

			echo '</td></tr></table>';

		$this->render_form_end( $uri, $sub );
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $user = gEditorial()->user() ) {

			$name = get_userdata( $user )->display_name;
			$edit = WordPress::getUserEditLink( $user );

			HTML::desc( sprintf( _x( 'Editorial Site User Is %s', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ),
				$edit ? HTML::link( $name, $edit, TRUE ) : $name ) );
		} else {
			HTML::desc( _x( 'No Editorial Site User available!', 'Modules: Config', GEDITORIAL_TEXTDOMAIN ), TRUE, '-empty' );
		}
	}

	public function ajax()
	{
		if ( ! $this->cuc( 'settings' ) )
			self::cheatin();

		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'state':

				Ajax::checkReferer( $this->hook() );

				if ( ! isset( $_POST['doing'], $_POST['name'] ) )
					Ajax::errorMessage( _x( 'No action or name!', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );

				if ( ! $module = gEditorial()->get_module_by( 'name', sanitize_key( $_POST['name'] ) ) )
					Ajax::errorMessage( _x( 'Cannot find the module!', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );

				$enabled = 'enable' == sanitize_key( $_POST['doing'] ) ? TRUE : FALSE;

				if ( gEditorial()->update_module_option( $module->name, 'enabled', $enabled ) )
					Ajax::successMessage( _x( 'Module state succesfully changed.', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
				else
					Ajax::errorMessage( _x( 'Cannot change module state!', 'Modules: Config: Ajax Notice', GEDITORIAL_TEXTDOMAIN ) );
		}

		Ajax::errorWhat();
	}

	public function admin_settings_page()
	{
		if ( ! $key = self::req( 'module', FALSE ) )
			$module = $this->module;

		else if ( ! $module = gEditorial()->get_module_by( 'name', $key ) )
			return Settings::wrapError( HTML::warning( _x( 'Not a registered Editorial module.', 'Modules: Config: Page Notice', GEDITORIAL_TEXTDOMAIN ), FALSE ) );

		if ( ! gEditorial()->enabled( $module->name ) )
			return Settings::wrapError( HTML::warning( _x( 'Module not enabled. Please enable it from the Editorial settings page.', 'Modules: Config: Page Notice', GEDITORIAL_TEXTDOMAIN ), FALSE ) );

		$plugin = gEditorial();

		$plugin->{$module->name}->settings_header();
			$plugin->{$module->name}->settings_from();
		$plugin->{$module->name}->settings_footer();
	}

	public function settings_from()
	{
		echo '<div class="modules -list">';

		foreach ( gEditorial()->modules( 'title' ) as $module ) {

			if ( $module->autoload )
				continue;

			$enabled = gEditorial()->enabled( $module->name );

			echo '<div data-module="'.$module->name.'" class="module '
				.( $enabled ? '-enabled' : '-disabled' ).'">';

			if ( $module->icon )
				echo Helper::getIcon( $module->icon );

			echo Ajax::spinner();

			Settings::moduleInfo( $module, $enabled );

			if ( FALSE === $module->disabled ) {

				echo '<p class="actions">';
					Settings::moduleConfigure( $module, $enabled );
					Settings::moduleButtons( $module, $enabled );
				echo '</p>';

			} else if ( $module->disabled ) {

				echo HTML::wrap( $module->disabled, 'actions -danger' );
			}

			echo '</div>';
		}

		echo '<div class="clear"></div></div>';
	}

	public function admin_settings_load()
	{
		if ( ! $this->cuc( 'settings' ) )
			return FALSE;

		$module = self::req( 'module', FALSE );

		$this->settings_disable( $module );
		$this->settings_reset( $module );
		$this->settings_save( $module );

		if ( $module )
			$GLOBALS['submenu_file'] = $this->base.'-settings&module='.$module;

		do_action( 'geditorial_settings_load', $module );

		$this->enqueue_asset_js( [], NULL, [ 'jquery', Scripts::pkgListJS() ] );
	}

	// no settings/only screen options
	public function register_settings( $module = FALSE )
	{
		if ( $module )
			return;

		$this->register_help_tabs();
	}

	public function settings_disable( $module = FALSE )
	{
		if ( ! isset( $_POST['disable'], $_POST['geditorial_module_name'] ) )
			return FALSE;

		if ( ! $module = gEditorial()->get_module_by( 'name', sanitize_key( $_POST['geditorial_module_name'] ) ) )
			return FALSE;

		if ( ! $this->nonce_verify( 'settings', NULL, $module->name ) )
			self::cheatin();

		if ( gEditorial()->update_module_option( $module->name, 'enabled', FALSE ) )
			WordPress::redirectReferer( [ 'message' => 'disabled', 'module' => FALSE ] );

		else
			WordPress::redirectReferer( 'error' );
	}

	public function settings_reset( $module = FALSE )
	{
		if ( ! isset( $_POST['reset'], $_POST['geditorial_module_name'] ) )
			return FALSE;

		if ( ! $module = gEditorial()->get_module_by( 'name', sanitize_key( $_POST['geditorial_module_name'] ) ) )
			return FALSE;

		if ( ! $this->nonce_verify( 'settings', NULL, $module->name ) )
			self::cheatin();

		gEditorial()->update_all_module_options( $module->name, [ 'enabled' => TRUE ] );

		WordPress::redirectReferer( 'resetting' );
	}

	public function settings_save( $module = FALSE )
	{
		if ( ! isset( $_POST['submit'], $_POST['action'], $_POST['geditorial_module_name'] ) )
			return FALSE;

		if ( 'update' != $_POST['action'] )
			return FALSE;

		if ( ! $module = gEditorial()->get_module_by( 'name', sanitize_key( $_POST['geditorial_module_name'] ) ) )
			return FALSE;

		if ( ! $this->nonce_verify( 'settings', NULL, $module->name ) )
			self::cheatin();

		$posted  = empty( $_POST[$this->base.'_'.$module->name] ) ? [] : $_POST[$this->base.'_'.$module->name];
		$options = gEditorial()->{$module->name}->settings_validate( $posted );

		$options['enabled'] = TRUE;

		gEditorial()->update_all_module_options( $module->name, $options );

		WordPress::redirectReferer();
	}
}
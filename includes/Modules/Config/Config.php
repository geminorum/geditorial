<?php namespace geminorum\gEditorial\Modules\Config;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Database;
use geminorum\gEditorial\WordPress\User;
use geminorum\gEditorial\O2O;

class Config extends gEditorial\Module
{

	protected $caps = [
		'reports'  => 'publish_posts',
		'settings' => 'manage_options',
		'tools'    => 'edit_posts',
	];

	public static function module()
	{
		return [
			'name'     => 'config',
			'title'    => _x( 'Editorial', 'Modules: Config', 'geditorial' ),
			'desc'     => _x( 'WordPress in Magazine Style', 'Modules: Config', 'geditorial' ),
			'frontend' => FALSE,
			'autoload' => TRUE,
		];
	}

	public function init_ajax()
	{
		$this->_hook_ajax();
	}

	public function admin_menu()
	{
		$can = $this->cuc( 'settings' );

		$hook_reports = add_submenu_page(
			'index.php',
			_x( 'Editorial Reports', 'Menu Title', 'geditorial-config' ),
			_x( 'My Reports', 'Menu Title', 'geditorial-config' ),
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
			_x( 'Editorial Tools', 'Menu Title', 'geditorial-config' ),
			( $can
				? _x( 'Tools', 'Menu Title', 'geditorial-config' )
				: _x( 'Editorial Tools', 'Menu Title', 'geditorial-config' )
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

	public function admin_reports_page()
	{
		$can = $this->cuc( 'reports' );
		$uri = Settings::reportsURL( FALSE, ! $can );
		$sub = Settings::sub();

		$subs = [ 'overview' => _x( 'Overview', 'Reports Sub', 'geditorial-config' ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Reports Sub', 'geditorial-config' );

		$subs = apply_filters( $this->base.'_reports_subs', $subs, 'reports' );

		$messages = apply_filters( $this->base.'_reports_messages', Settings::messages(), $sub );

		Settings::wrapOpen( $sub, 'reports' );

			Settings::headerTitle( _x( 'Editorial Reports', 'Page Title', 'geditorial-config' ) );
			HTML::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->reports_overview( $uri );

			else if ( 'console' == $sub )
				gEditorial()->files( 'Layouts/console.reports' );

			else if ( has_action( $this->base.'_reports_sub_'.$sub ) )
				do_action( $this->base.'_reports_sub_'.$sub, $uri, $sub );

			else
				Settings::cheatin();

			$this->settings_signature( 'reports' );

		Settings::wrapClose();
	}

	protected function reports_overview( $uri )
	{
		// summary of available tools
	}

	public function admin_tools_page()
	{
		$can = $this->cuc( 'settings' );
		$uri = Settings::toolsURL( FALSE, ! $can );
		$sub = Settings::sub( ( $can ? 'general' : 'overview' ) );

		$subs = [ 'overview' => _x( 'Overview', 'Tools Sub', 'geditorial-config' ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Tools Sub', 'geditorial-config' );

		$subs = apply_filters( $this->base.'_tools_subs', $subs, 'tools' );

		if ( User::isSuperAdmin() ) {
			$subs['options'] = _x( 'Options', 'Tools Sub', 'geditorial-config' );
			$subs['console'] = _x( 'Console', 'Tools Sub', 'geditorial-config' );
		}

		$messages = apply_filters( $this->base.'_tools_messages', Settings::messages(), $sub );

		Settings::wrapOpen( $sub, 'tools' );

			Settings::headerTitle( _x( 'Editorial Tools', 'Page Title', 'geditorial-config' ) );
			HTML::headerNav( $uri, $sub, $subs );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->tools_overview( $uri );

			else if ( 'options' == $sub )
				$this->tools_options( $uri );

			else if ( 'console' == $sub )
				gEditorial()->files( 'Layouts/console.tools' );

			else if ( has_action( $this->base.'_tools_sub_'.$sub ) )
				do_action( $this->base.'_tools_sub_'.$sub, $uri, $sub );

			else
				Settings::cheatin();

			$this->settings_signature( 'tools' );

		Settings::wrapClose();
	}

	protected function tools_overview( $uri )
	{
		if ( function_exists( 'gnetwork_update_notice' ) )
			gnetwork_update_notice( GEDITORIAL_FILE );

		if ( function_exists( 'gnetwork_github_readme' ) )
			gnetwork_github_readme( 'geminorum/geditorial' );
	}

	protected function tools_options( $uri )
	{
		User::superAdminOnly();

		echo '<br />';

		if ( $options = get_option( 'geditorial_options' ) )
			HTML::tableSide( $options );
		else
			HTML::desc( gEditorial\Plugin::na() );
	}

	public function admin_reports_load()
	{
		$sub = Settings::sub();

		if ( 'general' == $sub ) {

			add_action( $this->base.'_reports_sub_general', [ $this, 'reports_sub' ], 10, 2 );

			$this->register_help_tabs();
		}

		do_action( $this->base.'_reports_settings', $sub );
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

				if ( Tablelist::isAction( 'upgrade_old_options' ) ) {

					$result = gEditorial()->upgrade_old_options();

					if ( count( $result ) )
						WordPress::redirectReferer( [
							'message' => 'upgraded',
							'count'   => count( $result ),
						] );

				} else if ( Tablelist::isAction( 'delete_all_options' ) ) {

					if ( delete_option( 'geditorial_options' ) )
						WordPress::redirectReferer( 'purged' );

				} else if ( Tablelist::isAction( 'custom_fields_empty' ) ) {

					if ( $post['empty_module'] && isset( gEditorial()->{$post['empty_module']}->meta_key ) ) {

						$result = Database::deleteEmptyMeta( gEditorial()->{$post['empty_module']}->meta_key );

						if ( count( $result ) )
							WordPress::redirectReferer( [
								'message' => 'emptied',
								'count'   => count( $result ),
							] );
					}

				} else if ( Tablelist::isAction( 'convert_connection_type' ) ) {

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

			add_action( $this->base.'_tools_sub_'.$sub, [ $this, 'tools_sub' ], 10, 2 );

			$this->register_help_tabs();
		}

		do_action( $this->base.'_tools_settings', $sub );
	}

	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			self::cheatin();

		HTML::h3( _x( 'General Editorial Reports', 'Header', 'geditorial-config' ) );
		HTML::desc( _x( 'No reports available!', 'Message', 'geditorial-config' ), TRUE, '-empty' );
	}

	protected function render_tools_html( $uri, $sub )
	{
		if ( ! $this->cuc( 'tools' ) )
			self::cheatin();

		$post = $this->get_current_form( [
			'empty_module' => 'meta',
		], 'tools' );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Options', 'Tools', 'geditorial-config' ).'</th><td>';

			echo '<p>';
				Settings::submitButton( 'upgrade_old_options',
					_x( 'Upgrade Old Options', 'Button', 'geditorial-config' ) );

				HTML::desc( _x( 'Checks for old options and upgrade them. Also deletes the old options.', 'Tools: Message', 'geditorial-config' ), FALSE );
			echo '</p>';

			if ( User::isSuperAdmin() || WordPress::isDev() ) {
				echo '<br /><p>';
					Settings::submitButton( 'delete_all_options',
						_x( 'Delete All Options', 'Button', 'geditorial-config' ), 'danger', TRUE );

					HTML::desc( _x( 'Deletes all editorial options on current site', 'Tools: Message', 'geditorial-config' ), FALSE );
				echo '</p>';
			}

		echo '</td></tr></table>';

		HTML::h2( _x( 'Maintenance Tasks', 'Tools: Header', 'geditorial-config' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Empty Meta Fields', 'Tools', 'geditorial-config' ).'</th><td>';

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'empty_module',
				'values'       => gEditorial()->list_modules(),
				'default'      => $post['empty_module'],
				'option_group' => 'tools',
			] );

			echo '&nbsp;&nbsp;';

			Settings::submitButton( 'custom_fields_empty',
				_x( 'Empty', 'Button', 'geditorial-config' ), 'danger', TRUE );

			HTML::desc( _x( 'Deletes empty meta values. This solves common problems with imported posts.', 'Tools: Message', 'geditorial-config' ) );

		echo '</td></tr>';

		echo '<tr><th scope="row">'._x( 'Orphan Connections', 'Tools', 'geditorial-config' ).'</th><td>';

		// $counts = O2O\API::getConnectionCounts();

		if ( empty( $counts ) ) {

			HTML::desc( _x( 'No connection types found.', 'Tools: Message', 'geditorial-config' ), TRUE, '-empty' );

		} else {

			$types = O2O\ConnectionTypeFactory::get_all_instances();
			$empty = TRUE;

			foreach ( $counts as $type => $count ) {

				if ( O2O\API::type( $type ) )
					continue;

				$empty = FALSE;

				echo HTML::wrapLTR( '<code>'.$type.'</code>' );
				echo ' &mdash; ('.Helper::getCounted( $count ).') &mdash; ';

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'new_o2o_type',
					'values'       => array_keys( $types ),
					// 'default'      => $post['empty_module'],
					'option_group' => 'tools',
				] );

				echo '&nbsp;&nbsp;';

				Settings::submitButton( 'convert_connection_type',
					_x( 'Convert', 'Button', 'geditorial-config' ), 'danger', TRUE );
			}

			if ( $empty )
				HTML::desc( _x( 'No orphaned connection types found in the database.', 'Tools: Message', 'geditorial-config' ), TRUE, '-empty' );
		}

		echo '</td></tr></table>';
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $user = gEditorial()->user() ) {

			$name = get_userdata( $user )->display_name;
			$edit = WordPress::getUserEditLink( $user );

			/* translators: %s: user link placeholder */
			HTML::desc( sprintf( _x( 'Editorial Site User Is %s', 'Sidebox: Message', 'geditorial-config' ),
				$edit ? HTML::link( $name, $edit, TRUE ) : $name ) );

		} else {

			HTML::desc( _x( 'No Editorial Site User available!', 'Sidebox: Message', 'geditorial-config' ), TRUE, '-empty' );
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
					Ajax::errorMessage( _x( 'No action or name!', 'Ajax Notice', 'geditorial-config' ) );

				if ( ! $module = gEditorial()->get_module_by( 'name', sanitize_key( $_POST['name'] ) ) )
					Ajax::errorMessage( _x( 'Cannot find the module!', 'Ajax Notice', 'geditorial-config' ) );

				$enabled = 'enable' == sanitize_key( $_POST['doing'] ) ? TRUE : FALSE;

				if ( gEditorial()->update_module_option( $module->name, 'enabled', $enabled ) )
					Ajax::successMessage( _x( 'Module state successfully changed.', 'Ajax Notice', 'geditorial-config' ) );
				else
					Ajax::errorMessage( _x( 'Cannot change module state!', 'Ajax Notice', 'geditorial-config' ) );
		}

		Ajax::errorWhat();
	}

	public function admin_settings_page()
	{
		if ( ! $key = self::req( 'module', FALSE ) )
			$module = $this->module;

		else if ( ! $module = gEditorial()->get_module_by( 'name', $key ) )
			return Settings::wrapError( HTML::warning( _x( 'Not a registered Editorial module.', 'Page Notice', 'geditorial-config' ), FALSE ) );

		if ( ! gEditorial()->enabled( $module->name ) )
			return Settings::wrapError( HTML::warning( _x( 'Module not enabled. Please enable it from the Editorial settings page.', 'Page Notice', 'geditorial-config' ), FALSE ) );

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

		do_action( $this->base.'_settings_load', $module );

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

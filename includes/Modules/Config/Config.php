<?php namespace geminorum\gEditorial\Modules\Config;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Ajax;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Datetime;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Info;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Parser;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Tablelist;
use geminorum\gEditorial\WordPress;

class Config extends gEditorial\Module
{
	use Internals\CoreMenuPage;
	use Internals\ViewEngines;

	protected $caps     = [];  // reset the default caps!
	protected $caps_map = [
		'reports'  => 'publish_posts',
		'settings' => 'manage_options',
		'tools'    => 'edit_posts',
		'roles'    => 'edit_users',
		'tests'    => 'manage_options', // TODO: add test pages
		'imports'  => 'import',
	];

	protected $positions = [
		'imports' => 1,
		'roles'   => 2,
	];

	public static function module()
	{
		return [
			'name'       => 'config',
			'title'      => _x( 'Editorial', 'Modules: Config', 'geditorial-admin' ),
			'desc'       => _x( 'WordPress in Magazine Style', 'Modules: Config', 'geditorial-admin' ),
			'textdomain' => FALSE, // strings in this module are loaded via plugin
			'frontend'   => FALSE, // move all strings to `geditorial-admin` text-domain
			'autoload'   => TRUE,
			'access'     => 'stable',
		];
	}

	public function init()
	{
		parent::init();
		$this->filter( 'map_meta_cap', 4 );
	}

	// @REF: http://justintadlock.com/?p=2462
	public function map_meta_cap( $caps, $cap, $user_id, $args )
	{
		switch ( $cap ) {

			/**
			 * - Meta caps need to be defined with the `map_meta_cap` filter.
			 * - Primitive caps are assigned to user roles.
			 * - Meta caps *never* should be assigned to a role.
			 * - Primitive caps are generally plural, meta caps are singular.
			 *
			 * @source https://wpmayor.com/roles-capabilities-wordpress/
			 */

			case 'editorial_reports':
			case 'editorial_settings':
			case 'editorial_tools':
			case 'editorial_roles':
			case 'editorial_tests':
			case 'editorial_imports':

				if ( WordPress\User::isSuperAdmin() )
					return [ 'exist' ];

				$context = Core\Text::stripPrefix( $cap, 'editorial_' );

				if ( user_can( $user_id, $this->hook_base( $context ) ) )
					return [ 'exist' ];

				if ( array_key_exists( $context, $this->caps_map ) )
					return (array) $this->caps_map[$context];

				// fallback
				return (array) $this->caps_map['settings'];
		}

		return $caps;
	}

	public function admin_menu()
	{
		$can = $this->cuc( 'settings' );

		$hook_reports = add_submenu_page(
			'index.php',
			_x( 'Editorial Reports', 'Menu Title', 'geditorial-admin' ),
			_x( 'My Reports', 'Menu Title', 'geditorial-admin' ),
			'editorial_reports',
			$this->base.'-reports',
			[ $this, 'admin_reports_page' ]
		);

		$hook_settings = add_menu_page(
			$this->module->title,
			$this->module->title,
			'editorial_settings',
			$this->base.'-settings',
			[ $this, 'admin_settings_page' ],
			$this->get_posttype_icon()
		);

		$hook_tools = add_submenu_page(
			( $can ? $this->base.'-settings' : 'tools.php' ),
			_x( 'Editorial Tools', 'Menu Title', 'geditorial-admin' ),
			( $can
				? _x( 'Tools', 'Menu Title', 'geditorial-admin' )
				: _x( 'Editorial Tools', 'Menu Title', 'geditorial-admin' )
			),
			'editorial_tools',
			$this->base.'-tools',
			[ $this, 'admin_tools_page' ]
		);

		$this->_hook_wp_submenu_page( 'roles', 'users.php',
			_x( 'Editorial Roles', 'Menu Title', 'geditorial-admin' ),
			NULL, 'editorial_roles' );

		$this->_hook_wp_submenu_page( 'imports', 'tools.php',
			_x( 'Editorial Imports', 'Menu Title', 'geditorial-admin' ),
			NULL, 'editorial_imports' );

		add_action( 'load-'.$hook_reports, [ $this, 'admin_reports_load' ] );
		add_action( 'load-'.$hook_settings, [ $this, 'admin_settings_load' ] );
		add_action( 'load-'.$hook_tools, [ $this, 'admin_tools_load' ] );

		if ( $this->is_thrift_mode() )
			return;

		foreach ( gEditorial()->modules( 'title' ) as $module ) {

			if ( ! $module->configure || in_array( $module->configure, [ 'tools', 'reports', 'imports' ], TRUE ) )
				continue;

			if ( $module->name == $this->module->name )
				continue;

			if ( FALSE !== $module->disabled )
				continue;

			if ( ! gEditorial()->enabled( $module->name, FALSE ) )
				continue;

			add_submenu_page(
				$this->base.'-settings',
				$module->title,
				$module->title,
				'editorial_settings',
				$this->base.'-settings&module='.$module->name,
				[ $this, 'admin_settings_page' ]
			);
		}
	}

	public function admin_reports_page()
	{
		$can = $this->cuc( 'reports' );
		$uri = Settings::reportsURL( FALSE, ! $can );
		$sub = Settings::sub( $can ? 'general' : 'overview' );

		$subs = [ 'overview' => _x( 'Overview', 'Reports Sub', 'geditorial-admin' ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Reports Sub', 'geditorial-admin' );

		$subs     = apply_filters( $this->hook_base( 'reports', 'subs' ), $subs, 'reports', $can );
		$messages = apply_filters( $this->hook_base( 'reports', 'messages' ), Settings::messages(), $sub. $can );

		Settings::wrapOpen( $sub, 'reports' );

			// Settings::headerTitle( _x( 'Editorial Reports', 'Page Title', 'geditorial-admin' ) );
			// Core\HTML::headerNav( $uri, $sub, $subs );
			Settings::sideOpen( _x( 'Reports', 'Page Title', 'geditorial-admin' ), $uri, $sub, $subs, FALSE );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->reports_overview( $uri );

			else if ( 'console' == $sub )
				gEditorial()->files( 'Layouts/console.reports' );

			else if ( has_action( $this->hook_base( 'reports', 'sub', $sub ) ) )
				do_action( $this->hook_base( 'reports', 'sub', $sub ), $uri, $sub );

			else
				Settings::cheatin();

			$this->settings_signature( 'reports' );

			Settings::sideClose();
		Settings::wrapClose();
	}

	// TODO: display wp_dashboard on overview
	protected function reports_overview( $uri )
	{
		do_action( $this->hook_base( 'reports', 'overview' ), $uri );
	}

	public function admin_tools_page()
	{
		$can = $this->cuc( 'tools' );
		$uri = Settings::toolsURL( FALSE, ! $can );
		$sub = Settings::sub( ( $can ? 'general' : 'overview' ) );

		$subs = [ 'overview' => _x( 'Overview', 'Tools Sub', 'geditorial-admin' ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Tools Sub', 'geditorial-admin' );

		$subs     = apply_filters( $this->hook_base( 'tools', 'subs' ), $subs, 'tools', $can );
		$messages = apply_filters( $this->hook_base( 'tools', 'messages' ), Settings::messages(), $sub, $can );

		if ( WordPress\User::isSuperAdmin() ) {
			$subs['options'] = _x( 'Options', 'Tools Sub', 'geditorial-admin' );
			$subs['console'] = _x( 'Console', 'Tools Sub', 'geditorial-admin' );
		}

		Settings::wrapOpen( $sub, 'tools' );

			// Settings::headerTitle( _x( 'Editorial Tools', 'Page Title', 'geditorial-admin' ) );
			// Core\HTML::headerNav( $uri, $sub, $subs );
			Settings::sideOpen( _x( 'Tools', 'Page Title', 'geditorial-admin' ), $uri, $sub, $subs, FALSE );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->tools_overview( $uri );

			else if ( 'options' == $sub )
				$this->tools_options( $uri );

			else if ( 'console' == $sub )
				gEditorial()->files( 'Layouts/console.tools' );

			else if ( has_action( $this->hook_base( 'tools', 'sub', $sub ) ) )
				do_action( $this->hook_base( 'tools', 'sub', $sub ), $uri, $sub );

			else
				Settings::cheatin();

			$this->settings_signature( 'tools' );

			Settings::sideClose();
		Settings::wrapClose();
	}

	protected function tools_overview( $uri )
	{
		do_action( $this->hook_base( 'tools', 'overview' ), $uri );
	}

	public function tools_overview_notice( $uri )
	{
		if ( function_exists( 'gnetwork_update_notice' ) )
			gnetwork_update_notice( GEDITORIAL_FILE );
	}

	public function tools_overview_readme( $uri )
	{
		if ( function_exists( 'gnetwork_github_readme' ) )
			gnetwork_github_readme( 'geminorum/geditorial' );
	}

	protected function tools_options( $uri )
	{
		WordPress\User::superAdminOnly();

		echo '<br />';

		if ( $options = get_option( 'geditorial_options' ) )
			Core\HTML::tableSide( $options );
		else
			Core\HTML::desc( gEditorial\Plugin::na() );
	}

	public function admin_roles_page()
	{
		$can = $this->cuc( 'roles' );
		$uri = Settings::rolesURL( FALSE, ! $can );
		$sub = Settings::sub( ( $can ? 'general' : 'overview' ) );

		$subs = [ 'overview' => _x( 'Overview', 'Roles Sub', 'geditorial-admin' ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Roles Sub', 'geditorial-admin' );

		$subs     = apply_filters( $this->hook_base( 'roles', 'subs' ), $subs, 'roles', $can );
		$messages = apply_filters( $this->hook_base( 'roles', 'messages' ), Settings::messages(), $sub, $can );

		if ( WordPress\User::isSuperAdmin() ) {
			$subs['console'] = _x( 'Console', 'Roles Sub', 'geditorial-admin' );
		}

		Settings::wrapOpen( $sub, 'roles' );

			// Settings::headerTitle( _x( 'Editorial Roles', 'Page Title', 'geditorial-admin' ) );
			// Core\HTML::headerNav( $uri, $sub, $subs );
			Settings::sideOpen( _x( 'Roles', 'Page Title', 'geditorial-admin' ), $uri, $sub, $subs, FALSE );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->roles_overview( $uri );

			else if ( 'console' == $sub )
				gEditorial()->files( 'Layouts/console.roles' );

			else if ( has_action( $this->hook_base( 'roles', 'sub', $sub ) ) )
				do_action( $this->hook_base( 'roles', 'sub', $sub ), $uri, $sub );

			else
				Settings::cheatin();

			$this->settings_signature( 'roles' );

			Settings::sideClose();
		Settings::wrapClose();
	}

	protected function roles_overview( $uri )
	{
		do_action( $this->hook_base( 'roles', 'overview' ), $uri );
	}

	public function admin_reports_load()
	{
		$sub = Settings::sub();

		if ( 'general' == $sub ) {

			// if ( ! empty( $_POST ) ) {
			// 	$this->nonce_check( 'roles', $sub );
			// }

			add_action( $this->hook_base( 'reports', 'sub', $sub ), [ $this, 'reports_sub' ], 10, 2 );

			$this->register_help_tabs( NULL, 'reports' );
		}

		do_action( $this->hook_base( 'reports', 'settings' ), $sub );
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

					if ( $result )
						Core\WordPress::redirectReferer( [
							'message' => 'upgraded',
							'count'   => count( $result ),
						] );

				} else if ( Tablelist::isAction( 'import_all_options' ) ) {

					if ( ! $file = WordPress\Media::handleImportUpload() )
						Core\WordPress::redirectReferer( 'wrong' );

					if ( ! $data = Parser::fromJSON_Legacy( Core\File::normalize( $file['file'] ) ) )
						Core\WordPress::redirectReferer( 'wrong' );

					if ( ! update_option( 'geditorial_options', $data, TRUE ) )
						Core\WordPress::redirectReferer( 'wrong' );

					Core\WordPress::redirectReferer( 'updated' );

				} else if ( Tablelist::isAction( 'download_all_options' ) ) {

					if ( FALSE !== ( $data = get_option( 'geditorial_options' ) ) )
						Core\Text::download(
							wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ),
							Core\File::prepName( sprintf( '%s-options.%s', $this->base, 'json' ) )
						);

					Core\WordPress::redirectReferer( 'wrong' );

				} else if ( Tablelist::isAction( 'delete_all_options' ) ) {

					if ( delete_option( 'geditorial_options' ) )
						Core\WordPress::redirectReferer( 'purged' );

				} else if ( Tablelist::isAction( 'custom_fields_empty' ) ) {

					if ( $post['empty_module'] && isset( gEditorial()->module( $post['empty_module'] )->meta_key ) ) {

						$result = WordPress\Database::deleteEmptyMeta( gEditorial()->module( $post['empty_module'] )->meta_key );

						if ( $result )
							Core\WordPress::redirectReferer( [
								'message' => 'emptied',
								'count'   => count( $result ),
							] );
					}

				} else if ( Tablelist::isAction( 'convert_connection_type' ) ) {

					if ( empty( $_POST['old_o2o_type'] )
						|| empty( $_POST['new_o2o_type'] ) )
							Core\WordPress::redirectReferer( 'wrong' );

					$result = Services\O2O\API::convertConnection( $_POST['old_o2o_type'], $_POST['new_o2o_type'] );

					if ( FALSE === $result )
						Core\WordPress::redirectReferer( 'wrong' );

					else
						Core\WordPress::redirectReferer( [
							'message' => 'converted',
							'count'   => $result,
						] );

				} else {

					Core\WordPress::redirectReferer( 'huh' );
				}
			}

			add_action( $this->hook_base( 'tools', 'sub', $sub ), [ $this, 'tools_sub' ], 10, 2 );

			$this->register_help_tabs( NULL, 'tools' );
		}

		do_action( $this->hook_base( 'tools', 'settings' ), $sub );

		$this->action( 'tools_overview', 1, 6, 'notice', $this->base );
		$this->action( 'tools_overview', 1, 9, 'readme', $this->base );
	}

	public function admin_roles_load()
	{
		$sub = Settings::sub();

		if ( 'general' == $sub ) {

			// if ( ! empty( $_POST ) ) {
			// 	$this->nonce_check( 'roles', $sub );
			// }

			add_action( $this->hook_base( 'roles', 'sub', $sub ), [ $this, 'roles_sub' ], 10, 2 );

			$this->register_help_tabs( NULL, 'roles' );
		}

		do_action( $this->hook_base( 'roles', 'settings' ), $sub );
	}

	// TODO: display download reports box for each module
	protected function render_reports_html( $uri, $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			self::cheatin();

		Core\HTML::h3( _x( 'General Editorial Reports', 'Header', 'geditorial-admin' ) );

		$action = $this->hook_base( 'reports' , 'general_summary' );

		if ( has_action( $action ) )
			do_action( $action, $uri );

		else
			Info::renderNoReportsAvailable();
	}

	// TODO: add button to use `wp_set_options_autoload()`
	protected function render_tools_html( $uri, $sub )
	{
		if ( ! $this->cuc( 'tools' ) )
			self::cheatin();

		$post = $this->get_current_form( [
			'empty_module' => 'meta',
		], 'tools' );

		$empty = TRUE;

		if ( current_user_can( 'manage_options' ) ) {

			if ( $this->_render_tools_html_options( $post ) )
				$empty = FALSE;
		}

		if ( current_user_can( 'edit_others_posts' ) ) {

			if ( $this->_render_tools_html_maintenance( $post ) )
				$empty = FALSE;

			if ( $this->_render_tools_html_o2o( $post ) )
				$empty = FALSE;
		}

		if ( $empty )
			Info::renderNoToolsAvailable();
	}

	private function _render_tools_html_options( $post )
	{
		echo '<table class="form-table">';
		echo '<tr><th scope="row">'._x( 'Options', 'Tools', 'geditorial-admin' ).'</th><td>';

			if ( $filesize = $this->settings_render_upload_field( '.json' ) ) {
				echo $this->wrap_open_buttons( '-import-all-options' );
					Settings::submitButton( 'import_all_options',
						_x( 'Imports All Options', 'Button', 'geditorial-admin' ), 'danger', TRUE );

					Core\HTML::desc( sprintf(
						/* translators: `%1$s`: file ext-type, `%2$s`: file size */
						_x( 'Imports all editorial option data in %1$s file from your computer. Maximum upload size: %2$s', 'Message', 'geditorial-admin' ),
						Core\HTML::code( 'json' ),
						Core\HTML::code( Core\HTML::wrapLTR( $filesize ) )
					), FALSE );

				echo '</p>';
			}

			echo $this->wrap_open_buttons( '-download-all-options' );
				Settings::submitButton( 'download_all_options',
					_x( 'Download All Options', 'Button', 'geditorial-admin' ) );

				Core\HTML::desc( sprintf(
					/*translators: %s: file ext-type */
					_x( 'Exports all editorial option data as %s file for you to download.', 'Message', 'geditorial-admin' ),
					Core\HTML::code( 'json' )
				), FALSE );
			echo '</p>';

			echo $this->wrap_open_buttons( '-upgrade-old-options' );
				Settings::submitButton( 'upgrade_old_options',
					_x( 'Upgrade Old Options', 'Button', 'geditorial-admin' ) );

				Core\HTML::desc( _x( 'Checks for old options and upgrade them. Also deletes the old options.', 'Message', 'geditorial-admin' ), FALSE );
			echo '</p>';

			if ( Core\WordPress::isDev() || WordPress\User::isSuperAdmin() ) {
				echo $this->wrap_open_buttons( '-delete-all-options' );
					Settings::submitButton( 'delete_all_options',
						_x( 'Delete All Options', 'Button', 'geditorial-admin' ), 'danger', TRUE );

					Core\HTML::desc( _x( 'Tries to delete all editorial options on the current site.', 'Message', 'geditorial-admin' ), FALSE );
				echo '</p>';
			}

		echo '</td></tr></table>';

		return TRUE;
	}

	private function _render_tools_html_maintenance( $post )
	{
		Core\HTML::h3( _x( 'Maintenance Tasks', 'Header', 'geditorial-admin' ) );

		echo '<table class="form-table">';

		echo '<tr><th scope="row">'._x( 'Empty Meta Fields', 'Tools', 'geditorial-admin' ).'</th><td>';

			$this->do_settings_field( [
				'type'         => 'select',
				'field'        => 'empty_module',
				'values'       => gEditorial()->list_modules(),
				'default'      => $post['empty_module'],
				'cap'          => 'edit_others_posts',
				'option_group' => 'tools',
			] );

			Settings::submitButton( 'custom_fields_empty', _x( 'Empty', 'Button', 'geditorial-admin' ), 'danger', TRUE );
			Core\HTML::desc( _x( 'Deletes empty meta values. This solves common problems with imported posts.', 'Message', 'geditorial-admin' ) );

		echo '</td></tr></table>';

		return TRUE;
	}

	private function _render_tools_html_o2o( $post )
	{
		echo '<table class="form-table">';
		echo '<tr><th scope="row">'._x( 'Orphan Connections', 'Tools', 'geditorial-admin' ).'</th><td>';

		// $counts = Services\O2O\API::getConnectionCounts();

		if ( empty( $counts ) ) {

			Core\HTML::desc( _x( 'No connection types found.', 'Message', 'geditorial-admin' ), TRUE, '-empty' );

		} else {

			$types = Services\O2O\ConnectionTypeFactory::get_all_instances();
			$empty = TRUE;

			foreach ( $counts as $type => $count ) {

				if ( Services\O2O\API::type( $type ) )
					continue;

				$empty = FALSE;

				echo Core\HTML::wrapLTR( '<code>'.$type.'</code>' );
				echo ' &mdash; ('.WordPress\Strings::getCounted( $count ).') &mdash; ';

				$this->do_settings_field( [
					'type'         => 'select',
					'field'        => 'new_o2o_type',
					'values'       => array_keys( $types ),
					// 'default'      => $post['empty_module'],
					'option_group' => 'tools',
				] );

				Settings::submitButton( 'convert_connection_type', _x( 'Convert', 'Button', 'geditorial-admin' ), 'danger', TRUE );
			}

			if ( $empty )
				Core\HTML::desc( _x( 'No orphaned connection types found in the database.', 'Message', 'geditorial-admin' ), TRUE, '-empty' );
		}

		echo '</td></tr></table>';

		return TRUE;
	}

	// TODO: add buttons to append `{$this->base}_{$context}` to current role
	// FIXME: move here `render_tools_html` from `Users` Module
	protected function render_roles_html( $uri, $sub )
	{
		if ( ! $this->cuc( 'roles' ) )
			self::cheatin();

		Core\HTML::h3( _x( 'General Editorial Roles', 'Header', 'geditorial-admin' ) );

		$action = $this->hook_base( 'roles' , 'general_summary' );

		if ( has_action( $action ) )
			do_action( $action, $uri );

		else
			Info::renderNoRolesAvailable();
	}

	public function admin_imports_load()
	{
		$sub = Settings::sub();

		if ( 'general' == $sub ) {

			add_action( $this->hook_base( 'imports', 'sub', 'general' ), [ $this, 'imports_sub' ], 10, 2 );

			$this->register_help_tabs( NULL, 'imports' );
		}

		do_action( $this->hook_base( 'imports', 'settings' ), $sub );
	}

	public function admin_imports_page()
	{
		$can = $this->cuc( 'imports' );
		$uri = Settings::importsURL( FALSE );
		$sub = Settings::sub( $can ? 'general' : 'overview' );

		$subs = [ 'overview' => _x( 'Overview', 'Imports Sub', 'geditorial-admin' ) ];

		if ( $can )
			$subs['general'] = _x( 'General', 'Imports Sub', 'geditorial-admin' );

		$subs     = apply_filters( $this->hook_base( 'imports', 'subs' ), $subs, 'imports', $can );
		$messages = apply_filters( $this->hook_base( 'imports', 'messages' ), Settings::messages(), $sub, $can );

		if ( $can )
			$subs['data'] = _x( 'Data', 'Imports Sub', 'geditorial-admin' );

		Settings::wrapOpen( $sub, 'imports' );

			// Settings::headerTitle( _x( 'Editorial Imports', 'Page Title', 'geditorial-admin' ) );
			// Core\HTML::headerNav( $uri, $sub, $subs );
			Settings::sideOpen( _x( 'Imports', 'Page Title', 'geditorial-admin' ), $uri, $sub, $subs, FALSE );
			Settings::message( $messages );

			if ( 'overview' == $sub )
				$this->imports_overview( $uri );

			else if ( 'data' == $sub )
				$this->imports_data( $uri );

			else if ( 'console' == $sub )
				gEditorial()->files( 'Layouts/console.imports' );

			else if ( has_action( $this->hook_base( 'imports', 'sub', $sub ) ) )
				do_action( $this->hook_base( 'imports', 'sub', $sub ), $uri, $sub );

			else
				Settings::cheatin();

			$this->settings_signature( 'imports' );

			Settings::sideClose();
		Settings::wrapClose();
	}

	protected function imports_overview( $uri )
	{
		do_action( $this->hook_base( 'imports', 'overview' ), $uri );
	}

	// TODO: download link
	protected function imports_data( $uri )
	{
		foreach ( apply_filters( $this->hook_base( 'imports' , 'data_summary' ), [] ) as $row ) {

			$data = self::atts( [
				'title'       => _x( 'Untitled', 'Imports: Data Summary', 'geditorial-admin' ),
				'description' => '',
				'path'        => '',
				'updated'     => '',
				'sources'     => [],
			], $row );

			$data['updated']     = Datetime::htmlHumanTime( $data['updated'] );
			$data['description'] = WordPress\Strings::prepDescription( $data['description'] );
			$data['links']       = count( (array) $data['sources'] );

			echo $this->wrap_open( '-view -imports-data-summary' );
				$this->render_view( 'imports-data-summary', $data );
			echo '</div>';
		}

		do_action( $this->hook_base( 'imports', 'data' ), $uri );
	}

	protected function render_imports_html( $uri, $sub )
	{
		if ( ! $this->cuc( 'imports' ) )
			self::cheatin();

		Core\HTML::h3( _x( 'General Editorial Imports', 'Header', 'geditorial-admin' ) );

		$action = $this->hook_base( 'imports' , 'general_summary' );

		if ( has_action( $action ) )
			do_action( $action, $uri );

		else
			Info::renderNoImportsAvailable();
	}

	public function settings_sidebox( $sub, $uri )
	{
		if ( $user = gEditorial()->user() ) {

			$name = get_userdata( $user )->display_name;
			$edit = Core\WordPress::getUserEditLink( $user );

			/* translators: `%s`: user link placeholder */
			Core\HTML::desc( sprintf( _x( 'Editorial Site User Is %s', 'Sidebox: Message', 'geditorial-admin' ),
				$edit ? Core\HTML::link( $name, $edit, TRUE ) : $name ) );

		} else {

			Core\HTML::desc( _x( 'Editorial Site User is not available!', 'Sidebox: Message', 'geditorial-admin' ), TRUE, '-empty' );
		}
	}

	public function do_ajax()
	{
		if ( ! $this->cuc( 'settings' ) )
			self::cheatin();

		$post = self::unslash( $_POST );
		$what = empty( $post['what'] ) ? 'nothing': trim( $post['what'] );

		switch ( $what ) {

			case 'state':

				Ajax::checkReferer( $this->hook() );

				if ( ! isset( $_POST['doing'], $_POST['name'] ) )
					Ajax::errorMessage( _x( 'No action or name!', 'Ajax Notice', 'geditorial-admin' ) );

				if ( ! $module = gEditorial()->get_module_by( 'name', sanitize_key( $_POST['name'] ) ) )
					Ajax::errorMessage( _x( 'Cannot find the module!', 'Ajax Notice', 'geditorial-admin' ) );

				$enabled = 'enable' == sanitize_key( $_POST['doing'] ) ? TRUE : FALSE;

				if ( gEditorial()->update_module_option( $module->name, 'enabled', $enabled ) )
					Ajax::successMessage( _x( 'Module state successfully changed.', 'Ajax Notice', 'geditorial-admin' ) );
				else
					Ajax::errorMessage( _x( 'Cannot change module state!', 'Ajax Notice', 'geditorial-admin' ) );
		}

		Ajax::errorWhat();
	}

	public function admin_settings_page()
	{
		if ( ! $key = self::req( 'module', FALSE ) )
			$module = $this->module;

		else if ( ! $module = gEditorial()->get_module_by( 'name', $key ) )
			return Settings::wrapError( Core\HTML::warning( _x( 'Not a registered Editorial module.', 'Page Notice', 'geditorial-admin' ), FALSE ) );

		if ( ! gEditorial()->enabled( $module->name, FALSE ) )
			return Settings::wrapError( Core\HTML::warning( _x( 'Module not enabled. Please enable it from the Editorial settings page.', 'Page Notice', 'geditorial-admin' ), FALSE ) );

		gEditorial()->module( $module->name )->settings_header();
		gEditorial()->module( $module->name )->settings_from();
		gEditorial()->module( $module->name )->settings_footer();
	}

	public function settings_from()
	{
		$stage = Helper::const( 'WP_STAGE', 'production' );  // 'development'

		echo '<div class="modules -list">';

		foreach ( gEditorial()->modules( 'title' ) as $module ) {

			// skip if `config`
			if ( $this->module->name === $module->name )
				continue;

			if ( ! Helper::moduleLoading( $module, $stage ) )
				continue;

			$enabled = gEditorial()->enabled( $module->name, FALSE );

			echo '<div id="wrap-module-'.$module->name.'" '
				.'data-module="'.$module->name.'" class="module '
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

				echo Core\HTML::wrap( $module->disabled, 'actions -danger' );
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

		do_action( $this->hook_base( 'settings', 'load' ), $module );

		$this->enqueue_asset_js( [], NULL, [ 'jquery', Scripts::pkgListJS() ] );
		Scripts::enqueueAdminSelectAll();
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
			Core\WordPress::redirectReferer( [ 'message' => 'disabled', 'module' => FALSE ] );

		else
			Core\WordPress::redirectReferer( 'error' );
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

		Core\WordPress::redirectReferer( 'resetting' );
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

		$option  = $this->hook_base( $module->name );
		$posted  = empty( $_POST[$option] ) ? [] : $_POST[$option];
		$options = gEditorial()->module( $module->name )->settings_validate( $posted );

		$options['enabled'] = TRUE;

		gEditorial()->update_all_module_options( $module->name, $options );

		Core\WordPress::redirectReferer();
	}
}

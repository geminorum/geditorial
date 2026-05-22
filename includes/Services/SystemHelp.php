<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

class SystemHelp extends gEditorial\Service
{
	/**
	 * Returns Documentation URL for the module.
	 *
	 * @param boolean|object $module
	 * @return string
	 */
	public static function getModuleDocsURL( $module = FALSE )
	{
		if ( ! GEDITORIAL_WIKI_URL )
			return FALSE;

		return FALSE === $module || 'config' == $module->name
			? GEDITORIAL_WIKI_URL
			: sprintf( '%s/Modules-%s',
				Core\URL::untrail( GEDITORIAL_WIKI_URL ),
				Modulation::moduleSlug( $module->name )
			);
	}

	public static function sidebar( $list )
	{
		if ( ! is_array( $list ) )
			return $list;

		$html = '';

		foreach ( $list as $link )
			$html.= '<li>'.Core\HTML::link( $link['title'], $link['url'], TRUE ).'</li>';

		return $html ? Core\HTML::wrap( '<ul>'.$html.'</ul>', '-help-sidebar' ) : FALSE;
	}

	/**
	 * Returns the help content for given module
	 *
	 * @param boolean|object $module
	 * @return array
	 */
	public static function content( $module = FALSE )
	{
		if ( ! function_exists( 'gnetwork_github' ) )
			return [];

		$wikihome = [
			'module'   => $module,
			'id'       => 'geditorial-wikihome',
			'callback' => [ __CLASS__, 'add_help_tab_home_callback' ],
			'title'    => _x( 'Editorial Wiki', 'Service: System Help: Help Content Title', 'geditorial-admin' ),
		];

		if ( FALSE === $module || 'config' === $module->name )
			return [ $wikihome ];

		$wikimodule = [
			'module'   => $module,
			'id'       => 'geditorial-'.$module->name.'-wikihome',
			'callback' => [ __CLASS__, 'add_help_tab_module_callback' ],
			'title'    => sprintf(
				/* translators: `%s`: module title */
				_x( '%s Wiki', 'Service: System Help: Help Content Title', 'geditorial-admin' ),
				$module->title
			),
		];

		return [
			$wikimodule,
			$wikihome,
		];
	}

	public static function add_help_tab_home_callback( $screen, $tab )
	{
		$tab['module'] = FALSE;
		self::add_help_tab_module_callback( $screen, $tab );
	}

	public static function add_help_tab_module_callback( $screen, $tab )
	{
		if ( ! function_exists( 'gnetwork_github' ) )
			return;

		$module = empty( $tab['module'] ) ? FALSE : $tab['module'];

		$page = FALSE === $module || 'config' === $module->name
			? 'Home'
			: 'Modules-'.Modulation::moduleSlug( $module->name );

		echo gnetwork_github( [
			'repo'    => 'geminorum/geditorial',
			'type'    => 'wiki',
			'page'    => $page,
			'context' => 'help_tab',
		] );
	}
}

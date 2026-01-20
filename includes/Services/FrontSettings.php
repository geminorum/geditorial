<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Controls;
use geminorum\gEditorial\WordPress;

/**
	Site Title & Tagline (title_tagline): 20
	Colors (`colors`): 40
	Header Image (`header_image`): 60
	Background Image (`background_image`): 80
	Menus (Panel) (`nav_menus`): 100
	Widgets (Panel) (`widgets`): 110
	Static Front Page (`static_front_page`): 120
	default: 160
	Additional CSS (`custom_css`): 200
**/

class FrontSettings extends gEditorial\Service
{
	const MAIN_PANEL   = 'geditorial';
	const MAIN_SECTION = 'geditorial_general';
	const MORE_SECTION = 'geditorial_andmore';

	public static function setup()
	{
		add_action( 'customize_register', [ __CLASS__, 'customize_register' ] );

		if ( is_admin() )
			return;

		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_menu' ], 9999, 1 );
	}

	/**
	 * Registers `Customizer` main panel and sections for this service.
	 *
	 * Core controls include `text`, `checkbox`, `textarea`, `radio`, `select`,
	 * and `dropdown-pages`. Additional input types such as `email`, `url`,
	 * `number`, `hidden`, and `date` are supported implicitly. Default is `text`.
	 * @link https://developer.wordpress.org/themes/customize-api/
	 *
	 * @param object $manager
	 * @return void
	 */
	public static function customize_register( $manager )
	{
		$system = gEditorial\Plugin::system();

		$manager->add_panel( static::MAIN_PANEL, [
			'title'          => $system ?: _x( 'Editorial', 'Customizer: Panel Title', 'geditorial-admin' ),
			'description'    => self::filters( 'front_settings_description', '', $system ),
			'capability'     => 'edit_theme_options',
			'priority'       => 400,
			'theme_supports' => '',

			'auto_expand_sole_section' => ! WordPress\IsIt::Dev(),
		] );

		$manager->add_section( static::MAIN_SECTION, [
			'panel'              => static::MAIN_PANEL,
			'title'              => _x( 'General', 'Customizer: Section Title', 'geditorial-admin' ),
			'description'        => _x( 'Editorial General Customizations', 'Customizer: Section Description', 'geditorial-admin' ),
			'description_hidden' => ! WordPress\IsIt::Dev(),
			'priority'           => 10,
		] );

		$manager->add_section( static::MORE_SECTION, [
			'panel'              => static::MAIN_PANEL,
			'title'              => _x( 'And More', 'Customizer: Section Title', 'geditorial-admin' ),
			'description'        => _x( 'More info about Editorial System', 'Customizer: Section Description', 'geditorial-admin' ),
			'description_hidden' => ! WordPress\IsIt::Dev(),
			'priority'           => 99,
		] );

		$welcome = self::hook( 'more', 'welcome' );
		$manager->add_setting( $welcome, [
			'default'    => NULL,
			'capability' => gEditorial\Plugin::CAPABILITY_SETTINGS,
		] );

		$manager->add_control(
			new Controls\Welcome(
				$manager,
				$welcome,
				[
					'label'    => _x( 'Looking for more options?', 'Customizer: Control label', 'geditorial-admin' ),
					'section'  => static::MORE_SECTION,
					'settings' => $welcome,
					'priority' => 1,
				]
			)
		);

		do_action_ref_array(
			implode( '_', [ static::BASE, 'front_settings', 'customize' ] ),
			[
				&$manager,
				static::MAIN_PANEL   ,   // $main_panel
				static::MAIN_SECTION ,   // $main_section
				static::MORE_SECTION ,   // $more_section
				$welcome             ,   // $welcome_control
			]
		);
	}

	public static function admin_bar_menu( $wp_admin_bar )
	{
		if ( ! is_user_logged_in() )
			return;

		if ( ! current_user_can( gEditorial\Plugin::CAPABILITY_SETTINGS ) )
			return;

		$wp_admin_bar->add_menu( [
			'parent' => 'appearance', // 'site-name',
			'id'     => self::classs( 'settings' ),
			'href'   => gEditorial\Settings::getURLbyContext( 'settings', TRUE ),
			'title'  => sprintf(
				/* translators: `%s`: system string */
				_x( '%s Settings', 'Service: Front Settings', 'geditorial-admin' ),
				gEditorial\Plugin::system()
			),
			'meta' => [
				'class' => self::classs( 'adminbar', 'node' ),
			],
		] );
	}
}

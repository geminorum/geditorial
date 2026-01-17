<?php namespace geminorum\gEditorial\Services;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Controls;
use geminorum\gEditorial\WordPress;

class FrontSettings extends gEditorial\Service
{
	public static function setup()
	{
		add_action( 'customize_register', [ __CLASS__, 'customize_register' ] );
	}

	/**
	 * Registers `Customizer` setting.
	 *
	 * Core controls include `text`, `checkbox`, `textarea`, `radio`, `select`,
	 * and `dropdown-pages`. Additional input types such as `email`, `url`,
	 * `number`, `hidden`, and `date` are supported implicitly. Default is `text`.
	 *
	 * @see https://developer.wordpress.org/themes/customize-api/customizer-objects/
	 * @see https://developer.wordpress.org/reference/classes/wp_customize_control/
	 * @see https://developer.wordpress.org/themes/customize-api/
	 *
	 * @source https://www.billerickson.net/code/customizer-setting/
	 *
	 * @param object $manager
	 * @return void
	 */
	public static function customize_register( $manager )
	{
		$manager->add_panel( static::BASE, [
		$system = gEditorial\Plugin::system();

			'title'          => $system ?: _x( 'Editorial', 'Customizer: Panel Title', 'geditorial-admin' ),
			'description'    => self::filters( 'front_settings_description', '', $system ),
			'capability'     => 'edit_theme_options',
			'priority'       => 400,
			'theme_supports' => '',

			'auto_expand_sole_section' => ! WordPress\IsIt::Dev(),
		] );

		$general = self::hook( 'general' );
		$welcome = self::hook( 'general', 'welcome' );

		$manager->add_section( $general, [
			'panel'              => static::BASE,
			'title'              => _x( 'General', 'Customizer: Section Title', 'geditorial-admin' ),
			'description'        => _x( 'Editorial General Customizations', 'Customizer: Section Description', 'geditorial-admin' ),
			'description_hidden' => TRUE,
			'priority'           => 10,
		] );

		$manager->add_setting( $welcome, [
			'default'           => NULL,
			'sanitize_callback' => 'sanitize_text_field',
			'capability'        => gEditorial\Plugin::CAPABILITY_CUSTOMS,
		] );

		$manager->add_control(
			new Controls\Welcome(
				$manager,
				$welcome,
				[
					'label'    => _x( 'Looking for more options?', 'Customizer: Control label', 'geditorial-admin' ),
					'section'  => $general,
					'settings' => $welcome,
					'priority' => 1,
				]
			)
		);

		self::actions( 'front_settings_customize',
			static::BASE,  // panel
			$general    ,  // section
			$welcome    ,  // cotrol
		);
	}
}

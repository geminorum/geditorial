<?php namespace geminorum\gEditorial\Controls;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

// @source https://github.com/WPTT/customize-section-button

#[\AllowDynamicProperties]
class SectionButton extends \WP_Customize_Section
{
	// const CONTROL = 'section_button';

	/**
	 * The type of customize section being rendered.
	 * @var string
	 */
	public $type = 'geditorial-section-button';

	/**
	 * Custom button text to output.
	 * @var string
	 */
	public $button_text = '';

	/**
	 * Custom button URL to output.
	 * @var string
	 */
	public $button_url = '';

	/**
	 * Default priority of the section.
	 * @var string
	 */
	public $priority = 999;

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @return array
	 */
	public function json()
	{
		$json       = parent::json();
		$theme      = wp_get_theme();
		$button_url = $this->button_url;

		// Fall back to the `Theme URI` defined in `style.css`.
		if ( ! $button_url && $theme->get( 'ThemeURI' ) ) {

			$button_url = $theme->get( 'ThemeURI' );

		// Fall back to the `Author URI` defined in `style.css`.
		} else if ( ! $button_url && $theme->get( 'AuthorURI' ) ) {

			$button_url = $theme->get( 'AuthorURI' );
		}

		$json['button_text'] = $this->button_text ? $this->button_text : $theme->get( 'Name' );
		$json['button_url']  = esc_url( $button_url );

		return $json;
	}

	/**
	 * Outputs the `Underscore.js` template.
	 *
	 * @return void
	 */
	protected function render_template()
	{
		?><li id="accordion-section-{{ data.id }}" class="accordion-section control-section control-section-{{ data.type }} cannot-expand">

			<h3 class="accordion-section-title">
				{{ data.title }}

				<# if ( data.button_text && data.button_url ) { #>
					<a href="{{ data.button_url }}" class="button button-small button-secondary alignright" target="_blank" rel="external nofollow noopener noreferrer">{{ data.button_text }}</a>
				<# } #>
			</h3>
		</li><?php
	}
}

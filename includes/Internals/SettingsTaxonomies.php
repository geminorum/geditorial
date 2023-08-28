<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait SettingsTaxonomies
{
	/**
	 * Retrieves settings option for the target taxonomies.
	 *
	 * @param  string $target
	 * @param  array  $fallback
	 * @return array  $posttypes
	 */
	public function get_setting_taxonomies( $target, $fallback = [] )
	{
		return $target ? $this->get_setting( sprintf( '%s_taxonomies', $target ), $fallback ): $fallback;
	}

	/**
	 * Checks if taxonomy is in settings option for the target taxonomies.
	 *
	 * @param  string $taxonomy
	 * @param  string $target
	 * @param  array  $fallback
	 * @return array  $posttypes
	 */
	public function in_setting_taxonomies( $taxonomy, $target, $fallback = FALSE )
	{
		return ( $taxonomy && in_array( $taxonomy, $this->get_setting_taxonomies( $target ) ) ) || $fallback;
	}

	public function register_settings_taxonomies_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'taxonomies_title', 'post', 'settings',
				_x( 'Enable for Taxonomies', 'Module', 'geditorial' ) );

		$option = $this->base.'_'.$this->module->name;

		Settings::addModuleSection( $option, [
			'id'            => $option.'_taxonomies',
			'section_class' => 'taxonomies_option_section',
		] );

		add_settings_field( 'taxonomies',
			$title,
			[ $this, 'settings_taxonomies_option' ],
			$option,
			$option.'_taxonomies'
		);
	}

	public function settings_taxonomies_option()
	{
		if ( $before = $this->get_string( 'taxonomies_before', 'post', 'settings', NULL ) )
			Core\HTML::desc( $before );

		echo Settings::tabPanelOpen();

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'tax-'.$taxonomy,
				'name'    => $this->base.'_'.$this->module->name.'[taxonomies]['.$taxonomy.']',
				'checked' => ! empty( $this->options->taxonomies[$taxonomy] ),
			] );

			$html.= '&nbsp;'.Core\HTML::escape( $label );
			$html.= ' &mdash; <code>'.$taxonomy.'</code>';

			Core\HTML::label( $html, 'tax-'.$taxonomy, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'taxonomies_after', 'post', 'settings', NULL ) )
			Core\HTML::desc( $after );
	}

	// enabled taxonomies for this module
	public function taxonomies( $taxonomies = NULL )
	{
		if ( is_null( $taxonomies ) )
			$taxonomies = [];

		else if ( ! is_array( $taxonomies ) )
			$taxonomies = [ $this->constant( $taxonomies ) ];

		if ( empty( $this->options->taxonomies ) )
			return $taxonomies;

		$taxonomies = array_merge( $taxonomies, array_keys( array_filter( $this->options->taxonomies ) ) );

		return did_action( 'wp_loaded' )
			? array_filter( $taxonomies, 'taxonomy_exists' )
			: $taxonomies;
	}

	public function taxonomy_supported( $taxonomy )
	{
		return $taxonomy && in_array( $taxonomy, $this->taxonomies(), TRUE );
	}

	public function list_taxonomies( $pre = NULL, $taxonomies = NULL, $capability = NULL, $args = [ 'show_ui' => TRUE ], $user_id = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All Taxonomies', 'Module', 'geditorial' ) ];

		$all = WordPress\Taxonomy::get( 0, $args, FALSE, $capability, $user_id );

		foreach ( $this->taxonomies( $taxonomies ) as $taxonomy ) {

			if ( array_key_exists( $taxonomy, $all ) )
				$pre[$taxonomy] = $all[$taxonomy];

			// only if no checks required
			else if ( is_null( $capability ) )
				$pre[$taxonomy] = $taxonomy;
		}

		return $pre;
	}

	public function all_taxonomies( $args = [ 'show_ui' => TRUE ], $exclude_extra = [] )
	{
		return Core\Arraay::stripByKeys( WordPress\Taxonomy::get( 0, $args ), Core\Arraay::prepString( $this->taxonomies_excluded( $exclude_extra ) ) );
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded', Settings::taxonomiesExcluded( $extra ) );
	}

	protected function _hook_taxonomies_excluded( $constant, $module = NULL )
	{
		$hook = sprintf( '%s_%s_taxonomies_excluded', $this->base, is_null( $module ) ? $this->module->name : $module );
		$this->filter_append( $hook, $this->constant( $constant ) );
	}
}

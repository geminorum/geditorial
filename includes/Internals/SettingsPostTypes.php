<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait SettingsPostTypes
{

	/**
	 * Retrieves settings option for the target posttypes.
	 * NOTE: common targets: `subcontent`, `parent`, `directed`
	 *
	 * @param  string $target
	 * @param  array  $fallback
	 * @return array  $posttypes
	 */
	public function get_setting_posttypes( $target, $fallback = [] )
	{
		return $target ? $this->get_setting( sprintf( '%s_posttypes', $target ), $fallback ) : $fallback;
	}

	/**
	 * Checks if posttype is in settings option for the target posttypes.
	 * NOTE: common targets: `subcontent`, `parent`, `directed`
	 *
	 * @param  string $posttype
	 * @param  string $target
	 * @param  array  $fallback
	 * @return array  $posttypes
	 */
	public function in_setting_posttypes( $posttype, $target, $fallback = FALSE )
	{
		return ( $posttype && in_array( $posttype, $this->get_setting_posttypes( $target ) ) ) || $fallback;
	}

	public function register_settings_posttypes_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'post_types_title', 'post', 'settings',
				_x( 'Enable for Post Types', 'Module', 'geditorial' ) );

		$option = $this->base.'_'.$this->module->name;

		Settings::addModuleSection( $option, [
			'id'            => $option.'_posttypes',
			'section_class' => 'posttypes_option_section',
		] );

		add_settings_field( 'post_types',
			$title,
			[ $this, 'settings_posttypes_option' ],
			$option,
			$option.'_posttypes'
		);
	}

	public function settings_posttypes_option()
	{
		if ( $before = $this->get_string( 'post_types_before', 'post', 'settings', NULL ) )
			Core\HTML::desc( $before );

		echo Settings::tabPanelOpen();

		foreach ( $this->all_posttypes() as $posttype => $label ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$posttype,
				'name'    => $this->base.'_'.$this->module->name.'[post_types]['.$posttype.']',
				'checked' => ! empty( $this->options->post_types[$posttype] ),
			] );

			$html.= '&nbsp;'.Core\HTML::escape( $label );
			$html.= ' &mdash; <code>'.$posttype.'</code>';

			Core\HTML::label( $html, 'type-'.$posttype, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'post_types_after', 'post', 'settings', NULL ) )
			Core\HTML::desc( $after );
	}

	// enabled post types for this module
	public function posttypes( $posttypes = NULL )
	{
		if ( is_null( $posttypes ) )
			$posttypes = [];

		else if ( ! is_array( $posttypes ) )
			$posttypes = [ $this->constant( $posttypes ) ];

		if ( empty( $this->options->post_types ) )
			return $posttypes;

		$posttypes = array_merge( $posttypes, array_keys( array_filter( $this->options->post_types ) ) );

		return did_action( 'wp_loaded' )
			? array_filter( $posttypes, 'post_type_exists' )
			: $posttypes;
	}

	public function posttype_supported( $posttype )
	{
		return $posttype && in_array( $posttype, $this->posttypes(), TRUE );
	}

	public function list_posttypes( $pre = NULL, $posttypes = NULL, $capability = NULL, $args = [ 'show_ui' => TRUE ], $user_id = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All PostTypes', 'Module', 'geditorial' ) ];

		$all = WordPress\PostType::get( 0, $args, $capability, $user_id );

		foreach ( $this->posttypes( $posttypes ) as $posttype ) {

			if ( array_key_exists( $posttype, $all ) )
				$pre[$posttype] = $all[$posttype];

			// only if no checks required
			else if ( is_null( $capability ) && post_type_exists( $posttype ) )
				$pre[$posttype] = $posttype;
		}

		return $pre;
	}

	public function all_posttypes( $args = [ 'show_ui' => TRUE ], $exclude_extra = [] )
	{
		return Core\Arraay::stripByKeys( WordPress\PostType::get( 0, $args ), Core\Arraay::prepString( $this->posttypes_excluded( $exclude_extra ) ) );
	}

	// DEFAULT METHOD
	protected function posttypes_excluded( $extra = [] )
	{
		$extra = (array) $extra;

		if ( method_exists( $this, 'paired_get_constants' ) ) {

			if ( $paired = $this->paired_get_constants() )
				$extra[] = $this->constant( $paired[0] );
		}

		return $this->filters( 'posttypes_excluded', Settings::posttypesExcluded( $extra ) );
	}

	// DEFAULT METHOD
	protected function posttypes_parents( $extra = [] )
	{
		return $this->filters( 'posttypes_parents', Settings::posttypesParents( $extra ) );
	}

	/**
	 * Gets posttype parents for use in settings.
	 *
	 * @param  array        $extra
	 * @param  null|string  $capability
	 * @return array        $posttypes
	 */
	protected function get_settings_posttypes_parents( $extra = [], $capability = NULL )
	{
		$list       = [];
		$posttypes = WordPress\PostType::get( 0, [ 'show_ui' => TRUE ], $capability );

		foreach ( $this->posttypes_parents( $extra ) as $posttype ) {

			if ( array_key_exists( $posttype, $posttypes ) )
				$list[$posttype] = $posttypes[$posttype];

			// only if no checks required
			else if ( is_null( $capability ) && post_type_exists( $posttype ) )
				$list[$posttype] = $posttype;
		}

		return $list;
	}
}

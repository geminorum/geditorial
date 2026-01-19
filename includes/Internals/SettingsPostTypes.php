<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait SettingsPostTypes
{
	/**
	 * Retrieves settings option for the target post-types.
	 * NOTE: common targets: `subcontent`, `parent`, `directed`, `supported`
	 *
	 * @param string $target
	 * @param array $fallback
	 * @return array
	 */
	public function get_setting_posttypes( $target, $fallback = [] )
	{
		return $target ? $this->get_setting( sprintf( '%s_posttypes', $target ), $fallback ) : $fallback;
	}

	/**
	 * Checks if post-type is in settings option for the target post-types.
	 * NOTE: common targets: `subcontent`, `parent`, `directed`, `supported`
	 *
	 * @param string $posttype
	 * @param string $target
	 * @param array $fallback
	 * @return array
	 */
	public function in_setting_posttypes( $posttype, $target, $fallback = FALSE )
	{
		return ( $posttype && in_array( $posttype, $this->get_setting_posttypes( $target ) ) ) || $fallback;
	}

	public function register_settings_posttypes_option( $title = NULL )
	{
		if ( is_null( $title ) )
			$title = $this->get_string( 'post_types_title', FALSE, 'settings',
				_x( 'Supported Post-types', 'Internal: SettingsPostTypes: Field Title', 'geditorial-admin' ) );

		$option = $this->hook_base( $this->module->name );

		gEditorial\Settings::addModuleSection( $option, [
			'id'            => $option.'_posttypes',
			'title'         => _x( 'Post-types', 'Internal: SettingsPostTypes: Section Title', 'geditorial-admin' ),
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
		if ( $before = $this->get_string( 'post_types_before', FALSE, 'settings', NULL ) )
			Core\HTML::desc( $before );

		echo gEditorial\Settings::tabPanelOpen( FALSE, '-panel-expanded' );

		foreach ( $this->all_posttypes() as $posttype => $label ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => 'type-'.$posttype,
				'name'    => $this->hook_base( $this->module->name ).'[post_types]['.$posttype.']',
				'checked' => ! empty( $this->options->post_types[$posttype] ),
			] );

			$html.= '&nbsp;'.Core\HTML::escape( $label );
			$html.= ' &mdash; <code>'.$posttype.'</code>';

			Core\HTML::label( $html, 'type-'.$posttype, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'post_types_after', FALSE, 'settings', NULL ) )
			Core\HTML::desc( $after );
	}

	// Enabled post-types for this module
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

	public function posttype_woocommerce( $posttype, $supported_default = TRUE )
	{
		return $posttype
			&& $posttype === WordPress\WooCommerce::PRODUCT_POSTTYPE
			&& $this->get_setting( 'woocommerce_support', $supported_default );
	}

	public function posttype_anchor( $posttype )
	{
		$posttype = $this->constant( $posttype, $posttype );
		$object   = WordPress\PostType::object( $posttype );

		if ( ! empty( $object->rest_base ) )
			return $object->rest_base;

		return Core\L10n::pluralize( $posttype );
	}

	public function screen_posttype_supported( $screen, $base = [ 'edit', 'post' ] )
	{
		if ( $base && ! in_array( $screen->base, (array) $base, TRUE ) )
			return FALSE;

		return $this->posttype_supported( $screen->post_type );
	}

	public function list_posttypes( $pre = NULL, $posttypes = NULL, $capability = NULL, $args = [ 'show_ui' => TRUE ], $user_id = NULL )
	{
		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All PostTypes', 'Module', 'geditorial-admin' ) ];

		$all = WordPress\PostType::get( 0, $args, $capability, $user_id );

		foreach ( $this->posttypes( $posttypes ) as $posttype ) {

			if ( array_key_exists( $posttype, $all ) )
				$pre[$posttype] = $all[$posttype];

			// only if no checks required
			else if ( is_null( $capability ) && WordPress\PostType::exists( $posttype ) )
				$pre[$posttype] = $posttype;
		}

		return $pre;
	}

	public function all_posttypes( $args = [ 'show_ui' => TRUE ], $exclude_extra = [] )
	{
		return Core\Arraay::stripByKeys(
			WordPress\PostType::get( 0, $args ),
			Core\Arraay::prepString(
				$this->posttypes_excluded( $exclude_extra )
			)
		);
	}

	// DEFAULT METHOD
	protected function posttypes_excluded( $extra = [] )
	{
		$extra = (array) $extra;

		if ( method_exists( $this, 'paired_get_constants' ) ) {

			if ( $paired = $this->paired_get_constants() )
				$extra[] = $this->constant( $paired[0] );
		}

		return $this->filters( 'posttypes_excluded',
			gEditorial\Settings::posttypesExcluded(
				$extra,
				$this->keep_posttypes
			)
		);
	}

	// DEFAULT METHOD
	protected function posttypes_parents( $extra = [] )
	{
		return $this->filters( 'posttypes_parents', gEditorial\Settings::posttypesParents( $extra ) );
	}

	/**
	 * Gets post-type parents for use in settings.
	 *
	 * @param array $extra
	 * @param string $capability
	 * @return array
	 */
	protected function get_settings_posttypes_parents( $extra = [], $capability = NULL )
	{
		$list       = [];
		$posttypes = WordPress\PostType::get( 0, [ 'show_ui' => TRUE ], $capability );

		foreach ( $this->posttypes_parents( $extra ) as $posttype ) {

			if ( array_key_exists( $posttype, $posttypes ) )
				$list[$posttype] = $posttypes[$posttype];

			// only if no checks required
			else if ( is_null( $capability ) && WordPress\PostType::exists( $posttype ) )
				$list[$posttype] = $posttype;
		}

		return $list;
	}
}

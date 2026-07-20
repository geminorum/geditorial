<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait SettingsTaxonomies
{
	/**
	 * Retrieves settings option for the target taxonomies.
	 *
	 * @param string $target
	 * @param array $fallback
	 * @return array
	 */
	public function get_setting_taxonomies( string $target, array $fallback = [] ): array
	{
		return $target ? $this->get_setting( self::und( $target, 'taxonomies' ), $fallback ): $fallback;
	}

	/**
	 * Checks if taxonomy is in settings option for the target taxonomies.
	 *
	 * @param string $taxonomy
	 * @param string $target
	 * @param bool $fallback
	 * @return bool
	 */
	public function in_setting_taxonomies( false|string $taxonomy, string $target, bool $fallback = FALSE ): bool
	{
		return ( $taxonomy && in_array( $taxonomy, $this->get_setting_taxonomies( $target ) ) ) || $fallback;
	}

	public function register_settings_taxonomies_option( ?string $title = NULL ): bool
	{
		$option = $this->hook_base( $this->module->name );
		$title  = $title ?? $this->get_string( 'taxonomies_title', FALSE, 'settings',
			_x( 'Supported Taxonomies', 'Internal: SettingsTaxonomies: Field Title', 'geditorial-admin' ) );

		Services\Modulation::addSection( $option, [
			'id'            => self::und( $option, 'taxonomies' ),
			'title'         => _x( 'Taxonomies', 'Internal: SettingsTaxonomies: Section Title', 'geditorial-admin' ),
			'section_class' => 'taxonomies_option_section',
		] );

		add_settings_field( 'taxonomies',
			$title,
			[ $this, 'settings_taxonomies_option' ],
			$option,
			self::und( $option, 'taxonomies' )
		);

		return TRUE;
	}

	public function settings_taxonomies_option(): void
	{
		if ( $before = $this->get_string( 'taxonomies_before', FALSE, 'settings', NULL ) )
			Core\HTML::desc( $before );

		echo gEditorial\Settings::tabPanelOpen( FALSE, '-panel-expanded' );

		foreach ( $this->all_taxonomies() as $taxonomy => $label ) {

			$html = Core\HTML::tag( 'input', [
				'type'    => 'checkbox',
				'value'   => 'enabled',
				'id'      => self::dsh( 'tax', $taxonomy ),
				'name'    => $this->hook_base( $this->module->name ).'[taxonomies]['.$taxonomy.']',
				'checked' => ! empty( $this->options->taxonomies[$taxonomy] ),
			] );

			$html.= '&nbsp;'.Core\HTML::escape( $label );
			$html.= ' &mdash; <code>'.$taxonomy.'</code>';

			Core\HTML::label( $html, 'tax-'.$taxonomy, 'li' );
		}

		echo '</ul></div>';

		if ( $after = $this->get_string( 'taxonomies_after', FALSE, 'settings', NULL ) )
			Core\HTML::desc( $after );
	}

	// enabled taxonomies for this module
	public function taxonomies( string|array|null $taxonomies = NULL ): array
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

	public function taxonomy_supported( false|string $taxonomy ): bool
	{
		return $taxonomy && in_array( $taxonomy, $this->taxonomies(), TRUE );
	}

	public function taxonomy_anchor( string $taxonomy ): string
	{
		$taxonomy = $this->constant( $taxonomy, $taxonomy );
		$object   = WordPress\Taxonomy::object( $taxonomy );

		if ( ! empty( $object->rest_base ) )
			return $object->rest_base;

		return Core\L10n::pluralize( $taxonomy );
	}

	public function screen_taxonomy_supported( object $screen, string|array|false|null $base = NULL ): bool
	{
		$base = $base ?? [ 'edit-tags', 'term' ];

		if ( $base && ! in_array( $screen->base, (array) $base, TRUE ) )
			return FALSE;

		return $this->taxonomy_supported( $screen->taxonomy );
	}

	public function list_taxonomies(
		mixed $pre = NULL,
		string|array|null $taxonomies = NULL,
		?string $capability = NULL,
		?array $args = NULL,
		?int $user_id = NULL,
	): array {

		if ( is_null( $pre ) )
			$pre = [];

		else if ( TRUE === $pre )
			$pre = [ 'all' => _x( 'All Taxonomies', 'Module', 'geditorial-admin' ) ];

		$all = WordPress\Taxonomy::get( 0, $args ?? [ 'show_ui' => TRUE ], FALSE, $capability, $user_id );

		foreach ( $this->taxonomies( $taxonomies ) as $taxonomy ) {

			if ( array_key_exists( $taxonomy, $all ) )
				$pre[$taxonomy] = $all[$taxonomy];

			// only if no checks required
			else if ( is_null( $capability ) )
				$pre[$taxonomy] = $taxonomy;
		}

		return $pre;
	}

	public function all_taxonomies( ?array $args = NULL, array $exclude_extra = [] ): array
	{
		return Core\Arraay::stripByKeys(
			WordPress\Taxonomy::get( 0, $args ?? [ 'show_ui' => TRUE ] ),
			Core\Arraay::prepString(
				$this->taxonomies_excluded( $exclude_extra )
			)
		);
	}

	protected function taxonomies_excluded( array $extra = [] ): array
	{
		return $this->filters( 'taxonomies_excluded',
			gEditorial\Settings::taxonomiesExcluded(
				$extra,
				$this->keep_taxonomies
			)
		);
	}

	protected function _hook_taxonomies_excluded( string $constant, ?string $module = NULL ): bool
	{
		return $this->filter_append(
			$this->hook_base( $module ?? $this->module->name, 'taxonomies_excluded' ),
			$this->constant( $constant )
		);
	}

	protected function get_taxonomy_autolink_terms_desc( string $constant ): string
	{
		return sprintf(
			/* translators: `%s`: taxonomy name */
			_x( 'Tries to linkify the string of %s in the contents of supported post-types.', 'Settings: Taxonomies', 'geditorial-admin' ),
			Core\HTML::strong( $this->get_taxonomy_label( $constant ) )
		);
	}

	protected function get_taxonomy_show_in_navmenus_desc( string $constant ): string
	{
		return sprintf(
			/* translators: `%s`: taxonomy name */
			_x( 'Makes %s available for selection in navigation menus.', 'Settings: Taxonomies', 'geditorial-admin' ),
			Core\HTML::strong( $this->get_taxonomy_label( $constant ) )
		);
	}

	protected function get_taxonomy_show_in_quickedit_desc( string $constant ): string
	{
		return sprintf(
			/* translators: `%s`: taxonomy name */
			_x( 'Whether to show the %s in the quick/bulk edit panel.', 'Settings: Taxonomies', 'geditorial-admin' ),
			Core\HTML::strong( $this->get_taxonomy_label( $constant ) )
		);
	}

	protected function get_taxonomy_parents_as_views_desc( string $constant ): string
	{
		return sprintf(
			/* translators: `%s`: taxonomy name */
			_x( 'Prepends the parent terms of %s to views on supported post-types', 'Settings: Taxonomies', 'geditorial-admin' ),
			Core\HTML::strong( $this->get_taxonomy_label( $constant ) )
		);
	}
}

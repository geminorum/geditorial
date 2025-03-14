<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\WordPress;

trait CoreCapabilities
{

	/**
	 * Wraps Role API method for given taxonomy constant
	 * FIXME: WTF: maybe check if taxonomy exists
	 *
	 * @param  string $constant
	 * @param  string|array $whats
	 * @param  null|int $user_id
	 * @param  bool $fallback
	 * @param  bool $admins
	 * @param  string $prefix
	 * @return bool $can
	 */
	protected function corecaps_taxonomy_role_can( $constant, $whats, $user_id = NULL, $fallback = FALSE, $admins = TRUE, $prefix = '_roles' )
	{
		if ( ! $taxonomy = $this->constant( $constant ) )
			return $fallback;

		$checks = [];

		foreach ( (array) $whats as $what )
			$checks[] = sprintf( 'taxonomy_%s_%s', $taxonomy, $what );

		return $this->role_can( $checks, $user_id, $fallback, $admins, $prefix );
	}

	/**
	 * Hooks corresponding filter for `map_meta_cap` of WordPress.
	 * @REF: https://make.wordpress.org/core/?p=20496
	 *
	 * @param  string $constant
	 * @return bool $hooked
	 */
	protected function corecaps__handle_taxonomy_metacaps_roles( $constant )
	{
		if ( ! $taxonomy = $this->constant_plural( $constant ) )
			return FALSE;

		add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) use ( $constant, $taxonomy ) {

			switch ( $cap ) {

				case 'edit_post':
				case 'edit_page':
				case 'delete_post':
				case 'delete_page':
				case 'publish_post':

					$locking = $this->get_setting( sprintf( 'taxonomy_%s_locking_terms', $taxonomy[0] ), [] );

					if ( empty( $locking ) )
						return $caps;

					if ( ! $post = WordPress\Post::get( $args[0] ) )
						return $caps;

					if ( ! $this->posttype_supported( $post->post_type ) )
						return $caps;

					foreach ( $locking as $term_id )
						if ( is_object_in_term( $post->ID, $taxonomy[0], (int) $term_id ) )
							return $this->corecaps_taxonomy_role_can( $constant, 'manage', $user_id )
								? $caps
								: [ 'do_not_allow' ];

					break;

				case 'manage_'.$taxonomy[0]:  // FIXME: DEPRECATED
				case 'edit_'.$taxonomy[0]:    // FIXME: DEPRECATED
				case 'delete_'.$taxonomy[0]:  // FIXME: DEPRECATED

				case 'manage_'.$taxonomy[1]:
				case 'edit_'.$taxonomy[1]:
				case 'delete_'.$taxonomy[1]:

					return $this->corecaps_taxonomy_role_can( $constant, 'manage', $user_id )
						? [ 'exist' ]
						: [ 'do_not_allow' ];

					break;

				case 'assign_'.$taxonomy[0]:  // FIXME: DEPRECATED
				case 'assign_'.$taxonomy[1]:

					return $this->corecaps_taxonomy_role_can( $constant, 'assign', $user_id )
						? [ 'exist' ]
						: [ 'do_not_allow' ];

					break;

				case 'assign_term':

					$term = get_term( (int) $args[0] );

					if ( ! $term || is_wp_error( $term ) )
						return $caps;

					if ( $taxonomy != $term->taxonomy )
						return $caps;

					if ( ! $roles = get_term_meta( $term->term_id, 'roles', TRUE ) )
						return $caps;

					if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
						return [ 'do_not_allow' ];
			}

			return $caps;
		}, 10, 4 );

		return TRUE;
	}

	/**
	 * Hooks corresponding filter for `map_meta_cap` of WordPress.
	 * @REF: https://make.wordpress.org/core/?p=20496
	 *
	 * @param  string $constant
	 * @return bool $hooked
	 */
	protected function corecaps__handle_taxonomy_metacaps_forced( $constant )
	{
		if ( ! $taxonomy = $this->constant_plural( $constant ) )
			return FALSE;

		add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) use ( $constant, $taxonomy ) {

			switch ( $cap ) {

				case 'manage_'.$taxonomy[0]: // FIXME: DEPRECATED
				case 'edit_'.$taxonomy[0]:   // FIXME: DEPRECATED
				case 'delete_'.$taxonomy[0]: // FIXME: DEPRECATED
				case 'assign_'.$taxonomy[0]: // FIXME: DEPRECATED

				case 'manage_'.$taxonomy[1]:
				case 'edit_'.$taxonomy[1]:
				case 'delete_'.$taxonomy[1]:
				case 'assign_'.$taxonomy[1]:

					return $this->role_can( 'manage', $user_id )
						? [ 'exist' ]
						: [ 'do_not_allow' ];

				case 'assign_term':

					$term = get_term( (int) $args[0] );

					if ( ! $term || is_wp_error( $term ) )
						return $caps;

					if ( $taxonomy[0] != $term->taxonomy )
						return $caps;

					if ( ! $roles = get_term_meta( $term->term_id, 'roles', TRUE ) )
						return $caps;

					if ( ! WordPress\User::hasRole( Core\Arraay::prepString( 'administrator', $roles ), $user_id ) )
						return [ 'do_not_allow' ];
			}

			return $caps;
		}, 10, 4 );

		return TRUE;
	}

	/**
	 * Retrieves setting arguments for given taxonomy constant roles.
	 *
	 * @param  string $constant
	 * @param  bool $locking
	 * @param  null|array $terms
	 * @param  null|string $empty
	 * @return array $settings
	 */
	protected function corecaps_taxonomy_get_roles_settings( $constant, $restricted = FALSE, $locking = FALSE, $terms = NULL, $empty = NULL )
	{
		if ( ! $taxonomy = $this->constant( $constant ) )
			return [];

		$roles = $this->get_settings_default_roles();
		$label = $this->get_taxonomy_label( $constant, 'extended_label', NULL, 'name' );

		$settings = [
			[
				'field'       => sprintf( 'taxonomy_%s_manage_roles', $taxonomy ),
				'type'        => 'checkboxes',
				'title'       => _x( 'Manage Roles', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ),
				/* translators: `%s`: taxonomy extended label */
				'description' => sprintf( _x( 'Roles that can Manage, Edit and Delete %s.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ), $label ),
				'values'      => $roles,
			],
			[
				'field'       => sprintf( 'taxonomy_%s_assign_roles', $taxonomy ),
				'type'        => 'checkboxes',
				'title'       => _x( 'Assign Roles', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ),
				/* translators: `%s`: taxonomy extended label */
				'description' => sprintf( _x( 'Roles that can Assign %s.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ), $label ),
				'values'      => $roles,
			],
			[
				'field'       => sprintf( 'taxonomy_%s_reports_roles', $taxonomy ),
				'type'        => 'checkboxes',
				'title'       => _x( 'Reports Roles', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ),
				/* translators: `%s`: taxonomy extended label */
				'description' => sprintf( _x( 'Roles that can see %s Reports.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ), $label ),
				'values'      => $roles,
			],
			[
				'field'       => sprintf( 'taxonomy_%s_tools_roles', $taxonomy ),
				'type'        => 'checkboxes',
				'title'       => _x( 'Tools Roles', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ),
				/* translators: `%s`: taxonomy extended label */
				'description' => sprintf( _x( 'Roles that can use %s Tools.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ), $label ),
				'values'      => $roles,
			],
		];

		if ( $restricted ) {

			$settings[] = [
				'field'       => sprintf( 'taxonomy_%s_restricted_roles', $taxonomy ),
				'type'        => 'checkboxes',
				'title'       => _x( 'Restricted Roles', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ),
				/* translators: `%s`: taxonomy extended label */
				'description' => sprintf( _x( 'Roles that check for %s visibility.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ), $label ),
				'values'      => $roles,
			];

			$settings[] = [
				'field'       => sprintf( 'taxonomy_%s_restricted_visibility', $taxonomy ),
				'type'        => 'select',
				/* translators: `%s`: taxonomy extended label */
				'title'       => sprintf( _x( 'Restricted %s', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ), $label ),
				'description' => _x( 'Handles visibility of each item based on meta values.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ),
				'default'     => 'disabled',
				'values'      => [
					'disabled' => _x( 'Disabled', 'Internal: CoreCapabilities: Setting Option', 'geditorial-admin' ),
					'hidden'   => _x( 'Hidden', 'Internal: CoreCapabilities: Setting Option', 'geditorial-admin' ),
				],
			];
		}

		if ( $locking )
			$settings[] = [
				'field'        => 'locking_terms',
				'type'         => 'checkbox-panel',
				'title'        => _x( 'Locking Terms', 'Internal: CoreCapabilities: Setting Title', 'geditorial-admin' ),
				/* translators: `%s`: taxonomy extended label */
				'description'  => sprintf( _x( 'Selected items will lock editing the post to %s managers.', 'Internal: CoreCapabilities: Setting Description', 'geditorial-admin' ), $label ),
				'string_empty' => $empty ?? $this->get_taxonomy_label( $constant, 'no_items_available', NULL, 'no_terms' ),
				'values'       => $terms ?? WordPress\Taxonomy::listTerms( $taxonomy ),
			];

		return $settings;
	}

	protected function corecaps__render_captype_install( $constant, $setting = NULL )
	{
		// display table with generated caps using the captype
		// list available roles to add the cap types
		// add/remove buttons for selected roles
	}
}

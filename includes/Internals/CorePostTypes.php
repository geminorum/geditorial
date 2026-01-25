<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

trait CorePostTypes
{
	public function register_posttype( $constant, $atts = [], $settings_atts = [], $taxonomies = [ 'post_tag' ] )
	{
		$posttype = $this->constant( $constant );
		$plural   = str_replace( '_', '-', Core\L10n::pluralize( $posttype ) );

		$args = self::recursiveParseArgs( $atts, [
			'description'   => $this->strings['labels'][$constant]['description'] ?? '',
			'show_in_menu'  => NULL, // or TRUE or `$parent_slug`
			'menu_position' => empty( $this->positions[$constant] ) ? 4 : $this->positions[$constant],

			// 'show_in_nav_menus' => TRUE,
			// 'show_in_admin_bar' => TRUE,

			'query_var'   => $this->constant( $constant.'_query_var', $posttype ),
			'has_archive' => $this->constant( $constant.'_archive', $plural ),

			'rewrite' => NULL,

			'hierarchical' => FALSE,
			'public'       => TRUE,
			'show_ui'      => TRUE,
			'map_meta_cap' => TRUE,

			// 'embeddable' => NULL, // `is_post_embeddable()`

			'register_meta_box_cb' => method_exists( $this, 'add_meta_box_cb_'.$constant )
				? [ $this, 'add_meta_box_cb_'.$constant ] : NULL,

			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $plural ) ),

			// 'rest_namespace ' => 'wp/v2', // @SEE: https://core.trac.wordpress.org/ticket/54536
			// 'late_route_registration' => TRUE, // A flag to direct the REST API controllers for autosave / revisions should be registered before/after the post type controller.

			'can_export'          => TRUE,
			'delete_with_user'    => FALSE,
			'exclude_from_search' => $this->get_setting( $constant.'_exclude_search', FALSE ),

			/// gEditorial Props // TODO: move to settings
			Services\Paired::PAIRED_TAXONOMY_PROP     => FALSE,  // @SEE: `Paired::isPostType()`
			gEditorial\MetaBox::POSTTYPE_MAINBOX_PROP => FALSE,  // @SEE: `hook_taxonomy_metabox_mainbox()`

			/// Misc Props
			// @SEE: https://github.com/torounit/custom-post-type-permalinks
			'cptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink

			// only `%post_id%` and `%postname%`
			// @SEE: https://github.com/torounit/simple-post-type-permalinks
			'sptp_permalink_structure' => $this->constant( $constant.'_permalink', FALSE ), // will lock the permalink
		] );

		$rewrite = [
			'slug'       => $this->constant( $constant.'_slug', str_replace( '_', '-', $posttype ) ),
			'ep_mask'    => $this->constant( $constant.'_endpoint', EP_PERMALINK | EP_PAGES ), // https://make.wordpress.org/plugins?p=29
			'with_front' => FALSE,
			'feeds'      => TRUE,
			'pages'      => TRUE,
		];

		if ( is_null( $args['rewrite'] ) )
			$args['rewrite'] = $rewrite;

		else if ( is_array( $args['rewrite'] ) )
			$args['rewrite'] = array_merge( $rewrite, $args['rewrite'] );

		if ( ! array_key_exists( 'labels', $args ) )
			$args['labels'] = $this->get_posttype_labels( $constant );

		if ( ! array_key_exists( 'taxonomies', $args ) )
			$args['taxonomies'] = is_null( $taxonomies ) ? $this->taxonomies() : $taxonomies;

		if ( ! array_key_exists( 'supports', $args ) )
			$args['supports'] = $this->get_posttype_supports( $constant );

		$settings = self::atts( [
			'parent_module'     => $this->key,
			'block_editor'      => FALSE,
			'quick_edit'        => NULL,
			'custom_icon'       => TRUE,
			'is_viewable'       => NULL,
			'custom_captype'    => FALSE,
			'primary_taxonomy'  => NULL,
			'status_taxonomy'   => NULL,
			'readonly_title'    => NULL,
			'tinymce_disabled'  => NULL,
			'slug_disabled'     => NULL,
			'date_disabled'     => NULL,
			'author_disabled'   => NULL,
			'password_disabled' => TRUE,
			'ical_source'       => TRUE,         // `TRUE`/`FALSE`/`paired`
			'no_autosave'       => NULL,         // after register
		], $settings_atts );

		// NOTE: apply settings BEFORE registration
		foreach ( $settings as $setting => $value ) {

			// NOTE: `NULL` means do not touch!
			if ( is_null( $value ) )
				continue;

			switch ( $setting ) {

				case 'parent_module':

					// TODO: use `const`
					$args[$this->hook_base( 'module' )] = $value;
					break;

				case 'block_editor':

					add_filter( 'use_block_editor_for_post_type',
						static function ( $edit, $type ) use ( $posttype, $value ) {
							return $posttype === $type ? (bool) $value : $edit;
						}, 12, 2 );

					break;

				case 'quick_edit':

					add_filter( 'quick_edit_enabled_for_post_type',
						static function ( $edit, $type ) use ( $posttype, $value ) {
							return $posttype === $type ? (bool) $value : $edit;
						}, 12, 2 );

					break;

				case 'custom_icon':

					/**
					 * The URL to the icon to be used for this menu. Pass a
					 * `base64-encoded` SVG using a data URI, which will be
					 * colored to match the color scheme -this should begin
					 * with `data:image/svg+xml;base64,`.
					 *
					 * Pass the name of a `Dashicons` helper class to use a font
					 * icon, e.g. `dashicons-chart-pie`.
					 *
					 * Pass `none` to leave `div.wp-menu-image` empty so an icon
					 * can be added via CSS.
					 *
					 * Default is to use the posts icon.
					 */

					if ( array_key_exists( 'menu_icon', $args ) )
						break;

					if ( TRUE === $value && in_array( $constant, [
						'main_posttype',
						'primary_posttype',
					], TRUE ) )
						$icon = $this->module->icon;

					else if ( TRUE === $value )
						$icon = $this->module->icon ?: 'welcome-write-blog';

					else if ( $value )
						$icon = $value;

					else
						break;

					if ( is_array( $icon ) )
						$args['menu_icon'] = Core\Icon::getBase64( $icon[1], $icon[0] );

					else
						$args['menu_icon'] = sprintf( 'dashicons-%s', $icon );

					// NOTE: passing icon on the original format: string/array
					$args[Services\Icons::MENUICON_PROP] = $icon;

					break;

				case 'is_viewable':

					// NOTE: only applies if the setting is `disabled`
					if ( $value )
						break;

					$args = array_merge( $args, [
						'public'              => FALSE,
						'exclude_from_search' => TRUE,
						'publicly_queryable'  => FALSE,
						'show_in_nav_menus'   => FALSE,
						'show_in_admin_bar'   => FALSE,
					] );

					add_filter( 'is_post_type_viewable',
						static function ( $is_viewable, $object ) use ( $posttype ) {
							return $object->name === $posttype ? FALSE : $is_viewable;
						}, 2, 9 );

					add_filter( $this->hook_base( 'meta', 'access_posttype_field' ),
						static function ( $access, $field, $post, $context, $user_id, $original ) use ( $posttype ) {
							if ( 'edit' === $context ) return $access;
							return $post->post_type === $posttype ? TRUE : $access;
						}, 12, 6 );

					add_filter( $this->hook_base( 'units', 'access_posttype_field' ),
						static function ( $access, $field, $post, $context, $user_id, $original ) use ( $posttype ) {
							if ( 'edit' === $context ) return $access;
							return $post->post_type === $posttype ? TRUE : $access;
						}, 12, 6 );

					// makes `Tabloid` links visible for non-viewable post-types
					add_filter( $this->hook_base( 'tabloid', 'is_post_viewable' ),
						static function ( $viewable, $post ) use ( $posttype ) {
							return $post->post_type === $posttype ? TRUE : $viewable;
						}, 12, 2 );

					// makes available on current module
					add_filter( $this->hook_base( $this->key, 'is_post_viewable' ),
						static function ( $viewable, $post ) use ( $posttype ) {
							return $post->post_type === $posttype ? TRUE : $viewable;
						}, 12, 2 );

					break;

				case 'custom_captype':

					// @SEE: `get_post_type_capabilities()`
					// @REF: https://learn.wordpress.org/tutorial/custom-post-types-and-capabilities/

					if ( self::bool( $value ) ) {

						$captype = empty( $value )
							? $this->constant_plural( $constant )
							: $value; // FIXME: WTF: what if passed `1`?!

						if ( is_array( $captype ) )
							$args['capability_type'] = $captype;

						else
							$args['capability_type'] = [
								$captype,
								Core\L10n::pluralize( $captype ),
							];

						if ( ! array_key_exists( 'capabilities', $args ) )
							$args['capabilities'] = [ 'create_posts' => sprintf( 'create_%s', $args['capability_type'][1] ) ];

						else if ( ! array_key_exists( 'create_posts', $args['capabilities'] ) )
							$args['capabilities']['create_posts'] = sprintf( 'create_%s', $args['capability_type'][1] );

						// same as `edit_others_posts`
						if ( ! array_key_exists( 'import_posts', $args['capabilities'] ) )
							$args['capabilities']['import_posts'] = sprintf( 'edit_others_%s', $args['capability_type'][1] );

						if ( in_array( 'comments', $args['supports'], TRUE ) )
							add_filter( 'map_meta_cap',
								function ( $caps, $cap, $user_id, $passed_args ) use ( $posttype, $args ) {

									if ( 'edit_comment' !== $cap )
										return $caps;

									if ( empty( $passed_args[0] ) || ! $comment = get_comment( (int) $passed_args[0] ) )
										return $caps;

									if ( empty( $comment->comment_post_ID ) || ! $post = get_post( $comment->comment_post_ID ) )
										return $caps;

									if ( $posttype !== $post->post_type )
										return $caps;

									if ( user_can( $user_id, sprintf( 'manage_%s', $args['capability_type'][1] ) ) )
										return [ 'exist' ];

									return [ 'do_not_allow' ];
								}, 12, 4 );

						if ( is_array( $taxonomies ) && in_array( 'post_tag', $taxonomies, TRUE ) )
							add_filter( 'map_meta_cap',
								function ( $caps, $cap, $user_id ) use ( $args ) {

									if ( [ 'read' ] === $caps || [ 'exist' ] === $caps )
										return $caps; // already cleared!

									if ( 'assign_post_tags' === $cap
										&& user_can( $user_id, sprintf( 'edit_%s', $args['capability_type'][1] ) ) )
											return [ 'exist' ];

									if ( in_array( $cap, [ 'manage_post_tags', 'edit_post_tags', 'delete_post_tags' ], TRUE )
										&& user_can( $user_id, sprintf( 'manage_%s', $args['capability_type'][1] ) ) )
											return [ 'exist' ];

									return $caps;
								}, 12, 3 );

					} else if ( gEditorial()->enabled( 'roled' ) ) {

						if ( in_array( $posttype, gEditorial()->module( 'roled' )->posttypes() ) ) {

							$args['capability_type'] = gEditorial()->module( 'roled' )->constant( 'base_type' );

							// @SEE: https://core.trac.wordpress.org/ticket/22895
							if ( ! array_key_exists( 'capabilities', $args ) )
								$args['capabilities'] = [
									'create_posts' => sprintf( 'create_%s',
										is_array( $args['capability_type'] )
											? $args['capability_type'][1]
											: Core\L10n::pluralize( $args['capability_type'] ) ),
									'import_posts' => sprintf( 'edit_others_%s',
										is_array( $args['capability_type'] )
											? $args['capability_type'][1]
											: Core\L10n::pluralize( $args['capability_type'] ) )
								];
						}

					} else {

						$args['capability_type'] = 'post';
					}

					break;

				case 'primary_taxonomy': $args[Services\PrimaryTaxonomy::POSTTYPE_PROP]   = TRUE === $value ? $this->constant( $setting, $setting ) : $value; break;
				case 'status_taxonomy' : $args[Services\PrimaryTaxonomy::STATUS_TAX_PROP] = TRUE === $value ? $this->constant( $setting, $setting ) : $value; break;

				case 'ical_source' : $args[Services\Calendars::POSTTYPE_ICAL_SOURCE] = $value; break;

				case 'readonly_title':
				case 'tinymce_disabled':
				case 'slug_disabled':
				case 'date_disabled':
				case 'author_disabled':
				case 'password_disabled':
					$args[$setting] = TRUE;
					break;
			}
		}

		$object = register_post_type( $posttype, $args );

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		// NOTE: apply settings AFTER registration
		foreach ( $settings as $setting => $value ) {

			// NOTE: `NULL` means do not touch!
			if ( is_null( $value ) )
				continue;

			switch ( $setting ) {

				case 'no_autosave':

					/**
					 * For backward compatibility reasons, adding `editor` support
					 * implies `autosave` support, so one would need to explicitly
					 * use `remove_post_type_support()` to remove it again.
					 *
					 * @REF: https://core.trac.wordpress.org/changeset/58201
					 */
					remove_post_type_support( $posttype, 'autosave' );

					break;
			}
		}

		return $object;
	}

	public function get_posttype_labels( $constant )
	{
		if ( isset( $this->strings['labels'] )
			&& array_key_exists( $constant, $this->strings['labels'] ) )
				$labels = $this->strings['labels'][$constant];
		else
			$labels = [];

		if ( FALSE === $labels )
			return FALSE;

		// DEPRECATED: back-comp
		if ( $menu_name = $this->get_string( 'menu_name', $constant, 'misc', NULL ) )
			$labels['menu_name'] = $menu_name;

		// DEPRECATED: back-comp
		if ( $author_metabox = $this->get_string( 'author_metabox', $constant, 'misc', NULL ) )
			$labels['author_label'] = $author_metabox;

		// DEPRECATED: back-comp
		if ( $excerpt_metabox = $this->get_string( 'excerpt_metabox', $constant, 'misc', NULL ) )
			$labels['excerpt_label'] = $excerpt_metabox;

		if ( ! empty( $this->strings['noops'][$constant] ) )
			return Services\CustomPostType::generateLabels(
				gEditorial\Info::getNoop( $this->strings['noops'][$constant] ) ?: $this->strings['noops'][$constant],
				// DEPRECATED: back-comp: use `labels->featured_image`
				$this->get_string( 'featured', $constant, 'misc', NULL ),
				$labels,
				$this->constant( $constant )
			);

		return $labels;
	}

	protected function get_posttype_supports( $constant )
	{
		if ( isset( $this->options->settings[$constant.'_supports'] ) )
			return $this->options->settings[$constant.'_supports'];

		return array_keys( $this->settings_supports_defaults( $constant ) );
	}

	protected function settings_supports_defaults( $constant, $excludes = NULL )
	{
		// default excludes
		if ( is_null( $excludes ) )
			$excludes = [ 'post-formats', 'trackbacks' ];

		$posttype = $this->constant( $constant );
		$supports = $this->filters( $constant.'_supports', gEditorial\Settings::supportsOptions(), $posttype, $excludes );

		// has custom fields
		foreach ( [ 'meta', 'units', 'geo', 'seo' ] as $type )
			if ( isset( $this->fields[$type][$posttype] ) )
				unset( $supports['editorial-'.$type] );

		if ( count( $excludes ) )
			$supports = array_diff_key( $supports, array_flip( (array) $excludes ) );

		return $supports;
	}

	protected function settings_supports_option( $constant, $defaults = TRUE, $excludes = NULL )
	{
		$supports = $this->settings_supports_defaults( $constant, $excludes );

		if ( FALSE === $defaults )
			$defaults = [];

		else if ( TRUE === $defaults )
			$defaults = array_keys( $supports );

		$singular = $this->get_posttype_label( $constant, 'singular_name' );

		return [
			'field'   => $constant.'_supports',
			'type'    => 'checkboxes-values',
			'default' => $defaults,
			'values'  => $supports,
			'title'   => sprintf(
				/* translators: `%s`: singular post-type name */
				_x( '%s Supports', 'Module: Setting Title', 'geditorial-admin' ),
				$singular
			),
			'description' => sprintf(
				/* translators: `%s`: singular post-type name */
				_x( 'Support core and extra features for %s posttype.', 'Module: Setting Description', 'geditorial-admin' ),
				$singular
			),
		];
	}

	public function is_posttype( $constant, $post = NULL )
	{
		if ( ! $constant )
			return FALSE;

		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		return $this->constant( $constant ) == $post->post_type;
	}

	public function get_posttype_label( $constant, $label = 'name', $fallback = '' )
	{
		return Services\CustomPostType::getLabel( $this->constant( $constant, $constant ), $label, NULL, $fallback );
	}

	public function get_posttype_taxonomies_list( $constant )
	{
		return WordPress\Taxonomy::get( 0, [ 'show_ui' => TRUE ], $this->constant( $constant ) );
	}

	public function get_posttype_fields_list( $constant, $module = 'meta' )
	{
		return Core\Arraay::pluck( Services\PostTypeFields::getEnabled( $this->constant( $constant ), $module ), 'title', 'name' );
	}

	// @REF: `post_type_supports()`
	protected function is_posttype_support( $posttype, $feature, $fallback = TRUE )
	{
		global $_wp_post_type_features;

		if ( empty( $posttype ) || empty( $feature ) )
			return FALSE;

		return $this->filters( sprintf( 'posttype_%s_supports_%s', $posttype, $feature ),
			$_wp_post_type_features[$posttype][$feature] ?? $fallback,
			$posttype,
			$feature,
			$fallback
		);
	}

	// NOTE: like core but with `FALSE` support
	// @REF: `add_post_type_support()`
	protected function add_posttype_support( $posttype, $feature, $args = TRUE )
	{
		global $_wp_post_type_features;

		foreach ( (array) $feature as $key )
			$_wp_post_type_features[$posttype][$key] = $args;
	}

	public function is_post_viewable( $post = NULL )
	{
		if ( ! $post = WordPress\Post::get( $post ) )
			return FALSE;

		return $this->filters( 'is_post_viewable', WordPress\Post::viewable( $post ), $post );
	}

	public function get_image_sizes_for_posttype( $posttype )
	{
		if ( ! isset( $this->image_sizes[$posttype] ) ) {

			$sizes = $this->filters( $posttype.'_image_sizes', [] );

			if ( FALSE === $sizes ) {

				$this->image_sizes[$posttype] = []; // no sizes

			} else if ( count( $sizes ) ) {

				$this->image_sizes[$posttype] = $sizes; // custom sizes

			} else {

				foreach ( WordPress\Media::defaultImageSizes() as $size => $args )
					$this->image_sizes[$posttype][$posttype.'-'.$size] = $args;
			}
		}

		return $this->image_sizes[$posttype];
	}

	// NOTE: use this on `after_setup_theme` hook
	public function register_posttype_thumbnail( $constant )
	{
		if ( ! $this->get_setting( 'thumbnail_support', FALSE ) )
			return;

		$posttype = $this->constant( $constant );

		WordPress\Media::themeThumbnails( [ $posttype ] );

		foreach ( $this->get_image_sizes_for_posttype( $posttype ) as $name => $size )
			WordPress\Media::registerImageSize( $name, array_merge( $size, [ 'p' => [ $posttype ] ] ) );
	}

	// TODO: must add meta-box to list the attachments: maybe on `Attachments` Module
	// @REF: https://stackoverflow.com/questions/15283026/attaching-media-to-post-type-without-editor-support
	public function posttype__media_register_headerbutton( $constant, $post = NULL, $editor_check = TRUE )
	{
		// already handled!
		if ( $editor_check && post_type_supports( $this->constant( $constant, $constant ), 'editor' ) )
			return FALSE;

		if ( ! post_type_supports( $this->constant( $constant, $constant ), 'thumbnail' ) )
			return FALSE;

		if ( ! $this->cuc( 'uploads', 'upload_files' ) )
			return FALSE;

		$args = [];

		if ( $post = WordPress\Post::get( $post ) )
			$args['post'] = $post;

		wp_enqueue_media( $args );

		return Services\HeaderButtons::register( 'posttype_overview', [
			'text'     => _x( 'Uploads', 'Internal: CorePostTypes: Header Button', 'geditorial-admin' ),
			'icon'     => 'admin-media',
			'class'    => 'insert-media add_media', // needed for media library evoc
			'priority' => 5,
		] );
	}

	protected function posttypes__increase_menu_order( $posttype )
	{
		add_filter( 'wp_insert_post_data',
			function ( $data, $postarr )
				use ( $posttype ) {

				if ( ! empty( $data['menu_order'] ) )
					return $data;

				if ( empty( $postarr['post_type'] ) || $posttype !== $postarr['post_type'] )
					return $data;

				$data['menu_order'] = WordPress\PostType::getLastMenuOrder(
					$postarr['post_type'],
					$postarr['ID'] ?? ''
				) + 1;

				return $data;

			}, 9, 2 );
	}
}

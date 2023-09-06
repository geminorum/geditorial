<?php namespace geminorum\gEditorial\Internals;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\WordPress;

trait CorePostTypes
{

	public function register_posttype( $constant, $atts = [], $taxonomies = [ 'post_tag' ], $block_editor = FALSE )
	{
		$posttype = $this->constant( $constant );
		$cap_type = $this->get_posttype_cap_type( $constant );
		$plural   = str_replace( '_', '-', Core\L10n::pluralize( $posttype ) );

		$args = self::recursiveParseArgs( $atts, [
			'description' => isset( $this->strings['labels'][$constant]['description'] )
				? $this->strings['labels'][$constant]['description'] : '',

			'show_in_menu'  => NULL, // or TRUE or `$parent_slug`
			'menu_icon'     => $this->get_posttype_icon( $constant ),
			'menu_position' => empty( $this->positions[$constant] ) ? 4 : $this->positions[$constant],

			// 'show_in_nav_menus' => TRUE,
			// 'show_in_admin_bar' => TRUE,

			'query_var'   => $this->constant( $constant.'_query_var', $posttype ),
			'has_archive' => $this->constant( $constant.'_archive', $plural ),

			'rewrite' => NULL,

			'hierarchical' => FALSE,
			'public'       => TRUE,
			'show_ui'      => TRUE,

			'capability_type' => $cap_type,
			'map_meta_cap'    => TRUE,

			'register_meta_box_cb' => method_exists( $this, 'add_meta_box_cb_'.$constant )
				? [ $this, 'add_meta_box_cb_'.$constant ] : NULL,

			'show_in_rest' => TRUE,
			'rest_base'    => $this->constant( $constant.'_rest', $this->constant( $constant.'_archive', $plural ) ),

			// 'rest_namespace ' => 'wp/v2', // @SEE: https://core.trac.wordpress.org/ticket/54536

			'can_export'          => TRUE,
			'delete_with_user'    => FALSE,
			'exclude_from_search' => $this->get_setting( $constant.'_exclude_search', FALSE ),

			/// gEditorial Props
			WordPress\PostType::PRIMARY_TAXONOMY_PROP => NULL,   // @SEE: `PostType::getPrimaryTaxonomy()`
			Services\Paired::PAIRED_TAXONOMY_PROP     => FALSE,  // @SEE: `Paired::isPostType()`

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

		// @ALSO SEE: https://core.trac.wordpress.org/ticket/22895
		if ( ! array_key_exists( 'capabilities', $args ) && 'post' != $cap_type )
			$args['capabilities'] = [ 'create_posts' => is_array( $cap_type ) ? 'create_'.$cap_type[1] : 'create_'.$cap_type.'s' ];

		$object = register_post_type( $posttype, $args );

		if ( self::isError( $object ) )
			return $this->log( 'CRITICAL', $object->get_error_message(), $args );

		if ( ! $block_editor )
			add_filter( 'use_block_editor_for_post_type', static function( $edit, $type ) use ( $posttype ) {
				return $posttype === $type ? FALSE : $edit;
			}, 12, 2 );

		return $object;
	}

	// NOTE: also accepts: `[ 'story', 'stories' ]`
	public function get_posttype_cap_type( $constant )
	{
		$default = $this->constant( $constant.'_cap_type', 'post' );

		if ( ! gEditorial()->enabled( 'roles' ) )
			return $default;

		if ( ! in_array( $this->constant( $constant ), gEditorial()->module( 'roles' )->posttypes() ) )
			return $default;

		return gEditorial()->module( 'roles' )->constant( 'base_type' );
	}

	public function get_posttype_icon( $constant = NULL, $default = 'welcome-write-blog' )
	{
		$icon  = $this->module->icon ? $this->module->icon : $default;
		$icons = $this->get_module_icons();

		if ( $constant && isset( $icons['post_types'][$constant] ) )
			$icon = $icons['post_types'][$constant];

		if ( is_array( $icon ) )
			$icon = Core\Icon::getBase64( $icon[1], $icon[0] );

		else if ( $icon )
			$icon = 'dashicons-'.$icon;

		return $icon ?: 'dashicons-'.$default;
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
			return Helper::generatePostTypeLabels(
				$this->strings['noops'][$constant],
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

		return array_keys( Settings::supportsOptions() );
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
		return Helper::getPostTypeLabel( $this->constant( $constant, $constant ), $label, NULL, $fallback );
	}

	// @REF: `post_type_supports()`
	protected function is_posttype_support( $posttype, $feature, $fallback = TRUE )
	{
		global $_wp_post_type_features;

		if ( empty( $posttype ) || empty( $feature ) )
			return FALSE;

		$supported = isset( $_wp_post_type_features[$posttype][$feature] )
			? $_wp_post_type_features[$posttype][$feature]
			: $fallback;

		return $this->filters( sprintf( 'posttype_%s_supports_%s', $posttype, $feature ),
			$supported,
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

	// NOTE: only applies if the setting is `disabled`
	protected function _hook_posttype_viewable( $posttype, $default = TRUE, $setting = 'posttype_viewable' )
	{
		if ( $this->get_setting( $setting, $default ) )
			return;

		add_filter( 'is_post_type_viewable',
			function ( $is_viewable, $object ) use ( $posttype ) {
				return $object->name === $posttype ? FALSE : $is_viewable;
			}, 2, 9 );

		// add_filter( $this->hook_base( 'meta', 'access_posttype_field' ),
		// 	function ( $access, $field, $post, $context, $user_id ) use ( $posttype ) {
		// 		return $post->post_type === $posttype ? TRUE : $access;
		// 	}, 12, 5 );

		// NOTE: makes Tabloid links visible for non-viewable post-types
		add_filter( $this->hook_base( 'tabloid', 'is_post_viewable' ),
			function ( $viewable, $post ) use ( $posttype ) {
				return $post->post_type === $posttype ? TRUE : $viewable;
			}, 12, 2 );
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
}

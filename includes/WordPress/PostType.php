<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class PostType extends Core\Base
{

	const NAME_INPUT_PATTERN    = '[-a-zA-Z0-9_]{3,20}';
	const PRIMARY_TAXONOMY_PROP = 'primary_taxonomy';   // FIXME: DEPRECATED
	const MAP_CAP_IMPORT_POSTS  = 'edit_others_posts';

	public static function object( $posttype_or_post )
	{
		if ( ! $posttype_or_post )
			return FALSE;

		if ( $posttype_or_post instanceof \WP_Post )
			return get_post_type_object( $posttype_or_post->post_type );

		if ( $posttype_or_post instanceof \WP_Post_Type )
			return $posttype_or_post;

		return get_post_type_object( $posttype_or_post );
	}

	/**
	 * Determines whether a post-type is registered.
	 * @source `post_type_exists()`
	 *
	 * @param string|object $posttype_or_post
	 * @return bool
	 */
	public static function exists( $posttype_or_post )
	{
		return (bool) self::object( $posttype_or_post );
	}

	public static function viewable( $posttype )
	{
		if ( ! $posttype )
			return FALSE;

		return is_post_type_viewable( $posttype );
	}

	/**
	 * Returns the names or objects of the taxonomies which are
	 * registered for the requested object or object type.
	 *
	 * @param string|object $posttype_or_post
	 * @param string $output
	 * @return array
	 */
	public static function taxonomies( $posttype_or_post, $output = 'names' )
	{
		if ( ! $posttype_or_post )
			return [];

		return get_object_taxonomies( $posttype_or_post, $output );
	}

	/**
	 * Checks for post-type capability.
	 * NOTE: caches the result
	 *
	 * If assigned post-type `capability_type` argument:
	 *
	 * /// Meta capabilities
	 * 	[edit_post]   => "edit_{$capability_type}"
	 * 	[read_post]   => "read_{$capability_type}"
	 * 	[delete_post] => "delete_{$capability_type}"
	 *
	 * /// Primitive capabilities used outside of map_meta_cap():
	 * 	[edit_posts]             => "edit_{$capability_type}s"
	 * 	[edit_others_posts]      => "edit_others_{$capability_type}s"
	 * 	[publish_posts]          => "publish_{$capability_type}s"
	 * 	[read_private_posts]     => "read_private_{$capability_type}s"
	 *
	 * /// Primitive capabilities used within map_meta_cap():
	 * 	[read]                   => "read",
	 * 	[delete_posts]           => "delete_{$capability_type}s"
	 * 	[delete_private_posts]   => "delete_private_{$capability_type}s"
	 * 	[delete_published_posts] => "delete_published_{$capability_type}s"
	 * 	[delete_others_posts]    => "delete_others_{$capability_type}s"
	 * 	[edit_private_posts]     => "edit_private_{$capability_type}s"
	 * 	[edit_published_posts]   => "edit_published_{$capability_type}s"
	 * 	[create_posts]           => "edit_{$capability_type}s"
	 *
	 * @param string|object $posttype
	 * @param null|string $capability
	 * @param null|int|object $user_id
	 * @param bool $fallback
	 * @return bool
	 */
	public static function can( $posttype, $capability = 'edit_posts', $user_id = NULL, $fallback = FALSE )
	{
		static $cache = [];

		if ( is_null( $capability ) )
			return TRUE;

		else if ( ! $capability )
			return $fallback;

		if ( ! $object = self::object( $posttype ) )
			return $fallback;

		// Fallbacks if it was a custom capability.
		if ( ! isset( $object->cap->{$capability} ) && 'import_posts' === $capability )
			$capability = static::MAP_CAP_IMPORT_POSTS;

		if ( ! isset( $object->cap->{$capability} ) )
			return $fallback;

		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();

		else if ( is_object( $user_id ) )
			$user_id = $user_id->ID;

		if ( ! $user_id )
			return user_can( $user_id, $object->cap->{$capability} );

		if ( isset( $cache[$user_id][$object->name][$capability] ) )
			return $cache[$user_id][$object->name][$capability];

		$can = user_can( $user_id, $object->cap->{$capability} );

		// fallback for super-admins
		// if ( ! $can && is_multisite() )
		// 	$can = user_can( $user_id, 'manage_network' );

		return $cache[$user_id][$object->name][$capability] = $can;
	}

	/**
	 * Retrieves the capability assigned to the post-type.
	 *
	 * @param string|object $posttype
	 * @param string $capability
	 * @param string $fallback
	 * @return string
	 */
	public static function cap( $posttype, $capability = 'edit_posts', $fallback = NULL )
	{
		if ( is_null( $capability ) )
			return TRUE;

		else if ( ! $capability )
			return $fallback;

		if ( ! $object = self::object( $posttype ) )
			return $fallback;

		// Fallbacks if it was a custom capability.
		if ( ! isset( $object->cap->{$capability} ) && 'import_posts' === $capability )
			$capability = static::MAP_CAP_IMPORT_POSTS;

		if ( isset( $object->cap->{$capability} ) )
			return $object->cap->{$capability};

		return $fallback ?? $object->cap->edit_posts; // WTF?!
	}

	/**
	 * Retrieves the list of post-types.
	 *
	 * Argument values for `$args` include:
	 * 	`public` Boolean: If true, only public post types will be returned.
	 * 	`publicly_queryable` Boolean
	 * 	`exclude_from_search` Boolean
	 * 	`show_ui` Boolean
	 * 	`capability_type`
	 * 	`hierarchical`
	 * 	`menu_position`
	 * 	`menu_icon`
	 * 	`permalink_epmask`
	 *  `rewrite`
	 * 	`query_var`
	 *  `show_in_rest` Boolean: If true, will return post types whitelisted for the REST API
	 * 	`_builtin` Boolean: If true, will return WordPress default post types. Use false to return only custom post types.
	 *
	 * @param  int $mod
	 * @param  array $args
	 * @param  null|string $capability
	 * @param  int $user_id
	 * @return array $list
	 */
	public static function get( $mod = 0, $args = [ 'public' => TRUE ], $capability = NULL, $user_id = NULL )
	{
		$list = [];

		foreach ( get_post_types( $args, 'objects' ) as $posttype => $posttype_obj ) {

			if ( ! self::can( $posttype_obj, $capability, $user_id ) )
				continue;

			// just the name!
			if ( -1 === $mod )
				$list[] = $posttype_obj->name;

			// label
			else if ( 0 === $mod )
				$list[$posttype] = $posttype_obj->label ? $posttype_obj->label : $posttype_obj->name;

			// plural
			else if ( 1 === $mod )
				$list[$posttype] = $posttype_obj->labels->name;

			// singular
			else if ( 2 === $mod )
				$list[$posttype] = $posttype_obj->labels->singular_name;

			// nooped
			else if ( 3 === $mod )
				$list[$posttype] = [
					0          => $posttype_obj->labels->singular_name,
					1          => $posttype_obj->labels->name,
					'singular' => $posttype_obj->labels->singular_name,
					'plural'   => $posttype_obj->labels->name,
					'context'  => NULL,
					'domain'   => NULL,
				];

			// object
			else if ( 4 === $mod )
				$list[$posttype] = $posttype_obj;
		}

		return $list;
	}

	/**
	 * Retrieves post-type archive link.
	 *
	 * @param string|object $posttype
	 * @param mixed $fallback
	 * @return string
	 */
	public static function link( $posttype, $fallback = NULL )
	{
		if ( ! $object = self::object( $posttype ) )
			return $fallback;

		if ( ! $link = get_post_type_archive_link( $object->name ) )
			$link = $fallback;

		return apply_filters( 'geditorial_posttype_archive_link', $link, $object->name );
	}

	/**
	 * Retrieves the URL for editing a given post-type.
	 * @old `WordPress::getPostTypeEditLink()`
	 *
	 * @param string|object $posttype
	 * @param array $extra
	 * @param mixed $fallback
	 * @return string
	 */
	public static function edit( $posttype, $extra = [], $fallback = FALSE )
	{
		if ( ! $object = self::object( $posttype ) )
			return $fallback;

		if ( ! self::can( $posttype, 'read' ) )
			return $fallback;

		return add_query_arg( array_merge( [
			'post_type' => $object->name,
		], $extra ), admin_url( 'edit.php' ) );
	}

	public static function newLink( $posttype, $extra = [] )
	{
		$args = 'post' === $posttype
			? []
			: [ 'post_type' => $posttype ];

		return add_query_arg( array_merge( $args, $extra ), admin_url( 'post-new.php' ) );
	}

	// OLD: `Core\WordPress::getAuthorEditHTML()`
	public static function authorLink( $posttype, $author, $extra = [] )
	{
		if ( $author_data = get_user_by( 'id', $author ) )
			return Core\HTML::tag( 'a', [
				'href' => add_query_arg( array_merge( [
					'post_type' => $posttype,
					'author'    => $author,
				], $extra ), admin_url( 'edit.php' ) ),
				'title' => $author_data->user_login,
				'class' => '-author',
			], Core\HTML::escape( $author_data->display_name ) );

		return FALSE;
	}

	// * 'publish' - a published post or page
	// * 'pending' - post is pending review
	// * 'draft' - a post in draft status
	// * 'auto-draft' - a newly created post, with no content
	// * 'future' - a post to publish in the future
	// * 'private' - not visible to users who are not logged in
	// * 'inherit' - a revision. see get_children.
	// * 'trash' - post is in trashbin. added with Version 2.9.
	// NOTE: DEPRECATED
	public static function getStatuses()
	{
		global $wp_post_statuses;

		$statuses = [];

		foreach ( $wp_post_statuses as $status )
			$statuses[$status->name] = $status->label;

		return $statuses;
	}

	public static function getAvailableStatuses( $posttype, $excludes = NULL )
	{
		if ( is_null( $excludes ) )
			$excludes = [
				'trash',
				'private',
				'auto-draft',
			];

		return array_diff_key( get_available_post_statuses( $posttype ), (array) $excludes );
	}

	// @REF: https://stackoverflow.com/questions/4829199/sql-is-there-a-way-to-get-the-average-number-of-characters-for-a-field
	// `SELECT AVG(CHAR_LENGTH(<column>)) AS avgLength FROM <table>`
	// `select sum(len(theTextColumn)) / count(*) from theTable;`
	public static function getMetaAverageDataLength( $metakey, $fallback = FALSE )
	{
		global $wpdb;

		if ( empty( $metakey ) )
			return $fallback;

		$query = $wpdb->prepare( "
			SELECT AVG(CHAR_LENGTH(meta_value))
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
		", $metakey );

		return $wpdb->get_var( $query ) ?: $fallback;
	}

	// TODO: support regex on meta-keys
	// @SEE: https://tommcfarlin.com/get-post-id-by-meta-value/
	public static function getIDbyMeta( $meta, $value, $single = TRUE )
	{
		global $wpdb, $gEditorialIDbyMeta;

		if ( empty( $meta ) || empty( $value ) )
			return FALSE;

		if ( empty( $gEditorialIDbyMeta ) )
			$gEditorialIDbyMeta = [];

		$group = $single ? 'single' : 'all';

		if ( isset( $gEditorialIDbyMeta[$meta][$group][$value] ) )
			return $gEditorialIDbyMeta[$meta][$group][$value];

		$query = $wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value = %s
		", $meta, $value );

		$results = $single
			? $wpdb->get_var( $query )
			: $wpdb->get_col( $query );

		return $gEditorialIDbyMeta[$meta][$group][$value] = $results;
	}

	public static function getIDListbyMeta( $meta, $values )
	{
		global $wpdb, $gEditorialIDbyMeta;

		if ( empty( $meta ) )
			return FALSE;

		$filtered = array_filter( (array) $values );

		if ( empty( $filtered ) )
			return FALSE;

		$query = $wpdb->prepare( "
			SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			AND meta_value IN ( '".implode( "', '", esc_sql( $filtered ) )."' )
		", $meta );

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( empty( $results ) )
			return [];

		$list = Core\Arraay::pluck( $results, 'post_id', 'meta_value' );

		if ( empty( $gEditorialIDbyMeta ) )
			$gEditorialIDbyMeta = [];

		// update cache
		foreach ( $filtered as $value )
			$gEditorialIDbyMeta[$meta]['single'][$value] = array_key_exists( $value, $list ) ? $list[$value] : FALSE;

		return $list;
	}

	public static function invalidateIDbyMeta( $meta, $value )
	{
		global $gEditorialIDbyMeta;

		if ( empty( $meta ) )
			return TRUE;

		if ( empty( $gEditorialIDbyMeta ) )
			return TRUE;

		if ( FALSE === $value ) {

			// clear all meta by key
			foreach ( (array) $meta as $key ) {
				unset( $gEditorialIDbyMeta[$key]['all'] );
				unset( $gEditorialIDbyMeta[$key]['single'] );
			}

		} else {

			foreach ( (array) $meta as $key ) {
				unset( $gEditorialIDbyMeta[$key]['all'][$value] );
				unset( $gEditorialIDbyMeta[$key]['single'][$value] );
			}
		}

		return TRUE;
	}

	// WTF: `WP_Query` does not support `id=>name` as fields
	public static function getIDs( $posttype = 'any', $extra = [], $fields = NULL )
	{
		$args = array_merge( [
			'fields'         => $fields ?? 'ids', // OR: `id=>parent`
			'post_type'      => $posttype,
			'post_status'    => Status::acceptable( $posttype ),
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $extra );

		$query = new \WP_Query();

		return (array) $query->query( $args );
	}

	/**
	 * Retrieves lists of posts that have particular meta-key.
	 *
	 * @param string $metakey
	 * @param string|array $posttype
	 * @param array $extra
	 * @param string $fields
	 * @return array
	 */
	public static function getIDListByMetakey( $metakey, $posttype = 'any', $extra = [], $fields = NULL )
	{
		if ( ! $metakey )
			return [];

		$args = array_merge( [
			'fields'         => $fields ?? 'ids', // OR: `id=>parent`
			'post_type'      => $posttype,
			'post_status'    => Status::acceptable( $posttype ),
			'posts_per_page' => -1,

			'meta_query' => [ [
				'key'     => $metakey,
				'compare' => 'EXISTS'
			] ],

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $extra );

		$query = new \WP_Query();

		return (array) $query->query( $args );
	}

	public static function getIDsBySearch( $string, $atts = [], $columns = NULL, $fields = NULL )
	{
		$args = array_merge( [
			's'              => $string,
			'fields'         => $fields ?? 'ids', // OR: `id=>parent`
			'post_type'      => 'any',
			'post_status'    => Status::acceptable( 'any' ), // 'any',
			'posts_per_page' => -1,
			'search_columns' => $columns ?? '', // [ 'post_title', 'post_excerpt', 'post_content' ]

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $atts );

		$query = new \WP_Query();

		return (array) $query->query( $args );
	}

	// NOTE: DEPRECATED: use `Post::getByTitle()`
	public static function getIDsByTitle( $title, $atts = [] )
	{
		$args = array_merge( [
			'title'          => $title,
			'fields'         => 'ids',
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $atts );

		$query = new \WP_Query();

		return (array) $query->query( $args );
	}

	// TODO: move to `Post` Class
	public static function getIDbySlug( $slug, $posttype = 'post', $url = FALSE )
	{
		static $cache = [];

		if ( $url ) {
			$slug = rawurlencode( urldecode( $slug ) );
			$slug = sanitize_title( basename( $slug ) );
		}

		$slug = trim( $slug );

		if ( isset( $cache[$posttype] ) && array_key_exists( $slug, $cache[$posttype] ) )
			return $cache[$posttype][$slug];

		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_name = %s
			AND post_type = %s
		", $slug, $posttype ) );

		return $cache[$posttype][$slug] = $id;
	}

	public static function getLastRevisionID( $post )
	{
		if ( ! $post = get_post( $post ) )
			return FALSE;

		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_parent = %s
				AND post_type = 'revision'
				AND post_status = 'inherit'
				ORDER BY post_date DESC
			", $post->ID )
		);
	}

	// TODO: use db query
	public static function getLastMenuOrder( $posttype = 'post', $exclude = '', $key = 'menu_order', $statuses = NULL )
	{
		$post = get_posts( [
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'exclude'        => $exclude,
			'post_type'      => $posttype,
			'post_status'    => $statuses ?? Status::acceptable( $posttype, 'recent', [ 'pending' ] ),

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		] );

		if ( empty( $post ) )
			return 0;

		if ( 'menu_order' == $key )
			return (int) $post[0]->menu_order;

		return $post[0]->{$key};
	}

	// TODO: use db query
	public static function getRandomPostID( $posttype, $has_thumbnail = FALSE, $object = FALSE, $status = 'publish' )
	{
		$args = [
			'post_type'      => $posttype,
			'post_status'    => $status,
			'posts_per_page' => 1,
			'orderby'        => 'rand',

			'ignore_sticky_posts'    => TRUE,
			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		];

		if ( ! $object )
			$args['fields'] = 'ids';

		if ( $has_thumbnail )
			$args['meta_query'] = [ [
				'key'     => '_thumbnail_id',
				'compare' => 'EXISTS'
			] ];

		$query = new \WP_Query();
		$posts = $query->query( $args );

		return empty( $posts ) ? FALSE : $posts[0];
	}

	public static function getParentPostID( $post_id = NULL, $object = FALSE )
	{
		if ( ! $post = get_post( $post_id ) )
			return FALSE;

		if ( empty( $post->post_parent ) )
			return FALSE;

		if ( $object )
			return get_post( $post->post_parent );

		return (int) $post->post_parent;
	}

	// @REF: `wp_dashboard_recent_drafts()`
	public static function getRecent( $posttypes = [ 'post' ], $extra = [], $published = TRUE )
	{
		$args = array_merge( [
			'post_type'      => $posttypes,
			'post_status'    => $published ? 'publish' : Status::acceptable( $posttypes, 'recent' ),
			// 'posts_per_page' => 7, // will use default
			'orderby'        => 'modified',
			'order'          => 'DESC',

			'no_found_rows'          => TRUE,
			'suppress_filters'       => TRUE,
			'update_post_meta_cache' => FALSE,
			'update_post_term_cache' => FALSE,
			'lazy_load_term_meta'    => FALSE,
		], $extra );

		$query = new \WP_Query();

		return (array) $query->query( $args );
	}

	public static function supports( $posttype, $feature, $fallback = [] )
	{
		if ( empty( $posttype ) || empty( $feature ) )
			return $fallback;

		$all = get_all_post_type_supports( $posttype );

		if ( isset( $all[$feature][0] ) && is_array( $all[$feature][0] ) )
			return $all[$feature][0];

		return $fallback;
	}

	public static function isThumbnail( $attachment_id, $metakey = '_thumbnail_id' )
	{
		if ( ! $attachment_id )
			return FALSE;

		$query = new \WP_Query( [
			'post_type'   => 'any',
			'post_status' => 'any',
			'orderby'     => 'none',
			'fields'      => 'ids',
			'meta_query'  => [ [
				'value'   => $attachment_id,
				'key'     => $metakey,
				'compare' => '=',
			] ],
			'suppress_filters' => TRUE,
			'posts_per_page'   => -1,
		] );

		return $query->have_posts() ? $query->posts : [];
	}

	// Must add `add_thickbox()` for thick-box
	// @SEE: `Scripts::enqueueThickBox()`
	public static function htmlFeaturedImage( $post_id, $size = NULL, $link = TRUE, $metakey = NULL )
	{
		if ( is_null( $size ) )
			$size = Media::getAttachmentImageDefaultSize( get_post_type( $post_id ) );

		return Media::htmlAttachmentImage(
			self::getThumbnailID( $post_id, $metakey ),
			$size,
			$link,
			[ 'post' => $post_id ],
			'-featured'
		);
	}

	public static function getThumbnailID( $post_id, $metakey = NULL )
	{
		if ( is_null( $metakey ) )
			$thumbnail_id = get_post_thumbnail_id( $post_id ); // has filter @since WP 5.9.0

		else if ( $metakey )
			$thumbnail_id = (int) get_post_meta( $post_id, $metakey, TRUE );

		else
			$thumbnail_id = FALSE;

		return apply_filters( 'geditorial_get_post_thumbnail_id', $thumbnail_id, $post_id, $metakey );
	}

	// NOTE: DEPRECATED
	public static function getArchiveLink( $posttype )
	{
		self::_dep( 'WordPress\PostType::link()' );

		return self::link( $posttype );
	}

	// NOTE: DEPRECATED
	public static function supportBlocksByPost( $post )
	{
		self::_dep( 'WordPress\Post::supportBlocks()' );

		if ( ! function_exists( 'use_block_editor_for_post' ) )
			return FALSE;

		return use_block_editor_for_post( $post );
	}

	public static function supportBlocks( $posttype )
	{
		if ( ! function_exists( 'use_block_editor_for_post_type' ) )
			return FALSE;

		if ( ! $object = self::object( $posttype ) )
			return FALSE;

		return use_block_editor_for_post_type( $object->name );
	}

	public static function newPostFromTerm( $term, $taxonomy = 'category', $posttype = 'post', $user_id = 0 )
	{
		if ( ! is_object( $term ) && ! is_array( $term ) )
			$term = get_term( $term, $taxonomy );

		$new_post = [
			'post_title'   => $term->name,
			'post_name'    => $term->slug,
			'post_content' => $term->description,
			'post_status'  => 'pending',
			'post_author'  => $user_id ? $user_id : get_current_user_id(),
			'post_type'    => $posttype,
		];

		return wp_insert_post( $new_post );
	}

	public static function current( $default = NULL )
	{
		global $post, $typenow, $pagenow, $current_screen;

		if ( $post && $post->post_type )
			return $post->post_type;

		if ( $typenow )
			return $typenow;

		if ( $current_screen && isset( $current_screen->post_type ) )
			return $current_screen->post_type;

		if ( isset( $_REQUEST['post_type'] ) )
			return sanitize_key( $_REQUEST['post_type'] );

		return $default;
	}

	public static function getEditLinkByTerm( $term_or_id, $posttype, $taxonomy = '' )
	{
		if ( ! self::object( $posttype )->show_ui )
			return FALSE;

		if ( ! $term = Term::get( $term_or_id, $taxonomy ) )
			return FALSE;

		$object = Taxonomy::object( $term->taxonomy );

		$args = $object->query_var
			? [ $object->query_var => $term->slug ]
			: [ 'taxonomy' => $object->name, 'term' => $term->slug ];

		if ( 'post' !== $posttype )
			$args['post_type'] = $posttype;

		return add_query_arg( $args, ( 'attachment' === $posttype ? 'upload.php' : 'edit.php' ) );
	}

	// NOTE: DEPRECATED: use `Post::get()`
	public static function getPost( $post = NULL, $output = OBJECT, $filter = 'raw' )
	{
		return Post::get( $post, $output, $filter );
	}

	// NOTE: DEPRECATED: use `Post::link()`
	public static function getPostLink( $post, $fallback = NULL, $statuses = NULL )
	{
		return Post::link( $post, $fallback, $statuses );
	}

	// NOTE: DEPRECATED: use `Post::title()`
	public static function getPostTitle( $post, $fallback = NULL, $filter = TRUE )
	{
		return Post::title( $post, $fallback, $filter );
	}

	// NOTE: DEPRECATED: use `Post::getParentTitles()`
	public static function getParentTitles( $post, $suffix = '', $linked = FALSE, $separator = NULL )
	{
		return Post::getParentTitles( $post, $suffix, $linked, $separator );
	}

	// NOTE: DEPRECATED: use `Services\PrimaryTaxonomy::get()`
	public static function getPrimaryTaxonomy( $posttype, $fallback = FALSE )
	{
		self::_dep( 'Services\PrimaryTaxonomy::get()' );

		$taxonomy = $fallback;

		if ( 'post' === $posttype )
			$taxonomy = 'category';

		else if ( 'page' === $posttype )
			$taxonomy = $fallback;

		else if ( $posttype === WooCommerce::PRODUCT_POSTTYPE && WooCommerce::isActive() )
			$taxonomy = WooCommerce::PROCUCT_CATEGORY;

		if ( ! $taxonomy && ( $object = self::object( $posttype ) ) ) {

			if ( ! empty( $object->{self::PRIMARY_TAXONOMY_PROP} )
				&& Taxonomy::exists( $object->{self::PRIMARY_TAXONOMY_PROP} ) )
					$taxonomy = $object->{self::PRIMARY_TAXONOMY_PROP};
		}

		return apply_filters( 'geditorial_posttype_primary_taxonomy', $taxonomy, $posttype, $fallback );
	}

	/**
	 * Retrieves post-type rest route given post-type name or object.
	 * @ref `rest_get_route_for_post_type_items()`
	 *
	 * @param string $posttype
	 * @return string
	 */
	public static function getRestRoute( $posttype )
	{
		if ( ! $object = self::object( $posttype ) )
			return FALSE;

		if ( ! $object->show_in_rest )
			return FALSE;

		$route = sprintf( '/%s/%s', $object->rest_namespace, $object->rest_base );

		// NOTE: core filter
		return apply_filters( 'rest_route_for_post_type_items', $route, $object );
	}

	/**
	 * Retrieves post-type count of posts by statuses or given status.
	 * NOTE: wrapper for `wp_count_posts()` and returns `array()`.
	 *
	 * @param string $posttype
	 * @param bool|string $status
	 * @return int|array
	 */
	public static function countByStatuses( $posttype, $status = FALSE )
	{
		$count = wp_count_posts( $posttype );

		if ( $status && isset( $count->{$status} ) )
			return $count->{$status};

		return get_object_vars( $count );
	}

	public static function sortByTitle( $posts )
	{
		usort( $posts, function ( $a, $b ) {

			$title_a = mb_strtolower( preg_replace( '~\P{Xan}++~u', '', $a->post_title ) );
			$title_b = mb_strtolower( preg_replace( '~\P{Xan}++~u', '', $b->post_title ) );

			if ( $title_a == $title_b )
				return 0 ;

			return ( $title_a < $title_b ) ? -1 : 1;
		} );

		return $posts;
	}

	/**
	 * Tries to re-order list of posts given meta-key or order list.
	 *
	 * @param array $posts
	 * @param string|array $reference
	 * @param string $fields
	 * @return array
	 */
	public static function reorderPostsByMeta( $posts, $reference = 'order', $fields = 'all' )
	{
		if ( empty( $posts ) || count( $posts ) === 1 || 'count' === $fields )
			return $posts;

		$type = 'object';
		$prop = '_order';
		$list = [];

		if ( in_array( $fields, [ 'ids' ], TRUE ) )
			$type = 'array';

		else if ( Core\Text::starts( $fields, 'id=>' ) )
			$type = 'assoc';

		foreach ( $posts as $index => $data ) {

			if ( 'array' == $type )
				$post_id = $data;

			else if ( 'assoc' == $type )
				$post_id = $index;

			else if ( isset( $data->ID ) )
				$post_id = $data->ID;

			else
				continue;

			if ( is_array( $reference ) )
				$order = isset( $reference[$post_id] ) ? intval( $reference[$post_id] ) : 0;

			else if ( $meta = get_post_meta( $post_id, $reference, TRUE ) )
				$order = (int) $meta;

			else
				$order = 0;

			if ( 'array' == $type ) {

				$list[] = [
					'post_id' => $data,
					$prop     => $order,
				];

			} else if ( 'assoc' == $type ) {

				$list[] = [
					'post_id' => $index,
					'data'    => $data,
					$prop     => $order,
				];

			} else if ( 'object' == $type ) {

				$data->{$prop} = $order;
				$list[] = $data;
			}
		}

		// Bailing if cannot determine the post ids
		if ( empty( $list ) )
			return $posts;

		if ( 'array' == $type )
			return array_column( Core\Arraay::sortByPriority( $list, $prop ), 'post_id' );

		if ( 'assoc' == $type )
			return array_column( Core\Arraay::sortByPriority( $list, $prop ), 'data', 'post_id' );

		return Core\Arraay::sortObjectByPriority( $list, $prop );
	}
}

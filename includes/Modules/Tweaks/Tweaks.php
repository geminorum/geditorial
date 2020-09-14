<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Listtable;
use geminorum\gEditorial\MetaBox;
use geminorum\gEditorial\Scripts;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Icon;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\Third;
use geminorum\gEditorial\Core\URL;
use geminorum\gEditorial\Core\WordPress;
use geminorum\gEditorial\WordPress\Media;
use geminorum\gEditorial\WordPress\PostType;
use geminorum\gEditorial\WordPress\User;

class Tweaks extends gEditorial\Module
{

	protected $priority_init = 14;
	private $enqueued_post = FALSE;

	public static function module()
	{
		return [
			'name'     => 'tweaks',
			'title'    => _x( 'Tweaks', 'Modules: Tweaks', 'geditorial' ),
			'desc'     => _x( 'Admin UI Enhancement', 'Modules: Tweaks', 'geditorial' ),
			'icon'     => 'admin-tools',
			'frontend' => FALSE,
		];
	}

	protected function settings_help_tabs( $context = 'settings' )
	{
		$tabs = [
			[
				'id'      => $this->classs( 'category-search' ),
				'title'   => _x( 'Category Search', 'Help Tab Title', 'geditorial-tweaks' ),
				'content' => '<div class="-info"><p>Makes it quick and easy for writers to select categories related to what they are writing. As they type in the search box, categories will be shown and hidden in real time, allowing them to easily select what is relevant to their content without having to scroll through possibly hundreds of categories.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/searchable-categories/" target="_blank">Searchable Categories</a> by <a href="http://ididntbreak.it" target="_blank">Jason Corradino</a></p></div>',
			],
			[
				'id'      => $this->classs( 'checklist-tree' ),
				'title'   => _x( 'Checklist Tree', 'Help Tab Title', 'geditorial-tweaks' ),
				'content' => '<div class="-info"><p>If you\'ve ever used categories extensively, you will have noticed that after you save a post, the checked categories appear on top of all the other ones. This can be useful if you have a lot of categories, since you don’t have to scroll. Unfortunately, this behaviour has a serious side-effect: it breaks the hierarchy. If you have deeply nested categories that don’t make sense out of context, this will completely screw you over.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/category-checklist-tree/" target="_blank">Category Checklist Tree</a> by <a href="http://scribu.net/wordpress/category-checklist-tree" target="_blank">scribu</a></p></div>',
			],
		];

		return array_merge( $tabs, parent::settings_help_tabs( $context ) );
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_editlist' => [
				[
					'field'       => 'group_taxonomies',
					'type'        => 'posttypes',
					'title'       => _x( 'Group Taxonomies', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Group selected taxonomies on selected post type edit pages', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'group_attributes',
					'type'        => 'posttypes',
					'title'       => _x( 'Group Attributes', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Group post attributes on selected post type edit pages', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'author_attribute',
					'type'        => 'posttypes',
					'title'       => _x( 'Author Attribute', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays author name as post type attribute', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_feature( 'author' ),
				],
				[
					'field'       => 'post_status',
					'type'        => 'posttypes',
					'title'       => _x( 'Post Status', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays post status as post type attribute', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'slug_attribute',
					'type'        => 'posttypes',
					'title'       => _x( 'Slug Attribute', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays post name as post type attribute.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'page_template',
					'type'        => 'posttypes',
					'title'       => _x( 'Page Template', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays the template used for the post.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'comment_status',
					'type'        => 'posttypes',
					'title'       => _x( 'Comment Status', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays only the closed comment status for the post.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_feature( 'comments' ),
				],
				[
					'field'       => 'search_meta',
					'type'        => 'posttypes',
					'title'       => _x( 'Search Meta', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Extends admin search to include custom fields.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude( 'day' ),
				],
			],
			'_columns' => [
				[
					'field'       => 'column_id',
					'type'        => 'posttypes',
					'title'       => _x( 'ID Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays ID Column on the post list table.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'column_order',
					'type'        => 'posttypes',
					'title'       => _x( 'Order Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays Order Column on the post list table.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude( [
						'publication',
						'day',
						'profile', // gPeople
					] ),
				],
				[
					'field'       => 'column_thumb',
					'type'        => 'posttypes',
					'title'       => _x( 'Thumbnail Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays Thumbnail Column on the post list table.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_feature( 'thumbnail', [
						'attachment:audio',
						'attachment:video',
						'profile', // gPeople
					] ),
				],
			],
			'_editpost' => [
				[
					'field'       => 'post_mainbox',
					'type'        => 'posttypes',
					'title'       => _x( 'Group Post-Boxes', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Groups common post-boxes into one for simpler editing experience.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'post_modified',
					'type'        => 'posttypes',
					'title'       => _x( 'Modified Action', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays last modified time as post misc action on publish metabox.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_exclude(),
				],
				[
					'field'       => 'post_excerpt',
					'type'        => 'posttypes',
					'title'       => _x( 'Advanced Excerpt', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Replaces the default Post Excerpt meta box with a superior editing experience.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->get_posttypes_support_feature( 'excerpt', [
						'inquiry',
						'day',
					] ),
				],
				[
					'field'       => 'after_title_excerpt',
					'title'       => _x( 'Excerpt After Title', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Moves up advanced excerpt to after title field.', 'Setting Description', 'geditorial-tweaks' ),
				],
				[
					'field'       => 'category_search',
					'title'       => _x( 'Category Search', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Replaces the category selector to include searching categories', 'Setting Description', 'geditorial-tweaks' ),
				],
				[
					'field'       => 'checklist_tree',
					'title'       => _x( 'Checklist Tree', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Preserves the category hierarchy on the post editing screen', 'Setting Description', 'geditorial-tweaks' ),
				],
			],
			'_comments' => [
				[
					'field'       => 'comments_user',
					'title'       => _x( 'Comments User Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays a logged-in comment author\'s site display name on the comments admin', 'Setting Description', 'geditorial-tweaks' ),
				],
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'title_column_title'    => _x( 'Title', 'Column Title', 'geditorial-tweaks' ),
				'rows_column_title'     => _x( 'Extra', 'Column Title', 'geditorial-tweaks' ),
				'atts_column_title'     => _x( 'Attributes', 'Column Title', 'geditorial-tweaks' ),
				'id_column_title'       => _x( 'ID', 'Column Title', 'geditorial-tweaks' ),
				'thumb_column_title'    => _x( 'Featured', 'Column Title', 'geditorial-tweaks' ),
				'order_column_title'    => _x( 'O', 'Column Title', 'geditorial-tweaks' ),
				'user_column_title'     => _x( 'User', 'Column Title', 'geditorial-tweaks' ),
				'contacts_column_title' => _x( 'Contacts', 'Column Title', 'geditorial-tweaks' ),
				'id_column_title'       => _x( 'ID', 'Column Title', 'geditorial-tweaks' ),
			],
			'js' => [
				'search_title'       => _x( 'Type to filter by', 'Meta Box Search Title', 'geditorial-tweaks' ),
				'search_placeholder' => _x( 'Search &hellip;', 'Meta Box Search Placeholder', 'geditorial-tweaks' ),
			],
		];
	}

	protected function taxonomies_excluded()
	{
		return Settings::taxonomiesExcluded( [
			'nav_menu',
			'post_format',
			'link_category',
			'bp_member_type',
			'bp_group_type',
			'bp-email-type',
			'ef_editorial_meta',
			'following_users',
			'ef_usergroup',
			'post_status',
			'flamingo_contact_tag',
			'flamingo_inbound_channel',
			'people',
			'rel_people',
			'rel_post',
			'affiliation',
			'entry_section',
			'specs',
			'label',
			'user_group',
			'cartable_user',
			'cartable_group',
			'follow_users',
			'follow_groups',
			'status',
		] );
	}

	// internal helper
	private function get_posttypes_support_feature( $feature, $extra_excludes = [] )
	{
		$posttypes = [];
		$supported = get_post_types_by_support( $feature );
		$excluded  = Settings::posttypesExcluded( $extra_excludes );

		foreach ( PostType::get( 0, [ 'show_ui' => TRUE ] ) as $posttype => $label )
			if ( in_array( $posttype, $supported ) && ! in_array( $posttype, $excluded ) )
				$posttypes[$posttype] = $label;

		return $posttypes;
	}

	// internal helper
	private function get_posttypes_support_exclude( $extra_excludes = [] )
	{
		$supported = PostType::get( 0, [ 'show_ui' => TRUE ] );
		$excluded  = Settings::posttypesExcluded( $extra_excludes );

		return array_diff_key( $supported, array_flip( $excluded ) );
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, $this->posttypes() ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		$enqueue = FALSE;

		if ( 'post' == $screen->base
			&& ! PostType::supportBlocks( $screen->post_type ) ) {

			if ( $this->in_setting( $screen->post_type, 'post_modified' ) ) )
				$this->action( 'post_submitbox_misc_actions', 1, 1 );

			if ( $this->get_setting( 'checklist_tree', FALSE ) ) {

				add_filter( 'wp_terms_checklist_args', function( $args ){
					return array_merge( $args, [ 'checked_ontop' => FALSE ] );
				} );

				$enqueue = TRUE;
			}

			if ( $this->get_setting( 'category_search', FALSE ) )
				$enqueue = TRUE;

			if ( $enqueue )
				$this->enqueue_asset_js( [
					'settings' => $this->options->settings,
					'strings'  => $this->strings['js'],
				], $screen );

		} else if ( 'edit' == $screen->base ) {

			if ( $this->posttype_supported( $screen->post_type ) ) {
				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
			}

			if ( $this->in_setting( $screen->post_type, 'search_meta' ) ) {
				$this->filter( 'posts_join', 2, 9 );
				$this->filter( 'posts_where', 2, 9 );
				$this->filter( 'posts_distinct', 2, 9 );
			}

		} else if ( 'users' == $screen->base ) {

			$this->filter( 'manage_users_columns', 1, 1 );
			$this->filter( 'manage_users_custom_column', 3, 1 );
			$this->filter( 'manage_users_sortable_columns' );

			// INTERNAL HOOKS
			add_action( $this->hook( 'column_user' ), [ $this, 'column_user_default' ] );
			add_action( $this->hook( 'column_contacts' ), [ $this, 'column_contacts_default' ] );

		} else if ( 'edit-tags' == $screen->base ) {

			// TODO: add support for taxonomy list table

		} else if ( 'edit-comments' == $screen->base ) {

			if ( $this->get_setting( 'comments_user', FALSE ) ) {

				$this->_admin_enabled();

				add_filter( 'manage_edit-comments_columns', [ $this, 'manage_comments_columns' ] );
				add_action( 'manage_comments_custom_column', [ $this, 'comments_custom_column' ], 10, 2 );

				// TODO: add sortable for comments
			}
		}
	}

	private function _edit_screen( $posttype )
	{
		add_filter( 'manage_taxonomies_for_'.$posttype.'_columns', [ $this, 'manage_taxonomies_columns' ], 10, 2 );

		add_filter( 'manage_posts_columns', [ $this, 'manage_posts_columns' ], 1, 2 );
		add_filter( 'manage_pages_columns', [ $this, 'manage_pages_columns' ], 1, 1 );
		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
		add_filter( 'manage_edit-'.$posttype.'_sortable_columns', [ $this, 'sortable_columns' ] );

		// add_filter( 'manage_'.$posttype.'_posts_columns', [ $this, 'manage_posts_columns_late' ], 999, 1 );
		// add_filter( 'list_table_primary_column', [ $this, 'list_table_primary_column' ], 10, 2 );

		if ( ! WordPress::isAJAX() && $this->in_setting( $posttype, 'column_thumb' ) )
			Scripts::enqueueThickBox();

		// INTERNAL HOOKS
		if ( $this->in_setting( $posttype, 'group_taxonomies' ) )
			add_action( $this->hook( 'column_row' ), [ $this, 'column_row_taxonomies' ] );

		if ( $this->in_setting( $posttype, 'author_attribute' ) && post_type_supports( $posttype, 'author' ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_author' ], 1 );

		if ( $this->in_setting( $posttype, 'post_status' ) && ! self::req( 'post_status' ) ) // if the view is NOT set
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_status' ], 2 );

		if ( $this->in_setting( $posttype, 'group_attributes' ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_default' ], 3 );

		if ( $this->in_setting( $posttype, 'page_template' ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_page_template' ], 50 );

		if ( $this->in_setting( $posttype, 'slug_attribute' ) && is_post_type_viewable( $posttype ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_slug' ], 50 );

		if ( $this->in_setting( $posttype, 'comment_status' ) && post_type_supports( $posttype, 'comments' ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_comment_status' ], 15 );
	}

	// we use this hook to early control `current_screen` on other modules
	public function add_meta_boxes( $posttype, $post )
	{
		if ( PostType::supportBlocksByPost( $post ) )
			return;

		$screen = get_current_screen();
		$object = PostType::object( $posttype );

		if ( $this->in_setting( $posttype, 'post_mainbox' ) ) {

			remove_meta_box( 'pageparentdiv', $screen, 'side' );
			// remove_meta_box( 'trackbacksdiv', $screen, 'normal' );
			remove_meta_box( 'commentstatusdiv', $screen, 'normal' );
			remove_meta_box( 'authordiv', $screen, 'normal' );
			remove_meta_box( 'slugdiv', $screen, 'normal' );

			$this->filter_false_module( 'module', 'metabox_parent' ); // for all modules

			add_meta_box( $this->classs( 'mainbox' ),
				$object->labels->attributes,
				[ $this, 'do_metabox_mainbox' ],
				$screen,
				'side',
				'low'
			);
		}

		if ( $this->in_setting( $posttype, 'post_excerpt' )
			&& post_type_supports( $posttype, 'excerpt' ) ) {

			// remove_meta_box( 'postexcerpt', $screen, 'normal' );

			MetaBox::classEditorBox( $screen );

			add_meta_box( 'postexcerpt',
				empty( $object->labels->excerpt_metabox ) ? __( 'Excerpt' ) : $object->labels->excerpt_metabox,
				[ $this, 'do_metabox_excerpt' ],
				$screen,
				$this->get_setting( 'after_title_excerpt' ) ? 'after_title' : 'normal'
			);
		}
	}

	// @REF: https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
	// join posts and postmeta tables
	public function posts_join( $join, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $join;

		global $wpdb;

		return $join." LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ";
	}

	// modify the search query with posts_where
	public function posts_where( $where, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $where;

		global $wpdb;

		return preg_replace( "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			"({$wpdb->posts}.post_title LIKE $1) OR ({$wpdb->postmeta}.meta_value LIKE $1)", $where );
	}

	// prevent duplicates
	public function posts_distinct( $distinct, $wp_query )
	{
		return $wp_query->is_search() ? "DISTINCT" : $distinct;
	}

	public function manage_taxonomies_columns( $taxonomies, $posttype )
	{
		foreach ( $this->taxonomies() as $taxonomy )
			unset( $taxonomies[$taxonomy] );

		return $taxonomies;
	}

	public function manage_pages_columns( $columns )
	{
		return $this->manage_posts_columns( $columns, 'page' );
	}

	public function manage_posts_columns( $columns, $posttype )
	{
		$new   = [];
		$added = FALSE;

		$ajax = WordPress::isAJAX();
		$rows = $ajax || has_action( $this->hook( 'column_row' ) ) ? $this->get_column_title( 'rows', $posttype ) : FALSE;
		$atts = $ajax || has_action( $this->hook( 'column_attr' ) ) ? $this->get_column_title( 'atts', $posttype ) : FALSE;

		foreach ( $columns as $key => $value ) {

			if ( 'title' == $key && $this->in_setting( $posttype, 'column_thumb' ) )
				$new[$this->classs( 'thumb' )] = $this->get_column_title( 'thumb', $posttype );

			if ( ( 'comments' == $key && ! $added )
				|| ( 'date' == $key && ! $added ) ) {

					if ( $rows )
						$new['geditorial-tweaks-rows'] = $rows;

					if ( $atts )
						$new['geditorial-tweaks-atts'] = $atts;

					$added = TRUE;
			}

			if ( 'author' == $key && $atts && $this->in_setting( $posttype, 'author_attribute' ) )
				continue;

			$new[$key] = $value;

			if ( 'cb' == $key && $this->in_setting( $posttype, 'column_order' ) )
				$new[$this->classs( 'order' )] = $this->get_column_title( 'order', $posttype );
		}

		if ( $this->in_setting( $posttype, 'group_attributes' ) )
			unset( $new['date'] );

		if ( ! $added ) {

			if ( $rows )
				$new['geditorial-tweaks-rows'] = $rows;

			if ( $atts )
				$new['geditorial-tweaks-atts'] = $atts;
		}

		if ( $this->in_setting( $posttype, 'column_id' ) )
			$new['geditorial-tweaks-id'] = $this->get_column_title( 'id', $posttype );

		return $new;
	}

	public function manage_posts_columns_late( $columns )
	{
		$new = [];

		foreach ( $columns as $key => $value )

			if ( 'title' == $key )
				$new['geditorial-tweaks-title'] = $this->get_column_title( 'title', WordPress::currentPostType( 'post' ) );

			else
				$new[$key] = $value;

		return $new;
	}

	public function list_table_primary_column( $default, $screen_id )
	{
		return 'geditorial-tweaks-title';
	}

	public function posts_custom_column( $column_name, $post_id )
	{
		global $post, $wp_list_table;

		switch ( $column_name ) {

			// FIXME: wont work beacuse of page-title css class
			case $this->classs( 'title' ):

				// TODO: add before action
				$wp_list_table->column_title( $post );
				// TODO: add after action
				// TODO: must hook to 'the_excerpt' for before excerpt
				// echo $wp_list_table->handle_row_actions( $post, 'title', $wp_list_table->get_primary_column_name() );

			break;
			case $this->classs( 'rows' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -rows"><ul class="-rows">';
					do_action( $this->hook( 'column_row' ), $post );
				echo '</ul></div>';

			break;
			case $this->classs( 'atts' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -atts"><ul class="-rows">';
					do_action( $this->hook( 'column_attr' ), $post );
				echo '</ul></div>';

			break;
			case $this->classs( 'thumb' ):

				$sizes = wp_get_additional_image_sizes();
				$size  = isset( $sizes[$post->post_type.'-thumbnail'] )
					? $post->post_type.'-thumbnail'
					: [ 45, 72 ];

				echo $this->filters( 'column_thumb', PostType::htmlFeaturedImage( $post_id, $size ), $post_id, $size );

			break;
			case $this->classs( 'order' ):

				echo Listtable::columnOrder( $post->menu_order );

			break;
			case $this->classs( 'id' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -id">';
					echo HTML::link( $post_id, WordPress::getPostShortLink( $post_id ), TRUE );
				echo '</div>';
		}
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, [
			$this->classs( 'order' ) => 'menu_order',
			$this->classs( 'atts' )  => [ 'date', TRUE ],
			$this->classs( 'id' )    => [ 'ID', TRUE ],
		] );
	}

	public function manage_users_columns( $columns )
	{
		$new = [];

		foreach ( $columns as $key => $value )

			if ( 'username' == $key ) {

				$new[$key] = $value;
				$new[$this->classs( 'user' )] = $this->get_column_title( 'rows', 'users' );

			} else if ( 'email' == $key ) {

				$new[$this->classs( 'contacts' )] = $this->get_column_title( 'contacts', 'users' );

			} else if ( 'posts' == $key ) {

				$new[$this->classs( 'id' )] = $this->get_column_title( 'id', 'users' );

			} else if ( in_array( $key, [
				'name',
				'role',
				'roles',
				'md_multiple_roles_column',
			] ) ) {

				// do nothing

			} else {
				$new[$key] = $value;
			}

		return $new;
	}

	public function manage_users_custom_column( $output, $column_name, $user_id )
	{
		if ( $this->classs( 'user' ) == $column_name ) {

			ob_start();

			echo '<div class="geditorial-admin-wrap-column -tweaks -user"><ul class="-rows">';
				do_action( $this->hook( 'column_user' ), get_userdata( $user_id ) );
			echo '</ul></div>';

			$output.= ob_get_clean();

		} else if ( $this->classs( 'contacts' ) == $column_name ) {

			ob_start();

			echo '<div class="geditorial-admin-wrap-column -tweaks -contacts"><ul class="-rows">';
				do_action( $this->hook( 'column_contacts' ), get_userdata( $user_id ) );
			echo '</ul></div>';

			$output.= ob_get_clean();

		} else if ( $this->classs( 'id' ) == $column_name ) {

			ob_start();

			echo '<div class="geditorial-admin-wrap-column -tweaks -id">';
				echo $user_id;
			echo '</div>';

			$output.= ob_get_clean();
		}

		return $output;
	}

	public function manage_users_sortable_columns( $columns )
	{
		return array_merge( $columns, [
			$this->classs( 'contacts' ) => 'email',
			$this->classs( 'id' )       => 'id',
		] );
	}

	public function manage_comments_columns( $columns )
	{
		$columns['user'] = $this->get_column_title( 'user', 'comments' );

		// move core WP response column to the end.
		if ( isset( $columns['response'] ) ) {
			$response = $columns['response'];
			unset( $columns['response'] );
			$columns['response'] = $response;
		}

		return $columns;
	}

	public function comments_custom_column( $column_name, $comment_id )
	{
		if ( 'user' !== $column_name )
			return;

		$comment = get_comment( $comment_id );

		if ( $comment->user_id && $user = get_userdata( $comment->user_id ) ) {

			// FIXME: make core helper
			printf( '<a href="%s">%s</a>',
				esc_url( add_query_arg( 'user_id', $comment->user_id,
					admin_url( 'edit-comments.php' ) ) ),
				HTML::escape( $user->display_name )
			);
		}
	}

	public function column_row_taxonomies( $post )
	{
		$taxonomies = get_object_taxonomies( $post->post_type );

		$cat = [ 'icon' => 'category', 'title' => __( 'Categories' ), 'edit' => NULL ];
		$tag = [ 'icon' => 'tag', 'title' => __( 'Tags' ), 'edit' => NULL ];

		foreach ( $this->taxonomies() as $taxonomy ) {

			if ( ! in_array( $taxonomy, $taxonomies ) )
				continue;

			$object = get_taxonomy( $taxonomy );

			$info = $this->filters( 'taxonomy_info', ( $object->hierarchical ? $cat : $tag ), $object, $post->post_type );

			if ( FALSE === $info )
				continue;

			if ( is_null( $info['edit'] ) )
				$info['edit'] = WordPress::getEditTaxLink( $object->name );

			$before = '<li class="-row tweaks-tax-'.$taxonomy.'">';
			$before.= $this->get_column_icon( $info['edit'], $info['icon'], $info['title'] );

			Helper::getTermsEditRow( $post, $object, $before, '</li>' );
		}
	}

	// @SEE: [Post Type Templates in 4.7](https://make.wordpress.org/core/?p=20437)
	// @SEE: [#18375 (Post type templates)](https://core.trac.wordpress.org/ticket/18375)
	// FIXME: use `get_file_description( untrailingslashit( get_stylesheet_directory() ).'/'.get_page_template_slug() )`
	public function column_attr_page_template( $post )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		if ( ! empty( $post->page_template )
			&& 'default' != $post->page_template ) {

			if ( ! isset( $this->page_templates[$post->post_type] ) )
				$this->page_templates[$post->post_type] = wp_get_theme()->get_page_templates( $post, $post->post_type );

			echo '<li class="-row tweaks-page-template">';

				echo $this->get_column_icon( FALSE, 'admin-page', _x( 'Page Template', 'Row Icon Title', 'geditorial-tweaks' ) );

				if ( ! empty( $this->page_templates[$post->post_type][$post->page_template] ) )
					echo '<span title="'.HTML::escape( $post->page_template ).'">'
						.HTML::escape( $this->page_templates[$post->post_type][$post->page_template] ).'</span>';
				else
					echo '<span>'.HTML::escape( $post->page_template ).'</span>';

			echo '</li>';
		}
	}

	public function column_attr_comment_status( $post )
	{
		if ( $filtered = comments_open( $post ) )
			return;

		echo '<li class="-row tweaks-page-template">';

			$link = add_query_arg( [ 'p' => $post->ID ], admin_url( 'edit-comments.php' ) );

			echo $this->get_column_icon( $link, 'welcome-comments', _x( 'Comment Status', 'Row Icon Title', 'geditorial-tweaks' ) );

			if ( 'closed' == $post->comment_status )
				$status = _x( 'Closed', 'Comment Status', 'geditorial-tweaks' );

			else if ( 'open' == $post->comment_status && ! $filtered )
				$status = _x( 'Closed for Old Posts', 'Comment Status', 'geditorial-tweaks' );

			else
				$status = $post->comment_status;

			/* translators: %s: status */
			printf( _x( 'Comments are %s', 'Comment Status', 'geditorial-tweaks' ), $status );

		echo '</li>';
	}

	public function column_attr_author( $post )
	{
		if ( ! isset( $this->site_user_id ) )
			$this->site_user_id = gEditorial()->user();

		if ( $post->post_author == $this->site_user_id )
			return;

		echo '<li class="-row tweaks-default-atts -post-author -post-author-'.$post->post_author.'">';
			echo $this->get_column_icon( FALSE, 'admin-users', _x( 'Author', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo '<span class="-author">'.WordPress::getAuthorEditHTML( $post->post_type, $post->post_author ).'</span>';
		echo '</li>';
	}

	public function column_attr_slug( $post )
	{
		if ( ! $post->post_name )
			return;

		echo '<li class="-row tweaks-default-atts -post-name">';
			echo $this->get_column_icon( FALSE, 'admin-links', _x( 'Post Slug', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo '<code>'.urldecode( $post->post_name ).'</code>';
		echo '</li>';
	}

	public function column_attr_status( $post )
	{
		if ( ! isset( $this->post_statuses ) )
			$this->post_statuses = PostType::getStatuses();

		if ( isset( $this->post_statuses[$post->post_status] ) )
			$status = HTML::escape( $this->post_statuses[$post->post_status] );
		else
			$status = $post->post_status;

		if ( 'future' === $post->post_status ) {

			$time_diff = time() - get_post_time( 'G', TRUE, $post );

			if ( $time_diff > 0 )
				$status = '<strong class="error-message">'._x( 'Missed schedule', 'Attr: Status', 'geditorial-tweaks' ).'</strong>';
		}

		echo '<li class="-row tweaks-default-atts -post-status -post-status-'.$post->post_status.'">';
			echo $this->get_column_icon( FALSE, 'post-status', _x( 'Status', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo '<span class="-status" title="'.$post->post_status.'">'.$status.'</span>';
		echo '</li>';
	}

	public function column_attr_default( $post )
	{
		echo '<li class="-row tweaks-default-atts -post-date">';
			echo $this->get_column_icon( FALSE, 'calendar-alt', _x( 'Publish Date', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo Helper::getDateEditRow( $post->post_date, '-date' );
		echo '</li>';

		if ( $post->post_modified != $post->post_date
			&& current_user_can( 'edit_post', $post->ID ) ) {

			echo '<li class="-row tweaks-default-atts -post-modified">';
				echo $this->get_column_icon( FALSE, 'edit', _x( 'Last Edit', 'Row Icon Title', 'geditorial-tweaks' ) );
				echo Helper::getModifiedEditRow( $post, '-edit' );
			echo '</li>';
		}
	}

	public function column_user_default( $user )
	{
		if ( $user->first_name || $user->last_name ) {
			echo '<li class="-row tweaks-user-atts -name">';
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Row Icon Title', 'geditorial-tweaks' ) );
				echo "$user->first_name $user->last_name";
			echo '</li>';
		}

		$role = $this->get_column_icon( FALSE, 'businessman', _x( 'Roles', 'Row Icon Title', 'geditorial-tweaks' ) );
		echo Helper::getJoined( User::getRoleList( $user ), '<li class="-row tweaks-user-atts -roles">'.$role, '</li>' );
	}

	public function column_contacts_default( $user )
	{
		if ( $user->user_email ) {
			echo '<li class="-row tweaks-user-contacts -email">';
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Row Icon Title', 'geditorial-tweaks' ) );
				echo HTML::mailto( $user->user_email );
			echo '</li>';
		}

		foreach ( wp_get_user_contact_methods( $user ) as $method => $title ) {

			if ( ! $meta = get_user_meta( $user->ID, $method, TRUE ) )
				continue;

			echo '<li class="-row tweaks-user-contacts -contact-'.$method.'">';
				echo $this->get_column_icon( FALSE, Icon::guess( $method, 'email-alt' ), $title );
				echo $this->display_meta( $meta, $method );
			echo '</li>';
		}
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'mobile'    : return HTML::tel( $value );
			case 'twitter'   : return Third::htmlTwitterIntent( $value, TRUE );
			case 'facebook'  : return HTML::link( URL::prepTitle( $value ), $value );
			case 'instagram' : return Third::htmlHandle( $value, 'https://instagram.com/' );
			case 'telegram'  : return Third::htmlHandle( $value, 'https://t.me/' );
		}

		return HTML::escape( $value );
	}

	public function do_metabox_mainbox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$posttype = PostType::object( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'mainbox', $post, $box );

			if ( post_type_supports( $posttype->name, 'author' ) && current_user_can( $posttype->cap->edit_others_posts ) )
				$this->do_mainbox_author( $post, $posttype );

			if ( ! ( 'pending' == get_post_status( $post ) && ! current_user_can( $posttype->cap->publish_posts ) ) )
				$this->do_mainbox_slug( $post, $posttype );

			if ( post_type_supports( $posttype->name, 'page-attributes' ) )
				$this->do_mainbox_parent( $post, $posttype );

			if ( get_option( 'page_for_posts' ) != $post->ID
				&& count( get_page_templates( $post ) ) > 0 )
					$this->do_mainbox_templates( $post, $posttype );

			if ( post_type_supports( $posttype->name, 'page-attributes' ) )
				$this->do_mainbox_menuorder( $post, $posttype );

			if ( post_type_supports( $posttype->name, 'comments' ) )
				$this->do_mainbox_comment_status( $post, $posttype );

			do_action( 'page_attributes_misc_attributes', $post );

		echo '</div>';
	}

	private function do_mainbox_parent( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_parent', $posttype->hierarchical, $posttype->name, $post ) )
			return;

		MetaBox::fieldPostParent( $post, FALSE );
	}

	private function do_mainbox_menuorder( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_menuorder', TRUE, $posttype->name, $post ) )
			return;

		MetaBox::fieldPostMenuOrder( $post );
	}

	private function do_mainbox_slug( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_slug', TRUE, $posttype->name, $post ) )
			return;

		MetaBox::fieldPostSlug( $post );
	}

	private function do_mainbox_author( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_author', TRUE, $posttype->name, $post ) )
			return;

		MetaBox::fieldPostAuthor( $post );
	}

	// @REF: `post_comment_status_meta_box()`
	private function do_mainbox_comment_status( $post, $posttype )
	{
		echo '<input name="advanced_view" type="hidden" value="1" />'; // FIXME: check this

		echo '<label for="comment_status" class="selectit">';
		echo '<input name="comment_status" type="checkbox" id="comment_status" value="open" ';
		checked( $post->comment_status, 'open' );
		echo ' /> '.__( 'Allow comments' );
		echo '</label><br />';

		echo '<label for="ping_status" class="selectit">';
		echo '<input name="ping_status" type="checkbox" id="ping_status" value="open" ';
		checked( $post->ping_status, 'open' );
		echo ' /> ';
		printf(
			/* translators: %s: codex url */
			_x( 'Allow <a href="%s">trackbacks and pingbacks</a>', 'MainBox', 'geditorial-tweaks' ),
			__( 'https://wordpress.org/support/article/introduction-to-blogging/#managing-comments' )
		);
		echo '</label>';

		do_action( 'post_comment_status_meta_box-options', $post );
	}

	private function do_mainbox_templates( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_templates', TRUE, $posttype->name, $post ) )
			return;

		$template = empty( $post->page_template ) ? FALSE : $post->page_template;

		do_action( 'page_attributes_meta_box_template', $template, $post );

		echo '<div class="-wrap field-wrap -select">';

			echo '<select name="page_template" id="page_template">';

				echo '<option value="default">';
					echo HTML::escape( apply_filters( 'default_page_template_title', __( 'Default Template' ), 'meta-box' ) );
				echo '</option>';

				page_template_dropdown( $template, $post->post_type );

		echo '</select></div>';
	}

	public function do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		MetaBox::fieldEditorBox( $post->post_excerpt );
	}

	public function post_submitbox_misc_actions( $post )
	{
		if (  $post->post_modified == $post->post_date )
			return;

		echo '<div class="-misc misc-pub-section misc-pub-modified">';
			echo $this->get_column_icon( FALSE, 'edit', _x( 'Last Modified', 'Misc Action', 'geditorial-tweaks' ) );
			echo _x( 'Modified:', 'Misc Action', 'geditorial-tweaks' );
			echo ' '.Helper::getModifiedEditRow( $post, '-edit' );
		echo '</div>';
	}
}

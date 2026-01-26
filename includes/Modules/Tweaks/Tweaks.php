<?php namespace geminorum\gEditorial\Modules\Tweaks;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Core;
use geminorum\gEditorial\Internals;
use geminorum\gEditorial\Services;
use geminorum\gEditorial\WordPress;

class Tweaks extends gEditorial\Module
{
	use Internals\PostMeta;

	protected $priority_init      = 14;
	protected $priority_init_ajax = 14;

	private $_page_templates = [];
	private $_site_user_id   = [];
	private $_post_statuses  = [];

	public static function module()
	{
		return [
			'name'     => 'tweaks',
			'title'    => _x( 'Tweaks', 'Modules: Tweaks', 'geditorial-admin' ),
			'desc'     => _x( 'Admin UI Enhancements', 'Modules: Tweaks', 'geditorial-admin' ),
			'icon'     => 'admin-tools',
			'frontend' => FALSE,
			'access'   => 'stable',
			'keywords' => [
				'admin',
				'tweaks',
			],
		];
	}

	protected function settings_help_tabs( $context = 'settings' )
	{
		return array_merge(
			ModuleInfo::getHelpTabs( $context ),
			parent::settings_help_tabs( $context )
		);
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
					'values'      => $this->_get_posttypes_support_exclude(),
				],
				[
					'field'       => 'group_attributes',
					'type'        => 'posttypes',
					'title'       => _x( 'Group Attributes', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Group post attributes on selected post type edit pages', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude(),
				],
				[
					'field'       => 'author_attribute',
					'type'        => 'posttypes',
					'title'       => _x( 'Author Attribute', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays author name as post type attribute', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_feature( 'author' ),
				],
				[
					'field'       => 'post_status',
					'type'        => 'posttypes',
					'title'       => _x( 'Post Status', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays post status as post type attribute', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude(),
				],
				[
					'field'       => 'slug_attribute',
					'type'        => 'posttypes',
					'title'       => _x( 'Slug Attribute', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays post name as post type attribute.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude(),
				],
				[
					'field'       => 'page_template',
					'type'        => 'posttypes',
					'title'       => _x( 'Page Template', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays the template used for the post.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude( [
						WordPress\WooCommerce::PRODUCT_POSTTYPE,
					] ),
				],
				[
					'field'       => 'comment_status',
					'type'        => 'posttypes',
					'title'       => _x( 'Comment Status', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays only the closed comment status for the post.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_feature( 'comments' ),
				],
				[
					'field'       => 'search_meta',
					'type'        => 'posttypes',
					'title'       => _x( 'Search Meta', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Extends admin search to include custom fields.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude( [
						'day',
					] ),
				],
			],
			'_columns' => [
				[
					'field'       => 'column_id',
					'type'        => 'posttypes',
					'title'       => _x( 'ID Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays ID Column on the post list table.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude( [
						WordPress\WooCommerce::PRODUCT_POSTTYPE,
					] ),
				],
				[
					'field'       => 'column_order',
					'type'        => 'posttypes',
					'title'       => _x( 'Order Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays Order Column on the post list table.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude( [
						WordPress\WooCommerce::PRODUCT_POSTTYPE,
						'publication',
						'day',
					] ),
				],
				[
					'field'       => 'column_thumb',
					'type'        => 'posttypes',
					'title'       => _x( 'Thumbnail Column', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays Thumbnail Column on the post list table.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_feature( 'thumbnail', [
						WordPress\WooCommerce::PRODUCT_POSTTYPE,
						'attachment:audio',
						'attachment:video',
					] ),
				],
			],
			'_editpost' => [
				[
					'field'       => 'post_mainbox',
					'type'        => 'posttypes',
					'title'       => _x( 'Group Post-Boxes', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Groups common post-boxes into one for simpler editing experience.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude(),
				],
				[
					'field'       => 'post_modified',
					'type'        => 'posttypes',
					'title'       => _x( 'Modified Action', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Displays last modified time as post misc action on publish metabox.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_exclude(),
				],
				[
					'field'       => 'post_excerpt',
					'type'        => 'posttypes',
					'title'       => _x( 'Advanced Excerpt', 'Setting Title', 'geditorial-tweaks' ),
					'description' => _x( 'Replaces the default Post Excerpt meta box with a superior editing experience.', 'Setting Description', 'geditorial-tweaks' ),
					'values'      => $this->_get_posttypes_support_feature( 'excerpt', [
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
				'order_column_title'    => _x( 'Order', 'Column Title', 'geditorial-tweaks' ),
				'user_column_title'     => _x( 'User', 'Column Title', 'geditorial-tweaks' ),
				'contacts_column_title' => _x( 'Contacts', 'Column Title', 'geditorial-tweaks' ),
				'id_column_title'       => _x( 'ID', 'Column Title', 'geditorial-tweaks' ),
			],
			'js' => [
				'editpost' => [
					'search_title'       => _x( 'Type to filter by', 'Meta Box Search Title', 'geditorial-tweaks' ),
					'search_placeholder' => _x( 'Search &hellip;', 'Meta Box Search Placeholder', 'geditorial-tweaks' ),
				],
			],
		];
	}

	protected function taxonomies_excluded( $extra = [] )
	{
		return $this->filters( 'taxonomies_excluded',
			gEditorial\Settings::taxonomiesExcluded( [
				'blood_type'     ,   // `Abo` Module
				'custom_status'  ,   // `Statuses` Module
				'entry_section'  ,   // `Entry` Module
				'equipment'      ,   // `Equipped` Module
				'human_status'   ,   // `Personage` Module
				'label'          ,   // `Labeled` Module
				'marital_status' ,   // `NextOfKin` Module
				'people'         ,   // `People` Module
				'specs'          ,   // `Specs` Module
				'gender'         ,   // `WasBorn` Module
				'age_group'      ,   // `WasBorn` Module
				'year_of_birth'  ,   // `WasBorn` Module
				'checklist_item' ,   // has it's own overview
			] + $extra, $this->keep_taxonomies )
		);
	}

	private function _get_posttypes_support_feature( $feature, $extra_excludes = [] )
	{
		$posttypes = [];
		$supported = get_post_types_by_support( $feature );
		$excluded  = gEditorial\Settings::posttypesExcluded( $extra_excludes, $this->keep_posttypes );

		foreach ( WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] ) as $posttype => $label )
			if ( in_array( $posttype, $supported ) && ! in_array( $posttype, $excluded ) )
				$posttypes[$posttype] = $label;

		return $posttypes;
	}

	// internal helper
	private function _get_posttypes_support_exclude( $extra = [] )
	{
		$supported = WordPress\PostType::get( 0, [ 'show_ui' => TRUE ] );
		$excluded  = Core\Arraay::prepString( $this->posttypes_excluded( $extra ) );

		return array_diff_key( $supported, array_flip( $excluded ) );
	}

	public function setup_ajax()
	{
		if ( $posttype = $this->is_inline_save_posttype( $this->posttypes() ) )
			$this->_edit_screen( $posttype );
	}

	public function current_screen( $screen )
	{
		$enqueue = FALSE;

		if ( 'post' == $screen->base
			&& ! WordPress\PostType::supportBlocks( $screen->post_type ) ) {

			if ( $this->in_setting( $screen->post_type, 'post_modified' ) )
				$this->action( 'post_submitbox_misc_actions', 1, 1 );

			if ( $this->get_setting( 'checklist_tree', FALSE ) ) {

				add_filter( 'wp_terms_checklist_args', static function ( $args ) {
					return array_merge( $args, [ 'checked_ontop' => FALSE ] );
				} );

				$enqueue = TRUE;
			}

			if ( $this->get_setting( 'category_search', FALSE ) )
				$enqueue = TRUE;

			if ( $enqueue )
				$this->enqueue_asset_js( [
					'settings' => $this->options->settings,
					'strings'  => $this->get_strings( 'editpost', 'js' ),
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
			$this->filter( 'manage_users_custom_column', 3, 99 );
			$this->filter( 'manage_users_sortable_columns' );

			// INTERNAL HOOKS
			add_action( $this->hook( 'column_user' ), [ $this, 'column_user_default' ], 10, 3 );
			add_action( $this->hook( 'column_contacts' ), [ $this, 'column_contacts_default' ], 20, 3 );

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
		$this->filter_unset( 'manage_taxonomies_for_'.$posttype.'_columns', $this->taxonomies(), 12 );

		add_filter( sprintf( 'manage_%s_posts_columns', $posttype ),
			function ( $columns ) use ( $posttype ) {
				return $this->manage_posts_columns( $columns, $posttype );
			}, WordPress\WooCommerce::PRODUCT_POSTTYPE === $posttype ? 11 : 1, 1 );

		add_filter( 'manage_edit-'.$posttype.'_sortable_columns', [ $this, 'sortable_columns' ] );
		add_action( 'manage_'.$posttype.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );

		// add_filter( 'manage_'.$posttype.'_posts_columns', [ $this, 'manage_posts_columns_late' ], 999, 1 );
		// add_filter( 'list_table_primary_column', [ $this, 'list_table_primary_column' ], 10, 2 );

		if ( ! WordPress\IsIt::ajax() && $this->in_setting( $posttype, 'column_thumb' ) )
			gEditorial\Scripts::enqueueThickBox();

		// INTERNAL HOOKS
		if ( $this->in_setting( $posttype, 'group_taxonomies' ) )
			add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_taxonomies' ], 10, 4 );

		if ( $this->in_setting( $posttype, 'author_attribute' ) && $this->is_posttype_support( $posttype, 'author' ) )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_author' ], 1, 3 );

		if ( $this->in_setting( $posttype, 'post_status' ) && ! self::req( 'post_status' ) ) // if the view is NOT set
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_status' ], 2, 3 );

		if ( $this->in_setting( $posttype, 'group_attributes' ) && $this->is_posttype_support( $posttype, 'date' ) )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_date' ], 3, 3 );

		if ( $this->in_setting( $posttype, 'page_template' ) )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_page_template' ], 50, 3 );

		if ( $this->in_setting( $posttype, 'slug_attribute' ) && WordPress\PostType::viewable( $posttype ) )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_slug' ], 50, 3 );

		if ( $this->in_setting( $posttype, 'comment_status' ) && $this->is_posttype_support( $posttype, 'comments' ) )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_comment_status' ], 15, 3 );

		if ( WordPress\WooCommerce::PRODUCT_POSTTYPE !== $posttype )
			return;

		if ( WordPress\WooCommerce::skuEnabled() )
			add_action( $this->hook( 'column_row', $posttype ), [ $this, 'column_row_sku' ], 99, 4 );

		if ( WordPress\WooCommerce::manageStock() )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_stock' ], -10, 3 );

		if ( WordPress\WooCommerce::featureEnabled( 'cost_of_goods_sold' ) )
			add_action( $this->hook( 'column_attr', $posttype ), [ $this, 'column_attr_cogs_value' ], -8, 3 );

		// Avoid error notice from woo-commerce
		if ( ! empty( $GLOBALS['WC_Brands_Admin'] ) )
			remove_filter( 'manage_product_posts_columns', [ $GLOBALS['WC_Brands_Admin'], 'product_columns' ], 20, 1 );
	}

	// Using this hook to early control `current_screen` on other modules
	public function add_meta_boxes( $posttype, $post )
	{
		if ( WordPress\Post::supportBlocks( $post ) )
			return;

		$screen = get_current_screen();
		$object = WordPress\PostType::object( $posttype );

		if ( $this->in_setting( $posttype, 'post_mainbox' ) ) {

			remove_meta_box( 'pageparentdiv', $screen, 'side' );
			// remove_meta_box( 'trackbacksdiv', $screen, 'normal' );
			remove_meta_box( 'commentstatusdiv', $screen, 'normal' );
			remove_meta_box( 'authordiv', $screen, 'normal' );
			remove_meta_box( 'slugdiv', $screen, 'normal' );

			// $this->filter_false_module( 'module', 'metabox_parent' ); // for all modules

			add_meta_box( $this->classs( 'mainbox' ),
				$object->labels->attributes,
				[ $this, 'do_metabox_mainbox' ],
				$screen,
				'side',
				'low'
			);
		}

		if ( $this->in_setting( $posttype, 'post_excerpt' )
			&& $this->is_posttype_support( $posttype, 'excerpt' ) ) {

			// remove_meta_box( 'postexcerpt', $screen, 'normal' );

			gEditorial\MetaBox::classEditorBox( $screen );

			add_meta_box( 'postexcerpt',
				empty( $object->labels->excerpt_label ) ? __( 'Excerpt' ) : $object->labels->excerpt_label,
				[ $this, 'do_metabox_excerpt' ],
				$screen,
				$this->get_setting( 'after_title_excerpt' ) ? 'after_title' : 'normal'
			);
		}
	}

	// TODO: move to Services
	// @REF: https://adambalee.com/search-wordpress-by-custom-fields-without-a-plugin/
	// join posts and post-meta tables
	public function posts_join( $join, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $join;

		global $wpdb;

		return $join." LEFT JOIN {$wpdb->postmeta} AS searchmeta ON {$wpdb->posts}.ID = searchmeta.post_id ";
	}

	// Modifies the search query with posts_where
	public function posts_where( $where, $wp_query )
	{
		if ( ! $wp_query->is_search() )
			return $where;

		global $wpdb;

		return preg_replace(
			"/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			"({$wpdb->posts}.post_title LIKE $1) OR (searchmeta.meta_value LIKE $1)",
		$where );
	}

	// prevent duplicates
	public function posts_distinct( $distinct, $wp_query )
	{
		return $wp_query->is_search() ? "DISTINCT" : $distinct;
	}

	public function manage_posts_columns( $columns, $posttype )
	{
		$new  = [];
		$rows = has_action( $this->hook( 'column_row', $posttype ) ) ? $this->get_column_title( 'rows', $posttype ) : FALSE;
		$atts = has_action( $this->hook( 'column_attr', $posttype ) ) ? $this->get_column_title( 'atts', $posttype ) : FALSE;

		// simplify the logic!
		$rows_added = ! $rows;
		$atts_added = ! $atts;

		if ( WordPress\WooCommerce::PRODUCT_POSTTYPE === $posttype ) {
			$columns = Core\Arraay::stripByKeys( $columns, $this->taxonomies() ); // lately added by woo-commerce
			unset(
				$columns['is_in_stock'],
				$columns['cogs_value'],
				$columns['sku']
			);
		}

		foreach ( $columns as $key => $value ) {

			if ( in_array( $key, [ 'title', 'name' ], TRUE )
				&& $this->in_setting( $posttype, 'column_thumb' ) )
					$new[$this->classs( 'thumb' )] = $this->get_column_title_icon( 'thumb', $posttype );

			// last resort checks
			if ( in_array( $key, [ 'comments', 'date' ], TRUE ) ) {

				if ( ! $rows_added )
					$rows_added = $new['geditorial-tweaks-rows'] = $rows;

				if ( ! $atts_added )
					$atts_added = $new['geditorial-tweaks-atts'] = $atts;
			}

			if ( 'author' == $key && $atts && $this->in_setting( $posttype, 'author_attribute' ) )
				continue;

			$new[$key] = $value;

			if ( 'cb' == $key && $this->in_setting( $posttype, 'column_order' ) )
				$new[$this->classs( 'order' )] = $this->get_column_title_icon( 'order', $posttype );

			if ( WordPress\WooCommerce::PRODUCT_POSTTYPE === $posttype ) {

				if ( ! $rows_added && in_array( $key, [ 'title', 'name' ], TRUE ) )
					$rows_added = $new['geditorial-tweaks-rows'] = $rows;

				// in case we keep the `cogs_value` column
				// first check for `cogs_value` then `price`
				// since they came before each other.
				if ( ! $atts_added && 'cogs_value' === $key )
					$atts_added = $new['geditorial-tweaks-atts'] = $atts;

				else if ( ! $atts_added && 'price' === $key
					&& ! array_key_exists( 'cogs_value', $columns ) )
						$atts_added = $new['geditorial-tweaks-atts'] = $atts;
			}
		}

		if ( $this->in_setting( $posttype, 'group_attributes' )
			|| ! $this->is_posttype_support( $posttype, 'date' ) )
				unset( $new['date'] );

		if ( ! $rows_added )
			$new['geditorial-tweaks-rows'] = $rows;

		if ( ! $atts_added )
			$new['geditorial-tweaks-atts'] = $atts;

		if ( $this->in_setting( $posttype, 'column_id' ) )
			$new['geditorial-tweaks-id'] = $this->get_column_title_icon( 'id', $posttype );

		return $new;
	}

	public function manage_posts_columns_late( $columns )
	{
		$new = [];

		foreach ( $columns as $key => $value )

			if ( 'title' == $key )
				$new['geditorial-tweaks-title'] = $this->get_column_title( 'title', WordPress\PostType::current( 'post' ) );

			else
				$new[$key] = $value;

		return $new;
	}

	public function list_table_primary_column( $default, $screen_id )
	{
		return 'geditorial-tweaks-title';
	}

	public function posts_custom_column( $column, $post_id )
	{
		global $post, $wp_list_table;

		if ( $this->check_hidden_column( $column ) )
			return;

		switch ( $column ) {

			// FIXME: wont work because of page-title CSS class
			case $this->classs( 'title' ):

				// TODO: add before action
				$wp_list_table->column_title( $post );
				// TODO: add after action
				// TODO: must hook to 'the_excerpt' for before excerpt
				// echo $wp_list_table->handle_row_actions( $post, 'title', $wp_list_table->get_primary_column_name() );
				break;

			case $this->classs( 'rows' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -rows"><ul class="-rows -flex-rows">';

					// NOTE: DEPRECATED
					do_action( $this->hook( 'column_row' ), $post );

					do_action( $this->hook( 'column_row', $post->post_type ),
						$post,
						$this->wrap_open_row( 'row', [
							'-column-row',
							'-type-'.$post->post_type,
							'%s',
						] ),
						'</li>',
						$this->module->name
					);

				echo '</ul></div>';
				break;

			case $this->classs( 'atts' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -atts"><ul class="-rows">';

					// NOTE: DEPRECATED
					do_action( $this->hook( 'column_attr' ), $post );

					do_action( $this->hook( 'column_attr', $post->post_type ),
						$post,
						$this->wrap_open_row( 'attr', [
							'-column-attr',
							'-type-'.$post->post_type,
							'%s', // to use by caller
						] ),
						'</li>'
					);

				echo '</ul></div>';
				break;

			case $this->classs( 'thumb' ):

				$size = NULL; // MAYBE: filter for this module?!

				echo $this->filters( 'column_thumb',
					WordPress\PostType::htmlFeaturedImage( $post_id, $size ),
					$post_id,
					$size
				);

				break;

			case $this->classs( 'order' ):

				echo gEditorial\Listtable::columnOrder( $post->menu_order );
				break;

			case $this->classs( 'id' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -id">';
					echo Core\HTML::link( $post_id, WordPress\Post::shortlink( $post_id ), TRUE );
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

				$new[$this->classs( 'id' )] = $this->get_column_title_icon( 'id', 'users' );

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

	public function manage_users_custom_column( $output, $column, $user_id )
	{
		if ( $this->classs( 'user' ) == $column ) {

			if ( $this->check_hidden_column( $column ) )
				return $output;

			ob_start();

			echo '<div class="geditorial-admin-wrap-column -tweaks -user"><ul class="-rows">';
				do_action( $this->hook( 'column_user' ),
					get_userdata( $user_id ),
					$this->wrap_open_row( 'attr', [
						'-column-user',
						'%s', // to use by caller
					] ),
					'</li>'
				);
			echo '</ul></div>';

			$output.= ob_get_clean();

		} else if ( $this->classs( 'contacts' ) == $column ) {

			if ( $this->check_hidden_column( $column ) )
				return $output;

			ob_start();

			echo '<div class="geditorial-admin-wrap-column -tweaks -contacts"><ul class="-rows">';
				do_action( $this->hook( 'column_contacts' ),
					get_userdata( $user_id ),
					$this->wrap_open_row( 'attr', [
						'-column-contacts',
						'%s', // to use by caller
					] ),
					'</li>'
				);
			echo '</ul></div>';

			$output.= ob_get_clean();

		} else if ( $this->classs( 'id' ) == $column ) {

			if ( $this->check_hidden_column( $column ) )
				return $output;

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

		// Move core WP response column to the end.
		if ( isset( $columns['response'] ) ) {
			$response = $columns['response'];
			unset( $columns['response'] );
			$columns['response'] = $response;
		}

		return $columns;
	}

	public function comments_custom_column( $column, $comment_id )
	{
		if ( 'user' !== $column )
			return;

		if ( $this->check_hidden_column( $column ) )
			return;

		$comment = WordPress\Comment::get( $comment_id );

		if ( $comment->user_id && $user = get_userdata( $comment->user_id ) ) {

			// FIXME: make core helper
			printf( '<a href="%s">%s</a>',
				esc_url( add_query_arg( 'user_id', $comment->user_id,
					admin_url( 'edit-comments.php' ) ) ),
				Core\HTML::escape( $user->display_name )
			);
		}
	}

	public function column_row_taxonomies( $post, $before, $after, $module )
	{
		$taxonomies = get_object_taxonomies( $post->post_type );

		foreach ( $this->taxonomies() as $taxonomy ) {

			if ( ! in_array( $taxonomy, $taxonomies ) )
				continue;

			if ( ! $object = get_taxonomy( $taxonomy ) )
				continue;

			if ( ! empty( $object->{Services\Paired::PAIRED_POSTTYPE_PROP} ) )
				continue;

			$edit  = WordPress\Taxonomy::edit( $object, [ 'post_type' => $post->post_type ] );
			$icon  = $object->menu_icon ?? ( $object->hierarchical ? 'category' : 'tag' );
			$title = Services\CustomTaxonomy::getLabel( $object, 'extended_label' );

			gEditorial\Helper::renderPostTermsEditRow(
				$post,
				$object,
				sprintf( $before, '-taxonomy-'.$taxonomy ).$this->get_column_icon( $edit, $icon, $title ),
				$after
			);
		}
	}

	// @SEE: [Post Type Templates in 4.7](https://make.wordpress.org/core/?p=20437)
	// @SEE: [#18375 (Post type templates)](https://core.trac.wordpress.org/ticket/18375)
	// FIXME: use `get_file_description( untrailingslashit( get_stylesheet_directory() ).'/'.get_page_template_slug() )`
	public function column_attr_page_template( $post, $before, $after )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		if ( ! empty( $post->page_template )
			&& 'default' != $post->page_template ) {

			if ( ! isset( $this->_page_templates[$post->post_type] ) )
				$this->_page_templates[$post->post_type] = wp_get_theme()->get_page_templates( $post, $post->post_type );

			printf( $before, '-page-template' );

				echo $this->get_column_icon( FALSE, 'admin-page', _x( 'Page Template', 'Row Icon Title', 'geditorial-tweaks' ) );

				if ( ! empty( $this->_page_templates[$post->post_type][$post->page_template] ) )
					echo '<span title="'.Core\HTML::escape( $post->page_template ).'">'
						.Core\HTML::escape( $this->_page_templates[$post->post_type][$post->page_template] ).'</span>';
				else
					echo '<span>'.Core\HTML::escape( $post->page_template ).'</span>';

			echo $after;
		}
	}

	public function column_attr_comment_status( $post, $before, $after )
	{
		if ( $filtered = comments_open( $post ) )
			return;

		printf( $before, '-comment-status' );

			$link = add_query_arg( [ 'p' => $post->ID ], admin_url( 'edit-comments.php' ) );

			echo $this->get_column_icon( $link, 'welcome-comments', _x( 'Comment Status', 'Row Icon Title', 'geditorial-tweaks' ) );

			if ( 'closed' == $post->comment_status )
				$status = _x( 'Closed', 'Comment Status', 'geditorial-tweaks' );

			else if ( 'open' == $post->comment_status && ! $filtered )
				$status = _x( 'Closed for Old Posts', 'Comment Status', 'geditorial-tweaks' );

			else
				$status = $post->comment_status;

			printf(
				/* translators: `%s`: status */
				_x( 'Comments are %s', 'Comment Status', 'geditorial-tweaks' ),
				$status
			);

		echo $after;
	}

	public function column_attr_author( $post, $before, $after )
	{
		if ( empty( $this->_site_user_id ) )
			$this->_site_user_id = gEditorial()->user();

		if ( $post->post_author == $this->_site_user_id )
			return;

		printf( $before, '-post-author' );
			echo $this->get_column_icon( FALSE, 'admin-users', _x( 'Author', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo '<span class="-author">'.WordPress\PostType::authorEditMarkup( $post->post_type, $post->post_author ).'</span>';
		echo '</li>';
	}

	public function column_attr_slug( $post, $before, $after )
	{
		if ( ! $post->post_name )
			return;

		printf( $before, '-post-name' );
			echo $this->get_column_icon( FALSE, 'admin-links', _x( 'Post Slug', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo Core\HTML::code( urldecode( $post->post_name ) );
		echo $after;
	}

	public function column_attr_status( $post, $before, $after )
	{
		if ( empty( $this->_post_statuses ) )
			$this->_post_statuses = WordPress\Status::get();

		if ( isset( $this->_post_statuses[$post->post_status] ) )
			$status = Core\HTML::escape( $this->_post_statuses[$post->post_status] );

		else
			$status = $post->post_status;

		if ( 'future' === $post->post_status ) {

			$time_diff = time() - get_post_time( 'G', TRUE, $post );

			if ( $time_diff > 0 )
				$status = '<strong class="error-message">'._x( 'Missed schedule', 'Attr: Status', 'geditorial-tweaks' ).'</strong>';
		}

		printf( $before, '-post-status -post-status-'.$post->post_status );
			echo $this->get_column_icon( FALSE, 'post-status', _x( 'Status', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo '<span class="-status" title="'.$post->post_status.'">'.$status.'</span>';
		echo $after;
	}

	public function column_attr_date( $post, $before, $after )
	{
		printf( $before, '-post-date' );
			echo $this->get_column_icon( FALSE, 'calendar-alt', _x( 'Publish Date', 'Row Icon Title', 'geditorial-tweaks' ) );
			echo gEditorial\Helper::getDateEditRow( $post->post_date, '-date' );
		echo $after;

		if ( $post->post_modified != $post->post_date
			&& current_user_can( 'edit_post', $post->ID ) ) {

			printf( $before, '-post-modified' );
				echo $this->get_column_icon( FALSE, 'edit', _x( 'Last Edit', 'Row Icon Title', 'geditorial-tweaks' ) );
				echo gEditorial\Helper::getModifiedEditRow( $post, '-edit' );
			echo $after;
		}
	}

	public function column_row_sku( $post, $before, $after, $module )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		if ( ! $html = $product->get_sku() )
			return;

		printf( $before, '-product-sku' );
			echo $this->get_column_icon( FALSE, 'store', __( 'SKU', 'woocommerce' ), $post->post_type ); // TODO: better icon
			echo Core\HTML::code( $html, '-sku', TRUE );
		echo $after;
	}

	public function column_attr_stock( $post, $before, $after )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		printf( $before, '-product-stock' );

			if ( $product->is_on_backorder() ) {

				$html = Core\HTML::mark( __( 'On backorder', 'woocommerce' ), 'onbackorder' );
				$icon = 'archive'; // TODO: better icon

			} else if ( $product->is_in_stock() ) {

				$html = Core\HTML::mark( __( 'In stock', 'woocommerce' ), 'instock' );
				$icon = 'archive'; // TODO: better icon

			} else {

				$html = Core\HTML::mark( __( 'Out of stock', 'woocommerce' ), 'outofstock' );
				$icon = 'archive'; // TODO: better icon
			}

			if ( $product->managing_stock() )
				$html.= sprintf( ' (%s)', wc_stock_amount( $product->get_stock_quantity() ) );

			echo $this->get_column_icon( FALSE, $icon, __( 'Stock', 'woocommerce' ), $post->post_type );
			echo wp_kses_post( apply_filters( 'woocommerce_admin_stock_html', $html, $product ) );

		echo $after;
	}

	public function column_attr_cogs_value( $post, $before, $after )
	{
		global $product;

		if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) )
			return;

		if ( ! $html = $product->get_cogs_value_html() )
			return;

		printf( $before, '-product-cost' );
			echo $this->get_column_icon( FALSE, 'store', __( 'Cost', 'woocommerce' ), $post->post_type ); // TODO: better icon
			echo wp_kses_post( $html );
		echo $after;
	}

	public function column_user_default( $user, $before, $after )
	{
		if ( $user->first_name || $user->last_name ) {
			printf( $before, '-user-fullname' );
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Row Icon Title', 'geditorial-tweaks' ) );
				echo "$user->first_name $user->last_name";
			echo $after;
		}

		$role = $this->get_column_icon( FALSE, 'businessman', _x( 'Roles', 'Row Icon Title', 'geditorial-tweaks' ) );
		echo WordPress\Strings::getJoined( WordPress\Role::get( 0, [], $user ), '<li class="-row tweaks-user-atts -roles">'.$role, $after );
	}

	public function column_contacts_default( $user, $before, $after )
	{
		if ( $user->user_email ) {
			printf( $before, '-user-contact -email' );
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Row Icon Title', 'geditorial-tweaks' ) );
				echo Core\HTML::mailto( $user->user_email );
			echo $after;
		}

		foreach ( wp_get_user_contact_methods( $user ) as $method => $title ) {

			if ( ! $value = get_user_meta( $user->ID, $method, TRUE ) )
				continue;

			printf( $before, '-user-contact -contact-'.$method );
				echo $this->get_column_icon( FALSE, Core\Icon::guess( $method, 'email-alt' ), $title );
				echo $this->prep_meta_row( $value, $method, [ 'type' => 'contact_method', 'title' => $title ], $value );
			echo $after;
		}
	}

	public function do_metabox_mainbox( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		$posttype = WordPress\PostType::object( $post->post_type );

		echo $this->wrap_open( '-admin-metabox' );
			$this->actions( 'mainbox', $post, $box );

			if ( $this->is_posttype_support( $posttype->name, 'author' ) && current_user_can( $posttype->cap->edit_others_posts ) )
				$this->do_mainbox_author( $post, $posttype );

			if ( ! ( 'pending' == get_post_status( $post ) && ! current_user_can( $posttype->cap->publish_posts ) ) )
				$this->do_mainbox_slug( $post, $posttype );

			if ( $this->is_posttype_support( $posttype->name, 'page-attributes' ) )
				$this->do_mainbox_parent( $post, $posttype );

			if ( get_option( 'page_for_posts' ) != $post->ID
				&& count( get_page_templates( $post ) ) > 0 )
					$this->do_mainbox_templates( $post, $posttype );

			if ( $this->is_posttype_support( $posttype->name, 'page-attributes' ) )
				$this->do_mainbox_menuorder( $post, $posttype );

			if ( $this->is_posttype_support( $posttype->name, 'comments' ) )
				$this->do_mainbox_comment_status( $post, $posttype );

			do_action( 'page_attributes_misc_attributes', $post );

		echo '</div>';
	}

	private function do_mainbox_parent( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_parent', $posttype->hierarchical, $posttype->name, $post ) )
			return;

		gEditorial\MetaBox::fieldPostParent( $post, FALSE );
	}

	private function do_mainbox_menuorder( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_menuorder', TRUE, $posttype->name, $post ) )
			return;

		gEditorial\MetaBox::fieldPostMenuOrder( $post );
	}

	private function do_mainbox_slug( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_slug', empty( $posttype->slug_disabled ), $posttype->name, $post ) )
			return;

		gEditorial\MetaBox::fieldPostSlug( $post );
	}

	private function do_mainbox_author( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_author', empty( $posttype->author_disabled ), $posttype->name, $post ) )
			return;

		gEditorial\MetaBox::fieldPostAuthor( $post );
	}

	// @REF: `post_comment_status_meta_box()`
	private function do_mainbox_comment_status( $post, $posttype )
	{
		if ( ! $this->filters( 'metabox_commentstatus', TRUE, $posttype->name, $post ) )
			return;

		echo '<div class="-wrap field-wrap -checkbox">';
		echo '<label for="comment_status" class="selectit">';
		echo '<input name="comment_status" type="checkbox" id="comment_status" value="open" ';
		checked( $post->comment_status, 'open' );
		echo ' /> '.__( 'Allow comments' );
		echo '</label>';
		echo '</div>';

		echo '<div class="-wrap field-wrap -checkbox">';
		echo '<label for="ping_status" class="selectit">';
		echo '<input name="ping_status" type="checkbox" id="ping_status" value="open" ';
		checked( $post->ping_status, 'open' );
		echo ' /> ';
		printf(
			/* translators: `%s`: Documentation URL */
			_x( 'Allow <a href="%s">trackbacks and pingbacks</a>', 'MainBox', 'geditorial-tweaks' ),
			__( 'https://wordpress.org/support/article/introduction-to-blogging/#comments' )
		);
		echo '</label>';
		echo '</div>';

		do_action( 'post_comment_status_meta_box-options', $post );

		echo '<input name="advanced_view" type="hidden" value="1" />'; // FIXME: check this
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
					echo Core\HTML::escape( apply_filters( 'default_page_template_title', __( 'Default Template' ), 'meta-box' ) );
				echo '</option>';

				page_template_dropdown( $template, $post->post_type );

		echo '</select></div>';
	}

	public function do_metabox_excerpt( $post, $box )
	{
		if ( $this->check_hidden_metabox( $box, $post->post_type ) )
			return;

		gEditorial\MetaBox::fieldEditorBox( $post->post_excerpt );
	}

	public function post_submitbox_misc_actions( $post )
	{
		if ( $post->post_modified === $post->post_date )
			return;

		echo '<div class="-misc misc-pub-section misc-pub-modified">';
			echo $this->get_column_icon( FALSE, 'edit', _x( 'Last Modified', 'Misc Action', 'geditorial-tweaks' ) );
			echo _x( 'Modified:', 'Misc Action', 'geditorial-tweaks' );
			echo ' '.gEditorial\Helper::getModifiedEditRow( $post, '-edit' );
		echo '</div>';
	}
}

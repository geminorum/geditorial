<?php namespace geminorum\gEditorial\Modules;

defined( 'ABSPATH' ) or die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial;
use geminorum\gEditorial\Helper;
use geminorum\gEditorial\Settings;
use geminorum\gEditorial\Core\HTML;
use geminorum\gEditorial\Core\Number;
use geminorum\gEditorial\Core\WordPress;
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
			'title'    => _x( 'Tweaks', 'Modules: Tweaks', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Admin UI Enhancement', 'Modules: Tweaks', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'admin-settings',
			'frontend' => FALSE,
		];
	}

	protected function settings_help_tabs()
	{
		$tabs = Settings::settingsHelpContent( $this->module );

		$tabs[] = [
			'id'       => 'geditorial-tweaks-category_search',
			'title'    => _x( 'Category Search', 'Modules: Tweaks: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>Makes it quick and easy for writers to select categories related to what they are writing. As they type in the search box, categories will be shown and hidden in real time, allowing them to easily select what is relevant to their content without having to scroll through possibly hundreds of categories.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/searchable-categories/" target="_blank">Searchable Categories</a> by <a href="http://ididntbreak.it" target="_blank">Jason Corradino</a></p></div>',
		];

		$tabs[] = [
			'id'       => 'geditorial-tweaks-checklist_tree',
			'title'    => _x( 'Checklist Tree', 'Modules: Tweaks: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>If you’ve ever used categories extensively, you will have noticed that after you save a post, the checked categories appear on top of all the other ones. This can be useful if you have a lot of categories, since you don’t have to scroll.</p>
<p>Unfortunately, this behaviour has a serious side-effect: it breaks the hierarchy. If you have deeply nested categories that don’t make sense out of context, this will completely screw you over.</p>
<p>It preserves the category tree at all times. Just activate it and you’re good.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/category-checklist-tree/" target="_blank">Category Checklist Tree</a> by <a href="http://scribu.net/wordpress/category-checklist-tree" target="_blank">scribu</a></p></div>',
		];

		return $tabs;
	}

	protected function get_global_settings()
	{
		return [
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_general' => [
				[
					'field'       => 'group_attributes',
					'title'       => _x( 'Group Attributes', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group post attributes on selected post type edit pages', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'column_id',
					'title'       => _x( 'ID Column', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays ID Column on the post list table.', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'column_order',
					'type'        => 'posttypes',
					'title'       => _x( 'Order Column', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays Order Column on the post list table.', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_posttypes_support_order(),
				],
				[
					'field'       => 'column_thumb',
					'type'        => 'posttypes',
					'title'       => _x( 'Thumbnail Column', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays Thumbnail Column on the post list table.', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'values'      => $this->get_posttypes_support_thumbnail(),
				],
				[
					'field'       => 'attachment_count',
					'title'       => _x( 'Attachment Count', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays attachment summary of the post.', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'author_attribute',
					'title'       => _x( 'Author Attribute', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays author name as post type attribute', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'page_template',
					'title'       => _x( 'Page Template', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays the template used for the post.', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'comment_status',
					'title'       => _x( 'Comment Status', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays only the closed comment status for the post.', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'category_search',
					'title'       => _x( 'Category Search', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Replaces the category selector to include searching categories', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'checklist_tree',
					'title'       => _x( 'Checklist Tree', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Preserves the category hierarchy on the post editing screen', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'excerpt_count',
					'title'       => _x( 'Excerpt Count', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display word count for excerpt textareas', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'comments_user',
					'title'       => _x( 'Comments User Column', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays a logged-in comment author\'s site display name on the comments admin', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
				[
					'field'       => 'group_taxonomies',
					'title'       => _x( 'Group Taxonomies', 'Modules: Tweaks: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group selected taxonomies on selected post type edit pages', 'Modules: Tweaks: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				],
			],
		];
	}

	protected function get_global_strings()
	{
		return [
			'misc' => [
				'title_column_title'   => _x( 'Title', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'rows_column_title'    => _x( 'Extra', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'atts_column_title'    => _x( 'Attributes', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'id_column_title'      => _x( 'ID', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'thumb_column_title'   => _x( 'Featured', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'order_column_title'   => _x( 'Order', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'user_column_title'    => _x( 'User', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'contacts_column_title' => _x( 'Contacts', 'Modules: Tweaks: Column Title', GEDITORIAL_TEXTDOMAIN ),
			],
			'js' => [
				'search_title'       => _x( 'Type to filter by', 'Modules: Tweaks: Meta Box Search Title', GEDITORIAL_TEXTDOMAIN ),
				'search_placeholder' => _x( 'Search &hellip;', 'Modules: Tweaks: Meta Box Search Placeholder', GEDITORIAL_TEXTDOMAIN ),
			],
		];
	}

	// FIXME:
	private function get_posttypes_support_order()
	{
		return PostType::get();
	}

	private function get_posttypes_support_thumbnail()
	{
		$supported = get_post_types_by_support( 'thumbnail' );
		$posttypes = [];
		$excludes  = [
			'attachment:audio',
			'attachment:video',
			'profile', // gPeople
		];

		foreach ( PostType::get() as $post_type => $label )
			if ( in_array( $post_type, $supported )
				&& ! in_array( $post_type, $excludes ) )
					$posttypes[$post_type] = $label;

		return $posttypes;
	}

	public function init()
	{
		parent::init();

		$this->taxonomies_excluded = [
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
			'alphabet_tax',
			'entry_section',
			'specs',
			'label',
		];
	}

	public function init_ajax()
	{
		if ( $this->is_inline_save( $_REQUEST, $this->post_types() ) )
			$this->_edit_screen( $_REQUEST['post_type'] );
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				if ( post_type_supports( $screen->post_type, 'excerpt' ) ) {
					$this->remove_meta_box( $screen->post_type, $screen->post_type, 'excerpt' );
					add_meta_box( 'postexcerpt', _x( 'Excerpt', 'Modules: Tweaks', GEDITORIAL_TEXTDOMAIN ), [ $this, 'post_excerpt_meta_box' ], $screen->post_type, 'normal' );
				}

				if ( $this->get_setting( 'checklist_tree', FALSE )
					|| $this->get_setting( 'category_search', FALSE )
					|| $this->get_setting( 'excerpt_count', FALSE ) ) {

						$this->enqueue_asset_js( [
							'settings' => $this->options->settings,
							'strings'  => $this->strings['js'],
						], $screen );

						if ( $this->get_setting( 'checklist_tree', FALSE ) )
							add_filter( 'wp_terms_checklist_args', function( $args ){
								return array_merge( $args, [ 'checked_ontop' => FALSE ] );
							} );
				}

			} else if ( 'edit' == $screen->base ) {
				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
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

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_taxonomies_for_'.$post_type.'_columns', [ $this, 'manage_taxonomies_columns' ], 10, 2 );

		add_filter( 'manage_posts_columns', [ $this, 'manage_posts_columns' ], 1, 2 );
		add_filter( 'manage_pages_columns', [ $this, 'manage_pages_columns' ], 1, 1 );
		add_action( 'manage_'.$post_type.'_posts_custom_column', [ $this, 'posts_custom_column' ], 10, 2 );
		add_filter( 'manage_edit-'.$post_type.'_sortable_columns', [ $this, 'sortable_columns' ] );

		// add_filter( 'manage_'.$post_type.'_posts_columns', [ $this, 'manage_posts_columns_late' ], 999, 1 );
		// add_filter( 'list_table_primary_column', [ $this, 'list_table_primary_column' ], 10, 2 );

		if ( in_array( $post_type, $this->get_setting( 'column_thumb', [] ) ) )
			add_thickbox();

		// INTERNAL HOOKS
		if ( $this->get_setting( 'group_taxonomies', FALSE ) )
			add_action( $this->hook( 'column_row' ), [ $this, 'column_row_taxonomies' ] );

		if ( $this->get_setting( 'author_attribute', TRUE ) && post_type_supports( $post_type, 'author' ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_author' ], 1 );

		if ( $this->get_setting( 'group_attributes', FALSE ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_default' ], 2 );

		if ( $this->get_setting( 'attachment_count', FALSE ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_attachments' ], 20 );

		if ( $this->get_setting( 'page_template', FALSE ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_page_template' ], 50 );

		if ( $this->get_setting( 'comment_status', FALSE ) && post_type_supports( $post_type, 'comments' ) )
			add_action( $this->hook( 'column_attr' ), [ $this, 'column_attr_comment_status' ], 15 );
	}

	public function manage_taxonomies_columns( $taxonomies, $post_type )
	{
		foreach ( $this->taxonomies() as $taxonomy )
			unset( $taxonomies[$taxonomy] );

		return $taxonomies;
	}

	public function manage_pages_columns( $columns )
	{
		return $this->manage_posts_columns( $columns, 'page' );
	}

	public function manage_posts_columns( $columns, $post_type )
	{
		$new   = [];
		$added = FALSE;

		$rows = has_action( $this->hook( 'column_row' ) ) ? $this->get_column_title( 'rows', $post_type ) : FALSE;
		$atts = has_action( $this->hook( 'column_attr' ) ) ? $this->get_column_title( 'atts', $post_type ) : FALSE;

		foreach ( $columns as $key => $value ) {

			if ( 'title' == $key && in_array( $post_type, $this->get_setting( 'column_thumb', [] ) ) )
				$new[$this->classs( 'thumb' )] = $this->get_column_title( 'thumb', $post_type );

			if ( ( 'comments' == $key && ! $added )
				|| ( 'date' == $key && ! $added ) ) {

					if ( $rows )
						$new['geditorial-tweaks-rows'] = $rows;

					if ( $atts )
						$new['geditorial-tweaks-atts'] = $atts;

					$added = TRUE;
			}

			if ( 'author' == $key && $atts && $this->get_setting( 'author_attribute', TRUE ) )
				continue;

			$new[$key] = $value;

			if ( 'cb' == $key && in_array( $post_type, $this->get_setting( 'column_order', [] ) ) )
				$new[$this->classs( 'order' )] = $this->get_column_title( 'order', $post_type );
		}

		if ( $this->get_setting( 'group_attributes', FALSE ) )
			unset( $new['date'] );

		if ( ! $added ) {

			if ( $rows )
				$new['geditorial-tweaks-rows'] = $rows;

			if ( $atts )
				$new['geditorial-tweaks-atts'] = $atts;
		}

		if ( $this->get_setting( 'column_id', FALSE ) )
			$new['geditorial-tweaks-id'] = $this->get_column_title( 'id', $post_type );

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

				echo '<div class="geditorial-admin-wrap-column -tweaks -rows"><ul>';
					do_action( $this->hook( 'column_row' ), $post );
				echo '</ul></div>';

			break;
			case $this->classs( 'atts' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -atts"><ul>';
					do_action( $this->hook( 'column_attr' ), $post );
				echo '</ul></div>';

			break;
			case $this->classs( 'thumb' ):

				$this->column_thumb( $post_id );

			break;
			case $this->classs( 'order' ):

				$this->column_count( $post->menu_order );

			break;
			case $this->classs( 'id' ):

				echo '<div class="geditorial-admin-wrap-column -tweaks -id">';
					echo esc_html( $post_id );
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

			} else if ( in_array( $key, [
				'name',
				'role',
				'posts',
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

			echo '<div class="geditorial-admin-wrap-column -tweaks -user"><ul>';
				do_action( $this->hook( 'column_user' ), get_userdata( $user_id ) );
			echo '</ul></div>';

			$output .= ob_get_clean();

		} else if ( $this->classs( 'contacts' ) == $column_name ) {

			ob_start();

			echo '<div class="geditorial-admin-wrap-column -tweaks -contacts"><ul>';
				do_action( $this->hook( 'column_contacts' ), get_userdata( $user_id ) );
			echo '</ul></div>';

			$output .= ob_get_clean();
		}

		return $output;
	}

	public function manage_users_sortable_columns( $columns )
	{
		return array_merge( $columns, [ $this->classs( 'contacts' ) => 'email' ] );
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
				esc_html( $user->display_name )
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
				$info['edit'] = current_user_can( $object->cap->manage_terms )
					? WordPress::getEditTaxLink( $object->name )
					: FALSE;

			$before  = '<li class="-row tweaks-tax-'.$taxonomy.'">';
			$before .= $this->get_column_icon( $info['edit'], $info['icon'], $info['title'] );

			Helper::getTermsEditRow( $post, $object, $before, '</li>' );
		}
	}

	// @SEE: [Post Type Templates in 4.7](https://make.wordpress.org/core/?p=20437)
	// @SEE: [#18375 (Post type templates)](https://core.trac.wordpress.org/ticket/18375)
	public function column_attr_page_template( $post )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		if ( ! empty( $post->page_template )
			&& 'default' != $post->page_template ) {

			if ( ! isset( $this->page_templates[$post->post_type] ) )
				$this->page_templates[$post->post_type] = array_flip( get_page_templates( $post, $post->post_type ) );

			echo '<li class="-row tweaks-page-template">';

				echo $this->get_column_icon( FALSE, 'admin-page', _x( 'Page Template', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

				if ( ! empty( $this->page_templates[$post->post_type][$post->page_template] ) )
					echo '<span title="'.esc_attr( $post->page_template ).'">'
						.esc_html( $this->page_templates[$post->post_type][$post->page_template] ).'</span>';
				else
					echo '<span>'.esc_html( $post->page_template ).'</span>';

			echo '</li>';
		}
	}

	public function column_attr_comment_status( $post )
	{
		if ( $filtered = comments_open( $post ) )
			return;

		echo '<li class="-row tweaks-page-template">';

			$link = add_query_arg( [ 'p' => $post->ID ], admin_url( 'edit-comments.php' ) );

			echo $this->get_column_icon( $link, 'welcome-comments', _x( 'Comment Status', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

			if ( 'closed' == $post->comment_status )
				$status = _x( 'Closed', 'Modules: Tweaks: Comment Status', GEDITORIAL_TEXTDOMAIN );

			else if ( 'open' == $post->comment_status && ! $filtered )
				$status = _x( 'Closed for Old Posts', 'Modules: Tweaks: Comment Status', GEDITORIAL_TEXTDOMAIN );

			else
				$status = $post->comment_status;

			printf( _x( 'Comments are %s', 'Modules: Tweaks: Comment Status', GEDITORIAL_TEXTDOMAIN ), $status );

		echo '</li>';
	}

	// FIXME: move this to attachments module
	// FIXME: maybe use: `wp_count_attachments()`
	public function column_attr_attachments( $post )
	{
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		$attachments = WordPress::getAttachments( $post->ID, '' );
		$count       = count( $attachments );
		$mime_types  = array_unique( array_map( function( $r ){
			return $r->post_mime_type;
		}, $attachments ) );

		if ( $count ) {

			echo '<li class="-row tweaks-attachment-count">';

				echo $this->get_column_icon( FALSE, 'images-alt2', _x( 'Attachments', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

				$title = sprintf( _nx( '%s Attachment', '%s Attachments', $count, 'Modules: Tweaks', GEDITORIAL_TEXTDOMAIN ), Number::format( $count ) );

				if ( current_user_can( 'upload_files' ) )
					echo HTML::tag( 'a', [
						'href'   => WordPress::getPostAttachmentsLink( $post->ID ),
						'title'  => _x( 'View the list of attachments', 'Modules: Tweaks', GEDITORIAL_TEXTDOMAIN ),
						'target' => '_blank',
					], $title );
				else
					echo $title;

				Helper::getMimeTypeEditRow( $mime_types, $post->ID, ' <span class="-mime-types">(', ')</span>' );

			echo '</li>';
		}
	}

	public function column_attr_author( $post )
	{
		if ( ! isset( $this->site_user_id ) )
			$this->site_user_id = Helper::getEditorialUserID( FALSE );

		if ( $post->post_author == $this->site_user_id )
			return;

		echo '<li class="-attr tweaks-default-atts -post-author -post-author-'.$post->post_status.'">';
			echo $this->get_column_icon( FALSE, 'admin-users', _x( 'Author', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
			echo '<span class="-author">'.WordPress::getAuthorEditHTML( $post->post_type, $post->post_author ).'</span>';
		echo '</li>';
	}

	// @SEE: `_post_states()`
	public function column_attr_default( $post )
	{
		$status = $date = '';

		if ( 'publish' === $post->post_status ) {
			$status = _x( 'Published', 'Modules: Tweaks: Attr: Status', GEDITORIAL_TEXTDOMAIN );

		} else if ( 'future' === $post->post_status ) {

			$time_diff = time() - get_post_time( 'G', TRUE, $post );

			if ( $time_diff > 0 )
				$status = '<strong class="error-message">'._x( 'Missed schedule', 'Modules: Tweaks: Attr: Status', GEDITORIAL_TEXTDOMAIN ).'</strong>';

			else
				$status = _x( 'Scheduled', 'Modules: Tweaks: Attr: Status', GEDITORIAL_TEXTDOMAIN );

		} else {
			$status = _x( 'Drafted', 'Modules: Tweaks: Attr: Status', GEDITORIAL_TEXTDOMAIN );
		}

		echo '<li class="-attr tweaks-default-atts -post-status -post-status-'.$post->post_status.'">';
			echo $this->get_column_icon( FALSE, 'post-status', _x( 'Status', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
			echo '<span class="-status" title="'.$post->post_status.'">'.$status.'</span>';
		echo '</li>';

		echo '<li class="-attr tweaks-default-atts -post-date">';
			echo $this->get_column_icon( FALSE, 'calendar-alt', _x( 'Publish Date', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
			echo Helper::getDateEditRow( $post->post_date, '-date' );
		echo '</li>';

		if ( $post->post_modified != $post->post_date
			&& current_user_can( 'edit_post', $post->ID ) ) {

			echo '<li class="-attr tweaks-default-atts -post-modified">';
				echo $this->get_column_icon( FALSE, 'edit', _x( 'Last Edit', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo Helper::getDateEditRow( $post->post_modified, '-edit' );
			echo '</li>';
		}

		if ( 'excerpt' == $GLOBALS['mode'] && $post->post_name ) {
			echo '<li class="-attr tweaks-default-atts -post-name">';
				echo $this->get_column_icon( FALSE, 'admin-links', _x( 'Post Slug', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo '<code>'.urldecode( $post->post_name ).'</code>';
			echo '</li>';
		}
	}

	public function column_user_default( $user )
	{
		if ( $user->first_name || $user->last_name ) {
			echo '<li class="-attr tweaks-user-atts -name">';
				echo $this->get_column_icon( FALSE, 'nametag', _x( 'Name', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo "$user->first_name $user->last_name";
			echo '</li>';
		}

		$role = $this->get_column_icon( FALSE, 'businessman', _x( 'Roles', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
		echo Helper::getJoined( User::getRoleList( $user ), '<li class="-attr tweaks-user-atts -roles">'.$role, '</li>' );
	}

	public function column_contacts_default( $user )
	{
		if ( $user->user_email ) {
			echo '<li class="-attr tweaks-user-contacts -email">';
				echo $this->get_column_icon( FALSE, 'email', _x( 'Email', 'Modules: Tweaks: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo HTML::mailto( $user->user_email );
			echo '</li>';
		}

		foreach( wp_get_user_contact_methods( $user ) as $method => $title ) {

			if ( ! $meta = get_user_meta( $user->ID, $method, TRUE ) )
				continue;

			if ( in_array( $method, [ 'twitter', 'facebook', 'googleplus' ] ) )
				$icon = $method;
			else if ( in_array( $method, [ 'mobile', 'phone' ] ) )
				$icon = 'phone';
			else
				$icon = 'email-alt';

			echo '<li class="-attr tweaks-user-contacts -contact-'.$method.'">';
				echo $this->get_column_icon( FALSE, $icon, $title );
				echo $this->display_meta( $meta, $method );
			echo '</li>';
		}
	}

	public function display_meta( $value, $key = NULL, $field = [] )
	{
		switch ( $key ) {
			case 'mobile': return HTML::tel( $value );
			case 'twitter': return HTML::link( '@'.$value, sprintf( 'https://twitter.com/intent/user?screen_name=%s', $value ), TRUE ); // FIXME: validate
		}

		return esc_html( $value );
	}

	// display post excerpt form fields
	public function post_excerpt_meta_box( $post )
	{
		echo '<div class="geditorial-admin-wrap-textbox geditorial-wordcount-wrap">';

			echo '<label class="screen-reader-text" for="excerpt">';
				_e( 'Excerpt' );
			echo '</label>';

			echo '<textarea rows="1" cols="40" name="excerpt" id="excerpt">';
				echo $post->post_excerpt; // textarea_escaped
			echo '</textarea>';

			echo Helper::htmlWordCount( 'excerpt', $post->post_type );

		echo '</div>';
	}
}

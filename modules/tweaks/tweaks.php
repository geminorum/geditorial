<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTweaks extends gEditorialModuleCore
{

	protected $priority_init = 14;
	private $enqueued_post = FALSE;

	public static function module()
	{
		return array(
			'name'     => 'tweaks',
			'title'    => _x( 'Tweaks', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Admin UI Enhancement', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'     => 'admin-settings',
			'frontend' => FALSE,
		);
	}

	protected function settings_help_tabs()
	{
		$tabs = gEditorialSettingsCore::settingsHelpContent( $this->module );

		$tabs[] = array(
			'id'       => 'geditorial-tweaks-category_search',
			'title'    => _x( 'Category Search', 'Tweaks Module: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>Makes it quick and easy for writers to select categories related to what they are writing. As they type in the search box, categories will be shown and hidden in real time, allowing them to easily select what is relevant to their content without having to scroll through possibly hundreds of categories.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/searchable-categories/" target="_blank">Searchable Categories</a> by <a href="http://ididntbreak.it" target="_blank">Jason Corradino</a></p></div>',
		);

		$tabs[] = array(
			'id'       => 'geditorial-tweaks-checklist_tree',
			'title'    => _x( 'Checklist Tree', 'Tweaks Module: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>If you’ve ever used categories extensively, you will have noticed that after you save a post, the checked categories appear on top of all the other ones. This can be useful if you have a lot of categories, since you don’t have to scroll.</p>
<p>Unfortunately, this behaviour has a serious side-effect: it breaks the hierarchy. If you have deeply nested categories that don’t make sense out of context, this will completely screw you over.</p>
<p>It preserves the category tree at all times. Just activate it and you’re good.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/category-checklist-tree/" target="_blank">Category Checklist Tree</a> by <a href="http://scribu.net/wordpress/category-checklist-tree" target="_blank">scribu</a></p></div>',
		);

		return $tabs;
	}

	protected function get_global_settings()
	{
		return array(
			'posttypes_option' => 'posttypes_option',
			'_general' => array(
				array(
					'field'       => 'group_attributes',
					'title'       => _x( 'Group Attributes', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group post attributes on selected post type edit pages', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'column_id',
					'title'       => _x( 'ID Column', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays ID Column on the post list table.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'attachment_count',
					'title'       => _x( 'Attachment Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays attachment summary of the post.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'author_attribute',
					'title'       => _x( 'Author Attribute', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays author name as post type attribute', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'page_template',
					'title'       => _x( 'Page Template', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays the template used for the post.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'category_search',
					'title'       => _x( 'Category Search', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Replaces the category selector to include searching categories', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'checklist_tree',
					'title'       => _x( 'Checklist Tree', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Preserves the category hierarchy on the post editing screen', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'excerpt_count',
					'title'       => _x( 'Excerpt Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display word count for excerpt textareas', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'comments_user',
					'title'       => _x( 'Comments User Column', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays a logged-in comment author\'s site display name on the comments admin', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'group_taxonomies',
					'title'       => _x( 'Group Taxonomies', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group selected taxonomies on selected post type edit pages', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'taxonomies_option' => 'taxonomies_option',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'title_column_title'          => _x( 'Title', 'Tweaks Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'rows_column_title'           => _x( 'Extra', 'Tweaks Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'atts_column_title'           => _x( 'Attributes', 'Tweaks Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'id_column_title'             => _x( 'ID', 'Tweaks Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'user_column_title'           => _x( 'User', 'Tweaks Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_search_title'       => _x( 'Type to filter by', 'Tweaks Module: Meta Box Search Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_search_placeholder' => _x( 'Search &hellip;', 'Tweaks Module: Meta Box Search Placeholder', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function init()
	{
		parent::init();

		$this->taxonomies_excluded = array(
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
		);
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
					add_meta_box( 'postexcerpt', _x( 'Excerpt', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ), array( $this, 'post_excerpt_meta_box' ), $screen->post_type, 'normal' );
				}

				if ( $this->get_setting( 'checklist_tree', FALSE )
					|| $this->get_setting( 'category_search', FALSE )
					|| $this->get_setting( 'excerpt_count', FALSE ) ) {

						$this->enqueue_asset_js( array(
							'settings' => $this->options->settings,
							'strings'  => array(
								'search_title'       => $this->get_string( 'meta_box_search_title', $screen->post_type, 'misc' ),
								'search_placeholder' => $this->get_string( 'meta_box_search_placeholder', $screen->post_type, 'misc' ),
							),
						), 'tweaks.post' );

						if ( $this->get_setting( 'checklist_tree', FALSE ) )
							add_filter( 'wp_terms_checklist_args', function( $args ){
								return array_merge( $args, array( 'checked_ontop' => FALSE ) );
							} );
				}

			} else if ( 'edit' == $screen->base ) {
				$this->_admin_enabled();
				$this->_edit_screen( $screen->post_type );
			}

		} else if ( 'edit-tags' == $screen->base ) {

			// TODO: add support for taxonomy list table

		} else if ( 'edit-comments' == $screen->base ) {

			if ( $this->get_setting( 'comments_user', FALSE ) ) {

				$this->_admin_enabled();

				add_filter( 'manage_edit-comments_columns', array( $this, 'manage_comments_columns' ) );
				add_action( 'manage_comments_custom_column', array( $this, 'comments_custom_column' ), 10, 2 );

				// TODO: add sortable for comments
			}
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_taxonomies_for_'.$post_type.'_columns', array( $this, 'manage_taxonomies_columns'), 10, 2 );

		add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns' ), 1, 2 );
		add_filter( 'manage_pages_columns', array( $this, 'manage_pages_columns' ), 1, 1 );
		add_action( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );
		add_filter( 'manage_edit-'.$post_type.'_sortable_columns', array( $this, 'sortable_columns' ) );

		// add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns_late' ), 999, 1 );
		// add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );

		// INTERNAL HOOKS
		if ( $this->get_setting( 'group_taxonomies', FALSE ) )
			add_action( $this->hook( 'column_row' ), array( $this, 'column_row_taxonomies' ) );

		if ( $this->get_setting( 'author_attribute', TRUE ) && post_type_supports( $post_type, 'author' ) )
			add_action( $this->hook( 'column_attr' ), array( $this, 'column_attr_author' ), 1 );

		if ( $this->get_setting( 'group_attributes', FALSE ) )
			add_action( $this->hook( 'column_attr' ), array( $this, 'column_attr_default' ), 2 );

		if ( $this->get_setting( 'attachment_count', FALSE ) )
			add_action( $this->hook( 'column_attr' ), array( $this, 'column_attr_attachments' ), 20 );

		if ( $this->get_setting( 'page_template', FALSE ) )
			add_action( $this->hook( 'column_attr' ), array( $this, 'column_attr_page_template' ), 50 );
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

	// FIXME: add thumbnail column if posttype supports
	public function manage_posts_columns( $columns, $post_type )
	{
		$new   = array();
		$added = FALSE;

		$rows = has_action( $this->hook( 'column_row' ) ) ? $this->get_column_title( 'rows', $post_type ) : FALSE;
		$atts = has_action( $this->hook( 'column_attr' ) ) ? $this->get_column_title( 'atts', $post_type ) : FALSE;

		foreach ( $columns as $key => $value ) {

			if ( ( 'comments' == $key && ! $added )
				|| ( 'date' == $key && ! $added ) ) {

					if ( $rows )
						$new['geditorial-tweaks-rows'] = $rows;

					if ( $atts )
						$new['geditorial-tweaks-atts'] = $atts;

					$added = TRUE;
			}

			$new[$key] = $value;
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
		$new = array();

		foreach ( $columns as $key => $value )

			if ( 'title' == $key )
				$new['geditorial-tweaks-title'] = $this->get_column_title( 'title', gEditorialWordPress::currentPostType( 'post' ) );

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
			case 'geditorial-tweaks-title' :

				// TODO: add before action
				$wp_list_table->column_title( $post );
				// TODO: add after action
				// TODO: must hook to 'the_excerpt' for before excerpt
				// echo $wp_list_table->handle_row_actions( $post, 'title', $wp_list_table->get_primary_column_name() );

			break;
			case 'geditorial-tweaks-rows' :

				echo '<div class="geditorial-admin-wrap-column -tweaks -rows"><ul>';
					do_action( $this->hook( 'column_row' ), $post );
				echo '</ul></div>';

			break;
			case 'geditorial-tweaks-atts' :

				echo '<div class="geditorial-admin-wrap-column -tweaks -atts"><ul>';
					do_action( $this->hook( 'column_attr' ), $post );
				echo '</ul></div>';

			break;
			case 'geditorial-tweaks-id' :

				echo '<div class="geditorial-admin-wrap-column -tweaks -id">';
					echo esc_html( $post_id );
				echo '</div>';

			break;
		}
	}

	public function sortable_columns( $columns )
	{
		return array_merge( $columns, array(
			'geditorial-tweaks-atts' => array( 'date', TRUE ),
			'geditorial-tweaks-id'   => array( 'ID', TRUE ),
		) );
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


		if ( $comment->user_id
			&& $user = get_userdata( $comment->user_id ) ) {

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

		$cat = array( 'icon' => 'category', 'title' => __( 'Categories' ), 'edit'  => NULL );
		$tag = array( 'icon' => 'tag', 'title' => __( 'Tags' ), 'edit'  => NULL );

		foreach ( $this->taxonomies() as $taxonomy ) {

			if ( ! in_array( $taxonomy, $taxonomies ) )
				continue;

			$object = get_taxonomy( $taxonomy );

			$info = $this->filters( 'taxonomy_info', ( $object->hierarchical ? $cat : $tag ), $object, $post->post_type );

			if ( FALSE === $info )
				continue;

			if ( is_null( $info['edit'] ) )
				$info['edit'] = current_user_can( $object->cap->manage_terms )
					? gEditorialWordPress::getEditTaxLink( $object->name )
					: FALSE;

			$before  = '<li class="-row tweaks-tax-'.$taxonomy.'">';
			$before .= $this->get_column_icon( $info['edit'], $info['icon'], $info['title'] );

			gEditorialHelper::getTermsEditRow( $post, $object, $before, '</li>' );
		}
	}

	// @SEE: [Post Type Templates in 4.7](https://make.wordpress.org/core/?p=20437)
	// @SEE: [#18375 (Post type templates)](https://core.trac.wordpress.org/ticket/18375)
	public function column_attr_page_template( $post )
	{
		if ( ! empty( $post->page_template )
			&& 'default' != $post->page_template ) {

			if ( ! isset( $this->page_templates[$post->post_type] ) )
				$this->page_templates[$post->post_type] = array_flip( get_page_templates( $post, $post->post_type ) );

			echo '<li class="-row tweaks-page-template">';

				echo $this->get_column_icon( FALSE, 'admin-page', _x( 'Page Template', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

				if ( ! empty( $this->page_templates[$post->post_type][$post->page_template] ) )
					echo '<span title="'.esc_attr( $post->page_template ).'">'
						.esc_html( $this->page_templates[$post->post_type][$post->page_template] ).'</span>';
				else
					echo '<span>'.esc_html( $post->page_template ).'</span>';

			echo '</li>';
		}
	}

	// FIXME: move this to attachments module
	// FIXME: maybe use: `wp_count_attachments()`
	public function column_attr_attachments( $post )
	{
		$attachments = gEditorialWordPress::getAttachments( $post->ID, '' );
		$count       = count( $attachments );
		$mime_types  = array_unique( array_map( function( $r ){
			return $r->post_mime_type;
		}, $attachments ) );

		if ( $count ) {

			echo '<li class="-row tweaks-attachment-count">';

				echo $this->get_column_icon( FALSE, 'images-alt2', _x( 'Attachments', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );

				$title = sprintf( _nx( '%s Attachment', '%s Attachments', $count, 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ), gEditorialNumber::format( $count ) );

				if ( current_user_can( 'upload_files' ) )
					echo gEditorialHTML::tag( 'a', array(
						'href'   => gEditorialWordPress::getPostAttachmentsLink( $post->ID ),
						'title'  => _x( 'View the list of attachments', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
						'target' => '_blank',
					), $title );
				else
					echo $title;

				gEditorialHelper::getMimeTypeEditRow( $mime_types, $post->ID, ' <span class="-mime-types">(', ')</span>' );

			echo '</li>';
		}
	}

	public function column_attr_author( $post )
	{
		if ( ! isset( $this->site_user_id ) )
			$this->site_user_id = gEditorialHelper::getEditorialUserID( FALSE );

		if ( $post->post_author == $this->site_user_id )
			return;

		echo '<li class="-attr tweaks-default-atts -post-author -post-author-'.$post->post_status.'">';
			echo $this->get_column_icon( FALSE, 'admin-users', _x( 'Author', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
			echo '<span class="-author">'.gEditorialWordPress::getAuthorEditHTML( $post->post_type, $post->post_author ).'</span>';
		echo '</li>';
	}

	// @SEE: `_post_states()`
	public function column_attr_default( $post )
	{
		$status = $date = '';

		if ( 'publish' === $post->post_status ) {
			$status = _x( 'Published', 'Tweaks Module: Attr: Status', GEDITORIAL_TEXTDOMAIN );

		} else if ( 'future' === $post->post_status ) {

			$time_diff = time() - get_post_time( 'G', TRUE, $post );

			if ( $time_diff > 0 )
				$status = '<strong class="error-message">'._x( 'Missed schedule', 'Tweaks Module: Attr: Status', GEDITORIAL_TEXTDOMAIN ).'</strong>';

			else
				$status = _x( 'Scheduled', 'Tweaks Module: Attr: Status', GEDITORIAL_TEXTDOMAIN );

		} else {
			$status = _x( 'Drafted', 'Tweaks Module: Attr: Status', GEDITORIAL_TEXTDOMAIN );
		}

		echo '<li class="-attr tweaks-default-atts -post-status -post-status-'.$post->post_status.'">';
			echo $this->get_column_icon( FALSE, 'post-status', _x( 'Status', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
			echo '<span class="-status" title="'.$post->post_status.'">'.$status.'</span>';
		echo '</li>';

		echo '<li class="-attr tweaks-default-atts -post-date">';
			echo $this->get_column_icon( FALSE, 'calendar-alt', _x( 'Publish Date', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
			echo gEditorialHelper::getDateEditRow( $post->post_date, '-date' );
		echo '</li>';

		if ( $post->post_modified != $post->post_date ) {
			echo '<li class="-attr tweaks-default-atts -post-modified">';
				echo $this->get_column_icon( FALSE, 'edit', _x( 'Last Edit', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN ) );
				echo gEditorialHelper::getDateEditRow( $post->post_modified, '-edit' );
			echo '</li>';
		}
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

			echo gEditorialHelper::htmlWordCount( 'excerpt', $post->post_type );

		echo '</div>';
	}
}

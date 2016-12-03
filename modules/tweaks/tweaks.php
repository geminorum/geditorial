<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTweaks extends gEditorialModuleCore
{

	protected $priority_init = 14;
	private $enqueued_post = FALSE;

	public static function module()
	{
		return array(
			'name'  => 'tweaks',
			'title' => _x( 'Tweaks', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Admin UI Enhancement', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-settings',
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
					'field'       => 'column_id',
					'title'       => _x( 'ID Column', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays ID Column on the post list table.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'revision_count',
					'title'       => _x( 'Revision Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays revision summary of the post.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'attachment_count',
					'title'       => _x( 'Attachment Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays attachment summary of the post.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
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
				'id_column_title'             => _x( 'ID', 'Tweaks Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_search_title'       => _x( 'Type to filter by', 'Tweaks Module: Meta Box Search Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_search_placeholder' => _x( 'Search &hellip;', 'Tweaks Module: Meta Box Search Placeholder', GEDITORIAL_TEXTDOMAIN ),
			),
			'taxonomies' => array(
				'category' => array(
					'column' => 'categories',
					'icon'   => 'category',
					'title'  => _x( 'Categories', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
				),
				'post_tag' => array(
					'column' => 'tags',
					'icon'   => 'tag',
					'title'  => _x( 'Tags', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_tweaks_init', $this->module );

		$this->do_globals();

		$this->taxonomies_excluded = array(
			'nav_menu',
			'post_format',
			'link_category',
			'bp_member_type',
			'bp_group_type',
			'bp-email-type',
			'people',
			'rel_people',
			'rel_post',
			'alphabet_tax',
			'entry_section',
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
				$this->_edit_screen( $screen->post_type );
			}

			// TODO: add support for taxonomy list table
		}
	}

	private function _edit_screen( $post_type )
	{
		add_filter( 'manage_taxonomies_for_'.$post_type.'_columns', array( $this, 'manage_taxonomies_columns'), 10, 2 );

		add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns' ), 1, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'posts_custom_column'), 10, 2 );

		// add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns_late' ), 999, 1 );
		// add_filter( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );

		// INTERNAL HOOKS
		if ( $this->get_setting( 'group_taxonomies', FALSE ) )
			add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_taxonomies' ) );

		if ( $this->get_setting( 'attachment_count', FALSE ) )
			add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_attachments' ), 20 );

		if ( $this->get_setting( 'page_template', FALSE ) )
			add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_page_template' ), 50 );

		if ( $this->get_setting( 'revision_count', FALSE ) )
			add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_revisions' ), 100 );
	}

	public function manage_taxonomies_columns( $taxonomies, $post_type )
	{
		foreach ( $this->taxonomies() as $taxonomy )
			unset( $taxonomies[$taxonomy] );

		return $taxonomies;
	}

	public function manage_posts_columns( $posts_columns, $post_type )
	{
		if ( count( $this->taxonomies() )
			&& has_action( 'geditorial_tweaks_column_row' ) ) {

			$new   = array();
			$added = FALSE;
			$title = $this->get_column_title( 'rows', $post_type );

			foreach ( $posts_columns as $key => $value ) {

				if ( ( 'comments' == $key && ! $added )
					|| ( 'date' == $key && ! $added ) ) {
						$new['geditorial-tweaks-rows'] = $title;
						$added = TRUE;
				}

				$new[$key] = $value;
			}

			if ( ! $added )
				$new['geditorial-tweaks-rows'] = $title;

			$posts_columns = $new;
		}

		if ( $this->get_setting( 'column_id', FALSE ) )
			$posts_columns['geditorial-tweaks-id'] = $this->get_column_title( 'id', $post_type );

		return $posts_columns;
	}

	// FIXME: must add to sortable too
	public function manage_posts_columns_late( $posts_columns )
	{
		$new = array();

		foreach ( $posts_columns as $key => $value )

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
					do_action( 'geditorial_tweaks_column_row', $post );
				echo '</ul></div>';

			break;
			case 'geditorial-tweaks-id' :

				echo '<div class="geditorial-admin-wrap-column -tweaks -id">';
					echo esc_html( $post_id );
				echo '</div>';

			break;
		}
	}

	public function column_row_taxonomies( $post )
	{
		$taxonomies = get_object_taxonomies( $post->post_type );

		foreach ( $this->taxonomies() as $taxonomy ) {

			if ( ! in_array( $taxonomy, $taxonomies ) )
				continue;

			$object = get_taxonomy( $taxonomy );
			$manage = current_user_can( $object->cap->manage_terms );

			$before = '<li class="-row tweaks-tax-'.$taxonomy.'">';

			if ( $icon = $this->get_string( 'icon', $taxonomy, 'taxonomies', 'tag' ) )
				$before .= gEditorialHTML::tag( ( $manage ? 'a' : 'span' ), array(
					'href'   => $manage ? gEditorialWordPress::getEditTaxLink( $taxonomy ) : FALSE,
					'title'  => $this->get_string( 'title', $taxonomy, 'taxonomies', $taxonomy ),
					'class'  => array( '-icon', ( $manage ? '-link' : '-info' ) ),
					'target' => $manage ? '_blank' : FALSE,
				), '<span class="dashicons dashicons-'.$icon.'"></span>' );

			gEditorialHelper::getTermsEditRow( $post,
				$post->post_type, $object, $before, '</li>' );
		}
	}

	// @SEE: [Post Type Templates in 4.7](https://make.wordpress.org/core/?p=20437)
	// @SEE: [#18375 (Post type templates)](https://core.trac.wordpress.org/ticket/18375)
	public function column_row_page_template( $post )
	{
		if ( ! empty( $post->page_template )
			&& 'default' != $post->page_template ) {

			if ( ! isset( $this->page_templates[$post->post_type] ) )
				$this->page_templates[$post->post_type] = array_flip( get_page_templates( $post, $post->post_type ) );

			echo '<li class="-row tweaks-page-template">';

				echo '<span class="-icon" title="'
					.esc_attr_x( 'Page Template', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN )
					.'"><span class="dashicons dashicons-admin-page"></span></span>';

				if ( ! empty( $this->page_templates[$post->post_type][$post->page_template] ) )
					echo '<span title="'.esc_attr( $post->page_template ).'">'
						.esc_html( $this->page_templates[$post->post_type][$post->page_template] ).'</span>';
				else
					echo '<span>'.esc_html( $post->page_template ).'</span>';

			echo '</li>';
		}
	}

	// FIXME: move this to revisions module
	public function column_row_revisions( $post )
	{
		if ( wp_revisions_enabled( $post ) ) {

			$revisions = wp_get_post_revisions( $post->ID, array( 'check_enabled' => FALSE ) );
			$count     = count( $revisions );
			$authors   = array_unique( array_map( function( $r ){
				return $r->post_author;
			}, $revisions ) );

			if ( $count ) {

				$edit = current_user_can( 'edit_post', key( $revisions ) );

				echo '<li class="-row tweaks-revision-count">';

					echo '<span class="-icon" title="'
						.esc_attr_x( 'Revisions', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN )
						.'"><span class="dashicons dashicons-backup"></span></span>';

					$title = sprintf( _nx( '%s Revision', '%s Revisions', $count, 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $count ) );

					if ( current_user_can( 'edit_post', key( $revisions ) ) )
						echo gEditorialHTML::tag( 'a', array(
							'href'   => get_edit_post_link( key( $revisions ) ),
							'title'  => _x( 'View the last revision', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
							'target' => '_blank',
						), $title );
					else
						echo $title;

					gEditorialHelper::getAuthorsEditRow( $authors, $post->post_type, ' <span class="-authors">(', ')</span>' );

				echo '</li>';
			}
		}
	}

	// FIXME: move this to attachments module
	public function column_row_attachments( $post )
	{
		$attachments = gEditorialWordPress::getAttachments( $post->ID, '' );
		$count       = count( $attachments );
		$mime_types  = array_unique( array_map( function( $r ){
			return $r->post_mime_type;
		}, $attachments ) );

		if ( $count ) {

			echo '<li class="-row tweaks-attachment-count">';

				echo '<span class="-icon" title="'
					.esc_attr_x( 'Attachments', 'Tweaks Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN )
					.'"><span class="dashicons dashicons-images-alt2"></span></span>';

				$title = sprintf( _nx( '%s Attachment', '%s Attachments', $count, 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $count ) );

				if ( current_user_can( 'upload_files' ) )
					echo gEditorialHTML::tag( 'a', array(
						'href'   => gEditorialWordPress::getPostAttachmentsLink( $post->ID ),
						'title'  => _x( 'View the list of attachments', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
						'target' => '_blank',
					), $title );
				else
					echo title;

				gEditorialHelper::getMimeTypeEditRow( $mime_types, $post->ID, ' <span class="-mime-types">(', ')</span>' );

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

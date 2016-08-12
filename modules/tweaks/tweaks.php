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
			'dashicon' => 'admin-settings',
		);
	}

	protected function settings_help_tabs()
	{
		$tabs = gEditorialHelper::settingsHelpContent( $this->module );

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
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
			'_general' => array(
				array(
					'field'       => 'group_taxonomies',
					'title'       => _x( 'Group Taxonomies', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group selected taxonomies on selected post type edit pages', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'revision_count',
					'title'       => _x( 'Revision Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Displays revision summary. Needs Group Taxonomies enabled.', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'category_search',
					'title'       => _x( 'Category Search', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Replaces the category selector to include searching categories', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'checklist_tree',
					'title'       => _x( 'Checklist Tree', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Preserves the category hierarchy on the post editing screen', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'excerpt_count',
					'title'       => _x( 'Excerpt Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display word count for excerpt textareas', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
			),
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'title_column_title'          => _x( 'Title', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
				'group_taxes_column_title'    => _x( 'Taxonomies', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_search_title'       => _x( 'Type to filter by', 'Tweaks Module: Meta Box Search Title', GEDITORIAL_TEXTDOMAIN ),
				'meta_box_search_placeholder' => _x( 'Search &hellip;', 'Tweaks Module: Meta Box Search Placeholder', GEDITORIAL_TEXTDOMAIN ),
			),
			'taxonomies' => array(
				'category' => array(
					'column'     => 'categories',
					'dashicon'   => 'category',
					'title_attr' => _x( 'Categories', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
				),
				'post_tag' => array(
					'column'     => 'tags',
					'dashicon'   => 'tag',
					'title_attr' => _x( 'Tags', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
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
			'bp_member_type',
			'people',
			'rel_people',
			'rel_post',
		);
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

				if ( $this->get_setting( 'group_taxonomies', FALSE ) ) {

					add_filter( 'manage_taxonomies_for_'.$screen->post_type.'_columns', array( $this, 'manage_taxonomies_columns'), 10, 2 );
					add_filter( 'manage_'.$screen->post_type.'_posts_columns', array( $this, 'manage_posts_columns' ), 1 );
					add_filter( 'manage_'.$screen->post_type.'_posts_custom_column', array( $this, 'custom_column'), 10, 2 );
				}
			}
		}
	}

	public function manage_taxonomies_columns( $taxonomies, $post_type )
	{
		foreach ( $this->taxonomies() as $taxonomy )
			unset( $taxonomies[$taxonomy] );

		return $taxonomies;
	}

	public function manage_posts_columns( $posts_columns )
	{
		if ( ! count( $this->taxonomies() ) )
			return $posts_columns;

		$new   = array();
		$type  = self::getCurrentPostType( 'post' );
		$title = $this->get_string( 'group_taxes_column_title', $type, 'misc' );
		$added = FALSE;

		foreach ( $posts_columns as $key => $value ) {

			if ( ( 'comments' == $key && ! $added )
				|| ( 'date' == $key && ! $added ) ) {
					$new['geditorial-tweaks-group_taxes'] = $title;
					$added = TRUE;
			}

			// FIXME: working but messing up others!
			// FIXME: must add to sortable too
			// if ( 'title' == $key )
			// 	$new['geditorial-tweaks-title'] = $this->get_string( 'title_column_title', $type, 'misc' );
			// else
				$new[$key] = $value;
		}

		if ( ! $added )
			$new['geditorial-tweaks-group_taxes'] = $title;

		return $new;
	}

	public function custom_column( $column_name, $post_id )
	{
		global $post, $wp_list_table;

		switch ( $column_name ) {

			// FIXME: wont work beacuse of page-title css class
			case 'geditorial-tweaks-title' :

				// TODO: add before action
				echo $wp_list_table->column_title( $post );
				// TODO: add after action
				// TODO: must hook to 'the_excerpt' for before excerpt
				echo $wp_list_table->handle_row_actions( $post, 'title', $wp_list_table->get_primary_column_name() );

			break;
			case 'geditorial-tweaks-group_taxes' :

				echo '<div class="geditorial-admin-wrap-column tweaks">';

				$taxonomies = get_object_taxonomies( $post->post_type );

				foreach ( $this->taxonomies() as $taxonomy ) {

					if ( ! in_array( $taxonomy, $taxonomies ) )
						continue;

					$before = '<div class="tweaks-row tweaks-'.$taxonomy.'">';

					if ( $dashicon = $this->get_string( 'dashicon', $taxonomy, 'taxonomies', 'tag' ) )
						$before .= self::html( 'a', array(
							'href'   => self::getEditTaxLink( $taxonomy ),
							'title'  => $this->get_string( 'title_attr', $taxonomy, 'taxonomies', $taxonomy ),
							'class'  => '-edit',
							'target' => '_blank',
						), '<span class="dashicons dashicons-'.$dashicon.'"></span>' );

					gEditorialHelper::getTermsEditRow( $post_id,
						$post->post_type, $taxonomy, $before, '</div>' );
				}

				if ( $this->get_setting( 'revision_count', FALSE )
				 	&& wp_revisions_enabled( $post ) ) {

					$revisions = wp_get_post_revisions( $post_id, array( 'check_enabled' => FALSE ) );
					$count     = count( $revisions );

					if ( $count ) {

						$edit = current_user_can( 'edit_post', $post_id );

						echo '<div class="tweaks-row tweaks-revision-count">';
							echo '<span class="dashicons dashicons-backup"></span> ';
							echo self::html( ( $edit ? 'a' : 'span' ), array(
								'href'  => $edit ? add_query_arg( array( 'revision' => key( $revisions ) ), admin_url( 'revision.php' ) ) : FALSE,
								'title' => $edit ? _x( 'View the last revision', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ) : FALSE,
							), sprintf( _nx( '%s Revision', '%s Revisions', $count, 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $count ) ) );
						echo '</div>';
					}
				}

				echo '</div>';
			break;
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

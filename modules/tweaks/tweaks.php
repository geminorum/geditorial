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

	protected function settings_help_sidebar()
	{
		return gEditorialHelper::settingsHelpLinks( 'Modules-Tweaks', _x( 'Editorial Tweaks Documentation', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	protected function settings_help_tabs()
	{
		$tabs = gEditorialHelper::settingsHelpContent( $this->module );

		$tabs[] = array(
			'id'       => 'geditorial-tweaks-checklist_tree',
			'title'    => _x( 'Checklist Tree', 'Tweaks Module: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>If you’ve ever used categories extensively, you will have noticed that after you save a post, the checked categories appear on top of all the other ones. This can be useful if you have a lot of categories, since you don’t have to scroll.</p>
<p>Unfortunately, this behaviour has a serious side-effect: it breaks the hierarchy. If you have deeply nested categories that don’t make sense out of context, this will completely screw you over.</p>
<p>It preserves the category tree at all times. Just activate it and you’re good.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/category-checklist-tree/" target="_blank">Category Checklist Tree</a> by <a href="http://scribu.net/wordpress/category-checklist-tree" target="_blank">scribu</a></p></div>',
		);

		$tabs[] = array(
			'id'       => 'geditorial-tweaks-category_search',
			'title'    => _x( 'Category Search', 'Tweaks Module: Help Tab Title', GEDITORIAL_TEXTDOMAIN ),
			'content'  => '<div class="-info"><p>Makes it quick and easy for writers to select categories related to what they are writing. As they type in the search box, categories will be shown and hidden in real time, allowing them to easily select what is relevant to their content without having to scroll through possibly hundreds of categories.</p>
<p class="-from">Adopted from: <a href="https://wordpress.org/plugins/searchable-categories/" target="_blank">Searchable Categories</a> by <a href="http://ididntbreak.it" target="_blank">Jason Corradino</a></p></div>',
		);

		return $tabs;
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'group_taxonomies',
					'title'       => _x( 'Group Taxonomies', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group selected taxonomies on selected post type edit pages', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'checklist_tree',
					'title'       => _x( 'Checklist Tree', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Preserves the category hierarchy on the post editing screen', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'category_search',
					'title'       => _x( 'Category Search', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Replaces the category selector to include searching categories', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
				array(
					'field'       => 'excerpt_count',
					'title'       => _x( 'Excerpt Count', 'Tweaks Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Display word count for excerpt textare', 'Tweaks Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => '0',
				),
			),
			'posttypes_option'  => 'posttypes_option',
			'taxonomies_option' => 'taxonomies_option',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
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

	public function admin_init()
	{
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 20, 2 );

		if ( $this->get_setting( 'group_taxonomies', FALSE ) ) {

			$post_type = self::getCurrentPostType( 'post' );

			if ( in_array( $post_type, $this->post_types() ) ) {
				add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'manage_posts_columns' ) );
				add_filter( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'custom_column'), 10, 2 );
			}
		}

		if ( $this->get_setting( 'checklist_tree', FALSE )
			|| $this->get_setting( 'category_search', FALSE ) )
				add_filter( 'wp_terms_checklist_args', array( $this, 'wp_terms_checklist_args' ) );
	}

	// TODO: check filter: "manage_taxonomies_for_{$post_type}_columns"
	// @SEE: https://make.wordpress.org/core/2012/12/11/wordpress-3-5-admin-columns-for-custom-taxonomies/
	public function manage_posts_columns( $posts_columns )
	{
		$added        = FALSE;
		$new_columns  = $exc_columns = array();
		$post_type    = self::getCurrentPostType( 'post' );
		$column_title = $this->get_string( 'group_taxes_column_title', $post_type, 'misc' );

		foreach ( $this->taxonomies() as $taxonomy )
			$exc_columns[] = $this->get_string( 'column', $taxonomy, 'taxonomies', 'taxonomy-'.$taxonomy );

		if ( ! count( $exc_columns ) )
			return $posts_columns;

		foreach ( $posts_columns as $key => $value ) {

			if ( ( 'comments' == $key && ! $added )
				|| ( 'date' == $key && ! $added ) ) {
					$new_columns['geditorial-tweaks-group_taxes'] = $column_title;
					$added = TRUE;
			}

			if ( ! in_array( $key, $exc_columns ) )
				$new_columns[$key] = $value;
		}

		if ( ! $added )
			$new_columns['geditorial-tweaks-group_taxes'] = $column_title;

		return $new_columns;
	}

	public function custom_column( $column_name, $post_id )
	{
		global $post;

		switch ( $column_name ) {
			case 'geditorial-tweaks-group_taxes' :

				echo '<div class="geditorial-admin-wrap-column tweaks">';

				$taxonomies = get_object_taxonomies( $post->post_type );

				foreach ( $this->taxonomies() as $taxonomy ) {

					if ( ! in_array( $taxonomy, $taxonomies ) )
						continue;

					$before = '<div class="tweaks-row tweaks-'.$taxonomy.'">';
					if ( $dashicon = $this->get_string( 'dashicon', $taxonomy, 'taxonomies', 'tag' ) )
						$before .= '<span class="dashicons dashicons-'.$dashicon.'" title="'.esc_attr(
							$this->get_string( 'title_attr', $taxonomy, 'taxonomies', $taxonomy )
						).'"></span> ';

					gEditorialHelper::getTermsEditRow( $post_id,
						$post->post_type, $taxonomy, $before, '</div>' );
				}

				echo '</div>';
			break;
		}
	}

	public function add_meta_boxes( $post_type, $post )
	{
		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			$this->remove_meta_box( $post_type, $post_type, 'excerpt' );
			add_meta_box( 'postexcerpt', _x( 'Excerpt', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ), array( $this, 'post_excerpt_meta_box' ), $post_type, 'normal' );
		}
	}

	// display post excerpt form fields
	public function post_excerpt_meta_box( $post )
	{
		echo '<label class="screen-reader-text" for="excerpt">';
			_e( 'Excerpt' );
		echo '</label>';

		echo '<textarea rows="1" cols="40" name="excerpt" id="excerpt">';
			echo $post->post_excerpt; // textarea_escaped
		echo '</textarea>';
	}

	public function wp_terms_checklist_args( $args )
	{
		if ( ! $this->enqueued_post )
			$this->enqueued_post = $this->enqueue_asset_js( array(
				'settings' => $this->options->settings,
				'strings'  => array(
					'search_title'       => $this->get_string( 'meta_box_search_title', 'post', 'misc' ),
					'search_placeholder' => $this->get_string( 'meta_box_search_placeholder', 'post', 'misc' ),
				),
			), 'tweaks.post' );

		return array_merge( $args, array( 'checked_ontop' => FALSE ) );
	}
}

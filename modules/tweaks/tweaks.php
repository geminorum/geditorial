<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTweaks extends gEditorialModuleCore
{

	protected $priority_init = 14;

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

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'group_taxonomies',
					'title'       => _x( 'Group Taxonomies', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Group selected taxonomies on selected post type edit pages', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
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
				'group_taxes_column_title' => _x( 'Taxonomies', 'Tweaks Module', GEDITORIAL_TEXTDOMAIN ),
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
}

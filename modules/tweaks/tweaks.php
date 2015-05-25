<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialTweaks extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'tweaks';

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Tweaks', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Admin UI Enhancement', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'heart',
			'slug'                 => 'tweaks',
			'load_frontend'        => true,

			'constants' => array(
				'tweaks_tax' => 'tweaks',
			),

			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
					'post' => 'on',
					'page' => 'off',
				),
				'settings' => array(
				),
				'taxonomies' => array(
					'category' => true,
					'post_tag' => true,
				),
			),

			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'group_taxonomies',
						'title'       => __( 'Group Default Taxonomies', GEDITORIAL_TEXTDOMAIN ),
						// 'description' => __( '', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
					array(
						'field'       => 'disable_notes',
						'title'       => __( 'Form Notes', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Removes extra notes after comment form on frontpage.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 1,
					),
					array(
						'field'       => 'widget_args',
						'title'       => __( 'Force Widget', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Force Recent Comments Widget to show only featured and non-buried comments.', GEDITORIAL_TEXTDOMAIN ),
						'default'     => 0,
					),
				),
				'post_types_option' => 'post_types_option',
				'taxonomies_option' => 'taxonomies_option',
			),
			'strings' => array(
				'misc' => array(
					'group_taxes_column_title' => __( 'Taxonomies', GEDITORIAL_TEXTDOMAIN ),
				),
				'taxonomies' => array(
					'category' => array(
						'column'     => 'categories',
						'dashicon'   => 'category',
						'title_attr' => __( 'Categories', GEDITORIAL_TEXTDOMAIN ),
					),
					'post_tag' => array(
						'column'     => 'tags',
						'dashicon'   => 'tag',
						'title_attr' => __( 'Tags', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-tweaks-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/tweaks',
				__( 'Editorial Tweaks Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/geditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_tweaks_init', $this->module );

		$this->do_filters();
		$this->register_taxonomies();
	}

	public function admin_init()
	{
		if ( $this->get_setting( 'group_taxonomies', false ) ) {

			foreach( $this->post_types() as $post_type ) {
				add_filter( "manage_{$post_type}_posts_columns", array( &$this, 'manage_posts_columns' ) );
				add_filter( "manage_{$post_type}_posts_custom_column", array( &$this, 'custom_column'), 10, 2 );
			}
		}
	}

	public function manage_posts_columns( $posts_columns )
	{
		$new_columns = array();
		$exc_columns = array();
		$added       = false;
		$post_type   = gEditorialHelper::get_current_post_type();

		foreach( $this->taxonomies() as $taxonomy )
			$exc_columns[] = $this->get_string( 'column', $taxonomy, 'taxonomies', 'taxonomy-'.$taxonomy );

		foreach( $posts_columns as $key => $value ) {

			if ( ( 'comments' == $key && ! $added )
				|| ( 'date' == $key && ! $added ) ) {
					$new_columns['geditorial-tweaks-group_taxes'] = $this->get_string( 'group_taxes_column_title', $post_type, 'misc' );
					$added = true;
			}

			if ( ! in_array( $key, $exc_columns ) )
				$new_columns[$key] = $value;
		}

		return $new_columns;
	}

	public function custom_column( $column_name, $post_id )
	{
		global $post;

		switch ( $column_name ) {
			case 'geditorial-tweaks-group_taxes' :

				echo '<div class="geditorial-admin-wrap-column tweaks">';

				$taxonomies = get_object_taxonomies( $post->post_type );

				foreach( $this->taxonomies() as $taxonomy ) {

					if ( ! in_array( $taxonomy, $taxonomies ) )
						continue;

					$before = '<div class="tweaks-row tweaks-'.$taxonomy.'">';
					if ( $dashicon = $this->get_string( 'dashicon', $taxonomy, 'taxonomies' ) )
						$before .= '<span class="dashicons dashicons-'.$dashicon.'" title="'.esc_attr(
							$this->get_string( 'title_attr', $taxonomy, 'taxonomies' )
						).'"></span> ';

					gEditorialHelper::getTermsEditRow( $post_id,
						$post->post_type, $taxonomy, $before, '</div>' );
				}

				echo '</div>';
			break;
		}
	}
}

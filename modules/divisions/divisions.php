<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialDivisions extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'divisions';
	var $meta_key    = '_ge_divisions';

	var $pre_term    = 'gXsXsE-';

    function __construct()
    {
		global $gEditorial;

		$this->module_url = $this->get_module_url( __FILE__ );
		$args = array(
			'title' => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
			'short_description' => __( 'Post Divisions Management', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Adding Post Divisions Functionality to WordPress With Custom Post Types', GEDITORIAL_TEXTDOMAIN ),
			'module_url' => $this->module_url,
			'dashicon' => 'smiley',
			'slug' => 'divisions',
			'load_frontend' => true,
			'constants' => array(
				'division_cpt' => 'division',
				'division_archives' => 'divisions',
				'division_tax' => 'division',

				'p2p_connection_name' => 'everything_to_division',

				'divisions_shortcode' => 'divisions',
				'multiple_divisions_shortcode' => 'multiple_divisions',
			),
			'default_options' => array(
				'enabled' => 'off',
				'post_types' => array(
					'post' => 'on',
					'page' => 'off',
				),
				'post_fields' => array(
					'in_divisions_title' => 'on',
					'in_divisions_order' => 'on',
					'in_divisions_desc' => 'off',
				),
				'settings' => array(
				),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field' => 'multiple',
						'title' => __( 'Multiple Divisions', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Using multiple divisions for each post.', GEDITORIAL_TEXTDOMAIN ),
						'default' => 0,
					),
					array(
						'field' => 'editor_button',
						'title' => _x( 'Editor Button', 'Divisions Editor Button', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Adding an Editor Button to insert shortcodes', GEDITORIAL_TEXTDOMAIN ),
						'default' => 1,
					),
				),
				'post_types_option' => 'post_types_option',
				'post_types_fields' => 'post_types_fields',
			),
			'strings' => array(
				'titles' => array(
					'post' => array(
						'in_divisions_title' => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
						'in_divisions_order' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
						'in_divisions_desc' => __( 'Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'descriptions' => array(
					'post' => array(
						'in_divisions_title' => __( 'In Divisions Title', GEDITORIAL_TEXTDOMAIN ),
						'in_divisions_order' => __( 'In Divisions Order', GEDITORIAL_TEXTDOMAIN ),
						'in_divisions_desc' => __( 'In Divisions Description', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'misc' => array(
					'post' => array(
						'box_title' => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
						'column_title' => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
						'select_divisions' => __( '&mdash; Choose a Divisions &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'division_cpt' => array(
						'name' => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
					),
					'division_tax' => array(
						'name' => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
						'singular_name' => __( 'Division', GEDITORIAL_TEXTDOMAIN ),
						'search_items' => __( 'Search Divisions', GEDITORIAL_TEXTDOMAIN ),
						'popular_items' => null, // to disable tag cloud on edit tag page // __( 'Popular Divisions', GEDITORIAL_TEXTDOMAIN ),
						'all_items' => __( 'All Divisions', GEDITORIAL_TEXTDOMAIN ),
						'parent_item' => __( 'Parent Division', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon' => __( 'Parent Division:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item' => __( 'Edit Division', GEDITORIAL_TEXTDOMAIN ),
						'update_item' => __( 'Update Division', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item' => __( 'Add New Division', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name' => __( 'New Division Name', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate divisions with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items' => __( 'Add or remove divisions', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used' => __( 'Choose from the most used divisions', GEDITORIAL_TEXTDOMAIN ),
						'menu_name' => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id' => 'geditorial-divisions-overview',
				'title' => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/divisions',
				__( 'Editorial Divisions Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/gEditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'p2p_init', array( &$this, 'p2p_init' ) );

		if ( is_admin() ) {
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );
		} else {

		}
	}

	public function init()
    {
		do_action( 'geditorial_divisions_init', $this->module );

		$this->do_filters();
		$this->register_post_types();
		$this->register_taxonomies();
	}

	public function register_post_types()
	{
		$post_type_support = $this->get_post_types_for_module( $this->module );

        register_post_type( $this->module->constants['division_cpt'], array(
            'labels' => $this->module->strings['labels']['division_cpt'],
            'hierarchical' => true,
			'supports' => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes' ),
            'taxonomies' => array( 'category', 'post_tag', $this->module->constants['division_tax'] ),
            'public' => true,
            'show_ui' => true,
            // 'show_in_menu' => ( 1 == count( $post_type_support ) ? 'edit.php?post_type='.$post_type_support[0] : 'index.php' ) ,
            // 'menu_position' => 5,
            'menu_icon' => 'dashicons-networking',
            'show_in_nav_menus' => false,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'has_archive' => $this->module->constants['division_archives'],
            'query_var' => $this->module->constants['division_cpt'],
            'can_export' => true,
            'rewrite' =>  array(
                'slug' => $this->module->constants['division_cpt'],
                'with_front' => false,
            ),
            // 'capabilities' => $this->options['publication_capabilities'],
			'map_meta_cap' => true,
        ) );
	}

	public function register_taxonomies()
	{
        register_taxonomy( $this->module->constants['division_tax'], $this->get_post_types_for_module( $this->module ), array(
            'labels' => $this->module->strings['labels']['division_tax'],
            'public' => true,
            'show_in_nav_menus' => false,
            // 'show_ui' => true, //current_user_can( 'update_plugins' ),
            'show_ui' => false,
            'show_admin_column' => false,
            'show_tagcloud' => false,
            'hierarchical' => false,
            'update_count_callback' => array( 'gEditorialHelper', 'update_count_callback' ),
            'rewrite' => array(
				'slug' => $this->module->constants['division_tax'],
				'hierarchical' => false,
				'with_front' => false,
			),
            'query_var' => true,
            'capabilities' => array(
                'manage_terms' => 'edit_others_posts',
                'edit_terms' => 'edit_others_posts',
                'delete_terms' => 'edit_others_posts',
                'assign_terms' => 'edit_published_posts'
            )
        ) );
    }

    public function p2p_init()
    {
		// https://github.com/scribu/wp-posts-to-posts/wiki/Connection-information
		p2p_register_connection_type( array(
            'name' => $this->module->constants['p2p_connection_name'],
            'from' => $this->get_post_types_for_module( $this->module ),
            'to' => $this->module->constants['division_cpt'],

			'sortable' => 'any',
			// 'admin_dropdown' => 'any', // temporarly

			'title' => array(
				'from' => __( 'Connected Divisions', GEDITORIAL_TEXTDOMAIN ),
                'to' => __( 'Connected Posts', GEDITORIAL_TEXTDOMAIN ),
            ),
            'from_labels' => array(
                'singular_name' => __( 'Post', GEDITORIAL_TEXTDOMAIN ),
                'search_items' => __( 'Search posts', GEDITORIAL_TEXTDOMAIN ),
                'not_found' => __( 'No posts found.', GEDITORIAL_TEXTDOMAIN ),
                'create' => __( 'Create Posts', GEDITORIAL_TEXTDOMAIN ),
            ),
            'to_labels' => array(
                'singular_name' => __( 'Division', GEDITORIAL_TEXTDOMAIN ),
                'search_items' => __( 'Search divisions', GEDITORIAL_TEXTDOMAIN ),
                'not_found' => __( 'No divisions found.', GEDITORIAL_TEXTDOMAIN ),
                'create' => __( 'Create Divisions', GEDITORIAL_TEXTDOMAIN ),
            ),
			'fields' => array(
                'visibility' => array(
                    'title' => __( 'Visibility', GEDITORIAL_TEXTDOMAIN ),
                    'type' => 'select',
                    'values' => apply_filters( 'gidea_ring_values_labels', array(
                        'public' => __( 'Public', GEDITORIAL_TEXTDOMAIN ),
                        'read' => __( 'Logged in', GEDITORIAL_TEXTDOMAIN ),
                        'edit_others_posts' => __( 'Editors', GEDITORIAL_TEXTDOMAIN ),
                    ) ),
                    'default' => 'public',

                ),

                'title' => array(
                    'title' => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
                    'type' => 'text',
                    'value' => '%s',
                ),
                // 'access' => array(
                //     'title' => 'Access',
                //     'type' => 'checkbox'
                // ),
			),

		) );
    }

	public function get_connected( $atts = array() )
	{
		$args = shortcode_atts( array(
			'visibility' => 'all',
			'compare' => 'IN',

			'title' => '',
			'title_tag' => 'h3',
			'class' => '',

			'order' => 'ASC',
			'orderby' => 'term_order, name',

			'exclude' => null,
			'before' => '',
			'after' => '',
			'context' => null,
		), $atts );


		if ( 'all' == $args['visibility'] ) {
			$args['visibility'] = array( 'public' );
			if ( current_user_can( 'edit_others_posts' ) )
				$args['visibility'][] = 'edit_others_posts';
			else if ( is_user_logged_in() )
				$args['visibility'][] = 'read';
		} else {
			$args['visibility'] == (array) $args['visibility'];
		}

		$connected = new WP_Query( array(
			'connected_type' => $this->module->constants['p2p_connection_name'],
			'connected_items' => get_queried_object(),
			'nopaging' => true,

			// TODO : drop meta query if all visible
			'connected_meta' => array( array(
				'key' => 'visibility',
				'value' => $args['visibility'],
				'compare' => $args['compare'],
			) ),
		) );

		$divisions = array();

		if ( $connected->have_posts() ) {
			global $post;
			while ( $connected->have_posts() ) {
				$connected->the_post();
				$division = array();
				ob_start();
				$division['title'] = p2p_get_meta( $post->p2p_id, 'title', true );
				if ( ! $division['title'] )
					$division['title'] = get_the_title();
				get_template_part( 'content', $args['context'] );
				$division['content'] = ob_get_clean();
				$divisions[] = $division;
			}
			wp_reset_postdata();
		}

		if ( count( $divisions ) )
			return $divisions;

		return false;
	}

	// temporarly
	public function connected( $atts = array() )
	{

		$args = shortcode_atts( array(
			'visibility' => 'all',
			'compare' => 'IN',

			'title' => '',
			'title_tag' => 'h3',
			'class' => '',

			'order' => 'ASC',
			'orderby' => 'term_order, name',

			'exclude' => null,
			'before' => '',
			'after' => '',
			'context' => null,
		), $atts );


		if ( 'all' == $args['visibility'] ) {
			$args['visibility'] = array( 'public' );
			if ( current_user_can( 'edit_others_posts' ) )
				$args['visibility'][] = 'edit_others_posts';
			else if ( is_user_logged_in() )
				$args['visibility'][] = 'read';
		} else {
			$args['visibility'] == (array) $args['visibility'];
		}

		$connected = new WP_Query( array(
			'connected_type' => $this->module->constants['p2p_connection_name'],
			'connected_items' => get_queried_object(),
			'nopaging' => true,
			//'connected_meta' => array( 'visibility' => 'strong' )

			// TODO : drop meta query if all visible
			'connected_meta' => array( array(
				'key' => 'visibility',
				'value' => $args['visibility'],
				'compare' => $args['compare'],
			) ),
		) );

		if ( $connected->have_posts() ) {
			global $post;
			echo '<section class="gtheme-p2p-connected"><ul>';
			while ( $connected->have_posts() ) {
				$connected->the_post();

				$title = p2p_get_meta( $post->p2p_id, 'title', true );
				if ( $title )
					echo '>> '.$title;

				get_template_part( 'content' );

			}

			echo '</ul></section>';
			wp_reset_postdata();
		}
	}
}

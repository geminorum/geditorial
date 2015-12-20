<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialDivisions extends gEditorialModuleCore
{
/*
	// FIXME:

	- register division cpt / division type tax

	- add support: posttypes

	- foreach division :
		- store parent as post_parent
		- store order in menu_order

	- add shortcode
		- get all divisions on display
		- seperate them by <!--nextpage-->
			[division]
			<!--nextpage-->
			[division]
			<!--nextpage-->
			[division]
			<!--nextpage-->

	- keep divitions simple
	- like attachments for supported posttypes

	- must get in on 'the_posts' filter
		- change post_content into [division] / <!--nextpage-->

*/
	public static function module()
	{
		return array(
			'name'     => 'divisions',
			'title'    => _x( 'Divisions', 'Divisions Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Post Divisions Management', 'Divisions Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'layout',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'editor_button',
			),
			'posttypes_option' => 'posttypes_option',
			'fields_option'    => 'fields_option',
		);
	}

	protected function get_global_constants()
	{
		return array(
			'division_cpt'                 => 'division',
			'division_cpt_archive'         => 'divisions',
			'division_type'                => 'division_type',
			'p2p_connection_name'          => 'everything_to_division',
			'divisions_shortcode'          => 'divisions',
			'multiple_divisions_shortcode' => 'multiple_divisions',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'post' => array(
					'in_divisions_title' => __( 'Title', GEDITORIAL_TEXTDOMAIN ),
					'in_divisions_order' => __( 'Order', GEDITORIAL_TEXTDOMAIN ),
					'in_divisions_desc'  => __( 'Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'post' => array(
					'in_divisions_title' => __( 'In Divisions Title', GEDITORIAL_TEXTDOMAIN ),
					'in_divisions_order' => __( 'In Divisions Order', GEDITORIAL_TEXTDOMAIN ),
					'in_divisions_desc'  => __( 'In Divisions Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'post' => array(
					'box_title'        => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
					'column_title'     => __( 'Divisions', GEDITORIAL_TEXTDOMAIN ),
					'select_divisions' => __( '&mdash; Choose a Divisions &mdash;', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'labels' => array(
				'division_cpt' => array(
					'name'                  => _x( 'Divisions', 'Divisions Module: Division CPT Labels: Name', GEDITORIAL_TEXTDOMAIN ),
					'menu_name'             => _x( 'Divisions', 'Divisions Module: Division CPT Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
					'singular_name'         => _x( 'Division', 'Divisions Module: Division CPT Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
					'description'           => _x( 'Division Post Type', 'Divisions Module: Division CPT Labels: Description', GEDITORIAL_TEXTDOMAIN ),
					'add_new'               => _x( 'Add New', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'add_new_item'          => _x( 'Add New Division', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'edit_item'             => _x( 'Edit Division', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'new_item'              => _x( 'New Division', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'view_item'             => _x( 'View Division', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'search_items'          => _x( 'Search Divisions', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'not_found'             => _x( 'No divisions found.', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'not_found_in_trash'    => _x( 'No divisions found in Trash.', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'all_items'             => _x( 'All Divisions', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'archives'              => _x( 'Division Archives', 'Divisions Module: Division CPT', GEDITORIAL_TEXTDOMAIN ),
					'insert_into_item'      => _x( 'Insert into division', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'uploaded_to_this_item' => _x( 'Uploaded to this division', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'filter_items_list'     => _x( 'Filter divisions list', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'items_list_navigation' => _x( 'Divisions list navigation', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
					'items_list'            => _x( 'Divisions list', 'Divisions Module: Division CPT Labels', GEDITORIAL_TEXTDOMAIN ),
				),
				'division_type' => array(
                    'name'                  => _x( 'Division Types', 'Entry Module: Division Type Tax Labels: Name', GEDITORIAL_TEXTDOMAIN ),
                    'menu_name'             => _x( 'Division Types', 'Entry Module: Division Type Tax Labels: Menu Name', GEDITORIAL_TEXTDOMAIN ),
                    'singular_name'         => _x( 'Division Type', 'Entry Module: Division Type Tax Labels: Singular Name', GEDITORIAL_TEXTDOMAIN ),
                    'search_items'          => _x( 'Search Division Types', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'all_items'             => _x( 'All Division Types', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item'           => _x( 'Parent Division Type', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'parent_item_colon'     => _x( 'Parent Division Type:', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'edit_item'             => _x( 'Edit Division Type', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'view_item'             => _x( 'View Division Type', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'update_item'           => _x( 'Update Division Type', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'add_new_item'          => _x( 'Add New Division Type', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'new_item_name'         => _x( 'New Division Type Name', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'not_found'             => _x( 'No division types found.', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'no_terms'              => _x( 'No division types', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list_navigation' => _x( 'Division Types list navigation', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
                    'items_list'            => _x( 'Division Types list', 'Entry Module: Division Type Tax Labels', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	protected function get_global_supports()
	{
		return array(
			'division_cpt' => array(
				'title',
				'editor',
				'excerpt',
				'author',
				'thumbnail',
				'trackbacks',
				'custom-fields',
				'comments',
				'revisions',
				'page-attributes',
			),
		);
	}


	protected function get_global_fields()
	{
		return array(
			$this->constant( 'post_cpt' ) => array(
				'in_divisions_title' => TRUE,
				'in_divisions_order' => TRUE,
				'in_divisions_desc'  => FALSE,
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_divisions_init', $this->module );

		$this->do_globals();

		$this->register_post_type( 'division_cpt', array(), array( 'post_tag' ) );
		$this->register_taxonomy( 'division_type', array(
			'hierarchical' => TRUE,
		) );
	}

	// FIXME: use API: $this->register_p2p( 'issue_cpt' );
	public function p2p_init()
	{
		// https://github.com/scribu/wp-posts-to-posts/wiki/Connection-information
		p2p_register_connection_type( array(
			'name' => $this->constant( 'p2p_connection_name' ),
			'from' => $this->post_types(),
			'to' => $this->constant( 'division_cpt' ),

			'sortable' => 'any',
			// 'admin_dropdown' => 'any', // temporarly

			'title' => array(
				'from' => __( 'Connected Divisions', GEDITORIAL_TEXTDOMAIN ),
				'to'   => __( 'Connected Posts', GEDITORIAL_TEXTDOMAIN ),
			),
			'from_labels' => array(
				'singular_name' => __( 'Post', GEDITORIAL_TEXTDOMAIN ),
				'search_items'  => __( 'Search posts', GEDITORIAL_TEXTDOMAIN ),
				'not_found'     => __( 'No posts found.', GEDITORIAL_TEXTDOMAIN ),
				'create'        => __( 'Create Posts', GEDITORIAL_TEXTDOMAIN ),
			),
			'to_labels' => array(
				'singular_name' => __( 'Division', GEDITORIAL_TEXTDOMAIN ),
				'search_items'  => __( 'Search divisions', GEDITORIAL_TEXTDOMAIN ),
				'not_found'     => __( 'No divisions found.', GEDITORIAL_TEXTDOMAIN ),
				'create'        => __( 'Create Divisions', GEDITORIAL_TEXTDOMAIN ),
			),
			'fields' => array(
				'visibility' => array(
					'title'  => __( 'Visibility', GEDITORIAL_TEXTDOMAIN ),
					'type'   => 'select',
					'values' => array(
						'public'            => __( 'Public', GEDITORIAL_TEXTDOMAIN ),
						'read'              => __( 'Logged in', GEDITORIAL_TEXTDOMAIN ),
						'edit_others_posts' => __( 'Editors', GEDITORIAL_TEXTDOMAIN ),
					),
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
			'compare'    => 'IN',
			'title'      => '',
			'title_tag'  => 'h3',
			'class'      => '',
			'order'      => 'ASC',
			'orderby'    => 'term_order, name',
			'exclude'    => NULL,
			'before'     => '',
			'after'      => '',
			'context'    => NULL,
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
			'connected_type'  => $this->constant( 'p2p_connection_name' ),
			'connected_items' => get_queried_object(),
			'nopaging'        => TRUE,

			// TODO : drop meta query if all visible
			'connected_meta' => array( array(
				'key'     => 'visibility',
				'value'   => $args['visibility'],
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
				$division['title'] = p2p_get_meta( $post->p2p_id, 'title', TRUE );
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

		return FALSE;
	}

	// temporarly
	public function connected( $atts = array() )
	{

		$args = shortcode_atts( array(
			'visibility' => 'all',
			'compare'    => 'IN',
			'title'      => '',
			'title_tag'  => 'h3',
			'class'      => '',
			'order'      => 'ASC',
			'orderby'    => 'term_order, name',
			'exclude'    => NULL,
			'before'     => '',
			'after'      => '',
			'context'    => NULL,
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
			'connected_type'  => $this->constant( 'p2p_connection_name' ),
			'connected_items' => get_queried_object(),
			'nopaging'        => TRUE,
			// 'connected_meta'  => array( 'visibility' => 'strong' )

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

				$title = p2p_get_meta( $post->p2p_id, 'title', TRUE );
				if ( $title )
					echo '>> '.$title;

				get_template_part( 'content' );

			}

			echo '</ul></section>';
			wp_reset_postdata();
		}
	}
}

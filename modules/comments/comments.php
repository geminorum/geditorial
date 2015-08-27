<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialComments extends gEditorialModuleCore
{

	var $module;
	var $module_name = 'comments';
	var $meta_key    = '_ge_comments';

	var $_actions = array(
		'unfeature',
		'feature',
		'unbury',
		'bury',
	);

	public function __construct()
	{
		global $gEditorial;

		$args = array(
			'title'                => __( 'Comments', GEDITORIAL_TEXTDOMAIN ),
			'short_description'    => __( 'Comment Managment Enhancements', GEDITORIAL_TEXTDOMAIN ),
			'extended_description' => __( 'Series of tools to help better managing the comments on a magazine.', GEDITORIAL_TEXTDOMAIN ),
			'dashicon'             => 'admin-comments',
			'slug'                 => 'comments',
			'load_frontend'        => TRUE,
			'constants'            => array(
				'comments_shortcode' => 'comments',
			),
			'default_options' => array(
				'enabled'    => FALSE,
				'post_types' => array(
					'post' => TRUE,
					'page' => FALSE,
				),
				'settings' => array(),
			),
			'settings' => array(
				'_general' => array(
					array(
						'field'       => 'front_actions',
						'title'       => __( 'Frontpage Actions', GEDITORIAL_TEXTDOMAIN ),
						'description' => __( 'Appends the actions to the comment text on frontpage.', GEDITORIAL_TEXTDOMAIN ),
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
			),
			'strings' => array(
				'titles' => array(
					'comments' => array(
						'feature'   => __( 'Feature', GEDITORIAL_TEXTDOMAIN ),
						'unfeature' => __( 'Unfeature', GEDITORIAL_TEXTDOMAIN ),
						'bury'      => __( 'Bury', GEDITORIAL_TEXTDOMAIN ),
						'unbury'    => __( 'Unbury', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'descriptions' => array(
					'comments' => array(
						'feature'   => __( 'Feature this comment', GEDITORIAL_TEXTDOMAIN ),
						'unfeature' => __( 'Unfeature this comment', GEDITORIAL_TEXTDOMAIN ),
						'bury'      => __( 'Bury this comment', GEDITORIAL_TEXTDOMAIN ),
						'unbury'    => __( 'Unbury this comment', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'misc' => array(
					'comments' => array(
						'box_title'       => __( 'Featured Comments', GEDITORIAL_TEXTDOMAIN ),
						'column_title'    => __( 'Comments', GEDITORIAL_TEXTDOMAIN ),
						'select_comments' => __( '&mdash; Choose a Comments &mdash;', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'labels' => array(
					'comments_tax' => array(
						'name'                       => __( 'Comments', GEDITORIAL_TEXTDOMAIN ),
						'singular_name'              => __( 'Comments', GEDITORIAL_TEXTDOMAIN ),
						'search_items'               => __( 'Search Comments', GEDITORIAL_TEXTDOMAIN ),
						'popular_items'              => null, // to disable tag cloud on edit tag page // __( 'Popular Comments', GEDITORIAL_TEXTDOMAIN ),
						'all_items'                  => __( 'All Comments', GEDITORIAL_TEXTDOMAIN ),
						'parent_item'                => __( 'Parent Comments', GEDITORIAL_TEXTDOMAIN ),
						'parent_item_colon'          => __( 'Parent Comments:', GEDITORIAL_TEXTDOMAIN ),
						'edit_item'                  => __( 'Edit Comments', GEDITORIAL_TEXTDOMAIN ),
						'update_item'                => __( 'Update Comments', GEDITORIAL_TEXTDOMAIN ),
						'add_new_item'               => __( 'Add New Comments', GEDITORIAL_TEXTDOMAIN ),
						'new_item_name'              => __( 'New Comments Name', GEDITORIAL_TEXTDOMAIN ),
						'separate_items_with_commas' => __( 'Separate comments with commas', GEDITORIAL_TEXTDOMAIN ),
						'add_or_remove_items'        => __( 'Add or remove comments', GEDITORIAL_TEXTDOMAIN ),
						'choose_from_most_used'      => __( 'Choose from the most used comments', GEDITORIAL_TEXTDOMAIN ),
						'menu_name'                  => __( 'Comments', GEDITORIAL_TEXTDOMAIN ),
					),
				),
			),
			'configure_page_cb' => 'print_configure_view',
			'settings_help_tab' => array(
				'id'      => 'geditorial-comments-overview',
				'title'   => __( 'help-tab-title', GEDITORIAL_TEXTDOMAIN ),
				'content' => __( '<p>help-tab-content</p>', GEDITORIAL_TEXTDOMAIN ),
				),
			'settings_help_sidebar' => sprintf(
				__( '<p><strong>For more information</strong>:</p><p><a href="%1$s">%2$s</a></p><p><a href="%3$s">gEditorial on GitHub</a></p>', GEDITORIAL_TEXTDOMAIN ),
				'http://geminorum.ir/wordpress/geditorial/modules/comments',
				__( 'Editorial Comments Documentations', GEDITORIAL_TEXTDOMAIN ),
				'https://github.com/geminorum/gEditorial' ),

		);

		$gEditorial->register_module( $this->module_name, $args );
	}

	public function setup()
	{
		add_action( 'init', array( &$this, 'init' ) );

		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'geditorial_settings_load', array( &$this, 'register_settings' ) );

			//add_action( 'admin_menu', array( &$this, 'add_meta_box' ) );
			//add_action( 'edit_comment', array( &$this, 'save_meta_box_postdata' ) );

			add_filter( 'comment_row_actions', array( &$this, 'comment_row_actions' ) );
			add_action( 'wp_ajax_geditorial_comments', array( &$this, 'ajax' ) );

		} else {

			require_once( GEDITORIAL_DIR.'modules/meta/templates.php' );
		}
	}

	public function init()
	{
		do_action( 'geditorial_comments_init', $this->module );

		$this->do_filters();

		if ( ! is_admin() ) {
			add_filter( 'comment_class', array( &$this, 'comment_class' ) );

			if ( $this->get_setting( 'widget_args', false ) )
				add_filter( 'widget_comments_args', array( &$this, 'widget_comments_args' ) );

			if ( $this->get_setting( 'front_actions', false ) )
				add_filter( 'comment_text', array( &$this, 'comment_text' ), 10, 3 );

			if ( ! $this->get_setting( 'disable_notes', false ) )
				add_filter( 'comment_form_defaults', array( &$this, 'comment_form_defaults' ), 12 );

			//add_shortcode( 'comments', array( &$this, 'shortcode_comments' ) );

			//add_filter( 'gtheme_comment_actions', array( &$this, 'gtheme_comment_actions' ), 10, 4 );
		}
	}

	public function admin_init()
	{
		add_action( 'admin_print_styles', array( &$this, 'admin_print_styles' ) );
	}

	public function comment_form_defaults( $defaults )
	{
		$defaults['comment_notes_after'] = '';
		return $defaults;
	}

	public function admin_print_styles()
	{
		if ( ! current_user_can( 'moderate_comments' ) )
			return;

		$screen = get_current_screen();

		if ( 'edit-comments' == $screen->base ) {
			gEditorialHelper::linkStyleSheet( GEDITORIAL_URL.'assets/css/admin.comments.css' );
			$this->enqueue_asset_js();
		}
	}

	public function add_meta_box()
	{
		add_meta_box( 'comment_meta_box',
			$this->get_string( 'box_title', 'comments', 'misc' ),
			array( &$this, 'comment_meta_box' ),
			'comment',
			'normal'
		);
	}

	public function ajax()
	{
		if ( ! isset( $_POST['do'] ) )
			die;

		// TODO : check nounce

		$action = $_POST['do'];
		if ( in_array( $action, $this->_actions ) ) {
			$comment_id = absint( $_POST['comment_id'] );
			if ( ! $comment = get_comment( $comment_id )
				|| ! current_user_can( 'edit_post', $comment->comment_post_ID ) )
					die;

			switch ( $action ) {
				case 'feature'  : add_comment_meta(    $comment_id, 'featured', '1' ); break;
				case 'unfeature': delete_comment_meta( $comment_id, 'featured'      ); break;
				case 'bury'     : add_comment_meta(    $comment_id, 'buried',   '1' ); break;
				case 'unbury'   : delete_comment_meta( $comment_id, 'buried'        ); break;
			}
		}
		die;
	}

	public function widget_comments_args( $args )
	{
		return array_merge( $args, array(
			'meta_query' => array(
				//'relation' => 'AND',
				array(
					'key' => 'featured',
					'value' => '1'
				),
				/**array(
					'key' => 'buried',
					'value' => '1',
					//'type' => 'numeric',
					'compare' => '!='
				),**/
			),
		) );
	}

	public function comment_row_actions( $actions )
	{
		global $comment;
		$actions['geditorial_comments'] = $this->get_row_actions( $comment->comment_ID );
		return $actions;
	}

	// UNFINISHED
	public function comment_text( $comment_text )
	{
		if ( is_admin()
			|| ! current_user_can( 'moderate_comments' ) )
				return $comment_text;

		global $comment;
		return $comment_text.
			'<div class="geditorial-comments comment-actions">'.
				$this->get_row_actions( $comment->comment_ID ).
			'</div>';
	}

	// Internal
	public function get_row_actions( $comment_id )
	{
		$output = '';
		$current_status = implode( ' ', $this->comment_class( array( 'geditorial-comments-row-action', 'hide-if-no-js' ) ) );
		foreach ( $this->_actions as $action )
			$output .= '<a data-do="'.$action.'" data-comment_id="'.$comment_id.'" class="'.$action.' '.$current_status.'" title="'.esc_attr( $this->get_string( $action, 'comments', 'descriptions' ) ).'">'.$this->get_string( $action, 'comments' ).'</a>';

		return $output;
	}

	// for gtheme 3
	// UNFINISHED
	public function gtheme_comment_actions( $actions, $comment, $args, $depth )
	{
		// check for cap

		$current_status = implode( ' ', $this->comment_class( array( 'geditorial-comments-row-action', 'hide-if-no-js' ) ) );
		foreach ( $this->_actions as $action )
			$actions[] = '<a data-do="'.$action.'" data-comment_id="'.$comment->comment_ID.'" class="'.$action.' '.$current_status.'" title="'.esc_attr( $this->get_string( $action, 'comments', 'descriptions' ) ).'">'.$this->get_string( $action, 'comments' ).'</a>';

		return $actions;
	}

	// UNFINISHED
	public function save_meta_box_postdata( $comment_id )
	{
		if ( ! wp_verify_nonce( $_POST['featured_comments_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		if ( ! current_user_can( 'moderate_comments', $comment_id ) )
			comment_footer_die( __( 'You are not allowed to edit comments on this post.', GEDITORIAL_TEXTDOMAIN ) );

		update_comment_meta( $comment_id, 'featured', isset( $_POST['featured'] ) ? '1' : '0' );
		update_comment_meta( $comment_id, 'buried', isset( $_POST['buried'] ) ? '1' : '0' );
	}

	// UNFINISHED
	public function comment_meta_box()
	{
		global $comment;
		$comment_id = $comment->comment_ID;
		echo '<p>';
		echo wp_nonce_field( plugin_basename( __FILE__ ), 'featured_comments_nonce' );
		echo '<input id = "featured" type="checkbox" name="featured" value="true"' . checked( true, $this->is_comment_featured( $comment_id ), false ) . '/>';
		echo ' <label for="featured">' . __( "Featured", GEDITORIAL_TEXTDOMAIN ) . '</label>&nbsp;';
		echo '<input id = "buried" type="checkbox" name="buried" value="true"' . checked( true, $this->is_comment_buried( $comment_id ), false ) . '/>';
		echo ' <label for="buried">' . __( "Buried", GEDITORIAL_TEXTDOMAIN ) . '</label>';
		echo '</p>';
	}

	public function comment_class( $classes = array() )
	{
		global $comment;

		$comment_id = $comment->comment_ID;

		if ( $this->is_comment_featured( $comment_id ) )
			$classes[] = 'featured';

		if ( $this->is_comment_buried( $comment_id ) )
			$classes [] = 'buried';

		return $classes;
	}

	public function is_comment_featured( $comment_id )
	{
		if ( '1' == get_comment_meta( $comment_id, 'featured', true ) )
			return 1;
		return 0;
	}


	public function is_comment_buried( $comment_id )
	{
		if ( '1' == get_comment_meta( $comment_id, 'buried', true ) )
			return 1;
		return 0;
	}
}

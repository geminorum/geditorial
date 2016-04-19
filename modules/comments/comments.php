<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialComments extends gEditorialModuleCore
{

	protected $actions = array(
		'unfeature',
		'feature',
		'unbury',
		'bury',
	);

	public static function module()
	{
		if ( ! self::isDev() )
			return FALSE;

		return array(
			'name'     => 'comments',
			'title'    => _x( 'Comments', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Comment Managment Enhancements', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'admin-comments',
		);
	}

	protected function settings_help_sidebar()
	{
		return gEditorialHelper::settingsHelpLinks( 'Modules-Comments', _x( 'Editorial Comments Documentation', 'Comments Module', GEDITORIAL_TEXTDOMAIN ) );
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'front_actions',
					'title'       => _x( 'Frontpage Actions', 'Comments Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Appends the actions to the comment text on frontpage.', 'Comments Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'widget_args',
					'title'       => _x( 'Force Widget', 'Comments Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Force Recent Comments Widget to show only featured and non-buried comments.', 'Comments Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	protected function get_global_constants()
	{
		return array(
			'comments_shortcode' => 'comments',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'titles' => array(
				'comments' => array(
					'feature'   => _x( 'Feature', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
					'unfeature' => _x( 'Unfeature', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
					'bury'      => _x( 'Bury', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
					'unbury'    => _x( 'Unbury', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'descriptions' => array(
				'comments' => array(
					'feature'   => _x( 'Feature this comment', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
					'unfeature' => _x( 'Unfeature this comment', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
					'bury'      => _x( 'Bury this comment', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
					'unbury'    => _x( 'Unbury this comment', 'Comments Module', GEDITORIAL_TEXTDOMAIN ),
				),
			),
			'misc' => array(
				'comments' => array(
					'box_title'    => _x( 'Featured Comments', 'Comments Module: Box Title', GEDITORIAL_TEXTDOMAIN ),
					'column_title' => _x( 'Comments', 'Comments Module: Column Title', GEDITORIAL_TEXTDOMAIN ),
				),
			),
		);
	}

	public function setup( $partials = array() )
	{
		parent::setup();

		if ( is_admin() ) {

			// add_action( 'admin_menu', array( $this, 'add_meta_box' ) );
			// add_action( 'edit_comment', array( $this, 'save_meta_box_postdata' ) );

			add_filter( 'comment_row_actions', array( $this, 'comment_row_actions' ) );
			add_action( 'wp_ajax_geditorial_comments', array( $this, 'ajax' ) );
		}
	}

	public function init()
	{
		do_action( 'geditorial_comments_init', $this->module );

		$this->do_globals();

		if ( ! is_admin() ) {
			add_filter( 'comment_class', array( $this, 'comment_class' ) );

			if ( $this->get_setting( 'widget_args', FALSE ) )
				add_filter( 'widget_comments_args', array( $this, 'widget_comments_args' ) );

			if ( $this->get_setting( 'front_actions', FALSE ) )
				add_filter( 'comment_text', array( $this, 'comment_text' ), 10, 3 );

			// add_shortcode( 'comments', array( $this, 'shortcode_comments' ) );
			// add_filter( 'gtheme_comment_actions', array( $this, 'gtheme_comment_actions' ), 10, 4 );
		}
	}

	public function admin_init()
	{
		add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
	}

	public function admin_print_styles()
	{
		if ( ! current_user_can( 'moderate_comments' ) )
			return;

		$screen = get_current_screen();

		if ( 'edit-comments' == $screen->base ) {
			gEditorialHelper::linkStyleSheetAdmin( 'comments' );
			$this->enqueue_asset_js(); // FIXME: the js not using the internal api!
		}
	}

	public function add_meta_box()
	{
		add_meta_box( 'comment_meta_box',
			$this->get_string( 'box_title', 'comments', 'misc' ),
			array( $this, 'comment_meta_box' ),
			'comment',
			'normal'
		);
	}

	public function ajax()
	{
		if ( ! isset( $_POST['do'] ) )
			die;

		// TODO: check nounce

		$action = $_POST['do'];
		if ( in_array( $action, $this->actions ) ) {
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
		foreach ( $this->actions as $action )
			$output .= '<a data-do="'.$action.'" data-comment_id="'.$comment_id.'" class="'.$action.' '.$current_status.'" title="'.esc_attr( $this->get_string( $action, 'comments', 'descriptions' ) ).'">'.$this->get_string( $action, 'comments' ).'</a>';

		return $output;
	}

	// for gtheme 3
	// UNFINISHED
	public function gtheme_comment_actions( $actions, $comment, $args, $depth )
	{
		// check for cap

		$current_status = implode( ' ', $this->comment_class( array( 'geditorial-comments-row-action', 'hide-if-no-js' ) ) );
		foreach ( $this->actions as $action )
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
		echo '<input id = "featured" type="checkbox" name="featured" value="true"' . checked( TRUE, $this->is_comment_featured( $comment_id ), FALSE ) . '/>';
		echo ' <label for="featured">' . __( "Featured", GEDITORIAL_TEXTDOMAIN ) . '</label>&nbsp;';
		echo '<input id = "buried" type="checkbox" name="buried" value="true"' . checked( TRUE, $this->is_comment_buried( $comment_id ), FALSE ) . '/>';
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
		if ( '1' == get_comment_meta( $comment_id, 'featured', TRUE ) )
			return 1;
		return 0;
	}

	public function is_comment_buried( $comment_id )
	{
		if ( '1' == get_comment_meta( $comment_id, 'buried', TRUE ) )
			return 1;
		return 0;
	}
}

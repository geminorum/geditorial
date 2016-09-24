<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialUsers extends gEditorialModuleCore
{

	protected $caps = array(
		'reports' => 'edit_others_posts',
	);

	public static function module()
	{
		return array(
			'name'  => 'users',
			'title' => _x( 'Users', 'Users Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Editorial Users', 'Users Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'admin-users',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				'calendar_type',
				'admin_restrict',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'misc' => array(
				'show_option_all' => _x( 'All authors', 'Users Module: Show Option All', GEDITORIAL_TEXTDOMAIN ),
			),
			'settings' => array(
				'posttype_stats' => _x( 'Query Stats', 'Users Module: Setting Info', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_users_init', $this->module );
		$this->do_globals();
	}

	public function current_screen( $screen )
	{
		if ( 'edit' == $screen->base ) {
			if ( in_array( $screen->post_type, $this->post_types() ) ) {

				if ( $this->get_setting( 'admin_restrict', FALSE ) )
					add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
			}
		}
	}

	public function restrict_manage_posts()
	{
		global $wp_query;

		wp_dropdown_users( array(
			'name'                    => 'author',
			'show'                    => 'display_name_with_login',
			'selected'                => isset( $wp_query->query['author'] ) ? $wp_query->query['author'] : 0,
			'show_option_all'         => $this->get_string( 'show_option_all', get_query_var( 'post_type', 'post' ), 'misc' ),
			'option_none_value'       => 0,
			'hide_if_only_one_author' => TRUE,
		) );
	}

	public function reports_settings( $sub )
	{
		if ( ! $this->cuc( 'reports' ) )
			return;

		if ( $this->module->name == $sub ) {

			// if ( ! empty( $_POST ) ) {
			// 	$this->settings_check_referer( $sub, 'reports' );
			// 	if ( isset( $_POST['posttype_stats'] ) ) {}
			// }

			// add_filter( 'geditorial_reports_messages', array( $this, 'reports_messages' ), 10, 2 );
			add_action( 'geditorial_reports_sub_'.$this->module->name, array( $this, 'reports_sub' ), 10, 2 );
		}

		add_filter( 'geditorial_reports_subs', array( $this, 'append_sub' ), 10, 2 );
	}

	public function reports_sub( $uri, $sub )
	{
		$post       = empty( $_POST[$this->module->group]['reports'] ) ? array() : $_POST[$this->module->group]['reports'];
		$user_id    = empty( $post['user_id'] ) ? gEditorialHelper::getEditorialUserID() : $post['user_id'];
		$post_type  = empty( $post['post_type'] ) ? 'post' : $post['post_type'];
		$year_month = empty( $post['year_month'] ) ? '' : $post['year_month'];

		$calendar_type = $this->get_setting( 'calendar_type', 'gregorian' );

		echo '<form class="settings-form" method="post" action="">';

			$this->settings_field_referer( $sub, 'reports' );

			echo '<h3>'._x( 'User Reports', 'Users Module', GEDITORIAL_TEXTDOMAIN ).'</h3>';
			echo '<table class="form-table">';

			echo '<tr><th scope="row">'._x( 'By PostType', 'Users Module', GEDITORIAL_TEXTDOMAIN ).'</th><td>';

			$this->do_settings_field( array(
				'type'       => 'select',
				'field'      => 'post_type',
				'values'     => gEditorialWordPress::getPostTypes(),
				'default'    => $post_type,
				'name_group' => 'reports',
			) );

			$this->do_settings_field( array(
				'type'       => 'users',
				'field'      => 'user_id',
				'none_title' => _x( 'All Users', 'Users Module', GEDITORIAL_TEXTDOMAIN ),
				'none_value' => 0,
				'default'    => $user_id,
				'name_group' => 'reports',
			) );

			$this->do_settings_field( array(
				'type'       => 'select',
				'field'      => 'year_month',
				'values'     => gEditorialHelper::getPosttypeMonths( $calendar_type, $post_type, array(), $user_id ),
				'default'    => $year_month,
				'name_group' => 'reports',
			) );

			$this->submit_button( 'posttype_stats' );

			if ( ! empty( $_POST ) && isset( $_POST['posttype_stats'] ) ) {

				$period = $year_month ? gEditorialHelper::monthFirstAndLast( $calendar_type, substr( $year_month, 0, 4 ), substr( $year_month, 4, 2 ) ) : array();
				$posts  = gEditorialWordPress::countPostsByPosttype( $post_type, $user_id, $period );

				gEditorialHTML::tableCode( $posts );
			}

			echo '</td></tr>';
			echo '</table>';
		echo '</form>';
	}
}
